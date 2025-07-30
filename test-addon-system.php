<?php
/**
 * Simple test script for addon system
 */

// This would be run within WordPress context
echo "Testing Skylearn Billing Pro Addon System\n";
echo "==========================================\n\n";

// Test 1: Check if addon manager is working
echo "1. Testing Addon Manager:\n";
try {
    if (function_exists('skylearn_billing_pro_addon_manager')) {
        $addon_manager = skylearn_billing_pro_addon_manager();
        $available_addons = $addon_manager->get_available_addons();
        echo "✓ Addon Manager initialized successfully\n";
        echo "✓ Found " . count($available_addons) . " available addons\n";
        
        foreach ($available_addons as $addon) {
            echo "  - " . $addon['name'] . " (" . $addon['type'] . ", " . $addon['status'] . ")\n";
        }
    } else {
        echo "✗ Addon Manager not available\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Check if license manager is working
echo "2. Testing License Manager:\n";
try {
    if (function_exists('skylearn_billing_pro_license_manager')) {
        $license_manager = skylearn_billing_pro_license_manager();
        echo "✓ License Manager initialized successfully\n";
        
        // Test license checks for different addon types
        $test_addons = ['email-addon', 'affiliate-addon', 'reporting-addon'];
        foreach ($test_addons as $addon_id) {
            $accessible = $license_manager->is_addon_accessible($addon_id);
            echo "  - " . $addon_id . ": " . ($accessible ? "Accessible" : "Not Accessible") . "\n";
        }
    } else {
        echo "✗ License Manager not available\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Check if addons are properly structured
echo "3. Testing Addon File Structure:\n";
$addons_dir = SKYLEARN_BILLING_PLUGIN_DIR . 'addons/';
if (is_dir($addons_dir)) {
    $addon_files = glob($addons_dir . '*-addon.php');
    echo "✓ Addons directory exists\n";
    echo "✓ Found " . count($addon_files) . " addon files\n";
    
    foreach ($addon_files as $file) {
        $filename = basename($file);
        echo "  - " . $filename . "\n";
        
        // Check if file has proper headers
        $headers = get_file_data($file, array(
            'id' => 'Addon ID',
            'name' => 'Addon Name',
            'type' => 'Type'
        ));
        
        if (!empty($headers['id']) && !empty($headers['name'])) {
            echo "    ✓ Valid headers found\n";
        } else {
            echo "    ✗ Missing required headers\n";
        }
    }
} else {
    echo "✗ Addons directory not found\n";
}

echo "\n";

echo "Testing completed!\n";
?>