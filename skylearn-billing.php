<?php
/**
 * Skylearn Billing Pro - Legacy Bootstrap File
 * 
 * This file contains legacy functionality and should not be used as a plugin entry point.
 * The main plugin entry point is skylearn-billing-pro.php
 * 
 * @package SkyLearnBillingPro
 * @deprecated Use skylearn-billing-pro.php instead
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SKYLEARN_BILLING_VERSION', '1.0.0');
define('SKYLEARN_BILLING_PLUGIN_FILE', __FILE__);
define('SKYLEARN_BILLING_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SKYLEARN_BILLING_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SKYLEARN_BILLING_INCLUDES_DIR', SKYLEARN_BILLING_PLUGIN_DIR . 'includes/');
define('SKYLEARN_BILLING_TEMPLATES_DIR', SKYLEARN_BILLING_PLUGIN_DIR . 'templates/');
define('SKYLEARN_BILLING_ASSETS_URL', SKYLEARN_BILLING_PLUGIN_URL . 'assets/');

/**
 * Main plugin class
 */
class Skylearn_Billing_Pro {
    
    /**
     * Single instance of the class
     */
    protected static $_instance = null;
    
    /**
     * Main instance
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
        $this->init_autoloader();
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 0);
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
    }
    
    /**
     * Initialize autoloader for includes/ directory
     */
    private function init_autoloader() {
        spl_autoload_register(array($this, 'autoload'));
    }
    
    /**
     * Autoload classes from includes/ directory
     */
    public function autoload($class_name) {
        // Only autoload our classes
        if (strpos($class_name, 'Skylearn_Billing_') !== 0) {
            return;
        }
        
        // Convert class name to file path
        $file_name = str_replace('_', '-', strtolower($class_name));
        $file_path = SKYLEARN_BILLING_INCLUDES_DIR . 'class-' . $file_name . '.php';
        
        if (file_exists($file_path)) {
            require_once $file_path;
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('skylearn-billing', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize core components
        $this->load_core_classes();
        
        // Initialize plugin components (placeholder for future development)
        do_action('skylearn_billing_init');
    }
    
    /**
     * Load core plugin classes
     */
    private function load_core_classes() {
        // Load update manager
        if (file_exists(SKYLEARN_BILLING_INCLUDES_DIR . 'class-update-manager.php')) {
            require_once SKYLEARN_BILLING_INCLUDES_DIR . 'class-update-manager.php';
        }
        
        // Load error reporter
        if (file_exists(SKYLEARN_BILLING_INCLUDES_DIR . 'class-error-reporter.php')) {
            require_once SKYLEARN_BILLING_INCLUDES_DIR . 'class-error-reporter.php';
        }
    }
    
    /**
     * When WordPress has loaded all plugins
     */
    public function plugins_loaded() {
        do_action('skylearn_billing_loaded');
    }
}

/**
 * Plugin activation hook
 */
function skylearn_billing_activate() {
    // Placeholder for activation logic
    // Future: Create database tables, set default options, etc.
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    do_action('skylearn_billing_activated');
}

/**
 * Plugin deactivation hook
 */
function skylearn_billing_deactivate() {
    // Placeholder for deactivation logic
    // Future: Clean up scheduled events, flush rules, etc.
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    do_action('skylearn_billing_deactivated');
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'skylearn_billing_activate');
register_deactivation_hook(__FILE__, 'skylearn_billing_deactivate');

/**
 * Main instance of Skylearn Billing Pro
 */
function skylearn_billing() {
    return Skylearn_Billing_Pro::instance();
}

// Initialize the plugin
skylearn_billing();