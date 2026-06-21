<?php
/**
 * Uninstall WebP Auto Converter.
 *
 * @package WebP_Auto_Converter
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'webp_auto_converter_quality' );
