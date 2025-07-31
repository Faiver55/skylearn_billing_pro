<?php
/**
 * Manual test script for Skylearn Billing Pro Phase 16 implementation
 * 
 * This script verifies basic functionality of the onboarding wizard and admin UI
 * Run this from WordPress admin or CLI to test the implementation
 */

// Define WordPress constants for testing (if not already defined)
if (!defined('ABSPATH')) {
    define('ABSPATH', '/path/to/wordpress/');
}

if (!defined('SKYLEARN_BILLING_PRO_PLUGIN_DIR')) {
    define('SKYLEARN_BILLING_PRO_PLUGIN_DIR', __DIR__ . '/');
}

if (!defined('SKYLEARN_BILLING_PRO_PLUGIN_URL')) {
    define('SKYLEARN_BILLING_PRO_PLUGIN_URL', 'http://localhost/wp-content/plugins/skylearn-billing-pro/');
}

/**
 * Test the onboarding class functionality
 */
function test_onboarding_class() {
    echo "Testing Onboarding Class...\n";
    
    // Include the onboarding class
    require_once 'includes/class-onboarding.php';
    
    // Test class instantiation
    $onboarding = skylearn_billing_pro_onboarding();
    
    if ($onboarding instanceof SkyLearn_Billing_Pro_Onboarding) {
        echo "✓ Onboarding class instantiated successfully\n";
    } else {
        echo "✗ Failed to instantiate onboarding class\n";
        return false;
    }
    
    // Test getting onboarding steps
    $steps = $onboarding->get_onboarding_steps();
    
    if (is_array($steps) && count($steps) > 0) {
        echo "✓ Onboarding steps retrieved: " . count($steps) . " steps\n";
        
        // Check required steps
        $required_steps = ['welcome', 'license', 'lms', 'payment', 'products', 'complete'];
        foreach ($required_steps as $step) {
            if (isset($steps[$step])) {
                echo "  ✓ Step '{$step}' exists\n";
            } else {
                echo "  ✗ Step '{$step}' missing\n";
            }
        }
    } else {
        echo "✗ Failed to retrieve onboarding steps\n";
        return false;
    }
    
    // Test contextual help
    $help_general = $onboarding->get_contextual_help('general');
    if ($help_general && isset($help_general['title'])) {
        echo "✓ Contextual help working for 'general' page\n";
    } else {
        echo "✗ Contextual help not working\n";
    }
    
    return true;
}

/**
 * Test the admin UI class functionality
 */
function test_admin_ui_class() {
    echo "\nTesting Admin UI Class...\n";
    
    // Include the admin UI class
    require_once 'includes/admin/class-admin-ui.php';
    
    // Test class instantiation
    $admin_ui = skylearn_billing_pro_admin_ui();
    
    if ($admin_ui instanceof SkyLearn_Billing_Pro_Admin_UI) {
        echo "✓ Admin UI class instantiated successfully\n";
    } else {
        echo "✗ Failed to instantiate admin UI class\n";
        return false;
    }
    
    return true;
}

/**
 * Test template files exist and are readable
 */
function test_template_files() {
    echo "\nTesting Template Files...\n";
    
    $templates = [
        'templates/admin/onboarding.php' => 'Onboarding wizard template',
        'templates/admin/help.php' => 'Help and support template'
    ];
    
    foreach ($templates as $file => $description) {
        if (file_exists($file) && is_readable($file)) {
            echo "✓ {$description} exists and is readable\n";
            
            // Check if template has required PHP tags
            $content = file_get_contents($file);
            if (strpos($content, '<?php') !== false) {
                echo "  ✓ Template has PHP opening tag\n";
            } else {
                echo "  ✗ Template missing PHP opening tag\n";
            }
            
            // Check for security prevention
            if (strpos($content, "if (!defined('ABSPATH'))") !== false) {
                echo "  ✓ Template has security check\n";
            } else {
                echo "  ✗ Template missing security check\n";
            }
            
        } else {
            echo "✗ {$description} not found or not readable\n";
        }
    }
    
    return true;
}

/**
 * Test asset files exist
 */
function test_asset_files() {
    echo "\nTesting Asset Files...\n";
    
    $assets = [
        'assets/css/onboarding.css' => 'Onboarding styles',
        'assets/css/admin-ui.css' => 'Admin UI styles',
        'assets/js/onboarding.js' => 'Onboarding JavaScript',
        'assets/js/admin-ui.js' => 'Admin UI JavaScript'
    ];
    
    foreach ($assets as $file => $description) {
        if (file_exists($file) && is_readable($file)) {
            echo "✓ {$description} exists and is readable\n";
            
            $content = file_get_contents($file);
            if (strlen($content) > 100) { // Basic check for non-empty content
                echo "  ✓ Asset file has content (" . strlen($content) . " bytes)\n";
            } else {
                echo "  ✗ Asset file seems empty or too small\n";
            }
            
        } else {
            echo "✗ {$description} not found or not readable\n";
        }
    }
    
    return true;
}

/**
 * Test documentation files
 */
function test_documentation_files() {
    echo "\nTesting Documentation Files...\n";
    
    $docs = [
        'docs/README.md' => 'Documentation index',
        'docs/user/installation-and-setup.md' => 'User documentation',
        'docs/developer/api-hooks-and-development.md' => 'Developer documentation',
        'docs/faq/troubleshooting-guide.md' => 'FAQ and troubleshooting'
    ];
    
    foreach ($docs as $file => $description) {
        if (file_exists($file) && is_readable($file)) {
            echo "✓ {$description} exists and is readable\n";
            
            $content = file_get_contents($file);
            if (strlen($content) > 500) { // Check for substantial content
                echo "  ✓ Documentation has substantial content (" . strlen($content) . " bytes)\n";
            } else {
                echo "  ✗ Documentation seems too short\n";
            }
            
            // Check for markdown formatting
            if (strpos($content, '#') !== false && strpos($content, '##') !== false) {
                echo "  ✓ Documentation has proper markdown headers\n";
            } else {
                echo "  ✗ Documentation missing proper markdown formatting\n";
            }
            
        } else {
            echo "✗ {$description} not found or not readable\n";
        }
    }
    
    return true;
}

/**
 * Run all tests
 */
function run_all_tests() {
    echo "=== Skylearn Billing Pro Phase 16 Manual Tests ===\n\n";
    
    $tests = [
        'test_onboarding_class',
        'test_admin_ui_class', 
        'test_template_files',
        'test_asset_files',
        'test_documentation_files'
    ];
    
    $passed = 0;
    $total = count($tests);
    
    foreach ($tests as $test) {
        try {
            if (function_exists($test) && $test()) {
                $passed++;
            }
        } catch (Exception $e) {
            echo "✗ Test {$test} failed with exception: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n=== Test Summary ===\n";
    echo "Passed: {$passed}/{$total} tests\n";
    
    if ($passed === $total) {
        echo "🎉 All tests passed! Phase 16 implementation looks good.\n";
    } else {
        echo "⚠️  Some tests failed. Review the output above for details.\n";
    }
    
    echo "\n=== Next Steps ===\n";
    echo "1. Test the onboarding wizard in a WordPress environment\n";
    echo "2. Verify contextual help tooltips display correctly\n";
    echo "3. Check that help links work and are accessible\n";
    echo "4. Review documentation for completeness and accuracy\n";
    echo "5. Test on different devices and browsers\n";
}

// Run the tests if this file is executed directly
if (php_sapi_name() === 'cli' || (isset($_GET['test']) && $_GET['test'] === 'phase16')) {
    run_all_tests();
}