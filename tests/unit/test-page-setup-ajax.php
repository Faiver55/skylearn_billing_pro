<?php
/**
 * Test AJAX functionality for Page Setup
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

class Test_Page_Setup_AJAX extends WP_UnitTestCase {

    private $admin_user;
    private $page_setup_instance;

    public function setUp(): void {
        parent::setUp();
        
        // Create admin user
        $this->admin_user = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        wp_set_current_user($this->admin_user);
        
        // Ensure all required files are loaded
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/frontend/page-generator.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/page-setup.php';
        
        // Initialize page setup
        $this->page_setup_instance = skylearn_billing_pro_page_setup();
    }

    public function tearDown(): void {
        parent::tearDown();
        wp_set_current_user(0);
    }

    /**
     * Test that AJAX handler is properly registered
     */
    public function test_ajax_handler_registered() {
        global $wp_filter;
        
        // Check if the AJAX handler is registered
        $this->assertArrayHasKey('wp_ajax_skylearn_page_setup_action', $wp_filter, 'AJAX handler should be registered for logged-in users');
        
        // Check if handler callback exists
        $callbacks = $wp_filter['wp_ajax_skylearn_page_setup_action']->callbacks;
        $this->assertNotEmpty($callbacks, 'AJAX handler should have callbacks registered');
    }

    /**
     * Test AJAX handler with invalid nonce
     */
    public function test_ajax_handler_invalid_nonce() {
        $_POST = array(
            'action' => 'skylearn_page_setup_action',
            'nonce' => 'invalid_nonce',
            'page_action' => 'create_all'
        );

        // Capture the output
        ob_start();
        try {
            do_action('wp_ajax_skylearn_page_setup_action');
            $output = ob_get_clean();
            
            // Should return error for invalid nonce
            $response = json_decode($output, true);
            $this->assertFalse($response['success'], 'Should fail with invalid nonce');
            $this->assertContains('Invalid nonce', $response['data']['message']);
        } catch (Exception $e) {
            ob_end_clean();
            // If we get here, the AJAX handler isn't properly registered
            $this->fail('AJAX handler not properly registered: ' . $e->getMessage());
        }
    }

    /**
     * Test AJAX handler with valid nonce but no permissions
     */
    public function test_ajax_handler_no_permissions() {
        // Create non-admin user
        $subscriber = $this->factory->user->create(array('role' => 'subscriber'));
        wp_set_current_user($subscriber);

        $_POST = array(
            'action' => 'skylearn_page_setup_action',
            'nonce' => wp_create_nonce('skylearn_page_setup_nonce'),
            'page_action' => 'create_all'
        );

        ob_start();
        try {
            do_action('wp_ajax_skylearn_page_setup_action');
            $output = ob_get_clean();
            
            $response = json_decode($output, true);
            $this->assertFalse($response['success'], 'Should fail without proper permissions');
            $this->assertContains('Insufficient permissions', $response['data']['message']);
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail('AJAX handler not properly registered: ' . $e->getMessage());
        }
        
        // Reset to admin user
        wp_set_current_user($this->admin_user);
    }

    /**
     * Test AJAX handler with valid request
     */
    public function test_ajax_handler_valid_request() {
        $_POST = array(
            'action' => 'skylearn_page_setup_action',
            'nonce' => wp_create_nonce('skylearn_page_setup_nonce'),
            'page_action' => 'create_all'
        );

        ob_start();
        try {
            do_action('wp_ajax_skylearn_page_setup_action');
            $output = ob_get_clean();
            
            // Should not be empty if handler is working
            $this->assertNotEmpty($output, 'AJAX handler should produce output');
            
            // Try to decode as JSON
            $response = json_decode($output, true);
            $this->assertNotNull($response, 'AJAX response should be valid JSON');
            
        } catch (Exception $e) {
            ob_end_clean();
            $this->fail('AJAX handler failed: ' . $e->getMessage());
        }
    }

    /**
     * Test that page generator dependency is available
     */
    public function test_page_generator_dependency() {
        $this->assertTrue(function_exists('skylearn_billing_pro_page_generator'), 'Page generator function should be available');
        
        $page_generator = skylearn_billing_pro_page_generator();
        $this->assertNotNull($page_generator, 'Page generator instance should be available');
        $this->assertTrue(method_exists($page_generator, 'create_pages'), 'Page generator should have create_pages method');
    }

    /**
     * Test early initialization during plugins_loaded
     */
    public function test_early_initialization() {
        // Test that our class can be instantiated early
        $early_instance = new SkyLearn_Billing_Pro_Page_Setup();
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Page_Setup', $early_instance);
        
        // Test that AJAX handlers can be registered early
        global $wp_filter;
        $this->assertArrayHasKey('wp_ajax_skylearn_page_setup_action', $wp_filter);
    }
}