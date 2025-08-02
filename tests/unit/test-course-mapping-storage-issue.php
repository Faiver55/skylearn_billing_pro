<?php
/**
 * Integration test to simulate the actual course mapping storage issue
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC  
 * @license GPLv3
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

class CourseMapppingStorageIssueTest extends PHPUnit\Framework\TestCase {
    
    private $course_mapping;
    private $db_manager;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Clear any existing options
        global $test_options;
        $test_options = array();
        
        $this->db_manager = new SkyLearn_Billing_Pro_Database_Manager();
        
        // Create a mock course mapping that skips course validation
        $this->course_mapping = $this->getMockBuilder('SkyLearn_Billing_Pro_Course_Mapping')
            ->setMethods(['validate_course_exists'])
            ->getMock();
        
        // Mock course validation to always return true for tests
        $this->course_mapping->method('validate_course_exists')->willReturn(true);
        
        // Use reflection to set the db_manager property
        $reflection = new ReflectionClass($this->course_mapping);
        $db_manager_property = $reflection->getProperty('db_manager');
        $db_manager_property->setAccessible(true);
        $db_manager_property->setValue($this->course_mapping, $this->db_manager);
        
        // Also set migration property
        $migration = new SkyLearn_Billing_Pro_Course_Mapping_Migration();
        $migration_property = $reflection->getProperty('migration');
        $migration_property->setAccessible(true);
        $migration_property->setValue($this->course_mapping, $migration);
    }
    
    protected function tearDown(): void {
        // Clean up
        global $test_options;
        $test_options = array();
        parent::tearDown();
    }
    
    /**
     * Test the exact scenario described in the issue:
     * Large data size (1740 bytes) causing options API to fail
     */
    public function test_large_course_mapping_data_storage() {
        // Simulate the exact scenario from the error log
        $product_id = "585363";
        $course_id = 33671;
        
        // Create additional settings that would make the data size large
        $large_additional_settings = array(
            'enrollment_settings' => array(
                'auto_enroll' => true,
                'send_email' => true,
                'email_template' => str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 50), // ~2.5KB
                'custom_fields' => array_fill(0, 20, 'custom_value_' . str_repeat('x', 50))
            ),
            'course_access' => array(
                'duration' => 365,
                'access_type' => 'unlimited',
                'restrictions' => array_fill(0, 10, 'restriction_' . str_repeat('y', 100))
            )
        );
        
        // Calculate approximate data size
        $test_data_size = strlen(serialize($large_additional_settings));
        $this->assertGreaterThan(1740, $test_data_size, 'Test data should be larger than the reported issue size');
        
        // This should succeed with the new custom table implementation
        $result = $this->course_mapping->save_course_mapping($product_id, $course_id, 'payment', $large_additional_settings);
        
        $this->assertTrue($result, 'Large course mapping should be saved successfully with custom table');
        
        // Verify the mapping can be retrieved
        $saved_mapping = $this->course_mapping->get_course_mapping($product_id);
        $this->assertNotFalse($saved_mapping, 'Saved mapping should be retrievable');
        $this->assertEquals($course_id, $saved_mapping['course_id'], 'Course ID should match');
    }
    
    /**
     * Test multiple large mappings to simulate the "mapping_count":1 issue
     */
    public function test_multiple_large_course_mappings() {
        $mappings_to_create = 5;
        $product_ids = array();
        
        for ($i = 0; $i < $mappings_to_create; $i++) {
            $product_id = "large_product_$i";
            $course_id = 1000 + $i;
            $product_ids[] = $product_id;
            
            // Create large settings for each mapping
            $large_settings = array(
                'bulk_data' => array_fill(0, 100, "data_chunk_$i" . str_repeat('z', 50)),
                'metadata' => array(
                    'created_by' => 'test_system',
                    'description' => str_repeat("Description for mapping $i. ", 100),
                    'tags' => array_fill(0, 20, "tag_$i" . str_repeat('t', 25))
                )
            );
            
            $result = $this->course_mapping->save_course_mapping($product_id, $course_id, 'payment', $large_settings);
            $this->assertTrue($result, "Large mapping $i should be saved successfully");
        }
        
        // Verify all mappings are saved and retrievable
        $all_mappings = $this->course_mapping->get_course_mappings();
        $this->assertCount($mappings_to_create, $all_mappings, 'All large mappings should be stored');
        
        foreach ($product_ids as $index => $product_id) {
            $this->assertArrayHasKey($product_id, $all_mappings, "Mapping for $product_id should exist");
            $this->assertEquals(1000 + $index, $all_mappings[$product_id]['course_id'], "Course ID should match for $product_id");
        }
    }
    
    /**
     * Test the specific error scenario: "WordPress unable to update options"
     */
    public function test_wordpress_options_limitation_resolved() {
        // Create a mapping that would definitely exceed WordPress options limitations in the old system
        $massive_settings = array();
        
        // Generate approximately 50KB of data
        for ($i = 0; $i < 100; $i++) {
            $massive_settings["section_$i"] = array(
                'large_text' => str_repeat("Sample text for section $i with lots of content. ", 100),
                'array_data' => array_fill(0, 50, "item_$i" . str_repeat('x', 20)),
                'nested_structure' => array(
                    'level1' => array(
                        'level2' => array(
                            'data' => str_repeat("nested_$i", 50)
                        )
                    )
                )
            );
        }
        
        $data_size = strlen(serialize($massive_settings));
        $this->assertGreaterThan(50000, $data_size, 'Test data should be very large (>50KB)');
        
        // This would definitely fail with the old options-based system but should work with custom tables
        $result = $this->course_mapping->save_course_mapping('massive_product', 99999, 'payment', $massive_settings);
        
        $this->assertTrue($result, 'Massive course mapping should be saved successfully with custom table');
        
        // Verify data integrity
        $saved_mapping = $this->course_mapping->get_course_mapping('massive_product');
        $this->assertNotFalse($saved_mapping, 'Massive mapping should be retrievable');
        $this->assertEquals(99999, $saved_mapping['course_id'], 'Course ID should be preserved');
        $this->assertArrayHasKey('settings', $saved_mapping, 'Large settings should be preserved');
        $this->assertArrayHasKey('section_0', $saved_mapping['settings'], 'Settings structure should be preserved');
    }
    
    /**
     * Test error handling improvements described in the issue
     */
    public function test_improved_error_handling() {
        // Test duplicate mapping error
        $result1 = $this->course_mapping->save_course_mapping('duplicate_test', 123, 'payment');
        $this->assertTrue($result1, 'First mapping should succeed');
        
        $result2 = $this->course_mapping->save_course_mapping('duplicate_test', 456, 'payment');
        $this->assertInstanceOf('WP_Error', $result2, 'Duplicate mapping should return WP_Error');
        $this->assertEquals('duplicate_mapping', $result2->get_error_code(), 'Should return specific error code');
        $this->assertStringContainsString('already exists', $result2->get_error_message(), 'Should provide clear error message');
    }
    
    /**
     * Test data validation improvements
     */
    public function test_improved_data_validation() {
        // Test invalid product ID
        $result = $this->course_mapping->save_course_mapping('', 123, 'payment');
        $this->assertInstanceOf('WP_Error', $result, 'Empty product ID should be rejected');
        $this->assertEquals('invalid_product_id', $result->get_error_code());
        
        // Test invalid course ID
        $result = $this->course_mapping->save_course_mapping('valid_product', 0, 'payment');
        $this->assertInstanceOf('WP_Error', $result, 'Zero course ID should be rejected');
        $this->assertEquals('invalid_course_id', $result->get_error_code());
        
        // Test invalid trigger type
        $result = $this->course_mapping->save_course_mapping('valid_product', 123, 'invalid_trigger');
        $this->assertInstanceOf('WP_Error', $result, 'Invalid trigger should be rejected');
        $this->assertEquals('invalid_trigger', $result->get_error_code());
        
        // Test course ID range validation
        $result = $this->course_mapping->save_course_mapping('valid_product', 1000000000, 'payment');
        $this->assertInstanceOf('WP_Error', $result, 'Course ID outside valid range should be rejected');
        $this->assertEquals('invalid_course_range', $result->get_error_code());
    }
    
    /**
     * Test enrollment logging improvements
     */
    public function test_improved_enrollment_logging() {
        // Use reflection to access private logging method
        $reflection = new ReflectionClass($this->course_mapping);
        $log_method = $reflection->getMethod('log_enrollment');
        $log_method->setAccessible(true);
        
        // Test successful enrollment logging
        $log_method->invoke($this->course_mapping, 'test_product', 1, 123, 'payment', 'success');
        
        // Test failed enrollment logging with error message
        $log_method->invoke($this->course_mapping, 'test_product_2', 2, 456, 'webhook', 'failed', 'Course not found');
        
        // Retrieve logs
        $logs = $this->course_mapping->get_enrollment_log(10);
        
        $this->assertCount(2, $logs, 'Should have 2 log entries');
        
        // Check latest log (failed enrollment)
        $latest_log = $logs[0];
        $this->assertEquals('test_product_2', $latest_log['product_id']);
        $this->assertEquals('failed', $latest_log['status']);
        $this->assertEquals('Course not found', $latest_log['error_message']);
        
        // Check older log (successful enrollment)
        $older_log = $logs[1];
        $this->assertEquals('test_product', $older_log['product_id']);
        $this->assertEquals('success', $older_log['status']);
    }
    
    /**
     * Test performance improvement with custom tables
     */
    public function test_performance_improvement() {
        $start_time = microtime(true);
        
        // Create many mappings to test performance
        for ($i = 0; $i < 50; $i++) {
            $result = $this->course_mapping->save_course_mapping("perf_test_$i", 1000 + $i, 'payment');
            $this->assertTrue($result, "Performance test mapping $i should be saved");
        }
        
        $save_time = microtime(true) - $start_time;
        
        $start_time = microtime(true);
        
        // Retrieve all mappings
        $all_mappings = $this->course_mapping->get_course_mappings();
        
        $retrieve_time = microtime(true) - $start_time;
        
        $this->assertCount(50, $all_mappings, 'Should retrieve all 50 mappings');
        
        // These are basic performance checks - in a real database, custom tables would be much faster
        $this->assertLessThan(1.0, $save_time, 'Saving 50 mappings should be reasonably fast');
        $this->assertLessThan(0.1, $retrieve_time, 'Retrieving mappings should be fast');
    }
}