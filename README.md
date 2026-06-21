# WebP Auto Converter

[![Release](https://github.com/EvgeniSasim/webp-auto-converter/actions/workflows/release.yml/badge.svg)](https://github.com/EvgeniSasim/webp-auto-converter/actions/workflows/release.yml)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](LICENSE)

Open-source WordPress plugin that automatically converts uploaded JPEG and PNG images to WebP, prefers WebP in responsive `srcset`, and cleans up WebP siblings when attachments are deleted.

| | |
|---|---|
| **Plugin directory** | `webp-auto-converter/` |
| **Contributing** | [CONTRIBUTING.md](CONTRIBUTING.md) |
| **Security** | [SECURITY.md](SECURITY.md) |
| **Releases** | [GitHub Releases](https://github.com/EvgeniSasim/webp-auto-converter/releases) |
| **WordPress.org** | Submit via [docs/wordpress-org-submission.md](docs/wordpress-org-submission.md) |

**Changelog:** [CHANGELOG.md](CHANGELOG.md)

## Features

- Automatic WebP generation on upload (full image + all registered sizes)
- Configurable quality in **Settings → WebP Converter**
- Batch conversion for existing media library images
- WebP URLs in `srcset` when a sibling file exists
- Imagick with GD fallback
- Uninstall removes plugin options

## Installation

Copy `webp-auto-converter/` into `wp-content/plugins/` or symlink:

```bash
ln -s "$(pwd)/webp-auto-converter" /path/to/wp-content/plugins/webp-auto-converter
```

Activate in the WordPress admin, then open **Settings → WebP Converter**.

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

## Author

**Evgenii Sasim** — [Instagram](https://www.instagram.com/evgenii.sasim/)

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
