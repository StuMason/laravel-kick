<?php

namespace StuMason\Kick\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use StuMason\Kick\KickServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app): array
    {
        return [
            KickServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('kick.enabled', true);
        $app['config']->set('kick.tokens', [
            'test-token-full' => ['*'],
            'test-token-logs' => ['logs:read'],
            'test-token-limited' => [], // No scopes - for testing 403 responses
        ]);
        $app['config']->set('kick.allowed_commands', [
            'about',
            'route:list',
            'cache:clear',
        ]);
    }
}
