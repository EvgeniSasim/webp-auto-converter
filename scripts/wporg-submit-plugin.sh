#!/usr/bin/env bash
# Submit a new plugin ZIP to WordPress.org for manual review (initial submission).
#
# Usage:
#   WPORG_USER=evgenij347 WPORG_PASS='app-password' bash scripts/wporg-submit-plugin.sh [zip-path]
#
# Defaults:
#   zip-path -> build/sasim-webp-auto-converter.zip

set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ZIP_PATH="${1:-${REPO_ROOT}/build/sasim-webp-auto-converter.zip}"

if [[ -z "${WPORG_USER:-}" || -z "${WPORG_PASS:-}" ]]; then
	echo "Set WPORG_USER and WPORG_PASS environment variables." >&2
	exit 2
fi

if [[ ! -f "${ZIP_PATH}" ]]; then
	echo "ZIP not found: ${ZIP_PATH}" >&2
	echo "Run: bash scripts/build-release.sh" >&2
	exit 2
fi

COOKIE_JAR="$(mktemp)"
trap 'rm -f "${COOKIE_JAR}"' EXIT

curl -sS -c "${COOKIE_JAR}" -b "${COOKIE_JAR}" 'https://login.wordpress.org/wp-login.php' -o /dev/null

curl -sS -c "${COOKIE_JAR}" -b "${COOKIE_JAR}" -L -X POST 'https://login.wordpress.org/wp-login.php' \
	-H 'Cookie: wordpress_test_cookie=WP%20Cookie%20check' \
	--data-urlencode "log=${WPORG_USER}" \
	--data-urlencode "pwd=${WPORG_PASS}" \
	--data-urlencode 'rememberme=forever' \
	--data-urlencode 'wp-submit=Log In' \
	--data-urlencode 'redirect_to=https://wordpress.org/plugins/developers/add/' \
	--data-urlencode 'testcookie=1' \
	-o /tmp/wporg-add.html

if ! grep -qi 'Howdy' /tmp/wporg-add.html; then
	echo "Login failed. Check WPORG_USER / WPORG_PASS (2FA may block automated login)." >&2
	exit 1
fi

NONCE="$(python3 - <<'PY'
import re
html = open('/tmp/wporg-add.html').read()
m = re.search(r'name="_wpnonce"\s+value="([^"]+)"', html)
print(m.group(1) if m else '')
PY
)"

PLUGIN_ID="$(python3 - <<'PY'
import re
html = open('/tmp/wporg-add.html').read()
m = re.search(r'name="plugin_id"\s+value="(\d+)"', html)
print(m.group(1) if m else '')
PY
)"

echo "Uploading ${ZIP_PATH} ..."

if [[ -n "${PLUGIN_ID}" ]]; then
	echo "Existing review detected (plugin_id=${PLUGIN_ID}); using upload-additional."
	curl -sS -b "${COOKIE_JAR}" -L -X POST 'https://wordpress.org/plugins/developers/add/' \
		-F "_wpnonce=${NONCE}" \
		-F "_wp_http_referer=/plugins/developers/add/" \
		-F "action=upload-additional" \
		-F "plugin_id=${PLUGIN_ID}" \
		-F "comment=Sasim WebP Auto Converter 1.4.1 — renamed slug and prefixed identifiers per review." \
		-F "zip_file=@${ZIP_PATH};type=application/zip" \
		-o /tmp/wporg-upload-result.html
else
	curl -sS -b "${COOKIE_JAR}" -L -X POST 'https://wordpress.org/plugins/developers/add/' \
		-F "_wpnonce=${NONCE}" \
		-F "_wp_http_referer=/plugins/developers/add/" \
		-F "zip_file=@${ZIP_PATH};type=application/zip" \
		-o /tmp/wporg-upload-result.html
fi

python3 - <<'PY'
import re
html = open('/tmp/wporg-upload-result.html').read()
title = re.search(r'<title>([^<]+)</title>', html)
print('Title:', title.group(1) if title else '?')
for m in re.finditer(r'class="notice[^"]*"[^>]*>(.*?)</div>', html, re.S | re.I):
	text = re.sub('<[^>]+>', ' ', m.group(1))
	text = ' '.join(text.split())
	if text:
		print('NOTICE:', text[:600])
if 'plugin-submission-file' in html or 'being reviewed' in html.lower():
	print('OK: submission appears accepted.')
if 'login' in html.lower() and 'wp-login' in html.lower():
	print('WARN: response looks like login redirect')
PY
