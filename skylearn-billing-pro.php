<?php
/**
 * Plugin Name: Skylearn Billing Pro – Ultimate billing solution for WordPress course creators
 * Plugin URI: https://skyian.com/skylearn-billing/
 * Description: Ultimate billing solution for WordPress course creators with seamless integration for Stripe and Lemon Squeezy payment gateways. Manage subscriptions, course access, and billing with ease.
 * Version: 1.0.0
 * Author: Ferdous Khalifa
 * Author URI: https://skyian.com/
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: skylearn-billing-pro
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
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

// Define plugin constants
define('SKYLEARN_BILLING_PRO_VERSION', '1.0.0');
define('SKYLEARN_BILLING_PRO_PLUGIN_FILE', __FILE__);
define('SKYLEARN_BILLING_PRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SKYLEARN_BILLING_PRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SKYLEARN_BILLING_PRO_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class SkyLearnBillingPro {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearnBillingPro
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearnBillingPro
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
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        
        // Add webhook rewrite rule and query var
        add_action('init', array($this, 'add_webhook_rewrite_rule'));
        add_filter('query_vars', array($this, 'add_webhook_query_var'));
        
        // Activation, deactivation and uninstall hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        register_uninstall_hook(__FILE__, array('SkyLearnBillingPro', 'uninstall'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize plugin components
        $this->includes();
        
        // Plugin initialization code will go here
        do_action('skylearn_billing_pro_init');
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Core classes
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-licensing-manager.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-feature-flags.php';
        
        // LMS integration classes
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-lms-manager.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/lms/class-course-mapping.php';
        
        // Payment integration classes
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/payment/class-payment-manager.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/payment/class-payment-fields.php';
        
        // Checkout shortcodes
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-checkout-shortcodes.php';
        
        // Product management classes
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/product/class-product-manager.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/product/class-product-table.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/product/class-bundle-manager.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/product/class-migration-tool.php';
        
        // User enrollment and webhook handler
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-user-enrollment.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-webhook-handler.php';
        
        // Automation system
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/automation/class-automation-manager.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/automation/integrations/crm.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/automation/integrations/email.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/automation/integrations/sms.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/automation/integrations/marketing.php';
        
        // Email system
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/admin/class-email.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/emails/class-email-builder.php';
        
        // Subscription management
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/subscriptions/class-subscription-manager.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/subscriptions/class-loyalty.php';
        
        // Membership management
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/memberships/class-membership-manager.php';
        
        // Portal features
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/portal/class-nurture-popup.php';
        
        // Initialize instances
        skylearn_billing_pro_lms_manager();
        skylearn_billing_pro_course_mapping();
        skylearn_billing_pro_payment_manager();
        skylearn_billing_pro_payment_fields();
        skylearn_billing_pro_checkout_shortcodes();
        skylearn_billing_pro_product_manager();
        skylearn_billing_pro_product_ui();
        skylearn_billing_pro_bundle_manager();
        skylearn_billing_pro_migration_tool();
        skylearn_billing_pro_user_enrollment();
        skylearn_billing_pro_webhook_handler();
        
        // Initialize automation system
        skylearn_billing_pro_automation_manager();
        
        // Initialize email system
        skylearn_billing_pro_email();
        skylearn_billing_pro_email_builder();
        
        // Initialize subscription and membership systems
        skylearn_billing_pro_subscription_manager();
        skylearn_billing_pro_membership_manager();
        skylearn_billing_pro_loyalty();
        skylearn_billing_pro_nurture_popup();
        
        // Initialize security and compliance features
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/security/class-security.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/security/class-audit-logger.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/security/class-gdpr-tools.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/security/class-performance.php';
        require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/security/class-role-access.php';
        
        skylearn_billing_pro_security();
        skylearn_billing_pro_audit_logger();
        skylearn_billing_pro_gdpr_tools();
        skylearn_billing_pro_performance();
        skylearn_billing_pro_role_access();
        
        // Include admin class if in admin
        if (is_admin()) {
            require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/admin/class-welcome-email.php';
            require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/admin/class-reporting.php';
            require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-admin.php';
            new SkyLearn_Billing_Pro_Admin();
            skylearn_billing_pro_welcome_email_admin();
            skylearn_billing_pro_reporting();
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('skylearn-billing-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Load the welcome email admin class for default template
        if (!class_exists('SkyLearn_Billing_Pro_Welcome_Email_Admin')) {
            require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/admin/class-welcome-email.php';
        }
        
        // Create plugin options with default values
        $default_options = array(
            'version' => SKYLEARN_BILLING_PRO_VERSION,
            'general_settings' => array(
                'company_name' => '',
                'company_email' => get_option('admin_email'),
                'currency' => 'USD',
                'test_mode' => true,
            ),
            'lms_settings' => array(
                'active_lms' => '',
                'auto_enroll' => true,
            ),
            'email_settings' => array(
                'welcome_email_enabled' => true,
                'welcome_email_subject' => __('Welcome to {{site_name}}', 'skylearn-billing-pro'),
                'welcome_email_template' => SkyLearn_Billing_Pro_Welcome_Email_Admin::get_default_email_template(),
                'welcome_email_format' => 'html',
            ),
            'webhook_settings' => array(
                'secret' => wp_generate_password(32, false),
                'send_welcome_email' => true,
                'enabled' => true,
            ),
            'course_mappings' => array(),
            'enrollment_log' => array(),
            'email_log' => array(),
            'user_activity_log' => array()
        );
        
        add_option('skylearn_billing_pro_options', $default_options);
        
        // Set plugin installation timestamp
        add_option('skylearn_billing_pro_installed', time());
        
        // Add webhook rewrite rule
        add_rewrite_rule('^skylearn-billing/webhook/?$', 'index.php?skylearn_webhook=1', 'top');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action('skylearn_billing_pro_activate');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clear any scheduled events
        wp_clear_scheduled_hook('skylearn_billing_pro_daily_tasks');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        do_action('skylearn_billing_pro_deactivate');
    }
    
    /**
     * Plugin uninstall
     */
    public static function uninstall() {
        // Remove all plugin options
        delete_option('skylearn_billing_pro_options');
        delete_option('skylearn_billing_pro_installed');
        
        // Remove any custom database tables if they exist
        global $wpdb;
        
        // Note: In the future, we would drop custom tables here
        // Example: $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}skylearn_billing_subscriptions");
        
        // Clear any scheduled events
        wp_clear_scheduled_hook('skylearn_billing_pro_daily_tasks');
        
        do_action('skylearn_billing_pro_uninstall');
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'skylearn-billing-pro') === false) {
            return;
        }
        
        // Main admin styles and scripts
        wp_enqueue_style('skylearn-billing-pro-admin', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/admin.css', array(), SKYLEARN_BILLING_PRO_VERSION);
        wp_enqueue_script('skylearn-billing-pro-admin', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SKYLEARN_BILLING_PRO_VERSION, true);
        
        // Email builder assets
        wp_enqueue_style('skylearn-email-builder', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/email-builder.css', array(), SKYLEARN_BILLING_PRO_VERSION);
        wp_enqueue_script('skylearn-email-builder', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/email-builder.js', array('jquery', 'jquery-ui-sortable'), SKYLEARN_BILLING_PRO_VERSION, true);
        
        // WordPress media library for image uploads
        wp_enqueue_media();
        
        // Localize scripts with nonces
        wp_localize_script('skylearn-email-builder', 'skylearn_email_builder_nonces', array(
            'skylearn_email_builder' => wp_create_nonce('skylearn_email_builder'),
            'skylearn_email_preview' => wp_create_nonce('skylearn_email_preview'),
            'skylearn_email_test' => wp_create_nonce('skylearn_email_test'),
            'skylearn_email_save' => wp_create_nonce('skylearn_email_save'),
            'skylearn_email_analytics' => wp_create_nonce('skylearn_email_analytics'),
            'skylearn_smtp_test' => wp_create_nonce('skylearn_smtp_test'),
            'skylearn_export_templates' => wp_create_nonce('skylearn_export_templates'),
            'skylearn_import_templates' => wp_create_nonce('skylearn_import_templates')
        ));
    }
    
    /**
     * Add webhook rewrite rule
     */
    public function add_webhook_rewrite_rule() {
        add_rewrite_rule('^skylearn-billing/webhook/?$', 'index.php?skylearn_webhook=1', 'top');
    }
    
    /**
     * Add webhook query variable
     */
    public function add_webhook_query_var($vars) {
        $vars[] = 'skylearn_webhook';
        return $vars;
    }
}

/**
 * Get the main instance of SkyLearnBillingPro
 *
 * @return SkyLearnBillingPro
 */
function skylearn_billing_pro() {
    return SkyLearnBillingPro::instance();
}

// Initialize the plugin
skylearn_billing_pro();