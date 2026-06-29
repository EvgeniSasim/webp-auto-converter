<?php
/**
 * Plugin Name:       Sasim WebP Auto Converter
 * Plugin URI:        https://github.com/EvgeniSasim/webp-auto-converter
 * Description:       Converts uploaded JPEG and PNG images to WebP and automatically serves them on the front end (plug & play).
 * Version:           1.4.1
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Evgenii Sasim
 * Author URI:        https://www.instagram.com/evgenii.sasim/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sasim-webp-auto-converter
 *
 * @package Sasim_WebP_Auto_Converter
 */

defined( 'ABSPATH' ) || exit;

const SASWAC_VERSION      = '1.4.1';
const SASWAC_OPTION       = 'saswac_quality';
const SASWAC_AUTO_OUTPUT  = 'saswac_auto_output';
const SASWAC_BATCH        = 25;

register_activation_hook( __FILE__, 'saswac_activate' );

/**
 * Set default options on activation.
 */
function saswac_activate(): void {
	add_option( SASWAC_OPTION, 82 );
	add_option( SASWAC_AUTO_OUTPUT, 1 );
}

// --- Settings ---
add_action( 'admin_menu', 'saswac_menu' );
add_action( 'admin_init', 'saswac_settings' );
add_action( 'admin_enqueue_scripts', 'saswac_admin_assets' );

/**
 * Register settings page under Settings.
 */
function saswac_menu(): void {
	add_options_page(
		__( 'WebP Converter', 'sasim-webp-auto-converter' ),
		__( 'WebP Converter', 'sasim-webp-auto-converter' ),
		'manage_options',
		'sasim-webp-auto-converter',
		'saswac_settings_page'
	);
}

/**
 * Register plugin settings.
 */
function saswac_settings(): void {
	register_setting(
		'saswac_options',
		SASWAC_OPTION,
		array(
			'type'              => 'integer',
			'default'           => 82,
			'sanitize_callback' => static function ( $value ) {
				return max( 0, min( 100, absint( $value ) ) );
			},
		)
	);

	register_setting(
		'saswac_options',
		SASWAC_AUTO_OUTPUT,
		array(
			'type'              => 'boolean',
			'default'           => true,
			'sanitize_callback' => static function ( $value ) {
				return ! empty( $value );
			},
		)
	);

	add_settings_section(
		'saswac_main',
		'',
		null,
		'sasim-webp-auto-converter'
	);

	add_settings_field(
		SASWAC_AUTO_OUTPUT,
		__( 'Plug & play front-end output', 'sasim-webp-auto-converter' ),
		'saswac_auto_output_field',
		'sasim-webp-auto-converter',
		'saswac_main'
	);

	add_settings_field(
		SASWAC_OPTION,
		__( 'WebP quality (0–100)', 'sasim-webp-auto-converter' ),
		'saswac_quality_field',
		'sasim-webp-auto-converter',
		'saswac_main'
	);
}

/**
 * Render plug-and-play setting field.
 */
function saswac_auto_output_field(): void {
	$value = (bool) get_option( SASWAC_AUTO_OUTPUT, true );
	echo '<input type="hidden" name="' . esc_attr( SASWAC_AUTO_OUTPUT ) . '" value="0">';
	echo '<label>';
	echo '<input type="checkbox" name="' . esc_attr( SASWAC_AUTO_OUTPUT ) . '" value="1" ' . checked( $value, true, false ) . '>';
	echo ' ' . esc_html__( 'Automatically output WebP in themes (no code required)', 'sasim-webp-auto-converter' );
	echo '</label>';
	echo '<p class="description">' . esc_html__( 'Enhances featured images, attachment images, and post/widget content with responsive <picture> markup when WebP files exist.', 'sasim-webp-auto-converter' ) . '</p>';
}

/**
 * Render quality field.
 */
function saswac_quality_field(): void {
	$value = (int) get_option( SASWAC_OPTION, 82 );
	?>
	<div class="saswac-quality">
		<input
			type="range"
			id="saswac-quality-range"
			class="saswac-quality__range"
			min="0"
			max="100"
			value="<?php echo esc_attr( (string) $value ); ?>"
			aria-label="<?php echo esc_attr__( 'WebP quality (0–100)', 'sasim-webp-auto-converter' ); ?>"
		>
		<input
			type="number"
			id="saswac-quality-number"
			class="small-text saswac-quality__number"
			name="<?php echo esc_attr( SASWAC_OPTION ); ?>"
			value="<?php echo esc_attr( (string) $value ); ?>"
			min="0"
			max="100"
			aria-label="<?php echo esc_attr__( 'WebP quality (0–100)', 'sasim-webp-auto-converter' ); ?>"
		>
	</div>
	<p class="description">
		<?php echo esc_html__( 'Lower values produce smaller files. 80–85 is a good balance for photos.', 'sasim-webp-auto-converter' ); ?>
	</p>
	<p class="description">
		<?php echo esc_html__( 'Applies to new conversions. Re-run batch below to regenerate existing media.', 'sasim-webp-auto-converter' ); ?>
	</p>
	<?php
}

/**
 * Detect which image backend is used for conversion.
 *
 * @return string|null "imagick", "gd", or null when unavailable.
 */
function saswac_get_converter_backend(): ?string {
	if ( ! saswac_gd_or_imagick_available() ) {
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
function saswac_count_convertible_attachments(): int {
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
function saswac_docs_url(): string {
	/**
	 * Filters the theme helpers documentation URL shown in admin.
	 *
	 * @param string $url Documentation URL.
	 */
	return (string) apply_filters(
		'saswac_docs_url',
		'https://github.com/EvgeniSasim/webp-auto-converter/blob/main/docs/theme-helpers.md'
	);
}

/**
 * Enqueue admin assets on the settings screen.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 */
function saswac_admin_assets( string $hook_suffix ): void {
	if ( 'settings_page_sasim-webp-auto-converter' !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'saswac-admin',
		plugins_url( 'assets/admin.css', __FILE__ ),
		array(),
		SASWAC_VERSION
	);

	wp_enqueue_script(
		'saswac-admin',
		plugins_url( 'assets/admin.js', __FILE__ ),
		array(),
		SASWAC_VERSION,
		true
	);

	wp_localize_script(
		'saswac-admin',
		'saswacAdmin',
		array(
			'ajaxUrl'            => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'saswac_batch' ),
			'converterAvailable' => saswac_gd_or_imagick_available(),
			'i18n'               => array(
				'start'        => __( 'Generate WebP', 'sasim-webp-auto-converter' ),
				'running'      => __( 'Converting…', 'sasim-webp-auto-converter' ),
				'starting'     => __( 'Starting…', 'sasim-webp-auto-converter' ),
				'error'        => __( 'Error', 'sasim-webp-auto-converter' ),
				'networkError' => __( 'Network error. Check your connection and try again.', 'sasim-webp-auto-converter' ),
				'noImages'     => __( 'No JPEG or PNG images found in the media library.', 'sasim-webp-auto-converter' ),
				'progress'     => __( 'Processed %1$s of %2$s attachments · %3$s WebP files created', 'sasim-webp-auto-converter' ),
				'done'         => __( 'All done. %1$s WebP files created across %2$s attachments.', 'sasim-webp-auto-converter' ),
			),
		)
	);
}

/**
 * Render the read-only status strip.
 */
function saswac_render_status_strip(): void {
	$backend      = saswac_get_converter_backend();
	$auto_output  = (bool) get_option( SASWAC_AUTO_OUTPUT, true );
	$converter_ok = null !== $backend;
	?>
	<div class="saswac-status" role="region" aria-label="<?php echo esc_attr__( 'Plugin status', 'sasim-webp-auto-converter' ); ?>">
		<p class="saswac-status__item">
			<span class="saswac-status__dot<?php echo esc_attr( $converter_ok ? ' saswac-status__dot--ok' : '' ); ?>" aria-hidden="true"></span>
			<?php
			if ( 'imagick' === $backend ) {
				echo esc_html__( 'Converter ready (Imagick)', 'sasim-webp-auto-converter' );
			} elseif ( 'gd' === $backend ) {
				echo esc_html__( 'Converter ready (GD)', 'sasim-webp-auto-converter' );
			} else {
				echo esc_html__( 'Converter unavailable', 'sasim-webp-auto-converter' );
			}
			?>
		</p>
		<p class="saswac-status__item">
			<span class="saswac-status__dot<?php echo esc_attr( $auto_output ? ' saswac-status__dot--ok' : ' saswac-status__dot--off' ); ?>" aria-hidden="true"></span>
			<?php
			echo $auto_output
				? esc_html__( 'Plug & play: On', 'sasim-webp-auto-converter' )
				: esc_html__( 'Plug & play: Off', 'sasim-webp-auto-converter' );
			?>
		</p>
		<?php if ( $converter_ok ) : ?>
			<p class="saswac-status__item">
				<span class="saswac-status__dot saswac-status__dot--ok" aria-hidden="true"></span>
				<?php echo esc_html__( 'New uploads: WebP generated automatically', 'sasim-webp-auto-converter' ); ?>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Render settings page.
 */
function saswac_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$converter_ok = saswac_gd_or_imagick_available();
	?>
	<div class="wrap" id="saswac-settings">
		<h1><?php echo esc_html__( 'WebP Converter', 'sasim-webp-auto-converter' ); ?></h1>

		<?php if ( ! $converter_ok ) : ?>
			<div class="notice notice-warning">
				<p>
					<?php
					echo esc_html__(
						'WebP conversion is unavailable on this server. Enable GD with imagewebp support or Imagick with WebP support, then refresh this page.',
						'sasim-webp-auto-converter'
					);
					?>
				</p>
			</div>
		<?php endif; ?>

		<?php saswac_render_status_strip(); ?>

		<div class="postbox saswac-postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php echo esc_html__( 'Conversion settings', 'sasim-webp-auto-converter' ); ?></h2>
			</div>
			<div class="inside">
				<form method="post" action="options.php">
					<?php
					settings_fields( 'saswac_options' );
					do_settings_sections( 'sasim-webp-auto-converter' );
					submit_button();
					?>
				</form>
			</div>
		</div>

		<div class="postbox saswac-postbox">
			<div class="postbox-header">
				<h2 class="hndle"><?php echo esc_html__( 'Existing media', 'sasim-webp-auto-converter' ); ?></h2>
			</div>
			<div class="inside">
				<p><?php echo esc_html__( 'Generate WebP for images that were uploaded before this plugin was active.', 'sasim-webp-auto-converter' ); ?></p>
				<p>
					<button
						type="button"
						class="button button-primary"
						id="saswac-batch-start"
						<?php disabled( ! $converter_ok ); ?>
					>
						<?php echo esc_html__( 'Generate WebP', 'sasim-webp-auto-converter' ); ?>
					</button>
				</p>
				<div
					id="saswac-batch-progress"
					class="saswac-progress"
					role="progressbar"
					aria-valuemin="0"
					aria-valuemax="100"
					aria-valuenow="0"
					hidden
				>
					<div id="saswac-batch-progress-bar" class="saswac-progress__bar"></div>
				</div>
				<p id="saswac-batch-status" class="saswac-batch-status" aria-live="polite" aria-atomic="true"></p>
			</div>
		</div>

		<details class="postbox saswac-postbox saswac-dev">
			<summary><?php echo esc_html__( 'For developers', 'sasim-webp-auto-converter' ); ?></summary>
			<div class="saswac-dev__body">
				<p>
					<?php
					echo esc_html__(
						'Plug & play covers most themes. For custom templates, use the theme helper functions in your PHP templates.',
						'sasim-webp-auto-converter'
					);
					?>
				</p>
				<p>
					<a href="<?php echo esc_url( saswac_docs_url() ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html__( 'View theme helpers documentation', 'sasim-webp-auto-converter' ); ?>
						<span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'sasim-webp-auto-converter' ); ?></span>
					</a>
				</p>
				<p><?php echo esc_html__( 'Disable auto output in code:', 'sasim-webp-auto-converter' ); ?></p>
				<code class="saswac-dev__code">add_filter( 'saswac_auto_output_enabled', '__return_false' );</code>
			</div>
		</details>
	</div>
	<?php
}

// --- Convert on upload ---
add_filter( 'wp_generate_attachment_metadata', 'saswac_generate_webp', 20, 2 );

/**
 * Generate WebP siblings after WordPress creates attachment metadata.
 *
 * @param array $metadata      Attachment metadata.
 * @param int   $attachment_id Attachment ID.
 * @return array
 */
function saswac_generate_webp( $metadata, $attachment_id ) {
	$file_path = get_attached_file( $attachment_id );
	$mime_type = get_post_mime_type( $attachment_id );

	if ( ! saswac_is_convertible_mime( $mime_type ) ) {
		return $metadata;
	}

	saswac_convert_file( $file_path );

	if ( ! empty( $metadata['sizes'] ) && ! empty( $metadata['file'] ) ) {
		$upload_dir = wp_upload_dir();
		$base_dir   = path_join( $upload_dir['basedir'], dirname( $metadata['file'] ) );
		foreach ( $metadata['sizes'] as $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}
			saswac_convert_file( path_join( $base_dir, $size['file'] ) );
		}
	}

	return $metadata;
}

// --- Prefer WebP in responsive srcset when a sibling .webp exists ---
add_filter( 'wp_calculate_image_srcset', 'saswac_srcset_webp', 10, 5 );

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
function saswac_srcset_webp( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
	unset( $size_array, $image_src, $image_meta, $attachment_id );

	if ( empty( $sources ) || is_admin() ) {
		return $sources;
	}

	foreach ( $sources as $width => $source ) {
		$webp_url = saswac_url_to_webp( $source['url'] );
		if ( $webp_url && saswac_url_file_exists( $webp_url ) ) {
			$sources[ $width ]['url'] = $webp_url;
		}
	}

	return $sources;
}

// --- Cleanup ---
add_action( 'delete_attachment', 'saswac_delete_webp_versions' );

/**
 * Remove WebP siblings when an attachment is deleted.
 *
 * @param int $attachment_id Attachment ID.
 */
function saswac_delete_webp_versions( $attachment_id ): void {
	$file       = get_attached_file( $attachment_id );
	$meta       = wp_get_attachment_metadata( $attachment_id );
	$upload_dir = wp_upload_dir();

	saswac_unlink_webp( $file );

	if ( ! empty( $meta['sizes'] ) && ! empty( $meta['file'] ) ) {
		$base_dir = path_join( $upload_dir['basedir'], dirname( $meta['file'] ) );
		foreach ( $meta['sizes'] as $size ) {
			if ( empty( $size['file'] ) ) {
				continue;
			}
			saswac_unlink_webp( path_join( $base_dir, $size['file'] ) );
		}
	}
}

// --- Batch AJAX ---
add_action( 'wp_ajax_saswac_batch', 'saswac_ajax_batch' );

/**
 * AJAX handler for batch WebP generation.
 */
function saswac_ajax_batch(): void {
	check_ajax_referer( 'saswac_batch' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'Forbidden', 'sasim-webp-auto-converter' ) ), 403 );
	}

	$offset = isset( $_POST['offset'] ) ? absint( $_POST['offset'] ) : 0;

	$query = new WP_Query(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => array( 'image/jpeg', 'image/png' ),
			'posts_per_page' => SASWAC_BATCH,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'fields'         => 'ids',
		)
	);

	$converted = 0;
	foreach ( $query->posts as $attachment_id ) {
		$file = get_attached_file( $attachment_id );
		if ( $file && saswac_convert_file( $file ) ) {
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
				if ( saswac_convert_file( path_join( $base_dir, $size['file'] ) ) ) {
					++$converted;
				}
			}
		}
	}

	$batch_count = count( $query->posts );
	$processed   = $offset + $batch_count;
	$next_offset = $offset + SASWAC_BATCH;
	$done        = $batch_count < SASWAC_BATCH;
	$total       = 0 === $offset ? saswac_count_convertible_attachments() : null;

	$response = array(
		'done'            => $done,
		'next_offset'     => $done ? $offset : $next_offset,
		'processed'       => $processed,
		'converted_batch' => $converted,
		'message'         => $done
			? sprintf(
				/* translators: %d: number of converted files in the last batch */
				__( 'Done. Converted %d file(s) in the last batch.', 'sasim-webp-auto-converter' ),
				$converted
			)
			: sprintf(
				/* translators: 1: batch offset, 2: number of converted files */
				__( 'Processed batch (offset %1$d). Converted %2$d file(s). Continuing…', 'sasim-webp-auto-converter' ),
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
function saswac_is_convertible_mime( ?string $mime ): bool {
	return in_array( $mime, array( 'image/jpeg', 'image/png' ), true );
}

/**
 * Convert a single image file to WebP.
 *
 * @param string $source_path Absolute path to JPEG or PNG.
 */
function saswac_convert_file( string $source_path ): bool {
	if ( ! file_exists( $source_path ) || ! saswac_gd_or_imagick_available() ) {
		return false;
	}

	$extension = strtolower( pathinfo( $source_path, PATHINFO_EXTENSION ) );
	if ( ! in_array( $extension, array( 'jpg', 'jpeg', 'png' ), true ) ) {
		return false;
	}

	$destination = saswac_path_to_webp( $source_path );
	if ( ! $destination ) {
		return false;
	}

	$quality = (int) get_option( SASWAC_OPTION, 82 );

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
function saswac_gd_or_imagick_available(): bool {
	return ( function_exists( 'imagewebp' ) && function_exists( 'imagecreatefromjpeg' ) )
		|| class_exists( 'Imagick' );
}

/**
 * Build WebP path from JPEG/PNG path.
 *
 * @param string $path Source path.
 */
function saswac_path_to_webp( string $path ): string {
	return (string) preg_replace( '/\.(jpe?g|png)$/i', '.webp', $path );
}

/**
 * Build WebP URL from JPEG/PNG URL.
 *
 * @param string $url Source URL.
 */
function saswac_url_to_webp( string $url ): string {
	return (string) preg_replace( '/\.(jpe?g|png)(\?.*)?$/i', '.webp$2', $url );
}

/**
 * Whether a uploads URL maps to an existing WebP file.
 *
 * @param string $url Image URL.
 */
function saswac_url_file_exists( string $url ): bool {
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
function saswac_unlink_webp( string $source_path ): void {
	$webp = saswac_path_to_webp( $source_path );
	if ( $webp && file_exists( $webp ) ) {
		wp_delete_file( $webp );
	}
}

require_once __DIR__ . '/includes/image-helpers.php';
require_once __DIR__ . '/includes/auto-output.php';
