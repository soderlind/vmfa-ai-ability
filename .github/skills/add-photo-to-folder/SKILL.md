---
name: add-photo-to-folder
description: "Use when uploading or organizing images in Virtual Media Folders using MCP. Triggers: add photo to folder, place image by content, classify image into folder, auto-folder image. Uses vmfo/list-folders, vmfo/create-folder, and vmfo/add-to-folder through mcp-adapter-execute-ability. Requires VMFA AI Ability add-on."
---

# Add Photo To Folder

Organize WordPress media library images into Virtual Media Folders based on content, topic, or user intent.

## Installation

```bash
npx skills add soderlind/vmfa-ai-ability@add-photo-to-folder
```

## Prerequisites

Before using this skill, ensure you have:

1. **WordPress site** with these plugins active:
   - [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) (free)
   - [VMFA AI Ability](https://github.com/soderlind/vmfa-ai-ability) (add-on)
   - [WordPress MCP Adapter](https://github.com/wordpress/mcp-adapter) (connects MCP to WordPress)

2. **MCP server configured** in your AI client (VS Code, Claude Desktop, Cursor):
   - Endpoint: `https://your-site.com/wp-json/mcp/mcp-adapter-default-server`
   - Authentication: WordPress Application Password with Basic Auth

3. **User capabilities**:
   - `upload_files` — required for listing folders and assigning media
   - `manage_categories` — required only when creating new folders

## When to Use

Use this skill when:

- User says "add this photo to the Travel folder"
- User uploads an image and asks "where should this go?"
- User wants to auto-organize images by content (food, nature, team photos)
- User asks to "classify this image into a folder"
- Batch organizing multiple uploads into topic-based folders

## Quick Example

**User prompt:**
> "Add the beach sunset photo (attachment 1234) to my Travel folder"

**Agent workflow:**

1. Search for existing Travel folder
2. Resolve folder by path (handles duplicates)
3. Assign attachment 1234 to the folder
4. Report: "Added beach-sunset.jpg to Travel (folder ID 42)"

## Required Inputs

| Input | Type | Required | Description |
|-------|------|----------|-------------|
| `attachment_id` | integer | ✅ | WordPress media ID from upload |
| `topic` | string | ✅ | Folder name or topic label (e.g., "Travel", "Food", "Team") |
| `parent_id` | integer | ❌ | Parent folder ID for nested folders (default: 0 = root) |

## Available MCP Tools

All tools are called through the `mcp-adapter-execute-ability` gateway:

### vmfo/list-folders

Search and list existing folders.

```json
{
  "ability_name": "vmfo/list-folders",
  "parameters": {
    "search": "travel",
    "hide_empty": false
  }
}
```

**Returns:** Array of folders with `id`, `name`, `path`, `parent_id`, `count`

### vmfo/create-folder

Create a new folder (requires `manage_categories` capability).

```json
{
  "ability_name": "vmfo/create-folder",
  "parameters": {
    "name": "Travel",
    "parent_id": 0
  }
}
```

**Returns:** Created folder with `id`, `name`, `path`, `parent_id`

### vmfo/add-to-folder

Assign one or more attachments to a folder.

```json
{
  "ability_name": "vmfo/add-to-folder",
  "parameters": {
    "folder_id": 42,
    "attachment_ids": [1234, 1235, 1236]
  }
}
```

**Returns:** Success status with assigned attachment IDs

## Step-by-Step Procedure

### Step 1: Search for Existing Folder

Always check if the target folder exists before creating:

```json
{
  "method": "tools/call",
  "params": {
    "name": "mcp-adapter-execute-ability",
    "arguments": {
      "ability_name": "vmfo/list-folders",
      "parameters": {
        "search": "travel",
        "hide_empty": false
      }
    }
  }
}
```

### Step 2: Resolve Folder by Path

When results return, prefer matching by `path` over `name`:

```json
// Example response
[
  { "id": 10, "name": "Travel", "path": "Travel", "parent_id": 0 },
  { "id": 25, "name": "Travel", "path": "Photos/Travel", "parent_id": 5 }
]
```

- If user said "Travel" with no parent context → use ID 10
- If user said "Photos/Travel" → use ID 25

### Step 3: Create Folder If Missing

Only create when no matching folder exists:

```json
{
  "method": "tools/call",
  "params": {
    "name": "mcp-adapter-execute-ability",
    "arguments": {
      "ability_name": "vmfo/create-folder",
      "parameters": {
        "name": "Travel",
        "parent_id": 0
      }
    }
  }
}
```

### Step 4: Assign Image to Folder

Use the resolved or newly created folder ID:

```json
{
  "method": "tools/call",
  "params": {
    "name": "mcp-adapter-execute-ability",
    "arguments": {
      "ability_name": "vmfo/add-to-folder",
      "parameters": {
        "folder_id": 42,
        "attachment_ids": [1234]
      }
    }
  }
}
```

### Step 5: Return Result

Report a compact summary to the user:

```json
{
  "attachment_id": 1234,
  "folder_id": 42,
  "folder_path": "Travel",
  "created": false,
  "status": "success"
}
```

## Guardrails

| Rule | Reason |
|------|--------|
| Always list folders before creating | Prevents duplicate folders |
| Match by `path`, not just `name` | Handles folders with same name in different parents |
| Stop on permission errors | Don't retry with guessed IDs |
| Validate attachment IDs exist | Prevents silent failures |
| Never create folders blindly | User may have existing organization |

## Error Handling

| Error | Cause | Resolution |
|-------|-------|------------|
| `401 Unauthorized` | Invalid Application Password | Check credentials and regenerate if needed |
| `403 Forbidden` | Missing capability | User needs `manage_categories` to create folders |
| `term_exists` | Folder already exists | Re-run list-folders and resolve by path |
| `invalid_attachment` | Attachment ID doesn't exist | Verify media upload succeeded |
| Tool not found | MCP adapter misconfigured | Run `tools/list` to verify available tools |

## Complete Workflow Example

**Scenario:** User uploads `beach-sunset.jpg` and asks to add it to Travel folder.

```
1. Upload image via WordPress REST API
   POST /wp-json/wp/v2/media
   → Returns: { "id": 1234, "title": "beach-sunset" }

2. Search for Travel folder
   vmfo/list-folders { "search": "travel" }
   → Returns: [{ "id": 42, "name": "Travel", "path": "Travel" }]

3. Folder exists, skip creation

4. Assign image to folder
   vmfo/add-to-folder { "folder_id": 42, "attachment_ids": [1234] }
   → Returns: { "success": true }

5. Report to user
   "Added beach-sunset.jpg to Travel folder"
```

## Related Documentation

- [MCP Integration Guide](https://github.com/soderlind/vmfa-ai-ability/blob/main/docs/mcp.md) — Full MCP setup and curl examples
- [Virtual Media Folders](https://github.com/soderlind/virtual-media-folders) — Core plugin documentation
- [WordPress MCP Adapter](https://github.com/wordpress/mcp-adapter) — MCP server for WordPress
