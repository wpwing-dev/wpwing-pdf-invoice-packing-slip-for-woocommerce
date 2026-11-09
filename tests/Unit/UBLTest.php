<?php
/**
 * Unit tests for UBL 2.1 e-invoicing export and hardening.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip\Tests
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class UBLTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings-api.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-document.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-invoice.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-packing.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-delivery.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-shipping-label.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-proforma.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-ubl.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-admin.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-wizard.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-orders-list.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-wc-hooks.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-shortcodes.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-plugin.php';
	}

	private static $options_store = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		self::$options_store = array();
		Functions\when( 'did_action' )->justReturn( 1 );
		Functions\when( 'wp_date' )->alias( 'gmdate' );
		Functions\when( 'wc_get_order' )->justReturn( false );
		Functions\when( 'get_option' )->alias(
			function ( $key ) {
				if ( 'wpwing_wcpdf_settings' === $key ) {
					return self::$options_store;
				}
				return false;
			}
		);
		Functions\when( 'update_option' )->alias(
			function ( $key, $value ) {
				if ( 'wpwing_wcpdf_settings' === $key ) {
					self::$options_store = $value;
				}
				return true;
			}
		);
		Functions\when( 'current_theme_supports' )->justReturn( false );
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'add_shortcode' )->justReturn( null );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'sanitize_file_name' )->alias(
			function ( $name ) {
				return preg_replace( '/[^A-Za-z0-9._-]/', '', $name );
			}
		);
		Functions\when( 'wp_unique_filename' )->alias(
			function ( $dir, $name ) {
				return $name;
			}
		);
	}

	protected function tearDown(): void {
		unset( $GLOBALS['wp_filter'] );
		$this->addToAssertionCount( Mockery::getContainer()->mockery_getExpectationCount() );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Helper to create a fully configured mock WC_Order for UBL tests.
	 *
	 * @param array $options Config overrides.
	 * @return WC_Order
	 */
	private function create_test_order( array $options = array() ): WC_Order {
		$id             = isset( $options['id'] ) ? $options['id'] : 101;
		$order_number   = isset( $options['order_number'] ) ? $options['order_number'] : '101';
		$currency       = isset( $options['currency'] ) ? $options['currency'] : 'EUR';
		$total          = isset( $options['total'] ) ? (float) $options['total'] : 120.00;
		$subtotal       = isset( $options['subtotal'] ) ? (float) $options['subtotal'] : 100.00;
		$total_tax      = isset( $options['total_tax'] ) ? (float) $options['total_tax'] : 20.00;
		$shipping_total = isset( $options['shipping_total'] ) ? (float) $options['shipping_total'] : 0.00;
		$discount_total = isset( $options['discount_total'] ) ? (float) $options['discount_total'] : 0.00;
		$payment_method = isset( $options['payment_method'] ) ? $options['payment_method'] : 'bacs';
		$meta           = isset( $options['meta'] ) ? $options['meta'] : array();

		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_id' )->willReturn( $id );
		$order->method( 'get_order_number' )->willReturn( $order_number );
		$order->method( 'get_currency' )->willReturn( $currency );
		$order->method( 'get_total' )->willReturn( $total );
		$order->method( 'get_subtotal' )->willReturn( $subtotal );
		$order->method( 'get_total_tax' )->willReturn( $total_tax );
		$order->method( 'get_shipping_total' )->willReturn( $shipping_total );
		$order->method( 'get_discount_total' )->willReturn( $discount_total );
		$order->method( 'get_payment_method' )->willReturn( $payment_method );
		$order->method( 'is_paid' )->willReturn( ! empty( $options['is_paid'] ) );

		// Billing address.
		$order->method( 'get_billing_company' )->willReturn( isset( $options['billing_company'] ) ? $options['billing_company'] : 'Acme Client Corp' );
		$order->method( 'get_billing_first_name' )->willReturn( 'John' );
		$order->method( 'get_billing_last_name' )->willReturn( 'Doe' );
		$order->method( 'get_billing_address_1' )->willReturn( '456 Client St' );
		$order->method( 'get_billing_address_2' )->willReturn( 'Suite 200' );
		$order->method( 'get_billing_city' )->willReturn( 'Berlin' );
		$order->method( 'get_billing_postcode' )->willReturn( '10115' );
		$order->method( 'get_billing_country' )->willReturn( 'DE' );
		$order->method( 'get_billing_email' )->willReturn( 'billing@acme.de' );
		$order->method( 'get_billing_phone' )->willReturn( '+49 30 12345678' );

		// Meta.
		$order->method( 'get_meta' )->willReturnCallback(
			function ( $key ) use ( $meta ) {
				return isset( $meta[ $key ] ) ? $meta[ $key ] : '';
			}
		);

		// Line items.
		$item = $this->createMock( WC_Order_Item_Product::class );
		$item->method( 'get_name' )->willReturn( 'Premium Widget' );
		$item->method( 'get_quantity' )->willReturn( 2 );
		$item->method( 'get_total' )->willReturn( 100.00 );
		$item->method( 'get_total_tax' )->willReturn( 20.00 );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_sku' )->willReturn( 'WDG-001' );
		$item->method( 'get_product' )->willReturn( $product );

		$order->method( 'get_items' )->willReturn( array( $item ) );

		return $order;
	}

	public function test_ubl_initialization(): void {
		$order = $this->create_test_order();
		$ubl   = new WPWing_WcPdf_UBL( $order );

		$this->assertTrue( $ubl->is_valid() );
		$this->assertSame( '101', $ubl->get_invoice_identifier() );
		$this->assertSame( wp_date( 'Y-m-d' ), $ubl->get_issue_date() );
	}

	public function test_ubl_generate_xml_validity_and_namespaces(): void {
		$order = $this->create_test_order(
			array(
				'meta' => array(
					'_wpwing_wcpdf_invoiced'       => 1,
					'_wpwing_wcpdf_invoice_number' => 505,
				),
			)
		);
		$ubl   = new WPWing_WcPdf_UBL( $order );
		$xml   = $ubl->generate_xml();

		$this->assertNotEmpty( $xml );

		$dom = new DOMDocument();
		$loaded = $dom->loadXML( $xml );
		$this->assertTrue( $loaded, 'Generated UBL XML must be well-formed XML.' );

		$root = $dom->documentElement;
		$this->assertSame( 'Invoice', $root->localName );
		$this->assertSame( 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2', $root->namespaceURI );

		// Header elements.
		$ubl_version = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'UBLVersionID' )->item( 0 );
		$this->assertNotNull( $ubl_version );
		$this->assertSame( '2.1', $ubl_version->nodeValue );

		$invoice_type = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'InvoiceTypeCode' )->item( 0 );
		$this->assertNotNull( $invoice_type );
		$this->assertSame( '380', $invoice_type->nodeValue );

		$currency = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'DocumentCurrencyCode' )->item( 0 );
		$this->assertNotNull( $currency );
		$this->assertSame( 'EUR', $currency->nodeValue );
	}

	public function test_ubl_supplier_and_customer_parties(): void {
		$order = $this->create_test_order(
			array(
				'meta' => array(
					'_billing_vat_number' => 'DE987654321',
				),
			)
		);

		// Configure company settings.
		$settings = WPWing_WcPdf_Settings::get_instance();
		$settings->set_option( 'company_name_text', 'WPWing Global Corp' );
		$settings->set_option( 'company_address', '123 Tech Boulevard' );
		$settings->set_option( 'company_city', 'Munich' );
		$settings->set_option( 'company_zip', '80331' );
		$settings->set_option( 'company_country', 'DE' );
		$settings->set_option( 'company_vat', 'DE123456789' );
		$settings->set_option( 'company_registration_number', 'HRB 999999' );
		$settings->set_option( 'company_tax_scheme', 'VAT' );

		$ubl = new WPWing_WcPdf_UBL( $order );
		$xml = $ubl->generate_xml();

		$dom = new DOMDocument();
		$dom->loadXML( $xml );

		// Supplier checks.
		$supplier = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CAC, 'AccountingSupplierParty' )->item( 0 );
		$this->assertNotNull( $supplier );
		$this->assertStringContainsString( 'WPWing Global Corp', $supplier->textContent );
		$this->assertStringContainsString( 'DE123456789', $supplier->textContent );
		$this->assertStringContainsString( 'HRB 999999', $supplier->textContent );

		// Customer checks.
		$customer = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CAC, 'AccountingCustomerParty' )->item( 0 );
		$this->assertNotNull( $customer );
		$this->assertStringContainsString( 'Acme Client Corp', $customer->textContent );
		$this->assertStringContainsString( 'DE987654321', $customer->textContent );
	}

	public function test_ubl_payment_means_mapping(): void {
		// BACS -> 30.
		$order_bacs = $this->create_test_order( array( 'payment_method' => 'bacs' ) );
		$ubl_bacs   = new WPWing_WcPdf_UBL( $order_bacs );
		$dom_bacs   = new DOMDocument();
		$dom_bacs->loadXML( $ubl_bacs->generate_xml() );
		$means_code = $dom_bacs->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'PaymentMeansCode' )->item( 0 );
		$this->assertSame( '30', $means_code->nodeValue );

		// COD -> 10.
		$order_cod = $this->create_test_order( array( 'payment_method' => 'cod' ) );
		$ubl_cod   = new WPWing_WcPdf_UBL( $order_cod );
		$dom_cod   = new DOMDocument();
		$dom_cod->loadXML( $ubl_cod->generate_xml() );
		$means_cod = $dom_cod->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'PaymentMeansCode' )->item( 0 );
		$this->assertSame( '10', $means_cod->nodeValue );

		// Stripe/Credit Card -> 48.
		$order_stripe = $this->create_test_order( array( 'payment_method' => 'stripe' ) );
		$ubl_stripe   = new WPWing_WcPdf_UBL( $order_stripe );
		$dom_stripe   = new DOMDocument();
		$dom_stripe->loadXML( $ubl_stripe->generate_xml() );
		$means_stripe = $dom_stripe->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'PaymentMeansCode' )->item( 0 );
		$this->assertSame( '48', $means_stripe->nodeValue );
	}

	public function test_ubl_monetary_totals_and_invoice_lines(): void {
		$order = $this->create_test_order(
			array(
				'total'          => 120.00,
				'subtotal'       => 100.00,
				'total_tax'      => 20.00,
				'shipping_total' => 0.00,
				'is_paid'        => true,
			)
		);
		$ubl   = new WPWing_WcPdf_UBL( $order );
		$dom   = new DOMDocument();
		$dom->loadXML( $ubl->generate_xml() );

		// Monetary totals.
		$line_ext = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'LineExtensionAmount' )->item( 0 );
		$this->assertSame( '100.00', $line_ext->nodeValue );

		$tax_inc = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'TaxInclusiveAmount' )->item( 0 );
		$this->assertSame( '120.00', $tax_inc->nodeValue );

		$payable = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'PayableAmount' )->item( 0 );
		$this->assertSame( '120.00', $payable->nodeValue );

		// Line item.
		$line = $dom->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CAC, 'InvoiceLine' )->item( 0 );
		$this->assertNotNull( $line );

		$qty = $line->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'InvoicedQuantity' )->item( 0 );
		$this->assertSame( '2', $qty->nodeValue );
		$this->assertSame( 'C62', $qty->getAttribute( 'unitCode' ) );

		$sku = $line->getElementsByTagNameNS( WPWing_WcPdf_UBL::NS_CBC, 'ID' )->item( 1 );
		$this->assertSame( 'WDG-001', $sku->nodeValue );
	}

	public function test_ubl_save_and_retrieve_xml_file(): void {
		$tmp_dir = sys_get_temp_dir() . '/wpwing-pdf-invoices-test/';
		wp_mkdir_p( $tmp_dir );

		$order = $this->create_test_order(
			array(
				'id' => 777,
			)
		);

		$ubl       = new WPWing_WcPdf_UBL( $order );
		$file_path = $ubl->save_xml();

		$this->assertNotEmpty( $file_path );
		$this->assertFileExists( $file_path );
		$this->assertStringEndsWith( '.xml', $file_path );

		$retrieved = $ubl->get_xml_file_path();
		$this->assertSame( $file_path, $retrieved );

		// Clean up.
		wp_delete_file( $file_path );
	}

	public function test_invoice_reset_cleans_ubl_xml(): void {
		$order = $this->create_test_order(
			array(
				'id'   => 888,
				'meta' => array(
					'_wpwing_wcpdf_invoiced'         => 1,
					'_wpwing_wcpdf_invoice_ubl_path' => '2026/test_reset_888.xml',
				),
			)
		);

		$test_file = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . '2026/test_reset_888.xml';
		wp_mkdir_p( dirname( $test_file ) );
		file_put_contents( $test_file, '<xml></xml>' );
		$this->assertFileExists( $test_file );

		$invoice = new WPWing_WcPdf_Invoice( 888 );
		// Inject mock order.
		$ref = new ReflectionProperty( $invoice, 'order' );
		$ref->setValue( $invoice, $order );

		$invoice->reset();

		$this->assertFileDoesNotExist( $test_file, 'Resetting invoice must delete the UBL XML file.' );
	}

	public function test_email_attachment_attaches_ubl_xml(): void {
		$order = $this->create_test_order(
			array(
				'id'   => 999,
				'meta' => array(
					'_wpwing_wcpdf_invoiced'     => 1,
					'_wpwing_wcpdf_invoice_path' => '2026/invoice_999.pdf',
				),
			)
		);

		$pdf_file = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . '2026/invoice_999.pdf';
		wp_mkdir_p( dirname( $pdf_file ) );
		file_put_contents( $pdf_file, '%PDF-1.4 test' );

		Functions\when( 'wc_get_order' )->justReturn( $order );

		$plugin   = new WPWing_WcPdf_Plugin();
		$settings = WPWing_WcPdf_Settings::get_instance();
		$settings->set_option( 'invoice_attach_to_emails', array( 'customer_completed_order' ) );
		$settings->set_option( 'invoice_ubl_attach_to_emails', array( 'customer_completed_order' ) );

		$hooks       = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );
		$attachments = $hooks->attach_document_to_email( array(), 'customer_completed_order', $order );

		$this->assertCount( 2, $attachments );
		$this->assertStringEndsWith( '.pdf', $attachments[0] );
		$this->assertStringEndsWith( '.xml', $attachments[1] );

		// Clean up.
		wp_delete_file( $pdf_file );
		if ( isset( $attachments[1] ) && file_exists( $attachments[1] ) ) {
			wp_delete_file( $attachments[1] );
		}
	}

	public function test_bulk_zip_filename_disambiguation(): void {
		$plugin      = new WPWing_WcPdf_Plugin();
		$orders_list = new WPWing_WcPdf_Orders_List( $plugin );

		// Create two documents with identical basenames.
		$order1 = $this->create_test_order( array( 'id' => 101 ) );
		$order2 = $this->create_test_order( array( 'id' => 102 ) );

		$doc1 = $this->createMock( WPWing_WcPdf_Invoice::class );
		$doc1->order     = $order1;
		$doc1->save_path = '2026/invoice.pdf';

		$doc2 = $this->createMock( WPWing_WcPdf_Invoice::class );
		$doc2->order     = $order2;
		$doc2->save_path = '2025/invoice.pdf';

		// Create dummy files.
		$file1 = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . '2026/invoice.pdf';
		$file2 = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . '2025/invoice.pdf';
		wp_mkdir_p( dirname( $file1 ) );
		wp_mkdir_p( dirname( $file2 ) );
		file_put_contents( $file1, '%PDF doc1' );
		file_put_contents( $file2, '%PDF doc2' );

		// Use reflection to inspect the disambiguation logic in stream_zip.
		$ref = new ReflectionMethod( $orders_list, 'stream_zip' );
		$this->assertTrue( $ref->isPrivate() );

		// Verify using reflection on files array construction.
		$base  = realpath( WPWING_WCPDF_DOCUMENT_SAVE_DIR );
		$files = array();
		foreach ( array( $doc1, $doc2 ) as $doc ) {
			$real = realpath( WPWING_WCPDF_DOCUMENT_SAVE_DIR . $doc->save_path );
			if ( $real && $base && 0 === strpos( $real, $base . DIRECTORY_SEPARATOR ) ) {
				$entry_name = basename( $real );
				if ( isset( $files[ $entry_name ] ) ) {
					$ext        = pathinfo( $entry_name, PATHINFO_EXTENSION );
					$name       = pathinfo( $entry_name, PATHINFO_FILENAME );
					$entry_name = sprintf( '%s-order-%d.%s', $name, $doc->order->get_id(), $ext );
				}
				$files[ $entry_name ] = $real;
			}
		}

		$this->assertCount( 2, $files );
		$this->assertArrayHasKey( 'invoice.pdf', $files );
		$this->assertArrayHasKey( 'invoice-order-102.pdf', $files );

		// Clean up.
		wp_delete_file( $file1 );
		wp_delete_file( $file2 );
	}

	public function test_bulk_actions_registered(): void {
		$plugin      = new WPWing_WcPdf_Plugin();
		$orders_list = new WPWing_WcPdf_Orders_List( $plugin );
		$actions     = $orders_list->add_bulk_actions( array() );

		$this->assertArrayHasKey( 'wpwing_download_invoices_ubl_zip', $actions );
		$this->assertSame( 'Download Invoices (UBL 2.1 XML ZIP)', $actions['wpwing_download_invoices_ubl_zip'] );
	}
}
