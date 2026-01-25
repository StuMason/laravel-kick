---
title: Configuration Reference
description: Full configuration options for Laravel Kick
---

## Full Config File

```php
// config/kick.php
return [
    // Master switch
    'enabled' => env('KICK_ENABLED', false),

    // URL prefix for all routes
    'prefix' => env('KICK_PREFIX', 'kick'),

    // Token => scopes mapping
    'tokens' => [
        env('KICK_TOKEN') => ['*'],
    ],

    // Commands that can be executed via artisan endpoint
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

    // Rate limits per minute
    'rate_limits' => [
        'default' => 60,
        'artisan' => 10,
    ],

    // Log reader settings
    'logs' => [
        'path' => storage_path('logs'),
        'allowed_extensions' => ['log'],
        'max_lines' => 500,
    ],

    // MCP server
    'mcp' => [
        'enabled' => env('KICK_MCP_ENABLED', true),
    ],

    // PII scrubber
    'scrubber' => [
        'enabled' => env('KICK_SCRUBBER_ENABLED', true),
        'replacement' => '[REDACTED]',
        // 'patterns' => [
        //     'custom_id' => '/CUST-[0-9]{8}/',
        // ],
    ],
];
```

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `KICK_ENABLED` | `false` | Enable Kick |
| `KICK_TOKEN` | - | Primary auth token |
| `KICK_PREFIX` | `kick` | Route prefix |
| `KICK_MCP_ENABLED` | `true` | Enable MCP server |
| `KICK_SCRUBBER_ENABLED` | `true` | Enable PII scrubbing |

## Adding Custom Commands

Add to the `allowed_commands` array:

```php
'allowed_commands' => [
    // ... defaults
    'my:custom-command',
    'app:some-task',
],
```

## Custom PII Patterns

Add regex patterns to scrub additional data:

```php
'scrubber' => [
    'enabled' => true,
    'patterns' => [
        'customer_id' => '/CUST-[0-9]{8}/',
        'order_ref' => '/ORD-[A-Z]{2}-[0-9]+/',
    ],
],
```
