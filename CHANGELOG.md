# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Health endpoint with database, cache, storage checks and latency measurements
- Stats endpoint with CPU, memory, disk, uptime (cgroups v1/v2 aware)
- Queue endpoints for overview, failed jobs, retry functionality
- Artisan endpoint for listing and executing whitelisted commands
- Comprehensive test suite (94 tests, 277 assertions)

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

[Unreleased]: https://github.com/StuMason/laravel-kick/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/StuMason/laravel-kick/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/StuMason/laravel-kick/releases/tag/v0.1.0
