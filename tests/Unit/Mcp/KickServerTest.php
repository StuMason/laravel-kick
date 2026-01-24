<?php

use StuMason\Kick\Mcp\KickServer;

it('can be instantiated', function () {
    $server = new KickServer;

    expect($server)->toBeInstanceOf(KickServer::class);
});

it('extends Laravel MCP Server', function () {
    $server = new KickServer;

    expect($server)->toBeInstanceOf(\Laravel\Mcp\Server::class);
});

it('configures expected tool classes', function () {
    // Use reflection to access protected $tools property
    $server = new KickServer;
    $reflection = new ReflectionClass($server);
    $toolsProperty = $reflection->getProperty('tools');
    $toolsProperty->setAccessible(true);
    $tools = $toolsProperty->getValue($server);

    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\HealthTool::class);
    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\StatsTool::class);
    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\LogsListTool::class);
    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\LogsReadTool::class);
    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\QueueStatusTool::class);
    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\QueueRetryTool::class);
    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\ArtisanListTool::class);
    expect($tools)->toContain(\StuMason\Kick\Mcp\Tools\ArtisanRunTool::class);
});

it('has server name configured', function () {
    $server = new KickServer;
    $reflection = new ReflectionClass($server);
    $nameProperty = $reflection->getProperty('name');
    $nameProperty->setAccessible(true);

    expect($nameProperty->getValue($server))->toBe('Laravel Kick');
});

it('has instructions configured', function () {
    $server = new KickServer;
    $reflection = new ReflectionClass($server);
    $instructionsProperty = $reflection->getProperty('instructions');
    $instructionsProperty->setAccessible(true);
    $instructions = $instructionsProperty->getValue($server);

    expect($instructions)->toContain('introspection');
    expect($instructions)->toContain('Health Checks');
    expect($instructions)->toContain('Queue Management');
});
