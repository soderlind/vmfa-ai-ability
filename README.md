# Virtual Media Folders - AI Ability

Exposes Virtual Media Folders operations as WordPress Abilities API tools for AI agents and MCP adapters.

## Description

This add-on for [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) registers folder management operations as WordPress Abilities API tools, making them available to AI agents and MCP (Model Context Protocol) adapters.

### Registered Abilities

| Ability | Description |
|---------|-------------|
| `vmfo/list-folders` | Lists folders with IDs, names, and paths for name-to-ID resolution |
| `vmfo/create-folder` | Creates a folder with an optional parent |
| `vmfo/add-to-folder` | Assigns one or more media items to a folder |

## Requirements

- WordPress 7.0+
- PHP 8.3+
- Virtual Media Folders plugin (required dependency)

## Installation

1. Download [`vmfa-ai-ability.zip`](https://github.com/soderlind/vmfa-ai-ability/releases/latest/download/vmfa-ai-ability.zip)
2. Upload via  `Plugins → Add New → Upload Plugin`
3. Activate via `WordPress Admin → Plugins`

## MCP Integration

Once activated, the abilities are automatically exposed to MCP adapters. See the [Virtual Media Folders MCP guide](https://github.com/soderlind/virtual-media-folders/blob/main/docs/mcp.md) for client configuration (Claude, GitHub Copilot, Cursor).

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later
