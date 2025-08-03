<?php
/**
 * Payment Manager class for handling payment gateway integration
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

// Load gateway registry
if (!class_exists('SkyLearn_Billing_Pro_Gateway_Registry')) {
    require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/gateways/class-gateway-registry.php';
}

/**
 * Payment Manager class
 */
class SkyLearn_Billing_Pro_Payment_Manager {
    
    /**
     * Supported payment gateways
     *
     * @var array
     */
    private $supported_gateways = array(
        'stripe' => array(
            'name' => 'Stripe',
            'description' => 'Accept payments worldwide with Stripe',
            'supports_inline' => true,
            'supports_overlay' => true,
            'supports_hosted' => true,
            'requires_hosted_only' => false,
            'connector_class' => 'SkyLearn_Billing_Pro_Stripe_Connector',
            'tier_requirements' => array('free', 'pro', 'pro_plus'),
            'credentials' => array(
                'publishable_key' => 'Publishable Key',
                'secret_key' => 'Secret Key',
                'webhook_secret' => 'Webhook Secret'
            )
        ),
        'paddle' => array(
            'name' => 'Paddle',
            'description' => 'Simplified payment processing with Paddle',
            'supports_inline' => true,
            'supports_overlay' => true,
            'supports_hosted' => true,
            'requires_hosted_only' => false,
            'connector_class' => 'SkyLearn_Billing_Pro_Paddle_Connector',
            'tier_requirements' => array('pro', 'pro_plus'),
            'credentials' => array(
                'vendor_id' => 'Vendor ID',
                'vendor_auth_code' => 'Vendor Auth Code',
                'public_key' => 'Public Key'
            )
        ),
        'lemonsqueezy' => array(
            'name' => 'Lemon Squeezy',
            'description' => 'Simple checkout for digital products',
            'supports_inline' => false,
            'supports_overlay' => false,
            'supports_hosted' => true,
            'requires_hosted_only' => true,
            'connector_class' => 'SkyLearn_Billing_Pro_LemonSqueezy_Connector',
            'tier_requirements' => array('pro', 'pro_plus'),
            'credentials' => array(
                'api_key' => 'API Key',
                'store_id' => 'Store ID',
                'webhook_secret' => 'Webhook Secret'
            )
        ),
        'woocommerce' => array(
            'name' => 'WooCommerce',
            'description' => 'Integrate with WooCommerce checkout',
            'supports_inline' => true,
            'supports_overlay' => false,
            'supports_hosted' => true,
            'requires_hosted_only' => false,
            'connector_class' => 'SkyLearn_Billing_Pro_WooCommerce_Connector',
            'tier_requirements' => array('free', 'pro', 'pro_plus'),
            'credentials' => array(
                'consumer_key' => 'Consumer Key',
                'consumer_secret' => 'Consumer Secret'
            )
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        
        // Initialize gateway registry
        SkyLearn_Billing_Pro_Gateway_Registry::init();
    }
    
    /**
     * Initialize payment manager
     */
    public function init() {
        // Load gateway connectors (legacy support)
        $this->load_connectors();
    }
    
    /**
     * Get all supported gateways
     *
     * @return array
     */
    public function get_supported_gateways() {
        // Use new gateway registry to get gateway configurations
        $registry_configs = SkyLearn_Billing_Pro_Gateway_Registry::get_admin_configs();
        
        if (!empty($registry_configs)) {
            // Convert registry format to legacy format for backward compatibility
            $legacy_format = array();
            foreach ($registry_configs as $gateway_id => $config) {
                $legacy_format[$gateway_id] = array(
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'supports_inline' => $config['supports_inline'],
                    'supports_overlay' => $config['supports_overlay'],
                    'supports_hosted' => $config['supports_hosted'],
                    'requires_hosted_only' => $config['requires_hosted_only'],
                    'connector_class' => 'SkyLearn_Billing_Pro_' . ucfirst($gateway_id) . '_Gateway',
                    'tier_requirements' => $config['tier_requirements'],
                    'credentials' => $config['credentials']
                );
            }
            return apply_filters('skylearn_billing_pro_supported_gateways', $legacy_format);
        }
        
        // Fallback to legacy hardcoded list if registry is empty
        return apply_filters('skylearn_billing_pro_supported_gateways', $this->supported_gateways);
    }
    
    /**
     * Get available gateways for current tier
     *
     * @return array
     */
    public function get_available_gateways() {
        // Use new gateway registry
        $available_gateways = SkyLearn_Billing_Pro_Gateway_Registry::get_available_gateways();
        
        if (!empty($available_gateways)) {
            // Convert to legacy format
            $legacy_format = array();
            foreach ($available_gateways as $gateway_id => $gateway) {
                $config = $gateway->get_admin_config();
                $legacy_format[$gateway_id] = array(
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'supports_inline' => $config['supports_inline'],
                    'supports_overlay' => $config['supports_overlay'],
                    'supports_hosted' => $config['supports_hosted'],
                    'requires_hosted_only' => $config['requires_hosted_only'],
                    'connector_class' => 'SkyLearn_Billing_Pro_' . ucfirst($gateway_id) . '_Gateway',
                    'tier_requirements' => $config['tier_requirements'],
                    'credentials' => $config['credentials']
                );
            }
            return apply_filters('skylearn_billing_pro_available_gateways', $legacy_format);
        }
        
        // Fallback to legacy implementation
        $licensing_manager = skylearn_billing_pro_licensing();
        $current_tier = $licensing_manager->get_license_tier();
        
        $available = array();
        foreach ($this->supported_gateways as $gateway_id => $gateway_data) {
            if (in_array($current_tier, $gateway_data['tier_requirements'])) {
                $available[$gateway_id] = $gateway_data;
            }
        }
        
        return apply_filters('skylearn_billing_pro_available_gateways', $available);
    }
    
    /**
     * Get enabled gateways
     *
     * @return array
     */
    public function get_enabled_gateways() {
        // Try to use new gateway registry first
        $enabled_gateways = SkyLearn_Billing_Pro_Gateway_Registry::get_enabled_gateways();
        
        if (!empty($enabled_gateways)) {
            // Convert to legacy format
            $legacy_format = array();
            foreach ($enabled_gateways as $gateway_id => $gateway) {
                $config = $gateway->get_admin_config();
                $legacy_format[$gateway_id] = array(
                    'name' => $config['name'],
                    'description' => $config['description'],
                    'supports_inline' => $config['supports_inline'],
                    'supports_overlay' => $config['supports_overlay'],
                    'supports_hosted' => $config['supports_hosted'],
                    'requires_hosted_only' => $config['requires_hosted_only'],
                    'connector_class' => 'SkyLearn_Billing_Pro_' . ucfirst($gateway_id) . '_Gateway',
                    'tier_requirements' => $config['tier_requirements'],
                    'credentials' => $config['credentials']
                );
            }
            return $legacy_format;
        }
        
        // Fallback to legacy implementation
        $options = get_option('skylearn_billing_pro_options', array());
        $enabled = isset($options['payment_settings']['enabled_gateways']) ? $options['payment_settings']['enabled_gateways'] : array();
        
        $available = $this->get_available_gateways();
        $enabled_gateways = array();
        
        foreach ($enabled as $gateway_id) {
            if (isset($available[$gateway_id])) {
                $enabled_gateways[$gateway_id] = $available[$gateway_id];
            }
        }
        
        return $enabled_gateways;
    }
    
    /**
     * Check if gateway is enabled
     *
     * @param string $gateway_id Gateway ID
     * @return bool
     */
    public function is_gateway_enabled($gateway_id) {
        // Try new gateway registry first
        $gateway = SkyLearn_Billing_Pro_Gateway_Registry::get_gateway($gateway_id);
        if ($gateway) {
            return $gateway->is_enabled();
        }
        
        // Fallback to legacy implementation
        $enabled_gateways = $this->get_enabled_gateways();
        return isset($enabled_gateways[$gateway_id]);
    }
    
    /**
     * Get gateway credentials
     *
     * @param string $gateway_id Gateway ID
     * @return array
     */
    public function get_gateway_credentials($gateway_id) {
        $options = get_option('skylearn_billing_pro_options', array());
        $credentials = isset($options['payment_settings']['gateways'][$gateway_id]) ? $options['payment_settings']['gateways'][$gateway_id] : array();
        
        return $credentials;
    }
    
    /**
     * Check if gateway has required credentials configured
     *
     * @param string $gateway_id Gateway ID
     * @return bool
     */
    public function has_gateway_credentials($gateway_id) {
        // Try new gateway registry first
        $gateway = SkyLearn_Billing_Pro_Gateway_Registry::get_gateway($gateway_id);
        if ($gateway) {
            return $gateway->has_credentials();
        }
        
        // Fallback to legacy implementation
        if (!isset($this->supported_gateways[$gateway_id])) {
            return false;
        }
        
        $gateway_data = $this->supported_gateways[$gateway_id];
        $credentials = $this->get_gateway_credentials($gateway_id);
        
        foreach ($gateway_data['credentials'] as $field => $label) {
            if (empty($credentials[$field])) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get gateway warnings and notices
     *
     * @param string $gateway_id Gateway ID
     * @return array
     */
    public function get_gateway_notices($gateway_id) {
        // Try new gateway registry first
        $gateway = SkyLearn_Billing_Pro_Gateway_Registry::get_gateway($gateway_id);
        if ($gateway) {
            return $gateway->get_notices();
        }
        
        // Fallback to legacy implementation
        $notices = array();
        
        if (!isset($this->supported_gateways[$gateway_id])) {
            return $notices;
        }
        
        $gateway_data = $this->supported_gateways[$gateway_id];
        
        // Check for hosted checkout only warning
        if ($gateway_data['requires_hosted_only']) {
            $notices[] = array(
                'type' => 'warning',
                'message' => sprintf(
                    __('%s only supports hosted checkout. Customers will be redirected to %s to complete their payment.', 'skylearn-billing-pro'),
                    $gateway_data['name'],
                    $gateway_data['name']
                )
            );
        }
        
        // Check tier restrictions
        $licensing_manager = skylearn_billing_pro_licensing();
        $current_tier = $licensing_manager->get_license_tier();
        
        if (!in_array($current_tier, $gateway_data['tier_requirements'])) {
            $notices[] = array(
                'type' => 'error',
                'message' => sprintf(
                    __('%s requires %s license tier or higher.', 'skylearn-billing-pro'),
                    $gateway_data['name'],
                    ucfirst($gateway_data['tier_requirements'][1] ?? 'Pro')
                )
            );
        }
        
        // Check credentials
        if (!$this->has_gateway_credentials($gateway_id)) {
            $notices[] = array(
                'type' => 'error',
                'message' => sprintf(
                    __('%s credentials are not configured.', 'skylearn-billing-pro'),
                    $gateway_data['name']
                )
            );
        }
        
        return apply_filters('skylearn_billing_pro_gateway_notices', $notices, $gateway_id);
    }
    
    /**
     * Load gateway connectors
     */
    private function load_connectors() {
        $enabled_gateways = $this->get_enabled_gateways();
        
        foreach ($enabled_gateways as $gateway_id => $gateway_data) {
            $connector_file = SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/payment/class-' . $gateway_id . '.php';
            if (file_exists($connector_file)) {
                require_once $connector_file;
                
                if (class_exists($gateway_data['connector_class'])) {
                    new $gateway_data['connector_class']();
                }
            }
        }
    }
    
    /**
     * Get gateway connector instance
     *
     * @param string $gateway_id Gateway ID
     * @return object|false Gateway connector instance or false
     */
    public function get_gateway_connector($gateway_id) {
        // Try new gateway registry first
        $gateway = SkyLearn_Billing_Pro_Gateway_Registry::get_gateway($gateway_id);
        if ($gateway) {
            return $gateway;
        }
        
        // Fallback to legacy implementation
        if (!$this->is_gateway_enabled($gateway_id)) {
            return false;
        }
        
        $gateway_data = $this->supported_gateways[$gateway_id];
        
        if (class_exists($gateway_data['connector_class'])) {
            return new $gateway_data['connector_class']();
        }
        
        return false;
    }
    
    /**
     * Process payment webhook
     *
     * @param string $gateway_id Gateway ID
     * @param array $payload Webhook payload
     * @return array
     */
    public function process_payment_webhook($gateway_id, $payload) {
        // Try new gateway registry first
        return SkyLearn_Billing_Pro_Gateway_Registry::process_webhook($gateway_id, $payload);
    }
}

/**
 * Get global payment manager instance
 *
 * @return SkyLearn_Billing_Pro_Payment_Manager
 */
function skylearn_billing_pro_payment_manager() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Payment_Manager();
    }
    
    return $instance;
}