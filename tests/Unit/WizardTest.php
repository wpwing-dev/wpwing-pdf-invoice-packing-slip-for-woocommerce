<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class WizardTest extends TestCase {

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();
		require_once WPWING_WCPDF_DIR . 'includes/class-wpwing-wcpdf-wizard.php';
	}

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_sanitize_step_accepts_known_step(): void {
		$this->assertSame( 'paper_size', WPWing_WcPdf_Wizard::sanitize_step( 'paper_size' ) );
	}

	public function test_sanitize_step_falls_back_to_first_step_for_unknown_value(): void {
		$this->assertSame( 'shop_info', WPWing_WcPdf_Wizard::sanitize_step( 'not-a-real-step' ) );
	}

	public function test_sanitize_step_falls_back_to_first_step_for_empty_value(): void {
		$this->assertSame( 'shop_info', WPWing_WcPdf_Wizard::sanitize_step( '' ) );
	}

	public function test_get_next_step_returns_the_following_step(): void {
		$this->assertSame( 'logo', WPWing_WcPdf_Wizard::get_next_step( 'shop_info' ) );
		$this->assertSame( 'paper_size', WPWing_WcPdf_Wizard::get_next_step( 'logo' ) );
		$this->assertSame( 'auto_statuses', WPWing_WcPdf_Wizard::get_next_step( 'paper_size' ) );
		$this->assertSame( 'email_attachment', WPWing_WcPdf_Wizard::get_next_step( 'auto_statuses' ) );
		$this->assertSame( 'finish', WPWing_WcPdf_Wizard::get_next_step( 'email_attachment' ) );
	}

	public function test_get_next_step_stays_on_finish_once_reached(): void {
		$this->assertSame( 'finish', WPWing_WcPdf_Wizard::get_next_step( 'finish' ) );
	}

	public function test_get_next_step_falls_back_to_finish_for_unknown_step(): void {
		$this->assertSame( 'finish', WPWing_WcPdf_Wizard::get_next_step( 'not-a-real-step' ) );
	}

	public function test_should_redirect_to_wizard_when_conditions_are_clean(): void {
		$this->assertTrue( WPWing_WcPdf_Wizard::should_redirect_to_wizard( false, false, false, true ) );
	}

	public function test_should_redirect_to_wizard_is_false_for_ajax_requests(): void {
		$this->assertFalse( WPWing_WcPdf_Wizard::should_redirect_to_wizard( true, false, false, true ) );
	}

	public function test_should_redirect_to_wizard_is_false_for_network_admin(): void {
		$this->assertFalse( WPWing_WcPdf_Wizard::should_redirect_to_wizard( false, true, false, true ) );
	}

	public function test_should_redirect_to_wizard_is_false_for_bulk_activation(): void {
		$this->assertFalse( WPWing_WcPdf_Wizard::should_redirect_to_wizard( false, false, true, true ) );
	}

	public function test_should_redirect_to_wizard_is_false_without_capability(): void {
		$this->assertFalse( WPWing_WcPdf_Wizard::should_redirect_to_wizard( false, false, false, false ) );
	}

	public function test_checkbox_gates_forces_name_checkbox_when_name_present(): void {
		$gates = WPWing_WcPdf_Wizard::get_checkbox_gates_to_apply(
			'shop_info',
			array( 'company_name_text' => 'Acme Inc' )
		);

		$this->assertContains( 'company_name_checkbox', $gates );
		$this->assertNotContains( 'company_details_checkbox', $gates );
	}

	public function test_checkbox_gates_forces_details_checkbox_when_any_address_field_present(): void {
		$gates = WPWing_WcPdf_Wizard::get_checkbox_gates_to_apply(
			'shop_info',
			array(
				'company_name_text' => '',
				'company_address'   => '',
				'company_city'      => 'Springfield',
				'company_zip'       => '',
				'company_country'   => '',
			)
		);

		$this->assertContains( 'company_details_checkbox', $gates );
		$this->assertNotContains( 'company_name_checkbox', $gates );
	}

	public function test_checkbox_gates_returns_nothing_when_shop_info_fields_are_all_empty(): void {
		$gates = WPWing_WcPdf_Wizard::get_checkbox_gates_to_apply(
			'shop_info',
			array(
				'company_name_text' => '',
				'company_address'   => '',
				'company_city'      => '',
				'company_zip'       => '',
				'company_country'   => '',
			)
		);

		$this->assertSame( array(), $gates );
	}

	public function test_checkbox_gates_forces_logo_checkbox_when_logo_present(): void {
		$gates = WPWing_WcPdf_Wizard::get_checkbox_gates_to_apply(
			'logo',
			array( 'company_logo_upload' => 'https://example.com/logo.png' )
		);

		$this->assertSame( array( 'company_logo_checkbox' ), $gates );
	}

	public function test_checkbox_gates_returns_nothing_for_a_step_with_no_gated_fields(): void {
		$gates = WPWing_WcPdf_Wizard::get_checkbox_gates_to_apply( 'paper_size', array() );

		$this->assertSame( array(), $gates );
	}
}
