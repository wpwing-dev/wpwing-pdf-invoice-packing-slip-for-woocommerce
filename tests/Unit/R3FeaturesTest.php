<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

class R3FeaturesTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings-api.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-plugin.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-document.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-shortcodes.php';
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'wp_date' )->alias( 'gmdate' );
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

	private function create_document( $order, string $filename_format, string $type = 'invoice' ): WPWing_WcPdf_Document {
		Functions\when( 'wc_get_order' )->justReturn( false );

		$document = new class( 0 ) extends WPWing_WcPdf_Document {
			public $date;
			public function render_template_customer_data() {}
			public function render_template_order_data() {}
			public function get_from_label() { return 'From'; }
			public function save() {}
			public function reset() {}
		};

		$settings = $this->createMock( WPWing_WcPdf_Settings::class );
		$settings->method( 'get_option' )->willReturnMap( array( array( 'document_filename_format', $filename_format ) ) );

		$property = new ReflectionProperty( WPWing_WcPdf_Document::class, 'settings' );
		$property->setAccessible( true );
		$property->setValue( $document, $settings );

		$document->order         = $order;
		$document->document_type = $type;

		return $document;
	}

	private function create_order( array $values = array() ): WC_Order {
		$values = array_merge(
			array(
				'meta'         => '',
				'order_number' => '1234',
				'order_key'    => 'wc_order_abc123',
				'customer_id'  => 0,
			),
			$values
		);

		$order = $this->createMock( WC_Order::class );
		$order->method( 'get_meta' )->willReturn( $values['meta'] );
		$order->method( 'get_order_number' )->willReturn( $values['order_number'] );
		$order->method( 'get_order_key' )->willReturn( $values['order_key'] );
		$order->method( 'get_customer_id' )->willReturn( $values['customer_id'] );
		$order->method( 'get_id' )->willReturn( 55 );

		return $order;
	}

	public function test_filename_keeps_default_when_format_empty(): void {
		$document = $this->create_document( $this->create_order(), '' );

		$this->assertSame( '/invoice_7', $document->get_formatted_filename( '/invoice_7', 2026 ) );
	}

	public function test_filename_replaces_all_tokens(): void {
		$document       = $this->create_document( $this->create_order(), '{{doc_type}}-{{order_number}}-{{invoice_date}}' );
		$document->date = gmmktime( 12, 0, 0, 10, 12, 2026 );

		$this->assertSame( '/invoice-1234-2026-10-12', $document->get_formatted_filename( '/invoice_7', 2026 ) );
	}

	public function test_filename_uses_order_invoice_date_for_other_documents(): void {
		$order          = $this->create_order( array( 'meta' => (string) gmmktime( 12, 0, 0, 1, 5, 2026 ) ) );
		$document       = $this->create_document( $order, '{{doc_type}}_{{invoice_date}}', 'packing' );
		$document->date = gmmktime( 12, 0, 0, 10, 12, 2026 );

		$this->assertSame( '/packing_2026-01-05', $document->get_formatted_filename( '/packing_55', 2026 ) );
	}

	public function test_filename_falls_back_when_pattern_sanitizes_to_empty(): void {
		$document = $this->create_document( $this->create_order(), '###' );

		$this->assertSame( '/invoice_7', $document->get_formatted_filename( '/invoice_7', 2026 ) );
	}

	public function test_filename_is_made_unique_in_year_directory(): void {
		$calls = array();
		Functions\when( 'wp_unique_filename' )->alias(
			function ( $dir, $name ) use ( &$calls ) {
				$calls[] = array( $dir, $name );
				return 'invoice-1234-1.pdf';
			}
		);

		$document = $this->create_document( $this->create_order(), '{{doc_type}}-{{order_number}}' );

		$this->assertSame( '/invoice-1234-1', $document->get_formatted_filename( '/invoice_7', 2026 ) );
		$this->assertSame( array( array( WPWING_WCPDF_DOCUMENT_SAVE_DIR . '2026', 'invoice-1234.pdf' ) ), $calls );
	}

	public function test_flush_template_removes_only_own_callbacks(): void {
		$document = $this->create_document( $this->create_order(), '' );
		$other    = new stdClass();

		$GLOBALS['wp_filter'] = array(
			'wpwing_wcpdf_template_head'             => (object) array(
				'callbacks' => array(
					10 => array(
						'a' => array( 'function' => array( $document, 'add_template_head' ) ),
						'b' => array( 'function' => array( $other, 'third_party' ) ),
						'c' => array( 'function' => 'third_party_function' ),
					),
				),
			),
			'wpwing_wcpdf_invoice_template_footer' => (object) array(
				'callbacks' => array(
					20 => array( 'd' => array( 'function' => array( $document, 'render_template_footer' ) ) ),
				),
			),
		);

		Actions\expectRemoved( 'wpwing_wcpdf_template_head' )->once()->with( array( $document, 'add_template_head' ), 10 );
		Actions\expectRemoved( 'wpwing_wcpdf_template_head' )->never()->with( array( $other, 'third_party' ), 10 );
		Actions\expectRemoved( 'wpwing_wcpdf_template_head' )->never()->with( 'third_party_function', 10 );
		Actions\expectRemoved( 'wpwing_wcpdf_invoice_template_footer' )->once()->with( array( $document, 'render_template_footer' ), 20 );

		$document->flush_template();
	}

	public function test_guest_key_matches_guest_order(): void {
		$order = $this->create_order();

		$this->assertTrue( WPWing_WcPdf_Plugin::guest_order_key_matches( $order, 'wc_order_abc123' ) );
		$this->assertFalse( WPWing_WcPdf_Plugin::guest_order_key_matches( $order, 'wc_order_wrong' ) );
		$this->assertFalse( WPWing_WcPdf_Plugin::guest_order_key_matches( $order, '' ) );
	}

	public function test_guest_key_never_grants_access_to_customer_order(): void {
		$order = $this->create_order( array( 'customer_id' => 9 ) );

		$this->assertFalse( WPWing_WcPdf_Plugin::guest_order_key_matches( $order, 'wc_order_abc123' ) );
	}

	public function test_guest_key_rejects_order_without_key(): void {
		$order = $this->create_order( array( 'order_key' => '' ) );

		$this->assertFalse( WPWing_WcPdf_Plugin::guest_order_key_matches( $order, '' ) );
	}

	private function render_shortcode_for_guest( WC_Order $order ): string {
		Functions\when( 'shortcode_atts' )->alias(
			function ( $defaults, $atts ) {
				return array_merge( $defaults, (array) $atts );
			}
		);
		Functions\when( '__' )->returnArg();
		Functions\when( 'absint' )->alias( 'intval' );
		Functions\when( 'wc_get_order' )->justReturn( $order );
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'add_query_arg' )->alias( 'http_build_query' );
		Functions\when( 'wp_nonce_url' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();

		$invoice         = new stdClass();
		$invoice->exists = true;

		$plugin = $this->createMock( WPWing_WcPdf_Plugin::class );
		$plugin->method( 'get_document_by_type' )->willReturn( $invoice );

		return ( new WPWing_WcPdf_Shortcodes( $plugin ) )->render_invoice_link( array( 'order_id' => 55 ) );
	}

	public function test_shortcode_renders_link_with_key_for_guest_with_valid_key(): void {
		$_GET['key'] = 'wc_order_abc123';
		$html        = $this->render_shortcode_for_guest( $this->create_order() );
		unset( $_GET['key'] );

		$this->assertStringContainsString( 'wpwing-view-invoice=55', $html );
		$this->assertStringContainsString( 'key=wc_order_abc123', $html );
	}

	public function test_shortcode_renders_nothing_for_guest_without_key(): void {
		unset( $_GET['key'] );

		$this->assertSame( '', $this->render_shortcode_for_guest( $this->create_order() ) );
	}
}
