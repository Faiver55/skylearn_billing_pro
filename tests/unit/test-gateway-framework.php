<?php
/**
 * Unit tests for Gateway Framework
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Load gateway framework
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/gateways/class-gateway-base.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/gateways/class-gateway-registry.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/gateways/class-stripe-gateway.php';

class Test_Gateway_Framework extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Initialize gateway registry
        SkyLearn_Billing_Pro_Gateway_Registry::init();
    }
    
    /**
     * Test Gateway Registry initialization
     */
    public function test_gateway_registry_init() {
        $this->assertTrue(class_exists('SkyLearn_Billing_Pro_Gateway_Registry'));
    }
    
    /**
     * Test gateway registration
     */
    public function test_gateway_registration() {
        // Register a test gateway
        SkyLearn_Billing_Pro_Gateway_Registry::register_gateway(
            'test_gateway', 
            'SkyLearn_Billing_Pro_Stripe_Gateway'
        );
        
        $this->assertTrue(SkyLearn_Billing_Pro_Gateway_Registry::is_registered('test_gateway'));
        
        // Clean up
        SkyLearn_Billing_Pro_Gateway_Registry::unregister_gateway('test_gateway');
    }
    
    /**
     * Test Stripe gateway instantiation
     */
    public function test_stripe_gateway_instantiation() {
        $gateway = new SkyLearn_Billing_Pro_Stripe_Gateway();
        
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Stripe_Gateway', $gateway);
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Gateway_Base', $gateway);
        
        $this->assertEquals('stripe', $gateway->get_id());
        $this->assertEquals('Stripe', $gateway->get_name());
        $this->assertTrue($gateway->supports_inline());
        $this->assertTrue($gateway->supports_overlay());
        $this->assertTrue($gateway->supports_hosted());
        $this->assertFalse($gateway->requires_hosted_only());
    }
    
    /**
     * Test gateway credential fields
     */
    public function test_gateway_credential_fields() {
        $gateway = new SkyLearn_Billing_Pro_Stripe_Gateway();
        $fields = $gateway->get_credential_fields();
        
        $this->assertIsArray($fields);
        $this->assertArrayHasKey('publishable_key', $fields);
        $this->assertArrayHasKey('secret_key', $fields);
        $this->assertArrayHasKey('webhook_secret', $fields);
    }
    
    /**
     * Test gateway availability check
     */
    public function test_gateway_availability() {
        $gateway = new SkyLearn_Billing_Pro_Stripe_Gateway();
        
        // Gateway should be available for all tiers (free, pro, pro_plus)
        $this->assertTrue($gateway->is_available());
    }
    
    /**
     * Test gateway notices
     */
    public function test_gateway_notices() {
        $gateway = new SkyLearn_Billing_Pro_Stripe_Gateway();
        $notices = $gateway->get_notices();
        
        $this->assertIsArray($notices);
        
        // Should have error notice for missing credentials
        $has_credential_error = false;
        foreach ($notices as $notice) {
            if ($notice['type'] === 'error' && strpos($notice['message'], 'credentials') !== false) {
                $has_credential_error = true;
                break;
            }
        }
        $this->assertTrue($has_credential_error);
    }
    
    /**
     * Test gateway admin config
     */
    public function test_gateway_admin_config() {
        $gateway = new SkyLearn_Billing_Pro_Stripe_Gateway();
        $config = $gateway->get_admin_config();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('id', $config);
        $this->assertArrayHasKey('name', $config);
        $this->assertArrayHasKey('description', $config);
        $this->assertArrayHasKey('supports_inline', $config);
        $this->assertArrayHasKey('supports_overlay', $config);
        $this->assertArrayHasKey('supports_hosted', $config);
        $this->assertArrayHasKey('requires_hosted_only', $config);
        $this->assertArrayHasKey('tier_requirements', $config);
        $this->assertArrayHasKey('credentials', $config);
        $this->assertArrayHasKey('is_available', $config);
        $this->assertArrayHasKey('is_enabled', $config);
        $this->assertArrayHasKey('has_credentials', $config);
        $this->assertArrayHasKey('notices', $config);
        
        $this->assertEquals('stripe', $config['id']);
        $this->assertEquals('Stripe', $config['name']);
    }
    
    /**
     * Test gateway registry get all gateways
     */
    public function test_registry_get_all_gateways() {
        // Register Stripe gateway
        SkyLearn_Billing_Pro_Gateway_Registry::register_gateway(
            'stripe', 
            'SkyLearn_Billing_Pro_Stripe_Gateway'
        );
        
        $gateways = SkyLearn_Billing_Pro_Gateway_Registry::get_all_gateways();
        
        $this->assertIsArray($gateways);
        $this->assertArrayHasKey('stripe', $gateways);
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Stripe_Gateway', $gateways['stripe']);
    }
    
    /**
     * Test gateway registry get available gateways
     */
    public function test_registry_get_available_gateways() {
        // Register Stripe gateway
        SkyLearn_Billing_Pro_Gateway_Registry::register_gateway(
            'stripe', 
            'SkyLearn_Billing_Pro_Stripe_Gateway'
        );
        
        $gateways = SkyLearn_Billing_Pro_Gateway_Registry::get_available_gateways();
        
        $this->assertIsArray($gateways);
        $this->assertArrayHasKey('stripe', $gateways);
    }
    
    /**
     * Test gateway registry admin configs
     */
    public function test_registry_admin_configs() {
        // Register Stripe gateway
        SkyLearn_Billing_Pro_Gateway_Registry::register_gateway(
            'stripe', 
            'SkyLearn_Billing_Pro_Stripe_Gateway'
        );
        
        $configs = SkyLearn_Billing_Pro_Gateway_Registry::get_admin_configs();
        
        $this->assertIsArray($configs);
        $this->assertArrayHasKey('stripe', $configs);
        $this->assertIsArray($configs['stripe']);
        $this->assertEquals('stripe', $configs['stripe']['id']);
        $this->assertEquals('Stripe', $configs['stripe']['name']);
    }
    
    /**
     * Test payment creation with gateway registry
     */
    public function test_payment_creation() {
        // Register Stripe gateway
        SkyLearn_Billing_Pro_Gateway_Registry::register_gateway(
            'stripe', 
            'SkyLearn_Billing_Pro_Stripe_Gateway'
        );
        
        // Mock payment data
        $payment_data = array(
            'amount' => 99.99,
            'currency' => 'USD',
            'product_id' => 123,
            'customer_email' => 'test@example.com',
            'customer_name' => 'Test Customer'
        );
        
        // This should fail because credentials are not configured
        $result = SkyLearn_Billing_Pro_Gateway_Registry::create_payment('stripe', $payment_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('message', $result);
        $this->assertStringContainsString('credentials', $result['message']);
    }
    
    /**
     * Test webhook processing with gateway registry
     */
    public function test_webhook_processing() {
        // Register Stripe gateway
        SkyLearn_Billing_Pro_Gateway_Registry::register_gateway(
            'stripe', 
            'SkyLearn_Billing_Pro_Stripe_Gateway'
        );
        
        // Mock webhook payload
        $payload = array(
            'type' => 'payment_intent.succeeded',
            'data' => array(
                'object' => array(
                    'id' => 'pi_test_123',
                    'amount' => 9999,
                    'currency' => 'usd',
                    'metadata' => array(
                        'product_id' => '123',
                        'customer_email' => 'test@example.com'
                    )
                )
            )
        );
        
        // This should fail because gateway is not enabled
        $result = SkyLearn_Billing_Pro_Gateway_Registry::process_webhook('stripe', $payload);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertFalse($result['success']);
    }
}