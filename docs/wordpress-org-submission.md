# WordPress.org Submission Guide

Этот документ содержит чеклист и инструкции для отправки плагина в официальный репозиторий WordPress.org.

## Подготовка аккаунта

- [ ] Зарегистрируйте аккаунт на [WordPress.org](https://login.wordpress.org/register), если его ещё нет.
- [ ] Username должен совпадать с `evgeniisasim` (указан в `Contributors` в `readme.txt`).

## Чеклист перед отправкой

- [ ] Версия в `webp-auto-converter.php` совпадает со `Stable tag` в `readme.txt`.
- [ ] `readme.txt` проходит валидацию в [Readme Validator](https://wordpress.org/plugins/developers/readme-validator/).
- [ ] В коде нет секретов, API ключей или ссылок на тестовые стенды.
- [ ] `uninstall.php` удаляет опцию `webp_auto_converter_quality`.
- [ ] Лицензия GPLv2 или выше (`LICENSE` в корне и в папке плагина).

## Процесс отправки

1. Соберите ZIP:

   ```bash
   bash scripts/build-release.sh
   ```

2. Откройте [Add your plugin](https://wordpress.org/plugins/add/).
3. Загрузите `build/webp-auto-converter.zip`.
4. Дождитесь ручного ревью (обычно 1–2 недели).

## Работа с SVN (после аппрува)

После одобрения будет доступен SVN-репозиторий `webp-auto-converter`.

### Структура SVN

- `/trunk` — содержимое папки `webp-auto-converter/`
- `/tags/1.1.0` — копия trunk для релиза (версия = Stable tag)
- `/assets` — иконки, баннеры, скриншоты из `wordpress-org/assets/`

### Assets и релиз

Сгенерировать PNG локально:

```bash
python3 -m venv .venv-assets && source .venv-assets/bin/activate
pip install -r scripts/requirements-assets.txt
python3 scripts/generate-wporg-assets.py
```

Загрузить assets в SVN:

```bash
bash scripts/svn-upload-assets.sh
```

Опубликовать trunk + tag:

```bash
bash scripts/svn-publish-release.sh 1.1.0
```

### Ручной checkout

```bash
svn co https://plugins.svn.wordpress.org/webp-auto-converter/ my-plugin-svn
```

## Важные правила

- Text domain: `webp-auto-converter`
- Не включайте `node_modules` или `vendor` в ZIP (`build-release.sh` исключает лишнее через `.distignore`)
- Плагин не отправляет данные на внешние серверы — конвертация локальная (GD/Imagick)

## После публикации

1. Обновите `Plugin URI` в заголовке плагина на `https://wordpress.org/plugins/webp-auto-converter/`
2. Добавьте ссылку в корневой `README.md`
3. Для переводов используйте [GlotPress](https://translate.wordpress.org/) после появления плагина в каталоге
