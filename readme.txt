=== Virtual Media Folders - AI Ability ===
Contributors: persoderlind
Tags: media, folders, ai, mcp, abilities
Requires at least: 7.0
Tested up to: 7.1
Requires PHP: 8.3
Stable tag: 1.4.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Exposes Virtual Media Folders operations as WordPress Abilities API tools for AI agents and MCP adapters.

== Description ==

This add-on for Virtual Media Folders registers folder management operations as WordPress Abilities API tools, making them available to AI agents and MCP (Model Context Protocol) adapters.

= Registered Abilities =

* `vmfo/list-folders` - Lists folders with IDs, names, and paths for name-to-ID resolution
* `vmfo/create-folder` - Creates a folder with an optional parent
* `vmfo/add-to-folder` - Assigns one or more media items to a folder

= MCP Integration =

For client configuration (Claude, GitHub Copilot, Cursor) and usage examples, see the [MCP Integration Guide](https://github.com/soderlind/vmfa-ai-ability/blob/main/docs/mcp.md).

== Installation ==

1. Ensure Virtual Media Folders is installed and activated
2. Upload `vmfa-ai-ability` to `/wp-content/plugins/`
3. Run `composer install` in the plugin directory
4. Activate the plugin through WordPress admin

== Changelog ==

= 1.4.0 =
* Security: Add tier-2 per-object authorization to media abilities. `vmfo/add-to-folder`, `vmfo/remove-from-folder`, and `vmfo/get-suggestions` now verify the caller can edit each target attachment (`edit_post`), closing an IDOR gap where any user with `upload_files` could act on attachments they do not own.
* Security: Deny ability invocations made with no authenticated user (MCP/agent/background contexts) and return a uniform HTTP 403 for all authorization failures.

= 1.3.1 =
* Fixed: Prevent a fatal error when the "Virtual Media Folders" parent plugin is missing or older than 2.0.0; show an admin notice instead.

= 1.3.0 =
* Added: Top-level `public` meta flag on all abilities for WordPress 7.1 REST/MCP/AI discovery.
* Added: Automatic Title Case schema `title` for every ability input/output property.
* Added: `vmfa_ai_ability_invoked` action hook for auditing/telemetry (WordPress 7.1 `wp_ability_invoked`; raw input omitted).

= 1.2.0 =
* Added: MCP abilities for base plugin and 4 add-ons (Rules Engine, Media Cleanup, Folder Exporter, AI Organizer)
* Fixed: Remove from folder now sends media_id per attachment to match REST endpoint signature
* Documentation: Per-ability reference docs, 101/201/301 tutorials, refactored mcp.md to auth reference

= 1.1.0 =
* Added: GitHub updater for automatic updates from releases
* Added: GitHub Actions workflows for building release zip

= 1.0.0 =
* Initial release
* Extracted from Virtual Media Folders core plugin
* Registers `vmfo/list-folders`, `vmfo/create-folder`, `vmfo/add-to-folder` abilities
