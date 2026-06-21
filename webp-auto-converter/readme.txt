=== WebP Auto Converter ===
Contributors: evgeniisasim
Tags: webp, images, performance, media, optimization
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.3.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically convert uploaded JPEG and PNG images to WebP and serve them on the front end with zero theme integration.

== Description ==

WebP Auto Converter is a lightweight WordPress plugin that creates WebP copies of your JPEG and PNG uploads and **automatically outputs them on the front end** — no theme code required.

**Plug & play (enabled by default)**

* Featured images (`the_post_thumbnail`)
* `wp_get_attachment_image()` output
* Images in post content, text widgets, and block widgets
* Responsive `<picture>` with WebP `<source>` when sibling files exist

Disable under **Settings → WebP Converter** if your theme uses custom image helpers.

**Conversion**
* Configurable WebP quality (0–100) in **Settings → WebP Converter**
* Batch tool to generate WebP for existing media library images
* Prefers WebP URLs in `srcset` when a sibling `.webp` file exists
* Removes WebP files when the attachment is deleted
* Uses Imagick when available, falls back to GD

**Requirements**

* PHP 7.4+
* GD with WebP support (`imagewebp`) or Imagick with WebP support

== Installation ==

1. Upload the `webp-auto-converter` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Go to **Settings → WebP Converter** to adjust quality or batch-convert existing images. Front-end WebP output works automatically.

== Frequently Asked Questions ==

= Does this delete my original JPEG or PNG files? =

No. The plugin creates additional `.webp` files alongside the originals. With plug & play enabled, browsers that support WebP receive `<picture>` markup automatically.

= Do I need to edit my theme? =

No. Plug & play is on by default. Disable it in settings if your theme already outputs custom `<picture>` elements and you want to avoid double processing.

= Will every visitor receive WebP images? =

With plug & play enabled, the plugin outputs `<picture>` with a WebP `<source>` when sibling files exist. Browsers that support WebP load the smaller format; others fall back to JPEG/PNG via the `<img>` tag.

= What quality should I use? =

80–85 is a good starting point for photographs. Lower values reduce file size but may reduce visual quality.

= Does it work on existing uploads? =

Yes. Use **Generate WebP (batch)** on the settings page to process the media library in batches.

== Screenshots ==

1. Settings page with quality control and batch conversion
2. Automatic WebP generation on upload
3. WebP preferred in responsive srcset

== Changelog ==

= 1.3.0 =
* Plug & play mode: auto-enhance featured images, attachment images, and content (on by default)
* Settings toggle to disable front-end auto output
* Developer filters: `webp_ac_auto_output_enabled`, `webp_ac_should_auto_output`, `webp_ac_default_sizes_attr`

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
