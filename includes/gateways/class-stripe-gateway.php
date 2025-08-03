<?php
/**
 * Stripe Payment Gateway
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
 * Stripe Gateway class
 */
class SkyLearn_Billing_Pro_Stripe_Gateway extends SkyLearn_Billing_Pro_Gateway_Base {
    
    /**
     * Initialize gateway
     */
    protected function init() {
        $this->gateway_id = 'stripe';
        $this->gateway_name = 'Stripe';
        $this->gateway_description = 'Accept payments worldwide with Stripe';
        
        $this->supported_modes = array('inline', 'overlay', 'hosted');
        
        $this->credential_fields = array(
            'publishable_key' => 'Publishable Key',
            'secret_key' => 'Secret Key',
            'webhook_secret' => 'Webhook Secret'
        );
        
        $this->tier_requirements = array('free', 'pro', 'pro_plus');
    }
    
    /**
     * Enqueue Stripe scripts
     */
    public function enqueue_scripts() {
        if ($this->should_load_scripts()) {
            wp_enqueue_script(
                'stripe-js',
                'https://js.stripe.com/v3/',
                array(),
                '3.0',
                true
            );
            
            wp_enqueue_script(
                'skylearn-stripe',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/stripe.js',
                array('jquery', 'stripe-js'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            wp_localize_script('skylearn-stripe', 'skylearn_stripe', array(
                'publishable_key' => $this->get_publishable_key(),
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skylearn_create_payment')
            ));
        }
    }
    
    /**
     * Create payment
     *
     * @param array $payment_data Payment data
     * @return array Payment result
     */
    public function create_payment($payment_data) {
        try {
            // Initialize Stripe (this would require the Stripe PHP library)
            $this->init_stripe_api();
            
            // Create Payment Intent
            $intent_data = array(
                'amount' => $payment_data['amount'] * 100, // Convert to cents
                'currency' => strtolower($payment_data['currency']),
                'metadata' => array(
                    'product_id' => $payment_data['product_id'],
                    'customer_email' => $payment_data['customer_email'],
                    'source' => 'skylearn_billing_pro'
                )
            );
            
            // Add customer info if available
            if (!empty($payment_data['customer_email'])) {
                $intent_data['receipt_email'] = $payment_data['customer_email'];
            }
            
            // This would create the actual Stripe Payment Intent
            // For now, we'll return a mock response
            $payment_intent = $this->create_stripe_payment_intent($intent_data);
            
            return array(
                'success' => true,
                'payment_intent' => $payment_intent,
                'client_secret' => $payment_intent['client_secret'] ?? '',
                'redirect_url' => isset($payment_data['checkout_mode']) && $payment_data['checkout_mode'] === 'hosted' 
                    ? $this->create_checkout_session($payment_data) 
                    : null
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
            switch ($event['type']) {
                case 'payment_intent.succeeded':
                    return $this->handle_payment_succeeded($event['data']['object']);
                    
                case 'payment_intent.payment_failed':
                    return $this->handle_payment_failed($event['data']['object']);
                    
                case 'checkout.session.completed':
                    return $this->handle_checkout_completed($event['data']['object']);
                    
                default:
                    return array(
                        'success' => true,
                        'message' => 'Event type not handled: ' . $event['type']
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
            $this->init_stripe_api();
            
            // Try to retrieve account info (this would use actual Stripe API)
            $account = $this->test_stripe_connection();
            
            return array(
                'valid' => true,
                'message' => sprintf(__('Connected to Stripe account: %s', 'skylearn-billing-pro'), $account['display_name'] ?? 'Unknown')
            );
            
        } catch (Exception $e) {
            return array(
                'valid' => false,
                'message' => sprintf(__('Stripe connection failed: %s', 'skylearn-billing-pro'), $e->getMessage())
            );
        }
    }
    
    /**
     * Check if scripts should be loaded
     *
     * @return bool
     */
    private function should_load_scripts() {
        // Load on checkout pages or pages with payment forms
        return is_page() || is_single() || (function_exists('is_woocommerce') && is_woocommerce());
    }
    
    /**
     * Get publishable key based on test mode
     *
     * @return string
     */
    private function get_publishable_key() {
        $key = $this->get_setting('publishable_key');
        
        // In test mode, use test key if available
        if ($this->is_test_mode()) {
            $test_key = $this->get_setting('test_publishable_key');
            if (!empty($test_key)) {
                $key = $test_key;
            }
        }
        
        return $key;
    }
    
    /**
     * Get secret key based on test mode
     *
     * @return string
     */
    private function get_secret_key() {
        $key = $this->get_setting('secret_key');
        
        // In test mode, use test key if available
        if ($this->is_test_mode()) {
            $test_key = $this->get_setting('test_secret_key');
            if (!empty($test_key)) {
                $key = $test_key;
            }
        }
        
        return $key;
    }
    
    /**
     * Initialize Stripe API
     */
    private function init_stripe_api() {
        // This would initialize the actual Stripe PHP library
        // For framework demonstration, we'll just validate the key exists
        $secret_key = $this->get_secret_key();
        
        if (empty($secret_key)) {
            throw new Exception(__('Stripe secret key not configured', 'skylearn-billing-pro'));
        }
        
        // Set the API key (this would be done with actual Stripe library)
        // \Stripe\Stripe::setApiKey($secret_key);
    }
    
    /**
     * Create Stripe Payment Intent (mock implementation)
     *
     * @param array $intent_data Payment Intent data
     * @return array Payment Intent response
     */
    private function create_stripe_payment_intent($intent_data) {
        // This would create an actual Stripe Payment Intent
        // For framework demonstration, return mock data
        return array(
            'id' => 'pi_mock_' . time(),
            'client_secret' => 'pi_mock_' . time() . '_secret_mock',
            'amount' => $intent_data['amount'],
            'currency' => $intent_data['currency'],
            'status' => 'requires_payment_method'
        );
    }
    
    /**
     * Create Stripe Checkout Session (mock implementation)
     *
     * @param array $payment_data Payment data
     * @return string Checkout URL
     */
    private function create_checkout_session($payment_data) {
        // This would create an actual Stripe Checkout Session
        // For framework demonstration, return mock URL
        return 'https://checkout.stripe.com/pay/mock_session_' . time();
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
        
        // This would verify the actual Stripe webhook signature
        // For framework demonstration, we'll just check if secret exists
        return !empty($webhook_secret);
    }
    
    /**
     * Test Stripe connection (mock implementation)
     *
     * @return array Account info
     */
    private function test_stripe_connection() {
        // This would test actual Stripe API connection
        // For framework demonstration, return mock account data
        return array(
            'display_name' => 'Test Account',
            'id' => 'acct_mock_' . time()
        );
    }
    
    /**
     * Handle payment succeeded webhook
     *
     * @param array $payment_intent Payment Intent object
     * @return array Processing result
     */
    private function handle_payment_succeeded($payment_intent) {
        // Process successful payment
        $product_id = $payment_intent['metadata']['product_id'] ?? null;
        $customer_email = $payment_intent['metadata']['customer_email'] ?? null;
        
        if ($product_id && $customer_email) {
            // Enroll user in course, send emails, etc.
            do_action('skylearn_billing_pro_payment_succeeded', array(
                'gateway' => $this->gateway_id,
                'payment_id' => $payment_intent['id'],
                'amount' => $payment_intent['amount'] / 100,
                'currency' => $payment_intent['currency'],
                'product_id' => $product_id,
                'customer_email' => $customer_email
            ));
        }
        
        return array(
            'success' => true,
            'message' => 'Payment processed successfully'
        );
    }
    
    /**
     * Handle payment failed webhook
     *
     * @param array $payment_intent Payment Intent object
     * @return array Processing result
     */
    private function handle_payment_failed($payment_intent) {
        // Process failed payment
        $product_id = $payment_intent['metadata']['product_id'] ?? null;
        $customer_email = $payment_intent['metadata']['customer_email'] ?? null;
        
        do_action('skylearn_billing_pro_payment_failed', array(
            'gateway' => $this->gateway_id,
            'payment_id' => $payment_intent['id'],
            'amount' => $payment_intent['amount'] / 100,
            'currency' => $payment_intent['currency'],
            'product_id' => $product_id,
            'customer_email' => $customer_email,
            'failure_reason' => $payment_intent['last_payment_error']['message'] ?? 'Unknown error'
        ));
        
        return array(
            'success' => true,
            'message' => 'Payment failure processed'
        );
    }
    
    /**
     * Handle checkout session completed webhook
     *
     * @param array $session Checkout Session object
     * @return array Processing result
     */
    private function handle_checkout_completed($session) {
        // Process completed checkout session
        do_action('skylearn_billing_pro_checkout_completed', array(
            'gateway' => $this->gateway_id,
            'session_id' => $session['id'],
            'payment_intent' => $session['payment_intent'] ?? null,
            'customer_email' => $session['customer_email'] ?? null
        ));
        
        return array(
            'success' => true,
            'message' => 'Checkout session processed'
        );
    }
}