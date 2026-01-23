<?php

namespace StuMason\Kick;

use Illuminate\Support\ServiceProvider;
use StuMason\Kick\Http\Middleware\Authenticate;
use StuMason\Kick\Services\ArtisanRunner;
use StuMason\Kick\Services\HealthChecker;
use StuMason\Kick\Services\LogReader;
use StuMason\Kick\Services\QueueInspector;
use StuMason\Kick\Services\StatsCollector;

class KickServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/kick.php', 'kick');

        $this->app->singleton(Kick::class, fn () => new Kick);

        $this->app->singleton(LogReader::class, fn ($app) => new LogReader(
            config('kick.logs.path', storage_path('logs')),
            config('kick.logs.allowed_extensions', ['log']),
            config('kick.logs.max_lines', 500)
        ));

        $this->app->singleton(HealthChecker::class);
        $this->app->singleton(StatsCollector::class);
        $this->app->singleton(QueueInspector::class);
        $this->app->singleton(ArtisanRunner::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerPublishing();
        $this->registerRoutes();
        $this->registerMcpRoutes();
    }

    /**
     * Register the package's publishable resources.
     */
    protected function registerPublishing(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/kick.php' => config_path('kick.php'),
            ], 'kick-config');
        }
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        if (! Kick::enabled()) {
            return;
        }

        $this->app['router']->aliasMiddleware('kick.auth', Authenticate::class);

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    /**
     * Register the MCP routes if laravel/mcp is installed.
     */
    protected function registerMcpRoutes(): void
    {
        if (! Kick::enabled()) {
            return;
        }

        if (! config('kick.mcp.enabled', true)) {
            return;
        }

        // Check if laravel/mcp is installed
        if (! class_exists(\Laravel\Mcp\Facades\Mcp::class)) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/../routes/mcp.php');
    }
}
