<?php
/**
 * Abstract pro document base class.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_ProDocument' ) ) {

	/**
	 * Abstract base for all pro document types.
	 * Overrides get_theme_dir() to use the pro plugin's template directory,
	 * and provides shared rendering helpers used by Proforma and CreditNote.
	 */
	abstract class WPWing_WcPdf_ProDocument extends WPWing_WcPdf_Document {

		/**
		 * Settings API instance.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		public $settings;

		/**
		 * Resolve the pro template directory for this document type.
		 * Respects the same invoice_template setting as the free plugin,
		 * but looks inside the pro plugin's templates/ directory.
		 *
		 * @return string Absolute path with trailing slash.
		 */
		public function get_theme_dir() {
			$settings  = WPWing_WcPdf_Settings::get_instance();
			$theme     = $settings->get_option( 'invoice_template' );
			$theme     = $theme ? $theme : 'default';
			$theme_dir = WPWING_WCPDF_PRO_TEMPLATE_DIR . trailingslashit( $theme );

			if ( ! is_dir( $theme_dir ) ) {
				$theme_dir = WPWING_WCPDF_PRO_TEMPLATE_DIR . 'default/';
			}

			return apply_filters( 'wpwing_wcpdf_pdf_pro_theme_dir', $theme_dir, $this->document_type );
		}

		/**
		 * Render company name + structured address block.
		 *
		 * @param string $label "From" label shown above the company name.
		 */
		protected function render_company_data( $label ) {
			$company_name = $this->settings->get_option( 'company_name_checkbox' ) ? $this->settings->get_option( 'company_name_text' ) : null;
			$show_details = (bool) $this->settings->get_option( 'company_details_checkbox' );

			if ( ! $company_name && ! $show_details ) {
				return;
			}

			echo '<span class="invoice-from-to">' . esc_html( $label ) . '</span>';

			if ( $company_name ) {
				echo '<div class="company-name">' . esc_html( $company_name ) . '</div>';
			}

			if ( $show_details ) {
				$address = $this->settings->get_option( 'company_address' );
				$city    = $this->settings->get_option( 'company_city' );
				$zip     = $this->settings->get_option( 'company_zip' );
				$country = $this->settings->get_option( 'company_country' );
				$phone   = $this->settings->get_option( 'company_phone' );
				$email   = $this->settings->get_option( 'company_email' );
				$vat     = $this->settings->get_option( 'company_vat' );

				echo '<div class="company-details">';
				if ( $address ) {
					echo '<div>' . esc_html( $address ) . '</div>';
				}
				$city_line = trim( $zip . ' ' . $city );
				if ( $city_line ) {
					echo '<div>' . esc_html( $city_line ) . '</div>';
				}
				if ( $country ) {
					echo '<div>' . esc_html( $country ) . '</div>';
				}
				if ( $phone ) {
					echo '<div>' . esc_html__( 'Tel:', 'wpwing-pdf-invoice-pro' ) . ' ' . esc_html( $phone ) . '</div>';
				}
				if ( $email ) {
					echo '<div>' . esc_html__( 'Email:', 'wpwing-pdf-invoice-pro' ) . ' ' . esc_html( $email ) . '</div>';
				}
				if ( $vat ) {
					echo '<div>' . esc_html__( 'VAT:', 'wpwing-pdf-invoice-pro' ) . ' ' . esc_html( $vat ) . '</div>';
				}
				echo '</div>';
			}
		}

		/**
		 * Render company logo.
		 */
		protected function render_company_logo() {
			$company_logo = $this->settings->get_option( 'company_logo_checkbox' ) ? $this->settings->get_option( 'company_logo_upload' ) : null;

			if ( ! $company_logo ) {
				return;
			}

			echo '<div class="company-logo"><img src="' . esc_url( apply_filters( 'wpwing_wcpdf_company_image_path', $company_logo ) ) . '"></div>';
		}

		/**
		 * Render billing address block.
		 *
		 * @param string $label Label shown above the address.
		 */
		protected function render_billing_address( $label ) {
			global $wpwing_wcpdf_document;

			echo '<div class="invoice-to-section">';
			if ( $wpwing_wcpdf_document->order->get_formatted_billing_address() ) {
				echo '<span class="invoice-from-to">' . esc_html( $label ) . '</span>';
				echo '<div class="customer-details">' . wp_kses( $wpwing_wcpdf_document->order->get_formatted_billing_address(), array( 'br' => array() ) ) . '</div>';
			}
			echo '</div>';
		}

		/**
		 * Include footer template (notes + footer text).
		 *
		 * @param string $doc_type Subfolder name within the theme dir (e.g. 'proforma').
		 */
		protected function render_footer( $doc_type ) {
			$theme_dir = $this->get_theme_dir();
			$notes     = null;
			$footer    = null;

			if ( $this->settings->get_option( 'company_notes_checkbox' ) ) {
				$notes = $this->settings->get_option( 'company_notes_text' );
			}
			if ( $this->settings->get_option( 'company_footer_checkbox' ) ) {
				$footer = $this->settings->get_option( 'company_footer_text' );
			}

			$footer_path = $theme_dir . $doc_type . '/footer.php';
			if ( file_exists( $footer_path ) ) {
				include $footer_path;
			}
		}
	}
}
