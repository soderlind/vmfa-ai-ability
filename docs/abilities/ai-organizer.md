# AI Organizer Abilities

These abilities are active when the **[vmfa-ai-organizer](https://github.com/soderlind/vmfa-ai-organizer)** add-on is installed and active (`VMFA_AI_ORGANIZER_VERSION` defined).

**Category slug:** `vmfo-ai-organizer`  
**REST namespace:** `vmfa/v1` (AI Organizer registers its scan routes on the shared base namespace)

Typical workflow: `start-scan` → poll `get-scan-status` until `status` is `completed`. Use `dry_run: true` to preview assignments without persisting them.

---

## `vmfo-ai-organizer/start-scan`

**Label:** Start AI Organizer Scan  
**Permission:** `manage_options`  
**Flags:** —

Starts an AI-powered batch scan that analyses unassigned media and either suggests or immediately applies folder assignments.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `mode` | string | No | — | `"analyze"` — preview suggestions only; `"apply"` — assign folders immediately |
| `dry_run` | boolean | No | `false` | When `true`, analyse media but do not persist any folder assignments |

### Output

```json
{ "status": "running", "started_at": "2026-05-02T10:00:00Z" }
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
        "ability_name": "vmfo-ai-organizer/start-scan",
        "parameters": { "mode": "analyze", "dry_run": true }
      }
    }
  }'
```

---

## `vmfo-ai-organizer/get-scan-status`

**Label:** Get AI Scan Status  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns the current progress of the running or last-completed AI Organizer scan.

### Input

No parameters.

### Output

```json
{
  "status": "completed",
  "mode": "analyze",
  "dry_run": true,
  "total": 340,
  "processed": 340,
  "percentage": 100,
  "applied": 0,
  "failed": 0,
  "results": [
    { "attachment_id": 1001, "suggested_folder_id": 42, "confidence": 0.91 }
  ],
  "started_at": "2026-05-02T10:00:00Z",
  "completed_at": "2026-05-02T10:02:18Z",
  "error": null
}
```

`status` values: `idle` · `running` · `completed` · `cancelled` · `failed`

### Example

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 1, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": { "ability_name": "vmfo-ai-organizer/get-scan-status", "parameters": {} }
    }
  }'
```

---

## `vmfo-ai-organizer/cancel-scan`

**Label:** Cancel AI Scan  
**Permission:** `manage_options`  
**Flags:** idempotent

Cancels the currently running AI Organizer scan. Safe to call when no scan is running.

### Input

No parameters.

### Output

```json
{ "cancelled": true }
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
      "arguments": { "ability_name": "vmfo-ai-organizer/cancel-scan", "parameters": {} }
    }
  }'
```
