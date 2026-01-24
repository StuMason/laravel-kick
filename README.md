# Laravel Kick

[![Tests](https://github.com/StuMason/laravel-kick/actions/workflows/tests.yml/badge.svg)](https://github.com/StuMason/laravel-kick/actions/workflows/tests.yml)
[![Code Style](https://github.com/StuMason/laravel-kick/actions/workflows/lint.yml/badge.svg)](https://github.com/StuMason/laravel-kick/actions/workflows/lint.yml)
[![Latest Version](https://img.shields.io/packagist/v/stumason/laravel-kick.svg)](https://packagist.org/packages/stumason/laravel-kick)
[![PHP Version](https://img.shields.io/packagist/php-v/stumason/laravel-kick.svg)](https://packagist.org/packages/stumason/laravel-kick)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![License](https://img.shields.io/packagist/l/stumason/laravel-kick.svg)](LICENSE)
[![Downloads](https://img.shields.io/packagist/dt/stumason/laravel-kick.svg)](https://packagist.org/packages/stumason/laravel-kick)

**Secure remote introspection and control for Laravel applications via HTTP API and MCP.**

Kick gives you secure, authenticated API endpoints to monitor and manage your Laravel applications remotely. Perfect for AI agents, monitoring systems, and DevOps automation.

## Why Kick?

- **Health Checks** - Database, cache, storage connectivity with latency measurements
- **System Stats** - CPU, memory, disk usage (cgroups-aware for containers)
- **Log Access** - Read and search Laravel logs remotely with filtering
- **Queue Management** - Monitor job counts, view failed jobs, retry with one call
- **Artisan Commands** - Execute whitelisted artisan commands securely
- **MCP Integration** - Built-in Model Context Protocol support for AI agents like Claude

## Quick Start

```bash
composer require stumason/laravel-kick
```

Add environment variables:

```env
KICK_ENABLED=true
KICK_TOKEN=your-secure-random-token
```

That's it! Your endpoints are ready at `/kick/*`.

## Endpoints

| Endpoint | Method | Scope | Description |
|----------|--------|-------|-------------|
| `/kick/health` | GET | `health:read` | Health checks with latency |
| `/kick/stats` | GET | `stats:read` | System/container statistics |
| `/kick/logs` | GET | `logs:read` | List available log files |
| `/kick/logs/{file}` | GET | `logs:read` | Read log entries with filtering |
| `/kick/queue` | GET | `queue:read` | Queue overview with job counts |
| `/kick/queue/failed` | GET | `queue:read` | List failed jobs |
| `/kick/queue/retry/{id}` | POST | `queue:retry` | Retry specific failed job |
| `/kick/queue/retry-all` | POST | `queue:retry` | Retry all failed jobs |
| `/kick/artisan` | GET | `artisan:list` | List available commands |
| `/kick/artisan` | POST | `artisan:execute` | Execute a command |

## Authentication

All endpoints require Bearer token authentication:

```bash
curl -H "Authorization: Bearer your-token" https://your-app.com/kick/health
```

### Token Scopes

Configure multiple tokens with different scopes for fine-grained access control:

```php
// config/kick.php
'tokens' => [
    env('KICK_TOKEN') => ['*'],  // Full access
    env('KICK_READONLY_TOKEN') => ['health:read', 'stats:read', 'logs:read'],
    env('KICK_MONITOR_TOKEN') => ['health:read', 'queue:read'],
],
```

Available scopes:
- `*` - Full access to all endpoints
- `health:read` - Health check endpoint
- `stats:read` - System statistics
- `logs:read` - Read log files
- `queue:read` - View queue status and failed jobs
- `queue:retry` - Retry failed jobs
- `artisan:list` - List available commands
- `artisan:execute` - Execute artisan commands

## Usage Examples

### Health Check

```bash
curl -H "Authorization: Bearer $TOKEN" https://app.com/kick/health
```

```json
{
  "status": "healthy",
  "checks": {
    "database": { "status": "healthy", "message": "Database connection successful", "latency_ms": 1.23 },
    "cache": { "status": "healthy", "message": "Cache read/write successful", "latency_ms": 0.45 },
    "storage": { "status": "healthy", "message": "Storage read/write successful", "latency_ms": 2.10 }
  },
  "timestamp": "2026-01-23T21:00:00+00:00"
}
```

### System Stats

```bash
curl -H "Authorization: Bearer $TOKEN" https://app.com/kick/stats
```

```json
{
  "stats": {
    "cpu": { "cores": 2, "load_average": { "1m": 0.5, "5m": 0.3, "15m": 0.2 } },
    "memory": { "used_bytes": 524288000, "total_bytes": 1073741824, "used_percent": 48.83 },
    "disk": { "used_bytes": 10737418240, "total_bytes": 53687091200, "used_percent": 20.0 },
    "uptime": { "system_uptime_seconds": 86400, "php_uptime_seconds": 3600 }
  },
  "timestamp": "2026-01-23T21:00:00+00:00"
}
```

### Read Logs

```bash
# List log files
curl -H "Authorization: Bearer $TOKEN" https://app.com/kick/logs

# Read specific log with filtering
curl -H "Authorization: Bearer $TOKEN" "https://app.com/kick/logs/laravel-2026-01-23.log?level=ERROR&lines=50"
```

### Execute Artisan Command

```bash
curl -X POST -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"command": "cache:clear"}' \
  https://app.com/kick/artisan
```

```json
{
  "success": true,
  "command": "cache:clear",
  "output": "Application cache cleared successfully.",
  "exit_code": 0
}
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=kick-config
```

```php
// config/kick.php
return [
    'enabled' => env('KICK_ENABLED', false),
    'prefix' => env('KICK_PREFIX', 'kick'),

    'tokens' => [
        env('KICK_TOKEN') => ['*'],
    ],

    'allowed_commands' => [
        'about',
        'route:list',
        'migrate:status',
        'cache:clear',
        'config:cache',
        'queue:retry',
        'horizon:status',
        // Add more as needed
    ],

    'logs' => [
        'path' => storage_path('logs'),
        'allowed_extensions' => ['log'],
        'max_lines' => 500,
    ],
];
```

## Security

Kick is designed with security as a priority:

- **Token-based authentication** - All endpoints require valid Bearer tokens
- **Scope-based authorization** - Fine-grained control over what each token can access
- **Command whitelist** - Only explicitly allowed artisan commands can be executed
- **Path traversal protection** - Log reader prevents directory escape attacks
- **Disabled by default** - Must be explicitly enabled via `KICK_ENABLED=true`

**Recommendations:**
- Use strong, randomly generated tokens (32+ characters)
- Use separate tokens for different purposes/consumers
- Only whitelist artisan commands you actually need
- Consider IP restrictions at the infrastructure level

## MCP Integration

Kick includes built-in support for the [Model Context Protocol](https://modelcontextprotocol.io), enabling AI assistants like Claude to interact with your Laravel application.

### Setup

The MCP server auto-registers at `/mcp/kick` when Kick is installed.

Ensure your token has wildcard (`*`) scope for MCP access (see [Token Scopes](#token-scopes)).

### Available MCP Tools

| Tool | Description |
|------|-------------|
| `kick_health` | Check application health (database, cache, storage, redis) |
| `kick_stats` | Get system/container statistics (CPU, memory, disk, uptime) |
| `kick_logs_list` | List available log files |
| `kick_logs_read` | Read log entries with filtering by level and search |
| `kick_queue_status` | Get queue overview and optionally list failed jobs |
| `kick_queue_retry` | Retry a specific failed job or all failed jobs |
| `kick_artisan_list` | List available whitelisted artisan commands |
| `kick_artisan_run` | Execute a whitelisted artisan command |

### Claude Desktop Configuration

Add to your Claude Desktop config (`~/Library/Application Support/Claude/claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "laravel-kick": {
      "url": "https://your-app.com/mcp/kick",
      "headers": {
        "Authorization": "Bearer your-kick-token"
      }
    }
  }
}
```

The MCP server uses the same token-based authentication as the HTTP API, but **requires a token with wildcard (`*`) scope** since it provides access to all tools.

### Example Conversation

> **You:** Check the health of my Laravel app
>
> **Claude:** *uses kick_health tool*
>
> Your application is HEALTHY. All services are responding normally:
> - Database: healthy (1.23ms)
> - Cache: healthy (0.45ms)
> - Storage: healthy (2.10ms)

> **You:** Are there any errors in the logs?
>
> **Claude:** *uses kick_logs_read with level=ERROR*
>
> Found 3 error entries in laravel.log...

### Disable MCP

To disable MCP integration while keeping the HTTP API:

```env
KICK_MCP_ENABLED=false
```

## Container Support

Kick automatically detects containerized environments and reads metrics from:

- **cgroups v2** (modern Docker/Kubernetes)
- **cgroups v1** (older Docker)
- **System fallback** (bare metal/VM)

## Requirements

- PHP 8.4+
- Laravel 12.x

## Testing

```bash
composer test           # Run tests
composer test:coverage  # Run tests with coverage
composer lint           # Run PHPStan
```

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.

## Credits

- [Stu Mason](https://github.com/StuMason)
- [All Contributors](https://github.com/StuMason/laravel-kick/contributors)
