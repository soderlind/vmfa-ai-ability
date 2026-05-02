# Virtual Media Folders - AI Ability

Exposes Virtual Media Folders operations as WordPress Abilities API tools for AI agents and MCP adapters.

## Description

This add-on for [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) registers folder management, rules, media cleanup, ZIP export, and AI-powered batch organisation as WordPress Abilities API tools, making them available to AI agents and MCP (Model Context Protocol) adapters.

Add-on abilities are registered automatically when the corresponding add-on plugin is active — no extra configuration required.

### Registered Abilities

#### Folder Management — always active

| Ability | Description |
|---|---|
| `vmfo/list-folders` | List folders with IDs, names, paths, and counts |
| `vmfo/create-folder` | Create a folder with an optional parent |
| `vmfo/add-to-folder` | Assign one or more media items to a folder |
| `vmfo/update-folder` | Rename or move a folder |
| `vmfo/delete-folder` | Delete a folder |
| `vmfo/remove-from-folder` | Remove one or more media items from a folder |
| `vmfo/get-suggestions` | Get AI-powered folder suggestions for a media item |

#### Rules Engine — requires [vmfa-rules-engine](https://github.com/soderlind/vmfa-rules-engine)

| Ability | Description |
|---|---|
| `vmfo-rules/list-rules` | List all folder-assignment rules |
| `vmfo-rules/create-rule` | Create an automatic folder-assignment rule |
| `vmfo-rules/update-rule` | Update an existing rule |
| `vmfo-rules/delete-rule` | Delete a rule |
| `vmfo-rules/preview` | Preview which media a rule would match (dry run) |
| `vmfo-rules/apply` | Apply rules to existing media |

#### Media Cleanup — requires [vmfa-media-cleanup](https://github.com/soderlind/vmfa-media-cleanup)

| Ability | Description |
|---|---|
| `vmfo-cleanup/start-scan` | Start a background scan for unused, duplicate, or oversized media |
| `vmfo-cleanup/get-scan-status` | Get current scan progress |
| `vmfo-cleanup/cancel-scan` | Cancel the running scan |
| `vmfo-cleanup/get-stats` | Get dashboard statistics from the last scan |
| `vmfo-cleanup/list-results` | List paginated scan results by type |
| `vmfo-cleanup/archive` | Move media to an archive folder |
| `vmfo-cleanup/trash` | Move media to the WordPress trash |
| `vmfo-cleanup/delete` | Permanently delete media and files from disk |

#### Folder Exporter — requires [vmfa-folder-exporter](https://github.com/soderlind/vmfa-folder-exporter)

| Ability | Description |
|---|---|
| `vmfo-folder-exporter/start-export` | Start an async ZIP export of a folder |
| `vmfo-folder-exporter/get-export-status` | Get the status of an export job |
| `vmfo-folder-exporter/list-exports` | List all recent export jobs |
| `vmfo-folder-exporter/delete-export` | Delete an export job and its ZIP file |

#### AI Organizer — requires [vmfa-ai-organizer](https://github.com/soderlind/vmfa-ai-organizer)

| Ability | Description |
|---|---|
| `vmfo-ai-organizer/start-scan` | Start an AI-powered batch folder-assignment scan |
| `vmfo-ai-organizer/get-scan-status` | Get current scan progress |
| `vmfo-ai-organizer/cancel-scan` | Cancel the running scan |

## Requirements

- WordPress 7.0+
- PHP 8.3+
- [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) (required)
- [WordPress MCP Adapter](https://github.com/soderlind/mcp-adapter) (required to expose abilities as MCP tools)

## Installation

1. Download [`vmfa-ai-ability.zip`](https://github.com/soderlind/vmfa-ai-ability/releases/latest/download/vmfa-ai-ability.zip)
2. Upload via `Plugins → Add New → Upload Plugin`
3. Activate via `WordPress Admin → Plugins`

## MCP Integration

Once activated, all abilities are automatically exposed to MCP adapters. See:

- [docs/mcp.md](docs/mcp.md) — authentication, endpoint, and client configuration (Claude, GitHub Copilot, Cursor)
- [docs/README.md](docs/README.md) — full ability reference

## Tutorials

| Level | Tutorial |
|---|---|
| 101 | [Upload & Organise a Photo](docs/tutorials/101-first-folder-workflow.md) |
| 201 | [Detect & Clean Up Unused Media](docs/tutorials/201-media-cleanup.md) |
| 301 | [Automate Folder Assignment with Rules](docs/tutorials/301-rules-automation.md) |

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

GPL-2.0-or-later
