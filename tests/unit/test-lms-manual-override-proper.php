<?php
/**
 * Unit test for manual override functionality with proper mocking
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';

class Test_LMS_Manual_Override_Proper extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * LMS Manager instance
     *
     * @var SkyLearn_Billing_Pro_LMS_Manager
     */
    private $lms_manager;
    
    /**
     * Mock options storage
     *
     * @var array
     */
    private static $mock_options = array();
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Reset mock options
        self::$mock_options = array();
        
        // Override WordPress functions if they're not properly set up
        if (!function_exists('get_option') || get_option('test', 'fallback') === 'fallback') {
            $this->redefineWordPressFunctions();
        }
        
        $this->lms_manager = new SkyLearn_Billing_Pro_LMS_Manager();
    }
    
    /**
     * Redefine WordPress functions for proper testing
     */
    private function redefineWordPressFunctions() {
        // Note: This approach won't work in PHPUnit due to function redefinition limitations
        // But it illustrates what the issue is
    }
    
    /**
     * Test manual override functionality using direct method calls
     */
    public function test_manual_override_direct() {
        // Test that the manual override can be set and retrieved
        $result = $this->lms_manager->set_lms_manual_override('learndash', true);
        $this->assertTrue($result);
        
        // Test that we can get the override status
        $override_status = $this->lms_manager->get_lms_manual_override('learndash');
        $this->assertTrue($override_status);
        
        // Test that LearnDash is now detected as active due to override
        $is_active = $this->lms_manager->is_lms_active('learndash');
        $this->assertTrue($is_active);
        
        // Test detected LMS includes LearnDash
        $detected = $this->lms_manager->get_detected_lms();
        $this->assertArrayHasKey('learndash', $detected);
        $this->assertEquals('LearnDash', $detected['learndash']['name']);
    }
    
    /**
     * Test that manual override allows setting active LMS
     */
    public function test_manual_override_allows_set_active() {
        // Set manual override
        $this->lms_manager->set_lms_manual_override('learndash', true);
        
        // This should now work because LearnDash is detected as active
        $result = $this->lms_manager->set_active_lms('learndash');
        $this->assertTrue($result, 'Should be able to set LearnDash as active with manual override');
        
        // Note: We can't test get_active_lms() in this environment because 
        // the mock get_option doesn't persist data between calls
    }
    
    /**
     * Test disabling manual override
     */
    public function test_disable_manual_override() {
        // Enable override
        $this->lms_manager->set_lms_manual_override('learndash', true);
        $this->assertTrue($this->lms_manager->is_lms_active('learndash'));
        
        // Disable override
        $this->lms_manager->set_lms_manual_override('learndash', false);
        $this->assertFalse($this->lms_manager->get_lms_manual_override('learndash'));
        
        // Should no longer be active (since no real LearnDash is installed)
        $this->assertFalse($this->lms_manager->is_lms_active('learndash'));
    }
    
    /**
     * Test alternative detection methods
     */
    public function test_alternative_detection_methods() {
        $supported_lms = $this->lms_manager->get_supported_lms();
        
        // Check that LearnDash has alternative detection methods
        $this->assertArrayHasKey('learndash', $supported_lms);
        $learndash_config = $supported_lms['learndash'];
        
        $this->assertArrayHasKey('alternative_paths', $learndash_config);
        $this->assertArrayHasKey('alternative_classes', $learndash_config);
        $this->assertArrayHasKey('alternative_functions', $learndash_config);
        
        $this->assertIsArray($learndash_config['alternative_paths']);
        $this->assertIsArray($learndash_config['alternative_classes']);
        $this->assertIsArray($learndash_config['alternative_functions']);
        
        $this->assertNotEmpty($learndash_config['alternative_paths']);
        $this->assertNotEmpty($learndash_config['alternative_classes']);
        $this->assertNotEmpty($learndash_config['alternative_functions']);
    }
}