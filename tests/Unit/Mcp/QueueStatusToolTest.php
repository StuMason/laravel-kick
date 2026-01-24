<?php

use Laravel\Mcp\Request;
use StuMason\Kick\Mcp\Tools\QueueStatusTool;
use StuMason\Kick\Services\QueueInspector;

it('returns queue overview', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);
    $mockInspector->shouldReceive('getOverview')->once()->andReturn([
        'connection' => 'redis',
        'queues' => ['default' => ['size' => 5]],
        'failed_count' => 2,
    ]);

    $tool = new QueueStatusTool($mockInspector);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn([]);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('includes failed jobs when requested', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);
    $mockInspector->shouldReceive('getOverview')->once()->andReturn([
        'connection' => 'redis',
        'queues' => ['default' => ['size' => 5]],
        'failed_count' => 2,
    ]);
    $mockInspector->shouldReceive('getFailedJobs')->once()->with(10)->andReturn([
        ['id' => '123', 'queue' => 'default', 'exception' => 'Error occurred'],
    ]);

    $tool = new QueueStatusTool($mockInspector);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['include_failed' => true]);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('surfaces errors from queue inspector', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);
    $mockInspector->shouldReceive('getOverview')->once()->andReturn([
        'connection' => 'redis',
        'queues' => ['default' => ['size' => 0, 'error' => 'Unable to read queue size']],
        'failed_count' => 0,
        'errors' => ['Queue \'default\' is unreachable'],
    ]);

    $tool = new QueueStatusTool($mockInspector);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn([]);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('has correct name and description', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);
    $tool = new QueueStatusTool($mockInspector);

    expect($tool->name())->toBe('kick_queue_status');
    expect($tool->description())->toContain('queue');
});
