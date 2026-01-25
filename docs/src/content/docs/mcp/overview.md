---
title: MCP Overview
description: Model Context Protocol integration for Laravel
---

Kick includes a built-in [Model Context Protocol](https://modelcontextprotocol.io) server. This lets AI assistants interact directly with your Laravel application.

## What You Can Do

Connect your MCP client to your production or staging app and ask:

- "Check the health of my app"
- "Are there any errors in the logs?"
- "What's in the failed jobs queue?"
- "Clear the config cache"
- "Show me the last 20 ERROR entries"

The LLM calls the appropriate Kick tool and returns the results conversationally.

## How It Works

1. Kick registers an MCP server at `/mcp/kick`
2. Configure your MCP client with the app URL and token
3. The LLM discovers the available tools
4. You ask questions in natural language
5. The LLM translates to tool calls and presents results

## Available Tools

| Tool | Description |
|------|-------------|
| `kick_health` | Check database, cache, storage, redis |
| `kick_stats` | CPU, memory, disk, uptime |
| `kick_logs_list` | List available log files |
| `kick_logs_read` | Read log entries with filtering |
| `kick_queue_status` | Queue overview and failed jobs |
| `kick_queue_retry` | Retry failed jobs |
| `kick_artisan_list` | List available commands |
| `kick_artisan_run` | Execute a command |

## Requirements

MCP requires a token with wildcard (`*`) scope since it provides access to all functionality.
