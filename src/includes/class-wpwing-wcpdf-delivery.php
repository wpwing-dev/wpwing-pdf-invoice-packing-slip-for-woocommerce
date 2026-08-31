<?php
/**
 * Delivery note document type.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Delivery' ) ) {

	/**
	 * Implements features related to a PDF document
	 *
	 * @class   WPWing_WcPdf_Delivery
	 * @package WPWing
	 * @since   1.12.0
	 */
	class WPWing_WcPdf_Delivery extends WPWing_WcPdf_Document {

		/**
		 * Document type
		 *
		 * @var string
		 */
		public $document_type = 'delivery';

		/**
		 * Order Number
		 *
		 * @var int
		 */
		public $number;

		/**
		 * Delivery note creation timestamp.
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
		 * @since 1.12.0
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

			// Fill delivery note information from a previous note if it exists, or from general plugin options plus order data.
			$this->init_document();
		}

		/**
		 * Check if a delivery note exists for current order and load related data
		 *
		 * @since 1.12.0
		 */
		private function init_document() {

			$this->settings = WPWing_WcPdf_Settings::get_instance();

			$this->exists = $this->order->get_meta( '_wpwing_wcpdf_delivery' );

			if ( $this->exists ) {
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_delivery_path' );
			}
		}

		/**
		 * Reset order meta data of delivery note
		 *
		 * @since 1.12.0
		 */
		public function reset() {

			$this->order->delete_meta_data( '_wpwing_wcpdf_delivery' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_delivery_path' );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Set delivery note data for current order and persist the PDF file.
		 *
		 * @since 1.12.0
		 */
		public function save() {

			// Avoid generating a new document if a previous one still exists.
			if ( $this->exists ) {
				return;
			}

			$this->date = time();
			$date       = getdate( $this->date );
			$year       = $date['year'];

			$filename        = apply_filters( 'wpwing_wcpdf_delivery_filename', '/delivery_' . $this->number, $this );
			$this->save_path = $year . $filename . '.pdf';
			$pdf_path        = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			$this->exists    = true;
			add_action( 'wpwing_wcpdf_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );

			if ( ! file_exists( $pdf_path ) ) {
				$this->exists = false;
				return;
			}

			$this->order->update_meta_data( '_wpwing_wcpdf_delivery', $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpdf_delivery_path', $this->save_path );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}


		/**
		 * Returns the localised "From" heading for the delivery note header block.
		 *
		 * @return string
		 */
		protected function get_from_label() {
			return esc_html__( 'Delivery From', 'wpwing-wcpdf' );
		}

		/**
		 * Render the recipient address block in the delivery note template.
		 * Prefers the shipping address; falls back to billing.
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
		 * Render the order details table in the delivery note template.
		 */
		public function render_template_order_data() {

			global $wpwing_wcpdf_document;

			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			?>
			<table>
				<tr class="invoice-order-number">
					<td><?php esc_html_e( 'Order Number', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-date">
					<td><?php esc_html_e( 'Delivery Date', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
			</table>
			<?php
		}
	}
}
