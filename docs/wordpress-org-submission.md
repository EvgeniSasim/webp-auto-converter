# WordPress.org Submission Guide

Чеклист для отправки **WebP Auto Converter** в официальный репозиторий WordPress.org.

## Подготовка аккаунта

- Username в `Contributors` readme.txt: **`evgenij347`**
- Аккаунт: https://profiles.wordpress.org/evgenij347/

## Чеклист перед отправкой (уроки из Privaro review)

- [ ] Версия в `webp-auto-converter.php` = `Stable tag` в `readme.txt`
- [ ] [Readme Validator](https://wordpress.org/plugins/developers/readme-validator/) без ошибок
- [ ] Нет секретов, тестовых URL, `deactivate_plugins()` при активации
- [ ] Весь вывод в админке/фронте экранирован (`esc_html`, `esc_attr`, `esc_url`)
- [ ] AJAX: nonce + `manage_options`
- [ ] `uninstall.php` удаляет опции; поведение с `.webp` файлами описано в FAQ
- [ ] Секции **Privacy**, **External services** в readme.txt (локальная обработка, без phone-home)
- [ ] GPL `LICENSE` в корне и в папке плагина
- [ ] ZIP через `bash scripts/build-release.sh` (без dev-файлов)

## Отправка на ревью

```bash
bash scripts/build-release.sh
python3 scripts/generate-wporg-assets.py   # опционально, для SVN после аппрува

# Автоматическая загрузка (нужен app password; 2FA может блокировать curl):
WPORG_USER=evgenij347 WPORG_PASS='...' bash scripts/wporg-submit-plugin.sh
```

Или вручную: [Add your plugin](https://wordpress.org/plugins/developers/add/) → загрузить `build/webp-auto-converter.zip`.

Повторная загрузка во время ревью:

```bash
WPORG_USER=... WPORG_PASS='...' bash scripts/wporg-upload-update.sh
```

## SVN (после аппрува)

```bash
python3 scripts/generate-wporg-assets.py
bash scripts/svn-upload-assets.sh
bash scripts/svn-publish-release.sh 1.4.0
```

## Правила

- Text domain: `webp-auto-converter`
- Плагин **не** обращается к внешним API — конвертация локальная
- После публикации обновить `Plugin URI` на `https://wordpress.org/plugins/webp-auto-converter/`
