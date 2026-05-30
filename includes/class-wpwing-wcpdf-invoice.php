<?php

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

			$this->settings = WPWing_WcPdf_Settings::get_instance();

			$this->exists = $this->order->get_meta( '_wpwing_wcpdf_invoiced' );
			if ( $this->exists ) {
				$this->number = $this->order->get_meta( '_wpwing_wcpdf_invoice_number' );
				$this->prefix = $this->order->get_meta( '_wpwing_wcpdf_invoice_prefix' );
				$this->suffix = $this->order->get_meta( '_wpwing_wcpdf_invoice_suffix' );
				$this->date = $this->order->get_meta( '_wpwing_wcpdf_invoice_date' );
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_invoice_path' );
			} else {
				$prefix       = $this->settings->get_option( 'invoice_prefix' );
				$this->prefix = $prefix ? $prefix : 'prefix';
				$suffix       = $this->settings->get_option( 'invoice_suffix' );
				$this->suffix = $suffix ? $suffix : 'suffix';
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
				$this->number,   // legacy [number]
				$this->prefix,   // legacy [prefix]
				$this->suffix,   // legacy [suffix]
			);

			return apply_filters( 'wpwing_wcpdf_get_formatted_invoice_number', str_replace( $search, $replace, $format ), $this->order );

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

			$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 5)', 'wpwing_wcpdf_invoice_number' ) );

			$current = (int) $this->settings->get_option( 'invoice_number' );
			if ( $current < 1 ) {
				$current = 1;
			}

			$this->settings->set_option( 'invoice_number', $current + 1 );

			$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', 'wpwing_wcpdf_invoice_number' ) );

			return $current;

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

			$invoice_number = apply_filters( 'wpwing_wcpdf_new_invoice_number', null, $this->order );

			$this->number = $invoice_number ? $invoice_number : $this->get_new_invoice_number();

			$filename = apply_filters( 'wpwing_wcpdf_invoice_filename', "/invoice_" . $this->number, $this );
			$this->save_path = $year . $filename . ".pdf";
			$pdf_path = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			$this->exists = true;
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

			$this->order->apply_changes();
			$this->order->save_meta_data();

		}

		/**
		 * Reset actions and add new ones related to current document being generated
		 *
		 * @since 1.0.0
		 */
		public function init_template_generation_actions() {

			add_action( 'wpwing_wcpdf_invoice_template_company_data', array( $this, 'show_invoice_template_company_data' ) );
			add_action( 'wpwing_wcpdf_invoice_template_company_logo', array( $this, 'show_invoice_template_company_logo', ) );
			add_action( 'wpwing_wcpdf_invoice_template_customer_data', array( $this, 'show_invoice_template_customer_data', ) );
			add_action( 'wpwing_wcpdf_invoice_template_order_data', array( $this, 'show_invoice_template_order_data', ) );
			add_action( 'wpwing_wcpdf_invoice_template_product_list', array( $this, 'show_invoice_template_product_list', ) );
			add_action( 'wpwing_wcpdf_invoice_template_footer', array( $this, 'show_invoice_template_footer' ) );

		}

		/**
		 * Render and show company data
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_company_data() {

			$company_name    = $this->settings->get_option( 'company_name_checkbox' ) ? $this->settings->get_option( 'company_name_text' ) : null;
			$show_details    = (bool) $this->settings->get_option( 'company_details_checkbox' );

			if ( ! $company_name && ! $show_details ) {
				return;
			}

			echo '<span class="invoice-from-to">' . esc_html__( 'Invoice From', 'wpwing-wcpdf' ) . '</span>';

			if ( $company_name ) {
				echo '<div class="company-name">' . esc_html( $company_name ) . '</div>';
			}

			if ( $show_details ) {
				$address = $this->settings->get_option( 'company_address' );
				$city    = $this->settings->get_option( 'company_city' );
				$zip     = $this->settings->get_option( 'company_zip' );
				$country = $this->settings->get_option( 'company_country' );
				$phone   = $this->settings->get_option( 'company_phone' );
				$email   = $this->settings->get_option( 'company_email' );
				$vat     = $this->settings->get_option( 'company_vat' );

				echo '<div class="company-details">';
				if ( $address ) {
					echo '<div>' . esc_html( $address ) . '</div>';
				}
				$city_line = trim( $zip . ' ' . $city );
				if ( $city_line ) {
					echo '<div>' . esc_html( $city_line ) . '</div>';
				}
				if ( $country ) {
					echo '<div>' . esc_html( $country ) . '</div>';
				}
				if ( $phone ) {
					echo '<div>' . esc_html__( 'Tel:', 'wpwing-wcpdf' ) . ' ' . esc_html( $phone ) . '</div>';
				}
				if ( $email ) {
					echo '<div>' . esc_html__( 'Email:', 'wpwing-wcpdf' ) . ' ' . esc_html( $email ) . '</div>';
				}
				if ( $vat ) {
					echo '<div>' . esc_html__( 'VAT:', 'wpwing-wcpdf' ) . ' ' . esc_html( $vat ) . '</div>';
				}
				echo '</div>';
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
					<img src="' . apply_filters( 'wpwing_wcpdf_company_image_path', esc_url( $company_logo ) ) . '">
				</div>';
			}

		}

		/**
		 * Render and show customer data
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_customer_data() {

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
		 * Render and show order data
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_order_data() {

			global $wpwing_wcpdf_document;

			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			?>
			<table>
				<tr class="invoice-number">
					<td><?php _e( "Invoice", 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_invoice_number() ); ?></td>
				</tr>

				<tr class="invoice-order-number">
					<td><?php _e( "Order", 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>

				<tr class="invoice-date">
					<td><?php _e( "Invoice date", 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>

				<tr class="invoice-amount">
					<td><?php _e( "Order Amount", 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo wc_price( $wpwing_wcpdf_document->order->get_total() ); ?></td>
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

			$theme_dir = $this->get_theme_dir();
			$file      = $theme_dir . 'invoice/products.php';

			if ( file_exists( $file ) ) {
				include( $file );
			}

		}

		/**
		 * Show footer information
		 *
		 * @since 1.0.0
		 */
		public function show_invoice_template_footer() {

			$theme_dir = $this->get_theme_dir();
			$notes     = null;
			$footer    = null;

			if ( $this->settings->get_option( 'company_notes_checkbox' ) ) {
				$notes = $this->settings->get_option( 'company_notes_text' );
			}
			if ( $this->settings->get_option( 'company_footer_checkbox' ) ) {
				$footer = $this->settings->get_option( 'company_footer_text' );
			}

			$file = $theme_dir . 'invoice/footer.php';
			if ( file_exists( $file ) ) {
				include( $file );
			}

		}

	}

}
