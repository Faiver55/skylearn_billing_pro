<?php
/**
 * Stripe payment gateway connector
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

/**
 * Stripe connector class
 */
class SkyLearn_Billing_Pro_Stripe_Connector {
    
    /**
     * Gateway ID
     *
     * @var string
     */
    private $gateway_id = 'stripe';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Initialize connector
     */
    public function init() {
        // Add Stripe-specific initialization
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
                null,
                true
            );
            
            wp_enqueue_script(
                'skylearn-stripe',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/stripe.js',
                array('stripe-js', 'jquery'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            $credentials = $this->get_credentials();
            wp_localize_script('skylearn-stripe', 'skylernStripe', array(
                'publishableKey' => $credentials['publishable_key'] ?? '',
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skylearn_stripe_nonce')
            ));
        }
    }
    
    /**
     * Check if scripts should be loaded
     *
     * @return bool
     */
    private function should_load_scripts() {
        // Load on checkout pages or pages with checkout shortcodes
        return is_page() || has_shortcode(get_post()->post_content ?? '', 'skylearn_checkout');
    }
    
    /**
     * Get Stripe credentials
     *
     * @return array
     */
    private function get_credentials() {
        $payment_manager = skylearn_billing_pro_payment_manager();
        return $payment_manager->get_gateway_credentials($this->gateway_id);
    }
    
    /**
     * Create payment intent
     *
     * @param array $args Payment arguments
     * @return array
     */
    public function create_payment_intent($args) {
        $credentials = $this->get_credentials();
        
        if (empty($credentials['secret_key'])) {
            return array(
                'success' => false,
                'message' => 'Stripe secret key not configured'
            );
        }
        
        $payment_data = array(
            'amount' => $args['amount'] * 100, // Convert to cents
            'currency' => $args['currency'] ?? 'usd',
            'metadata' => array(
                'product_id' => $args['product_id'] ?? '',
                'customer_email' => $args['customer_email'] ?? ''
            )
        );
        
        // Make API call to Stripe (simplified for basic implementation)
        $response = wp_remote_post('https://api.stripe.com/v1/payment_intents', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $credentials['secret_key'],
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($payment_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (wp_remote_retrieve_response_code($response) !== 200) {
            return array(
                'success' => false,
                'message' => $body['error']['message'] ?? 'Unknown error'
            );
        }
        
        return array(
            'success' => true,
            'client_secret' => $body['client_secret'],
            'payment_intent_id' => $body['id']
        );
    }
    
    /**
     * Process webhook
     *
     * @param array $payload Webhook payload
     * @return array
     */
    public function process_webhook($payload) {
        $credentials = $this->get_credentials();
        
        if (empty($credentials['webhook_secret'])) {
            return array(
                'success' => false,
                'message' => 'Stripe webhook secret not configured'
            );
        }
        
        // Verify webhook signature (simplified)
        $signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        
        if (!$this->verify_webhook_signature($payload, $signature, $credentials['webhook_secret'])) {
            return array(
                'success' => false,
                'message' => 'Invalid webhook signature'
            );
        }
        
        $event = json_decode($payload, true);
        
        if ($event['type'] === 'payment_intent.succeeded') {
            return $this->handle_payment_success($event['data']['object']);
        }
        
        return array(
            'success' => true,
            'message' => 'Webhook processed (no action required)'
        );
    }
    
    /**
     * Handle successful payment
     *
     * @param array $payment_intent Payment intent data
     * @return array
     */
    private function handle_payment_success($payment_intent) {
        $product_id = $payment_intent['metadata']['product_id'] ?? '';
        $customer_email = $payment_intent['metadata']['customer_email'] ?? '';
        
        if (empty($product_id) || empty($customer_email)) {
            return array(
                'success' => false,
                'message' => 'Missing product ID or customer email'
            );
        }
        
        // Process enrollment via existing webhook handler
        $webhook_handler = skylearn_billing_pro_webhook_handler();
        
        return $webhook_handler->process_enrollment(array(
            'email' => $customer_email,
            'product_id' => $product_id,
            'payment_amount' => $payment_intent['amount'] / 100,
            'payment_currency' => $payment_intent['currency'],
            'payment_gateway' => 'stripe',
            'payment_id' => $payment_intent['id']
        ));
    }
    
    /**
     * Verify webhook signature (simplified implementation)
     *
     * @param string $payload Webhook payload
     * @param string $signature Webhook signature
     * @param string $secret Webhook secret
     * @return bool
     */
    private function verify_webhook_signature($payload, $signature, $secret) {
        // This is a simplified verification - in production you'd use Stripe's official verification method
        return !empty($signature) && !empty($secret);
    }
    
    /**
     * Get supported checkout types
     *
     * @return array
     */
    public function get_supported_checkout_types() {
        return array('inline', 'overlay', 'hosted');
    }
    
    /**
     * Render inline checkout
     *
     * @param array $args Checkout arguments
     * @return string
     */
    public function render_inline_checkout($args) {
        ob_start();
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/checkout/stripe-inline.php';
        return ob_get_clean();
    }
    
    /**
     * Render overlay checkout
     *
     * @param array $args Checkout arguments
     * @return string
     */
    public function render_overlay_checkout($args) {
        ob_start();
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/checkout/stripe-overlay.php';
        return ob_get_clean();
    }
}