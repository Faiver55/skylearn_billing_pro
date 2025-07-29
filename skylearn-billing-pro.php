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
        // Include admin class if in admin
        if (is_admin()) {
            require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-admin.php';
            new SkyLearn_Billing_Pro_Admin();
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
        // Create plugin options with default values
        $default_options = array(
            'version' => SKYLEARN_BILLING_PRO_VERSION,
            'general_settings' => array(
                'company_name' => '',
                'company_email' => get_option('admin_email'),
                'currency' => 'USD',
                'test_mode' => true,
            )
        );
        
        add_option('skylearn_billing_pro_options', $default_options);
        
        // Set plugin installation timestamp
        add_option('skylearn_billing_pro_installed', time());
        
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
        
        wp_enqueue_style('skylearn-billing-pro-admin', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/admin.css', array(), SKYLEARN_BILLING_PRO_VERSION);
        wp_enqueue_script('skylearn-billing-pro-admin', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SKYLEARN_BILLING_PRO_VERSION, true);
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