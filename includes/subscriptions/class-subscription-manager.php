<?php
/**
 * Subscription Manager for Skylearn Billing Pro
 *
 * Handles multiple tiers, bundles, pause/resume, upgrades/downgrades, renewals
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
 * Subscription Manager class
 */
class SkyLearn_Billing_Pro_Subscription_Manager {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Subscription_Manager
     */
    private static $_instance = null;
    
    /**
     * Subscription statuses
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_PAUSED = 'paused';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_EXPIRED = 'expired';
    const STATUS_PENDING = 'pending';
    
    /**
     * Subscription tiers
     */
    const TIER_BASIC = 'basic';
    const TIER_PREMIUM = 'premium';
    const TIER_PRO = 'pro';
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Subscription_Manager
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
        add_action('wp_ajax_skylearn_subscription_action', array($this, 'handle_subscription_action'));
        add_action('wp_ajax_nopriv_skylearn_subscription_action', array($this, 'handle_subscription_action'));
        add_action('skylearn_billing_subscription_expired', array($this, 'handle_subscription_expiry'));
        add_action('skylearn_billing_subscription_renewed', array($this, 'handle_subscription_renewal'));
        
        // Daily task for subscription maintenance
        add_action('skylearn_billing_pro_daily_tasks', array($this, 'daily_subscription_maintenance'));
    }
    
    /**
     * Initialize subscription manager
     */
    public function init() {
        // Initialize subscription manager
        do_action('skylearn_billing_subscription_manager_init');
    }
    
    /**
     * Create a new subscription
     *
     * @param array $args Subscription arguments
     * @return int|WP_Error Subscription ID or error
     */
    public function create_subscription($args) {
        $defaults = array(
            'user_id' => 0,
            'plan_id' => '',
            'tier' => self::TIER_BASIC,
            'status' => self::STATUS_PENDING,
            'amount' => 0,
            'currency' => 'USD',
            'billing_cycle' => 'monthly',
            'start_date' => current_time('mysql'),
            'next_payment_date' => '',
            'trial_end_date' => '',
            'is_bundle' => false,
            'bundle_items' => array(),
            'metadata' => array()
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Validate required fields
        if (empty($args['user_id']) || empty($args['plan_id'])) {
            return new WP_Error('missing_required_fields', __('User ID and Plan ID are required.', 'skylearn-billing-pro'));
        }
        
        // Calculate next payment date if not provided
        if (empty($args['next_payment_date'])) {
            $args['next_payment_date'] = $this->calculate_next_payment_date($args['start_date'], $args['billing_cycle']);
        }
        
        // Store subscription data
        $subscription_data = array(
            'user_id' => absint($args['user_id']),
            'plan_id' => sanitize_text_field($args['plan_id']),
            'tier' => sanitize_text_field($args['tier']),
            'status' => sanitize_text_field($args['status']),
            'amount' => floatval($args['amount']),
            'currency' => sanitize_text_field($args['currency']),
            'billing_cycle' => sanitize_text_field($args['billing_cycle']),
            'start_date' => sanitize_text_field($args['start_date']),
            'next_payment_date' => sanitize_text_field($args['next_payment_date']),
            'trial_end_date' => sanitize_text_field($args['trial_end_date']),
            'is_bundle' => (bool) $args['is_bundle'],
            'bundle_items' => is_array($args['bundle_items']) ? $args['bundle_items'] : array(),
            'metadata' => is_array($args['metadata']) ? $args['metadata'] : array(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        // Generate unique subscription ID
        $subscription_id = wp_generate_uuid4();
        
        // Store in user meta for now (can be moved to custom table later)
        $user_subscriptions = get_user_meta($args['user_id'], 'skylearn_billing_subscriptions', true);
        if (!is_array($user_subscriptions)) {
            $user_subscriptions = array();
        }
        
        $user_subscriptions[$subscription_id] = $subscription_data;
        update_user_meta($args['user_id'], 'skylearn_billing_subscriptions', $user_subscriptions);
        
        // Set active subscription if this is the first one
        $active_subscription = get_user_meta($args['user_id'], 'skylearn_billing_active_subscription', true);
        if (empty($active_subscription) && $args['status'] === self::STATUS_ACTIVE) {
            update_user_meta($args['user_id'], 'skylearn_billing_active_subscription', $subscription_id);
        }
        
        do_action('skylearn_billing_subscription_created', $subscription_id, $subscription_data);
        
        return $subscription_id;
    }
    
    /**
     * Get subscription by ID
     *
     * @param string $subscription_id Subscription ID
     * @param int $user_id User ID (optional)
     * @return array|false Subscription data or false if not found
     */
    public function get_subscription($subscription_id, $user_id = null) {
        if ($user_id) {
            $user_subscriptions = get_user_meta($user_id, 'skylearn_billing_subscriptions', true);
            if (is_array($user_subscriptions) && isset($user_subscriptions[$subscription_id])) {
                return array_merge($user_subscriptions[$subscription_id], array('id' => $subscription_id));
            }
        } else {
            // Search all users if user_id not provided (less efficient)
            $users = get_users(array('meta_key' => 'skylearn_billing_subscriptions'));
            foreach ($users as $user) {
                $user_subscriptions = get_user_meta($user->ID, 'skylearn_billing_subscriptions', true);
                if (is_array($user_subscriptions) && isset($user_subscriptions[$subscription_id])) {
                    return array_merge($user_subscriptions[$subscription_id], array('id' => $subscription_id));
                }
            }
        }
        
        return false;
    }
    
    /**
     * Update subscription
     *
     * @param string $subscription_id Subscription ID
     * @param array $args Update arguments
     * @return bool True on success, false on failure
     */
    public function update_subscription($subscription_id, $args) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            return false;
        }
        
        $user_id = $subscription['user_id'];
        $user_subscriptions = get_user_meta($user_id, 'skylearn_billing_subscriptions', true);
        
        if (!is_array($user_subscriptions) || !isset($user_subscriptions[$subscription_id])) {
            return false;
        }
        
        // Update fields
        foreach ($args as $key => $value) {
            if (in_array($key, array('tier', 'status', 'amount', 'currency', 'billing_cycle', 'next_payment_date', 'trial_end_date', 'is_bundle', 'bundle_items', 'metadata'))) {
                $user_subscriptions[$subscription_id][$key] = $value;
            }
        }
        
        $user_subscriptions[$subscription_id]['updated_at'] = current_time('mysql');
        
        update_user_meta($user_id, 'skylearn_billing_subscriptions', $user_subscriptions);
        
        do_action('skylearn_billing_subscription_updated', $subscription_id, $args);
        
        return true;
    }
    
    /**
     * Pause subscription
     *
     * @param string $subscription_id Subscription ID
     * @return bool True on success, false on failure
     */
    public function pause_subscription($subscription_id) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription || $subscription['status'] !== self::STATUS_ACTIVE) {
            return false;
        }
        
        $result = $this->update_subscription($subscription_id, array(
            'status' => self::STATUS_PAUSED,
            'paused_at' => current_time('mysql')
        ));
        
        if ($result) {
            do_action('skylearn_billing_subscription_paused', $subscription_id);
        }
        
        return $result;
    }
    
    /**
     * Resume subscription
     *
     * @param string $subscription_id Subscription ID
     * @return bool True on success, false on failure
     */
    public function resume_subscription($subscription_id) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription || $subscription['status'] !== self::STATUS_PAUSED) {
            return false;
        }
        
        // Calculate new next payment date
        $next_payment_date = $this->calculate_next_payment_date(current_time('mysql'), $subscription['billing_cycle']);
        
        $result = $this->update_subscription($subscription_id, array(
            'status' => self::STATUS_ACTIVE,
            'next_payment_date' => $next_payment_date,
            'resumed_at' => current_time('mysql')
        ));
        
        if ($result) {
            do_action('skylearn_billing_subscription_resumed', $subscription_id);
        }
        
        return $result;
    }
    
    /**
     * Cancel subscription
     *
     * @param string $subscription_id Subscription ID
     * @param bool $immediate Whether to cancel immediately or at period end
     * @return bool True on success, false on failure
     */
    public function cancel_subscription($subscription_id, $immediate = false) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription) {
            return false;
        }
        
        $update_data = array(
            'cancelled_at' => current_time('mysql')
        );
        
        if ($immediate) {
            $update_data['status'] = self::STATUS_CANCELLED;
        } else {
            $update_data['cancel_at_period_end'] = true;
        }
        
        $result = $this->update_subscription($subscription_id, $update_data);
        
        if ($result) {
            do_action('skylearn_billing_subscription_cancelled', $subscription_id, $immediate);
        }
        
        return $result;
    }
    
    /**
     * Upgrade subscription
     *
     * @param string $subscription_id Subscription ID
     * @param string $new_plan_id New plan ID
     * @param string $new_tier New tier
     * @return bool True on success, false on failure
     */
    public function upgrade_subscription($subscription_id, $new_plan_id, $new_tier) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription || $subscription['status'] !== self::STATUS_ACTIVE) {
            return false;
        }
        
        $result = $this->update_subscription($subscription_id, array(
            'plan_id' => $new_plan_id,
            'tier' => $new_tier,
            'upgraded_at' => current_time('mysql'),
            'previous_plan_id' => $subscription['plan_id'],
            'previous_tier' => $subscription['tier']
        ));
        
        if ($result) {
            do_action('skylearn_billing_subscription_upgraded', $subscription_id, $new_plan_id, $new_tier);
        }
        
        return $result;
    }
    
    /**
     * Downgrade subscription
     *
     * @param string $subscription_id Subscription ID
     * @param string $new_plan_id New plan ID
     * @param string $new_tier New tier
     * @return bool True on success, false on failure
     */
    public function downgrade_subscription($subscription_id, $new_plan_id, $new_tier) {
        $subscription = $this->get_subscription($subscription_id);
        if (!$subscription || $subscription['status'] !== self::STATUS_ACTIVE) {
            return false;
        }
        
        $result = $this->update_subscription($subscription_id, array(
            'plan_id' => $new_plan_id,
            'tier' => $new_tier,
            'downgraded_at' => current_time('mysql'),
            'previous_plan_id' => $subscription['plan_id'],
            'previous_tier' => $subscription['tier']
        ));
        
        if ($result) {
            do_action('skylearn_billing_subscription_downgraded', $subscription_id, $new_plan_id, $new_tier);
        }
        
        return $result;
    }
    
    /**
     * Get user's active subscription
     *
     * @param int $user_id User ID
     * @return array|false Active subscription or false if none found
     */
    public function get_user_active_subscription($user_id) {
        $active_subscription_id = get_user_meta($user_id, 'skylearn_billing_active_subscription', true);
        if ($active_subscription_id) {
            return $this->get_subscription($active_subscription_id, $user_id);
        }
        
        return false;
    }
    
    /**
     * Get user's subscriptions
     *
     * @param int $user_id User ID
     * @param string $status Filter by status (optional)
     * @return array Array of subscriptions
     */
    public function get_user_subscriptions($user_id, $status = '') {
        $user_subscriptions = get_user_meta($user_id, 'skylearn_billing_subscriptions', true);
        if (!is_array($user_subscriptions)) {
            return array();
        }
        
        $subscriptions = array();
        foreach ($user_subscriptions as $id => $subscription) {
            if (empty($status) || $subscription['status'] === $status) {
                $subscription['id'] = $id;
                $subscriptions[] = $subscription;
            }
        }
        
        return $subscriptions;
    }
    
    /**
     * Handle subscription action via AJAX
     */
    public function handle_subscription_action() {
        check_ajax_referer('skylearn_subscription_action', 'nonce');
        
        $action = sanitize_text_field($_POST['subscription_action']);
        $subscription_id = sanitize_text_field($_POST['subscription_id']);
        $user_id = get_current_user_id();
        
        // Verify subscription belongs to current user
        $subscription = $this->get_subscription($subscription_id, $user_id);
        if (!$subscription) {
            wp_send_json_error(__('Subscription not found.', 'skylearn-billing-pro'));
        }
        
        $result = false;
        $message = '';
        
        switch ($action) {
            case 'pause':
                $result = $this->pause_subscription($subscription_id);
                $message = $result ? __('Subscription paused successfully.', 'skylearn-billing-pro') : __('Failed to pause subscription.', 'skylearn-billing-pro');
                break;
                
            case 'resume':
                $result = $this->resume_subscription($subscription_id);
                $message = $result ? __('Subscription resumed successfully.', 'skylearn-billing-pro') : __('Failed to resume subscription.', 'skylearn-billing-pro');
                break;
                
            case 'cancel':
                $immediate = isset($_POST['immediate']) && $_POST['immediate'] === 'true';
                $result = $this->cancel_subscription($subscription_id, $immediate);
                $message = $result ? __('Subscription cancelled successfully.', 'skylearn-billing-pro') : __('Failed to cancel subscription.', 'skylearn-billing-pro');
                break;
                
            default:
                wp_send_json_error(__('Invalid action.', 'skylearn-billing-pro'));
        }
        
        if ($result) {
            wp_send_json_success($message);
        } else {
            wp_send_json_error($message);
        }
    }
    
    /**
     * Calculate next payment date
     *
     * @param string $start_date Start date
     * @param string $billing_cycle Billing cycle
     * @return string Next payment date
     */
    private function calculate_next_payment_date($start_date, $billing_cycle) {
        $date = new DateTime($start_date);
        
        switch ($billing_cycle) {
            case 'weekly':
                $date->add(new DateInterval('P7D'));
                break;
            case 'monthly':
                $date->add(new DateInterval('P1M'));
                break;
            case 'quarterly':
                $date->add(new DateInterval('P3M'));
                break;
            case 'yearly':
                $date->add(new DateInterval('P1Y'));
                break;
            default:
                $date->add(new DateInterval('P1M'));
        }
        
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * Handle subscription expiry
     *
     * @param string $subscription_id Subscription ID
     */
    public function handle_subscription_expiry($subscription_id) {
        $this->update_subscription($subscription_id, array(
            'status' => self::STATUS_EXPIRED,
            'expired_at' => current_time('mysql')
        ));
        
        do_action('skylearn_billing_subscription_access_revoked', $subscription_id);
    }
    
    /**
     * Handle subscription renewal
     *
     * @param string $subscription_id Subscription ID
     */
    public function handle_subscription_renewal($subscription_id) {
        $subscription = $this->get_subscription($subscription_id);
        if ($subscription) {
            $next_payment_date = $this->calculate_next_payment_date($subscription['next_payment_date'], $subscription['billing_cycle']);
            
            $this->update_subscription($subscription_id, array(
                'status' => self::STATUS_ACTIVE,
                'next_payment_date' => $next_payment_date,
                'renewed_at' => current_time('mysql')
            ));
        }
    }
    
    /**
     * Daily subscription maintenance
     */
    public function daily_subscription_maintenance() {
        // Check for expired subscriptions
        $users = get_users(array('meta_key' => 'skylearn_billing_subscriptions'));
        
        foreach ($users as $user) {
            $user_subscriptions = get_user_meta($user->ID, 'skylearn_billing_subscriptions', true);
            if (!is_array($user_subscriptions)) {
                continue;
            }
            
            foreach ($user_subscriptions as $id => $subscription) {
                if ($subscription['status'] === self::STATUS_ACTIVE && !empty($subscription['next_payment_date'])) {
                    $next_payment = new DateTime($subscription['next_payment_date']);
                    $now = new DateTime();
                    
                    if ($now > $next_payment) {
                        // Subscription is overdue
                        do_action('skylearn_billing_subscription_overdue', $id);
                        
                        // If grace period exceeded, expire subscription
                        $grace_period = apply_filters('skylearn_billing_subscription_grace_period', 7); // 7 days default
                        $grace_end = clone $next_payment;
                        $grace_end->add(new DateInterval('P' . $grace_period . 'D'));
                        
                        if ($now > $grace_end) {
                            $this->handle_subscription_expiry($id);
                        }
                    }
                }
            }
        }
    }
}

/**
 * Get the subscription manager instance
 *
 * @return SkyLearn_Billing_Pro_Subscription_Manager
 */
function skylearn_billing_pro_subscription_manager() {
    return SkyLearn_Billing_Pro_Subscription_Manager::instance();
}