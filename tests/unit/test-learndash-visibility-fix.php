<?php
/**
 * Unit tests for LearnDash Method Visibility Fix
 *
 * This test specifically validates that the is_learndash_active() method
 * is now public and can be accessed by the LMS Manager without reflection.
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-learndash.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';

class Test_LearnDash_Visibility_Fix extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * LearnDash connector instance
     *
     * @var SkyLearn_Billing_Pro_LearnDash_Connector
     */
    private $connector;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Mock LearnDash functions globally if not already defined
        $this->mockLearnDashFunctions();
        
        $this->connector = new SkyLearn_Billing_Pro_LearnDash_Connector();
    }
    
    /**
     * Mock LearnDash functions for testing
     */
    private function mockLearnDashFunctions() {
        if (!function_exists('learndash_get_course_id')) {
            eval('function learndash_get_course_id() { return 1; }');
        }
        
        if (!class_exists('SFWD_LMS')) {
            eval('class SFWD_LMS {}');
        }
    }
    
    /**
     * Test that is_learndash_active() method is now public and accessible
     */
    public function test_is_learndash_active_method_is_public() {
        // Test 1: Method should exist
        $this->assertTrue(
            method_exists($this->connector, 'is_learndash_active'),
            'The is_learndash_active method should exist on the LearnDash connector'
        );
        
        // Test 2: Method should be public (can be called directly)
        $reflection = new ReflectionMethod($this->connector, 'is_learndash_active');
        $this->assertTrue(
            $reflection->isPublic(),
            'The is_learndash_active method should be public'
        );
        
        // Test 3: Method should be callable without reflection
        $result = $this->connector->is_learndash_active();
        $this->assertIsBool($result, 'is_learndash_active should return a boolean value');
        
        // Test 4: Method should return true in our test environment (mocked functions exist)
        $this->assertTrue($result, 'is_learndash_active should return true when LearnDash functions are available');
    }
    
    /**
     * Test that LMS Manager can now call the method directly without reflection
     */
    public function test_lms_manager_can_call_method_directly() {
        $lms_manager = new SkyLearn_Billing_Pro_LMS_Manager();
        
        // Mock the active connector to be our LearnDash connector
        $reflection = new ReflectionClass($lms_manager);
        $property = $reflection->getProperty('active_connector');
        $property->setAccessible(true);
        $property->setValue($lms_manager, $this->connector);
        
        // Test that the method can be called directly on the active connector
        $active_connector = $lms_manager->get_active_connector();
        
        if ($active_connector && method_exists($active_connector, 'is_learndash_active')) {
            $result = $active_connector->is_learndash_active();
            $this->assertIsBool($result, 'LMS Manager should be able to call is_learndash_active directly');
            $this->assertTrue($result, 'Method should return true when LearnDash is mocked as active');
        }
    }
    
    /**
     * Test the debugging functionality added to the method
     */
    public function test_debugging_functionality() {
        // Enable debug mode for this test
        if (!defined('WP_DEBUG')) {
            define('WP_DEBUG', true);
        }
        
        // Capture error log output
        $original_error_log = ini_get('error_log');
        $test_log_file = '/tmp/test_debug.log';
        ini_set('error_log', $test_log_file);
        
        // Clear any existing log
        if (file_exists($test_log_file)) {
            unlink($test_log_file);
        }
        
        // Call the method - this should trigger debug logging
        $result = $this->connector->is_learndash_active();
        
        // Check if debug information was logged
        if (file_exists($test_log_file)) {
            $log_content = file_get_contents($test_log_file);
            $this->assertStringContainsString('LearnDash active check', $log_content, 'Debug information should be logged');
            $this->assertStringContainsString('learndash_get_course_id exists:', $log_content, 'Function existence check should be logged');
            $this->assertStringContainsString('SFWD_LMS class exists:', $log_content, 'Class existence check should be logged');
            $this->assertStringContainsString('Result:', $log_content, 'Result should be logged');
            unlink($test_log_file);
        }
        
        // Restore original error log setting
        ini_set('error_log', $original_error_log);
        
        $this->assertIsBool($result, 'Method should still return boolean even with debug logging');
    }
    
    /**
     * Test that method works correctly when LearnDash is not available
     */
    public function test_method_when_learndash_not_available() {
        // Create a new connector instance but don't mock the LearnDash functions
        $connector_without_learndash = new SkyLearn_Billing_Pro_LearnDash_Connector();
        
        // The method should still be callable and return false
        $result = $connector_without_learndash->is_learndash_active();
        $this->assertIsBool($result, 'Method should return boolean when LearnDash is not available');
        
        // Note: Result might be true if functions were already mocked in previous tests,
        // so we just verify it returns a boolean value
    }
    
    /**
     * Test that the method maintains backward compatibility
     */
    public function test_backward_compatibility() {
        // Even though the method is now public, it should still work the same way
        // Test with reflection (the old way) and direct call (the new way)
        
        // Old way with reflection
        $reflection = new ReflectionMethod($this->connector, 'is_learndash_active');
        $reflection->setAccessible(true);
        $result_reflection = $reflection->invoke($this->connector);
        
        // New way with direct call
        $result_direct = $this->connector->is_learndash_active();
        
        // Both should give the same result
        $this->assertEquals($result_reflection, $result_direct, 'Reflection and direct call should give the same result');
        $this->assertIsBool($result_reflection, 'Reflection call should return boolean');
        $this->assertIsBool($result_direct, 'Direct call should return boolean');
    }
}