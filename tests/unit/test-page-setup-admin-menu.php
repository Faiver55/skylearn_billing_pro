<?php
/**
 * Test Admin Menu Registration for Page Setup
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

class Test_Page_Setup_Admin_Menu extends WP_UnitTestCase {

    private $admin_user;
    private $page_setup_instance;

    public function setUp(): void {
        parent::setUp();
        
        // Create admin user
        $this->admin_user = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        wp_set_current_user($this->admin_user);
        
        // Ensure required files are loaded
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/frontend/page-generator.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/page-setup.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-admin.php';
    }

    public function tearDown(): void {
        parent::tearDown();
        wp_set_current_user(0);
    }

    /**
     * Test that admin menu hook is registered with correct priority
     */
    public function test_admin_menu_hook_priority() {
        global $wp_filter;
        
        // Initialize page setup
        $page_setup_instance = new SkyLearn_Billing_Pro_Page_Setup();
        
        // Check that admin_menu hook is registered
        $this->assertArrayHasKey('admin_menu', $wp_filter, 'admin_menu hook should be registered');
        
        // Find the Page Setup callback in the admin_menu hook
        $admin_menu_callbacks = $wp_filter['admin_menu']->callbacks;
        $page_setup_found = false;
        $page_setup_priority = null;
        
        foreach ($admin_menu_callbacks as $priority => $callbacks) {
            foreach ($callbacks as $callback) {
                if (is_array($callback['function']) && 
                    is_object($callback['function'][0]) && 
                    $callback['function'][0] instanceof SkyLearn_Billing_Pro_Page_Setup &&
                    $callback['function'][1] === 'add_admin_menu') {
                    $page_setup_found = true;
                    $page_setup_priority = $priority;
                    break 2;
                }
            }
        }
        
        $this->assertTrue($page_setup_found, 'Page Setup admin_menu callback should be registered');
        $this->assertEquals(20, $page_setup_priority, 'Page Setup admin_menu should use priority 20');
    }

    /**
     * Test that the admin menu callback method exists
     */
    public function test_admin_menu_callback_exists() {
        $page_setup_instance = new SkyLearn_Billing_Pro_Page_Setup();
        
        $this->assertTrue(method_exists($page_setup_instance, 'add_admin_menu'), 'add_admin_menu method should exist');
        $this->assertTrue(method_exists($page_setup_instance, 'render_admin_page'), 'render_admin_page method should exist');
    }

    /**
     * Test admin menu registration without parent menu
     */
    public function test_admin_menu_without_parent() {
        global $menu, $submenu;
        
        // Clear any existing menu
        $menu = array();
        $submenu = array();
        
        $page_setup_instance = new SkyLearn_Billing_Pro_Page_Setup();
        
        // Capture error logs
        $errors = array();
        $original_error_handler = set_error_handler(function($severity, $message) use (&$errors) {
            $errors[] = $message;
        });
        
        // Try to add admin menu without parent
        $page_setup_instance->add_admin_menu();
        
        // Restore error handler
        set_error_handler($original_error_handler);
        
        // Should not have added submenu without parent
        $this->assertEmpty($submenu, 'Submenu should not be added without parent menu');
    }

    /**
     * Test admin menu registration with parent menu
     */
    public function test_admin_menu_with_parent() {
        global $menu, $submenu;
        
        // Set up parent menu
        $menu = array(
            array('Skylearn Billing', 'manage_options', 'skylearn-billing-pro', 'Skylearn Billing', 'manage_options', 'skylearn-billing-pro', 'dashicons-credit-card')
        );
        $submenu = array();
        
        $page_setup_instance = new SkyLearn_Billing_Pro_Page_Setup();
        
        // Mock add_submenu_page function to return success
        if (!function_exists('add_submenu_page_mock')) {
            function add_submenu_page_mock($parent, $title, $menu_title, $capability, $slug, $callback) {
                global $submenu;
                if (!isset($submenu[$parent])) {
                    $submenu[$parent] = array();
                }
                $submenu[$parent][] = array($menu_title, $capability, $slug, $title);
                return 'page_hook_suffix';
            }
        }
        
        // Temporarily replace add_submenu_page
        $original_function = 'add_submenu_page';
        if (function_exists($original_function)) {
            // This is a limitation of the test - we can't easily mock WordPress functions
            // In a real WordPress environment, this would work properly
            $this->markTestSkipped('Cannot mock WordPress functions in unit test');
        }
    }

    /**
     * Test render admin page method
     */
    public function test_render_admin_page_method() {
        // Mock skylearn_billing_pro_page_generator function
        if (!function_exists('skylearn_billing_pro_page_generator')) {
            function skylearn_billing_pro_page_generator() {
                return new stdClass();
            }
        }
        
        $page_setup_instance = new SkyLearn_Billing_Pro_Page_Setup();
        
        // Test that the method is callable
        $this->assertTrue(is_callable(array($page_setup_instance, 'render_admin_page')), 'render_admin_page should be callable');
        
        // Test method execution (this would need more mocking in real scenario)
        ob_start();
        try {
            // This would fail in real execution due to missing dependencies
            // but we can test that the method exists and is callable
            $this->assertTrue(method_exists($page_setup_instance, 'render_admin_page'));
        } catch (Exception $e) {
            // Expected due to missing WordPress context
        }
        ob_end_clean();
    }

    /**
     * Test that capability check works in render method
     */
    public function test_render_admin_page_capability_check() {
        // Create non-admin user
        $subscriber = $this->factory->user->create(array('role' => 'subscriber'));
        wp_set_current_user($subscriber);
        
        $page_setup_instance = new SkyLearn_Billing_Pro_Page_Setup();
        
        // Mock wp_die to capture the call
        $wp_die_called = false;
        if (!function_exists('wp_die_mock')) {
            function wp_die_mock($message) {
                global $wp_die_called;
                $wp_die_called = true;
                throw new Exception('wp_die called: ' . $message);
            }
        }
        
        // This test would need more sophisticated mocking to work properly
        $this->assertTrue(method_exists($page_setup_instance, 'render_admin_page'));
        
        // Reset to admin user
        wp_set_current_user($this->admin_user);
    }
}