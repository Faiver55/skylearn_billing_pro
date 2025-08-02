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
        add_action('plugins_loaded', array($this, 'early_ajax_init'));
        add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        add_action('admin_init', array($this, 'handle_activation_redirect'));
        
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
        // Include debug logger first for error tracking
        if (file_exists(SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-debug-logger.php')) {
            require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-debug-logger.php';
            SkyLearn_Billing_Pro_Debug_Logger::info('Starting plugin initialization');
        }
        
        // Core classes with error handling
        $this->safe_include('includes/class-licensing-manager.php', 'Licensing Manager');
        $this->safe_include('includes/class-feature-flags.php', 'Feature Flags');
        $this->safe_include('includes/class-database-manager.php', 'Database Manager');
        
        // LMS integration classes
        $this->safe_include('includes/lms/class-lms-manager.php', 'LMS Manager');
        $this->safe_include('includes/lms/class-course-mapping.php', 'Course Mapping');
        $this->safe_include('includes/lms/class-course-mapping-migration.php', 'Course Mapping Migration');
        
        // Payment integration classes
        $this->safe_include('includes/payment/class-payment-manager.php', 'Payment Manager');
        $this->safe_include('includes/payment/class-payment-fields.php', 'Payment Fields');
        
        // Checkout shortcodes
        $this->safe_include('includes/class-checkout-shortcodes.php', 'Checkout Shortcodes');
        
        // Product management classes
        $this->safe_include('includes/product/class-product-manager.php', 'Product Manager');
        $this->safe_include('includes/product/class-product-table.php', 'Product Table');
        $this->safe_include('includes/product/class-bundle-manager.php', 'Bundle Manager');
        $this->safe_include('includes/product/class-migration-tool.php', 'Migration Tool');
        
        // User enrollment and webhook handler
        $this->safe_include('includes/class-user-enrollment.php', 'User Enrollment');
        $this->safe_include('includes/class-webhook-handler.php', 'Webhook Handler');
        
        // Automation system
        $this->safe_include('includes/automation/class-automation-manager.php', 'Automation Manager');
        $this->safe_include('includes/automation/integrations/crm.php', 'CRM Integration');
        $this->safe_include('includes/automation/integrations/email.php', 'Email Integration');
        $this->safe_include('includes/automation/integrations/sms.php', 'SMS Integration');
        $this->safe_include('includes/automation/integrations/marketing.php', 'Marketing Integration');
        
        // Email system
        $this->safe_include('includes/admin/class-email.php', 'Admin Email');
        $this->safe_include('includes/emails/class-email-builder.php', 'Email Builder');
        
        // Subscription management
        $this->safe_include('includes/subscriptions/class-subscription-manager.php', 'Subscription Manager');
        $this->safe_include('includes/subscriptions/class-loyalty.php', 'Loyalty System');
        
        // Membership management
        $this->safe_include('includes/memberships/class-membership-manager.php', 'Membership Manager');
        
        // Portal features
        $this->safe_include('includes/portal/class-nurture-popup.php', 'Nurture Popup');
        
        // Initialize instances with error handling
        $functions_to_init = array(
            'skylearn_billing_pro_database_manager',
            'skylearn_billing_pro_lms_manager',
            'skylearn_billing_pro_course_mapping',
            'skylearn_billing_pro_payment_manager',
            'skylearn_billing_pro_payment_fields',
            'skylearn_billing_pro_checkout_shortcodes',
            'skylearn_billing_pro_product_manager',
            'skylearn_billing_pro_product_ui',
            'skylearn_billing_pro_bundle_manager',
            'skylearn_billing_pro_migration_tool',
            'skylearn_billing_pro_user_enrollment',
            'skylearn_billing_pro_webhook_handler'
        );
        
        foreach ($functions_to_init as $function_name) {
            if (function_exists($function_name)) {
                try {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_start($function_name);
                    }
                    call_user_func($function_name);
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_end($function_name, true);
                    }
                } catch (Exception $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::exception($e, "Failed to initialize $function_name");
                        SkyLearn_Billing_Pro_Debug_Logger::function_end($function_name, false);
                    }
                    error_log('SkyLearn Billing Pro: Failed to initialize ' . $function_name . ' - ' . $e->getMessage());
                } catch (Error $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::error("Fatal error in $function_name: " . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                        SkyLearn_Billing_Pro_Debug_Logger::function_end($function_name, false);
                    }
                    error_log('SkyLearn Billing Pro: Fatal error in ' . $function_name . ' - ' . $e->getMessage());
                }
            } else {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::warning("Function $function_name does not exist");
                }
                error_log('SkyLearn Billing Pro: Function ' . $function_name . ' does not exist');
            }
        }
        
        // Initialize automation system with error handling
        if (function_exists('skylearn_billing_pro_automation_manager')) {
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_automation_manager');
                }
                skylearn_billing_pro_automation_manager();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_automation_manager', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Automation manager initialization failed');
                }
                error_log('SkyLearn Billing Pro: Automation manager initialization failed - ' . $e->getMessage());
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in automation manager: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in automation manager - ' . $e->getMessage());
            }
        }
        
        // Initialize email system with error handling
        if (function_exists('skylearn_billing_pro_email')) {
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_email');
                }
                skylearn_billing_pro_email();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_email', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Email system initialization failed');
                }
                error_log('SkyLearn Billing Pro: Email system initialization failed - ' . $e->getMessage());
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in email system: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in email system - ' . $e->getMessage());
            }
        }
        
        if (function_exists('skylearn_billing_pro_email_builder')) {
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_email_builder');
                }
                skylearn_billing_pro_email_builder();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_email_builder', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Email builder initialization failed');
                }
                error_log('SkyLearn Billing Pro: Email builder initialization failed - ' . $e->getMessage());
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in email builder: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in email builder - ' . $e->getMessage());
            }
        }
        
        // Initialize subscription and membership systems with error handling
        $subscription_functions = array(
            'skylearn_billing_pro_subscription_manager',
            'skylearn_billing_pro_membership_manager',
            'skylearn_billing_pro_loyalty',
            'skylearn_billing_pro_nurture_popup'
        );
        
        foreach ($subscription_functions as $function_name) {
            if (function_exists($function_name)) {
                try {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_start($function_name);
                    }
                    call_user_func($function_name);
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_end($function_name, true);
                    }
                } catch (Exception $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::exception($e, "Failed to initialize $function_name");
                    }
                    error_log('SkyLearn Billing Pro: Failed to initialize ' . $function_name . ' - ' . $e->getMessage());
                } catch (Error $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::error("Fatal error in $function_name: " . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                    }
                    error_log('SkyLearn Billing Pro: Fatal error in ' . $function_name . ' - ' . $e->getMessage());
                }
            } else {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::warning("Function $function_name does not exist");
                }
                error_log('SkyLearn Billing Pro: Function ' . $function_name . ' does not exist');
            }
        }
        
        // Initialize security and compliance features
        $this->safe_include('includes/security/class-security.php', 'Security System');
        $this->safe_include('includes/security/class-audit-logger.php', 'Audit Logger');
        $this->safe_include('includes/security/class-gdpr-tools.php', 'GDPR Tools');
        $this->safe_include('includes/security/class-performance.php', 'Performance Monitor');
        $this->safe_include('includes/security/class-role-access.php', 'Role Access');
        
        // Initialize security and compliance features with error handling
        $security_functions = array(
            'skylearn_billing_pro_security',
            'skylearn_billing_pro_audit_logger',
            'skylearn_billing_pro_gdpr_tools',
            'skylearn_billing_pro_performance',
            'skylearn_billing_pro_role_access'
        );
        
        foreach ($security_functions as $function_name) {
            if (function_exists($function_name)) {
                try {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_start($function_name);
                    }
                    call_user_func($function_name);
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_end($function_name, true);
                    }
                } catch (Exception $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::exception($e, "Failed to initialize $function_name");
                    }
                    error_log('SkyLearn Billing Pro: Failed to initialize ' . $function_name . ' - ' . $e->getMessage());
                } catch (Error $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::error("Fatal error in $function_name: " . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                    }
                    error_log('SkyLearn Billing Pro: Fatal error in ' . $function_name . ' - ' . $e->getMessage());
                }
            } else {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::warning("Function $function_name does not exist");
                }
                error_log('SkyLearn Billing Pro: Function ' . $function_name . ' does not exist');
            }
        }
        
        // Initialize Phase 15 Frontend Features
        $this->safe_include('includes/frontend/page-generator.php', 'Page Generator');
        $this->safe_include('includes/frontend/shortcodes.php', 'Frontend Shortcodes');
        $this->safe_include('includes/frontend/block-editor/blocks.php', 'Block Editor');
        $this->safe_include('includes/page-setup.php', 'Page Setup');
        
        // Initialize frontend features with proper error handling
        if (function_exists('skylearn_billing_pro_page_generator')) {
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_page_generator');
                }
                skylearn_billing_pro_page_generator();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_page_generator', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Page generator initialization failed');
                }
                error_log('SkyLearn Billing Pro: Page generator initialization failed - ' . $e->getMessage());
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in page generator: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in page generator - ' . $e->getMessage());
            }
        }
        
        if (function_exists('skylearn_billing_pro_frontend_shortcodes')) {
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_frontend_shortcodes');
                }
                skylearn_billing_pro_frontend_shortcodes();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_frontend_shortcodes', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Frontend shortcodes initialization failed');
                }
                error_log('SkyLearn Billing Pro: Frontend shortcodes initialization failed - ' . $e->getMessage());
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in frontend shortcodes: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in frontend shortcodes - ' . $e->getMessage());
            }
        }
        
        if (function_exists('skylearn_billing_pro_block_editor')) {
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_block_editor');
                }
                skylearn_billing_pro_block_editor();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_block_editor', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Block editor initialization failed');
                }
                error_log('SkyLearn Billing Pro: Block editor initialization failed - ' . $e->getMessage());
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in block editor: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in block editor - ' . $e->getMessage());
            }
        }
        
        if (function_exists('skylearn_billing_pro_page_setup')) {
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_page_setup');
                }
                skylearn_billing_pro_page_setup();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_page_setup', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Page setup initialization failed');
                }
                error_log('SkyLearn Billing Pro: Page setup initialization failed - ' . $e->getMessage());
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in page setup: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in page setup - ' . $e->getMessage());
            }
        }
        
        // Include admin class if in admin
        if (is_admin()) {
            $this->safe_include('includes/admin/class-welcome-email.php', 'Welcome Email Admin');
            $this->safe_include('includes/admin/class-reporting.php', 'Reporting Admin');
            $this->safe_include('includes/class-admin.php', 'Main Admin Class');
            
            // Include onboarding and admin UI enhancements
            $this->safe_include('includes/class-onboarding.php', 'Onboarding System');
            $this->safe_include('includes/admin/class-admin-ui.php', 'Admin UI');
            
            // Include page setup diagnostics for debugging
            $this->safe_include('includes/page-setup-diagnostics.php', 'Page Setup Diagnostics');
            
            // Initialize admin components with error handling
            try {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_start('SkyLearn_Billing_Pro_Admin constructor');
                }
                new SkyLearn_Billing_Pro_Admin();
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::function_end('SkyLearn_Billing_Pro_Admin constructor', true);
                }
            } catch (Exception $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Admin class initialization failed');
                }
                error_log('SkyLearn Billing Pro: Admin class initialization failed - ' . $e->getMessage());
                // Add admin notice for the error
                add_action('admin_notices', function() use ($e) {
                    echo '<div class="notice notice-error"><p><strong>SkyLearn Billing Pro:</strong> Admin initialization failed. Error: ' . esc_html($e->getMessage()) . '</p></div>';
                });
            } catch (Error $e) {
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in admin initialization: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                }
                error_log('SkyLearn Billing Pro: Fatal error in admin initialization - ' . $e->getMessage());
                // Add admin notice for the error
                add_action('admin_notices', function() use ($e) {
                    echo '<div class="notice notice-error"><p><strong>SkyLearn Billing Pro:</strong> Fatal error in admin initialization. Error: ' . esc_html($e->getMessage()) . '</p></div>';
                });
            }
            
            // Initialize admin email functions with error handling
            if (function_exists('skylearn_billing_pro_welcome_email_admin')) {
                try {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_welcome_email_admin');
                    }
                    skylearn_billing_pro_welcome_email_admin();
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_welcome_email_admin', true);
                    }
                } catch (Exception $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Welcome email admin initialization failed');
                    }
                    error_log('SkyLearn Billing Pro: Welcome email admin initialization failed - ' . $e->getMessage());
                } catch (Error $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in welcome email admin: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                    }
                    error_log('SkyLearn Billing Pro: Fatal error in welcome email admin - ' . $e->getMessage());
                }
            }
            
            if (function_exists('skylearn_billing_pro_reporting')) {
                try {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_start('skylearn_billing_pro_reporting');
                    }
                    skylearn_billing_pro_reporting();
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::function_end('skylearn_billing_pro_reporting', true);
                    }
                } catch (Exception $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::exception($e, 'Reporting initialization failed');
                    }
                    error_log('SkyLearn Billing Pro: Reporting initialization failed - ' . $e->getMessage());
                } catch (Error $e) {
                    if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                        SkyLearn_Billing_Pro_Debug_Logger::error('Fatal error in reporting: ' . $e->getMessage(), $e->getFile() . ':' . $e->getLine());
                    }
                    error_log('SkyLearn Billing Pro: Fatal error in reporting - ' . $e->getMessage());
                }
            }
        }
        
        // Include CLI commands if WP-CLI is available
        if (defined('WP_CLI') && WP_CLI) {
            $this->safe_include('cli/cli-commands.php', 'CLI Commands');
        }
        
        if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
            SkyLearn_Billing_Pro_Debug_Logger::info('Plugin initialization completed');
        }
    }
    
    /**
     * Safely include a file with error handling and logging
     * 
     * @param string $file_path The relative path to the file
     * @param string $description Human-readable description of the file
     * @return bool Whether the file was successfully included
     */
    private function safe_include($file_path, $description = '') {
        $full_path = SKYLEARN_BILLING_PRO_PLUGIN_DIR . $file_path;
        
        try {
            // Check if file exists
            if (!file_exists($full_path)) {
                $error_msg = "File not found: $file_path";
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error($error_msg, $description);
                }
                error_log('SkyLearn Billing Pro: ' . $error_msg . ($description ? " ($description)" : ''));
                return false;
            }
            
            // Check if file is readable
            if (!is_readable($full_path)) {
                $error_msg = "File not readable: $file_path";
                if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                    SkyLearn_Billing_Pro_Debug_Logger::error($error_msg, $description);
                }
                error_log('SkyLearn Billing Pro: ' . $error_msg . ($description ? " ($description)" : ''));
                return false;
            }
            
            // Log file inclusion attempt
            if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                SkyLearn_Billing_Pro_Debug_Logger::info("Including file: $file_path", $description);
            }
            
            // Include the file
            require_once $full_path;
            
            // Log successful inclusion
            if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                SkyLearn_Billing_Pro_Debug_Logger::info("Successfully included: $file_path", $description);
            }
            
            return true;
            
        } catch (ParseError $e) {
            $error_msg = "Parse error in $file_path: " . $e->getMessage();
            if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                SkyLearn_Billing_Pro_Debug_Logger::error($error_msg, $description . ' | Line: ' . $e->getLine());
            }
            error_log('SkyLearn Billing Pro: ' . $error_msg . ($description ? " ($description)" : ''));
            return false;
            
        } catch (Error $e) {
            $error_msg = "Fatal error including $file_path: " . $e->getMessage();
            if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                SkyLearn_Billing_Pro_Debug_Logger::error($error_msg, $description . ' | Line: ' . $e->getLine());
            }
            error_log('SkyLearn Billing Pro: ' . $error_msg . ($description ? " ($description)" : ''));
            return false;
            
        } catch (Exception $e) {
            $error_msg = "Exception including $file_path: " . $e->getMessage();
            if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
                SkyLearn_Billing_Pro_Debug_Logger::exception($e, $description);
            }
            error_log('SkyLearn Billing Pro: ' . $error_msg . ($description ? " ($description)" : ''));
            return false;
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain('skylearn-billing-pro', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    /**
     * Early initialization for AJAX handlers
     * This ensures AJAX handlers are available before the init hook
     */
    public function early_ajax_init() {
        // Only load AJAX handlers if we're in an AJAX context or admin area
        if (wp_doing_ajax() || is_admin()) {
            // Load required files for AJAX functionality
            if (file_exists(SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/frontend/page-generator.php')) {
                require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/frontend/page-generator.php';
            }
            
            if (file_exists(SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/page-setup.php')) {
                require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/page-setup.php';
                
                // Initialize page setup early for AJAX
                if (function_exists('skylearn_billing_pro_page_setup')) {
                    try {
                        skylearn_billing_pro_page_setup();
                        error_log('SkyLearn Billing Pro: Page setup initialized early for AJAX');
                    } catch (Exception $e) {
                        error_log('SkyLearn Billing Pro: Early page setup initialization failed - ' . $e->getMessage());
                    }
                }
            }
            
            // Also ensure page generator is available
            if (function_exists('skylearn_billing_pro_page_generator')) {
                try {
                    skylearn_billing_pro_page_generator();
                } catch (Exception $e) {
                    error_log('SkyLearn Billing Pro: Early page generator initialization failed - ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Handle activation redirect to onboarding
     */
    public function handle_activation_redirect() {
        // Only run in admin and if redirect flag is set
        if (!is_admin() || !get_transient('skylearn_billing_pro_activation_redirect')) {
            return;
        }
        
        // Clear the redirect flag
        delete_transient('skylearn_billing_pro_activation_redirect');
        
        // Don't redirect if doing AJAX or if already on our pages
        if (wp_doing_ajax() || (isset($_GET['page']) && strpos($_GET['page'], 'skylearn-billing-pro') !== false)) {
            return;
        }
        
        // Don't redirect if user can't manage options
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Redirect to onboarding
        wp_redirect(admin_url('admin.php?page=skylearn-billing-pro&onboarding=1'));
        exit;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        try {
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
                    'welcome_email_template' => class_exists('SkyLearn_Billing_Pro_Welcome_Email_Admin') ? 
                        SkyLearn_Billing_Pro_Welcome_Email_Admin::get_default_email_template() : '',
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
            
            // Set activation redirect flag for onboarding
            set_transient('skylearn_billing_pro_activation_redirect', true, 30);
            
            // Trigger activation hooks (but not page creation)
            do_action('skylearn_billing_pro_activate');
            
            // Log successful activation
            error_log('SkyLearn Billing Pro: Plugin activated successfully - will redirect to onboarding');
            
        } catch (Exception $e) {
            // Log activation error
            error_log('SkyLearn Billing Pro: Plugin activation failed - ' . $e->getMessage());
            
            // Deactivate plugin if activation fails
            deactivate_plugins(plugin_basename(__FILE__));
            
            // Show error message to user
            wp_die(
                sprintf(
                    __('SkyLearn Billing Pro activation failed: %s', 'skylearn-billing-pro'),
                    $e->getMessage()
                ),
                __('Plugin Activation Error', 'skylearn-billing-pro'),
                array('back_link' => true)
            );
        }
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
        delete_option('skylearn_billing_pro_mappings_migrated');
        
        // Remove custom database tables if they exist
        if (class_exists('SkyLearn_Billing_Pro_Database_Manager')) {
            try {
                $db_manager = new SkyLearn_Billing_Pro_Database_Manager();
                $db_manager->drop_tables();
            } catch (Exception $e) {
                error_log('SkyLearn Billing Pro: Error dropping tables during uninstall - ' . $e->getMessage());
            }
        }
        
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