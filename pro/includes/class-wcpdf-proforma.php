<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Proforma' ) ) {

	/**
	 * Proforma invoice document — same line items as the order but clearly
	 * marked "PROFORMA" and carries no permanent invoice number.
	 */
	class WPWing_WcPdf_Proforma extends WPWing_WcPdf_ProDocument {

		public $document_type = 'proforma';

		public $save_path;

		public function __construct( $order_id ) {
			parent::__construct( $order_id );
			if ( ! $this->is_valid ) {
				return;
			}
			$this->init_document();
		}

		private function init_document() {
			$this->settings = WPWing_WcPdf_Settings::get_instance();
			$this->exists   = $this->order->get_meta( '_wpwing_wcpdf_proforma' );
			if ( $this->exists ) {
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_proforma_path' );
			}
		}

		public function reset() {
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma_path' );
			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		public function save() {
			if ( $this->exists ) {
				return;
			}

			$date            = getdate( time() );
			$year            = $date['year'];
			$this->save_path = $year . '/proforma_' . $this->order->get_id() . '.pdf';
			$this->exists    = true;

			$this->order->update_meta_data( '_wpwing_wcpdf_proforma', $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpdf_proforma_path', $this->save_path );
			$this->order->apply_changes();
			$this->order->save_meta_data();

			$pdf_path = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			add_action( 'wpwing_wcpdf_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );
		}

		public function init_template_generation_actions() {
			add_action( 'wpwing_wcpdf_proforma_template_company_data', array( $this, 'show_proforma_template_company_data' ) );
			add_action( 'wpwing_wcpdf_proforma_template_company_logo', array( $this, 'show_proforma_template_company_logo' ) );
			add_action( 'wpwing_wcpdf_proforma_template_customer_data', array( $this, 'show_proforma_template_customer_data' ) );
			add_action( 'wpwing_wcpdf_proforma_template_order_data', array( $this, 'show_proforma_template_order_data' ) );
			add_action( 'wpwing_wcpdf_proforma_template_product_list', array( $this, 'show_proforma_template_product_list' ) );
			add_action( 'wpwing_wcpdf_proforma_template_footer', array( $this, 'show_proforma_template_footer' ) );
		}

		public function show_proforma_template_company_data() {
			$this->render_company_data( __( 'From', 'wpwing-pdf-invoice-pro' ) );
		}

		public function show_proforma_template_company_logo() {
			$this->render_company_logo();
		}

		public function show_proforma_template_customer_data() {
			$this->render_billing_address( __( 'Bill To', 'wpwing-pdf-invoice-pro' ) );
		}

		public function show_proforma_template_order_data() {
			global $wpwing_wcpdf_document;
			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			?>
			<table>
				<tr class="invoice-number">
					<td><?php esc_html_e( 'Proforma', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( 'PRO-' . $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-order-number">
					<td><?php esc_html_e( 'Order', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-date">
					<td><?php esc_html_e( 'Date', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
				<tr class="invoice-amount">
					<td><?php esc_html_e( 'Amount', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo wc_price( $wpwing_wcpdf_document->order->get_total() ); ?></td>
				</tr>
			</table>
			<?php
		}

		public function show_proforma_template_product_list() {
			include $this->get_theme_dir() . 'proforma/products.php';
		}

		public function show_proforma_template_footer() {
			$this->render_footer( 'proforma' );
		}
	}
}
