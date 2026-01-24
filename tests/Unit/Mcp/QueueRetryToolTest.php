<?php

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use StuMason\Kick\Mcp\Tools\QueueRetryTool;
use StuMason\Kick\Services\QueueInspector;

it('retries a specific job', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);
    $mockInspector->shouldReceive('retryJob')->once()->with('abc-123')->andReturn([
        'success' => true,
        'message' => 'Job abc-123 has been pushed back onto the queue.',
    ]);

    $tool = new QueueRetryTool($mockInspector);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['job_id' => 'abc-123']);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('retries all failed jobs', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);
    $mockInspector->shouldReceive('retryAllJobs')->once()->andReturn([
        'success' => true,
        'message' => 'All 5 failed jobs have been pushed back onto the queue.',
        'count' => 5,
    ]);

    $tool = new QueueRetryTool($mockInspector);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['retry_all' => true]);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('returns error when neither job_id nor retry_all provided', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);

    $tool = new QueueRetryTool($mockInspector);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn([]);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
});

it('returns error when both job_id and retry_all provided', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);

    $tool = new QueueRetryTool($mockInspector);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['job_id' => 'abc', 'retry_all' => true]);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
});

it('has correct name and description', function () {
    $mockInspector = Mockery::mock(QueueInspector::class);
    $tool = new QueueRetryTool($mockInspector);

    expect($tool->name())->toBe('kick_queue_retry');
    expect($tool->description())->toContain('Retry');
});
