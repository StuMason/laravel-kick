<?php

use Laravel\Mcp\Facades\Mcp;
use StuMason\Kick\Mcp\KickServer;

/*
|--------------------------------------------------------------------------
| Kick MCP Routes
|--------------------------------------------------------------------------
|
| These routes expose the Kick MCP server for AI client integration.
| The server is registered at /mcp/{prefix} where prefix defaults to 'kick'.
| Configure the prefix via config('kick.prefix') or KICK_PREFIX env var.
|
*/

$prefix = config('kick.prefix', 'kick');

// MCP requires wildcard scope (*) since it provides access to all tools
Mcp::web("/mcp/{$prefix}", KickServer::class)
    ->middleware(['kick.auth:*']);
