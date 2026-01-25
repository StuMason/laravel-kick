---
title: Authentication
description: REST API authentication for Laravel Kick
---

## Bearer Token

All endpoints require a Bearer token in the Authorization header:

```bash
curl -H "Authorization: Bearer your-token" https://app.com/kick/health
```

## Token Configuration

Tokens are configured in `config/kick.php`:

```php
'tokens' => [
    env('KICK_TOKEN') => ['*'],
    env('KICK_READONLY_TOKEN') => ['health:read', 'stats:read'],
],
```

Each token maps to an array of scopes.

## Scopes

| Scope | Endpoints |
|-------|-----------|
| `*` | All endpoints |
| `health:read` | GET /kick/health |
| `stats:read` | GET /kick/stats |
| `logs:read` | GET /kick/logs, GET /kick/logs/{file} |
| `queue:read` | GET /kick/queue, GET /kick/queue/failed |
| `queue:retry` | POST /kick/queue/retry/* |
| `artisan:list` | GET /kick/artisan |
| `artisan:execute` | POST /kick/artisan |

## Response Codes

| Code | Meaning |
|------|---------|
| 401 | Missing or invalid token |
| 403 | Token lacks required scope |
| 200 | Success |
