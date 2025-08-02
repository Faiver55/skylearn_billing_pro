<?php
/**
 * License Manager for Addons - Skylearn Billing Pro
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
 * License Manager Class for Addons
 * Handles license validation and feature eligibility specifically for addons
 */
class SkyLearn_Billing_Pro_License_Manager {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_License_Manager
     */
    private static $_instance = null;
    
    /**
     * Licensing manager instance
     *
     * @var SkyLearn_Billing_Pro_Licensing_Manager
     */
    private $licensing_manager;
    
    /**
     * Feature flags instance
     *
     * @var SkyLearn_Billing_Pro_Feature_Flags
     */
    private $feature_flags;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_License_Manager
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
        $this->licensing_manager = skylearn_billing_pro_licensing();
        $this->feature_flags = skylearn_billing_pro_features();
    }
    
    /**
     * Check if addon is accessible based on license tier
     *
     * @param string $addon_id Addon ID
     * @param string $required_tier Required license tier
     * @return bool True if accessible
     */
    public function is_addon_accessible($addon_id, $required_tier = null) {
        // Get addon data to determine required tier
        $addon_manager = skylearn_billing_pro_addon_manager();
        $addons = $addon_manager->get_available_addons();
        
        if (!isset($addons[$addon_id])) {
            return false;
        }
        
        $addon = $addons[$addon_id];
        $required_tier = $required_tier ?: $addon['required_tier'];
        
        // Free addons are always accessible
        if ($addon['type'] === 'free') {
            return true;
        }
        
        // Check license tier
        return $this->licensing_manager->has_tier($required_tier);
    }
    
    /**
     * Get upgrade message for addon
     *
     * @param string $addon_id Addon ID
     * @return string Upgrade message
     */
    public function get_addon_upgrade_message($addon_id) {
        $addon_manager = skylearn_billing_pro_addon_manager();
        $addons = $addon_manager->get_available_addons();
        
        if (!isset($addons[$addon_id])) {
            return __('Addon not found.', 'skylearn-billing-pro');
        }
        
        $addon = $addons[$addon_id];
        $required_tier = $addon['required_tier'];
        
        $tier_names = array(
            SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO => __('Pro', 'skylearn-billing-pro'),
            SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS => __('Pro Plus', 'skylearn-billing-pro')
        );
        
        $tier_name = $tier_names[$required_tier] ?? __('Pro', 'skylearn-billing-pro');
        
        return sprintf(
            __('This addon requires %s license. <a href="%s" target="_blank">Upgrade now</a> to unlock this feature.', 'skylearn-billing-pro'),
            $tier_name,
            $this->get_upgrade_url($required_tier)
        );
    }
    
    /**
     * Get upgrade URL for tier
     *
     * @param string $tier License tier
     * @return string Upgrade URL
     */
    public function get_upgrade_url($tier) {
        $base_url = 'https://skyian.com/skylearn-billing/pricing/';
        
        switch ($tier) {
            case SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO:
                return $base_url . '?plan=pro';
            case SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS:
                return $base_url . '?plan=pro-plus';
            default:
                return $base_url;
        }
    }
    
    /**
     * Check feature eligibility for addon functionality
     *
     * @param string $feature_key Feature key
     * @param string $addon_id Addon ID
     * @return bool True if eligible
     */
    public function is_feature_eligible($feature_key, $addon_id = null) {
        // Check basic feature availability
        if (!$this->feature_flags->is_feature_available($feature_key)) {
            return false;
        }
        
        // If addon is specified, check addon accessibility
        if ($addon_id && !$this->is_addon_accessible($addon_id)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get addon tier badge HTML
     *
     * @param string $addon_type Addon type (free/paid)
     * @param string $required_tier Required tier
     * @return string Badge HTML
     */
    public function get_addon_tier_badge($addon_type, $required_tier) {
        if ($addon_type === 'free') {
            return '<span class="skylearn-billing-addon-badge skylearn-billing-addon-free">' . __('Free', 'skylearn-billing-pro') . '</span>';
        }
        
        $badge_class = 'skylearn-billing-addon-pro';
        $badge_text = __('Pro', 'skylearn-billing-pro');
        
        if ($required_tier === SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS) {
            $badge_class = 'skylearn-billing-addon-plus';
            $badge_text = __('Pro+', 'skylearn-billing-pro');
        }
        
        return '<span class="skylearn-billing-addon-badge ' . esc_attr($badge_class) . '">' . esc_html($badge_text) . '</span>';
    }
    
    /**
     * Get license validation status for addon
     *
     * @param string $addon_id Addon ID
     * @return array Status information
     */
    public function get_addon_license_status($addon_id) {
        $addon_manager = skylearn_billing_pro_addon_manager();
        $addons = $addon_manager->get_available_addons();
        
        if (!isset($addons[$addon_id])) {
            return array(
                'valid' => false,
                'message' => __('Addon not found.', 'skylearn-billing-pro'),
                'action_required' => false
            );
        }
        
        $addon = $addons[$addon_id];
        
        // Free addons don't need license validation
        if ($addon['type'] === 'free') {
            return array(
                'valid' => true,
                'message' => __('No license required.', 'skylearn-billing-pro'),
                'action_required' => false
            );
        }
        
        $has_tier = $this->licensing_manager->has_tier($addon['required_tier']);
        $is_active = $this->licensing_manager->is_license_active();
        
        if (!$has_tier) {
            return array(
                'valid' => false,
                'message' => $this->get_addon_upgrade_message($addon_id),
                'action_required' => true,
                'action_type' => 'upgrade'
            );
        }
        
        if (!$is_active) {
            return array(
                'valid' => false,
                'message' => __('License is inactive. Please activate your license.', 'skylearn-billing-pro'),
                'action_required' => true,
                'action_type' => 'activate'
            );
        }
        
        return array(
            'valid' => true,
            'message' => __('License valid.', 'skylearn-billing-pro'),
            'action_required' => false
        );
    }
    
    /**
     * Log addon license check
     *
     * @param string $addon_id Addon ID
     * @param bool $access_granted Whether access was granted
     * @param string $reason Reason for decision
     */
    public function log_addon_access($addon_id, $access_granted, $reason = '') {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'Skylearn Billing Pro: Addon access check - %s: %s (Reason: %s)',
                $addon_id,
                $access_granted ? 'GRANTED' : 'DENIED',
                $reason
            ));
        }
        
        // Store access log in transient for admin review
        $log_key = 'skylearn_billing_addon_access_log';
        $log = get_transient($log_key) ?: array();
        
        $log[] = array(
            'timestamp' => current_time('timestamp'),
            'addon_id' => $addon_id,
            'access_granted' => $access_granted,
            'reason' => $reason,
            'user_id' => get_current_user_id(),
            'license_tier' => $this->licensing_manager->get_current_tier()
        );
        
        // Keep only last 100 entries
        $log = array_slice($log, -100);
        
        set_transient($log_key, $log, WEEK_IN_SECONDS);
    }
    
    /**
     * Get addon access log
     *
     * @return array Access log entries
     */
    public function get_addon_access_log() {
        return get_transient('skylearn_billing_addon_access_log') ?: array();
    }
    
    /**
     * Clear addon access log
     */
    public function clear_addon_access_log() {
        delete_transient('skylearn_billing_addon_access_log');
    }
}

/**
 * Get license manager instance
 *
 * @return SkyLearn_Billing_Pro_License_Manager
 */
function skylearn_billing_pro_license_manager() {
    return SkyLearn_Billing_Pro_License_Manager::instance();
}

// Initialize license manager
// skylearn_billing_pro_license_manager();