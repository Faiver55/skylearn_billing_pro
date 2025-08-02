<?php
/**
 * Detailed debugging test for manual override
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';

class Test_Manual_Override_Debug extends SkyLearn_Billing_Pro_Test_Case {
    
    public function test_debug_manual_override() {
        echo "\n=== Manual Override Debug Test ===\n";
        
        $lms_manager = new SkyLearn_Billing_Pro_LMS_Manager();
        
        echo "1. Initial state:\n";
        $is_active_before = $lms_manager->is_lms_active('learndash');
        echo "   LearnDash active: " . ($is_active_before ? 'YES' : 'NO') . "\n";
        
        $override_before = $lms_manager->get_lms_manual_override('learndash');
        echo "   Manual override: " . ($override_before ? 'YES' : 'NO') . "\n";
        
        echo "\n2. Setting manual override:\n";
        $set_result = $lms_manager->set_lms_manual_override('learndash', true);
        echo "   Set result: " . ($set_result ? 'SUCCESS' : 'FAILED') . "\n";
        
        $override_after = $lms_manager->get_lms_manual_override('learndash');
        echo "   Manual override after: " . ($override_after ? 'YES' : 'NO') . "\n";
        
        echo "\n3. Checking LMS active after override:\n";
        $is_active_after = $lms_manager->is_lms_active('learndash');
        echo "   LearnDash active after: " . ($is_active_after ? 'YES' : 'NO') . "\n";
        
        echo "\n4. Trying to set as active LMS:\n";
        $set_active_result = $lms_manager->set_active_lms('learndash');
        echo "   Set active result: " . ($set_active_result ? 'SUCCESS' : 'FAILED') . "\n";
        
        $active_lms = $lms_manager->get_active_lms();
        echo "   Active LMS: " . ($active_lms ?: 'NONE') . "\n";
        
        echo "\n5. Checking detected LMS:\n";
        $detected = $lms_manager->get_detected_lms();
        echo "   Detected count: " . count($detected) . "\n";
        foreach ($detected as $key => $data) {
            echo "   - {$key}: {$data['name']}\n";
        }
        
        // The test should pass if manual override works
        $this->assertTrue($override_after, 'Manual override should be set to true');
        $this->assertTrue($is_active_after, 'LearnDash should be active after manual override');
        $this->assertTrue($set_active_result, 'Should be able to set LearnDash as active');
        $this->assertEquals('learndash', $active_lms, 'Active LMS should be learndash');
    }
}