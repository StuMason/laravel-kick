<?php

use Laravel\Mcp\Request;
use StuMason\Kick\Mcp\Tools\LogsListTool;
use StuMason\Kick\Services\LogReader;

it('lists available log files', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $mockReader->shouldReceive('listFiles')->once()->andReturn(collect([
        ['name' => 'laravel.log', 'size' => 1024, 'modified' => time()],
        ['name' => 'laravel-2026-01-24.log', 'size' => 2048, 'modified' => time() - 86400],
    ]));

    $tool = new LogsListTool($mockReader);
    $request = Mockery::mock(Request::class);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('handles empty log directory', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $mockReader->shouldReceive('listFiles')->once()->andReturn(collect([]));

    $tool = new LogsListTool($mockReader);
    $request = Mockery::mock(Request::class);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\Response::class);
});

it('has correct name and description', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $tool = new LogsListTool($mockReader);

    expect($tool->name())->toBe('kick_logs_list');
    expect($tool->description())->toContain('log files');
});
