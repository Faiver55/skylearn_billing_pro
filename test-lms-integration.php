<?php
/**
 * Simple test to verify Course Mapping can be instantiated without fatal errors
 */

// Set up basic WordPress environment
if (!defined('ABSPATH')) {
    define('ABSPATH', '/tmp/');
}

// Mock essential WordPress functions
function add_action($hook, $function_to_add, $priority = 10, $accepted_args = 1) {
    return true;
}
function add_filter($tag, $function_to_add, $priority = 10, $accepted_args = 1) {
    return true;
}
function get_option($option, $default = false) {
    return $default;
}
function update_option($option, $value) {
    return true;
}
function current_time($type) {
    return date('Y-m-d H:i:s');
}
function get_user_by($field, $value) {
    return false;
}
function __($text, $domain = 'default') {
    return $text;
}

// Set up plugin constants
define('SKYLEARN_BILLING_PRO_PLUGIN_DIR', __DIR__ . '/');

echo "Testing Course Mapping instantiation and basic functionality...\n";

try {
    // Test including the LMS Manager
    require_once __DIR__ . '/includes/lms/class-lms-manager.php';
    echo "✓ LMS Manager class loaded successfully\n";
    
    // Test including Course Mapping
    require_once __DIR__ . '/includes/lms/class-course-mapping.php';
    echo "✓ Course Mapping class loaded successfully\n";
    
    // Test LMS Manager instantiation
    $lms_manager = new SkyLearn_Billing_Pro_LMS_Manager();
    echo "✓ LMS Manager instantiated successfully\n";
    
    // Test Course Mapping instantiation
    $course_mapping = new SkyLearn_Billing_Pro_Course_Mapping();
    echo "✓ Course Mapping instantiated successfully\n";
    
    // Test basic methods
    $mappings = $course_mapping->get_course_mappings();
    echo "✓ get_course_mappings() works - returned " . count($mappings) . " mappings\n";
    
    $log = $course_mapping->get_enrollment_log(10);
    echo "✓ get_enrollment_log() works - returned " . count($log) . " entries\n";
    
    // Test LMS Manager methods
    $supported = $lms_manager->get_supported_lms();
    echo "✓ get_supported_lms() works - found " . count($supported) . " supported LMS\n";
    
    $detected = $lms_manager->get_detected_lms();
    echo "✓ get_detected_lms() works - detected " . count($detected) . " LMS\n";
    
    $status = $lms_manager->get_integration_status();
    echo "✓ get_integration_status() works - active LMS: " . ($status['active_lms'] ?: 'none') . "\n";
    
    echo "\n✅ All tests passed! The LMS integration should now work without fatal errors.\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
} catch (Error $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}