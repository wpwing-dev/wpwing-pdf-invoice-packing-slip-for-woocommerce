<?php
use PHPUnit\Framework\TestCase;

class PluginTest extends TestCase {

    public function test_version_constant_is_defined(): void {
        $this->assertSame( '0.0.0-test', WPWING_WCPDF_VERSION );
    }
}
