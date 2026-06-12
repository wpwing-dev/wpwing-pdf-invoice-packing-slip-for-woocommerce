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


		protected function get_from_label() {
			return esc_html__( 'Packing From', 'wpwing-wcpdf' );
		}

		public function render_template_customer_data() {

			global $wpwing_wcpdf_document;

			echo '<div class="invoice-to-section">';

			if ( $wpwing_wcpdf_document->order->get_formatted_billing_address() ) {
				echo '<span class="invoice-from-to">' . esc_html__( 'Customer', 'wpwing-wcpdf' ) . '</span>';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpdf_document->order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</div>';
			}

			echo '</div>';

		}

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
					<td><?php esc_html_e( 'Packing Date', 'wpwing-wcpdf' ); ?></td>
					<td class="right"><?php echo esc_html( $wpwing_wcpdf_document->get_formatted_date() ); ?></td>
				</tr>
			</table>
			<?php

		}

	}
}
