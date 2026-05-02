# Tutorial 101: Upload & Organise a Photo

This is the core workflow: upload an image to WordPress, find the right folder, create one if it doesn't exist, and assign the image.

**Plugins required:** Virtual Media Folders · VMFA AI Ability · WordPress MCP Adapter

---

## What You'll Build

An agent or script that:

1. Uploads a photo to the WordPress Media Library.
2. Searches for a matching folder by name.
3. Creates the folder if it doesn't exist.
4. Assigns the photo to the folder.

---

## Step 1 — Upload the Photo

Media upload is handled by the standard WordPress REST API, not an ability. Upload the file first and capture the returned attachment `id`.

```bash
curl -s -X POST "https://example.com/wp-json/wp/v2/media" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Disposition: attachment; filename=beach-sunset.jpg" \
  -H "Content-Type: image/jpeg" \
  --data-binary "@/path/to/beach-sunset.jpg"
```

Response (truncated):

```json
{ "id": 1234, "title": { "rendered": "beach-sunset" }, "source_url": "..." }
```

Save the `id` — here `1234`.

---

## Step 2 — Search for a Matching Folder

Search existing folders by a keyword from the filename or context:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 2, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/list-folders",
        "parameters": { "search": "travel", "hide_empty": false }
      }
    }
  }'
```

Response:

```json
{
  "folders": [
    { "id": 42, "name": "Travel", "parent_id": 0, "path": "Travel", "count": 16 },
    { "id": 43, "name": "Travel/Beach", "parent_id": 42, "path": "Travel/Beach", "count": 4 }
  ],
  "total": 2
}
```

Use the `path` field to disambiguate folders with the same name. For a beach photo, `Travel/Beach` (id `43`) is the best match.

If `total` is `0`, proceed to Step 3. Otherwise skip to Step 4.

---

## Step 3 — Create the Folder (if missing)

If no suitable folder exists, create one. Use `parent_id` to nest it under an existing folder:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 3, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/create-folder",
        "parameters": { "name": "Beach", "parent_id": 42 }
      }
    }
  }'
```

Response:

```json
{ "id": 99, "name": "Beach", "parent_id": 42, "path": "Travel/Beach", "count": 0 }
```

Save the new folder `id` — here `99`.

---

## Step 4 — Assign the Photo to the Folder

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 4, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/add-to-folder",
        "parameters": { "folder_id": 43, "attachment_ids": [1234] }
      }
    }
  }'
```

Response:

```json
{
  "success": true,
  "folder_id": 43,
  "processed_count": 1,
  "results": [{ "attachment_id": 1234, "success": true, "message": "Assigned." }]
}
```

---

## Bonus: Ask for AI Suggestions First

Before committing to a folder, you can ask the AI Organizer for a confidence-ranked suggestion:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 5, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo/get-suggestions",
        "parameters": { "media_id": 1234 }
      }
    }
  }'
```

Response:

```json
{
  "suggestions": [
    { "folder_id": 43, "folder_name": "Travel/Beach", "score": 0.94 },
    { "folder_id": 42, "folder_name": "Travel", "score": 0.72 }
  ],
  "dismissed": []
}
```

Use the top suggestion's `folder_id` directly in Step 4.

---

## Summary

| Step | Ability | Purpose |
|---|---|---|
| 1 | WP REST `POST /wp/v2/media` | Upload photo, get attachment ID |
| 2 | `vmfo/list-folders` | Find folder by keyword |
| 3 | `vmfo/create-folder` | Create folder if missing |
| 4 | `vmfo/add-to-folder` | Assign photo to folder |
| Bonus | `vmfo/get-suggestions` | AI-ranked folder suggestions |

Next: [Tutorial 201 — Detect & Clean Up Unused Media](201-media-cleanup.md)
