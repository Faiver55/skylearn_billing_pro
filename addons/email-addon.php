<?php
/**
 * Email Addon for Skylearn Billing Pro
 *
 * Addon ID: email-addon
 * Addon Name: Email Addon
 * Description: Enhanced email notifications and templates
 * Version: 1.0.0
 * Author: Skylearn Team
 * Type: free
 * Required Tier: free
 * Category: notifications
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
 * Email Addon Class
 */
class SkyLearn_Billing_Pro_Email_Addon {
    
    /**
     * Addon ID
     */
    const ADDON_ID = 'email-addon';
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Email_Addon
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Email_Addon
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
        add_action('init', array($this, 'init'));
        
        // Addon activation hook
        add_action('skylearn_billing_addon_activated', array($this, 'on_addon_activated'));
        add_action('skylearn_billing_addon_deactivated', array($this, 'on_addon_deactivated'));
    }
    
    /**
     * Initialize addon
     */
    public function init() {
        // Only initialize if addon is active
        $addon_manager = skylearn_billing_pro_addon_manager();
        $active_addons = $addon_manager->get_active_addons();
        
        if (!in_array(self::ADDON_ID, $active_addons)) {
            return;
        }
        
        $this->init_hooks();
        $this->init_settings();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Email notification hooks
        add_action('skylearn_billing_payment_completed', array($this, 'send_payment_confirmation'));
        add_action('skylearn_billing_subscription_created', array($this, 'send_subscription_welcome'));
        add_action('skylearn_billing_subscription_cancelled', array($this, 'send_subscription_cancellation'));
        
        // Email template hooks
        add_filter('skylearn_billing_email_template', array($this, 'apply_custom_template'), 10, 3);
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_skylearn_billing_test_email', array($this, 'send_test_email'));
    }
    
    /**
     * Initialize settings
     */
    private function init_settings() {
        register_setting('skylearn_billing_email_addon', 'skylearn_billing_email_addon_settings');
        
        add_settings_section(
            'skylearn_billing_email_templates',
            __('Email Templates', 'skylearn-billing-pro'),
            array($this, 'render_email_templates_section'),
            'skylearn_billing_email_addon'
        );
        
        add_settings_field(
            'enable_custom_templates',
            __('Enable Custom Templates', 'skylearn-billing-pro'),
            array($this, 'render_enable_custom_templates_field'),
            'skylearn_billing_email_addon',
            'skylearn_billing_email_templates'
        );
        
        add_settings_field(
            'from_name',
            __('From Name', 'skylearn-billing-pro'),
            array($this, 'render_from_name_field'),
            'skylearn_billing_email_addon',
            'skylearn_billing_email_templates'
        );
        
        add_settings_field(
            'from_email',
            __('From Email', 'skylearn-billing-pro'),
            array($this, 'render_from_email_field'),
            'skylearn_billing_email_addon',
            'skylearn_billing_email_templates'
        );
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'skylearn-billing-pro',
            __('Email Addon Settings', 'skylearn-billing-pro'),
            __('Email Addon', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-email-addon',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'skylearn-billing-email-addon') === false) {
            return;
        }
        
        wp_enqueue_script(
            'skylearn-billing-email-addon',
            SKYLEARN_BILLING_PLUGIN_URL . 'assets/js/email-addon-admin.js',
            array('jquery'),
            SKYLEARN_BILLING_VERSION,
            true
        );
        
        wp_localize_script('skylearn-billing-email-addon', 'skylearn_billing_email_addon', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_billing_email_addon'),
            'strings' => array(
                'sending' => __('Sending test email...', 'skylearn-billing-pro'),
                'sent' => __('Test email sent successfully!', 'skylearn-billing-pro'),
                'error' => __('Error sending test email.', 'skylearn-billing-pro')
            )
        ));
    }
    
    /**
     * Send payment confirmation email
     *
     * @param array $payment_data Payment data
     */
    public function send_payment_confirmation($payment_data) {
        $to = $payment_data['customer_email'] ?? '';
        $subject = __('Payment Confirmation', 'skylearn-billing-pro');
        
        $message = $this->get_email_template('payment_confirmation', array(
            'customer_name' => $payment_data['customer_name'] ?? '',
            'amount' => $payment_data['amount'] ?? '',
            'currency' => $payment_data['currency'] ?? '',
            'payment_date' => date_i18n(get_option('date_format')),
            'order_id' => $payment_data['order_id'] ?? ''
        ));
        
        $this->send_email($to, $subject, $message);
    }
    
    /**
     * Send subscription welcome email
     *
     * @param array $subscription_data Subscription data
     */
    public function send_subscription_welcome($subscription_data) {
        $to = $subscription_data['customer_email'] ?? '';
        $subject = __('Welcome to Your Subscription', 'skylearn-billing-pro');
        
        $message = $this->get_email_template('subscription_welcome', array(
            'customer_name' => $subscription_data['customer_name'] ?? '',
            'plan_name' => $subscription_data['plan_name'] ?? '',
            'next_billing_date' => $subscription_data['next_billing_date'] ?? ''
        ));
        
        $this->send_email($to, $subject, $message);
    }
    
    /**
     * Send subscription cancellation email
     *
     * @param array $subscription_data Subscription data
     */
    public function send_subscription_cancellation($subscription_data) {
        $to = $subscription_data['customer_email'] ?? '';
        $subject = __('Subscription Cancelled', 'skylearn-billing-pro');
        
        $message = $this->get_email_template('subscription_cancellation', array(
            'customer_name' => $subscription_data['customer_name'] ?? '',
            'plan_name' => $subscription_data['plan_name'] ?? '',
            'cancellation_date' => date_i18n(get_option('date_format'))
        ));
        
        $this->send_email($to, $subject, $message);
    }
    
    /**
     * Get email template
     *
     * @param string $template Template name
     * @param array $variables Template variables
     * @return string Email content
     */
    private function get_email_template($template, $variables = array()) {
        $settings = get_option('skylearn_billing_email_addon_settings', array());
        
        // Default templates
        $templates = array(
            'payment_confirmation' => "Hi {customer_name},\n\nYour payment of {amount} {currency} has been successfully processed.\n\nOrder ID: {order_id}\nPayment Date: {payment_date}\n\nThank you for your business!",
            'subscription_welcome' => "Hi {customer_name},\n\nWelcome to your {plan_name} subscription!\n\nYour subscription is now active and your next billing date is {next_billing_date}.\n\nThank you for subscribing!",
            'subscription_cancellation' => "Hi {customer_name},\n\nYour {plan_name} subscription has been cancelled as of {cancellation_date}.\n\nIf you have any questions, please contact us.\n\nThank you for being a valued customer."
        );
        
        $content = $templates[$template] ?? '';
        
        // Apply custom template if enabled
        if (!empty($settings['enable_custom_templates']) && !empty($settings['templates'][$template])) {
            $content = $settings['templates'][$template];
        }
        
        // Replace variables
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', $value, $content);
        }
        
        return apply_filters('skylearn_billing_email_template_content', $content, $template, $variables);
    }
    
    /**
     * Send email
     *
     * @param string $to Recipient email
     * @param string $subject Email subject
     * @param string $message Email message
     * @return bool Success status
     */
    private function send_email($to, $subject, $message) {
        if (empty($to)) {
            return false;
        }
        
        $settings = get_option('skylearn_billing_email_addon_settings', array());
        
        // Set from name and email
        if (!empty($settings['from_name'])) {
            add_filter('wp_mail_from_name', function() use ($settings) {
                return $settings['from_name'];
            });
        }
        
        if (!empty($settings['from_email'])) {
            add_filter('wp_mail_from', function() use ($settings) {
                return $settings['from_email'];
            });
        }
        
        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $result = wp_mail($to, $subject, wpautop($message), $headers);
        
        // Log email sending
        do_action('skylearn_billing_email_sent', $to, $subject, $message, $result);
        
        return $result;
    }
    
    /**
     * Apply custom template filter
     *
     * @param string $content Template content
     * @param string $template Template name
     * @param array $variables Template variables
     * @return string Modified content
     */
    public function apply_custom_template($content, $template, $variables) {
        return $this->get_email_template($template, $variables);
    }
    
    /**
     * Send test email
     */
    public function send_test_email() {
        check_ajax_referer('skylearn_billing_email_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $test_email = sanitize_email($_POST['test_email']);
        
        if (empty($test_email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'skylearn-billing-pro'));
        }
        
        $subject = __('Test Email from Skylearn Billing Pro', 'skylearn-billing-pro');
        $message = __('This is a test email from the Skylearn Billing Pro Email Addon.', 'skylearn-billing-pro');
        
        $result = $this->send_email($test_email, $subject, $message);
        
        if ($result) {
            wp_send_json_success(__('Test email sent successfully!', 'skylearn-billing-pro'));
        } else {
            wp_send_json_error(__('Failed to send test email.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Email Addon Settings', 'skylearn-billing-pro'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('skylearn_billing_email_addon');
                do_settings_sections('skylearn_billing_email_addon');
                submit_button();
                ?>
            </form>
            
            <div class="skylearn-billing-test-email">
                <h2><?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Send a test email to verify your email settings.', 'skylearn-billing-pro'); ?></p>
                
                <input type="email" id="test-email-address" placeholder="<?php esc_attr_e('Enter email address...', 'skylearn-billing-pro'); ?>" />
                <button type="button" id="send-test-email" class="button"><?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?></button>
                <div id="test-email-result"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render email templates section
     */
    public function render_email_templates_section() {
        echo '<p>' . __('Configure email templates for automated notifications.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Render enable custom templates field
     */
    public function render_enable_custom_templates_field() {
        $settings = get_option('skylearn_billing_email_addon_settings', array());
        $value = $settings['enable_custom_templates'] ?? false;
        ?>
        <input type="checkbox" name="skylearn_billing_email_addon_settings[enable_custom_templates]" value="1" <?php checked($value); ?> />
        <label><?php esc_html_e('Enable custom email templates', 'skylearn-billing-pro'); ?></label>
        <?php
    }
    
    /**
     * Render from name field
     */
    public function render_from_name_field() {
        $settings = get_option('skylearn_billing_email_addon_settings', array());
        $value = $settings['from_name'] ?? get_bloginfo('name');
        ?>
        <input type="text" name="skylearn_billing_email_addon_settings[from_name]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Name displayed as sender of emails.', 'skylearn-billing-pro'); ?></p>
        <?php
    }
    
    /**
     * Render from email field
     */
    public function render_from_email_field() {
        $settings = get_option('skylearn_billing_email_addon_settings', array());
        $value = $settings['from_email'] ?? get_option('admin_email');
        ?>
        <input type="email" name="skylearn_billing_email_addon_settings[from_email]" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Email address used as sender of emails.', 'skylearn-billing-pro'); ?></p>
        <?php
    }
    
    /**
     * Handle addon activation
     *
     * @param string $addon_id Addon ID
     */
    public function on_addon_activated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Set default settings
            $default_settings = array(
                'enable_custom_templates' => false,
                'from_name' => get_bloginfo('name'),
                'from_email' => get_option('admin_email')
            );
            
            add_option('skylearn_billing_email_addon_settings', $default_settings);
            
            // Trigger activation hook for other integrations
            do_action('skylearn_billing_email_addon_activated');
        }
    }
    
    /**
     * Handle addon deactivation
     *
     * @param string $addon_id Addon ID
     */
    public function on_addon_deactivated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Clean up if needed
            do_action('skylearn_billing_email_addon_deactivated');
        }
    }
}

// Initialize the addon
SkyLearn_Billing_Pro_Email_Addon::instance();