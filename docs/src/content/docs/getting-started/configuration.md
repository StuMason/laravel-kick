---
title: Configuration
description: Configuring Laravel Kick
---

## Environment Variables

| Variable | Default | Description |
|----------|---------|-------------|
| `KICK_ENABLED` | `false` | Enable/disable all Kick functionality |
| `KICK_TOKEN` | - | Primary authentication token |
| `KICK_PREFIX` | `kick` | URL prefix for endpoints |
| `KICK_MCP_ENABLED` | `true` | Enable/disable MCP server |
| `KICK_SCRUBBER_ENABLED` | `true` | Enable/disable PII scrubbing |

## Multiple Tokens

Configure tokens with different scopes in `config/kick.php`:

```php
'tokens' => [
    env('KICK_TOKEN') => ['*'],  // Full access
    env('KICK_READONLY_TOKEN') => ['health:read', 'stats:read', 'logs:read'],
    env('KICK_MONITOR_TOKEN') => ['health:read', 'queue:read'],
],
```

## Available Scopes

| Scope | Access |
|-------|--------|
| `*` | All endpoints |
| `health:read` | Health checks |
| `stats:read` | System statistics |
| `logs:read` | Read log files |
| `queue:read` | View queue status |
| `queue:retry` | Retry failed jobs |
| `artisan:list` | List commands |
| `artisan:execute` | Execute commands |

## Allowed Artisan Commands

Only whitelisted commands can be executed:

```php
'allowed_commands' => [
    'about',
    'route:list',
    'migrate:status',
    'cache:clear',
    'config:cache',
    'queue:retry',
    'horizon:status',
],
```
