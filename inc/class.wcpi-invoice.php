<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WCPI_Invoice' ) ) {

	/**
	 * Implements features related to a PDF document
	 *
	 * @class   WCPI_Invoice
	 * @package WPWing
	 * @since   1.0.0
	 */
	class WCPI_Invoice extends WCPI_Document {

		public $document_type = 'invoice';

		public $date;

		private $number;

		private $prefix;

		private $suffix;

		public $save_path;

		/**
		 * Settings API instance
		 *
		 * @var Object
		 */
		public $settings;

		/**
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct( $order_id ) {

			// Call base class constructor
			parent::__construct( $order_id );

			// If this document is not related to a valid WooCommerce order, exit
			if ( ! $this->is_valid ) {
				return;
			}

			// Fill invoice information from a previous invoice is exists or from general plugin options plus order related data
			$this->init_document();
		}

		/**
		 * Check if an invoice exist for current order and load related data
		 *
		 * @since 1.0.0
		 */
		private function init_document() {

			$this->settings = WPWing_WCPI_Settings::get_instance();

			$this->exists = $this->order->get_meta( '_wpwing_wcpi_invoiced' );
			if ( $this->exists ) {
				$this->number = $this->order->get_meta( '_wpwing_wcpi_invoice_number' );
				$this->prefix = $this->order->get_meta( '_wpwing_wcpi_invoice_prefix' );
				$this->suffix = $this->order->get_meta( '_wpwing_wcpi_invoice_suffix' );
				$this->date = $this->order->get_meta( '_wpwing_wcpi_invoice_date' );
				$this->save_path = $this->order->get_meta( '_wpwing_wcpi_invoice_path' );
			} else {
				$this->prefix = $this->settings->get_option( 'invoice_prefix' ) ? $this->settings->get_option( 'invoice_prefix' ) : 'prefix';
				$this->suffix = $this->settings->get_option( 'invoice_suffix' ) ? $this->settings->get_option( 'invoice_suffix' ) : 'suffix';
			}

		}

		/**
		 * Get formatted invoice
		 *
		 * @since 1.0.0
		 */
		public function get_formatted_invoice_number() {

			$formatted_invoice_number = $this->settings->get_option( 'invoice_number_format' );
			if ( ! $formatted_invoice_number ) {
				$formatted_invoice_number = '[prefix]/[number]/[suffix]';
			}

			$formatted_invoice_number = str_replace(
				array( '[prefix]', '[suffix]', '[number]' ),
				array( $this->prefix, $this->suffix, $this->number ),
				$formatted_invoice_number
			);

			return apply_filters( 'wpwing_wcpi_get_formatted_invoice_number', $formatted_invoice_number, $this->order );

		}

		/**
		 * Reset order meta data of invoice
		 *
		 * @since 1.0.0
		 */
		public function reset() {

			$this->order->delete_meta_data( '_wpwing_wcpi_invoiced' );
			$this->order->delete_meta_data( '_wpwing_wcpi_invoice_number' );
			$this->order->delete_meta_data( '_wpwing_wcpi_invoice_prefix' );
			$this->order->delete_meta_data( '_wpwing_wcpi_invoice_suffix' );
			$this->order->delete_meta_data( '_wpwing_wcpi_invoice_date' );
			$this->order->delete_meta_data( '_wpwing_wcpi_invoice_path' );

			$this->order->apply_changes();
			$this->order->save_meta_data();

		}

		/**
		 * Return the next available invoice number
		 *
		 * @since 1.0.0
		 */
		public function get_new_invoice_number() {

			$current_invoice_number = $this->settings->get_option( 'invoice_number' );
			if ( ! isset( $current_invoice_number ) || ! is_numeric( $current_invoice_number ) ) {
				$current_invoice_number = 1;
			}

			return $current_invoice_number;

		}

		/**
		 * Set invoice data for current order, picking the invoice number from the related general option
		 *
		 * @since 1.0.0
		 */
		public function save() {

			// Avoid generating a new invoice from a previous one
			if ( $this->exists ) {
				return;
			}

			$this->date = time();
			$date = getdate( $this->date );
			$year = $date['year'];

			$invoice_number = apply_filters( 'wpwing_wcpi_new_invoice_number', null, $this->order );

			$this->number = $invoice_number ? $invoice_number : $this->get_new_invoice_number();

			$filename = apply_filters( 'wpwing_wcpi_invoice_filename', "/invoice_" . $this->number, $this );
			$this->save_path = $year . $filename . ".pdf";
			$this->exists = true;

			$this->order->update_meta_data( '_wpwing_wcpi_invoiced', $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpi_invoice_number', $this->number );
			$this->order->update_meta_data( '_wpwing_wcpi_invoice_prefix', $this->prefix );
			$this->order->update_meta_data( '_wpwing_wcpi_invoice_suffix', $this->suffix );
			$this->order->update_meta_data( '_wpwing_wcpi_invoice_date', $this->date );
			$this->order->update_meta_data( '_wpwing_wcpi_invoice_path', $this->save_path );

			$this->order->apply_changes();
			$this->order->save_meta_data();

			$pdf_path = WPWING_WCPI_DOCUMENT_SAVE_DIR . $this->save_path;
			add_action( 'wpwing_wcpi_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );
			if ( ! $invoice_number ) {
				// Auto increment the invoice number for next invoice
				$this->settings->set_option( 'invoice_number', $this->number + 1 );
			}

		}

		/**
		 * Reset actions and add new ones related to current document being generated
		 *
		 * @since 1.0.0
		 */
		public function init_template_generation_actions() {

			add_action( 'wpwing_wcpi_invoice_template_company_data', array( $this, 'show_invoice_template_company_data' ) );
			add_action( 'wpwing_wcpi_invoice_template_company_logo', array( $this, 'show_invoice_template_company_logo', ) );
			add_action( 'wpwing_wcpi_invoice_template_customer_data', array( $this, 'show_invoice_template_customer_data', ) );
			add_action( 'wpwing_wcpi_invoice_template_order_data', array( $this, 'show_invoice_template_order_data', ) );
			add_action( 'wpwing_wcpi_invoice_template_product_list', array( $this, 'show_invoice_template_product_list', ) );
			add_action( 'wpwing_wcpi_invoice_template_footer', array( $this, 'show_invoice_template_footer' ) );

		}

		/**
		 * Render and show company data
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_company_data() {

			$company_name = $this->settings->get_option( 'company_name_checkbox' ) ? $this->settings->get_option( 'company_name_text' ) : null;
			$company_details = $this->settings->get_option( 'company_details_checkbox' ) ? nl2br( $this->settings->get_option( 'company_details_text' ) ) : null;


			if ( ! isset( $company_name ) && ! isset( $company_details ) ) {
				return;
			}

			echo '<span class="invoice-from-to">' . __( "Invoice From", 'wpwing-wc-pdf-invoice' ) . ' </span>';
			if ( isset( $company_name ) ) {
				echo '<div class="company-name">' . wp_kses_post( $company_name ) . '</div>';
			}
			if ( isset( $company_details ) ) {
				echo '<div class="company-details" > ' . wp_kses_post( $company_details ) . '</div > ';
			}

		}

		/**
		 * Show company logo
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_company_logo() {

			$company_logo = $this->settings->get_option( 'company_logo_checkbox' ) ? $this->settings->get_option( 'company_logo_upload' ) : null;

			if ( ! isset( $company_logo ) ) {
				return;
			}

			if ( isset( $company_logo ) ) {
				echo '<div class="company-logo">
					<img src="' . apply_filters( 'wpwing_wcpi_company_image_path', esc_url( $company_logo ) ) . '">
				</div>';
			}

		}

		/**
		 * Render and show customer data
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_customer_data() {

			global $wpwing_wcpi_document;

			echo '<div class="invoice-to-section" > ';

			if ( $wpwing_wcpi_document->order->get_formatted_billing_address() ) {
				echo '<span class="invoice-from-to" > ' . __( "Invoice To", 'wpwing-wc-pdf-invoice' ) . '</span > ';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpi_document->order->get_formatted_billing_address(), array( "br" => array() ) ) . '</div>';
			}

			echo '</div > ';

		}

		/**
		 * Render and show order data
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_order_data() {

			global $wpwing_wcpi_document;

			if ( ! isset( $wpwing_wcpi_document ) || ! $wpwing_wcpi_document->exists ) {
				return;
			}
			?>
			<table>
				<tr class="invoice-number">
					<td><?php _e( "Invoice", 'wpwing-wc-pdf-invoice' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpi_document->get_formatted_invoice_number() ); ?></td>
				</tr>

				<tr class="invoice-order-number">
					<td><?php _e( "Order", 'wpwing-wc-pdf-invoice' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpi_document->order->get_order_number() ); ?></td>
				</tr>

				<tr class="invoice-date">
					<td><?php _e( "Invoice date", 'wpwing-wc-pdf-invoice' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpi_document->get_formatted_date() ); ?></td>
				</tr>

				<tr class="invoice-amount">
					<td><?php _e( "Order Amount", 'wpwing-wc-pdf-invoice' ); ?></td>
					<td class="right"><?php echo wc_price( $wpwing_wcpi_document->order->get_total() ); ?></td>
				</tr>
			</table>
			<?php

		}

		/**
		 * Show product list of current order
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_product_list() {

			$theme_dir = WPWING_WCPI_TEMPLATE_DIR . apply_filters( 'wpwing_wcpi_pdf_theme', 'default/' );

			include( $theme_dir . 'invoice/products.php' );

		}

		/**
		 * Show footer information
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_footer() {

			$theme_dir = WPWING_WCPI_TEMPLATE_DIR . apply_filters( 'wpwing_wcpi_pdf_theme', 'default/' );

			if ( $this->settings->get_option( 'company_notes_checkbox' ) ) {
				$notes = $this->settings->get_option( 'company_notes_text' );
			}
			if ( $this->settings->get_option( 'company_footer_checkbox' ) ) {
				$footer = $this->settings->get_option( 'company_footer_text' );
			}

			include( $theme_dir . 'invoice/footer.php' );

		}

	}

}
