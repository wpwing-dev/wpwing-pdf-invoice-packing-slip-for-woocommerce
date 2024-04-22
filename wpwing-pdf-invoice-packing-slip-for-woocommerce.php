<?php

/**
 * Plugin Name:           PDF Invoice and Packing Slip for WooCommerce
 * Plugin URI:            https://wpwing.com/
 * Description:           Download your WooCommerce order invoice and packing slip as PDF format for print or email.
 * Version:               1.4.3
 * Author:                WPWing
 * Author URI:            https://wpwing.com/
 * Requires PHP:          7.1
 * Requires at least:     4.8
 * Tested up to:          6.5
 * WC requires at least:  4.5
 * WC tested up to:       8.8
 * License:               GPL-3.0-or-later
 * License URI:           https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:           wpwing-wc-pdf-invoice
 */

defined( 'ABSPATH' ) || exit;

$wp_upload_dir = wp_upload_dir();

// Define constants
defined( 'WPWING_WCPI_DOCUMENT_SAVE_DIR' ) || define( 'WPWING_WCPI_DOCUMENT_SAVE_DIR', $wp_upload_dir['basedir'] . '/wpwing-pdf-invoices/' );

defined( 'WPWING_WCPI_VERSION' ) || define( 'WPWING_WCPI_VERSION', '1.3.4' );

defined( 'WPWING_WCPI_FILE' ) || define( 'WPWING_WCPI_FILE', __FILE__ );

defined( 'WPWING_WCPI_DIR' ) || define( 'WPWING_WCPI_DIR', plugin_dir_path( __FILE__ ) );

defined( 'WPWING_WCPI_DIR_NAME' ) || define( 'WPWING_WCPI_DIR_NAME', dirname( plugin_basename( __FILE__ ) ) );

defined( 'WPWING_WCPI_BASE_NAME' ) || define( 'WPWING_WCPI_BASE_NAME', plugin_basename( __FILE__ ) );

defined( 'WPWING_WCPI_URL' ) || define( 'WPWING_WCPI_URL', plugins_url( '/', __FILE__ ) );

defined( 'WPWING_WCPI_ASSETS_URL' ) || define( 'WPWING_WCPI_ASSETS_URL', WPWING_WCPI_URL . 'assets' );

defined( 'WPWING_WCPI_TEMPLATE_DIR' ) || define( 'WPWING_WCPI_TEMPLATE_DIR', WPWING_WCPI_DIR . 'templates/' );

defined( 'WPWING_WCPI_INC_DIR' ) || define( 'WPWING_WCPI_INC_DIR', WPWING_WCPI_DIR . 'includes/' );

defined( 'WPWING_WCPI_VENDOR_DIR' ) || define( 'WPWING_WCPI_VENDOR_DIR', WPWING_WCPI_DIR . 'vendor/' );

/**
 * Show notification if WooCommerce is not installed
 *
 * @since 1.0.0
 */
function wpwing_wcpi_wc_error_admin_notice() {
	echo '<div class="error notice">';
	echo '<p>';
	_e( '<strong>Error:</strong>', 'wpwing-wc-pdf-invoice' );
	_e( 'The <em>PDF Invoice and Packing Slip for WooCommerce</em> plugin won\'t execute because the following required plugin is not active: <em>WooCommerce</em>. <br>Please activate this <a href="plugins.php">plugin</a> first.', 'wpwing-wc-pdf-invoice' );
	echo '</p>';
	echo '</div>';
	echo '<div class="updated notice is-dismissible"><p>' . __( 'The <em>WPWing PDF Invoice and Packing Slip for WooCommerce</em> plugin deactivated.', 'wpwing-wc-pdf-invoice' ) . '</p></div>';
}

/**
 * Create files/directories to protect upload folders
 *
 * @since 1.0.0
 */
if (  ! function_exists( 'wpwing_wcpi_protect_folder' ) ) {
	function wpwing_wcpi_protect_folder() {
		$files = [
			[
				'base'    => WPWING_WCPI_DOCUMENT_SAVE_DIR,
				'file'    => 'index.html',
				'content' => '',
			],
			[
				'base'    => WPWING_WCPI_DOCUMENT_SAVE_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			],
		];

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}

		// Updating the option not to execute the function 'wpwing_wcpi_protect_folder' again
		update_option( 'wpwing_wcpi_check_folder_already_protected', true );
	}
}

/**
 * Load all the resources and init PDF Invoice class
 *
 * @since 1.0.0
 */
function wpwing_wcpi_init() {
	load_plugin_textdomain( 'wpwing-wc-pdf-invoice', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	require_once WPWING_WCPI_INC_DIR . 'class.wpwing-wc-pdf-invoice.php';
	require_once WPWING_WCPI_INC_DIR . 'class.wcpi-document.php';
	require_once WPWING_WCPI_INC_DIR . 'class.wcpi-invoice.php';
	require_once WPWING_WCPI_INC_DIR . 'class.wcpi-packing.php';
	require_once WPWING_WCPI_INC_DIR . 'class-wpwing-wcpi-settings.php';

	global $WPWing_WCPI_Instance;
	$WPWing_WCPI_Instance = new WPWing_WC_Pdf_Invoice();
}

add_action( 'wpwing_wcpi_init', 'wpwing_wcpi_init' );

/**
 * Kick-start the plugin
 *
 * @since 1.0.0
 */
function wpwing_wcpi_install() {
	if (  ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'wpwing_wcpi_wc_error_admin_notice' );

		// Call A Hook for Deactivate our plugin
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( __FILE__ ) );

		return;
	} else {
		do_action( 'wpwing_wcpi_init' );
	}

	if (  ! get_option( 'wpwing_wcpi_check_folder_already_protected' ) ) {
		wpwing_wcpi_protect_folder();
	}
}

add_action( 'plugins_loaded', 'wpwing_wcpi_install', 11 );

/**
 * For test and debug, log function to view any data in wp-content/debug.log
 * uses: log_it($variable);
 *
 * @since 1.0.0
 */

if (  ! function_exists( 'log_it' ) ) {
	function log_it( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				error_log( "\r\n" . print_r( $message, true ) );
			} else {
				error_log( $message );
			}
		}
	}
}