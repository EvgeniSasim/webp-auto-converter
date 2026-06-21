<?php
/**
 * Plugin Name:       WebP Auto Converter
 * Plugin URI:        https://github.com/EvgeniSasim/webp-auto-converter
 * Description:       Converts uploaded JPEG and PNG images to WebP (original and thumbnails), serves WebP in responsive srcset, and cleans up on delete.
 * Version:           1.2.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Evgenii Sasim
 * Author URI:        https://www.instagram.com/evgenii.sasim/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       webp-auto-converter
 *
 * @package WebP_Auto_Converter
 */

defined( 'ABSPATH' ) || exit;

const WEBP_AUTO_CONVERTER_OPTION = 'webp_auto_converter_quality';
const WEBP_AUTO_CONVERTER_BATCH  = 25;

// --- Settings ---
add_action( 'admin_menu', 'webp_auto_converter_menu' );
add_action( 'admin_init', 'webp_auto_converter_settings' );

/**
 * Register settings page under Settings.
 */
function webp_auto_converter_menu(): void {
	add_options_page(
		__( 'WebP Converter', 'webp-auto-converter' ),
		__( 'WebP Converter', 'webp-auto-converter' ),
		'manage_options',
		'webp-auto-converter',
		'webp_auto_converter_settings_page'
	);
}

/**
 * Register plugin settings.
 */
function webp_auto_converter_settings(): void {
	register_setting(
		'webp_auto_converter_options',
		WEBP_AUTO_CONVERTER_OPTION,
		array(
			'type'              => 'integer',
			'default'           => 82,
			'sanitize_callback' => static function ( $value ) {
				return max( 0, min( 100, absint( $value ) ) );
			},
		)
	);

	add_settings_section(
		'webp_auto_converter_main',
		__( 'Settings', 'webp-auto-converter' ),
		null,
		'webp-auto-converter'
	);

	add_settings_field(
		WEBP_AUTO_CONVERTER_OPTION,
		__( 'WebP quality (0–100)', 'webp-auto-converter' ),
		'webp_auto_converter_quality_field',
		'webp-auto-converter',
		'webp_auto_converter_main'
	);
}

/**
 * Render quality field.
 */
function webp_auto_converter_quality_field(): void {
	$value = (int) get_option( WEBP_AUTO_CONVERTER_OPTION, 82 );
	echo '<input type="number" name="' . esc_attr( WEBP_AUTO_CONVERTER_OPTION ) . '" value="' . esc_attr( (string) $value ) . '" min="0" max="100">';
	echo '<p class="description">' . esc_html__( 'Lower values produce smaller files. 80–85 is a good balance for photos.', 'webp-auto-converter' ) . '</p>';
}

/**
 * Render settings page.
 */
function webp_auto_converter_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php echo esc_html__( 'WebP Converter', 'webp-auto-converter' ); ?></h1>
		<form method="post" action="options.php">
			<?php
			settings_fields( 'webp_auto_converter_options' );
			do_settings_sections( 'webp-auto-converter' );
			submit_button();
			?>
		</form>
		<hr>
		<h2><?php echo esc_html__( 'Existing media', 'webp-auto-converter' ); ?></h2>
		<p><?php echo esc_html__( 'Generate WebP for images that were uploaded before this plugin was active.', 'webp-auto-converter' ); ?></p>
		<p>
			<button type="button" class="button button-primary" id="webp-regenerate-start"><?php echo esc_html__( 'Generate WebP (batch)', 'webp-auto-converter' ); ?></button>
			<span id="webp-regenerate-status" style="margin-left:12px;"></span>
		</p>
		<script>
		(function () {
			const btn = document.getElementById('webp-regenerate-start');
			const status = document.getElementById('webp-regenerate-status');
			if (!btn || !status) return;

			let offset = 0;
			let running = false;

			btn.addEventListener('click', async function () {
				if (running) return;
				running = true;
				offset = 0;
				btn.disabled = true;
				status.textContent = <?php echo wp_json_encode( __( 'Starting…', 'webp-auto-converter' ) ); ?>;

				while (true) {
					const body = new URLSearchParams();
					body.set('action', 'webp_auto_converter_batch');
					body.set('offset', String(offset));
					body.set('_ajax_nonce', '<?php echo esc_js( wp_create_nonce( 'webp_auto_converter_batch' ) ); ?>');

					const res = await fetch(ajaxurl, { method: 'POST', body, credentials: 'same-origin' });
					const data = await res.json();
					if (!data.success) {
						status.textContent = data.data?.message || <?php echo wp_json_encode( __( 'Error', 'webp-auto-converter' ) ); ?>;
						break;
					}
					const payload = data.data;
					offset = payload.next_offset;
					status.textContent = payload.message;
					if (payload.done) break;
				}

				btn.disabled = false;
				running = false;
			});
		})();
		</script>
	</div>
	<?php
}

// --- Convert on upload ---
add_filter( 'wp_generate_attachment_metadata', 'webp_auto_converter_generate_webp', 20, 2 );

/**
 * Generate WebP siblings after WordPress creates attachment metadata.
 *
 * @param array $metadata      Attachment metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function webp_auto_converter_generate_webp( $metadata, $attachment_id ) {
	$file_path = get_attached_file( $attachment_id );
	$mime_type = get_post_mime_type( $attachment_id );

	if ( ! webp_auto_converter_is_convertible_mime( $mime_type ) ) {
		return $metadata;
	}

	webp_auto_converter_convert_file( $file_path );

	if ( ! empty( $metadata['sizes'] ) && ! empty( $metadata['file'] ) ) {
		$upload_dir = wp_upload_dir();
		$base_dir   = path_join( $upload_dir['basedir'], dirname( $metadata['file'] ) );
		foreach ( $metadata['sizes'] as $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}
			webp_auto_converter_convert_file( path_join( $base_dir, $size['file'] ) );
		}
	}

	return $metadata;
}

// --- Prefer WebP in responsive srcset when a sibling .webp exists ---
add_filter( 'wp_calculate_image_srcset', 'webp_auto_converter_srcset_webp', 10, 5 );

/**
 * Swap srcset URLs to WebP when a sibling file exists.
 *
 * @param array  $sources       Srcset sources.
 * @param array  $size_array    Requested size.
 * @param string $image_src     Image source URL.
 * @param array  $image_meta    Attachment metadata.
 * @param int    $attachment_id Attachment ID.
 * @return array
 */
function webp_auto_converter_srcset_webp( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	unset( $size_array, $image_src, $image_meta, $attachment_id );

	if ( empty( $sources ) || is_admin() ) {
		return $sources;
	}

	foreach ( $sources as $width => $source ) {
		$webp_url = webp_auto_converter_url_to_webp( $source['url'] );
		if ( $webp_url && webp_auto_converter_url_file_exists( $webp_url ) ) {
			$sources[ $width ]['url'] = $webp_url;
		}
	}

	return $sources;
}

// --- Cleanup ---
add_action( 'delete_attachment', 'webp_auto_converter_delete_webp_versions' );

/**
 * Remove WebP siblings when an attachment is deleted.
 *
 * @param int $attachment_id Attachment ID.
 */
function webp_auto_converter_delete_webp_versions( $attachment_id ): void {
	$file       = get_attached_file( $attachment_id );
	$meta       = wp_get_attachment_metadata( $attachment_id );
	$upload_dir = wp_upload_dir();

	webp_auto_converter_unlink_webp( $file );

	if ( ! empty( $meta['sizes'] ) && ! empty( $meta['file'] ) ) {
		$base_dir = path_join( $upload_dir['basedir'], dirname( $meta['file'] ) );
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}
			webp_auto_converter_unlink_webp( path_join( $base_dir, $size['file'] ) );
		}
	}
}

// --- Batch AJAX ---
add_action( 'wp_ajax_webp_auto_converter_batch', 'webp_auto_converter_ajax_batch' );

/**
 * AJAX handler for batch WebP generation.
 */
function webp_auto_converter_ajax_batch(): void {
	check_ajax_referer( 'webp_auto_converter_batch' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Forbidden', 'webp-auto-converter' ) ), 403 );
	}

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

	$query = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => array( 'image/jpeg', 'image/png' ),
			'posts_per_page' => WEBP_AUTO_CONVERTER_BATCH,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	$converted = 0;
	foreach ( $query->posts as $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( $file && webp_auto_converter_convert_file( $file ) ) {
			++$converted;
		}
		$meta = wp_get_attachment_metadata( $attachment_id );
		if ( ! empty( $meta['sizes'] ) && ! empty( $meta['file'] ) ) {
			$upload_dir = wp_upload_dir();
			$base_dir   = path_join( $upload_dir['basedir'], dirname( $meta['file'] ) );
			foreach ( $meta['sizes'] as $size ) {
				if ( empty( $size['file'] ) ) {
					continue;
				}
				if ( webp_auto_converter_convert_file( path_join( $base_dir, $size['file'] ) ) ) {
					++$converted;
				}
			}
		}
	}

	$next_offset = $offset + WEBP_AUTO_CONVERTER_BATCH;
	$done        = $query->post_count < WEBP_AUTO_CONVERTER_BATCH;

	wp_send_json_success(
		array(
			'done'        => $done,
			'next_offset' => $done ? $offset : $next_offset,
			'message'     => $done
				? sprintf(
					/* translators: %d: number of converted files in the last batch */
					__( 'Done. Converted %d file(s) in the last batch.', 'webp-auto-converter' ),
					$converted
				)
				: sprintf(
					/* translators: 1: batch offset, 2: number of converted files */
					__( 'Processed batch (offset %1$d). Converted %2$d file(s). Continuing…', 'webp-auto-converter' ),
					$offset,
					$converted
				),
		)
	);
}

/**
 * Whether the mime type can be converted.
 *
 * @param string|null $mime Mime type.
 */
function webp_auto_converter_is_convertible_mime( ?string $mime ): bool {
	return in_array( $mime, array( 'image/jpeg', 'image/png' ), true );
}

/**
 * Convert a single image file to WebP.
 *
 * @param string $source_path Absolute path to JPEG or PNG.
 */
function webp_auto_converter_convert_file( string $source_path ): bool {
	if ( ! file_exists( $source_path ) || ! webp_auto_converter_gd_or_imagick_available() ) {
		return false;
	}

	$extension = strtolower( pathinfo( $source_path, PATHINFO_EXTENSION ) );
	if ( ! in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
		return false;
	}

	$destination = webp_auto_converter_path_to_webp( $source_path );
	if ( ! $destination ) {
		return false;
	}

	$quality = (int) get_option( WEBP_AUTO_CONVERTER_OPTION, 82 );

	if ( class_exists( 'Imagick' ) ) {
		try {
			$imagick = new Imagick( $source_path );
			if ( 'png' === $extension ) {
				$imagick->setImageFormat( 'webp' );
				$imagick->setOption( 'webp:lossless', 'false' );
			}
			$imagick->setImageCompressionQuality( $quality );
			$imagick->writeImage( $destination );
			$imagick->clear();
			$imagick->destroy();
			return file_exists( $destination );
		} catch ( Exception $e ) {
			// Fall through to GD.
			unset( $e );
		}
	}

	$image = null;
	if ( in_array( $extension, array( 'jpg', 'jpeg' ), true ) ) {
		$image = @imagecreatefromjpeg( $source_path );
	} elseif ( 'png' === $extension ) {
		$image = @imagecreatefrompng( $source_path );
		if ( $image && function_exists( 'imagepalettetotruecolor' ) ) {
			imagepalettetotruecolor( $image );
			imagealphablending( $image, true );
			imagesavealpha( $image, true );
		}
	}

	if ( ! $image ) {
		return false;
	}

	$ok = imagewebp( $image, $destination, $quality );
	imagedestroy( $image );

	return $ok && file_exists( $destination );
}

/**
 * Whether GD or Imagick WebP support is available.
 */
function webp_auto_converter_gd_or_imagick_available(): bool {
	return ( function_exists( 'imagewebp' ) && function_exists( 'imagecreatefromjpeg' ) )
		|| class_exists( 'Imagick' );
}

/**
 * Build WebP path from JPEG/PNG path.
 *
 * @param string $path Source path.
 */
function webp_auto_converter_path_to_webp( string $path ): string {
	return (string) preg_replace( '/\.(jpe?g|png)$/i', '.webp', $path );
}

/**
 * Build WebP URL from JPEG/PNG URL.
 *
 * @param string $url Source URL.
 */
function webp_auto_converter_url_to_webp( string $url ): string {
	return (string) preg_replace( '/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url );
}

/**
 * Whether a uploads URL maps to an existing WebP file.
 *
 * @param string $url Image URL.
 */
function webp_auto_converter_url_file_exists( string $url ): bool {
	$upload = wp_upload_dir();
	if ( empty( $upload['basedir'] ) || empty( $upload['baseurl'] ) ) {
		return false;
	}
	$path = str_replace( $upload['baseurl'], $upload['basedir'], preg_replace( '/\?.*$/', '', $url ) );
	return '' !== $path && file_exists( $path );
}

/**
 * Delete WebP sibling for a source image path.
 *
 * @param string $source_path Source path.
 */
function webp_auto_converter_unlink_webp( string $source_path ): void {
	$webp = webp_auto_converter_path_to_webp( $source_path );
	if ( $webp && file_exists( $webp ) ) {
		wp_delete_file( $webp );
	}
}

require_once __DIR__ . '/includes/image-helpers.php';
