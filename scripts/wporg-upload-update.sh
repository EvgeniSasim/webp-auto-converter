#!/usr/bin/env bash
# Upload an updated plugin ZIP during an in-progress WordPress.org review.
#
# Usage:
#   WPORG_USER=evgenij347 WPORG_PASS='...' bash scripts/wporg-upload-update.sh [zip-path] [comment]

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ZIP_PATH="${1:-${REPO_ROOT}/build/privaro-webp-auto-converter.zip}"
COMMENT="${2:-WebP Auto Converter 1.4.0 — review update.}"

exec env WPORG_USER="${WPORG_USER:-}" WPORG_PASS="${WPORG_PASS:-}" \
	bash "${REPO_ROOT}/scripts/wporg-submit-plugin.sh" "${ZIP_PATH}"
