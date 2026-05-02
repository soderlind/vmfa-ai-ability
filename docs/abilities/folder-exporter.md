# Folder Exporter Abilities

These abilities are active when the **[vmfa-folder-exporter](https://github.com/soderlind/vmfa-folder-exporter)** add-on is installed and active (`VMFA_FOLDER_EXPORTER_VERSION` defined).

**Category slug:** `vmfo-folder-exporter`  
**REST namespace:** `vmfa-folder-exporter/v1`

Export jobs are asynchronous. Typical workflow: `start-export` → poll `get-export-status` until `status` is `completed` → download the ZIP → `delete-export` to clean up.

---

## `vmfo-folder-exporter/start-export`

**Label:** Start Folder Export  
**Permission:** `upload_files`  
**Flags:** —

Starts an asynchronous ZIP export of a folder. Returns a `job_id` immediately; the archive is built in the background.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `folder_id` | integer ≥ 1 | **Yes** | — | Folder term ID to export |
| `include_children` | boolean | No | `true` | Include sub-folder contents in the ZIP |
| `include_manifest` | boolean | No | `true` | Include a CSV manifest file in the ZIP |

### Output

```json
{
  "job_id": "1add3528-74e3-40c6-8d5c-30514b7beb45",
  "folder_id": 42,
  "include_children": true,
  "include_manifest": true,
  "user_id": 1,
  "status": "pending",
  "progress": 0,
  "total": 0,
  "file_path": null,
  "file_name": null,
  "file_size": null,
  "created_at": "2026-05-02T10:00:00Z",
  "completed_at": null,
  "error": null
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
        "ability_name": "vmfo-folder-exporter/start-export",
        "parameters": {
          "folder_id": 42,
          "include_children": true,
          "include_manifest": true
        }
      }
    }
  }'
```

---

## `vmfo-folder-exporter/get-export-status`

**Label:** Get Export Status  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns the current status and metadata of an export job.

### Input

| Parameter | Type | Required | Description |
|---|---|---|---|
| `job_id` | string | **Yes** | The job ID returned by `start-export` |

### Output

Same shape as `start-export`, with updated `status`, `progress`, `file_name`, `file_size`, and `completed_at`.

`status` values: `pending` · `running` · `completed` · `failed`

```json
{
  "job_id": "1add3528-74e3-40c6-8d5c-30514b7beb45",
  "status": "completed",
  "progress": 100,
  "total": 17,
  "file_name": "Travel-2026-05-02.zip",
  "file_size": 48234521,
  "completed_at": "2026-05-02T10:00:45Z",
  "error": null
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
        "ability_name": "vmfo-folder-exporter/get-export-status",
        "parameters": { "job_id": "1add3528-74e3-40c6-8d5c-30514b7beb45" }
      }
    }
  }'
```

---

## `vmfo-folder-exporter/list-exports`

**Label:** List Exports  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns all recent export jobs for the current site.

### Input

No parameters.

### Output

An array of export job objects (same shape as `get-export-status`).

```json
[
  {
    "job_id": "1add3528-74e3-40c6-8d5c-30514b7beb45",
    "status": "completed",
    "file_name": "Travel-2026-05-02.zip",
    "created_at": "2026-05-02T10:00:00Z"
  }
]
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
      "arguments": { "ability_name": "vmfo-folder-exporter/list-exports", "parameters": {} }
    }
  }'
```

---

## `vmfo-folder-exporter/delete-export`

**Label:** Delete Export  
**Permission:** `upload_files`  
**Flags:** destructive

Deletes an export job record and removes the associated ZIP file from disk.

### Input

| Parameter | Type | Required | Description |
|---|---|---|---|
| `job_id` | string | **Yes** | Export job ID to delete |

### Output

```json
{ "deleted": true }
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
        "ability_name": "vmfo-folder-exporter/delete-export",
        "parameters": { "job_id": "1add3528-74e3-40c6-8d5c-30514b7beb45" }
      }
    }
  }'
```
