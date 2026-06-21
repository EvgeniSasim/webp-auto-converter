# Changelog

All notable changes to this project are documented here.

## [1.2.1] - 2026-06-21

### Added
- `webp_ac_get_the_post_thumbnail_html()` / `webp_ac_the_post_thumbnail()`
- `webp_ac_get_image_from_post_meta()` / `webp_ac_get_image_from_option()`
- `webp_ac_wp_attachment_image()` — drop-in for `wp_get_attachment_image()`
- `webp_ac_get_the_content_images_html()` for post content without ACF

## [1.2.0] - 2026-06-21

### Added
- `includes/image-helpers.php` — `webp_ac_get_image_html()`, icons, hero, content replacement
- Optional `the_content` filter via `webp_ac_filter_the_content`
- `docs/theme-helpers.md` with usage examples

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
