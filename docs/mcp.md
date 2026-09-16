# MCP Authentication & Endpoint Reference

> For ability details see the [ability reference docs](abilities/) and for step-by-step walkthroughs see the [tutorials](tutorials/).

## Endpoint

```
POST https://example.com/wp-json/mcp/mcp-adapter-default-server
```

Replace `example.com` with your site domain. The path is fixed by the [WordPress MCP Adapter](https://github.com/wordpress/mcp-adapter) plugin.

## Authentication

All requests use HTTP Basic auth with a WordPress **[Application Password](https://developer.wordpress.org/advanced-administration/security/application-passwords/)**:

```
Authorization: Basic base64(username:application-password)
```

Generate an [Application Password](https://developer.wordpress.org/advanced-administration/security/application-passwords/) in **Users → Profile → Application Passwords** in the WordPress admin.

```bash
# Convenience: pass credentials with -u; curl handles the base64 encoding.
curl -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" ...
```

## Required Permissions

| Capability | Needed for |
|---|---|
| `upload_files` | Listing folders, adding/removing media, read-only abilities |
| `manage_categories` | Creating, updating, or deleting folders; archiving media |
| `manage_options` | Rules, media cleanup actions, AI Organizer scan |

Administrators have all three. You can create a dedicated lower-privilege user with only `upload_files` + `manage_categories` for read/write folder operations without site-wide admin access.

## Request Format

Every call uses JSON-RPC 2.0 over HTTP POST:

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

```
Content-Type: application/json
```

## Response Format

Successful response:

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "result": { "content": [ { "type": "text", "text": "..." } ] }
}
```

The ability result is JSON-encoded inside `result.content[0].text`.

Error response (ability returned `WP_Error`, or JSON-RPC error):

```json
{
  "jsonrpc": "2.0",
  "id": 1,
  "error": { "code": -32000, "message": "Ability execution failed: ..." }
}
```

When an ability returns a `WP_Error`, its `code` and `data.status` (HTTP status)
are forwarded through the adapter so an agent can dispatch on `code` directly.
See [Error Codes](#error-codes) for the full taxonomy.

## Discovering Available Abilities

To list all registered abilities on a site:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{ "jsonrpc": "2.0", "id": 1, "method": "tools/list", "params": {} }'
```

Only abilities with `meta.mcp.public = true` are exposed. All abilities registered by VMFA AI Ability include this flag.

## Client Configuration

### Claude Desktop

```json
{
  "mcpServers": {
    "vmfo": {
      "type": "http",
      "url": "https://example.com/wp-json/mcp/mcp-adapter-default-server",
      "headers": {
        "Authorization": "Basic <base64(username:application-password)>"
      }
    }
  }
}
```

### GitHub Copilot in VS Code

1. Open Command Palette.
2. Run `MCP: Add Server`.
3. Choose HTTP server.
4. Use endpoint `https://example.com/wp-json/mcp/mcp-adapter-default-server`.
5. Add Basic auth header for your Application Password user.

### Cursor

```json
{
  "mcpServers": {
    "vmfo": {
      "url": "https://example.com/wp-json/mcp/mcp-adapter-default-server",
      "headers": {
        "Authorization": "Basic <base64(username:application-password)>"
      }
    }
  }
}
```

## Error Codes

When an ability fails it returns a `WP_Error` whose `code`, human-readable
`message`, and `data.status` (HTTP status) are forwarded through the MCP adapter.
Dispatch on `code`; it is stable across releases.

### Auth & permissions (HTTP 403 / 401)

| Code | Cause | Fix |
|---|---|---|
| `rest_forbidden` | Caller lacks the required capability, or the invocation ran with no authenticated user (MCP/agent/background context). Uniform 403 for every authorization failure. | Authenticate as a user with the capability listed under [Required Permissions](#required-permissions) and edit rights on the target attachment(s). |

### Not found (HTTP 404)

| Code | Cause | Fix |
|---|---|---|
| `rest_folder_not_found` | `folder_id` does not resolve to a folder term. | Re-resolve the folder with `vmfo/list-folders`. |
| `rest_media_not_found` | An `attachment_id` is not an existing attachment post. | Confirm the media ID; re-list media. |

### Validation (HTTP 400)

| Code | Cause | Fix |
|---|---|---|
| `ability_invalid_input` | A required argument is missing or invalid (e.g. no `folder_id`, empty `attachment_ids`, empty folder name). | Supply the required arguments per the ability's input schema. |
| `parent_not_exists` | `parent_id` on create/update does not resolve to a folder. | Use a valid parent folder ID, or `0` for top-level. |
| `term_exists` | A folder with the same name already exists under the parent. | Use a different name, or add media to the existing folder. |
| `empty_term_name` | Folder name resolved to an empty string. | Provide a non-empty `name`. |
| `invalid_term` / `invalid_taxonomy` | Underlying taxonomy rejected the operation. | Inspect the message; verify the folder taxonomy is registered. |

### Rate limit (HTTP 429)

| Code | Cause | Fix |
|---|---|---|
| `rate_limit_exceeded` | Per-user write budget exhausted. Batch/write abilities are capped at 30 calls/min; destructive abilities (`vmfo/delete-folder`, `vmfo-cleanup/trash`, `vmfo-cleanup/delete`) at 10 calls/min. `data.retry_after` gives the seconds until reset. | Wait `retry_after` seconds and retry; batch more attachment IDs per call. Tune via the `vmfa_ai_ability_rate_limit` filter (return `0` to disable). |

### Passthrough

| Code | Cause | Fix |
|---|---|---|
| `rest_error` (or the upstream code) | A delegated REST request returned an error; the underlying code, message, and status are forwarded unchanged. | Handle per the forwarded code/status. |

## Troubleshooting

| Symptom | Likely cause |
|---|---|
| `401` or `403` | Wrong Application Password or user lacks required capability |
| Tool not found | `mcp-adapter-execute-ability` not listed in `tools/list` — check adapter plugin is active |
| Folder mismatch | Resolve folders by `path`, not just `name` |
| Upload OK but assignment failed | Confirm the media ID is an attachment post and folder ID exists |
| `429` / `rate_limit_exceeded` | Too many write/destructive calls per minute; wait `retry_after` seconds or batch more IDs per call |

## Smoke Test

```bash
MCP_BASE_URL="https://example.com/wp-json/mcp/mcp-adapter-default-server" \
MCP_USER="per" \
MCP_APP_PASS="xxxx xxxx xxxx xxxx xxxx xxxx" \
./scripts/mcp-adapter-smoke-test.sh
```

Add `VMFO_RUN_MUTATING_TESTS=1` to enable write-operation tests (creates and deletes a test folder).

## Skill: Auto-Place Photos By Image Content

A reusable agent skill is available at [`.github/skills/add-photo-to-folder/SKILL.md`](../.github/skills/add-photo-to-folder/SKILL.md). It implements the full upload → suggest → create → assign flow described in [Tutorial 101](tutorials/101-first-folder-workflow.md).
