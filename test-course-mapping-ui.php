<?php
/**
 * Simple test to verify Course Mapping UI can be rendered without fatal errors
 */

// Set up basic WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Mock WordPress functions
function get_option($option, $default = false) { 
    return $default; 
}
function update_option($option, $value) { 
    return true; 
}
function current_time($type) { 
    return '2024-01-01 12:00:00'; 
}
function wp_create_nonce($action) { 
    return 'test_nonce_' . $action; 
}
function esc_html($text) { 
    return htmlspecialchars($text); 
}
function esc_attr($text) { 
    return htmlspecialchars($text); 
}
function esc_js($text) { 
    return addslashes($text); 
}
function __($text, $domain = 'default') { 
    return $text; 
}
function esc_html_e($text, $domain = 'default') { 
    echo esc_html(__($text, $domain)); 
}
function esc_html__($text, $domain = 'default') { 
    return esc_html(__($text, $domain)); 
}
function esc_attr_e($text, $domain = 'default') { 
    echo esc_attr(__($text, $domain)); 
}
function esc_attr__($text, $domain = 'default') { 
    return esc_attr(__($text, $domain)); 
}
function date_i18n($format, $timestamp = null) { 
    return date($format, $timestamp ?: time()); 
}
function admin_url($path) { 
    return 'http://example.com/wp-admin/' . $path; 
}

// Mock skylearn_billing_pro_lms_manager function
if (!function_exists('skylearn_billing_pro_lms_manager')) {
    function skylearn_billing_pro_lms_manager() {
        return null; // Simulate unavailable LMS manager
    }
}

// Set up plugin constants
define('SKYLEARN_BILLING_PRO_PLUGIN_DIR', __DIR__ . '/');

// Include the classes
require_once __DIR__ . '/includes/lms/class-lms-manager.php';
require_once __DIR__ . '/includes/lms/class-course-mapping.php';

echo "Testing Course Mapping UI rendering...\n";

try {
    // Create Course Mapping instance
    $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
    
    echo "Course Mapping instance created successfully.\n";
    
    // Start output buffering to catch any output
    ob_start();
    
    // Try to render the UI
    $course_mapping->render_mapping_ui();
    
    // Get the output
    $output = ob_get_clean();
    
    echo "Course Mapping UI rendered successfully.\n";
    echo "Output length: " . strlen($output) . " characters\n";
    
    // Check if error notice is present (expected when no LMS manager)
    if (strpos($output, 'LMS Manager could not be initialized') !== false) {
        echo "✓ Correct error handling when LMS Manager is unavailable\n";
    } else {
        echo "! Unexpected output - no error notice found\n";
    }
    
    echo "\nTest completed successfully - no fatal errors!\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}