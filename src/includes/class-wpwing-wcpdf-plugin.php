<?php
/**
 * Main plugin bootstrapper: wires up sub-classes and handles document actions.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Plugin' ) ) {

	/**
	 * Bootstrapper: initializes the plugin and wires up the three focused sub-classes.
	 * This class retains the document pipeline (create/view/reset/save) and the HTTP
	 * action router, which are shared across sub-classes via dependency injection.
	 *
	 * @class   WPWing_WcPdf_Plugin
	 * @package WPWing
	 * @since   1.0.0
	 */
	class WPWing_WcPdf_Plugin {

		/**
		 * Settings instance, shared with sub-classes.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		public $settings;

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->initialize();

			add_action( 'init', array( $this, 'init_plugin_actions' ) );

			( new WPWing_WcPdf_Admin( $this, $this->settings ) )->register();
			( new WPWing_WcPdf_Orders_List( $this ) )->register();
			( new WPWing_WcPdf_Wc_Hooks( $this, $this->settings ) )->register();
		}

		/**
		 * Create required upload directories and load settings.
		 *
		 * @since 1.0.0
		 */
		public function initialize() {
			$year = (int) gmdate( 'Y' );

			if ( ! file_exists( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) ) {
				wp_mkdir_p( WPWING_WCPDF_DOCUMENT_SAVE_DIR );
			}

			if ( ! file_exists( WPWING_WCPDF_DOCUMENT_SAVE_DIR . $year ) ) {
				wp_mkdir_p( WPWING_WCPDF_DOCUMENT_SAVE_DIR . $year );
			}

			$this->settings = WPWing_WcPdf_Settings::get_instance();
		}

		/**
		 * Process document action GET requests with nonce and capability verification.
		 *
		 * @since 1.0.0
		 */
		public function init_plugin_actions() {
			$this->maybe_migrate_invoice_number_format();

			// Each entry: GET param key => [ doc type, operation, admin-only, notice key ].
			$document_actions = array(
				'wpwing-create-invoice'       => array( 'invoice', 'create', true, 'invoice_created' ),
				'wpwing-view-invoice'         => array( 'invoice', 'view', false, '' ),
				'wpwing-reset-invoice'        => array( 'invoice', 'reset', true, 'invoice_cancelled' ),
				'wpwing-preview-html-invoice' => array( 'invoice', 'preview_html', true, '' ),
				'wpwing-create-packing'       => array( 'packing', 'create', true, 'packing_created' ),
				'wpwing-view-packing'         => array( 'packing', 'view', false, '' ),
				'wpwing-reset-packing'        => array( 'packing', 'reset', true, 'packing_cancelled' ),
				'wpwing-preview-html-packing' => array( 'packing', 'preview_html', true, '' ),
			);

			$notice = '';

			foreach ( $document_actions as $param => list( $doc_type, $op, $admin_only, $action_notice ) ) {
				if ( ! isset( $_GET[ $param ] ) ) {
					continue;
				}

				$order_id  = intval( $_GET[ $param ] );
				$nonce_key = "wpwing_{$op}_{$doc_type}_{$order_id}";

				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), $nonce_key ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wcpdf' ) );
				}

				if ( ! $this->user_can_manage_order( $order_id, $admin_only ) ) {
					$msg = $admin_only
						? esc_html__( 'You do not have permission to perform this action.', 'wpwing-wcpdf' )
						: esc_html__( 'You do not have permission to view this document.', 'wpwing-wcpdf' );
					wp_die( $msg ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- already escaped via esc_html__() above.
				}

				if ( in_array( $op, array( 'view', 'preview_html' ), true ) ) {
					$this->{$op . '_document'}( $order_id, $doc_type );
					return;
				}

				$this->{$op . '_document'}( $order_id, $doc_type );
				$notice = $action_notice;
				break;
			}

			if ( $notice && is_admin() && isset( $_SERVER['HTTP_REFERER'] ) ) {
				$location = add_query_arg(
					array(
						'wpwing_notice' => $notice,
						'wpwing_nonce'  => wp_create_nonce( 'wpwing_admin_notice' ),
					),
					sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
				);
				wp_safe_redirect( $location );
				exit();
			}
		}

		/**
		 * Whether the current user may manage (create/view/cancel) documents for a given order.
		 * Admins always pass. Customers pass only for view actions on their own orders.
		 *
		 * @param int  $order_id   WooCommerce order ID.
		 * @param bool $admin_only True for write actions (create/cancel); false allows order owner too.
		 * @return bool
		 */
		private function user_can_manage_order( $order_id, $admin_only = false ) {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				return true;
			}
			if ( $admin_only ) {
				return false;
			}
			$order = wc_get_order( $order_id );
			return $order instanceof WC_Order
				&& is_user_logged_in()
				&& get_current_user_id() === (int) $order->get_customer_id();
		}

		/**
		 * Return a new document of the requested type for a specific order.
		 *
		 * @param int    $order_id      The order ID.
		 * @param string $document_type 'invoice' or 'packing'.
		 * @return WPWing_WcPdf_Document|null
		 * @since 1.0.0
		 */
		public function get_document_by_type( $order_id, $document_type = '' ) {
			switch ( $document_type ) {
				case 'invoice':
					return new WPWing_WcPdf_Invoice( $order_id );
				case 'packing':
					return new WPWing_WcPdf_Packing( $order_id );
				default:
					return null;
			}
		}

		/**
		 * Create a new document, skipping free orders when the setting is enabled.
		 *
		 * @param int    $order_id      The order ID.
		 * @param string $document_type The document type to generate.
		 * @since 1.0.0
		 */
		public function create_document( $order_id, $document_type = '' ) {
			if ( 'invoice' === $document_type && $this->settings->get_option( 'invoice_disable_free_orders' ) ) {
				$order = wc_get_order( $order_id );
				if ( $order && (float) $order->get_total() === 0.0 ) {
					return;
				}
			}

			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null !== $document ) {
				$this->save_document( $document );
			}
		}

		/**
		 * Stream the PDF to the browser for viewing or download.
		 *
		 * @param int    $order_id      The order ID.
		 * @param string $document_type The document type.
		 * @since 1.0.0
		 */
		public function view_document( $order_id, $document_type ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null !== $document ) {
				$full_path       = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $document->save_path;
				$behavior_key    = $document_type . '_button_behavior';
				$button_behavior = $this->settings->get_option( $behavior_key ) ?: $this->settings->get_option( 'invoice_button_behavior' );
				$real            = realpath( $full_path );
				$base            = realpath( WPWING_WCPDF_DOCUMENT_SAVE_DIR );

				if ( ! $real || ! $base || strpos( $real, $base . DIRECTORY_SEPARATOR ) !== 0 ) {
					wp_die( esc_html__( 'Invoice file not found. Please regenerate the invoice.', 'wpwing-wcpdf' ) );
				}

				if ( 'open' === $button_behavior ) {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: inline; filename="' . sanitize_file_name( basename( $full_path ) ) . '"' );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $full_path ) );
					header( 'Accept-Ranges: bytes' );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
					readfile( $full_path );
					exit();
				} else {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( basename( $full_path ) ) . '"' );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
					readfile( $full_path );
					exit();
				}
			}
		}

		/**
		 * Render the document template as raw HTML and stream it to the browser.
		 * Admin-only. Useful for inspecting template layout without generating a PDF.
		 *
		 * @param int    $order_id      The order ID.
		 * @param string $document_type The document type.
		 * @since 1.0.0
		 */
		public function preview_html_document( $order_id, $document_type ) {
			$document = $this->get_document_by_type( $order_id, $document_type );
			if ( null === $document ) {
				wp_die( esc_html__( 'Invalid document type.', 'wpwing-wcpdf' ) );
			}

			$document->exists = true;

			global $wpwing_wcpdf_document;
			$wpwing_wcpdf_document = $document;

			$theme_dir = $document->get_theme_dir();

			try {
				$document->init_template();
				$document->init_template_generation_actions();
				ob_start();
				wc_get_template( 'template.php', null, $theme_dir, $theme_dir );
				$html = ob_get_clean();
			} finally {
				$document->flush_template();
				$wpwing_wcpdf_document = null;
			}

			header( 'Content-Type: text/html; charset=utf-8' );
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $html;
			exit();
		}

		/**
		 * Delete a generated document for a specific order.
		 *
		 * @param int    $order_id      The order ID.
		 * @param string $document_type The document type.
		 * @since 1.0.0
		 */
		public function reset_document( $order_id, $document_type ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null !== $document ) {
				$document->reset();
			}
		}

		/**
		 * Set the global document context and persist the PDF to disk.
		 *
		 * @param WPWing_WcPdf_Document $document The document object to save.
		 * @since 1.0.0
		 */
		public function save_document( $document ) {
			global $wpwing_wcpdf_document;
			$wpwing_wcpdf_document = $document;
			$wpwing_wcpdf_document->save();
		}

		/**
		 * One-time migration: convert old [prefix]/[number]/[suffix] format to new {number}/{year}/{month} tokens.
		 */
		private function maybe_migrate_invoice_number_format() {
			if ( get_option( 'wpwing_wcpdf_format_migrated_v2' ) ) {
				return;
			}

			$settings = $this->settings;
			$format   = $settings->get_option( 'invoice_number_format' );

			if ( $format && ( strpos( $format, '[prefix]' ) !== false || strpos( $format, '[suffix]' ) !== false || strpos( $format, '[number]' ) !== false ) ) {
				$raw_prefix = $settings->get_option( 'invoice_prefix' );
				$prefix     = $raw_prefix ? $raw_prefix : '';
				$raw_suffix = $settings->get_option( 'invoice_suffix' );
				$suffix     = $raw_suffix ? $raw_suffix : '';

				$new_format = str_replace(
					array( '[prefix]', '[suffix]', '[number]' ),
					array( $prefix, $suffix, '{number}' ),
					$format
				);

				// Remove separators left behind by an empty prefix or suffix.
				$new_format = preg_replace( '/^[\/\-_]+|[\/\-_]+$/', '', $new_format );
				$new_format = preg_replace( '/([\/\-_])\1+/', '$1', $new_format );

				$settings->set_option( 'invoice_number_format', trim( $new_format ) );
			} elseif ( ! $format ) {
				$settings->set_option( 'invoice_number_format', '{number}' );
			}

			update_option( 'wpwing_wcpdf_format_migrated_v2', true );
		}
	}
}
