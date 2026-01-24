<?php

use Laravel\Mcp\Request;
use StuMason\Kick\Mcp\Tools\HealthTool;
use StuMason\Kick\Services\HealthChecker;

it('returns health status as text and structured content', function () {
    $mockChecker = Mockery::mock(HealthChecker::class);
    $mockChecker->shouldReceive('check')->once()->andReturn([
        'status' => 'healthy',
        'checks' => [
            'database' => ['status' => 'healthy', 'message' => 'OK', 'latency_ms' => 1.5],
            'cache' => ['status' => 'healthy', 'message' => 'OK', 'latency_ms' => 0.5],
        ],
        'timestamp' => '2026-01-24T00:00:00+00:00',
    ]);

    $tool = new HealthTool($mockChecker);
    $request = Mockery::mock(Request::class);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('has correct name and description', function () {
    $mockChecker = Mockery::mock(HealthChecker::class);
    $tool = new HealthTool($mockChecker);

    expect($tool->name())->toBe('kick_health');
    expect($tool->description())->toContain('health');
});

it('returns empty input schema', function () {
    $mockChecker = Mockery::mock(HealthChecker::class);
    $tool = new HealthTool($mockChecker);

    $schema = Mockery::mock(\Illuminate\Contracts\JsonSchema\JsonSchema::class);
    expect($tool->schema($schema))->toBe([]);
});
