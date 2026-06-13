<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class SettingsApiTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings-api.php';
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_get_option_returns_stored_value(): void {
		Functions\when( 'get_option' )->alias( function ( $key ) {
			return 'wpwing_wcpdf_settings' === $key ? array( 'invoice_prefix' => 'INV-' ) : false;
		} );
		Functions\when( 'current_theme_supports' )->justReturn( false );

		$api = new WPWing_WcPdf_Settings_API();

		$this->assertSame( 'INV-', $api->get_option( 'invoice_prefix' ) );
	}

	public function test_get_option_returns_null_for_unknown_key_when_options_exist(): void {
		Functions\when( 'get_option' )->alias( function ( $key ) {
			return 'wpwing_wcpdf_settings' === $key ? array( 'other_key' => 'val' ) : false;
		} );
		Functions\when( 'current_theme_supports' )->justReturn( false );

		$api = new WPWing_WcPdf_Settings_API();

		$this->assertNull( $api->get_option( 'nonexistent_key' ) );
	}

	public function test_get_option_returns_null_for_unknown_key_on_new_install(): void {
		// options = false simulates a fresh install (no data saved yet).
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'current_theme_supports' )->justReturn( false );

		$api = new WPWing_WcPdf_Settings_API();

		$this->assertNull( $api->get_option( 'nonexistent_key' ) );
	}

	public function test_set_option_merges_value_into_existing_options(): void {
		Functions\when( 'get_option' )->alias( function ( $key ) {
			return 'wpwing_wcpdf_settings' === $key ? array( 'invoice_prefix' => 'INV-' ) : false;
		} );
		Functions\when( 'current_theme_supports' )->justReturn( false );

		$saved = null;
		Functions\when( 'update_option' )->alias( function ( $key, $value ) use ( &$saved ) {
			if ( 'wpwing_wcpdf_settings' === $key ) {
				$saved = $value;
			}
			return true;
		} );

		$api = new WPWing_WcPdf_Settings_API();
		$api->set_option( 'invoice_number', 42 );

		$this->assertSame( array( 'invoice_prefix' => 'INV-', 'invoice_number' => 42 ), $saved );
	}

	public function test_set_option_creates_options_array_when_none_stored(): void {
		Functions\when( 'get_option' )->justReturn( false );
		Functions\when( 'current_theme_supports' )->justReturn( false );

		$saved = null;
		Functions\when( 'update_option' )->alias( function ( $key, $value ) use ( &$saved ) {
			if ( 'wpwing_wcpdf_settings' === $key ) {
				$saved = $value;
			}
			return true;
		} );

		$api = new WPWing_WcPdf_Settings_API();
		$api->set_option( 'invoice_number', 1 );

		$this->assertSame( array( 'invoice_number' => 1 ), $saved );
	}
}
