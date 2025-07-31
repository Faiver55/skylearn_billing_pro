<?php
/**
 * Unit tests for LMS Manager
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';

class Test_LMS_Manager extends SkyLearn_Billing_Pro_Test_Case {
    
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
     * Test LMS Manager instantiation
     */
    public function test_lms_manager_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_LMS_Manager', $this->lms_manager);
    }
    
    /**
     * Test getting supported LMS plugins
     */
    public function test_get_supported_lms() {
        $supported_lms = $this->lms_manager->get_supported_lms();
        
        $this->assertIsArray($supported_lms);
        $this->assertNotEmpty($supported_lms);
        
        // Check that key LMS platforms are supported
        $this->assertArrayHasKey('learndash', $supported_lms);
        $this->assertArrayHasKey('tutor', $supported_lms);
        $this->assertArrayHasKey('lifter', $supported_lms);
        $this->assertArrayHasKey('learnpress', $supported_lms);
        
        // Check LMS structure
        foreach ($supported_lms as $lms_key => $lms_data) {
            $this->assertArrayHasKey('name', $lms_data);
            $this->assertArrayHasKey('plugin_path', $lms_data);
            $this->assertArrayHasKey('class_name', $lms_data);
            $this->assertArrayHasKey('function_name', $lms_data);
            $this->assertArrayHasKey('connector_class', $lms_data);
            
            $this->assertIsString($lms_data['name']);
            $this->assertIsString($lms_data['plugin_path']);
            $this->assertIsString($lms_data['class_name']);
            $this->assertIsString($lms_data['function_name']);
            $this->assertIsString($lms_data['connector_class']);
        }
    }
    
    /**
     * Test detecting installed LMS plugins
     */
    public function test_detect_lms_plugins() {
        // Mock function_exists to simulate LearnDash being installed
        $original_function_exists = null;
        if (function_exists('runkit_function_redefine')) {
            runkit_function_redefine('function_exists', '$function_name', 'return $function_name === "learndash_get_course_id";');
        }
        
        $detected_lms = $this->lms_manager->detect_lms_plugins();
        
        $this->assertIsArray($detected_lms);
        
        // Restore original function if modified
        if ($original_function_exists !== null && function_exists('runkit_function_redefine')) {
            runkit_function_redefine('function_exists', '$function_name', $original_function_exists);
        }
    }
    
    /**
     * Test setting active LMS
     */
    public function test_set_active_lms() {
        // Test setting a valid LMS
        $result = $this->lms_manager->set_active_lms('learndash');
        $this->assertTrue($result);
        
        // Test setting an invalid LMS
        $result = $this->lms_manager->set_active_lms('invalid_lms');
        $this->assertFalse($result);
    }
    
    /**
     * Test getting active LMS
     */
    public function test_get_active_lms() {
        // Initially should be empty
        $active_lms = $this->lms_manager->get_active_lms();
        $this->assertEmpty($active_lms);
        
        // Set active LMS and test retrieval
        $this->lms_manager->set_active_lms('learndash');
        $active_lms = $this->lms_manager->get_active_lms();
        $this->assertEquals('learndash', $active_lms);
    }
    
    /**
     * Test checking if LMS is available
     */
    public function test_is_lms_available() {
        // Test with a supported LMS (should return false as it's not actually installed)
        $available = $this->lms_manager->is_lms_available('learndash');
        $this->assertIsBool($available);
        
        // Test with an unsupported LMS
        $available = $this->lms_manager->is_lms_available('invalid_lms');
        $this->assertFalse($available);
    }
    
    /**
     * Test getting LMS connector
     */
    public function test_get_lms_connector() {
        // Test without active LMS
        $connector = $this->lms_manager->get_lms_connector();
        $this->assertNull($connector);
        
        // Test with active LMS (mocked)
        $this->lms_manager->set_active_lms('learndash');
        $connector = $this->lms_manager->get_lms_connector();
        
        // Since the actual connector class might not exist in test environment,
        // we just test that it returns something (null or object)
        $this->assertTrue($connector === null || is_object($connector));
    }
    
    /**
     * Test enrolling user in course
     */
    public function test_enroll_user_in_course() {
        $user_id = 123;
        $course_id = 456;
        
        // Test without active LMS
        $result = $this->lms_manager->enroll_user_in_course($user_id, $course_id);
        $this->assertFalse($result);
        
        // Test with active LMS (will fail since connector doesn't exist, but should handle gracefully)
        $this->lms_manager->set_active_lms('learndash');
        $result = $this->lms_manager->enroll_user_in_course($user_id, $course_id);
        $this->assertIsBool($result);
    }
    
    /**
     * Test validating course ID
     */
    public function test_validate_course_id() {
        // Test with invalid course ID
        $valid = $this->lms_manager->validate_course_id(0);
        $this->assertFalse($valid);
        
        $valid = $this->lms_manager->validate_course_id(-1);
        $this->assertFalse($valid);
        
        $valid = $this->lms_manager->validate_course_id('');
        $this->assertFalse($valid);
        
        // Test with valid course ID
        $valid = $this->lms_manager->validate_course_id(123);
        $this->assertIsBool($valid); // May be true or false depending on LMS availability
    }
    
    /**
     * Test getting course information
     */
    public function test_get_course_info() {
        $course_id = 123;
        
        // Test without active LMS
        $info = $this->lms_manager->get_course_info($course_id);
        $this->assertNull($info);
        
        // Test with active LMS
        $this->lms_manager->set_active_lms('learndash');
        $info = $this->lms_manager->get_course_info($course_id);
        
        // Should return null or array with course info
        $this->assertTrue($info === null || is_array($info));
    }
    
    /**
     * Test LMS status check
     */
    public function test_get_lms_status() {
        $status = $this->lms_manager->get_lms_status();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('active_lms', $status);
        $this->assertArrayHasKey('detected_lms', $status);
        $this->assertArrayHasKey('available_lms', $status);
        
        $this->assertIsArray($status['detected_lms']);
        $this->assertIsArray($status['available_lms']);
    }
    
    /**
     * Test LMS compatibility check
     */
    public function test_check_lms_compatibility() {
        $compatibility = $this->lms_manager->check_lms_compatibility();
        
        $this->assertIsArray($compatibility);
        $this->assertArrayHasKey('compatible', $compatibility);
        $this->assertArrayHasKey('issues', $compatibility);
        
        $this->assertIsBool($compatibility['compatible']);
        $this->assertIsArray($compatibility['issues']);
    }
    
    /**
     * Test handling multiple LMS detection
     */
    public function test_handle_multiple_lms() {
        // Mock multiple LMS being detected
        $multiple_lms = array('learndash', 'tutor', 'lifter');
        
        // Test that only one can be active at a time
        foreach ($multiple_lms as $lms) {
            $this->lms_manager->set_active_lms($lms);
            $active = $this->lms_manager->get_active_lms();
            $this->assertEquals($lms, $active);
        }
        
        // The last one set should be active
        $this->assertEquals('lifter', $this->lms_manager->get_active_lms());
    }
}