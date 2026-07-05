<?php
/**
 * Plugin Name:           PDF Invoice and Packing Slip for WooCommerce
 * Plugin URI:            https://wpwing.com/
 * Description:           Automatically generate, print, and attach professional PDF invoices and packing slips to WooCommerce emails. Clean, lightweight, and fast.
 * Version: 1.10.0
 * Author:                WPWing
 * Author URI:            https://wpwing.com/
 * Requires PHP:          7.4
 * Requires at least:     4.8
 * Tested up to:          7.0
 * WC requires at least:  4.5
 * WC tested up to:       10.8.1
 * WC HPOS Compatible:    Yes
 * License:               GPL-3.0-or-later
 * License URI:           https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:           wpwing-wcpdf
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

$wp_upload_dir = wp_upload_dir();

// Define constants.
defined( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR' ) || define( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR', $wp_upload_dir['basedir'] . '/wpwing-pdf-invoices/' );

defined( 'WPWING_WCPDF_VERSION' ) || define( 'WPWING_WCPDF_VERSION', '1.10.0' );

defined( 'WPWING_WCPDF_FILE' ) || define( 'WPWING_WCPDF_FILE', __FILE__ );

defined( 'WPWING_WCPDF_DIR' ) || define( 'WPWING_WCPDF_DIR', plugin_dir_path( __FILE__ ) );

defined( 'WPWING_WCPDF_DIR_NAME' ) || define( 'WPWING_WCPDF_DIR_NAME', dirname( plugin_basename( __FILE__ ) ) );

defined( 'WPWING_WCPDF_BASE_NAME' ) || define( 'WPWING_WCPDF_BASE_NAME', plugin_basename( __FILE__ ) );

defined( 'WPWING_WCPDF_URL' ) || define( 'WPWING_WCPDF_URL', plugins_url( '/', __FILE__ ) );

defined( 'WPWING_WCPDF_ASSETS_URL' ) || define( 'WPWING_WCPDF_ASSETS_URL', WPWING_WCPDF_URL . 'assets' );

defined( 'WPWING_WCPDF_TEMPLATE_DIR' ) || define( 'WPWING_WCPDF_TEMPLATE_DIR', WPWING_WCPDF_DIR . 'templates/' );

defined( 'WPWING_WCPDF_INC_DIR' ) || define( 'WPWING_WCPDF_INC_DIR', WPWING_WCPDF_DIR . 'includes/' );

defined( 'WPWING_WCPDF_VENDOR_DIR' ) || define( 'WPWING_WCPDF_VENDOR_DIR', WPWING_WCPDF_DIR . 'vendor/' );

/** Implement HPOS compatibility */
add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true // True if compatible, false otherwise.
			);
		}
	}
);

/**
 * Show notification if WooCommerce is not installed
 *
 * @since 1.0.0
 */
function wpwing_wcpdf_wc_error_admin_notice() {
	echo '<div class="error notice">';
	echo '<p>';
	echo '<strong>' . esc_html__( 'Error:', 'wpwing-wcpdf' ) . '</strong>';
	echo wp_kses_post( __( 'The <em>PDF Invoice and Packing Slip for WooCommerce</em> plugin won\'t execute because the following required plugin is not active: <em>WooCommerce</em>. <br>Please activate this <a href="plugins.php">plugin</a> first.', 'wpwing-wcpdf' ) );
	echo '</p>';
	echo '</div>';
	echo '<div class="updated notice is-dismissible"><p>' . wp_kses_post( __( 'The <em>WPWing PDF Invoice and Packing Slip for WooCommerce</em> plugin deactivated.', 'wpwing-wcpdf' ) ) . '</p></div>';
}

if ( ! function_exists( 'wpwing_wcpdf_protect_folder' ) ) {
	/**
	 * Create files/directories to protect upload folders.
	 *
	 * @since 1.0.0
	 */
	function wpwing_wcpdf_protect_folder() {
		$files = array(
			array(
				'base'    => WPWING_WCPDF_DOCUMENT_SAVE_DIR,
				'file'    => 'index.html',
				'content' => '',
			),
			array(
				'base'    => WPWING_WCPDF_DOCUMENT_SAVE_DIR,
				'file'    => '.htaccess',
				'content' => 'deny from all',
			),
		);

		foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen
				$file_handle = fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' );
				if ( false !== $file_handle ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite
					fwrite( $file_handle, $file['content'] );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
					fclose( $file_handle );
				}
			}
		}

		// Updating the option not to execute the function 'wpwing_wcpdf_protect_folder' again.
		update_option( 'wpwing_wcpdf_check_folder_already_protected', true );
	}
}

/**
 * Load all the resources and init PDF Invoice class
 *
 * @since 1.0.0
 */
function wpwing_wcpdf_init() {
	load_plugin_textdomain( 'wpwing-wcpdf', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-migration.php';
	WPWing_WcPdf_Migration::maybe_run();

	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-document.php';
	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-invoice.php';
	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-packing.php';
	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-settings.php';
	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-admin.php';
	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-orders-list.php';
	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-wc-hooks.php';
	require_once WPWING_WCPDF_INC_DIR . 'class-wpwing-wcpdf-plugin.php';

	global $wpwing_wcpdf;
	$wpwing_wcpdf = new WPWing_WcPdf_Plugin();
}

add_action( 'wpwing_wcpdf_init', 'wpwing_wcpdf_init' );

/**
 * Kick-start the plugin
 *
 * @since 1.0.0
 */
function wpwing_wcpdf_install() {
	if ( ! function_exists( 'WC' ) ) {
		add_action( 'admin_notices', 'wpwing_wcpdf_wc_error_admin_notice' );

		// Call a hook to deactivate our plugin.
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( plugin_basename( __FILE__ ) );

		return;
	} else {
		do_action( 'wpwing_wcpdf_init' );
	}

	if ( ! get_option( 'wpwing_wcpdf_check_folder_already_protected' ) ) {
		wpwing_wcpdf_protect_folder();
	}
}

add_action( 'plugins_loaded', 'wpwing_wcpdf_install', 11 );

if ( ! function_exists( 'log_it' ) ) {
	/**
	 * For test and debug, log function to view any data in wp-content/debug.log.
	 *
	 * @since 1.0.0
	 * @param mixed $message Value to log.
	 */
	function log_it( $message ) {
		if ( WP_DEBUG === true ) {
			if ( is_array( $message ) || is_object( $message ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.PHP.DevelopmentFunctions.error_log_print_r
				error_log( "\r\n" . print_r( $message, true ) );
			} else {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( $message );
			}
		}
	}
}
