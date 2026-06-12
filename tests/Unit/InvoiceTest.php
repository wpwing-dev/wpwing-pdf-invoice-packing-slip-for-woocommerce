<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class InvoiceTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_due_date_is_empty_when_days_is_zero(): void {
		Functions\when( 'get_option' )
			->justReturn( ['invoice_due_date_days' => '0'] );

		$settings = get_option( 'wpwing_wcpdf_invoice' );
		$days     = (int) ( $settings['invoice_due_date_days'] ?? 0 );

		$this->assertSame( '', $days > 0 ? 'some-date' : '' );
	}

	public function test_due_date_is_populated_when_days_is_positive(): void {
		Functions\when( 'get_option' )
			->justReturn( ['invoice_due_date_days' => '30'] );
		Functions\when( 'wp_date' )
			->justReturn( '12/06/2026' );

		$settings = get_option( 'wpwing_wcpdf_invoice' );
		$days     = (int) ( $settings['invoice_due_date_days'] ?? 0 );
		$due      = $days > 0 ? wp_date( 'd/m/Y', time() + $days * DAY_IN_SECONDS ) : '';

		$this->assertSame( '12/06/2026', $due );
	}
}
