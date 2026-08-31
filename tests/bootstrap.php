<?php
require_once __DIR__ . '/../src/vendor/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php';

define( 'ABSPATH', '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'WPWING_WCPDF_DIR', __DIR__ . '/../src/' );
define( 'WPWING_WCPDF_VERSION', '0.0.0-test' );
define( 'WPWING_WCPDF_DIR_NAME', 'wpwing-pdf-invoice-packing-slip-for-woocommerce' );
define( 'WPWING_WCPDF_BASE_NAME', 'wpwing-pdf-invoice-packing-slip-for-woocommerce/wpwing-pdf-invoice-packing-slip-for-woocommerce.php' );
define( 'WPWING_WCPDF_FILE', WPWING_WCPDF_DIR . 'wpwing-pdf-invoice-packing-slip-for-woocommerce.php' );
define( 'WPWING_WCPDF_URL', 'http://example.test/wp-content/plugins/wpwing-pdf-invoice-packing-slip-for-woocommerce/' );
define( 'WPWING_WCPDF_ASSETS_URL', WPWING_WCPDF_URL . 'assets' );
define( 'WPWING_WCPDF_TEMPLATE_DIR', WPWING_WCPDF_DIR . 'templates/' );
define( 'WPWING_WCPDF_INC_DIR', WPWING_WCPDF_DIR . 'includes/' );
define( 'WPWING_WCPDF_VENDOR_DIR', WPWING_WCPDF_DIR . 'vendor/' );
define( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR', sys_get_temp_dir() . '/wpwing-pdf-invoices/' );

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
