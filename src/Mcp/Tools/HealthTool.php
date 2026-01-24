<?php

namespace StuMason\Kick\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use StuMason\Kick\Services\HealthChecker;

#[IsReadOnly]
#[IsIdempotent]
class HealthTool extends Tool
{
    protected string $name = 'kick_health';

    protected string $description = 'Check Laravel application health including database, cache, and storage. Redis is checked when used for session or queue (but not when cache already uses Redis). Returns status and latency for each service.';

    public function __construct(
        protected HealthChecker $healthChecker,
    ) {}

    public function handle(Request $request): Response|ResponseFactory
    {
        $result = $this->healthChecker->check();

        $summary = sprintf(
            "Application is %s.\n\n",
            strtoupper($result['status'])
        );

        foreach ($result['checks'] as $name => $check) {
            $latency = isset($check['latency_ms']) ? " ({$check['latency_ms']}ms)" : '';
            $summary .= sprintf(
                "- %s: %s%s\n",
                ucfirst($name),
                $check['status'],
                $latency
            );
        }

        return Response::make(
            Response::text($summary)
        )->withStructuredContent($result);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'status' => $schema->string()->description('Overall health status: healthy or unhealthy')->required(),
            'checks' => $schema->object()->description('Individual service check results')->required(),
            'timestamp' => $schema->string()->description('ISO 8601 timestamp')->required(),
        ];
    }
}
