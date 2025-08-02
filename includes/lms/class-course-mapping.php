<?php
/**
 * Course Mapping UI for Skylearn Billing Pro
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
 * Course Mapping class
 */
class SkyLearn_Billing_Pro_Course_Mapping {
    
    /**
     * LMS Manager instance
     *
     * @var SkyLearn_Billing_Pro_LMS_Manager
     */
    private $lms_manager;
    
    /**
     * Database Manager instance
     *
     * @var SkyLearn_Billing_Pro_Database_Manager
     */
    private $db_manager;
    
    /**
     * Course Mapping Migration instance
     *
     * @var SkyLearn_Billing_Pro_Course_Mapping_Migration
     */
    private $migration;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize database manager first
        try {
            $this->db_manager = skylearn_billing_pro_database_manager();
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Failed to initialize Database Manager in Course Mapping - ' . $e->getMessage());
            $this->db_manager = null;
        }
        
        // Initialize migration utility
        try {
            $this->migration = skylearn_billing_pro_course_mapping_migration();
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Failed to initialize Course Mapping Migration - ' . $e->getMessage());
            $this->migration = null;
        }
        
        // Initialize LMS manager with error handling
        try {
            $this->lms_manager = skylearn_billing_pro_lms_manager();
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Failed to initialize LMS Manager in Course Mapping - ' . $e->getMessage());
            $this->lms_manager = null;
        }
        
        // Check and run migration if needed
        add_action('admin_init', array($this, 'maybe_run_migration'));
        
        // Only add AJAX handlers if required managers are available
        if ($this->lms_manager && $this->db_manager) {
            add_action('wp_ajax_skylearn_billing_save_course_mapping', array($this, 'ajax_save_course_mapping'));
            add_action('wp_ajax_skylearn_billing_delete_course_mapping', array($this, 'ajax_delete_course_mapping'));
            add_action('wp_ajax_skylearn_billing_search_courses', array($this, 'ajax_search_courses'));
        }
    }
    
    /**
     * Check and run migration if needed
     */
    public function maybe_run_migration() {
        // Only run in admin area and if migration utility is available
        if (!is_admin() || !$this->migration || !$this->db_manager) {
            return;
        }
        
        // Check if migration has already been completed
        $migrated_flag = get_option('skylearn_billing_pro_mappings_migrated', false);
        if ($migrated_flag) {
            return;
        }
        
        try {
            $status = $this->migration->get_migration_status();
            
            if ($status['migration_needed']) {
                error_log('SkyLearn Billing Pro: Starting course mapping migration...');
                
                $result = $this->migration->migrate_course_mappings();
                
                if ($result['success']) {
                    // Mark migration as completed
                    update_option('skylearn_billing_pro_mappings_migrated', true);
                    
                    // Clean up options data after successful migration
                    $this->migration->cleanup_options_data();
                    
                    error_log('SkyLearn Billing Pro: Course mapping migration completed successfully');
                } else {
                    error_log('SkyLearn Billing Pro: Course mapping migration failed - ' . implode(', ', $result['errors']));
                }
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Migration check failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Get all course mappings
     *
     * @return array Course mappings
     */
    public function get_course_mappings() {
        global $wpdb;
        
        if (!$this->db_manager || !$this->db_manager->tables_exist()) {
            // Fallback to options-based storage
            return $this->get_course_mappings_from_options();
        }
        
        try {
            $table_name = $this->db_manager->get_course_mappings_table();
            
            $results = $wpdb->get_results(
                "SELECT * FROM $table_name WHERE status = 'active' ORDER BY created_at DESC",
                ARRAY_A
            );
            
            if ($wpdb->last_error) {
                error_log('SkyLearn Billing Pro: Database error in get_course_mappings - ' . $wpdb->last_error);
                return $this->get_course_mappings_from_options();
            }
            
            // Format results to match the expected structure
            $mappings = array();
            foreach ($results as $row) {
                $mappings[$row['product_id']] = array(
                    'product_id' => $row['product_id'],
                    'course_id' => intval($row['course_id']),
                    'trigger_type' => $row['trigger_type'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at']
                );
                
                // Parse additional settings if they exist
                if (!empty($row['additional_settings'])) {
                    $settings = json_decode($row['additional_settings'], true);
                    if (is_array($settings)) {
                        $mappings[$row['product_id']]['settings'] = $settings;
                    }
                }
            }
            
            return $mappings;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error getting course mappings from database - ' . $e->getMessage());
            return $this->get_course_mappings_from_options();
        }
    }
    
    /**
     * Fallback method to get course mappings from options
     *
     * @return array Course mappings from options
     */
    private function get_course_mappings_from_options() {
        $options = get_option('skylearn_billing_pro_options', array());
        return isset($options['course_mappings']) ? $options['course_mappings'] : array();
    }
    
    /**
     * Save course mapping
     *
     * @param string $product_id Product ID
     * @param int $course_id Course ID
     * @param string $trigger_type Trigger type (payment, manual, webhook)
     * @param array $additional_settings Additional mapping settings
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    public function save_course_mapping($product_id, $course_id, $trigger_type = 'payment', $additional_settings = array()) {
        global $wpdb;
        
        try {
            // Validate inputs
            $validation_result = $this->validate_mapping_inputs($product_id, $course_id, $trigger_type);
            if (is_wp_error($validation_result)) {
                return $validation_result;
            }
            
            // Additional data integrity checks
            $integrity_result = $this->validate_data_integrity($product_id, $course_id);
            if (is_wp_error($integrity_result)) {
                return $integrity_result;
            }
            
            // Validate course exists
            if (!$this->validate_course_exists($course_id)) {
                return new WP_Error('invalid_course', __('The selected course does not exist or is not accessible.', 'skylearn-billing-pro'));
            }
            
            // Use custom table if available, otherwise fallback to options
            if ($this->db_manager && $this->db_manager->tables_exist()) {
                return $this->save_course_mapping_to_table($product_id, $course_id, $trigger_type, $additional_settings);
            } else {
                return $this->save_course_mapping_to_options($product_id, $course_id, $trigger_type, $additional_settings);
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error saving course mapping - ' . $e->getMessage());
            return new WP_Error('exception', __('An unexpected error occurred while saving the course mapping.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Save course mapping to custom table
     *
     * @param string $product_id Product ID
     * @param int $course_id Course ID
     * @param string $trigger_type Trigger type
     * @param array $additional_settings Additional mapping settings
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    private function save_course_mapping_to_table($product_id, $course_id, $trigger_type, $additional_settings) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_course_mappings_table();
        
        // Check for duplicate mapping
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE product_id = %s",
            $product_id
        ));
        
        if ($existing) {
            return new WP_Error('duplicate_mapping', __('A mapping for this product ID already exists.', 'skylearn-billing-pro'));
        }
        
        // Prepare additional settings
        $additional_settings_json = null;
        if (!empty($additional_settings) && is_array($additional_settings)) {
            $additional_settings_json = wp_json_encode($additional_settings);
        }
        
        // Insert mapping
        $result = $wpdb->insert(
            $table_name,
            array(
                'product_id' => sanitize_text_field($product_id),
                'course_id' => intval($course_id),
                'trigger_type' => sanitize_text_field($trigger_type),
                'status' => 'active',
                'additional_settings' => $additional_settings_json,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
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
            $error_message = $wpdb->last_error ?: 'Unknown database error';
            
            error_log('SkyLearn Billing Pro: Failed to save course mapping to custom table - ' . $error_message);
            
            // Provide specific error messages based on the error
            if (strpos($error_message, 'Duplicate entry') !== false) {
                return new WP_Error('duplicate_mapping', __('A mapping for this product ID already exists.', 'skylearn-billing-pro'));
            } elseif (strpos($error_message, 'Data too long') !== false) {
                return new WP_Error('save_failed', __('Failed to save course mapping: Data too large.', 'skylearn-billing-pro'));
            } else {
                return new WP_Error('save_failed', sprintf(__('Failed to save course mapping: %s', 'skylearn-billing-pro'), esc_html($error_message)));
            }
        }
        
        // Verify the save was successful
        $verification = $wpdb->get_row($wpdb->prepare(
            "SELECT product_id, course_id FROM $table_name WHERE product_id = %s",
            $product_id
        ));
        
        if (!$verification || $verification->course_id != $course_id) {
            error_log('SkyLearn Billing Pro: Course mapping save verification failed');
            return new WP_Error('save_failed', __('Failed to save course mapping: Data verification failed.', 'skylearn-billing-pro'));
        }
        
        // Log successful mapping creation
        error_log(sprintf(
            'SkyLearn Billing Pro: Course mapping created in custom table - Product: %s, Course: %d, Trigger: %s',
            $product_id,
            $course_id,
            $trigger_type
        ));
        
        // Fire action hook for other plugins to use
        do_action('skylearn_billing_pro_course_mapping_saved', $product_id, $course_id, $trigger_type, array(
            'product_id' => $product_id,
            'course_id' => $course_id,
            'trigger_type' => $trigger_type,
            'status' => 'active',
            'settings' => $additional_settings
        ));
        
        return true;
    }
    
    /**
     * Fallback method to save to options (for backwards compatibility)
     *
     * @param string $product_id Product ID
     * @param int $course_id Course ID
     * @param string $trigger_type Trigger type
     * @param array $additional_settings Additional mapping settings
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    private function save_course_mapping_to_options($product_id, $course_id, $trigger_type, $additional_settings) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['course_mappings'])) {
            $options['course_mappings'] = array();
        }
        
        // Check for duplicate mapping
        if (isset($options['course_mappings'][$product_id])) {
            return new WP_Error('duplicate_mapping', __('A mapping for this product ID already exists.', 'skylearn-billing-pro'));
        }
        
        // Create mapping data
        $mapping_data = array(
            'product_id' => sanitize_text_field($product_id),
            'course_id' => intval($course_id),
            'trigger_type' => sanitize_text_field($trigger_type),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'status' => 'active'
        );
        
        // Add additional settings
        if (!empty($additional_settings) && is_array($additional_settings)) {
            $mapping_data['settings'] = $additional_settings;
        }
        
        // Save mapping
        $options['course_mappings'][$product_id] = $mapping_data;
        
        // Check data size before saving
        $options_size = strlen(serialize($options));
        if ($options_size > 1048576) { // 1MB limit
            error_log('SkyLearn Billing Pro: Course mapping data size warning - ' . $options_size . ' bytes');
            
            if ($options_size > 16777216) { // 16MB - MySQL default max_allowed_packet
                return new WP_Error('save_failed', __('Failed to save course mapping: Too much data stored. Please contact support.', 'skylearn-billing-pro'));
            }
        }
        
        $result = update_option('skylearn_billing_pro_options', $options);
        
        if ($result === false) {
            global $wpdb;
            $last_error = $wpdb->last_error;
            
            error_log('SkyLearn Billing Pro: Failed to save course mapping to options - ' . $last_error);
            return new WP_Error('save_failed', __('Failed to save course mapping: WordPress unable to update options. Please check your database connection and try again.', 'skylearn-billing-pro'));
        }
        
        return true;
    }
    
    /**
     * Validate data integrity for course mapping
     *
     * @param string $product_id Product ID
     * @param int $course_id Course ID
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_data_integrity($product_id, $course_id) {
        // Check if we have too many mappings (performance consideration)
        $current_options = get_option('skylearn_billing_pro_options', array());
        $current_mappings = isset($current_options['course_mappings']) ? $current_options['course_mappings'] : array();
        
        if (count($current_mappings) >= 1000) {
            return new WP_Error('too_many_mappings', __('Cannot create more course mappings. Maximum limit of 1000 mappings reached. Please contact support.', 'skylearn-billing-pro'));
        }
        
        // Check for potential data corruption
        if (isset($current_mappings[$product_id]) && !is_array($current_mappings[$product_id])) {
            error_log('SkyLearn Billing Pro: Data corruption detected in course mappings for product: ' . $product_id);
            return new WP_Error('data_corruption', __('Data corruption detected in existing mappings. Please contact support.', 'skylearn-billing-pro'));
        }
        
        // Validate product ID format (basic sanitization check)
        if ($product_id !== sanitize_text_field($product_id)) {
            return new WP_Error('invalid_product_format', __('Product ID contains invalid characters.', 'skylearn-billing-pro'));
        }
        
        // Validate course ID is within reasonable bounds
        if ($course_id < 1 || $course_id > 999999999) {
            return new WP_Error('invalid_course_range', __('Course ID is outside the valid range.', 'skylearn-billing-pro'));
        }
        
        return true;
    }
    
    /**
     * Validate mapping inputs
     *
     * @param string $product_id Product ID
     * @param int $course_id Course ID  
     * @param string $trigger_type Trigger type
     * @return bool|WP_Error True if valid, WP_Error if invalid
     */
    private function validate_mapping_inputs($product_id, $course_id, $trigger_type) {
        // Validate product ID
        if (empty($product_id) || !is_string($product_id)) {
            return new WP_Error('invalid_product_id', __('Product ID is required and must be a valid string.', 'skylearn-billing-pro'));
        }
        
        if (strlen($product_id) < 3) {
            return new WP_Error('invalid_product_id', __('Product ID must be at least 3 characters long.', 'skylearn-billing-pro'));
        }
        
        if (strlen($product_id) > 100) {
            return new WP_Error('invalid_product_id', __('Product ID must be 100 characters or less.', 'skylearn-billing-pro'));
        }
        
        // Validate course ID
        if (empty($course_id) || !is_numeric($course_id) || intval($course_id) <= 0) {
            return new WP_Error('invalid_course_id', __('Course ID is required and must be a positive integer.', 'skylearn-billing-pro'));
        }
        
        // Validate trigger type
        $valid_triggers = array('payment', 'webhook', 'manual', 'any');
        if (!in_array($trigger_type, $valid_triggers)) {
            return new WP_Error('invalid_trigger', __('Invalid trigger type specified.', 'skylearn-billing-pro'));
        }
        
        return true;
    }
    
    /**
     * Delete course mapping
     *
     * @param string $product_id Product ID
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    public function delete_course_mapping($product_id) {
        global $wpdb;
        
        try {
            // Use custom table if available, otherwise fallback to options
            if ($this->db_manager && $this->db_manager->tables_exist()) {
                return $this->delete_course_mapping_from_table($product_id);
            } else {
                return $this->delete_course_mapping_from_options($product_id);
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Exception during course mapping deletion - ' . $e->getMessage());
            return new WP_Error('exception', __('An unexpected error occurred while deleting the course mapping.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Delete course mapping from custom table
     *
     * @param string $product_id Product ID
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    private function delete_course_mapping_from_table($product_id) {
        global $wpdb;
        
        $table_name = $this->db_manager->get_course_mappings_table();
        
        // Get the mapping being deleted for logging
        $deleted_mapping = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE product_id = %s",
            $product_id
        ), ARRAY_A);
        
        if (!$deleted_mapping) {
            // Mapping doesn't exist - this could be considered successful deletion
            return true;
        }
        
        // Delete the mapping
        $result = $wpdb->delete(
            $table_name,
            array('product_id' => $product_id),
            array('%s')
        );
        
        if ($result === false) {
            $error_message = $wpdb->last_error ?: 'Unknown database error';
            error_log('SkyLearn Billing Pro: Failed to delete course mapping from custom table - ' . $error_message);
            return new WP_Error('delete_failed', sprintf(__('Failed to delete course mapping: %s', 'skylearn-billing-pro'), esc_html($error_message)));
        }
        
        if ($result === 0) {
            // No rows affected - mapping might have already been deleted
            return true;
        }
        
        // Verify the deletion was successful
        $verification = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE product_id = %s",
            $product_id
        ));
        
        if ($verification) {
            error_log('SkyLearn Billing Pro: Course mapping deletion verification failed - data still exists after delete');
            return new WP_Error('delete_failed', __('Failed to delete course mapping: Data verification failed.', 'skylearn-billing-pro'));
        }
        
        error_log(sprintf(
            'SkyLearn Billing Pro: Course mapping deleted from custom table - Product: %s',
            $product_id
        ));
        
        // Fire action hook
        do_action('skylearn_billing_pro_course_mapping_deleted', $product_id, $deleted_mapping);
        
        return true;
    }
    
    /**
     * Delete course mapping from options (fallback)
     *
     * @param string $product_id Product ID
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    private function delete_course_mapping_from_options($product_id) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['course_mappings'][$product_id])) {
            // Mapping doesn't exist - this could be considered successful deletion
            return true;
        }
        
        // Store the mapping being deleted for logging
        $deleted_mapping = $options['course_mappings'][$product_id];
        
        unset($options['course_mappings'][$product_id]);
        
        $result = update_option('skylearn_billing_pro_options', $options);
        
        if ($result === false) {
            global $wpdb;
            $last_error = $wpdb->last_error;
            
            error_log('SkyLearn Billing Pro: Failed to delete course mapping from options - ' . $last_error);
            
            if (!empty($last_error)) {
                return new WP_Error('delete_failed', sprintf(__('Failed to delete course mapping: Database error (%s).', 'skylearn-billing-pro'), esc_html($last_error)));
            } else {
                return new WP_Error('delete_failed', __('Failed to delete course mapping: WordPress unable to update options.', 'skylearn-billing-pro'));
            }
        }
        
        // Verify the deletion was successful
        $verification_options = get_option('skylearn_billing_pro_options', array());
        if (isset($verification_options['course_mappings'][$product_id])) {
            error_log('SkyLearn Billing Pro: Course mapping deletion verification failed - data still exists after delete');
            return new WP_Error('delete_failed', __('Failed to delete course mapping: Data verification failed.', 'skylearn-billing-pro'));
        }
        
        return true;
    }
    
    /**
     * Get course mapping by product ID
     *
     * @param string $product_id Product ID
     * @return array|false Course mapping or false
     */
    public function get_course_mapping($product_id) {
        global $wpdb;
        
        if ($this->db_manager && $this->db_manager->tables_exist()) {
            try {
                $table_name = $this->db_manager->get_course_mappings_table();
                
                $result = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM $table_name WHERE product_id = %s AND status = 'active'",
                    $product_id
                ), ARRAY_A);
                
                if ($wpdb->last_error) {
                    error_log('SkyLearn Billing Pro: Database error in get_course_mapping - ' . $wpdb->last_error);
                    return $this->get_course_mapping_from_options($product_id);
                }
                
                if ($result) {
                    // Format result to match expected structure
                    $mapping = array(
                        'product_id' => $result['product_id'],
                        'course_id' => intval($result['course_id']),
                        'trigger_type' => $result['trigger_type'],
                        'status' => $result['status'],
                        'created_at' => $result['created_at'],
                        'updated_at' => $result['updated_at']
                    );
                    
                    // Parse additional settings if they exist
                    if (!empty($result['additional_settings'])) {
                        $settings = json_decode($result['additional_settings'], true);
                        if (is_array($settings)) {
                            $mapping['settings'] = $settings;
                        }
                    }
                    
                    return $mapping;
                }
                
                return false;
                
            } catch (Exception $e) {
                error_log('SkyLearn Billing Pro: Error getting course mapping from database - ' . $e->getMessage());
                return $this->get_course_mapping_from_options($product_id);
            }
        }
        
        return $this->get_course_mapping_from_options($product_id);
    }
    
    /**
     * Fallback method to get course mapping from options
     *
     * @param string $product_id Product ID
     * @return array|false Course mapping or false
     */
    private function get_course_mapping_from_options($product_id) {
        $mappings = $this->get_course_mappings_from_options();
        return isset($mappings[$product_id]) ? $mappings[$product_id] : false;
    }
    
    /**
     * Get course mapping by course ID
     *
     * @param int $course_id Course ID
     * @return array Course mappings for this course
     */
    public function get_mappings_by_course($course_id) {
        $mappings = $this->get_course_mappings();
        $course_mappings = array();
        
        foreach ($mappings as $product_id => $mapping) {
            if ($mapping['course_id'] == $course_id) {
                $course_mappings[$product_id] = $mapping;
            }
        }
        
        return $course_mappings;
    }
    
    /**
     * Process enrollment for product purchase
     *
     * @param string $product_id Product ID
     * @param int $user_id User ID
     * @param string $trigger Trigger type
     * @return bool Success status
     */
    public function process_enrollment($product_id, $user_id, $trigger = 'payment') {
        if (!$this->lms_manager) {
            error_log('SkyLearn Billing Pro: LMS Manager not available for enrollment processing');
            return false;
        }
        
        try {
            $mapping = $this->get_course_mapping($product_id);
            
            if (!$mapping) {
                return false;
            }
            
            // Check if trigger matches
            if ($mapping['trigger_type'] !== $trigger && $mapping['trigger_type'] !== 'any') {
                return false;
            }
            
            // Check if mapping is active
            if (isset($mapping['status']) && $mapping['status'] !== 'active') {
                return false;
            }
            
            // Enroll user in course
            $result = $this->lms_manager->enroll_user($user_id, $mapping['course_id']);
            
            if ($result) {
                // Log successful enrollment
                $this->log_enrollment($product_id, $user_id, $mapping['course_id'], $trigger, 'success');
                
                // Fire action hook
                do_action('skylearn_billing_pro_course_mapping_enrolled', $user_id, $mapping['course_id'], $product_id, $trigger);
            } else {
                // Log failed enrollment
                $this->log_enrollment($product_id, $user_id, $mapping['course_id'], $trigger, 'failed');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error processing enrollment - ' . $e->getMessage());
            
            // Still try to log this failure if we have the required data
            if (isset($mapping) && isset($mapping['course_id'])) {
                $this->log_enrollment($product_id, $user_id, $mapping['course_id'], $trigger, 'failed');
            }
            
            return false;
        }
    }
    
    /**
     * Validate if course exists in active LMS
     *
     * @param int $course_id Course ID
     * @return bool
     */
    private function validate_course_exists($course_id) {
        if (!$this->lms_manager || !$this->lms_manager->has_active_lms()) {
            return false;
        }
        
        try {
            $course_details = $this->lms_manager->get_course_details($course_id);
            return $course_details !== false;
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error validating course existence - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log enrollment activity
     *
     * @param string $product_id Product ID
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @param string $trigger Trigger type
     * @param string $status Status (success/failed)
     * @param string $error_message Optional error message for failed enrollments
     */
    private function log_enrollment($product_id, $user_id, $course_id, $trigger, $status, $error_message = null) {
        global $wpdb;
        
        try {
            // Prepare log entry data
            $log_entry = array(
                'product_id' => $product_id,
                'user_id' => $user_id,
                'course_id' => $course_id,
                'trigger_type' => $trigger,
                'status' => $status,
                'user_email' => '',
                'course_title' => '',
                'error_message' => $error_message,
                'created_at' => current_time('mysql')
            );
            
            // Get user email
            $user = get_user_by('id', $user_id);
            if ($user) {
                $log_entry['user_email'] = $user->user_email;
            }
            
            // Get course title
            if ($this->lms_manager && $this->lms_manager->has_active_lms()) {
                try {
                    $course_details = $this->lms_manager->get_course_details($course_id);
                    if ($course_details) {
                        $log_entry['course_title'] = $course_details['title'];
                    }
                } catch (Exception $e) {
                    error_log('SkyLearn Billing Pro: Error getting course title for log - ' . $e->getMessage());
                }
            }
            
            // Use custom table if available, otherwise fallback to options
            if ($this->db_manager && $this->db_manager->tables_exist()) {
                $this->log_enrollment_to_table($log_entry);
            } else {
                $this->log_enrollment_to_options($log_entry);
            }
            
            // Also log to error log if debug is enabled
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'Skylearn Billing Pro - Course Mapping: Product %s, User %d, Course %d, Trigger %s, Status %s',
                    $product_id,
                    $user_id,
                    $course_id,
                    $trigger,
                    $status
                ));
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error logging enrollment activity - ' . $e->getMessage());
        }
    }
    
    /**
     * Log enrollment to custom table
     *
     * @param array $log_entry Log entry data
     */
    private function log_enrollment_to_table($log_entry) {
        global $wpdb;
        
        try {
            $table_name = $this->db_manager->get_enrollment_log_table();
            
            $result = $wpdb->insert(
                $table_name,
                $log_entry,
                array(
                    '%s', // product_id
                    '%d', // user_id
                    '%d', // course_id
                    '%s', // trigger_type
                    '%s', // status
                    '%s', // user_email
                    '%s', // course_title
                    '%s', // error_message
                    '%s'  // created_at
                )
            );
            
            if ($result === false) {
                error_log('SkyLearn Billing Pro: Failed to log enrollment to custom table - ' . ($wpdb->last_error ?: 'Unknown error'));
                // Fallback to options logging
                $this->log_enrollment_to_options($log_entry);
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error logging enrollment to custom table - ' . $e->getMessage());
            // Fallback to options logging
            $this->log_enrollment_to_options($log_entry);
        }
    }
    
    /**
     * Log enrollment to options (fallback)
     *
     * @param array $log_entry Log entry data
     */
    private function log_enrollment_to_options($log_entry) {
        try {
            // Convert to legacy format
            $legacy_entry = array(
                'timestamp' => $log_entry['created_at'],
                'product_id' => $log_entry['product_id'],
                'user_id' => $log_entry['user_id'],
                'course_id' => $log_entry['course_id'],
                'trigger' => $log_entry['trigger_type'],
                'status' => $log_entry['status'],
                'user_email' => $log_entry['user_email'],
                'course_title' => $log_entry['course_title']
            );
            
            // Save to enrollment log
            $options = get_option('skylearn_billing_pro_options', array());
            if (!isset($options['enrollment_log'])) {
                $options['enrollment_log'] = array();
            }
            
            $options['enrollment_log'][] = $legacy_entry;
            
            // Keep only last 1000 entries to prevent database bloat
            if (count($options['enrollment_log']) > 1000) {
                $options['enrollment_log'] = array_slice($options['enrollment_log'], -1000);
            }
            
            update_option('skylearn_billing_pro_options', $options);
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error logging enrollment to options - ' . $e->getMessage());
        }
    }
    
    /**
     * Get enrollment log
     *
     * @param int $limit Number of entries to return
     * @return array Enrollment log entries
     */
    public function get_enrollment_log($limit = 50) {
        global $wpdb;
        
        if ($this->db_manager && $this->db_manager->tables_exist()) {
            try {
                $table_name = $this->db_manager->get_enrollment_log_table();
                
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM $table_name ORDER BY created_at DESC LIMIT %d",
                    $limit
                ), ARRAY_A);
                
                if ($wpdb->last_error) {
                    error_log('SkyLearn Billing Pro: Database error in get_enrollment_log - ' . $wpdb->last_error);
                    return $this->get_enrollment_log_from_options($limit);
                }
                
                // Convert to legacy format for backwards compatibility
                $formatted_log = array();
                foreach ($results as $row) {
                    $formatted_log[] = array(
                        'timestamp' => $row['created_at'],
                        'product_id' => $row['product_id'],
                        'user_id' => intval($row['user_id']),
                        'course_id' => intval($row['course_id']),
                        'trigger' => $row['trigger_type'],
                        'status' => $row['status'],
                        'user_email' => $row['user_email'],
                        'course_title' => $row['course_title'],
                        'error_message' => $row['error_message']
                    );
                }
                
                return $formatted_log;
                
            } catch (Exception $e) {
                error_log('SkyLearn Billing Pro: Error getting enrollment log from database - ' . $e->getMessage());
                return $this->get_enrollment_log_from_options($limit);
            }
        }
        
        return $this->get_enrollment_log_from_options($limit);
    }
    
    /**
     * Get enrollment log from options (fallback)
     *
     * @param int $limit Number of entries to return
     * @return array Enrollment log entries
     */
    private function get_enrollment_log_from_options($limit) {
        $options = get_option('skylearn_billing_pro_options', array());
        $log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        // Return latest entries first
        $log = array_reverse($log);
        
        if ($limit > 0) {
            $log = array_slice($log, 0, $limit);
        }
        
        return $log;
    }
    
    /**
     * Render course mapping UI
     */
    public function render_mapping_ui() {
        // Check if LMS manager is available
        if (!$this->lms_manager) {
            $this->render_error_notice(__('LMS Manager could not be initialized. Please check your plugin configuration.', 'skylearn-billing-pro'));
            return;
        }
        
        try {
            $mappings = $this->get_course_mappings();
            $courses = array();
            $lms_status = array();
            
            // Get courses with error handling
            try {
                $courses = $this->lms_manager->get_courses();
                
                // Add debugging information when courses are retrieved
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('SkyLearn Billing Pro: Course Mapping UI - Retrieved ' . count($courses) . ' courses for dropdown');
                    if (empty($courses)) {
                        $integration_status = $this->lms_manager->get_integration_status();
                        error_log('SkyLearn Billing Pro: Course Mapping UI - No courses found. LMS Status: Active=' . 
                                  ($integration_status['active_lms'] ?: 'none') . 
                                  ', Connector=' . ($integration_status['has_active_connector'] ? 'yes' : 'no') .
                                  ', Count=' . $integration_status['course_count']);
                    }
                }
            } catch (Exception $e) {
                error_log('SkyLearn Billing Pro: Error getting courses - ' . $e->getMessage());
                $courses = array();
            }
            
            // Get LMS status with error handling
            try {
                $lms_status = $this->lms_manager->get_integration_status();
            } catch (Exception $e) {
                error_log('SkyLearn Billing Pro: Error getting LMS status - ' . $e->getMessage());
                $lms_status = array(
                    'detected_count' => 0,
                    'detected_lms' => array(),
                    'active_lms' => false,
                    'active_lms_name' => false,
                    'has_active_connector' => false,
                    'course_count' => 0
                );
            }
            
            // Fallback: If courses array is empty but status shows courses are available,
            // try to retrieve courses again - this helps with potential timing issues
            if (empty($courses) && isset($lms_status['course_count']) && $lms_status['course_count'] > 0) {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('SkyLearn Billing Pro: Course Mapping UI - Fallback: Retrying course retrieval. Status shows ' . 
                              $lms_status['course_count'] . ' courses but courses array is empty');
                }
                
                try {
                    // Give it one more try
                    $courses = $this->lms_manager->get_courses();
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SkyLearn Billing Pro: Course Mapping UI - Fallback retrieved ' . count($courses) . ' courses');
                    }
                } catch (Exception $e) {
                    error_log('SkyLearn Billing Pro: Course Mapping UI - Fallback course retrieval failed: ' . $e->getMessage());
                }
            }
            
            $this->render_mapping_ui_content($mappings, $courses, $lms_status);
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Critical error in render_mapping_ui - ' . $e->getMessage());
            $this->render_error_notice(__('An error occurred while loading the course mapping interface. Please check the error logs for more details.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Render error notice
     *
     * @param string $message Error message to display
     */
    private function render_error_notice($message) {
        ?>
        <div class="skylearn-billing-course-mapping">
            <div class="skylearn-billing-card">
                <div class="skylearn-billing-card-header">
                    <h3><?php esc_html_e('Course Mapping Error', 'skylearn-billing-pro'); ?></h3>
                </div>
                <div class="skylearn-billing-card-body">
                    <div class="skylearn-billing-notice skylearn-billing-notice-error">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong><?php esc_html_e('Error:', 'skylearn-billing-pro'); ?></strong>
                            <?php echo esc_html($message); ?>
                        </div>
                    </div>
                    <p><?php esc_html_e('Please try the following:', 'skylearn-billing-pro'); ?></p>
                    <ul>
                        <li><?php esc_html_e('Ensure you have a supported LMS plugin installed and activated', 'skylearn-billing-pro'); ?></li>
                        <li><?php esc_html_e('Check that your LMS is properly configured in the LMS Settings tab', 'skylearn-billing-pro'); ?></li>
                        <li><?php esc_html_e('Contact support if the problem persists', 'skylearn-billing-pro'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render the main course mapping UI content
     *
     * @param array $mappings Current course mappings
     * @param array $courses Available courses
     * @param array $lms_status LMS status information
     */
    private function render_mapping_ui_content($mappings, $courses, $lms_status) {
        
        
        ?>
        <div class="skylearn-billing-course-mapping">
            <!-- LMS Status Section -->
            <div class="skylearn-billing-card">
                <div class="skylearn-billing-card-header">
                    <h3><?php esc_html_e('LMS Integration Status', 'skylearn-billing-pro'); ?></h3>
                </div>
                <div class="skylearn-billing-card-body">
                    <?php if ($lms_status['detected_count'] === 0): ?>
                        <div class="skylearn-billing-notice skylearn-billing-notice-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <div>
                                <strong><?php esc_html_e('No LMS plugins detected', 'skylearn-billing-pro'); ?></strong><br>
                                <?php esc_html_e('Please install and activate a supported LMS plugin (LearnDash, TutorLMS, LifterLMS, or LearnPress) to enable course mapping.', 'skylearn-billing-pro'); ?>
                            </div>
                        </div>
                    <?php elseif (!$lms_status['active_lms']): ?>
                        <div class="skylearn-billing-notice skylearn-billing-notice-info">
                            <span class="dashicons dashicons-info"></span>
                            <div>
                                <strong><?php esc_html_e('LMS detected but not configured', 'skylearn-billing-pro'); ?></strong><br>
                                <?php printf(
                                    esc_html__('Found %d LMS plugin(s). Please select an active LMS in the LMS settings tab.', 'skylearn-billing-pro'),
                                    $lms_status['detected_count']
                                ); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="skylearn-billing-lms-status-grid">
                            <div class="skylearn-billing-status-item">
                                <span class="dashicons dashicons-admin-plugins"></span>
                                <div>
                                    <strong><?php esc_html_e('Active LMS:', 'skylearn-billing-pro'); ?></strong>
                                    <?php echo esc_html($lms_status['active_lms_name']); ?>
                                </div>
                            </div>
                            <div class="skylearn-billing-status-item">
                                <span class="dashicons dashicons-book"></span>
                                <div>
                                    <strong><?php esc_html_e('Available Courses:', 'skylearn-billing-pro'); ?></strong>
                                    <?php echo intval($lms_status['course_count']); ?>
                                </div>
                            </div>
                            <div class="skylearn-billing-status-item">
                                <span class="dashicons dashicons-admin-links"></span>
                                <div>
                                    <strong><?php esc_html_e('Active Mappings:', 'skylearn-billing-pro'); ?></strong>
                                    <?php echo count($mappings); ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($lms_status['has_active_connector']): ?>
                <!-- Add New Mapping Section -->
                <div class="skylearn-billing-card">
                    <div class="skylearn-billing-card-header">
                        <h3><?php esc_html_e('Add Course Mapping', 'skylearn-billing-pro'); ?></h3>
                        <p><?php esc_html_e('Map payment processor product IDs to LMS courses for automatic enrollment.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    <div class="skylearn-billing-card-body">
                        <form id="skylearn-course-mapping-form" class="skylearn-billing-form">
                            <div class="skylearn-billing-form-row">
                                <div class="skylearn-billing-form-group">
                                    <label for="product_id"><?php esc_html_e('Product ID', 'skylearn-billing-pro'); ?></label>
                                    <input type="text" id="product_id" name="product_id" class="regular-text" placeholder="<?php esc_attr_e('e.g., stripe_prod_123, ls_variant_456', 'skylearn-billing-pro'); ?>" required>
                                    <p class="description"><?php esc_html_e('Enter the product ID from your payment processor (Stripe, Lemon Squeezy, etc.).', 'skylearn-billing-pro'); ?></p>
                                </div>
                                
                                <div class="skylearn-billing-form-group">
                                    <label for="course_id"><?php esc_html_e('Course', 'skylearn-billing-pro'); ?></label>
                                    <select id="course_id" name="course_id" class="skylearn-billing-course-select" required>
                                        <?php if (empty($courses)): ?>
                                            <option value=""><?php esc_html_e('No courses available - check LMS settings', 'skylearn-billing-pro'); ?></option>
                                        <?php else: ?>
                                            <option value=""><?php esc_html_e('Select a course...', 'skylearn-billing-pro'); ?></option>
                                            <?php foreach ($courses as $course): ?>
                                                <option value="<?php echo esc_attr($course['id']); ?>">
                                                    <?php echo esc_html($course['title']); ?>
                                                    (ID: <?php echo esc_html($course['id']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                    <?php if (empty($courses)): ?>
                                        <p class="description" style="color: #d63638;">
                                            <?php esc_html_e('No courses found. Ensure your LMS is properly configured and contains published courses.', 'skylearn-billing-pro'); ?>
                                        </p>
                                    <?php else: ?>
                                        <p class="description">
                                            <?php printf(esc_html__('Select from %d available courses.', 'skylearn-billing-pro'), count($courses)); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="skylearn-billing-form-row">
                                <div class="skylearn-billing-form-group">
                                    <label for="trigger_type"><?php esc_html_e('Enrollment Trigger', 'skylearn-billing-pro'); ?></label>
                                    <select id="trigger_type" name="trigger_type">
                                        <option value="payment"><?php esc_html_e('Payment Completed', 'skylearn-billing-pro'); ?></option>
                                        <option value="webhook"><?php esc_html_e('Webhook Received', 'skylearn-billing-pro'); ?></option>
                                        <option value="manual"><?php esc_html_e('Manual Enrollment', 'skylearn-billing-pro'); ?></option>
                                        <option value="any"><?php esc_html_e('Any Event', 'skylearn-billing-pro'); ?></option>
                                    </select>
                                </div>
                                
                                <div class="skylearn-billing-form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="skylearn-billing-btn skylearn-billing-btn-primary">
                                        <?php esc_html_e('Add Mapping', 'skylearn-billing-pro'); ?>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Existing Mappings Section -->
                <div class="skylearn-billing-card">
                    <div class="skylearn-billing-card-header">
                        <h3><?php esc_html_e('Course Mappings', 'skylearn-billing-pro'); ?></h3>
                    </div>
                    <div class="skylearn-billing-card-body">
                        <?php if (empty($mappings)): ?>
                            <div class="skylearn-billing-empty-state">
                                <span class="dashicons dashicons-admin-links"></span>
                                <h4><?php esc_html_e('No course mappings yet', 'skylearn-billing-pro'); ?></h4>
                                <p><?php esc_html_e('Create your first course mapping to automatically enroll customers in courses after purchase.', 'skylearn-billing-pro'); ?></p>
                            </div>
                        <?php else: ?>
                            <div class="skylearn-billing-mappings-table">
                                <table class="wp-list-table widefat fixed striped">
                                    <thead>
                                        <tr>
                                            <th><?php esc_html_e('Product ID', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Course', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Trigger', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Created', 'skylearn-billing-pro'); ?></th>
                                            <th><?php esc_html_e('Actions', 'skylearn-billing-pro'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mappings as $product_id => $mapping): ?>
                                            <?php
                                            $course_details = null;
                                            $course_title = __('Course not found', 'skylearn-billing-pro');
                                            
                                            // Get course details with error handling
                                            try {
                                                if ($this->lms_manager && $this->lms_manager->has_active_lms()) {
                                                    $course_details = $this->lms_manager->get_course_details($mapping['course_id']);
                                                    if ($course_details) {
                                                        $course_title = $course_details['title'];
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                error_log('SkyLearn Billing Pro: Error getting course details for mapping - ' . $e->getMessage());
                                            }
                                            ?>
                                            <tr data-product-id="<?php echo esc_attr($product_id); ?>">
                                                <td>
                                                    <strong><?php echo esc_html($product_id); ?></strong>
                                                </td>
                                                <td>
                                                    <?php echo esc_html($course_title); ?>
                                                    <br><small>ID: <?php echo esc_html($mapping['course_id']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="skylearn-billing-badge">
                                                        <?php echo esc_html(ucfirst($mapping['trigger_type'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (isset($mapping['status']) && $mapping['status'] === 'active'): ?>
                                                        <span class="skylearn-billing-status-active"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                                                    <?php else: ?>
                                                        <span class="skylearn-billing-status-inactive"><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (isset($mapping['created_at'])) {
                                                        echo esc_html(date_i18n(get_option('date_format'), strtotime($mapping['created_at'])));
                                                    } else {
                                                        esc_html_e('Unknown', 'skylearn-billing-pro');
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <button class="button button-small skylearn-billing-delete-mapping" data-product-id="<?php echo esc_attr($product_id); ?>">
                                                        <?php esc_html_e('Delete', 'skylearn-billing-pro'); ?>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for saving course mapping
     */
    public function ajax_save_course_mapping() {
        try {
            // Verify nonce and permissions
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'skylearn_course_mapping_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security verification failed. Please refresh the page and try again.', 'skylearn-billing-pro'),
                    'code' => 'invalid_nonce'
                ));
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('You do not have permission to perform this action.', 'skylearn-billing-pro'),
                    'code' => 'insufficient_permissions'
                ));
            }
            
            // Sanitize and validate inputs
            $product_id = sanitize_text_field($_POST['product_id'] ?? '');
            $course_id = intval($_POST['course_id'] ?? 0);
            $trigger_type = sanitize_text_field($_POST['trigger_type'] ?? 'payment');
            
            // Basic validation
            if (empty($product_id)) {
                wp_send_json_error(array(
                    'message' => __('Product ID is required.', 'skylearn-billing-pro'),
                    'code' => 'missing_product_id'
                ));
            }
            
            if (empty($course_id)) {
                wp_send_json_error(array(
                    'message' => __('Please select a course.', 'skylearn-billing-pro'),
                    'code' => 'missing_course_id'
                ));
            }
            
            // Attempt to save the mapping
            $result = $this->save_course_mapping($product_id, $course_id, $trigger_type);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code(),
                    'details' => array(
                        'product_id' => $product_id,
                        'course_id' => $course_id,
                        'trigger_type' => $trigger_type
                    )
                ));
            }
            
            if ($result === true) {
                wp_send_json_success(array(
                    'message' => __('Course mapping saved successfully!', 'skylearn-billing-pro'),
                    'mapping' => array(
                        'product_id' => $product_id,
                        'course_id' => $course_id,
                        'trigger_type' => $trigger_type
                    )
                ));
            } else {
                // This shouldn't happen with the new logic, but just in case
                error_log('SkyLearn Billing Pro: Unexpected return value from save_course_mapping: ' . var_export($result, true));
                wp_send_json_error(array(
                    'message' => __('Failed to save course mapping. Please try again.', 'skylearn-billing-pro'),
                    'code' => 'unexpected_result'
                ));
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error in ajax_save_course_mapping - ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An unexpected error occurred. Please try again or contact support if the problem persists.', 'skylearn-billing-pro'),
                'code' => 'exception'
            ));
        }
    }
    
    /**
     * AJAX handler for deleting course mapping
     */
    public function ajax_delete_course_mapping() {
        try {
            // Verify nonce and permissions
            if (!wp_verify_nonce($_POST['nonce'] ?? '', 'skylearn_course_mapping_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Security verification failed. Please refresh the page and try again.', 'skylearn-billing-pro'),
                    'code' => 'invalid_nonce'
                ));
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => __('You do not have permission to perform this action.', 'skylearn-billing-pro'),
                    'code' => 'insufficient_permissions'
                ));
            }
            
            $product_id = sanitize_text_field($_POST['product_id'] ?? '');
            
            if (empty($product_id)) {
                wp_send_json_error(array(
                    'message' => __('Product ID is required for deletion.', 'skylearn-billing-pro'),
                    'code' => 'missing_product_id'
                ));
            }
            
            // Check if mapping exists before attempting deletion
            $existing_mapping = $this->get_course_mapping($product_id);
            if (!$existing_mapping) {
                wp_send_json_error(array(
                    'message' => __('Course mapping not found. It may have already been deleted.', 'skylearn-billing-pro'),
                    'code' => 'mapping_not_found'
                ));
            }
            
            $result = $this->delete_course_mapping($product_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message(),
                    'code' => $result->get_error_code()
                ));
            } elseif ($result === true) {
                // Log successful deletion
                error_log(sprintf(
                    'SkyLearn Billing Pro: Course mapping deleted - Product: %s',
                    $product_id
                ));
                
                // Fire action hook
                do_action('skylearn_billing_pro_course_mapping_deleted', $product_id, $existing_mapping);
                
                wp_send_json_success(array(
                    'message' => __('Course mapping deleted successfully.', 'skylearn-billing-pro'),
                    'product_id' => $product_id
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to delete course mapping. Please try again.', 'skylearn-billing-pro'),
                    'code' => 'delete_failed'
                ));
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error in ajax_delete_course_mapping - ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An unexpected error occurred while deleting the mapping.', 'skylearn-billing-pro'),
                'code' => 'exception'
            ));
        }
    }
    
    /**
     * AJAX handler for searching courses
     */
    public function ajax_search_courses() {
        try {
            check_ajax_referer('skylearn_course_mapping_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_die(__('Unauthorized', 'skylearn-billing-pro'));
            }
            
            $search = sanitize_text_field($_POST['search']);
            
            if (!$this->lms_manager || !$this->lms_manager->has_active_lms()) {
                wp_send_json_error(array('message' => __('No active LMS available.', 'skylearn-billing-pro')));
                return;
            }
            
            $courses = $this->lms_manager->get_courses();
            
            $filtered_courses = array();
            
            foreach ($courses as $course) {
                if (empty($search) || stripos($course['title'], $search) !== false) {
                    $filtered_courses[] = $course;
                }
            }
            
            wp_send_json_success($filtered_courses);
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error in ajax_search_courses - ' . $e->getMessage());
            wp_send_json_error(array('message' => __('An error occurred while searching courses.', 'skylearn-billing-pro')));
        }
    }
}

/**
 * Get the Course Mapping instance
 *
 * @return SkyLearn_Billing_Pro_Course_Mapping
 */
function skylearn_billing_pro_course_mapping() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Course_Mapping();
    }
    
    return $instance;
}