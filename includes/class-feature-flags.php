<?php
/**
 * Feature Flags System for Skylearn Billing Pro
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
 * Feature Flags Class
 * Manages feature availability based on license tier
 */
class SkyLearn_Billing_Pro_Feature_Flags {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Feature_Flags
     */
    private static $_instance = null;
    
    /**
     * Licensing manager instance
     *
     * @var SkyLearn_Billing_Pro_Licensing_Manager
     */
    private $licensing_manager;
    
    /**
     * Feature definitions
     *
     * @var array
     */
    private $features = array();
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Feature_Flags
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
        $this->define_features();
    }
    
    /**
     * Define all features and their tier requirements
     */
    private function define_features() {
        $this->features = array(
            // Core Features
            'basic_billing' => array(
                'name' => __('Basic Billing', 'skylearn-billing-pro'),
                'description' => __('Accept payments and manage basic billing', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE,
                'category' => 'core'
            ),
            
            // Payment Gateway Features
            'stripe_gateway' => array(
                'name' => __('Stripe Payment Gateway', 'skylearn-billing-pro'),
                'description' => __('Accept payments through Stripe', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'payment_gateways'
            ),
            'lemonsqueezy_gateway' => array(
                'name' => __('Lemon Squeezy Payment Gateway', 'skylearn-billing-pro'),
                'description' => __('Accept payments through Lemon Squeezy', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'payment_gateways'
            ),
            'paypal_gateway' => array(
                'name' => __('PayPal Payment Gateway', 'skylearn-billing-pro'),
                'description' => __('Accept payments through PayPal', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'payment_gateways'
            ),
            
            // Subscription Features
            'recurring_subscriptions' => array(
                'name' => __('Recurring Subscriptions', 'skylearn-billing-pro'),
                'description' => __('Create and manage recurring subscription plans', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'subscriptions'
            ),
            'subscription_analytics' => array(
                'name' => __('Subscription Analytics', 'skylearn-billing-pro'),
                'description' => __('Advanced analytics and reporting for subscriptions', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'subscriptions'
            ),
            
            // Product Management
            'unlimited_products' => array(
                'name' => __('Unlimited Products', 'skylearn-billing-pro'),
                'description' => __('Create unlimited products and courses', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'products',
                'limit' => array(
                    SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE => 3,
                    SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO => -1, // Unlimited
                    SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS => -1 // Unlimited
                )
            ),
            'advanced_product_options' => array(
                'name' => __('Advanced Product Options', 'skylearn-billing-pro'),
                'description' => __('Advanced product customization and options', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'products'
            ),
            
            // Email & Notifications
            'email_templates' => array(
                'name' => __('Custom Email Templates', 'skylearn-billing-pro'),
                'description' => __('Customize email templates and notifications', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'emails'
            ),
            'email_automation' => array(
                'name' => __('Email Automation', 'skylearn-billing-pro'),
                'description' => __('Automated email sequences and triggers', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'emails'
            ),
            
            // Customer Portal
            'customer_portal' => array(
                'name' => __('Customer Portal', 'skylearn-billing-pro'),
                'description' => __('Self-service customer portal', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'portal'
            ),
            'advanced_portal_widgets' => array(
                'name' => __('Advanced Portal Widgets', 'skylearn-billing-pro'),
                'description' => __('Additional widgets and customization for customer portal', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'portal'
            ),
            
            // Analytics & Reporting
            'basic_analytics' => array(
                'name' => __('Basic Analytics', 'skylearn-billing-pro'),
                'description' => __('Basic sales and customer analytics', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE,
                'category' => 'analytics'
            ),
            'advanced_analytics' => array(
                'name' => __('Advanced Analytics', 'skylearn-billing-pro'),
                'description' => __('Advanced analytics, reports, and insights', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'analytics'
            ),
            
            // Addons & Integrations
            'premium_addons' => array(
                'name' => __('Premium Addons', 'skylearn-billing-pro'),
                'description' => __('Access to premium addons and integrations', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'addons'
            ),
            'white_label' => array(
                'name' => __('White Label', 'skylearn-billing-pro'),
                'description' => __('Remove branding and white label the plugin', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'addons'
            ),
            
            // Support
            'priority_support' => array(
                'name' => __('Priority Support', 'skylearn-billing-pro'),
                'description' => __('Priority email and chat support', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'support'
            ),
            'phone_support' => array(
                'name' => __('Phone Support', 'skylearn-billing-pro'),
                'description' => __('Direct phone support access', 'skylearn-billing-pro'),
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'support'
            )
        );
        
        // Allow other plugins/themes to modify features
        $this->features = apply_filters('skylearn_billing_pro_features', $this->features);
    }
    
    /**
     * Check if a feature is available
     *
     * @param string $feature_key
     * @return bool
     */
    public function is_feature_available($feature_key) {
        if (!isset($this->features[$feature_key])) {
            return false;
        }
        
        $feature = $this->features[$feature_key];
        $current_tier = $this->licensing_manager->get_current_tier();
        $required_tier = $feature['required_tier'];
        
        // Check if license is active (except for free tier features)
        if ($required_tier !== SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE && !$this->licensing_manager->is_license_active()) {
            return false;
        }
        
        // Check tier hierarchy
        return $this->is_tier_sufficient($current_tier, $required_tier);
    }
    
    /**
     * Check if current tier meets the required tier
     *
     * @param string $current_tier
     * @param string $required_tier
     * @return bool
     */
    private function is_tier_sufficient($current_tier, $required_tier) {
        $tier_hierarchy = array(
            SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE => 0,
            SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO => 1,
            SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS => 2
        );
        
        $current_level = isset($tier_hierarchy[$current_tier]) ? $tier_hierarchy[$current_tier] : 0;
        $required_level = isset($tier_hierarchy[$required_tier]) ? $tier_hierarchy[$required_tier] : 0;
        
        return $current_level >= $required_level;
    }
    
    /**
     * Get feature limit for current tier
     *
     * @param string $feature_key
     * @return int|bool -1 for unlimited, number for limit, false if not available
     */
    public function get_feature_limit($feature_key) {
        if (!isset($this->features[$feature_key])) {
            return false;
        }
        
        $feature = $this->features[$feature_key];
        $current_tier = $this->licensing_manager->get_current_tier();
        
        // If feature has limits defined
        if (isset($feature['limit']) && isset($feature['limit'][$current_tier])) {
            return $feature['limit'][$current_tier];
        }
        
        // If feature is available, assume unlimited
        if ($this->is_feature_available($feature_key)) {
            return -1; // Unlimited
        }
        
        return false; // Not available
    }
    
    /**
     * Get all features by category
     *
     * @param string $category
     * @return array
     */
    public function get_features_by_category($category) {
        $category_features = array();
        
        foreach ($this->features as $key => $feature) {
            if ($feature['category'] === $category) {
                $category_features[$key] = $feature;
                $category_features[$key]['available'] = $this->is_feature_available($key);
            }
        }
        
        return $category_features;
    }
    
    /**
     * Get all features
     *
     * @return array
     */
    public function get_all_features() {
        $all_features = array();
        
        foreach ($this->features as $key => $feature) {
            $all_features[$key] = $feature;
            $all_features[$key]['available'] = $this->is_feature_available($key);
        }
        
        return $all_features;
    }
    
    /**
     * Get required tier for feature
     *
     * @param string $feature_key
     * @return string|false
     */
    public function get_required_tier($feature_key) {
        if (!isset($this->features[$feature_key])) {
            return false;
        }
        
        return $this->features[$feature_key]['required_tier'];
    }
    
    /**
     * Get feature info
     *
     * @param string $feature_key
     * @return array|false
     */
    public function get_feature_info($feature_key) {
        if (!isset($this->features[$feature_key])) {
            return false;
        }
        
        $feature = $this->features[$feature_key];
        $feature['available'] = $this->is_feature_available($feature_key);
        $feature['limit'] = $this->get_feature_limit($feature_key);
        
        return $feature;
    }
    
    /**
     * Check if user has reached feature limit
     *
     * @param string $feature_key
     * @param int $current_usage
     * @return bool
     */
    public function has_reached_limit($feature_key, $current_usage) {
        $limit = $this->get_feature_limit($feature_key);
        
        // No limit or unlimited
        if ($limit === false || $limit === -1) {
            return false;
        }
        
        return $current_usage >= $limit;
    }
    
    /**
     * Get upgrade message for feature
     *
     * @param string $feature_key
     * @return string
     */
    public function get_upgrade_message($feature_key) {
        if (!isset($this->features[$feature_key])) {
            return '';
        }
        
        $feature = $this->features[$feature_key];
        $required_tier = $feature['required_tier'];
        $tier_name = $this->licensing_manager->get_tier_display_name($required_tier);
        
        return sprintf(
            __('This feature requires %s or higher. <a href="%s" target="_blank">Upgrade now</a> to unlock this feature.', 'skylearn-billing-pro'),
            $tier_name,
            $this->licensing_manager->get_upgrade_url($required_tier)
        );
    }
}

/**
 * Get the feature flags instance
 *
 * @return SkyLearn_Billing_Pro_Feature_Flags
 */
function skylearn_billing_pro_features() {
    return SkyLearn_Billing_Pro_Feature_Flags::instance();
}

/**
 * Helper function to check if a feature is available
 *
 * @param string $feature_key
 * @return bool
 */
function skylearn_billing_pro_is_feature_available($feature_key) {
    return skylearn_billing_pro_features()->is_feature_available($feature_key);
}

/**
 * Helper function to get feature limit
 *
 * @param string $feature_key
 * @return int|bool
 */
function skylearn_billing_pro_get_feature_limit($feature_key) {
    return skylearn_billing_pro_features()->get_feature_limit($feature_key);
}