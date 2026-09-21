<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

// Mirrors the fallback chain in WPWing_WcPdf_Document::get_formatted_date().
class DocumentDateTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	private function resolve_date( string $format, ?string $completed, ?object $created ): string {
		if ( ! $format ) {
			$format = 'd/m/Y';
		}
		if ( $completed ) {
			$date = wp_date( $format, strtotime( $completed ) );
		} elseif ( $created ) {
			$date = wp_date( $format, $created->getTimestamp() );
		} else {
			$date = wp_date( $format );
		}
		return $date;
	}

	public function test_uses_completed_date_when_present(): void {
		Functions\when( 'wp_date' )->alias( fn( $fmt, $ts = null ) => date( $fmt, $ts ?? time() ) );

		$completed = '2026-01-15 10:00:00';
		$date      = $this->resolve_date( 'd/m/Y', $completed, null );

		$this->assertSame( '15/01/2026', $date );
	}

	public function test_falls_back_to_created_date_when_no_completed_date(): void {
		Functions\when( 'wp_date' )->alias( fn( $fmt, $ts = null ) => date( $fmt, $ts ?? time() ) );

		$created = new class {
			public function getTimestamp(): int {
				return mktime( 0, 0, 0, 3, 20, 2025 );
			}
		};
		$date = $this->resolve_date( 'd/m/Y', null, $created );

		$this->assertSame( '20/03/2025', $date );
	}

	public function test_format_defaults_to_day_month_year_when_empty(): void {
		Functions\when( 'wp_date' )->alias( fn( $fmt, $ts = null ) => date( $fmt, $ts ?? time() ) );

		$created = new class {
			public function getTimestamp(): int {
				return mktime( 0, 0, 0, 6, 1, 2026 );
			}
		};
		$date = $this->resolve_date( '', null, $created );

		$this->assertSame( '01/06/2026', $date );
	}
}
