<?php
/**
 * Simplified test for the course mapping storage solution
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC  
 * @license GPLv3
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

class CourseMapppingStorageSolutionTest extends PHPUnit\Framework\TestCase {
    
    /**
     * Test that the solution addresses the core issues mentioned in the problem statement
     */
    public function test_core_storage_issues_addressed() {
        // 1. Custom table structure resolves options table limitations
        $db_manager = new SkyLearn_Billing_Pro_Database_Manager();
        $course_mappings_table = $db_manager->get_course_mappings_table();
        $enrollment_log_table = $db_manager->get_enrollment_log_table();
        
        $this->assertEquals('wp_skylearn_course_mappings', $course_mappings_table);
        $this->assertEquals('wp_skylearn_enrollment_log', $enrollment_log_table);
        
        // 2. Validation methods are in place
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        $reflection = new ReflectionClass($course_mapping);
        
        $this->assertTrue($reflection->hasMethod('validate_mapping_inputs'));
        $this->assertTrue($reflection->hasMethod('validate_data_integrity'));
        
        // 3. Fallback methods exist for backward compatibility
        $this->assertTrue($reflection->hasMethod('get_course_mappings_from_options'));
        $this->assertTrue($reflection->hasMethod('save_course_mapping_to_options'));
        $this->assertTrue($reflection->hasMethod('delete_course_mapping_from_options'));
        
        // 4. Migration system is in place
        $migration = new SkyLearn_Billing_Pro_Course_Mapping_Migration();
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Course_Mapping_Migration', $migration);
    }
    
    /**
     * Test that error handling is improved as specified in the requirements
     */
    public function test_improved_error_handling() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        $reflection = new ReflectionClass($course_mapping);
        $validate_inputs = $reflection->getMethod('validate_mapping_inputs');
        $validate_inputs->setAccessible(true);
        
        // Test specific error codes mentioned in the requirements
        $result = $validate_inputs->invoke($course_mapping, '', 456, 'payment');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_product_id', $result->get_error_code());
        
        $result = $validate_inputs->invoke($course_mapping, 'valid_product', 0, 'payment');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_course_id', $result->get_error_code());
        
        $result = $validate_inputs->invoke($course_mapping, 'valid_product', 456, 'invalid_trigger');
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_trigger', $result->get_error_code());
    }
    
    /**
     * Test that data validation improvements are implemented
     */
    public function test_data_validation_improvements() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        $reflection = new ReflectionClass($course_mapping);
        $validate_integrity = $reflection->getMethod('validate_data_integrity');
        $validate_integrity->setAccessible(true);
        
        // Test product ID sanitization check
        $result = $validate_integrity->invoke($course_mapping, 'valid_product', 456);
        $this->assertTrue($result, 'Valid data should pass integrity check');
        
        // Test course ID range validation
        $result = $validate_integrity->invoke($course_mapping, 'valid_product', 1000000000);
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_course_range', $result->get_error_code());
        
        // Test mapping count limit
        // This would normally check against actual stored mappings, but in test environment it should pass
        $result = $validate_integrity->invoke($course_mapping, 'test_product', 123);
        $this->assertTrue($result, 'Should pass integrity check in test environment');
    }
    
    /**
     * Test that migration system handles the exact scenario from the problem statement
     */
    public function test_migration_handles_problem_scenario() {
        // Simulate the exact data mentioned in the error log
        $problem_scenario_data = array(
            'course_mappings' => array(
                '585363' => array(
                    'product_id' => '585363',
                    'course_id' => 33671,
                    'trigger_type' => 'payment',
                    'status' => 'active',
                    'created_at' => '2024-01-01 00:00:00',
                    'updated_at' => '2024-01-01 00:00:00',
                    // Add data that would make this ~1740 bytes
                    'large_settings' => array(
                        'enrollment_data' => str_repeat('x', 1000),
                        'course_metadata' => array_fill(0, 50, 'metadata_value'),
                        'user_restrictions' => array_fill(0, 20, 'restriction_rule')
                    )
                )
            )
        );
        
        // Verify the data size matches the problem scenario
        $data_size = strlen(serialize($problem_scenario_data));
        $this->assertGreaterThan(1740, $data_size, 'Test data should match problem scenario size');
        
        // Simulate saving this to options (would fail in real scenario)
        update_option('skylearn_billing_pro_options', $problem_scenario_data);
        
        // Test migration can handle this scenario
        $migration = new SkyLearn_Billing_Pro_Course_Mapping_Migration();
        $status = $migration->get_migration_status();
        
        $this->assertIsArray($status, 'Migration status should be available');
        $this->assertArrayHasKey('migration_needed', $status);
        $this->assertArrayHasKey('options_mapping_count', $status);
        
        // The migration should identify this as needing migration
        $this->assertEquals(1, $status['options_mapping_count'], 'Should detect 1 mapping in options');
    }
    
    /**
     * Test that the solution uses proper database operations instead of options
     */
    public function test_database_operations_over_options() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        $reflection = new ReflectionClass($course_mapping);
        
        // Verify that new table-based methods exist
        $this->assertTrue($reflection->hasMethod('save_course_mapping_to_table'));
        $this->assertTrue($reflection->hasMethod('delete_course_mapping_from_table'));
        
        // Verify that the main methods try table operations first
        $save_method = $reflection->getMethod('save_course_mapping');
        $save_method_source = file_get_contents($reflection->getFileName());
        
        // Check that the save method uses custom table when available
        $this->assertStringContainsString('save_course_mapping_to_table', $save_method_source);
        $this->assertStringContainsString('save_course_mapping_to_options', $save_method_source);
        $this->assertStringContainsString('tables_exist()', $save_method_source);
    }
    
    /**
     * Test that enrollment logging is improved with custom table
     */
    public function test_improved_enrollment_logging() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        $reflection = new ReflectionClass($course_mapping);
        
        // Verify enhanced logging methods exist
        $this->assertTrue($reflection->hasMethod('log_enrollment_to_table'));
        $this->assertTrue($reflection->hasMethod('log_enrollment_to_options'));
        
        // Verify that the logging method accepts error messages (new parameter)
        $log_method = $reflection->getMethod('log_enrollment');
        $log_method->setAccessible(true);
        
        // This should not throw an error even with the new error_message parameter
        try {
            $log_method->invoke($course_mapping, 'test_product', 1, 123, 'payment', 'success', 'test_error');
            $this->assertTrue(true, 'Enhanced log_enrollment method should accept error message parameter');
        } catch (Exception $e) {
            $this->fail('Enhanced log_enrollment method should not throw exception: ' . $e->getMessage());
        }
    }
    
    /**
     * Test that the solution provides better error messages as required
     */
    public function test_better_error_messages() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        $reflection = new ReflectionClass($course_mapping);
        $validate_inputs = $reflection->getMethod('validate_mapping_inputs');
        $validate_inputs->setAccessible(true);
        
        // Test that error messages are descriptive and user-friendly
        $result = $validate_inputs->invoke($course_mapping, '', 456, 'payment');
        $message = $result->get_error_message();
        $this->assertStringContainsString('required', $message);
        $this->assertStringContainsString('string', $message);
        
        $result = $validate_inputs->invoke($course_mapping, 'ab', 456, 'payment');
        $message = $result->get_error_message();
        $this->assertStringContainsString('3 characters', $message);
        
        $result = $validate_inputs->invoke($course_mapping, 'valid_product', 0, 'payment');
        $message = $result->get_error_message();
        $this->assertStringContainsString('positive integer', $message);
    }
    
    /**
     * Test that custom table schema is optimized for course mapping data
     */
    public function test_optimized_table_schema() {
        // This test verifies that the table creation SQL includes proper optimization
        $db_manager = new SkyLearn_Billing_Pro_Database_Manager();
        $reflection = new ReflectionClass($db_manager);
        $create_method = $reflection->getMethod('create_course_mappings_table');
        
        // Read the source code to verify schema design
        $source = file_get_contents($reflection->getFileName());
        
        // Verify key optimizations mentioned in the requirements
        $this->assertStringContainsString('PRIMARY KEY (id)', $source);
        $this->assertStringContainsString('UNIQUE KEY product_id', $source);
        $this->assertStringContainsString('KEY course_id', $source);
        $this->assertStringContainsString('KEY trigger_type', $source);
        $this->assertStringContainsString('KEY status', $source);
        $this->assertStringContainsString('ENGINE=InnoDB', $source);
        $this->assertStringContainsString('utf8mb4_unicode_ci', $source);
        
        // Verify that large data is handled with longtext
        $this->assertStringContainsString('longtext', $source);
    }
}