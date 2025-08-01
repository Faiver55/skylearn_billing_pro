<?php
/**
 * Unit test for Course Mapping functionality after fixes
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-course-mapping.php';

class Test_Course_Mapping_Fixes extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Course Mapping instance
     *
     * @var SkyLearn_Billing_Pro_Course_Mapping
     */
    private $course_mapping;
    
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
        $this->course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
    }
    
    /**
     * Test Course Mapping instantiation
     */
    public function test_course_mapping_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Course_Mapping', $this->course_mapping);
    }
    
    /**
     * Test LMS Manager has all required methods
     */
    public function test_lms_manager_has_required_methods() {
        // Test all methods that were previously missing
        $this->assertTrue(method_exists($this->lms_manager, 'is_lms_available'));
        $this->assertTrue(method_exists($this->lms_manager, 'get_lms_connector'));
        $this->assertTrue(method_exists($this->lms_manager, 'enroll_user_in_course'));
        $this->assertTrue(method_exists($this->lms_manager, 'validate_course_id'));
        $this->assertTrue(method_exists($this->lms_manager, 'get_course_info'));
        $this->assertTrue(method_exists($this->lms_manager, 'get_lms_status'));
        $this->assertTrue(method_exists($this->lms_manager, 'check_lms_compatibility'));
    }
    
    /**
     * Test course mapping basic operations
     */
    public function test_course_mapping_basic_operations() {
        // Test getting empty mappings
        $mappings = $this->course_mapping->get_course_mappings();
        $this->assertIsArray($mappings);
        
        // Test getting empty enrollment log
        $log = $this->course_mapping->get_enrollment_log();
        $this->assertIsArray($log);
    }
    
    /**
     * Test saving course mapping
     */
    public function test_save_course_mapping() {
        // This will fail because no LMS is available, but shouldn't cause fatal error
        $result = $this->course_mapping->save_course_mapping('test_product', 123, 'payment');
        $this->assertIsBool($result);
    }
    
    /**
     * Test process enrollment with no LMS
     */
    public function test_process_enrollment_no_lms() {
        // Should handle gracefully when no LMS is available
        $result = $this->course_mapping->process_enrollment('test_product', 123, 'payment');
        $this->assertFalse($result);
    }
    
    /**
     * Test LMS Manager error handling
     */
    public function test_lms_manager_error_handling() {
        // These should all return safe default values without fatal errors
        $courses = $this->lms_manager->get_courses();
        $this->assertIsArray($courses);
        
        $status = $this->lms_manager->get_integration_status();
        $this->assertIsArray($status);
        $this->assertArrayHasKey('detected_count', $status);
        $this->assertArrayHasKey('active_lms', $status);
        
        $enroll_result = $this->lms_manager->enroll_user(123, 456);
        $this->assertFalse($enroll_result);
        
        $course_details = $this->lms_manager->get_course_details(123);
        $this->assertFalse($course_details);
    }
    
    /**
     * Test Course Mapping UI rendering doesn't cause fatal errors
     * 
     * @group ui
     */
    public function test_course_mapping_ui_rendering() {
        // Skip this test in basic environment since it requires WordPress functions
        if (!function_exists('esc_html_e')) {
            $this->markTestSkipped('UI rendering test requires full WordPress environment');
        }
        
        // Start output buffering
        ob_start();
        
        try {
            // This should not cause fatal errors
            $this->course_mapping->render_mapping_ui();
            $output = ob_get_clean();
            
            // Should have some output
            $this->assertNotEmpty($output);
            
            // Should contain error notice about LMS Manager
            $this->assertStringContainsString('LMS Manager could not be initialized', $output);
            
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail('Course Mapping UI rendering caused exception: ' . $e->getMessage());
        } catch (Error $e) {
            ob_end_clean();
            $this->fail('Course Mapping UI rendering caused fatal error: ' . $e->getMessage());
        }
    }
    
    /**
     * Test validate course ID
     */
    public function test_validate_course_id() {
        // Test invalid course IDs
        $this->assertFalse($this->lms_manager->validate_course_id(0));
        $this->assertFalse($this->lms_manager->validate_course_id(-1));
        $this->assertFalse($this->lms_manager->validate_course_id(''));
        $this->assertFalse($this->lms_manager->validate_course_id('invalid'));
        
        // Test valid course ID (will be false due to no LMS, but shouldn't cause error)
        $result = $this->lms_manager->validate_course_id(123);
        $this->assertIsBool($result);
    }
    
    /**
     * Test LMS compatibility check
     */
    public function test_lms_compatibility_check() {
        $compatibility = $this->lms_manager->check_lms_compatibility();
        
        $this->assertIsArray($compatibility);
        $this->assertArrayHasKey('compatible', $compatibility);
        $this->assertArrayHasKey('issues', $compatibility);
        $this->assertIsBool($compatibility['compatible']);
        $this->assertIsArray($compatibility['issues']);
    }
    
    /**
     * Test LMS status retrieval
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
}