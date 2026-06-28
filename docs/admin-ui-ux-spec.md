# Admin UI/UX Spec — WebP Auto Converter

| Поле | Значение |
|------|----------|
| Версия | 1.0 |
| Страница | Settings → WebP Converter |
| Slug | `webp-auto-converter` |
| Text domain | `webp-auto-converter` |
| Дата | 2026-06-21 |

---

## Executive summary

- Страница остаётся под **Settings → WebP Converter** — плагин настраивается редко, не заслуживает отдельного top-level меню.
- Вверху — **Status strip**: готовность конвертера (GD/Imagick), состояние plug & play, краткая подсказка «что уже работает».
- **Settings** и **Batch conversion** — два отдельных блока (postbox): форма сохраняется через Settings API; batch не отправляет `options.php`.
- **Quality** — range slider + number input (sync), с пояснением что влияет только на *новые* конвертации; re-batch не пересчитывает старые WebP автоматически.
- **Batch UX (MVP)** — progress bar, счётчики processed/total/converted, live region; расширение AJAX-ответа `total` и `processed` (backward-compatible).
- **For developers** — сворачиваемый блок со ссылкой на `docs/theme-helpers.md` (GitHub raw или readme на wordpress.org).

---

## Personas & JTBD

### Persona A — Site owner (non-dev)

**Контекст:** активировал плагин из каталога, хочет быстрее сайт без кода.

| Job | Мотивация | Успех |
|-----|-----------|-------|
| Убедиться, что плагин работает | Страх «сломать сайт» | Видит зелёный статус «Converter ready» и «Plug & play: On» |
| Не трогать настройки | «Поставил и забыл» | Понимает, что defaults уже OK (quality 82) |
| Конвертировать старые фото | Медиатека до плагина | Запускает batch, видит прогресс и «Done» |
| Понять, если сервер не поддерживает WebP | Хостинг без GD WebP | Видит warning notice с actionable текстом |

### Persona B — Agency / theme developer

**Контекст:** знает WordPress, может отключить auto output и использовать `webp_ac_*` helpers.

| Job | Мотивация | Успех |
|-----|-----------|-------|
| Отключить plug & play | Кастомный `<picture>` в теме | Toggle off + понимает, что front end вернётся к JPEG/PNG |
| Подобрать quality | Баланс вес/качество | Меняет slider, знает что нужен re-upload или re-batch |
| Найти API хелперов | Документация | Раскрывает «For developers», переходит к theme-helpers |
| Диагностика | Imagick vs GD | Видит backend в status strip |

### Out of scope personas

- Контент-редактор без `manage_options` — страница недоступна (как сейчас).

---

## Information architecture

### Рекомендация: остаться в Settings

| Вариант | За | Против |
|---------|-----|--------|
| **Settings → WebP Converter** ✓ | Соответствует WP-паттерну «редкие настройки»; уже в readme; не засоряет sidebar | Менее заметен при первой активации |
| Top-level «WebP» | Заметнее после install | Избыточно для 2 опций + batch; конкурирует с Media |

**Компенсация visibility:** one-time dismissible admin notice после активации со ссылкой на settings (nice-to-have, не MVP).

### Иерархия страницы (сверху вниз)

```
.wrap#webp-ac-settings
├── h1  WebP Converter
├── [Notice area — dismissible, contextual]
├── Status strip (read-only)
├── Postbox: Conversion settings
│   └── form → options.php
├── Postbox: Existing media (batch)
│   └── standalone UI + AJAX
└── Postbox (closed by default): For developers
```

### Порядок полей в Settings

1. **Plug & play front-end output** — главный переключатель продукта; первым, потому что отвечает на «нужен ли мне код в теме».
2. **WebP quality** — вторичная fine-tuning настройка.

### Почему batch отдельно от формы

- Batch — длительная операция; не должна блокировать Save и не должна сбрасываться при submit формы.
- Разные mental models: «сохранить предпочтения» vs «запустить job».
- При running batch quality можно read-only (optional MVP+) чтобы избежать путаницы «какой quality применился».

---

## UX flows

### Flow 1 — First visit after activation

```
Activate plugin
    → Redirect / next admin page
    → [Optional] Admin notice: "WebP Auto Converter is active. New uploads will get WebP. Convert existing images → Settings link"
User opens Settings → WebP Converter
    → Status strip loads (PHP, no AJAX)
        IF converter unavailable → warning notice, rest of page muted guidance
        IF converter OK → green indicators
    → Plug & play: ON (default), Quality: 82
    → User reads status, optionally runs batch
```

**Edge cases:**
- No JPEG/PNG in library → batch card shows info: «No convertible images found» (disable button or show on complete with 0 total).
- Multisite: settings per site (current behavior) — no change.

### Flow 2 — Change quality

```
User adjusts slider/number → Save Changes
    → Standard WP settings updated notice
New uploads → converted at new quality
Existing .webp files → NOT regenerated until user runs batch again
```

**Copy requirement:** field description must state: *Applies to new conversions. Re-run batch to regenerate WebP for existing media.*

**Edge case:** user changes quality during batch → MVP: allow save; batch continues with quality frozen at job start (document in dev notes). MVP+: disable quality field while batch running.

### Flow 3 — Batch run

```
States: idle | running | complete | error

idle:
    Button "Generate WebP" enabled
    Progress hidden or 0%
    Summary text: "Convert JPEG and PNG images uploaded before the plugin was active."

running:
    Button disabled, label → "Converting…"
    Progress bar indeterminate OR determinate if total known
    Live region announces each batch update
    Counts: "Processed 50 of 240 · Converted 38 files in this batch"

complete (done=true):
    Button re-enabled
    Progress 100%
    Success notice inline: "All done. Converted N files total."
    [Optional] link to Media Library

error:
    Button re-enabled
    Error notice with message (nonce / network / server)
    Progress frozen at last known %
    User can retry (resets offset to 0)
```

**AJAX extension (backward-compatible):**

Current response: `{ done, next_offset, message }`

Add optional fields (MVP):

```json
{
  "done": false,
  "next_offset": 25,
  "message": "…",
  "total": 240,
  "processed": 25,
  "converted_total": 18,
  "converted_batch": 5
}
```

- `total` — count of convertible attachments (query once on offset=0, cache in JS).
- `processed` — cumulative attachments processed.
- `converted_total` — cumulative files converted (main + sizes).
- Existing clients ignore new keys.

**Cancel (nice-to-have):** AbortController on fetch; stop loop; show «Stopped at offset N. Run again to continue.» — без server-side job persistence offset сбрасывается при новом run (document).

### Flow 4 — Toggle plug & play off

```
User unchecks → Save
    → Status strip: "Plug & play: Off"
    → Inline description reminds: front end serves original JPEG/PNG unless theme uses webp_ac_* helpers
Developer expands "For developers" for manual integration
```

No confirmation modal (low risk); optional subtle info notice after save.

---

## Wireframe

### Desktop (min-width ~782px, standard WP admin)

```
┌─────────────────────────────────────────────────────────────────────────────┐
│ WebP Converter                                                              │
├─────────────────────────────────────────────────────────────────────────────┤
│ ┌─ Status ────────────────────────────────────────────────────────────────┐ │
│ │ ● Converter ready (Imagick)    ● Plug & play: On    ● Auto on upload   │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ ┌─ Conversion settings ─────────────────────────────────────────────────┐ │
│ │                                                                         │ │
│ │  Plug & play front-end output                          [====●----] ON   │ │
│ │  Automatically output WebP in themes…                                   │ │
│ │                                                                         │ │
│ │  WebP quality                                          [ 82 ]           │ │
│ │  [========●----------------] 0 ─────────────────────────────── 100   │ │
│ │  Lower values = smaller files. 80–85 recommended for photos.          │ │
│ │  Applies to new conversions. Re-run batch for existing media.         │ │
│ │                                                                         │ │
│ │                                              [ Save Changes ]           │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ ┌─ Existing media ──────────────────────────────────────────────────────┐ │
│ │  Generate WebP for images uploaded before this plugin was active.      │ │
│ │                                                                         │ │
│ │  [ Generate WebP ]                                                      │ │
│ │                                                                         │ │
│ │  ████████████░░░░░░░░░░░░░░░░  52%                                      │ │
│ │  Processed 125 of 240 attachments · 89 WebP files created             │ │
│ │  (aria-live polite region for status text)                              │ │
│ └─────────────────────────────────────────────────────────────────────────┘ │
│                                                                             │
│ ▶ For developers                                                            │
│   (collapsed)                                                               │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Expanded «For developers»

```
┌─ For developers ────────────────────────────────────────────────────────────┐
│  Plug & play covers most themes. For custom templates use theme helpers:    │
│  webp_ac_get_image_html(), webp_ac_the_post_thumbnail(), etc.              │
│                                                                             │
│  [ View theme helpers documentation ↗ ]                                     │
│                                                                             │
│  Disable auto output in code:                                               │
│  add_filter( 'webp_ac_auto_output_enabled', '__return_false' );             │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Mobile admin (~<782px)

- Postboxes stack full width.
- Status strip: indicators wrap to 2 lines; keep text short.
- Slider full width; number input min-width 4ch.
- Progress bar 100% width.

### Warning state (no WebP support)

```
┌─ notice notice-warning ───────────────────────────────────────────────────┐
│  WebP conversion is unavailable. Enable GD with imagewebp or Imagick      │
│  with WebP support on your server.                                        │
└───────────────────────────────────────────────────────────────────────────┘
(Status strip: red/gray ● Converter unavailable)
(Batch button disabled; settings save still allowed for future use)
```

---

## Component spec

### C1 — Page wrapper

| Property | Value |
|----------|-------|
| Root | `.wrap` + `#webp-ac-settings` |
| Title | `h1`: «WebP Converter» |
| Assets | `admin_enqueue_scripts` only on `settings_page_webp-auto-converter`: `admin.css`, `admin.js` |

### C2 — Status strip

| Property | Value |
|----------|-------|
| Container | `.webp-ac-status` — flex row, gap 24px, padding 12px 16px, background `#f6f7f7`, border 1px `#c3c4c7`, border-radius 4px |
| Items | 3 max |

**Item 1 — Converter**

| State | Indicator | Text |
|-------|-----------|------|
| OK (Imagick) | green dot `#00a32a` | Converter ready (Imagick) |
| OK (GD) | green dot | Converter ready (GD) |
| Fail | gray/red dot | Converter unavailable |

Logic: reuse `webp_auto_converter_gd_or_imagick_available()`; detect Imagick vs GD same as convert path.

**Item 2 — Plug & play**

| State | Text |
|-------|------|
| On | Plug & play: On |
| Off | Plug & play: Off |

**Item 3 — Upload behavior**

Static when converter OK: «New uploads: WebP generated automatically»

### C3 — Conversion settings postbox

| Property | Value |
|----------|-------|
| Markup | `.postbox` > `.postbox-header` > `h2` + `.inside` |
| Title | Conversion settings |
| Form | `method="post" action="options.php"` — unchanged Settings API group `webp_auto_converter_options` |

**Field: Plug & play**

| Property | Value |
|----------|-------|
| Control | Checkbox → **upgrade to toggle-style** optional; MVP: keep checkbox with clearer label |
| Hidden input | `value="0"` (existing pattern) |
| Label | Automatically output WebP in themes (no code required) |
| Description | Enhances featured images, attachment images, and post/widget content with responsive `<picture>` markup when WebP files exist. |

**Field: Quality**

| Property | Value |
|----------|-------|
| Controls | `<input type="range" min="0" max="100">` + `<input type="number" min="0" max="100" class="small-text">` synced via JS |
| Settings API | Still registers integer option; range is UI-only |
| Description line 1 | Lower values produce smaller files. 80–85 is a good balance for photos. |
| Description line 2 | Applies to new conversions. Re-run batch below to regenerate existing media. |

**Submit**

Standard `submit_button()` — «Save Changes»

### C4 — Existing media postbox

| Property | Value |
|----------|-------|
| Title | Existing media |
| Intro | Generate WebP for images that were uploaded before this plugin was active. |

**Primary button**

| State | Label | Class |
|-------|-------|-------|
| idle | Generate WebP | `button button-primary` |
| running | Converting… | disabled |
| complete | Generate WebP | enabled (allow re-run) |

**Progress bar**

| Property | Value |
|----------|-------|
| Markup | `.webp-ac-progress` > `role="progressbar"` `aria-valuenow` `aria-valuemin="0"` `aria-valuemax="100"` |
| Inner | `.webp-ac-progress__bar` width % |
| Visibility | hidden when idle; visible when running or complete |
| Calculation | `processed / total * 100` when total > 0; else indeterminate animation (CSS) for first batch |

**Status line**

| Property | Value |
|----------|-------|
| Element | `#webp-ac-batch-status` |
| a11y | `aria-live="polite"` `aria-atomic="true"` |
| Content | Human message + numeric counts |

**Disabled when:** converter unavailable OR (optional) total=0 on preflight.

### C5 — For developers postbox

| Property | Value |
|----------|-------|
| Default | collapsed: `.postbox` + `.handlediv` or simple `<details>`/JS toggle |
| Title | For developers |
| Body | Short paragraph + external link |
| Link target | `https://github.com/EvgeniSasim/webp-auto-converter/blob/main/docs/theme-helpers.md` |
| Code snippet | Read-only `<code>` in `.code` style (monospace, not editable) |

### C6 — Notices

| Type | When | Class |
|------|------|-------|
| Warning | No GD/Imagick WebP | `.notice.notice-warning` below h1 |
| Success | Settings saved | WP core `settings_errors()` |
| Info | No images to convert | `.notice.notice-info` inside batch postbox |
| Error | Batch AJAX fail | `.notice.notice-error` inline in batch postbox |

---

## Microcopy deck

| string_key_or_location | English copy | notes for translators |
|------------------------|--------------|----------------------|
| `page.title` | WebP Converter | Page `<h1>` and menu label |
| `status.converter.imagick` | Converter ready (Imagick) | Backend name stays Latin |
| `status.converter.gd` | Converter ready (GD) | |
| `status.converter.unavailable` | Converter unavailable | |
| `status.plug_play.on` | Plug & play: On | |
| `status.plug_play.off` | Plug & play: Off | |
| `status.uploads` | New uploads: WebP generated automatically | |
| `notice.converter_unavailable` | WebP conversion is unavailable on this server. Enable GD with imagewebp support or Imagick with WebP support, then refresh this page. | |
| `section.settings.title` | Conversion settings | Postbox heading |
| `field.auto_output.label` | Plug & play front-end output | Settings field label |
| `field.auto_output.checkbox` | Automatically output WebP in themes (no code required) | |
| `field.auto_output.description` | Enhances featured images, attachment images, and post/widget content with responsive `<picture>` markup when WebP files exist. | |
| `field.quality.label` | WebP quality (0–100) | |
| `field.quality.description.size` | Lower values produce smaller files. 80–85 is a good balance for photos. | |
| `field.quality.description.scope` | Applies to new conversions. Re-run batch below to regenerate existing media. | |
| `section.batch.title` | Existing media | |
| `section.batch.intro` | Generate WebP for images that were uploaded before this plugin was active. | |
| `button.batch.start` | Generate WebP | |
| `button.batch.running` | Converting… | |
| `batch.starting` | Starting… | Initial AJAX |
| `batch.progress` | Processed %1$s of %2$s attachments · %3$s WebP files created | placeholders |
| `batch.batch_detail` | Converted %1$d file(s) in this batch. Continuing… | mid-run |
| `batch.done` | All done. %1$s WebP files created across %2$s attachments. | final |
| `batch.done_simple` | Done. Converted %d file(s) in the last batch. | fallback current copy |
| `batch.error` | Error | Generic |
| `batch.error.network` | Network error. Check your connection and try again. | |
| `batch.no_images` | No JPEG or PNG images found in the media library. | |
| `section.dev.title` | For developers | |
| `section.dev.intro` | Plug & play covers most themes. For custom templates, use the theme helper functions in your PHP templates. | |
| `section.dev.link` | View theme helpers documentation | Link text |
| `section.dev.code_hint` | Disable auto output in code: | Before code snippet |
| `activation.notice` | WebP Auto Converter is active. New uploads will automatically get WebP versions. | Admin notice |
| `activation.notice.link` | Convert existing media | Link to settings page |

---

## Visual & a11y guidelines

### Visual tokens (WP-native)

| Token | Value | Usage |
|-------|-------|-------|
| Primary button | core `.button-primary` | Generate WebP, Save |
| Postbox | core `.postbox` | Section cards |
| Status background | `#f6f7f7` | Status strip |
| Border | `#c3c4c7` | Strip, progress track |
| Success indicator | `#00a32a` | Status dot |
| Warning | core `.notice-warning` | Server issues |
| Progress fill | `#2271b1` | Bar fill |
| Progress track | `#dcdcde` | Bar background |
| Text muted | `#646970` | Descriptions (core `.description`) |

**CSS scope:** all rules under `#webp-ac-settings` only. No global admin overrides.

**Dashicons (optional):** `dashicons-images-alt2` before page title — nice-to-have only.

### Typography & spacing

- Headings: inherit WP admin (h1 23px, h2 in postbox 14px semibold).
- Form table: keep `.form-table` for settings fields.
- Postbox margin-bottom: 20px (core default).
- Status strip margin-bottom: 20px.

### Accessibility checklist

| # | Requirement | Implementation |
|---|-------------|----------------|
| 1 | Page title | Single `h1`, postboxes use `h2` |
| 2 | Labels | Every input has `<label>` or `aria-labelledby` |
| 3 | Quality slider | `aria-valuemin/max/now` on range; number field labeled |
| 4 | Batch progress | `role="progressbar"` + `aria-valuenow` updated each batch |
| 5 | Live updates | `#webp-ac-batch-status` has `aria-live="polite"` |
| 6 | Button states | `disabled` + visual; `aria-busy="true"` on button while running |
| 7 | Focus order | Status → settings → batch → developers; logical tab order |
| 8 | Focus visible | Do not remove outline; use core focus styles |
| 9 | Color contrast | Status dots paired with text (not color-only) |
| 10 | Reduced motion | `@media (prefers-reduced-motion: reduce)` — disable indeterminate animation |
| 11 | Keyboard | Enter on Save submits form; batch button activatable via Space/Enter |
| 12 | Error announcements | Errors in live region or `role="alert"` for critical failures |

---

## Implementation roadmap

### MVP — ship in next minor (e.g. 1.4.0)

| Priority | Item | Rationale | Effort |
|----------|------|-----------|--------|
| P0 | Status strip (converter + plug & play + uploads) | First-run confidence | S |
| P0 | Postbox layout for settings + batch | Visual hierarchy, WP-native | S |
| P0 | Batch progress bar + processed/total counts | Core UX pain today | M |
| P0 | Extend AJAX with `total`, `processed`, `converted_total` | Enables determinate progress | M |
| P0 | Warning notice when converter unavailable | Prevent silent failure | S |
| P0 | Enqueue scoped `admin.css` / `admin.js` | Slider sync, progress UI | M |
| P1 | Quality range + number sync | Better tuning UX | S |
| P1 | Field copy: quality scope + batch hint | Reduces support questions | S |
| P1 | `aria-live` + progressbar ARIA | a11y compliance | S |
| P1 | For developers collapsible + doc link | Dev persona without clutter | S |

**MVP file touch list:**

- `webp-auto-converter/webp-auto-converter.php` — markup refactor, AJAX fields, preflight total query
- `webp-auto-converter/assets/admin.css` (new)
- `webp-auto-converter/assets/admin.js` (new)

### Nice-to-have — later minor

| Priority | Item | Rationale | Effort |
|----------|------|-----------|--------|
| P2 | Activation admin notice with deep link | Discoverability under Settings | S |
| P2 | Toggle UI instead of checkbox | Modern feel | S |
| P2 | Cancel batch (AbortController) | Long libraries | S |
| P2 | Disable quality save during batch | Edge-case clarity | S |
| P2 | Preflight «N images to convert» before start | Sets expectation | M |
| P2 | Link to Media Library after complete | Quick verification | S |
| P3 | Regenerate only missing WebP (skip existing) | Performance on re-run | L |
| P3 | Stats: total WebP disk savings | Delight, complex | L |
| P3 | Update `scripts/generate-wporg-assets.py` screenshots | Marketing alignment | M |

### Suggested PR split

1. **PR1 (structure + status):** postboxes, status strip, notices, dev section, CSS scaffold.
2. **PR2 (batch UX):** AJAX extension, progress bar, JS refactor, a11y.

---

## Open questions

| # | Question | Recommended default |
|---|----------|---------------------|
| 1 | Checkbox vs toggle for plug & play? | **Checkbox for MVP** (Settings API native); toggle in P2 |
| 2 | Re-batch regenerates all WebP or skip existing files? | **Current behavior** (overwrite via convert) — document; skip-existing in P3 |
| 3 | Preflight total count on page load vs first AJAX? | **First AJAX (offset=0)** returns total — fewer queries on every page view |
| 4 | Activation notice dismissible? | **Yes**, user meta `webp_ac_welcome_dismissed` — P2 |
| 5 | Doc link: GitHub vs inline readme tab on WP.org? | **GitHub main branch** until WP.org stable; filter `webp_ac_docs_url` for forks |

---

## Appendix — PHP markup skeleton (implementer reference)

```php
<div class="wrap" id="webp-ac-settings">
	<h1><?php esc_html_e( 'WebP Converter', 'webp-auto-converter' ); ?></h1>

	<?php if ( ! webp_auto_converter_gd_or_imagick_available() ) : ?>
		<div class="notice notice-warning">…</div>
	<?php endif; ?>

	<div class="webp-ac-status" role="region" aria-label="…">…</div>

	<div class="postbox">
		<div class="postbox-header"><h2>Conversion settings</h2></div>
		<div class="inside">
			<form method="post" action="options.php">…</form>
		</div>
	</div>

	<div class="postbox">
		<div class="postbox-header"><h2>Existing media</h2></div>
		<div class="inside">
			<button type="button" id="webp-ac-batch-start" class="button button-primary">…</button>
			<div class="webp-ac-progress" role="progressbar" hidden>…</div>
			<p id="webp-ac-batch-status" aria-live="polite"></p>
		</div>
	</div>

	<details class="postbox webp-ac-dev">…</details>
</div>
```

---

*Spec generated from `prompts/webp-admin-ui-ux.md` v1.0.*
