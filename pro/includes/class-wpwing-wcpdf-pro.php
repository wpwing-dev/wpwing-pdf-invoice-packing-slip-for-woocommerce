<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Pro' ) ) {

	/**
	 * Pro plugin controller.
	 *
	 * Wires up:
	 *  - Document access control (Phase 6.1)
	 *  - Proforma invoice metabox + actions (Phase 6.2)
	 *  - Auto credit note on WooCommerce refund (Phase 6.3)
	 */
	class WPWing_WcPdf_Pro {

		public function __construct() {
			// Access control runs before free plugin's init_plugin_actions (priority 10).
			add_action( 'init', array( $this, 'check_document_access' ), 5 );

			// Proforma actions — run after free plugin has handled its own actions.
			add_action( 'init', array( $this, 'init_pro_actions' ), 11 );

			// Proforma metabox on the order edit screen.
			add_action( 'woocommerce_admin_order_data_after_order_details', array( $this, 'add_proforma_metabox' ) );

			// Auto-generate credit note when a refund is issued.
			add_action( 'woocommerce_order_refunded', array( $this, 'handle_order_refunded' ), 10, 2 );

			// Admin notices for pro actions.
			add_action( 'admin_notices', array( $this, 'show_pro_admin_notices' ) );
		}

		// -------------------------------------------------------------------------
		// Access control (Phase 6.1)
		// -------------------------------------------------------------------------

		/**
		 * Validate the current user may view the requested document.
		 * Fires at init priority 5, before the free plugin's action handler at priority 10.
		 */
		public function check_document_access() {
			$protected = array( 'wpwing-view-invoice', 'wpwing-view-packing', 'wpwing-view-proforma' );

			foreach ( $protected as $param ) {
				if ( ! isset( $_GET[ $param ] ) ) {
					continue;
				}

				$order_id = intval( $_GET[ $param ] );
				if ( $this->user_can_view_order( $order_id ) ) {
					continue;
				}

				wp_die(
					esc_html__( 'You do not have permission to view this document.', 'wpwing-pdf-invoice-pro' ),
					esc_html__( 'Access Denied', 'wpwing-pdf-invoice-pro' ),
					array( 'response' => 403 )
				);
			}
		}

		/**
		 * Return true if the current user is an admin or is the order's customer.
		 *
		 * @param int $order_id Order ID.
		 * @return bool
		 */
		private function user_can_view_order( $order_id ) {
			if ( current_user_can( 'manage_woocommerce' ) ) {
				return true;
			}

			if ( ! is_user_logged_in() ) {
				return false;
			}

			$order = wc_get_order( $order_id );
			if ( ! $order ) {
				return false;
			}

			return get_current_user_id() === (int) $order->get_customer_id();
		}

		// -------------------------------------------------------------------------
		// Proforma actions (Phase 6.2)
		// -------------------------------------------------------------------------

		/**
		 * Handle proforma-related GET actions with nonce verification.
		 */
		public function init_pro_actions() {
			$notice = '';

			if ( isset( $_GET['wpwing-create-proforma'] ) ) {
				$order_id = intval( $_GET['wpwing-create-proforma'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_create_proforma_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-pdf-invoice-pro' ) );
				}
				$this->save_proforma( $order_id );
				$notice = 'proforma_created';

			} elseif ( isset( $_GET['wpwing-view-proforma'] ) ) {
				$order_id = intval( $_GET['wpwing-view-proforma'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_view_proforma_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-pdf-invoice-pro' ) );
				}
				$this->view_proforma( $order_id );
				return;

			} elseif ( isset( $_GET['wpwing-reset-proforma'] ) ) {
				$order_id = intval( $_GET['wpwing-reset-proforma'] );
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_reset_proforma_' . $order_id ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-pdf-invoice-pro' ) );
				}
				$proforma = new WPWing_WcPdf_Proforma( $order_id );
				$proforma->reset();
				$notice = 'proforma_cancelled';

			} else {
				return;
			}

			if ( is_admin() && isset( $_SERVER['HTTP_REFERER'] ) ) {
				$location = add_query_arg( 'wpwing_pro_notice', $notice, sanitize_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) );
				wp_safe_redirect( $location );
				exit();
			}
		}

		/**
		 * Create and save a proforma PDF if one does not yet exist.
		 *
		 * @param int $order_id Order ID.
		 */
		private function save_proforma( $order_id ) {
			$proforma = new WPWing_WcPdf_Proforma( $order_id );
			if ( $proforma->is_valid && ! $proforma->exists ) {
				global $wpwing_wcpdf_document;
				$wpwing_wcpdf_document = $proforma;
				$wpwing_wcpdf_document->save();
			}
		}

		/**
		 * Stream the proforma PDF to the browser.
		 *
		 * @param int $order_id Order ID.
		 */
		private function view_proforma( $order_id ) {
			$proforma = new WPWing_WcPdf_Proforma( $order_id );
			if ( ! $proforma->is_valid || ! $proforma->exists ) {
				return;
			}

			$full_path       = WPWING_WCPDF_DOCUMENT_SAVE_DIR . $proforma->save_path;
			$button_behavior = WPWing_WcPdf_Settings::get_instance()->get_option( 'invoice_button_behavior' );

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

		/**
		 * Register the proforma meta box on the order edit screen.
		 * Follows the same pattern as the free plugin's add_invoice_metabox().
		 */
		public function add_proforma_metabox() {
			add_meta_box(
				'wpwing-pdf-proforma-box',
				esc_html__( 'Proforma Invoice (Pro)', 'wpwing-pdf-invoice-pro' ),
				array( $this, 'show_proforma_metabox' ),
				'shop_order',
				'side',
				'default'
			);
		}

		/**
		 * Render the proforma meta box content.
		 *
		 * @param WP_Post $post The order post object.
		 */
		public function show_proforma_metabox( $post ) {
			$order_id = $post->ID;
			$proforma = new WPWing_WcPdf_Proforma( $order_id );
			?>
			<div class="invoice-information">
				<?php if ( $proforma->is_valid && $proforma->exists ) : ?>
					<div class="wpwing-wcpdf-meta-actions">
						<a class="button tips"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-view-proforma', $order_id ), 'wpwing_view_proforma_' . $order_id ) ); ?>"
							target="_blank">
							<?php esc_html_e( 'View Proforma', 'wpwing-pdf-invoice-pro' ); ?>
						</a>
						<a class="button tips"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-reset-proforma', $order_id ), 'wpwing_reset_proforma_' . $order_id ) ); ?>"
							onclick="return confirm('<?php esc_attr_e( 'Are you sure you want to cancel this proforma?', 'wpwing-pdf-invoice-pro' ); ?>')">
							<?php esc_html_e( 'Cancel Proforma', 'wpwing-pdf-invoice-pro' ); ?>
						</a>
					</div>
				<?php else : ?>
					<p>
						<a class="button"
							href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wpwing-create-proforma', $order_id ), 'wpwing_create_proforma_' . $order_id ) ); ?>">
							<?php esc_html_e( 'Create Proforma', 'wpwing-pdf-invoice-pro' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
			<?php
		}

		// -------------------------------------------------------------------------
		// Credit notes (Phase 6.3)
		// -------------------------------------------------------------------------

		/**
		 * Auto-generate a credit note PDF when WooCommerce processes a refund.
		 *
		 * @param int $order_id  Original order ID.
		 * @param int $refund_id WooCommerce refund ID.
		 */
		public function handle_order_refunded( $order_id, $refund_id ) {
			$creditnote = new WPWing_WcPdf_CreditNote( $order_id, $refund_id );
			if ( $creditnote->is_valid && ! $creditnote->exists ) {
				global $wpwing_wcpdf_document;
				$wpwing_wcpdf_document = $creditnote;
				$wpwing_wcpdf_document->save();
			}
		}

		// -------------------------------------------------------------------------
		// Admin notices
		// -------------------------------------------------------------------------

		/**
		 * Show admin notices set by pro document actions.
		 */
		public function show_pro_admin_notices() {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended
			if ( ! isset( $_GET['wpwing_pro_notice'] ) ) {
				return;
			}

			$notice = sanitize_key( $_GET['wpwing_pro_notice'] );

			$messages = array(
				'proforma_created'   => array( 'success', __( 'Proforma invoice created successfully.', 'wpwing-pdf-invoice-pro' ) ),
				'proforma_cancelled' => array( 'warning', __( 'Proforma invoice has been cancelled.', 'wpwing-pdf-invoice-pro' ) ),
			);

			if ( isset( $messages[ $notice ] ) ) {
				list( $type, $message ) = $messages[ $notice ];
				printf(
					'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
					esc_attr( $type ),
					esc_html( $message )
				);
			}
			// phpcs:enable WordPress.Security.NonceVerification.Recommended
		}
	}
}
