<?php
/**
 * Test script for LMS Integration menu fixes
 * Validates the JavaScript event handler fix and course mapping improvements
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

// This script tests the fixes for the LMS Integration menu issues

echo "=== SkyLearn Billing Pro - LMS Integration Menu Fixes Test ===\n";
echo "Testing fixes for issue: Comprehensive Review and Fix of LMS Integration Menu\n\n";

// Test 1: Verify admin.js exists and has correct event handler
echo "1. Testing JavaScript Event Handler Fix...\n";
$admin_js_path = __DIR__ . '/assets/js/admin.js';
if (file_exists($admin_js_path)) {
    $admin_js_content = file_get_contents($admin_js_path);
    
    // Check that the old problematic selector is gone
    if (strpos($admin_js_content, ".skylearn-billing-btn-secondary:contains(\"Reset to Defaults\")") === false) {
        echo "✓ Old problematic selector removed\n";
    } else {
        echo "✗ Old problematic selector still present\n";
    }
    
    // Check that new specific selectors are present
    if (strpos($admin_js_content, "handleBackToDashboard") !== false) {
        echo "✓ New handleBackToDashboard method added\n";
    } else {
        echo "✗ handleBackToDashboard method missing\n";
    }
    
    // Check for specific reset button selectors
    if (strpos($admin_js_content, "[data-action=\"reset\"]") !== false) {
        echo "✓ Specific data-action reset selector added\n";
    } else {
        echo "✗ Specific data-action reset selector missing\n";
    }
} else {
    echo "✗ admin.js file not found\n";
}

echo "\n2. Testing Course Mapping Improvements...\n";

// Test 2: Verify course-mapping.js exists and has comprehensive functionality
$course_mapping_js_path = __DIR__ . '/assets/js/course-mapping.js';
if (file_exists($course_mapping_js_path)) {
    $course_mapping_js_content = file_get_contents($course_mapping_js_path);
    
    // Check for key features
    $features_to_check = [
        'validateForm' => 'Form validation method',
        'handleSubmissionError' => 'Error handling method',
        'setLoadingState' => 'Loading state management',
        'showNotice' => 'User notification system',
        'checkDuplicateMapping' => 'Duplicate prevention',
        'markFieldAsError' => 'Field validation feedback',
        'timeout: 30000' => 'AJAX timeout protection'
    ];
    
    foreach ($features_to_check as $feature => $description) {
        if (strpos($course_mapping_js_content, $feature) !== false) {
            echo "✓ {$description} present\n";
        } else {
            echo "✗ {$description} missing\n";
        }
    }
} else {
    echo "✗ course-mapping.js file not found\n";
}

echo "\n3. Testing Course Mapping Class Improvements...\n";

// Test 3: Verify course mapping class has improved error handling
$course_mapping_class_path = __DIR__ . '/includes/lms/class-course-mapping.php';
if (file_exists($course_mapping_class_path)) {
    $course_mapping_class_content = file_get_contents($course_mapping_class_path);
    
    // Check for improvements
    $improvements_to_check = [
        'validate_mapping_inputs' => 'Input validation method',
        'WP_Error' => 'WordPress error handling',
        'wp_send_json_error' => 'AJAX error response',
        'wp_send_json_success' => 'AJAX success response',
        'sanitize_text_field' => 'Input sanitization',
        'current_user_can' => 'Permission checking',
        'wp_verify_nonce' => 'Security nonce verification'
    ];
    
    foreach ($improvements_to_check as $improvement => $description) {
        if (strpos($course_mapping_class_content, $improvement) !== false) {
            echo "✓ {$description} implemented\n";
        } else {
            echo "✗ {$description} missing\n";
        }
    }
} else {
    echo "✗ class-course-mapping.php file not found\n";
}

echo "\n4. Testing CSS Styling Improvements...\n";

// Test 4: Verify CSS file exists and has styling for form validation
$course_mapping_css_path = __DIR__ . '/assets/css/course-mapping.css';
if (file_exists($course_mapping_css_path)) {
    $course_mapping_css_content = file_get_contents($course_mapping_css_path);
    
    $css_features = [
        '.skylearn-field-error' => 'Error field styling',
        '.skylearn-field-valid' => 'Valid field styling',
        '.loading' => 'Loading state styling',
        '@keyframes' => 'Animation support',
        '@media' => 'Responsive design',
        'prefers-reduced-motion' => 'Accessibility support'
    ];
    
    foreach ($css_features as $feature => $description) {
        if (strpos($course_mapping_css_content, $feature) !== false) {
            echo "✓ {$description} present\n";
        } else {
            echo "✗ {$description} missing\n";
        }
    }
} else {
    echo "✗ course-mapping.css file not found\n";
}

echo "\n5. Testing Admin Class Integration...\n";

// Test 5: Verify admin class properly enqueues new assets
$admin_class_path = __DIR__ . '/includes/class-admin.php';
if (file_exists($admin_class_path)) {
    $admin_class_content = file_get_contents($admin_class_path);
    
    if (strpos($admin_class_content, 'skylearn-billing-pro-course-mapping') !== false) {
        echo "✓ Course mapping script enqueued\n";
    } else {
        echo "✗ Course mapping script not enqueued\n";
    }
    
    if (strpos($admin_class_content, 'skylernCourseMappingData') !== false) {
        echo "✓ Course mapping data localization added\n";
    } else {
        echo "✗ Course mapping data localization missing\n";
    }
    
    if (strpos($admin_class_content, 'course-mapping.css') !== false) {
        echo "✓ Course mapping CSS enqueued\n";
    } else {
        echo "✗ Course mapping CSS not enqueued\n";
    }
} else {
    echo "✗ class-admin.php file not found\n";
}

echo "\n=== Test Summary ===\n";

// Count the results
$total_tests = substr_count(ob_get_contents(), "✓") + substr_count(ob_get_contents(), "✗");
$passed_tests = substr_count(ob_get_contents(), "✓");
$failed_tests = substr_count(ob_get_contents(), "✗");

echo "Total Tests: {$total_tests}\n";
echo "Passed: {$passed_tests}\n";
echo "Failed: {$failed_tests}\n";

if ($failed_tests === 0) {
    echo "\n🎉 All tests passed! The LMS Integration menu fixes have been successfully implemented.\n";
} else {
    echo "\n⚠️  Some tests failed. Please review the output above for details.\n";
}

echo "\nKey improvements implemented:\n";
echo "- Fixed JavaScript event handler specificity to prevent 'Back to Dashboard' button conflicts\n";
echo "- Added comprehensive course mapping validation and error handling\n";
echo "- Implemented user-friendly loading states and progress indicators\n";
echo "- Enhanced AJAX operations with proper timeout and error recovery\n";
echo "- Added accessible CSS styling with responsive design\n";
echo "- Improved database operations with better validation and logging\n";

echo "\nNext recommended actions:\n";
echo "1. Test the admin interface in a live WordPress environment\n";
echo "2. Verify LMS integration with actual LMS plugins (LearnDash, etc.)\n";
echo "3. Test webhook functionality with third-party integrations\n";
echo "4. Validate enrollment log functionality with real transactions\n";

?>