<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WCPI_Settings' ) ) {
	class WPWing_WCPI_Settings {

		private static $instance = null;
		protected $_api;

		public function __construct() {
			add_action( 'init', array( $this, 'add_settings' ), 2 );
			add_action( 'init', array( $this, 'load_api' ), 5 );
			do_action( 'wpwing_wcpi_settings_init', $this );

			return $this;
		}

		public static function get_instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function load_api() {
			if ( ! $this->_api ) {
				require_once 'class-wpwing-wcpi-settings-api.php';
				$this->_api = new WPWing_WCPI_Settings_API();
			}

			return $this;
		}

		public function api() {
			if ( ! $this->_api ) {
				$this->load_api();
			}

			return $this->_api;
		}

		public function add_settings() {

			do_action( 'before_wpwing_wcpi_settings', $this );

			$this->add_setting( 'wpwing_pdf_general', esc_html__( 'General', 'wpwing-wc-pdf-invoice' ), apply_filters( 'wpwing_wcpi_general_settings_section', array(
				array(
					'title'  => esc_html__( 'General Section', 'wpwing-wc-pdf-invoice' ),
					'desc'   => esc_html__( 'Basic settings for PDF Invoice', 'wpwing-wc-pdf-invoice' ),
					'fields' => apply_filters( 'wpwing_wcpi_general_settings_fields', array(
						array(
							'id'          => 'invoice_number',
							'type'        => 'text',
							'title'       => esc_html__( 'Next invoice number:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Invoice number for next invoice document.',
							'placeholder' => 'Invoice number',
						),
						array(
							'id'          => 'invoice_prefix',
							'type'        => 'text',
							'title'       => esc_html__( 'Invoice prefix:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Set a text to be used as prefix in invoice number. Leave it blank if no prefix has to be used.',
							'placeholder' => 'Invoice prefix',
						),
						array(
							'id'          => 'invoice_suffix',
							'type'        => 'text',
							'title'       => esc_html__( 'Invoice suffix:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Set a text to be used as suffix in invoice number. Leave it blank if no suffix has to be used.',
							'placeholder' => 'Invoice suffix',
						),
						array(
							'id'          => 'invoice_number_format',
							'type'        => 'text',
							'title'       => esc_html__( 'Invoice number format:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Set format for invoice number. Use [number], [prefix] and [suffix] as placeholders.',
							'placeholder' => '[prefix]/[number]/[suffix]',
						),
						array(
							'id'          => 'invoice_date_format',
							'type'        => 'text',
							'title'       => esc_html__( 'Invoice date format:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Set date format as it should appear on invoices.',
							'placeholder' => 'd/m/Y',
						),
						array(
							'id'      	  => 'invoice_button_behavior',
							'type'        => 'radio',
							'title'       => esc_html__( 'PDF invoice button behaviour:', 'wpwing-wc-pdf-invoice' ),
							// 'desc'        => esc_html__( 'Button behaviour', 'wpwing-wc-pdf-invoice' ),
							'options'     => array(
								'download'	=> esc_html__( 'Download PDF', 'wpwing-wc-pdf-invoice' ),
								'open'		=> esc_html__( 'Open PDF on Browser', 'wpwing-wc-pdf-invoice' ),
							),
							'default' => 'download'
						),
						array(
							'id'      	  => 'invoice_send_customer',
							'type'    	  => 'checkbox',
							'title'   	  => esc_html__( 'Send invoice to customer:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Yes',
							'default' 	  => false,
						),
					) )
				)
			) ), apply_filters( 'wpwing_wcpi_general_settings_default_active', true ) );

			$this->add_setting( 'wpwing_pdf_template', esc_html__( 'Template', 'wpwing-wc-pdf-invoice' ), apply_filters( 'wpwing_wcpi_template_settings_section', array(
				array(
					'title'  => esc_html__( 'Template Section', 'wpwing-wc-pdf-invoice' ),
					'desc'   => esc_html__( 'Basic template for PDF Invoice', 'wpwing-wc-pdf-invoice' ),
					'fields' => apply_filters( 'wpwing_wcpi_template_settings_fields', array(
						array(
							'id'      	  => 'company_name_checkbox',
							'type'    	  => 'checkbox',
							'title'   	  => esc_html__( 'Show company name on invoice:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Yes',
							'default' 	  => false,
						),
						array(
							'id'          => 'company_name_text',
							'type'        => 'text',
							'title'       => esc_html__( 'Company name:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => '',
							'placeholder' => 'Your company name',
						),
						array(
							'id'      	  => 'company_logo_checkbox',
							'type'    	  => 'checkbox',
							'title'   	  => esc_html__( 'Show company logo on invoice:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Yes',
							'default' 	  => false,
						),
						array(
							'id'          => 'company_logo_upload',
							'type'        => 'upload',
							'title'       => esc_html__( 'Company logo:', 'wpwing-wc-pdf-invoice' ),
							'placeholder' => 'Logo URL',
						),
						array(
							'id'      	  => 'company_details_checkbox',
							'type'    	  => 'checkbox',
							'title'   	  => esc_html__( 'Show company details on invoice:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Yes',
							'default' 	  => false,
						),
						array(
							'id'          => 'company_details_text',
							'type'        => 'textarea',
							'title'       => esc_html__( 'Company details:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => '',
							'placeholder' => 'Write your company details, Address, City, State',
						),
						array(
							'id'      	  => 'company_notes_checkbox',
							'type'        => 'checkbox',
							'title'   	  => esc_html__( 'Show notes on invoice:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Yes',
							'default' 	  => false,
						),
						array(
							'id'          => 'company_notes_text',
							'type'        => 'textarea',
							'title'       => esc_html__( 'Your notes:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => '',
							'placeholder' => 'Write your notes',
						),
						array(
							'id'      	  => 'company_footer_checkbox',
							'type'    	  => 'checkbox',
							'title'   	  => esc_html__( 'Show footer on invoice:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => 'Yes',
							'default' 	  => false,
						),
						array(
							'id'          => 'company_footer_text',
							'type'        => 'textarea',
							'title'       => esc_html__( 'Footer text:', 'wpwing-wc-pdf-invoice' ),
							'desc'        => '',
							'placeholder' => 'Write footer text',
						),
					) )
				)
			) ), apply_filters( 'wpwing_wcpi_template_settings_default_active', false ) );

			do_action( 'after_wpwing_wcpi_settings', $this );
		}

		public function add_setting( $tab_id, $tab_title, $tab_sections, $active = false, $is_pro_tab = false, $is_new = false ) {
			add_filter( 'wpwing_wcpi_settings', function ( $fields ) use ( $tab_id, $tab_title, $tab_sections, $active, $is_pro_tab, $is_new ) {
				array_push( $fields, array(
					'id'       => $tab_id,
					'title'    => esc_html( $tab_title ),
					'active'   => $active,
					'sections' => $tab_sections,
					'is_pro'   => $is_pro_tab,
					'is_new'   => $is_new
				) );

				return $fields;
			} );
		}

		public function get_option( $id ) {
			// Avoid loading too early.
			if ( ! did_action( 'init' ) ) {
				wc_doing_it_wrong( __CLASS__ . '::' . __FUNCTION__,
					esc_html__( 'Get settings option should not be called before the init action.', 'wpwing-wc-pdf-invoice' ),
					'1.0.0' );
			}

			return $this->api()->get_option( $id );
		}

		public function set_option( $key, $value ) {
			// Avoid loading too early.
			if ( ! did_action( 'init' ) ) {
				wc_doing_it_wrong( __CLASS__ . '::' . __FUNCTION__,
					esc_html__( 'Set settings option should not be called before the init action.', 'wpwing-wc-pdf-invoice' ),
					'1.0.0' );
			}

			return $this->api()->set_option( $key, $value );
		}
	}
}