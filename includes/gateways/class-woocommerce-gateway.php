<?php
/**
 * WooCommerce Payment Gateway
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
 * WooCommerce Gateway class
 */
class SkyLearn_Billing_Pro_WooCommerce_Gateway extends SkyLearn_Billing_Pro_Gateway_Base {
    
    /**
     * Initialize gateway
     */
    protected function init() {
        $this->gateway_id = 'woocommerce';
        $this->gateway_name = 'WooCommerce';
        $this->gateway_description = 'Integrate with WooCommerce checkout';
        
        $this->supported_modes = array('inline', 'hosted');
        
        $this->credential_fields = array(
            'consumer_key' => 'Consumer Key',
            'consumer_secret' => 'Consumer Secret'
        );
        
        $this->tier_requirements = array('free', 'pro', 'pro_plus');
    }
    
    /**
     * Check if gateway is available
     *
     * @return bool
     */
    public function is_available() {
        // WooCommerce gateway requires WooCommerce to be active
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        return parent::is_available();
    }
    
    /**
     * Get gateway notices
     *
     * @return array
     */
    public function get_notices() {
        $notices = parent::get_notices();
        
        // Add WooCommerce requirement notice
        if (!class_exists('WooCommerce')) {
            $notices[] = array(
                'type' => 'error',
                'message' => __('WooCommerce plugin is required for the WooCommerce payment gateway.', 'skylearn-billing-pro')
            );
        }
        
        return $notices;
    }
    
    /**
     * Create payment
     *
     * @param array $payment_data Payment data
     * @return array Payment result
     */
    public function create_payment($payment_data) {
        try {
            // Check WooCommerce availability
            if (!class_exists('WooCommerce')) {
                throw new Exception(__('WooCommerce is not available', 'skylearn-billing-pro'));
            }
            
            // Initialize WooCommerce API
            $this->init_woocommerce_api();
            
            if (isset($payment_data['checkout_mode']) && $payment_data['checkout_mode'] === 'hosted') {
                // Create WooCommerce product and redirect to checkout
                return $this->create_woocommerce_checkout($payment_data);
            } else {
                // Create WooCommerce order via API
                return $this->create_woocommerce_order($payment_data);
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
            // WooCommerce doesn't use traditional webhooks, but we can handle order status changes
            $order_id = $payload['order_id'] ?? null;
            $status = $payload['status'] ?? null;
            
            if (!$order_id) {
                return array(
                    'success' => false,
                    'message' => 'Order ID not provided'
                );
            }
            
            // Handle different order statuses
            switch ($status) {
                case 'completed':
                case 'processing':
                    return $this->handle_order_completed($order_id);
                    
                case 'cancelled':
                case 'failed':
                    return $this->handle_order_failed($order_id);
                    
                default:
                    return array(
                        'success' => true,
                        'message' => 'Order status not handled: ' . $status
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
        
        if (!class_exists('WooCommerce')) {
            return array(
                'valid' => false,
                'message' => __('WooCommerce is not installed or activated', 'skylearn-billing-pro')
            );
        }
        
        try {
            // Test WooCommerce API connection
            $this->init_woocommerce_api();
            
            // Try to retrieve store info
            $store_info = $this->test_woocommerce_connection();
            
            return array(
                'valid' => true,
                'message' => sprintf(__('Connected to WooCommerce store: %s', 'skylearn-billing-pro'), $store_info['name'] ?? 'Unknown')
            );
            
        } catch (Exception $e) {
            return array(
                'valid' => false,
                'message' => sprintf(__('WooCommerce connection failed: %s', 'skylearn-billing-pro'), $e->getMessage())
            );
        }
    }
    
    /**
     * Initialize WooCommerce API
     */
    private function init_woocommerce_api() {
        $consumer_key = $this->get_setting('consumer_key');
        $consumer_secret = $this->get_setting('consumer_secret');
        
        if (empty($consumer_key) || empty($consumer_secret)) {
            throw new Exception(__('WooCommerce API credentials not configured', 'skylearn-billing-pro'));
        }
        
        // Initialize WooCommerce REST API client (this would use actual WooCommerce library)
    }
    
    /**
     * Create WooCommerce checkout
     *
     * @param array $payment_data Payment data
     * @return array Checkout result
     */
    private function create_woocommerce_checkout($payment_data) {
        // Create or get existing WooCommerce product
        $product_id = $this->get_or_create_wc_product($payment_data);
        
        // Add product to cart and redirect to checkout
        $checkout_url = $this->add_to_cart_and_get_checkout_url($product_id, $payment_data);
        
        return array(
            'success' => true,
            'checkout_url' => $checkout_url,
            'product_id' => $product_id
        );
    }
    
    /**
     * Create WooCommerce order
     *
     * @param array $payment_data Payment data
     * @return array Order result
     */
    private function create_woocommerce_order($payment_data) {
        // Create order via WooCommerce API
        $order_data = array(
            'status' => 'pending',
            'currency' => $payment_data['currency'],
            'customer_id' => $this->get_or_create_wc_customer($payment_data),
            'billing' => array(
                'first_name' => explode(' ', $payment_data['customer_name'])[0] ?? '',
                'last_name' => explode(' ', $payment_data['customer_name'], 2)[1] ?? '',
                'email' => $payment_data['customer_email']
            ),
            'line_items' => array(
                array(
                    'product_id' => $this->get_or_create_wc_product($payment_data),
                    'quantity' => 1,
                    'total' => $payment_data['amount']
                )
            ),
            'meta_data' => array(
                array(
                    'key' => '_skylearn_product_id',
                    'value' => $payment_data['product_id']
                ),
                array(
                    'key' => '_skylearn_source',
                    'value' => 'skylearn_billing_pro'
                )
            )
        );
        
        $order = $this->create_wc_order($order_data);
        
        return array(
            'success' => true,
            'order_id' => $order['id'],
            'payment_url' => $order['payment_url'] ?? ''
        );
    }
    
    /**
     * Get or create WooCommerce product
     *
     * @param array $payment_data Payment data
     * @return int Product ID
     */
    private function get_or_create_wc_product($payment_data) {
        // This would get or create a WooCommerce product
        // For framework demonstration, return mock product ID
        return 999; // Mock WooCommerce product ID
    }
    
    /**
     * Get or create WooCommerce customer
     *
     * @param array $payment_data Payment data
     * @return int Customer ID
     */
    private function get_or_create_wc_customer($payment_data) {
        // This would get or create a WooCommerce customer
        // For framework demonstration, return mock customer ID
        return 888; // Mock WooCommerce customer ID
    }
    
    /**
     * Add to cart and get checkout URL
     *
     * @param int $product_id Product ID
     * @param array $payment_data Payment data
     * @return string Checkout URL
     */
    private function add_to_cart_and_get_checkout_url($product_id, $payment_data) {
        // This would add product to WooCommerce cart and return checkout URL
        // For framework demonstration, return mock checkout URL
        return home_url('/checkout/?product=' . $product_id);
    }
    
    /**
     * Create WooCommerce order
     *
     * @param array $order_data Order data
     * @return array Order response
     */
    private function create_wc_order($order_data) {
        // This would create an actual WooCommerce order via API
        // For framework demonstration, return mock order data
        return array(
            'id' => time(), // Mock order ID
            'payment_url' => home_url('/checkout/order-pay/' . time())
        );
    }
    
    /**
     * Test WooCommerce connection
     *
     * @return array Store info
     */
    private function test_woocommerce_connection() {
        // This would test actual WooCommerce API connection
        // For framework demonstration, return mock store data
        return array(
            'name' => get_bloginfo('name'),
            'version' => class_exists('WooCommerce') ? WC()->version : '0.0.0'
        );
    }
    
    /**
     * Handle order completed
     *
     * @param int $order_id Order ID
     * @return array Processing result
     */
    private function handle_order_completed($order_id) {
        // Get order details from WooCommerce
        $order = $this->get_wc_order($order_id);
        
        if (!$order) {
            return array(
                'success' => false,
                'message' => 'Order not found'
            );
        }
        
        $product_id = $order['meta_data']['_skylearn_product_id'] ?? null;
        $customer_email = $order['billing']['email'] ?? null;
        
        if ($product_id && $customer_email) {
            // Enroll user in course, send emails, etc.
            do_action('skylearn_billing_pro_payment_succeeded', array(
                'gateway' => $this->gateway_id,
                'payment_id' => $order_id,
                'amount' => floatval($order['total'] ?? 0),
                'currency' => $order['currency'] ?? 'USD',
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
     * Handle order failed
     *
     * @param int $order_id Order ID
     * @return array Processing result
     */
    private function handle_order_failed($order_id) {
        // Get order details from WooCommerce
        $order = $this->get_wc_order($order_id);
        
        if (!$order) {
            return array(
                'success' => false,
                'message' => 'Order not found'
            );
        }
        
        $product_id = $order['meta_data']['_skylearn_product_id'] ?? null;
        $customer_email = $order['billing']['email'] ?? null;
        
        do_action('skylearn_billing_pro_payment_failed', array(
            'gateway' => $this->gateway_id,
            'payment_id' => $order_id,
            'amount' => floatval($order['total'] ?? 0),
            'currency' => $order['currency'] ?? 'USD',
            'product_id' => $product_id,
            'customer_email' => $customer_email,
            'failure_reason' => 'Order failed or cancelled'
        ));
        
        return array(
            'success' => true,
            'message' => 'Order failure processed'
        );
    }
    
    /**
     * Get WooCommerce order
     *
     * @param int $order_id Order ID
     * @return array|null Order data
     */
    private function get_wc_order($order_id) {
        // This would get actual WooCommerce order data
        // For framework demonstration, return mock order data
        return array(
            'id' => $order_id,
            'total' => '99.99',
            'currency' => 'USD',
            'billing' => array(
                'email' => 'test@example.com'
            ),
            'meta_data' => array(
                '_skylearn_product_id' => '123'
            )
        );
    }
}