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
            'class_name' => 'SFWD_LMS',
            'function_name' => 'learndash_get_course_id',
            'connector_class' => 'SkyLearn_Billing_Pro_LearnDash_Connector'
        ),
        'tutor' => array(
            'name' => 'TutorLMS',
            'plugin_path' => 'tutor/tutor.php',
            'class_name' => 'TUTOR',
            'function_name' => 'tutor_get_course_id',
            'connector_class' => 'SkyLearn_Billing_Pro_Tutor_Connector'
        ),
        'lifter' => array(
            'name' => 'LifterLMS',
            'plugin_path' => 'lifterlms/lifterlms.php',
            'class_name' => 'LifterLMS',
            'function_name' => 'llms_get_course',
            'connector_class' => 'SkyLearn_Billing_Pro_Lifter_Connector'
        ),
        'learnpress' => array(
            'name' => 'LearnPress',
            'plugin_path' => 'learnpress/learnpress.php',
            'class_name' => 'LearnPress',
            'function_name' => 'learn_press_get_course',
            'connector_class' => 'SkyLearn_Billing_Pro_LearnPress_Connector'
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
        $this->detect_lms_plugins();
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
            return false;
        }
        
        $lms_data = $this->supported_lms[$lms_key];
        
        // Check if plugin is active by plugin path
        if (!function_exists('is_plugin_active')) {
            // Use proper WordPress path if available
            $plugin_file = defined('ABSPATH') ? ABSPATH . 'wp-admin/includes/plugin.php' : '';
            if ($plugin_file && file_exists($plugin_file)) {
                include_once($plugin_file);
            }
        }
        
        // Check if plugin is active (only if function is available)
        if (function_exists('is_plugin_active') && is_plugin_active($lms_data['plugin_path'])) {
            return true;
        }
        
        // Fallback: Check if class exists
        if (isset($lms_data['class_name']) && class_exists($lms_data['class_name'])) {
            return true;
        }
        
        // Fallback: Check if function exists
        if (isset($lms_data['function_name']) && function_exists($lms_data['function_name'])) {
            return true;
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
     * @return bool Success status
     */
    public function set_active_lms($lms_key) {
        if (!isset($this->supported_lms[$lms_key])) {
            return false;
        }
        
        if (!$this->is_lms_active($lms_key)) {
            return false;
        }
        
        $options = get_option('skylearn_billing_pro_options', array());
        if (!isset($options['lms_settings'])) {
            $options['lms_settings'] = array();
        }
        
        $options['lms_settings']['active_lms'] = $lms_key;
        
        return update_option('skylearn_billing_pro_options', $options);
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
            
            if ($this->has_active_lms()) {
                try {
                    $courses = $this->get_courses();
                    $course_count = count($courses);
                    
                    // Add additional debugging for zero courses
                    if ($course_count === 0) {
                        error_log('SkyLearn Billing Pro: get_courses() returned 0 courses - checking active connector');
                        if ($this->active_connector) {
                            error_log('SkyLearn Billing Pro: Active connector exists, class: ' . get_class($this->active_connector));
                            
                            // Test LearnDash detection directly
                            if (method_exists($this->active_connector, 'is_learndash_active')) {
                                $reflection = new ReflectionClass($this->active_connector);
                                $method = $reflection->getMethod('is_learndash_active');
                                $method->setAccessible(true);
                                $is_ld_active = $method->invoke($this->active_connector);
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
    }
    
    return $instance;
}