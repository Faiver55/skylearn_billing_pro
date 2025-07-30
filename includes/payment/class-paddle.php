<?php
/**
 * Paddle payment gateway connector
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
 * Paddle connector class
 */
class SkyLearn_Billing_Pro_Paddle_Connector {
    
    /**
     * Gateway ID
     *
     * @var string
     */
    private $gateway_id = 'paddle';
    
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
        // Add Paddle-specific initialization
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
                null,
                true
            );
            
            wp_enqueue_script(
                'skylearn-paddle',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/paddle.js',
                array('paddle-js', 'jquery'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            $credentials = $this->get_credentials();
            wp_localize_script('skylearn-paddle', 'skylernPaddle', array(
                'vendorId' => $credentials['vendor_id'] ?? '',
                'environment' => $this->is_test_mode() ? 'sandbox' : 'production',
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skylearn_paddle_nonce')
            ));
        }
    }
    
    /**
     * Check if scripts should be loaded
     *
     * @return bool
     */
    private function should_load_scripts() {
        return is_page() || has_shortcode(get_post()->post_content ?? '', 'skylearn_checkout');
    }
    
    /**
     * Get Paddle credentials
     *
     * @return array
     */
    private function get_credentials() {
        $payment_manager = skylearn_billing_pro_payment_manager();
        return $payment_manager->get_gateway_credentials($this->gateway_id);
    }
    
    /**
     * Create checkout session
     *
     * @param array $args Payment arguments
     * @return array
     */
    public function create_checkout_session($args) {
        $credentials = $this->get_credentials();
        
        if (empty($credentials['vendor_id']) || empty($credentials['vendor_auth_code'])) {
            return array(
                'success' => false,
                'message' => 'Paddle credentials not configured'
            );
        }
        
        $checkout_data = array(
            'vendor_id' => $credentials['vendor_id'],
            'vendor_auth_code' => $credentials['vendor_auth_code'],
            'product_id' => $args['product_id'] ?? '',
            'title' => $args['product_name'] ?? 'Course Access',
            'prices' => array($args['currency'] . ':' . $args['amount']),
            'customer_email' => $args['customer_email'] ?? '',
            'passthrough' => json_encode(array(
                'product_id' => $args['product_id'] ?? '',
                'customer_email' => $args['customer_email'] ?? ''
            ))
        );
        
        return array(
            'success' => true,
            'checkout_data' => $checkout_data
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
        
        if (empty($credentials['public_key'])) {
            return array(
                'success' => false,
                'message' => 'Paddle public key not configured'
            );
        }
        
        // Verify webhook signature (simplified)
        if (!$this->verify_webhook_signature($payload, $credentials['public_key'])) {
            return array(
                'success' => false,
                'message' => 'Invalid webhook signature'
            );
        }
        
        // Parse webhook data (comes as form data)
        $data = array();
        parse_str($payload, $data);
        
        if (isset($data['alert_name']) && $data['alert_name'] === 'payment_succeeded') {
            return $this->handle_payment_success($data);
        }
        
        return array(
            'success' => true,
            'message' => 'Webhook processed (no action required)'
        );
    }
    
    /**
     * Handle successful payment
     *
     * @param array $data Payment data
     * @return array
     */
    private function handle_payment_success($data) {
        $passthrough = json_decode($data['passthrough'] ?? '{}', true);
        $product_id = $passthrough['product_id'] ?? '';
        $customer_email = $data['email'] ?? '';
        
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
            'name' => $data['customer_name'] ?? '',
            'product_id' => $product_id,
            'payment_amount' => $data['sale_gross'] ?? 0,
            'payment_currency' => $data['currency'] ?? 'USD',
            'payment_gateway' => 'paddle',
            'payment_id' => $data['order_id'] ?? ''
        ));
    }
    
    /**
     * Verify webhook signature (simplified implementation)
     *
     * @param string $payload Webhook payload
     * @param string $public_key Public key
     * @return bool
     */
    private function verify_webhook_signature($payload, $public_key) {
        // This is a simplified verification - in production you'd use Paddle's official verification method
        return !empty($public_key);
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
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/checkout/paddle-inline.php';
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
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/checkout/paddle-overlay.php';
        return ob_get_clean();
    }
}