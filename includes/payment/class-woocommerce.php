<?php
/**
 * WooCommerce payment gateway connector
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
 * WooCommerce connector class
 */
class SkyLearn_Billing_Pro_WooCommerce_Connector {
    
    /**
     * Gateway ID
     *
     * @var string
     */
    private $gateway_id = 'woocommerce';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('woocommerce_payment_complete', array($this, 'handle_payment_complete'));
        add_action('woocommerce_order_status_completed', array($this, 'handle_order_completed'));
    }
    
    /**
     * Initialize connector
     */
    public function init() {
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            return;
        }
        
        // Add WooCommerce-specific initialization
    }
    
    /**
     * Check if WooCommerce is active
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return function_exists('WC') && class_exists('WooCommerce');
    }
    
    /**
     * Get WooCommerce credentials
     *
     * @return array
     */
    private function get_credentials() {
        $payment_manager = skylearn_billing_pro_payment_manager();
        return $payment_manager->get_gateway_credentials($this->gateway_id);
    }
    
    /**
     * Create product in WooCommerce
     *
     * @param array $args Product arguments
     * @return array
     */
    public function create_product($args) {
        if (!$this->is_woocommerce_active()) {
            return array(
                'success' => false,
                'message' => 'WooCommerce is not active'
            );
        }
        
        try {
            $product = new WC_Product_Simple();
            $product->set_name($args['name'] ?? 'Course Access');
            $product->set_description($args['description'] ?? '');
            $product->set_short_description($args['short_description'] ?? '');
            $product->set_regular_price($args['price'] ?? 0);
            $product->set_virtual(true);
            $product->set_downloadable(false);
            $product->set_status('publish');
            
            // Add custom meta for course mapping
            $product->update_meta_data('_skylearn_product_id', $args['product_id'] ?? '');
            $product->update_meta_data('_skylearn_course_id', $args['course_id'] ?? '');
            
            $product_id = $product->save();
            
            return array(
                'success' => true,
                'product_id' => $product_id,
                'product_url' => get_permalink($product_id)
            );
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    /**
     * Handle payment completion
     *
     * @param int $order_id Order ID
     */
    public function handle_payment_complete($order_id) {
        $this->process_order_enrollment($order_id);
    }
    
    /**
     * Handle order completion
     *
     * @param int $order_id Order ID
     */
    public function handle_order_completed($order_id) {
        $this->process_order_enrollment($order_id);
    }
    
    /**
     * Process order enrollment
     *
     * @param int $order_id Order ID
     * @return array
     */
    private function process_order_enrollment($order_id) {
        if (!$this->is_woocommerce_active()) {
            return array(
                'success' => false,
                'message' => 'WooCommerce is not active'
            );
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'success' => false,
                'message' => 'Order not found'
            );
        }
        
        // Check if already processed
        if ($order->get_meta('_skylearn_processed')) {
            return array(
                'success' => true,
                'message' => 'Order already processed'
            );
        }
        
        $customer_email = $order->get_billing_email();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        
        $enrollment_results = array();
        
        // Process each order item
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if (!$product) {
                continue;
            }
            
            $product_id = $product->get_meta('_skylearn_product_id');
            
            if (empty($product_id)) {
                // Use WooCommerce product ID as fallback
                $product_id = 'wc_' . $product->get_id();
            }
            
            // Process enrollment via existing webhook handler
            $webhook_handler = skylearn_billing_pro_webhook_handler();
            
            $result = $webhook_handler->process_enrollment(array(
                'email' => $customer_email,
                'name' => trim($customer_name),
                'product_id' => $product_id,
                'payment_amount' => $order->get_total(),
                'payment_currency' => $order->get_currency(),
                'payment_gateway' => 'woocommerce',
                'payment_id' => $order_id
            ));
            
            $enrollment_results[] = $result;
        }
        
        // Mark order as processed
        $order->update_meta_data('_skylearn_processed', true);
        $order->add_order_note(__('Course enrollment processed by Skylearn Billing Pro', 'skylearn-billing-pro'));
        $order->save();
        
        return array(
            'success' => true,
            'message' => 'Order enrollment processed',
            'results' => $enrollment_results
        );
    }
    
    /**
     * Process webhook (for external WooCommerce webhooks)
     *
     * @param array $payload Webhook payload
     * @return array
     */
    public function process_webhook($payload) {
        $data = json_decode($payload, true);
        
        if (!isset($data['id'])) {
            return array(
                'success' => false,
                'message' => 'Invalid webhook payload'
            );
        }
        
        return $this->process_order_enrollment($data['id']);
    }
    
    /**
     * Get supported checkout types
     *
     * @return array
     */
    public function get_supported_checkout_types() {
        return array('inline', 'hosted');
    }
    
    /**
     * Render inline checkout (WooCommerce cart/checkout)
     *
     * @param array $args Checkout arguments
     * @return string
     */
    public function render_inline_checkout($args) {
        if (!$this->is_woocommerce_active()) {
            return '<p>' . __('WooCommerce is not active.', 'skylearn-billing-pro') . '</p>';
        }
        
        ob_start();
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/checkout/woocommerce-inline.php';
        return ob_get_clean();
    }
    
    /**
     * Get WooCommerce integration status
     *
     * @return array
     */
    public function get_integration_status() {
        return array(
            'woocommerce_active' => $this->is_woocommerce_active(),
            'api_configured' => !empty($this->get_credentials()['consumer_key']),
            'products_count' => $this->get_skylearn_products_count()
        );
    }
    
    /**
     * Get count of Skylearn-enabled products
     *
     * @return int
     */
    private function get_skylearn_products_count() {
        if (!$this->is_woocommerce_active()) {
            return 0;
        }
        
        $args = array(
            'post_type' => 'product',
            'meta_query' => array(
                array(
                    'key' => '_skylearn_product_id',
                    'compare' => 'EXISTS'
                )
            ),
            'posts_per_page' => -1,
            'fields' => 'ids'
        );
        
        $products = get_posts($args);
        return count($products);
    }
}