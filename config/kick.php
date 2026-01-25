<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kick Enabled
    |--------------------------------------------------------------------------
    |
    | This option controls whether Kick endpoints are accessible. Set to false
    | to completely disable all Kick functionality.
    |
    */

    'enabled' => env('KICK_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix
    |--------------------------------------------------------------------------
    |
    | The URI prefix for all Kick routes. Change this if you need to avoid
    | conflicts with existing routes in your application.
    |
    */

    'prefix' => env('KICK_PREFIX', 'kick'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Tokens
    |--------------------------------------------------------------------------
    |
    | Define tokens and their allowed scopes. Each token maps to an array of
    | scopes that determine what actions are permitted. Use '*' for full access.
    |
    | Available scopes:
    | - logs:read       - List and read log files
    | - stats:read      - View container/system statistics
    | - health:read     - Check application health status
    | - queue:read      - View queue job counts and failed jobs
    | - queue:retry     - Retry failed queue jobs
    | - artisan:list    - List available artisan commands
    | - artisan:execute - Execute whitelisted artisan commands
    |
    */

    'tokens' => [
        env('KICK_TOKEN') => ['*'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Artisan Commands
    |--------------------------------------------------------------------------
    |
    | Only commands listed here can be executed via the artisan endpoint.
    | This whitelist prevents arbitrary command execution.
    |
    */

    'allowed_commands' => [
        'about',
        'route:list',
        'migrate:status',
        'queue:monitor',
        'schedule:list',
        'cache:clear',
        'config:cache',
        'config:clear',
        'route:cache',
        'route:clear',
        'view:cache',
        'view:clear',
        'queue:retry',
        'queue:restart',
        'horizon:status',
        'horizon:pause',
        'horizon:continue',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Configure rate limits per minute for different endpoint groups.
    |
    */

    'rate_limits' => [
        'default' => 60,
        'artisan' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Reader Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the log reader service behavior.
    |
    */

    'logs' => [
        'path' => storage_path('logs'),
        'allowed_extensions' => ['log'],
        'max_lines' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | MCP Server
    |--------------------------------------------------------------------------
    |
    | Enable or disable the MCP (Model Context Protocol) server integration.
    |
    */

    'mcp' => [
        'enabled' => env('KICK_MCP_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | PII Scrubber
    |--------------------------------------------------------------------------
    |
    | The PII scrubber automatically redacts sensitive information from log
    | entries and queue exception messages before they are returned via API
    | or MCP. This helps prevent accidental exposure of personal data.
    |
    | Patterns detected by default:
    | - Email addresses
    | - IP addresses (IPv4 and IPv6)
    | - Phone numbers
    | - Credit card numbers
    | - Social Security Numbers (US)
    | - API keys and tokens
    | - Bearer tokens
    | - JWT tokens
    | - Password fields in key=value format
    |
    */

    'scrubber' => [
        'enabled' => env('KICK_SCRUBBER_ENABLED', true),

        // Default replacement text for patterns without specific replacements
        'replacement' => '[REDACTED]',

        // Add custom regex patterns here (merged with defaults)
        // 'patterns' => [
        //     'custom_id' => '/CUST-[0-9]{8}/',
        // ],
    ],
];
