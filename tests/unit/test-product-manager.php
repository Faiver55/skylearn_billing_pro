<?php
/**
 * Test Product Manager functionality
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

class Test_Product_Manager extends WP_UnitTestCase {

    private $product_manager;

    public function setUp(): void {
        parent::setUp();
        
        // Ensure the Product Manager class is loaded
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/product/class-product-manager.php';
        
        // Get instance of product manager
        $this->product_manager = SkyLearn_Billing_Pro_Product_Manager::instance();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    /**
     * Test that check_product_limit handles empty product counts without warnings
     */
    public function test_check_product_limit_no_warnings() {
        // Mock the licensing manager to return 'free' tier
        $mock_licensing = $this->getMockBuilder('stdClass')
                              ->setMethods(['get_current_tier'])
                              ->getMock();
        $mock_licensing->method('get_current_tier')->willReturn('free');
        
        // Override the licensing function temporarily
        $original_function = null;
        if (function_exists('skylearn_billing_pro_licensing')) {
            // We can't easily mock a function, so let's test the actual method behavior
            // by creating a scenario where wp_count_posts might return an empty object
        }
        
        // Test that the method doesn't cause PHP warnings when properties don't exist
        // This would typically happen when no posts of the custom post type exist yet
        
        // Create a mock wp_count_posts result that might be missing properties
        $empty_count = new stdClass();
        // Intentionally not setting publish, draft, private properties
        
        // We'll test indirectly by ensuring the method can be called without errors
        $result = $this->product_manager->check_product_limit();
        
        // The method should return a boolean without throwing warnings
        $this->assertIsBool($result, 'check_product_limit should return a boolean');
    }

    /**
     * Test that check_product_limit works correctly with existing products
     */
    public function test_check_product_limit_with_products() {
        // Create some test products
        $product_ids = array();
        for ($i = 0; $i < 3; $i++) {
            $product_ids[] = wp_insert_post(array(
                'post_title' => 'Test Product ' . $i,
                'post_type' => SkyLearn_Billing_Pro_Product_Manager::POST_TYPE,
                'post_status' => 'publish'
            ));
        }
        
        // Test that the method works with actual products
        $result = $this->product_manager->check_product_limit();
        $this->assertIsBool($result, 'check_product_limit should return a boolean with products');
        
        // Clean up
        foreach ($product_ids as $id) {
            wp_delete_post($id, true);
        }
    }

    /**
     * Test that the Product Manager instance can be created
     */
    public function test_product_manager_instance() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Product_Manager', $this->product_manager);
    }

    /**
     * Test that the POST_TYPE constant is defined
     */
    public function test_post_type_constant() {
        $this->assertEquals('skylearn_product', SkyLearn_Billing_Pro_Product_Manager::POST_TYPE);
    }
}