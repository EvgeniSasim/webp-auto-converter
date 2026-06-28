#!/usr/bin/env python3
"""Generate WordPress.org plugin directory marketing assets."""

from __future__ import annotations

import os
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "wordpress-org" / "assets"
DOCS = ROOT / "docs" / "assets"

TEAL = (13, 148, 136)
TEAL_DARK = (15, 118, 110)
ACCENT = (45, 212, 191)
WHITE = (255, 255, 255)
LIGHT = (240, 253, 250)
MUTED = (100, 116, 139)
CARD = (255, 255, 255)


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
	candidates = (
		"/System/Library/Fonts/Supplemental/Arial Bold.ttf" if bold else "/System/Library/Fonts/Supplemental/Arial.ttf",
		"/Library/Fonts/Arial Bold.ttf" if bold else "/Library/Fonts/Arial.ttf",
		"/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf" if bold else "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
	)
	for path in candidates:
		if os.path.exists(path):
			return ImageFont.truetype(path, size)
	return ImageFont.load_default()


def draw_webp_badge(draw: ImageDraw.ImageDraw, cx: int, cy: int, size: int) -> None:
	w = size
	h = int(size * 0.72)
	x0 = cx - w // 2
	y0 = cy - h // 2
	draw.rounded_rectangle((x0, y0, x0 + w, y0 + h), radius=size // 8, fill=ACCENT)
	font = load_font(max(10, size // 5), bold=True)
	label = "WebP"
	bbox = draw.textbbox((0, 0), label, font=font)
	draw.text(
		(cx - (bbox[2] - bbox[0]) // 2, cy - (bbox[3] - bbox[1]) // 2),
		label,
		fill=TEAL_DARK,
		font=font,
	)


def make_icon(size: int) -> Image.Image:
	img = Image.new("RGBA", (size, size), TEAL)
	draw = ImageDraw.Draw(img)
	draw_webp_badge(draw, size // 2, size // 2, int(size * 0.62))
	return img


def make_banner(width: int, height: int) -> Image.Image:
	img = Image.new("RGB", (width, height), TEAL)
	draw = ImageDraw.Draw(img)
	for x in range(width):
		shade = int(TEAL[0] + (TEAL_DARK[0] - TEAL[0]) * x / width)
		draw.line([(x, 0), (x, height)], fill=(shade, TEAL[1], TEAL[2]))

	draw_webp_badge(draw, width // 5, height // 2, min(height, 110))
	title = load_font(max(28, height // 7), bold=True)
	sub = load_font(max(16, height // 12))
	draw.text((width // 3, height // 2 - height // 5), "WebP Auto Converter", fill=WHITE, font=title)
	draw.text((width // 3, height // 2 + height // 12), "Plug & play WebP for WordPress uploads", fill=(220, 245, 242), font=sub)
	return img


def make_social_preview() -> Image.Image:
	width, height = 1280, 640
	img = Image.new("RGB", (width, height), TEAL)
	draw = ImageDraw.Draw(img)
	for x in range(width):
		shade = int(TEAL[0] + (TEAL_DARK[0] - TEAL[0]) * x / width)
		draw.line([(x, 0), (x, height)], fill=(shade, TEAL[1], TEAL[2]))
	draw_webp_badge(draw, 120, height // 2, 160)
	title = load_font(48, bold=True)
	sub = load_font(24)
	draw.text((280, 200), "WebP Auto Converter", fill=WHITE, font=title)
	draw.text((280, 270), "Automatic WebP conversion — plug & play front-end output", fill=(220, 245, 242), font=sub)
	draw.text((280, 330), "Upload · Batch · srcset · Imagick/GD", fill=(180, 220, 215), font=load_font(20))
	return img


def rounded_rect(draw: ImageDraw.ImageDraw, box: tuple[int, int, int, int], radius: int, fill: tuple[int, int, int]) -> None:
	draw.rounded_rectangle(box, radius=radius, fill=fill)


def make_screenshot(index: int, title: str, subtitle: str) -> Image.Image:
	width, height = 1280, 720
	img = Image.new("RGB", (width, height), LIGHT)
	draw = ImageDraw.Draw(img)

	draw.rectangle((0, 0, width, 46), fill=(35, 40, 45))
	draw.rectangle((0, 46, 180, height), fill=(44, 51, 56))
	font = load_font(18, bold=True)
	draw.text((24, 12), "WebP Auto Converter", fill=WHITE, font=font)
	draw.text((24, 80), "Settings", fill=(200, 205, 210), font=load_font(14))
	draw.text((24, 110), "Media", fill=(200, 205, 210), font=load_font(14))

	title_font = load_font(34, bold=True)
	sub_font = load_font(20)
	draw.text((220, 70), title, fill=(30, 41, 59), font=title_font)
	draw.text((220, 120), subtitle, fill=MUTED, font=sub_font)

	if index == 1:
		rounded_rect(draw, (220, 180, 1180, 620), 12, CARD)
		draw.text((250, 200), "Plugin status", fill=MUTED, font=load_font(14, bold=True))
		draw.text((250, 230), "Converter ready (Imagick) · Plug & play: On", fill=TEAL_DARK, font=load_font(16))
		draw.text((250, 280), "WebP quality", fill=(30, 41, 59), font=load_font(22, bold=True))
		draw.rounded_rectangle((250, 320, 650, 340), radius=6, fill=(226, 232, 240))
		draw.rounded_rectangle((250, 320, 520, 340), radius=6, fill=TEAL)
		draw.text((250, 360), "82", fill=(30, 41, 59), font=load_font(20, bold=True))
		rounded_rect(draw, (250, 400, 420, 440), 8, TEAL)
		draw.text((275, 412), "Generate WebP", fill=WHITE, font=load_font(15, bold=True))
	elif index == 2:
		rows = [
			("photo.jpg", "photo.webp", "Converted"),
			("hero.png", "hero.webp", "Converted"),
			("thumb.jpg", "thumb.webp", "Converted"),
		]
		y = 200
		for original, webp, status in rows:
			draw.text((240, y), original, fill=(30, 41, 59), font=load_font(18))
			draw.text((520, y), "→", fill=MUTED, font=load_font(18, bold=True))
			draw.text((560, y), webp, fill=TEAL_DARK, font=load_font(18, bold=True))
			draw.text((900, y), status, fill=TEAL, font=load_font(16, bold=True))
			y += 56
	else:
		draw.text((240, 190), "srcset prefers WebP when sibling exists", fill=(30, 41, 59), font=load_font(22, bold=True))
		draw.text((240, 250), "image-300x200.webp", fill=TEAL_DARK, font=load_font(18, bold=True))
		draw.text((240, 300), "image-768x512.webp", fill=TEAL_DARK, font=load_font(18, bold=True))
		draw.text((240, 350), "Original JPEG/PNG files are kept", fill=MUTED, font=load_font(18))

	return img


def sync_docs_assets(assets: dict[str, Image.Image]) -> None:
	DOCS.mkdir(parents=True, exist_ok=True)
	mapping = {
		"icon-256x256.png": "icon-256x256.png",
		"banner-1544x500.png": "hero-banner.png",
		"social-preview.png": "social-preview.png",
	}
	for src_name, dest_name in mapping.items():
		if src_name in assets:
			img = assets[src_name]
			if img.mode == "RGBA":
				img.save(DOCS / dest_name, "PNG")
			else:
				img.convert("RGB").save(DOCS / dest_name, "PNG")
	for i in range(1, 4):
		key = f"screenshot-{i}.png"
		if key in assets:
			assets[key].save(DOCS / key, "PNG")


def main() -> None:
	OUT.mkdir(parents=True, exist_ok=True)
	assets = {
		"icon-128x128.png": make_icon(128),
		"icon-256x256.png": make_icon(256),
		"banner-772x250.png": make_banner(772, 250),
		"banner-1544x500.png": make_banner(1544, 500),
		"social-preview.png": make_social_preview(),
	}
	screens = [
		(1, "Quality settings", "Configure WebP quality and run batch conversion."),
		(2, "Upload conversion", "JPEG and PNG uploads get WebP siblings automatically."),
		(3, "Responsive srcset", "WordPress srcset URLs prefer WebP when available."),
	]
	for idx, title, subtitle in screens:
		assets[f"screenshot-{idx}.png"] = make_screenshot(idx, title, subtitle)

	for name, image in assets.items():
		path = OUT / name
		if name.endswith(".png") and image.mode == "RGBA" and "icon" in name:
			image.save(path, "PNG")
		else:
			image.convert("RGB").save(path, "PNG")
		print(f"Wrote {path}")

	sync_docs_assets(assets)
	print(f"Synced docs assets to {DOCS}")


if __name__ == "__main__":
	main()
