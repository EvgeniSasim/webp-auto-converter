<?php
/**
 * Uninstall WebP Auto Converter.
 *
 * @package Sasim_WebP_Auto_Converter
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'saswac_quality' );
delete_option( 'saswac_auto_output' );
