<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class GuestOrderAccessTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		unset( $_GET['key'] );
		Monkey\tearDown();
		parent::tearDown();
	}

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-plugin.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-shortcodes.php';
	}

	private function create_guest_order() {
		return new class extends WC_Order {
			public function get_customer_id() {
				return 0;
			}

			public function get_order_key() {
				return 'wc_order_guest_key';
			}
		};
	}

	public function test_guest_can_view_order_with_matching_key(): void {
		$order = $this->create_guest_order();
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wc_get_order' )->justReturn( $order );
		Functions\when( 'wp_unslash' )->alias( fn( $value ) => stripslashes( $value ) );
		$_GET['key'] = 'wc_order_guest_key';

		$plugin  = ( new ReflectionClass( WPWing_WcPdf_Plugin::class ) )->newInstanceWithoutConstructor();
		$method  = new ReflectionMethod( WPWing_WcPdf_Plugin::class, 'user_can_manage_order' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $plugin, 1, false ) );
		$this->assertFalse( $method->invoke( $plugin, 1, true ) );
	}

	public function test_guest_cannot_view_order_with_invalid_key(): void {
		$order = $this->create_guest_order();
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wc_get_order' )->justReturn( $order );
		Functions\when( 'wp_unslash' )->alias( fn( $value ) => stripslashes( $value ) );
		$_GET['key'] = 'wrong_key';

		$plugin = ( new ReflectionClass( WPWing_WcPdf_Plugin::class ) )->newInstanceWithoutConstructor();
		$method = new ReflectionMethod( WPWing_WcPdf_Plugin::class, 'user_can_manage_order' );
		$method->setAccessible( true );

		$this->assertFalse( $method->invoke( $plugin, 1, false ) );
	}

	public function test_guest_shortcode_access_requires_matching_order_key(): void {
		$order = $this->create_guest_order();
		Functions\when( 'current_user_can' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'wp_unslash' )->alias( fn( $value ) => stripslashes( $value ) );
		$_GET['key'] = 'wc_order_guest_key';

		$plugin   = ( new ReflectionClass( WPWing_WcPdf_Plugin::class ) )->newInstanceWithoutConstructor();
		$shortcode = new WPWing_WcPdf_Shortcodes( $plugin );
		$method   = new ReflectionMethod( WPWing_WcPdf_Shortcodes::class, 'current_user_can_view' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $shortcode, $order ) );

		$_GET['key'] = 'wrong_key';
		$this->assertFalse( $method->invoke( $shortcode, $order ) );
	}
}
