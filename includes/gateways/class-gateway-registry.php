<?php
/**
 * Gateway Registry class for managing payment gateways
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
 * Gateway Registry class
 */
class SkyLearn_Billing_Pro_Gateway_Registry {
    
    /**
     * Registered gateways
     *
     * @var array
     */
    private static $gateways = array();
    
    /**
     * Gateway instances
     *
     * @var array
     */
    private static $instances = array();
    
    /**
     * Initialize the registry
     */
    public static function init() {
        add_action('init', array(__CLASS__, 'load_gateways'), 5);
        add_action('init', array(__CLASS__, 'register_core_gateways'), 10);
    }
    
    /**
     * Register a gateway
     *
     * @param string $gateway_id Gateway ID
     * @param string $gateway_class Gateway class name
     * @param string $gateway_file Path to gateway file
     */
    public static function register_gateway($gateway_id, $gateway_class, $gateway_file = '') {
        self::$gateways[$gateway_id] = array(
            'class' => $gateway_class,
            'file' => $gateway_file,
            'registered' => true
        );
        
        // Auto-load if file is provided
        if (!empty($gateway_file) && file_exists($gateway_file)) {
            require_once $gateway_file;
        }
    }
    
    /**
     * Unregister a gateway
     *
     * @param string $gateway_id Gateway ID
     */
    public static function unregister_gateway($gateway_id) {
        unset(self::$gateways[$gateway_id]);
        unset(self::$instances[$gateway_id]);
    }
    
    /**
     * Get all registered gateways
     *
     * @return array
     */
    public static function get_registered_gateways() {
        return self::$gateways;
    }
    
    /**
     * Check if gateway is registered
     *
     * @param string $gateway_id Gateway ID
     * @return bool
     */
    public static function is_registered($gateway_id) {
        return isset(self::$gateways[$gateway_id]);
    }
    
    /**
     * Get gateway instance
     *
     * @param string $gateway_id Gateway ID
     * @return SkyLearn_Billing_Pro_Gateway_Base|false
     */
    public static function get_gateway($gateway_id) {
        if (!self::is_registered($gateway_id)) {
            return false;
        }
        
        // Return cached instance if available
        if (isset(self::$instances[$gateway_id])) {
            return self::$instances[$gateway_id];
        }
        
        $gateway_data = self::$gateways[$gateway_id];
        
        // Load gateway file if needed
        if (!empty($gateway_data['file']) && file_exists($gateway_data['file'])) {
            require_once $gateway_data['file'];
        }
        
        // Create instance if class exists
        if (class_exists($gateway_data['class'])) {
            self::$instances[$gateway_id] = new $gateway_data['class']();
            return self::$instances[$gateway_id];
        }
        
        return false;
    }
    
    /**
     * Get all gateway instances
     *
     * @return array
     */
    public static function get_all_gateways() {
        $gateways = array();
        
        foreach (self::$gateways as $gateway_id => $gateway_data) {
            $gateway = self::get_gateway($gateway_id);
            if ($gateway) {
                $gateways[$gateway_id] = $gateway;
            }
        }
        
        return $gateways;
    }
    
    /**
     * Get available gateways (filtered by license tier)
     *
     * @return array
     */
    public static function get_available_gateways() {
        $gateways = array();
        
        foreach (self::get_all_gateways() as $gateway_id => $gateway) {
            if ($gateway->is_available()) {
                $gateways[$gateway_id] = $gateway;
            }
        }
        
        return $gateways;
    }
    
    /**
     * Get enabled gateways
     *
     * @return array
     */
    public static function get_enabled_gateways() {
        $gateways = array();
        
        foreach (self::get_available_gateways() as $gateway_id => $gateway) {
            if ($gateway->is_enabled()) {
                $gateways[$gateway_id] = $gateway;
            }
        }
        
        return $gateways;
    }
    
    /**
     * Get gateways by checkout mode
     *
     * @param string $mode Checkout mode (inline, overlay, hosted)
     * @return array
     */
    public static function get_gateways_by_mode($mode) {
        $gateways = array();
        
        foreach (self::get_enabled_gateways() as $gateway_id => $gateway) {
            $supported_modes = $gateway->get_supported_modes();
            if (in_array($mode, $supported_modes)) {
                $gateways[$gateway_id] = $gateway;
            }
        }
        
        return $gateways;
    }
    
    /**
     * Get gateway configurations for admin display
     *
     * @return array
     */
    public static function get_admin_configs() {
        $configs = array();
        
        foreach (self::get_all_gateways() as $gateway_id => $gateway) {
            $configs[$gateway_id] = $gateway->get_admin_config();
        }
        
        return $configs;
    }
    
    /**
     * Validate gateway settings
     *
     * @param string $gateway_id Gateway ID
     * @return array Validation result
     */
    public static function validate_gateway($gateway_id) {
        $gateway = self::get_gateway($gateway_id);
        
        if (!$gateway) {
            return array(
                'valid' => false,
                'message' => __('Gateway not found.', 'skylearn-billing-pro')
            );
        }
        
        return $gateway->validate_credentials();
    }
    
    /**
     * Load gateway files from directories
     */
    public static function load_gateways() {
        $gateway_dirs = array(
            SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/gateways/',
            SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/payment/' // Legacy support
        );
        
        foreach ($gateway_dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            
            $files = glob($dir . 'class-*.php');
            foreach ($files as $file) {
                // Skip the base class and registry
                $filename = basename($file);
                if (in_array($filename, array('class-gateway-base.php', 'class-gateway-registry.php'))) {
                    continue;
                }
                
                require_once $file;
            }
        }
    }
    
    /**
     * Register core gateways
     */
    public static function register_core_gateways() {
        // Register Stripe gateway
        if (class_exists('SkyLearn_Billing_Pro_Stripe_Gateway')) {
            self::register_gateway('stripe', 'SkyLearn_Billing_Pro_Stripe_Gateway');
        }
        
        // Register Paddle gateway
        if (class_exists('SkyLearn_Billing_Pro_Paddle_Gateway')) {
            self::register_gateway('paddle', 'SkyLearn_Billing_Pro_Paddle_Gateway');
        }
        
        // Register Lemon Squeezy gateway
        if (class_exists('SkyLearn_Billing_Pro_LemonSqueezy_Gateway')) {
            self::register_gateway('lemonsqueezy', 'SkyLearn_Billing_Pro_LemonSqueezy_Gateway');
        }
        
        // Register WooCommerce gateway
        if (class_exists('SkyLearn_Billing_Pro_WooCommerce_Gateway')) {
            self::register_gateway('woocommerce', 'SkyLearn_Billing_Pro_WooCommerce_Gateway');
        }
        
        // Allow plugins to register additional gateways
        do_action('skylearn_billing_pro_register_gateways');
    }
    
    /**
     * Process webhook for a specific gateway
     *
     * @param string $gateway_id Gateway ID
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    public static function process_webhook($gateway_id, $payload) {
        $gateway = self::get_gateway($gateway_id);
        
        if (!$gateway) {
            return array(
                'success' => false,
                'message' => 'Gateway not found'
            );
        }
        
        if (!$gateway->is_enabled()) {
            return array(
                'success' => false,
                'message' => 'Gateway not enabled'
            );
        }
        
        return $gateway->process_webhook($payload);
    }
    
    /**
     * Create payment with specific gateway
     *
     * @param string $gateway_id Gateway ID
     * @param array $payment_data Payment data
     * @return array Payment result
     */
    public static function create_payment($gateway_id, $payment_data) {
        $gateway = self::get_gateway($gateway_id);
        
        if (!$gateway) {
            return array(
                'success' => false,
                'message' => 'Gateway not found'
            );
        }
        
        if (!$gateway->is_enabled()) {
            return array(
                'success' => false,
                'message' => 'Gateway not enabled'
            );
        }
        
        if (!$gateway->has_credentials()) {
            return array(
                'success' => false,
                'message' => 'Gateway credentials not configured'
            );
        }
        
        return $gateway->create_payment($payment_data);
    }
    
    /**
     * Test gateway connection
     *
     * @param string $gateway_id Gateway ID
     * @return array Test result
     */
    public static function test_gateway_connection($gateway_id) {
        $gateway = self::get_gateway($gateway_id);
        
        if (!$gateway) {
            return array(
                'success' => false,
                'message' => 'Gateway not found'
            );
        }
        
        // Check if gateway has test method
        if (method_exists($gateway, 'test_connection')) {
            return $gateway->test_connection();
        }
        
        // Fallback to credential validation
        return $gateway->validate_credentials();
    }
}