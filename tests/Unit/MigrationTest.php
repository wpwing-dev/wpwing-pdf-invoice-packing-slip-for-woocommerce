<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class MigrationTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-migration.php';
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function make_wpdb(): object {
		return new class {
			public $postmeta = 'wp_postmeta';
			public $prefix   = 'wp_';
			public function query( $_sql ) { return 1; }
			public function prepare( $_sql, ...$_args ) { return $_sql; }
			public function get_var( $_sql ) { return null; }
		};
	}

	public function test_maybe_run_skips_when_db_version_matches(): void {
		$this->expectNotToPerformAssertions();
		Functions\when( 'get_option' )->justReturn( WPWING_WCPDF_VERSION );
		Functions\expect( 'update_option' )->never();

		WPWing_WcPdf_Migration::maybe_run();
	}

	public function test_maybe_run_updates_db_version_when_stale(): void {
		global $wpdb;
		$wpdb = $this->make_wpdb();

		$updated_keys = array();
		Functions\when( 'get_option' )->alias( function ( $key ) {
			return 'wpwing_wcpdf_db_version' === $key ? 'old-version' : false;
		} );
		Functions\when( 'update_option' )->alias( function ( $key, $_value ) use ( &$updated_keys ) {
			$updated_keys[] = $key;
			return true;
		} );
		Functions\when( 'delete_option' )->justReturn( true );

		WPWing_WcPdf_Migration::maybe_run();

		$this->assertContains( 'wpwing_wcpdf_db_version', $updated_keys );
	}

	public function test_maybe_run_copies_old_settings_to_new_key(): void {
		global $wpdb;
		$wpdb = $this->make_wpdb();

		$stored = array();
		Functions\when( 'get_option' )->alias( function ( $key ) {
			if ( 'wpwing_wcpdf_db_version' === $key ) {
				return 'old-version';
			}
			if ( 'wpwing_wcpi_settings' === $key ) {
				return array( 'invoice_prefix' => 'INV-' );
			}
			return false;
		} );
		Functions\when( 'update_option' )->alias( function ( $key, $value ) use ( &$stored ) {
			$stored[ $key ] = $value;
			return true;
		} );
		Functions\when( 'delete_option' )->justReturn( true );

		WPWing_WcPdf_Migration::maybe_run();

		$this->assertArrayHasKey( 'wpwing_wcpdf_settings', $stored );
		$this->assertSame( array( 'invoice_prefix' => 'INV-' ), $stored['wpwing_wcpdf_settings'] );
	}
}
