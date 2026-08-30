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

	/**
	 * Build an API instance with a small fixed schema (one field per type) registered as
	 * defaults, so sanitize_value()/sanitize_callback() have real type lookups to work with.
	 */
	private function make_api_with_defaults(): WPWing_WcPdf_Settings_API {
		Functions\when( 'apply_filters' )->alias( function ( $tag, $value ) {
			if ( 'wpwing_wcpdf_settings' === $tag ) {
				return array(
					array(
						'id'       => 'tab',
						'sections' => array(
							array(
								'fields' => array(
									array( 'id' => 'a_text', 'type' => 'text' ),
									array( 'id' => 'a_textarea', 'type' => 'textarea' ),
									array( 'id' => 'a_upload', 'type' => 'upload' ),
									array( 'id' => 'a_number', 'type' => 'number' ),
									array( 'id' => 'a_checkbox', 'type' => 'checkbox' ),
									array( 'id' => 'a_group', 'type' => 'checkboxgroup' ),
								),
							),
						),
					),
				);
			}
			return $value;
		} );

		$api = new WPWing_WcPdf_Settings_API();
		$api->set_defaults();

		return $api;
	}

	public function test_sanitize_value_text_strips_tags(): void {
		$api = $this->make_api_with_defaults();
		$this->assertSame( 'bold', $api->sanitize_value( 'a_text', '<b>bold</b>' ) );
	}

	public function test_sanitize_value_upload_escapes_as_url(): void {
		$api = $this->make_api_with_defaults();
		$this->assertSame( 'https://example.com/logo.png', $api->sanitize_value( 'a_upload', 'https://example.com/logo.png' ) );
	}

	public function test_sanitize_value_number_casts_to_absint(): void {
		$api = $this->make_api_with_defaults();
		$this->assertSame( 5, $api->sanitize_value( 'a_number', '5abc' ) );
	}

	public function test_sanitize_value_checkbox_coerces_to_one_or_zero(): void {
		$api = $this->make_api_with_defaults();
		$this->assertSame( 1, $api->sanitize_value( 'a_checkbox', '1' ) );
		$this->assertSame( 0, $api->sanitize_value( 'a_checkbox', '' ) );
	}

	public function test_sanitize_value_checkboxgroup_sanitizes_each_key(): void {
		$api = $this->make_api_with_defaults();
		$this->assertSame(
			array( 'processing', 'on-hold' ),
			$api->sanitize_value( 'a_group', array( 'Processing!', 'on-hold' ) )
		);
	}

	/**
	 * Regression test: sanitize_callback() walks every registered field, not just the ones
	 * present in the given array, and forces any missing checkbox/checkboxgroup field to its
	 * "unchecked" value. A caller that only has a subset of fields (e.g. the Setup Wizard
	 * saving one step at a time) must never pass a partial array through this method directly -
	 * it would silently zero out every other checkbox/checkboxgroup setting in the plugin.
	 * Use sanitize_value() per-field instead. See class-wpwing-wcpdf-wizard.php::save_step().
	 */
	public function test_sanitize_callback_forces_missing_checkbox_fields_to_unchecked(): void {
		$api = $this->make_api_with_defaults();

		$result = $api->sanitize_callback( array( 'a_text' => 'hello' ) );

		$this->assertSame( 0, $result['a_checkbox'] );
		$this->assertSame( array(), $result['a_group'] );
	}
}
