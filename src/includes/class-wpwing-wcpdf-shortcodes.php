<?php
/**
 * Frontend shortcodes.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Shortcodes' ) ) {

	/**
	 * Registers the [wpwing_invoice] shortcode: a context-aware invoice download
	 * link that resolves the order from the Thank You page or a My Account order
	 * detail page, and renders nothing when no order context is found.
	 *
	 * @since 1.11.0
	 */
	class WPWing_WcPdf_Shortcodes {

		/**
		 * Plugin instance.
		 *
		 * @var WPWing_WcPdf_Plugin
		 */
		private $plugin;

		/**
		 * Constructor.
		 *
		 * @param WPWing_WcPdf_Plugin $plugin Plugin instance.
		 */
		public function __construct( WPWing_WcPdf_Plugin $plugin ) {
			$this->plugin = $plugin;
		}

		/**
		 * Register shortcodes.
		 */
		public function register() {
			add_shortcode( 'wpwing_invoice', array( $this, 'render_invoice_link' ) );
		}

		/**
		 * Render the invoice download link.
		 *
		 * Attributes (all optional):
		 * - order_id: explicit order ID, overrides context detection.
		 * - label:    link text, defaults to "Download Invoice".
		 *
		 * Renders nothing when the order cannot be resolved, the invoice does not
		 * exist yet, or the current user is not allowed to view it.
		 *
		 * @param array|string $atts Shortcode attributes.
		 * @return string
		 */
		public function render_invoice_link( $atts ) {
			$atts = shortcode_atts(
				array(
					'order_id' => 0,
					'label'    => __( 'Download Invoice', 'wpwing-wcpdf' ),
				),
				$atts,
				'wpwing_invoice'
			);

			$order_id = $this->resolve_order_id( absint( $atts['order_id'] ) );
			if ( ! $order_id ) {
				return '';
			}

			$order = wc_get_order( $order_id );
			if ( ! ( $order instanceof WC_Order ) || ! $this->current_user_can_view( $order ) ) {
				return '';
			}

			$invoice = $this->plugin->get_document_by_type( $order_id, 'invoice' );
			if ( null === $invoice || ! $invoice->exists ) {
				return '';
			}

			$url = wp_nonce_url(
				add_query_arg( 'wpwing-view-invoice', $order_id ),
				'wpwing_view_invoice_' . $order_id
			);

			return sprintf(
				'<a class="wpwing-wcpdf-invoice-link" href="%s">%s</a>',
				esc_url( $url ),
				esc_html( $atts['label'] )
			);
		}

		/**
		 * Resolve the order ID from an explicit attribute or the current page context.
		 *
		 * @param int $explicit_id Order ID passed as a shortcode attribute (0 if none).
		 * @return int Order ID, or 0 when no context is found.
		 */
		private function resolve_order_id( $explicit_id ) {
			if ( $explicit_id > 0 ) {
				return $explicit_id;
			}

			global $wp;
			if ( ! isset( $wp->query_vars ) ) {
				return 0;
			}

			// Thank You page.
			if ( ! empty( $wp->query_vars['order-received'] ) ) {
				return absint( $wp->query_vars['order-received'] );
			}

			// My Account order detail page.
			if ( ! empty( $wp->query_vars['view-order'] ) ) {
				return absint( $wp->query_vars['view-order'] );
			}

			return 0;
		}

		/**
		 * Whether the current user may view documents for the given order.
		 * Mirrors the access rules of the document view endpoint.
		 *
		 * @param WC_Order $order Order object.
		 * @return bool
		 */
		private function current_user_can_view( $order ) {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				return true;
			}
			return is_user_logged_in() && get_current_user_id() === (int) $order->get_customer_id();
		}
	}
}
