<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use StuMason\Kick\Services\QueueInspector;

#[IsDestructive]
class QueueRetryTool extends Tool
{
    protected string $name = 'kick_queue_retry';

    protected string $description = 'Retry failed queue jobs. Can retry a specific job by ID or all failed jobs.';

    public function __construct(
        protected QueueInspector $queueInspector,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'job_id' => 'nullable|string',
            'retry_all' => 'nullable|boolean',
        ], [
            'job_id.string' => 'Job ID must be a string (UUID).',
        ]);

        $jobId = $validated['job_id'] ?? null;
        $retryAll = $validated['retry_all'] ?? false;

        if (! $jobId && ! $retryAll) {
            return Response::error('You must specify either a job_id to retry or set retry_all to true.');
        }

        if ($jobId && $retryAll) {
            return Response::error('Cannot specify both job_id and retry_all. Use one or the other.');
        }

        if ($retryAll) {
            $result = $this->queueInspector->retryAllJobs();

            return Response::make(
                Response::text(sprintf('Retried %d failed job(s).', $result['count']))
            )->withStructuredContent($result);
        }

        $result = $this->queueInspector->retryJob($jobId);

        if (! $result['success']) {
            return Response::error($result['message']);
        }

        return Response::make(
            Response::text(sprintf('Successfully queued job %s for retry.', $jobId))
        )->withStructuredContent($result);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'job_id' => $schema->string()
                ->description('The UUID of a specific failed job to retry'),

            'retry_all' => $schema->boolean()
                ->description('Set to true to retry all failed jobs')
                ->default(false),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'success' => $schema->boolean()->description('Whether the retry was successful')->required(),
            'message' => $schema->string()->description('Result message')->required(),
            'count' => $schema->integer()->description('Number of jobs retried (only present for retry_all)'),
        ];
    }
}
