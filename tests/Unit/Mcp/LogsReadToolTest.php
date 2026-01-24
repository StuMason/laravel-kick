<?php

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use StuMason\Kick\Mcp\Tools\LogsReadTool;
use StuMason\Kick\Services\LogReader;

it('reads log entries', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $mockReader->shouldReceive('read')->once()->with('laravel.log', 100, 0, null, null)->andReturn([
        'entries' => [
            ['line' => 1, 'content' => '[2026-01-24] local.ERROR: Test error'],
        ],
        'total_lines' => 1,
        'has_more' => false,
    ]);

    $tool = new LogsReadTool($mockReader);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['file' => 'laravel.log']);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('reads log entries with level filter', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $mockReader->shouldReceive('read')->once()->with('laravel.log', 100, 0, null, 'ERROR')->andReturn([
        'entries' => [
            ['line' => 1, 'content' => '[2026-01-24] local.ERROR: Test error'],
        ],
        'total_lines' => 1,
        'has_more' => false,
    ]);

    $tool = new LogsReadTool($mockReader);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['file' => 'laravel.log', 'level' => 'ERROR']);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('returns error for invalid file', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $mockReader->shouldReceive('read')->once()->andThrow(new InvalidArgumentException('Log file not found'));

    $tool = new LogsReadTool($mockReader);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['file' => 'nonexistent.log']);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
});

it('returns message when no entries found', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $mockReader->shouldReceive('read')->once()->andReturn([
        'entries' => [],
        'total_lines' => 0,
        'has_more' => false,
    ]);

    $tool = new LogsReadTool($mockReader);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['file' => 'laravel.log']);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
});

it('has correct name and description', function () {
    $mockReader = Mockery::mock(LogReader::class);
    $tool = new LogsReadTool($mockReader);

    expect($tool->name())->toBe('kick_logs_read');
    expect($tool->description())->toContain('log file');
});
