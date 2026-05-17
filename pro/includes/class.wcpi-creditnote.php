<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WCPI_CreditNote' ) ) {

	/**
	 * Credit note document — auto-generated when a WooCommerce refund is issued.
	 * Each refund gets its own credit note, stored against the original order.
	 */
	class WCPI_CreditNote extends WCPI_ProDocument {

		public $document_type = 'creditnote';

		/** @var int WooCommerce refund ID */
		public $refund_id;

		/** @var WC_Order_Refund|null */
		public $refund;

		public $save_path;

		public function __construct( $order_id, $refund_id = 0 ) {
			parent::__construct( $order_id );
			$this->refund_id = (int) $refund_id;
			if ( $this->refund_id ) {
				$this->refund = wc_get_order( $this->refund_id );
			}
			if ( ! $this->is_valid ) {
				return;
			}
			$this->init_document();
		}

		private function init_document() {
			$this->settings = WPWing_WCPI_Settings::get_instance();
			$this->exists   = $this->order->get_meta( '_wpwing_wcpi_creditnote_' . $this->refund_id );
			if ( $this->exists ) {
				$this->save_path = $this->order->get_meta( '_wpwing_wcpi_creditnote_path_' . $this->refund_id );
			}
		}

		public function reset() {
			$this->order->delete_meta_data( '_wpwing_wcpi_creditnote_' . $this->refund_id );
			$this->order->delete_meta_data( '_wpwing_wcpi_creditnote_path_' . $this->refund_id );
			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		public function save() {
			if ( $this->exists ) {
				return;
			}

			$date            = getdate( time() );
			$year            = $date['year'];
			$this->save_path = $year . '/creditnote_' . $this->refund_id . '.pdf';
			$this->exists    = true;

			$this->order->update_meta_data( '_wpwing_wcpi_creditnote_' . $this->refund_id, $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpi_creditnote_path_' . $this->refund_id, $this->save_path );
			$this->order->apply_changes();
			$this->order->save_meta_data();

			$pdf_path = WPWING_WCPI_DOCUMENT_SAVE_DIR . $this->save_path;
			add_action( 'wpwing_wcpi_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );
		}

		public function init_template_generation_actions() {
			add_action( 'wpwing_wcpi_creditnote_template_company_data', array( $this, 'show_creditnote_template_company_data' ) );
			add_action( 'wpwing_wcpi_creditnote_template_company_logo', array( $this, 'show_creditnote_template_company_logo' ) );
			add_action( 'wpwing_wcpi_creditnote_template_customer_data', array( $this, 'show_creditnote_template_customer_data' ) );
			add_action( 'wpwing_wcpi_creditnote_template_order_data', array( $this, 'show_creditnote_template_order_data' ) );
			add_action( 'wpwing_wcpi_creditnote_template_product_list', array( $this, 'show_creditnote_template_product_list' ) );
			add_action( 'wpwing_wcpi_creditnote_template_footer', array( $this, 'show_creditnote_template_footer' ) );
		}

		public function show_creditnote_template_company_data() {
			$this->render_company_data( __( 'From', 'wpwing-pdf-invoice-pro' ) );
		}

		public function show_creditnote_template_company_logo() {
			$this->render_company_logo();
		}

		public function show_creditnote_template_customer_data() {
			$this->render_billing_address( __( 'Bill To', 'wpwing-pdf-invoice-pro' ) );
		}

		public function show_creditnote_template_order_data() {
			global $wpwing_wcpi_document;
			if ( ! isset( $wpwing_wcpi_document ) || ! $wpwing_wcpi_document->exists ) {
				return;
			}
			$refund_total = $wpwing_wcpi_document->refund ? abs( $wpwing_wcpi_document->refund->get_total() ) : 0;
			?>
			<table>
				<tr class="invoice-number">
					<td><?php esc_html_e( 'Credit Note', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( 'CN-' . $wpwing_wcpi_document->refund_id ); ?></td>
				</tr>
				<tr class="invoice-order-number">
					<td><?php esc_html_e( 'Original Order', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpi_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-date">
					<td><?php esc_html_e( 'Date', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpi_document->get_formatted_date() ); ?></td>
				</tr>
				<tr class="invoice-amount">
					<td><?php esc_html_e( 'Refund Amount', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo wc_price( $refund_total ); ?></td>
				</tr>
			</table>
			<?php
		}

		public function show_creditnote_template_product_list() {
			include $this->get_theme_dir() . 'creditnote/products.php';
		}

		public function show_creditnote_template_footer() {
			$this->render_footer( 'creditnote' );
		}
	}
}
