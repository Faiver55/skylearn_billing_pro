<?php
/**
 * Migration Utility for Course Mappings
 *
 * Migrates course mapping data from WordPress options to custom table
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Course Mapping Migration class
 */
class SkyLearn_Billing_Pro_Course_Mapping_Migration {
    
    /**
     * Database Manager instance
     *
     * @var SkyLearn_Billing_Pro_Database_Manager
     */
    private $db_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db_manager = skylearn_billing_pro_database_manager();
    }
    
    /**
     * Migrate data from options to custom table
     *
     * @return array Migration results
     */
    public function migrate_course_mappings() {
        global $wpdb;
        
        $results = array(
            'success' => false,
            'migrated' => 0,
            'errors' => array(),
            'already_migrated' => false
        );
        
        try {
            // Check if tables exist
            if (!$this->db_manager->tables_exist()) {
                $this->db_manager->create_tables();
            }
            
            // Get existing data from options
            $options = get_option('skylearn_billing_pro_options', array());
            $course_mappings = isset($options['course_mappings']) ? $options['course_mappings'] : array();
            
            if (empty($course_mappings)) {
                $results['success'] = true;
                $results['message'] = 'No course mappings found in options to migrate';
                return $results;
            }
            
            // Check if data is already migrated
            $table_name = $this->db_manager->get_course_mappings_table();
            $existing_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            
            if ($existing_count > 0) {
                $results['already_migrated'] = true;
                $results['success'] = true;
                $results['message'] = "Migration already completed. Found $existing_count mappings in custom table.";
                return $results;
            }
            
            // Migrate each mapping
            $migrated_count = 0;
            
            foreach ($course_mappings as $product_id => $mapping) {
                try {
                    $this->migrate_single_mapping($product_id, $mapping);
                    $migrated_count++;
                } catch (Exception $e) {
                    $results['errors'][] = "Failed to migrate mapping for product $product_id: " . $e->getMessage();
                    error_log("SkyLearn Billing Pro Migration: Failed to migrate product $product_id - " . $e->getMessage());
                }
            }
            
            $results['migrated'] = $migrated_count;
            $results['success'] = $migrated_count > 0;
            $results['message'] = "Successfully migrated $migrated_count course mappings to custom table";
            
            // Migrate enrollment log if it exists
            $this->migrate_enrollment_log();
            
            error_log("SkyLearn Billing Pro: Course mapping migration completed - $migrated_count mappings migrated");
            
        } catch (Exception $e) {
            $results['errors'][] = $e->getMessage();
            error_log('SkyLearn Billing Pro Migration: Failed - ' . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Migrate a single course mapping
     *
     * @param string $product_id Product ID
     * @param array $mapping Mapping data
     */
    private function migrate_single_mapping($product_id, $mapping) {
        global $wpdb;
        
        // Validate and sanitize mapping data
        $course_id = isset($mapping['course_id']) ? intval($mapping['course_id']) : 0;
        $trigger_type = isset($mapping['trigger_type']) ? sanitize_text_field($mapping['trigger_type']) : 'payment';
        $status = isset($mapping['status']) ? sanitize_text_field($mapping['status']) : 'active';
        $created_at = isset($mapping['created_at']) ? $mapping['created_at'] : current_time('mysql');
        $updated_at = isset($mapping['updated_at']) ? $mapping['updated_at'] : current_time('mysql');
        
        // Handle additional settings
        $additional_settings = null;
        if (isset($mapping['settings']) && is_array($mapping['settings'])) {
            $additional_settings = wp_json_encode($mapping['settings']);
        }
        
        // Validate required fields
        if (empty($product_id) || $course_id <= 0) {
            throw new Exception("Invalid mapping data: product_id='$product_id', course_id='$course_id'");
        }
        
        // Insert into custom table
        $table_name = $this->db_manager->get_course_mappings_table();
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'product_id' => $product_id,
                'course_id' => $course_id,
                'trigger_type' => $trigger_type,
                'status' => $status,
                'additional_settings' => $additional_settings,
                'created_at' => $created_at,
                'updated_at' => $updated_at
            ),
            array(
                '%s', // product_id
                '%d', // course_id
                '%s', // trigger_type
                '%s', // status
                '%s', // additional_settings
                '%s', // created_at
                '%s'  // updated_at
            )
        );
        
        if ($result === false) {
            throw new Exception("Database error: " . ($wpdb->last_error ?: 'Unknown error'));
        }
    }
    
    /**
     * Migrate enrollment log from options to custom table
     */
    private function migrate_enrollment_log() {
        global $wpdb;
        
        try {
            $options = get_option('skylearn_billing_pro_options', array());
            $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
            
            if (empty($enrollment_log)) {
                return;
            }
            
            $table_name = $this->db_manager->get_enrollment_log_table();
            $migrated_count = 0;
            
            foreach ($enrollment_log as $entry) {
                try {
                    $result = $wpdb->insert(
                        $table_name,
                        array(
                            'product_id' => isset($entry['product_id']) ? sanitize_text_field($entry['product_id']) : '',
                            'user_id' => isset($entry['user_id']) ? intval($entry['user_id']) : 0,
                            'course_id' => isset($entry['course_id']) ? intval($entry['course_id']) : 0,
                            'trigger_type' => isset($entry['trigger']) ? sanitize_text_field($entry['trigger']) : 'unknown',
                            'status' => isset($entry['status']) ? sanitize_text_field($entry['status']) : 'unknown',
                            'user_email' => isset($entry['user_email']) ? sanitize_email($entry['user_email']) : null,
                            'course_title' => isset($entry['course_title']) ? sanitize_text_field($entry['course_title']) : null,
                            'created_at' => isset($entry['timestamp']) ? $entry['timestamp'] : current_time('mysql')
                        ),
                        array('%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s')
                    );
                    
                    if ($result !== false) {
                        $migrated_count++;
                    }
                    
                } catch (Exception $e) {
                    error_log("SkyLearn Billing Pro Migration: Failed to migrate enrollment log entry - " . $e->getMessage());
                }
            }
            
            if ($migrated_count > 0) {
                error_log("SkyLearn Billing Pro: Migrated $migrated_count enrollment log entries");
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro Migration: Failed to migrate enrollment log - ' . $e->getMessage());
        }
    }
    
    /**
     * Backup current options data before migration
     *
     * @return bool Success status
     */
    public function backup_options_data() {
        try {
            $options = get_option('skylearn_billing_pro_options', array());
            
            if (!empty($options)) {
                $backup_key = 'skylearn_billing_pro_options_backup_' . date('Y_m_d_H_i_s');
                update_option($backup_key, $options);
                
                error_log("SkyLearn Billing Pro: Options data backed up to $backup_key");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro Migration: Failed to backup options data - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Clean up options data after successful migration
     *
     * @param bool $keep_backup Whether to keep backup of original data
     * @return bool Success status
     */
    public function cleanup_options_data($keep_backup = true) {
        try {
            if ($keep_backup) {
                $this->backup_options_data();
            }
            
            // Remove course mappings and enrollment log from options
            $options = get_option('skylearn_billing_pro_options', array());
            
            if (isset($options['course_mappings'])) {
                unset($options['course_mappings']);
            }
            
            if (isset($options['enrollment_log'])) {
                unset($options['enrollment_log']);
            }
            
            $result = update_option('skylearn_billing_pro_options', $options);
            
            if ($result !== false) {
                error_log('SkyLearn Billing Pro: Cleaned up course mapping data from options');
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro Migration: Failed to cleanup options data - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check migration status
     *
     * @return array Status information
     */
    public function get_migration_status() {
        global $wpdb;
        
        $status = array(
            'tables_exist' => false,
            'options_data_exists' => false,
            'custom_table_count' => 0,
            'options_mapping_count' => 0,
            'migration_needed' => false
        );
        
        try {
            // Check if custom tables exist
            $status['tables_exist'] = $this->db_manager->tables_exist();
            
            // Check options data
            $options = get_option('skylearn_billing_pro_options', array());
            $course_mappings = isset($options['course_mappings']) ? $options['course_mappings'] : array();
            $status['options_mapping_count'] = count($course_mappings);
            $status['options_data_exists'] = $status['options_mapping_count'] > 0;
            
            // Check custom table data
            if ($status['tables_exist']) {
                $table_name = $this->db_manager->get_course_mappings_table();
                $status['custom_table_count'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
            }
            
            // Determine if migration is needed
            $status['migration_needed'] = $status['options_data_exists'] && 
                                        ($status['custom_table_count'] == 0 || 
                                         $status['custom_table_count'] < $status['options_mapping_count']);
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error checking migration status - ' . $e->getMessage());
        }
        
        return $status;
    }
}

/**
 * Get the Course Mapping Migration instance
 *
 * @return SkyLearn_Billing_Pro_Course_Mapping_Migration
 */
function skylearn_billing_pro_course_mapping_migration() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Course_Mapping_Migration();
    }
    
    return $instance;
}