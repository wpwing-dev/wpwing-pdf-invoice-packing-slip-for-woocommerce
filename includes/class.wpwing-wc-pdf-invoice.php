<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WC_Pdf_Invoice' ) ) {

	/**
	 * Implements features of WPWing WC Pdf Invoice
	 *
	 * @class   WPWing_WC_Pdf_Invoice
	 * @package WPWing
	 * @since   1.0.0
	 */
	class WPWing_WC_Pdf_Invoice {

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
		public function __construct() {
			// Plugin will handel this actions
			add_action( 'init', array( $this, 'init_plugin_actions' ) );

			$this->initialize();

			// Add metabox in admin order page
			add_action( 'add_meta_boxes', array( $this, 'add_invoice_metabox' ) );

			//  Add stylesheets and scripts files to back-end
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			// Add a create/view invoice button on admin orders page
			// add_action( 'woocommerce_admin_order_actions_end', array( $this, 'add_back_end_invoice_buttons' ) );

			add_filter( 'woocommerce_my_account_my_orders_actions', [ $this, 'filter_woocommerce_my_account_my_orders_actions'], 10, 2 );
		}

		/**
		 * Add Invoice download link in my account > order section
		 *
		 * @since 1.3.1
		 */
		public function filter_woocommerce_my_account_my_orders_actions( $actions, $order ) {
			$invoice = $this->get_document_by_type( $order->get_id(), 'invoice' );
			if ( ( null != $invoice ) && $invoice->exists ) :
				?>
				<div style="clear: both;">
					<a class="button tips wpwing_wcpi_view_invoice" data-tip="<?php _e( "View invoice", 'wpwing-wc-pdf-invoice' ); ?>" href="<?php echo add_query_arg( 'wpwing-view-invoice', $invoice->order->get_id() ); ?>"><?php _e( "Invoice", 'wpwing-wc-pdf-invoice' ); ?></a>
				</div>
				<?php
			endif;
		}

		/**
		 * Add the right action based on GET var current used
		 *
		 * @since 1.0.0
		 */
		public function init_plugin_actions() {
			if ( isset( $_GET['wpwing-create-invoice'] ) ) {
				$this->create_document( intval( $_GET['wpwing-create-invoice'] ), 'invoice' );
			} elseif ( isset( $_GET[ 'wpwing-view-invoice' ] ) ) {
				$this->view_document( intval( $_GET[ 'wpwing-view-invoice' ] ), 'invoice' );
			} elseif ( isset( $_GET[ 'wpwing-reset-invoice' ] ) ) {
				$this->reset_document( intval( $_GET[ 'wpwing-reset-invoice' ] ), 'invoice' );
			} elseif ( isset( $_GET['wpwing-create-packing'] ) ) {
				$this->create_document( intval( $_GET['wpwing-create-packing'] ), 'packing' );
			} elseif ( isset( $_GET[ 'wpwing-view-packing' ] ) ) {
				$this->view_document( intval( $_GET[ 'wpwing-view-packing' ] ), 'packing' );
			} elseif ( isset( $_GET[ 'wpwing-reset-packing' ] ) ) {
				$this->reset_document( intval( $_GET[ 'wpwing-reset-packing' ] ), 'packing' );
			} else {
				return;
			}

			if ( is_admin() && isset( $_SERVER['HTTP_REFERER'] ) ) {
				$location = $_SERVER['HTTP_REFERER'];
				wp_safe_redirect( $location );
				exit();
			}
		}

		/**
		 * Check file exist
		 *
		 * @since 1.0.0
		 */
		public function initialize() {
			$date = getdate( time() );
			$year = $date['year'];

			if ( ! file_exists( WPWING_WCPI_DOCUMENT_SAVE_DIR ) ) {
				wp_mkdir_p( WPWING_WCPI_DOCUMENT_SAVE_DIR );
			}

			if ( ! file_exists( WPWING_WCPI_DOCUMENT_SAVE_DIR . $year ) ) {
				wp_mkdir_p( WPWING_WCPI_DOCUMENT_SAVE_DIR . $year );
			}

			$this->settings = WPWing_WCPI_Settings::get_instance();
		}

		/**
		 * Add a metabox on backend order page, to be filled with order tracking information
		 *
		 * @since  1.0.0
		 */
		public function add_invoice_metabox() {
			add_meta_box( 'wpwing-pdf-invoice-box', esc_html__( 'PDF Invoice by WPWing', 'wpwing-wc-pdf-invoice' ), array( $this, 'show_pdf_invoice_metabox', ), 'shop_order', 'side', 'high' );
		}

		/**
		 * Show metabox content for tracking information on backend order page
		 *
		 * @param WP_Post $post the order object that is currently shown
		 *
		 * @since  1.0.0
		 */
		public function show_pdf_invoice_metabox( $post ) {
			$invoice = $this->get_document_by_type( $post->ID, 'invoice' );
			$packing = $this->get_document_by_type( $post->ID, 'packing' );
			?>
			<div class="invoice-information">
				<?php if ( ( null != $invoice ) && $invoice->exists ) : ?>
					<div style="overflow: hidden; padding: 5px 0">
						<span style="float:left"><?php _e( 'Invoiced on: ', 'wpwing-wc-pdf-invoice' ); ?></span>
						<strong><span style="float:right"><?php echo esc_html( $invoice->get_formatted_date() ); ?></span></strong>
					</div>

					<div style="overflow: hidden; padding: 5px 0">
						<span style="float:left"><?php _e( 'Invoice number: ', 'wpwing-wc-pdf-invoice' ); ?></span>
						<strong><span style="float:right"><?php echo esc_html( $invoice->get_formatted_invoice_number() ); ?></span></strong>
					</div>

					<div style="clear: both; margin-top: 15px">
						<a class="button tips wpwing_wcpi_view_invoice" data-tip="<?php _e( "View Invoice", 'wpwing-wc-pdf-invoice' ); ?>" href="<?php echo add_query_arg( 'wpwing-view-invoice', $invoice->order->get_id() ); ?>" target=”_blank”><?php _e( "Invoice", 'wpwing-wc-pdf-invoice' ); ?></a>
						<a class="button tips wpwing_wcpi_cancel_invoice" data-tip="<?php _e( "Cancel Invoice", 'wpwing-wc-pdf-invoice' ); ?>" href="<?php echo add_query_arg( 'wpwing-reset-invoice', $invoice->order->get_id() ); ?>"><?php _e( "Invoice", 'wpwing-wc-pdf-invoice' ); ?></a>
					</div>
				<?php else : ?>
					<p>
						<a class="button tips wpwing_wcpi_create_invoice" data-tip="<?php _e( "Create invoice", 'wpwing-wc-pdf-invoice' ); ?>" href="<?php echo add_query_arg( 'wpwing-create-invoice', $invoice->order->get_id() ); ?>"><?php _e( "Invoice", 'wpwing-wc-pdf-invoice' ); ?></a>
					</p>
				<?php endif; ?>

				<?php if ( ( null != $packing ) && $packing->exists ) : ?>
					<div style="clear: both; margin-top: 15px">
						<a class="button tips wpwing_wcpi_view_invoice" data-tip="<?php _e( "View Packing Slip", 'wpwing-wc-pdf-invoice' ); ?>" href="<?php echo add_query_arg( 'wpwing-view-packing', $packing->order->get_id() ); ?>" target=”_blank”><?php _e( "Packing Slip", 'wpwing-wc-pdf-invoice' ); ?></a>
						<a class="button tips wpwing_wcpi_cancel_invoice" data-tip="<?php _e( "Cancel Packing Slip", 'wpwing-wc-pdf-invoice' ); ?>" href="<?php echo add_query_arg( 'wpwing-reset-packing', $packing->order->get_id() ); ?>"><?php _e( "Packing Slip", 'wpwing-wc-pdf-invoice' ); ?></a>
					</div>
				<?php else : ?>
					<p>
						<a class="button tips wpwing_wcpi_create_invoice" data-tip="<?php _e( "Create Packing Slip", 'wpwing-wc-pdf-invoice' ); ?>" href="<?php echo add_query_arg( 'wpwing-create-packing', $packing->order->get_id() ); ?>"><?php _e( "Packing Slip", 'wpwing-wc-pdf-invoice' ); ?></a>
					</p>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Enqueue css file
		 *
		 * @since  1.0.0
		 */
		public function enqueue_styles() {
			wp_enqueue_style( 'wcpi-admin-css', WPWING_WCPI_ASSETS_URL . '/public/css/admin.css' );
		}

		/**
		 * Enqueue js file
		 *
		 * @since  1.0.0
		 */
		public function enqueue_scripts() {
			if ( ! did_action( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}

			wp_register_script( 'wcpi-admin-js', WPWING_WCPI_ASSETS_URL . '/public/js/admin.js', array(
				'jquery',
				'jquery-ui-sortable'
			), WPWING_WCPI_VERSION, true );

			wp_localize_script( 'wcpi-admin-js', 'wpwing_wcpi_object', apply_filters( 'wpwing_wcpi_admin_localize', array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'ajax_loader' => 'wcpi-admin-css', WPWING_WCPI_ASSETS_URL . '/images/ajax-loader.gif',
				'logo_message_1' => esc_html__( "The logo your uploading is ", 'wpwing-wc-pdf-invoice' ),
				'logo_message_2' => esc_html__( ". Logo must be no bigger than 300 x 150 pixels", 'wpwing-wc-pdf-invoice' ),
			) ) );

			wp_enqueue_script( 'wcpi-admin-js' );
		}

		/**
		 * Return a new document of the type requested, for a specific order
		 *
		 * @param        $order_id      the order id for which the document is created
		 * @param string $document_type the document type to be return
		 *
		 * @return object
		 * @since 1.0.0
		 */
		public function get_document_by_type( $order_id, $document_type = '' ) {
			switch ( $document_type ) {
				case 'invoice' :
					$document = new WCPI_Invoice( $order_id );
					break;
				case 'packing' :
					$document = new WCPI_Packing( $order_id );
					break;
				default:
					return null;
			}

			return $document;
		}

		/**
		 * Create a new document of the type requested, for a specific order
		 *
		 * @param        $order_id      the order id for which the document is created
		 * @param string $document_type the document type to be generated
		 *
		 * @since 1.0.0
		 */
		public function create_document( $order_id, $document_type = '' ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null != $document ) {
				$this->save_document( $document );
			}

			// Send invoice to customer billing email address using wc_mail
			if ( $this->settings->get_option( 'invoice_send_customer' ) ) {
				$order = new WC_Order( $order_id );
				$to = $order->get_billing_email();
				$subject = __( 'Order Invoice (PDF)', 'wpwing-wc-pdf-invoice' );
				$message = __( 'Dear Customer, Here is your order invoice. Please check the attachment.', 'wpwing-wc-pdf-invoice' );
				$headers = array( 'Content-Type: text/html; charset=UTF-8' );
				$attachment = WPWING_WCPI_DOCUMENT_SAVE_DIR . $order->get_meta( '_wpwing_wcpi_invoice_path' );
				wc_mail( $to, $subject, $message, $headers, $attachment );
			}
		}

		/**
     * Generate the PDF when you click on view invoice
		 *
		 * @param        $order_id      the order id for which the document is created
		 * @param string $document_type the document type to be generated
		 *
		 * @since 1.0.0
		 */
		public function view_document( $order_id, $document_type ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null != $document ) {
				$full_path = WPWING_WCPI_DOCUMENT_SAVE_DIR . $document->save_path;
				// Check if show pdf invoice on browser or asking to download it
				$button_behavior = $this->settings->get_option( 'invoice_button_behavior' );
				if ( 'open' == $button_behavior ) {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: inline; filename = "' . basename( $full_path ) . '"' );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $full_path ) );
					header( 'Accept-Ranges: bytes' );
					@readfile( $full_path );
					exit();
				} else {
					header( "Content-type: application/pdf" );
					header( 'Content-Disposition: attachment; filename = "' . basename( $full_path ) . '"' );
					@readfile( $full_path );
				}
			}
		}

		/**
         * Reset the PDF when you click on view invoice
		 *
		 * @param        $order_id      the order id for which the document is created
		 * @param string $document_type the document type to be generated
		 *
		 * @since 1.0.0
     */
		public function reset_document( $order_id, $document_type ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null != $document ) {
				$document->reset();
			}
		}

		/**
		 * Save a PDF file starting from a previously created document
		 *
		 * @param string $document_type the document type to be generated
		 *
		 * @since 1.0.0
		 */
		public function save_document( $document ) {
			global $wpwing_wcpi_document;
			$wpwing_wcpi_document = $document;
			$wpwing_wcpi_document->save();
		}

	}

}
