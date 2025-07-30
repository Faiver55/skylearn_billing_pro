<?php
/**
 * Lemon Squeezy payment gateway connector
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
 * Lemon Squeezy connector class
 */
class SkyLearn_Billing_Pro_LemonSqueezy_Connector {
    
    /**
     * Gateway ID
     *
     * @var string
     */
    private $gateway_id = 'lemonsqueezy';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize connector
     */
    public function init() {
        // Add Lemon Squeezy-specific initialization
    }
    
    /**
     * Get Lemon Squeezy credentials
     *
     * @return array
     */
    private function get_credentials() {
        $payment_manager = skylearn_billing_pro_payment_manager();
        return $payment_manager->get_gateway_credentials($this->gateway_id);
    }
    
    /**
     * Create checkout URL (hosted only)
     *
     * @param array $args Payment arguments
     * @return array
     */
    public function create_checkout_url($args) {
        $credentials = $this->get_credentials();
        
        if (empty($credentials['api_key']) || empty($credentials['store_id'])) {
            return array(
                'success' => false,
                'message' => 'Lemon Squeezy credentials not configured'
            );
        }
        
        $checkout_data = array(
            'data' => array(
                'type' => 'checkouts',
                'attributes' => array(
                    'product_options' => array(
                        'name' => $args['product_name'] ?? 'Course Access',
                        'description' => $args['product_description'] ?? ''
                    ),
                    'checkout_options' => array(
                        'embed' => false,
                        'media' => false,
                        'logo' => true
                    ),
                    'checkout_data' => array(
                        'email' => $args['customer_email'] ?? '',
                        'name' => $args['customer_name'] ?? '',
                        'custom' => array(
                            'product_id' => $args['product_id'] ?? ''
                        )
                    ),
                    'expires_at' => null,
                    'preview' => false,
                    'test_mode' => $this->is_test_mode()
                ),
                'relationships' => array(
                    'store' => array(
                        'data' => array(
                            'type' => 'stores',
                            'id' => $credentials['store_id']
                        )
                    ),
                    'variant' => array(
                        'data' => array(
                            'type' => 'variants',
                            'id' => $args['variant_id'] ?? ''
                        )
                    )
                )
            )
        );
        
        $response = wp_remote_post('https://api.lemonsqueezy.com/v1/checkouts', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $credentials['api_key'],
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json'
            ),
            'body' => json_encode($checkout_data),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (wp_remote_retrieve_response_code($response) !== 201) {
            return array(
                'success' => false,
                'message' => $body['errors'][0]['detail'] ?? 'Unknown error'
            );
        }
        
        return array(
            'success' => true,
            'checkout_url' => $body['data']['attributes']['url']
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
                'message' => 'Lemon Squeezy webhook secret not configured'
            );
        }
        
        // Verify webhook signature (simplified)
        $signature = $_SERVER['HTTP_X_SIGNATURE'] ?? '';
        
        if (!$this->verify_webhook_signature($payload, $signature, $credentials['webhook_secret'])) {
            return array(
                'success' => false,
                'message' => 'Invalid webhook signature'
            );
        }
        
        $event = json_decode($payload, true);
        
        if ($event['meta']['event_name'] === 'order_created') {
            return $this->handle_order_created($event['data']);
        }
        
        return array(
            'success' => true,
            'message' => 'Webhook processed (no action required)'
        );
    }
    
    /**
     * Handle order created event
     *
     * @param array $order Order data
     * @return array
     */
    private function handle_order_created($order) {
        $product_id = $order['attributes']['first_order_item']['product_id'] ?? '';
        $customer_email = $order['attributes']['user_email'] ?? '';
        $customer_name = $order['attributes']['user_name'] ?? '';
        
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
            'name' => $customer_name,
            'product_id' => $product_id,
            'payment_amount' => $order['attributes']['total'] / 100,
            'payment_currency' => $order['attributes']['currency'],
            'payment_gateway' => 'lemonsqueezy',
            'payment_id' => $order['id']
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
        // This is a simplified verification - in production you'd use proper HMAC verification
        return !empty($signature) && !empty($secret);
    }
    
    /**
     * Check if test mode is enabled
     *
     * @return bool
     */
    private function is_test_mode() {
        $options = get_option('skylearn_billing_pro_options', array());
        return $options['general_settings']['test_mode'] ?? true;
    }
    
    /**
     * Get supported checkout types (hosted only)
     *
     * @return array
     */
    public function get_supported_checkout_types() {
        return array('hosted');
    }
    
    /**
     * Render hosted checkout (redirect warning)
     *
     * @param array $args Checkout arguments
     * @return string
     */
    public function render_hosted_checkout($args) {
        ob_start();
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/checkout/lemonsqueezy-hosted.php';
        return ob_get_clean();
    }
    
    /**
     * Get checkout notice for hosted-only gateway
     *
     * @return string
     */
    public function get_checkout_notice() {
        return __('You will be redirected to Lemon Squeezy to complete your payment securely.', 'skylearn-billing-pro');
    }
}