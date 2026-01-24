<?php

use Laravel\Mcp\Request;
use StuMason\Kick\Mcp\Tools\StatsTool;
use StuMason\Kick\Services\StatsCollector;

it('returns system stats as text and structured content', function () {
    $mockCollector = Mockery::mock(StatsCollector::class);
    $mockCollector->shouldReceive('collect')->once()->andReturn([
        'cpu' => ['cores' => 4, 'load_average' => ['1m' => 0.5, '5m' => 0.4, '15m' => 0.3]],
        'memory' => ['used_bytes' => 1024000, 'total_bytes' => 2048000, 'used_percent' => 50],
        'disk' => ['used_bytes' => 5000000, 'total_bytes' => 10000000, 'used_percent' => 50],
        'uptime' => ['system_uptime_seconds' => 3600],
    ]);

    $tool = new StatsTool($mockCollector);
    $request = Mockery::mock(Request::class);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('has correct name and description', function () {
    $mockCollector = Mockery::mock(StatsCollector::class);
    $tool = new StatsTool($mockCollector);

    expect($tool->name())->toBe('kick_stats');
    expect($tool->description())->toContain('statistics');
});

it('handles missing stats gracefully', function () {
    $mockCollector = Mockery::mock(StatsCollector::class);
    $mockCollector->shouldReceive('collect')->once()->andReturn([
        'cpu' => [],
        'memory' => ['error' => 'Unable to read memory'],
        'disk' => [],
        'uptime' => [],
    ]);

    $tool = new StatsTool($mockCollector);
    $request = Mockery::mock(Request::class);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});
