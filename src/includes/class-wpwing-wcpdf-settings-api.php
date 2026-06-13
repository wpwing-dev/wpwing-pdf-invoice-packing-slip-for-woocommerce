<?php
/**
 * Settings API: admin menu, tabs, field rendering, and option storage.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

// 1. add settings: init priority 1.
// 2. initial class: init priority 2.
// 3. store defaults: init priority 3.
// 4. get defaults / do whatever you want to do.

if ( ! class_exists( 'WPWing_WcPdf_Settings_API' ) ) {

	/**
	 * Low-level settings API: handles WP options, admin tabs, and field rendering.
	 */
	class WPWing_WcPdf_Settings_API {

		/**
		 * Option name used to persist all plugin settings.
		 *
		 * @var string
		 */
		private $setting_name = 'wpwing_wcpdf_settings';

		/**
		 * Filterable option name (may differ from $setting_name via filter).
		 *
		 * @var string
		 */
		private $settings_name = '';

		/**
		 * URL query key used to trigger a full settings reset.
		 *
		 * @var string
		 */
		private $setting_reset_name = 'reset';

		/**
		 * URL query key used to show the pro tab.
		 *
		 * @var string
		 */
		private $show_pro_name = 'pro';

		/**
		 * Transient key for temporary option overrides.
		 *
		 * @var string
		 */
		private $transient_setting_name = '_temp_wpwing_wcpdf_options';

		/**
		 * Object-cache key for options.
		 *
		 * @var string
		 */
		private $cache_key = 'wpwing_wcpdf_options';

		/**
		 * Theme feature name for theme support overrides.
		 *
		 * @var string
		 */
		private $theme_feature_name = 'wpwing-wcpdf';

		/**
		 * Admin page/menu slug.
		 *
		 * @var string
		 */
		private $slug;

		/**
		 * Default values keyed by field ID.
		 *
		 * @var array
		 */
		private $defaults = array();

		/**
		 * Option key used to store reserved (protected) field values.
		 *
		 * @var string
		 */
		private $reserved_key = '';

		/**
		 * Field IDs whose values are stored separately and never overwritten on save.
		 *
		 * @var array
		 */
		private $reserved_fields = array();

		/**
		 * Registered settings fields grouped by tab.
		 *
		 * @var array
		 */
		private $fields = array();

		/**
		 * Allowed HTML tags for wp_kses() calls when rendering field HTML.
		 *
		 * @var array
		 */
		private $allowed_html = array(
			'fieldset' => array( 'class' => array() ),
			'label'    => array(),
			'input'    => array(
				'type'        => array(),
				'id'          => array(),
				'class'       => array(),
				'name'        => array(),
				'value'       => array(),
				'checked'     => array(),
				'placeholder' => array(),
				'readonly'    => array(),

			),
			'select'   => array(
				'id'       => array(),
				'class'    => array(),
				'name'     => array(),
				'value'    => array(),
				'readonly' => array(),
				'multiple' => array(),
				'size'     => array(),
			),
			'option'   => array(
				'value'    => array(),
				'selected' => array(),
			),
			'textarea' => array(
				'id'          => array(),
				'class'       => array(),
				'name'        => array(),
				'placeholder' => array(),
				'readonly'    => array(),
			),
			'a'        => array(
				'href'  => array(),
				'title' => array(),
				'class' => array(),
			),
			'p'        => array(
				'class' => array(),
			),
			'br'       => array(),
			'strong'   => array(),
			'button'   => array(
				'type'     => array(),
				'id'       => array(),
				'class'    => array(),
				'name'     => array(),
				'disabled' => array(),
			),
		);

		/**
		 * Constructor.
		 */
		public function __construct() {

			$this->settings_name      = apply_filters( 'wpwing_wcpdf_settings_name', $this->setting_name );
			$this->setting_reset_name = apply_filters( 'wpwing_wcpdf_settings_reset_name', $this->setting_reset_name );

			$this->slug = sprintf( '%s-settings', sanitize_key( WPWING_WCPDF_DIR_NAME ) );
			// License key.
			$this->fields          = apply_filters( 'wpwing_wcpdf_settings', $this->fields );
			$this->reserved_key    = sprintf( '%s_reserved', esc_html( $this->settings_name ) );
			$this->reserved_fields = apply_filters( 'wpwing_wcpdf_reserved_fields', array() );

			add_action( 'admin_menu', array( $this, 'add_menu' ) );
			add_action( 'admin_init', array( $this, 'redirect_parent_menu' ), 15 );

			add_action( 'init', array( $this, 'set_defaults' ), 8 );

			add_action( 'admin_init', array( $this, 'settings_init' ), 90 );

			add_filter( "pre_update_option_{$this->settings_name}", array( $this, 'before_update' ), 10, 3 );
			add_action( "update_option_{$this->settings_name}", array( $this, 'after_update' ), 10, 3 );

			add_filter( 'plugin_action_links_' . WPWING_WCPDF_BASE_NAME, array( $this, 'plugin_action_links' ) );
			add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );

			if ( apply_filters( 'show_wpwing_wcpdf_settings_link_on_admin_bar', false ) ) :
				add_action( 'wp_before_admin_bar_render', array( $this, 'add_admin_bar' ), 999 );
			endif;

			add_action( 'admin_footer', array( $this, 'admin_inline_js' ) );

			do_action( 'wpwing_wcpdf_setting_api_init', $this );
		}

		/**
		 * Retrieve reserved field data, optionally filtered by key.
		 *
		 * @param string|false $key Field key, or false to return all.
		 * @return mixed
		 */
		public function get_reserved( $key = false ) {

			$data = (array) get_option( $this->reserved_key );
			if ( $key ) {
				return isset( $data[ $key ] ) ? $data[ $key ] : null;
			} else {
				return $data;
			}
		}

		/**
		 * Persist reserved field values separately so they survive a full settings save.
		 *
		 * @param array $value Incoming options array from the settings form.
		 */
		public function save_reserved( $value ) {

			$reserved_data = array();
			foreach ( (array) $this->reserved_fields as $field_key ) {
				if ( ! empty( $value[ $field_key ] ) ) {
					$reserved_data[ $field_key ] = $value[ $field_key ];
				}
			}

			if ( ! empty( $reserved_data ) ) {
				update_option( $this->reserved_key, $reserved_data );
			} else {
				delete_option( $this->reserved_key );
			}
		}

		/**
		 * Hook fired before the settings option is updated.
		 *
		 * @param mixed  $value     New option value.
		 * @param mixed  $old_value Previous option value.
		 * @param string $option    Option name.
		 * @return mixed
		 */
		public function before_update( $value, $old_value, $option ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress hook callback must match hook signature.

			$this->save_reserved( $value );
			do_action( sprintf( 'before_update_%s_settings', esc_html( $this->settings_name ) ), $this );

			return $value;
		}

		/**
		 * Hook fired after the settings option is updated.
		 *
		 * @param mixed  $old_value Previous option value.
		 * @param mixed  $value     New option value.
		 * @param string $option    Option name.
		 * @return mixed
		 */
		public function after_update( $old_value, $value, $option ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress hook callback must match hook signature.

			return $value;
		}

		/**
		 * Admin inline js for settings tabs
		 *
		 * @since 1.0.0
		 */
		public function admin_inline_js() {

			?>
			<script type="text/javascript">
				jQuery( function ( $ ) {
					$( '#<?php echo esc_html( $this->slug ); ?>-wrap' ).on( 'click', '.nav-tab', function ( event ) {
						event.preventDefault()
						var target = $( this ).data( 'target' );
						$( this ).addClass( 'nav-tab-active' ).siblings().removeClass( 'nav-tab-active' );
						$( '#' + target ).show().siblings().hide();
						$( '#_last_active_tab' ).val( target );
					})
				})
			</script>
			<?php
		}

		/**
		 * Create dashboard menu for settings
		 *
		 * @since 1.0.0
		 */
		public function add_menu() {

			if ( empty( $this->fields ) ) {
				return '';
			}

			// Register the shared WPWing parent menu only if no other WPWing plugin
			// has already done so (guard pattern shared across all WPWing plugins).
			if ( '' === menu_page_url( 'wpwing', false ) ) {
				add_menu_page(
					__( 'WPWing', 'wpwing-wcpdf' ),
					__( 'WPWing', 'wpwing-wcpdf' ),
					'manage_woocommerce',
					'wpwing',
					'__return_null',
					'dashicons-heart',
					58
				);
			}

			$page_title = esc_html__( 'PDF Invoice for WooCommerce Settings', 'wpwing-wcpdf' );
			$menu_title = esc_html__( 'Invoice Settings', 'wpwing-wcpdf' );
			add_submenu_page( 'wpwing', $page_title, $menu_title, 'manage_woocommerce', $this->slug, array( $this, 'settings_form' ) );
		}

		/**
		 * Redirect the bare WPWing parent menu to Invoice Settings.
		 *
		 * Fires on admin_init (priority 15) so wp_safe_redirect() works before
		 * headers are sent. When wishlist-waitlist is also active its redirect
		 * fires at priority 10 and calls exit first, so there is no conflict.
		 */
		public function redirect_parent_menu() {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['page'] ) && 'wpwing' === $_GET['page'] ) {
				wp_safe_redirect( admin_url( 'admin.php?page=' . $this->slug ) );
				exit;
			}
		}

		/**
		 * Add a Settings link to the WP admin bar.
		 */
		public function add_admin_bar() {

			if ( empty( $this->fields ) ) {
				return '';
			}

			global $wp_admin_bar;

			$url        = admin_url( sprintf( 'admin.php?page=%s', esc_html( $this->slug ) ) );
			$menu_title = esc_html__( 'Invoice Settings', 'wpwing-wcpdf' );

			$args = array(
				'id'    => $this->settings_name,
				'title' => $menu_title,
				'href'  => $url,
				'meta'  => array(
					'class' => sprintf( '%s-admin-toolbar', esc_html( $this->slug ) ),
				),
			);
			$wp_admin_bar->add_menu( $args );

			if ( ! is_admin() && class_exists( 'WooCommerce' ) && ( is_singular( 'product' ) || is_shop() ) ) {
				$wp_admin_bar->add_menu(
					array(
						'id'     => 'wpwing-wcpdf-clear-transient',
						'title'  => esc_html__( 'Clear transient', 'wpwing-wcpdf' ),
						'href'   => esc_url(
							remove_query_arg(
								array(
									'variation_id',
									'remove_item',
									'add-to-cart',
									'added-to-cart',
								),
								add_query_arg( 'wpwing_wcpdf_clear_transient', '' )
							)
						),
						'parent' => $this->settings_name,
						'meta'   => array(
							'class' => sprintf( '%s-admin-toolbar-cache', esc_html( $this->slug ) ),
						),
					)
				);
			}

			do_action( 'wpwing_wcpdf_admin_bar_menu', $wp_admin_bar, $this->settings_name );
		}

		/**
		 * Add a Settings link to the plugin action links on the Plugins page.
		 *
		 * @param array $links Existing plugin action links.
		 * @return array
		 */
		public function plugin_action_links( $links ) {

			if ( empty( $this->fields ) ) {
				return $links;
			}

			$url          = admin_url( sprintf( 'admin.php?page=%s', esc_html( $this->slug ) ) );
			$plugin_links = array( sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'wpwing-wcpdf' ) ) );

			return array_merge( $plugin_links, $links );
		}

		/**
		 * Add documentation link to plugin row meta on the Plugins page.
		 *
		 * @param array  $links       Existing row meta links.
		 * @param string $plugin_file Plugin basename.
		 * @return array
		 */
		public function plugin_row_meta( $links, $plugin_file ) {

			if ( WPWING_WCPDF_BASE_NAME !== $plugin_file ) {
				return $links;
			}

			$docs_url = plugins_url( 'docs/', WPWING_WCPDF_FILE );
			$links[]  = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( $docs_url ), esc_html__( 'Documentation', 'wpwing-wcpdf' ) );

			return $links;
		}

		/**
		 * Register a single field default.
		 *
		 * @param string $key   Field ID.
		 * @param string $type  Field type.
		 * @param mixed  $value Default value.
		 */
		private function set_default( $key, $type, $value ) {

			$this->defaults[ $key ] = array(
				'id'    => $key,
				'type'  => $type,
				'value' => $value,
			);
		}

		/**
		 * Get the default definition for a single field.
		 *
		 * @param string $key Field ID.
		 * @return array|null
		 */
		private function get_default( $key ) {

			return isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : null;
		}

		/**
		 * Return all registered field defaults.
		 *
		 * @return array
		 */
		public function get_defaults() {

			return $this->defaults;
		}

		/**
		 * Walk all registered fields and call set_default() for each non-pro field.
		 */
		public function set_defaults() {

			foreach ( $this->fields as $tab_key => $tab ) {
				$tab = apply_filters( 'wpwing_wcpdf_settings_tab', $tab );

				foreach ( $tab['sections'] as $section_key => $section ) {

					$section = apply_filters( 'wpwing_wcpdf_settings_section', $section, $tab );

					$section['id'] = ! isset( $section['id'] ) ? $tab['id'] . '-section' : $section['id'];

					$section['fields'] = apply_filters( 'wpwing_wcpdf_settings_fields', $section['fields'], $section, $tab );

					foreach ( $section['fields'] as $field ) {
						if ( isset( $field['pro'] ) ) {
							continue;
						}
						$field['default'] = isset( $field['default'] ) ? $field['default'] : null;
						if ( 'checkboxgroup' === $field['type'] && is_null( $field['default'] ) ) {
							$field['default'] = array();
						}
						$this->set_default( $field['id'], $field['type'], $field['default'] );
					}
				}
			}
		}

		/**
		 * Delete settings data completely
		 *
		 * @since 1.0.0
		 */
		public function delete_settings() {

			do_action( sprintf( 'delete_%s_settings', esc_html( $this->settings_name ) ), $this );

			// License key should not be updated.

			return delete_option( $this->settings_name );
		}

		/**
		 * Retrieve a single option value, falling back to its registered default.
		 *
		 * @param string $option Option key.
		 * @return mixed
		 */
		public function get_option( $option ) {

			$default = $this->get_default( $option );
			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- kept for reference during development.
			// $all_defaults = wp_list_pluck( $this->get_defaults(), 'value' );

			$options = get_option( $this->settings_name );

			$is_new = ( ! is_array( $options ) && is_bool( $options ) );

			// Theme Support.
			if ( current_theme_supports( $this->theme_feature_name ) ) {
				$theme_support    = get_theme_support( $this->theme_feature_name );
				$default['value'] = isset( $theme_support[0][ $option ] ) ? $theme_support[0][ $option ] : $default['value'];
			}

			$default_value = isset( $default['value'] ) ? $default['value'] : null;

			if ( ! is_null( $this->get_reserved( $option ) ) ) {
				$default_value = $this->get_reserved( $option );
			}

			if ( $is_new ) {
				return $default_value;
			} else {
				return isset( $options[ $option ] ) ? $options[ $option ] : $default_value;
			}
		}

		/**
		 * Return the full options array from the database.
		 *
		 * @return array|false
		 */
		public function get_options() {

			return get_option( $this->settings_name );
		}

		/**
		 * Persist a single option value within the settings array.
		 *
		 * @param string $key   Option key.
		 * @param mixed  $value Option value.
		 */
		public function set_option( $key, $value ) {

			$options         = get_option( $this->settings_name );
			$options         = is_array( $options ) ? $options : array();
			$options[ $key ] = $value;
			update_option( $this->settings_name, $options );
		}

		/**
		 * Sanitize and normalize the options array on save.
		 *
		 * @param array $options Raw options from the settings form.
		 * @return array
		 */
		public function sanitize_callback( $options ) {

			if ( ! is_array( $options ) ) {
				return array();
			}

			foreach ( $this->get_defaults() as $opt ) {
				$id   = $opt['id'];
				$type = $opt['type'];

				if ( ! isset( $options[ $id ] ) ) {
					if ( 'checkbox' === $type ) {
						$options[ $id ] = 0;
					} elseif ( 'multiselect' === $type || 'checkboxgroup' === $type ) {
						$options[ $id ] = array();
					}
					continue;
				}

				switch ( $type ) {
					case 'text':
					case 'select':
					case 'radio':
						$options[ $id ] = sanitize_text_field( $options[ $id ] );
						break;
					case 'textarea':
						$options[ $id ] = sanitize_textarea_field( $options[ $id ] );
						break;
					case 'upload':
						$options[ $id ] = esc_url_raw( $options[ $id ] );
						break;
					case 'number':
						$options[ $id ] = absint( $options[ $id ] );
						break;
					case 'checkbox':
						$options[ $id ] = absint( $options[ $id ] ) ? 1 : 0;
						break;
					case 'multiselect':
					case 'checkboxgroup':
						$options[ $id ] = array_map( 'sanitize_key', (array) $options[ $id ] );
						break;
				}
			}

			return $options;
		}

		/**
		 * Return true when the current request is a settings reset action.
		 *
		 * @return bool
		 */
		public function is_reset_all() {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- nonce verified in settings_init() before any action is taken.
			return isset( $_GET['page'] ) && ( sanitize_key( $_GET['page'] ) === $this->slug ) && isset( $_GET[ $this->setting_reset_name ] );
		}

		/**
		 * Return true when the current request should show pro fields.
		 *
		 * @return bool
		 */
		public function is_show_pro() {

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only checking presence of GET param, no data processed.
			return isset( $_GET['page'] ) && ( sanitize_key( $_GET['page'] ) === $this->slug ) && isset( $_GET[ $this->show_pro_name ] );
		}

		/**
		 * Init settings on admin init
		 *
		 * @since 1.0.0
		 */
		public function settings_init() {

			if ( $this->is_reset_all() ) {
				if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'wpwing_reset_settings' ) ) {
					wp_die( esc_html__( 'Security check failed.', 'wpwing-wcpdf' ) );
				}
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html__( 'You do not have permission to reset settings.', 'wpwing-wcpdf' ) );
				}
				$this->delete_settings();
				wp_safe_redirect( $this->settings_url() );
			}

			register_setting( $this->settings_name, $this->settings_name, array( $this, 'sanitize_callback' ) );

			foreach ( $this->fields as $tab_key => $tab ) {

				$tab = apply_filters( 'wpwing_wcpdf_settings_tab', $tab );

				foreach ( $tab['sections'] as $section_key => $section ) {

					$section = apply_filters( 'wpwing_wcpdf_settings_section', $section, $tab );

					$section['id'] = ! isset( $section['id'] ) ? $tab['id'] . '-section-' . $section_key : $section['id'];

					// Adding Settings section id.
					$this->fields[ $tab_key ]['sections'][ $section_key ]['id'] = $section['id'];

					add_settings_section(
						$tab['id'] . $section['id'],
						$section['title'],
						function () use ( $section ) {
							if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
								echo '<div class="inside">' . esc_html( $section['desc'] ) . '</div>';
							}
						},
						$tab['id'] . $section['id']
					);

					$section['fields'] = apply_filters( 'wpwing_wcpdf_settings_fields', $section['fields'], $section, $tab );

					foreach ( $section['fields'] as $field ) {

						if ( isset( $field['pro'] ) ) {
							$field['id']    = uniqid( 'pro' );
							$field['type']  = '';
							$field['title'] = '';
						}

						// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- kept for reference during development.
						// $field[ 'label_for' ] = $this->settings_name . '[' . $field[ 'id' ] . ']';
						$field['label_for'] = $field['id'] . '-field';
						$field['default']   = isset( $field['default'] ) ? $field['default'] : null;

						// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- kept for reference during development.
						// $this->set_default( $field[ 'id' ], $field[ 'default' ] );

						if ( 'checkbox' === $field['type'] || 'radio' === $field['type'] || 'checkboxgroup' === $field['type'] ) {
							unset( $field['label_for'] );
						}

						add_settings_field(
							$this->settings_name . '[' . $field['id'] . ']',
							$field['title'],
							array(
								$this,
								'field_callback',
							),
							$tab['id'] . $section['id'],
							$tab['id'] . $section['id'],
							$field
						);

					}
				}
			}
		}

		/**
		 * Build an HTML attribute string from an array, excluding specified keys.
		 *
		 * @param array $attributes Key-value pairs of HTML attributes.
		 * @param array $except     Attribute names to exclude.
		 * @return string
		 */
		public function make_implode_html_attributes( $attributes, $except = array( 'type', 'id', 'name', 'value' ) ) {

			$attrs = array();
			foreach ( $attributes as $name => $value ) {
				if ( in_array( $name, $except, true ) ) {
					continue;
				}
				$attrs[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}

			return implode( ' ', array_unique( $attrs ) );
		}

		/**
		 * Render a settings field. Dispatches on $args['type'] and builds the
		 * appropriate HTML inline - no separate per-type methods needed.
		 *
		 * @since 1.0.0 (unified in 2.1.0)
		 * @param array $args Field definition array registered via add_settings_field().
		 */
		public function field_callback( $args ) {

			$id    = $args['id'];
			$type  = $args['type'];
			$name  = $this->settings_name;
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';
			$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';
			$desc  = $this->get_field_description( $args );
			$html  = '';

			switch ( $type ) {

				case 'radio':
					$options = apply_filters( "wpwing_wcpdf_settings_{$id}_radio_options", $args['options'] );
					$value   = esc_attr( $this->get_option( $id ) );
					$html    = '<fieldset>';
					$html   .= implode(
						'<br />',
						array_map(
							function ( $key, $option ) use ( $attrs, $id, $name, $value ) {
								return sprintf(
									'<label><input %s type="radio" name="%s[%s]" value="%s" %s/> %s</label>',
									esc_attr( $attrs ),
									esc_html( $name ),
									esc_attr( $id ),
									esc_html( $key ),
									checked( $value, $key, false ),
									esc_html( $option )
								);
							},
							array_keys( $options ),
							$options
						)
					);
					$html   .= $desc . '</fieldset>';
					break;

				case 'checkbox':
					$value = wc_string_to_bool( $this->get_option( $id ) );
					$html  = sprintf(
						'<fieldset><label><input %s type="checkbox" id="%s-field" name="%s[%s]" value="1" %s /> %s</label></fieldset>',
						esc_attr( $attrs ),
						esc_attr( $id ),
						esc_html( $name ),
						esc_attr( $id ),
						checked( $value, true, false ),
						esc_html( $args['desc'] )
					);
					break;

				case 'select':
					$options  = apply_filters( "wpwing_wcpdf_settings_{$id}_select_options", $args['options'] );
					$value    = esc_attr( $this->get_option( $id ) );
					$opt_html = implode(
						'',
						array_map(
							function ( $key, $label ) use ( $value ) {
								return sprintf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $key, $value, false ), esc_html( $label ) );
							},
							array_keys( $options ),
							$options
						)
					);
					$html     = sprintf( '<select %s class="%s-text" id="%s-field" name="%s[%s]">%s</select>', esc_attr( $attrs ), esc_html( $size ), esc_attr( $id ), esc_html( $name ), esc_attr( $id ), $opt_html );
					$html    .= $desc;
					break;

				case 'multiselect':
					$options  = apply_filters( "wpwing_wcpdf_settings_{$id}_multiselect_options", $args['options'] );
					$saved    = $this->get_option( $id );
					$value    = is_array( $saved ) ? $saved : array();
					$opt_html = implode(
						'',
						array_map(
							function ( $key, $label ) use ( $value ) {
								$sel = in_array( $key, $value, true ) ? ' selected="selected"' : '';
								return '<option value="' . esc_attr( $key ) . '"' . $sel . '>' . esc_html( $label ) . '</option>';
							},
							array_keys( $options ),
							$options
						)
					);
					$html     = sprintf( '<select %s multiple="multiple" size="6" class="%s-text" id="%s-field" name="%s[%s][]">%s</select>', esc_attr( $attrs ), esc_html( $size ), esc_attr( $id ), esc_html( $name ), esc_attr( $id ), $opt_html );
					$html    .= '<p class="description">' . esc_html__( 'Hold Ctrl (Windows) or Cmd (Mac) to select multiple options.', 'wpwing-wcpdf' ) . '</p>';
					$html    .= $desc;
					break;

				case 'checkboxgroup':
					$options = apply_filters( "wpwing_wcpdf_settings_{$id}_checkboxgroup_options", $args['options'] );
					$saved   = $this->get_option( $id );
					$value   = is_array( $saved ) ? $saved : array();
					$items   = implode(
						'',
						array_map(
							function ( $key, $label ) use ( $id, $name, $value ) {
								$checked = in_array( $key, $value, true ) ? ' checked="checked"' : '';
								return sprintf(
									'<label><input type="checkbox" name="%s[%s][]" value="%s"%s /> %s</label>',
									esc_html( $name ),
									esc_attr( $id ),
									esc_attr( $key ),
									$checked,
									esc_html( $label )
								);
							},
							array_keys( $options ),
							$options
						)
					);
					$html    = '<fieldset class="wpwing-checkboxgroup">' . $items . '</fieldset>';
					$html   .= $desc;
					break;

				case 'button':
					$class = isset( $args['class'] ) ? $args['class'] : '';
					$html  = sprintf( '<button type="button" id="%s-field" class="button %s">%s</button>', esc_attr( $id ), esc_attr( $class ), esc_html( $args['label'] ) );
					$html .= $desc;
					break;

				case 'upload':
					$value = esc_attr( $this->get_option( $id ) );
					$html  = sprintf( '<input %s type="text" class="%s-text" id="%s-field" name="%s[%s]" placeholder="%s" value="%s" readonly />', esc_attr( $attrs ), esc_html( $size ), esc_attr( $id ), esc_html( $name ), esc_attr( $id ), esc_html( $args['placeholder'] ), esc_html( $value ) );
					$html .= '&nbsp;&nbsp;<a href="#" class="wcpdf_upload_image">Upload Logo</a>';
					$html .= $desc;
					break;

				case 'textarea':
					$value = esc_attr( $this->get_option( $id ) );
					$html  = sprintf( '<textarea %s class="%s-text" id="%s-field" name="%s[%s]" placeholder="%s">%s</textarea>', esc_attr( $attrs ), esc_html( $size ), esc_attr( $id ), esc_html( $name ), esc_attr( $id ), esc_html( $args['placeholder'] ), esc_html( $value ) );
					$html .= $desc;
					break;

				default: // Text.
					$value = $this->get_option( $id );
					$ph    = isset( $args['placeholder'] ) ? $args['placeholder'] : '';
					$html  = sprintf( '<input %s type="text" class="%s-text" id="%s-field" name="%s[%s]" placeholder="%s" value="%s" />', esc_attr( $attrs ), esc_html( $size ), esc_attr( $id ), esc_html( $name ), esc_attr( $id ), esc_html( $ph ), esc_attr( $value ) );
					$html .= $desc;
					break;

			}

			echo wp_kses( $html, $this->allowed_html );
			do_action( 'wpwing_wcpdf_settings_field_callback', $args );
		}


		/**
		 * Show description after field.
		 *
		 * @since 1.0.0
		 * @param array $args Field definition array.
		 */
		public function get_field_description( $args ) {

			$desc = '';

			if ( ! empty( $args['desc'] ) ) {
				$desc .= sprintf( '<p class="description">%s</p>', esc_html( $args['desc'] ) );
			} else {
				$desc .= '';
			}

			return wp_kses( $desc, $this->allowed_html );
		}

		/**
		 * Create settings forms
		 *
		 * @since 1.0.0
		 */
		public function settings_form() {

			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpwing-wcpdf' ) );
			}
			?>
			<div id="<?php echo esc_attr( $this->slug ); ?>-wrap" class="wrap settings-wrap">

				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

				<div class="wpwing-settings-layout">

					<div class="wpwing-settings-left">
						<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" enctype="multipart/form-data">
							<?php
							settings_errors();
							settings_fields( $this->settings_name );
							?>

							<?php $this->options_tabs(); ?>

							<div id="settings-tabs">
								<?php
								foreach ( $this->fields as $tab ) :

									if ( ! isset( $tab['active'] ) ) {
										$tab['active'] = false;
									}
									$is_active = ( $this->get_last_active_tab() === $tab['id'] );
									?>

									<div id="<?php echo esc_attr( $tab['id'] ); ?>"
										class="settings-tab wpwing-wcpdf-setting-tab"
										style="<?php echo ! $is_active ? 'display: none' : ''; ?>">
										<?php
										foreach ( $tab['sections'] as $section ) :
											$this->do_settings_sections( $tab['id'] . $section['id'] );
										endforeach;
										?>
									</div>

								<?php endforeach; ?>
							</div>
							<?php
							$this->last_tab_input();
							?>
							<p class="submit wpwing-wcpdf-button-wrapper">
								<input type="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'wpwing-wcpdf' ); ?>">
								<a onclick="return confirm('<?php esc_attr_e( 'Are you sure to reset current settings?', 'wpwing-wcpdf' ); ?>')" class="reset" href="<?php echo esc_url( $this->reset_url() ); ?>"><?php esc_html_e( 'Reset all', 'wpwing-wcpdf' ); ?></a>
							</p>

						</form>
					</div><!-- .wpwing-settings-left -->

					<div id="wpwing-preview-panel" class="wpwing-settings-right" style="display:none">
						<div class="wpwing-preview-header">
							<h2><?php esc_html_e( 'Invoice Preview', 'wpwing-wcpdf' ); ?></h2>
							<button id="wpwing-preview-close" type="button">&times;</button>
						</div>
						<iframe id="wpwing-preview-frame" frameborder="0"></iframe>
						<div id="wpwing-preview-placeholder">
							<p><?php esc_html_e( 'Click "Preview Invoice" to load a preview.', 'wpwing-wcpdf' ); ?></p>
						</div>
					</div><!-- #wpwing-preview-panel -->

				</div><!-- .wpwing-settings-layout -->
			</div>
			<?php
		}

		/**
		 * Settings reset url
		 *
		 * @since 1.0.0
		 */
		public function reset_url() {

			return wp_nonce_url(
				add_query_arg(
					array(
						'page'  => $this->slug,
						'reset' => '',
					),
					admin_url( 'admin.php' )
				),
				'wpwing_reset_settings'
			);
		}

		/**
		 * Settings URL
		 *
		 * @since 1.0.0
		 */
		public function settings_url() {

			return add_query_arg( array( 'page' => $this->slug ), admin_url( 'admin.php' ) );
		}

		/**
		 * Hidden input for last active tab
		 *
		 * @since 1.0.0
		 */
		private function last_tab_input() {

			printf( '<input type="hidden" id="_last_active_tab" name="%s[_last_active_tab]" value="%s">', esc_html( $this->settings_name ), esc_html( $this->get_last_active_tab() ) );
		}

		/**
		 * Setting menu tabs
		 *
		 * @since 1.0.0
		 */
		public function options_tabs() {

			?>
			<h2 class="nav-tab-wrapper wp-clearfix">
				<?php foreach ( $this->fields as $tabs ) : ?>
					<a data-target="<?php echo esc_attr( $tabs['id'] ); ?>" class="wpwing-wcpdf-setting-nav-tab nav-tab <?php echo esc_attr( $this->get_options_tab_css_classes( $tabs ) ); ?>" href="#<?php echo esc_attr( $tabs['id'] ); ?>"><?php echo esc_html( $tabs['title'] ); ?></a>
				<?php endforeach; ?>
			</h2>
			<?php
		}

		/**
		 * Build the CSS class string for a settings nav tab.
		 *
		 * @param array $tabs Tab definition array.
		 * @return string
		 */
		private function get_options_tab_css_classes( $tabs ) {

			$classes = array();

			$classes[] = ( $this->get_last_active_tab() === $tabs['id'] ) ? 'nav-tab-active' : '';

			// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- kept for reference during development.
			// $classes[] = ( $this->get_options_tab_pro_attr( $tabs ) ) ? 'pro-tab' : '';

			return implode( ' ', array_unique( apply_filters( 'get_options_tab_css_classes', $classes ) ) );
		}

		/**
		 * Get last active tab of settings
		 *
		 * @since 1.0.0
		 */
		private function get_last_active_tab() {

			$last_tab = trim( $this->get_option( '_last_active_tab' ) );

			$default_tab = '';
			foreach ( $this->fields as $tabs ) {
				if ( isset( $tabs['active'] ) && $tabs['active'] ) {
					$default_tab = $tabs['id'];
					break;
				}
			}

			return ! empty( $last_tab ) ? esc_html( $last_tab ) : esc_html( $default_tab );
		}

		/**
		 * Tab section content.
		 *
		 * @since 1.0.0
		 * @param string $page Settings page slug.
		 */
		private function do_settings_sections( $page ) {

			global $wp_settings_sections, $wp_settings_fields;

			if ( ! isset( $wp_settings_sections[ $page ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_sections[ $page ] as $section ) {
				if ( $section['title'] ) {
					echo '<h2>' . esc_html( $section['title'] ) . '</h2>';
				}

				if ( $section['callback'] ) {
					call_user_func( $section['callback'], $section );
				}

				if ( ! isset( $wp_settings_fields ) || ! isset( $wp_settings_fields[ $page ] ) || ! isset( $wp_settings_fields[ $page ][ $section['id'] ] ) ) {
					continue;
				}

				echo '<table class="form-table wpwing-pdf-invoice">';
				$this->do_settings_fields( $page, $section['id'] );
				echo '</table>';
			}
		}

		/**
		 * Build a data-attribute string for JS-driven field dependencies.
		 *
		 * @param array $require_array Map of field IDs to required values.
		 * @return string
		 */
		private function build_dependency( $require_array ) {

			$b_array = array();
			foreach ( $require_array as $k => $v ) {
				$b_array[ '#' . $k . '-field' ] = $v;
			}

			return 'data-wpwing-wcpdf-depends="[' . esc_attr( wp_json_encode( $b_array ) ) . ']"';
		}

		/**
		 * Tab section fields.
		 *
		 * @since 1.0.0
		 * @param string $page    Settings page slug.
		 * @param string $section Section ID.
		 */
		private function do_settings_fields( $page, $section ) {

			global $wp_settings_fields;

			if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {

				$wrapper_id = ! empty( $field['args']['id'] ) ? esc_attr( $field['args']['id'] ) . '-wrapper' : '';
				$dependency = ! empty( $field['args']['require'] ) ? $this->build_dependency( $field['args']['require'] ) : '';

				$is_new   = ( isset( $field['args']['is_new'] ) && $field['args']['is_new'] );
				$new_html = $is_new ? '<span class="wpwing-wcpdf-new-feature-tick">' . esc_html__( 'NEW', 'wpwing-wcpdf' ) . '</span>' : '';

				printf( '<tr id="%s" %s>', esc_attr( $wrapper_id ), esc_attr( $dependency ) );

				echo '<th scope="row" class="pb-wc-settings-label">';
				if ( ! empty( $field['args']['label_for'] ) ) {
					echo '<label for="' . esc_attr( $field['args']['label_for'] ) . '">' . esc_html( $field['title'] ) . esc_html( $new_html ) . '</label>';
				} else {
					echo esc_html( $field['title'] ) . esc_html( $new_html );
				}
				echo '</th>';

				echo '<td class="wpwing-wcpdf-settings-field-content">';
				call_user_func( $field['callback'], $field['args'] );
				echo '</td>';

				echo '</tr>';
			}
		}
	}

}