# Tutorial 301: Automate Folder Assignment with Rules

This tutorial shows how to use the Rules Engine add-on to define automatic folder-assignment rules, preview their impact, and apply them to your existing media library.

**Plugins required:** Virtual Media Folders · VMFA AI Ability · vmfa-rules-engine · [WordPress MCP Adapter](https://github.com/wordpress/mcp-adapter)

---

## What You'll Build

An agent or script that:

1. Audits existing rules.
2. Creates a new rule targeting a specific pattern.
3. Previews which media it would match — without changing anything.
4. Applies the rule to existing unassigned media.
5. (Optional) Disables or deletes the rule later.

---

## Step 1 — Audit Existing Rules

List all current rules to understand the existing configuration:

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

Response:

```json
[
  {
    "id": "existing-rule-1",
    "name": "Product Photos",
    "folder_id": 10,
    "conditions": [{ "type": "filename", "operator": "contains", "value": "product" }],
    "enabled": true,
    "priority": 5
  }
]
```

Check priorities. Lower numbers run first; new rules should use a higher number unless they need to override an existing rule.

---

## Step 2 — Find (or Create) the Target Folder

Rules need a `folder_id`. Use `vmfo/list-folders` to find an existing folder:

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
        "parameters": { "search": "invoices" }
      }
    }
  }'
```

If the folder doesn't exist, create it with `vmfo/create-folder` (see [Tutorial 101](101-first-folder-workflow.md#step-3--create-the-folder-if-missing)).

In this example the "Invoices" folder has id `55`.

---

## Step 3 — Create the Rule

Create a rule that routes any file with "invoice" in its filename to the Invoices folder:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 3, "method": "tools/call",
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
          "enabled": true,
          "priority": 20,
          "description": "Route invoice PDFs and images to the Invoices folder."
        }
      }
    }
  }'
```

Response:

```json
{
  "id": "abc123",
  "name": "Invoices",
  "folder_id": 55,
  "conditions": [{ "type": "filename", "operator": "contains", "value": "invoice" }],
  "enabled": true,
  "priority": 20
}
```

Save the rule `id` — here `"abc123"`.

> **Note:** Once created, the rule will run automatically on new uploads. The next steps apply it to *existing* media.

---

## Step 4 — Preview the Match (Dry Run)

Before modifying any media, preview which existing items the rule would match:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 4, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-rules/preview",
        "parameters": {
          "rule_id": "abc123",
          "unassigned_only": true,
          "limit": 20
        }
      }
    }
  }'
```

Response:

```json
{
  "total_count": 540,
  "matched": 14,
  "unmatched": 526,
  "items": [
    { "attachment_id": 2001, "title": "invoice_jan_2026.pdf", "matched_rule_id": "abc123", "target_folder_id": 55 },
    { "attachment_id": 2002, "title": "invoice_feb_2026.pdf", "matched_rule_id": "abc123", "target_folder_id": 55 }
  ],
  "rule_id": "abc123"
}
```

Review the `items` list. If the matches look correct, proceed. If not, update the rule's conditions with `vmfo-rules/update-rule` and preview again.

---

## Step 5 — Apply the Rule

Apply the rule to all unassigned media. Matched items are moved to the target folder:

```bash
curl -s -X POST "https://example.com/wp-json/mcp/mcp-adapter-default-server" \
  -u "admin:xxxx xxxx xxxx xxxx xxxx xxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "jsonrpc": "2.0", "id": 5, "method": "tools/call",
    "params": {
      "name": "mcp-adapter-execute-ability",
      "arguments": {
        "ability_name": "vmfo-rules/apply",
        "parameters": { "rule_id": "abc123", "unassigned_only": true }
      }
    }
  }'
```

Response:

```json
{ "processed": 540, "assigned": 14, "skipped": 526 }
```

---

## Iterating and Refining

### Update a rule condition

```bash
"ability_name": "vmfo-rules/update-rule",
"parameters": {
  "rule_id": "abc123",
  "conditions": [
    { "type": "filename", "operator": "contains", "value": "invoice" },
    { "type": "mimetype", "operator": "equals", "value": "application/pdf" }
  ]
}
```

### Disable without deleting

```bash
"ability_name": "vmfo-rules/update-rule",
"parameters": { "rule_id": "abc123", "enabled": false }
```

### Run all enabled rules at once

Omit `rule_id` from `vmfo-rules/apply` to apply every enabled rule:

```bash
"ability_name": "vmfo-rules/apply",
"parameters": { "unassigned_only": true }
```

---

## Summary

| Step | Ability | Purpose |
|---|---|---|
| 1 | `vmfo-rules/list-rules` | Audit existing rules and priorities |
| 2 | `vmfo/list-folders` | Resolve target folder ID |
| 3 | `vmfo-rules/create-rule` | Define the new assignment rule |
| 4 | `vmfo-rules/preview` | Dry-run: inspect matches before committing |
| 5 | `vmfo-rules/apply` | Assign matching media to folders |
| — | `vmfo-rules/update-rule` | Refine conditions or disable rule |
| — | `vmfo-rules/delete-rule` | Remove rule permanently |

Previous: [Tutorial 201](201-media-cleanup.md)
