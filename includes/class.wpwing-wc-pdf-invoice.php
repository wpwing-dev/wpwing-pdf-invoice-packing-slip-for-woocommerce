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
			add_action( 'init', array( $this, 'init_plugin_actions' ) );

			$this->initialize();

			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_invoice_metabox' ) );
			add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'filter_woocommerce_my_account_my_orders_actions' ), 10, 2 );
		}

		/**
		 * Add Invoice download link in my account > orders section.
		 *
		 * @param array    $actions Existing actions.
		 * @param WC_Order $order   Current order.
		 * @return array
		 *
		 * @since 1.3.1
		 */
		public function filter_woocommerce_my_account_my_orders_actions( $actions, $order ) {
			$invoice = $this->get_document_by_type( $order->get_id(), 'invoice' );

			if ( ( null !== $invoice ) && $invoice->exists ) {
				$nonce_url = wp_nonce_url(
					add_query_arg( 'wpwing-view-invoice', $order->get_id() ),
					'wpwing_view_invoice_' . $order->get_id()
				);

				$actions['wpwing_invoice'] = array(
					'url'  => $nonce_url,
					'name' => __( 'Invoice', 'wpwing-wc-pdf-invoice' ),
				);
			}

			return $actions;
		}

		/**
		 * Display admin notices set by document actions.
		 *
		 * @since 2.0.0
		 */
		public function show_admin_notices() {
			if ( ! isset( $_GET['wpwing_notice'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			$notice = sanitize_key( $_GET['wpwing_notice'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$messages = array(
				'invoice_created'    => array( 'success', __( 'Invoice created successfully.', 'wpwing-wc-pdf-invoice' ) ),
				'invoice_cancelled'  => array( 'warning', __( 'Invoice has been cancelled.', 'wpwing-wc-pdf-invoice' ) ),
				'packing_created'    => array( 'success', __( 'Packing slip created successfully.', 'wpwing-wc-pdf-invoice' ) ),
				'packing_cancelled'  => array( 'warning', __( 'Packing slip has been cancelled.', 'wpwing-wc-pdf-invoice' ) ),
			);

			if ( isset( $messages[ $notice ] ) ) {
				list( $type, $message ) = $messages[ $notice ];
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $type ),
					esc_html( $message )
				);
			}
		}

		/**
		 * Process document action GET requests with nonce verification.
		 *
		 * @since 1.0.0
		 */
		public function init_plugin_actions() {
			$notice = '';

			if ( isset( $_GET['wpwing-create-invoice'] ) ) {
				$order_id = intval( $_GET['wpwing-create-invoice'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_create_invoice_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->create_document( $order_id, 'invoice' );
				$notice = 'invoice_created';

			} elseif ( isset( $_GET['wpwing-view-invoice'] ) ) {
				$order_id = intval( $_GET['wpwing-view-invoice'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_view_invoice_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->view_document( $order_id, 'invoice' );
				return;

			} elseif ( isset( $_GET['wpwing-reset-invoice'] ) ) {
				$order_id = intval( $_GET['wpwing-reset-invoice'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_reset_invoice_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->reset_document( $order_id, 'invoice' );
				$notice = 'invoice_cancelled';

			} elseif ( isset( $_GET['wpwing-create-packing'] ) ) {
				$order_id = intval( $_GET['wpwing-create-packing'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_create_packing_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->create_document( $order_id, 'packing' );
				$notice = 'packing_created';

			} elseif ( isset( $_GET['wpwing-view-packing'] ) ) {
				$order_id = intval( $_GET['wpwing-view-packing'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_view_packing_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->view_document( $order_id, 'packing' );
				return;

			} elseif ( isset( $_GET['wpwing-reset-packing'] ) ) {
				$order_id = intval( $_GET['wpwing-reset-packing'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_reset_packing_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->reset_document( $order_id, 'packing' );
				$notice = 'packing_cancelled';

			} else {
				return;
			}

			if ( is_admin() && isset( $_SERVER['HTTP_REFERER'] ) ) {
				$location = add_query_arg( 'wpwing_notice', $notice, sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
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
		 * Add a metabox on backend order page.
		 *
		 * @since  1.0.0
		 */
		public function add_invoice_metabox() {
			add_meta_box( 'wpwing-pdf-invoice-box', esc_html__( 'PDF Invoice by WPWing', 'wpwing-wc-pdf-invoice' ), array( $this, 'show_pdf_invoice_metabox' ), 'shop_order', 'side', 'high' );
		}

		/**
		 * Show metabox content on backend order page.
		 *
		 * @param WP_Post $post The order object currently shown.
		 *
		 * @since  1.0.0
		 */
		public function show_pdf_invoice_metabox( $post ) {
			$invoice = $this->get_document_by_type( $post->ID, 'invoice' );
			$packing = $this->get_document_by_type( $post->ID, 'packing' );
			?>
			<div class="invoice-information">

				<?php if ( ( null !== $invoice ) && $invoice->exists ) : ?>
					<div class="wpwing-wcpi-meta-row">
						<span><?php esc_html_e( 'Invoiced on:', 'wpwing-wc-pdf-invoice' ); ?></span>
						<strong><?php echo esc_html( $invoice->get_formatted_date() ); ?></strong>
					</div>
					<div class="wpwing-wcpi-meta-row">
						<span><?php esc_html_e( 'Invoice number:', 'wpwing-wc-pdf-invoice' ); ?></span>
						<strong><?php echo esc_html( $invoice->get_formatted_invoice_number() ); ?></strong>
					</div>
					<div class="wpwing-wcpi-meta-actions">
						<a class="button tips wpwing_wcpi_view_invoice"
							data-tip="<?php esc_attr_e( 'View Invoice', 'wpwing-wc-pdf-invoice' ); ?>"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-view-invoice', $invoice->order->get_id() ), 'wpwing_view_invoice_' . $invoice->order->get_id() ) ); ?>"
							target="_blank">
							<?php esc_html_e( 'View Invoice', 'wpwing-wc-pdf-invoice' ); ?>
						</a>
						<a class="button tips wpwing_wcpi_cancel_invoice"
							data-tip="<?php esc_attr_e( 'Cancel Invoice', 'wpwing-wc-pdf-invoice' ); ?>"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-reset-invoice', $invoice->order->get_id() ), 'wpwing_reset_invoice_' . $invoice->order->get_id() ) ); ?>"
							onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this invoice?', 'wpwing-wc-pdf-invoice' ); ?>')">
							<?php esc_html_e( 'Cancel Invoice', 'wpwing-wc-pdf-invoice' ); ?>
						</a>
					</div>
				<?php else : ?>
					<p>
						<a class="button tips wpwing_wcpi_create_invoice"
							data-tip="<?php esc_attr_e( 'Create Invoice', 'wpwing-wc-pdf-invoice' ); ?>"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-create-invoice', $invoice->order->get_id() ), 'wpwing_create_invoice_' . $invoice->order->get_id() ) ); ?>">
							<?php esc_html_e( 'Create Invoice', 'wpwing-wc-pdf-invoice' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<?php if ( ( null !== $packing ) && $packing->exists ) : ?>
					<div class="wpwing-wcpi-meta-actions">
						<a class="button tips wpwing_wcpi_view_invoice"
							data-tip="<?php esc_attr_e( 'View Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-view-packing', $packing->order->get_id() ), 'wpwing_view_packing_' . $packing->order->get_id() ) ); ?>"
							target="_blank">
							<?php esc_html_e( 'View Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>
						</a>
						<a class="button tips wpwing_wcpi_cancel_invoice"
							data-tip="<?php esc_attr_e( 'Cancel Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-reset-packing', $packing->order->get_id() ), 'wpwing_reset_packing_' . $packing->order->get_id() ) ); ?>"
							onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this packing slip?', 'wpwing-wc-pdf-invoice' ); ?>')">
							<?php esc_html_e( 'Cancel Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>
						</a>
					</div>
				<?php else : ?>
					<p>
						<a class="button tips wpwing_wcpi_create_invoice"
							data-tip="<?php esc_attr_e( 'Create Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-create-packing', $packing->order->get_id() ), 'wpwing_create_packing_' . $packing->order->get_id() ) ); ?>">
							<?php esc_html_e( 'Create Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>
						</a>
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
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_style( 'wcpi-admin-css', WPWING_WCPI_ASSETS_URL . "/public/css/admin{$suffix}.css", array(), WPWING_WCPI_VERSION );
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

			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

			wp_register_script(
				'wcpi-admin-js',
				WPWING_WCPI_ASSETS_URL . "/public/js/admin{$suffix}.js",
				array( 'jquery', 'jquery-ui-sortable' ),
				WPWING_WCPI_VERSION,
				true
			);

			wp_localize_script(
				'wcpi-admin-js',
				'wpwing_wcpi_object',
				apply_filters(
					'wpwing_wcpi_admin_localize',
					array(
						'ajax_url'     => admin_url( 'admin-ajax.php' ),
						'ajax_loader'  => WPWING_WCPI_ASSETS_URL . '/images/ajax-loader.gif',
						'logo_message_1' => esc_html__( 'The logo your uploading is ', 'wpwing-wc-pdf-invoice' ),
						'logo_message_2' => esc_html__( '. Logo must be no bigger than 300 x 150 pixels', 'wpwing-wc-pdf-invoice' ),
					)
				)
			);

			wp_enqueue_script( 'wcpi-admin-js' );
		}

		/**
		 * Return a new document of the type requested, for a specific order.
		 *
		 * @param int    $order_id      The order ID for which the document is created.
		 * @param string $document_type The document type to return.
		 *
		 * @return object|null
		 * @since 1.0.0
		 */
		public function get_document_by_type( $order_id, $document_type = '' ) {
			switch ( $document_type ) {
				case 'invoice':
					$document = new WCPI_Invoice( $order_id );
					break;
				case 'packing':
					$document = new WCPI_Packing( $order_id );
					break;
				default:
					return null;
			}

			return $document;
		}

		/**
		 * Create a new document of the type requested, for a specific order.
		 *
		 * @param int    $order_id      The order ID for which the document is created.
		 * @param string $document_type The document type to be generated.
		 *
		 * @since 1.0.0
		 */
		public function create_document( $order_id, $document_type = '' ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null !== $document ) {
				$this->save_document( $document );
			}

			if ( $this->settings->get_option( 'invoice_send_customer' ) ) {
				$order      = new WC_Order( $order_id );
				$to         = $order->get_billing_email();
				$subject    = __( 'Order Invoice (PDF)', 'wpwing-wc-pdf-invoice' );
				$message    = __( 'Dear Customer, Here is your order invoice. Please check the attachment.', 'wpwing-wc-pdf-invoice' );
				$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
				$attachment = WPWING_WCPI_DOCUMENT_SAVE_DIR . $order->get_meta( '_wpwing_wcpi_invoice_path' );
				wc_mail( $to, $subject, $message, $headers, $attachment );
			}
		}

		/**
		 * Stream the PDF to the browser for viewing or download.
		 *
		 * @param int    $order_id      The order ID.
		 * @param string $document_type The document type.
		 *
		 * @since 1.0.0
		 */
		public function view_document( $order_id, $document_type ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null !== $document ) {
				$full_path      = WPWING_WCPI_DOCUMENT_SAVE_DIR . $document->save_path;
				$button_behavior = $this->settings->get_option( 'invoice_button_behavior' );

				if ( 'open' === $button_behavior ) {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: inline; filename="' . basename( $full_path ) . '"' );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $full_path ) );
					header( 'Accept-Ranges: bytes' );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
					readfile( $full_path );
					exit();
				} else {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: attachment; filename="' . basename( $full_path ) . '"' );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
					readfile( $full_path );
				}
			}
		}

		/**
		 * Delete a generated document for a specific order.
		 *
		 * @param int    $order_id      The order ID.
		 * @param string $document_type The document type.
		 *
		 * @since 1.0.0
		 */
		public function reset_document( $order_id, $document_type ) {
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null !== $document ) {
				$document->reset();
			}
		}

		/**
		 * Save a PDF file starting from a previously created document.
		 *
		 * @param object $document The document object to save.
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
