# VMFA AI Ability — Reference

VMFA AI Ability bridges Virtual Media Folders and its add-ons with the [WordPress Abilities API](https://developer.wordpress.org/apis/abilities/) and the [MCP Adapter](https://github.com/wordpress/mcp-adapter), making folder management, rules, media cleanup, ZIP exports, and AI-powered batch organisation available as MCP tools that any AI assistant can call.

## How It Works

Abilities are called via the `mcp-adapter-execute-ability` gateway tool:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "method": "tools/call",
  "params": {
    "name": "mcp-adapter-execute-ability",
    "arguments": {
      "ability_name": "<ability-name>",
      "parameters": { }
    }
  }
}
```

POST to `https://example.com/wp-json/mcp/mcp-adapter-default-server` with a `Basic` header using a WordPress Application Password. See [mcp.md](mcp.md) for authentication details.

## Prerequisites

| Requirement | Notes |
|---|---|
| WordPress 6.8+ | Required for the Abilities API |
| Virtual Media Folders | Base plugin — always required |
| VMFA AI Ability | This plugin |
| [WordPress MCP Adapter](https://github.com/wordpress/mcp-adapter) | Exposes abilities as MCP tools |
| Application Password | For per-user authentication |

---

## Ability Reference

### Folder Management

Always active (requires only the Virtual Media Folders base plugin).

| Ability | Label | Permission | Flags |
|---|---|---|---|
| [`vmfo/list-folders`](abilities/folder-management.md#vmfolist-folders) | List Folders | `upload_files` | readonly, idempotent |
| [`vmfo/create-folder`](abilities/folder-management.md#vmfocreate-folder) | Create Folder | `manage_categories` | — |
| [`vmfo/add-to-folder`](abilities/folder-management.md#vmfoadd-to-folder) | Add Media to Folder | `upload_files` | — |
| [`vmfo/update-folder`](abilities/folder-management.md#vmfoupdate-folder) | Update Folder | `manage_categories` | idempotent |
| [`vmfo/delete-folder`](abilities/folder-management.md#vmfodelete-folder) | Delete Folder | `manage_categories` | destructive |
| [`vmfo/remove-from-folder`](abilities/folder-management.md#vmforemove-from-folder) | Remove Media from Folder | `upload_files` | — |
| [`vmfo/get-suggestions`](abilities/folder-management.md#vmfoget-suggestions) | Get Folder Suggestions | `upload_files` | readonly, idempotent |

→ [Full reference](abilities/folder-management.md)

---

### Rules Engine

Requires the **vmfa-rules-engine** add-on.

| Ability | Label | Permission | Flags |
|---|---|---|---|
| [`vmfo-rules/list-rules`](abilities/rules-engine.md#vmfo-ruleslist-rules) | List Rules | `upload_files` | readonly, idempotent |
| [`vmfo-rules/create-rule`](abilities/rules-engine.md#vmfo-rulescreate-rule) | Create Rule | `manage_options` | — |
| [`vmfo-rules/update-rule`](abilities/rules-engine.md#vmfo-rulesupdate-rule) | Update Rule | `manage_options` | idempotent |
| [`vmfo-rules/delete-rule`](abilities/rules-engine.md#vmfo-rulesdelete-rule) | Delete Rule | `manage_options` | destructive |
| [`vmfo-rules/preview`](abilities/rules-engine.md#vmfo-rulespreview) | Preview Rule Matches | `upload_files` | readonly |
| [`vmfo-rules/apply`](abilities/rules-engine.md#vmfo-rulesapply) | Apply Rules | `manage_options` | — |

→ [Full reference](abilities/rules-engine.md)

---

### Media Cleanup

Requires the **vmfa-media-cleanup** add-on.

| Ability | Label | Permission | Flags |
|---|---|---|---|
| [`vmfo-cleanup/start-scan`](abilities/media-cleanup.md#vmfo-cleanupstart-scan) | Start Cleanup Scan | `manage_options` | — |
| [`vmfo-cleanup/get-scan-status`](abilities/media-cleanup.md#vmfo-cleanupget-scan-status) | Get Scan Status | `upload_files` | readonly, idempotent |
| [`vmfo-cleanup/cancel-scan`](abilities/media-cleanup.md#vmfo-cleanupcancel-scan) | Cancel Scan | `manage_options` | idempotent |
| [`vmfo-cleanup/get-stats`](abilities/media-cleanup.md#vmfo-cleanupget-stats) | Get Cleanup Stats | `upload_files` | readonly, idempotent |
| [`vmfo-cleanup/list-results`](abilities/media-cleanup.md#vmfo-cleanuplist-results) | List Results | `upload_files` | readonly, idempotent |
| [`vmfo-cleanup/archive`](abilities/media-cleanup.md#vmfo-cleanuparchive) | Archive Media | `manage_categories` | idempotent |
| [`vmfo-cleanup/trash`](abilities/media-cleanup.md#vmfo-cleanuptrash) | Trash Media | `manage_options` | destructive, idempotent |
| [`vmfo-cleanup/delete`](abilities/media-cleanup.md#vmfo-cleanupdelete) | Permanently Delete Media | `manage_options` | destructive |

→ [Full reference](abilities/media-cleanup.md)

---

### Folder Exporter

Requires the **vmfa-folder-exporter** add-on.

| Ability | Label | Permission | Flags |
|---|---|---|---|
| [`vmfo-folder-exporter/start-export`](abilities/folder-exporter.md#vmfo-folder-exporterstart-export) | Start Folder Export | `upload_files` | — |
| [`vmfo-folder-exporter/get-export-status`](abilities/folder-exporter.md#vmfo-folder-exporterget-export-status) | Get Export Status | `upload_files` | readonly, idempotent |
| [`vmfo-folder-exporter/list-exports`](abilities/folder-exporter.md#vmfo-folder-exporterlist-exports) | List Exports | `upload_files` | readonly, idempotent |
| [`vmfo-folder-exporter/delete-export`](abilities/folder-exporter.md#vmfo-folder-exporterdelete-export) | Delete Export | `upload_files` | destructive |

→ [Full reference](abilities/folder-exporter.md)

---

### AI Organizer

Requires the **vmfa-ai-organizer** add-on.

| Ability | Label | Permission | Flags |
|---|---|---|---|
| [`vmfo-ai-organizer/start-scan`](abilities/ai-organizer.md#vmfo-ai-organizerstart-scan) | Start AI Organizer Scan | `manage_options` | — |
| [`vmfo-ai-organizer/get-scan-status`](abilities/ai-organizer.md#vmfo-ai-organizerget-scan-status) | Get AI Scan Status | `upload_files` | readonly, idempotent |
| [`vmfo-ai-organizer/cancel-scan`](abilities/ai-organizer.md#vmfo-ai-organizercancel-scan) | Cancel AI Scan | `manage_options` | idempotent |

→ [Full reference](abilities/ai-organizer.md)

---

## Tutorials

| Level | Tutorial | What You'll Learn |
|---|---|---|
| [101](tutorials/101-first-folder-workflow.md) | Upload & Organise a Photo | Upload media via REST, find or create a folder, assign it |
| [201](tutorials/201-media-cleanup.md) | Detect & Clean Up Unused Media | Run a cleanup scan, review results, bulk-trash unused files |
| [301](tutorials/301-rules-automation.md) | Automate Folder Assignment with Rules | Write a rule, preview its matches, apply it to existing media |
