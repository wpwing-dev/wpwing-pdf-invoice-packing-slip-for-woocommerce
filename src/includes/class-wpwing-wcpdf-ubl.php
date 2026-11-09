<?php
/**
 * UBL 2.1 e-Invoice XML generator.
 *
 * Implements compliant UBL 2.1 (Universal Business Language, ISO/IEC 19845:2015)
 * invoice export aligned with European standard EN 16931 and Peppol BIS Billing 3.0.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WPWing_WcPdf_UBL' ) ) {

	/**
	 * Generates, persists, and streams UBL 2.1 e-invoice XML for WooCommerce orders.
	 *
	 * @since 1.19.0
	 */
	class WPWing_WcPdf_UBL {

		/**
		 * UBL XML Namespaces.
		 */
		const NS_INVOICE = 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2';
		const NS_CAC     = 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2';
		const NS_CBC     = 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2';

		/**
		 * WooCommerce order object.
		 *
		 * @var WC_Order
		 */
		public $order;

		/**
		 * Invoice document object.
		 *
		 * @var WPWing_WcPdf_Invoice|null
		 */
		public $invoice;

		/**
		 * Settings instance.
		 *
		 * @var WPWing_WcPdf_Settings
		 */
		public $settings;

		/**
		 * Constructor.
		 *
		 * @param WC_Order|WPWing_WcPdf_Invoice|WPWing_WcPdf_Document|int $order_or_invoice Order or Invoice instance or order ID.
		 */
		public function __construct( $order_or_invoice ) {
			$this->settings = WPWing_WcPdf_Settings::get_instance();

			if ( $order_or_invoice instanceof WPWing_WcPdf_Invoice ) {
				$this->invoice = $order_or_invoice;
				$this->order   = $order_or_invoice->order;
			} elseif ( $order_or_invoice instanceof WPWing_WcPdf_Document ) {
				$this->invoice = null;
				$this->order   = $order_or_invoice->order instanceof WC_Order ? $order_or_invoice->order : null;
			} elseif ( $order_or_invoice instanceof WC_Order ) {
				$this->order   = $order_or_invoice;
				$this->invoice = function_exists( 'wpwing_wcpdf' )
					? wpwing_wcpdf()->get_document_by_type( $order_or_invoice->get_id(), 'invoice' )
					: new WPWing_WcPdf_Invoice( $order_or_invoice->get_id() );
			} else {
				$order_id    = (int) $order_or_invoice;
				$this->order = wc_get_order( $order_id );
				if ( $this->order ) {
					$this->invoice = function_exists( 'wpwing_wcpdf' )
						? wpwing_wcpdf()->get_document_by_type( $order_id, 'invoice' )
						: new WPWing_WcPdf_Invoice( $order_id );
				}
			}
		}

		/**
		 * Check whether the UBL instance has a valid order.
		 *
		 * @return bool
		 */
		public function is_valid() {
			return $this->order instanceof WC_Order;
		}

		/**
		 * Generate the complete UBL 2.1 XML document and return it as a string.
		 *
		 * @return string XML string or empty string on failure.
		 */
		public function generate_xml() {
			if ( ! $this->is_valid() ) {
				return '';
			}

			$dom               = new DOMDocument( '1.0', 'UTF-8' );
			$dom->formatOutput = true; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

			// Root <Invoice> element with namespaces.
			$invoice_el = $dom->createElementNS( self::NS_INVOICE, 'Invoice' );
			$invoice_el->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:cac', self::NS_CAC );
			$invoice_el->setAttributeNS( 'http://www.w3.org/2000/xmlns/', 'xmlns:cbc', self::NS_CBC );
			$dom->appendChild( $invoice_el );

			$currency = $this->order->get_currency();
			if ( empty( $currency ) ) {
				$currency = 'EUR';
			}

			$invoice_number = $this->get_invoice_identifier();
			$issue_date     = $this->get_issue_date();
			$due_date       = $this->get_due_date();

			// Header components.
			$customization_id = apply_filters(
				'wpwing_wcpdf_ubl_customization_id',
				'urn:cen.eu:en16931:2017#compliant#urn:fdc:peppol.eu:2017:poacc:billing:3.0',
				$this->order,
				$this->invoice
			);
			$profile_id       = apply_filters(
				'wpwing_wcpdf_ubl_profile_id',
				'urn:fdc:peppol.eu:2017:poacc:billing:01:1.0',
				$this->order,
				$this->invoice
			);

			$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:UBLVersionID', '2.1' );
			$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:CustomizationID', $customization_id );
			$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:ProfileID', $profile_id );
			$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:ID', $invoice_number );
			$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:IssueDate', $issue_date );

			if ( ! empty( $due_date ) ) {
				$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:DueDate', $due_date );
			}

			// InvoiceTypeCode: 380 = Commercial Invoice (UN/CEFACT 1001).
			$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:InvoiceTypeCode', '380' );
			$this->append_child( $dom, $invoice_el, self::NS_CBC, 'cbc:DocumentCurrencyCode', $currency );

			// Order reference.
			$order_ref_el = $dom->createElementNS( self::NS_CAC, 'cac:OrderReference' );
			$this->append_child( $dom, $order_ref_el, self::NS_CBC, 'cbc:ID', (string) $this->order->get_order_number() );
			$invoice_el->appendChild( $order_ref_el );

			// AccountingSupplierParty (Seller).
			$this->append_supplier_party( $dom, $invoice_el );

			// AccountingCustomerParty (Buyer).
			$this->append_customer_party( $dom, $invoice_el );

			// PaymentMeans.
			$this->append_payment_means( $dom, $invoice_el, $invoice_number );

			// TaxTotal.
			$this->append_tax_total( $dom, $invoice_el, $currency );

			// LegalMonetaryTotal.
			$this->append_monetary_total( $dom, $invoice_el, $currency );

			// InvoiceLine items.
			$this->append_invoice_lines( $dom, $invoice_el, $currency );

			$xml = $dom->saveXML();
			return apply_filters( 'wpwing_wcpdf_ubl_xml', $xml ? $xml : '', $this->order, $this->invoice );
		}

		/**
		 * Return the invoice identifier (formatted invoice number, or order number).
		 *
		 * @return string
		 */
		public function get_invoice_identifier() {
			if ( $this->invoice && method_exists( $this->invoice, 'get_formatted_invoice_number' ) ) {
				$formatted = $this->invoice->get_formatted_invoice_number();
				if ( ! empty( $formatted ) ) {
					return $formatted;
				}
			}
			return (string) $this->order->get_order_number();
		}

		/**
		 * Return the issue date formatted as YYYY-MM-DD.
		 *
		 * @return string
		 */
		public function get_issue_date() {
			if ( $this->invoice && ! empty( $this->invoice->date ) ) {
				$ts = is_numeric( $this->invoice->date ) ? (int) $this->invoice->date : strtotime( (string) $this->invoice->date );
				if ( $ts ) {
					return wp_date( 'Y-m-d', $ts );
				}
			}

			$created = $this->order->get_date_created();
			if ( $created ) {
				return wp_date( 'Y-m-d', $created->getTimestamp() );
			}

			return wp_date( 'Y-m-d' );
		}

		/**
		 * Return the payment due date formatted as YYYY-MM-DD, or empty string if none.
		 *
		 * @return string
		 */
		public function get_due_date() {
			$days = (int) $this->settings->get_option( 'invoice_due_date_days' );
			if ( $days <= 0 ) {
				return '';
			}

			$created = $this->order->get_date_created();
			$base    = $created ? $created->getTimestamp() : time();
			return wp_date( 'Y-m-d', $base + ( $days * DAY_IN_SECONDS ) );
		}

		/**
		 * Append seller party block (cac:AccountingSupplierParty).
		 *
		 * @param DOMDocument $dom        Document instance.
		 * @param DOMElement  $invoice_el Root invoice element.
		 */
		private function append_supplier_party( DOMDocument $dom, DOMElement $invoice_el ) {
			$supplier_party_el = $dom->createElementNS( self::NS_CAC, 'cac:AccountingSupplierParty' );
			$party_el          = $dom->createElementNS( self::NS_CAC, 'cac:Party' );

			$company_name = (string) $this->settings->get_option( 'company_name_text' );
			if ( '' === $company_name ) {
				$company_name = get_bloginfo( 'name' );
			}

			// PartyName.
			$party_name_el = $dom->createElementNS( self::NS_CAC, 'cac:PartyName' );
			$this->append_child( $dom, $party_name_el, self::NS_CBC, 'cbc:Name', $company_name );
			$party_el->appendChild( $party_name_el );

			// PostalAddress.
			$address_el = $dom->createElementNS( self::NS_CAC, 'cac:PostalAddress' );
			$street     = (string) $this->settings->get_option( 'company_address' );
			$city       = (string) $this->settings->get_option( 'company_city' );
			$zip        = (string) $this->settings->get_option( 'company_zip' );
			$country    = (string) $this->settings->get_option( 'company_country' );

			if ( '' !== $street ) {
				$this->append_child( $dom, $address_el, self::NS_CBC, 'cbc:StreetName', $street );
			}
			if ( '' !== $city ) {
				$this->append_child( $dom, $address_el, self::NS_CBC, 'cbc:CityName', $city );
			}
			if ( '' !== $zip ) {
				$this->append_child( $dom, $address_el, self::NS_CBC, 'cbc:PostalZone', $zip );
			}

			$country_code = $this->resolve_country_code( $country );
			$country_el   = $dom->createElementNS( self::NS_CAC, 'cac:Country' );
			$this->append_child( $dom, $country_el, self::NS_CBC, 'cbc:IdentificationCode', $country_code );
			$address_el->appendChild( $country_el );
			$party_el->appendChild( $address_el );

			// PartyTaxScheme.
			$vat = (string) $this->settings->get_option( 'company_vat' );
			if ( '' !== $vat ) {
				$tax_scheme_el = $dom->createElementNS( self::NS_CAC, 'cac:PartyTaxScheme' );
				$this->append_child( $dom, $tax_scheme_el, self::NS_CBC, 'cbc:CompanyID', $vat );

				$scheme_id = (string) $this->settings->get_option( 'company_tax_scheme' );
				$tax_id_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxScheme' );
				$this->append_child( $dom, $tax_id_el, self::NS_CBC, 'cbc:ID', '' !== $scheme_id ? $scheme_id : 'VAT' );
				$tax_scheme_el->appendChild( $tax_id_el );
				$party_el->appendChild( $tax_scheme_el );
			}

			// PartyLegalEntity.
			$legal_entity_el = $dom->createElementNS( self::NS_CAC, 'cac:PartyLegalEntity' );
			$this->append_child( $dom, $legal_entity_el, self::NS_CBC, 'cbc:RegistrationName', $company_name );

			$registration_no = (string) $this->settings->get_option( 'company_registration_number' );
			if ( '' !== $registration_no ) {
				$this->append_child( $dom, $legal_entity_el, self::NS_CBC, 'cbc:CompanyID', $registration_no );
			} elseif ( '' !== $vat ) {
				$this->append_child( $dom, $legal_entity_el, self::NS_CBC, 'cbc:CompanyID', $vat );
			}
			$party_el->appendChild( $legal_entity_el );

			// Contact.
			$phone = (string) $this->settings->get_option( 'company_phone' );
			$email = (string) $this->settings->get_option( 'company_email' );
			if ( '' !== $phone || '' !== $email ) {
				$contact_el = $dom->createElementNS( self::NS_CAC, 'cac:Contact' );
				if ( '' !== $phone ) {
					$this->append_child( $dom, $contact_el, self::NS_CBC, 'cbc:Telephone', $phone );
				}
				if ( '' !== $email ) {
					$this->append_child( $dom, $contact_el, self::NS_CBC, 'cbc:ElectronicMail', $email );
				}
				$party_el->appendChild( $contact_el );
			}

			$supplier_party_el->appendChild( $party_el );
			$invoice_el->appendChild( $supplier_party_el );
		}

		/**
		 * Append buyer party block (cac:AccountingCustomerParty).
		 *
		 * @param DOMDocument $dom        Document instance.
		 * @param DOMElement  $invoice_el Root invoice element.
		 */
		private function append_customer_party( DOMDocument $dom, DOMElement $invoice_el ) {
			$customer_party_el = $dom->createElementNS( self::NS_CAC, 'cac:AccountingCustomerParty' );
			$party_el          = $dom->createElementNS( self::NS_CAC, 'cac:Party' );

			$company    = $this->order->get_billing_company();
			$first_name = $this->order->get_billing_first_name();
			$last_name  = $this->order->get_billing_last_name();
			$full_name  = trim( $first_name . ' ' . $last_name );
			$buyer_name = ! empty( $company ) ? $company : ( ! empty( $full_name ) ? $full_name : __( 'Customer', 'wpwing-wcpdf' ) );

			// PartyName.
			$party_name_el = $dom->createElementNS( self::NS_CAC, 'cac:PartyName' );
			$this->append_child( $dom, $party_name_el, self::NS_CBC, 'cbc:Name', $buyer_name );
			$party_el->appendChild( $party_name_el );

			// PostalAddress.
			$address_el = $dom->createElementNS( self::NS_CAC, 'cac:PostalAddress' );
			$address_1  = $this->order->get_billing_address_1();
			$address_2  = $this->order->get_billing_address_2();
			$street     = trim( $address_1 . ( $address_2 ? ', ' . $address_2 : '' ) );
			$city       = $this->order->get_billing_city();
			$postcode   = $this->order->get_billing_postcode();
			$country    = $this->order->get_billing_country();

			if ( ! empty( $street ) ) {
				$this->append_child( $dom, $address_el, self::NS_CBC, 'cbc:StreetName', $street );
			}
			if ( ! empty( $city ) ) {
				$this->append_child( $dom, $address_el, self::NS_CBC, 'cbc:CityName', $city );
			}
			if ( ! empty( $postcode ) ) {
				$this->append_child( $dom, $address_el, self::NS_CBC, 'cbc:PostalZone', $postcode );
			}

			$country_el = $dom->createElementNS( self::NS_CAC, 'cac:Country' );
			$this->append_child( $dom, $country_el, self::NS_CBC, 'cbc:IdentificationCode', ! empty( $country ) ? strtoupper( $country ) : 'ZZ' );
			$address_el->appendChild( $country_el );
			$party_el->appendChild( $address_el );

			// Customer VAT number if stored in order meta.
			$buyer_vat = $this->get_buyer_vat();
			if ( ! empty( $buyer_vat ) ) {
				$tax_scheme_el = $dom->createElementNS( self::NS_CAC, 'cac:PartyTaxScheme' );
				$this->append_child( $dom, $tax_scheme_el, self::NS_CBC, 'cbc:CompanyID', $buyer_vat );
				$tax_id_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxScheme' );
				$this->append_child( $dom, $tax_id_el, self::NS_CBC, 'cbc:ID', 'VAT' );
				$tax_scheme_el->appendChild( $tax_id_el );
				$party_el->appendChild( $tax_scheme_el );
			}

			// PartyLegalEntity.
			$legal_entity_el = $dom->createElementNS( self::NS_CAC, 'cac:PartyLegalEntity' );
			$this->append_child( $dom, $legal_entity_el, self::NS_CBC, 'cbc:RegistrationName', $buyer_name );
			if ( ! empty( $buyer_vat ) ) {
				$this->append_child( $dom, $legal_entity_el, self::NS_CBC, 'cbc:CompanyID', $buyer_vat );
			}
			$party_el->appendChild( $legal_entity_el );

			// Contact.
			$phone = $this->order->get_billing_phone();
			$email = $this->order->get_billing_email();
			if ( ! empty( $phone ) || ! empty( $email ) ) {
				$contact_el = $dom->createElementNS( self::NS_CAC, 'cac:Contact' );
				if ( ! empty( $full_name ) ) {
					$this->append_child( $dom, $contact_el, self::NS_CBC, 'cbc:Name', $full_name );
				}
				if ( ! empty( $phone ) ) {
					$this->append_child( $dom, $contact_el, self::NS_CBC, 'cbc:Telephone', $phone );
				}
				if ( ! empty( $email ) ) {
					$this->append_child( $dom, $contact_el, self::NS_CBC, 'cbc:ElectronicMail', $email );
				}
				$party_el->appendChild( $contact_el );
			}

			$customer_party_el->appendChild( $party_el );
			$invoice_el->appendChild( $customer_party_el );
		}

		/**
		 * Retrieve the buyer VAT identifier from order meta if available.
		 *
		 * @return string
		 */
		private function get_buyer_vat() {
			$keys = array( '_billing_vat_number', 'vat_number', '_vat_number', 'billing_vat', 'vat_id' );
			foreach ( $keys as $key ) {
				$vat = (string) $this->order->get_meta( $key );
				if ( '' !== $vat ) {
					return $vat;
				}
			}
			return '';
		}

		/**
		 * Append payment means (cac:PaymentMeans).
		 *
		 * @param DOMDocument $dom            Document instance.
		 * @param DOMElement  $invoice_el     Root invoice element.
		 * @param string      $invoice_number Invoice number.
		 */
		private function append_payment_means( DOMDocument $dom, DOMElement $invoice_el, $invoice_number ) {
			$payment_method = (string) $this->order->get_payment_method();

			// Map WooCommerce payment gateway to UN/ECE 4461 Payment Means Code.
			// 10 = Cash, 30 = Credit transfer / Bank transfer, 48 = Credit / Debit Card, 1 = Instrument not defined.
			$code = '1';
			if ( in_array( $payment_method, array( 'bacs', 'bank_transfer', 'cheque' ), true ) ) {
				$code = '30';
			} elseif ( in_array( $payment_method, array( 'cod' ), true ) ) {
				$code = '10';
			} elseif ( in_array( $payment_method, array( 'stripe', 'stripe_cc', 'ppcp', 'paypal', 'woocommerce_payments', 'authorize_net_cim_credit_card' ), true ) ) {
				$code = '48';
			}

			$code = apply_filters( 'wpwing_wcpdf_ubl_payment_means_code', $code, $payment_method, $this->order );

			$means_el = $dom->createElementNS( self::NS_CAC, 'cac:PaymentMeans' );
			$this->append_child( $dom, $means_el, self::NS_CBC, 'cbc:PaymentMeansCode', $code );
			$this->append_child( $dom, $means_el, self::NS_CBC, 'cbc:PaymentID', $invoice_number );
			$invoice_el->appendChild( $means_el );
		}

		/**
		 * Append tax total and subtotals (cac:TaxTotal).
		 *
		 * @param DOMDocument $dom        Document instance.
		 * @param DOMElement  $invoice_el Root invoice element.
		 * @param string      $currency   Currency code.
		 */
		private function append_tax_total( DOMDocument $dom, DOMElement $invoice_el, $currency ) {
			$tax_total_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxTotal' );
			$total_tax    = (float) $this->order->get_total_tax();

			$amount_el = $dom->createElementNS( self::NS_CBC, 'cbc:TaxAmount', $this->format_number( $total_tax ) );
			$amount_el->setAttribute( 'currencyID', $currency );
			$tax_total_el->appendChild( $amount_el );

			// Calculate taxable base and tax subtotal.
			$taxes = $this->order->get_taxes();
			if ( ! empty( $taxes ) ) {
				foreach ( $taxes as $tax_item ) {
					$tax_amount = (float) $tax_item->get_tax_total() + (float) $tax_item->get_shipping_tax_total();
					$rate_id    = $tax_item->get_rate_id();
					$rate_pct   = (float) WC_Tax::get_rate_percent_value( $rate_id );

					// Estimate taxable amount: if tax is non-zero, base = tax / (rate/100).
					if ( $rate_pct > 0.0 ) {
						$taxable_amount = $tax_amount / ( $rate_pct / 100.0 );
					} else {
						$taxable_amount = (float) $this->order->get_subtotal();
					}

					$subtotal_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxSubtotal' );

					$taxable_el = $dom->createElementNS( self::NS_CBC, 'cbc:TaxableAmount', $this->format_number( $taxable_amount ) );
					$taxable_el->setAttribute( 'currencyID', $currency );
					$subtotal_el->appendChild( $taxable_el );

					$sub_amount_el = $dom->createElementNS( self::NS_CBC, 'cbc:TaxAmount', $this->format_number( $tax_amount ) );
					$sub_amount_el->setAttribute( 'currencyID', $currency );
					$subtotal_el->appendChild( $sub_amount_el );

					// TaxCategory.
					$category_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxCategory' );
					$category_id = $rate_pct > 0.0 ? 'S' : 'Z';
					$this->append_child( $dom, $category_el, self::NS_CBC, 'cbc:ID', $category_id );
					$this->append_child( $dom, $category_el, self::NS_CBC, 'cbc:Percent', $this->format_number( $rate_pct ) );

					$tax_scheme_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxScheme' );
					$this->append_child( $dom, $tax_scheme_el, self::NS_CBC, 'cbc:ID', 'VAT' );
					$category_el->appendChild( $tax_scheme_el );

					$subtotal_el->appendChild( $category_el );
					$tax_total_el->appendChild( $subtotal_el );
				}
			} else {
				// Zero-tax or tax-exempt fallback subtotal.
				$subtotal_el    = $dom->createElementNS( self::NS_CAC, 'cac:TaxSubtotal' );
				$taxable_amount = (float) $this->order->get_subtotal();

				$taxable_el = $dom->createElementNS( self::NS_CBC, 'cbc:TaxableAmount', $this->format_number( $taxable_amount ) );
				$taxable_el->setAttribute( 'currencyID', $currency );
				$subtotal_el->appendChild( $taxable_el );

				$sub_amount_el = $dom->createElementNS( self::NS_CBC, 'cbc:TaxAmount', $this->format_number( 0.0 ) );
				$sub_amount_el->setAttribute( 'currencyID', $currency );
				$subtotal_el->appendChild( $sub_amount_el );

				$category_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxCategory' );
				$this->append_child( $dom, $category_el, self::NS_CBC, 'cbc:ID', 'Z' );
				$this->append_child( $dom, $category_el, self::NS_CBC, 'cbc:Percent', '0.00' );

				$tax_scheme_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxScheme' );
				$this->append_child( $dom, $tax_scheme_el, self::NS_CBC, 'cbc:ID', 'VAT' );
				$category_el->appendChild( $tax_scheme_el );

				$subtotal_el->appendChild( $category_el );
				$tax_total_el->appendChild( $subtotal_el );
			}

			$invoice_el->appendChild( $tax_total_el );
		}

		/**
		 * Append legal monetary total block (cac:LegalMonetaryTotal).
		 *
		 * @param DOMDocument $dom        Document instance.
		 * @param DOMElement  $invoice_el Root invoice element.
		 * @param string      $currency   Currency code.
		 */
		private function append_monetary_total( DOMDocument $dom, DOMElement $invoice_el, $currency ) {
			$monetary_el = $dom->createElementNS( self::NS_CAC, 'cac:LegalMonetaryTotal' );

			$items_total = 0.0;
			$line_items  = $this->order->get_items( 'line_item' );
			foreach ( $line_items as $item ) {
				if ( $item instanceof WC_Order_Item_Product ) {
					$items_total += (float) $item->get_total();
				}
			}

			$shipping_total = (float) $this->order->get_shipping_total();
			$discount_total = (float) $this->order->get_discount_total();
			$total_tax      = (float) $this->order->get_total_tax();
			$grand_total    = (float) $this->order->get_total();
			$net_total      = $grand_total - $total_tax;

			$amounts = array(
				'LineExtensionAmount'  => $items_total,
				'TaxExclusiveAmount'   => $net_total,
				'TaxInclusiveAmount'   => $grand_total,
				'AllowanceTotalAmount' => $discount_total,
				'ChargeTotalAmount'    => $shipping_total,
				'PrepaidAmount'        => $this->order->is_paid() ? $grand_total : 0.0,
				'PayableAmount'        => $grand_total,
			);

			foreach ( $amounts as $name => $val ) {
				$el = $dom->createElementNS( self::NS_CBC, 'cbc:' . $name, $this->format_number( $val ) );
				$el->setAttribute( 'currencyID', $currency );
				$monetary_el->appendChild( $el );
			}

			$invoice_el->appendChild( $monetary_el );
		}

		/**
		 * Append invoice line items (cac:InvoiceLine).
		 *
		 * @param DOMDocument $dom        Document instance.
		 * @param DOMElement  $invoice_el Root invoice element.
		 * @param string      $currency   Currency code.
		 */
		private function append_invoice_lines( DOMDocument $dom, DOMElement $invoice_el, $currency ) {
			$line_items = $this->order->get_items( 'line_item' );
			$line_num   = 1;

			foreach ( $line_items as $item ) {
				if ( ! $item instanceof WC_Order_Item_Product ) {
					continue;
				}

				$line_el = $dom->createElementNS( self::NS_CAC, 'cac:InvoiceLine' );
				$this->append_child( $dom, $line_el, self::NS_CBC, 'cbc:ID', (string) $line_num );

				// InvoicedQuantity with UN/ECE Rec 20 unit code (C62 = piece/unit).
				$qty    = $item->get_quantity();
				$qty_el = $dom->createElementNS( self::NS_CBC, 'cbc:InvoicedQuantity', (string) $qty );
				$qty_el->setAttribute( 'unitCode', 'C62' );
				$line_el->appendChild( $qty_el );

				// LineExtensionAmount (net total).
				$line_total    = (float) $item->get_total();
				$line_total_el = $dom->createElementNS( self::NS_CBC, 'cbc:LineExtensionAmount', $this->format_number( $line_total ) );
				$line_total_el->setAttribute( 'currencyID', $currency );
				$line_el->appendChild( $line_total_el );

				// Item block.
				$item_el = $dom->createElementNS( self::NS_CAC, 'cac:Item' );
				$this->append_child( $dom, $item_el, self::NS_CBC, 'cbc:Description', $item->get_name() );
				$this->append_child( $dom, $item_el, self::NS_CBC, 'cbc:Name', $item->get_name() );

				$product = $item->get_product();
				$sku     = $product ? $product->get_sku() : '';
				if ( ! empty( $sku ) ) {
					$sellers_item_el = $dom->createElementNS( self::NS_CAC, 'cac:SellersItemIdentification' );
					$this->append_child( $dom, $sellers_item_el, self::NS_CBC, 'cbc:ID', $sku );
					$item_el->appendChild( $sellers_item_el );
				}

				// Tax classification.
				$tax_total = (float) $item->get_total_tax();
				$rate_pct  = 0.0;
				if ( $line_total > 0.0 && $tax_total > 0.0 ) {
					$rate_pct = round( ( $tax_total / $line_total ) * 100, 2 );
				}

				$tax_cat_el = $dom->createElementNS( self::NS_CAC, 'cac:ClassifiedTaxCategory' );
				$cat_id     = $rate_pct > 0.0 ? 'S' : 'Z';
				$this->append_child( $dom, $tax_cat_el, self::NS_CBC, 'cbc:ID', $cat_id );
				$this->append_child( $dom, $tax_cat_el, self::NS_CBC, 'cbc:Percent', $this->format_number( $rate_pct ) );

				$scheme_el = $dom->createElementNS( self::NS_CAC, 'cac:TaxScheme' );
				$this->append_child( $dom, $scheme_el, self::NS_CBC, 'cbc:ID', 'VAT' );
				$tax_cat_el->appendChild( $scheme_el );
				$item_el->appendChild( $tax_cat_el );
				$line_el->appendChild( $item_el );

				// Unit price.
				$unit_price = $qty > 0 ? ( $line_total / $qty ) : $line_total;
				$price_el   = $dom->createElementNS( self::NS_CAC, 'cac:Price' );
				$p_amount   = $dom->createElementNS( self::NS_CBC, 'cbc:PriceAmount', $this->format_number( $unit_price ) );
				$p_amount->setAttribute( 'currencyID', $currency );
				$price_el->appendChild( $p_amount );

				$base_qty = $dom->createElementNS( self::NS_CBC, 'cbc:BaseQuantity', '1' );
				$base_qty->setAttribute( 'unitCode', 'C62' );
				$price_el->appendChild( $base_qty );
				$line_el->appendChild( $price_el );

				$invoice_el->appendChild( $line_el );
				++$line_num;
			}
		}

		/**
		 * Helper to create and append a namespaced child element safely.
		 *
		 * @param DOMDocument $dom       Document instance.
		 * @param DOMElement  $parent_el Parent element.
		 * @param string      $ns_uri    Namespace URI.
		 * @param string      $tag_name  Qualified tag name.
		 * @param string      $value     Text value.
		 * @return DOMElement
		 */
		private function append_child( DOMDocument $dom, DOMElement $parent_el, $ns_uri, $tag_name, $value ) {
			$el = $dom->createElementNS( $ns_uri, $tag_name );
			$el->appendChild( $dom->createTextNode( (string) $value ) );
			$parent_el->appendChild( $el );
			return $el;
		}

		/**
		 * Format floats with standard 2 decimal places.
		 *
		 * @param float|int $val Numeric value.
		 * @return string
		 */
		private function format_number( $val ) {
			return number_format( (float) $val, 2, '.', '' );
		}

		/**
		 * Map a country name or code to a 2-letter ISO 3166-1 alpha-2 code.
		 *
		 * @param string $country Country name or code.
		 * @return string 2-letter ISO code.
		 */
		private function resolve_country_code( $country ) {
			$country = trim( (string) $country );
			if ( 2 === strlen( $country ) ) {
				return strtoupper( $country );
			}

			// Fallback to WooCommerce base country.
			if ( function_exists( 'wc_get_base_location' ) ) {
				$base = wc_get_base_location();
				if ( ! empty( $base['country'] ) ) {
					return strtoupper( $base['country'] );
				}
			}

			return 'ZZ';
		}

		/**
		 * Save the UBL XML file to disk and register its relative path in order meta.
		 *
		 * @param string|null $custom_path Optional custom relative path.
		 * @return string Absolute file path to saved XML.
		 */
		public function save_xml( $custom_path = null ) {
			if ( ! $this->is_valid() ) {
				return '';
			}

			$xml = $this->generate_xml();
			if ( empty( $xml ) ) {
				return '';
			}

			$year     = (int) wp_date( 'Y' );
			$year_dir = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . $year;
			if ( ! file_exists( $year_dir ) ) {
				wp_mkdir_p( $year_dir );
			}

			if ( ! empty( $custom_path ) ) {
				$relative_path = $custom_path;
			} elseif ( $this->invoice && ! empty( $this->invoice->save_path ) ) {
				$relative_path = preg_replace( '/\.pdf$/i', '.xml', $this->invoice->save_path );
			} else {
				$filename      = 'invoice_' . $this->order->get_id();
				$relative_path = $year . '/' . $filename . '.xml';
			}

			$full_path = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . $relative_path;
			$dir       = dirname( $full_path );
			if ( ! file_exists( $dir ) ) {
				wp_mkdir_p( $dir );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $full_path, $xml );

			$this->order->update_meta_data( '_wpwing_wcpdf_invoice_ubl_path', $relative_path );
			$this->order->apply_changes();
			$this->order->save_meta_data();

			return $full_path;
		}

		/**
		 * Return the absolute file path to the UBL XML file, generating it if needed.
		 *
		 * @return string Absolute file path or empty string.
		 */
		public function get_xml_file_path() {
			if ( ! $this->is_valid() ) {
				return '';
			}

			$stored_path = (string) $this->order->get_meta( '_wpwing_wcpdf_invoice_ubl_path' );
			if ( ! empty( $stored_path ) ) {
				$full_path = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . $stored_path;
				if ( file_exists( $full_path ) ) {
					return $full_path;
				}
			}

			return $this->save_xml();
		}
	}
}
