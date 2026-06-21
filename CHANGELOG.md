# Changelog

All notable changes to this project are documented here.

## [1.1.0] - 2026-06-21

### Added
- WordPress.org release pack (`readme.txt`, uninstall cleanup, text domain)
- Batch regeneration tool for existing media library images
- Imagick conversion path with GD fallback
- WebP cleanup when attachments are deleted

### Changed
- Admin UI strings are translatable (`webp-auto-converter` text domain)
- Uses `wp_delete_file()` for WebP removal

## [1.0.0] - 2025

### Added
- Automatic WebP conversion on upload
- Quality setting in admin
- WebP preference in responsive `srcset`
