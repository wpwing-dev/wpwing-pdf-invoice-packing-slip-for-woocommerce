<?php
/**
 * Abstract PDF document base class.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

use Dompdf\Dompdf;
use Dompdf\Options;

if ( ! class_exists( 'WPWing_WcPdf_Document' ) ) {

	/**
	 * Abstract features related to a PDF document
	 *
	 * @class   WPWing_WcPdf_Document
	 * @package WPWing
	 * @since   1.0.0
	 */
	abstract class WPWing_WcPdf_Document {

		/**
		 * Current document type.
		 *
		 * @var string
		 */
		public $document_type = '';

		/**
		 * Whether a saved document file exists.
		 *
		 * @var bool
		 */
		public $exists = false;

		/**
		 * Current WooCommerce order.
		 *
		 * @var WC_Order
		 */
		public $order;

		/**
		 * Whether the order_id resolved to a valid WooCommerce order.
		 *
		 * @var bool
		 */
		public $is_valid = false;

		/**
		 * Settings instance, set by child classes.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		protected $settings;

		/**
		 * Constructor.
		 *
		 * Initialize class with WooCommerce order object.
		 *
		 * @since 1.0.0
		 * @param int $order_id WooCommerce order ID.
		 */
		public function __construct( $order_id ) {

			$this->settings = WPWing_WcPdf_Settings::get_instance();

			// Get the WooCommerce order for this order id.
			$this->order = wc_get_order( $order_id );

			// Check if an order exists for this order id.
			$this->is_valid = $this->order instanceof WC_Order;
		}

		/**
		 * Resolve the theme directory for this document type.
		 * Falls back to 'default/' if the selected template directory is missing.
		 *
		 * @return string Absolute path with trailing slash.
		 * @since 2.0.0
		 */
		public function get_theme_dir() {

			$settings   = WPWing_WcPdf_Settings::get_instance();
			$option_key = 'invoice' === $this->document_type ? 'invoice_template' : 'packing_template';
			$theme      = $settings->get_option( $option_key );
			$theme_dir  = WPWING_WCPDF_TEMPLATE_DIR . trailingslashit( $theme ? $theme : 'default' );

			if ( ! is_dir( $theme_dir ) ) {
				$theme_dir = WPWING_WCPDF_TEMPLATE_DIR . 'default/';
			}

			return apply_filters( 'wpwing_wcpdf_pdf_theme_dir', $theme_dir, $this->document_type );
		}

		/**
		 * Generate and save PDF invoice file.
		 *
		 * @since 1.0.0
		 * @param string $file_path Absolute path to save the PDF to.
		 */
		public function save_file( $file_path ) {

			$dir = dirname( $file_path );
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}

			try {
				$pdf_content = $this->generate_template();
			} catch ( \Exception $e ) {
				wc_get_logger()->error(
					sprintf( 'WPWing PDF Invoice: PDF generation failed - %s', $e->getMessage() ),
					array( 'source' => 'wpwing-pdf-invoice' )
				);
				set_transient( 'wpwing_wcpdf_pdf_error_' . get_current_user_id(), $e->getMessage(), 60 );
				return;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem not available in this context.
			$bytes = file_put_contents( $file_path, $pdf_content );

			if ( false === $bytes ) {
				$msg = sprintf( 'Failed to write PDF file: %s', basename( $file_path ) );
				wc_get_logger()->error(
					sprintf( 'WPWing PDF Invoice: %s', $msg ),
					array( 'source' => 'wpwing-pdf-invoice' )
				);
				set_transient( 'wpwing_wcpdf_pdf_error_' . get_current_user_id(), $msg, 60 );
			}
		}

		/**
		 * Generate the template
		 *
		 * @since 1.0.0
		 */
		private function generate_template() {

			$this->init_template();

			$theme_dir = $this->get_theme_dir();

			do_action( 'wpwing_wcpdf_before_template_generation' );

			ob_start();
			wc_get_template( 'template.php', null, $theme_dir, $theme_dir );
			$html = ob_get_clean();

			require_once WPWING_WCPDF_VENDOR_DIR . 'autoload.php';

			// Use a writable font cache so Dompdf generates complete .ufm metrics
			// from the full TTF glyph table, covering all currency symbols (Taka,
			// Bitcoin, etc.) that the bundled vendor .ufm files omit.
			$font_cache = trailingslashit( wp_upload_dir()['basedir'] ) . 'wpwing-pdf-fonts/';
			wp_mkdir_p( $font_cache );

			$options = new Options();
			$options->setIsRemoteEnabled( true );
			$options->setFontDir( $font_cache );
			$options->setFontCache( $font_cache );

			$paper_size = WPWing_WcPdf_Settings::get_instance()->get_option( 'paper_size' );
			$dompdf     = new Dompdf( $options );

			// One-time font registration — skipped on every subsequent PDF.
			if ( ! file_exists( $font_cache . 'fonts_ready' ) ) {
				$src     = WPWING_WCPDF_VENDOR_DIR . 'dompdf/dompdf/lib/fonts/';
				$metrics = $dompdf->getFontMetrics();
				$metrics->registerFont(
					array(
						'family' => 'DejaVu Sans',
						'weight' => 'normal',
						'style'  => 'normal',
					),
					'file://' . $src . 'DejaVuSans.ttf'
				);
				$metrics->registerFont(
					array(
						'family' => 'DejaVu Sans',
						'weight' => 'bold',
						'style'  => 'normal',
					),
					'file://' . $src . 'DejaVuSans-Bold.ttf'
				);
				$metrics->registerFont(
					array(
						'family' => 'DejaVu Sans',
						'weight' => 'normal',
						'style'  => 'italic',
					),
					'file://' . $src . 'DejaVuSans-Oblique.ttf'
				);
				$metrics->registerFont(
					array(
						'family' => 'DejaVu Sans',
						'weight' => 'bold',
						'style'  => 'italic',
					),
					'file://' . $src . 'DejaVuSans-BoldOblique.ttf'
				);
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- WP_Filesystem not available in this context.
				file_put_contents( $font_cache . 'fonts_ready', '1' );
			}

			$dompdf->setPaper( $paper_size ? strtolower( $paper_size ) : 'a4' );
			$dompdf->loadHtml( $html );
			$dompdf->render();

			$pdf = $dompdf->output();
			$this->flush_template();

			return $pdf;
		}

		/**
		 * Get formatted date
		 *
		 * @since 1.0.0
		 */
		public function get_formatted_date() {

			$format = apply_filters( 'wpwing_wcpdf_invoice_date_format', $this->settings->get_option( 'invoice_date_format' ) );
			if ( ! $format ) {
				$format = 'd/m/Y';
			}
			$completed = $this->order->get_meta( '_completed_date' );
			$created   = $this->order->get_date_created();
			if ( $completed ) {
				$date = wp_date( $format, strtotime( $completed ) );
			} elseif ( $created ) {
				$date = wp_date( $format, $created->getTimestamp() );
			} else {
				$date = wp_date( $format );
			}

			return $date;
		}

		/**
		 * Initiate invoice template
		 *
		 * @since 1.0.0
		 */
		public function init_template() {

			add_action( 'wpwing_wcpdf_template_head', array( $this, 'add_template_head' ) );
			add_action( 'wpwing_wcpdf_template_content', array( $this, 'add_template_content' ) );
		}

		/**
		 * Flush template hooks
		 *
		 * @since 1.0.0
		 */
		public function flush_template() {

			remove_all_filters( 'wpwing_wcpdf_template_head' );
			remove_all_filters( 'wpwing_wcpdf_template_content' );

			remove_all_filters( 'wpwing_wcpdf_' . $this->document_type . '_template_company_data' );
			remove_all_filters( 'wpwing_wcpdf_' . $this->document_type . '_template_company_logo' );
			remove_all_filters( 'wpwing_wcpdf_' . $this->document_type . '_template_customer_data' );
			remove_all_filters( 'wpwing_wcpdf_' . $this->document_type . '_template_order_data' );
			remove_all_filters( 'wpwing_wcpdf_' . $this->document_type . '_template_product_list' );
			remove_all_filters( 'wpwing_wcpdf_' . $this->document_type . '_template_footer' );
		}

		/**
		 * Add style from css file
		 *
		 * @since 1.0.0
		 */
		public function add_template_head() {

			$theme_dir         = $this->get_theme_dir();
			$template_filename = $this->document_type . '/style.css';
			$template_path     = $theme_dir . $template_filename;
			if ( file_exists( $template_path ) ) {
				ob_start();
				wc_get_template( $template_filename, null, $theme_dir, $theme_dir );
				$content = ob_get_clean();

				if ( $content ) {
					echo '<style type="text/css">';
					echo esc_html( $content );
					echo '</style>';
				}
			}
		}

		/**
		 * Add content from order object
		 *
		 * @since 1.0.0
		 */
		public function add_template_content() {

			global $wpwing_wcpdf_document;
			$theme_dir         = $this->get_theme_dir();
			$template_filename = $this->document_type . '/index.php';
			$template_path     = $theme_dir . $template_filename;

			if ( file_exists( $template_path ) ) {
				wc_get_template( $template_filename, array( $wpwing_wcpdf_document ), $theme_dir, $theme_dir );
			}
		}

		// -------------------------------------------------------------------------
		// Template section rendering — shared across all document types
		// -------------------------------------------------------------------------

		/**
		 * Returns the localised "From" heading shown in the company header block.
		 * Each document type provides its own label (e.g. "Invoice From").
		 */
		abstract protected function get_from_label();

		/**
		 * Persist document metadata to order meta.
		 */
		abstract public function save();

		/**
		 * Remove document metadata from order meta.
		 */
		abstract public function reset();

		/**
		 * Registers the six template-section hooks for this document type.
		 * Hook names are built dynamically from $this->document_type so both
		 * 'invoice' and 'packing' resolve correctly without duplication.
		 *
		 * @since 2.0.0
		 */
		public function init_template_generation_actions() {

			$type = $this->document_type;
			add_action( "wpwing_wcpdf_{$type}_template_company_data", array( $this, 'render_template_company_data' ) );
			add_action( "wpwing_wcpdf_{$type}_template_company_logo", array( $this, 'render_template_company_logo' ) );
			add_action( "wpwing_wcpdf_{$type}_template_customer_data", array( $this, 'render_template_customer_data' ) );
			add_action( "wpwing_wcpdf_{$type}_template_order_data", array( $this, 'render_template_order_data' ) );
			add_action( "wpwing_wcpdf_{$type}_template_product_list", array( $this, 'render_template_product_list' ) );
			add_action( "wpwing_wcpdf_{$type}_template_footer", array( $this, 'render_template_footer' ) );
		}

		/**
		 * Render the company sender block (name + contact details).
		 */
		public function render_template_company_data() {

			$company_name = $this->settings->get_option( 'company_name_checkbox' ) ? $this->settings->get_option( 'company_name_text' ) : null;
			$show_details = (bool) $this->settings->get_option( 'company_details_checkbox' );

			if ( ! $company_name && ! $show_details ) {
				return;
			}

			echo '<span class="invoice-from-to">' . esc_html( $this->get_from_label() ) . '</span>';

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
					echo '<div>' . esc_html( $address ) . '</div>'; }
				$city_line = trim( $zip . ' ' . $city );
				if ( $city_line ) {
					echo '<div>' . esc_html( $city_line ) . '</div>'; }
				if ( $country ) {
					echo '<div>' . esc_html( $country ) . '</div>'; }
				if ( $phone ) {
					echo '<div>' . esc_html__( 'Tel:', 'wpwing-wcpdf' ) . ' ' . esc_html( $phone ) . '</div>'; }
				if ( $email ) {
					echo '<div>' . esc_html__( 'Email:', 'wpwing-wcpdf' ) . ' ' . esc_html( $email ) . '</div>'; }
				if ( $vat ) {
					echo '<div>' . esc_html__( 'VAT:', 'wpwing-wcpdf' ) . ' ' . esc_html( $vat ) . '</div>'; }
				echo '</div>';
			}
		}

		/**
		 * Render the company logo.
		 */
		public function render_template_company_logo() {

			$company_logo = $this->settings->get_option( 'company_logo_checkbox' ) ? $this->settings->get_option( 'company_logo_upload' ) : null;

			if ( ! $company_logo ) {
				return;
			}

			echo '<div class="company-logo"><img src="' . esc_url( apply_filters( 'wpwing_wcpdf_company_image_path', $company_logo ) ) . '"></div>';
		}

		/**
		 * Render the product list for this document type.
		 * Path resolves to templates/{theme}/{document_type}/products.php.
		 */
		public function render_template_product_list() {

			$file = $this->get_theme_dir() . $this->document_type . '/products.php';
			if ( file_exists( $file ) ) {
				include $file;
			}
		}

		/**
		 * Render footer notes and text.
		 * Path resolves to templates/{theme}/{document_type}/footer.php.
		 */
		public function render_template_footer() {

			$theme_dir = $this->get_theme_dir();
			$notes     = $this->settings->get_option( 'company_notes_checkbox' ) ? $this->settings->get_option( 'company_notes_text' ) : null;
			$footer    = $this->settings->get_option( 'company_footer_checkbox' ) ? $this->settings->get_option( 'company_footer_text' ) : null;

			$file = $theme_dir . $this->document_type . '/footer.php';
			if ( file_exists( $file ) ) {
				include $file;
			}
		}

		/**
		 * Render the customer/recipient address block.
		 * Implemented differently per document type.
		 */
		abstract public function render_template_customer_data();

		/**
		 * Render the document metadata table (number, date, amounts, etc.).
		 * Implemented differently per document type.
		 */
		abstract public function render_template_order_data();
	}
}
