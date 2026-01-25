---
title: Endpoints
description: REST API endpoints for Laravel Kick
---

## Health Check

```
GET /kick/health
Scope: health:read
```

```json
{
  "status": "healthy",
  "checks": {
    "database": { "status": "healthy", "latency_ms": 1.23 },
    "cache": { "status": "healthy", "latency_ms": 0.45 },
    "storage": { "status": "healthy", "latency_ms": 2.10 }
  },
  "timestamp": "2026-01-25T12:00:00+00:00"
}
```

---

## System Stats

```
GET /kick/stats
Scope: stats:read
```

```json
{
  "stats": {
    "cpu": { "cores": 2, "load_average": { "1m": 0.5, "5m": 0.3, "15m": 0.2 } },
    "memory": { "used_bytes": 524288000, "total_bytes": 1073741824, "used_percent": 48.83 },
    "disk": { "used_bytes": 10737418240, "total_bytes": 53687091200, "used_percent": 20.0 }
  },
  "timestamp": "2026-01-25T12:00:00+00:00"
}
```

---

## List Log Files

```
GET /kick/logs
Scope: logs:read
```

```json
{
  "files": ["laravel-2026-01-25.log", "laravel-2026-01-24.log"]
}
```

---

## Read Log File

```
GET /kick/logs/{file}
Scope: logs:read
```

**Query Parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `lines` | int | 100 | Number of lines |
| `level` | string | - | Filter by level |
| `search` | string | - | Search term |

```bash
curl "https://app.com/kick/logs/laravel.log?level=ERROR&lines=50"
```

---

## Queue Status

```
GET /kick/queue
Scope: queue:read
```

```json
{
  "queues": {
    "default": { "pending": 5, "processing": 2 },
    "high": { "pending": 0, "processing": 0 }
  },
  "failed_count": 3
}
```

---

## Failed Jobs

```
GET /kick/queue/failed
Scope: queue:read
```

```json
{
  "failed_jobs": [
    {
      "id": 123,
      "connection": "redis",
      "queue": "default",
      "failed_at": "2026-01-25T10:00:00+00:00",
      "exception": "Connection refused"
    }
  ]
}
```

---

## Retry Failed Job

```
POST /kick/queue/retry/{id}
Scope: queue:retry
```

---

## Retry All Failed Jobs

```
POST /kick/queue/retry-all
Scope: queue:retry
```

---

## List Artisan Commands

```
GET /kick/artisan
Scope: artisan:list
```

```json
{
  "commands": ["about", "cache:clear", "config:cache", "route:list"]
}
```

---

## Execute Artisan Command

```
POST /kick/artisan
Scope: artisan:execute
Content-Type: application/json
```

```json
{
  "command": "cache:clear"
}
```

**Response:**

```json
{
  "success": true,
  "command": "cache:clear",
  "output": "Application cache cleared successfully.",
  "exit_code": 0
}
```
