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
        
        // Activation and deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Plugin initialization code will go here
        do_action('skylearn_billing_pro_init');
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
        // Activation code will go here
        do_action('skylearn_billing_pro_activate');
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Deactivation code will go here
        do_action('skylearn_billing_pro_deactivate');
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