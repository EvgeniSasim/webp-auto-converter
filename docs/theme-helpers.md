# Theme helpers — image output examples

Плагин подключает `includes/image-helpers.php` с функциями для вывода `<picture>` + WebP (`webp_ac_*`). **ACF не требуется** — достаточно стандартного WordPress API.

## Без ACF (WordPress API)

### Featured image (миниатюра записи)

```php
<?php
// Возвращает HTML
echo webp_ac_get_the_post_thumbnail_html(
	null,
	array(
		'class'  => 'entry__thumb',
		'size'   => 'large',
		'sizes'  => '(max-width: 768px) 100vw, 800px',
		'is_lcp' => is_singular(),
	)
);

// Или echo-обёртка (аналог the_post_thumbnail)
webp_ac_the_post_thumbnail( 'large', array( 'class' => 'entry__thumb' ) );
```

### Attachment ID из шаблона

```php
<?php
echo webp_ac_wp_attachment_image(
	123,
	'medium_large',
	array(
		'class' => 'card__img',
		'sizes' => '(max-width: 768px) 100vw, 50vw',
	)
);
```

### Post meta с ID вложения или URL файла

```php
<?php
echo webp_ac_get_image_from_post_meta(
	get_the_ID(),
	'hero_image_id',
	array(
		'class'  => 'hero__image',
		'size'   => 'full',
		'sizes'  => '100vw',
		'is_lcp' => true,
	)
);
```

### Site option (Customizer / options page)

```php
<?php
echo webp_ac_get_image_from_option(
	'my_theme_logo_id',
	array(
		'class'      => 'site-logo',
		'size'       => 'medium',
		'decorative' => true,
	)
);
```

### Hero по ID вложения

```php
<?php
$hero_id = (int) get_post_meta( get_the_ID(), 'hero_image_id', true );
echo webp_ac_hero_image_html( $hero_id, true, 'hero__bg', 'full' );
```

### Контент записи (Gutenberg / классический редактор)

```php
<?php
// Только картинки из post_content
echo webp_ac_get_the_content_images_html();

// Или полный контент с заменой wp-image
echo webp_ac_render_rich_content( get_the_content() );
```

Замена в готовой HTML-строке:

```php
<?php
$html = webp_ac_replace_content_images( $post->post_content );
```

### Декоративная иконка (ID из meta)

```php
<?php
$icon_id = (int) get_post_meta( get_the_ID(), 'service_icon', true );
echo webp_ac_icon_html( $icon_id, __( 'Consulting', 'my-theme' ), 'service-card__icon' );
```

### Автофильтр `the_content` (выключен по умолчанию)

```php
add_filter( 'webp_ac_filter_the_content', '__return_true' );
```

---

## С ACF (опционально)

Если используется Advanced Custom Fields, те же функции принимают значение поля напрямую:

```php
<?php
echo webp_ac_attachment_image_html(
	get_field( 'hero_image' ),
	array(
		'class'  => 'hero__image',
		'size'   => 'large',
		'is_lcp' => true,
	)
);
```

ACF может вернуть ID, URL или массив — `webp_ac_normalize_image()` обработает все варианты.

## Справочник функций

| Функция | Назначение |
|---------|------------|
| `webp_ac_normalize_image( $image )` | ID / URL / массив → `['ID' => …]` |
| `webp_ac_get_webp_url( $url )` | WebP URL, если файл существует |
| `webp_ac_srcset_to_webp( $srcset )` | Замена расширений в srcset |
| `webp_ac_get_image_html( $img, $args )` | Основной вывод: `<picture>` или `<img srcset>` |
| `webp_ac_wp_attachment_image( $id, $size, $args )` | Аналог `wp_get_attachment_image()` + WebP |
| `webp_ac_get_the_post_thumbnail_html( $post, $args )` | Featured image |
| `webp_ac_the_post_thumbnail( $size, $args )` | Echo featured image |
| `webp_ac_get_image_from_post_meta( $post, $key, $args )` | Картинка из post meta |
| `webp_ac_get_image_from_option( $name, $args )` | Картинка из `get_option()` |
| `webp_ac_icon_html( … )` | Декоративная иконка |
| `webp_ac_hero_image_html( … )` | Full-width hero, `sizes="100vw"` |
| `webp_ac_attachment_image_html( … )` | Универсальная обёртка + fallback |
| `webp_ac_get_the_content_images_html( $post, $args )` | `post_content` с заменой wp-image |
| `webp_ac_replace_content_images( $html )` | Замена wp-image в HTML |
| `webp_ac_render_rich_content( $html )` | `wp_kses_post` + замена картинок |

## Аргументы `webp_ac_get_image_html()`

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `class` | `''` | CSS-класс |
| `size` | `large` | Размер WordPress |
| `sizes` | `(max-width: 768px) 100vw, 1200px` | Атрибут `sizes` |
| `loading` | `lazy` | `lazy` / `eager` |
| `is_lcp` | `false` | `fetchpriority="high"` + eager |
| `decorative` | `false` | Пустой alt + `aria-hidden` |
| `context_label` | `''` | Fallback для alt у иконок |

## Что генерируется

При наличии WebP-файлов:

```html
<picture>
  <source srcset="…webp…" type="image/webp" sizes="…">
  <img src="…jpg…" srcset="…jpg…" sizes="…" alt="…" loading="lazy" decoding="async" width="…" height="…">
</picture>
```

Без WebP — обычный `<img srcset="…">`.
