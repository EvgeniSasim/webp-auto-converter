<?php
/**
 * Theme-friendly helpers for responsive <picture> output with WebP.
 *
 * @package WebP_Auto_Converter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Normalize attachment input: ID, URL, or image array (`ID` / `id` keys).
 *
 * @param mixed $image Attachment ID, URL, or image array.
 * @return array{ID:int,url?:string,alt?:string,decorative?:bool,context_label?:string}|null
 */
function webp_ac_normalize_image( $image ): ?array {
	if ( null === $image || false === $image || '' === $image ) {
		return null;
	}

	if ( is_numeric( $image ) ) {
		$id = (int) $image;

		return $id > 0 ? array( 'ID' => $id ) : null;
	}

	if ( is_string( $image ) ) {
		if ( is_numeric( $image ) ) {
			$id = (int) $image;

			return $id > 0 ? array( 'ID' => $id ) : null;
		}

		$id = attachment_url_to_postid( $image );

		return $id > 0 ? array( 'ID' => $id, 'url' => $image ) : null;
	}

	if ( ! is_array( $image ) ) {
		return null;
	}

	if ( ! empty( $image['ID'] ) ) {
		$image['ID'] = (int) $image['ID'];

		return $image;
	}

	if ( ! empty( $image['id'] ) ) {
		$image['ID'] = (int) $image['id'];

		return $image;
	}

	return null;
}

/**
 * Return WebP URL when a sibling file exists, otherwise empty string.
 *
 * @param string $url JPEG or PNG URL.
 */
function webp_ac_get_webp_url( string $url ): string {
	if ( '' === $url ) {
		return '';
	}

	$webp = webp_auto_converter_url_to_webp( $url );

	return webp_auto_converter_url_file_exists( $webp ) ? $webp : '';
}

/**
 * Convert a JPEG/PNG srcset string to WebP URLs.
 *
 * @param string $srcset Original srcset attribute value.
 */
function webp_ac_srcset_to_webp( string $srcset ): string {
	if ( '' === $srcset ) {
		return '';
	}

	return (string) preg_replace( '/\.(jpe?g|png)(\s+\d+w)?/i', '.webp$2', $srcset );
}

/**
 * Build a single <img> tag from attributes.
 *
 * @param array<string,mixed> $attrs Image attributes.
 * @param string              $class Optional CSS class.
 */
function webp_ac_build_img_tag( array $attrs, string $class = '' ): string {
	$html = '<img';

	foreach ( $attrs as $key => $value ) {
		if ( 'alt' === $key ) {
			$html .= sprintf( ' alt="%s"', esc_attr( (string) $value ) );
			continue;
		}

		if ( 'aria-hidden' === $key && ( true === $value || 'true' === $value ) ) {
			$html .= ' aria-hidden="true"';
			continue;
		}

		if ( null !== $value && '' !== $value ) {
			$html .= sprintf( ' %s="%s"', esc_attr( (string) $key ), esc_attr( (string) $value ) );
		}
	}

	if ( '' !== $class ) {
		$html .= ' class="' . esc_attr( $class ) . '"';
	}

	$html .= '>';

	return $html;
}

/**
 * Resolve alt text from image data and attachment meta.
 *
 * @param int                 $attachment_id Attachment ID.
 * @param array<string,mixed> $img           Normalized image data.
 * @param bool                $decorative    Whether the image is decorative.
 */
function webp_ac_resolve_image_alt( int $attachment_id, array $img, bool $decorative = false ): string {
	if ( $decorative ) {
		return '';
	}

	if ( ! empty( $img['alt'] ) ) {
		return (string) $img['alt'];
	}

	$meta_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
	if ( is_string( $meta_alt ) && '' !== $meta_alt ) {
		return $meta_alt;
	}

	if ( ! empty( $img['context_label'] ) ) {
		return (string) $img['context_label'];
	}

	$title = get_the_title( $attachment_id );
	if ( $title && ! preg_match( '/^(image|attachment)-\d+$/i', $title ) ) {
		return $title;
	}

	return '';
}

/**
 * Apply accessibility-related attributes.
 *
 * @param array<string,mixed> $attrs       Image attributes.
 * @param string              $alt         Alt text.
 * @param bool                $decorative  Decorative flag.
 * @return array<string,mixed>
 */
function webp_ac_apply_image_a11y_attrs( array $attrs, string $alt, bool $decorative ): array {
	$attrs['alt'] = $alt;

	if ( $decorative ) {
		$attrs['aria-hidden'] = true;
		unset( $attrs['role'] );
	}

	return $attrs;
}

/**
 * Render responsive image markup with optional <picture> WebP source.
 *
 * @param mixed              $img   Attachment ID, URL, or image array.
 * @param array<string,mixed> $args {
 *     Optional arguments.
 *
 *     @type string $class    CSS class.
 *     @type string $size     Registered image size. Default 'large'.
 *     @type string $sizes    sizes attribute. Default '(max-width: 768px) 100vw, 1200px'.
 *     @type string $loading  loading attribute. Default 'lazy'.
 *     @type bool   $is_lcp   Mark as LCP candidate (eager + fetchpriority=high).
 *     @type bool   $decorative Decorative image (empty alt + aria-hidden).
 *     @type string $context_label Fallback alt context for icons.
 * }
 */
function webp_ac_get_image_html( $img, array $args = array() ): string {
	$img = webp_ac_normalize_image( $img );
	if ( ! $img ) {
		return '';
	}

	$defaults = array(
		'class'          => '',
		'size'           => 'large',
		'sizes'          => '(max-width: 768px) 100vw, 1200px',
		'loading'        => 'lazy',
		'is_lcp'         => false,
		'decorative'     => false,
		'context_label'  => '',
	);

	$args = array_merge( $defaults, $args );

	if ( $args['decorative'] ) {
		$img['decorative'] = true;
	}
	if ( '' !== $args['context_label'] ) {
		$img['context_label'] = (string) $args['context_label'];
	}

	$id = (int) $img['ID'];
	if ( $id <= 0 ) {
		return '';
	}

	$decorative   = ! empty( $img['decorative'] );
	$mime_type    = get_post_mime_type( $id );
	$class        = (string) $args['class'];
	$sizes        = esc_attr( (string) $args['sizes'] );
	$loading_attr = $args['is_lcp'] ? 'eager' : (string) $args['loading'];
	$alt          = webp_ac_resolve_image_alt( $id, $img, $decorative );

	if ( 'image/svg+xml' === $mime_type ) {
		$url = wp_get_attachment_url( $id );
		if ( ! $url ) {
			return '';
		}

		$meta   = wp_get_attachment_metadata( $id );
		$width  = ! empty( $meta['width'] ) ? (int) $meta['width'] : null;
		$height = ! empty( $meta['height'] ) ? (int) $meta['height'] : null;

		$attrs = array(
			'src'      => esc_url( $url ),
			'loading'  => $loading_attr,
			'decoding' => 'async',
		);

		if ( ! $decorative && '' !== $alt ) {
			$attrs['role'] = 'img';
		}
		if ( $width && $height ) {
			$attrs['width']  = $width;
			$attrs['height'] = $height;
		}
		if ( $args['is_lcp'] ) {
			$attrs['fetchpriority'] = 'high';
		}

		$attrs = webp_ac_apply_image_a11y_attrs( $attrs, $alt, $decorative );

		return webp_ac_build_img_tag( $attrs, $class );
	}

	$img_data = wp_get_attachment_image_src( $id, $args['size'] );
	if ( ! $img_data ) {
		return '';
	}

	list( $url, $width, $height ) = $img_data;
	$srcset = wp_get_attachment_image_srcset( $id, $args['size'] );

	$base_attrs = array(
		'loading'  => $loading_attr,
		'decoding' => 'async',
	);
	if ( $args['is_lcp'] ) {
		$base_attrs['fetchpriority'] = 'high';
	}

	if ( ! $srcset ) {
		$attrs = array_merge(
			$base_attrs,
			array(
				'src'    => esc_url( $url ),
				'width'  => $width,
				'height' => $height,
			)
		);
		$attrs = webp_ac_apply_image_a11y_attrs( $attrs, $alt, $decorative );

		return webp_ac_build_img_tag( $attrs, $class );
	}

	$webp_srcset = '';
	if ( webp_ac_get_webp_url( $url ) ) {
		$webp_srcset = webp_ac_srcset_to_webp( $srcset );
	}

	if ( '' !== $webp_srcset ) {
		$html  = '<picture>';
		$html .= sprintf( '<source srcset="%s" type="image/webp" sizes="%s">', esc_attr( $webp_srcset ), $sizes );

		$attrs = array_merge(
			$base_attrs,
			array(
				'src'    => esc_url( $url ),
				'srcset' => esc_attr( $srcset ),
				'sizes'  => $sizes,
				'width'  => $width,
				'height' => $height,
			)
		);
		$attrs = webp_ac_apply_image_a11y_attrs( $attrs, $alt, $decorative );

		$html .= webp_ac_build_img_tag( $attrs, $class );
		$html .= '</picture>';

		return $html;
	}

	$attrs = array_merge(
		$base_attrs,
		array(
			'src'    => esc_url( $url ),
			'srcset' => esc_attr( $srcset ),
			'sizes'  => $sizes,
			'width'  => $width,
			'height' => $height,
		)
	);
	$attrs = webp_ac_apply_image_a11y_attrs( $attrs, $alt, $decorative );

	return webp_ac_build_img_tag( $attrs, $class );
}

/**
 * Decorative icon next to visible text.
 *
 * @param mixed  $image          Attachment ID, URL, or image array.
 * @param string $context_label  Visible label used as fallback alt context.
 * @param string $class          CSS class.
 * @param string $size           Registered image size. Default 'thumbnail'.
 */
function webp_ac_icon_html( $image, string $context_label = '', string $class = '', string $size = 'thumbnail' ): string {
	$image = webp_ac_normalize_image( $image );
	if ( ! $image ) {
		return '';
	}

	if ( '' !== $context_label ) {
		$image['context_label'] = $context_label;
	}
	$image['decorative'] = true;

	return webp_ac_get_image_html(
		$image,
		array(
			'class'      => $class,
			'size'       => $size,
			'sizes'      => '40px',
			'decorative' => true,
		)
	);
}

/**
 * Full-width hero / background image as discoverable <img> (better LCP than CSS background).
 *
 * @param mixed  $img    Attachment ID, URL, or image array.
 * @param bool   $is_lcp LCP candidate flag.
 * @param string $class  CSS class.
 * @param string $size   Registered image size. Default 'full'.
 */
function webp_ac_hero_image_html( $img, bool $is_lcp = true, string $class = 'hero__bg', string $size = 'full' ): string {
	$img = webp_ac_normalize_image( $img );
	if ( ! $img ) {
		return '';
	}

	return webp_ac_get_image_html(
		$img,
		array(
			'class'   => $class,
			'size'    => $size,
			'sizes'   => '100vw',
			'loading' => $is_lcp ? 'eager' : 'lazy',
			'is_lcp'  => $is_lcp,
		)
	);
}

/**
 * Attachment ID, URL, or image array with sensible defaults.
 *
 * Accepts attachment ID, media URL, or array with `ID` / `id` (e.g. from custom fields).
 *
 * @param mixed              $image   Attachment ID, URL, or image array.
 * @param array<string,mixed> $options Same keys as webp_ac_get_image_html() args.
 */
function webp_ac_attachment_image_html( $image, array $options = array() ): string {
	$image = webp_ac_normalize_image( $image );
	if ( ! $image ) {
		return '';
	}

	$html = webp_ac_get_image_html( $image, $options );
	if ( '' !== $html ) {
		return $html;
	}

	$size    = $options['size'] ?? 'large';
	$loading = ! empty( $options['is_lcp'] ) ? 'eager' : ( $options['loading'] ?? 'lazy' );
	$attrs   = array(
		'loading' => $loading,
		'class'   => $options['class'] ?? '',
	);

	return (string) wp_get_attachment_image( (int) $image['ID'], $size, false, $attrs );
}

/**
 * Featured image markup for a post (no ACF required).
 *
 * @param int|WP_Post|null      $post Post object, ID, or null for current post.
 * @param array<string,mixed>   $args Same keys as webp_ac_get_image_html() args.
 */
function webp_ac_get_the_post_thumbnail_html( $post = null, array $args = array() ): string {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	$thumbnail_id = (int) get_post_thumbnail_id( $post );
	if ( $thumbnail_id <= 0 ) {
		return '';
	}

	$defaults = array(
		'size' => 'post-thumbnail',
	);
	$args = array_merge( $defaults, $args );

	return webp_ac_get_image_html( $thumbnail_id, $args );
}

/**
 * Echo featured image markup.
 *
 * @param string|array<string,mixed> $size Image size name or webp_ac_get_image_html() args when array.
 * @param array<string,mixed>        $args Extra args when $size is a string.
 */
function webp_ac_the_post_thumbnail( $size = 'post-thumbnail', array $args = array() ): void {
	if ( is_array( $size ) ) {
		$args = $size;
		$size = $args['size'] ?? 'post-thumbnail';
		unset( $args['size'] );
	}

	$args['size'] = (string) $size;

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in builder.
	echo webp_ac_get_the_post_thumbnail_html( null, $args );
}

/**
 * Image from post meta (attachment ID or media URL stored in meta).
 *
 * @param int|WP_Post|null    $post     Post object, ID, or null for current post.
 * @param string              $meta_key Post meta key.
 * @param array<string,mixed> $args     Same keys as webp_ac_get_image_html() args.
 */
function webp_ac_get_image_from_post_meta( $post, string $meta_key, array $args = array() ): string {
	$post = get_post( $post );
	if ( ! $post || '' === $meta_key ) {
		return '';
	}

	$value = get_post_meta( $post->ID, $meta_key, true );
	$image = webp_ac_normalize_image( $value );
	if ( ! $image ) {
		return '';
	}

	return webp_ac_get_image_html( $image, $args );
}

/**
 * Image from a site option (attachment ID or media URL).
 *
 * @param string              $option_name Option name.
 * @param array<string,mixed> $args        Same keys as webp_ac_get_image_html() args.
 */
function webp_ac_get_image_from_option( string $option_name, array $args = array() ): string {
	if ( '' === $option_name ) {
		return '';
	}

	$value = get_option( $option_name, null );
	$image = webp_ac_normalize_image( $value );
	if ( ! $image ) {
		return '';
	}

	return webp_ac_get_image_html( $image, $args );
}

/**
 * Drop-in for wp_get_attachment_image() with WebP <picture> when available.
 *
 * @param int|string          $attachment_id Attachment ID.
 * @param string|int[]        $size          Registered size name or [width, height].
 * @param array<string,mixed> $args          Same keys as webp_ac_get_image_html() args.
 */
function webp_ac_wp_attachment_image( $attachment_id, $size = 'thumbnail', array $args = array() ): string {
	$attachment_id = (int) $attachment_id;
	if ( $attachment_id <= 0 ) {
		return '';
	}

	if ( is_array( $size ) ) {
		$args['size'] = $size;
	} else {
		$args['size'] = (string) $size;
	}

	$html = webp_ac_get_image_html( $attachment_id, $args );
	if ( '' !== $html ) {
		return $html;
	}

	$loading = ! empty( $args['is_lcp'] ) ? 'eager' : ( $args['loading'] ?? 'lazy' );
	$attrs   = array(
		'loading' => $loading,
		'class'   => $args['class'] ?? '',
	);

	return (string) wp_get_attachment_image( $attachment_id, $size, false, $attrs );
}

/**
 * Post content with wp-image tags upgraded to <picture> markup.
 *
 * @param int|WP_Post|null    $post Post object, ID, or null for current post.
 * @param array<string,mixed> $args Replacement options.
 */
function webp_ac_get_the_content_images_html( $post = null, array $args = array() ): string {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}

	return webp_ac_replace_content_images( $post->post_content, $args );
}

/**
 * Replace <img class="wp-image-{id}"> tags in HTML with responsive <picture> markup.
 *
 * @param string              $content Post content HTML.
 * @param array<string,mixed> $args    Optional size, sizes, loading for replacements.
 */
function webp_ac_replace_content_images( string $content, array $args = array() ): string {
	if ( '' === $content || false === stripos( $content, '<img' ) ) {
		return $content;
	}

	$size       = $args['size'] ?? 'large';
	$sizes_attr = $args['sizes'] ?? '(max-width: 768px) 100vw, 800px';
	$loading    = $args['loading'] ?? 'lazy';

	return (string) preg_replace_callback(
		'/<img[^>]+wp-image-(\d+)[^>]*>/i',
		static function ( array $matches ) use ( $size, $sizes_attr, $loading ): string {
			$img_tag = $matches[0];
			$img_id  = (int) $matches[1];

			if ( ! wp_attachment_is_image( $img_id ) ) {
				return $img_tag;
			}

			$class = '';
			if ( preg_match( '/class=["\']([^"\']+)["\']/', $img_tag, $class_match ) ) {
				$class = $class_match[1];
			}

			$alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
			if ( '' === $alt && preg_match( '/\balt=["\']([^"\']*)["\']/', $img_tag, $alt_match ) ) {
				$alt = $alt_match[1];
			}

			$replacement = webp_ac_get_image_html(
				array(
					'ID'    => $img_id,
					'alt'   => is_string( $alt ) ? $alt : '',
					'title' => get_the_title( $img_id ),
				),
				array(
					'class'   => $class,
					'size'    => $size,
					'sizes'   => $sizes_attr,
					'loading' => $loading,
				)
			);

			return '' !== $replacement ? $replacement : $img_tag;
		},
		$content
	);
}

/**
 * Sanitized WYSIWYG output with optimized media library images.
 *
 * @param mixed              $content        HTML content.
 * @param array<string,mixed> $image_options Replacement options.
 */
function webp_ac_render_rich_content( $content, array $image_options = array() ): string {
	if ( null === $content || '' === $content || is_array( $content ) ) {
		return '';
	}

	$html = wp_kses_post( (string) $content );

	if ( false === stripos( $html, '<img' ) ) {
		return $html;
	}

	return webp_ac_replace_content_images( $html, $image_options );
}
