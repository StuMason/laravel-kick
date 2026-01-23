<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use StuMason\Kick\Services\LogReader;

#[IsReadOnly]
#[IsIdempotent]
class LogsListTool extends Tool
{
    protected string $name = 'kick_logs_list';

    protected string $description = 'List available Laravel log files with their sizes and last modified timestamps.';

    public function __construct(
        protected LogReader $logReader,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $files = $this->logReader->listFiles()->toArray();

        if (count($files) === 0) {
            return Response::text('No log files found.');
        }

        $summary = sprintf("Found %d log file(s):\n\n", count($files));

        foreach ($files as $file) {
            $size = $this->formatBytes($file['size']);
            $modified = date('Y-m-d H:i:s', $file['modified']);
            $summary .= sprintf("- %s (%s, modified %s)\n", $file['name'], $size, $modified);
        }

        return Response::make(
            Response::text($summary)
        )->withStructuredContent(['files' => $files]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'files' => $schema->array()->description('List of log files with name, size, and modified timestamp')->required(),
        ];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2).' '.$units[$pow];
    }
}
