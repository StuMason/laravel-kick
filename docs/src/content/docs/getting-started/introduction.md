---
title: Introduction
description: MCP server and REST API for Laravel application introspection
---

Laravel Kick provides an MCP server and REST API for introspecting and administering Laravel applications. Connect Claude Desktop (or any MCP client) directly to your Laravel app to check health, read logs, inspect queues, and run commands.

## What It Does

- **Health checks** - Database, cache, storage, redis connectivity with latency
- **System stats** - CPU, memory, disk usage (cgroups-aware for containers)
- **Log access** - Read and filter Laravel logs
- **Queue management** - Job counts, failed jobs, retry
- **Artisan commands** - Execute whitelisted commands

## Two Ways to Access

**MCP Server** - Connect AI assistants like Claude directly to your application. Ask Claude to check your logs, see why jobs are failing, or clear caches.

**REST API** - The same functionality via HTTP endpoints. Use for monitoring dashboards, CI/CD pipelines, or custom tooling.

## Requirements

- PHP 8.4+
- Laravel 12
