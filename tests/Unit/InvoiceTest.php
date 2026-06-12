<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;

class InvoiceTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function test_due_date_returns_empty_when_offset_is_zero(): void {
        Monkey\Functions\when( 'get_option' )
            ->justReturn( ['invoice_due_date_days' => '0'] );

        require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-invoice.php';
        $invoice = new WPWing_WcPdf_Invoice( null );

        $this->assertSame( '', $invoice->get_due_date() );
    }
}
