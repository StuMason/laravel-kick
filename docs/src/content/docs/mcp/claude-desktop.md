---
title: MCP Client Setup
description: Connecting MCP clients to your Laravel application
---

## Claude Desktop Configuration

Add to your Claude Desktop config:

**macOS:** `~/Library/Application Support/Claude/claude_desktop_config.json`

**Windows:** `%APPDATA%\Claude\claude_desktop_config.json`

```json
{
  "mcpServers": {
    "my-laravel-app": {
      "command": "npx",
      "args": [
        "mcp-remote@latest",
        "https://your-app.com/mcp/kick",
        "--transport",
        "http-only",
        "--header",
        "Authorization:${AUTH_HEADER}"
      ],
      "env": {
        "AUTH_HEADER": "Bearer your-kick-token"
      }
    }
  }
}
```

## Multiple Applications

Configure multiple apps with different names:

```json
{
  "mcpServers": {
    "production": {
      "command": "npx",
      "args": [
        "mcp-remote@latest",
        "https://app.example.com/mcp/kick",
        "--transport",
        "http-only",
        "--header",
        "Authorization:${AUTH_HEADER}"
      ],
      "env": {
        "AUTH_HEADER": "Bearer prod-token"
      }
    },
    "staging": {
      "command": "npx",
      "args": [
        "mcp-remote@latest",
        "https://staging.example.com/mcp/kick",
        "--transport",
        "http-only",
        "--header",
        "Authorization:${AUTH_HEADER}"
      ],
      "env": {
        "AUTH_HEADER": "Bearer staging-token"
      }
    }
  }
}
```

## Restart MCP Client

After editing the config, restart your MCP client for changes to take effect.

## Verify Connection

Ask the LLM: "What tools do you have available from my Laravel app?"

It should list the Kick tools if connected successfully.

## Troubleshooting

**Tools not appearing:**

- Check the URL is accessible from your machine
- Verify the token has wildcard (`*`) scope
- Ensure `KICK_ENABLED=true` and `KICK_MCP_ENABLED=true`

**401 Unauthorized:**

- Token is missing or invalid
- Check `Authorization: Bearer` header format

**403 Forbidden:**

- Token doesn't have required scope (needs `*` for MCP)
