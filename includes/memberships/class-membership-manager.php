<?php
/**
 * Membership Manager for Skylearn Billing Pro
 *
 * Handles access control and membership levels
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
 * Membership Manager class
 */
class SkyLearn_Billing_Pro_Membership_Manager {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Membership_Manager
     */
    private static $_instance = null;
    
    /**
     * Membership levels
     */
    const LEVEL_FREE = 'free';
    const LEVEL_BASIC = 'basic';
    const LEVEL_PREMIUM = 'premium';
    const LEVEL_PRO = 'pro';
    const LEVEL_VIP = 'vip';
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Membership_Manager
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
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'));
        add_filter('user_has_cap', array($this, 'filter_user_capabilities'), 10, 4);
        add_action('skylearn_billing_subscription_created', array($this, 'handle_subscription_membership'), 10, 2);
        add_action('skylearn_billing_subscription_cancelled', array($this, 'handle_subscription_cancellation'), 10, 2);
        add_action('skylearn_billing_subscription_expired', array($this, 'handle_subscription_expiry'), 10, 1);
        add_action('skylearn_billing_subscription_upgraded', array($this, 'handle_subscription_upgrade'), 10, 3);
        add_action('skylearn_billing_subscription_downgraded', array($this, 'handle_subscription_downgrade'), 10, 3);
        
        // Content restriction hooks
        add_filter('the_content', array($this, 'restrict_content'));
        add_action('template_redirect', array($this, 'redirect_restricted_content'));
    }
    
    /**
     * Initialize membership manager
     */
    public function init() {
        // Initialize membership levels
        $this->register_default_membership_levels();
        
        do_action('skylearn_billing_membership_manager_init');
    }
    
    /**
     * Register default membership levels
     */
    private function register_default_membership_levels() {
        $default_levels = array(
            self::LEVEL_FREE => array(
                'name' => __('Free', 'skylearn-billing-pro'),
                'description' => __('Free membership with basic access', 'skylearn-billing-pro'),
                'capabilities' => array('read'),
                'priority' => 1,
                'restrictions' => array(
                    'course_limit' => 3,
                    'download_limit' => 5,
                    'support_level' => 'community'
                )
            ),
            self::LEVEL_BASIC => array(
                'name' => __('Basic', 'skylearn-billing-pro'),
                'description' => __('Basic membership with extended access', 'skylearn-billing-pro'),
                'capabilities' => array('read', 'access_basic_courses'),
                'priority' => 2,
                'restrictions' => array(
                    'course_limit' => 10,
                    'download_limit' => 25,
                    'support_level' => 'email'
                )
            ),
            self::LEVEL_PREMIUM => array(
                'name' => __('Premium', 'skylearn-billing-pro'),
                'description' => __('Premium membership with full course access', 'skylearn-billing-pro'),
                'capabilities' => array('read', 'access_basic_courses', 'access_premium_courses'),
                'priority' => 3,
                'restrictions' => array(
                    'course_limit' => 50,
                    'download_limit' => 100,
                    'support_level' => 'priority'
                )
            ),
            self::LEVEL_PRO => array(
                'name' => __('Pro', 'skylearn-billing-pro'),
                'description' => __('Professional membership with advanced features', 'skylearn-billing-pro'),
                'capabilities' => array('read', 'access_basic_courses', 'access_premium_courses', 'access_pro_courses'),
                'priority' => 4,
                'restrictions' => array(
                    'course_limit' => -1, // Unlimited
                    'download_limit' => -1, // Unlimited
                    'support_level' => 'priority'
                )
            ),
            self::LEVEL_VIP => array(
                'name' => __('VIP', 'skylearn-billing-pro'),
                'description' => __('VIP membership with exclusive access', 'skylearn-billing-pro'),
                'capabilities' => array('read', 'access_basic_courses', 'access_premium_courses', 'access_pro_courses', 'access_vip_content'),
                'priority' => 5,
                'restrictions' => array(
                    'course_limit' => -1, // Unlimited
                    'download_limit' => -1, // Unlimited
                    'support_level' => 'white_glove'
                )
            )
        );
        
        // Allow customization of membership levels
        $membership_levels = apply_filters('skylearn_billing_membership_levels', $default_levels);
        
        // Store membership levels
        update_option('skylearn_billing_membership_levels', $membership_levels);
    }
    
    /**
     * Get membership levels
     *
     * @return array Membership levels
     */
    public function get_membership_levels() {
        return get_option('skylearn_billing_membership_levels', array());
    }
    
    /**
     * Get membership level
     *
     * @param string $level_id Level ID
     * @return array|false Membership level data or false if not found
     */
    public function get_membership_level($level_id) {
        $levels = $this->get_membership_levels();
        return isset($levels[$level_id]) ? $levels[$level_id] : false;
    }
    
    /**
     * Set user membership level
     *
     * @param int $user_id User ID
     * @param string $level_id Membership level ID
     * @param array $metadata Additional metadata
     * @return bool True on success, false on failure
     */
    public function set_user_membership_level($user_id, $level_id, $metadata = array()) {
        $level = $this->get_membership_level($level_id);
        if (!$level) {
            return false;
        }
        
        $membership_data = array(
            'level_id' => $level_id,
            'level_name' => $level['name'],
            'assigned_at' => current_time('mysql'),
            'metadata' => $metadata
        );
        
        // Store current membership
        update_user_meta($user_id, 'skylearn_billing_membership_level', $level_id);
        update_user_meta($user_id, 'skylearn_billing_membership_data', $membership_data);
        
        // Store membership history
        $membership_history = get_user_meta($user_id, 'skylearn_billing_membership_history', true);
        if (!is_array($membership_history)) {
            $membership_history = array();
        }
        
        $membership_history[] = $membership_data;
        update_user_meta($user_id, 'skylearn_billing_membership_history', $membership_history);
        
        do_action('skylearn_billing_membership_level_set', $user_id, $level_id, $metadata);
        
        return true;
    }
    
    /**
     * Get user membership level
     *
     * @param int $user_id User ID
     * @return string Membership level ID
     */
    public function get_user_membership_level($user_id) {
        return get_user_meta($user_id, 'skylearn_billing_membership_level', true) ?: self::LEVEL_FREE;
    }
    
    /**
     * Get user membership data
     *
     * @param int $user_id User ID
     * @return array Membership data
     */
    public function get_user_membership_data($user_id) {
        $membership_data = get_user_meta($user_id, 'skylearn_billing_membership_data', true);
        if (!is_array($membership_data)) {
            $level_id = $this->get_user_membership_level($user_id);
            $level = $this->get_membership_level($level_id);
            
            $membership_data = array(
                'level_id' => $level_id,
                'level_name' => $level ? $level['name'] : __('Free', 'skylearn-billing-pro'),
                'assigned_at' => current_time('mysql'),
                'metadata' => array()
            );
        }
        
        return $membership_data;
    }
    
    /**
     * Check if user has membership level or higher
     *
     * @param int $user_id User ID
     * @param string $required_level Required membership level
     * @return bool True if user has required level or higher
     */
    public function user_has_membership_level($user_id, $required_level) {
        $user_level = $this->get_user_membership_level($user_id);
        $user_level_data = $this->get_membership_level($user_level);
        $required_level_data = $this->get_membership_level($required_level);
        
        if (!$user_level_data || !$required_level_data) {
            return false;
        }
        
        return $user_level_data['priority'] >= $required_level_data['priority'];
    }
    
    /**
     * Check if user can access content
     *
     * @param int $user_id User ID
     * @param string $content_type Content type (course, product, download, etc.)
     * @param int $content_id Content ID
     * @return bool True if user can access content
     */
    public function user_can_access_content($user_id, $content_type, $content_id) {
        $user_level = $this->get_user_membership_level($user_id);
        $level_data = $this->get_membership_level($user_level);
        
        if (!$level_data) {
            return false;
        }
        
        // Check content-specific restrictions
        $content_restrictions = get_post_meta($content_id, 'skylearn_billing_membership_restrictions', true);
        if (is_array($content_restrictions) && !empty($content_restrictions['required_level'])) {
            return $this->user_has_membership_level($user_id, $content_restrictions['required_level']);
        }
        
        // Check global restrictions based on content type
        switch ($content_type) {
            case 'course':
                $course_access = $this->check_course_access($user_id, $content_id);
                return $course_access;
                
            case 'download':
                return $this->check_download_access($user_id, $content_id);
                
            case 'product':
                return $this->check_product_access($user_id, $content_id);
                
            default:
                return apply_filters('skylearn_billing_user_can_access_content', true, $user_id, $content_type, $content_id);
        }
    }
    
    /**
     * Check course access
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool True if user can access course
     */
    private function check_course_access($user_id, $course_id) {
        $user_level = $this->get_user_membership_level($user_id);
        $level_data = $this->get_membership_level($user_level);
        
        if (!$level_data) {
            return false;
        }
        
        // Check course limit
        if (isset($level_data['restrictions']['course_limit']) && $level_data['restrictions']['course_limit'] > 0) {
            $enrolled_courses = $this->get_user_enrolled_courses_count($user_id);
            if ($enrolled_courses >= $level_data['restrictions']['course_limit']) {
                return false;
            }
        }
        
        // Check capabilities
        $course_level = get_post_meta($course_id, 'skylearn_billing_course_level', true);
        if ($course_level) {
            $required_capability = 'access_' . $course_level . '_courses';
            return in_array($required_capability, $level_data['capabilities']);
        }
        
        return true;
    }
    
    /**
     * Check download access
     *
     * @param int $user_id User ID
     * @param int $download_id Download ID
     * @return bool True if user can access download
     */
    private function check_download_access($user_id, $download_id) {
        $user_level = $this->get_user_membership_level($user_id);
        $level_data = $this->get_membership_level($user_level);
        
        if (!$level_data) {
            return false;
        }
        
        // Check download limit
        if (isset($level_data['restrictions']['download_limit']) && $level_data['restrictions']['download_limit'] > 0) {
            $download_count = $this->get_user_download_count($user_id);
            if ($download_count >= $level_data['restrictions']['download_limit']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check product access
     *
     * @param int $user_id User ID
     * @param int $product_id Product ID
     * @return bool True if user can access product
     */
    private function check_product_access($user_id, $product_id) {
        $user_level = $this->get_user_membership_level($user_id);
        $product_level = get_post_meta($product_id, 'skylearn_billing_product_level', true);
        
        if ($product_level) {
            return $this->user_has_membership_level($user_id, $product_level);
        }
        
        return true;
    }
    
    /**
     * Get user enrolled courses count
     *
     * @param int $user_id User ID
     * @return int Number of enrolled courses
     */
    private function get_user_enrolled_courses_count($user_id) {
        // This would integrate with the LMS to get actual enrollment count
        $enrolled_courses = get_user_meta($user_id, 'skylearn_billing_enrolled_courses', true);
        return is_array($enrolled_courses) ? count($enrolled_courses) : 0;
    }
    
    /**
     * Get user download count for current period
     *
     * @param int $user_id User ID
     * @return int Number of downloads in current period
     */
    private function get_user_download_count($user_id) {
        $download_log = get_user_meta($user_id, 'skylearn_billing_download_log', true);
        if (!is_array($download_log)) {
            return 0;
        }
        
        // Count downloads in current month
        $current_month = date('Y-m');
        $count = 0;
        
        foreach ($download_log as $download) {
            if (isset($download['date']) && strpos($download['date'], $current_month) === 0) {
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Filter user capabilities based on membership level
     *
     * @param array $allcaps All capabilities
     * @param array $caps Required capabilities
     * @param array $args Arguments
     * @param WP_User $user User object
     * @return array Modified capabilities
     */
    public function filter_user_capabilities($allcaps, $caps, $args, $user) {
        if (!$user || !$user->ID) {
            return $allcaps;
        }
        
        $user_level = $this->get_user_membership_level($user->ID);
        $level_data = $this->get_membership_level($user_level);
        
        if ($level_data && isset($level_data['capabilities'])) {
            foreach ($level_data['capabilities'] as $capability) {
                $allcaps[$capability] = true;
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Handle subscription membership assignment
     *
     * @param string $subscription_id Subscription ID
     * @param array $subscription_data Subscription data
     */
    public function handle_subscription_membership($subscription_id, $subscription_data) {
        $user_id = $subscription_data['user_id'];
        $tier = $subscription_data['tier'];
        
        // Map subscription tier to membership level
        $membership_level = $this->map_tier_to_membership_level($tier);
        
        if ($membership_level) {
            $this->set_user_membership_level($user_id, $membership_level, array(
                'subscription_id' => $subscription_id,
                'tier' => $tier
            ));
        }
    }
    
    /**
     * Handle subscription cancellation
     *
     * @param string $subscription_id Subscription ID
     * @param bool $immediate Whether cancellation is immediate
     */
    public function handle_subscription_cancellation($subscription_id, $immediate) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_subscription($subscription_id);
        
        if ($subscription && $immediate) {
            // Downgrade to free membership
            $this->set_user_membership_level($subscription['user_id'], self::LEVEL_FREE, array(
                'reason' => 'subscription_cancelled',
                'previous_subscription_id' => $subscription_id
            ));
        }
    }
    
    /**
     * Handle subscription expiry
     *
     * @param string $subscription_id Subscription ID
     */
    public function handle_subscription_expiry($subscription_id) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_subscription($subscription_id);
        
        if ($subscription) {
            // Downgrade to free membership
            $this->set_user_membership_level($subscription['user_id'], self::LEVEL_FREE, array(
                'reason' => 'subscription_expired',
                'previous_subscription_id' => $subscription_id
            ));
        }
    }
    
    /**
     * Handle subscription upgrade
     *
     * @param string $subscription_id Subscription ID
     * @param string $new_plan_id New plan ID
     * @param string $new_tier New tier
     */
    public function handle_subscription_upgrade($subscription_id, $new_plan_id, $new_tier) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_subscription($subscription_id);
        
        if ($subscription) {
            $new_membership_level = $this->map_tier_to_membership_level($new_tier);
            
            if ($new_membership_level) {
                $this->set_user_membership_level($subscription['user_id'], $new_membership_level, array(
                    'subscription_id' => $subscription_id,
                    'tier' => $new_tier,
                    'reason' => 'subscription_upgraded'
                ));
            }
        }
    }
    
    /**
     * Handle subscription downgrade
     *
     * @param string $subscription_id Subscription ID
     * @param string $new_plan_id New plan ID
     * @param string $new_tier New tier
     */
    public function handle_subscription_downgrade($subscription_id, $new_plan_id, $new_tier) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_subscription($subscription_id);
        
        if ($subscription) {
            $new_membership_level = $this->map_tier_to_membership_level($new_tier);
            
            if ($new_membership_level) {
                $this->set_user_membership_level($subscription['user_id'], $new_membership_level, array(
                    'subscription_id' => $subscription_id,
                    'tier' => $new_tier,
                    'reason' => 'subscription_downgraded'
                ));
            }
        }
    }
    
    /**
     * Map subscription tier to membership level
     *
     * @param string $tier Subscription tier
     * @return string Membership level
     */
    private function map_tier_to_membership_level($tier) {
        $mapping = apply_filters('skylearn_billing_tier_membership_mapping', array(
            'basic' => self::LEVEL_BASIC,
            'premium' => self::LEVEL_PREMIUM,
            'pro' => self::LEVEL_PRO,
            'vip' => self::LEVEL_VIP
        ));
        
        return isset($mapping[$tier]) ? $mapping[$tier] : self::LEVEL_FREE;
    }
    
    /**
     * Restrict content based on membership level
     *
     * @param string $content Post content
     * @return string Modified content
     */
    public function restrict_content($content) {
        global $post;
        
        if (!$post || is_admin()) {
            return $content;
        }
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            return $this->get_login_required_message() . $content;
        }
        
        $required_level = get_post_meta($post->ID, 'skylearn_billing_required_membership_level', true);
        if ($required_level && !$this->user_has_membership_level($user_id, $required_level)) {
            return $this->get_upgrade_required_message($required_level);
        }
        
        return $content;
    }
    
    /**
     * Redirect restricted content
     */
    public function redirect_restricted_content() {
        global $post;
        
        if (!$post || is_admin()) {
            return;
        }
        
        $user_id = get_current_user_id();
        $required_level = get_post_meta($post->ID, 'skylearn_billing_required_membership_level', true);
        
        if ($required_level) {
            if (!$user_id) {
                // Redirect to login
                $login_url = wp_login_url(get_permalink($post->ID));
                wp_redirect($login_url);
                exit;
            } elseif (!$this->user_has_membership_level($user_id, $required_level)) {
                // Redirect to upgrade page
                $upgrade_url = apply_filters('skylearn_billing_upgrade_url', home_url('/upgrade/'));
                wp_redirect($upgrade_url);
                exit;
            }
        }
    }
    
    /**
     * Get login required message
     *
     * @return string Login required message
     */
    private function get_login_required_message() {
        $message = '<div class="skylearn-billing-restriction-notice login-required">';
        $message .= '<h3>' . __('Login Required', 'skylearn-billing-pro') . '</h3>';
        $message .= '<p>' . __('Please log in to access this content.', 'skylearn-billing-pro') . '</p>';
        $message .= '<p><a href="' . wp_login_url(get_permalink()) . '" class="button">' . __('Login', 'skylearn-billing-pro') . '</a></p>';
        $message .= '</div>';
        
        return apply_filters('skylearn_billing_login_required_message', $message);
    }
    
    /**
     * Get upgrade required message
     *
     * @param string $required_level Required membership level
     * @return string Upgrade required message
     */
    private function get_upgrade_required_message($required_level) {
        $level_data = $this->get_membership_level($required_level);
        $level_name = $level_data ? $level_data['name'] : $required_level;
        
        $message = '<div class="skylearn-billing-restriction-notice upgrade-required">';
        $message .= '<h3>' . __('Upgrade Required', 'skylearn-billing-pro') . '</h3>';
        $message .= '<p>' . sprintf(__('This content requires %s membership or higher.', 'skylearn-billing-pro'), $level_name) . '</p>';
        $message .= '<p><a href="' . apply_filters('skylearn_billing_upgrade_url', home_url('/upgrade/')) . '" class="button">' . __('Upgrade Now', 'skylearn-billing-pro') . '</a></p>';
        $message .= '</div>';
        
        return apply_filters('skylearn_billing_upgrade_required_message', $message, $required_level);
    }
}

/**
 * Get the membership manager instance
 *
 * @return SkyLearn_Billing_Pro_Membership_Manager
 */
function skylearn_billing_pro_membership_manager() {
    return SkyLearn_Billing_Pro_Membership_Manager::instance();
}