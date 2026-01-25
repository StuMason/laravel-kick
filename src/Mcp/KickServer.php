<?php

namespace StuMason\Kick\Mcp;

use Composer\InstalledVersions;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Contracts\Transport;
use StuMason\Kick\Mcp\Tools\ArtisanListTool;
use StuMason\Kick\Mcp\Tools\ArtisanRunTool;
use StuMason\Kick\Mcp\Tools\HealthTool;
use StuMason\Kick\Mcp\Tools\LogsListTool;
use StuMason\Kick\Mcp\Tools\LogsReadTool;
use StuMason\Kick\Mcp\Tools\QueueRetryTool;
use StuMason\Kick\Mcp\Tools\QueueStatusTool;
use StuMason\Kick\Mcp\Tools\StatsTool;

class KickServer extends Server
{
    protected string $name = 'Laravel Kick';

    protected string $version = '';

    public function __construct(Transport $transport)
    {
        parent::__construct($transport);
        $this->version = InstalledVersions::getPrettyVersion('stumason/laravel-kick') ?? 'dev';
    }

    protected string $instructions = <<<'INSTRUCTIONS'
Laravel Kick provides secure introspection and control for Laravel applications.

Available capabilities:
- **Health Checks**: Check database, cache, storage, and Redis connectivity
- **System Stats**: View CPU, memory, disk usage and uptime (container-aware)
- **Log Management**: List and read Laravel log files with filtering
- **Queue Management**: Monitor queue depths, view failed jobs, retry jobs
- **Artisan Commands**: List and execute whitelisted artisan commands

Use these tools to monitor application health, debug issues, and perform common operations.
INSTRUCTIONS;

    protected array $tools = [
        HealthTool::class,
        StatsTool::class,
        LogsListTool::class,
        LogsReadTool::class,
        QueueStatusTool::class,
        QueueRetryTool::class,
        ArtisanListTool::class,
        ArtisanRunTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
