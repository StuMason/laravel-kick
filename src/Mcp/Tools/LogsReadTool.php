<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use InvalidArgumentException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use RuntimeException;
use StuMason\Kick\Services\LogReader;

#[IsReadOnly]
#[IsIdempotent]
class LogsReadTool extends Tool
{
    protected string $name = 'kick_logs_read';

    protected string $description = 'Read entries from a Laravel log file. Supports filtering by log level (DEBUG, INFO, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY) and searching within messages.';

    public function __construct(
        protected LogReader $logReader,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $validated = $request->validate([
            'file' => 'required|string|max:255',
            'level' => 'nullable|string|in:DEBUG,INFO,WARNING,ERROR,CRITICAL,ALERT,EMERGENCY',
            'search' => 'nullable|string|max:255',
            'limit' => 'nullable|integer|min:1|max:500',
        ], [
            'file.required' => 'You must specify a log file name. Use kick_logs_list to see available files.',
            'level.in' => 'Level must be one of: DEBUG, INFO, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY',
        ]);

        $file = $validated['file'];
        $level = $validated['level'] ?? null;
        $search = $validated['search'] ?? null;
        $limit = $validated['limit'] ?? 100;

        try {
            $result = $this->logReader->read($file, $limit, 0, $search, $level);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return Response::error($e->getMessage());
        }

        $entries = $result['entries'];

        if (count($entries) === 0) {
            $filterMsg = $level ? " with level {$level}" : '';
            $filterMsg .= $search ? " matching '{$search}'" : '';

            return Response::text("No log entries found in {$file}{$filterMsg}.");
        }

        $summary = sprintf(
            "Found %d entries in %s (showing %d):\n\n",
            $result['total_lines'],
            $file,
            count($entries)
        );

        foreach (array_slice($entries, 0, 20) as $entry) {
            $summary .= sprintf("Line %d: %s\n", $entry['line'], mb_substr($entry['content'], 0, 200));
        }

        if (count($entries) > 20) {
            $summary .= sprintf("\n... and %d more entries (use structured content for full data)\n", count($entries) - 20);
        }

        return Response::make(
            Response::text($summary)
        )->withStructuredContent([
            'entries' => $entries,
            'total_lines' => $result['total_lines'],
            'has_more' => $result['has_more'],
            'file' => $file,
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'file' => $schema->string()
                ->description('The log file name to read (e.g., "laravel.log")')
                ->required(),

            'level' => $schema->string()
                ->enum(['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'])
                ->description('Filter entries by log level'),

            'search' => $schema->string()
                ->description('Search string to filter log messages'),

            'limit' => $schema->integer()
                ->description('Maximum number of entries to return (default: 100, max: 500)')
                ->default(100),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'entries' => $schema->array()->description('Log entries with line number and content')->required(),
            'total_lines' => $schema->integer()->description('Total number of matching entries')->required(),
            'has_more' => $schema->boolean()->description('Whether there are more entries available')->required(),
            'file' => $schema->string()->description('The log file that was read')->required(),
        ];
    }
}
