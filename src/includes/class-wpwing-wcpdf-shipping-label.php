<?php
/**
 * Shipping label document type.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Shipping_Label' ) ) {

	/**
	 * Print-ready shipping label: sender details, prominent recipient shipping
	 * address, order weight, and an optional QR code linking back to the order.
	 * Carries no pricing information.
	 *
	 * @class   WPWing_WcPdf_Shipping_Label
	 * @package WPWing
	 * @since   1.15.0
	 */
	class WPWing_WcPdf_Shipping_Label extends WPWing_WcPdf_Document {

		/**
		 * Document type.
		 *
		 * @var string
		 */
		public $document_type = 'shipping_label';

		/**
		 * Order Number
		 *
		 * @var int
		 */
		public $number;

		/**
		 * Shipping label creation timestamp.
		 *
		 * @var int
		 */
		public $date;

		/**
		 * Settings API instance
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		public $settings;

		/**
		 * Constructor.
		 *
		 * @since 1.15.0
		 * @param int $order_id WooCommerce order ID.
		 */
		public function __construct( $order_id ) {

			// Call base class constructor.
			parent::__construct( $order_id );

			// If this document is not related to a valid WooCommerce order, exit.
			if ( ! $this->is_valid ) {
				return;
			}

			$this->number = $order_id;

			// Fill shipping label information from a previous label if it exists, or from general plugin options plus order data.
			$this->init_document();
		}

		/**
		 * Check if a shipping label exists for current order and load related data
		 *
		 * @since 1.15.0
		 */
		private function init_document() {

			$this->settings = WPWing_WcPdf_Settings::get_instance();

			$this->exists = $this->order->get_meta( '_wpwing_wcpdf_shipping_label' );

			if ( $this->exists ) {
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_shipping_label_path' );
			}
		}

		/**
		 * Reset order meta data of shipping label
		 *
		 * @since 1.15.0
		 */
		public function reset() {

			$this->order->delete_meta_data( '_wpwing_wcpdf_shipping_label' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_shipping_label_path' );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Set shipping label data for current order and persist the PDF file.
		 *
		 * @since 1.15.0
		 */
		public function save() {

			// Avoid generating a new document if a previous one still exists.
			if ( $this->exists ) {
				return;
			}

			$this->date = time();
			$date       = getdate( $this->date );
			$year       = $date['year'];

			$filename        = apply_filters( 'wpwing_wcpdf_shipping_label_filename', '/shipping_label_' . $this->number, $this );
			$this->save_path = $year . $filename . '.pdf';
			$pdf_path        = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			$this->exists    = true;
			add_action( 'wpwing_wcpdf_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );

			if ( ! file_exists( $pdf_path ) ) {
				$this->exists = false;
				return;
			}

			$this->order->update_meta_data( '_wpwing_wcpdf_shipping_label', $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpdf_shipping_label_path', $this->save_path );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Returns the localised "From" heading for the shipping label header block.
		 *
		 * @return string
		 */
		protected function get_from_label() {
			return esc_html__( 'Ship From', 'wpwing-wcpdf' );
		}

		/**
		 * Render the recipient address block in the shipping label template.
		 * Prefers the shipping address; falls back to billing. Kept visually
		 * prominent by the template's own styling, not by markup differences here.
		 */
		public function render_template_customer_data() {

			global $wpwing_wcpdf_document;

			$address = $wpwing_wcpdf_document->order->get_formatted_shipping_address();
			if ( ! $address ) {
				$address = $wpwing_wcpdf_document->order->get_formatted_billing_address();
			}

			echo '<div class="invoice-to-section">';

			if ( $address ) {
				echo '<span class="invoice-from-to">' . esc_html__( 'Deliver To', 'wpwing-wcpdf' ) . '</span>';
				echo '<div class="customer-details">' . wp_kses( $address, array( 'br' => array() ) ) . '</div>';
			}

			echo '</div>';
		}

		/**
		 * Render the order details table in the shipping label template.
		 */
		public function render_template_order_data() {

			global $wpwing_wcpdf_document;

			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			$weight = $wpwing_wcpdf_document->get_formatted_order_weight();
			?>
			<table>
				<tr class="invoice-order-number">
					<td><?php esc_html_e( 'Order Number', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-date">
					<td><?php esc_html_e( 'Ship Date', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
				<?php if ( $weight ) : ?>
				<tr class="invoice-weight">
					<td><?php esc_html_e( 'Weight', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $weight ); ?></td>
				</tr>
				<?php endif; ?>
				<?php if ( $wpwing_wcpdf_document->order->get_shipping_method() ) : ?>
				<tr class="invoice-shipping-method">
					<td><?php esc_html_e( 'Shipping Method', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_shipping_method() ); ?></td>
				</tr>
				<?php endif; ?>
			</table>
			<?php
			$this->render_shipping_label_qr_code();
		}

		/**
		 * Sum the weight of every line item's product, in the shop's configured weight unit.
		 *
		 * @return string Formatted weight (e.g. "2.5 kg"), or an empty string when no items have a weight set.
		 */
		public function get_formatted_order_weight() {

			$total = 0.0;
			$found = false;

			foreach ( $this->order->get_items() as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}
				$product = $item->get_product();
				if ( ! $product || '' === $product->get_weight() ) {
					continue;
				}
				$found  = true;
				$total += (float) $product->get_weight() * $item->get_quantity();
			}

			if ( ! $found ) {
				return '';
			}

			$unit = get_option( 'woocommerce_weight_unit', 'kg' );

			return wc_format_decimal( $total, 2, true ) . ' ' . $unit;
		}

		/**
		 * Render the QR code linking back to the order, beneath the order details table, when enabled.
		 */
		public function render_shipping_label_qr_code() {

			if ( ! $this->settings->get_option( 'shipping_label_show_qr' ) ) {
				return;
			}

			global $wpwing_wcpdf_document;

			$uri = $this->generate_qr_data_uri( (string) $wpwing_wcpdf_document->order->get_view_order_url() );
			if ( '' === $uri ) {
				return;
			}

			echo '<div class="invoice-qr" style="margin-top:10px;"><img src="' . esc_attr( $uri ) . '" width="110" height="110" alt="QR code" /></div>';
		}
	}
}
