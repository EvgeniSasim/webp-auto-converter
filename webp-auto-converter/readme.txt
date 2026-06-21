=== WebP Auto Converter ===
Contributors: evgeniisasim
Tags: webp, images, performance, media, optimization
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically convert uploaded JPEG and PNG images to WebP, serve WebP in responsive srcset, and clean up on delete.

== Description ==

WebP Auto Converter is a lightweight WordPress plugin that creates WebP copies of your JPEG and PNG uploads. Original files are kept; WebP siblings are stored next to them in the uploads directory.

**Features**

* Automatic conversion on upload (full size and all registered image sizes)
* Configurable WebP quality (0–100) in **Settings → WebP Converter**
* Batch tool to generate WebP for existing media library images
* Prefers WebP URLs in `srcset` when a sibling `.webp` file exists
* Removes WebP files when the attachment is deleted
* Uses Imagick when available, falls back to GD

**Requirements**

* PHP 7.4+
* GD with WebP support (`imagewebp`) or Imagick with WebP support

This plugin does not replace your theme's `<picture>` markup or server-level content negotiation. It focuses on generating WebP files and improving responsive image URLs where WordPress builds `srcset`.

== Installation ==

1. Upload the `webp-auto-converter` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → WebP Converter** to set quality and optionally run batch conversion for existing images.

== Frequently Asked Questions ==

= Does this delete my original JPEG or PNG files? =

No. The plugin creates additional `.webp` files alongside the originals.

= Will every visitor receive WebP images? =

The plugin swaps URLs in WordPress `srcset` output when a WebP sibling exists. Browsers that request those URLs will load WebP. For broader coverage, combine with theme `<picture>` elements or server rules if needed.

= What quality should I use? =

80–85 is a good starting point for photographs. Lower values reduce file size but may reduce visual quality.

= Does it work on existing uploads? =

Yes. Use **Generate WebP (batch)** on the settings page to process the media library in batches.

== Screenshots ==

1. Settings page with quality control and batch conversion
2. Automatic WebP generation on upload
3. WebP preferred in responsive srcset

== Changelog ==

= 1.2.1 =
* WordPress-native helpers without ACF: featured image, post meta, options, wp_attachment_image drop-in

= 1.2.0 =
* Theme helper functions for responsive `<picture>` WebP output (`webp_ac_*`)
* Optional `the_content` filter (disabled by default)
* Documentation: docs/theme-helpers.md

= 1.1.0 =
* WordPress.org release pack: readme, uninstall cleanup, i18n text domain
* Batch regeneration for existing media library images
* Imagick support with GD fallback
* WebP cleanup on attachment delete

= 1.0.0 =
* Initial release: upload conversion, srcset WebP preference, settings page

== Upgrade Notice ==

= 1.1.0 =
First public release on WordPress.org with batch conversion and improved metadata.
