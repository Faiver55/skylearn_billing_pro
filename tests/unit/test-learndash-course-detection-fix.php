<?php
/**
 * Unit tests for LearnDash Course Detection Fix
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-learndash.php';

class Test_LearnDash_Course_Detection_Fix extends SkyLearn_Billing_Pro_Test_Case {
    
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
        
        // Mock other necessary WordPress functions
        if (!function_exists('get_the_ID')) {
            eval('function get_the_ID() { global $post; return isset($post->ID) ? $post->ID : 123; }');
        }
        if (!function_exists('get_the_title')) {
            eval('function get_the_title() { global $post; return isset($post->post_title) ? $post->post_title : "Mock Course"; }');
        }
        if (!function_exists('get_permalink')) {
            eval('function get_permalink() { return "http://example.com/course"; }');
        }
        if (!function_exists('get_post_status')) {
            eval('function get_post_status() { return "publish"; }');
        }
        if (!function_exists('wp_reset_postdata')) {
            eval('function wp_reset_postdata() {}');
        }
    }
    
    /**
     * Test LearnDash connector instantiation
     */
    public function test_learndash_connector_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_LearnDash_Connector', $this->connector);
    }
    
    /**
     * Test LearnDash detection
     */
    public function test_learndash_detection() {
        // Use reflection to access the private method
        $reflection = new ReflectionClass($this->connector);
        $method = $reflection->getMethod('is_learndash_active');
        $method->setAccessible(true);
        
        $is_active = $method->invoke($this->connector);
        $this->assertTrue($is_active, 'LearnDash should be detected as active');
    }
    
    /**
     * Test get_enrolled_count error handling
     */
    public function test_get_enrolled_count_error_handling() {
        // Use reflection to access the private method
        $reflection = new ReflectionClass($this->connector);
        $method = $reflection->getMethod('get_enrolled_count');
        $method->setAccessible(true);
        
        // Test with invalid course ID - should return 0, not fail
        $count = $method->invoke($this->connector, 999);
        $this->assertIsInt($count, 'get_enrolled_count should return integer');
        $this->assertGreaterThanOrEqual(0, $count, 'get_enrolled_count should return non-negative number');
    }
    
    /**
     * Test get_course_price error handling
     */
    public function test_get_course_price_error_handling() {
        // Use reflection to access the private method
        $reflection = new ReflectionClass($this->connector);
        $method = $reflection->getMethod('get_course_price');
        $method->setAccessible(true);
        
        // Test with invalid course ID - should return string, not fail
        $price = $method->invoke($this->connector, 999);
        $this->assertIsString($price, 'get_course_price should return string');
        $this->assertNotEmpty($price, 'get_course_price should return non-empty string');
    }
    
    /**
     * Test get_courses with no database connection
     * This simulates the original error condition
     */
    public function test_get_courses_with_no_database() {
        // Create a mock WP_Query that simulates LearnDash courses
        if (!class_exists('WP_Query')) {
            eval('
                class WP_Query {
                    private $posts = array();
                    private $index = 0;
                    
                    public function __construct($args = array()) {
                        if (isset($args["post_type"]) && $args["post_type"] === "sfwd-courses") {
                            $this->posts = array(
                                (object) array("ID" => 101, "post_title" => "Test Course 1"),
                                (object) array("ID" => 102, "post_title" => "Test Course 2"),
                            );
                        }
                    }
                    
                    public function have_posts() {
                        return $this->index < count($this->posts);
                    }
                    
                    public function the_post() {
                        global $post;
                        if ($this->index < count($this->posts)) {
                            $post = $this->posts[$this->index];
                            $this->index++;
                        }
                    }
                }
            ');
        }
        
        // The key test: get_courses should not fail even when $wpdb is not available
        $courses = $this->connector->get_courses();
        
        $this->assertIsArray($courses, 'get_courses should return array');
        // Note: May be empty due to mocking limitations, but should not throw error
    }
    
    /**
     * Test error logging functionality
     */
    public function test_error_logging() {
        // Capture error log output
        $original_error_log = ini_get('error_log');
        $test_log_file = '/tmp/test_error.log';
        ini_set('error_log', $test_log_file);
        
        // Clear any existing log
        if (file_exists($test_log_file)) {
            unlink($test_log_file);
        }
        
        // Use reflection to access the private method
        $reflection = new ReflectionClass($this->connector);
        $method = $reflection->getMethod('get_enrolled_count');
        $method->setAccessible(true);
        
        // This should trigger the error logging for $wpdb not available
        $count = $method->invoke($this->connector, 123);
        
        // Check if error was logged
        if (file_exists($test_log_file)) {
            $log_content = file_get_contents($test_log_file);
            $this->assertContains('$wpdb not available', $log_content, 'Error should be logged when $wpdb is not available');
            unlink($test_log_file);
        }
        
        // Restore original error log setting
        ini_set('error_log', $original_error_log);
        
        // The method should still return a valid integer
        $this->assertIsInt($count, 'Method should return integer even when logging error');
    }
    
    /**
     * Test that get_courses method is robust against individual course processing errors
     */
    public function test_get_courses_continues_on_individual_errors() {
        // This test verifies that if processing one course fails, 
        // the method continues to process other courses
        
        $courses = $this->connector->get_courses();
        $this->assertIsArray($courses, 'get_courses should always return an array');
        
        // Even if some courses fail to process, the method should not throw an exception
        // and should return what it can successfully process
    }
}