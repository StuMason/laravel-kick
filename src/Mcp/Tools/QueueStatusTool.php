<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use StuMason\Kick\Services\QueueInspector;

#[IsReadOnly]
#[IsIdempotent]
class QueueStatusTool extends Tool
{
    protected string $name = 'kick_queue_status';

    protected string $description = 'Get queue status including job counts per queue, failed job count, and connection info. Optionally list failed jobs with their details.';

    public function __construct(
        protected QueueInspector $queueInspector,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'include_failed' => 'nullable|boolean',
            'failed_limit' => 'nullable|integer|min:1|max:100',
        ]);

        $includeFailed = $validated['include_failed'] ?? false;
        $failedLimit = $validated['failed_limit'] ?? 10;

        $overview = $this->queueInspector->getOverview();

        $summary = sprintf("Queue Connection: %s\n\n", $overview['connection']);

        $summary .= "Queue Sizes:\n";
        foreach ($overview['queues'] as $name => $info) {
            $summary .= sprintf("- %s: %d jobs\n", $name, $info['size']);
        }

        $summary .= sprintf("\nFailed Jobs: %d\n", $overview['failed_count']);

        $result = [
            'overview' => $overview,
        ];

        if ($includeFailed && $overview['failed_count'] > 0) {
            $failed = ['failed_jobs' => $this->queueInspector->getFailedJobs($failedLimit)];
            $result['failed_jobs'] = $failed;

            $summary .= "\nRecent Failed Jobs:\n";
            foreach (array_slice($failed['failed_jobs'], 0, 5) as $job) {
                $summary .= sprintf(
                    "- [%s] on %s: %s\n",
                    $job['id'],
                    $job['queue'],
                    mb_substr($job['exception'], 0, 100)
                );
            }
        }

        return Response::make(
            Response::text($summary)
        )->withStructuredContent($result);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'include_failed' => $schema->boolean()
                ->description('Include list of failed jobs in the response')
                ->default(false),

            'failed_limit' => $schema->integer()
                ->description('Maximum number of failed jobs to include (default: 10, max: 100)')
                ->default(10),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'overview' => $schema->object()->description('Queue overview with connection, queues, and failed count')->required(),
            'failed_jobs' => $schema->array()->description('List of failed jobs (if include_failed is true)'),
        ];
    }
}
