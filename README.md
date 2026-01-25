# Laravel Kick

[![Tests](https://github.com/StuMason/laravel-kick/actions/workflows/tests.yml/badge.svg)](https://github.com/StuMason/laravel-kick/actions/workflows/tests.yml)
[![Code Style](https://github.com/StuMason/laravel-kick/actions/workflows/lint.yml/badge.svg)](https://github.com/StuMason/laravel-kick/actions/workflows/lint.yml)
[![Latest Version](https://img.shields.io/packagist/v/stumason/laravel-kick.svg)](https://packagist.org/packages/stumason/laravel-kick)
[![License](https://img.shields.io/packagist/l/stumason/laravel-kick.svg)](LICENSE)

MCP server and REST API for Laravel application introspection.

Connect your MCP client directly to your Laravel app. Check health, read logs, inspect queues, run commands - all through natural conversation.

**[Documentation](https://stumason.github.io/laravel-kick/)**

## Installation

```bash
composer require stumason/laravel-kick
```

Add to `.env`:

```bash
KICK_ENABLED=true
KICK_TOKEN=your-secure-random-token
```

## MCP Setup

Add to `~/Library/Application Support/Claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "my-app": {
      "command": "npx",
      "args": [
        "mcp-remote@latest",
        "https://your-app.com/mcp/kick",
        "--transport",
        "http-only",
        "--header",
        "Authorization:${AUTH_HEADER}"
      ],
      "env": {
        "AUTH_HEADER": "Bearer your-kick-token"
      }
    }
  }
}
```

Then ask the LLM:
- "Check the health of my app"
- "Show me the last 20 ERROR entries in the logs"
- "What's failing in the queue?"
- "Clear the config cache"

## Available Tools

| Tool | Description |
|------|-------------|
| `kick_health` | Database, cache, storage, redis connectivity |
| `kick_stats` | CPU, memory, disk, uptime |
| `kick_logs_list` | List log files |
| `kick_logs_read` | Read logs with filtering |
| `kick_queue_status` | Queue overview, failed jobs |
| `kick_queue_retry` | Retry failed jobs |
| `kick_artisan_list` | List available commands |
| `kick_artisan_run` | Execute whitelisted commands |

## REST API

The same functionality is available via HTTP:

```bash
curl -H "Authorization: Bearer $TOKEN" https://app.com/kick/health
curl -H "Authorization: Bearer $TOKEN" https://app.com/kick/stats
curl -H "Authorization: Bearer $TOKEN" "https://app.com/kick/logs/laravel.log?level=ERROR"
curl -X POST -H "Authorization: Bearer $TOKEN" -d '{"command":"cache:clear"}' https://app.com/kick/artisan
```

See the [API documentation](https://stumason.github.io/laravel-kick/api/endpoints/) for all endpoints.

## Security

- Disabled by default (`KICK_ENABLED=false`)
- Token-based authentication with scopes
- Artisan command whitelist
- Path traversal protection
- Automatic PII scrubbing from logs

## Requirements

- PHP 8.4+
- Laravel 12

## License

MIT
