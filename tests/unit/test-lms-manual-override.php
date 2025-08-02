<?php
/**
 * Unit test for manual override functionality
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';

class Test_LMS_Manual_Override extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * LMS Manager instance
     *
     * @var SkyLearn_Billing_Pro_LMS_Manager
     */
    private $lms_manager;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        $this->lms_manager = new SkyLearn_Billing_Pro_LMS_Manager();
    }
    
    /**
     * Test manual override functionality
     */
    public function test_manual_override_functionality() {
        // Initially LearnDash should not be active (no real LMS installed)
        $this->assertFalse($this->lms_manager->is_lms_active('learndash'));
        
        // Set manual override for LearnDash
        $result = $this->lms_manager->set_lms_manual_override('learndash', true);
        $this->assertTrue($result);
        
        // Now LearnDash should be detected as active
        $this->assertTrue($this->lms_manager->is_lms_active('learndash'));
        
        // Should now be able to set LearnDash as active LMS
        $set_active_result = $this->lms_manager->set_active_lms('learndash');
        $this->assertTrue($set_active_result);
        
        // Should be the active LMS
        $this->assertEquals('learndash', $this->lms_manager->get_active_lms());
        
        // Disable override
        $this->lms_manager->set_lms_manual_override('learndash', false);
        
        // Should no longer be active (unless actually installed)
        $this->assertFalse($this->lms_manager->is_lms_active('learndash'));
    }
    
    /**
     * Test manual override with invalid LMS
     */
    public function test_manual_override_invalid_lms() {
        $result = $this->lms_manager->set_lms_manual_override('invalid_lms', true);
        $this->assertFalse($result);
    }
    
    /**
     * Test getting override status
     */
    public function test_get_manual_override_status() {
        // Initially should be false
        $this->assertFalse($this->lms_manager->get_lms_manual_override('learndash'));
        
        // Set override
        $this->lms_manager->set_lms_manual_override('learndash', true);
        
        // Should now be true
        $this->assertTrue($this->lms_manager->get_lms_manual_override('learndash'));
        
        // Test with invalid LMS
        $this->assertFalse($this->lms_manager->get_lms_manual_override('invalid_lms'));
    }
    
    /**
     * Test integration status with manual override
     */
    public function test_integration_status_with_override() {
        // Set manual override
        $this->lms_manager->set_lms_manual_override('learndash', true);
        $this->lms_manager->set_active_lms('learndash');
        
        $status = $this->lms_manager->get_integration_status();
        
        $this->assertIsArray($status);
        $this->assertEquals(1, $status['detected_count']);
        $this->assertArrayHasKey('learndash', $status['detected_lms']);
        $this->assertEquals('learndash', $status['active_lms']);
        $this->assertEquals('LearnDash', $status['active_lms_name']);
    }
}