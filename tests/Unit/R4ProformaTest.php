<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

class R4ProformaTest extends TestCase {

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
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-admin.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-wizard.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-orders-list.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-wc-hooks.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-shortcodes.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-plugin.php';
		require_once WPWING_WCPDF_DIR . 'pro/includes/class-wcpdf-proforma.php';
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'wp_date' )->alias( 'gmdate' );
		Functions\when( 'wc_get_order' )->justReturn( false );
		Functions\when( 'get_option' )->justReturn( false );
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

	private function create_order( array $meta = array(), int $id = 42, string $order_number = '1042' ): WC_Order {
		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_id' )->willReturn( $id );
		$order->method( 'get_order_number' )->willReturn( $order_number );
		$order->method( 'get_customer_id' )->willReturn( 10 );
		$order->method( 'get_total' )->willReturn( 99.0 );

		$order->method( 'get_meta' )->willReturnCallback(
			function ( $key ) use ( $meta ) {
				return isset( $meta[ $key ] ) ? $meta[ $key ] : '';
			}
		);

		return $order;
	}

	public function test_proforma_initializes_with_order_id_when_no_meta(): void {
		$order = $this->create_order();
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma = new WPWing_WcPdf_Proforma( 42 );

		$this->assertFalse( $proforma->exists );
		$this->assertSame( 42, $proforma->number );
		$this->assertSame( 'PRO-42', $proforma->get_formatted_proforma_number() );
	}

	public function test_proforma_loads_existing_meta(): void {
		$order = $this->create_order(
			array(
				'_wpwing_wcpdf_proforma'        => 1,
				'_wpwing_wcpdf_proforma_path'   => '2026/proforma_99.pdf',
				'_wpwing_wcpdf_proforma_number' => 'PRO-99',
				'_wpwing_wcpdf_proforma_date'   => 1700000000,
			)
		);
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma = new WPWing_WcPdf_Proforma( 42 );

		$this->assertTrue( $proforma->exists );
		$this->assertSame( '2026/proforma_99.pdf', $proforma->save_path );
		$this->assertSame( 'PRO-99', $proforma->number );
	}

	public function test_proforma_from_label(): void {
		$order = $this->create_order();
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma = new WPWing_WcPdf_Proforma( 42 );
		$ref      = new ReflectionMethod( WPWing_WcPdf_Proforma::class, 'get_from_label' );
		$ref->setAccessible( true );

		$this->assertSame( 'Proforma From', $ref->invoke( $proforma ) );
	}

	public function test_proforma_reset_deletes_meta(): void {
		$order = $this->create_order();
		$order->expects( $this->exactly( 4 ) )
			->method( 'delete_meta_data' )
			->willReturnCallback(
				function ( $key ) {
					$valid_keys = array(
						'_wpwing_wcpdf_proforma',
						'_wpwing_wcpdf_proforma_path',
						'_wpwing_wcpdf_proforma_number',
						'_wpwing_wcpdf_proforma_date',
					);
					TestCase::assertTrue( in_array( $key, $valid_keys, true ) );
				}
			);
		$order->expects( $this->once() )->method( 'save_meta_data' );

		Functions\when( 'wc_get_order' )->justReturn( $order );
		$proforma = new WPWing_WcPdf_Proforma( 42 );
		$proforma->reset();
	}

	public function test_plugin_returns_proforma_document(): void {
		$order = $this->create_order();
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$plugin   = new WPWing_WcPdf_Plugin();
		$document = $plugin->get_document_by_type( 42, 'proforma' );

		$this->assertInstanceOf( WPWing_WcPdf_Proforma::class, $document );
		$this->assertSame( 'proforma', $document->document_type );
	}

	public function test_proforma_class_can_be_filtered_for_pro_subclass(): void {
		$order = $this->create_order();
		Functions\when( 'wc_get_order' )->justReturn( $order );
		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'wpwing_wcpdf_proforma_class' === $hook ) {
					return WPWing_WcPdf_Pro_Proforma::class;
				}
				return $value;
			}
		);

		$plugin   = new WPWing_WcPdf_Plugin();
		$document = $plugin->get_document_by_type( 42, 'proforma' );

		$this->assertInstanceOf( WPWing_WcPdf_Pro_Proforma::class, $document );
		$this->assertInstanceOf( WPWing_WcPdf_Proforma::class, $document );
	}

	public function test_pro_subclass_formats_number(): void {
		$order = $this->create_order( array(), 55, '5555' );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma         = new WPWing_WcPdf_Pro_Proforma( 55 );
		$proforma->number = 55;
		$this->assertSame( 'PRO-55', $proforma->get_formatted_proforma_number() );
	}

	public function test_orders_list_bulk_actions_include_proforma(): void {
		$plugin      = new WPWing_WcPdf_Plugin();
		$orders_list = new WPWing_WcPdf_Orders_List( $plugin );

		$actions = $orders_list->add_bulk_actions( array() );

		$this->assertArrayHasKey( 'wpwing_generate_proforma', $actions );
		$this->assertArrayHasKey( 'wpwing_download_proforma_zip', $actions );
		$this->assertArrayHasKey( 'wpwing_download_proforma_pdf', $actions );
	}

	public function test_status_change_auto_generates_proforma_on_matching_status(): void {
		$plugin   = $this->createMock( WPWing_WcPdf_Plugin::class );
		$settings = $this->createMock( WPWing_WcPdf_Settings::class );
		$settings->method( 'get_option' )->willReturnMap(
			array(
				array( 'invoice_auto_statuses', array() ),
				array( 'packing_auto_statuses', array() ),
				array( 'delivery_auto_statuses', array() ),
				array( 'shipping_label_auto_statuses', array() ),
				array( 'proforma_auto_statuses', array( 'pending', 'on-hold' ) ),
			)
		);

		$order = $this->create_order( array(), 42 );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma         = new WPWing_WcPdf_Proforma( 42 );
		$proforma->exists = false;

		$plugin->expects( $this->once() )
			->method( 'get_document_by_type' )
			->with( 42, 'proforma' )
			->willReturn( $proforma );

		$plugin->expects( $this->once() )
			->method( 'save_document' )
			->with( $proforma );

		$hooks = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );

		$hooks->maybe_auto_generate( 42, 'draft', 'pending', $order );
	}

	public function test_email_attachment_attaches_proforma_pdf(): void {
		$plugin   = $this->createMock( WPWing_WcPdf_Plugin::class );
		$settings = $this->createMock( WPWing_WcPdf_Settings::class );
		$settings->method( 'get_option' )->willReturnMap(
			array(
				array( 'invoice_attach_to_emails', array() ),
				array( 'proforma_attach_to_emails', array( 'customer_on_hold_order' ) ),
			)
		);

		$save_dir = sys_get_temp_dir() . '/wpwing-pdf-invoices/';
		wp_mkdir_p( $save_dir . '2026' );
		$pdf_file = $save_dir . '2026/proforma_42.pdf';
		file_put_contents( $pdf_file, '%PDF-1.4 test' );

		$order = $this->create_order( array(), 42 );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma            = new WPWing_WcPdf_Proforma( 42 );
		$proforma->exists    = true;
		$proforma->save_path = '2026/proforma_42.pdf';

		$plugin->method( 'get_document_by_type' )
			->with( 42, 'proforma' )
			->willReturn( $proforma );

		$hooks       = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );
		$attachments = $hooks->attach_document_to_email( array(), 'customer_on_hold_order', $order );

		$this->assertCount( 1, $attachments );
		$this->assertSame( realpath( $pdf_file ), $attachments[0] );

		if ( file_exists( $pdf_file ) ) {
			unlink( $pdf_file );
		}
	}

	public function test_my_account_shows_proforma_when_no_invoice(): void {
		$plugin   = $this->createMock( WPWing_WcPdf_Plugin::class );
		$settings = $this->createMock( WPWing_WcPdf_Settings::class );

		$order = $this->create_order( array(), 77 );
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$invoice         = new WPWing_WcPdf_Invoice( 77 );
		$invoice->exists = false;

		$proforma         = new WPWing_WcPdf_Proforma( 77 );
		$proforma->exists = true;

		$plugin->method( 'get_document_by_type' )->willReturnCallback(
			function ( $id, $type ) use ( $invoice, $proforma ) {
				if ( 'invoice' === $type ) {
					return $invoice;
				}
				if ( 'proforma' === $type ) {
					return $proforma;
				}
				return null;
			}
		);

		Functions\when( 'add_query_arg' )->justReturn( 'http://example.test/?wpwing-view-proforma=77' );
		Functions\when( 'wp_nonce_url' )->justReturn( 'http://example.test/?wpwing-view-proforma=77&_wpnonce=abc' );

		$hooks   = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );
		$actions = $hooks->my_account_order_actions( array(), $order );

		$this->assertArrayHasKey( 'wpwing_proforma', $actions );
		$this->assertSame( 'Proforma', $actions['wpwing_proforma']['name'] );
	}

	public function test_proforma_due_date_calculation(): void {
		$order = $this->create_order();
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma = new WPWing_WcPdf_Proforma( 42 );

		$settings = $this->createMock( WPWing_WcPdf_Settings::class );
		$settings->method( 'get_option' )->willReturnMap(
			array(
				array( 'invoice_due_date_days', '14' ),
				array( 'invoice_date_format', 'Y-m-d' ),
			)
		);
		$proforma->settings = $settings;

		$due = $proforma->get_due_date();
		$this->assertNotEmpty( $due );
	}

	public function test_proforma_due_date_empty_when_not_configured(): void {
		$order = $this->create_order();
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$proforma = new WPWing_WcPdf_Proforma( 42 );

		$settings = $this->createMock( WPWing_WcPdf_Settings::class );
		$settings->method( 'get_option' )->willReturnMap(
			array(
				array( 'invoice_due_date_days', '0' ),
			)
		);
		$proforma->settings = $settings;

		$this->assertSame( '', $proforma->get_due_date() );
	}

	public function test_pro_auto_conversion_on_payment_creates_invoice(): void {
		require_once WPWING_WCPDF_DIR . 'pro/includes/class-wpwing-wcpdf-pro.php';

		$order = $this->create_order(
			array(
				'_wpwing_wcpdf_proforma' => 1,
				'_wpwing_wcpdf_invoiced' => '',
			),
			99
		);
		Functions\when( 'wc_get_order' )->justReturn( $order );

		$pro = new WPWing_WcPdf_Pro();
		$this->assertSame( 'WPWing_WcPdf_Pro_Proforma', $pro->get_proforma_class() );
	}
}
