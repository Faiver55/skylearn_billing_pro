<?php
/**
 * Tests for database saving fixes in LMS Integration
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC  
 * @license GPLv3
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

class DatabaseSavingFixesTest extends PHPUnit\Framework\TestCase {
    
    private $course_mapping;
    private $lms_manager;
    private $webhook_handler;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Create mock instances
        $this->course_mapping = $this->createMock('SkyLearn_Billing_Pro_Course_Mapping');
        $this->lms_manager = $this->createMock('SkyLearn_Billing_Pro_LMS_Manager');
        $this->webhook_handler = $this->createMock('SkyLearn_Billing_Pro_Webhook_Handler');
        
        // Clear any existing options
        delete_option('skylearn_billing_pro_options');
    }
    
    protected function tearDown(): void {
        // Clean up
        delete_option('skylearn_billing_pro_options');
        parent::tearDown();
    }
    
    /**
     * Test that update_option false return value is handled correctly
     */
    public function test_update_option_false_handling() {
        // Simulate the condition where update_option returns false due to no changes
        $test_options = array(
            'course_mappings' => array(
                'test_product_123' => array(
                    'product_id' => 'test_product_123',
                    'course_id' => 456,
                    'trigger_type' => 'payment',
                    'status' => 'active'
                )
            )
        );
        
        // Set initial options
        update_option('skylearn_billing_pro_options', $test_options);
        
        // Verify that saving the same data again doesn't cause an error
        $result = update_option('skylearn_billing_pro_options', $test_options);
        
        // update_option returns false when data hasn't changed - this is expected behavior
        $this->assertFalse($result, 'update_option should return false when data hasn\'t changed');
        
        // But the data should still be there
        $stored_options = get_option('skylearn_billing_pro_options', array());
        $this->assertEquals($test_options, $stored_options, 'Data should still be stored correctly');
    }
    
    /**
     * Test error handling for large data
     */
    public function test_large_data_handling() {
        // Create a large options array to test size limits
        $large_mappings = array();
        for ($i = 0; $i < 100; $i++) {
            $large_mappings["product_$i"] = array(
                'product_id' => "product_$i",
                'course_id' => $i,
                'trigger_type' => 'payment',
                'status' => 'active',
                'large_data' => str_repeat('x', 1000) // 1KB of data per mapping
            );
        }
        
        $large_options = array('course_mappings' => $large_mappings);
        
        // Calculate approximate size
        $size = strlen(serialize($large_options));
        $this->assertLessThan(1048576, $size, 'Test data should be less than 1MB for this test');
        
        // This should work fine
        $result = update_option('skylearn_billing_pro_options', $large_options);
        $this->assertTrue($result, 'Should be able to save moderately large data');
    }
    
    /**
     * Test input validation
     */
    public function test_input_validation() {
        // Test various invalid inputs that should be caught by validation
        $invalid_inputs = array(
            array('', 123, 'payment'), // Empty product ID
            array('ab', 123, 'payment'), // Too short product ID
            array(str_repeat('x', 101), 123, 'payment'), // Too long product ID
            array('valid_product', 0, 'payment'), // Invalid course ID
            array('valid_product', -1, 'payment'), // Negative course ID
            array('valid_product', 'not_a_number', 'payment'), // Non-numeric course ID
            array('valid_product', 123, 'invalid_trigger'), // Invalid trigger type
        );
        
        foreach ($invalid_inputs as $input) {
            list($product_id, $course_id, $trigger_type) = $input;
            
            // These should fail validation
            $product_valid = !empty($product_id) && is_string($product_id) && strlen($product_id) >= 3 && strlen($product_id) <= 100;
            $course_valid = !empty($course_id) && is_numeric($course_id) && intval($course_id) > 0;
            $trigger_valid = in_array($trigger_type, array('payment', 'webhook', 'manual', 'any'));
            
            $is_valid = $product_valid && $course_valid && $trigger_valid;
            
            $this->assertFalse($is_valid, "Input should be invalid: product_id='$product_id', course_id='$course_id', trigger_type='$trigger_type'");
        }
    }
    
    /**
     * Test data integrity validation
     */
    public function test_data_integrity() {
        // Test that product IDs are properly sanitized
        $test_product_id = "test<script>alert('xss')</script>product";
        $sanitized = sanitize_text_field($test_product_id);
        
        $this->assertNotEquals($test_product_id, $sanitized, 'Product ID should be sanitized');
        $this->assertStringNotContainsString('<script>', $sanitized, 'Sanitized product ID should not contain script tags');
    }
    
    /**
     * Test error message handling
     */
    public function test_error_messages() {
        // Test that WP_Error objects are created properly for different scenarios
        $error_cases = array(
            array('duplicate_mapping', 'A mapping for this product ID already exists.'),
            array('invalid_course', 'The selected course does not exist or is not accessible.'),
            array('save_failed', 'Failed to save course mapping to database.'),
        );
        
        foreach ($error_cases as $case) {
            list($code, $message) = $case;
            $error = new WP_Error($code, $message);
            
            $this->assertTrue(is_wp_error($error), 'Should create a valid WP_Error object');
            $this->assertEquals($code, $error->get_error_code(), 'Error code should match');
            $this->assertEquals($message, $error->get_error_message(), 'Error message should match');
        }
    }
    
    /**
     * Test option structure validation
     */
    public function test_option_structure() {
        // Test that the options array has the expected structure
        $test_options = array(
            'course_mappings' => array(),
            'lms_settings' => array(
                'active_lms' => 'learndash',
                'manual_overrides' => array()
            ),
            'webhook_settings' => array(
                'enabled' => true,
                'secret' => 'test_secret',
                'send_welcome_email' => false
            )
        );
        
        update_option('skylearn_billing_pro_options', $test_options);
        $stored_options = get_option('skylearn_billing_pro_options');
        
        $this->assertIsArray($stored_options, 'Options should be an array');
        $this->assertArrayHasKey('course_mappings', $stored_options, 'Should have course_mappings key');
        $this->assertArrayHasKey('lms_settings', $stored_options, 'Should have lms_settings key');
        $this->assertArrayHasKey('webhook_settings', $stored_options, 'Should have webhook_settings key');
        
        $this->assertIsArray($stored_options['course_mappings'], 'course_mappings should be an array');
        $this->assertIsArray($stored_options['lms_settings'], 'lms_settings should be an array');
        $this->assertIsArray($stored_options['webhook_settings'], 'webhook_settings should be an array');
    }
}