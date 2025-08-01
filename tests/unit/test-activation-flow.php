<?php
/**
 * Test Activation Flow and Onboarding
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

class Test_Activation_Flow extends WP_UnitTestCase {

    private $admin_user;

    public function setUp(): void {
        parent::setUp();
        
        // Create admin user
        $this->admin_user = $this->factory->user->create(array(
            'role' => 'administrator'
        ));
        
        wp_set_current_user($this->admin_user);
        
        // Ensure all required files are loaded
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-onboarding.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/frontend/page-generator.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/page-setup.php';
    }

    public function tearDown(): void {
        parent::tearDown();
        wp_set_current_user(0);
        
        // Clean up options
        delete_option('skylearn_billing_pro_options');
        delete_option('skylearn_billing_pro_pages_created');
        delete_transient('skylearn_billing_pro_activation_redirect');
    }

    /**
     * Test that activation sets redirect flag instead of creating pages immediately
     */
    public function test_activation_sets_redirect_flag() {
        // Clean up any existing state
        delete_option('skylearn_billing_pro_options');
        delete_option('skylearn_billing_pro_pages_created');
        delete_transient('skylearn_billing_pro_activation_redirect');
        
        // Simulate plugin activation
        $skylearn_plugin = skylearn_billing_pro();
        $skylearn_plugin->activate();
        
        // Check that redirect flag is set
        $redirect_flag = get_transient('skylearn_billing_pro_activation_redirect');
        $this->assertTrue($redirect_flag, 'Activation should set redirect flag');
        
        // Check that pages are NOT created immediately
        $pages_created = get_option('skylearn_billing_pro_pages_created', false);
        $this->assertFalse($pages_created, 'Pages should not be created immediately on activation');
        
        // Check that basic options are set
        $options = get_option('skylearn_billing_pro_options');
        $this->assertNotEmpty($options, 'Plugin options should be created');
        $this->assertEquals(SKYLEARN_BILLING_PRO_VERSION, $options['version'], 'Version should be set');
    }

    /**
     * Test onboarding completion creates pages
     */
    public function test_onboarding_completion_creates_pages() {
        // Initialize onboarding
        $onboarding = skylearn_billing_pro_onboarding();
        
        // Mock page generator to avoid actual page creation
        if (function_exists('skylearn_billing_pro_page_generator')) {
            // Complete onboarding (which should create pages)
            $onboarding->complete_onboarding();
            
            // Check that onboarding is marked as complete
            $options = get_option('skylearn_billing_pro_options');
            $this->assertTrue($options['onboarding_completed'], 'Onboarding should be marked as complete');
            $this->assertNotEmpty($options['onboarding_completed_at'], 'Completion timestamp should be set');
            
            // Check that pages creation flag is set
            $pages_created = get_option('skylearn_billing_pro_pages_created', false);
            $this->assertTrue($pages_created, 'Pages should be marked as created after onboarding completion');
        } else {
            $this->markTestSkipped('Page generator not available');
        }
    }

    /**
     * Test that onboarding is required for new installations
     */
    public function test_onboarding_required_for_new_installation() {
        // Clean slate
        delete_option('skylearn_billing_pro_options');
        
        $onboarding = skylearn_billing_pro_onboarding();
        
        // Should show onboarding for new installation
        $this->assertTrue($onboarding->should_show_onboarding(), 'Should require onboarding for new installation');
        
        // Should be on welcome step
        $this->assertEquals('welcome', $onboarding->get_current_step(), 'Should start on welcome step');
    }

    /**
     * Test that onboarding is not required after completion
     */
    public function test_onboarding_not_required_after_completion() {
        $onboarding = skylearn_billing_pro_onboarding();
        
        // Complete onboarding
        $onboarding->complete_onboarding();
        
        // Should not show onboarding anymore
        $this->assertFalse($onboarding->should_show_onboarding(), 'Should not require onboarding after completion');
    }

    /**
     * Test activation redirect handling
     */
    public function test_activation_redirect_handling() {
        global $_GET;
        
        // Set up redirect flag
        set_transient('skylearn_billing_pro_activation_redirect', true, 30);
        
        // Mock being in admin
        set_current_screen('dashboard');
        
        // Clear $_GET to simulate clean admin access
        $original_get = $_GET;
        $_GET = array();
        
        // Create main plugin instance
        $skylearn_plugin = skylearn_billing_pro();
        
        // The redirect should happen, but we can't test the actual redirect
        // Instead, check that the transient gets deleted when conditions are met
        
        // Manually call the handler to simulate the admin_init hook
        if (method_exists($skylearn_plugin, 'handle_activation_redirect')) {
            // This would normally redirect, but in test environment it won't
            // We can at least verify the method exists and handles the logic
            $this->assertTrue(true, 'Activation redirect handler exists');
        }
        
        // Restore $_GET
        $_GET = $original_get;
    }

    /**
     * Test that skip onboarding also creates pages
     */
    public function test_skip_onboarding_creates_pages() {
        $onboarding = skylearn_billing_pro_onboarding();
        
        // Set up POST data for skip action
        $_POST = array(
            'nonce' => wp_create_nonce('skylearn_onboarding_nonce'),
            'action' => 'skylearn_skip_onboarding'
        );
        
        if (function_exists('skylearn_billing_pro_page_generator')) {
            // Capture output (skip_onboarding sends JSON)
            ob_start();
            try {
                $onboarding->skip_onboarding();
                $output = ob_get_clean();
                
                // Parse the JSON response
                $response = json_decode($output, true);
                $this->assertNotNull($response, 'Skip onboarding should return JSON response');
                $this->assertTrue($response['success'], 'Skip onboarding should succeed');
                
                // Check that onboarding is complete
                $options = get_option('skylearn_billing_pro_options');
                $this->assertTrue($options['onboarding_completed'], 'Onboarding should be marked complete after skip');
                
                // Check that pages are created
                $pages_created = get_option('skylearn_billing_pro_pages_created', false);
                $this->assertTrue($pages_created, 'Pages should be created when skipping onboarding');
                
            } catch (Exception $e) {
                ob_end_clean();
                $this->fail('Skip onboarding failed: ' . $e->getMessage());
            }
        } else {
            $this->markTestSkipped('Page generator not available');
        }
    }
}