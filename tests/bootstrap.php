<?php
/**
 * Bootstrap file for PHPUnit tests
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

// Define test constants
define('SKYLEARN_BILLING_PRO_TESTS_DIR', __DIR__);
define('SKYLEARN_BILLING_PRO_PLUGIN_DIR', dirname(__DIR__) . '/');
define('SKYLEARN_BILLING_PRO_PLUGIN_URL', 'http://localhost/wp-content/plugins/skylearn-billing-pro/');
define('SKYLEARN_BILLING_PRO_VERSION', '1.0.0');

// Set up WordPress test environment
$_tests_dir = getenv('WP_TESTS_DIR');
if (!$_tests_dir) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

// Include WordPress test functions
if (file_exists($_tests_dir . '/includes/functions.php')) {
    require_once $_tests_dir . '/includes/functions.php';
    
    function _manually_load_plugin() {
        require SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'skylearn-billing-pro.php';
    }
    tests_add_filter('muplugins_loaded', '_manually_load_plugin');
    
    require $_tests_dir . '/includes/bootstrap.php';
} else {
    // Fallback for basic testing without full WordPress environment
    if (!defined('ABSPATH')) {
        define('ABSPATH', '/tmp/');
    }
    
    // Mock WordPress functions for basic testing
    if (!function_exists('add_action')) {
        function add_action($hook, $function) { return true; }
    }
    if (!function_exists('add_filter')) {
        function add_filter($hook, $function) { return true; }
    }
    if (!function_exists('wp_create_nonce')) {
        function wp_create_nonce($action) { return 'test_nonce_' . $action; }
    }
    if (!function_exists('get_option')) {
        function get_option($option, $default = false) { return $default; }
    }
    if (!function_exists('update_option')) {
        function update_option($option, $value) { return true; }
    }
    if (!function_exists('__')) {
        function __($text, $domain = 'default') { return $text; }
    }
    if (!function_exists('esc_html')) {
        function esc_html($text) { return htmlspecialchars($text); }
    }
    if (!function_exists('wp_generate_password')) {
        function wp_generate_password($length = 12, $special_chars = true) { 
            return 'test_password_' . time(); 
        }
    }
    if (!function_exists('delete_option')) {
        function delete_option($option) { return true; }
    }
    if (!function_exists('current_time')) {
        function current_time($format) { return date($format); }
    }
    if (!function_exists('sanitize_text_field')) {
        function sanitize_text_field($text) { return trim(strip_tags($text)); }
    }
    if (!function_exists('sanitize_email')) {
        function sanitize_email($email) { return filter_var($email, FILTER_SANITIZE_EMAIL); }
    }
    if (!function_exists('wp_json_encode')) {
        function wp_json_encode($data) { return json_encode($data); }
    }
    if (!function_exists('get_user_by')) {
        function get_user_by($field, $value) { 
            return (object) array('user_email' => 'test@example.com'); 
        }
    }
    if (!function_exists('do_action')) {
        function do_action($hook) { return true; }
    }
    if (!function_exists('set_transient')) {
        function set_transient($key, $value, $expiration = 0) { return true; }
    }
    if (!function_exists('get_transient')) {
        function get_transient($key) { return false; }
    }
    if (!function_exists('delete_transient')) {
        function delete_transient($key) { return true; }
    }
    if (!defined('OBJECT')) {
        define('OBJECT', 'OBJECT');
    }
    if (!defined('ARRAY_A')) {
        define('ARRAY_A', 'ARRAY_A');
    }
    if (!function_exists('dbDelta')) {
        function dbDelta($queries) { return array('wp_skylearn_course_mappings' => 'Created table'); }
    }
    if (!function_exists('is_admin')) {
        function is_admin() { return true; }
    }
    if (!function_exists('wp_doing_ajax')) {
        function wp_doing_ajax() { return false; }
    }
    if (!function_exists('current_user_can')) {
        function current_user_can($capability) { return true; }
    }
    if (!function_exists('check_ajax_referer')) {
        function check_ajax_referer($action) { return true; }
    }
    if (!function_exists('wp_die')) {
        function wp_die($message) { throw new Exception($message); }
    }
    if (!function_exists('wp_verify_nonce')) {
        function wp_verify_nonce($nonce, $action) { return true; }
    }
    if (!function_exists('wp_send_json_success')) {
        function wp_send_json_success($data) { echo json_encode(array('success' => true, 'data' => $data)); }
    }
    if (!function_exists('wp_send_json_error')) {
        function wp_send_json_error($data) { echo json_encode(array('success' => false, 'data' => $data)); }
    }
    if (!class_exists('WP_Error')) {
        class WP_Error {
            private $errors = array();
            
            public function __construct($code = '', $message = '', $data = '') {
                if (!empty($code)) {
                    $this->errors[$code] = array($message);
                }
            }
            
            public function get_error_code() {
                return array_keys($this->errors)[0] ?? '';
            }
            
            public function get_error_message() {
                $errors = array_values($this->errors);
                return $errors[0][0] ?? '';
            }
        }
    }
    if (!function_exists('is_wp_error')) {
        function is_wp_error($thing) {
            return $thing instanceof WP_Error;
        }
    }
    // Mock WordPress database global with in-memory storage for testing
    if (!isset($GLOBALS['wpdb'])) {
        class MockWPDB {
            public $prefix = 'wp_';
            public $last_error = '';
            private $tables = array();
            
            public function prepare($query, ...$args) {
                return sprintf($query, ...$args);
            }
            
            public function get_results($query, $output = OBJECT) {
                // Simple mock - return empty array for testing
                return array();
            }
            
            public function get_row($query, $output = OBJECT) {
                return null;
            }
            
            public function get_var($query) {
                // For table existence checks
                if (strpos($query, 'SHOW TABLES') !== false) {
                    if (strpos($query, 'skylearn_course_mappings') !== false) {
                        return $this->prefix . 'skylearn_course_mappings';
                    }
                    if (strpos($query, 'skylearn_enrollment_log') !== false) {
                        return $this->prefix . 'skylearn_enrollment_log';
                    }
                }
                return null;
            }
            
            public function insert($table, $data, $format = null) {
                $this->last_error = '';
                return 1; // Success
            }
            
            public function delete($table, $where, $format = null) {
                $this->last_error = '';
                return 1; // Success
            }
            
            public function query($query) {
                $this->last_error = '';
                return true;
            }
        }
        
        $GLOBALS['wpdb'] = new MockWPDB();
    }
}

// Include test helpers
require_once __DIR__ . '/helpers.php';

// Include the classes we're testing if not in full WordPress environment
if (!class_exists('SkyLearn_Billing_Pro_Database_Manager')) {
    require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-database-manager.php';
    require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-course-mapping-migration.php';
    require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-course-mapping.php';
    
    // Add mock functions for dependencies
    if (!function_exists('skylearn_billing_pro_lms_manager')) {
        function skylearn_billing_pro_lms_manager() {
            return null; // Return null to simulate missing LMS manager
        }
    }
    if (!function_exists('skylearn_billing_pro_database_manager')) {
        function skylearn_billing_pro_database_manager() {
            return new SkyLearn_Billing_Pro_Database_Manager();
        }
    }
    if (!function_exists('skylearn_billing_pro_course_mapping_migration')) {
        function skylearn_billing_pro_course_mapping_migration() {
            return new SkyLearn_Billing_Pro_Course_Mapping_Migration();
        }
    }
}