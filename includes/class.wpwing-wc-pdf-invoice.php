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

			add_action( 'add_meta_boxes', array( $this, 'add_invoice_metabox' ) );
			add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

			add_filter( 'woocommerce_my_account_my_orders_actions', array( $this, 'filter_woocommerce_my_account_my_orders_actions' ), 10, 2 );

			// Auto-generate on status change.
			add_action( 'woocommerce_order_status_changed', array( $this, 'maybe_auto_generate' ), 10, 4 );

			// Attach PDF to WooCommerce emails.
			add_filter( 'woocommerce_email_attachments', array( $this, 'attach_document_to_email' ), 10, 3 );

			// Bulk actions — classic orders screen.
			add_filter( 'bulk_actions-edit-shop_order', array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'handle_bulk_actions' ), 10, 3 );

			// Bulk actions — HPOS orders screen.
			add_filter( 'bulk_actions-woocommerce_page_wc-orders', array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'handle_bulk_actions' ), 10, 3 );

			// Invoice status column — classic orders screen.
			add_filter( 'manage_shop_order_posts_columns', array( $this, 'add_order_list_column' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );

			// Invoice status column — HPOS orders screen.
			add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_list_column' ) );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'render_order_list_column' ), 10, 2 );

			// AJAX invoice/packing preview.
			add_action( 'wp_ajax_wpwing_preview_document', array( $this, 'ajax_preview_document' ) );
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
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['wpwing_notice'] ) ) {
				$notice = sanitize_key( $_GET['wpwing_notice'] );

				$messages = array(
					'invoice_created'   => array( 'success', __( 'Invoice created successfully.', 'wpwing-wc-pdf-invoice' ) ),
					'invoice_cancelled' => array( 'warning', __( 'Invoice has been cancelled.', 'wpwing-wc-pdf-invoice' ) ),
					'packing_created'   => array( 'success', __( 'Packing slip created successfully.', 'wpwing-wc-pdf-invoice' ) ),
					'packing_cancelled' => array( 'warning', __( 'Packing slip has been cancelled.', 'wpwing-wc-pdf-invoice' ) ),
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

			if ( isset( $_GET['wpwing_bulk_created'] ) ) {
				$count   = intval( $_GET['wpwing_bulk_created'] );
				$skipped = isset( $_GET['wpwing_bulk_skipped'] ) ? intval( $_GET['wpwing_bulk_skipped'] ) : 0;
				$type    = isset( $_GET['wpwing_bulk_type'] ) ? sanitize_key( $_GET['wpwing_bulk_type'] ) : 'invoice';
				$label   = 'invoice' === $type ? __( 'invoice', 'wpwing-wc-pdf-invoice' ) : __( 'packing slip', 'wpwing-wc-pdf-invoice' );

				// translators: %1$d: count of generated documents, %2$s: document type label.
				$message = sprintf( _n( '%1$d %2$s generated.', '%1$d %2$ss generated.', $count, 'wpwing-wc-pdf-invoice' ), $count, $label );

				if ( $skipped > 0 ) {
					// translators: %d: number of orders not processed due to the 50-order batch limit.
					$message .= ' ' . sprintf( _n( '%d order was not processed (50-order limit per action — run again to continue).', '%d orders were not processed (50-order limit per action — run again to continue).', $skipped, 'wpwing-wc-pdf-invoice' ), $skipped );
				}

				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( $message )
				);
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Whether the current user may manage (create/view/cancel) documents for a given order.
		 * Admins always pass. Customers pass only for view actions on their own orders.
		 *
		 * @param int  $order_id     WooCommerce order ID.
		 * @param bool $admin_only   True for write actions (create/cancel); false allows order owner too.
		 * @return bool
		 */
		private function user_can_manage_order( $order_id, $admin_only = false ) {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				return true;
			}
			if ( $admin_only ) {
				return false;
			}
			$order = wc_get_order( $order_id );
			return $order instanceof WC_Order
				&& is_user_logged_in()
				&& get_current_user_id() === (int) $order->get_customer_id();
		}

		/**
		 * Process document action GET requests with nonce and capability verification.
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
				if ( ! $this->user_can_manage_order( $order_id, true ) ) {
					wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->create_document( $order_id, 'invoice' );
				$notice = 'invoice_created';

			} elseif ( isset( $_GET['wpwing-view-invoice'] ) ) {
				$order_id = intval( $_GET['wpwing-view-invoice'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_view_invoice_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				if ( ! $this->user_can_manage_order( $order_id ) ) {
					wp_die( esc_html__( 'You do not have permission to view this document.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->view_document( $order_id, 'invoice' );
				return;

			} elseif ( isset( $_GET['wpwing-reset-invoice'] ) ) {
				$order_id = intval( $_GET['wpwing-reset-invoice'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_reset_invoice_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				if ( ! $this->user_can_manage_order( $order_id, true ) ) {
					wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->reset_document( $order_id, 'invoice' );
				$notice = 'invoice_cancelled';

			} elseif ( isset( $_GET['wpwing-create-packing'] ) ) {
				$order_id = intval( $_GET['wpwing-create-packing'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_create_packing_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				if ( ! $this->user_can_manage_order( $order_id, true ) ) {
					wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->create_document( $order_id, 'packing' );
				$notice = 'packing_created';

			} elseif ( isset( $_GET['wpwing-view-packing'] ) ) {
				$order_id = intval( $_GET['wpwing-view-packing'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_view_packing_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				if ( ! $this->user_can_manage_order( $order_id ) ) {
					wp_die( esc_html__( 'You do not have permission to view this document.', 'wpwing-wc-pdf-invoice' ) );
				}
				$this->view_document( $order_id, 'packing' );
				return;

			} elseif ( isset( $_GET['wpwing-reset-packing'] ) ) {
				$order_id = intval( $_GET['wpwing-reset-packing'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_reset_packing_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wc-pdf-invoice' ) );
				}
				if ( ! $this->user_can_manage_order( $order_id, true ) ) {
					wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wc-pdf-invoice' ) );
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
			$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
			add_meta_box( 'wpwing-pdf-invoice-box', esc_html__( 'PDF Invoice by WPWing', 'wpwing-wc-pdf-invoice' ), array( $this, 'show_pdf_invoice_metabox' ), $screen, 'side', 'high' );
		}

		/**
		 * Show metabox content on backend order page.
		 *
		 * @param WP_Post $post The order object currently shown.
		 *
		 * @since  1.0.0
		 */
		public function show_pdf_invoice_metabox( $post ) {
			$order_id = $post instanceof WC_Order ? $post->get_id() : $post->ID;
			$invoice  = $this->get_document_by_type( $order_id, 'invoice' );
			$packing  = $this->get_document_by_type( $order_id, 'packing' );
			?>
			<div class="wpwing-wcpi-metabox">

				<?php if ( ( null !== $invoice ) && $invoice->exists ) : ?>
				<div class="wpwing-wcpi-summary">
					<?php esc_html_e( 'Invoiced on:', 'wpwing-wc-pdf-invoice' ); ?>
					<strong><?php echo esc_html( $invoice->get_formatted_date() ); ?></strong>
					<span class="wpwing-wcpi-sep">|</span>
					<?php esc_html_e( 'Invoice:', 'wpwing-wc-pdf-invoice' ); ?>
					<strong><?php echo esc_html( $invoice->get_formatted_invoice_number() ); ?></strong>
				</div>
				<?php endif; ?>

				<div class="wpwing-wcpi-doc-row">
					<span class="dashicons dashicons-media-document wpwing-wcpi-doc-icon"></span>
					<span class="wpwing-wcpi-doc-label"><?php esc_html_e( 'Invoice:', 'wpwing-wc-pdf-invoice' ); ?></span>
					<div class="wpwing-wcpi-doc-actions">
						<?php if ( ( null !== $invoice ) && $invoice->exists ) : ?>
							<a class="button tips wpwing_wcpi_view_invoice"
								data-tip="<?php esc_attr_e( 'View Invoice', 'wpwing-wc-pdf-invoice' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-view-invoice', $invoice->order->get_id() ), 'wpwing_view_invoice_' . $invoice->order->get_id() ) ); ?>"
								target="_blank">
								<?php esc_html_e( 'View', 'wpwing-wc-pdf-invoice' ); ?>
							</a>
							<a class="button tips wpwing_wcpi_cancel_invoice wpwing-btn-cancel"
								data-tip="<?php esc_attr_e( 'Cancel Invoice', 'wpwing-wc-pdf-invoice' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-reset-invoice', $invoice->order->get_id() ), 'wpwing_reset_invoice_' . $invoice->order->get_id() ) ); ?>"
								onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this invoice?', 'wpwing-wc-pdf-invoice' ); ?>')">
								<span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Cancel', 'wpwing-wc-pdf-invoice' ); ?>
							</a>
						<?php else : ?>
							<a class="button tips wpwing_wcpi_create_invoice"
								data-tip="<?php esc_attr_e( 'Create Invoice', 'wpwing-wc-pdf-invoice' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-create-invoice', $invoice->order->get_id() ), 'wpwing_create_invoice_' . $invoice->order->get_id() ) ); ?>">
								<?php esc_html_e( 'Create', 'wpwing-wc-pdf-invoice' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<div class="wpwing-wcpi-doc-row">
					<span class="dashicons dashicons-archive wpwing-wcpi-doc-icon"></span>
					<span class="wpwing-wcpi-doc-label"><?php esc_html_e( 'Packing Slip:', 'wpwing-wc-pdf-invoice' ); ?></span>
					<div class="wpwing-wcpi-doc-actions">
						<?php if ( ( null !== $packing ) && $packing->exists ) : ?>
							<a class="button tips wpwing_wcpi_view_invoice"
								data-tip="<?php esc_attr_e( 'View Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-view-packing', $packing->order->get_id() ), 'wpwing_view_packing_' . $packing->order->get_id() ) ); ?>"
								target="_blank">
								<?php esc_html_e( 'View', 'wpwing-wc-pdf-invoice' ); ?>
							</a>
							<a class="button tips wpwing_wcpi_cancel_invoice wpwing-btn-cancel"
								data-tip="<?php esc_attr_e( 'Cancel Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-reset-packing', $packing->order->get_id() ), 'wpwing_reset_packing_' . $packing->order->get_id() ) ); ?>"
								onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this packing slip?', 'wpwing-wc-pdf-invoice' ); ?>')">
								<span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Cancel', 'wpwing-wc-pdf-invoice' ); ?>
							</a>
						<?php else : ?>
							<a class="button tips wpwing_wcpi_create_invoice"
								data-tip="<?php esc_attr_e( 'Create Packing Slip', 'wpwing-wc-pdf-invoice' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-create-packing', $packing->order->get_id() ), 'wpwing_create_packing_' . $packing->order->get_id() ) ); ?>">
								<?php esc_html_e( 'Create', 'wpwing-wc-pdf-invoice' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

			</div>
			<?php
		}

		/**
		 * Return true when the current admin screen belongs to this plugin.
		 *
		 * @return bool
		 * @since 1.5.0
		 */
		private function is_plugin_screen() {
			$screen = get_current_screen();
			if ( ! $screen ) {
				return false;
			}
			$order_screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
			$allowed      = array( 'wpwing-pdf-invoice', $order_screen, 'woocommerce_page_wc-orders' );
			return in_array( $screen->id, $allowed, true );
		}

		/**
		 * Enqueue css file
		 *
		 * @since  1.0.0
		 */
		public function enqueue_styles() {
			if ( ! $this->is_plugin_screen() ) {
				return;
			}
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_style( 'wcpi-admin-css', WPWING_WCPI_ASSETS_URL . "/public/css/admin{$suffix}.css", array(), WPWING_WCPI_VERSION );
		}

		/**
		 * Enqueue js file
		 *
		 * @since  1.0.0
		 */
		public function enqueue_scripts() {
			if ( ! $this->is_plugin_screen() ) {
				return;
			}

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
						'ajax_url'       => admin_url( 'admin-ajax.php' ),
						'ajax_loader'    => WPWING_WCPI_ASSETS_URL . '/images/ajax-loader.gif',
						'logo_message_1' => esc_html__( 'The logo your uploading is ', 'wpwing-wc-pdf-invoice' ),
						'logo_message_2' => esc_html__( '. Logo must be no bigger than 300 x 150 pixels', 'wpwing-wc-pdf-invoice' ),
						'preview_nonce'  => wp_create_nonce( 'wpwing_preview_document' ),
						'preview_btn'    => esc_html__( 'Preview Invoice', 'wpwing-wc-pdf-invoice' ),
						'preview_title'  => esc_html__( 'Invoice Preview', 'wpwing-wc-pdf-invoice' ),
						'preview_loading' => esc_html__( 'Loading…', 'wpwing-wc-pdf-invoice' ),
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
			if ( 'invoice' === $document_type && $this->settings->get_option( 'invoice_disable_free_orders' ) ) {
				$order = wc_get_order( $order_id );
				if ( $order && (float) $order->get_total() === 0.0 ) {
					return;
				}
			}

			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null !== $document ) {
				$this->save_document( $document );
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

				if ( ! file_exists( $full_path ) ) {
					wp_die( esc_html__( 'Invoice file not found. Please regenerate the invoice.', 'wpwing-wc-pdf-invoice' ) );
				}

				if ( 'open' === $button_behavior ) {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: inline; filename="' . sanitize_file_name( basename( $full_path ) ) . '"' );
					header( 'Content-Transfer-Encoding: binary' );
					header( 'Content-Length: ' . filesize( $full_path ) );
					header( 'Accept-Ranges: bytes' );
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
					readfile( $full_path );
					exit();
				} else {
					header( 'Content-type: application/pdf' );
					header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( basename( $full_path ) ) . '"' );
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

		/**
		 * Auto-generate invoice or packing slip when an order status matches configured triggers.
		 *
		 * @param int      $order_id   Order ID.
		 * @param string   $old_status Previous status slug (without wc- prefix).
		 * @param string   $new_status New status slug (without wc- prefix).
		 * @param WC_Order $order      Order object.
		 *
		 * @since 2.0.0
		 */
		public function maybe_auto_generate( $order_id, $old_status, $new_status, $order ) {
			static $running = false;
			if ( $running ) {
				return;
			}
			$running = true;

			$invoice_statuses = $this->settings->get_option( 'invoice_auto_statuses' );
			if ( is_array( $invoice_statuses ) && in_array( $new_status, $invoice_statuses, true ) ) {
				$skip = $this->settings->get_option( 'invoice_disable_free_orders' ) && (float) $order->get_total() === 0.0;
				if ( ! $skip ) {
					$invoice = $this->get_document_by_type( $order_id, 'invoice' );
					if ( null !== $invoice && ! $invoice->exists ) {
						$this->save_document( $invoice );
					}
				}
			}

			$packing_statuses = $this->settings->get_option( 'packing_auto_statuses' );
			if ( is_array( $packing_statuses ) && in_array( $new_status, $packing_statuses, true ) ) {
				$packing = $this->get_document_by_type( $order_id, 'packing' );
				if ( null !== $packing && ! $packing->exists ) {
					$this->save_document( $packing );
				}
			}

			$running = false;
		}

		/**
		 * Attach the invoice PDF to configured WooCommerce transactional emails.
		 *
		 * @param array    $attachments Current email attachments.
		 * @param string   $email_id    WooCommerce email ID.
		 * @param WC_Order $object      Object passed to the email (usually WC_Order).
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public function attach_document_to_email( $attachments, $email_id, $object ) {
			static $running = false;
			if ( $running ) {
				return $attachments;
			}

			$configured_ids = $this->settings->get_option( 'invoice_attach_to_emails' );

			if ( ! is_array( $configured_ids ) || ! in_array( $email_id, $configured_ids, true ) ) {
				return $attachments;
			}

			if ( ! ( $object instanceof WC_Order ) ) {
				return $attachments;
			}

			$running  = true;
			$order_id = $object->get_id();
			$invoice  = $this->get_document_by_type( $order_id, 'invoice' );

			if ( null === $invoice ) {
				$running = false;
				return $attachments;
			}

			if ( ! $invoice->exists ) {
				$this->save_document( $invoice );
			}

			$path = WPWING_WCPI_DOCUMENT_SAVE_DIR . $invoice->save_path;
			if ( file_exists( $path ) ) {
				$attachments[] = $path;
			}

			$running = false;
			return $attachments;
		}

		/**
		 * Register bulk actions on the orders list screen.
		 *
		 * @param array $actions Existing bulk actions.
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public function add_bulk_actions( $actions ) {
			$actions['wpwing_generate_invoices'] = __( 'Generate Invoices', 'wpwing-wc-pdf-invoice' );
			$actions['wpwing_generate_packing']  = __( 'Generate Packing Slips', 'wpwing-wc-pdf-invoice' );
			return $actions;
		}

		/**
		 * Process bulk invoice / packing slip generation.
		 *
		 * @param string $redirect_to Redirect URL after processing.
		 * @param string $action      Bulk action key.
		 * @param array  $ids         Selected order IDs.
		 * @return string
		 *
		 * @since 2.0.0
		 */
		public function handle_bulk_actions( $redirect_to, $action, $ids ) {
			if ( 'wpwing_generate_invoices' !== $action && 'wpwing_generate_packing' !== $action ) {
				return $redirect_to;
			}

			$document_type = ( 'wpwing_generate_invoices' === $action ) ? 'invoice' : 'packing';
			$count         = 0;
			$all_ids       = (array) $ids;
			$batch_ids     = array_slice( $all_ids, 0, 50 );
			$skipped       = count( $all_ids ) - count( $batch_ids );

			foreach ( $batch_ids as $order_id ) {
				$document = $this->get_document_by_type( intval( $order_id ), $document_type );
				if ( null !== $document && ! $document->exists ) {
					$this->save_document( $document );
					++$count;
				}
			}

			$redirect_to = remove_query_arg( array( 'wpwing_bulk_type', 'wpwing_bulk_created', 'wpwing_bulk_skipped' ), $redirect_to );
			$redirect_to = add_query_arg(
				array(
					'wpwing_bulk_type'    => $document_type,
					'wpwing_bulk_created' => $count,
					'wpwing_bulk_skipped' => $skipped,
				),
				$redirect_to
			);

			return $redirect_to;
		}

		/**
		 * Add invoice status column to the orders list table.
		 *
		 * @param array $columns Existing table columns.
		 * @return array
		 *
		 * @since 2.0.0
		 */
		public function add_order_list_column( $columns ) {
			$new_columns = array();
			foreach ( $columns as $key => $label ) {
				$new_columns[ $key ] = $label;
				if ( 'order_status' === $key ) {
					$new_columns['wpwing_invoice'] = __( 'Invoice', 'wpwing-wc-pdf-invoice' );
				}
			}
			return $new_columns;
		}

		/**
		 * Render the invoice status column cell.
		 *
		 * Works for both classic (post ID int) and HPOS (WC_Order object) screens.
		 *
		 * @param string         $column      Column key.
		 * @param int|WC_Order   $order_or_id Order ID or WC_Order object.
		 *
		 * @since 2.0.0
		 */
		public function render_order_list_column( $column, $order_or_id ) {
			if ( 'wpwing_invoice' !== $column ) {
				return;
			}

			$order_id = $order_or_id instanceof WC_Order ? $order_or_id->get_id() : intval( $order_or_id );
			$invoice  = $this->get_document_by_type( $order_id, 'invoice' );

			if ( null !== $invoice && $invoice->exists ) {
				printf(
					'<span class="dashicons dashicons-yes-alt" title="%s"></span>',
					esc_attr__( 'Invoice generated', 'wpwing-wc-pdf-invoice' )
				);
			} else {
				$url = wp_nonce_url(
					add_query_arg( 'wpwing-create-invoice', $order_id ),
					'wpwing_create_invoice_' . $order_id
				);
				printf(
					'<a href="%s" title="%s"><span class="dashicons dashicons-plus-alt2"></span></a>',
					esc_url( $url ),
					esc_attr__( 'Create Invoice', 'wpwing-wc-pdf-invoice' )
				);
			}
		}

		/**
		 * AJAX handler: render an HTML preview of the invoice or packing slip template.
		 *
		 * @since 2.0.0
		 */
		public function ajax_preview_document() {
			check_ajax_referer( 'wpwing_preview_document', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( __( 'Permission denied.', 'wpwing-wc-pdf-invoice' ) );
			}

			$document_type = isset( $_POST['document_type'] ) ? sanitize_key( $_POST['document_type'] ) : 'invoice';

			$orders = wc_get_orders( array( 'limit' => 1, 'orderby' => 'date', 'order' => 'DESC' ) );
			if ( empty( $orders ) ) {
				wp_send_json_error( __( 'No orders found to preview.', 'wpwing-wc-pdf-invoice' ) );
			}

			$order_id = $orders[0]->get_id();
			$document = $this->get_document_by_type( $order_id, $document_type );

			if ( null === $document ) {
				wp_send_json_error( __( 'Invalid document type.', 'wpwing-wc-pdf-invoice' ) );
			}

			$document->exists = true;

			global $wpwing_wcpi_document;
			$wpwing_wcpi_document = $document;

			$document->init_template();
			$document->init_template_generation_actions();

			$theme_dir = $document->get_theme_dir();

			ob_start();
			wc_get_template( 'template.php', null, $theme_dir, $theme_dir );
			$html = ob_get_clean();

			$document->flush_template();

			wp_send_json_success( array( 'html' => $html ) );
		}

	}

}
