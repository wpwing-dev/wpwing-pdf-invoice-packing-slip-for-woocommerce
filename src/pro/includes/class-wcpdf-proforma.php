<?php
/**
 * Proforma invoice document type.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Proforma' ) ) {

	/**
	 * Proforma invoice document - same line items as the order but clearly
	 * marked "PROFORMA" and carries no permanent invoice number.
	 */
	class WPWing_WcPdf_Proforma extends WPWing_WcPdf_ProDocument {

		/**
		 * Document type identifier.
		 *
		 * @var string
		 */
		public $document_type = 'proforma';

		/**
		 * Relative path to the saved PDF file.
		 *
		 * @var string
		 */
		public $save_path;

		/**
		 * Constructor.
		 *
		 * @param int $order_id WooCommerce order ID.
		 */
		public function __construct( $order_id ) {
			parent::__construct( $order_id );
			if ( ! $this->is_valid ) {
				return;
			}
			$this->init_document();
		}

		/**
		 * Load proforma state from order meta.
		 */
		private function init_document() {
			$this->settings = WPWing_WcPdf_Settings::get_instance();
			$this->exists   = $this->order->get_meta( '_wpwing_wcpdf_proforma' );
			if ( $this->exists ) {
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_proforma_path' );
			}
		}

		/**
		 * Remove proforma metadata from the order.
		 */
		public function reset() {
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma_path' );
			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Generate and save the proforma PDF.
		 */
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

		/**
		 * Register template section hooks for proforma rendering.
		 */
		public function init_template_generation_actions() {
			add_action( 'wpwing_wcpdf_proforma_template_company_data', array( $this, 'show_proforma_template_company_data' ) );
			add_action( 'wpwing_wcpdf_proforma_template_company_logo', array( $this, 'show_proforma_template_company_logo' ) );
			add_action( 'wpwing_wcpdf_proforma_template_customer_data', array( $this, 'show_proforma_template_customer_data' ) );
			add_action( 'wpwing_wcpdf_proforma_template_order_data', array( $this, 'show_proforma_template_order_data' ) );
			add_action( 'wpwing_wcpdf_proforma_template_product_list', array( $this, 'show_proforma_template_product_list' ) );
			add_action( 'wpwing_wcpdf_proforma_template_footer', array( $this, 'show_proforma_template_footer' ) );
		}

		/**
		 * Render the company data section.
		 */
		public function show_proforma_template_company_data() {
			$this->render_company_data( __( 'From', 'wpwing-pdf-invoice-pro' ) );
		}

		/**
		 * Render the company logo.
		 */
		public function show_proforma_template_company_logo() {
			$this->render_company_logo();
		}

		/**
		 * Render the customer address block.
		 */
		public function show_proforma_template_customer_data() {
			$this->render_billing_address( __( 'Bill To', 'wpwing-pdf-invoice-pro' ) );
		}

		/**
		 * Render the proforma order details table.
		 */
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
					<td class="right"><?php echo wp_kses_post( wc_price( $wpwing_wcpdf_document->order->get_total() ) ); ?></td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Render the product list by including the proforma products template.
		 */
		public function show_proforma_template_product_list() {
			include $this->get_theme_dir() . 'proforma/products.php';
		}

		/**
		 * Render the footer section.
		 */
		public function show_proforma_template_footer() {
			$this->render_footer( 'proforma' );
		}
	}
}
