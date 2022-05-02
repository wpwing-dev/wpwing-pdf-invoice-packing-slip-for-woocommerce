<?php

defined( 'ABSPATH' ) || exit;

use Dompdf\Dompdf;
use Dompdf\Options;

if ( ! class_exists( 'WCPI_Document' ) ) {

	/**
	 * Abstract features related to a PDF document
	 *
	 * @class   WCPI_Document
	 * @package WPWing
	 * @since   1.0.0
	 */
	abstract class WCPI_Document {

		/**
		 * @var string Current document type
		 */
		public $document_type = '';

		/**
		 * @var bool If a document type exists
		 */
		public $exists = false;

		/**
		 * @var WC_Order Current order
		 */
		public $order;

		/**
		 * @var bool If this document is a valid WooCommerce order
		 */
		public $is_valid = false;

		/**
		 * Constructor
		 *
		 * Initialize class with WooCommerce order object
		 *
		 * @since  1.0.0
		 */
		public function __construct( $order_id ) {

			// Get the WooCommerce order for this order id
			$this->order = wc_get_order( $order_id );

			// Check if an order exists for this order id
			$this->is_valid = isset( $this->order );

		}

		/**
		 * Generate and save PDF invoice file
		 *
		 * @since 1.0.0
		 */
		public function save_file( $file_path ) {

			$pdf_content = $this->generate_template();
			file_put_contents( $file_path, $pdf_content );

		}

		/**
		 * Generate the template
		 *
		 * @since 1.0.0
		 */
		private function generate_template() {

			$this->init_template();

			$theme_dir = WPWING_WCPI_TEMPLATE_DIR . apply_filters( 'wpwing_wcpi_pdf_theme', 'default/' );

			do_action( 'wpwing_wcpi_before_template_generation' );

			ob_start();
			wc_get_template( 'template.php', null, $theme_dir, $theme_dir );
			$html = ob_get_contents();
			ob_end_clean();

			require_once( WPWING_WCPI_VENDOR_DIR . 'autoload.php' );

			$options = new Options();
			$options->setIsRemoteEnabled( true );

			$dompdf = new Dompdf();
			$dompdf->setOptions( $options );
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

      $format = apply_filters( 'wpwing_wcpi_invoice_date_format', $this->settings->get_option( 'invoice_date_format' ) );
			if ( ! $format) {
				$format = 'd/m/Y';
			}
			$date = $this->order->get_meta( '_completed_date' ) ? date( $format, strtotime( $this->order->get_meta( '_completed_date' ) ) ) : date( $format, $this->order->get_date_created()->getTimestamp() );

			return $date;

		}

		/**
		 * Initiate invoice template
		 *
		 * @since 1.0.0
		 */
		public function init_template() {

			add_action( 'wpwing_wcpi_template_head', array( $this, 'add_template_head' ) );
			add_action( 'wpwing_wcpi_template_content', array( $this, 'add_template_content' ) );

		}

		/**
		 * Flush template hooks
		 *
		 * @since 1.0.0
		 */
		public function flush_template() {

			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_head' );
			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_content' );

			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_company_data' );
			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_company_logo' );
			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_customer_data' );
			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_order_data' );
			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_product_list' );
			remove_all_filters( 'wpwing_wcpi_' . $this->document_type . '_template_footer' );

		}

		/**
		 * Add style from css file
		 *
		 * @since 1.0.0
		 */
		public function add_template_head() {

			$theme_dir = WPWING_WCPI_TEMPLATE_DIR . apply_filters( 'wpwing_wcpi_pdf_theme', 'default/' );
			$template_filename = $this->document_type . '/style.css';
      $template_path = $theme_dir . $template_filename;
			if ( file_exists( $template_path ) ) {
				ob_start();
				wc_get_template( $template_filename, null, $theme_dir, $theme_dir );
				$content = ob_get_contents();
				ob_end_clean();

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

			global $wpwing_wcpi_document;
			$theme_dir = WPWING_WCPI_TEMPLATE_DIR . apply_filters( 'wpwing_wcpi_pdf_theme', 'default/' );
			$template_filename = $this->document_type . '/index.php';
			$template_path = $theme_dir . $template_filename;

			if ( file_exists( $template_path ) ) {
				wc_get_template( $template_filename, array( $wpwing_wcpi_document ), $theme_dir, $theme_dir );
			}

		}
	}
}
