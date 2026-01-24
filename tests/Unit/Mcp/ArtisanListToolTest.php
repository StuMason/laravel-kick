<?php

use Laravel\Mcp\Request;
use StuMason\Kick\Mcp\Tools\ArtisanListTool;
use StuMason\Kick\Services\ArtisanRunner;

it('lists available commands', function () {
    $mockRunner = Mockery::mock(ArtisanRunner::class);
    $mockRunner->shouldReceive('listCommands')->once()->andReturn([
        ['name' => 'cache:clear', 'description' => 'Clear cache'],
        ['name' => 'config:cache', 'description' => 'Cache config'],
    ]);

    $tool = new ArtisanListTool($mockRunner);
    $request = Mockery::mock(Request::class);

    $response = $tool->handle($request);

    expect($response)->toBeInstanceOf(\Laravel\Mcp\ResponseFactory::class);
});

it('has correct name and description', function () {
    $mockRunner = Mockery::mock(ArtisanRunner::class);
    $tool = new ArtisanListTool($mockRunner);

    expect($tool->name())->toBe('kick_artisan_list');
    expect($tool->description())->toContain('artisan');
});

it('returns empty schema for inputs', function () {
    $mockRunner = Mockery::mock(ArtisanRunner::class);
    $tool = new ArtisanListTool($mockRunner);

    $schema = Mockery::mock(\Illuminate\Contracts\JsonSchema\JsonSchema::class);
    expect($tool->schema($schema))->toBe([]);
});
