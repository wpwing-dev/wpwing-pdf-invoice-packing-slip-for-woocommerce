<?php
use PHPUnit\Framework\TestCase;

class InvoiceNumberFormatTest extends TestCase {

	// Mirrors the token-substitution logic in WPWing_WcPdf_Invoice::get_formatted_invoice_number().
	private function apply_format( string $format, int $number, string $prefix, string $suffix, string $year, string $month, string $day ): string {
		$search  = array( '{number}', '{year}', '{month}', '{day}', '[number]', '[prefix]', '[suffix]' );
		$replace = array( $number, $year, $month, $day, $number, $prefix, $suffix );
		return str_replace( $search, $replace, $format );
	}

	public function test_number_token_replaced(): void {
		$this->assertSame( '42', $this->apply_format( '{number}', 42, '', '', '2026', '06', '13' ) );
	}

	public function test_year_month_day_tokens_replaced(): void {
		$result = $this->apply_format( 'INV-{number}/{year}/{month}/{day}', 1, '', '', '2026', '06', '13' );
		$this->assertSame( 'INV-1/2026/06/13', $result );
	}

	public function test_legacy_prefix_number_suffix_tokens(): void {
		$result = $this->apply_format( '[prefix]-[number]-[suffix]', 1, 'INV', 'A', '2026', '06', '13' );
		$this->assertSame( 'INV-1-A', $result );
	}

	public function test_format_with_no_tokens_is_unchanged(): void {
		$this->assertSame( 'STATIC', $this->apply_format( 'STATIC', 1, '', '', '2026', '06', '13' ) );
	}

	public function test_legacy_number_token_replaced_same_as_curly(): void {
		$legacy = $this->apply_format( '[number]', 7, '', '', '2026', '06', '13' );
		$curly  = $this->apply_format( '{number}', 7, '', '', '2026', '06', '13' );
		$this->assertSame( $curly, $legacy );
	}

	// Mirrors the format-migration logic in WPWing_WcPdf_Plugin::maybe_migrate_invoice_number_format().
	private function migrate_format( string $format, string $prefix, string $suffix ): string {
		$new_format = str_replace(
			array( '[prefix]', '[suffix]', '[number]' ),
			array( $prefix, $suffix, '{number}' ),
			$format
		);
		$new_format = preg_replace( '/^[\/\-_]+|[\/\-_]+$/', '', $new_format );
		$new_format = preg_replace( '/([\/\-_])\1+/', '$1', $new_format );
		return trim( $new_format );
	}

	public function test_migration_with_both_prefix_and_suffix(): void {
		$this->assertSame( 'INV/{number}/A', $this->migrate_format( '[prefix]/[number]/[suffix]', 'INV', 'A' ) );
	}

	public function test_migration_strips_leading_separator_when_prefix_empty(): void {
		$this->assertSame( '{number}', $this->migrate_format( '[prefix]-[number]', '', '' ) );
	}

	public function test_migration_strips_trailing_separator_when_suffix_empty(): void {
		$this->assertSame( '{number}', $this->migrate_format( '[number]-[suffix]', '', '' ) );
	}

	public function test_migration_keeps_prefix_separator_without_suffix(): void {
		$this->assertSame( 'INV-{number}', $this->migrate_format( '[prefix]-[number]-[suffix]', 'INV', '' ) );
	}

	public function test_migration_collapses_duplicate_separators_in_middle(): void {
		$this->assertSame( 'INV-{number}', $this->migrate_format( '[prefix]--[number]', 'INV', '' ) );
	}

	public function test_migration_bare_number_token(): void {
		$this->assertSame( '{number}', $this->migrate_format( '[number]', '', '' ) );
	}
}
