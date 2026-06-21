# WordPress.org assets

PNG files in this directory are uploaded to the plugin SVN `/assets` folder (not inside `/trunk`).

## Generate

```bash
python3 -m venv .venv-assets && source .venv-assets/bin/activate
pip install -r scripts/requirements-assets.txt
python3 scripts/generate-wporg-assets.py
```

## Upload

After WordPress.org approves the plugin:

```bash
bash scripts/svn-upload-assets.sh
```

## Files

| File | Purpose |
|------|---------|
| `icon-128x128.png` | Plugin icon |
| `icon-256x256.png` | Plugin icon (retina) |
| `banner-772x250.png` | Plugin banner |
| `banner-1544x500.png` | Plugin banner (retina) |
| `screenshot-1.png` … `screenshot-3.png` | Listing screenshots |

Replace mock screenshots with real admin captures when convenient.
