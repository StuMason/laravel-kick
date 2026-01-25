# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel package providing an MCP server and REST API for application introspection. Enables AI assistants (via MCP) and HTTP clients to check health, read logs, inspect queues, and run whitelisted artisan commands.

**Stack:** PHP 8.4+, Laravel 12, Pest for testing

## Commands

```bash
composer test                    # Run all tests
composer test:coverage           # Tests with coverage
composer lint                    # PHPStan static analysis
./vendor/bin/pest --filter=HealthChecker  # Run single test file/pattern
```

**Documentation (Astro Starlight):**

```bash
cd docs && npm install && npm run dev   # Local dev server on :4321
cd docs && npm run build                # Build for production
```

## Architecture

### Dual Interface Pattern

The package exposes the same functionality through two interfaces:

- **MCP Server** (`/mcp/kick`) - For AI assistants via Model Context Protocol
- **REST API** (`/kick/*`) - For HTTP clients

Both interfaces use the same underlying Services and share authentication.

### Core Structure

```text
src/
  KickServiceProvider.php       # Registers routes, middleware, MCP server
  Http/
    Middleware/Authenticate.php # Token + scope validation
    Controllers/                # Thin controllers, delegate to Services
  Services/                     # Business logic
    HealthChecker.php          # DB, cache, storage, redis checks
    StatsCollector.php         # CPU, memory, disk (cgroups aware)
    LogReader.php              # Log file reading with filters
    QueueInspector.php         # Queue stats, failed jobs
    ArtisanRunner.php          # Whitelisted command execution
    PiiScrubber.php            # Redacts sensitive data from output
  Mcp/
    KickServer.php             # MCP server registration
    Tools/                     # MCP tool implementations (wrap Services)
```

### MCP Tools

Each tool in `src/Mcp/Tools/` wraps a Service. Tools define input schemas and format responses for AI consumption. The `KickServer` registers all tools with the Laravel MCP package.

### Authentication Flow

1. Request includes `Authorization: Bearer <token>` header
2. Middleware looks up token in `config('kick.tokens')`
3. Token's scopes checked against endpoint's required scope
4. Wildcard scope `*` grants full access (required for MCP)

### PII Scrubber

`PiiScrubber` automatically redacts sensitive data (emails, IPs, cards, tokens, etc.) from log entries and queue exceptions before returning via API/MCP. Patterns configurable in `config/kick.php`.

## Testing

Uses Orchestra Testbench with Pest. Tests are in `tests/Unit/` (Services, MCP tools) and `tests/Feature/` (Controllers with auth).

## Adding New Functionality

1. Add business logic to existing Service or create new one in `src/Services/`
2. For REST API: Add controller method, add route with scope middleware
3. For MCP: Create tool in `src/Mcp/Tools/`, register in `KickServer`
4. Write unit tests for service/tool, feature tests for controller

## Git Workflow

Never push directly to main. Create feature branch, run `composer test && composer lint`, create PR.
