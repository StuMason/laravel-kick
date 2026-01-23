<?php

use Laravel\Mcp\Facades\Mcp;
use StuMason\Kick\Mcp\KickServer;

/*
|--------------------------------------------------------------------------
| Kick MCP Routes
|--------------------------------------------------------------------------
|
| These routes expose the Kick MCP server for AI client integration.
| The server is registered at /mcp/kick by default.
|
*/

$prefix = config('kick.prefix', 'kick');

Mcp::web("/mcp/{$prefix}", KickServer::class)
    ->middleware(['kick.auth']);
