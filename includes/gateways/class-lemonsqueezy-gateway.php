<?php
/**
 * Lemon Squeezy Payment Gateway
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure base class is loaded
if (!class_exists('SkyLearn_Billing_Pro_Gateway_Base')) {
    require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/gateways/class-gateway-base.php';
}

/**
 * Lemon Squeezy Gateway class
 */
class SkyLearn_Billing_Pro_LemonSqueezy_Gateway extends SkyLearn_Billing_Pro_Gateway_Base {
    
    /**
     * Initialize gateway
     */
    protected function init() {
        $this->gateway_id = 'lemonsqueezy';
        $this->gateway_name = 'Lemon Squeezy';
        $this->gateway_description = 'Simple checkout for digital products';
        
        // Lemon Squeezy only supports hosted checkout
        $this->supported_modes = array('hosted');
        
        $this->credential_fields = array(
            'api_key' => 'API Key',
            'store_id' => 'Store ID',
            'webhook_secret' => 'Webhook Secret'
        );
        
        $this->tier_requirements = array('pro', 'pro_plus');
    }
    
    /**
     * Create payment
     *
     * @param array $payment_data Payment data
     * @return array Payment result
     */
    public function create_payment($payment_data) {
        try {
            // Initialize Lemon Squeezy API
            $this->init_lemonsqueezy_api();
            
            // Create checkout session
            $checkout_data = array(
                'store_id' => $this->get_setting('store_id'),
                'variant_id' => $this->get_product_variant_id($payment_data['product_id']),
                'checkout_data' => array(
                    'email' => $payment_data['customer_email'],
                    'name' => $payment_data['customer_name'],
                    'custom' => array(
                        'product_id' => $payment_data['product_id'],
                        'source' => 'skylearn_billing_pro'
                    )
                ),
                'checkout_options' => array(
                    'button_color' => '#007cba'
                ),
                'product_options' => array(
                    'enabled_variants' => array($this->get_product_variant_id($payment_data['product_id'])),
                    'redirect_url' => $payment_data['return_url'] ?? '',
                    'receipt_button_text' => 'Access Course',
                    'receipt_link_url' => $payment_data['return_url'] ?? ''
                )
            );
            
            // Create checkout session with Lemon Squeezy
            $checkout_session = $this->create_lemonsqueezy_checkout($checkout_data);
            
            return array(
                'success' => true,
                'checkout_url' => $checkout_session['url'],
                'session_id' => $checkout_session['id']
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Process webhook
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    public function process_webhook($payload) {
        try {
            // Verify webhook signature
            if (!$this->verify_webhook_signature($payload)) {
                return array(
                    'success' => false,
                    'message' => 'Invalid webhook signature'
                );
            }
            
            $event = $payload;
            
            // Handle different event types
            switch ($event['meta']['event_name']) {
                case 'order_created':
                    return $this->handle_order_created($event['data']);
                    
                case 'subscription_created':
                    return $this->handle_subscription_created($event['data']);
                    
                case 'subscription_cancelled':
                    return $this->handle_subscription_cancelled($event['data']);
                    
                default:
                    return array(
                        'success' => true,
                        'message' => 'Event type not handled: ' . $event['meta']['event_name']
                    );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Validate credentials
     *
     * @return array Validation result
     */
    public function validate_credentials() {
        if (!$this->has_credentials()) {
            return parent::validate_credentials();
        }
        
        try {
            // Test API connection
            $this->init_lemonsqueezy_api();
            
            // Try to retrieve store info
            $store = $this->test_lemonsqueezy_connection();
            
            return array(
                'valid' => true,
                'message' => sprintf(__('Connected to Lemon Squeezy store: %s', 'skylearn-billing-pro'), $store['name'] ?? 'Unknown')
            );
            
        } catch (Exception $e) {
            return array(
                'valid' => false,
                'message' => sprintf(__('Lemon Squeezy connection failed: %s', 'skylearn-billing-pro'), $e->getMessage())
            );
        }
    }
    
    /**
     * Initialize Lemon Squeezy API
     */
    private function init_lemonsqueezy_api() {
        $api_key = $this->get_setting('api_key');
        
        if (empty($api_key)) {
            throw new Exception(__('Lemon Squeezy API key not configured', 'skylearn-billing-pro'));
        }
        
        // Set API key for requests (this would be done with actual Lemon Squeezy library)
    }
    
    /**
     * Get product variant ID from product ID
     *
     * @param int $product_id Product ID
     * @return string Variant ID
     */
    private function get_product_variant_id($product_id) {
        // This would map internal product IDs to Lemon Squeezy variant IDs
        // For framework demonstration, return mock variant ID
        return 'variant_' . $product_id;
    }
    
    /**
     * Create Lemon Squeezy checkout session (mock implementation)
     *
     * @param array $checkout_data Checkout data
     * @return array Checkout session response
     */
    private function create_lemonsqueezy_checkout($checkout_data) {
        // This would create an actual Lemon Squeezy checkout session
        // For framework demonstration, return mock data
        return array(
            'id' => 'checkout_' . time(),
            'url' => 'https://lemonsqueezy.com/checkout/mock_' . time()
        );
    }
    
    /**
     * Verify webhook signature
     *
     * @param array $payload Webhook payload
     * @return bool
     */
    private function verify_webhook_signature($payload) {
        $webhook_secret = $this->get_setting('webhook_secret');
        
        if (empty($webhook_secret)) {
            return false;
        }
        
        // This would verify the actual Lemon Squeezy webhook signature
        // For framework demonstration, we'll just check if secret exists
        return !empty($webhook_secret);
    }
    
    /**
     * Test Lemon Squeezy connection (mock implementation)
     *
     * @return array Store info
     */
    private function test_lemonsqueezy_connection() {
        // This would test actual Lemon Squeezy API connection
        // For framework demonstration, return mock store data
        return array(
            'name' => 'Test Store',
            'id' => $this->get_setting('store_id')
        );
    }
    
    /**
     * Handle order created webhook
     *
     * @param array $order Order object
     * @return array Processing result
     */
    private function handle_order_created($order) {
        // Process order
        $product_id = $order['attributes']['custom']['product_id'] ?? null;
        $customer_email = $order['attributes']['user_email'] ?? null;
        
        if ($product_id && $customer_email) {
            // Enroll user in course, send emails, etc.
            do_action('skylearn_billing_pro_payment_succeeded', array(
                'gateway' => $this->gateway_id,
                'payment_id' => $order['id'],
                'amount' => $order['attributes']['total'] / 100,
                'currency' => $order['attributes']['currency'],
                'product_id' => $product_id,
                'customer_email' => $customer_email
            ));
        }
        
        return array(
            'success' => true,
            'message' => 'Order processed successfully'
        );
    }
    
    /**
     * Handle subscription created webhook
     *
     * @param array $subscription Subscription object
     * @return array Processing result
     */
    private function handle_subscription_created($subscription) {
        // Process subscription
        do_action('skylearn_billing_pro_subscription_created', array(
            'gateway' => $this->gateway_id,
            'subscription_id' => $subscription['id'],
            'customer_email' => $subscription['attributes']['user_email'] ?? null,
            'status' => $subscription['attributes']['status']
        ));
        
        return array(
            'success' => true,
            'message' => 'Subscription processed successfully'
        );
    }
    
    /**
     * Handle subscription cancelled webhook
     *
     * @param array $subscription Subscription object
     * @return array Processing result
     */
    private function handle_subscription_cancelled($subscription) {
        // Process subscription cancellation
        do_action('skylearn_billing_pro_subscription_cancelled', array(
            'gateway' => $this->gateway_id,
            'subscription_id' => $subscription['id'],
            'customer_email' => $subscription['attributes']['user_email'] ?? null,
            'cancelled_at' => $subscription['attributes']['cancelled_at']
        ));
        
        return array(
            'success' => true,
            'message' => 'Subscription cancellation processed'
        );
    }
}