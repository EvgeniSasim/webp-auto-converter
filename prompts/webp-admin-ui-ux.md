# Prompt Spec: webp-admin-ui-ux

| Поле | Значение |
|------|----------|
| ID | `webp-ac-design-admin-ui-ux` |
| Версия | 1.0 |
| Этап / Skill | UX audit + design spec → `@ui-ux-pro-max-v3` или `@wp-nitg` (реализация) |
| Модель / среда | Cursor Agent |
| Язык выхода | русский (документ); UI copy — английский (text domain `webp-auto-converter`) |
| Источник методики | prompt-manager / playbook |

## Objective

Спроектировать UI/UX страницы **Settings → WebP Converter** для WordPress-плагина WebP Auto Converter: понятный first-run, настройки, batch-конвертация и статус окружения — в стиле нативной WP Admin, с приоритетами для реализации.

## Prompt (готово к копированию)

```markdown
## Role
You are a senior product designer + WordPress admin UX specialist for a GPL-2.0 plugin aimed at site owners and theme developers.

## Context

**Product:** WebP Auto Converter — converts JPEG/PNG uploads to WebP and serves WebP on the front end (plug & play by default).

**Current admin (baseline — read before designing):**
- @webp-auto-converter/webp-auto-converter.php — settings page, fields, batch AJAX UI
- @README.md — features and positioning
- @docs/theme-helpers.md — optional developer helpers (link from admin when relevant)
- @CHANGELOG.md — v1.3.0 plug & play, batch tool, quality setting

**Current UI state (do not re-implement blindly — improve):**
- Location: Settings → WebP Converter (`add_options_page`)
- Form via Settings API: checkbox «Plug & play front-end output», number «WebP quality (0–100)», Save
- Below `<hr>`: «Existing media» + button «Generate WebP (batch)» + inline text status (no progress bar, no totals)
- Batch: AJAX loop, 25 attachments per request, status messages only
- No system status (GD/Imagick/WebP), no onboarding, no stats, no link to theme helpers docs

**Constraints:**
- Must feel native to WordPress admin (`.wrap`, `.form-table`, `.notice`, core buttons, no SPA framework)
- All user-facing strings translatable (`webp-auto-converter` text domain); design copy in English
- PHP 7.4+, no build step required for admin UI (plain CSS/JS enqueued on settings screen only is OK)
- Accessibility: keyboard, focus, ARIA for progress/live regions, sufficient contrast
- Scope: admin settings screen only — do NOT redesign Media Library or front end
- Do NOT change conversion logic, options keys, or AJAX contract unless you document a backward-compatible extension

**Do NOT touch:**
- `includes/image-helpers.php`, `includes/auto-output.php` (unless spec explicitly needs a read-only status hook)
- Release/CI scripts, WordPress.org assets

## Task

Design the admin UI/UX end-to-end. Deliver a **design spec** (not production code yet) that an implementer can follow in one or two focused PRs.

### 1. User & jobs

Define primary personas (e.g. site owner, agency dev) and top jobs-to-be-done on this screen:
- First activation: «does it work?»
- Tune quality vs file size
- Enable/disable plug & play
- Batch-convert legacy media
- Know when conversion failed or server lacks WebP support

### 2. Information architecture

Propose page structure with clear sections and hierarchy. Consider:
- Hero / status strip at top (plugin health: converter available, auto-output on/off)
- Settings card (order of fields + why)
- Batch conversion card (separate from save form — explain why)
- Optional «For developers» collapsible with link to theme helpers doc

Justify placement under **Settings** vs own top-level menu (recommend one).

### 3. UX flows (describe step-by-step)

Document flows with states and edge cases:
- **First visit** after activation
- **Change quality** → effect on *new* uploads vs re-batch
- **Batch run** — idle → running → partial progress → complete → error (nonce/network/server)
- **Toggle plug & play off** — what user should expect on front end

### 4. Component & interaction spec

For each UI block specify:
- Layout (WP patterns: `.form-table`, `.card`-like postbox if appropriate)
- Controls (slider vs number for quality? toggle vs checkbox?)
- Progress UX for batch: progress bar %, counts (processed / total / converted), cancel?, disable quality change while running?
- Notices: success, warning (no WebP support), info (Imagick vs GD)
- Empty/error states

Include **ASCII wireframe** or structured markdown mock of the full page (desktop; note mobile admin if relevant).

### 5. Microcopy deck

Table: `string_key_or_location` | `English copy` | `notes for translators`.

Cover: page title, section headings, field labels, descriptions, button labels, batch status messages, error messages, developer blurb.

Keep tone: concise, WordPress.org-friendly, no marketing hype.

### 6. Visual design tokens (WP-native)

- Colors: map to WP admin palette (`#2271b1`, notice classes) — no custom design system
- Typography/spacing: match core settings pages
- Optional minimal admin CSS scope: `#webp-ac-settings` only
- Icon usage: Dashicons if any

### 7. Accessibility checklist

WCAG-oriented checklist specific to this page (live region for batch progress, button disabled states, focus order, label association).

### 8. Implementation roadmap

Prioritized backlog:

| Priority | Item | Rationale | Effort (S/M/L) |
|----------|------|-----------|----------------|

Split **MVP** (ship in next minor) vs **nice-to-have**.

### 9. Open questions

List decisions that need product owner input (max 5), each with recommended default.

## Output format

Single markdown document with these H2 sections (exact titles):

1. Executive summary (≤5 bullets)
2. Personas & JTBD
3. Information architecture
4. UX flows
5. Wireframe
6. Component spec
7. Microcopy deck
8. Visual & a11y guidelines
9. Implementation roadmap
10. Open questions

Also save the spec to: `docs/admin-ui-ux-spec.md`

## Success criteria

Done when:
- A developer can implement the admin page from sections 5–8 without guessing layout or copy
- Batch conversion UX clearly communicates progress and completion
- First-time user understands plug & play vs optional theme helpers
- All recommendations respect WP admin conventions and plugin constraints above
- No scope creep into front-end theme markup or conversion algorithms
```

## Variables

| Переменная | Пример | Описание |
|------------|--------|----------|
| `{plugin}` | WebP Auto Converter | Название продукта |
| `{settings_slug}` | `webp-auto-converter` | Slug страницы настроек |
| `{executor}` | `@ui-ux-pro-max-v3` | Skill для design spec; `@wp-nitg` для WP-реализации |

## Output contract

- **Файлы:** `docs/admin-ui-ux-spec.md` (основной артефакт)
- **Executor skill (design):** `@ui-ux-pro-max-v3` или `@ipai-ui-ux-pro-max`
- **Executor skill (implement):** `@wp-nitg` — после утверждения spec пользователем
- **Формат:** markdown spec по 10 секциям из промпта; wireframe в ASCII или markdown-блоках
- **Критерий готовности:** roadmap с MVP; microcopy deck; batch UX с измеримым progress; a11y checklist

## Handoff to executor

**Design pass:** `@ui-ux-pro-max-v3`  
First read: section «Prompt (готово к копированию)» in `prompts/webp-admin-ui-ux.md`.

**Implement pass (отдельный чат после «ок»):** `@wp-nitg`  
First read: `docs/admin-ui-ux-spec.md` sections 5–9; implement only MVP unless user expands scope.

## Changelog

| Дата | Изменение |
|------|-----------|
| 2026-06-21 | v1.0 — initial spec from current admin baseline |

## Review notes

- Тест 1: промпт ссылается на реальные файлы плагина и текущие ограничения UI
- Итерация: после первого spec — уточнить MVP vs WordPress.org screenshot alignment (`scripts/generate-wporg-assets.py`)
