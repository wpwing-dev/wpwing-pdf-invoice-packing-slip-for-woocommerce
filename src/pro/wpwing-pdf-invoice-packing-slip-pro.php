<?php

/**
 * Plugin Name:       PDF Invoice and Packing Slip for WooCommerce — Pro
 * Plugin URI:        https://wpwing.com/
 * Description:       Pro add-on: access control, proforma invoices, and credit notes. Requires the free WPWing PDF Invoice plugin.
 * Version:           1.0.0
 * Author:            WPWing
 * Author URI:        https://wpwing.com/
 * Requires PHP:      7.1
 * Requires at least: 4.8
 * Tested up to:      6.8
 * WC requires at least: 4.5
 * WC tested up to:   10.1.1
 * WC HPOS Compatible: Yes
 * Requires Plugins:  wpwing-pdf-invoice-packing-slip-for-woocommerce
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wpwing-pdf-invoice-pro
 */

defined( 'ABSPATH' ) || exit;

define( 'WPWING_WCPDF_PRO_VERSION', '1.0.0' );
define( 'WPWING_WCPDF_PRO_FILE', __FILE__ );
define( 'WPWING_WCPDF_PRO_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPWING_WCPDF_PRO_TEMPLATE_DIR', WPWING_WCPDF_PRO_DIR . 'templates/' );

/** Declare HPOS compatibility */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Boot the pro plugin after the free plugin has loaded.
 */
add_action( 'plugins_loaded', 'wpwing_wcpdf_pro_init', 12 );

function wpwing_wcpdf_pro_init() {
	if ( ! class_exists( 'WPWing_WcPdf_Document' ) ) {
		add_action( 'admin_notices', 'wpwing_wcpdf_pro_dependency_notice' );
		return;
	}

	require_once WPWING_WCPDF_PRO_DIR . 'includes/class-wcpdf-pro-document.php';
	require_once WPWING_WCPDF_PRO_DIR . 'includes/class-wcpdf-proforma.php';
	require_once WPWING_WCPDF_PRO_DIR . 'includes/class-wcpdf-creditnote.php';
	require_once WPWING_WCPDF_PRO_DIR . 'includes/class-wpwing-wcpdf-pro.php';

	new WPWing_WcPdf_Pro();
}

function wpwing_wcpdf_pro_dependency_notice() {
	echo '<div class="notice notice-error"><p>' .
		esc_html__( 'WPWing PDF Invoice Pro requires the free WPWing PDF Invoice & Packing Slip plugin to be installed and active.', 'wpwing-pdf-invoice-pro' ) .
		'</p></div>';
}
