<?php
/**
 * Proforma invoice document type.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Proforma' ) ) {

	/**
	 * Pre-payment proforma invoice document.
	 *
	 * Carries complete order details, line items, and totals for pending/on-hold
	 * orders with a prominent proforma banner, without consuming the permanent
	 * sequential tax invoice numbering.
	 *
	 * @class   WPWing_WcPdf_Proforma
	 * @package WPWing
	 * @since   1.18.0
	 */
	class WPWing_WcPdf_Proforma extends WPWing_WcPdf_Document {

		/**
		 * Document type.
		 *
		 * @var string
		 */
		public $document_type = 'proforma';

		/**
		 * Proforma document number (defaults to WooCommerce order ID).
		 *
		 * @var int|string
		 */
		public $number;

		/**
		 * Proforma creation timestamp.
		 *
		 * @var int|string
		 */
		public $date;

		/**
		 * Settings API instance.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		public $settings;

		/**
		 * Constructor.
		 *
		 * @since 1.18.0
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
		 * Load existing proforma state from order meta.
		 *
		 * @since 1.18.0
		 */
		private function init_document() {
			$this->settings = WPWing_WcPdf_Settings::get_instance();

			$this->exists = (bool) $this->order->get_meta( '_wpwing_wcpdf_proforma' );
			if ( $this->exists ) {
				$this->save_path = (string) $this->order->get_meta( '_wpwing_wcpdf_proforma_path' );
				$this->number    = $this->order->get_meta( '_wpwing_wcpdf_proforma_number' );
				$this->date      = $this->order->get_meta( '_wpwing_wcpdf_proforma_date' );
			}

			if ( empty( $this->number ) ) {
				$this->number = $this->order->get_id();
			}
		}

		/**
		 * Format the display proforma number.
		 *
		 * @since 1.18.0
		 * @return string
		 */
		public function get_formatted_proforma_number() {
			$number    = ! empty( $this->number ) ? $this->number : $this->order->get_order_number();
			$formatted = 'PRO-' . $number;

			return apply_filters( 'wpwing_wcpdf_get_formatted_proforma_number', $formatted, $this->order, $this );
		}

		/**
		 * Return the formatted payment due date if configured.
		 *
		 * @since 1.18.0
		 * @return string Formatted due date, or empty string when not set.
		 */
		public function get_due_date() {
			$days = (int) $this->settings->get_option( 'invoice_due_date_days' );
			if ( $days <= 0 ) {
				return '';
			}

			$created    = $this->order->get_date_created();
			$base       = $created ? $created->getTimestamp() : time();
			$raw_format = $this->settings->get_option( 'invoice_date_format' );
			$format     = $raw_format ? $raw_format : 'd/m/Y';

			return wp_date( $format, $base + ( $days * DAY_IN_SECONDS ) );
		}

		/**
		 * Reset proforma metadata on the order.
		 *
		 * @since 1.18.0
		 */
		public function reset() {
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma_path' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma_number' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_proforma_date' );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Save proforma document and generate PDF file.
		 *
		 * @since 1.18.0
		 */
		public function save() {
			if ( $this->exists ) {
				return;
			}

			$this->date = time();
			$date       = getdate( $this->date );
			$year       = $date['year'];

			$filename        = apply_filters( 'wpwing_wcpdf_proforma_filename', $this->get_formatted_filename( '/proforma_' . $this->number, $year ), $this );
			$this->save_path = $year . $filename . '.pdf';
			$pdf_path        = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			$this->exists    = true;

			add_action( 'wpwing_wcpdf_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );

			if ( ! file_exists( $pdf_path ) ) {
				$this->exists = false;
				return;
			}

			$this->order->update_meta_data( '_wpwing_wcpdf_proforma', $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpdf_proforma_path', $this->save_path );
			$this->order->update_meta_data( '_wpwing_wcpdf_proforma_number', $this->number );
			$this->order->update_meta_data( '_wpwing_wcpdf_proforma_date', $this->date );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Return the localized "From" label.
		 *
		 * @since 1.18.0
		 * @return string
		 */
		protected function get_from_label() {
			return esc_html__( 'Proforma From', 'wpwing-wcpdf' );
		}

		/**
		 * Render the customer address block.
		 *
		 * @since 1.18.0
		 */
		public function render_template_customer_data() {
			global $wpwing_wcpdf_document;

			echo '<div class="invoice-to-section">';

			if ( $wpwing_wcpdf_document->order->get_formatted_billing_address() ) {
				echo '<span class="invoice-from-to">' . esc_html__( 'Proforma To', 'wpwing-wcpdf' ) . '</span>';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpdf_document->order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</div>';
			}

			if ( $this->settings->get_option( 'show_shipping_address' ) && $wpwing_wcpdf_document->order->get_formatted_shipping_address() ) {
				echo '<span class="invoice-from-to invoice-ship-to">' . esc_html__( 'Ship To', 'wpwing-wcpdf' ) . '</span>';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpdf_document->order->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</div>';
			}

			echo '</div>';
		}

		/**
		 * Render the proforma order metadata table.
		 *
		 * @since 1.18.0
		 */
		public function render_template_order_data() {
			global $wpwing_wcpdf_document;

			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			?>
			<table>
				<tr class="invoice-number">
					<td><?php esc_html_e( 'Proforma', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_proforma_number() ); ?></td>
				</tr>
				<tr class="invoice-order-number">
					<td><?php esc_html_e( 'Order', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-date">
					<td><?php esc_html_e( 'Date', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
				<?php
				$due_date = $wpwing_wcpdf_document->get_due_date();
				if ( $due_date ) :
					?>
				<tr class="invoice-due-date">
					<td><?php esc_html_e( 'Payment due date', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $due_date ); ?></td>
				</tr>
				<?php endif; ?>
				<tr class="invoice-amount">
					<td><?php esc_html_e( 'Order Amount', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo wp_kses_post( wc_price( $wpwing_wcpdf_document->order->get_total() ) ); ?></td>
				</tr>
			</table>
			<?php
		}
	}
}

