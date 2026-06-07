<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Orders_List' ) ) {

	/**
	 * Handles the invoice status column and bulk generation actions
	 * on both the classic and HPOS WooCommerce orders list screens.
	 *
	 * @since 1.8.0
	 */
	class WPWing_WcPdf_Orders_List {

		private $plugin;

		public function __construct( WPWing_WcPdf_Plugin $plugin ) {
			$this->plugin = $plugin;
		}

		public function register() {
			// Bulk actions - classic orders screen.
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions' ), 10, 3 );

			// Bulk actions - HPOS orders screen.
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_actions' ), 10, 3 );

			// Invoice status column - classic orders screen.
			add_filter( 'manage_shop_order_posts_columns', array( $this, 'add_order_list_column' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );

			// Invoice status column - HPOS orders screen.
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_list_column' ) );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );
		}

		/**
		 * Register bulk actions on the orders list screen.
		 *
		 * @param array $actions Existing bulk actions.
		 * @return array
		 */
		public function add_bulk_actions( $actions ) {
			$actions['wpwing_generate_invoices'] = __( 'Generate Invoices', 'wpwing-wcpdf' );
			$actions['wpwing_generate_packing']  = __( 'Generate Packing Slips', 'wpwing-wcpdf' );
			return $actions;
		}

		/**
		 * Process bulk invoice / packing slip generation.
		 *
		 * @param string $redirect_to Redirect URL after processing.
		 * @param string $action      Bulk action key.
		 * @param array  $ids         Selected order IDs.
		 * @return string
		 */
		public function handle_bulk_actions( $redirect_to, $action, $ids ) {
			if ( 'wpwing_generate_invoices' !== $action && 'wpwing_generate_packing' !== $action ) {
				return $redirect_to;
			}

			$document_type = ( 'wpwing_generate_invoices' === $action ) ? 'invoice' : 'packing';
			$count         = 0;
			$all_ids       = (array) $ids;
			$batch_ids     = array_slice( $all_ids, 0, 50 );
			$skipped       = count( $all_ids ) - count( $batch_ids );

			foreach ( $batch_ids as $order_id ) {
				$document = $this->plugin->get_document_by_type( intval( $order_id ), $document_type );
				if ( null !== $document && ! $document->exists ) {
					$this->plugin->save_document( $document );
					++$count;
				}
			}

			$redirect_to = remove_query_arg( array( 'wpwing_bulk_type', 'wpwing_bulk_created', 'wpwing_bulk_skipped' ), $redirect_to );
			$redirect_to = add_query_arg(
				array(
					'wpwing_bulk_type'    => $document_type,
					'wpwing_bulk_created' => $count,
					'wpwing_bulk_skipped' => $skipped,
				),
				$redirect_to
			);

			return $redirect_to;
		}

		/**
		 * Add invoice status column to the orders list table.
		 *
		 * @param array $columns Existing table columns.
		 * @return array
		 */
		public function add_order_list_column( $columns ) {
			$new_columns = array();
			foreach ( $columns as $key => $label ) {
				$new_columns[ $key ] = $label;
				if ( 'order_status' === $key ) {
					$new_columns['wpwing_invoice'] = __( 'Invoice', 'wpwing-wcpdf' );
				}
			}
			return $new_columns;
		}

		/**
		 * Render the invoice status column cell.
		 *
		 * Works for both classic (post ID int) and HPOS (WC_Order object) screens.
		 *
		 * @param string       $column      Column key.
		 * @param int|WC_Order $order_or_id Order ID or WC_Order object.
		 */
		public function render_order_list_column( $column, $order_or_id ) {
			if ( 'wpwing_invoice' !== $column ) {
				return;
			}

			$order_id = $order_or_id instanceof WC_Order ? $order_or_id->get_id() : intval( $order_or_id );
			$invoice  = $this->plugin->get_document_by_type( $order_id, 'invoice' );

			if ( null !== $invoice && $invoice->exists ) {
				printf(
					'<span class="dashicons dashicons-yes-alt" title="%s"></span>',
					esc_attr__( 'Invoice generated', 'wpwing-wcpdf' )
				);
			} else {
				$url = wp_nonce_url(
					add_query_arg( 'wpwing-create-invoice', $order_id ),
					'wpwing_create_invoice_' . $order_id
				);
				printf(
					'<a href="%s" title="%s"><span class="dashicons dashicons-plus-alt2"></span></a>',
					esc_url( $url ),
					esc_attr__( 'Create Invoice', 'wpwing-wcpdf' )
				);
			}
		}
	}
}
