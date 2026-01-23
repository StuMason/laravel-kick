# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel package for secure remote introspection and control of Laravel applications via HTTP API. Provides authenticated endpoints for health checks, system stats, log reading, queue management, and artisan command execution.

**Stack:** PHP 8.2+, Laravel 11/12, Pest for testing

## Common Commands

```bash
# Run tests
composer test

# Run tests with coverage
composer test:coverage

# Static analysis
composer lint
```

## Architecture

### Core Components

- `src/KickServiceProvider.php` - Main service provider, registers routes and middleware
- `src/Kick.php` - Facade-accessible class with static helpers
- `src/Http/Middleware/Authenticate.php` - Token and scope validation

### Services

| Service | Purpose |
|---------|---------|
| `Services/HealthChecker.php` | Database, cache, storage, redis connectivity checks |
| `Services/StatsCollector.php` | CPU, memory, disk, uptime stats (cgroups aware) |
| `Services/LogReader.php` | Read and filter Laravel log files |
| `Services/QueueInspector.php` | Queue job counts, failed jobs, retry functionality |
| `Services/ArtisanRunner.php` | Whitelisted artisan command execution |

### Controllers

| Controller | Endpoints |
|------------|-----------|
| `HealthController.php` | `GET /kick/health` |
| `StatsController.php` | `GET /kick/stats` |
| `LogController.php` | `GET /kick/logs`, `GET /kick/logs/{file}` |
| `QueueController.php` | `GET /kick/queue`, `GET /kick/queue/failed`, `POST /kick/queue/retry/*` |
| `ArtisanController.php` | `GET /kick/artisan`, `POST /kick/artisan` |

### Configuration

- `config/kick.php` - Main config file with tokens, scopes, allowed commands, rate limits

### Testing

Uses Orchestra Testbench for Laravel package testing with Pest.

- `tests/Unit/` - Service unit tests (HealthChecker, StatsCollector, LogReader, QueueInspector, ArtisanRunner)
- `tests/Feature/` - Controller feature tests with auth/scope testing

All HTTP calls are mocked. Tests cover:
- Authentication (401 without token, 403 with wrong scope)
- Authorization (scope-based access control)
- Functionality (correct responses, error handling)

## Key Patterns

1. **Service Pattern** - Business logic in Services, controllers are thin
2. **Scope-based Auth** - Middleware validates token and required scope
3. **Command Whitelist** - Only explicitly allowed artisan commands can run
4. **Path Traversal Protection** - LogReader validates file paths
5. **Container Detection** - StatsCollector auto-detects cgroups v1/v2

## Authentication Flow

1. Request includes `Authorization: Bearer <token>` header
2. `Authenticate` middleware extracts token
3. Token looked up in `config('kick.tokens')`
4. Token's scopes checked against required scope for endpoint
5. Wildcard scope `*` grants access to everything

## Adding New Endpoints

1. Create service in `src/Services/` with business logic
2. Create controller in `src/Http/Controllers/`
3. Add route in `routes/api.php` with appropriate scope middleware
4. Add scope to config docblock in `config/kick.php`
5. Write unit tests for service, feature tests for controller

## Git Workflow

**NEVER push directly to main.** Always follow this workflow:

1. Create a feature branch from main
2. Make changes and commit
3. Run tests (`composer test`) and lint (`composer lint`)
4. Create a PR to main
5. Address review feedback
6. Merge PR
7. Tag release if needed

```bash
# Example workflow
git checkout -b feature/my-feature
# ... make changes ...
composer test && composer lint
git add -A && git commit -m "feat: description"
git push -u origin feature/my-feature
gh pr create --title "feat: description" --body "..."
```

## Future Tasks

- [ ] MCP (Model Context Protocol) server integration
- [ ] Rate limiting middleware
- [ ] Metrics export (Prometheus format)
- [ ] WebSocket support for real-time logs
