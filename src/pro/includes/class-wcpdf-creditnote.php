<?php
/**
 * Credit note document type.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_CreditNote' ) ) {

	/**
	 * Credit note document - auto-generated when a WooCommerce refund is issued.
	 * Each refund gets its own credit note, stored against the original order.
	 */
	class WPWing_WcPdf_CreditNote extends WPWing_WcPdf_ProDocument {

		/**
		 * Document type identifier.
		 *
		 * @var string
		 */
		public $document_type = 'creditnote';

		/**
		 * WooCommerce refund ID.
		 *
		 * @var int
		 */
		public $refund_id;

		/**
		 * WooCommerce refund order object.
		 *
		 * @var WC_Order_Refund|null
		 */
		public $refund;

		/**
		 * Relative path to the saved PDF file.
		 *
		 * @var string
		 */
		public $save_path;

		/**
		 * Constructor.
		 *
		 * @param int $order_id  WooCommerce order ID.
		 * @param int $refund_id WooCommerce refund ID.
		 */
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

		/**
		 * Load credit note state from order meta.
		 */
		private function init_document() {
			$this->settings = WPWing_WcPdf_Settings::get_instance();
			$this->exists   = $this->order->get_meta( '_wpwing_wcpdf_creditnote_' . $this->refund_id );
			if ( $this->exists ) {
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_creditnote_path_' . $this->refund_id );
			}
		}

		/**
		 * Remove credit note metadata from the order.
		 */
		public function reset() {
			$this->order->delete_meta_data( '_wpwing_wcpdf_creditnote_' . $this->refund_id );
			$this->order->delete_meta_data( '_wpwing_wcpdf_creditnote_path_' . $this->refund_id );
			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Generate and save the credit note PDF.
		 */
		public function save() {
			if ( $this->exists ) {
				return;
			}

			$date            = getdate( time() );
			$year            = $date['year'];
			$this->save_path = $year . '/creditnote_' . $this->refund_id . '.pdf';
			$this->exists    = true;

			$this->order->update_meta_data( '_wpwing_wcpdf_creditnote_' . $this->refund_id, $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpdf_creditnote_path_' . $this->refund_id, $this->save_path );
			$this->order->apply_changes();
			$this->order->save_meta_data();

			$pdf_path = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			add_action( 'wpwing_wcpdf_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );
		}

		/**
		 * Register template section hooks for credit note rendering.
		 */
		public function init_template_generation_actions() {
			add_action( 'wpwing_wcpdf_creditnote_template_company_data', array( $this, 'show_creditnote_template_company_data' ) );
			add_action( 'wpwing_wcpdf_creditnote_template_company_logo', array( $this, 'show_creditnote_template_company_logo' ) );
			add_action( 'wpwing_wcpdf_creditnote_template_customer_data', array( $this, 'show_creditnote_template_customer_data' ) );
			add_action( 'wpwing_wcpdf_creditnote_template_order_data', array( $this, 'show_creditnote_template_order_data' ) );
			add_action( 'wpwing_wcpdf_creditnote_template_product_list', array( $this, 'show_creditnote_template_product_list' ) );
			add_action( 'wpwing_wcpdf_creditnote_template_footer', array( $this, 'show_creditnote_template_footer' ) );
		}

		/**
		 * Render the company data section.
		 */
		public function show_creditnote_template_company_data() {
			$this->render_company_data( __( 'From', 'wpwing-pdf-invoice-pro' ) );
		}

		/**
		 * Render the company logo.
		 */
		public function show_creditnote_template_company_logo() {
			$this->render_company_logo();
		}

		/**
		 * Render the customer address block.
		 */
		public function show_creditnote_template_customer_data() {
			$this->render_billing_address( __( 'Bill To', 'wpwing-pdf-invoice-pro' ) );
		}

		/**
		 * Render the credit note order details table.
		 */
		public function show_creditnote_template_order_data() {
			global $wpwing_wcpdf_document;
			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			$refund_total = $wpwing_wcpdf_document->refund ? abs( $wpwing_wcpdf_document->refund->get_total() ) : 0;
			?>
			<table>
				<tr class="invoice-number">
					<td><?php esc_html_e( 'Credit Note', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( 'CN-' . $wpwing_wcpdf_document->refund_id ); ?></td>
				</tr>
				<tr class="invoice-order-number">
					<td><?php esc_html_e( 'Original Order', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-date">
					<td><?php esc_html_e( 'Date', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
				<tr class="invoice-amount">
					<td><?php esc_html_e( 'Refund Amount', 'wpwing-pdf-invoice-pro' ); ?></td>
					<td class="right"><?php echo wp_kses_post( wc_price( $refund_total ) ); ?></td>
				</tr>
			</table>
			<?php
		}

		/**
		 * Render the product list by including the creditnote products template.
		 */
		public function show_creditnote_template_product_list() {
			include $this->get_theme_dir() . 'creditnote/products.php';
		}

		/**
		 * Render the footer section.
		 */
		public function show_creditnote_template_footer() {
			$this->render_footer( 'creditnote' );
		}
	}
}
