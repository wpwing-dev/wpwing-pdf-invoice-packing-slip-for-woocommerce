<?php
/**
 * Admin UI: metabox, notices, asset enqueuing, and AJAX preview.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Admin' ) ) {

	/**
	 * Handles all admin-facing concerns: metabox, admin notices,
	 * asset enqueuing, and the AJAX live preview endpoint.
	 *
	 * @since 1.8.0
	 */
	class WPWing_WcPdf_Admin {

		/**
		 * Plugin instance.
		 *
		 * @var WPWing_WcPdf_Plugin
		 */
		private $plugin;

		/**
		 * Settings instance.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		private $settings;

		/**
		 * Constructor.
		 *
		 * @param WPWing_WcPdf_Plugin   $plugin   Plugin instance.
		 * @param WPWing_WcPdf_Settings $settings Settings instance.
		 */
		public function __construct( WPWing_WcPdf_Plugin $plugin, WPWing_WcPdf_Settings $settings ) {
			$this->plugin   = $plugin;
			$this->settings = $settings;
		}

		/**
		 * Register all admin hooks.
		 */
		public function register() {
			add_action( 'add_meta_boxes', array( $this, 'add_invoice_metabox' ) );
			add_action( 'admin_notices', array( $this, 'show_admin_notices' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_styles' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'wp_ajax_wpwing_preview_document', array( $this, 'ajax_preview_document' ) );
		}

		/**
		 * Return true when the current admin screen belongs to this plugin.
		 *
		 * @return bool
		 */
		private function is_plugin_screen() {
			$screen = get_current_screen();
			if ( ! $screen ) {
				return false;
			}
			$order_screen    = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
			$settings_screen = 'wpwing_page_' . sprintf( '%s-settings', sanitize_key( WPWING_WCPDF_DIR_NAME ) );
			$allowed         = array( 'wpwing-pdf-invoice', $settings_screen, $order_screen, 'woocommerce_page_wc-orders' );
			return in_array( $screen->id, $allowed, true );
		}

		/**
		 * Register the order metabox.
		 */
		public function add_invoice_metabox() {
			$screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
			add_meta_box( 'wpwing-pdf-invoice-box', esc_html__( 'PDF Invoice by WPWing', 'wpwing-wcpdf' ), array( $this, 'show_pdf_invoice_metabox' ), $screen, 'side', 'high' );
		}

		/**
		 * Render the order metabox content.
		 *
		 * @param WP_Post|WC_Order $post The order object currently shown.
		 */
		public function show_pdf_invoice_metabox( $post ) {
			$order_id = $post instanceof WC_Order ? $post->get_id() : $post->ID;
			$invoice  = $this->plugin->get_document_by_type( $order_id, 'invoice' );
			$packing  = $this->plugin->get_document_by_type( $order_id, 'packing' );
			?>
			<div class="wpwing-wcpdf-metabox">

				<?php if ( ( null !== $invoice ) && $invoice->exists ) : ?>
				<div class="wpwing-wcpdf-summary">
					<?php esc_html_e( 'Invoiced on:', 'wpwing-wcpdf' ); ?>
					<strong><?php echo esc_html( $invoice->get_formatted_date() ); ?></strong>
					<span class="wpwing-wcpdf-sep">|</span>
					<?php esc_html_e( 'Invoice:', 'wpwing-wcpdf' ); ?>
					<strong><?php echo esc_html( $invoice->get_formatted_invoice_number() ); ?></strong>
				</div>
				<?php endif; ?>

				<div class="wpwing-wcpdf-doc-row">
					<span class="dashicons dashicons-media-document wpwing-wcpdf-doc-icon"></span>
					<span class="wpwing-wcpdf-doc-label"><?php esc_html_e( 'Invoice:', 'wpwing-wcpdf' ); ?></span>
					<div class="wpwing-wcpdf-doc-actions">
						<?php if ( ( null !== $invoice ) && $invoice->exists ) : ?>
							<a class="button tips wpwing_wcpdf_view_invoice"
								data-tip="<?php esc_attr_e( 'View Invoice', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-view-invoice', $invoice->order->get_id() ), 'wpwing_view_invoice_' . $invoice->order->get_id() ) ); ?>"
								target="_blank">
								<?php esc_html_e( 'View', 'wpwing-wcpdf' ); ?>
							</a>
							<a class="button tips wpwing_wcpdf_preview_html"
								data-tip="<?php esc_attr_e( 'Preview invoice template as HTML', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-preview-html-invoice', $invoice->order->get_id() ), 'wpwing_preview_html_invoice_' . $invoice->order->get_id() ) ); ?>"
								target="_blank">
								<?php esc_html_e( 'Preview HTML', 'wpwing-wcpdf' ); ?>
							</a>
							<a class="button tips wpwing_wcpdf_cancel_invoice wpwing-btn-cancel"
								data-tip="<?php esc_attr_e( 'Cancel Invoice', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-reset-invoice', $invoice->order->get_id() ), 'wpwing_reset_invoice_' . $invoice->order->get_id() ) ); ?>"
								onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this invoice?', 'wpwing-wcpdf' ); ?>')">
								<span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Cancel', 'wpwing-wcpdf' ); ?>
							</a>
						<?php else : ?>
							<a class="button tips wpwing_wcpdf_create_invoice"
								data-tip="<?php esc_attr_e( 'Create Invoice', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-create-invoice', $order_id ), 'wpwing_create_invoice_' . $order_id ) ); ?>">
								<?php esc_html_e( 'Create', 'wpwing-wcpdf' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<div class="wpwing-wcpdf-doc-row">
					<span class="dashicons dashicons-archive wpwing-wcpdf-doc-icon"></span>
					<span class="wpwing-wcpdf-doc-label"><?php esc_html_e( 'Packing Slip:', 'wpwing-wcpdf' ); ?></span>
					<div class="wpwing-wcpdf-doc-actions">
						<?php if ( ( null !== $packing ) && $packing->exists ) : ?>
							<a class="button tips wpwing_wcpdf_view_invoice"
								data-tip="<?php esc_attr_e( 'View Packing Slip', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-view-packing', $packing->order->get_id() ), 'wpwing_view_packing_' . $packing->order->get_id() ) ); ?>"
								target="_blank">
								<?php esc_html_e( 'View', 'wpwing-wcpdf' ); ?>
							</a>
							<a class="button tips wpwing_wcpdf_preview_html"
								data-tip="<?php esc_attr_e( 'Preview packing slip template as HTML', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-preview-html-packing', $packing->order->get_id() ), 'wpwing_preview_html_packing_' . $packing->order->get_id() ) ); ?>"
								target="_blank">
								<?php esc_html_e( 'Preview HTML', 'wpwing-wcpdf' ); ?>
							</a>
							<a class="button tips wpwing_wcpdf_cancel_invoice wpwing-btn-cancel"
								data-tip="<?php esc_attr_e( 'Cancel Packing Slip', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-reset-packing', $packing->order->get_id() ), 'wpwing_reset_packing_' . $packing->order->get_id() ) ); ?>"
								onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this packing slip?', 'wpwing-wcpdf' ); ?>')">
								<span class="dashicons dashicons-dismiss"></span><?php esc_html_e( 'Cancel', 'wpwing-wcpdf' ); ?>
							</a>
						<?php else : ?>
							<a class="button tips wpwing_wcpdf_create_invoice"
								data-tip="<?php esc_attr_e( 'Create Packing Slip', 'wpwing-wcpdf' ); ?>"
								href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-create-packing', $order_id ), 'wpwing_create_packing_' . $order_id ) ); ?>">
								<?php esc_html_e( 'Create', 'wpwing-wcpdf' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

			</div>
			<?php
		}

		/**
		 * Display admin notices set by document actions and PDF errors.
		 */
		public function show_admin_notices() {
			$pdf_error = get_transient( 'wpwing_wcpdf_pdf_error_' . get_current_user_id() );
			if ( $pdf_error ) {
				delete_transient( 'wpwing_wcpdf_pdf_error_' . get_current_user_id() );
				printf(
					'<div class="notice notice-error is-dismissible"><p><strong>%s</strong> %s</p></div>',
					esc_html__( 'PDF generation failed:', 'wpwing-wcpdf' ),
					esc_html( $pdf_error )
				);
			}

			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['wpwing_notice'] ) ) {
				$notice = sanitize_key( $_GET['wpwing_notice'] );

				$messages = array(
					'invoice_created'   => array( 'success', __( 'Invoice created successfully.', 'wpwing-wcpdf' ) ),
					'invoice_cancelled' => array( 'warning', __( 'Invoice has been cancelled.', 'wpwing-wcpdf' ) ),
					'packing_created'   => array( 'success', __( 'Packing slip created successfully.', 'wpwing-wcpdf' ) ),
					'packing_cancelled' => array( 'warning', __( 'Packing slip has been cancelled.', 'wpwing-wcpdf' ) ),
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
				$label   = 'invoice' === $type ? __( 'invoice', 'wpwing-wcpdf' ) : __( 'packing slip', 'wpwing-wcpdf' );

				// translators: %1$d: count of generated documents, %2$s: document type label.
				$message = sprintf( _n( '%1$d %2$s generated.', '%1$d %2$ss generated.', $count, 'wpwing-wcpdf' ), $count, $label );

				if ( $skipped > 0 ) {
					// translators: %d: number of orders not processed due to the 50-order batch limit.
					$message .= ' ' . sprintf( _n( '%d order was not processed (50-order limit per action - run again to continue).', '%d orders were not processed (50-order limit per action - run again to continue).', $skipped, 'wpwing-wcpdf' ), $skipped );
				}

				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( $message )
				);
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}

		/**
		 * Enqueue admin stylesheet on plugin screens.
		 */
		public function enqueue_styles() {
			if ( ! $this->is_plugin_screen() ) {
				return;
			}
			$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_style( 'wcpdf-admin-css', WPWING_WCPDF_ASSETS_URL . "/public/css/admin{$suffix}.css", array(), WPWING_WCPDF_VERSION );
		}

		/**
		 * Enqueue admin script and localize data on plugin screens.
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
				'wcpdf-admin-js',
				WPWING_WCPDF_ASSETS_URL . "/public/js/admin{$suffix}.js",
				array( 'jquery', 'jquery-ui-sortable' ),
				WPWING_WCPDF_VERSION,
				true
			);

			wp_localize_script(
				'wcpdf-admin-js',
				'wpwing_wcpdf_object',
				apply_filters(
					'wpwing_wcpdf_admin_localize',
					array(
						'ajax_url'        => admin_url( 'admin-ajax.php' ),
						'ajax_loader'     => WPWING_WCPDF_ASSETS_URL . '/images/ajax-loader.gif',
						'logo_message_1'  => esc_html__( 'The logo your uploading is ', 'wpwing-wcpdf' ),
						'logo_message_2'  => esc_html__( '. Logo must be no bigger than 300 x 150 pixels', 'wpwing-wcpdf' ),
						'preview_nonce'   => wp_create_nonce( 'wpwing_preview_document' ),
						'preview_btn'     => esc_html__( 'Preview Invoice', 'wpwing-wcpdf' ),
						'preview_title'   => esc_html__( 'Invoice Preview', 'wpwing-wcpdf' ),
						'preview_loading' => esc_html__( 'Loading…', 'wpwing-wcpdf' ),
					)
				)
			);

			wp_enqueue_script( 'wcpdf-admin-js' );
		}

		/**
		 * AJAX handler: render an HTML preview of the invoice or packing slip template.
		 */
		public function ajax_preview_document() {
			check_ajax_referer( 'wpwing_preview_document', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( __( 'Permission denied.', 'wpwing-wcpdf' ) );
			}

			$document_type = isset( $_POST['document_type'] ) ? sanitize_key( $_POST['document_type'] ) : 'invoice';

			// Temporarily override saved settings with live (unsaved) form values for the preview.
			if ( ! empty( $_POST['preview_overrides'] ) && is_array( $_POST['preview_overrides'] ) ) {
				$overrides   = array_map( 'sanitize_text_field', wp_unslash( $_POST['preview_overrides'] ) );
				$option_name = apply_filters( 'wpwing_wcpdf_settings_name', 'wpwing_wcpdf_settings' );
				add_filter(
					"option_{$option_name}",
					function ( $value ) use ( $overrides ) {
						return array_merge( (array) $value, $overrides );
					},
					999
				);
			}

			$orders = wc_get_orders(
				array(
					'limit'   => 1,
					'orderby' => 'date',
					'order'   => 'DESC',
				)
			);
			if ( empty( $orders ) ) {
				wp_send_json_error( __( 'No orders found to preview.', 'wpwing-wcpdf' ) );
			}

			$order_id = $orders[0]->get_id();
			$document = $this->plugin->get_document_by_type( $order_id, $document_type );

			if ( null === $document ) {
				wp_send_json_error( __( 'Invalid document type.', 'wpwing-wcpdf' ) );
			}

			$document->exists = true;

			global $wpwing_wcpdf_document;
			$wpwing_wcpdf_document = $document;

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
