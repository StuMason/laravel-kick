<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use StuMason\Kick\Services\StatsCollector;

#[IsReadOnly]
#[IsIdempotent]
class StatsTool extends Tool
{
    protected string $name = 'kick_stats';

    protected string $description = 'Get system/container statistics including CPU load, memory usage, disk space, and uptime. Container-aware using cgroups v1/v2 when available.';

    public function __construct(
        protected StatsCollector $statsCollector,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $stats = $this->statsCollector->collect();

        $summary = "System Statistics:\n\n";

        // CPU
        if (isset($stats['cpu']['load_average'])) {
            $load = $stats['cpu']['load_average'];
            $summary .= sprintf("CPU Load: %.2f / %.2f / %.2f (1m/5m/15m)\n", $load['1m'], $load['5m'], $load['15m']);
        }
        if (isset($stats['cpu']['cores'])) {
            $summary .= sprintf("CPU Cores: %s\n", $stats['cpu']['cores']);
        }

        // Memory
        if (isset($stats['memory']['used_percent'])) {
            $summary .= sprintf(
                "Memory: %.1f%% used (%s / %s)\n",
                $stats['memory']['used_percent'],
                $this->formatBytes($stats['memory']['used_bytes'] ?? 0),
                $this->formatBytes($stats['memory']['total_bytes'] ?? 0)
            );
        } elseif (isset($stats['memory']['error'])) {
            $summary .= "Memory: {$stats['memory']['error']}\n";
        }

        // Disk
        if (isset($stats['disk']['used_percent'])) {
            $summary .= sprintf(
                "Disk: %.1f%% used (%s / %s)\n",
                $stats['disk']['used_percent'],
                $this->formatBytes($stats['disk']['used_bytes'] ?? 0),
                $this->formatBytes($stats['disk']['total_bytes'] ?? 0)
            );
        }

        // Uptime
        if (isset($stats['uptime']['system_uptime_seconds'])) {
            $summary .= sprintf("System Uptime: %s\n", $this->formatUptime($stats['uptime']['system_uptime_seconds']));
        }

        return Response::make(
            Response::text($summary)
        )->withStructuredContent([
            'stats' => $stats,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        return round($bytes / (1024 ** $pow), 2).' '.$units[$pow];
    }

    protected function formatUptime(int $seconds): string
    {
        $days = floor($seconds / 86400);
        $hours = floor(($seconds % 86400) / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        if ($days > 0) {
            return sprintf('%dd %dh %dm', $days, $hours, $minutes);
        }
        if ($hours > 0) {
            return sprintf('%dh %dm', $hours, $minutes);
        }

        return sprintf('%dm', $minutes);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'stats' => $schema->object()->description('System statistics including cpu, memory, disk, and uptime')->required(),
            'timestamp' => $schema->string()->description('ISO 8601 timestamp')->required(),
        ];
    }
}
