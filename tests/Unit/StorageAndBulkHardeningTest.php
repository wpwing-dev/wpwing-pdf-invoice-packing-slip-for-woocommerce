<?php
/**
 * Tests for storage maintenance and peak volume bulk hardening.
 *
 * @package WPWing_PDF_Invoice_Packing_Slip
 */

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * StorageAndBulkHardeningTest Class
 */
class StorageAndBulkHardeningTest extends TestCase {

	/**
	 * Temporary test directory for storage tests.
	 *
	 * @var string
	 */
	private $test_save_dir;

	/**
	 * Set up test classes before test run.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings-api.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-settings.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-storage.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-document.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-invoice.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-plugin.php';
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-orders-list.php';
	}

	/**
	 * In-memory options storage for Brain Monkey.
	 *
	 * @var array
	 */
	private static $options_store = array();

	/**
	 * Set up each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		self::$options_store = array();

		$this->test_save_dir = WPWING_WCPDF_DOCUMENT_SAVE_DIR;
		if ( ! is_dir( $this->test_save_dir ) ) {
			wp_mkdir_p( $this->test_save_dir );
		}

		Functions\when( 'did_action' )->justReturn( 1 );
		Functions\when( 'current_theme_supports' )->justReturn( false );
		Functions\when( 'wp_date' )->alias( 'gmdate' );
		Functions\when( 'apply_filters' )->returnArg( 2 );
		Functions\when( 'wp_create_nonce' )->justReturn( 'dummy_nonce' );
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
		Functions\when( 'remove_query_arg' )->alias(
			function ( $keys, $url ) {
				return $url;
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			function ( $params, $url ) {
				return $url . '?' . http_build_query( $params );
			}
		);
	}

	/**
	 * Tear down each test.
	 */
	protected function tearDown(): void {
		// Clean up files created in test_save_dir.
		if ( is_dir( $this->test_save_dir ) ) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $this->test_save_dir, FilesystemIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			foreach ( $iterator as $item ) {
				if ( $item->isDir() ) {
					@rmdir( $item->getPathname() );
				} else {
					@unlink( $item->getPathname() );
				}
			}
		}

		unset( $GLOBALS['wp_filter'] );
		$this->addToAssertionCount( Mockery::getContainer()->mockery_getExpectationCount() );
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test temp file cleanup only purges files older than 24h.
	 */
	public function test_cleanup_temp_files_deletes_stale_files_only() {
		$old_temp = $this->test_save_dir . 'wpwing-wcpdf-bulk-old.tmp';
		$new_temp = $this->test_save_dir . 'wpwing-wcpdf-bulk-new.tmp';

		file_put_contents( $old_temp, 'old content' );
		file_put_contents( $new_temp, 'new content' );

		touch( $old_temp, time() - 90000 ); // ~25h old.
		touch( $new_temp, time() - 1800 );  // 30min old.

		$purged = WPWing_WcPdf_Storage::cleanup_temp_files();

		$this->assertGreaterThanOrEqual( 1, $purged );
		$this->assertFileDoesNotExist( $old_temp );
		$this->assertFileExists( $new_temp );

		@unlink( $new_temp );
	}

	/**
	 * Test thumbnail cache cleanup purges files older than 30 days.
	 */
	public function test_cleanup_thumbnails_deletes_old_thumbs_only() {
		$thumbs_dir = $this->test_save_dir . 'thumbs/';
		if ( ! is_dir( $thumbs_dir ) ) {
			wp_mkdir_p( $thumbs_dir );
		}

		$old_thumb = $thumbs_dir . 'thumb_10_32x32.png';
		$new_thumb = $thumbs_dir . 'thumb_11_32x32.png';

		file_put_contents( $old_thumb, 'fake png 1' );
		file_put_contents( $new_thumb, 'fake png 2' );

		touch( $old_thumb, time() - ( 35 * DAY_IN_SECONDS ) );
		touch( $new_thumb, time() - ( 2 * DAY_IN_SECONDS ) );

		$purged = WPWing_WcPdf_Storage::cleanup_thumbnails();

		$this->assertGreaterThanOrEqual( 1, $purged );
		$this->assertFileDoesNotExist( $old_thumb );
		$this->assertFileExists( $new_thumb );

		@unlink( $new_thumb );
	}

	/**
	 * Test document retention cleanup purges expired documents but preserves index/htaccess shields.
	 */
	public function test_cleanup_expired_documents_respects_retention_and_preserves_protected_files() {
		$sub_dir = $this->test_save_dir . '2026/';
		if ( ! is_dir( $sub_dir ) ) {
			wp_mkdir_p( $sub_dir );
		}

		$old_pdf    = $sub_dir . 'invoice-old.pdf';
		$old_xml    = $sub_dir . 'invoice-old.xml';
		$new_pdf    = $sub_dir . 'invoice-recent.pdf';
		$htaccess   = $this->test_save_dir . '.htaccess';
		$index_html = $this->test_save_dir . 'index.html';

		file_put_contents( $old_pdf, '%PDF-1.4 dummy' );
		file_put_contents( $old_xml, '<Invoice>dummy</Invoice>' );
		file_put_contents( $new_pdf, '%PDF-1.4 recent' );
		file_put_contents( $htaccess, 'deny from all' );
		file_put_contents( $index_html, '' );

		touch( $old_pdf, time() - ( 40 * DAY_IN_SECONDS ) );
		touch( $old_xml, time() - ( 40 * DAY_IN_SECONDS ) );
		touch( $new_pdf, time() - ( 5 * DAY_IN_SECONDS ) );
		touch( $htaccess, time() - ( 100 * DAY_IN_SECONDS ) );
		touch( $index_html, time() - ( 100 * DAY_IN_SECONDS ) );

		// Run retention with 30 days threshold.
		$purged = WPWing_WcPdf_Storage::cleanup_expired_documents( 30 );

		$this->assertSame( 2, $purged );
		$this->assertFileDoesNotExist( $old_pdf );
		$this->assertFileDoesNotExist( $old_xml );
		$this->assertFileExists( $new_pdf );
		$this->assertFileExists( $htaccess );
		$this->assertFileExists( $index_html );
	}

	/**
	 * Test document retention does nothing when set to 0 (Never).
	 */
	public function test_cleanup_expired_documents_disabled_when_retention_zero() {
		$old_pdf = $this->test_save_dir . 'invoice-permanent.pdf';
		file_put_contents( $old_pdf, '%PDF-1.4 permanent' );
		touch( $old_pdf, time() - ( 400 * DAY_IN_SECONDS ) );

		$purged = WPWing_WcPdf_Storage::cleanup_expired_documents( 0 );

		$this->assertSame( 0, $purged );
		$this->assertFileExists( $old_pdf );
	}

	/**
	 * Test get_storage_stats calculates counts, sizes, and retention threshold.
	 */
	public function test_get_storage_stats_calculates_counts_and_sizes() {
		$thumbs_dir = $this->test_save_dir . 'thumbs/';
		if ( ! is_dir( $thumbs_dir ) ) {
			wp_mkdir_p( $thumbs_dir );
		}

		file_put_contents( $this->test_save_dir . 'doc1.pdf', '12345' );
		file_put_contents( $this->test_save_dir . 'doc2.xml', '1234567' );
		file_put_contents( $thumbs_dir . 'thumb_1_32x32.png', '123' );

		$stats = WPWing_WcPdf_Storage::get_storage_stats();

		$this->assertIsArray( $stats );
		$this->assertSame( 2, $stats['document_count'] );
		$this->assertSame( 12, $stats['document_bytes'] );
		$this->assertSame( 1, $stats['thumb_count'] );
		$this->assertSame( 3, $stats['thumb_bytes'] );
		$this->assertSame( 15, $stats['total_bytes'] );
		$this->assertNotEmpty( $stats['formatted_total'] );
	}

	/**
	 * Test bulk merged PDF action caps batch to 30 orders.
	 */
	public function test_bulk_merged_pdf_batch_capped_to_thirty() {
		$plugin = $this->createMock( WPWing_WcPdf_Plugin::class );
		$plugin->method( 'get_document_by_type' )->willReturn( null );

		$orders_list = new WPWing_WcPdf_Orders_List( $plugin );

		// Simulate 50 selected orders.
		$order_ids = range( 1, 50 );

		$redirect = $orders_list->handle_bulk_actions(
			'admin.php?page=wc-orders',
			'wpwing_download_invoices_pdf',
			$order_ids
		);

		// With 50 orders and a limit of 30, skipped must be 20.
		$this->assertStringContainsString( 'wpwing_bulk_skipped=20', $redirect );
		$this->assertStringContainsString( 'wpwing_bulk_type=invoice', $redirect );
	}

	/**
	 * Test bulk ZIP action retains higher 50-order batch limit.
	 */
	public function test_bulk_zip_batch_retains_fifty_order_limit() {
		$plugin = $this->createMock( WPWing_WcPdf_Plugin::class );
		$plugin->method( 'get_document_by_type' )->willReturn( null );

		$orders_list = new WPWing_WcPdf_Orders_List( $plugin );

		// Simulate 50 selected orders.
		$order_ids = range( 1, 50 );

		$redirect = $orders_list->handle_bulk_actions(
			'admin.php?page=wc-orders',
			'wpwing_download_invoices_zip',
			$order_ids
		);

		// With 50 orders and a limit of 50 for ZIP, skipped must be 0.
		$this->assertStringContainsString( 'wpwing_bulk_skipped=0', $redirect );
	}

	/**
	 * Test filter wpwing_wcpdf_merged_pdf_max_orders customizes the cap.
	 */
	public function test_bulk_merged_pdf_cap_customizable_via_filter() {
		$plugin = $this->createMock( WPWing_WcPdf_Plugin::class );
		$plugin->method( 'get_document_by_type' )->willReturn( null );

		Functions\when( 'apply_filters' )->alias(
			function ( $hook, $value ) {
				if ( 'wpwing_wcpdf_merged_pdf_max_orders' === $hook ) {
					return 15;
				}
				return $value;
			}
		);

		$orders_list = new WPWing_WcPdf_Orders_List( $plugin );

		$order_ids = range( 1, 50 );

		$redirect = $orders_list->handle_bulk_actions(
			'admin.php?page=wc-orders',
			'wpwing_download_invoices_pdf',
			$order_ids
		);

		// With 50 orders and a limit of 15, skipped must be 35.
		$this->assertStringContainsString( 'wpwing_bulk_skipped=35', $redirect );
	}
}
