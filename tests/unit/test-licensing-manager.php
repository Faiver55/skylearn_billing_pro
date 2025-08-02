<?php
/**
 * Unit Test for Licensing Manager
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once(__DIR__ . '/../bootstrap.php');

class TestLicensingManager extends PHPUnit\Framework\TestCase {
    
    private $licensing_manager;
    
    protected function setUp(): void {
        $this->licensing_manager = new SkyLearn_Billing_Pro_Licensing_Manager();
        
        // Reset to clean state
        $this->licensing_manager->deactivate_license();
    }
    
    public function test_demo_key_validation() {
        $demo_keys = array(
            'SKYLRN-PRO-DEMO-2024' => 'pro',
            'SKYLRN-PLUS-DEMO-2024' => 'pro_plus',
            'SKYLEARN-PRO-DEMO-2024' => 'pro',
            'SKYLEARN-PLUS-DEMO-2024' => 'pro_plus',
            'DEMO-PRO-2024' => 'pro',
            'DEMO-PLUS-2024' => 'pro_plus'
        );
        
        foreach ($demo_keys as $key => $expected_tier) {
            $result = $this->licensing_manager->validate_license($key);
            
            $this->assertTrue($result['success'], "Demo key validation failed for: $key");
            $this->assertEquals($expected_tier, $this->licensing_manager->get_current_tier(), "Wrong tier for key: $key");
            $this->assertTrue($this->licensing_manager->is_license_active(), "License should be active for key: $key");
        }
    }
    
    public function test_invalid_key_handling() {
        $invalid_keys = array(
            '',
            'INVALID-KEY',
            'SKYLRN-INVALID-2024',
            'DEMO-INVALID-2024'
        );
        
        foreach ($invalid_keys as $key) {
            $result = $this->licensing_manager->validate_license($key);
            $this->assertFalse($result['success'], "Invalid key should have failed: $key");
            $this->assertNotEmpty($result['message'], "Error message should not be empty for key: $key");
        }
    }
    
    public function test_demo_pattern_recognition() {
        $pattern_keys = array(
            'SKYLRN-DEMO-PRO-2024' => false,  // Wrong order
            'SKYLEARN-DEMO-PLUS-2024' => false,  // Wrong order  
            'SKYLRN-TEST-PRO-2024' => false,     // Test instead of demo
        );
        
        foreach ($pattern_keys as $key => $should_succeed) {
            $result = $this->licensing_manager->validate_license($key);
            $this->assertEquals($should_succeed, $result['success'], "Pattern recognition failed for: $key");
            
            if (!$should_succeed) {
                $this->assertStringContainsString('Demo license key format recognized', $result['message'], "Should give helpful message for: $key");
            }
        }
    }
    
    public function test_license_deactivation() {
        // First activate a demo license
        $result = $this->licensing_manager->validate_license('SKYLRN-PLUS-DEMO-2024');
        $this->assertTrue($result['success']);
        $this->assertTrue($this->licensing_manager->is_license_active());
        
        // Then deactivate
        $result = $this->licensing_manager->deactivate_license();
        $this->assertTrue($result['success']);
        $this->assertFalse($this->licensing_manager->is_license_active());
        $this->assertEquals('free', $this->licensing_manager->get_current_tier());
    }
    
    public function test_tier_hierarchy() {
        $this->licensing_manager->validate_license('SKYLRN-PRO-DEMO-2024');
        
        $this->assertTrue($this->licensing_manager->has_tier('free'));
        $this->assertTrue($this->licensing_manager->has_tier('pro')); 
        $this->assertFalse($this->licensing_manager->has_tier('pro_plus'));
        
        $this->licensing_manager->validate_license('SKYLRN-PLUS-DEMO-2024');
        
        $this->assertTrue($this->licensing_manager->has_tier('free'));
        $this->assertTrue($this->licensing_manager->has_tier('pro')); 
        $this->assertTrue($this->licensing_manager->has_tier('pro_plus'));
    }
    
    public function test_license_data_consistency() {
        $initial_status = $this->licensing_manager->get_license_status();
        $initial_tier = $this->licensing_manager->get_current_tier();
        
        $this->assertEquals('inactive', $initial_status);
        $this->assertEquals('free', $initial_tier);
        
        // Activate demo license
        $result = $this->licensing_manager->validate_license('SKYLRN-PLUS-DEMO-2024');
        $this->assertTrue($result['success']);
        
        // Check immediate consistency
        $this->assertEquals('active', $this->licensing_manager->get_license_status());
        $this->assertEquals('pro_plus', $this->licensing_manager->get_current_tier());
        $this->assertEquals('SKYLRN-PLUS-DEMO-2024', $this->licensing_manager->get_license_key());
    }
}