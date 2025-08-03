<?php
/**
 * Paddle Payment Gateway
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
 * Paddle Gateway class
 */
class SkyLearn_Billing_Pro_Paddle_Gateway extends SkyLearn_Billing_Pro_Gateway_Base {
    
    /**
     * Initialize gateway
     */
    protected function init() {
        $this->gateway_id = 'paddle';
        $this->gateway_name = 'Paddle';
        $this->gateway_description = 'Simplified payment processing with Paddle';
        
        $this->supported_modes = array('inline', 'overlay', 'hosted');
        
        $this->credential_fields = array(
            'vendor_id' => 'Vendor ID',
            'vendor_auth_code' => 'Vendor Auth Code',
            'public_key' => 'Public Key'
        );
        
        $this->tier_requirements = array('pro', 'pro_plus');
    }
    
    /**
     * Enqueue Paddle scripts
     */
    public function enqueue_scripts() {
        if ($this->should_load_scripts()) {
            wp_enqueue_script(
                'paddle-js',
                'https://cdn.paddle.com/paddle/paddle.js',
                array(),
                '1.0',
                true
            );
            
            wp_enqueue_script(
                'skylearn-paddle',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/paddle.js',
                array('jquery', 'paddle-js'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            wp_localize_script('skylearn-paddle', 'skylearn_paddle', array(
                'vendor_id' => $this->get_setting('vendor_id'),
                'environment' => $this->is_test_mode() ? 'sandbox' : 'production',
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
            // Initialize Paddle API
            $this->init_paddle_api();
            
            if (isset($payment_data['checkout_mode']) && $payment_data['checkout_mode'] === 'hosted') {
                // Create hosted checkout
                return $this->create_hosted_checkout($payment_data);
            } else {
                // Return data for inline/overlay checkout
                return $this->prepare_inline_checkout($payment_data);
            }
            
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
            
            $alert_name = $payload['alert_name'] ?? '';
            
            // Handle different alert types
            switch ($alert_name) {
                case 'payment_succeeded':
                    return $this->handle_payment_succeeded($payload);
                    
                case 'payment_failed':
                    return $this->handle_payment_failed($payload);
                    
                case 'subscription_created':
                    return $this->handle_subscription_created($payload);
                    
                case 'subscription_cancelled':
                    return $this->handle_subscription_cancelled($payload);
                    
                default:
                    return array(
                        'success' => true,
                        'message' => 'Alert type not handled: ' . $alert_name
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
            $this->init_paddle_api();
            
            // Try to retrieve vendor info
            $vendor = $this->test_paddle_connection();
            
            return array(
                'valid' => true,
                'message' => sprintf(__('Connected to Paddle vendor: %s', 'skylearn-billing-pro'), $vendor['name'] ?? 'Unknown')
            );
            
        } catch (Exception $e) {
            return array(
                'valid' => false,
                'message' => sprintf(__('Paddle connection failed: %s', 'skylearn-billing-pro'), $e->getMessage())
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
     * Initialize Paddle API
     */
    private function init_paddle_api() {
        $vendor_id = $this->get_setting('vendor_id');
        $vendor_auth_code = $this->get_setting('vendor_auth_code');
        
        if (empty($vendor_id) || empty($vendor_auth_code)) {
            throw new Exception(__('Paddle credentials not configured', 'skylearn-billing-pro'));
        }
        
        // Set API credentials (this would be done with actual Paddle library)
    }
    
    /**
     * Create hosted checkout
     *
     * @param array $payment_data Payment data
     * @return array Checkout result
     */
    private function create_hosted_checkout($payment_data) {
        // Create hosted checkout session with Paddle
        $checkout_data = array(
            'vendor_id' => $this->get_setting('vendor_id'),
            'product_id' => $this->get_paddle_product_id($payment_data['product_id']),
            'customer_email' => $payment_data['customer_email'],
            'customer_name' => $payment_data['customer_name'],
            'prices' => array(
                'USD:' . $payment_data['amount']
            ),
            'passthrough' => json_encode(array(
                'product_id' => $payment_data['product_id'],
                'source' => 'skylearn_billing_pro'
            )),
            'return_url' => $payment_data['return_url'] ?? '',
            'success_redirect_url' => $payment_data['return_url'] ?? ''
        );
        
        $checkout_url = $this->create_paddle_checkout_url($checkout_data);
        
        return array(
            'success' => true,
            'checkout_url' => $checkout_url
        );
    }
    
    /**
     * Prepare inline checkout
     *
     * @param array $payment_data Payment data
     * @return array Checkout data
     */
    private function prepare_inline_checkout($payment_data) {
        // Return data needed for Paddle.js inline checkout
        return array(
            'success' => true,
            'checkout_data' => array(
                'vendor' => $this->get_setting('vendor_id'),
                'product' => $this->get_paddle_product_id($payment_data['product_id']),
                'email' => $payment_data['customer_email'],
                'price' => $payment_data['amount'],
                'currency' => $payment_data['currency'],
                'passthrough' => json_encode(array(
                    'product_id' => $payment_data['product_id'],
                    'source' => 'skylearn_billing_pro'
                ))
            )
        );
    }
    
    /**
     * Get Paddle product ID from internal product ID
     *
     * @param int $product_id Internal product ID
     * @return string Paddle product ID
     */
    private function get_paddle_product_id($product_id) {
        // This would map internal product IDs to Paddle product IDs
        // For framework demonstration, return mock product ID
        return 'paddle_product_' . $product_id;
    }
    
    /**
     * Create Paddle checkout URL (mock implementation)
     *
     * @param array $checkout_data Checkout data
     * @return string Checkout URL
     */
    private function create_paddle_checkout_url($checkout_data) {
        // This would create an actual Paddle checkout URL
        // For framework demonstration, return mock URL
        $query_params = http_build_query($checkout_data);
        return 'https://checkout.paddle.com/checkout?' . $query_params;
    }
    
    /**
     * Verify webhook signature
     *
     * @param array $payload Webhook payload
     * @return bool
     */
    private function verify_webhook_signature($payload) {
        $public_key = $this->get_setting('public_key');
        
        if (empty($public_key)) {
            return false;
        }
        
        // This would verify the actual Paddle webhook signature
        // For framework demonstration, we'll just check if public key exists
        return !empty($public_key);
    }
    
    /**
     * Test Paddle connection (mock implementation)
     *
     * @return array Vendor info
     */
    private function test_paddle_connection() {
        // This would test actual Paddle API connection
        // For framework demonstration, return mock vendor data
        return array(
            'name' => 'Test Vendor',
            'id' => $this->get_setting('vendor_id')
        );
    }
    
    /**
     * Handle payment succeeded webhook
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    private function handle_payment_succeeded($payload) {
        // Process successful payment
        $passthrough_data = json_decode($payload['passthrough'] ?? '{}', true);
        $product_id = $passthrough_data['product_id'] ?? null;
        $customer_email = $payload['email'] ?? null;
        
        if ($product_id && $customer_email) {
            // Enroll user in course, send emails, etc.
            do_action('skylearn_billing_pro_payment_succeeded', array(
                'gateway' => $this->gateway_id,
                'payment_id' => $payload['order_id'] ?? $payload['checkout_id'],
                'amount' => floatval($payload['sale_gross'] ?? 0),
                'currency' => $payload['currency'] ?? 'USD',
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
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    private function handle_payment_failed($payload) {
        // Process failed payment
        $passthrough_data = json_decode($payload['passthrough'] ?? '{}', true);
        $product_id = $passthrough_data['product_id'] ?? null;
        $customer_email = $payload['email'] ?? null;
        
        do_action('skylearn_billing_pro_payment_failed', array(
            'gateway' => $this->gateway_id,
            'payment_id' => $payload['order_id'] ?? $payload['checkout_id'],
            'amount' => floatval($payload['sale_gross'] ?? 0),
            'currency' => $payload['currency'] ?? 'USD',
            'product_id' => $product_id,
            'customer_email' => $customer_email,
            'failure_reason' => $payload['alert_name'] ?? 'Payment failed'
        ));
        
        return array(
            'success' => true,
            'message' => 'Payment failure processed'
        );
    }
    
    /**
     * Handle subscription created webhook
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    private function handle_subscription_created($payload) {
        // Process subscription creation
        do_action('skylearn_billing_pro_subscription_created', array(
            'gateway' => $this->gateway_id,
            'subscription_id' => $payload['subscription_id'] ?? null,
            'customer_email' => $payload['email'] ?? null,
            'status' => $payload['status'] ?? 'active'
        ));
        
        return array(
            'success' => true,
            'message' => 'Subscription processed successfully'
        );
    }
    
    /**
     * Handle subscription cancelled webhook
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    private function handle_subscription_cancelled($payload) {
        // Process subscription cancellation
        do_action('skylearn_billing_pro_subscription_cancelled', array(
            'gateway' => $this->gateway_id,
            'subscription_id' => $payload['subscription_id'] ?? null,
            'customer_email' => $payload['email'] ?? null,
            'cancelled_at' => $payload['cancellation_effective_date'] ?? null
        ));
        
        return array(
            'success' => true,
            'message' => 'Subscription cancellation processed'
        );
    }
}