<?php
/**
 * Plug-and-play front-end output: hooks WordPress image APIs automatically.
 *
 * @package WebP_Auto_Converter
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether automatic front-end image enhancement is enabled.
 */
function webp_ac_is_auto_output_enabled(): bool {
	$enabled = (bool) get_option( WEBP_AUTO_CONVERTER_AUTO_OUTPUT, true );

	/**
	 * Override plug-and-play mode.
	 *
	 * @param bool $enabled Whether auto output is on.
	 */
	return (bool) apply_filters( 'webp_ac_auto_output_enabled', $enabled );
}

/**
 * Whether auto output should run for the current request.
 */
function webp_ac_should_auto_output(): bool {
	if ( ! webp_ac_is_auto_output_enabled() ) {
		return false;
	}

	if ( is_admin() && ! wp_doing_ajax() ) {
		return false;
	}

	if ( is_feed() || is_preview() || wp_is_json_request() ) {
		return false;
	}

	/**
	 * Last chance to skip auto output (e.g. AMP, email templates).
	 *
	 * @param bool $should Whether to enhance images on this request.
	 */
	return (bool) apply_filters( 'webp_ac_should_auto_output', true );
}

/**
 * Map wp_get_attachment_image / thumbnail attributes to helper args.
 *
 * @param array<string,mixed>|string $attr Attachment attributes.
 * @param string                     $html Existing HTML for class fallback.
 * @return array<string,mixed>
 */
function webp_ac_parse_attrs_for_image_args( $attr, string $html = '' ): array {
	$args = array();

	if ( is_string( $attr ) && '' !== $attr ) {
		if ( preg_match( '/class=["\']([^"\']+)["\']/', $attr, $matches ) ) {
			$args['class'] = $matches[1];
		}
		if ( preg_match( '/loading=["\']([^"\']+)["\']/', $attr, $matches ) ) {
			$args['loading'] = $matches[1];
		}
	} elseif ( is_array( $attr ) ) {
		if ( ! empty( $attr['class'] ) ) {
			$args['class'] = (string) $attr['class'];
		}
		if ( ! empty( $attr['loading'] ) ) {
			$args['loading'] = (string) $attr['loading'];
		}
		if ( ! empty( $attr['fetchpriority'] ) && 'high' === $attr['fetchpriority'] ) {
			$args['is_lcp'] = true;
		}
	}

	if ( empty( $args['class'] ) && '' !== $html && preg_match( '/class=["\']([^"\']+)["\']/', $html, $matches ) ) {
		$args['class'] = $matches[1];
	}

	return $args;
}

/**
 * Skip HTML that is already enhanced or not a raster image tag.
 */
function webp_ac_should_skip_html_replacement( string $html, int $attachment_id = 0 ): bool {
	if ( '' === $html || false !== stripos( $html, '<picture' ) ) {
		return true;
	}

	if ( $attachment_id > 0 ) {
		$mime = get_post_mime_type( $attachment_id );
		if ( $mime && ! in_array( $mime, array( 'image/jpeg', 'image/png' ), true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Filter wp_get_attachment_image() output.
 *
 * @param string              $html            Original HTML.
 * @param int                 $attachment_id   Attachment ID.
 * @param string|int[]        $size            Image size.
 * @param bool                $icon            Mime icon flag.
 * @param array<string,mixed> $attr            Attributes.
 */
function webp_ac_filter_wp_get_attachment_image( string $html, int $attachment_id, $size, bool $icon, $attr ): string {
	if ( $icon || ! webp_ac_should_auto_output() || webp_ac_should_skip_html_replacement( $html, $attachment_id ) ) {
		return $html;
	}

	$args            = webp_ac_parse_attrs_for_image_args( $attr, $html );
	$args['size']    = $size;
	$args['sizes']   = $args['sizes'] ?? webp_ac_default_sizes_attr();
	$replacement     = webp_ac_get_image_html( $attachment_id, $args );

	return '' !== $replacement ? $replacement : $html;
}

/**
 * Filter post thumbnail HTML.
 *
 * @param string              $html               Original HTML.
 * @param int                 $post_id            Post ID.
 * @param int                 $post_thumbnail_id  Thumbnail attachment ID.
 * @param string|int[]        $size               Image size.
 * @param array<string,mixed> $attr               Attributes.
 */
function webp_ac_filter_post_thumbnail_html( string $html, int $post_id, int $post_thumbnail_id, $size, $attr ): string {
	unset( $post_id );

	if ( ! webp_ac_should_auto_output() || $post_thumbnail_id <= 0 || webp_ac_should_skip_html_replacement( $html, $post_thumbnail_id ) ) {
		return $html;
	}

	$args          = webp_ac_parse_attrs_for_image_args( $attr, $html );
	$args['size']  = $size;
	$args['sizes'] = $args['sizes'] ?? webp_ac_default_sizes_attr();
	$replacement   = webp_ac_get_image_html( $post_thumbnail_id, $args );

	return '' !== $replacement ? $replacement : $html;
}

/**
 * Default sizes attribute for auto-enhanced images.
 */
function webp_ac_default_sizes_attr(): string {
	/**
	 * Default `sizes` for plug-and-play output.
	 *
	 * @param string $sizes sizes attribute value.
	 */
	return (string) apply_filters( 'webp_ac_default_sizes_attr', '(max-width: 768px) 100vw, 1200px' );
}

/**
 * Filter post / widget HTML content.
 */
function webp_ac_filter_html_content( string $content ): string {
	if ( ! webp_ac_should_auto_output() || '' === $content || false === stripos( $content, '<img' ) ) {
		return $content;
	}

	return webp_ac_replace_content_images(
		$content,
		array(
			'sizes' => webp_ac_default_sizes_attr(),
		)
	);
}

/**
 * Legacy manual the_content toggle (when plug-and-play is off).
 *
 * @param string $content Post content.
 */
function webp_ac_filter_the_content_legacy( string $content ): string {
	if ( webp_ac_is_auto_output_enabled() ) {
		return $content;
	}

	if ( ! apply_filters( 'webp_ac_filter_the_content', false ) ) {
		return $content;
	}

	if ( is_admin() || '' === $content || false === stripos( $content, '<img' ) ) {
		return $content;
	}

	return webp_ac_replace_content_images( $content );
}

/**
 * Register plug-and-play hooks.
 */
function webp_ac_register_auto_output_hooks(): void {
	add_filter( 'wp_get_attachment_image', 'webp_ac_filter_wp_get_attachment_image', 20, 5 );
	add_filter( 'post_thumbnail_html', 'webp_ac_filter_post_thumbnail_html', 20, 5 );
	add_filter( 'the_content', 'webp_ac_filter_html_content', 25 );
	add_filter( 'widget_text_content', 'webp_ac_filter_html_content', 25 );
	add_filter( 'widget_block_content', 'webp_ac_filter_html_content', 25 );
	add_filter( 'the_content', 'webp_ac_filter_the_content_legacy', 26 );
}

webp_ac_register_auto_output_hooks();
