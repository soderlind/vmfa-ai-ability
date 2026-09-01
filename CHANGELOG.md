# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.4.0] - 2026-09-01

### Security

- Add tier-2 per-object authorization to media abilities. `vmfo/add-to-folder`, `vmfo/remove-from-folder`, and `vmfo/get-suggestions` now verify the caller can edit each target attachment (`edit_post`), closing an IDOR gap where any user with `upload_files` could act on attachments they do not own.
- Deny ability invocations made with no authenticated user (MCP/agent/background contexts) and return a uniform HTTP 403 for all authorization failures.

## [1.3.1] - 2026-08-12

### Fixed

- Prevent a fatal error when the "Virtual Media Folders" parent plugin is missing or older than 2.0.0; show an admin notice instead.

## [1.3.0] - 2026-08-03

### Added
- Top-level `public` meta flag on all abilities for WordPress 7.1 REST/MCP/AI discovery.
- Automatic Title Case schema `title` for every ability input/output property (via the `wp_register_ability_args` filter).
- `vmfa_ai_ability_invoked` action hook for auditing/telemetry, built on the WordPress 7.1 `wp_ability_invoked` action (raw input intentionally omitted).

## [1.2.0] - 2026-05-02

### Added
- Add MCP abilities for base plugin and 4 add-ons (Rules Engine, Media Cleanup, Folder Exporter, AI Organizer)

### Fixed
- Remove from folder sends media_id per attachment to match REST endpoint signature

### Documentation
- Add per-ability reference docs and README.md for each docs subdirectory
- Add 101/201/301 tutorials for common workflows
- Refactor mcp.md into focused authentication and endpoint reference

## [1.1.0] - 2026-04-19

### Added
- GitHub updater for automatic updates from releases
- GitHub Actions workflows for building release zip (`on-release-add.zip.yml`, `manually-build-zip.yml`)

## [1.0.0] - 2026-04-18

### Added
- Initial release
- Extracted Abilities API integration from Virtual Media Folders core plugin
- `vmfo/list-folders` ability - Lists folders with IDs, names, and paths
- `vmfo/create-folder` ability - Creates a folder with optional parent
- `vmfo/add-to-folder` ability - Assigns media items to a folder
- MCP adapter support for AI agents (Claude, Copilot, Cursor)

[Unreleased]: https://github.com/soderlind/vmfa-ai-ability/compare/1.3.0...HEAD
[1.3.0]: https://github.com/soderlind/vmfa-ai-ability/compare/1.2.0...1.3.0
[1.2.0]: https://github.com/soderlind/vmfa-ai-ability/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/soderlind/vmfa-ai-ability/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/soderlind/vmfa-ai-ability/releases/tag/1.0.0
