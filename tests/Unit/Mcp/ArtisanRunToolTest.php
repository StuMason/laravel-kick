<?php

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use StuMason\Kick\Exceptions\CommandNotAllowedException;
use StuMason\Kick\Mcp\Tools\ArtisanRunTool;
use StuMason\Kick\Services\ArtisanRunner;

it('executes an allowed command', function () {
    $mockRunner = Mockery::mock(ArtisanRunner::class);
    $mockRunner->shouldReceive('run')->once()->with('cache:clear', [])->andReturn([
        'success' => true,
        'command' => 'cache:clear',
        'output' => 'Cache cleared!',
        'exit_code' => 0,
    ]);

    $tool = new ArtisanRunTool($mockRunner);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['command' => 'cache:clear']);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('returns error for disallowed command', function () {
    $mockRunner = Mockery::mock(ArtisanRunner::class);
    $mockRunner->shouldReceive('run')->once()->andThrow(new CommandNotAllowedException('migrate'));
    $mockRunner->shouldReceive('listCommands')->once()->andReturn([
        ['name' => 'cache:clear', 'description' => 'Clear cache'],
    ]);

    $tool = new ArtisanRunTool($mockRunner);
    $request = Mockery::mock(Request::class);
    $request->shouldReceive('validate')->andReturn(['command' => 'migrate']);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(Response::class);
});

it('has correct name and description', function () {
    $mockRunner = Mockery::mock(ArtisanRunner::class);
    $tool = new ArtisanRunTool($mockRunner);

    expect($tool->name())->toBe('kick_artisan_run');
    expect($tool->description())->toContain('artisan');
});
