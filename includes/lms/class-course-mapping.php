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
     * Constructor
     */
    public function __construct() {
        // Initialize LMS manager with error handling
        try {
            $this->lms_manager = skylearn_billing_pro_lms_manager();
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Failed to initialize LMS Manager in Course Mapping - ' . $e->getMessage());
            $this->lms_manager = null;
        }
        
        // Only add AJAX handlers if LMS manager is available
        if ($this->lms_manager) {
            add_action('wp_ajax_skylearn_billing_save_course_mapping', array($this, 'ajax_save_course_mapping'));
            add_action('wp_ajax_skylearn_billing_delete_course_mapping', array($this, 'ajax_delete_course_mapping'));
            add_action('wp_ajax_skylearn_billing_search_courses', array($this, 'ajax_search_courses'));
        }
    }
    
    /**
     * Get all course mappings
     *
     * @return array Course mappings
     */
    public function get_course_mappings() {
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
            
            $options = get_option('skylearn_billing_pro_options', array());
            
            if (!isset($options['course_mappings'])) {
                $options['course_mappings'] = array();
            }
            
            // Check for duplicate mapping
            if (isset($options['course_mappings'][$product_id])) {
                return new WP_Error('duplicate_mapping', __('A mapping for this product ID already exists.', 'skylearn-billing-pro'));
            }
            
            // Validate course exists
            if (!$this->validate_course_exists($course_id)) {
                return new WP_Error('invalid_course', __('The selected course does not exist or is not accessible.', 'skylearn-billing-pro'));
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
            
            // Save mapping (using product_id as key for easy lookup)
            $options['course_mappings'][$product_id] = $mapping_data;
            
            // Get current options to compare and detect if update is actually needed
            $current_options = get_option('skylearn_billing_pro_options', array());
            $current_mappings = isset($current_options['course_mappings']) ? $current_options['course_mappings'] : array();
            
            // Check if this is actually a new mapping or if data has changed
            $is_new_mapping = !isset($current_mappings[$product_id]);
            $data_changed = $is_new_mapping || ($current_mappings[$product_id] !== $mapping_data);
            
            if (!$data_changed) {
                // Data hasn't changed, but this is still considered a successful operation
                error_log('SkyLearn Billing Pro: Course mapping for product ' . $product_id . ' already exists with identical data - no update needed');
                return true;
            }
            
            // Check data size before saving to prevent MySQL max_allowed_packet issues
            $options_size = strlen(serialize($options));
            if ($options_size > 1048576) { // 1MB limit to be safe
                error_log('SkyLearn Billing Pro: Course mapping data size warning - ' . $options_size . ' bytes, ' . count($options['course_mappings']) . ' mappings');
                
                if ($options_size > 16777216) { // 16MB - MySQL default max_allowed_packet
                    return new WP_Error('save_failed', __('Failed to save course mapping: Too much data stored. Please contact support to optimize your data.', 'skylearn-billing-pro'));
                }
            }
            
            // Attempt to save the options with detailed error logging
            $result = update_option('skylearn_billing_pro_options', $options);
            
            if ($result === false) {
                // Get more specific error information
                global $wpdb;
                $last_error = $wpdb->last_error;
                
                $error_details = array(
                    'wpdb_error' => $last_error,
                    'product_id' => $product_id,
                    'course_id' => $course_id,
                    'data_size' => strlen(serialize($options)),
                    'mapping_count' => count($options['course_mappings'])
                );
                
                error_log('SkyLearn Billing Pro: Failed to save course mapping to database - Details: ' . json_encode($error_details));
                
                // Try to determine the specific cause of failure
                if (!empty($last_error)) {
                    if (strpos($last_error, 'max_allowed_packet') !== false) {
                        return new WP_Error('save_failed', __('Failed to save course mapping: Data too large for database. Please contact support.', 'skylearn-billing-pro'));
                    } elseif (strpos($last_error, 'Deadlock') !== false) {
                        return new WP_Error('save_failed', __('Failed to save course mapping: Database temporarily busy. Please try again in a moment.', 'skylearn-billing-pro'));
                    } elseif (strpos($last_error, 'Access denied') !== false || strpos($last_error, 'permission') !== false) {
                        return new WP_Error('save_failed', __('Failed to save course mapping: Database permission error. Please contact your administrator.', 'skylearn-billing-pro'));
                    } else {
                        return new WP_Error('save_failed', sprintf(__('Failed to save course mapping: Database error (%s). Please contact support.', 'skylearn-billing-pro'), esc_html($last_error)));
                    }
                } else {
                    // Generic failure - could be a WordPress issue
                    return new WP_Error('save_failed', __('Failed to save course mapping: WordPress unable to update options. Please check your database connection and try again.', 'skylearn-billing-pro'));
                }
            }
            
            // Verify the save was successful by reading it back
            $verification_options = get_option('skylearn_billing_pro_options', array());
            if (!isset($verification_options['course_mappings'][$product_id]) || 
                $verification_options['course_mappings'][$product_id]['course_id'] != $course_id) {
                
                error_log('SkyLearn Billing Pro: Course mapping save verification failed - data not found after save');
                return new WP_Error('save_failed', __('Failed to save course mapping: Data verification failed. Please try again or contact support.', 'skylearn-billing-pro'));
            }
            
            // Log successful mapping creation
            error_log(sprintf(
                'SkyLearn Billing Pro: Course mapping created - Product: %s, Course: %d, Trigger: %s',
                $product_id,
                $course_id,
                $trigger_type
            ));
            
            // Fire action hook for other plugins to use
            do_action('skylearn_billing_pro_course_mapping_saved', $product_id, $course_id, $trigger_type, $mapping_data);
            
            return true;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error saving course mapping - ' . $e->getMessage());
            return new WP_Error('exception', __('An unexpected error occurred while saving the course mapping.', 'skylearn-billing-pro'));
        }
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
        try {
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
                // Get more specific error information
                global $wpdb;
                $last_error = $wpdb->last_error;
                
                error_log('SkyLearn Billing Pro: Failed to delete course mapping from database - Product ID: ' . $product_id . ', Error: ' . $last_error);
                
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
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Exception during course mapping deletion - ' . $e->getMessage());
            return new WP_Error('exception', __('An unexpected error occurred while deleting the course mapping.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Get course mapping by product ID
     *
     * @param string $product_id Product ID
     * @return array|false Course mapping or false
     */
    public function get_course_mapping($product_id) {
        $mappings = $this->get_course_mappings();
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
     */
    private function log_enrollment($product_id, $user_id, $course_id, $trigger, $status) {
        try {
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'product_id' => $product_id,
                'user_id' => $user_id,
                'course_id' => $course_id,
                'trigger' => $trigger,
                'status' => $status,
                'user_email' => '',
                'course_title' => ''
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
            
            // Save to enrollment log
            $options = get_option('skylearn_billing_pro_options', array());
            if (!isset($options['enrollment_log'])) {
                $options['enrollment_log'] = array();
            }
            
            $options['enrollment_log'][] = $log_entry;
            
            // Keep only last 1000 entries to prevent database bloat
            if (count($options['enrollment_log']) > 1000) {
                $options['enrollment_log'] = array_slice($options['enrollment_log'], -1000);
            }
            
            update_option('skylearn_billing_pro_options', $options);
            
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
     * Get enrollment log
     *
     * @param int $limit Number of entries to return
     * @return array Enrollment log entries
     */
    public function get_enrollment_log($limit = 50) {
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