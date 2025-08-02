<?php
/**
 * Test admin UI manual override functionality
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';

class Test_Admin_UI_Manual_Override extends SkyLearn_Billing_Pro_Test_Case {
    
    private $lms_manager;
    
    protected function setUp(): void {
        parent::setUp();
        $this->lms_manager = new SkyLearn_Billing_Pro_LMS_Manager();
    }
    
    /**
     * Test admin UI form processing simulation
     */
    public function test_admin_form_processing_simulation() {
        // Simulate form submission for enabling LearnDash manual override
        $this->simulateFormSubmission(array(
            'save_manual_override' => '1',
            'manual_override_nonce' => 'test_nonce',
            'manual_override_learndash' => '1',
            'manual_override_tutor' => '',
            'manual_override_lifter' => '',
            'manual_override_learnpress' => ''
        ));
        
        // After processing, LearnDash should be detected
        $this->assertTrue($this->lms_manager->is_lms_active('learndash'));
        $this->assertFalse($this->lms_manager->is_lms_active('tutor'));
        $this->assertFalse($this->lms_manager->is_lms_active('lifter'));
        $this->assertFalse($this->lms_manager->is_lms_active('learnpress'));
        
        // Check detected LMS includes LearnDash
        $detected = $this->lms_manager->get_detected_lms();
        $this->assertArrayHasKey('learndash', $detected);
        $this->assertEquals(1, count($detected), 'Only LearnDash should be detected');
    }
    
    /**
     * Test enabling multiple manual overrides
     */
    public function test_multiple_manual_overrides() {
        // Enable manual override for multiple LMS
        $this->simulateFormSubmission(array(
            'save_manual_override' => '1',
            'manual_override_nonce' => 'test_nonce',
            'manual_override_learndash' => '1',
            'manual_override_tutor' => '1',
            'manual_override_lifter' => '',
            'manual_override_learnpress' => ''
        ));
        
        // Both should be detected
        $this->assertTrue($this->lms_manager->is_lms_active('learndash'));
        $this->assertTrue($this->lms_manager->is_lms_active('tutor'));
        $this->assertFalse($this->lms_manager->is_lms_active('lifter'));
        $this->assertFalse($this->lms_manager->is_lms_active('learnpress'));
        
        $detected = $this->lms_manager->get_detected_lms();
        $this->assertEquals(2, count($detected), 'Both LearnDash and TutorLMS should be detected');
    }
    
    /**
     * Test disabling manual override
     */
    public function test_disable_manual_override() {
        // First enable override
        $this->lms_manager->set_lms_manual_override('learndash', true);
        $this->assertTrue($this->lms_manager->is_lms_active('learndash'));
        
        // Simulate form submission to disable
        $this->simulateFormSubmission(array(
            'save_manual_override' => '1',
            'manual_override_nonce' => 'test_nonce',
            'manual_override_learndash' => '', // Empty means disabled
            'manual_override_tutor' => '',
            'manual_override_lifter' => '',
            'manual_override_learnpress' => ''
        ));
        
        // Should no longer be detected
        $this->assertFalse($this->lms_manager->is_lms_active('learndash'));
        
        $detected = $this->lms_manager->get_detected_lms();
        $this->assertEquals(0, count($detected), 'No LMS should be detected');
    }
    
    /**
     * Test admin UI status display logic
     */
    public function test_admin_ui_status_display() {
        $supported_lms = $this->lms_manager->get_supported_lms();
        
        // Check initial state - no overrides
        foreach ($supported_lms as $lms_key => $lms_data) {
            $manual_override = $this->lms_manager->get_lms_manual_override($lms_key);
            $this->assertFalse($manual_override, "No manual override should be set initially for {$lms_key}");
        }
        
        // Enable LearnDash override
        $this->lms_manager->set_lms_manual_override('learndash', true);
        
        // Check status logic as it would appear in admin UI
        $lms_status = $this->lms_manager->get_integration_status();
        $learndash_detected = isset($lms_status['detected_lms']['learndash']);
        $learndash_override = $this->lms_manager->get_lms_manual_override('learndash');
        $learndash_naturally_active = $learndash_detected && !$learndash_override;
        
        $this->assertTrue($learndash_detected, 'LearnDash should be detected');
        $this->assertTrue($learndash_override, 'LearnDash manual override should be active');
        $this->assertFalse($learndash_naturally_active, 'LearnDash should not be naturally active (since override is enabled)');
    }
    
    /**
     * Test form validation and error handling
     */
    public function test_form_validation() {
        // Test with invalid LMS key (shouldn't cause errors)
        $this->simulateFormSubmission(array(
            'save_manual_override' => '1',
            'manual_override_nonce' => 'test_nonce',
            'manual_override_invalid_lms' => '1',
            'manual_override_learndash' => '1'
        ));
        
        // Valid LMS should still work
        $this->assertTrue($this->lms_manager->is_lms_active('learndash'));
        
        // Invalid LMS key shouldn't cause issues
        $this->assertFalse($this->lms_manager->get_lms_manual_override('invalid_lms'));
    }
    
    /**
     * Simulate admin form submission processing
     */
    private function simulateFormSubmission($form_data) {
        // Process the form data as the admin UI would
        $supported_lms = $this->lms_manager->get_supported_lms();
        
        foreach ($supported_lms as $lms_key => $lms_data) {
            $field_name = 'manual_override_' . $lms_key;
            $override_enabled = isset($form_data[$field_name]) && $form_data[$field_name] === '1';
            $current_override = $this->lms_manager->get_lms_manual_override($lms_key);
            
            if ($override_enabled !== $current_override) {
                $this->lms_manager->set_lms_manual_override($lms_key, $override_enabled);
            }
        }
    }
}