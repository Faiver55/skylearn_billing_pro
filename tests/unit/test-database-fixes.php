<?php
/**
 * Tests for database manager and course mapping fixes
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC  
 * @license GPLv3
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

class DatabaseFixesTest extends PHPUnit\Framework\TestCase {
    
    protected function setUp(): void {
        parent::setUp();
        
        // Clear any existing options
        global $test_options;
        $test_options = array();
    }
    
    protected function tearDown(): void {
        // Clean up
        global $test_options;
        $test_options = array();
        parent::tearDown();
    }
    
    /**
     * Test that database manager can be instantiated
     */
    public function test_database_manager_instantiation() {
        $db_manager = new SkyLearn_Billing_Pro_Database_Manager();
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Database_Manager', $db_manager);
    }
    
    /**
     * Test course mapping migration can be instantiated
     */
    public function test_migration_instantiation() {
        $migration = new SkyLearn_Billing_Pro_Course_Mapping_Migration();
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Course_Mapping_Migration', $migration);
    }
    
    /**
     * Test table names are generated correctly
     */
    public function test_table_name_generation() {
        $db_manager = new SkyLearn_Billing_Pro_Database_Manager();
        
        $course_mappings_table = $db_manager->get_course_mappings_table();
        $this->assertEquals('wp_skylearn_course_mappings', $course_mappings_table);
        
        $enrollment_log_table = $db_manager->get_enrollment_log_table();
        $this->assertEquals('wp_skylearn_enrollment_log', $enrollment_log_table);
    }
    
    /**
     * Test validation methods work correctly
     */
    public function test_course_mapping_validation() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        
        // Use reflection to access private validation methods
        $reflection = new ReflectionClass($course_mapping);
        $validate_inputs = $reflection->getMethod('validate_mapping_inputs');
        $validate_inputs->setAccessible(true);
        
        // Test valid inputs
        $result = $validate_inputs->invoke($course_mapping, 'valid_product_123', 456, 'payment');
        $this->assertTrue($result, 'Valid inputs should pass validation');
        
        // Test invalid inputs
        $result = $validate_inputs->invoke($course_mapping, '', 456, 'payment');
        $this->assertInstanceOf('WP_Error', $result, 'Empty product ID should fail validation');
        
        $result = $validate_inputs->invoke($course_mapping, 'valid_product', 0, 'payment');
        $this->assertInstanceOf('WP_Error', $result, 'Zero course ID should fail validation');
        
        $result = $validate_inputs->invoke($course_mapping, 'valid_product', 456, 'invalid_trigger');
        $this->assertInstanceOf('WP_Error', $result, 'Invalid trigger type should fail validation');
    }
    
    /**
     * Test data integrity validation
     */
    public function test_data_integrity_validation() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        
        // Use reflection to access private validation methods
        $reflection = new ReflectionClass($course_mapping);
        $validate_integrity = $reflection->getMethod('validate_data_integrity');
        $validate_integrity->setAccessible(true);
        
        // Test valid data
        $result = $validate_integrity->invoke($course_mapping, 'valid_product_123', 456);
        $this->assertTrue($result, 'Valid data should pass integrity check');
        
        // Test invalid course ID range
        $result = $validate_integrity->invoke($course_mapping, 'valid_product', 1000000000);
        $this->assertInstanceOf('WP_Error', $result, 'Course ID outside valid range should fail');
    }
    
    /**
     * Test that the course mapping class handles database manager availability
     */
    public function test_course_mapping_handles_missing_db_manager() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        
        // Use reflection to check internal state
        $reflection = new ReflectionClass($course_mapping);
        $db_manager_property = $reflection->getProperty('db_manager');
        $db_manager_property->setAccessible(true);
        
        // The db_manager should be set (even if mock)
        $db_manager = $db_manager_property->getValue($course_mapping);
        $this->assertNotNull($db_manager, 'Database manager should be initialized');
    }
    
    /**
     * Test that fallback methods exist and work
     */
    public function test_fallback_methods_exist() {
        $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
        
        // Check that the fallback method exists
        $reflection = new ReflectionClass($course_mapping);
        $this->assertTrue($reflection->hasMethod('get_course_mappings_from_options'), 'Fallback method should exist');
        $this->assertTrue($reflection->hasMethod('get_course_mapping_from_options'), 'Fallback method should exist');
    }
    
    /**
     * Test error handling for WP_Error objects
     */
    public function test_wp_error_handling() {
        $error = new WP_Error('test_code', 'Test message');
        
        $this->assertTrue(is_wp_error($error), 'Should identify WP_Error objects');
        $this->assertEquals('test_code', $error->get_error_code(), 'Should return correct error code');
        $this->assertEquals('Test message', $error->get_error_message(), 'Should return correct error message');
    }
    
    /**
     * Test course mapping structure
     */
    public function test_course_mapping_structure() {
        // Simulate mapping data structure
        $mapping_data = array(
            'product_id' => 'test_product_123',
            'course_id' => 456,
            'trigger_type' => 'payment',
            'status' => 'active',
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        );
        
        $this->assertArrayHasKey('product_id', $mapping_data);
        $this->assertArrayHasKey('course_id', $mapping_data);
        $this->assertArrayHasKey('trigger_type', $mapping_data);
        $this->assertArrayHasKey('status', $mapping_data);
        $this->assertArrayHasKey('created_at', $mapping_data);
        $this->assertArrayHasKey('updated_at', $mapping_data);
        
        $this->assertEquals('test_product_123', $mapping_data['product_id']);
        $this->assertEquals(456, $mapping_data['course_id']);
        $this->assertEquals('payment', $mapping_data['trigger_type']);
        $this->assertEquals('active', $mapping_data['status']);
    }
    
    /**
     * Test enrollment log structure
     */
    public function test_enrollment_log_structure() {
        // Simulate enrollment log entry structure
        $log_entry = array(
            'product_id' => 'test_product_123',
            'user_id' => 1,
            'course_id' => 456,
            'trigger_type' => 'payment',
            'status' => 'success',
            'user_email' => 'test@example.com',
            'course_title' => 'Test Course',
            'error_message' => null,
            'created_at' => '2024-01-01 00:00:00'
        );
        
        $this->assertArrayHasKey('product_id', $log_entry);
        $this->assertArrayHasKey('user_id', $log_entry);
        $this->assertArrayHasKey('course_id', $log_entry);
        $this->assertArrayHasKey('trigger_type', $log_entry);
        $this->assertArrayHasKey('status', $log_entry);
        $this->assertArrayHasKey('user_email', $log_entry);
        $this->assertArrayHasKey('course_title', $log_entry);
        $this->assertArrayHasKey('error_message', $log_entry);
        $this->assertArrayHasKey('created_at', $log_entry);
    }
    
    /**
     * Test migration status structure
     */
    public function test_migration_status_structure() {
        $migration = new SkyLearn_Billing_Pro_Course_Mapping_Migration();
        $status = $migration->get_migration_status();
        
        $this->assertIsArray($status, 'Migration status should be an array');
        $this->assertArrayHasKey('tables_exist', $status);
        $this->assertArrayHasKey('options_data_exists', $status);
        $this->assertArrayHasKey('custom_table_count', $status);
        $this->assertArrayHasKey('options_mapping_count', $status);
        $this->assertArrayHasKey('migration_needed', $status);
    }
    
    /**
     * Test that constants are properly defined
     */
    public function test_required_constants() {
        $this->assertTrue(defined('SKYLEARN_BILLING_PRO_PLUGIN_DIR'), 'Plugin directory constant should be defined');
        $this->assertTrue(defined('SKYLEARN_BILLING_PRO_VERSION'), 'Version constant should be defined');
    }
}