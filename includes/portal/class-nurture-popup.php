<?php
/**
 * Nurture Popup for Skylearn Billing Pro
 *
 * Handles nurture modal flows for cancel/upgrade/renew actions
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
 * Nurture Popup class
 */
class SkyLearn_Billing_Pro_Nurture_Popup {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Nurture_Popup
     */
    private static $_instance = null;
    
    /**
     * Popup types
     */
    const TYPE_CANCEL = 'cancel';
    const TYPE_UPGRADE = 'upgrade';
    const TYPE_DOWNGRADE = 'downgrade';
    const TYPE_RENEW = 'renew';
    const TYPE_PAUSE = 'pause';
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Nurture_Popup
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
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_skylearn_get_nurture_popup', array($this, 'get_nurture_popup'));
        add_action('wp_ajax_nopriv_skylearn_get_nurture_popup', array($this, 'get_nurture_popup'));
        add_action('wp_ajax_skylearn_track_popup_action', array($this, 'track_popup_action'));
        add_action('wp_ajax_nopriv_skylearn_track_popup_action', array($this, 'track_popup_action'));
        add_action('wp_footer', array($this, 'add_popup_html'));
    }
    
    /**
     * Initialize nurture popup
     */
    public function init() {
        // Initialize default popup configurations
        $this->initialize_default_popups();
        
        do_action('skylearn_billing_nurture_popup_init');
    }
    
    /**
     * Initialize default popup configurations
     */
    private function initialize_default_popups() {
        $default_popups = array(
            self::TYPE_CANCEL => array(
                'title' => __('Wait! Before you cancel...', 'skylearn-billing-pro'),
                'subtitle' => __('We hate to see you go. Let us help you find the right solution.', 'skylearn-billing-pro'),
                'offers' => array(
                    array(
                        'type' => 'pause',
                        'title' => __('Pause for 30 days', 'skylearn-billing-pro'),
                        'description' => __('Take a break and resume when you\'re ready. No charges during pause.', 'skylearn-billing-pro'),
                        'button_text' => __('Pause Subscription', 'skylearn-billing-pro'),
                        'icon' => 'pause'
                    ),
                    array(
                        'type' => 'discount',
                        'title' => __('50% off next 3 months', 'skylearn-billing-pro'),
                        'description' => __('Stay with us and save 50% on your subscription for the next 3 months.', 'skylearn-billing-pro'),
                        'button_text' => __('Apply Discount', 'skylearn-billing-pro'),
                        'icon' => 'discount'
                    ),
                    array(
                        'type' => 'downgrade',
                        'title' => __('Switch to Basic plan', 'skylearn-billing-pro'),
                        'description' => __('Downgrade to our Basic plan and keep access to essential features.', 'skylearn-billing-pro'),
                        'button_text' => __('Downgrade', 'skylearn-billing-pro'),
                        'icon' => 'downgrade'
                    )
                ),
                'cancel_text' => __('No thanks, cancel anyway', 'skylearn-billing-pro'),
                'active' => true
            ),
            self::TYPE_UPGRADE => array(
                'title' => __('Unlock your full potential', 'skylearn-billing-pro'),
                'subtitle' => __('Upgrade to access premium features and accelerate your learning.', 'skylearn-billing-pro'),
                'offers' => array(
                    array(
                        'type' => 'trial',
                        'title' => __('Free 7-day trial', 'skylearn-billing-pro'),
                        'description' => __('Try Premium features for free. Cancel anytime during trial.', 'skylearn-billing-pro'),
                        'button_text' => __('Start Free Trial', 'skylearn-billing-pro'),
                        'icon' => 'trial'
                    ),
                    array(
                        'type' => 'discount',
                        'title' => __('20% off first year', 'skylearn-billing-pro'),
                        'description' => __('Upgrade now and save 20% on your first year of Premium access.', 'skylearn-billing-pro'),
                        'button_text' => __('Upgrade with Discount', 'skylearn-billing-pro'),
                        'icon' => 'discount'
                    )
                ),
                'cancel_text' => __('Maybe later', 'skylearn-billing-pro'),
                'active' => true
            ),
            self::TYPE_DOWNGRADE => array(
                'title' => __('Consider your options', 'skylearn-billing-pro'),
                'subtitle' => __('Before you downgrade, see what you\'ll be missing out on.', 'skylearn-billing-pro'),
                'offers' => array(
                    array(
                        'type' => 'keep_current',
                        'title' => __('Stay at current level', 'skylearn-billing-pro'),
                        'description' => __('Keep all your premium features and continue your progress.', 'skylearn-billing-pro'),
                        'button_text' => __('Keep Current Plan', 'skylearn-billing-pro'),
                        'icon' => 'stay'
                    ),
                    array(
                        'type' => 'pause',
                        'title' => __('Pause instead', 'skylearn-billing-pro'),
                        'description' => __('Take a break and resume at current level when you\'re ready.', 'skylearn-billing-pro'),
                        'button_text' => __('Pause Subscription', 'skylearn-billing-pro'),
                        'icon' => 'pause'
                    )
                ),
                'cancel_text' => __('Proceed with downgrade', 'skylearn-billing-pro'),
                'active' => true
            ),
            self::TYPE_RENEW => array(
                'title' => __('Your subscription expires soon', 'skylearn-billing-pro'),
                'subtitle' => __('Don\'t lose access to your courses and progress. Renew today!', 'skylearn-billing-pro'),
                'offers' => array(
                    array(
                        'type' => 'auto_renew',
                        'title' => __('Enable auto-renewal', 'skylearn-billing-pro'),
                        'description' => __('Never worry about expiration again. Cancel anytime.', 'skylearn-billing-pro'),
                        'button_text' => __('Enable Auto-Renew', 'skylearn-billing-pro'),
                        'icon' => 'auto-renew'
                    ),
                    array(
                        'type' => 'manual_renew',
                        'title' => __('Renew now', 'skylearn-billing-pro'),
                        'description' => __('Continue your learning journey without interruption.', 'skylearn-billing-pro'),
                        'button_text' => __('Renew Subscription', 'skylearn-billing-pro'),
                        'icon' => 'renew'
                    )
                ),
                'cancel_text' => __('Let it expire', 'skylearn-billing-pro'),
                'active' => true
            ),
            self::TYPE_PAUSE => array(
                'title' => __('Pause your subscription', 'skylearn-billing-pro'),
                'subtitle' => __('Take a break from learning. Your progress will be saved.', 'skylearn-billing-pro'),
                'offers' => array(
                    array(
                        'type' => 'pause_30',
                        'title' => __('Pause for 30 days', 'skylearn-billing-pro'),
                        'description' => __('Short break to recharge. Resume automatically in 30 days.', 'skylearn-billing-pro'),
                        'button_text' => __('Pause 30 Days', 'skylearn-billing-pro'),
                        'icon' => 'pause'
                    ),
                    array(
                        'type' => 'pause_90',
                        'title' => __('Pause for 90 days', 'skylearn-billing-pro'),
                        'description' => __('Extended break for busy periods. Resume when ready.', 'skylearn-billing-pro'),
                        'button_text' => __('Pause 90 Days', 'skylearn-billing-pro'),
                        'icon' => 'pause'
                    ),
                    array(
                        'type' => 'pause_indefinite',
                        'title' => __('Pause indefinitely', 'skylearn-billing-pro'),
                        'description' => __('Pause until you manually resume. No automatic renewal.', 'skylearn-billing-pro'),
                        'button_text' => __('Pause Indefinitely', 'skylearn-billing-pro'),
                        'icon' => 'pause'
                    )
                ),
                'cancel_text' => __('Don\'t pause', 'skylearn-billing-pro'),
                'active' => true
            )
        );
        
        $popup_configs = get_option('skylearn_billing_nurture_popups', array());
        if (empty($popup_configs)) {
            update_option('skylearn_billing_nurture_popups', $default_popups);
        }
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on portal pages
        if (!$this->is_portal_page()) {
            return;
        }
        
        wp_enqueue_style(
            'skylearn-nurture-popup',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/nurture-popup.css',
            array(),
            SKYLEARN_BILLING_PRO_VERSION
        );
        
        wp_enqueue_script(
            'skylearn-nurture-popup',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/nurture-popup.js',
            array('jquery'),
            SKYLEARN_BILLING_PRO_VERSION,
            true
        );
        
        wp_localize_script('skylearn-nurture-popup', 'skyleanNurturePopup', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_nurture_popup'),
            'strings' => array(
                'loading' => __('Loading...', 'skylearn-billing-pro'),
                'error' => __('Something went wrong. Please try again.', 'skylearn-billing-pro'),
                'success' => __('Action completed successfully.', 'skylearn-billing-pro')
            )
        ));
    }
    
    /**
     * Check if current page is a portal page
     *
     * @return bool True if portal page
     */
    private function is_portal_page() {
        // This would check if we're on the customer portal
        // For now, we'll check for specific page templates or query parameters
        return is_page('portal') || get_query_var('skylearn_portal') || 
               (is_user_logged_in() && isset($_GET['skylearn_action']));
    }
    
    /**
     * Get nurture popup content via AJAX
     */
    public function get_nurture_popup() {
        check_ajax_referer('skylearn_nurture_popup', 'nonce');
        
        $popup_type = sanitize_text_field($_POST['popup_type']);
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(__('You must be logged in.', 'skylearn-billing-pro'));
        }
        
        $popup_content = $this->get_popup_content($popup_type, $user_id);
        
        if ($popup_content) {
            // Track popup display
            $this->track_popup_display($user_id, $popup_type);
            
            wp_send_json_success($popup_content);
        } else {
            wp_send_json_error(__('Popup not found.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Get popup content
     *
     * @param string $popup_type Popup type
     * @param int $user_id User ID
     * @return array|false Popup content or false if not found
     */
    public function get_popup_content($popup_type, $user_id) {
        $popup_configs = get_option('skylearn_billing_nurture_popups', array());
        
        if (!isset($popup_configs[$popup_type]) || !$popup_configs[$popup_type]['active']) {
            return false;
        }
        
        $config = $popup_configs[$popup_type];
        
        // Personalize content based on user data
        $config = $this->personalize_popup_content($config, $user_id);
        
        return $config;
    }
    
    /**
     * Personalize popup content
     *
     * @param array $config Popup configuration
     * @param int $user_id User ID
     * @return array Personalized configuration
     */
    private function personalize_popup_content($config, $user_id) {
        $user = get_userdata($user_id);
        $user_name = $user ? $user->display_name : '';
        
        // Replace placeholders
        $replacements = array(
            '{{user_name}}' => $user_name,
            '{{first_name}}' => $user ? $user->first_name : '',
        );
        
        // Apply replacements to title and subtitle
        $config['title'] = str_replace(array_keys($replacements), array_values($replacements), $config['title']);
        $config['subtitle'] = str_replace(array_keys($replacements), array_values($replacements), $config['subtitle']);
        
        // Filter offers based on user context
        $config['offers'] = $this->filter_offers_for_user($config['offers'], $user_id);
        
        return apply_filters('skylearn_billing_personalize_popup_content', $config, $user_id);
    }
    
    /**
     * Filter offers based on user context
     *
     * @param array $offers Available offers
     * @param int $user_id User ID
     * @return array Filtered offers
     */
    private function filter_offers_for_user($offers, $user_id) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
        $membership_level = skylearn_billing_pro_membership_manager()->get_user_membership_level($user_id);
        
        $filtered_offers = array();
        
        foreach ($offers as $offer) {
            $show_offer = true;
            
            // Apply business logic to show/hide offers
            switch ($offer['type']) {
                case 'downgrade':
                    // Only show downgrade if user is on premium plan
                    if (!$subscription || $subscription['tier'] === 'basic') {
                        $show_offer = false;
                    }
                    break;
                    
                case 'trial':
                    // Only show trial if user hasn't used trial before
                    $has_used_trial = get_user_meta($user_id, 'skylearn_billing_used_trial', true);
                    if ($has_used_trial) {
                        $show_offer = false;
                    }
                    break;
                    
                case 'pause':
                    // Only show pause if subscription is active
                    if (!$subscription || $subscription['status'] !== 'active') {
                        $show_offer = false;
                    }
                    break;
            }
            
            if ($show_offer) {
                $filtered_offers[] = $offer;
            }
        }
        
        return $filtered_offers;
    }
    
    /**
     * Track popup action via AJAX
     */
    public function track_popup_action() {
        check_ajax_referer('skylearn_nurture_popup', 'nonce');
        
        $user_id = get_current_user_id();
        $popup_type = sanitize_text_field($_POST['popup_type']);
        $action = sanitize_text_field($_POST['action']);
        $offer_type = sanitize_text_field($_POST['offer_type'] ?? '');
        
        if (!$user_id) {
            wp_send_json_error(__('You must be logged in.', 'skylearn-billing-pro'));
        }
        
        // Track the action
        $this->track_popup_interaction($user_id, $popup_type, $action, $offer_type);
        
        // Process the action
        $result = $this->process_popup_action($user_id, $popup_type, $action, $offer_type);
        
        if ($result['success']) {
            wp_send_json_success($result['message']);
        } else {
            wp_send_json_error($result['message']);
        }
    }
    
    /**
     * Process popup action
     *
     * @param int $user_id User ID
     * @param string $popup_type Popup type
     * @param string $action Action taken
     * @param string $offer_type Offer type
     * @return array Result with success status and message
     */
    private function process_popup_action($user_id, $popup_type, $action, $offer_type) {
        switch ($action) {
            case 'accept_offer':
                return $this->process_offer_acceptance($user_id, $offer_type);
                
            case 'dismiss':
                return array(
                    'success' => true,
                    'message' => __('Popup dismissed.', 'skylearn-billing-pro')
                );
                
            case 'proceed_anyway':
                return $this->process_proceed_anyway($user_id, $popup_type);
                
            default:
                return array(
                    'success' => false,
                    'message' => __('Invalid action.', 'skylearn-billing-pro')
                );
        }
    }
    
    /**
     * Process offer acceptance
     *
     * @param int $user_id User ID
     * @param string $offer_type Offer type
     * @return array Result with success status and message
     */
    private function process_offer_acceptance($user_id, $offer_type) {
        switch ($offer_type) {
            case 'pause':
            case 'pause_30':
            case 'pause_90':
            case 'pause_indefinite':
                return $this->process_pause_offer($user_id, $offer_type);
                
            case 'discount':
                return $this->process_discount_offer($user_id);
                
            case 'downgrade':
                return $this->process_downgrade_offer($user_id);
                
            case 'trial':
                return $this->process_trial_offer($user_id);
                
            case 'auto_renew':
                return $this->process_auto_renew_offer($user_id);
                
            case 'manual_renew':
                return $this->process_manual_renew_offer($user_id);
                
            default:
                return array(
                    'success' => false,
                    'message' => __('Unknown offer type.', 'skylearn-billing-pro')
                );
        }
    }
    
    /**
     * Process pause offer
     *
     * @param int $user_id User ID
     * @param string $offer_type Offer type
     * @return array Result
     */
    private function process_pause_offer($user_id, $offer_type) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
        
        if (!$subscription) {
            return array(
                'success' => false,
                'message' => __('No active subscription found.', 'skylearn-billing-pro')
            );
        }
        
        $result = skylearn_billing_pro_subscription_manager()->pause_subscription($subscription['id']);
        
        if ($result) {
            // Set resume date based on offer type
            $resume_date = null;
            switch ($offer_type) {
                case 'pause_30':
                    $resume_date = date('Y-m-d H:i:s', strtotime('+30 days'));
                    break;
                case 'pause_90':
                    $resume_date = date('Y-m-d H:i:s', strtotime('+90 days'));
                    break;
            }
            
            if ($resume_date) {
                update_user_meta($user_id, 'skylearn_billing_auto_resume_date', $resume_date);
            }
            
            return array(
                'success' => true,
                'message' => __('Your subscription has been paused.', 'skylearn-billing-pro')
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to pause subscription.', 'skylearn-billing-pro')
            );
        }
    }
    
    /**
     * Process discount offer
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function process_discount_offer($user_id) {
        // Create discount code for user
        $discount_code = 'RETAIN' . strtoupper(wp_generate_password(6, false));
        $expiry_date = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $discount_data = array(
            'code' => $discount_code,
            'type' => 'percentage',
            'value' => 50, // 50% discount
            'user_id' => $user_id,
            'usage_limit' => 1,
            'expiry_date' => $expiry_date,
            'created_at' => current_time('mysql')
        );
        
        $user_discounts = get_user_meta($user_id, 'skylearn_billing_discount_codes', true);
        if (!is_array($user_discounts)) {
            $user_discounts = array();
        }
        
        $user_discounts[] = $discount_data;
        update_user_meta($user_id, 'skylearn_billing_discount_codes', $user_discounts);
        
        return array(
            'success' => true,
            'message' => sprintf(__('Discount code %s has been applied to your account!', 'skylearn-billing-pro'), $discount_code)
        );
    }
    
    /**
     * Process downgrade offer
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function process_downgrade_offer($user_id) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
        
        if (!$subscription) {
            return array(
                'success' => false,
                'message' => __('No active subscription found.', 'skylearn-billing-pro')
            );
        }
        
        // Schedule downgrade at next billing cycle
        update_user_meta($user_id, 'skylearn_billing_scheduled_downgrade', array(
            'new_tier' => 'basic',
            'scheduled_at' => current_time('mysql'),
            'effective_date' => $subscription['next_payment_date']
        ));
        
        return array(
            'success' => true,
            'message' => __('Your subscription will be downgraded at the next billing cycle.', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Process trial offer
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function process_trial_offer($user_id) {
        // Mark user as having used trial
        update_user_meta($user_id, 'skylearn_billing_used_trial', true);
        
        // Create trial subscription or upgrade current one
        $trial_end_date = date('Y-m-d H:i:s', strtotime('+7 days'));
        
        update_user_meta($user_id, 'skylearn_billing_trial_end_date', $trial_end_date);
        
        return array(
            'success' => true,
            'message' => __('Your 7-day free trial has started!', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Process auto-renew offer
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function process_auto_renew_offer($user_id) {
        $subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
        
        if (!$subscription) {
            return array(
                'success' => false,
                'message' => __('No active subscription found.', 'skylearn-billing-pro')
            );
        }
        
        skylearn_billing_pro_subscription_manager()->update_subscription($subscription['id'], array(
            'auto_renew' => true
        ));
        
        return array(
            'success' => true,
            'message' => __('Auto-renewal has been enabled for your subscription.', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Process manual renew offer
     *
     * @param int $user_id User ID
     * @return array Result
     */
    private function process_manual_renew_offer($user_id) {
        // This would redirect to payment processing
        return array(
            'success' => true,
            'message' => __('Redirecting to renewal payment...', 'skylearn-billing-pro'),
            'redirect' => home_url('/portal/renew/')
        );
    }
    
    /**
     * Process proceed anyway action
     *
     * @param int $user_id User ID
     * @param string $popup_type Popup type
     * @return array Result
     */
    private function process_proceed_anyway($user_id, $popup_type) {
        switch ($popup_type) {
            case self::TYPE_CANCEL:
                $subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
                if ($subscription) {
                    $result = skylearn_billing_pro_subscription_manager()->cancel_subscription($subscription['id'], true);
                    if ($result) {
                        return array(
                            'success' => true,
                            'message' => __('Your subscription has been cancelled.', 'skylearn-billing-pro')
                        );
                    }
                }
                break;
                
            default:
                return array(
                    'success' => true,
                    'message' => __('Action completed.', 'skylearn-billing-pro')
                );
        }
        
        return array(
            'success' => false,
            'message' => __('Failed to complete action.', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Track popup display
     *
     * @param int $user_id User ID
     * @param string $popup_type Popup type
     */
    private function track_popup_display($user_id, $popup_type) {
        $analytics = get_option('skylearn_billing_popup_analytics', array());
        
        $date_key = date('Y-m-d');
        if (!isset($analytics[$date_key])) {
            $analytics[$date_key] = array();
        }
        
        if (!isset($analytics[$date_key][$popup_type])) {
            $analytics[$date_key][$popup_type] = array(
                'displays' => 0,
                'interactions' => array()
            );
        }
        
        $analytics[$date_key][$popup_type]['displays']++;
        
        update_option('skylearn_billing_popup_analytics', $analytics);
    }
    
    /**
     * Track popup interaction
     *
     * @param int $user_id User ID
     * @param string $popup_type Popup type
     * @param string $action Action taken
     * @param string $offer_type Offer type
     */
    private function track_popup_interaction($user_id, $popup_type, $action, $offer_type) {
        $analytics = get_option('skylearn_billing_popup_analytics', array());
        
        $date_key = date('Y-m-d');
        if (!isset($analytics[$date_key])) {
            $analytics[$date_key] = array();
        }
        
        if (!isset($analytics[$date_key][$popup_type])) {
            $analytics[$date_key][$popup_type] = array(
                'displays' => 0,
                'interactions' => array()
            );
        }
        
        $interaction_key = $action . ($offer_type ? '_' . $offer_type : '');
        if (!isset($analytics[$date_key][$popup_type]['interactions'][$interaction_key])) {
            $analytics[$date_key][$popup_type]['interactions'][$interaction_key] = 0;
        }
        
        $analytics[$date_key][$popup_type]['interactions'][$interaction_key]++;
        
        update_option('skylearn_billing_popup_analytics', $analytics);
        
        // Track user-specific interactions
        $user_interactions = get_user_meta($user_id, 'skylearn_billing_popup_interactions', true);
        if (!is_array($user_interactions)) {
            $user_interactions = array();
        }
        
        $user_interactions[] = array(
            'popup_type' => $popup_type,
            'action' => $action,
            'offer_type' => $offer_type,
            'timestamp' => current_time('mysql')
        );
        
        update_user_meta($user_id, 'skylearn_billing_popup_interactions', $user_interactions);
    }
    
    /**
     * Add popup HTML to footer
     */
    public function add_popup_html() {
        if (!$this->is_portal_page() || !is_user_logged_in()) {
            return;
        }
        
        ?>
        <div id="skylearn-nurture-popup-overlay" class="skylearn-popup-overlay" style="display: none;">
            <div class="skylearn-popup-container">
                <div class="skylearn-popup-header">
                    <button class="skylearn-popup-close" type="button">&times;</button>
                </div>
                <div class="skylearn-popup-content">
                    <!-- Content will be loaded via AJAX -->
                </div>
            </div>
        </div>
        <?php
    }
}

/**
 * Get the nurture popup instance
 *
 * @return SkyLearn_Billing_Pro_Nurture_Popup
 */
function skylearn_billing_pro_nurture_popup() {
    return SkyLearn_Billing_Pro_Nurture_Popup::instance();
}