<?php
/**
 * Plugin Name:       WebP Auto Converter
 * Plugin URI:        https://github.com/EvgeniSasim/webp-auto-converter
 * Description:       Converts uploaded JPEG and PNG images to WebP and automatically serves them on the front end (plug & play).
 * Version:           1.4.0
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

const WEBP_AUTO_CONVERTER_VERSION      = '1.4.0';
const WEBP_AUTO_CONVERTER_OPTION       = 'webp_auto_converter_quality';
const WEBP_AUTO_CONVERTER_AUTO_OUTPUT  = 'webp_auto_converter_auto_output';
const WEBP_AUTO_CONVERTER_BATCH        = 25;

register_activation_hook( __FILE__, 'webp_auto_converter_activate' );

/**
 * Set default options on activation.
 */
function webp_auto_converter_activate(): void {
	add_option( WEBP_AUTO_CONVERTER_OPTION, 82 );
	add_option( WEBP_AUTO_CONVERTER_AUTO_OUTPUT, 1 );
}

// --- Settings ---
add_action( 'admin_menu', 'webp_auto_converter_menu' );
add_action( 'admin_init', 'webp_auto_converter_settings' );
add_action( 'admin_enqueue_scripts', 'webp_auto_converter_admin_assets' );

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

	register_setting(
		'webp_auto_converter_options',
		WEBP_AUTO_CONVERTER_AUTO_OUTPUT,
		array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => static function ( $value ) {
				return ! empty( $value );
			},
		)
	);

	add_settings_section(
		'webp_auto_converter_main',
		'',
		null,
		'webp-auto-converter'
	);

	add_settings_field(
		WEBP_AUTO_CONVERTER_AUTO_OUTPUT,
		__( 'Plug & play front-end output', 'webp-auto-converter' ),
		'webp_auto_converter_auto_output_field',
		'webp-auto-converter',
		'webp_auto_converter_main'
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
 * Render plug-and-play setting field.
 */
function webp_auto_converter_auto_output_field(): void {
	$value = (bool) get_option( WEBP_AUTO_CONVERTER_AUTO_OUTPUT, true );
	echo '<input type="hidden" name="' . esc_attr( WEBP_AUTO_CONVERTER_AUTO_OUTPUT ) . '" value="0">';
	echo '<label>';
	echo '<input type="checkbox" name="' . esc_attr( WEBP_AUTO_CONVERTER_AUTO_OUTPUT ) . '" value="1" ' . checked( $value, true, false ) . '>';
	echo ' ' . esc_html__( 'Automatically output WebP in themes (no code required)', 'webp-auto-converter' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Enhances featured images, attachment images, and post/widget content with responsive <picture> markup when WebP files exist.', 'webp-auto-converter' ) . '</p>';
}

/**
 * Render quality field.
 */
function webp_auto_converter_quality_field(): void {
	$value = (int) get_option( WEBP_AUTO_CONVERTER_OPTION, 82 );
	?>
	<div class="webp-ac-quality">
		<input
			type="range"
			id="webp-ac-quality-range"
			class="webp-ac-quality__range"
			min="0"
			max="100"
			value="<?php echo esc_attr( (string) $value ); ?>"
			aria-label="<?php echo esc_attr__( 'WebP quality (0–100)', 'webp-auto-converter' ); ?>"
		>
		<input
			type="number"
			id="webp-ac-quality-number"
			class="small-text webp-ac-quality__number"
			name="<?php echo esc_attr( WEBP_AUTO_CONVERTER_OPTION ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			min="0"
			max="100"
			aria-label="<?php echo esc_attr__( 'WebP quality (0–100)', 'webp-auto-converter' ); ?>"
		>
	</div>
	<p class="description">
		<?php echo esc_html__( 'Lower values produce smaller files. 80–85 is a good balance for photos.', 'webp-auto-converter' ); ?>
	</p>
	<p class="description">
		<?php echo esc_html__( 'Applies to new conversions. Re-run batch below to regenerate existing media.', 'webp-auto-converter' ); ?>
	</p>
	<?php
}

/**
 * Detect which image backend is used for conversion.
 *
 * @return string|null "imagick", "gd", or null when unavailable.
 */
function webp_auto_converter_get_converter_backend(): ?string {
	if ( ! webp_auto_converter_gd_or_imagick_available() ) {
		return null;
	}

	if ( class_exists( 'Imagick' ) ) {
		return 'imagick';
	}

	if ( function_exists( 'imagewebp' ) && function_exists( 'imagecreatefromjpeg' ) ) {
		return 'gd';
	}

	return null;
}

/**
 * Count JPEG/PNG attachments in the media library.
 */
function webp_auto_converter_count_convertible_attachments(): int {
	$query = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => array( 'image/jpeg', 'image/png' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'no_found_rows'  => false,
		)
	);

	return (int) $query->found_posts;
}

/**
 * Theme helpers documentation URL.
 */
function webp_auto_converter_docs_url(): string {
	/**
	 * Filters the theme helpers documentation URL shown in admin.
	 *
	 * @param string $url Documentation URL.
	 */
	return (string) apply_filters(
		'webp_ac_docs_url',
		'https://github.com/EvgeniSasim/webp-auto-converter/blob/main/docs/theme-helpers.md'
	);
}

/**
 * Enqueue admin assets on the settings screen.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 */
function webp_auto_converter_admin_assets( string $hook_suffix ): void {
	if ( 'settings_page_webp-auto-converter' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'webp-ac-admin',
		plugins_url( 'assets/admin.css', __FILE__ ),
		array(),
		WEBP_AUTO_CONVERTER_VERSION
	);

	wp_enqueue_script(
		'webp-ac-admin',
		plugins_url( 'assets/admin.js', __FILE__ ),
		array(),
		WEBP_AUTO_CONVERTER_VERSION,
		true
	);

	wp_localize_script(
		'webp-ac-admin',
		'webpAcAdmin',
		array(
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'webp_auto_converter_batch' ),
			'converterAvailable' => webp_auto_converter_gd_or_imagick_available(),
			'i18n'               => array(
				'start'        => __( 'Generate WebP', 'webp-auto-converter' ),
				'running'      => __( 'Converting…', 'webp-auto-converter' ),
				'starting'     => __( 'Starting…', 'webp-auto-converter' ),
				'error'        => __( 'Error', 'webp-auto-converter' ),
				'networkError' => __( 'Network error. Check your connection and try again.', 'webp-auto-converter' ),
				'noImages'     => __( 'No JPEG or PNG images found in the media library.', 'webp-auto-converter' ),
				'progress'     => __( 'Processed %1$s of %2$s attachments · %3$s WebP files created', 'webp-auto-converter' ),
				'done'         => __( 'All done. %1$s WebP files created across %2$s attachments.', 'webp-auto-converter' ),
			),
		)
	);
}

/**
 * Render the read-only status strip.
 */
function webp_auto_converter_render_status_strip(): void {
	$backend      = webp_auto_converter_get_converter_backend();
	$auto_output  = (bool) get_option( WEBP_AUTO_CONVERTER_AUTO_OUTPUT, true );
	$converter_ok = null !== $backend;
	?>
	<div class="webp-ac-status" role="region" aria-label="<?php echo esc_attr__( 'Plugin status', 'webp-auto-converter' ); ?>">
		<p class="webp-ac-status__item">
			<span class="webp-ac-status__dot<?php echo esc_attr( $converter_ok ? ' webp-ac-status__dot--ok' : '' ); ?>" aria-hidden="true"></span>
			<?php
			if ( 'imagick' === $backend ) {
				echo esc_html__( 'Converter ready (Imagick)', 'webp-auto-converter' );
			} elseif ( 'gd' === $backend ) {
				echo esc_html__( 'Converter ready (GD)', 'webp-auto-converter' );
			} else {
				echo esc_html__( 'Converter unavailable', 'webp-auto-converter' );
			}
			?>
		</p>
		<p class="webp-ac-status__item">
			<span class="webp-ac-status__dot<?php echo esc_attr( $auto_output ? ' webp-ac-status__dot--ok' : ' webp-ac-status__dot--off' ); ?>" aria-hidden="true"></span>
			<?php
			echo $auto_output
				? esc_html__( 'Plug & play: On', 'webp-auto-converter' )
				: esc_html__( 'Plug & play: Off', 'webp-auto-converter' );
			?>
		</p>
		<?php if ( $converter_ok ) : ?>
			<p class="webp-ac-status__item">
				<span class="webp-ac-status__dot webp-ac-status__dot--ok" aria-hidden="true"></span>
				<?php echo esc_html__( 'New uploads: WebP generated automatically', 'webp-auto-converter' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render settings page.
 */
function webp_auto_converter_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$converter_ok = webp_auto_converter_gd_or_imagick_available();
	?>
	<div class="wrap" id="webp-ac-settings">
		<h1><?php echo esc_html__( 'WebP Converter', 'webp-auto-converter' ); ?></h1>

		<?php if ( ! $converter_ok ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php
					echo esc_html__(
						'WebP conversion is unavailable on this server. Enable GD with imagewebp support or Imagick with WebP support, then refresh this page.',
						'webp-auto-converter'
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<?php webp_auto_converter_render_status_strip(); ?>

		<div class="postbox webp-ac-postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php echo esc_html__( 'Conversion settings', 'webp-auto-converter' ); ?></h2>
			</div>
			<div class="inside">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'webp_auto_converter_options' );
					do_settings_sections( 'webp-auto-converter' );
					submit_button();
					?>
				</form>
			</div>
		</div>

		<div class="postbox webp-ac-postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php echo esc_html__( 'Existing media', 'webp-auto-converter' ); ?></h2>
			</div>
			<div class="inside">
				<p><?php echo esc_html__( 'Generate WebP for images that were uploaded before this plugin was active.', 'webp-auto-converter' ); ?></p>
				<p>
					<button
						type="button"
						class="button button-primary"
						id="webp-ac-batch-start"
						<?php disabled( ! $converter_ok ); ?>
					>
						<?php echo esc_html__( 'Generate WebP', 'webp-auto-converter' ); ?>
					</button>
				</p>
				<div
					id="webp-ac-batch-progress"
					class="webp-ac-progress"
					role="progressbar"
					aria-valuemin="0"
					aria-valuemax="100"
					aria-valuenow="0"
					hidden
				>
					<div id="webp-ac-batch-progress-bar" class="webp-ac-progress__bar"></div>
				</div>
				<p id="webp-ac-batch-status" class="webp-ac-batch-status" aria-live="polite" aria-atomic="true"></p>
			</div>
		</div>

		<details class="postbox webp-ac-postbox webp-ac-dev">
			<summary><?php echo esc_html__( 'For developers', 'webp-auto-converter' ); ?></summary>
			<div class="webp-ac-dev__body">
				<p>
					<?php
					echo esc_html__(
						'Plug & play covers most themes. For custom templates, use the theme helper functions in your PHP templates.',
						'webp-auto-converter'
					);
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( webp_auto_converter_docs_url() ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html__( 'View theme helpers documentation', 'webp-auto-converter' ); ?>
						<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'webp-auto-converter' ); ?></span>
					</a>
				</p>
				<p><?php echo esc_html__( 'Disable auto output in code:', 'webp-auto-converter' ); ?></p>
				<code class="webp-ac-dev__code">add_filter( 'webp_ac_auto_output_enabled', '__return_false' );</code>
			</div>
		</details>
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

	$batch_count = count( $query->posts );
	$processed   = $offset + $batch_count;
	$next_offset = $offset + WEBP_AUTO_CONVERTER_BATCH;
	$done        = $batch_count < WEBP_AUTO_CONVERTER_BATCH;
	$total       = 0 === $offset ? webp_auto_converter_count_convertible_attachments() : null;

	$response = array(
		'done'            => $done,
		'next_offset'     => $done ? $offset : $next_offset,
		'processed'       => $processed,
		'converted_batch' => $converted,
		'message'         => $done
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
	);

	if ( null !== $total ) {
		$response['total'] = $total;
	}

	wp_send_json_success( $response );
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
require_once __DIR__ . '/includes/auto-output.php';
