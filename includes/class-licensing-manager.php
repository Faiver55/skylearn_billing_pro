<?php
/**
 * Licensing Manager for Skylearn Billing Pro
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
 * Licensing Manager Class
 * Handles license validation, tier management, and feature access control
 */
class SkyLearn_Billing_Pro_Licensing_Manager {
    
    /**
     * License tiers
     */
    const TIER_FREE = 'free';
    const TIER_PRO = 'pro';
    const TIER_PRO_PLUS = 'pro_plus';
    
    /**
     * Option name for storing license data
     */
    const LICENSE_OPTION_NAME = 'skylearn_billing_pro_license';
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Licensing_Manager
     */
    private static $_instance = null;
    
    /**
     * Current license data
     *
     * @var array
     */
    private $license_data = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Licensing_Manager
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_license_data();
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        add_action('wp_ajax_skylearn_validate_license', array($this, 'ajax_validate_license'));
        add_action('wp_ajax_skylearn_deactivate_license', array($this, 'ajax_deactivate_license'));
    }
    
    /**
     * Load license data from database
     */
    private function load_license_data() {
        $this->license_data = get_option(self::LICENSE_OPTION_NAME, array(
            'license_key' => '',
            'tier' => self::TIER_FREE,
            'status' => 'inactive',
            'expires' => '',
            'activated_at' => '',
            'site_url' => home_url(),
            'last_check' => ''
        ));
    }
    
    /**
     * Get current tier
     *
     * @return string
     */
    public function get_current_tier() {
        return $this->license_data['tier'];
    }
    
    /**
     * Get license status
     *
     * @return string
     */
    public function get_license_status() {
        return $this->license_data['status'];
    }
    
    /**
     * Get license key
     *
     * @return string
     */
    public function get_license_key() {
        return $this->license_data['license_key'];
    }
    
    /**
     * Check if license is active
     *
     * @return bool
     */
    public function is_license_active() {
        return $this->license_data['status'] === 'active';
    }
    
    /**
     * Check if license is expired
     *
     * @return bool
     */
    public function is_license_expired() {
        if (empty($this->license_data['expires'])) {
            return false;
        }
        
        return time() > strtotime($this->license_data['expires']);
    }
    
    /**
     * Get tier display name
     *
     * @param string $tier
     * @return string
     */
    public function get_tier_display_name($tier = null) {
        if ($tier === null) {
            $tier = $this->get_current_tier();
        }
        
        switch ($tier) {
            case self::TIER_PRO:
                return __('Pro', 'skylearn-billing-pro');
            case self::TIER_PRO_PLUS:
                return __('Pro Plus', 'skylearn-billing-pro');
            default:
                return __('Free', 'skylearn-billing-pro');
        }
    }
    
    /**
     * Get tier color
     *
     * @param string $tier
     * @return string
     */
    public function get_tier_color($tier = null) {
        if ($tier === null) {
            $tier = $this->get_current_tier();
        }
        
        switch ($tier) {
            case self::TIER_PRO:
                return '#FF3B00'; // Accent color
            case self::TIER_PRO_PLUS:
                return '#28a745'; // Success color
            default:
                return '#666666'; // Medium gray
        }
    }
    
    /**
     * Validate license key
     *
     * @param string $license_key
     * @return array
     */
    public function validate_license($license_key) {
        // Sanitize the license key
        $license_key = sanitize_text_field($license_key);
        
        if (empty($license_key)) {
            return array(
                'success' => false,
                'message' => __('Please enter a license key.', 'skylearn-billing-pro')
            );
        }
        
        // For demo purposes, we'll simulate license validation
        // In a real implementation, this would make an API call to the licensing server
        $mock_response = $this->mock_license_validation($license_key);
        
        if ($mock_response['success']) {
            // Update license data
            $this->license_data = array_merge($this->license_data, array(
                'license_key' => $license_key,
                'tier' => $mock_response['tier'],
                'status' => 'active',
                'expires' => $mock_response['expires'],
                'activated_at' => current_time('mysql'),
                'last_check' => current_time('mysql')
            ));
            
            update_option(self::LICENSE_OPTION_NAME, $this->license_data);
            
            return array(
                'success' => true,
                'message' => sprintf(__('License activated successfully! You now have access to %s features.', 'skylearn-billing-pro'), $this->get_tier_display_name($mock_response['tier'])),
                'tier' => $mock_response['tier']
            );
        }
        
        return $mock_response;
    }
    
    /**
     * Deactivate license
     *
     * @return array
     */
    public function deactivate_license() {
        $this->license_data = array(
            'license_key' => '',
            'tier' => self::TIER_FREE,
            'status' => 'inactive',
            'expires' => '',
            'activated_at' => '',
            'site_url' => home_url(),
            'last_check' => ''
        );
        
        update_option(self::LICENSE_OPTION_NAME, $this->license_data);
        
        return array(
            'success' => true,
            'message' => __('License deactivated successfully.', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Mock license validation for demo purposes
     *
     * @param string $license_key
     * @return array
     */
    private function mock_license_validation($license_key) {
        // Demo license keys for testing
        $demo_licenses = array(
            'SKYLRN-PRO-DEMO-2024' => array(
                'tier' => self::TIER_PRO,
                'expires' => date('Y-m-d', strtotime('+1 year'))
            ),
            'SKYLRN-PLUS-DEMO-2024' => array(
                'tier' => self::TIER_PRO_PLUS,
                'expires' => date('Y-m-d', strtotime('+1 year'))
            ),
            'SKYLRN-EXPIRED-DEMO' => array(
                'tier' => self::TIER_PRO,
                'expires' => date('Y-m-d', strtotime('-1 day'))
            )
        );
        
        if (isset($demo_licenses[$license_key])) {
            $license_info = $demo_licenses[$license_key];
            
            // Check if expired
            if (strtotime($license_info['expires']) < time()) {
                return array(
                    'success' => false,
                    'message' => __('This license key has expired. Please renew your license.', 'skylearn-billing-pro')
                );
            }
            
            return array(
                'success' => true,
                'tier' => $license_info['tier'],
                'expires' => $license_info['expires']
            );
        }
        
        return array(
            'success' => false,
            'message' => __('Invalid license key. Please check your key and try again.', 'skylearn-billing-pro')
        );
    }
    
    /**
     * AJAX handler for license validation
     */
    public function ajax_validate_license() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_license_nonce')) {
            wp_die(__('Security check failed.', 'skylearn-billing-pro'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'skylearn-billing-pro'));
        }
        
        $license_key = sanitize_text_field($_POST['license_key']);
        $result = $this->validate_license($license_key);
        
        wp_send_json($result);
    }
    
    /**
     * AJAX handler for license deactivation
     */
    public function ajax_deactivate_license() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_license_nonce')) {
            wp_die(__('Security check failed.', 'skylearn-billing-pro'));
        }
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'skylearn-billing-pro'));
        }
        
        $result = $this->deactivate_license();
        
        wp_send_json($result);
    }
    
    /**
     * Get days until license expires
     *
     * @return int|false
     */
    public function get_days_until_expiry() {
        if (empty($this->license_data['expires'])) {
            return false;
        }
        
        $expires = strtotime($this->license_data['expires']);
        $now = time();
        $diff = $expires - $now;
        
        return floor($diff / (24 * 60 * 60));
    }
    
    /**
     * Get upgrade URL for tier
     *
     * @param string $target_tier
     * @return string
     */
    public function get_upgrade_url($target_tier = null) {
        // In a real implementation, this would return the actual upgrade URL
        $base_url = 'https://skyian.com/skylearn-billing/upgrade/';
        
        switch ($target_tier) {
            case self::TIER_PRO:
                return $base_url . 'pro/';
            case self::TIER_PRO_PLUS:
                return $base_url . 'pro-plus/';
            default:
                return $base_url;
        }
    }
}

/**
 * Get the licensing manager instance
 *
 * @return SkyLearn_Billing_Pro_Licensing_Manager
 */
function skylearn_billing_pro_licensing() {
    return SkyLearn_Billing_Pro_Licensing_Manager::instance();
}