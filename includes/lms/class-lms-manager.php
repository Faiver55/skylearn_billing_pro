<?php
/**
 * LMS Manager class for detecting and managing LMS integrations
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
 * LMS Manager class
 */
class SkyLearn_Billing_Pro_LMS_Manager {
    
    /**
     * Supported LMS plugins
     *
     * @var array
     */
    private $supported_lms = array(
        'learndash' => array(
            'name' => 'LearnDash',
            'plugin_path' => 'sfwd-lms/sfwd_lms.php',
            'alternative_paths' => array(
                'learndash/learndash.php',
                'learndash-core/learndash-core.php',
                'sfwd-lms/sfwd-lms.php'
            ),
            'class_name' => 'SFWD_LMS',
            'alternative_classes' => array(
                'LearnDash_Settings_Section',
                'learndash_LMS',
                'LDLMS_Post_Types'
            ),
            'function_name' => 'learndash_get_course_id',
            'alternative_functions' => array(
                'learndash_get_courses',
                'learndash_user_get_enrolled_courses',
                'learndash_is_active'
            ),
            'connector_class' => 'SkyLearn_Billing_Pro_LearnDash_Connector',
            'manual_override' => false
        ),
        'tutor' => array(
            'name' => 'TutorLMS',
            'plugin_path' => 'tutor/tutor.php',
            'class_name' => 'TUTOR',
            'function_name' => 'tutor_get_course_id',
            'connector_class' => 'SkyLearn_Billing_Pro_Tutor_Connector',
            'manual_override' => false
        ),
        'lifter' => array(
            'name' => 'LifterLMS',
            'plugin_path' => 'lifterlms/lifterlms.php',
            'class_name' => 'LifterLMS',
            'function_name' => 'llms_get_course',
            'connector_class' => 'SkyLearn_Billing_Pro_Lifter_Connector',
            'manual_override' => false
        ),
        'learnpress' => array(
            'name' => 'LearnPress',
            'plugin_path' => 'learnpress/learnpress.php',
            'class_name' => 'LearnPress',
            'function_name' => 'learn_press_get_course',
            'connector_class' => 'SkyLearn_Billing_Pro_LearnPress_Connector',
            'manual_override' => false
        )
    );
    
    /**
     * Detected LMS plugins
     *
     * @var array
     */
    private $detected_lms = null;
    
    /**
     * Active LMS connector
     *
     * @var object
     */
    private $active_connector = null;
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize LMS Manager
     */
    public function init() {
        $this->load_manual_overrides();
        $this->detect_lms_plugins();
        $this->auto_set_active_lms();
        $this->load_active_connector();
    }
    
    /**
     * Detect installed LMS plugins
     *
     * @return array List of detected LMS plugins
     */
    public function detect_lms_plugins() {
        if ($this->detected_lms !== null) {
            return $this->detected_lms;
        }
        
        $this->detected_lms = array();
        
        foreach ($this->supported_lms as $lms_key => $lms_data) {
            if ($this->is_lms_active($lms_key)) {
                $this->detected_lms[$lms_key] = $lms_data;
            }
        }
        
        return $this->detected_lms;
    }
    
    /**
     * Check if a specific LMS is active
     *
     * @param string $lms_key LMS key to check
     * @return bool
     */
    public function is_lms_active($lms_key) {
        if (!isset($this->supported_lms[$lms_key])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: Unknown LMS key: {$lms_key}");
            }
            return false;
        }
        
        $lms_data = $this->supported_lms[$lms_key];
        
        // Add comprehensive debugging information
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SkyLearn Billing Pro: Checking if {$lms_key} is active - primary plugin_path: {$lms_data['plugin_path']}");
        }
        
        // Check manual override first
        if ($this->get_lms_manual_override($lms_key)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: {$lms_key} detected as active via manual override");
            }
            return true;
        }
        
        // Ensure is_plugin_active function is available
        if (!function_exists('is_plugin_active')) {
            $plugin_file = defined('ABSPATH') ? ABSPATH . 'wp-admin/includes/plugin.php' : '';
            if ($plugin_file && file_exists($plugin_file)) {
                include_once($plugin_file);
            }
        }
        
        // Check primary plugin path
        if (function_exists('is_plugin_active') && is_plugin_active($lms_data['plugin_path'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: {$lms_key} detected as active via primary plugin path: {$lms_data['plugin_path']}");
            }
            return true;
        }
        
        // Check alternative plugin paths
        if (isset($lms_data['alternative_paths']) && is_array($lms_data['alternative_paths'])) {
            foreach ($lms_data['alternative_paths'] as $alt_path) {
                if (function_exists('is_plugin_active') && is_plugin_active($alt_path)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("SkyLearn Billing Pro: {$lms_key} detected as active via alternative plugin path: {$alt_path}");
                    }
                    return true;
                }
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: {$lms_key} not found in any alternative plugin paths: " . implode(', ', $lms_data['alternative_paths']));
            }
        }
        
        // Check primary class
        if (isset($lms_data['class_name']) && class_exists($lms_data['class_name'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: {$lms_key} detected as active via primary class existence: {$lms_data['class_name']}");
            }
            return true;
        }
        
        // Check alternative classes
        if (isset($lms_data['alternative_classes']) && is_array($lms_data['alternative_classes'])) {
            foreach ($lms_data['alternative_classes'] as $alt_class) {
                if (class_exists($alt_class)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("SkyLearn Billing Pro: {$lms_key} detected as active via alternative class existence: {$alt_class}");
                    }
                    return true;
                }
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: {$lms_key} not found in any alternative classes: " . implode(', ', $lms_data['alternative_classes']));
            }
        }
        
        // Check primary function
        if (isset($lms_data['function_name']) && function_exists($lms_data['function_name'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: {$lms_key} detected as active via primary function existence: {$lms_data['function_name']}");
            }
            return true;
        }
        
        // Check alternative functions
        if (isset($lms_data['alternative_functions']) && is_array($lms_data['alternative_functions'])) {
            foreach ($lms_data['alternative_functions'] as $alt_function) {
                if (function_exists($alt_function)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log("SkyLearn Billing Pro: {$lms_key} detected as active via alternative function existence: {$alt_function}");
                    }
                    return true;
                }
            }
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: {$lms_key} not found in any alternative functions: " . implode(', ', $lms_data['alternative_functions']));
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SkyLearn Billing Pro: {$lms_key} not detected as active - no detection method succeeded");
        }
        
        return false;
    }
    
    /**
     * Get all detected LMS plugins
     *
     * @return array
     */
    public function get_detected_lms() {
        return $this->detect_lms_plugins();
    }
    
    /**
     * Get all supported LMS plugins
     *
     * @return array
     */
    public function get_supported_lms() {
        return $this->supported_lms;
    }
    
    /**
     * Get active LMS from settings
     *
     * @return string|false
     */
    public function get_active_lms() {
        $options = get_option('skylearn_billing_pro_options', array());
        return isset($options['lms_settings']['active_lms']) ? $options['lms_settings']['active_lms'] : false;
    }
    
    /**
     * Set active LMS in settings
     *
     * @param string $lms_key LMS key to set as active
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    public function set_active_lms($lms_key) {
        if (!isset($this->supported_lms[$lms_key])) {
            return new WP_Error('invalid_lms', __('Invalid LMS specified.', 'skylearn-billing-pro'));
        }
        
        if (!$this->is_lms_active($lms_key)) {
            return new WP_Error('lms_not_available', __('The specified LMS is not installed or activated.', 'skylearn-billing-pro'));
        }
        
        try {
            $options = get_option('skylearn_billing_pro_options', array());
            $current_active = $this->get_active_lms();
            
            if (!isset($options['lms_settings'])) {
                $options['lms_settings'] = array();
            }
            
            // Check if this is actually a change
            if ($current_active === $lms_key) {
                // Already set to this LMS, no change needed
                return true;
            }
            
            $options['lms_settings']['active_lms'] = $lms_key;
            
            $result = update_option('skylearn_billing_pro_options', $options);
            
            if ($result === false) {
                global $wpdb;
                $last_error = $wpdb->last_error;
                
                error_log('SkyLearn Billing Pro: Failed to set active LMS - LMS: ' . $lms_key . ', Error: ' . $last_error);
                
                if (!empty($last_error)) {
                    return new WP_Error('save_failed', sprintf(__('Failed to set active LMS: Database error (%s).', 'skylearn-billing-pro'), esc_html($last_error)));
                } else {
                    return new WP_Error('save_failed', __('Failed to set active LMS: WordPress unable to update settings.', 'skylearn-billing-pro'));
                }
            }
            
            // Verify the change was successful
            $verification_options = get_option('skylearn_billing_pro_options', array());
            if (!isset($verification_options['lms_settings']['active_lms']) || 
                $verification_options['lms_settings']['active_lms'] !== $lms_key) {
                
                error_log('SkyLearn Billing Pro: LMS setting verification failed - active LMS not set correctly');
                return new WP_Error('save_failed', __('Failed to set active LMS: Data verification failed.', 'skylearn-billing-pro'));
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Exception setting active LMS - ' . $e->getMessage());
            return new WP_Error('exception', __('An unexpected error occurred while setting the active LMS.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Load the active LMS connector
     */
    private function load_active_connector() {
        try {
            $active_lms = $this->get_active_lms();
            
            if (!$active_lms || !isset($this->supported_lms[$active_lms])) {
                return;
            }
            
            if (!$this->is_lms_active($active_lms)) {
                return;
            }
            
            $connector_class = $this->supported_lms[$active_lms]['connector_class'];
            $connector_file = SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-' . $active_lms . '.php';
            
            if (file_exists($connector_file)) {
                require_once $connector_file;
                
                if (class_exists($connector_class)) {
                    $this->active_connector = new $connector_class();
                } else {
                    error_log('SkyLearn Billing Pro: Connector class ' . $connector_class . ' not found');
                }
            } else {
                error_log('SkyLearn Billing Pro: Connector file not found: ' . $connector_file);
            }
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error loading active connector - ' . $e->getMessage());
            $this->active_connector = null;
        }
    }
    
    /**
     * Auto-set the first detected LMS as active if none is currently set
     */
    private function auto_set_active_lms() {
        $current_active = $this->get_active_lms();
        
        // If there's already an active LMS set, don't change it
        if ($current_active && $this->is_lms_active($current_active)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: Active LMS already set to {$current_active}");
            }
            return;
        }
        
        // Get detected LMS plugins
        $detected_lms = $this->get_detected_lms();
        
        if (empty($detected_lms)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SkyLearn Billing Pro: No LMS plugins detected for auto-setting');
            }
            return;
        }
        
        // Set the first detected LMS as active
        $first_lms = array_keys($detected_lms)[0];
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("SkyLearn Billing Pro: Auto-setting {$first_lms} as active LMS");
        }
        
        $this->set_active_lms($first_lms);
    }
    
    /**
     * Get the active LMS connector
     *
     * @return object|null
     */
    public function get_active_connector() {
        return $this->active_connector;
    }
    
    /**
     * Check if an active LMS is available
     *
     * @return bool
     */
    public function has_active_lms() {
        return $this->active_connector !== null;
    }
    
    /**
     * Get all courses from the active LMS
     *
     * @return array
     */
    public function get_courses() {
        if (!$this->has_active_lms()) {
            return array();
        }
        
        try {
            return $this->active_connector->get_courses();
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error getting courses from LMS - ' . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Enroll user in course
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Success status
     */
    public function enroll_user($user_id, $course_id) {
        if (!$this->has_active_lms()) {
            return false;
        }
        
        try {
            return $this->active_connector->enroll_user($user_id, $course_id);
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error enrolling user in course - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Unenroll user from course
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Success status
     */
    public function unenroll_user($user_id, $course_id) {
        if (!$this->has_active_lms()) {
            return false;
        }
        
        try {
            return $this->active_connector->unenroll_user($user_id, $course_id);
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error unenrolling user from course - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if user is enrolled in course
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Enrollment status
     */
    public function is_user_enrolled($user_id, $course_id) {
        if (!$this->has_active_lms()) {
            return false;
        }
        
        try {
            return $this->active_connector->is_user_enrolled($user_id, $course_id);
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error checking user enrollment - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get course details
     *
     * @param int $course_id Course ID
     * @return array|false Course details or false
     */
    public function get_course_details($course_id) {
        if (!$this->has_active_lms()) {
            return false;
        }
        
        try {
            return $this->active_connector->get_course_details($course_id);
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error getting course details - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get LMS integration status for admin dashboard
     *
     * @return array
     */
    public function get_integration_status() {
        try {
            $detected = $this->get_detected_lms();
            $active = $this->get_active_lms();
            
            $course_count = 0;
            $course_error = null;
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('SkyLearn Billing Pro: get_integration_status() - Detected LMS count: ' . count($detected));
                error_log('SkyLearn Billing Pro: get_integration_status() - Active LMS: ' . ($active ? $active : 'none'));
                error_log('SkyLearn Billing Pro: get_integration_status() - Has active connector: ' . ($this->has_active_lms() ? 'yes' : 'no'));
            }
            
            if ($this->has_active_lms()) {
                try {
                    $courses = $this->get_courses();
                    $course_count = count($courses);
                    
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SkyLearn Billing Pro: get_integration_status() - Course count retrieved: ' . $course_count);
                    }
                    
                    // Add additional debugging for zero courses
                    if ($course_count === 0) {
                        error_log('SkyLearn Billing Pro: get_courses() returned 0 courses - checking active connector');
                        if ($this->active_connector) {
                            error_log('SkyLearn Billing Pro: Active connector exists, class: ' . get_class($this->active_connector));
                            
                            // Test LearnDash detection directly (now using public method)
                            if (method_exists($this->active_connector, 'is_learndash_active')) {
                                $is_ld_active = $this->active_connector->is_learndash_active();
                                error_log('SkyLearn Billing Pro: LearnDash active status: ' . ($is_ld_active ? 'YES' : 'NO'));
                            }
                        } else {
                            error_log('SkyLearn Billing Pro: No active connector found');
                        }
                    }
                } catch (Exception $e) {
                    $course_error = $e->getMessage();
                    error_log('SkyLearn Billing Pro: Error getting course count for integration status - ' . $e->getMessage());
                }
            } else {
                error_log('SkyLearn Billing Pro: No active LMS connector available');
                // Try to reload the connector as a recovery attempt
                if ($active && $this->is_lms_active($active)) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('SkyLearn Billing Pro: Attempting to reload active connector for ' . $active);
                    }
                    $this->load_active_connector();
                    
                    // Retry getting courses if connector loaded successfully
                    if ($this->has_active_lms()) {
                        try {
                            $courses = $this->get_courses();
                            $course_count = count($courses);
                            if (defined('WP_DEBUG') && WP_DEBUG) {
                                error_log('SkyLearn Billing Pro: After reload - Course count: ' . $course_count);
                            }
                        } catch (Exception $e) {
                            $course_error = $e->getMessage();
                            error_log('SkyLearn Billing Pro: Error getting courses after reload - ' . $e->getMessage());
                        }
                    }
                }
            }
            
            $status = array(
                'detected_count' => count($detected),
                'detected_lms' => $detected,
                'active_lms' => $active,
                'active_lms_name' => $active && isset($this->supported_lms[$active]) ? $this->supported_lms[$active]['name'] : false,
                'has_active_connector' => $this->has_active_lms(),
                'course_count' => $course_count
            );
            
            // Add error info for debugging
            if ($course_error) {
                $status['course_error'] = $course_error;
            }
            
            return $status;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Error getting integration status - ' . $e->getMessage());
            
            // Return safe default status
            return array(
                'detected_count' => 0,
                'detected_lms' => array(),
                'active_lms' => false,
                'active_lms_name' => false,
                'has_active_connector' => false,
                'course_count' => 0,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Check if a specific LMS is available (alias for is_lms_active for backward compatibility)
     *
     * @param string $lms_key LMS key to check
     * @return bool
     */
    public function is_lms_available($lms_key) {
        return $this->is_lms_active($lms_key);
    }
    
    /**
     * Get the active LMS connector (alias for get_active_connector for backward compatibility)
     *
     * @return object|null
     */
    public function get_lms_connector() {
        return $this->get_active_connector();
    }
    
    /**
     * Enroll user in course (wrapper method)
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Success status
     */
    public function enroll_user_in_course($user_id, $course_id) {
        return $this->enroll_user($user_id, $course_id);
    }
    
    /**
     * Validate course ID
     *
     * @param int|string $course_id Course ID to validate
     * @return bool
     */
    public function validate_course_id($course_id) {
        // Basic validation
        if (empty($course_id) || !is_numeric($course_id) || intval($course_id) <= 0) {
            return false;
        }
        
        // If no active LMS, can't validate further
        if (!$this->has_active_lms()) {
            return false;
        }
        
        // Try to get course details to validate existence
        $course_details = $this->get_course_details(intval($course_id));
        return $course_details !== false;
    }
    
    /**
     * Get course information (wrapper for get_course_details)
     *
     * @param int $course_id Course ID
     * @return array|null Course details or null
     */
    public function get_course_info($course_id) {
        $details = $this->get_course_details($course_id);
        return $details !== false ? $details : null;
    }
    
    /**
     * Get LMS status
     *
     * @return array LMS status information
     */
    public function get_lms_status() {
        $detected = $this->get_detected_lms();
        $active = $this->get_active_lms();
        
        return array(
            'active_lms' => $active,
            'detected_lms' => array_keys($detected),
            'available_lms' => array_keys($this->supported_lms)
        );
    }
    
    /**
     * Set manual override for an LMS
     *
     * @param string $lms_key LMS key to override
     * @param bool $override Whether to enable override
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    public function set_lms_manual_override($lms_key, $override = true) {
        if (!isset($this->supported_lms[$lms_key])) {
            return new WP_Error('invalid_lms', __('Invalid LMS specified for manual override.', 'skylearn-billing-pro'));
        }
        
        try {
            // Update in-memory setting
            $this->supported_lms[$lms_key]['manual_override'] = $override;
            
            // Save to WordPress options
            $options = get_option('skylearn_billing_pro_options', array());
            $current_override = $this->get_lms_manual_override($lms_key);
            
            if (!isset($options['lms_settings'])) {
                $options['lms_settings'] = array();
            }
            if (!isset($options['lms_settings']['manual_overrides'])) {
                $options['lms_settings']['manual_overrides'] = array();
            }
            
            // Check if this is actually a change
            if ($current_override === $override) {
                // Already set to this value, no change needed
                return true;
            }
            
            $options['lms_settings']['manual_overrides'][$lms_key] = $override;
            
            $result = update_option('skylearn_billing_pro_options', $options);
            
            if ($result === false) {
                global $wpdb;
                $last_error = $wpdb->last_error;
                
                error_log('SkyLearn Billing Pro: Failed to set manual override - LMS: ' . $lms_key . ', Override: ' . ($override ? 'true' : 'false') . ', Error: ' . $last_error);
                
                if (!empty($last_error)) {
                    return new WP_Error('save_failed', sprintf(__('Failed to set manual override: Database error (%s).', 'skylearn-billing-pro'), esc_html($last_error)));
                } else {
                    return new WP_Error('save_failed', __('Failed to set manual override: WordPress unable to update settings.', 'skylearn-billing-pro'));
                }
            }
            
            // Verify the change was successful
            $verification_options = get_option('skylearn_billing_pro_options', array());
            if (!isset($verification_options['lms_settings']['manual_overrides'][$lms_key]) || 
                $verification_options['lms_settings']['manual_overrides'][$lms_key] !== $override) {
                
                error_log('SkyLearn Billing Pro: Manual override setting verification failed');
                return new WP_Error('save_failed', __('Failed to set manual override: Data verification failed.', 'skylearn-billing-pro'));
            }
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("SkyLearn Billing Pro: Manual override for {$lms_key} set to " . ($override ? 'enabled' : 'disabled'));
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Exception setting manual override - ' . $e->getMessage());
            return new WP_Error('exception', __('An unexpected error occurred while setting the manual override.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Get manual override status for an LMS
     *
     * @param string $lms_key LMS key to check
     * @return bool Override status
     */
    public function get_lms_manual_override($lms_key) {
        if (!isset($this->supported_lms[$lms_key])) {
            return false;
        }
        
        // Check saved options first
        $options = get_option('skylearn_billing_pro_options', array());
        if (isset($options['lms_settings']['manual_overrides'][$lms_key])) {
            return $options['lms_settings']['manual_overrides'][$lms_key];
        }
        
        // Fall back to in-memory setting
        return isset($this->supported_lms[$lms_key]['manual_override']) ? 
               $this->supported_lms[$lms_key]['manual_override'] : false;
    }
    
    /**
     * Load manual overrides from options
     */
    private function load_manual_overrides() {
        $options = get_option('skylearn_billing_pro_options', array());
        if (isset($options['lms_settings']['manual_overrides']) && is_array($options['lms_settings']['manual_overrides'])) {
            foreach ($options['lms_settings']['manual_overrides'] as $lms_key => $override) {
                if (isset($this->supported_lms[$lms_key])) {
                    $this->supported_lms[$lms_key]['manual_override'] = $override;
                }
            }
        }
    }
    
    /**
     * Check LMS compatibility
     *
     * @return array Compatibility status
     */
    public function check_lms_compatibility() {
        $issues = array();
        $detected = $this->get_detected_lms();
        
        if (empty($detected)) {
            $issues[] = __('No compatible LMS plugins detected', 'skylearn-billing-pro');
        }
        
        $active = $this->get_active_lms();
        if ($active && !$this->is_lms_active($active)) {
            $issues[] = sprintf(__('Active LMS "%s" is not properly installed or activated', 'skylearn-billing-pro'), $active);
        }
        
        if ($active && !$this->has_active_lms()) {
            $issues[] = __('LMS connector could not be loaded', 'skylearn-billing-pro');
        }
        
        return array(
            'compatible' => empty($issues),
            'issues' => $issues
        );
    }
}

/**
 * Get the LMS Manager instance
 *
 * @return SkyLearn_Billing_Pro_LMS_Manager
 */
function skylearn_billing_pro_lms_manager() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_LMS_Manager();
        
        // Ensure immediate initialization to avoid WordPress hook timing issues
        // This fixes the course dropdown population issue where LMS detection
        // and auto-setting of active LMS wasn't happening due to late init hook
        if (method_exists($instance, 'init')) {
            $instance->init();
        }
    }
    
    return $instance;
}