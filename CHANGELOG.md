# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.2.0]: https://github.com/soderlind/vmfa-ai-ability/compare/1.1.0...1.2.0
[1.1.0]: https://github.com/soderlind/vmfa-ai-ability/compare/1.0.0...1.1.0
[1.0.0]: https://github.com/soderlind/vmfa-ai-ability/releases/tag/1.0.0
