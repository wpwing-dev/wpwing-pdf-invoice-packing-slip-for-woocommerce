<?php
/**
 * Pro plugin controller class.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Pro' ) ) {

	/**
	 * Pro plugin controller.
	 *
	 * Wires up:
	 *  - Document access control
	 *  - Proforma decorator class & auto-conversion on payment
	 *  - Auto credit note on WooCommerce refund
	 */
	class WPWing_WcPdf_Pro {

		/**
		 * Constructor.
		 */
		public function __construct() {
			// Access control runs before free plugin's init_plugin_actions (priority 10).
			add_action( 'init', array( $this, 'check_document_access' ), 5 );

			// Hook the Pro proforma subclass decorator.
			add_filter( 'wpwing_wcpdf_proforma_class', array( $this, 'get_proforma_class' ) );

			// Auto-convert proforma to invoice on payment.
			add_action( 'woocommerce_payment_complete', array( $this, 'maybe_convert_proforma_on_payment' ) );
			add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_convert_proforma_on_payment' ) );
			add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_convert_proforma_on_payment' ) );

			// Auto-generate credit note when a refund is issued.
			add_action( 'woocommerce_order_refunded', array( $this, 'handle_order_refunded' ), 10, 2 );

			// Admin notices for pro actions.
			add_action( 'admin_notices', array( $this, 'show_pro_admin_notices' ) );
		}

		/**
		 * Return the Pro proforma subclass name.
		 *
		 * @return string
		 */
		public function get_proforma_class() {
			return 'WPWing_WcPdf_Pro_Proforma';
		}

		/**
		 * Auto-convert proforma to invoice when payment is confirmed.
		 *
		 * @param int $order_id Order ID.
		 */
		public function maybe_convert_proforma_on_payment( $order_id ) {
			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return;
			}

			$has_proforma = (bool) $order->get_meta( '_wpwing_wcpdf_proforma' );
			$has_invoice  = (bool) $order->get_meta( '_wpwing_wcpdf_invoiced' );

			if ( $has_proforma && ! $has_invoice ) {
				$invoice = new WPWing_WcPdf_Invoice( $order_id );
				if ( $invoice->is_valid && ! $invoice->exists ) {
					$invoice->save();
				}
			}
		}

		// -------------------------------------------------------------------------
		// Access control
		// -------------------------------------------------------------------------

		/**
		 * Validate the current user may view the requested document.
		 * Fires at init priority 5, before the free plugin's action handler at priority 10.
		 */
		public function check_document_access() {
			$protected = array( 'wpwing-view-invoice', 'wpwing-view-packing', 'wpwing-view-proforma' );

			foreach ( $protected as $param ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only access gate; no data is mutated.
				if ( ! isset( $_GET[ $param ] ) ) {
					continue;
				}

				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only access gate; no data is mutated.
				$order_id = intval( $_GET[ $param ] );
				if ( $this->user_can_view_order( $order_id ) ) {
					continue;
				}

				wp_die(
					esc_html__( 'You do not have permission to view this document.', 'wpwing-pdf-invoice-pro' ),
					esc_html__( 'Access Denied', 'wpwing-pdf-invoice-pro' ),
					array( 'response' => 403 )
				);
			}
		}

		/**
		 * Return true if the current user is an admin or is the order's customer.
		 *
		 * @param int $order_id Order ID.
		 * @return bool
		 */
		private function user_can_view_order( $order_id ) {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				return true;
			}

			if ( ! is_user_logged_in() ) {
				return false;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return false;
			}

			return get_current_user_id() === (int) $order->get_customer_id();
		}

		// -------------------------------------------------------------------------
		// Credit notes
		// -------------------------------------------------------------------------

		/**
		 * Auto-generate a credit note PDF when WooCommerce processes a refund.
		 *
		 * @param int $order_id  Original order ID.
		 * @param int $refund_id WooCommerce refund ID.
		 */
		public function handle_order_refunded( $order_id, $refund_id ) {
			$creditnote = new WPWing_WcPdf_CreditNote( $order_id, $refund_id );
			if ( $creditnote->is_valid && ! $creditnote->exists ) {
				global $wpwing_wcpdf_document;
				$wpwing_wcpdf_document = $creditnote;
				$wpwing_wcpdf_document->save();
			}
		}

		// -------------------------------------------------------------------------
		// Admin notices
		// -------------------------------------------------------------------------

		/**
		 * Show admin notices set by pro document actions.
		 */
		public function show_pro_admin_notices() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['wpwing_pro_notice'] ) ) {
				return;
			}

			$notice = sanitize_key( $_GET['wpwing_pro_notice'] );

			$messages = array(
				'proforma_created'   => array( 'success', __( 'Proforma invoice created successfully.', 'wpwing-pdf-invoice-pro' ) ),
				'proforma_cancelled' => array( 'warning', __( 'Proforma invoice has been cancelled.', 'wpwing-pdf-invoice-pro' ) ),
			);

			if ( isset( $messages[ $notice ] ) ) {
				list( $type, $message ) = $messages[ $notice ];
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $type ),
					esc_html( $message )
				);
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}
	}
}
