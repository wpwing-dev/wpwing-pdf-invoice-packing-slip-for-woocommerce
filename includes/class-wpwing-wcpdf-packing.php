<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Packing' ) ) {

	/**
	 * Implements features related to a PDF document
	 *
	 * @class   WPWing_WcPdf_Packing
	 * @package WPWing
	 * @since   1.0.0
	 */
	class WPWing_WcPdf_Packing extends WPWing_WcPdf_Document {

		/**
		 * Document type
		 *
		 * @var string
		 */
		public $document_type = 'packing';

		/**
		 * Order Number
		 *
		 * @var string
		 */
		public $number;

		/**
		 * Save path
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
		 * Constructor
		 *
		 * Initialize plugin and registers actions and filters to be used
		 *
		 * @since  1.0.0
		 */
		public function __construct( $order_id ) {

			// Call base class constructor
			parent::__construct( $order_id );

			// If this document is not related to a valid WooCommerce order, exit.
			if ( ! $this->is_valid ) {
				return;
			}

			$this->number = $order_id;

			// Fill invoice information from a previous invoice is exists or from general plugin options plus order related data
			$this->init_document();

		}

		/**
		 * Check if a packing slip exist for current order and load related data
		 *
		 * @since 1.0.0
		 */
		private function init_document() {

			$this->settings = WPWing_WcPdf_Settings::get_instance();

			$this->exists = $this->order->get_meta( '_wpwing_wcpdf_packing' );

			if ( $this->exists ) {
				$this->save_path = $this->order->get_meta( '_wpwing_wcpdf_packing_path' );
			}

		}

		/**
		 * Reset order meta data of packing slip
		 *
		 * @since 1.0.0
		 */
		public function reset() {

			$this->order->delete_meta_data( '_wpwing_wcpdf_packing' );
			$this->order->delete_meta_data( '_wpwing_wcpdf_packing_path' );

			$this->order->apply_changes();
			$this->order->save_meta_data();

		}

		/**
		 * Set packing slip data for current order, picking the invoice number from the related general option
		 *
		 * @since 1.0.0
		 */
		public function save() {

			// Avoid generating a new document if a previous one still exists.
			if ( $this->exists ) {
				return;
			}

			$this->date = time();
			$date = getdate( $this->date );
			$year = $date['year'];

			$filename = apply_filters( 'wpwing_wcpdf_packing_filename', "/packing_" . $this->number, $this );
			$this->save_path = $year . $filename . ".pdf";
			$pdf_path = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $this->save_path;
			$this->exists = true;
			add_action( 'wpwing_wcpdf_before_template_generation', array( $this, 'init_template_generation_actions' ) );
			$this->save_file( $pdf_path );

			if ( ! file_exists( $pdf_path ) ) {
				$this->exists = false;
				return;
			}

			$this->order->update_meta_data( '_wpwing_wcpdf_packing', $this->exists );
			$this->order->update_meta_data( '_wpwing_wcpdf_packing_path', $this->save_path );

			$this->order->apply_changes();
			$this->order->save_meta_data();

		}


		/**
		 * Reset actions and add new ones related to current document being generated
		 *
		 * @since 1.0.0
		 */
		public function init_template_generation_actions() {

			add_action( 'wpwing_wcpdf_packing_template_company_data', array( $this, 'show_packing_template_company_data' ) );
			add_action( 'wpwing_wcpdf_packing_template_company_logo', array( $this, 'show_packing_template_company_logo', ) );
			add_action( 'wpwing_wcpdf_packing_template_customer_data', array( $this, 'show_packing_template_customer_data', ) );
			add_action( 'wpwing_wcpdf_packing_template_order_data', array( $this, 'show_packing_template_order_data', ) );
			add_action( 'wpwing_wcpdf_packing_template_product_list', array( $this, 'show_packing_template_product_list', ) );
			add_action( 'wpwing_wcpdf_packing_template_footer', array( $this, 'show_packing_template_footer' ) );

		}

		/**
		 * Render and show company data
		 *
		 * @since 1.0.0
		 */
		public function show_packing_template_company_data() {

			$company_name = $this->settings->get_option( 'company_name_checkbox' ) ? $this->settings->get_option( 'company_name_text' ) : null;
			$show_details = (bool) $this->settings->get_option( 'company_details_checkbox' );

			if ( ! $company_name && ! $show_details ) {
				return;
			}

			echo '<span class="invoice-from-to">' . esc_html__( 'Packing From', 'wpwing-wcpdf' ) . '</span>';

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
		public function show_packing_template_company_logo() {

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
		public function show_packing_template_customer_data() {

			global $wpwing_wcpdf_document;

			echo '<div class="invoice-to-section" > ';

			if ( $wpwing_wcpdf_document->order->get_formatted_billing_address() ) {
				echo '<span class="invoice-from-to" > ' . __( "Customer", 'wpwing-wcpdf' ) . '</span > ';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpdf_document->order->get_formatted_billing_address(), array( "br" => array() ) ) . '</div>';
			}

			echo '</div > ';

		}

		/**
		 * Render and show order data
		 *
		 * @since 1.0.0
		 */
		public function show_packing_template_order_data() {

			global $wpwing_wcpdf_document;

			if ( ! isset( $wpwing_wcpdf_document ) || ! $wpwing_wcpdf_document->exists ) {
				return;
			}
			?>
			<table>
				<tr class="invoice-order-number">
					<td><?php _e( "Order Number", 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->order->get_order_number() ); ?></td>
				</tr>

				<tr class="invoice-date">
					<td><?php _e( "Invoice Date", 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
			</table>
			<?php

		}

		/**
		 * Show product list of current order
		 *
		 * @since 1.0.0
		 */
		public function show_packing_template_product_list() {

			$theme_dir = $this->get_theme_dir();
			$file      = $theme_dir . 'packing/products.php';

			if ( file_exists( $file ) ) {
				include( $file );
			}

		}

		/**
		 * Show footer information
		 *
		 * @since 1.0.0
		 */
		public function show_packing_template_footer() {

			$theme_dir = $this->get_theme_dir();
			$notes     = null;
			$footer    = null;

			if ( $this->settings->get_option( 'company_notes_checkbox' ) ) {
				$notes = $this->settings->get_option( 'company_notes_text' );
			}
			if ( $this->settings->get_option( 'company_footer_checkbox' ) ) {
				$footer = $this->settings->get_option( 'company_footer_text' );
			}

			$file = $theme_dir . 'packing/footer.php';
			if ( file_exists( $file ) ) {
				include( $file );
			}

		}

	}
}
