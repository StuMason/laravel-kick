---
title: Installation
description: Installing Laravel Kick
---

## Install via Composer

```bash
composer require stumason/laravel-kick
```

## Environment Variables

Add to your `.env`:

```bash
KICK_ENABLED=true
KICK_TOKEN=your-secure-random-token
```

Generate a secure token:

```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Publish Config (Optional)

```bash
php artisan vendor:publish --tag=kick-config
```

This creates `config/kick.php` where you can customize tokens, scopes, allowed commands, and other settings.

## Verify Installation

```bash
curl -H "Authorization: Bearer your-token" http://localhost/kick/health
```

Returns application health status if configured correctly.
