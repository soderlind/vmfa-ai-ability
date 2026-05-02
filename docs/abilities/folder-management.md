# Folder Management Abilities

These abilities are always active when the **Virtual Media Folders** base plugin is installed and the **VMFA AI Ability** plugin is active. No add-on is required.

**Category slug:** `vmfo-folder-management`

---

## `vmfo/list-folders`

**Label:** List Folders  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns all folders with their IDs, names, parent IDs, full paths, and media counts. Use this ability to resolve folder names to IDs before calling write abilities.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `search` | string | No | — | Filter folders whose name contains this string |
| `parent_id` | integer ≥ 0 | No | — | Limit results to direct children of this folder ID |
| `hide_empty` | boolean | No | `false` | Exclude folders with zero media items |

### Output

```json
{
  "folders": [
    {
      "id": 42,
      "name": "Travel",
      "parent_id": 0,
      "path": "Travel",
      "count": 17
    }
  ],
  "total": 1
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/list-folders",
        "parameters": { "search": "travel", "hide_empty": false }
      }
    }
  }'
```

---

## `vmfo/create-folder`

**Label:** Create Folder  
**Permission:** `manage_categories`  
**Flags:** —

Creates a new folder, optionally nested under a parent.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `name` | string (min 1) | **Yes** | — | Folder name |
| `parent_id` | integer ≥ 0 | No | `0` | Parent folder ID; `0` creates a top-level folder |

### Output

```json
{
  "id": 99,
  "name": "Travel",
  "parent_id": 0,
  "path": "Travel",
  "count": 0
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/create-folder",
        "parameters": { "name": "Travel", "parent_id": 0 }
      }
    }
  }'
```

---

## `vmfo/add-to-folder`

**Label:** Add Media to Folder  
**Permission:** `upload_files`  
**Flags:** —

Assigns one or more media attachments to a folder.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `folder_id` | integer ≥ 1 | **Yes** | — | Target folder ID |
| `attachment_ids` | integer[] (min 1 item) | **Yes** | — | Attachment post IDs to assign |

### Output

```json
{
  "success": true,
  "folder_id": 42,
  "processed_count": 3,
  "results": [
    { "attachment_id": 1001, "success": true, "message": "Assigned." },
    { "attachment_id": 1002, "success": true, "message": "Assigned." }
  ]
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/add-to-folder",
        "parameters": { "folder_id": 42, "attachment_ids": [1001, 1002] }
      }
    }
  }'
```

---

## `vmfo/update-folder`

**Label:** Update Folder  
**Permission:** `manage_categories`  
**Flags:** idempotent

Renames a folder or moves it to a new parent. All fields except `folder_id` are optional — only supplied fields are changed.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `folder_id` | integer ≥ 1 | **Yes** | — | Folder to update |
| `name` | string (min 1) | No | — | New folder name |
| `parent_id` | integer ≥ 0 | No | — | New parent ID (`0` to move to root) |
| `description` | string | No | — | Folder description |

### Output

```json
{
  "id": 42,
  "name": "Road Trips",
  "parent_id": 0,
  "path": "Road Trips",
  "count": 17
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/update-folder",
        "parameters": { "folder_id": 42, "name": "Road Trips" }
      }
    }
  }'
```

---

## `vmfo/delete-folder`

**Label:** Delete Folder  
**Permission:** `manage_categories`  
**Flags:** destructive

Deletes a folder term. Pass `force: true` to hard-delete; omit or pass `false` to use the default behaviour (term is removed but media is not deleted).

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `folder_id` | integer ≥ 1 | **Yes** | — | Folder to delete |
| `force` | boolean | No | `false` | Hard-delete the term without safety checks |

### Output

```json
{ "deleted": true, "id": 42 }
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/delete-folder",
        "parameters": { "folder_id": 42 }
      }
    }
  }'
```

---

## `vmfo/remove-from-folder`

**Label:** Remove Media from Folder  
**Permission:** `upload_files`  
**Flags:** —

Removes one or more media attachments from a folder. The REST endpoint accepts one attachment at a time; this ability loops internally.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `folder_id` | integer ≥ 1 | **Yes** | — | Folder to remove media from |
| `attachment_ids` | integer[] (min 1 item) | **Yes** | — | Attachment IDs to remove |

### Output

```json
{
  "success": true,
  "folder_id": 42,
  "processed_count": 2,
  "results": [
    { "media_id": 1001, "success": true, "message": "Removed." },
    { "media_id": 1002, "success": true, "message": "Removed." }
  ]
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/remove-from-folder",
        "parameters": { "folder_id": 42, "attachment_ids": [1001, 1002] }
      }
    }
  }'
```

---

## `vmfo/get-suggestions`

**Label:** Get Folder Suggestions  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns AI-powered folder suggestions for a single media item based on its content and metadata.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `media_id` | integer ≥ 1 | **Yes** | — | Attachment post ID to get suggestions for |

### Output

```json
{
  "suggestions": [
    { "folder_id": 42, "folder_name": "Travel", "score": 0.91 }
  ],
  "dismissed": []
}
```

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/get-suggestions",
        "parameters": { "media_id": 1001 }
      }
    }
  }'
```
