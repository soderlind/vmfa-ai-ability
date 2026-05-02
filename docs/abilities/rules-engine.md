# Rules Engine Abilities

These abilities are active when the **[vmfa-rules-engine](https://github.com/soderlind/vmfa-rules-engine)** add-on is installed and active (`VMFA_RULES_ENGINE_VERSION` defined).

**Category slug:** `vmfo-rules-engine`  
**REST namespace:** `vmfa-rules/v1`

---

## `vmfo-rules/list-rules`

**Label:** List Rules  
**Permission:** `upload_files`  
**Flags:** readonly · idempotent

Returns all folder-assignment rules ordered by priority.

### Input

No parameters.

### Output

An array of rule objects:

```json
[
  {
    "id": "abc123",
    "name": "Invoices",
    "folder_id": 55,
    "conditions": [
      { "type": "filename", "operator": "contains", "value": "invoice" }
    ],
    "enabled": true,
    "priority": 10,
    "description": "Route invoice PDFs to the Invoices folder."
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
      "arguments": { "ability_name": "vmfo-rules/list-rules", "parameters": {} }
    }
  }'
```

---

## `vmfo-rules/create-rule`

**Label:** Create Rule  
**Permission:** `manage_options`  
**Flags:** —

Creates a new folder-assignment rule.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `name` | string (min 1) | **Yes** | — | Rule display name |
| `folder_id` | integer ≥ 1 | **Yes** | — | Target folder term ID |
| `conditions` | array | **Yes** | — | Array of condition objects (see below) |
| `enabled` | boolean | No | `true` | Whether the rule runs automatically |
| `priority` | integer ≥ 1 | No | — | Evaluation order; lower = evaluated first |
| `description` | string | No | — | Human-readable description |

**Condition object:**

```json
{ "type": "filename|mimetype|title|alt", "operator": "contains|equals|starts_with|ends_with", "value": "..." }
```

### Output

The created rule object (same shape as list-rules items).

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
        "ability_name": "vmfo-rules/create-rule",
        "parameters": {
          "name": "Invoices",
          "folder_id": 55,
          "conditions": [
            { "type": "filename", "operator": "contains", "value": "invoice" }
          ],
          "priority": 10
        }
      }
    }
  }'
```

---

## `vmfo-rules/update-rule`

**Label:** Update Rule  
**Permission:** `manage_options`  
**Flags:** idempotent

Updates an existing rule. Only the fields you supply are changed.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `rule_id` | string | **Yes** | — | Rule ID to update (from list-rules) |
| `name` | string | No | — | New display name |
| `folder_id` | integer ≥ 1 | No | — | New target folder |
| `conditions` | array | No | — | Replacement condition list |
| `enabled` | boolean | No | — | Enable or disable the rule |
| `priority` | integer ≥ 1 | No | — | New priority |
| `description` | string | No | — | New description |

### Output

The updated rule object.

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
        "ability_name": "vmfo-rules/update-rule",
        "parameters": { "rule_id": "abc123", "enabled": false }
      }
    }
  }'
```

---

## `vmfo-rules/delete-rule`

**Label:** Delete Rule  
**Permission:** `manage_options`  
**Flags:** destructive

Permanently deletes a folder-assignment rule. Existing media assignments are not changed.

### Input

| Parameter | Type | Required | Description |
|---|---|---|---|
| `rule_id` | string | **Yes** | Rule ID to delete |

### Output

```json
{ "deleted": true, "rule_id": "abc123" }
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
        "ability_name": "vmfo-rules/delete-rule",
        "parameters": { "rule_id": "abc123" }
      }
    }
  }'
```

---

## `vmfo-rules/preview`

**Label:** Preview Rule Matches  
**Permission:** `upload_files`  
**Flags:** readonly

Simulates which media items would be assigned by a rule (or all enabled rules) without making any changes.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `rule_id` | string | No | — | Preview a specific rule; omit to preview all enabled rules |
| `unassigned_only` | boolean | No | `true` | Limit preview to media not yet in any folder |
| `limit` | integer | No | `50` | Max items to return |
| `offset` | integer | No | `0` | Pagination offset |

### Output

```json
{
  "total_count": 120,
  "matched": 14,
  "unmatched": 106,
  "items": [ { "attachment_id": 1001, "title": "invoice_jan.pdf", "matched_rule_id": "abc123", "target_folder_id": 55 } ],
  "rule_id": "abc123"
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
        "ability_name": "vmfo-rules/preview",
        "parameters": { "rule_id": "abc123", "unassigned_only": true }
      }
    }
  }'
```

---

## `vmfo-rules/apply`

**Label:** Apply Rules  
**Permission:** `manage_options`  
**Flags:** —

Applies all enabled rules to existing media, assigning items to their target folders. This is the batch-run equivalent of the automatic rule engine that runs on upload.

### Input

| Parameter | Type | Required | Default | Description |
|---|---|---|---|---|
| `rule_id` | string | No | — | Apply only this rule; omit to apply all enabled rules |
| `unassigned_only` | boolean | No | `true` | Only process media not already in a folder |

### Output

```json
{
  "processed": 120,
  "assigned": 14,
  "skipped": 106
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
        "ability_name": "vmfo-rules/apply",
        "parameters": { "unassigned_only": true }
      }
    }
  }'
```
