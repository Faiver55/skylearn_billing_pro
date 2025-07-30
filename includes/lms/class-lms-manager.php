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
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        if (is_plugin_active($lms_data['plugin_path'])) {
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
            }
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
        
        return $this->active_connector->get_courses();
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
        
        return $this->active_connector->enroll_user($user_id, $course_id);
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
        
        return $this->active_connector->unenroll_user($user_id, $course_id);
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
        
        return $this->active_connector->is_user_enrolled($user_id, $course_id);
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
        
        return $this->active_connector->get_course_details($course_id);
    }
    
    /**
     * Get LMS integration status for admin dashboard
     *
     * @return array
     */
    public function get_integration_status() {
        $detected = $this->get_detected_lms();
        $active = $this->get_active_lms();
        
        return array(
            'detected_count' => count($detected),
            'detected_lms' => $detected,
            'active_lms' => $active,
            'active_lms_name' => $active && isset($this->supported_lms[$active]) ? $this->supported_lms[$active]['name'] : false,
            'has_active_connector' => $this->has_active_lms(),
            'course_count' => $this->has_active_lms() ? count($this->get_courses()) : 0
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