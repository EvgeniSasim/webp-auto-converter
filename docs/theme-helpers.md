# Theme helpers — image output examples

Плагин подключает `includes/image-helpers.php` с функциями для вывода `<picture>` + WebP (`webp_ac_*`).

## Быстрый старт

### ACF image field в шаблоне

```php
<?php
$image = get_field( 'hero_image' );
echo webp_ac_attachment_image_html(
	$image,
	array(
		'class'  => 'hero__image',
		'size'   => 'large',
		'sizes'  => '(max-width: 768px) 100vw, 1200px',
		'is_lcp' => true,
	)
);
```

### Декоративная иконка рядом с текстом

```php
<?php
echo webp_ac_icon_html(
	get_field( 'service_icon' ),
	__( 'Consulting', 'my-theme' ),
	'service-card__icon'
);
```

### Hero-фон как `<img>` (лучше для LCP, чем CSS background)

```php
<?php
echo webp_ac_hero_image_html( get_field( 'background' ), true, 'main-section__bg', 'full' );
```

### Произвольный attachment ID

```php
<?php
echo webp_ac_get_image_html(
	123,
	array(
		'class'   => 'card__thumb',
		'size'    => 'medium_large',
		'sizes'   => '(max-width: 768px) 100vw, 50vw',
		'loading' => 'lazy',
	)
);
```

### Контент редактора (WYSIWYG)

```php
<?php
echo webp_ac_render_rich_content(
	get_field( 'body' ),
	array(
		'size'   => 'large',
		'sizes'  => '(max-width: 768px) 100vw, 800px',
		'loading'=> 'lazy',
	)
);
```

Или только замена `<img class="wp-image-*">` в готовой строке:

```php
<?php
$html = webp_ac_replace_content_images( $post->post_content );
```

### Автофильтр `the_content` (выключен по умолчанию)

Чтобы не конфликтовать с темой, фильтр нужно явно включить в `functions.php`:

```php
add_filter( 'webp_ac_filter_the_content', '__return_true' );
```

## Справочник функций

| Функция | Назначение |
|---------|------------|
| `webp_ac_normalize_image( $image )` | ID / URL / ACF-массив → `['ID' => …]` |
| `webp_ac_get_webp_url( $url )` | WebP URL, если файл существует |
| `webp_ac_srcset_to_webp( $srcset )` | Замена расширений в srcset |
| `webp_ac_get_image_html( $img, $args )` | Основной вывод: `<picture>` или `<img srcset>` |
| `webp_ac_icon_html( … )` | Декоративная иконка (`alt=""`, `aria-hidden`) |
| `webp_ac_hero_image_html( … )` | Full-width hero, `sizes="100vw"` |
| `webp_ac_attachment_image_html( … )` | Обёртка с fallback на `wp_get_attachment_image()` |
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
