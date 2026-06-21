# Contributing to WebP Auto Converter

Thank you for your interest in contributing. This project is open source (GPL-2.0-or-later).

## Ways to contribute

- **Bug reports** — [open an issue](https://github.com/EvgeniSasim/webp-auto-converter/issues/new/choose) with steps to reproduce, WordPress/PHP versions, and whether GD or Imagick is in use.
- **Pull requests** — fork, branch from `main`, keep changes focused, and describe testing performed.
- **Translations** — text domain is `webp-auto-converter`; submit `.po` files or GlotPress contributions on WordPress.org when the plugin is listed.

## Development setup

```bash
git clone https://github.com/EvgeniSasim/webp-auto-converter.git
cd webp-auto-converter
php -l webp-auto-converter/webp-auto-converter.php
```

### Coding standards

Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/). Plugin code lives in `webp-auto-converter/`.

## Pull request checklist

- [ ] `php -l` passes on changed PHP files
- [ ] User-facing strings use `webp-auto-converter` text domain and are escaped on output
- [ ] Admin AJAX uses nonces and capability checks
- [ ] `readme.txt` **Stable tag** matches plugin header `Version:` when releasing
- [ ] `CHANGELOG.md` updated for user-visible changes
- [ ] No secrets or site-specific URLs in commits

## Release process (maintainers)

1. Bump version in `webp-auto-converter.php`, `readme.txt`, `CHANGELOG.md`.
2. `bash scripts/build-release.sh`
3. Tag: `git tag v1.x.x && git push origin v1.x.x` (triggers GitHub Release with zip).
4. WordPress.org SVN: `bash scripts/svn-publish-release.sh 1.x.x` (after approval).

## Code of conduct

This project follows the [Contributor Covenant](CODE_OF_CONDUCT.md). Be respectful and constructive.
