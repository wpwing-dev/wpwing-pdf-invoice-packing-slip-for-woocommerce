<?php

defined( 'ABSPATH' ) || exit;

// 1. add settings: init priority 1
// 2. initial class: init priority 2
// 3. store defaults: init priority 3
// 4. get defaults / do whatever you want to do

if ( ! class_exists( 'WPWing_WCPI_Settings_API' ) ) {

	class WPWing_WCPI_Settings_API {

		private $setting_name = 'wpwing_wcpi_settings';
		private $setting_reset_name = 'reset';
		private $show_pro_name = 'pro';
		private $transient_setting_name = '_temp_wpwing_wcpi_options';
		private $cache_key = 'wpwing_wcpi_options';
		private $theme_feature_name = 'wpwing-wc-pdf-invoice';
		private $slug;
		// private $plugin_class;
		private $defaults = [];
		private $reserved_key = '';
		private $reserved_fields = [];

		private $fields = [];
		private $allowed_html = [
			'fieldset' => [],
			'label' => [],
			'input' => [
				'type' => [],
				'id' => [],
				'class' => [],
				'name' => [],
				'value' => [],
				'checked' => [],
				'placeholder' => [],
				'readonly' => [],

			],
			'select' => [
				'id' => [],
				'class' => [],
				'name' => [],
				'value' => [],
				'readonly' => [],
			],
			'option' => [
				'value' => [],
				'selected' => [],
			],
			'textarea' => [
				'id' => [],
				'class' => [],
				'name' => [],
				'placeholder' => [],
				'readonly' => [],
			],
			'a'	=> [
				'href' => [],
				'title' => [],
				'class' => [],
			],
			'p'	=> [
				'class' => [],
			],
			'br' => [],
			'strong' => [],
		];

		public function __construct() {

			$this->settings_name = apply_filters( 'wpwing_wcpi_settings_name', $this->setting_name );
			$this->setting_reset_name = apply_filters( 'wpwing_wcpi_settings_reset_name', $this->setting_reset_name );

			$this->slug = sprintf( '%s-settings', sanitize_key( WPWING_WCPI_DIR_NAME ) );
			// license_key
			$this->fields = apply_filters( 'wpwing_wcpi_settings', $this->fields );
			$this->reserved_key = sprintf( '%s_reserved', esc_html( $this->settings_name ) );
			$this->reserved_fields = apply_filters( 'wpwing_wcpi_reserved_fields', [] );

			add_action( 'admin_menu', array( $this, 'add_menu' ) );

			add_action( 'init', array( $this, 'set_defaults' ), 8 );

			add_action( 'admin_init', array( $this, 'settings_init' ), 90 );

			// add_filter( 'pre_update_option', array( $this, 'before_update' ), 10, 3 );
			// add_action( 'updated_option', array( $this, 'before_update' ), 10, 3 );

			add_filter( "pre_update_option_{$this->settings_name}", array( $this, 'before_update' ), 10, 3 );
			add_action( "update_option_{$this->settings_name}", array( $this, 'after_update' ), 10, 3 );


			add_filter( 'plugin_action_links_' . WPWING_WCPI_BASE_NAME, array( $this, 'plugin_action_links' ) );

			if ( apply_filters( 'show_wpwing_wcpi_settings_link_on_admin_bar', false ) ):
				add_action( 'wp_before_admin_bar_render', array( $this, 'add_admin_bar' ), 999 );
			endif;

			add_action( 'admin_footer', array( $this, 'admin_inline_js' ) );

			do_action( 'wpwing_wcpi_setting_api_init', $this );

		}

		public function get_reserved( $key = false ) {

			$data = (array) get_option( $this->reserved_key );
			if ( $key ) {
				return isset( $data[ $key ] ) ? $data[ $key ] : null;
			} else {
				return $data;
			}

		}

		public function save_reserved( $value ) {

			$reserved_data = [];
			foreach ( (array) $this->reserved_fields as $fieldKey ) {
				if ( ! empty( $value[ $fieldKey ] ) ) {
					$reserved_data[ $fieldKey ] = $value[ $fieldKey ];
				}
			}

			if ( ! empty( $reserved_data ) ) {
				update_option( $this->reserved_key, $reserved_data );
			} else {
				delete_option( $this->reserved_key );
			}

		}

		public function before_update( $value, $old_value, $option ) {

			$this->save_reserved( $value );
			do_action( sprintf( 'before_update_%s_settings', esc_html( $this->settings_name ) ), $this );

			return $value;

		}

		public function after_update( $old_value, $value, $option ) {

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

			$page_title = esc_html__( 'PDF Invoice for WooCommerce Settings', 'wpwing-wc-pdf-invoice' );
			$menu_title = esc_html__( 'Invoice Settings', 'wpwing-wc-pdf-invoice' );
			add_menu_page( $page_title, $menu_title, 'edit_theme_options', $this->slug, array( $this, 'settings_form' ), 'dashicons-pdf', 31 );

		}

		public function add_admin_bar() {

			if ( empty( $this->fields ) ) {
				return '';
			}

			global $wp_admin_bar;

			$url        = admin_url( sprintf( 'admin.php?page=%s', esc_html( $this->slug ) ) );
			$menu_title = esc_html__( 'Invoice Settings', 'wpwing-wc-pdf-invoice' );

			$args = array(
				'id'    => $this->settings_name,
				'title' => $menu_title,
				'href'  => $url,
				'meta'  => array(
					'class' => sprintf( '%s-admin-toolbar', esc_html( $this->slug ) )
				)
			);
			$wp_admin_bar->add_menu( $args );

			if ( ! is_admin() && class_exists( 'WooCommerce' ) && ( is_singular( 'product' ) || is_shop() ) ) {
				$wp_admin_bar->add_menu( array(
					'id'     => 'wpwing-wcpi-clear-transient',
					'title'  => esc_html__( 'Clear transient', 'wpwing-wc-pdf-invoice' ),
					'href'   => esc_url( remove_query_arg( array(
						'variation_id',
						'remove_item',
						'add-to-cart',
						'added-to-cart'
					), add_query_arg( 'wpwing_wcpi_clear_transient', '' ) ) ),
					'parent' => $this->settings_name,
					'meta'   => array(
						'class' => sprintf( '%s-admin-toolbar-cache', esc_html( $this->slug ) )
					)
				) );
			}

			do_action( 'wpwing_wcpi_admin_bar_menu', $wp_admin_bar, $this->settings_name );

		}

		public function plugin_action_links( $links ) {

			if ( empty( $this->fields ) ) {
				return $links;
			}

			$url          = admin_url( sprintf( 'admin.php?page=%s', esc_html( $this->slug ) ) );
			$plugin_links = array( sprintf( '<a href="%s">%s</a>', esc_url( $url ), esc_html__( 'Settings', 'wpwing-wc-pdf-invoice' ) ) );

			return array_merge( $plugin_links, $links );

		}

		private function set_default( $key, $type, $value ) {

			$this->defaults[ $key ] = array( 'id' => $key, 'type' => $type, 'value' => $value );

		}

		private function get_default( $key ) {

			return isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : null;

		}

		public function get_defaults() {

			return $this->defaults;

		}

		public function set_defaults() {

			foreach ( $this->fields as $tab_key => $tab ) {
				$tab = apply_filters( 'wpwing_wcpi_settings_tab', $tab );

				foreach ( $tab['sections'] as $section_key => $section ) {

					$section = apply_filters( 'wpwing_wcpi_settings_section', $section, $tab );

					$section['id'] = ! isset( $section['id'] ) ? $tab['id'] . '-section' : $section['id'];

					$section['fields'] = apply_filters( 'wpwing_wcpi_settings_fields', $section['fields'], $section, $tab );

					foreach ( $section['fields'] as $field ) {
						if ( isset( $field['pro'] ) ) {
							continue;
						}
						$field['default'] = isset( $field['default'] ) ? $field['default'] : null;
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

			// license_key should not updated

			return delete_option( $this->settings_name );

		}

		public function get_option( $option ) {

			$default = $this->get_default( $option );
			// $all_defaults = wp_list_pluck( $this->get_defaults(), 'value' );

			$options = get_option( $this->settings_name );

			$is_new = ( ! is_array( $options ) && is_bool( $options ) );

			// Theme Support
			if ( current_theme_supports( $this->theme_feature_name ) ) {
				$theme_support    = get_theme_support( $this->theme_feature_name );
				$default['value'] = isset( $theme_support[0][ $option ] ) ? $theme_support[0][ $option ] : $default['value'];
			}

			$default_value = isset( $default['value'] ) ? $default['value'] : null;

			if ( ! is_null( $this->get_reserved( $option ) ) ) {
				$default_value = $this->get_reserved( $option );
			}

			if ( $is_new ) {
				// return ( $default[ 'type' ] === 'checkbox' ) ? ( ! ! $default[ 'value' ] ) : $default[ 'value' ];
				return $default_value;
			} else {
				// return ( $default[ 'type' ] === 'checkbox' ) ? ( isset( $options[ $option ] ) ? TRUE : FALSE ) : ( isset( $options[ $option ] ) ? $options[ $option ] : $default[ 'value' ] );
				return isset( $options[ $option ] ) ? $options[ $option ] : $default_value;
			}

		}

		public function get_options() {

			return get_option( $this->settings_name );

		}

		public function set_option( $key, $value ) {

			$options         = get_option( $this->settings_name );
			$options[ $key ] = $value;
			update_option( $this->settings_name, $options );

		}

		public function sanitize_callback( $options ) {

			foreach ( $this->get_defaults() as $opt ) {
				if ( $opt['type'] === 'checkbox' && ! isset( $options[ $opt['id'] ] ) ) {
					$options[ $opt['id'] ] = 0;
				}
			}

			return $options;

		}

		public function is_reset_all() {

			return isset( $_GET['page'] ) && ( $_GET['page'] == $this->slug ) && isset( $_GET[ $this->setting_reset_name ] );

		}

		public function is_show_pro() {

			return isset( $_GET['page'] ) && ( $_GET['page'] == $this->slug ) && isset( $_GET[ $this->show_pro_name ] );

		}

		/**
		 * Init settings on admin init
		 *
		 * @since 1.0.0
		 */
		public function settings_init() {

			if ( $this->is_reset_all() ) {
				$this->delete_settings();
				wp_redirect( $this->settings_url() );
			}

			register_setting( $this->settings_name, $this->settings_name, array( $this, 'sanitize_callback' ) );

			foreach ( $this->fields as $tab_key => $tab ) {

				$tab = apply_filters( 'wpwing_wcpi_settings_tab', $tab );

				foreach ( $tab['sections'] as $section_key => $section ) {

					$section = apply_filters( 'wpwing_wcpi_settings_section', $section, $tab );

					$section['id'] = ! isset( $section['id'] ) ? $tab['id'] . '-section-' . $section_key : $section['id'];

					// Adding Settings section id
					$this->fields[ $tab_key ]['sections'][ $section_key ]['id'] = $section['id'];

					add_settings_section( $tab['id'] . $section['id'], $section['title'], function () use ( $section ) {
						if ( isset( $section['desc'] ) && ! empty( $section['desc'] ) ) {
							echo '<div class="inside">' . esc_html( $section['desc'] ) . '</div>';
						}
					}, $tab['id'] . $section['id'] );

					$section['fields'] = apply_filters( 'wpwing_wcpi_settings_fields', $section['fields'], $section, $tab );

					foreach ( $section['fields'] as $field ) {

						if ( isset( $field['pro'] ) ) {
							$field['id']    = uniqid( 'pro' );
							$field['type']  = '';
							$field['title'] = '';
						}

						//$field[ 'label_for' ] = $this->settings_name . '[' . $field[ 'id' ] . ']';
						$field['label_for'] = $field['id'] . '-field';
						$field['default']   = isset( $field['default'] ) ? $field['default'] : null;

						// $this->set_default( $field[ 'id' ], $field[ 'default' ] );

						if ( $field['type'] == 'checkbox' || $field['type'] == 'radio' ) {
							unset( $field['label_for'] );
						}

						add_settings_field( $this->settings_name . '[' . $field['id'] . ']', $field['title'], array(
							$this,
							'field_callback'
						), $tab['id'] . $section['id'], $tab['id'] . $section['id'], $field );

					}
				}
			}

		}

		public function make_implode_html_attributes( $attributes, $except = array( 'type', 'id', 'name', 'value' ) ) {

			$attrs = [];
			foreach ( $attributes as $name => $value ) {
				if ( in_array( $name, $except, true ) ) {
					continue;
				}
				$attrs[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}

			return implode( ' ', array_unique( $attrs ) );

		}

		/**
		 * Create form field depends on argument type
		 *
		 * @since 1.0.0
		 */
		public function field_callback( $field ) {

			switch ( $field['type'] ) {
				case 'radio':
					$this->radio_field_callback( $field );
					break;

				case 'checkbox':
					$this->checkbox_field_callback( $field );
					break;

				case 'select':
					$this->select_field_callback( $field );
					break;

				case 'upload':
					$this->upload_field_callback( $field );
					break;

				case 'textarea':
					$this->textarea_field_callback( $field );
					break;

				default:
					$this->text_field_callback( $field );
					break;
			}

			do_action( 'wpwing_wcpi_settings_field_callback', $field );

		}

		/**
		 * Radio field
		 *
		 * @since 1.0.0
		 */
		public function radio_field_callback( $args ) {

			$options = apply_filters( "wpwing_wcpi_settings_{$args[ 'id' ]}_radio_options", $args['options'] );
			$value   = esc_attr( $this->get_option( $args['id'] ) );

			$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

			$html = '<fieldset>';
			$html .= implode( '<br />', array_map( function ( $key, $option ) use ( $attrs, $args, $value ) {
				return sprintf( '<label><input %1$s type="radio"  name="%4$s[%2$s]" value="%3$s" %5$s/> %6$s</label>', esc_attr( $attrs ), esc_attr( $args['id'] ), esc_html( $key ), esc_html( $this->settings_name ), checked( $value, $key, false ), esc_html( $option ) );
			}, array_keys( $options ), $options ) );
			$html .= $this->get_field_description( $args );
			$html .= '</fieldset>';

			echo wp_kses( $html, $this->allowed_html );

		}

		/**
		 * Checkbox field
		 *
		 * @since 1.0.0
		 */
		public function checkbox_field_callback( $args ) {

			$value = wc_string_to_bool( $this->get_option( $args['id'] ) );

			$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

			$html = sprintf( '<fieldset><label><input %1$s type="checkbox" id="%2$s-field" name="%4$s[%2$s]" value="%3$s" %5$s /> %6$s</label></fieldset>', esc_attr( $attrs ), esc_attr( $args['id'] ), true, esc_html( $this->settings_name ), checked( $value, true, false ), esc_attr( $args['desc'] ) );

			echo wp_kses( $html, $this->allowed_html );

		}

		/**
		 * Select field
		 *
		 * @since 1.0.0
		 */
		public function select_field_callback( $args ) {

			$options = apply_filters( "wpwing_wcpi_settings_{$args[ 'id' ]}_select_options", $args['options'] );
			$value = esc_attr( $this->get_option( $args['id'] ) );
			$options = array_map( function ( $key, $option ) use ( $value ) {
				return "<option value='{$key}'" . selected( $key, $value, false ) . ">{$option}</option>";
			}, array_keys( $options ), $options );
			$size = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

			$html = sprintf( '<select %5$s class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]">%3$s</select>', esc_html( $size ), esc_attr( $args['id'] ), implode( '', $options ), esc_html( $this->settings_name ), esc_attr( $attrs ) );
			$html .= $this->get_field_description( $args );

			echo wp_kses( $html, $this->allowed_html );

		}

		/**
		 * Upload field
		 *
		 * @since 1.0.0
		 */
		public function upload_field_callback( $args ) {

			$value = esc_attr( $this->get_option( $args['id'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

			$html = sprintf( '<input %5$s type="text" class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]" placeholder="%6$s" value="%3$s" readonly />', esc_html( $size ), esc_attr( $args['id'] ), esc_html( $value ), esc_html( $this->settings_name ), esc_attr( $attrs ), esc_html( $args['placeholder'] ) );
			$html .= '&nbsp;&nbsp;<a href="#" class="wcpi_upload_image">Upload Logo</a>';
			$html .= $this->get_field_description( $args );

			echo wp_kses( $html, $this->allowed_html );

		}

		/**
		 * Textarea field
		 *
		 * @since 1.0.0
		 */
		public function textarea_field_callback( $args ) {

			$value = esc_attr( $this->get_option( $args['id'] ) );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

			$html = sprintf( '<textarea %5$s class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]" placeholder="%6$s">%3$s</textarea>', esc_html( $size ), esc_attr( $args['id'] ), esc_html( $value ), esc_html( $this->settings_name ), esc_attr( $attrs ), esc_html( $args['placeholder'] ) );
			$html .= $this->get_field_description( $args );

			echo wp_kses( $html, $this->allowed_html );

		}

		/**
		 * Text field
		 *
		 * @since 1.0.0
		 */
		public function text_field_callback( $args ) {

			$value = $this->get_option( $args['id'] );
			$size  = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

			$attrs = isset( $args['attrs'] ) ? $this->make_implode_html_attributes( $args['attrs'] ) : '';

			$html = sprintf( '<input %5$s type="text" class="%1$s-text" id="%2$s-field" name="%4$s[%2$s]" placeholder="%6$s" value="%3$s"/>', esc_html( $size ), esc_attr( $args['id'] ), esc_attr( $value ), esc_html( $this->settings_name ), esc_attr( $attrs ), esc_html( $args['placeholder'] ) );
			$html .= $this->get_field_description( $args );

			echo wp_kses( $html, $this->allowed_html );

		}


		/**
		 * Show description after field
		 *
		 * @since 1.0.0
		 */
		public function get_field_description( $args ) {

			$desc = '';

			if ( ! empty( $args['desc'] ) ) {
				$desc .= sprintf( '<p class="description">%s</p>', $args['desc'] );
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
				wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
			}
			?>
			<div id="<?php echo esc_attr( $this->slug ); ?>-wrap" class="wrap settings-wrap">

				<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

				<form method="post" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" enctype="multipart/form-data">
					<?php
					settings_errors();
					settings_fields( $this->settings_name );
					?>

					<?php $this->options_tabs(); ?>

					<div id="settings-tabs">
						<?php foreach ( $this->fields as $tab ):

							if ( ! isset( $tab['active'] ) ) {
								$tab['active'] = false;
							}
							$is_active = ( $this->get_last_active_tab() == $tab['id'] );
							?>

							<div id="<?php echo esc_attr( $tab['id'] ); ?>"
								class="settings-tab wpwing-wcpi-setting-tab"
								style="<?php echo ! $is_active ? 'display: none' : ''; ?>">
								<?php foreach ( $tab['sections'] as $section ):
									$this->do_settings_sections( $tab['id'] . $section['id'] );
								endforeach; ?>
							</div>

						<?php endforeach; ?>
					</div>
					<?php
					$this->last_tab_input();
					// submit_button();
					?>
					<p class="submit wpwing-wcpi-button-wrapper">
						<input type="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'wpwing-wc-pdf-invoice' ) ?>">
						<a onclick="return confirm('<?php esc_attr_e( 'Are you sure to reset current settings?', 'wpwing-wc-pdf-invoice' ) ?>')" class="reset" href="<?php echo esc_url( $this->reset_url() ); ?>"><?php esc_html_e( 'Reset all', 'wpwing-wc-pdf-invoice' ) ?></a>
					</p>

				</form>
			</div>
			<?php

		}

		/**
		 * Settings reset url
		 *
		 * @since 1.0.0
		 */
		public function reset_url() {

			return add_query_arg( array( 'page' => $this->slug, 'reset' => '' ), admin_url( 'admin.php' ) );

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
				<?php foreach ( $this->fields as $tabs ): ?>
					<a data-target="<?php echo esc_attr( $tabs['id'] ); ?>" <?php echo esc_attr( $this->get_options_tab_pro_attr( $tabs ) ); ?> class="wpwing-wcpi-setting-nav-tab nav-tab <?php echo esc_attr( $this->get_options_tab_css_classes( $tabs ) ); ?> " href="#<?php echo esc_attr( $tabs['id'] ); ?>"><?php echo esc_html( $tabs['title'] ); ?></a>
				<?php endforeach; ?>
			</h2>
			<?php

		}

		private function get_options_tab_pro_attr( $tabs ) {

			// $attrs[] = ( isset( $tabs[ 'is_pro' ] ) && $tabs[ 'is_pro' ] ) ? sprintf( 'data-pro-text="%s"', apply_filters( 'wpwing_wcpi_settings_tab_pro_text', 'Pro' ) ) : false;
			$attrs[] = ( isset( $tabs['is_new'] ) && $tabs['is_new'] ) ? sprintf( 'data-new-text="%s"', apply_filters( 'wpwing_wcpi_settings_tab_new_text', 'New' ) ) : false;

			return implode( ' ', $attrs );

		}

		private function get_options_tab_css_classes( $tabs ) {

			$classes = array();

			$classes[] = ( $this->get_last_active_tab() == $tabs['id'] ) ? 'nav-tab-active' : '';

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
		 * Tab section content
		 *
		 * @since 1.0.0
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

		public function array2html_attr( $attributes, $do_not_add = array() ) {

			$attributes = wp_parse_args( $attributes, array() );

			if ( ! empty( $do_not_add ) and is_array( $do_not_add ) ) {
				foreach ( $do_not_add as $att_name ) {
					unset( $attributes[ $att_name ] );
				}
			}

			$attributes_array = array();

			foreach ( $attributes as $key => $value ) {

				if ( is_bool( $attributes[ $key ] ) and $attributes[ $key ] === true ) {
					return $attributes[ $key ] ? $key : '';
				} elseif ( is_bool( $attributes[ $key ] ) and $attributes[ $key ] === false ) {
					$attributes_array[] = '';
				} else {
					$attributes_array[] = $key . '="' . $value . '"';
				}
			}

			return implode( ' ', $attributes_array );

		}

		private function build_dependency( $require_array ) {

			$b_array = array();
			foreach ( $require_array as $k => $v ) {
				$b_array[ '#' . $k . '-field' ] = $v;
			}

			return 'data-wpwing-wcpi-depends="[' . esc_attr( wp_json_encode( $b_array ) ) . ']"';

		}

		/**
		 * Tab section fields
		 *
		 * @since 1.0.0
		 */
		private function do_settings_fields( $page, $section ) {

			global $wp_settings_fields;

			if ( ! isset( $wp_settings_fields[ $page ][ $section ] ) ) {
				return;
			}

			foreach ( (array) $wp_settings_fields[ $page ][ $section ] as $field ) {

				$custom_attributes = $this->array2html_attr( isset( $field['args']['attributes'] ) ? $field['args']['attributes'] : array() );

				$wrapper_id = ! empty( $field['args']['id'] ) ? esc_attr( $field['args']['id'] ) . '-wrapper' : '';
				$dependency = ! empty( $field['args']['require'] ) ? $this->build_dependency( $field['args']['require'] ) : '';

				$is_new   = ( isset( $field['args']['is_new'] ) && $field['args']['is_new'] );
				$new_html = $is_new ? '<span class="wpwing-wcpi-new-feature-tick">' . esc_html__( 'NEW', 'wpwing-wc-pdf-invoice' ) . '</span>' : '';

				printf( '<tr id="%s" %s %s>', esc_attr( $wrapper_id ), esc_attr( $custom_attributes ), esc_attr( $dependency ) );

				echo '<th scope="row" class="pb-wc-settings-label">';
				if ( ! empty( $field['args']['label_for'] ) ) {
					echo '<label for="' . esc_attr( $field['args']['label_for'] ) . '">' . esc_html( $field['title'] ) . esc_html( $new_html ) . '</label>';
				} else {
					echo esc_html( $field['title'] ) . esc_html( $new_html );
				}
				echo '</th>';

				echo '<td class="wpwing-wcpi-settings-field-content">';
				call_user_func( $field['callback'], $field['args'] );
				echo '</td>';

				echo '</tr>';
			}

		}

	}

}