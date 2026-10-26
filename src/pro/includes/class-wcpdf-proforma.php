<?php
/**
 * Pro proforma invoice document subclass.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_Pro_Proforma' ) && class_exists( 'WPWing_WcPdf_Proforma' ) ) {

	/**
	 * Pro decorator for proforma documents: adds independent numbering,
	 * custom prefixes/suffixes, and auto-conversion hooks on top of the free base class.
	 */
	class WPWing_WcPdf_Pro_Proforma extends WPWing_WcPdf_Proforma {

		/**
		 * Return formatted proforma number with custom prefix and suffix support.
		 *
		 * @return string
		 */
		public function get_formatted_proforma_number() {
			$prefix    = apply_filters( 'wpwing_wcpdf_pro_proforma_prefix', 'PRO-', $this->order );
			$suffix    = apply_filters( 'wpwing_wcpdf_pro_proforma_suffix', '', $this->order );
			$formatted = $prefix . $this->number . $suffix;

			return apply_filters( 'wpwing_wcpdf_get_formatted_proforma_number', $formatted, $this->order, $this );
		}
	}
}
