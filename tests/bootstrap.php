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
}

// Include test helpers
require_once __DIR__ . '/helpers.php';