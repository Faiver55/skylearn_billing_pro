<?php
/**
 * Unit tests for Payment Manager
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/payment/class-payment-manager.php';

class Test_Payment_Manager extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Payment Manager instance
     *
     * @var SkyLearn_Billing_Pro_Payment_Manager
     */
    private $payment_manager;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        $this->payment_manager = new SkyLearn_Billing_Pro_Payment_Manager();
    }
    
    /**
     * Test Payment Manager instantiation
     */
    public function test_payment_manager_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Payment_Manager', $this->payment_manager);
    }
    
    /**
     * Test getting supported payment gateways
     */
    public function test_get_supported_gateways() {
        $supported_gateways = $this->payment_manager->get_supported_gateways();
        
        $this->assertIsArray($supported_gateways);
        $this->assertNotEmpty($supported_gateways);
        
        // Check that key payment gateways are supported
        $this->assertArrayHasKey('stripe', $supported_gateways);
        $this->assertArrayHasKey('paddle', $supported_gateways);
        $this->assertArrayHasKey('lemonsqueezy', $supported_gateways);
        $this->assertArrayHasKey('woocommerce', $supported_gateways);
        
        // Check gateway structure
        foreach ($supported_gateways as $gateway_key => $gateway_data) {
            $this->assertArrayHasKey('name', $gateway_data);
            $this->assertArrayHasKey('description', $gateway_data);
            $this->assertArrayHasKey('supports_inline', $gateway_data);
            $this->assertArrayHasKey('supports_overlay', $gateway_data);
            $this->assertArrayHasKey('supports_hosted', $gateway_data);
            $this->assertArrayHasKey('requires_hosted_only', $gateway_data);
            $this->assertArrayHasKey('connector_class', $gateway_data);
            $this->assertArrayHasKey('tier_requirements', $gateway_data);
            $this->assertArrayHasKey('credentials', $gateway_data);
            
            $this->assertIsString($gateway_data['name']);
            $this->assertIsString($gateway_data['description']);
            $this->assertIsBool($gateway_data['supports_inline']);
            $this->assertIsBool($gateway_data['supports_overlay']);
            $this->assertIsBool($gateway_data['supports_hosted']);
            $this->assertIsBool($gateway_data['requires_hosted_only']);
            $this->assertIsString($gateway_data['connector_class']);
            $this->assertIsArray($gateway_data['tier_requirements']);
            $this->assertIsArray($gateway_data['credentials']);
        }
    }
    
    /**
     * Test setting active payment gateway
     */
    public function test_set_active_gateway() {
        // Test setting a valid gateway
        $result = $this->payment_manager->set_active_gateway('stripe');
        $this->assertTrue($result);
        
        // Test setting an invalid gateway
        $result = $this->payment_manager->set_active_gateway('invalid_gateway');
        $this->assertFalse($result);
    }
    
    /**
     * Test getting active payment gateway
     */
    public function test_get_active_gateway() {
        // Initially should be empty
        $active_gateway = $this->payment_manager->get_active_gateway();
        $this->assertEmpty($active_gateway);
        
        // Set active gateway and test retrieval
        $this->payment_manager->set_active_gateway('stripe');
        $active_gateway = $this->payment_manager->get_active_gateway();
        $this->assertEquals('stripe', $active_gateway);
    }
    
    /**
     * Test gateway configuration validation
     */
    public function test_validate_gateway_config() {
        // Test Stripe configuration
        $stripe_config = array(
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_123',
            'webhook_secret' => 'whsec_123'
        );
        
        $result = $this->payment_manager->validate_gateway_config('stripe', $stripe_config);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('valid', $result);
        $this->assertArrayHasKey('errors', $result);
        $this->assertIsBool($result['valid']);
        $this->assertIsArray($result['errors']);
        
        // Test invalid configuration
        $invalid_config = array(
            'publishable_key' => '',
            'secret_key' => '',
            'webhook_secret' => ''
        );
        
        $result = $this->payment_manager->validate_gateway_config('stripe', $invalid_config);
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
    }
    
    /**
     * Test payment processing
     */
    public function test_process_payment() {
        $payment_data = $this->create_mock_payment();
        
        // Test without active gateway
        $result = $this->payment_manager->process_payment($payment_data);
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        
        // Test with active gateway (mocked)
        $this->payment_manager->set_active_gateway('stripe');
        $result = $this->payment_manager->process_payment($payment_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test payment verification
     */
    public function test_verify_payment() {
        $payment_id = 'test_payment_123';
        
        // Test without active gateway
        $result = $this->payment_manager->verify_payment($payment_id);
        $this->assertFalse($result);
        
        // Test with active gateway
        $this->payment_manager->set_active_gateway('stripe');
        $result = $this->payment_manager->verify_payment($payment_id);
        $this->assertIsBool($result);
    }
    
    /**
     * Test getting payment details
     */
    public function test_get_payment_details() {
        $payment_id = 'test_payment_123';
        
        // Test without active gateway
        $details = $this->payment_manager->get_payment_details($payment_id);
        $this->assertNull($details);
        
        // Test with active gateway
        $this->payment_manager->set_active_gateway('stripe');
        $details = $this->payment_manager->get_payment_details($payment_id);
        
        // Should return null or array with payment details
        $this->assertTrue($details === null || is_array($details));
    }
    
    /**
     * Test refund processing
     */
    public function test_process_refund() {
        $payment_id = 'test_payment_123';
        $amount = 50.00;
        $reason = 'Customer request';
        
        // Test without active gateway
        $result = $this->payment_manager->process_refund($payment_id, $amount, $reason);
        $this->assertFalse($result['success']);
        
        // Test with active gateway
        $this->payment_manager->set_active_gateway('stripe');
        $result = $this->payment_manager->process_refund($payment_id, $amount, $reason);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test subscription creation
     */
    public function test_create_subscription() {
        $subscription_data = array(
            'customer_email' => 'test@example.com',
            'plan_id' => 'monthly_plan',
            'payment_method' => 'pm_test_123'
        );
        
        // Test without active gateway
        $result = $this->payment_manager->create_subscription($subscription_data);
        $this->assertFalse($result['success']);
        
        // Test with active gateway
        $this->payment_manager->set_active_gateway('stripe');
        $result = $this->payment_manager->create_subscription($subscription_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test subscription cancellation
     */
    public function test_cancel_subscription() {
        $subscription_id = 'sub_test_123';
        
        // Test without active gateway
        $result = $this->payment_manager->cancel_subscription($subscription_id);
        $this->assertFalse($result['success']);
        
        // Test with active gateway
        $this->payment_manager->set_active_gateway('stripe');
        $result = $this->payment_manager->cancel_subscription($subscription_id);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test webhook signature validation
     */
    public function test_validate_webhook_signature() {
        $payload = json_encode(array('test' => 'data'));
        $signature = 'test_signature';
        $secret = 'webhook_secret';
        
        // Test without active gateway
        $valid = $this->payment_manager->validate_webhook_signature($payload, $signature, $secret);
        $this->assertFalse($valid);
        
        // Test with active gateway
        $this->payment_manager->set_active_gateway('stripe');
        $valid = $this->payment_manager->validate_webhook_signature($payload, $signature, $secret);
        $this->assertIsBool($valid);
    }
    
    /**
     * Test getting payment gateway status
     */
    public function test_get_gateway_status() {
        $status = $this->payment_manager->get_gateway_status();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('active_gateway', $status);
        $this->assertArrayHasKey('configured_gateways', $status);
        $this->assertArrayHasKey('available_gateways', $status);
        
        $this->assertIsArray($status['configured_gateways']);
        $this->assertIsArray($status['available_gateways']);
    }
    
    /**
     * Test checkout form generation
     */
    public function test_generate_checkout_form() {
        $form_data = array(
            'amount' => 99.00,
            'currency' => 'USD',
            'product_id' => 'test_product',
            'customer_email' => 'test@example.com'
        );
        
        // Test without active gateway
        $form_html = $this->payment_manager->generate_checkout_form($form_data);
        $this->assertEmpty($form_html);
        
        // Test with active gateway
        $this->payment_manager->set_active_gateway('stripe');
        $form_html = $this->payment_manager->generate_checkout_form($form_data);
        
        // Should return string (HTML) or empty string
        $this->assertIsString($form_html);
    }
    
    /**
     * Test payment method support check
     */
    public function test_supports_payment_method() {
        // Test inline support
        $supports_inline = $this->payment_manager->supports_payment_method('stripe', 'inline');
        $this->assertTrue($supports_inline);
        
        // Test overlay support
        $supports_overlay = $this->payment_manager->supports_payment_method('stripe', 'overlay');
        $this->assertTrue($supports_overlay);
        
        // Test hosted support for Lemon Squeezy (requires hosted only)
        $supports_hosted = $this->payment_manager->supports_payment_method('lemonsqueezy', 'hosted');
        $this->assertTrue($supports_hosted);
        
        $supports_inline_ls = $this->payment_manager->supports_payment_method('lemonsqueezy', 'inline');
        $this->assertFalse($supports_inline_ls);
    }
    
    /**
     * Test tier requirement validation
     */
    public function test_validate_tier_requirements() {
        // Mock current tier as 'free'
        $current_tier = 'free';
        
        // Test Stripe (available on free tier)
        $valid = $this->payment_manager->validate_tier_requirements('stripe', $current_tier);
        $this->assertTrue($valid);
        
        // Test Paddle (requires pro tier)
        $valid = $this->payment_manager->validate_tier_requirements('paddle', $current_tier);
        $this->assertFalse($valid);
        
        // Test with pro tier
        $valid = $this->payment_manager->validate_tier_requirements('paddle', 'pro');
        $this->assertTrue($valid);
    }
}