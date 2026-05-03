# Media Cleanup Abilities

These abilities are active when the **[vmfa-media-cleanup](https://github.com/soderlind/vmfa-media-cleanup)** add-on is installed and active (`VMFA_MEDIA_CLEANUP_VERSION` defined).

**Category slug:** `vmfo-media-cleanup`  
**REST namespace:** `vmfa-cleanup/v1`

Typical workflow: `get-stats` → `start-scan` → poll `get-scan-status` → `list-results` → `archive` / `trash` / `delete`.

---

## `vmfo-cleanup/start-scan`

**Label:** Start Cleanup Scan  
**Permission:** `manage_options`  
**Flags:** —

Starts a background scan that analyses media for unused files, duplicates, and oversized images. Only one scan can run at a time.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `types` | string[] | No | all types | Subset of `["unused", "duplicate", "oversized"]` to scan |

### Output

```json
{ "status": "running", "started_at": "2026-05-02T10:00:00Z" }
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/start-scan",
        "parameters": { "types": ["unused", "duplicate"] }
      }
    }
  }'
```

---

## `vmfo-cleanup/get-scan-status`

**Label:** Get Scan Status  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns the current progress of the running or last-completed cleanup scan.

### Input

No parameters.

### Output

```json
{
  "status": "running",
  "phase": "duplicates",
  "total": 500,
  "processed": 210,
  "started_at": "2026-05-02T10:00:00Z",
  "completed_at": null,
  "types": ["unused", "duplicate"]
}
```

`status` values: `idle` · `running` · `completed` · `cancelled` · `failed`

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": { "ability_name": "vmfo-cleanup/get-scan-status", "parameters": {} }
    }
  }'
```

---

## `vmfo-cleanup/cancel-scan`

**Label:** Cancel Scan  
**Permission:** `manage_options`  
**Flags:** idempotent

Cancels the currently running scan. Safe to call when no scan is running.

### Input

No parameters.

### Output

```json
{ "cancelled": true }
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": { "ability_name": "vmfo-cleanup/cancel-scan", "parameters": {} }
    }
  }'
```

---

## `vmfo-cleanup/get-stats`

**Label:** Get Cleanup Stats  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns dashboard-level statistics from the last completed scan.

### Input

No parameters.

### Output

```json
{
  "total_media": 1240,
  "unused_count": 87,
  "duplicate_count": 34,
  "duplicate_groups": 12,
  "oversized_count": 5,
  "flagged_count": 126
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": { "ability_name": "vmfo-cleanup/get-stats", "parameters": {} }
    }
  }'
```

---

## `vmfo-cleanup/list-results`

**Label:** List Results  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns paginated scan results filterable by type.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `type` | string | No | — | `unused` · `duplicate` · `oversized` · `flagged` · `trash` |
| `page` | integer ≥ 1 | No | `1` | Page number |
| `per_page` | integer 1–100 | No | `20` | Items per page |

### Output

```json
{
  "items": [
    { "id": 1001, "title": "old-banner.png", "url": "...", "type": "unused", "file_size": 204800 }
  ],
  "total": 87,
  "page": 1,
  "per_page": 20,
  "total_pages": 5
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/list-results",
        "parameters": { "type": "unused", "per_page": 20 }
      }
    }
  }'
```

---

## `vmfo-cleanup/archive`

**Label:** Archive Media  
**Permission:** `manage_categories`  
**Flags:** idempotent

Moves media items to an archive folder. Reversible — items can be moved back.

### Input

| Parameter | Type | Required | Description |
|---|---|---|---|
| `attachment_ids` | integer[] (min 1, unique) | **Yes** | Attachment IDs to archive |

### Output

```json
{ "archived": 3, "failed": 0 }
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/archive",
        "parameters": { "attachment_ids": [1001, 1002, 1003] }
      }
    }
  }'
```

---

## `vmfo-cleanup/trash`

**Label:** Trash Media  
**Permission:** `manage_options`  
**Flags:** destructive · idempotent

Moves media items to the WordPress trash. Items can be restored from the trash within the configured retention period.

### Input

| Parameter | Type | Required | Description |
|---|---|---|---|
| `attachment_ids` | integer[] (min 1, unique) | **Yes** | Attachment IDs to trash |

### Output

```json
{ "trashed": 3, "failed": 0 }
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/trash",
        "parameters": { "attachment_ids": [1001, 1002] }
      }
    }
  }'
```

---

## `vmfo-cleanup/delete`

**Label:** Permanently Delete Media  
**Permission:** `manage_options`  
**Flags:** destructive

**Permanently** deletes media items and their files from disk. This action cannot be undone. Prefer `trash` unless you are certain.

### Input

| Parameter | Type | Required | Description |
|---|---|---|---|
| `attachment_ids` | integer[] (min 1, unique) | **Yes** | Attachment IDs to permanently delete |

### Output

```json
{ "deleted": 2, "failed": 0 }
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/delete",
        "parameters": { "attachment_ids": [1001] }
      }
    }
  }'
```
