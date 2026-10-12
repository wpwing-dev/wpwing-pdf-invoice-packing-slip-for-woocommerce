<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class DocumentTableFeaturesTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings-api.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-plugin.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-document.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-invoice.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-orders-list.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-wc-hooks.php';
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function create_document_instance(): WPWing_WcPdf_Document {
		Functions\when( 'wc_get_order' )->justReturn( false );

		return new class( 0 ) extends WPWing_WcPdf_Document {
			public function render_template_customer_data() {}
			public function render_template_order_data() {}
			public function get_from_label() { return 'From'; }
			public function save() {}
			public function reset() {}
		};
	}

	public function test_get_product_thumbnail_data_uri_returns_empty_when_not_wc_product(): void {
		$document = $this->create_document_instance();

		$this->assertSame( '', $document->get_product_thumbnail_data_uri( null ) );
		$this->assertSame( '', $document->get_product_thumbnail_data_uri( new stdClass() ) );
	}

	public function test_get_product_thumbnail_data_uri_returns_empty_when_no_image_id(): void {
		$document = $this->create_document_instance();
		$product  = $this->createMock( WC_Product::class );
		$product->method( 'get_image_id' )->willReturn( 0 );
		$product->method( 'get_parent_id' )->willReturn( 0 );

		$this->assertSame( '', $document->get_product_thumbnail_data_uri( $product ) );
	}

	public function test_get_product_thumbnail_data_uri_returns_cached_base64(): void {
		$document   = $this->create_document_instance();
		$image_id   = 99101;
		$dimension  = 32;
		$cache_dir  = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . 'thumbs/';
		$cache_file = $cache_dir . sprintf( 'thumb_%d_%dx%d.png', $image_id, $dimension, $dimension );

		if ( ! is_dir( $cache_dir ) ) {
			mkdir( $cache_dir, 0777, true );
		}

		$raw_png_bytes = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDRtest";
		file_put_contents( $cache_file, $raw_png_bytes );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_image_id' )->willReturn( $image_id );

		$uri = $document->get_product_thumbnail_data_uri( $product, $dimension );

		if ( file_exists( $cache_file ) ) {
			unlink( $cache_file );
		}

		$this->assertSame( 'data:image/png;base64,' . base64_encode( $raw_png_bytes ), $uri );
	}

	public function test_get_product_thumbnail_data_uri_falls_back_to_parent_image(): void {
		$document       = $this->create_document_instance();
		$parent_img_id  = 99102;
		$dimension      = 48;
		$cache_dir      = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . 'thumbs/';
		$cache_file     = $cache_dir . sprintf( 'thumb_%d_%dx%d.png', $parent_img_id, $dimension, $dimension );

		if ( ! is_dir( $cache_dir ) ) {
			mkdir( $cache_dir, 0777, true );
		}

		$raw_png_bytes = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDRparent";
		file_put_contents( $cache_file, $raw_png_bytes );

		$parent = $this->createMock( WC_Product::class );
		$parent->method( 'get_image_id' )->willReturn( $parent_img_id );

		$child = $this->createMock( WC_Product::class );
		$child->method( 'get_image_id' )->willReturn( 0 );
		$child->method( 'get_parent_id' )->willReturn( 501 );

		Functions\when( 'wc_get_product' )->justReturn( $parent );

		$uri = $document->get_product_thumbnail_data_uri( $child, $dimension );

		if ( file_exists( $cache_file ) ) {
			unlink( $cache_file );
		}

		$this->assertSame( 'data:image/png;base64,' . base64_encode( $raw_png_bytes ), $uri );
	}

	public function test_get_product_thumbnail_data_uri_clamps_dimension_to_allowed_sizes(): void {
		$document   = $this->create_document_instance();
		$image_id   = 99103;
		$cache_dir  = trailingslashit( WPWING_WCPDF_DOCUMENT_SAVE_DIR ) . 'thumbs/';
		$cache_file = $cache_dir . sprintf( 'thumb_%d_32x32.png', $image_id );

		if ( ! is_dir( $cache_dir ) ) {
			mkdir( $cache_dir, 0777, true );
		}

		$raw_png_bytes = "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDRclamped";
		file_put_contents( $cache_file, $raw_png_bytes );

		$product = $this->createMock( WC_Product::class );
		$product->method( 'get_image_id' )->willReturn( $image_id );

		// Pass unallowed dimension (120) which should clamp to default 32.
		$uri = $document->get_product_thumbnail_data_uri( $product, 120 );

		if ( file_exists( $cache_file ) ) {
			unlink( $cache_file );
		}

		$this->assertSame( 'data:image/png;base64,' . base64_encode( $raw_png_bytes ), $uri );
	}

	public function test_custom_filename_uses_tokens_and_sanitizes_path_separators(): void {
		$order = new class extends WC_Order {
			public function get_order_number() {
				return '1001';
			}
		};
		Functions\when( 'wc_get_order' )->justReturn( $order );
		Functions\when( 'wp_date' )->alias( fn( $format, $timestamp ) => gmdate( $format, $timestamp ) );
		Functions\when( 'sanitize_file_name' )->alias( fn( $filename ) => preg_replace( '/[^A-Za-z0-9_.-]/', '-', $filename ) );

		$settings = $this->createMock( WPWing_WcPdf_Settings::class );
		$settings->method( 'get_option' )->with( 'document_filename_format' )->willReturn( '../{{doc_type}}/{{order_number}}_{{invoice_date}}' );

		$document = new class( 1, $settings ) extends WPWing_WcPdf_Document {
			public $document_type = 'invoice';
			public $date          = 1790012345;

			public function __construct( $order_id, $settings ) {
				parent::__construct( $order_id );
				$this->settings = $settings;
			}

			protected function get_from_label() { return ''; }
			public function save() {}
			public function reset() {}
		};

		$this->assertSame( '/-invoice-1001_2026-09-21', $document->get_formatted_filename( '/invoice_1' ) );
	}

	public function test_empty_filename_pattern_keeps_legacy_filename(): void {
		$document = $this->create_document_instance();
		$this->assertSame( '/invoice_1', $document->get_formatted_filename( '/invoice_1' ) );
	}

	public function test_flush_template_removes_only_internal_callbacks(): void {
		$document = $this->create_document_instance();

		Functions\expect( 'remove_action' )->times( 9 );
		Functions\expect( 'remove_all_filters' )->never();

		$document->flush_template();
	}

	public function test_orders_list_displays_formatted_invoice_number(): void {
		$plugin  = $this->createMock( WPWing_WcPdf_Plugin::class );
		$invoice = $this->getMockBuilder( WPWing_WcPdf_Invoice::class )
			->disableOriginalConstructor()
			->onlyMethods( array( 'get_formatted_invoice_number' ) )
			->getMock();
		$invoice->exists = true;
		$invoice->method( 'get_formatted_invoice_number' )->willReturn( 'INV-42' );
		$plugin->method( 'get_document_by_type' )->with( 12, 'invoice' )->willReturn( $invoice );

		$orders_list = new WPWing_WcPdf_Orders_List( $plugin );
		$columns     = $orders_list->add_order_list_column( array( 'order_status' => 'Status' ) );

		ob_start();
		$orders_list->render_order_list_column( 'wpwing_invoice', 12 );
		$output = ob_get_clean();

		$this->assertSame( 'Invoice Number', $columns['wpwing_invoice'] );
		$this->assertStringContainsString( 'INV-42', $output );
	}

	public function test_attach_document_to_email_suppresses_for_free_orders_when_enabled(): void {
		$plugin   = $this->createMock( WPWing_WcPdf_Plugin::class );
		$settings = $this->createMock( WPWing_WcPdf_Settings::class );

		$settings->method( 'get_option' )->willReturnMap( array(
			array( 'invoice_attach_to_emails', array( 'customer_invoice' ) ),
			array( 'invoice_disable_free_orders', true ),
		) );

		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_total' )->willReturn( '0.00' );
		$order->method( 'get_id' )->willReturn( 12 );

		// Document generation must not be called when suppressed.
		$plugin->expects( $this->never() )->method( 'get_document_by_type' );

		$hooks       = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );
		$attachments = array( '/tmp/other.pdf' );
		$result      = $hooks->attach_document_to_email( $attachments, 'customer_invoice', $order );

		$this->assertSame( $attachments, $result );
	}

	public function test_attach_document_to_email_proceeds_for_free_orders_when_disabled(): void {
		$plugin   = $this->createMock( WPWing_WcPdf_Plugin::class );
		$settings = $this->createMock( WPWing_WcPdf_Settings::class );

		$settings->method( 'get_option' )->willReturnMap( array(
			array( 'invoice_attach_to_emails', array( 'customer_invoice' ) ),
			array( 'invoice_disable_free_orders', false ),
		) );

		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_total' )->willReturn( '0.00' );
		$order->method( 'get_id' )->willReturn( 12 );

		// Document generation must be attempted when suppression is disabled.
		$plugin->expects( $this->once() )
			->method( 'get_document_by_type' )
			->with( 12, 'invoice' )
			->willReturn( null );

		$hooks       = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );
		$attachments = array();
		$result      = $hooks->attach_document_to_email( $attachments, 'customer_invoice', $order );

		$this->assertSame( array(), $result );
	}

	public function test_attach_document_to_email_proceeds_for_paid_orders_when_free_suppression_enabled(): void {
		$plugin   = $this->createMock( WPWing_WcPdf_Plugin::class );
		$settings = $this->createMock( WPWing_WcPdf_Settings::class );

		$settings->method( 'get_option' )->willReturnMap( array(
			array( 'invoice_attach_to_emails', array( 'customer_invoice' ) ),
			array( 'invoice_disable_free_orders', true ),
		) );

		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_total' )->willReturn( '29.99' );
		$order->method( 'get_id' )->willReturn( 15 );

		$plugin->expects( $this->once() )
			->method( 'get_document_by_type' )
			->with( 15, 'invoice' )
			->willReturn( null );

		$hooks       = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );
		$attachments = array();
		$result      = $hooks->attach_document_to_email( $attachments, 'customer_invoice', $order );

		$this->assertSame( array(), $result );
	}

	public function test_maybe_auto_generate_suppresses_for_free_orders(): void {
		$plugin   = $this->createMock( WPWing_WcPdf_Plugin::class );
		$settings = $this->createMock( WPWing_WcPdf_Settings::class );

		$settings->method( 'get_option' )->willReturnMap( array(
			array( 'invoice_auto_statuses', array( 'completed' ) ),
			array( 'invoice_disable_free_orders', true ),
			array( 'packing_auto_statuses', array() ),
		) );

		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_total' )->willReturn( '0.00' );

		$plugin->expects( $this->never() )->method( 'get_document_by_type' );

		$hooks = new WPWing_WcPdf_Wc_Hooks( $plugin, $settings );
		$hooks->maybe_auto_generate( 25, 'pending', 'completed', $order );
	}
}
