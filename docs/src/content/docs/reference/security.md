---
title: Security
description: Security considerations for Laravel Kick
---

## Disabled by Default

Kick is disabled until you explicitly set `KICK_ENABLED=true`. This prevents accidental exposure.

## Token Authentication

All endpoints require Bearer token authentication. Tokens are configured server-side and never transmitted in responses.

**Recommendations:**
- Use 32+ character random tokens
- Use separate tokens for different consumers
- Rotate tokens periodically

Generate a token:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Scope-Based Authorization

Each token maps to specific scopes. Use the minimum scopes needed:

```php
'tokens' => [
    env('KICK_TOKEN') => ['*'],  // Full access - use sparingly
    env('KICK_MONITOR_TOKEN') => ['health:read', 'stats:read'],  // Read-only
],
```

## Command Whitelist

The artisan endpoint only executes explicitly whitelisted commands. Arbitrary command execution is not possible.

```php
'allowed_commands' => [
    'cache:clear',
    'config:cache',
    // Only these commands can run
],
```

## Path Traversal Protection

The log reader validates file paths to prevent directory traversal attacks. Only files within the configured logs directory with allowed extensions can be read.

## PII Scrubbing

Logs and queue exception messages are automatically scrubbed before returning via API or MCP:

- Email addresses
- IP addresses
- Phone numbers
- Credit card numbers
- SSN patterns
- API keys and tokens
- Bearer tokens
- JWT tokens
- Password fields

To disable (not recommended):

```bash
KICK_SCRUBBER_ENABLED=false
```

## Infrastructure Recommendations

- Use HTTPS in production
- Consider IP allowlisting at load balancer/firewall level
- Monitor access logs for unusual patterns
- Use separate tokens for CI/CD vs human access
