<?php
/**
 * Abstract Base Gateway class for payment processors
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
 * Abstract Gateway Base class
 */
abstract class SkyLearn_Billing_Pro_Gateway_Base {
    
    /**
     * Gateway ID
     *
     * @var string
     */
    protected $gateway_id;
    
    /**
     * Gateway name
     *
     * @var string
     */
    protected $gateway_name;
    
    /**
     * Gateway description
     *
     * @var string
     */
    protected $gateway_description;
    
    /**
     * Supported checkout modes
     *
     * @var array
     */
    protected $supported_modes = array();
    
    /**
     * Required credentials fields
     *
     * @var array
     */
    protected $credential_fields = array();
    
    /**
     * License tier requirements
     *
     * @var array
     */
    protected $tier_requirements = array('free');
    
    /**
     * Test mode
     *
     * @var bool
     */
    protected $test_mode = false;
    
    /**
     * Gateway settings
     *
     * @var array
     */
    protected $settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init();
        $this->load_settings();
        
        // Hook into WordPress if gateway is enabled
        if ($this->is_enabled()) {
            $this->init_hooks();
        }
    }
    
    /**
     * Initialize gateway (to be implemented by subclasses)
     */
    abstract protected function init();
    
    /**
     * Initialize WordPress hooks
     */
    protected function init_hooks() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_skylearn_' . $this->gateway_id . '_create_payment', array($this, 'ajax_create_payment'));
        add_action('wp_ajax_nopriv_skylearn_' . $this->gateway_id . '_create_payment', array($this, 'ajax_create_payment'));
    }
    
    /**
     * Get gateway ID
     *
     * @return string
     */
    public function get_id() {
        return $this->gateway_id;
    }
    
    /**
     * Get gateway name
     *
     * @return string
     */
    public function get_name() {
        return $this->gateway_name;
    }
    
    /**
     * Get gateway description
     *
     * @return string
     */
    public function get_description() {
        return $this->gateway_description;
    }
    
    /**
     * Get supported checkout modes
     *
     * @return array
     */
    public function get_supported_modes() {
        return $this->supported_modes;
    }
    
    /**
     * Get credential fields
     *
     * @return array
     */
    public function get_credential_fields() {
        return $this->credential_fields;
    }
    
    /**
     * Get tier requirements
     *
     * @return array
     */
    public function get_tier_requirements() {
        return $this->tier_requirements;
    }
    
    /**
     * Check if gateway supports inline checkout
     *
     * @return bool
     */
    public function supports_inline() {
        return in_array('inline', $this->supported_modes);
    }
    
    /**
     * Check if gateway supports overlay checkout
     *
     * @return bool
     */
    public function supports_overlay() {
        return in_array('overlay', $this->supported_modes);
    }
    
    /**
     * Check if gateway supports hosted checkout
     *
     * @return bool
     */
    public function supports_hosted() {
        return in_array('hosted', $this->supported_modes);
    }
    
    /**
     * Check if gateway requires hosted checkout only
     *
     * @return bool
     */
    public function requires_hosted_only() {
        return $this->supported_modes === array('hosted');
    }
    
    /**
     * Check if gateway is enabled
     *
     * @return bool
     */
    public function is_enabled() {
        $options = get_option('skylearn_billing_pro_options', array());
        $enabled_gateways = isset($options['payment_settings']['enabled_gateways']) ? $options['payment_settings']['enabled_gateways'] : array();
        
        return in_array($this->gateway_id, $enabled_gateways) && $this->is_available();
    }
    
    /**
     * Check if gateway is available for current license tier
     *
     * @return bool
     */
    public function is_available() {
        if (!function_exists('skylearn_billing_pro_licensing')) {
            return true; // Fallback if licensing not available
        }
        
        $licensing_manager = skylearn_billing_pro_licensing();
        $current_tier = $licensing_manager->get_license_tier();
        
        return in_array($current_tier, $this->tier_requirements);
    }
    
    /**
     * Check if test mode is enabled
     *
     * @return bool
     */
    public function is_test_mode() {
        return $this->test_mode;
    }
    
    /**
     * Load gateway settings
     */
    protected function load_settings() {
        $options = get_option('skylearn_billing_pro_options', array());
        $this->settings = isset($options['payment_settings']['gateways'][$this->gateway_id]) 
            ? $options['payment_settings']['gateways'][$this->gateway_id] 
            : array();
        
        // Set test mode from global setting or gateway-specific setting
        $global_test_mode = isset($options['payment_settings']['test_mode']) ? $options['payment_settings']['test_mode'] : false;
        $gateway_test_mode = isset($this->settings['test_mode']) ? $this->settings['test_mode'] : false;
        $this->test_mode = $global_test_mode || $gateway_test_mode;
    }
    
    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed
     */
    public function get_setting($key, $default = '') {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    /**
     * Check if gateway has required credentials configured
     *
     * @return bool
     */
    public function has_credentials() {
        foreach ($this->credential_fields as $field => $label) {
            if (empty($this->get_setting($field))) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validate gateway credentials
     *
     * @return array Array with 'valid' boolean and 'message' string
     */
    public function validate_credentials() {
        if (!$this->has_credentials()) {
            return array(
                'valid' => false,
                'message' => sprintf(__('%s credentials are not configured.', 'skylearn-billing-pro'), $this->gateway_name)
            );
        }
        
        // Subclasses can override this method to perform actual validation
        return array(
            'valid' => true,
            'message' => __('Credentials configured.', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Get gateway notices
     *
     * @return array
     */
    public function get_notices() {
        $notices = array();
        
        // Check availability
        if (!$this->is_available()) {
            $notices[] = array(
                'type' => 'error',
                'message' => sprintf(
                    __('%s requires %s license tier or higher.', 'skylearn-billing-pro'),
                    $this->gateway_name,
                    ucfirst($this->tier_requirements[1] ?? 'Pro')
                )
            );
        }
        
        // Check credentials
        if (!$this->has_credentials()) {
            $notices[] = array(
                'type' => 'error',
                'message' => sprintf(
                    __('%s credentials are not configured.', 'skylearn-billing-pro'),
                    $this->gateway_name
                )
            );
        }
        
        // Check hosted-only warning
        if ($this->requires_hosted_only()) {
            $notices[] = array(
                'type' => 'warning',
                'message' => sprintf(
                    __('%s only supports hosted checkout. Customers will be redirected to %s to complete their payment.', 'skylearn-billing-pro'),
                    $this->gateway_name,
                    $this->gateway_name
                )
            );
        }
        
        return apply_filters('skylearn_billing_pro_gateway_notices', $notices, $this->gateway_id);
    }
    
    /**
     * Enqueue gateway-specific scripts (to be implemented by subclasses)
     */
    public function enqueue_scripts() {
        // Subclasses should implement this method
    }
    
    /**
     * Create payment (to be implemented by subclasses)
     *
     * @param array $payment_data Payment data
     * @return array Payment result
     */
    abstract public function create_payment($payment_data);
    
    /**
     * Process payment webhook (to be implemented by subclasses)
     *
     * @param array $payload Webhook payload
     * @return array Processing result
     */
    abstract public function process_webhook($payload);
    
    /**
     * AJAX handler for creating payment
     */
    public function ajax_create_payment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_create_payment')) {
            wp_die(__('Security check failed.', 'skylearn-billing-pro'));
        }
        
        // Get payment data
        $payment_data = array(
            'amount' => floatval($_POST['amount']),
            'currency' => sanitize_text_field($_POST['currency']),
            'product_id' => intval($_POST['product_id']),
            'customer_email' => sanitize_email($_POST['customer_email']),
            'customer_name' => sanitize_text_field($_POST['customer_name']),
            'return_url' => esc_url_raw($_POST['return_url']),
            'cancel_url' => esc_url_raw($_POST['cancel_url'])
        );
        
        // Create payment
        $result = $this->create_payment($payment_data);
        
        wp_send_json($result);
    }
    
    /**
     * Get gateway configuration for admin display
     *
     * @return array
     */
    public function get_admin_config() {
        return array(
            'id' => $this->gateway_id,
            'name' => $this->gateway_name,
            'description' => $this->gateway_description,
            'supports_inline' => $this->supports_inline(),
            'supports_overlay' => $this->supports_overlay(),
            'supports_hosted' => $this->supports_hosted(),
            'requires_hosted_only' => $this->requires_hosted_only(),
            'tier_requirements' => $this->tier_requirements,
            'credentials' => $this->credential_fields,
            'is_available' => $this->is_available(),
            'is_enabled' => $this->is_enabled(),
            'has_credentials' => $this->has_credentials(),
            'notices' => $this->get_notices()
        );
    }
}