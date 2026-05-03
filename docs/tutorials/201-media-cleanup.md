# Tutorial 201: Detect & Clean Up Unused Media

This tutorial shows how to use the Media Cleanup add-on abilities to discover unused, duplicate, or oversized files and bulk-action them safely.

**Plugins required:** [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) · [VMFA AI Ability](https://github.com/soderlind/vmfa-ai-ability) · [vmfa-media-cleanup](https://github.com/soderlind/vmfa-media-cleanup) · [WordPress MCP Adapter](https://github.com/wordpress/mcp-adapter)

---

## What You'll Build

An agent or script that:

1. Checks current cleanup statistics.
2. Starts a background scan.
3. Polls until the scan finishes.
4. Reviews the unused-file results.
5. Trashes the confirmed unused items.

---

## Step 1 — Check Current Stats

Before starting a scan, get a quick overview of the last completed scan's findings:

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

Response:

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

If the numbers are stale (or this is the first run), start a fresh scan.

---

## Step 2 — Start a Scan

Start a scan limited to unused and duplicate detection:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 2, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/start-scan",
        "parameters": { "types": ["unused", "duplicate"] }
      }
    }
  }'
```

Response:

```json
{ "status": "running", "started_at": "2026-05-02T10:00:00Z" }
```

---

## Step 3 — Poll Until Complete

Call `get-scan-status` repeatedly until `status` is `completed` (or `failed`):

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 3, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": { "ability_name": "vmfo-cleanup/get-scan-status", "parameters": {} }
    }
  }'
```

Partial response mid-scan:

```json
{
  "status": "running",
  "phase": "unused",
  "total": 1240,
  "processed": 640,
  "started_at": "2026-05-02T10:00:00Z",
  "completed_at": null
}
```

Completed response:

```json
{
  "status": "completed",
  "total": 1240,
  "processed": 1240,
  "completed_at": "2026-05-02T10:02:10Z"
}
```

A reasonable polling interval is 5–10 seconds.

---

## Step 4 — List Unused Results

Retrieve the first page of unused media items:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 4, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/list-results",
        "parameters": { "type": "unused", "per_page": 50, "page": 1 }
      }
    }
  }'
```

Response:

```json
{
  "items": [
    { "id": 1001, "title": "old-banner-2019.png", "url": "...", "type": "unused", "file_size": 204800 },
    { "id": 1002, "title": "draft-hero.jpg",       "url": "...", "type": "unused", "file_size": 819200 }
  ],
  "total": 87,
  "page": 1,
  "per_page": 50,
  "total_pages": 2
}
```

Review the list. Collect the `id` values for items safe to remove.

---

## Step 5 — Trash the Items

Trash is reversible — prefer it over permanent deletion.

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "username:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 5, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-cleanup/trash",
        "parameters": { "attachment_ids": [1001, 1002] }
      }
    }
  }'
```

Response:

```json
{ "trashed": 2, "failed": 0 }
```

If you are certain items are safe to remove permanently, use `vmfo-cleanup/delete` instead. See [media-cleanup.md](../abilities/media-cleanup.md#vmfo-cleanupdelete) for details and a warning.

---

## Dealing with Duplicates

For duplicates, retrieve the list with `type: "duplicate"`, identify the canonical version you want to keep, collect the redundant IDs, and archive or trash them:

```bash
# List duplicate results
"parameters": { "type": "duplicate", "per_page": 50 }

# Archive the extras (reversible)
"ability_name": "vmfo-cleanup/archive",
"parameters": { "attachment_ids": [1003, 1004] }
```

---

## Summary

| Step | Ability | Purpose |
|---|---|---|
| 1 | `vmfo-cleanup/get-stats` | Overview of last scan |
| 2 | `vmfo-cleanup/start-scan` | Start background scan |
| 3 | `vmfo-cleanup/get-scan-status` | Poll for completion |
| 4 | `vmfo-cleanup/list-results` | Review flagged items |
| 5 | `vmfo-cleanup/trash` | Soft-delete unused media |
| — | `vmfo-cleanup/delete` | Hard-delete (irreversible) |

Previous: [Tutorial 101](101-first-folder-workflow.md) · Next: [Tutorial 301 — Automate Folder Assignment with Rules](301-rules-automation.md)
