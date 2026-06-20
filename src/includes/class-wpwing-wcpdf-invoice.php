<?php
/**
 * Invoice document type.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Invoice' ) ) {

	/**
	 * Implements features related to a PDF document
	 *
	 * @class   WPWing_WcPdf_Invoice
	 * @package WPWing
	 * @since   1.0.0
	 */
	class WPWing_WcPdf_Invoice extends WPWing_WcPdf_Document {

		/**
		 * Document type identifier.
		 *
		 * @var string
		 */
		public $document_type = 'invoice';

		/**
		 * Invoice creation timestamp.
		 *
		 * @var int
		 */
		public $date;

		/**
		 * Invoice number.
		 *
		 * @var int
		 */
		private $number;

		/**
		 * Invoice number prefix.
		 *
		 * @var string
		 */
		private $prefix;

		/**
		 * Invoice number suffix.
		 *
		 * @var string
		 */
		private $suffix;

		/**
		 * Relative path to the saved PDF file.
		 *
		 * @var string
		 */
		public $save_path;

		/**
		 * Settings API instance
		 *
		 * @var Object
		 */
		public $settings;

		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @param int $order_id WooCommerce order ID.
		 */
		public function __construct( $order_id ) {

			// Call base class constructor.
			parent::__construct( $order_id );

			// If this document is not related to a valid WooCommerce order, exit.
			if ( ! $this->is_valid ) {
				return;
			}

			// Fill invoice information from a previous invoice if it exists, or from general plugin options plus order data.
			$this->init_document();
		}

		/**
		 * Check if an invoice exist for current order and load related data
		 *
		 * @since 1.0.0
		 */
		private function init_document() {

			$this->settings = WPWing_WcPdf_Settings::get_instance();

			$this->exists = $this->order->get_meta( '_wpwing_wcpdf_invoiced' );
			if ( $this->exists ) {
				$this->number    = $this->order->get_meta( '_wpwing_wcpdf_invoice_number' );
				$this->prefix    = $this->order->get_meta( '_wpwing_wcpdf_invoice_prefix' );
				$this->suffix    = $this->order->get_meta( '_wpwing_wcpdf_invoice_suffix' );
				$this->date      = $this->order->get_meta( '_wpwing_wcpdf_invoice_date' );
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_invoice_path' );
			} else {
				$prefix       = $this->settings->get_option( 'invoice_prefix' );
				$this->prefix = $prefix ? $prefix : '';
				$suffix       = $this->settings->get_option( 'invoice_suffix' );
				$this->suffix = $suffix ? $suffix : '';
			}
		}

		/**
		 * Get formatted invoice
		 *
		 * @since 1.0.0
		 */
		public function get_formatted_invoice_number() {

			$format = $this->settings->get_option( 'invoice_number_format' );
			if ( ! $format ) {
				$format = '{number}';
			}

			// Resolve date for {year}/{month}/{day} tokens from invoice date, order date, or now.
			$timestamp = 0;
			if ( ! empty( $this->date ) ) {
				$timestamp = is_numeric( $this->date ) ? (int) $this->date : strtotime( $this->date );
			}
			if ( ! $timestamp && $this->order ) {
				$order_date = $this->order->get_date_created();
				if ( $order_date ) {
					$timestamp = $order_date->getTimestamp();
				}
			}
			if ( ! $timestamp ) {
				$timestamp = time();
			}

			$search  = array( '{number}', '{year}', '{month}', '{day}', '[number]', '[prefix]', '[suffix]' );
			$replace = array(
				$this->number,
				wp_date( 'Y', $timestamp ),
				wp_date( 'm', $timestamp ),
				wp_date( 'd', $timestamp ),
				$this->number,   // Legacy [number].
				$this->prefix,   // Legacy [prefix].
				$this->suffix,   // Legacy [suffix].
			);

			return apply_filters( 'wpwing_wcpdf_get_formatted_invoice_number', str_replace( $search, $replace, $format ), $this->order );
		}

		/**
		 * Get the formatted due date based on the configured payment terms.
		 *
		 * @return string Formatted due date, or empty string if not configured.
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
		 * Reset order meta data of invoice
		 *
		 * @since 1.0.0
		 */
		public function reset() {

			$this->order->delete_meta_data( '_wpwing_wcpdf_invoiced' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_invoice_number' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_invoice_prefix' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_invoice_suffix' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_invoice_date' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_invoice_path' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_pending_invoice_number' );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Return the next available invoice number and atomically increment the counter.
		 *
		 * Uses a MySQL advisory lock so concurrent requests cannot receive the same number.
		 *
		 * @since 1.5.0
		 */
		public function get_new_invoice_number() {

			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- advisory lock, no cacheable result.
			$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', 'wpwing_wcpdf_invoice_number' ) );

			if ( $this->settings->get_option( 'invoice_number_reset_yearly' ) ) {
				$current_year = (int) wp_date( 'Y' );
				$stored_year  = (int) $this->settings->get_option( '_invoice_last_year' );
				if ( $stored_year > 0 && $stored_year !== $current_year ) {
					$this->settings->set_option( 'invoice_number', 1 );
				}
				if ( $stored_year !== $current_year ) {
					$this->settings->set_option( '_invoice_last_year', $current_year );
				}
			}

			$current = (int) $this->settings->get_option( 'invoice_number' );
			if ( $current < 1 ) {
				$current = 1;
			}

			$this->settings->set_option( 'invoice_number', $current + 1 );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- advisory lock, no cacheable result.
			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'wpwing_wcpdf_invoice_number' ) );

			return $current;
		}

		/**
		 * Set invoice data for current order, picking the invoice number from the related general option
		 *
		 * @since 1.0.0
		 */
		public function save() {

			// Avoid generating a new invoice from a previous one.
			if ( $this->exists ) {
				return;
			}

			$this->date = time();
			$date       = getdate( $this->date );
			$year       = $date['year'];

			$invoice_number = apply_filters( 'wpwing_wcpdf_new_invoice_number', null, $this->order );

			if ( $invoice_number ) {
				$this->number = $invoice_number;
			} else {
				// Reuse a previously reserved number if PDF generation failed on a prior attempt.
				$reserved = (int) $this->order->get_meta( '_wpwing_wcpdf_pending_invoice_number' );
				if ( $reserved > 0 ) {
					$this->number = $reserved;
				} else {
					$this->number = $this->get_new_invoice_number();
					$this->order->update_meta_data( '_wpwing_wcpdf_pending_invoice_number', $this->number );
					$this->order->save_meta_data();
				}
			}

			$filename        = apply_filters( 'wpwing_wcpdf_invoice_filename', '/invoice_' . $this->number, $this );
			$this->save_path = $year . $filename . '.pdf';
			$pdf_path        = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			$this->exists    = true;
			add_action( 'wpwing_wcpdf_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );

			if ( ! file_exists( $pdf_path ) ) {
				$this->exists = false;
				return;
			}

			$this->order->update_meta_data( '_wpwing_wcpdf_invoiced', $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpdf_invoice_number', $this->number );
			$this->order->update_meta_data( '_wpwing_wcpdf_invoice_prefix', $this->prefix );
			$this->order->update_meta_data( '_wpwing_wcpdf_invoice_suffix', $this->suffix );
			$this->order->update_meta_data( '_wpwing_wcpdf_invoice_date', $this->date );
			$this->order->update_meta_data( '_wpwing_wcpdf_invoice_path', $this->save_path );
			$this->order->delete_meta_data( '_wpwing_wcpdf_pending_invoice_number' );

			$this->order->apply_changes();
			$this->order->save_meta_data();
		}

		/**
		 * Returns the localised "From" heading for the invoice header block.
		 *
		 * @return string
		 */
		protected function get_from_label() {
			return esc_html__( 'Invoice From', 'wpwing-wcpdf' );
		}

		/**
		 * Render the billing and shipping address block in the invoice template.
		 */
		public function render_template_customer_data() {

			global $wpwing_wcpdf_document;

			echo '<div class="invoice-to-section">';

			if ( $wpwing_wcpdf_document->order->get_formatted_billing_address() ) {
				echo '<span class="invoice-from-to">' . esc_html__( 'Invoice To', 'wpwing-wcpdf' ) . '</span>';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpdf_document->order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</div>';
			}

			if ( $this->settings->get_option( 'show_shipping_address' ) && $wpwing_wcpdf_document->order->get_formatted_shipping_address() ) {
				echo '<span class="invoice-from-to invoice-ship-to">' . esc_html__( 'Ship To', 'wpwing-wcpdf' ) . '</span>';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpdf_document->order->get_formatted_shipping_address(), array( 'br' => array() ) ) . '</div>';
			}

			echo '</div>';
		}

		/**
		 * Render the order details table in the invoice template.
		 */
		public function render_template_order_data() {

			global $wpwing_wcpdf_document;

			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			?>
			<table>
				<tr class="invoice-number">
					<td><?php esc_html_e( 'Invoice', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_invoice_number() ); ?></td>
				</tr>
				<tr class="invoice-order-number">
					<td><?php esc_html_e( 'Order', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>
				<tr class="invoice-date">
					<td><?php esc_html_e( 'Invoice date', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
				<?php $due_date = $wpwing_wcpdf_document->get_due_date(); if ( $due_date ) : ?>
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
