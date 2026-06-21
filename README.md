# WebP Auto Converter

[![Release](https://github.com/EvgeniSasim/webp-auto-converter/actions/workflows/release.yml/badge.svg)](https://github.com/EvgeniSasim/webp-auto-converter/actions/workflows/release.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE)

Open-source WordPress plugin that automatically converts uploaded JPEG and PNG images to WebP and serves them on the front end in **plug & play** mode — no theme integration required.

| | |
|---|---|
| **Plugin directory** | `webp-auto-converter/` |
| **Plug & play** | On by default — [docs/theme-helpers.md](docs/theme-helpers.md) |
| **Contributing** | [CONTRIBUTING.md](CONTRIBUTING.md) |
| **Security** | [SECURITY.md](SECURITY.md) |
| **Releases** | [GitHub Releases](https://github.com/EvgeniSasim/webp-auto-converter/releases) |
| **WordPress.org** | Submit via [docs/wordpress-org-submission.md](docs/wordpress-org-submission.md) |

**Changelog:** [CHANGELOG.md](CHANGELOG.md)

## Features

- **Plug & play** — featured images, attachment images, post/widget content (toggle in settings)
- Automatic WebP generation on upload (full image + all registered sizes)
- Configurable quality in **Settings → WebP Converter**
- Batch conversion for existing media library images
- WebP URLs in `srcset` when a sibling file exists
- Imagick with GD fallback
- Uninstall removes plugin options
- **Theme helpers** for `<picture>` output — see [docs/theme-helpers.md](docs/theme-helpers.md)

## Installation

Copy `webp-auto-converter/` into `wp-content/plugins/` or symlink:

```bash
ln -s "$(pwd)/webp-auto-converter" /path/to/wp-content/plugins/webp-auto-converter
```

Activate in the WordPress admin. **No theme code needed** — WebP output is automatic on the front end.

Optional theme helpers for custom markup: [docs/theme-helpers.md](docs/theme-helpers.md)

### Requirements

- WordPress 5.8+
- PHP 7.4+
- GD with `imagewebp` or Imagick with WebP support

## Development

```bash
git clone https://github.com/EvgeniSasim/webp-auto-converter.git
cd webp-auto-converter
php -l webp-auto-converter/webp-auto-converter.php
bash scripts/build-release.sh
```

### WordPress.org assets

```bash
python3 -m venv .venv-assets && source .venv-assets/bin/activate
pip install -r scripts/requirements-assets.txt
python3 scripts/generate-wporg-assets.py
```

### Theme image helpers (optional)

Plug & play is enabled by default. Use helpers only for custom templates:

```php
echo webp_ac_get_image_from_post_meta( get_the_ID(), 'hero_image_id', [ 'is_lcp' => true ] );
```

Disable auto output in settings or via code:

```php
add_filter( 'webp_ac_auto_output_enabled', '__return_false' );
```

Full examples: [docs/theme-helpers.md](docs/theme-helpers.md)

## Author

**Evgenii Sasim** — [Instagram](https://www.instagram.com/evgenii.sasim/)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
