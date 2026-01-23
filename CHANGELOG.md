# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- MCP (Model Context Protocol) integration via `laravel/mcp`
- KickServer MCP server with auto-registration at `/mcp/kick`
- 8 MCP tools for AI assistant interaction:
  - `kick_health` - Application health checks
  - `kick_stats` - System/container statistics
  - `kick_logs_list` - List available log files
  - `kick_logs_read` - Read and filter log entries
  - `kick_queue_status` - Queue overview with optional failed jobs
  - `kick_queue_retry` - Retry failed jobs
  - `kick_artisan_list` - List allowed commands
  - `kick_artisan_run` - Execute whitelisted commands

## [0.2.0] - 2026-01-23

### Added
- Health endpoint (`/kick/health`) - database, cache, storage, redis checks with latency
- Stats endpoint (`/kick/stats`) - CPU, memory, disk, uptime (cgroups v1/v2 aware)
- Queue endpoints (`/kick/queue`, `/kick/queue/failed`, `/kick/queue/retry/{id}`)
- Artisan endpoint (`/kick/artisan`) - list and execute whitelisted commands
- Comprehensive test suite (94 tests, 277 assertions)
- Full package documentation (README, CLAUDE.md, CHANGELOG)
- GitHub Actions CI (tests, lint, dependabot automerge)
- Issue and PR templates

## [0.1.1] - 2026-01-23

### Added
- `POST /kick/logs/test` endpoint to generate test log entries for verification

## [0.1.0] - 2026-01-23

### Added
- Initial release
- Token-based authentication with scope support
- Log reading endpoints with filtering by level and search
- Path traversal protection for log reader
- Configuration file with tokens, scopes, allowed commands
- PHPStan level 5 static analysis
- Pest test suite

[Unreleased]: https://github.com/StuMason/laravel-kick/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/StuMason/laravel-kick/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/StuMason/laravel-kick/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/StuMason/laravel-kick/releases/tag/v0.1.0
