<?php
/**
 * Tests for custom table course mappings implementation
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC  
 * @license GPLv3
 */

require_once dirname(__FILE__) . '/../bootstrap.php';

class CustomTableCourseMappingTest extends PHPUnit\Framework\TestCase {
    
    private $db_manager;
    private $course_mapping;
    private $migration;
    
    protected function setUp(): void {
        parent::setUp();
        
        // Clear any existing options and tables
        delete_option('skylearn_billing_pro_options');
        delete_option('skylearn_billing_pro_mappings_migrated');
        delete_option('skylearn_billing_pro_db_version');
        
        // Initialize components
        $this->db_manager = new SkyLearn_Billing_Pro_Database_Manager();
        $this->migration = new SkyLearn_Billing_Pro_Course_Mapping_Migration();
        
        // Create mock course mapping with db manager
        $this->course_mapping = $this->getMockBuilder('SkyLearn_Billing_Pro_Course_Mapping')
            ->setMethods(['validate_course_exists'])
            ->getMock();
        
        // Mock course validation to always return true for tests
        $this->course_mapping->method('validate_course_exists')->willReturn(true);
        
        // Use reflection to set the private db_manager property
        $reflection = new ReflectionClass($this->course_mapping);
        $db_manager_property = $reflection->getProperty('db_manager');
        $db_manager_property->setAccessible(true);
        $db_manager_property->setValue($this->course_mapping, $this->db_manager);
        
        $migration_property = $reflection->getProperty('migration');
        $migration_property->setAccessible(true);
        $migration_property->setValue($this->course_mapping, $this->migration);
    }
    
    protected function tearDown(): void {
        // Clean up tables and options
        $this->db_manager->drop_tables();
        delete_option('skylearn_billing_pro_options');
        delete_option('skylearn_billing_pro_mappings_migrated');
        delete_option('skylearn_billing_pro_db_version');
        parent::tearDown();
    }
    
    /**
     * Test custom table creation
     */
    public function test_table_creation() {
        $this->db_manager->create_tables();
        
        $this->assertTrue($this->db_manager->tables_exist(), 'Custom tables should be created');
        
        // Verify table structure
        global $wpdb;
        $course_mappings_table = $this->db_manager->get_course_mappings_table();
        
        $columns = $wpdb->get_results("SHOW COLUMNS FROM $course_mappings_table");
        $column_names = array_column($columns, 'Field');
        
        $expected_columns = ['id', 'product_id', 'course_id', 'trigger_type', 'status', 'additional_settings', 'created_at', 'updated_at'];
        
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $column_names, "Table should have $column column");
        }
    }
    
    /**
     * Test saving course mapping to custom table
     */
    public function test_save_course_mapping_to_table() {
        $this->db_manager->create_tables();
        
        $result = $this->course_mapping->save_course_mapping('test_product_123', 456, 'payment');
        
        $this->assertTrue($result, 'Should successfully save course mapping to custom table');
        
        // Verify data was saved
        global $wpdb;
        $table_name = $this->db_manager->get_course_mappings_table();
        
        $saved_mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE product_id = %s",
            'test_product_123'
        ), ARRAY_A);
        
        $this->assertNotEmpty($saved_mapping, 'Mapping should be saved in database');
        $this->assertEquals('test_product_123', $saved_mapping['product_id']);
        $this->assertEquals(456, $saved_mapping['course_id']);
        $this->assertEquals('payment', $saved_mapping['trigger_type']);
        $this->assertEquals('active', $saved_mapping['status']);
    }
    
    /**
     * Test retrieving course mappings from custom table
     */
    public function test_get_course_mappings_from_table() {
        $this->db_manager->create_tables();
        
        // Save test mappings
        $this->course_mapping->save_course_mapping('product_1', 101, 'payment');
        $this->course_mapping->save_course_mapping('product_2', 102, 'webhook');
        
        $mappings = $this->course_mapping->get_course_mappings();
        
        $this->assertCount(2, $mappings, 'Should retrieve 2 mappings');
        $this->assertArrayHasKey('product_1', $mappings);
        $this->assertArrayHasKey('product_2', $mappings);
        
        $this->assertEquals(101, $mappings['product_1']['course_id']);
        $this->assertEquals(102, $mappings['product_2']['course_id']);
    }
    
    /**
     * Test deleting course mapping from custom table
     */
    public function test_delete_course_mapping_from_table() {
        $this->db_manager->create_tables();
        
        // Save and then delete mapping
        $this->course_mapping->save_course_mapping('test_product_delete', 789, 'payment');
        $result = $this->course_mapping->delete_course_mapping('test_product_delete');
        
        $this->assertTrue($result, 'Should successfully delete course mapping');
        
        // Verify deletion
        $mapping = $this->course_mapping->get_course_mapping('test_product_delete');
        $this->assertFalse($mapping, 'Mapping should be deleted');
    }
    
    /**
     * Test migration from options to custom table
     */
    public function test_migration_from_options() {
        // Set up options data
        $options_data = array(
            'course_mappings' => array(
                'migrate_product_1' => array(
                    'product_id' => 'migrate_product_1',
                    'course_id' => 201,
                    'trigger_type' => 'payment',
                    'status' => 'active',
                    'created_at' => '2024-01-01 00:00:00',
                    'updated_at' => '2024-01-01 00:00:00'
                ),
                'migrate_product_2' => array(
                    'product_id' => 'migrate_product_2',
                    'course_id' => 202,
                    'trigger_type' => 'webhook',
                    'status' => 'active',
                    'created_at' => '2024-01-01 00:00:00',
                    'updated_at' => '2024-01-01 00:00:00',
                    'settings' => array('custom_setting' => 'value')
                )
            )
        );
        
        update_option('skylearn_billing_pro_options', $options_data);
        
        // Create tables and run migration
        $this->db_manager->create_tables();
        $result = $this->migration->migrate_course_mappings();
        
        $this->assertTrue($result['success'], 'Migration should succeed');
        $this->assertEquals(2, $result['migrated'], 'Should migrate 2 mappings');
        
        // Verify migrated data
        $mappings = $this->course_mapping->get_course_mappings();
        $this->assertCount(2, $mappings, 'Should have 2 migrated mappings');
        
        $this->assertEquals(201, $mappings['migrate_product_1']['course_id']);
        $this->assertEquals(202, $mappings['migrate_product_2']['course_id']);
        $this->assertEquals('webhook', $mappings['migrate_product_2']['trigger_type']);
        
        // Check additional settings migration
        $this->assertArrayHasKey('settings', $mappings['migrate_product_2']);
        $this->assertEquals('value', $mappings['migrate_product_2']['settings']['custom_setting']);
    }
    
    /**
     * Test fallback to options when tables don't exist
     */
    public function test_fallback_to_options() {
        // Don't create tables - should fallback to options
        $options_data = array(
            'course_mappings' => array(
                'fallback_product' => array(
                    'product_id' => 'fallback_product',
                    'course_id' => 999,
                    'trigger_type' => 'manual',
                    'status' => 'active'
                )
            )
        );
        
        update_option('skylearn_billing_pro_options', $options_data);
        
        $mappings = $this->course_mapping->get_course_mappings();
        
        $this->assertCount(1, $mappings, 'Should retrieve mapping from options');
        $this->assertEquals(999, $mappings['fallback_product']['course_id']);
    }
    
    /**
     * Test duplicate mapping prevention in custom table
     */
    public function test_duplicate_mapping_prevention() {
        $this->db_manager->create_tables();
        
        // Save first mapping
        $result1 = $this->course_mapping->save_course_mapping('duplicate_test', 123, 'payment');
        $this->assertTrue($result1, 'First mapping should succeed');
        
        // Try to save duplicate
        $result2 = $this->course_mapping->save_course_mapping('duplicate_test', 456, 'webhook');
        $this->assertInstanceOf('WP_Error', $result2, 'Duplicate mapping should return WP_Error');
        $this->assertEquals('duplicate_mapping', $result2->get_error_code());
    }
    
    /**
     * Test enrollment logging to custom table
     */
    public function test_enrollment_logging_to_table() {
        $this->db_manager->create_tables();
        
        // Use reflection to access private log_enrollment method
        $reflection = new ReflectionClass($this->course_mapping);
        $log_method = $reflection->getMethod('log_enrollment');
        $log_method->setAccessible(true);
        
        // Log an enrollment
        $log_method->invoke($this->course_mapping, 'log_test_product', 1, 123, 'payment', 'success');
        
        // Verify log was saved
        global $wpdb;
        $log_table = $this->db_manager->get_enrollment_log_table();
        
        $log_entry = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $log_table WHERE product_id = %s",
            'log_test_product'
        ), ARRAY_A);
        
        $this->assertNotEmpty($log_entry, 'Enrollment should be logged');
        $this->assertEquals('log_test_product', $log_entry['product_id']);
        $this->assertEquals(1, $log_entry['user_id']);
        $this->assertEquals(123, $log_entry['course_id']);
        $this->assertEquals('payment', $log_entry['trigger_type']);
        $this->assertEquals('success', $log_entry['status']);
    }
    
    /**
     * Test getting enrollment log from custom table
     */
    public function test_get_enrollment_log_from_table() {
        $this->db_manager->create_tables();
        
        // Add test log entries directly to database
        global $wpdb;
        $log_table = $this->db_manager->get_enrollment_log_table();
        
        $wpdb->insert($log_table, array(
            'product_id' => 'log_product_1',
            'user_id' => 1,
            'course_id' => 101,
            'trigger_type' => 'payment',
            'status' => 'success',
            'created_at' => current_time('mysql')
        ));
        
        $wpdb->insert($log_table, array(
            'product_id' => 'log_product_2',
            'user_id' => 2,
            'course_id' => 102,
            'trigger_type' => 'webhook',
            'status' => 'failed',
            'created_at' => current_time('mysql')
        ));
        
        $log = $this->course_mapping->get_enrollment_log();
        
        $this->assertCount(2, $log, 'Should retrieve 2 log entries');
        $this->assertEquals('log_product_1', $log[1]['product_id']); // Latest first
        $this->assertEquals('log_product_2', $log[0]['product_id']);
    }
    
    /**
     * Test error handling for database failures
     */
    public function test_database_error_handling() {
        // Create tables first
        $this->db_manager->create_tables();
        
        // Drop table to simulate error
        global $wpdb;
        $table_name = $this->db_manager->get_course_mappings_table();
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
        
        // This should now fallback to options
        $options_data = array(
            'course_mappings' => array(
                'error_test' => array(
                    'product_id' => 'error_test',
                    'course_id' => 555,
                    'trigger_type' => 'payment',
                    'status' => 'active'
                )
            )
        );
        update_option('skylearn_billing_pro_options', $options_data);
        
        $mappings = $this->course_mapping->get_course_mappings();
        $this->assertCount(1, $mappings, 'Should fallback to options on database error');
        $this->assertEquals(555, $mappings['error_test']['course_id']);
    }
}