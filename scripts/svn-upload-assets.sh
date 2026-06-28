#!/usr/bin/env bash
# Upload wordpress-org/assets PNG files to WordPress.org SVN /assets.
#
# Usage:
#   bash scripts/svn-upload-assets.sh [checkout-dir]

set -euo pipefail

CHECKOUT_DIR="${1:-/tmp/webp-auto-converter-svn}"
PLUGIN_SLUG="privaro-webp-auto-converter"
REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SVN_URL="https://plugins.svn.wordpress.org/${PLUGIN_SLUG}"
ASSETS_SRC="${REPO_ROOT}/wordpress-org/assets"

if [[ ! -d "${ASSETS_SRC}" ]]; then
	echo "Missing ${ASSETS_SRC}. Run scripts/generate-wporg-assets.py first."
	exit 1
fi

if [[ ! -d "${CHECKOUT_DIR}/.svn" ]]; then
	svn co "${SVN_URL}" "${CHECKOUT_DIR}"
fi

mkdir -p "${CHECKOUT_DIR}/assets"
cp "${ASSETS_SRC}"/*.png "${CHECKOUT_DIR}/assets/" 2>/dev/null || true

cd "${CHECKOUT_DIR}"
svn add --force assets 2>/dev/null || true
svn status
echo
echo "Review the status above, then commit from ${CHECKOUT_DIR}:"
echo "  svn ci -m \"Update plugin assets\""
