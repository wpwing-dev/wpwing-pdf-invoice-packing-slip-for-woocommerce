<?php
/**
 * WooCommerce hooks for auto-generation and email attachment.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Wc_Hooks' ) ) {

	/**
	 * Handles all WooCommerce integration hooks:
	 * auto-generation on status change, email attachment, and My Account link.
	 *
	 * @since 1.8.0
	 */
	class WPWing_WcPdf_Wc_Hooks {

		/**
		 * Plugin instance.
		 *
		 * @var WPWing_WcPdf_Plugin
		 */
		private $plugin;

		/**
		 * Settings instance.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		private $settings;

		/**
		 * Constructor.
		 *
		 * @param WPWing_WcPdf_Plugin   $plugin   Plugin instance.
		 * @param WPWing_WcPdf_Settings $settings Settings instance.
		 */
		public function __construct( WPWing_WcPdf_Plugin $plugin, WPWing_WcPdf_Settings $settings ) {
			$this->plugin   = $plugin;
			$this->settings = $settings;
		}

		/**
		 * Register all WooCommerce hooks.
		 */
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
			} else {
				$proforma = $this->plugin->get_document_by_type( $order->get_id(), 'proforma' );
				if ( ( null !== $proforma ) && $proforma->exists ) {
					$nonce_url = wp_nonce_url(
						add_query_arg( 'wpwing-view-proforma', $order->get_id() ),
						'wpwing_view_proforma_' . $order->get_id()
					);

					$actions['wpwing_proforma'] = array(
						'url'  => $nonce_url,
						'name' => __( 'Proforma', 'wpwing-wcpdf' ),
					);
				}
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

			try {
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

				$delivery_statuses = $this->settings->get_option( 'delivery_auto_statuses' );
				if ( is_array( $delivery_statuses ) && in_array( $new_status, $delivery_statuses, true ) ) {
					$delivery = $this->plugin->get_document_by_type( $order_id, 'delivery' );
					if ( null !== $delivery && ! $delivery->exists ) {
						$this->plugin->save_document( $delivery );
					}
				}

				$shipping_label_statuses = $this->settings->get_option( 'shipping_label_auto_statuses' );
				if ( is_array( $shipping_label_statuses ) && in_array( $new_status, $shipping_label_statuses, true ) ) {
					$shipping_label = $this->plugin->get_document_by_type( $order_id, 'shipping_label' );
					if ( null !== $shipping_label && ! $shipping_label->exists ) {
						$this->plugin->save_document( $shipping_label );
					}
				}

				$proforma_statuses = $this->settings->get_option( 'proforma_auto_statuses' );
				if ( is_array( $proforma_statuses ) && in_array( $new_status, $proforma_statuses, true ) ) {
					$proforma = $this->plugin->get_document_by_type( $order_id, 'proforma' );
					if ( null !== $proforma && ! $proforma->exists ) {
						$this->plugin->save_document( $proforma );
					}
				}
			} finally {
				$running = false;
			}
		}

		/**
		 * Attach configured document PDFs to WooCommerce transactional emails.
		 *
		 * @param array    $attachments Current email attachments.
		 * @param string   $email_id    WooCommerce email ID.
		 * @param WC_Order $object      Object passed to the email (usually WC_Order).
		 * @return array
		 */
		public function attach_document_to_email( $attachments, $email_id, $object ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.objectFound -- WooCommerce hook signature requires this parameter name.
			static $running = false;
			if ( $running ) {
				return $attachments;
			}

			if ( ! ( $object instanceof WC_Order ) ) {
				return $attachments;
			}

			$running  = true;
			$order_id = $object->get_id();

			try {
				// Invoice attachment.
				$invoice_email_ids = $this->settings->get_option( 'invoice_attach_to_emails' );
				$skip_free         = $this->settings->get_option( 'invoice_disable_free_orders' ) && (float) $object->get_total() === 0.0;
				if ( is_array( $invoice_email_ids ) && in_array( $email_id, $invoice_email_ids, true ) && ! $skip_free ) {
					$attachments = $this->append_document_attachment( $attachments, $order_id, 'invoice' );
				}

				// Proforma attachment.
				$proforma_email_ids = $this->settings->get_option( 'proforma_attach_to_emails' );
				if ( is_array( $proforma_email_ids ) && in_array( $email_id, $proforma_email_ids, true ) ) {
					$attachments = $this->append_document_attachment( $attachments, $order_id, 'proforma' );
				}

				// UBL 2.1 XML attachment alongside PDF.
				$ubl_email_ids = $this->settings->get_option( 'invoice_ubl_attach_to_emails' );
				if ( is_array( $ubl_email_ids ) && in_array( $email_id, $ubl_email_ids, true ) && ! $skip_free ) {
					$attachments = $this->append_ubl_attachment( $attachments, $order_id );
				}
			} finally {
				$running = false;
			}

			return $attachments;
		}

		/**
		 * Helper to append a generated document PDF file to the email attachments list.
		 *
		 * @param array  $attachments Current attachments.
		 * @param int    $order_id    Order ID.
		 * @param string $doc_type    Document type ('invoice', 'proforma', etc.).
		 * @return array
		 */
		private function append_document_attachment( $attachments, $order_id, $doc_type ) {
			$document = $this->plugin->get_document_by_type( $order_id, $doc_type );
			if ( null === $document ) {
				return $attachments;
			}

			if ( ! $document->exists ) {
				$this->plugin->save_document( $document );
			}

			if ( $document->exists && ! empty( $document->save_path ) ) {
				$path = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $document->save_path;
				$real = realpath( $path );
				$base = realpath( WPWING_WCPDF_DOCUMENT_SAVE_DIR );
				if ( $real && $base && 0 === strpos( $real, $base . DIRECTORY_SEPARATOR ) && ! in_array( $real, $attachments, true ) ) {
					$attachments[] = $real;
				}
			}

			return $attachments;
		}

		/**
		 * Helper to append a generated UBL 2.1 XML file to the email attachments list.
		 *
		 * @param array $attachments Current attachments.
		 * @param int   $order_id    Order ID.
		 * @return array
		 * @since 1.19.0
		 */
		private function append_ubl_attachment( $attachments, $order_id ) {
			$invoice = $this->plugin->get_document_by_type( $order_id, 'invoice' );
			if ( null === $invoice ) {
				return $attachments;
			}

			if ( ! $invoice->exists ) {
				$this->plugin->save_document( $invoice );
			}

			if ( $invoice instanceof WPWing_WcPdf_Invoice && $invoice->exists ) {
				$ubl  = new WPWing_WcPdf_UBL( $invoice );
				$path = $ubl->get_xml_file_path();
				if ( ! empty( $path ) ) {
					$real = realpath( $path );
					$base = realpath( WPWING_WCPDF_DOCUMENT_SAVE_DIR );
					if ( $real && $base && 0 === strpos( $real, $base . DIRECTORY_SEPARATOR ) && ! in_array( $real, $attachments, true ) ) {
						$attachments[] = $real;
					}
				}
			}

			return $attachments;
		}
	}
}
