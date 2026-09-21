<?php
require_once __DIR__ . '/../src/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/' );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'WPWING_WCPDF_DIR' ) ) {
	define( 'WPWING_WCPDF_DIR', __DIR__ . '/../src/' );
}
if ( ! defined( 'WPWING_WCPDF_VERSION' ) ) {
	define( 'WPWING_WCPDF_VERSION', '0.0.0-test' );
}
if ( ! defined( 'WPWING_WCPDF_DIR_NAME' ) ) {
	define( 'WPWING_WCPDF_DIR_NAME', 'wpwing-pdf-invoice-packing-slip-for-woocommerce' );
}
if ( ! defined( 'WPWING_WCPDF_BASE_NAME' ) ) {
	define( 'WPWING_WCPDF_BASE_NAME', 'wpwing-pdf-invoice-packing-slip-for-woocommerce/wpwing-pdf-invoice-packing-slip-for-woocommerce.php' );
}
if ( ! defined( 'WPWING_WCPDF_FILE' ) ) {
	define( 'WPWING_WCPDF_FILE', WPWING_WCPDF_DIR . 'wpwing-pdf-invoice-packing-slip-for-woocommerce.php' );
}
if ( ! defined( 'WPWING_WCPDF_URL' ) ) {
	define( 'WPWING_WCPDF_URL', 'http://example.test/wp-content/plugins/wpwing-pdf-invoice-packing-slip-for-woocommerce/' );
}
if ( ! defined( 'WPWING_WCPDF_ASSETS_URL' ) ) {
	define( 'WPWING_WCPDF_ASSETS_URL', WPWING_WCPDF_URL . 'assets' );
}
if ( ! defined( 'WPWING_WCPDF_TEMPLATE_DIR' ) ) {
	define( 'WPWING_WCPDF_TEMPLATE_DIR', WPWING_WCPDF_DIR . 'templates/' );
}
if ( ! defined( 'WPWING_WCPDF_INC_DIR' ) ) {
	define( 'WPWING_WCPDF_INC_DIR', WPWING_WCPDF_DIR . 'includes/' );
}
if ( ! defined( 'WPWING_WCPDF_VENDOR_DIR' ) ) {
	define( 'WPWING_WCPDF_VENDOR_DIR', WPWING_WCPDF_DIR . 'vendor/' );
}
if ( ! defined( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR' ) ) {
	define( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR', sys_get_temp_dir() . '/wpwing-pdf-invoices/' );
}

// Minimal stubs for WP string-helper functions used during class construction.
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

if ( ! function_exists( 'wc_doing_it_wrong' ) ) {
	function wc_doing_it_wrong( $function, $message, $version ) {}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( strip_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	function esc_url_raw( $url ) {
		return trim( (string) $url );
	}
}

if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( (string) $string, '/\\' ) . '/';
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $target ) {
		return is_dir( $target ) || mkdir( $target, 0777, true );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return false;
	}
}



if ( ! class_exists( 'WC_Product' ) ) {
	class WC_Product {
		public function get_id() { return 0; }
		public function get_image_id() { return 0; }
		public function get_parent_id() { return 0; }
		public function get_sku() { return ''; }
		public function get_weight() { return ''; }
	}
}

if ( ! class_exists( 'WC_Order' ) ) {
	class WC_Order {
		public function get_id() { return 0; }
		public function get_total() { return 0.0; }
		public function get_items() { return array(); }
	}
}

