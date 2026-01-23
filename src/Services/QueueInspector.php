<?php

namespace StuMason\Kick\Services;

use Exception;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue as QueueFacade;

class QueueInspector
{
    public function __construct(
        protected ?FailedJobProviderInterface $failedJobProvider = null
    ) {
        $this->failedJobProvider = $failedJobProvider ?? app('queue.failer');
    }

    /**
     * Get an overview of all queues.
     *
     * @return array{connection: string, queues: array<string, array{size: int}>, failed_count: int}
     */
    public function getOverview(): array
    {
        $connection = config('queue.default');
        $queues = $this->getConfiguredQueues();

        $queueStats = [];
        foreach ($queues as $queue) {
            $queueStats[$queue] = [
                'size' => $this->getQueueSize($queue),
            ];
        }

        return [
            'connection' => $connection,
            'queues' => $queueStats,
            'failed_count' => $this->getFailedJobCount(),
        ];
    }

    /**
     * Get the size of a specific queue.
     */
    public function getQueueSize(string $queue): int
    {
        try {
            /** @var Queue $queueConnection */
            $queueConnection = QueueFacade::connection();

            return $queueConnection->size($queue);
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Get the count of failed jobs.
     */
    public function getFailedJobCount(): int
    {
        try {
            $failed = $this->failedJobProvider->all();

            return count($failed);
        } catch (Exception) {
            return 0;
        }
    }

    /**
     * Get list of failed jobs.
     *
     * @return array<int, array{id: mixed, connection: string, queue: string, failed_at: string, exception: string}>
     */
    public function getFailedJobs(int $limit = 50): array
    {
        try {
            $failed = $this->failedJobProvider->all();

            return collect($failed)
                ->take($limit)
                ->map(fn ($job) => [
                    'id' => $job->id,
                    'connection' => $job->connection,
                    'queue' => $job->queue,
                    'failed_at' => $job->failed_at,
                    'exception' => $this->truncateException($job->exception ?? ''),
                ])
                ->values()
                ->all();
        } catch (Exception) {
            return [];
        }
    }

    /**
     * Retry a specific failed job.
     *
     * @return array{success: bool, message: string}
     */
    public function retryJob(string $id): array
    {
        try {
            $exitCode = Artisan::call('queue:retry', ['id' => [$id]]);

            if ($exitCode === 0) {
                return [
                    'success' => true,
                    'message' => "Job {$id} has been pushed back onto the queue.",
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to retry job. It may not exist or has already been retried.',
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retry job: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Retry all failed jobs.
     *
     * @return array{success: bool, message: string, count: int}
     */
    public function retryAllJobs(): array
    {
        try {
            $count = $this->getFailedJobCount();

            if ($count === 0) {
                return [
                    'success' => true,
                    'message' => 'No failed jobs to retry.',
                    'count' => 0,
                ];
            }

            $exitCode = Artisan::call('queue:retry', ['id' => ['all']]);

            if ($exitCode === 0) {
                return [
                    'success' => true,
                    'message' => "All {$count} failed jobs have been pushed back onto the queue.",
                    'count' => $count,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to retry jobs.',
                'count' => 0,
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retry jobs: '.$e->getMessage(),
                'count' => 0,
            ];
        }
    }

    /**
     * Get list of configured queues.
     *
     * @return array<string>
     */
    protected function getConfiguredQueues(): array
    {
        $connection = config('queue.default');
        $connectionConfig = config("queue.connections.{$connection}", []);

        // Default queue from connection config
        $defaultQueue = $connectionConfig['queue'] ?? 'default';

        // Common Laravel queue names - add the default
        $queues = [$defaultQueue];

        // Check for Horizon queues if available
        if (config('horizon.defaults')) {
            foreach (config('horizon.defaults', []) as $supervisor) {
                if (isset($supervisor['queue'])) {
                    $supervisorQueues = is_array($supervisor['queue'])
                        ? $supervisor['queue']
                        : explode(',', $supervisor['queue']);
                    $queues = array_merge($queues, $supervisorQueues);
                }
            }
        }

        // Check environment-specific Horizon config
        $environments = config('horizon.environments', []);
        foreach ($environments as $envConfig) {
            foreach ($envConfig as $supervisor) {
                if (isset($supervisor['queue'])) {
                    $supervisorQueues = is_array($supervisor['queue'])
                        ? $supervisor['queue']
                        : explode(',', $supervisor['queue']);
                    $queues = array_merge($queues, $supervisorQueues);
                }
            }
        }

        return array_values(array_unique($queues));
    }

    /**
     * Truncate exception message for readability.
     */
    protected function truncateException(string $exception, int $maxLength = 500): string
    {
        if (strlen($exception) <= $maxLength) {
            return $exception;
        }

        return substr($exception, 0, $maxLength).'...';
    }
}
