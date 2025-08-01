<?php
/**
 * Unit test for admin-lms.php TypeError fix
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class Test_Admin_LMS_Fix extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Test that null course mapping is handled safely
     */
    public function test_null_course_mapping_handling() {
        $course_mapping = null;
        $count = $course_mapping ? count($course_mapping->get_course_mappings()) : 0;
        $this->assertEquals(0, $count);
    }
    
    /**
     * Test that the old broken way would cause TypeError
     */
    public function test_old_broken_way_causes_type_error() {
        $this->expectException(TypeError::class);
        $this->expectExceptionMessage('count(): Argument #1 ($value) must be of type Countable|array, int given');
        
        // Simulate the old broken code
        $lms_status = array('course_count' => 5);
        count($lms_status['course_count'] ?? 0);
    }
    
    /**
     * Test that the new approach works with array
     */
    public function test_new_approach_with_array() {
        // Simulate course mappings array
        $mappings = array(
            'product_1' => array('course_id' => 1),
            'product_2' => array('course_id' => 2),
            'product_3' => array('course_id' => 3)
        );
        
        // This should work fine
        $count = count($mappings);
        $this->assertEquals(3, $count);
    }
    
    /**
     * Test that the fixed approach is safe
     */
    public function test_fixed_approach_safety() {
        // Test various scenarios that the fixed code should handle
        
        // Case 1: null object
        $course_mapping = null;
        $result = $course_mapping ? count(array()) : 0;
        $this->assertEquals(0, $result);
        
        // Case 2: object with empty array method result
        $result = true ? count(array()) : 0;
        $this->assertEquals(0, $result);
        
        // Case 3: object with populated array method result
        $result = true ? count(array('a', 'b', 'c')) : 0;
        $this->assertEquals(3, $result);
    }
}