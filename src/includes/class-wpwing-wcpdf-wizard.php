<?php
/**
 * First-run Setup Wizard: activation redirect, step flow, and relaunch link.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Wizard' ) ) {

	/**
	 * Handles the first-run Setup Wizard: the activation redirect, step
	 * rendering and save/sanitize, and the "Re-launch Setup Wizard" link
	 * shown on the Settings screen.
	 *
	 * @since 1.14.0
	 */
	class WPWing_WcPdf_Wizard {

		/**
		 * Hidden admin page slug.
		 */
		const SLUG = 'wpwing-wcpdf-wizard';

		/**
		 * Option storing whether the wizard has been completed (or skipped) at least once.
		 */
		const OPTION_COMPLETED = 'wpwing_wcpdf_wizard_completed';

		/**
		 * Transient scheduling a one-time redirect to the wizard on activation.
		 */
		const TRANSIENT_REDIRECT = 'wpwing_wcpdf_wizard_redirect';

		/**
		 * Ordered step slugs. 'finish' is a display-only summary screen, not a form step.
		 *
		 * @var string[]
		 */
		const STEPS = array( 'shop_info', 'logo', 'paper_size', 'auto_statuses', 'email_attachment', 'finish' );

		/**
		 * Settings instance.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		private $settings;

		/**
		 * Constructor.
		 *
		 * @param WPWing_WcPdf_Settings $settings Settings instance.
		 */
		public function __construct( WPWing_WcPdf_Settings $settings ) {
			$this->settings = $settings;
		}

		/**
		 * Register all wizard hooks.
		 */
		public function register() {
			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'maybe_redirect_to_wizard' ) );
			add_action( 'wpwing_wcpdf_settings_before_form', array( $this, 'render_relaunch_link' ) );
			add_filter( 'admin_title', array( $this, 'filter_admin_title' ) );
		}

		/**
		 * Fill in the browser tab title. get_admin_page_title() returns empty for this page
		 * because it is registered with an empty parent slug (hidden pages aren't found in
		 * the $submenu lookup get_admin_page_title() relies on).
		 *
		 * @param string $admin_title Default admin title.
		 * @return string
		 */
		public function filter_admin_title( $admin_title ) {
			$screen = get_current_screen();
			if ( $screen && 'admin_page_' . self::SLUG === $screen->id ) {
				return esc_html__( 'Setup Wizard', 'wpwing-wcpdf' ) . $admin_title;
			}
			return $admin_title;
		}

		/**
		 * Register the wizard's hidden admin page. An empty parent slug keeps it out of
		 * the admin sidebar while leaving it reachable at admin.php?page=<SLUG>.
		 *
		 * Also hooks handle_actions() onto this page's own "load-{hook}" action, which fires
		 * before wp-admin/admin-header.php sends any output - unlike the render() callback
		 * itself, which WP core invokes only after that header output has already started,
		 * too late for wp_safe_redirect() to work.
		 */
		public function add_menu() {
			$hook_suffix = add_submenu_page(
				'',
				esc_html__( 'Setup Wizard', 'wpwing-wcpdf' ),
				esc_html__( 'Setup Wizard', 'wpwing-wcpdf' ),
				'manage_woocommerce',
				self::SLUG,
				array( $this, 'render' )
			);

			if ( $hook_suffix ) {
				add_action( "load-{$hook_suffix}", array( $this, 'handle_actions' ) );
			}
		}

		/**
		 * Gate access and process any pending skip/step-save action for the wizard page.
		 * Runs on this page's "load-{hook}" action, before any output has been sent.
		 */
		public function handle_actions() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpwing-wcpdf' ) );
			}

			$this->maybe_handle_skip_all();
			$this->maybe_handle_submit();
		}

		/**
		 * Consume the activation transient and redirect to the wizard once, if appropriate.
		 */
		public function maybe_redirect_to_wizard() {

			if ( ! get_transient( self::TRANSIENT_REDIRECT ) ) {
				return;
			}
			delete_transient( self::TRANSIENT_REDIRECT );

			$should_redirect = self::should_redirect_to_wizard(
				wp_doing_ajax(),
				is_network_admin(),
				isset( $_GET['activate-multi'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only activation-context check, no state change.
				current_user_can( 'manage_woocommerce' )
			);

			if ( ! $should_redirect ) {
				return;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG ) );
			exit;
		}

		/**
		 * Pure decision helper for maybe_redirect_to_wizard(), kept separate so it is unit-testable
		 * without exercising the redirect/exit call itself.
		 *
		 * @param bool $is_ajax          Whether this is an AJAX request.
		 * @param bool $is_network_admin Whether this is a network admin screen.
		 * @param bool $is_bulk_activate Whether the current request is a bulk plugin activation.
		 * @param bool $can_manage       Whether the current user can manage WooCommerce.
		 * @return bool
		 */
		public static function should_redirect_to_wizard( $is_ajax, $is_network_admin, $is_bulk_activate, $can_manage ) {
			return ! $is_ajax && ! $is_network_admin && ! $is_bulk_activate && $can_manage;
		}

		/**
		 * Whitelist a step slug, falling back to the first step.
		 *
		 * @param string $raw Raw step value.
		 * @return string
		 */
		public static function sanitize_step( $raw ) {
			$step = sanitize_key( $raw );
			return in_array( $step, self::STEPS, true ) ? $step : self::STEPS[0];
		}

		/**
		 * Return the step that follows the given one, or 'finish' if it is the last data step.
		 *
		 * @param string $step Current step slug.
		 * @return string
		 */
		public static function get_next_step( $step ) {
			$index = array_search( $step, self::STEPS, true );
			if ( false === $index || ! isset( self::STEPS[ $index + 1 ] ) ) {
				return self::STEPS[ count( self::STEPS ) - 1 ];
			}
			return self::STEPS[ $index + 1 ];
		}

		/**
		 * Build the wizard URL for a given step.
		 *
		 * @param string $step Step slug.
		 * @return string
		 */
		private function step_url( $step ) {
			return add_query_arg(
				array(
					'page' => self::SLUG,
					'step' => $step,
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Mark the wizard as completed.
		 */
		private function mark_completed() {
			update_option( self::OPTION_COMPLETED, 1 );
		}

		/**
		 * Render the "Re-launch Setup Wizard" link on the Settings screen.
		 */
		public function render_relaunch_link() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}
			printf(
				'<p class="wpwing-wcpdf-wizard-relaunch"><a href="%s">%s</a></p>',
				esc_url( $this->step_url( self::STEPS[0] ) ),
				esc_html__( 'Re-launch Setup Wizard', 'wpwing-wcpdf' )
			);
		}

		/**
		 * Field definitions for a given step, consumed by both render and save.
		 *
		 * @param string $step Step slug.
		 * @return array[]
		 */
		private function get_step_fields( $step ) {

			switch ( $step ) {
				case 'shop_info':
					return array(
						array(
							'id'          => 'company_name_text',
							'type'        => 'text',
							'title'       => esc_html__( 'Company name:', 'wpwing-wcpdf' ),
							'placeholder' => 'Your company name',
						),
						array(
							'id'          => 'company_address',
							'type'        => 'text',
							'title'       => esc_html__( 'Street address:', 'wpwing-wcpdf' ),
							'placeholder' => '123 Main St',
						),
						array(
							'id'          => 'company_city',
							'type'        => 'text',
							'title'       => esc_html__( 'City:', 'wpwing-wcpdf' ),
							'placeholder' => 'City',
						),
						array(
							'id'          => 'company_zip',
							'type'        => 'text',
							'title'       => esc_html__( 'ZIP / Postcode:', 'wpwing-wcpdf' ),
							'placeholder' => '10001',
						),
						array(
							'id'          => 'company_country',
							'type'        => 'text',
							'title'       => esc_html__( 'Country:', 'wpwing-wcpdf' ),
							'placeholder' => 'United States',
						),
					);

				case 'logo':
					return array(
						array(
							'id'          => 'company_logo_upload',
							'type'        => 'upload',
							'title'       => esc_html__( 'Company logo:', 'wpwing-wcpdf' ),
							'placeholder' => 'Logo URL',
						),
					);

				case 'paper_size':
					return array(
						array(
							'id'      => 'paper_size',
							'type'    => 'radio',
							'title'   => esc_html__( 'Paper size:', 'wpwing-wcpdf' ),
							'options' => array(
								'A4'     => esc_html__( 'A4', 'wpwing-wcpdf' ),
								'letter' => esc_html__( 'Letter', 'wpwing-wcpdf' ),
							),
						),
					);

				case 'auto_statuses':
					$statuses = $this->settings->get_order_statuses();
					return array(
						array(
							'id'      => 'invoice_auto_statuses',
							'type'    => 'checkboxgroup',
							'title'   => esc_html__( 'Auto-generate invoice on status:', 'wpwing-wcpdf' ),
							'desc'    => esc_html__( 'Invoice is created automatically when an order reaches one of these statuses. Only created once per order.', 'wpwing-wcpdf' ),
							'options' => $statuses,
						),
						array(
							'id'      => 'packing_auto_statuses',
							'type'    => 'checkboxgroup',
							'title'   => esc_html__( 'Auto-generate packing slip on status:', 'wpwing-wcpdf' ),
							'desc'    => esc_html__( 'Packing slip is created automatically when an order reaches one of these statuses. Only created once per order.', 'wpwing-wcpdf' ),
							'options' => $statuses,
						),
						array(
							'id'      => 'delivery_auto_statuses',
							'type'    => 'checkboxgroup',
							'title'   => esc_html__( 'Auto-generate delivery note on status:', 'wpwing-wcpdf' ),
							'desc'    => esc_html__( 'Delivery note is created automatically when an order reaches one of these statuses. Only created once per order.', 'wpwing-wcpdf' ),
							'options' => $statuses,
						),
					);

				case 'email_attachment':
					return array(
						array(
							'id'      => 'invoice_attach_to_emails',
							'type'    => 'checkboxgroup',
							'title'   => esc_html__( 'Attach invoice PDF to emails:', 'wpwing-wcpdf' ),
							'desc'    => esc_html__( 'Invoice PDF is attached to the selected WooCommerce emails. Invoice is auto-created if it does not exist yet.', 'wpwing-wcpdf' ),
							'options' => $this->settings->get_email_options(),
						),
					);

				default:
					return array();
			}
		}

		/**
		 * Sanitize and persist every field for a step, then apply any paired checkbox gates.
		 *
		 * @param string $step Step slug.
		 */
		private function save_step( $step ) {

			$api           = $this->settings->api();
			$settings_name = $api->get_settings_name();
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce already verified by the caller before save_step() runs.
			$posted = isset( $_POST[ $settings_name ] ) ? map_deep( wp_unslash( $_POST[ $settings_name ] ), 'sanitize_text_field' ) : array();

			foreach ( $this->get_step_fields( $step ) as $field ) {
				$id = $field['id'];
				// Re-sanitized per its registered type below - the map_deep() pass above only
				// satisfies static analysis and is not relied on for correctness.
				$raw   = isset( $posted[ $id ] ) ? $posted[ $id ] : ( 'checkboxgroup' === $field['type'] ? array() : '' );
				$value = $api->sanitize_value( $id, $raw );
				$this->settings->set_option( $id, $value );
			}

			$this->apply_checkbox_gates( $step );
		}

		/**
		 * Force the checkbox fields that gate PDF rendering when their paired data was entered.
		 * Only shop_info and logo write fields that are gated this way.
		 *
		 * @param string $step Step slug.
		 */
		private function apply_checkbox_gates( $step ) {

			$fields = array( 'company_name_text', 'company_address', 'company_city', 'company_zip', 'company_country', 'company_logo_upload' );

			$values = array();
			foreach ( $fields as $field ) {
				$values[ $field ] = $this->settings->get_option( $field );
			}

			foreach ( self::get_checkbox_gates_to_apply( $step, $values ) as $checkbox_id ) {
				$this->settings->set_option( $checkbox_id, 1 );
			}
		}

		/**
		 * Pure decision helper for apply_checkbox_gates(): given the current values of the
		 * fields a step can write, return which paired checkbox option keys should be forced
		 * to 1 so the entered data actually renders on generated PDFs. Kept separate from
		 * apply_checkbox_gates() so it is unit-testable without a live Settings instance.
		 *
		 * @param string $step   Step slug.
		 * @param array  $values Map of field id to its current (already-saved) value.
		 * @return string[] Checkbox option keys to force to 1.
		 */
		public static function get_checkbox_gates_to_apply( $step, array $values ) {

			$gates = array();

			if ( 'shop_info' === $step ) {
				if ( ! empty( $values['company_name_text'] ) ) {
					$gates[] = 'company_name_checkbox';
				}
				foreach ( array( 'company_address', 'company_city', 'company_zip', 'company_country' ) as $address_field ) {
					if ( ! empty( $values[ $address_field ] ) ) {
						$gates[] = 'company_details_checkbox';
						break;
					}
				}
			} elseif ( 'logo' === $step ) {
				if ( ! empty( $values['company_logo_upload'] ) ) {
					$gates[] = 'company_logo_checkbox';
				}
			}

			return $gates;
		}

		/**
		 * Handle the persistent "Skip setup" action. Exits on success.
		 */
		private function maybe_handle_skip_all() {

			if ( ! isset( $_GET['wpwing_wcpdf_wizard_skip'] ) ) {
				return;
			}

			if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_wcpdf_wizard_skip' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wpwing-wcpdf' ) );
			}
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wcpdf' ) );
			}

			$this->mark_completed();
			wp_safe_redirect( admin_url( 'admin.php?page=' . sprintf( '%s-settings', sanitize_key( WPWING_WCPDF_DIR_NAME ) ) ) );
			exit;
		}

		/**
		 * Handle a step form submission, if any. Exits on success.
		 */
		private function maybe_handle_submit() {

			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce is verified explicitly below.
			if ( empty( $_POST['wpwing_wcpdf_wizard_step'] ) ) {
				return;
			}

			if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'wpwing_wcpdf_wizard_step' ) ) {
				wp_die( esc_html__( 'Security check failed.', 'wpwing-wcpdf' ) );
			}
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( esc_html__( 'You do not have permission to perform this action.', 'wpwing-wcpdf' ) );
			}

			$posted_step = self::sanitize_step( sanitize_key( wp_unslash( $_POST['wpwing_wcpdf_wizard_step'] ) ) );
			$this->save_step( $posted_step );

			wp_safe_redirect( $this->step_url( self::get_next_step( $posted_step ) ) );
			exit;
		}

		/**
		 * Render the wizard page for the current step. Any pending skip/submit action has
		 * already been processed by handle_actions() on the earlier "load-{hook}" action.
		 */
		public function render() {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only step navigation, no state change.
			$step = self::sanitize_step( isset( $_GET['step'] ) ? sanitize_key( wp_unslash( $_GET['step'] ) ) : '' );

			?>
			<div class="wrap wpwing-wizard-wrap">
				<h1><?php esc_html_e( 'PDF Invoice Setup Wizard', 'wpwing-wcpdf' ); ?></h1>

				<?php if ( 'finish' !== $step ) : ?>
					<p class="wpwing-wizard-skip-all">
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=' . self::SLUG . '&wpwing_wcpdf_wizard_skip=1' ), 'wpwing_wcpdf_wizard_skip' ) ); ?>">
							<?php esc_html_e( 'Skip setup', 'wpwing-wcpdf' ); ?>
						</a>
					</p>
				<?php endif; ?>

				<ol class="wpwing-wizard-steps">
					<?php foreach ( self::STEPS as $step_slug ) : ?>
						<li class="<?php echo esc_attr( $step_slug === $step ? 'wpwing-wizard-step-current' : '' ); ?>">
							<?php echo esc_html( $this->get_step_label( $step_slug ) ); ?>
						</li>
					<?php endforeach; ?>
				</ol>

				<div class="wpwing-wizard-card">
					<?php
					$method = 'render_step_' . $step;
					if ( method_exists( $this, $method ) ) {
						$this->{$method}( $step );
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Human-readable label for a step, used by the progress indicator.
		 *
		 * @param string $step Step slug.
		 * @return string
		 */
		private function get_step_label( $step ) {

			$labels = array(
				'shop_info'        => esc_html__( 'Shop Info', 'wpwing-wcpdf' ),
				'logo'             => esc_html__( 'Logo', 'wpwing-wcpdf' ),
				'paper_size'       => esc_html__( 'Paper Size', 'wpwing-wcpdf' ),
				'auto_statuses'    => esc_html__( 'Auto-generate', 'wpwing-wcpdf' ),
				'email_attachment' => esc_html__( 'Email Attachment', 'wpwing-wcpdf' ),
				'finish'           => esc_html__( 'Finish', 'wpwing-wcpdf' ),
			);

			return isset( $labels[ $step ] ) ? $labels[ $step ] : $step;
		}

		/**
		 * Render a step's fields table plus its Save & Continue / Skip this step controls.
		 *
		 * @param string $step Step slug.
		 */
		private function render_data_step( $step ) {

			$api = $this->settings->api();
			?>
			<form method="post" action="<?php echo esc_url( $this->step_url( $step ) ); ?>">
				<?php wp_nonce_field( 'wpwing_wcpdf_wizard_step' ); ?>
				<input type="hidden" name="wpwing_wcpdf_wizard_step" value="<?php echo esc_attr( $step ); ?>" />

				<table class="form-table">
					<tbody>
						<?php foreach ( $this->get_step_fields( $step ) as $field ) : ?>
							<tr>
								<th scope="row">
									<label for="<?php echo esc_attr( $field['id'] ); ?>-field"><?php echo esc_html( $field['title'] ); ?></label>
								</th>
								<td>
									<?php $api->field_callback( $field ); ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<p class="wpwing-wizard-actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save & Continue', 'wpwing-wcpdf' ); ?></button>
					<a class="wpwing-wizard-skip-step" href="<?php echo esc_url( $this->step_url( self::get_next_step( $step ) ) ); ?>">
						<?php esc_html_e( 'Skip this step', 'wpwing-wcpdf' ); ?>
					</a>
				</p>
			</form>
			<?php
		}

		/**
		 * Step: shop name and address.
		 */
		private function render_step_shop_info() {
			$this->render_data_step( 'shop_info' );
		}

		/**
		 * Step: logo upload.
		 */
		private function render_step_logo() {
			$this->render_data_step( 'logo' );
		}

		/**
		 * Step: paper size.
		 */
		private function render_step_paper_size() {
			$this->render_data_step( 'paper_size' );
		}

		/**
		 * Step: auto-generate statuses per document type.
		 */
		private function render_step_auto_statuses() {
			$this->render_data_step( 'auto_statuses' );
		}

		/**
		 * Step: email attachment (invoice only).
		 */
		private function render_step_email_attachment() {
			$this->render_data_step( 'email_attachment' );
		}

		/**
		 * Final screen: marks the wizard complete and links out to docs and the review page.
		 */
		private function render_step_finish() {

			$this->mark_completed();

			$docs_url     = plugins_url( 'docs/', WPWING_WCPDF_FILE );
			$review_url   = 'https://wordpress.org/support/plugin/' . sanitize_key( WPWING_WCPDF_DIR_NAME ) . '/reviews/#new-post';
			$settings_url = admin_url( 'admin.php?page=' . sprintf( '%s-settings', sanitize_key( WPWING_WCPDF_DIR_NAME ) ) );
			?>
			<p><?php esc_html_e( "You're all set! PDF Invoice and Packing Slip for WooCommerce is ready to go.", 'wpwing-wcpdf' ); ?></p>
			<p class="wpwing-wizard-actions">
				<a class="button button-primary" href="<?php echo esc_url( $settings_url ); ?>"><?php esc_html_e( 'Go to Settings', 'wpwing-wcpdf' ); ?></a>
				<a class="button" href="<?php echo esc_url( $docs_url ); ?>" target="_blank"><?php esc_html_e( 'Documentation', 'wpwing-wcpdf' ); ?></a>
				<a class="button" href="<?php echo esc_url( $review_url ); ?>" target="_blank"><?php esc_html_e( 'Leave a Review', 'wpwing-wcpdf' ); ?></a>
			</p>
			<?php
		}
	}
}
