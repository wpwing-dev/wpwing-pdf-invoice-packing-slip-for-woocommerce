<?php
/**
 * Plugin settings: registration, retrieval, and tab management.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Settings' ) ) {
	/**
	 * Manages plugin settings via a settings API and WP admin tabs.
	 */
	class WPWing_WcPdf_Settings {

		/**
		 * Singleton instance.
		 *
		 * @var WPWing_WcPdf_Settings|null
		 */
		private static $instance = null;

		/**
		 * Settings API instance.
		 *
		 * @var WPWing_WcPdf_Settings_API|null
		 */
		protected $_api; // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore -- legacy name kept for backward compatibility.

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_action( 'init', array( $this, 'add_settings' ), 2 );
			add_action( 'init', array( $this, 'load_api' ), 5 );
			do_action( 'wpwing_wcpdf_settings_init', $this );
		}

		/**
		 * Return the singleton instance, creating it if needed.
		 *
		 * @return WPWing_WcPdf_Settings
		 */
		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Lazily instantiate the settings API object.
		 */
		public function load_api() {
			if ( ! $this->_api ) {
				require_once 'class-wpwing-wcpdf-settings-api.php';
				$this->_api = new WPWing_WcPdf_Settings_API();
			}
		}

		/**
		 * Return the settings API object, loading it first if needed.
		 *
		 * @return WPWing_WcPdf_Settings_API
		 */
		public function api() {
			if ( ! $this->_api ) {
				$this->load_api();
			}

			return $this->_api;
		}

		/**
		 * Register all plugin setting tabs and fields.
		 */
		public function add_settings() {

			do_action( 'before_wpwing_wcpdf_settings', $this );

			$templates      = $this->get_available_templates();
			$order_statuses = $this->get_order_statuses();
			$email_options  = $this->get_email_options();

			$this->add_general_settings( $order_statuses, $email_options );
			$this->add_template_settings( $templates );
			$this->add_status_settings();

			do_action( 'after_wpwing_wcpdf_settings', $this );
		}

		/**
		 * Return an array of available template directory names.
		 *
		 * @return array
		 */
		private function get_available_templates() {
			$templates = array();
			if ( defined( 'WPWING_WCPDF_TEMPLATE_DIR' ) && is_dir( WPWING_WCPDF_TEMPLATE_DIR ) ) {
				foreach ( (array) glob( WPWING_WCPDF_TEMPLATE_DIR . '*', GLOB_ONLYDIR ) as $dir ) {
					$name               = basename( $dir );
					$templates[ $name ] = ucfirst( $name );
				}
			}
			return $templates ? $templates : array( 'default' => 'Default' );
		}

		/**
		 * Return an array of WooCommerce order statuses keyed without the wc- prefix.
		 *
		 * @return array
		 */
		public function get_order_statuses() {
			$statuses = array();
			if ( function_exists( 'wc_get_order_statuses' ) ) {
				foreach ( wc_get_order_statuses() as $status => $label ) {
					$statuses[ substr( $status, 3 ) ] = $label; // Strip 'wc-' prefix.
				}
			}
			return $statuses;
		}

		/**
		 * Return the WooCommerce email IDs available for invoice attachment.
		 *
		 * @return array
		 */
		public function get_email_options() {
			return array(
				'customer_processing_order' => esc_html__( 'Processing Order', 'wpwing-wcpdf' ),
				'customer_completed_order'  => esc_html__( 'Completed Order', 'wpwing-wcpdf' ),
				'customer_invoice'          => esc_html__( 'Customer Invoice / Order Details', 'wpwing-wcpdf' ),
				'customer_on_hold_order'    => esc_html__( 'Order On-Hold', 'wpwing-wcpdf' ),
			);
		}

		/**
		 * Register the General settings tab.
		 *
		 * @param array $order_statuses Available order statuses.
		 * @param array $email_options  Available email IDs.
		 */
		private function add_general_settings( $order_statuses, $email_options ) {
			$this->add_setting(
				'wpwing_pdf_general',
				esc_html__( 'General', 'wpwing-wcpdf' ),
				apply_filters(
					'wpwing_wcpdf_general_settings_section',
					array(
						array(
							'title'  => esc_html__( 'General Section', 'wpwing-wcpdf' ),
							'desc'   => esc_html__( 'Basic settings for PDF Invoice', 'wpwing-wcpdf' ),
							'fields' => apply_filters(
								'wpwing_wcpdf_general_settings_fields',
								array(
									array(
										'id'          => 'invoice_number',
										'type'        => 'text',
										'title'       => esc_html__( 'Next invoice number:', 'wpwing-wcpdf' ),
										'desc'        => 'Invoice number for next invoice document.',
										'placeholder' => 'Invoice number',
									),
									array(
										'id'          => 'invoice_number_format',
										'type'        => 'text',
										'title'       => esc_html__( 'Invoice number format:', 'wpwing-wcpdf' ),
										'desc'        => 'Tokens: {number} = serial, {year} = full year, {month} = 2-digit month, {day} = 2-digit day. Example: INV-{number}/{year}{month} → INV-1/202605',
										'placeholder' => '{number}',
										'default'     => '{number}',
									),
									array(
										'id'          => 'invoice_date_format',
										'type'        => 'text',
										'title'       => esc_html__( 'Invoice date format:', 'wpwing-wcpdf' ),
										'desc'        => 'Set date format as it should appear on invoices.',
										'placeholder' => 'd/m/Y',
									),
									array(
										'id'          => 'invoice_due_date_days',
										'type'        => 'number',
										'title'       => esc_html__( 'Payment due within (days):', 'wpwing-wcpdf' ),
										'desc'        => 'Days from order creation date until payment is due. Leave empty or 0 to hide the due date on the invoice.',
										'placeholder' => '0',
										'default'     => 0,
									),
									array(
										'id'      => 'invoice_number_reset_yearly',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Reset invoice number yearly:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'      => 'invoice_button_behavior',
										'type'    => 'radio',
										'title'   => esc_html__( 'PDF invoice button behaviour:', 'wpwing-wcpdf' ),
										'options' => array(
											'download' => esc_html__( 'Download PDF', 'wpwing-wcpdf' ),
											'open'     => esc_html__( 'Open PDF on Browser', 'wpwing-wcpdf' ),
										),
										'default' => 'download',
									),
									array(
										'id'      => 'paper_size',
										'type'    => 'radio',
										'title'   => esc_html__( 'Paper size:', 'wpwing-wcpdf' ),
										'options' => array(
											'A4'     => esc_html__( 'A4', 'wpwing-wcpdf' ),
											'letter' => esc_html__( 'Letter', 'wpwing-wcpdf' ),
										),
										'default' => 'A4',
									),
									array(
										'id'      => 'invoice_disable_free_orders',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Disable invoice for free orders:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'      => 'invoice_auto_statuses',
										'type'    => 'checkboxgroup',
										'title'   => esc_html__( 'Auto-generate invoice on status:', 'wpwing-wcpdf' ),
										'desc'    => esc_html__( 'Invoice is created automatically when an order reaches one of these statuses. Only created once per order.', 'wpwing-wcpdf' ),
										'options' => $order_statuses,
										'default' => array(),
									),
									array(
										'id'      => 'packing_auto_statuses',
										'type'    => 'checkboxgroup',
										'title'   => esc_html__( 'Auto-generate packing slip on status:', 'wpwing-wcpdf' ),
										'desc'    => esc_html__( 'Packing slip is created automatically when an order reaches one of these statuses. Only created once per order.', 'wpwing-wcpdf' ),
										'options' => $order_statuses,
										'default' => array(),
									),
									array(
										'id'      => 'delivery_auto_statuses',
										'type'    => 'checkboxgroup',
										'title'   => esc_html__( 'Auto-generate delivery note on status:', 'wpwing-wcpdf' ),
										'desc'    => esc_html__( 'Delivery note is created automatically when an order reaches one of these statuses. Only created once per order.', 'wpwing-wcpdf' ),
										'options' => $order_statuses,
										'default' => array(),
									),
									array(
										'id'      => 'invoice_attach_to_emails',
										'type'    => 'checkboxgroup',
										'title'   => esc_html__( 'Attach invoice PDF to emails:', 'wpwing-wcpdf' ),
										'desc'    => esc_html__( 'Invoice PDF is attached to the selected WooCommerce emails. Invoice is auto-created if it does not exist yet.', 'wpwing-wcpdf' ),
										'options' => $email_options,
										'default' => array(),
									),
								)
							),
						),
					)
				),
				apply_filters( 'wpwing_wcpdf_general_settings_default_active', true )
			);
		}

		/**
		 * Register the Template settings tab.
		 *
		 * @param array $templates Available template names.
		 */
		private function add_template_settings( $templates ) {
			$this->add_setting(
				'wpwing_pdf_template',
				esc_html__( 'Template', 'wpwing-wcpdf' ),
				apply_filters(
					'wpwing_wcpdf_template_settings_section',
					array(
						array(
							'title'  => esc_html__( 'Template Section', 'wpwing-wcpdf' ),
							'desc'   => esc_html__( 'Basic template for PDF Invoice', 'wpwing-wcpdf' ),
							'fields' => apply_filters(
								'wpwing_wcpdf_template_settings_fields',
								array(
									array(
										'id'      => 'invoice_template',
										'type'    => 'select',
										'title'   => esc_html__( 'Invoice template:', 'wpwing-wcpdf' ),
										'options' => $templates,
										'default' => 'default',
									),
									array(
										'id'      => 'packing_template',
										'type'    => 'select',
										'title'   => esc_html__( 'Packing slip template:', 'wpwing-wcpdf' ),
										'options' => $templates,
										'default' => 'default',
									),
									array(
										'id'      => 'delivery_template',
										'type'    => 'select',
										'title'   => esc_html__( 'Delivery note template:', 'wpwing-wcpdf' ),
										'options' => $templates,
										'default' => 'default',
									),
									array(
										'id'      => 'delivery_show_customer_note',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show customer order note on delivery note:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'    => 'invoice_preview_btn',
										'type'  => 'button',
										'title' => esc_html__( 'Preview invoice', 'wpwing-wcpdf' ),
										'label' => esc_html__( 'Preview Invoice', 'wpwing-wcpdf' ),
										'desc'  => esc_html__( 'Preview the invoice template using your most recent order.', 'wpwing-wcpdf' ),
									),
									array(
										'id'      => 'invoice_qr_checkbox',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show QR code on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'      => 'invoice_qr_content',
										'type'    => 'radio',
										'title'   => esc_html__( 'QR code content:', 'wpwing-wcpdf' ),
										'options' => array(
											'order_url' => esc_html__( 'Order view URL', 'wpwing-wcpdf' ),
											'custom'    => esc_html__( 'Custom text', 'wpwing-wcpdf' ),
										),
										'default' => 'order_url',
									),
									array(
										'id'          => 'invoice_qr_custom_text',
										'type'        => 'text',
										'title'       => esc_html__( 'QR custom text:', 'wpwing-wcpdf' ),
										'desc'        => esc_html__( 'Used when content is set to Custom text. Placeholders: {order_number}, {invoice_number}.', 'wpwing-wcpdf' ),
										'placeholder' => 'Order {order_number}',
									),
									array(
										'id'      => 'company_name_checkbox',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show company name on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'          => 'company_name_text',
										'type'        => 'text',
										'title'       => esc_html__( 'Company name:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => 'Your company name',
									),
									array(
										'id'      => 'company_logo_checkbox',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show company logo on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'          => 'company_logo_upload',
										'type'        => 'upload',
										'title'       => esc_html__( 'Company logo:', 'wpwing-wcpdf' ),
										'placeholder' => 'Logo URL',
									),
									array(
										'id'      => 'company_details_checkbox',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show company details on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'          => 'company_address',
										'type'        => 'text',
										'title'       => esc_html__( 'Street address:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => '123 Main St',
									),
									array(
										'id'          => 'company_city',
										'type'        => 'text',
										'title'       => esc_html__( 'City:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => 'City',
									),
									array(
										'id'          => 'company_zip',
										'type'        => 'text',
										'title'       => esc_html__( 'ZIP / Postcode:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => '10001',
									),
									array(
										'id'          => 'company_country',
										'type'        => 'text',
										'title'       => esc_html__( 'Country:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => 'United States',
									),
									array(
										'id'          => 'company_phone',
										'type'        => 'text',
										'title'       => esc_html__( 'Phone:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => '+1 (555) 000-0000',
									),
									array(
										'id'          => 'company_email',
										'type'        => 'text',
										'title'       => esc_html__( 'Company email:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => 'info@example.com',
									),
									array(
										'id'          => 'company_vat',
										'type'        => 'text',
										'title'       => esc_html__( 'VAT / Tax ID:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => 'VAT12345678',
									),
									array(
										'id'      => 'company_notes_checkbox',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show notes on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'          => 'company_notes_text',
										'type'        => 'textarea',
										'title'       => esc_html__( 'Your notes:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => 'Write your notes',
									),
									array(
										'id'      => 'company_footer_checkbox',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show footer on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'          => 'company_footer_text',
										'type'        => 'textarea',
										'title'       => esc_html__( 'Footer text:', 'wpwing-wcpdf' ),
										'desc'        => '',
										'placeholder' => 'Write footer text',
									),
									array(
										'id'      => 'show_shipping_address',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show shipping address on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'      => 'show_product_sku',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show product SKU on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'      => 'show_customer_note',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show customer order note on invoice:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes',
										'default' => false,
									),
									array(
										'id'      => 'show_tax_breakdown',
										'type'    => 'checkbox',
										'title'   => esc_html__( 'Show tax breakdown in totals:', 'wpwing-wcpdf' ),
										'desc'    => 'Yes - show each tax class as a separate line',
										'default' => true,
									),
									array(
										'id'          => 'template_custom_css',
										'type'        => 'textarea',
										'title'       => esc_html__( 'Custom CSS:', 'wpwing-wcpdf' ),
										'desc'        => esc_html__( 'Applied to invoice, packing slip and delivery note, after the template styles. Example: .company-name { font-size: 18px; }', 'wpwing-wcpdf' ),
										'placeholder' => '.company-name { font-size: 18px; }',
									),
								)
							),
						),
					)
				),
				apply_filters( 'wpwing_wcpdf_template_settings_default_active', false )
			);
		}

		/**
		 * Register the System Status tab: a read-only diagnostics table plus a
		 * "Copy system info" button, useful when contacting support.
		 */
		private function add_status_settings() {
			$this->add_setting(
				'wpwing_pdf_status',
				esc_html__( 'System Status', 'wpwing-wcpdf' ),
				array(
					array(
						'title'  => esc_html__( 'System Information', 'wpwing-wcpdf' ),
						'desc'   => esc_html__( 'Useful information to include when contacting support.', 'wpwing-wcpdf' ),
						'fields' => array(
							array(
								'id'       => 'system_status_info',
								'type'     => 'html',
								'title'    => '',
								'callback' => array( $this, 'render_system_status' ),
							),
						),
					),
				),
				false
			);
		}

		/**
		 * Render the System Status tab content: a diagnostics table and a
		 * "Copy system info" button that copies a plain-text summary.
		 *
		 * @return string
		 */
		public function render_system_status() {
			$rows = $this->get_system_status_rows();

			$plain_text = '';
			foreach ( $rows as $row ) {
				$line        = str_replace( array( '<br />', '&mdash;' ), array( ' - ', '-' ), $row['value_html'] );
				$plain_text .= $row['label'] . ': ' . trim( wp_strip_all_tags( $line ) ) . "\n";
			}

			ob_start();
			?>
			<table class="wpwing-status-table">
				<tbody>
					<?php foreach ( $rows as $row ) : ?>
						<tr>
							<th scope="row"><?php echo esc_html( $row['label'] ); ?></th>
							<td>
							<?php
							echo wp_kses(
								$row['value_html'],
								array(
									'code' => array(),
									'br'   => array(),
									'span' => array( 'class' => array() ),
								)
							);
							?>
								</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<p class="wpwing-status-actions">
				<button type="button" id="wpwing-copy-system-info" class="button"><?php esc_html_e( 'Copy system info', 'wpwing-wcpdf' ); ?></button>
				<span id="wpwing-copy-system-info-status" class="description"></span>
			</p>
			<textarea id="wpwing-status-copy-source" class="wpwing-status-copy-source" readonly><?php echo esc_textarea( $plain_text ); ?></textarea>
			<?php
			return ob_get_clean();
		}

		/**
		 * Build the System Status rows: label plus a small pre-escaped HTML fragment.
		 *
		 * @return array[]
		 */
		private function get_system_status_rows() {
			global $wp_version;

			$wc_version     = defined( 'WC_VERSION' ) ? WC_VERSION : esc_html__( 'Not detected', 'wpwing-wcpdf' );
			$dompdf_version = $this->get_dompdf_version();

			$save_dir      = defined( 'WPWING_WCPDF_DOCUMENT_SAVE_DIR' ) ? WPWING_WCPDF_DOCUMENT_SAVE_DIR : '';
			$save_writable = $save_dir && wp_is_writable( $save_dir );

			$font_cache_dir   = trailingslashit( wp_upload_dir()['basedir'] ) . 'wpwing-pdf-fonts/';
			$font_cache_ready = is_dir( $font_cache_dir ) && file_exists( $font_cache_dir . 'fonts_ready_2' );

			// The folder is created on first PDF generation, so on a fresh install
			// it won't exist yet - that's not a writability problem, so check the
			// parent (uploads) directory in that case rather than reporting a false alarm.
			$font_cache_writable = is_dir( $font_cache_dir )
				? wp_is_writable( $font_cache_dir )
				: wp_is_writable( dirname( $font_cache_dir ) );

			if ( ! $font_cache_writable ) {
				$font_cache_status = esc_html__( 'Not writable - using bundled fonts (extended currency symbols may not render)', 'wpwing-wcpdf' );
			} elseif ( $font_cache_ready ) {
				$font_cache_status = esc_html__( 'Ready', 'wpwing-wcpdf' );
			} else {
				$font_cache_status = esc_html__( 'Not generated yet - registers on first PDF', 'wpwing-wcpdf' );
			}

			return array(
				array(
					'label'      => esc_html__( 'Plugin version', 'wpwing-wcpdf' ),
					'value_html' => '<code>' . esc_html( WPWING_WCPDF_VERSION ) . '</code>',
				),
				array(
					'label'      => esc_html__( 'WordPress version', 'wpwing-wcpdf' ),
					'value_html' => '<code>' . esc_html( $wp_version ) . '</code>',
				),
				array(
					'label'      => esc_html__( 'PHP version', 'wpwing-wcpdf' ),
					'value_html' => '<code>' . esc_html( PHP_VERSION ) . '</code>',
				),
				array(
					'label'      => esc_html__( 'WooCommerce version', 'wpwing-wcpdf' ),
					'value_html' => '<code>' . esc_html( $wc_version ) . '</code>',
				),
				array(
					'label'      => esc_html__( 'Dompdf version', 'wpwing-wcpdf' ),
					'value_html' => '<code>' . esc_html( $dompdf_version ) . '</code>',
				),
				array(
					'label'      => esc_html__( 'Document save folder', 'wpwing-wcpdf' ),
					'value_html' => $this->format_path_status( $save_dir, $save_writable ),
				),
				array(
					'label'      => esc_html__( 'Font cache folder', 'wpwing-wcpdf' ),
					'value_html' => $this->format_path_status( $font_cache_dir, $font_cache_writable ) . '<br />' . $font_cache_status,
				),
				array(
					'label'      => esc_html__( 'Active document types', 'wpwing-wcpdf' ),
					'value_html' => esc_html( implode( ', ', $this->get_active_document_types() ) ),
				),
			);
		}

		/**
		 * Format a folder path with a writable/not-writable status badge.
		 *
		 * @param string $path     Folder path.
		 * @param bool   $writable Whether the folder is writable.
		 * @return string
		 */
		private function format_path_status( $path, $writable ) {
			$badge_class = $writable ? 'wpwing-status-ok' : 'wpwing-status-warn';
			$badge_text  = $writable ? esc_html__( 'Writable', 'wpwing-wcpdf' ) : esc_html__( 'Not writable', 'wpwing-wcpdf' );

			return '<code>' . esc_html( $path ) . '</code> &mdash; <span class="' . esc_attr( $badge_class ) . '">' . $badge_text . '</span>';
		}

		/**
		 * Return the display labels of document type classes currently loaded.
		 *
		 * @return string[]
		 */
		private function get_active_document_types() {
			$types = array(
				array(
					'label' => esc_html__( 'Invoice', 'wpwing-wcpdf' ),
					'class' => 'WPWing_WcPdf_Invoice',
				),
				array(
					'label' => esc_html__( 'Packing Slip', 'wpwing-wcpdf' ),
					'class' => 'WPWing_WcPdf_Packing',
				),
				array(
					'label' => esc_html__( 'Delivery Note', 'wpwing-wcpdf' ),
					'class' => 'WPWing_WcPdf_Delivery',
				),
			);

			$active = array();
			foreach ( $types as $type ) {
				if ( class_exists( $type['class'] ) ) {
					$active[] = $type['label'];
				}
			}

			return $active;
		}

		/**
		 * Return the installed dompdf/dompdf version from the Composer install manifest.
		 *
		 * @return string
		 */
		private function get_dompdf_version() {
			$manifest = WPWING_WCPDF_VENDOR_DIR . 'composer/installed.json';

			if ( ! file_exists( $manifest ) ) {
				return esc_html__( 'Unknown', 'wpwing-wcpdf' );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local vendor manifest, not a remote request.
			$data     = json_decode( (string) file_get_contents( $manifest ), true );
			$packages = isset( $data['packages'] ) ? $data['packages'] : $data; // Composer 1.x had no 'packages' wrapper.

			if ( is_array( $packages ) ) {
				foreach ( $packages as $package ) {
					if ( isset( $package['name'], $package['version'] ) && 'dompdf/dompdf' === $package['name'] ) {
						return ltrim( $package['version'], 'v' );
					}
				}
			}

			return esc_html__( 'Unknown', 'wpwing-wcpdf' );
		}

		/**
		 * Register a settings tab with its sections and fields.
		 *
		 * @param string $tab_id       Unique tab identifier.
		 * @param string $tab_title    Tab display title.
		 * @param array  $tab_sections Array of section definitions.
		 * @param bool   $active       Whether this tab is active by default.
		 * @param bool   $is_pro_tab   Whether this is a pro-only tab.
		 * @param bool   $is_new       Whether to show a "New" badge on the tab.
		 */
		public function add_setting( $tab_id, $tab_title, $tab_sections, $active = false, $is_pro_tab = false, $is_new = false ) {
			add_filter(
				'wpwing_wcpdf_settings',
				function ( $fields ) use ( $tab_id, $tab_title, $tab_sections, $active, $is_pro_tab, $is_new ) {
					array_push(
						$fields,
						array(
							'id'       => $tab_id,
							'title'    => esc_html( $tab_title ),
							'active'   => $active,
							'sections' => $tab_sections,
							'is_pro'   => $is_pro_tab,
							'is_new'   => $is_new,
						)
					);

					return $fields;
				}
			);
		}

		/**
		 * Retrieve a single plugin option value by key.
		 *
		 * @param string $id Option key.
		 * @return mixed
		 */
		public function get_option( $id ) {
			// Avoid loading too early.
			if ( ! did_action( 'init' ) ) {
				wc_doing_it_wrong(
					__CLASS__ . '::' . __FUNCTION__,
					esc_html__( 'Get settings option should not be called before the init action.', 'wpwing-wcpdf' ),
					'1.0.0'
				);
			}

			return $this->api()->get_option( $id );
		}

		/**
		 * Persist a single plugin option value by key.
		 *
		 * @param string $key   Option key.
		 * @param mixed  $value Option value.
		 * @return mixed
		 */
		public function set_option( $key, $value ) {
			// Avoid loading too early.
			if ( ! did_action( 'init' ) ) {
				wc_doing_it_wrong(
					__CLASS__ . '::' . __FUNCTION__,
					esc_html__( 'Set settings option should not be called before the init action.', 'wpwing-wcpdf' ),
					'1.0.0'
				);
			}

			return $this->api()->set_option( $key, $value );
		}
	}
}
