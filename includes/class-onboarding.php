<?php
/**
 * Onboarding wizard and contextual help system for Skylearn Billing Pro
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
 * Onboarding wizard class for initial setup and contextual help
 */
class SkyLearn_Billing_Pro_Onboarding {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Onboarding
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Onboarding
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
        add_action('admin_init', array($this, 'init_onboarding'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_skylearn_onboarding_step', array($this, 'handle_onboarding_step'));
        add_action('wp_ajax_skylearn_skip_onboarding', array($this, 'skip_onboarding'));
        add_action('wp_ajax_skylearn_dismiss_help', array($this, 'dismiss_help_tooltip'));
        add_action('admin_notices', array($this, 'show_onboarding_notice'));
    }
    
    /**
     * Initialize onboarding process
     */
    public function init_onboarding() {
        // Check if onboarding is needed
        if ($this->should_show_onboarding()) {
            // Redirect to onboarding if accessing plugin for first time
            if (isset($_GET['page']) && $_GET['page'] === 'skylearn-billing-pro' && !isset($_GET['onboarding'])) {
                wp_redirect(admin_url('admin.php?page=skylearn-billing-pro&onboarding=1'));
                exit;
            }
        }
    }
    
    /**
     * Check if onboarding should be shown
     */
    public function should_show_onboarding() {
        $options = get_option('skylearn_billing_pro_options', array());
        return empty($options['onboarding_completed']);
    }
    
    /**
     * Get onboarding steps
     */
    public function get_onboarding_steps() {
        return array(
            'welcome' => array(
                'title' => __('Welcome to Skylearn Billing Pro', 'skylearn-billing-pro'),
                'description' => __('Let\'s get you set up with the ultimate billing solution for your WordPress courses.', 'skylearn-billing-pro'),
                'icon' => 'dashicons-welcome-learn-more'
            ),
            'license' => array(
                'title' => __('License Activation', 'skylearn-billing-pro'),
                'description' => __('Activate your license to unlock all Pro features and receive updates.', 'skylearn-billing-pro'),
                'icon' => 'dashicons-admin-network'
            ),
            'lms' => array(
                'title' => __('LMS Integration', 'skylearn-billing-pro'),
                'description' => __('Connect your Learning Management System for automatic course enrollment.', 'skylearn-billing-pro'),
                'icon' => 'dashicons-welcome-learn-more'
            ),
            'payment' => array(
                'title' => __('Payment Gateways', 'skylearn-billing-pro'),
                'description' => __('Configure your payment processors to start accepting payments.', 'skylearn-billing-pro'),
                'icon' => 'dashicons-credit-card'
            ),
            'products' => array(
                'title' => __('Create Your First Product', 'skylearn-billing-pro'),
                'description' => __('Set up your first course or digital product for sale.', 'skylearn-billing-pro'),
                'icon' => 'dashicons-products'
            ),
            'complete' => array(
                'title' => __('Setup Complete!', 'skylearn-billing-pro'),
                'description' => __('Your billing system is ready. Start selling your courses today!', 'skylearn-billing-pro'),
                'icon' => 'dashicons-yes-alt'
            )
        );
    }
    
    /**
     * Get current onboarding step
     */
    public function get_current_step() {
        $options = get_option('skylearn_billing_pro_options', array());
        return isset($options['onboarding_step']) ? $options['onboarding_step'] : 'welcome';
    }
    
    /**
     * Update onboarding step
     */
    public function update_onboarding_step($step) {
        $options = get_option('skylearn_billing_pro_options', array());
        $options['onboarding_step'] = $step;
        update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Complete onboarding
     */
    public function complete_onboarding() {
        $options = get_option('skylearn_billing_pro_options', array());
        $options['onboarding_completed'] = true;
        $options['onboarding_completed_at'] = current_time('mysql');
        unset($options['onboarding_step']);
        update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Enqueue onboarding scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'skylearn-billing-pro') === false) {
            return;
        }
        
        wp_enqueue_style(
            'skylearn-onboarding',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/onboarding.css',
            array(),
            SKYLEARN_BILLING_PRO_VERSION
        );
        
        wp_enqueue_script(
            'skylearn-onboarding',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/onboarding.js',
            array('jquery'),
            SKYLEARN_BILLING_PRO_VERSION,
            true
        );
        
        // Localize script with data
        wp_localize_script('skylearn-onboarding', 'skylernOnboarding', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_onboarding_nonce'),
            'strings' => array(
                'processing' => __('Processing...', 'skylearn-billing-pro'),
                'error' => __('An error occurred. Please try again.', 'skylearn-billing-pro'),
                'success' => __('Success!', 'skylearn-billing-pro'),
                'skip_confirm' => __('Are you sure you want to skip the setup wizard?', 'skylearn-billing-pro')
            ),
            'currentStep' => $this->get_current_step(),
            'steps' => array_keys($this->get_onboarding_steps())
        ));
    }
    
    /**
     * Handle AJAX onboarding step processing
     */
    public function handle_onboarding_step() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_onboarding_nonce')) {
            wp_die(__('Security check failed.', 'skylearn-billing-pro'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $step = sanitize_text_field($_POST['step']);
        $data = isset($_POST['data']) ? $_POST['data'] : array();
        
        $response = array('success' => false);
        
        switch ($step) {
            case 'license':
                $response = $this->process_license_step($data);
                break;
                
            case 'lms':
                $response = $this->process_lms_step($data);
                break;
                
            case 'payment':
                $response = $this->process_payment_step($data);
                break;
                
            case 'products':
                $response = $this->process_products_step($data);
                break;
                
            case 'complete':
                $this->complete_onboarding();
                $response = array(
                    'success' => true,
                    'redirect' => admin_url('admin.php?page=skylearn-billing-pro')
                );
                break;
        }
        
        wp_send_json($response);
    }
    
    /**
     * Process license step
     */
    private function process_license_step($data) {
        if (empty($data['license_key'])) {
            return array(
                'success' => false,
                'message' => __('Please enter your license key.', 'skylearn-billing-pro')
            );
        }
        
        // Validate license key
        $license_manager = skylearn_billing_pro_licensing();
        $result = $license_manager->activate_license($data['license_key']);
        
        if ($result['success']) {
            $this->update_onboarding_step('lms');
            return array(
                'success' => true,
                'message' => __('License activated successfully!', 'skylearn-billing-pro'),
                'next_step' => 'lms'
            );
        } else {
            return array(
                'success' => false,
                'message' => $result['message']
            );
        }
    }
    
    /**
     * Process LMS step
     */
    private function process_lms_step($data) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (isset($data['active_lms'])) {
            $options['lms_settings']['active_lms'] = sanitize_text_field($data['active_lms']);
        }
        
        if (isset($data['auto_enroll'])) {
            $options['lms_settings']['auto_enroll'] = (bool) $data['auto_enroll'];
        }
        
        update_option('skylearn_billing_pro_options', $options);
        $this->update_onboarding_step('payment');
        
        return array(
            'success' => true,
            'message' => __('LMS settings saved successfully!', 'skylearn-billing-pro'),
            'next_step' => 'payment'
        );
    }
    
    /**
     * Process payment step
     */
    private function process_payment_step($data) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        // Save payment gateway settings
        if (isset($data['stripe_enabled'])) {
            $options['payment_settings']['stripe']['enabled'] = (bool) $data['stripe_enabled'];
            $options['payment_settings']['stripe']['public_key'] = sanitize_text_field($data['stripe_public_key']);
            $options['payment_settings']['stripe']['secret_key'] = sanitize_text_field($data['stripe_secret_key']);
        }
        
        if (isset($data['lemonsqueezy_enabled'])) {
            $options['payment_settings']['lemonsqueezy']['enabled'] = (bool) $data['lemonsqueezy_enabled'];
            $options['payment_settings']['lemonsqueezy']['store_id'] = sanitize_text_field($data['lemonsqueezy_store_id']);
            $options['payment_settings']['lemonsqueezy']['api_key'] = sanitize_text_field($data['lemonsqueezy_api_key']);
        }
        
        update_option('skylearn_billing_pro_options', $options);
        $this->update_onboarding_step('products');
        
        return array(
            'success' => true,
            'message' => __('Payment settings saved successfully!', 'skylearn-billing-pro'),
            'next_step' => 'products'
        );
    }
    
    /**
     * Process products step
     */
    private function process_products_step($data) {
        if (empty($data['product_name']) || empty($data['product_price'])) {
            return array(
                'success' => false,
                'message' => __('Please enter a product name and price.', 'skylearn-billing-pro')
            );
        }
        
        // Create first product
        $product_manager = skylearn_billing_pro_product_manager();
        $product_data = array(
            'name' => sanitize_text_field($data['product_name']),
            'description' => sanitize_textarea_field($data['product_description']),
            'price' => floatval($data['product_price']),
            'type' => sanitize_text_field($data['product_type'] ?? 'one_time'),
            'status' => 'active'
        );
        
        $product_id = $product_manager->create_product($product_data);
        
        if ($product_id) {
            $this->update_onboarding_step('complete');
            return array(
                'success' => true,
                'message' => __('Product created successfully!', 'skylearn-billing-pro'),
                'next_step' => 'complete'
            );
        } else {
            return array(
                'success' => false,
                'message' => __('Failed to create product. Please try again.', 'skylearn-billing-pro')
            );
        }
    }
    
    /**
     * Skip onboarding
     */
    public function skip_onboarding() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_onboarding_nonce')) {
            wp_die(__('Security check failed.', 'skylearn-billing-pro'));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $this->complete_onboarding();
        
        wp_send_json(array(
            'success' => true,
            'redirect' => admin_url('admin.php?page=skylearn-billing-pro')
        ));
    }
    
    /**
     * Show onboarding notice
     */
    public function show_onboarding_notice() {
        // Only show on plugin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'skylearn-billing-pro') === false) {
            return;
        }
        
        // Don't show if already in onboarding
        if (isset($_GET['onboarding'])) {
            return;
        }
        
        // Only show if onboarding needed
        if (!$this->should_show_onboarding()) {
            return;
        }
        
        echo '<div class="notice notice-info is-dismissible skylearn-onboarding-notice">';
        echo '<p><strong>' . esc_html__('Welcome to Skylearn Billing Pro!', 'skylearn-billing-pro') . '</strong> ';
        echo esc_html__('Complete the setup wizard to get started with your billing system.', 'skylearn-billing-pro');
        echo ' <a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro&onboarding=1')) . '" class="button button-primary">' . esc_html__('Start Setup', 'skylearn-billing-pro') . '</a>';
        echo '</p>';
        echo '</div>';
    }
    
    /**
     * Get contextual help for admin pages
     */
    public function get_contextual_help($page) {
        $help_data = array(
            'general' => array(
                'title' => __('General Settings Help', 'skylearn-billing-pro'),
                'content' => __('Configure your basic billing settings including company information, currency, and test mode.', 'skylearn-billing-pro'),
                'links' => array(
                    'docs' => 'https://skyian.com/skylearn-billing/doc/general-settings/',
                    'support' => 'https://skyian.com/skylearn-billing/support/'
                )
            ),
            'license' => array(
                'title' => __('License Management Help', 'skylearn-billing-pro'),
                'content' => __('Activate your license to unlock Pro features and receive automatic updates.', 'skylearn-billing-pro'),
                'links' => array(
                    'docs' => 'https://skyian.com/skylearn-billing/doc/license/',
                    'support' => 'https://skyian.com/skylearn-billing/support/'
                )
            ),
            'lms' => array(
                'title' => __('LMS Integration Help', 'skylearn-billing-pro'),
                'content' => __('Connect your Learning Management System to automatically enroll students after successful payments.', 'skylearn-billing-pro'),
                'links' => array(
                    'docs' => 'https://skyian.com/skylearn-billing/doc/lms-integration/',
                    'support' => 'https://skyian.com/skylearn-billing/support/'
                )
            ),
            'payments' => array(
                'title' => __('Payment Gateways Help', 'skylearn-billing-pro'),
                'content' => __('Configure Stripe, Lemon Squeezy, and other payment processors to accept payments from your customers.', 'skylearn-billing-pro'),
                'links' => array(
                    'docs' => 'https://skyian.com/skylearn-billing/doc/payment-gateways/',
                    'support' => 'https://skyian.com/skylearn-billing/support/'
                )
            ),
            'products' => array(
                'title' => __('Product Management Help', 'skylearn-billing-pro'),
                'content' => __('Create and manage your courses, digital products, and subscription plans.', 'skylearn-billing-pro'),
                'links' => array(
                    'docs' => 'https://skyian.com/skylearn-billing/doc/products/',
                    'support' => 'https://skyian.com/skylearn-billing/support/'
                )
            )
        );
        
        return isset($help_data[$page]) ? $help_data[$page] : null;
    }
    
    /**
     * Render contextual help tooltip
     */
    public function render_help_tooltip($page, $field = null) {
        $help = $this->get_contextual_help($page);
        if (!$help) {
            return;
        }
        
        $tooltip_id = 'skylearn-help-' . $page . ($field ? '-' . $field : '');
        
        echo '<span class="skylearn-help-tooltip" data-tooltip-id="' . esc_attr($tooltip_id) . '">';
        echo '<span class="dashicons dashicons-editor-help"></span>';
        echo '<div class="skylearn-tooltip-content" id="' . esc_attr($tooltip_id) . '">';
        echo '<h4>' . esc_html($help['title']) . '</h4>';
        echo '<p>' . esc_html($help['content']) . '</p>';
        echo '<div class="skylearn-help-links">';
        echo '<a href="' . esc_url($help['links']['docs']) . '" target="_blank">' . esc_html__('Documentation', 'skylearn-billing-pro') . '</a>';
        echo ' | ';
        echo '<a href="' . esc_url($help['links']['support']) . '" target="_blank">' . esc_html__('Get Support', 'skylearn-billing-pro') . '</a>';
        echo '</div>';
        echo '</div>';
        echo '</span>';
    }
    
    /**
     * Dismiss help tooltip
     */
    public function dismiss_help_tooltip() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_onboarding_nonce')) {
            wp_die(__('Security check failed.', 'skylearn-billing-pro'));
        }
        
        $tooltip_id = sanitize_text_field($_POST['tooltip_id']);
        $dismissed_tooltips = get_user_meta(get_current_user_id(), 'skylearn_dismissed_tooltips', true);
        
        if (!is_array($dismissed_tooltips)) {
            $dismissed_tooltips = array();
        }
        
        $dismissed_tooltips[] = $tooltip_id;
        update_user_meta(get_current_user_id(), 'skylearn_dismissed_tooltips', $dismissed_tooltips);
        
        wp_send_json(array('success' => true));
    }
}

/**
 * Get the onboarding instance
 */
function skylearn_billing_pro_onboarding() {
    return SkyLearn_Billing_Pro_Onboarding::instance();
}

// Initialize the onboarding system
skylearn_billing_pro_onboarding();