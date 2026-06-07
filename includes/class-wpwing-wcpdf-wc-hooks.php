<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Wc_Hooks' ) ) {

	/**
	 * Handles all WooCommerce integration hooks:
	 * auto-generation on status change, email attachment, and My Account link.
	 *
	 * @since 1.8.0
	 */
	class WPWing_WcPdf_Wc_Hooks {

		private $plugin;
		private $settings;

		public function __construct( WPWing_WcPdf_Plugin $plugin, WPWing_WcPdf_Settings $settings ) {
			$this->plugin   = $plugin;
			$this->settings = $settings;
		}

		public function register() {
			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'my_account_order_actions' ), 10, 2 );
			add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_auto_generate' ), 10, 4 );
			add_filter( 'woocommerce_email_attachments', array( $this, 'attach_document_to_email' ), 10, 3 );
		}

		/**
		 * Add invoice download link to My Account > Orders.
		 *
		 * @param array    $actions Existing actions.
		 * @param WC_Order $order   Current order.
		 * @return array
		 */
		public function my_account_order_actions( $actions, $order ) {
			$invoice = $this->plugin->get_document_by_type( $order->get_id(), 'invoice' );

			if ( ( null !== $invoice ) && $invoice->exists ) {
				$nonce_url = wp_nonce_url(
					add_query_arg( 'wpwing-view-invoice', $order->get_id() ),
					'wpwing_view_invoice_' . $order->get_id()
				);

				$actions['wpwing_invoice'] = array(
					'url'  => $nonce_url,
					'name' => __( 'Invoice', 'wpwing-wcpdf' ),
				);
			}

			return $actions;
		}

		/**
		 * Auto-generate invoice or packing slip when an order status matches configured triggers.
		 *
		 * @param int      $order_id   Order ID.
		 * @param string   $old_status Previous status slug (without wc- prefix).
		 * @param string   $new_status New status slug (without wc- prefix).
		 * @param WC_Order $order      Order object.
		 */
		public function maybe_auto_generate( $order_id, $old_status, $new_status, $order ) {
			static $running = false;
			if ( $running ) {
				return;
			}
			$running = true;

			$invoice_statuses = $this->settings->get_option( 'invoice_auto_statuses' );
			if ( is_array( $invoice_statuses ) && in_array( $new_status, $invoice_statuses, true ) ) {
				$skip = $this->settings->get_option( 'invoice_disable_free_orders' ) && (float) $order->get_total() === 0.0;
				if ( ! $skip ) {
					$invoice = $this->plugin->get_document_by_type( $order_id, 'invoice' );
					if ( null !== $invoice && ! $invoice->exists ) {
						$this->plugin->save_document( $invoice );
					}
				}
			}

			$packing_statuses = $this->settings->get_option( 'packing_auto_statuses' );
			if ( is_array( $packing_statuses ) && in_array( $new_status, $packing_statuses, true ) ) {
				$packing = $this->plugin->get_document_by_type( $order_id, 'packing' );
				if ( null !== $packing && ! $packing->exists ) {
					$this->plugin->save_document( $packing );
				}
			}

			$running = false;
		}

		/**
		 * Attach the invoice PDF to configured WooCommerce transactional emails.
		 *
		 * @param array    $attachments Current email attachments.
		 * @param string   $email_id    WooCommerce email ID.
		 * @param WC_Order $object      Object passed to the email (usually WC_Order).
		 * @return array
		 */
		public function attach_document_to_email( $attachments, $email_id, $object ) {
			static $running = false;
			if ( $running ) {
				return $attachments;
			}

			$configured_ids = $this->settings->get_option( 'invoice_attach_to_emails' );

			if ( ! is_array( $configured_ids ) || ! in_array( $email_id, $configured_ids, true ) ) {
				return $attachments;
			}

			if ( ! ( $object instanceof WC_Order ) ) {
				return $attachments;
			}

			$running  = true;
			$order_id = $object->get_id();
			$invoice  = $this->plugin->get_document_by_type( $order_id, 'invoice' );

			if ( null === $invoice ) {
				$running = false;
				return $attachments;
			}

			if ( ! $invoice->exists ) {
				$this->plugin->save_document( $invoice );
			}

			$path = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $invoice->save_path;
			if ( file_exists( $path ) ) {
				$attachments[] = $path;
			}

			$running = false;
			return $attachments;
		}
	}
}
