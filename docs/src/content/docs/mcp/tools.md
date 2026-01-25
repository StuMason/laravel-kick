---
title: Available Tools
description: MCP tools provided by Laravel Kick
---

## kick_health

Check application health status.

**Parameters:** None

**Returns:** Status of database, cache, storage, and redis connections with latency measurements.

**Example prompt:** "Check the health of my app"

---

## kick_stats

Get system/container statistics.

**Parameters:** None

**Returns:** CPU cores and load average, memory usage, disk usage, uptime.

**Example prompt:** "What are the server stats?"

---

## kick_logs_list

List available log files.

**Parameters:** None

**Returns:** Array of log filenames in the configured logs directory.

**Example prompt:** "What log files are available?"

---

## kick_logs_read

Read log entries with filtering.

**Parameters:**

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `file` | string | latest | Log file to read |
| `lines` | int | 100 | Number of lines |
| `level` | string | - | Filter by level (ERROR, WARNING, etc) |
| `search` | string | - | Search term |

**Example prompts:**
- "Show me the last 50 ERROR entries"
- "Search logs for 'Connection refused'"
- "Read today's log file"

---

## kick_queue_status

Get queue overview.

**Parameters:**

| Name | Type | Default | Description |
|------|------|---------|-------------|
| `include_failed` | bool | false | Include failed job details |

**Returns:** Job counts by queue, optionally with failed job list.

**Example prompts:**
- "What's the queue status?"
- "Show me the failed jobs"

---

## kick_queue_retry

Retry failed jobs.

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `job_id` | string | Specific job ID, or "all" |

**Example prompts:**
- "Retry job 123"
- "Retry all failed jobs"

---

## kick_artisan_list

List available artisan commands.

**Parameters:** None

**Returns:** Array of whitelisted command names.

**Example prompt:** "What artisan commands can you run?"

---

## kick_artisan_run

Execute a whitelisted artisan command.

**Parameters:**

| Name | Type | Description |
|------|------|-------------|
| `command` | string | Command to execute |
| `arguments` | array | Optional arguments |

**Returns:** Command output, exit code, success status.

**Example prompts:**
- "Clear the config cache"
- "Run migrate:status"
- "Show me route:list"
