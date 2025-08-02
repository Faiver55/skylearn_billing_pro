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
     * Force refresh license data from database
     * This helps prevent caching issues
     */
    private function refresh_license_data() {
        // Clear any object cache for this option if wp_cache_delete exists
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete(self::LICENSE_OPTION_NAME, 'options');
        }
        
        // Reload from database
        $this->load_license_data();
    }
    
    /**
     * Clear WordPress option cache if available
     */
    private function clear_option_cache() {
        if (function_exists('wp_cache_delete')) {
            wp_cache_delete(self::LICENSE_OPTION_NAME, 'options');
        }
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
     * Check if current license has access to a specific tier
     *
     * @param string $required_tier
     * @return bool
     */
    public function has_tier($required_tier) {
        // If license is not active, only free tier is available
        if (!$this->is_license_active()) {
            return $required_tier === self::TIER_FREE;
        }
        
        $current_tier = $this->get_current_tier();
        
        // Tier hierarchy: free < pro < pro_plus
        $tier_hierarchy = array(
            self::TIER_FREE => 0,
            self::TIER_PRO => 1,
            self::TIER_PRO_PLUS => 2
        );
        
        $current_level = isset($tier_hierarchy[$current_tier]) ? $tier_hierarchy[$current_tier] : 0;
        $required_level = isset($tier_hierarchy[$required_tier]) ? $tier_hierarchy[$required_tier] : 0;
        
        return $current_level >= $required_level;
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
        $license_key = sanitize_text_field(trim($license_key));
        
        // Log license validation attempt
        $this->log_license_attempt($license_key, 'validation_started');
        
        if (empty($license_key)) {
            $this->log_license_attempt($license_key, 'empty_key');
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
            
            // Force update and clear cache
            update_option(self::LICENSE_OPTION_NAME, $this->license_data);
            $this->clear_option_cache();
            
            // Refresh our local copy to ensure consistency
            $this->refresh_license_data();
            
            $this->log_license_attempt($license_key, 'validation_success', $mock_response['tier']);
            
            return array(
                'success' => true,
                'message' => sprintf(__('License activated successfully! You now have access to %s features.', 'skylearn-billing-pro'), $this->get_tier_display_name($mock_response['tier'])),
                'tier' => $mock_response['tier']
            );
        }
        
        $this->log_license_attempt($license_key, 'validation_failed', null, $mock_response['message']);
        return $mock_response;
    }
    
    /**
     * Deactivate license
     *
     * @return array
     */
    public function deactivate_license() {
        $old_key = $this->license_data['license_key'];
        $old_tier = $this->license_data['tier'];
        
        $this->log_license_attempt($old_key, 'deactivation_started', $old_tier);
        
        $this->license_data = array(
            'license_key' => '',
            'tier' => self::TIER_FREE,
            'status' => 'inactive',
            'expires' => '',
            'activated_at' => '',
            'site_url' => home_url(),
            'last_check' => ''
        );
        
        // Force update and clear cache
        update_option(self::LICENSE_OPTION_NAME, $this->license_data);
        $this->clear_option_cache();
        
        // Refresh our local copy to ensure consistency
        $this->refresh_license_data();
        
        $this->log_license_attempt($old_key, 'deactivation_success', $old_tier);
        
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
        // Normalize the license key for comparison
        $normalized_key = strtoupper(trim($license_key));
        
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
            'SKYLEARN-PRO-DEMO-2024' => array(
                'tier' => self::TIER_PRO,
                'expires' => date('Y-m-d', strtotime('+1 year'))
            ),
            'SKYLEARN-PLUS-DEMO-2024' => array(
                'tier' => self::TIER_PRO_PLUS,
                'expires' => date('Y-m-d', strtotime('+1 year'))
            ),
            'SKYLRN-EXPIRED-DEMO' => array(
                'tier' => self::TIER_PRO,
                'expires' => date('Y-m-d', strtotime('-1 day'))
            ),
            // Additional demo keys for testing
            'DEMO-PRO-2024' => array(
                'tier' => self::TIER_PRO,
                'expires' => date('Y-m-d', strtotime('+6 months'))
            ),
            'DEMO-PLUS-2024' => array(
                'tier' => self::TIER_PRO_PLUS,
                'expires' => date('Y-m-d', strtotime('+6 months'))
            )
        );
        
        if (isset($demo_licenses[$normalized_key])) {
            $license_info = $demo_licenses[$normalized_key];
            
            // Check if expired
            if (strtotime($license_info['expires']) < time()) {
                $this->log_license_attempt($license_key, 'expired_demo_key');
                return array(
                    'success' => false,
                    'message' => __('This demo license key has expired. Please use a current demo key or purchase a license.', 'skylearn-billing-pro')
                );
            }
            
            $this->log_license_attempt($license_key, 'valid_demo_key', $license_info['tier']);
            return array(
                'success' => true,
                'tier' => $license_info['tier'],
                'expires' => $license_info['expires']
            );
        }
        
        // Check for common demo key patterns that might be variations
        if (preg_match('/^(SKYLRN|SKYLEARN).*(DEMO|TEST).*2024$/i', $normalized_key)) {
            $this->log_license_attempt($license_key, 'demo_key_pattern_match_failed');
            return array(
                'success' => false,
                'message' => __('Demo license key format recognized but not valid. Please use one of the provided demo keys: SKYLRN-PRO-DEMO-2024 or SKYLRN-PLUS-DEMO-2024', 'skylearn-billing-pro')
            );
        }
        
        // Check for other demo patterns
        if (preg_match('/^DEMO-.*(PRO|PLUS).*2024$/i', $normalized_key)) {
            $this->log_license_attempt($license_key, 'demo_key_pattern_match_failed');
            return array(
                'success' => false,
                'message' => __('Demo license key format recognized but not valid. Please use: DEMO-PRO-2024 or DEMO-PLUS-2024', 'skylearn-billing-pro')
            );
        }
        
        $this->log_license_attempt($license_key, 'invalid_key');
        return array(
            'success' => false,
            'message' => __('Invalid license key. Please check your key and try again, or use one of the demo keys for testing.', 'skylearn-billing-pro')
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
    
    /**
     * Log license validation attempts for debugging
     *
     * @param string $license_key
     * @param string $action
     * @param string $tier
     * @param string $message
     */
    private function log_license_attempt($license_key, $action, $tier = null, $message = null) {
        // Mask the license key for security (show only first 4 and last 4 characters)
        $masked_key = '';
        if (strlen($license_key) > 8) {
            $masked_key = substr($license_key, 0, 4) . '****' . substr($license_key, -4);
        } else {
            $masked_key = str_repeat('*', strlen($license_key));
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'masked_key' => $masked_key,
            'action' => $action,
            'tier' => $tier,
            'message' => $message,
            'user_id' => get_current_user_id(),
            'ip_address' => $this->get_client_ip()
        );
        
        // Store in transient for admin review
        $log_key = 'skylearn_billing_license_log';
        $log = get_transient($log_key) ?: array();
        $log[] = $log_entry;
        
        // Keep only last 50 entries
        $log = array_slice($log, -50);
        set_transient($log_key, $log, WEEK_IN_SECONDS);
        
        // Also log to WordPress debug log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'SkyLearn Billing Pro License: %s - Key: %s, Action: %s, Tier: %s, Message: %s',
                current_time('mysql'),
                $masked_key,
                $action,
                $tier ?: 'N/A',
                $message ?: 'N/A'
            ));
        }
    }
    
    /**
     * Get client IP address
     *
     * @return string
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
    }
    
    /**
     * Get license validation log
     *
     * @return array
     */
    public function get_license_log() {
        return get_transient('skylearn_billing_license_log') ?: array();
    }
    
    /**
     * Clear license validation log
     */
    public function clear_license_log() {
        delete_transient('skylearn_billing_license_log');
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