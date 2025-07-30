<?php
/**
 * Loyalty System for Skylearn Billing Pro
 *
 * Handles rewards and loyalty features for subscribers
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
 * Loyalty class
 */
class SkyLearn_Billing_Pro_Loyalty {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Loyalty
     */
    private static $_instance = null;
    
    /**
     * Point types
     */
    const POINT_TYPE_PURCHASE = 'purchase';
    const POINT_TYPE_REFERRAL = 'referral';
    const POINT_TYPE_MILESTONE = 'milestone';
    const POINT_TYPE_BONUS = 'bonus';
    const POINT_TYPE_REDEMPTION = 'redemption';
    
    /**
     * Reward types
     */
    const REWARD_TYPE_DISCOUNT = 'discount';
    const REWARD_TYPE_COURSE = 'course';
    const REWARD_TYPE_PRODUCT = 'product';
    const REWARD_TYPE_EXTENSION = 'extension';
    const REWARD_TYPE_UPGRADE = 'upgrade';
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Loyalty
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
        add_action('skylearn_billing_subscription_created', array($this, 'award_subscription_points'), 10, 2);
        add_action('skylearn_billing_subscription_renewed', array($this, 'award_renewal_points'), 10, 1);
        add_action('skylearn_billing_subscription_upgraded', array($this, 'award_upgrade_points'), 10, 3);
        add_action('wp_ajax_skylearn_redeem_reward', array($this, 'handle_reward_redemption'));
        add_action('wp_ajax_nopriv_skylearn_redeem_reward', array($this, 'handle_reward_redemption'));
        
        // Check for milestones daily
        add_action('skylearn_billing_pro_daily_tasks', array($this, 'check_user_milestones'));
    }
    
    /**
     * Initialize loyalty system
     */
    public function init() {
        // Initialize default rewards
        $this->initialize_default_rewards();
        
        do_action('skylearn_billing_loyalty_init');
    }
    
    /**
     * Initialize default rewards
     */
    private function initialize_default_rewards() {
        $default_rewards = array(
            'discount_10_percent' => array(
                'name' => __('10% Discount', 'skylearn-billing-pro'),
                'description' => __('Get 10% off your next purchase', 'skylearn-billing-pro'),
                'type' => self::REWARD_TYPE_DISCOUNT,
                'cost' => 100,
                'value' => 10,
                'active' => true,
                'conditions' => array()
            ),
            'discount_25_percent' => array(
                'name' => __('25% Discount', 'skylearn-billing-pro'),
                'description' => __('Get 25% off your next purchase', 'skylearn-billing-pro'),
                'type' => self::REWARD_TYPE_DISCOUNT,
                'cost' => 250,
                'value' => 25,
                'active' => true,
                'conditions' => array()
            ),
            'free_month' => array(
                'name' => __('Free Month', 'skylearn-billing-pro'),
                'description' => __('Extend your subscription by one month for free', 'skylearn-billing-pro'),
                'type' => self::REWARD_TYPE_EXTENSION,
                'cost' => 500,
                'value' => 30, // 30 days
                'active' => true,
                'conditions' => array('active_subscription' => true)
            ),
            'tier_upgrade' => array(
                'name' => __('Tier Upgrade', 'skylearn-billing-pro'),
                'description' => __('Upgrade to the next tier for one month', 'skylearn-billing-pro'),
                'type' => self::REWARD_TYPE_UPGRADE,
                'cost' => 750,
                'value' => 1, // 1 tier level
                'active' => true,
                'conditions' => array('active_subscription' => true)
            )
        );
        
        $rewards = get_option('skylearn_billing_loyalty_rewards', array());
        if (empty($rewards)) {
            update_option('skylearn_billing_loyalty_rewards', $default_rewards);
        }
        
        // Initialize point earning rules
        $default_earning_rules = array(
            'subscription_purchase' => array(
                'name' => __('Subscription Purchase', 'skylearn-billing-pro'),
                'points_per_dollar' => 1,
                'bonus_points' => 50,
                'active' => true
            ),
            'subscription_renewal' => array(
                'name' => __('Subscription Renewal', 'skylearn-billing-pro'),
                'points_per_dollar' => 1,
                'bonus_points' => 25,
                'active' => true
            ),
            'referral' => array(
                'name' => __('Referral Bonus', 'skylearn-billing-pro'),
                'points_per_referral' => 200,
                'active' => true
            ),
            'milestone_1_year' => array(
                'name' => __('1 Year Milestone', 'skylearn-billing-pro'),
                'points' => 500,
                'active' => true
            ),
            'milestone_course_completion' => array(
                'name' => __('Course Completion', 'skylearn-billing-pro'),
                'points_per_course' => 50,
                'active' => true
            )
        );
        
        $earning_rules = get_option('skylearn_billing_loyalty_earning_rules', array());
        if (empty($earning_rules)) {
            update_option('skylearn_billing_loyalty_earning_rules', $default_earning_rules);
        }
    }
    
    /**
     * Award points to user
     *
     * @param int $user_id User ID
     * @param int $points Points to award
     * @param string $type Point type
     * @param string $description Description
     * @param array $metadata Additional metadata
     * @return bool True on success, false on failure
     */
    public function award_points($user_id, $points, $type = self::POINT_TYPE_BONUS, $description = '', $metadata = array()) {
        if ($points <= 0) {
            return false;
        }
        
        // Get current points
        $current_points = $this->get_user_points($user_id);
        $new_points = $current_points + $points;
        
        // Update user points
        update_user_meta($user_id, 'skylearn_billing_loyalty_points', $new_points);
        
        // Log the transaction
        $transaction = array(
            'user_id' => $user_id,
            'points' => $points,
            'type' => $type,
            'description' => $description,
            'metadata' => $metadata,
            'timestamp' => current_time('mysql'),
            'running_total' => $new_points
        );
        
        $this->log_points_transaction($user_id, $transaction);
        
        // Check for achievements
        $this->check_point_achievements($user_id, $new_points);
        
        do_action('skylearn_billing_loyalty_points_awarded', $user_id, $points, $type, $description);
        
        return true;
    }
    
    /**
     * Deduct points from user
     *
     * @param int $user_id User ID
     * @param int $points Points to deduct
     * @param string $description Description
     * @param array $metadata Additional metadata
     * @return bool True on success, false on failure
     */
    public function deduct_points($user_id, $points, $description = '', $metadata = array()) {
        if ($points <= 0) {
            return false;
        }
        
        $current_points = $this->get_user_points($user_id);
        if ($current_points < $points) {
            return false; // Insufficient points
        }
        
        $new_points = $current_points - $points;
        update_user_meta($user_id, 'skylearn_billing_loyalty_points', $new_points);
        
        // Log the transaction
        $transaction = array(
            'user_id' => $user_id,
            'points' => -$points,
            'type' => self::POINT_TYPE_REDEMPTION,
            'description' => $description,
            'metadata' => $metadata,
            'timestamp' => current_time('mysql'),
            'running_total' => $new_points
        );
        
        $this->log_points_transaction($user_id, $transaction);
        
        do_action('skylearn_billing_loyalty_points_deducted', $user_id, $points, $description);
        
        return true;
    }
    
    /**
     * Get user points
     *
     * @param int $user_id User ID
     * @return int User points
     */
    public function get_user_points($user_id) {
        return (int) get_user_meta($user_id, 'skylearn_billing_loyalty_points', true);
    }
    
    /**
     * Get user points history
     *
     * @param int $user_id User ID
     * @param int $limit Number of transactions to return
     * @return array Points history
     */
    public function get_user_points_history($user_id, $limit = 50) {
        $history = get_user_meta($user_id, 'skylearn_billing_loyalty_history', true);
        if (!is_array($history)) {
            return array();
        }
        
        // Sort by timestamp descending
        usort($history, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        return array_slice($history, 0, $limit);
    }
    
    /**
     * Log points transaction
     *
     * @param int $user_id User ID
     * @param array $transaction Transaction data
     */
    private function log_points_transaction($user_id, $transaction) {
        $history = get_user_meta($user_id, 'skylearn_billing_loyalty_history', true);
        if (!is_array($history)) {
            $history = array();
        }
        
        $history[] = $transaction;
        
        // Keep only last 200 transactions to prevent excessive data
        if (count($history) > 200) {
            $history = array_slice($history, -200);
        }
        
        update_user_meta($user_id, 'skylearn_billing_loyalty_history', $history);
    }
    
    /**
     * Get available rewards
     *
     * @return array Available rewards
     */
    public function get_available_rewards() {
        $rewards = get_option('skylearn_billing_loyalty_rewards', array());
        
        // Filter active rewards
        return array_filter($rewards, function($reward) {
            return isset($reward['active']) && $reward['active'];
        });
    }
    
    /**
     * Get reward by ID
     *
     * @param string $reward_id Reward ID
     * @return array|false Reward data or false if not found
     */
    public function get_reward($reward_id) {
        $rewards = get_option('skylearn_billing_loyalty_rewards', array());
        return isset($rewards[$reward_id]) ? $rewards[$reward_id] : false;
    }
    
    /**
     * Check if user can redeem reward
     *
     * @param int $user_id User ID
     * @param string $reward_id Reward ID
     * @return bool|string True if can redeem, error message if cannot
     */
    public function can_user_redeem_reward($user_id, $reward_id) {
        $reward = $this->get_reward($reward_id);
        if (!$reward) {
            return __('Reward not found.', 'skylearn-billing-pro');
        }
        
        $user_points = $this->get_user_points($user_id);
        if ($user_points < $reward['cost']) {
            return sprintf(__('Insufficient points. You have %d points, need %d.', 'skylearn-billing-pro'), $user_points, $reward['cost']);
        }
        
        // Check conditions
        if (isset($reward['conditions']) && is_array($reward['conditions'])) {
            foreach ($reward['conditions'] as $condition => $value) {
                switch ($condition) {
                    case 'active_subscription':
                        if ($value && !$this->user_has_active_subscription($user_id)) {
                            return __('Active subscription required.', 'skylearn-billing-pro');
                        }
                        break;
                        
                    case 'membership_level':
                        $user_level = skylearn_billing_pro_membership_manager()->get_user_membership_level($user_id);
                        if (!skylearn_billing_pro_membership_manager()->user_has_membership_level($user_id, $value)) {
                            return sprintf(__('Membership level %s required.', 'skylearn-billing-pro'), $value);
                        }
                        break;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Redeem reward for user
     *
     * @param int $user_id User ID
     * @param string $reward_id Reward ID
     * @return bool|string True on success, error message on failure
     */
    public function redeem_reward($user_id, $reward_id) {
        $can_redeem = $this->can_user_redeem_reward($user_id, $reward_id);
        if ($can_redeem !== true) {
            return $can_redeem;
        }
        
        $reward = $this->get_reward($reward_id);
        if (!$reward) {
            return __('Reward not found.', 'skylearn-billing-pro');
        }
        
        // Deduct points
        $deducted = $this->deduct_points($user_id, $reward['cost'], sprintf(__('Redeemed: %s', 'skylearn-billing-pro'), $reward['name']), array(
            'reward_id' => $reward_id,
            'reward_type' => $reward['type']
        ));
        
        if (!$deducted) {
            return __('Failed to deduct points.', 'skylearn-billing-pro');
        }
        
        // Apply reward
        $applied = $this->apply_reward($user_id, $reward_id, $reward);
        if (!$applied) {
            // Refund points if reward application failed
            $this->award_points($user_id, $reward['cost'], self::POINT_TYPE_BONUS, sprintf(__('Refund for failed redemption: %s', 'skylearn-billing-pro'), $reward['name']));
            return __('Failed to apply reward.', 'skylearn-billing-pro');
        }
        
        // Log redemption
        $this->log_reward_redemption($user_id, $reward_id, $reward);
        
        do_action('skylearn_billing_loyalty_reward_redeemed', $user_id, $reward_id, $reward);
        
        return true;
    }
    
    /**
     * Apply reward to user
     *
     * @param int $user_id User ID
     * @param string $reward_id Reward ID
     * @param array $reward Reward data
     * @return bool True on success, false on failure
     */
    private function apply_reward($user_id, $reward_id, $reward) {
        switch ($reward['type']) {
            case self::REWARD_TYPE_DISCOUNT:
                return $this->apply_discount_reward($user_id, $reward);
                
            case self::REWARD_TYPE_EXTENSION:
                return $this->apply_extension_reward($user_id, $reward);
                
            case self::REWARD_TYPE_UPGRADE:
                return $this->apply_upgrade_reward($user_id, $reward);
                
            case self::REWARD_TYPE_COURSE:
                return $this->apply_course_reward($user_id, $reward);
                
            default:
                return apply_filters('skylearn_billing_loyalty_apply_reward', false, $user_id, $reward_id, $reward);
        }
    }
    
    /**
     * Apply discount reward
     *
     * @param int $user_id User ID
     * @param array $reward Reward data
     * @return bool True on success, false on failure
     */
    private function apply_discount_reward($user_id, $reward) {
        // Create discount code
        $discount_code = 'LOYALTY' . strtoupper(wp_generate_password(8, false));
        $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $discount_data = array(
            'code' => $discount_code,
            'type' => 'percentage',
            'value' => $reward['value'],
            'user_id' => $user_id,
            'usage_limit' => 1,
            'expiry_date' => $expiry_date,
            'created_at' => current_time('mysql')
        );
        
        // Store discount code
        $user_discounts = get_user_meta($user_id, 'skylearn_billing_discount_codes', true);
        if (!is_array($user_discounts)) {
            $user_discounts = array();
        }
        
        $user_discounts[] = $discount_data;
        update_user_meta($user_id, 'skylearn_billing_discount_codes', $user_discounts);
        
        return true;
    }
    
    /**
     * Apply extension reward
     *
     * @param int $user_id User ID
     * @param array $reward Reward data
     * @return bool True on success, false on failure
     */
    private function apply_extension_reward($user_id, $reward) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
        if (!$subscription) {
            return false;
        }
        
        // Extend next payment date
        $current_next_payment = new DateTime($subscription['next_payment_date']);
        $current_next_payment->add(new DateInterval('P' . $reward['value'] . 'D'));
        
        return skylearn_billing_pro_subscription_manager()->update_subscription($subscription['id'], array(
            'next_payment_date' => $current_next_payment->format('Y-m-d H:i:s')
        ));
    }
    
    /**
     * Apply upgrade reward
     *
     * @param int $user_id User ID
     * @param array $reward Reward data
     * @return bool True on success, false on failure
     */
    private function apply_upgrade_reward($user_id, $reward) {
        // Store temporary upgrade that expires in 30 days
        $upgrade_data = array(
            'tier_boost' => $reward['value'],
            'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
            'applied_at' => current_time('mysql')
        );
        
        update_user_meta($user_id, 'skylearn_billing_loyalty_tier_boost', $upgrade_data);
        
        return true;
    }
    
    /**
     * Apply course reward
     *
     * @param int $user_id User ID
     * @param array $reward Reward data
     * @return bool True on success, false on failure
     */
    private function apply_course_reward($user_id, $reward) {
        // This would integrate with the LMS to enroll user in free course
        if (isset($reward['course_id'])) {
            do_action('skylearn_billing_enroll_user_in_course', $user_id, $reward['course_id'], 'loyalty_reward');
            return true;
        }
        
        return false;
    }
    
    /**
     * Log reward redemption
     *
     * @param int $user_id User ID
     * @param string $reward_id Reward ID
     * @param array $reward Reward data
     */
    private function log_reward_redemption($user_id, $reward_id, $reward) {
        $redemptions = get_user_meta($user_id, 'skylearn_billing_loyalty_redemptions', true);
        if (!is_array($redemptions)) {
            $redemptions = array();
        }
        
        $redemptions[] = array(
            'reward_id' => $reward_id,
            'reward_name' => $reward['name'],
            'cost' => $reward['cost'],
            'redeemed_at' => current_time('mysql')
        );
        
        update_user_meta($user_id, 'skylearn_billing_loyalty_redemptions', $redemptions);
    }
    
    /**
     * Award subscription points
     *
     * @param string $subscription_id Subscription ID
     * @param array $subscription_data Subscription data
     */
    public function award_subscription_points($subscription_id, $subscription_data) {
        $earning_rules = get_option('skylearn_billing_loyalty_earning_rules', array());
        $rule = isset($earning_rules['subscription_purchase']) ? $earning_rules['subscription_purchase'] : null;
        
        if ($rule && $rule['active']) {
            $points = 0;
            
            // Points per dollar spent
            if (isset($rule['points_per_dollar'])) {
                $points += (int) ($subscription_data['amount'] * $rule['points_per_dollar']);
            }
            
            // Bonus points
            if (isset($rule['bonus_points'])) {
                $points += $rule['bonus_points'];
            }
            
            if ($points > 0) {
                $this->award_points(
                    $subscription_data['user_id'],
                    $points,
                    self::POINT_TYPE_PURCHASE,
                    sprintf(__('Subscription purchase: %s', 'skylearn-billing-pro'), $subscription_data['plan_id']),
                    array('subscription_id' => $subscription_id)
                );
            }
        }
    }
    
    /**
     * Award renewal points
     *
     * @param string $subscription_id Subscription ID
     */
    public function award_renewal_points($subscription_id) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        $earning_rules = get_option('skylearn_billing_loyalty_earning_rules', array());
        $rule = isset($earning_rules['subscription_renewal']) ? $earning_rules['subscription_renewal'] : null;
        
        if ($rule && $rule['active']) {
            $points = 0;
            
            // Points per dollar spent
            if (isset($rule['points_per_dollar'])) {
                $points += (int) ($subscription['amount'] * $rule['points_per_dollar']);
            }
            
            // Bonus points
            if (isset($rule['bonus_points'])) {
                $points += $rule['bonus_points'];
            }
            
            if ($points > 0) {
                $this->award_points(
                    $subscription['user_id'],
                    $points,
                    self::POINT_TYPE_PURCHASE,
                    __('Subscription renewal', 'skylearn-billing-pro'),
                    array('subscription_id' => $subscription_id)
                );
            }
        }
    }
    
    /**
     * Award upgrade points
     *
     * @param string $subscription_id Subscription ID
     * @param string $new_plan_id New plan ID
     * @param string $new_tier New tier
     */
    public function award_upgrade_points($subscription_id, $new_plan_id, $new_tier) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_subscription($subscription_id);
        if (!$subscription) {
            return;
        }
        
        // Award bonus points for upgrading
        $this->award_points(
            $subscription['user_id'],
            100, // Fixed bonus for upgrades
            self::POINT_TYPE_BONUS,
            sprintf(__('Subscription upgrade to %s', 'skylearn-billing-pro'), $new_tier),
            array('subscription_id' => $subscription_id, 'new_tier' => $new_tier)
        );
    }
    
    /**
     * Handle reward redemption via AJAX
     */
    public function handle_reward_redemption() {
        check_ajax_referer('skylearn_redeem_reward', 'nonce');
        
        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(__('You must be logged in.', 'skylearn-billing-pro'));
        }
        
        $reward_id = sanitize_text_field($_POST['reward_id']);
        $result = $this->redeem_reward($user_id, $reward_id);
        
        if ($result === true) {
            wp_send_json_success(__('Reward redeemed successfully!', 'skylearn-billing-pro'));
        } else {
            wp_send_json_error($result);
        }
    }
    
    /**
     * Check if user has active subscription
     *
     * @param int $user_id User ID
     * @return bool True if user has active subscription
     */
    private function user_has_active_subscription($user_id) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
        return $subscription && $subscription['status'] === 'active';
    }
    
    /**
     * Check point achievements
     *
     * @param int $user_id User ID
     * @param int $points Current points
     */
    private function check_point_achievements($user_id, $points) {
        $achievements = array(
            100 => __('First 100 Points!', 'skylearn-billing-pro'),
            500 => __('High Earner - 500 Points!', 'skylearn-billing-pro'),
            1000 => __('Point Master - 1000 Points!', 'skylearn-billing-pro'),
            2500 => __('Loyalty Champion - 2500 Points!', 'skylearn-billing-pro')
        );
        
        $user_achievements = get_user_meta($user_id, 'skylearn_billing_loyalty_achievements', true);
        if (!is_array($user_achievements)) {
            $user_achievements = array();
        }
        
        foreach ($achievements as $threshold => $title) {
            if ($points >= $threshold && !in_array($threshold, $user_achievements)) {
                $user_achievements[] = $threshold;
                
                // Award bonus points for achievement
                $this->award_points($user_id, 50, self::POINT_TYPE_MILESTONE, $title);
                
                do_action('skylearn_billing_loyalty_achievement_unlocked', $user_id, $threshold, $title);
            }
        }
        
        update_user_meta($user_id, 'skylearn_billing_loyalty_achievements', $user_achievements);
    }
    
    /**
     * Check user milestones
     */
    public function check_user_milestones() {
        // Check for 1-year subscription milestone
        $users = get_users(array('meta_key' => 'skylearn_billing_subscriptions'));
        
        foreach ($users as $user) {
            $subscriptions = skylearn_billing_pro_subscription_manager()->get_user_subscriptions($user->ID, 'active');
            
            foreach ($subscriptions as $subscription) {
                $start_date = new DateTime($subscription['start_date']);
                $now = new DateTime();
                $diff = $now->diff($start_date);
                
                if ($diff->y >= 1) {
                    // Check if already awarded
                    $awarded_milestones = get_user_meta($user->ID, 'skylearn_billing_loyalty_milestones', true);
                    if (!is_array($awarded_milestones)) {
                        $awarded_milestones = array();
                    }
                    
                    $milestone_key = 'one_year_' . $subscription['id'];
                    if (!in_array($milestone_key, $awarded_milestones)) {
                        $this->award_points(
                            $user->ID,
                            500,
                            self::POINT_TYPE_MILESTONE,
                            __('1 Year Subscription Milestone', 'skylearn-billing-pro')
                        );
                        
                        $awarded_milestones[] = $milestone_key;
                        update_user_meta($user->ID, 'skylearn_billing_loyalty_milestones', $awarded_milestones);
                    }
                }
            }
        }
    }
}

/**
 * Get the loyalty system instance
 *
 * @return SkyLearn_Billing_Pro_Loyalty
 */
function skylearn_billing_pro_loyalty() {
    return SkyLearn_Billing_Pro_Loyalty::instance();
}