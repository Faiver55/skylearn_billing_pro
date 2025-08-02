<?php
/**
 * Admin functionality for Skylearn Billing Pro
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
 * Admin class for handling WordPress admin interface
 */
class SkyLearn_Billing_Pro_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Add admin menu items
     */
    public function add_admin_menu() {
        // Add main menu page
        add_menu_page(
            __('Skylearn Billing', 'skylearn-billing-pro'),
            __('Skylearn Billing', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro',
            array($this, 'admin_page'),
            'dashicons-credit-card',
            30
        );
        
        // Add submenu pages
        add_submenu_page(
            'skylearn-billing-pro',
            __('General Settings', 'skylearn-billing-pro'),
            __('General', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('LMS Integration', 'skylearn-billing-pro'),
            __('LMS Integration', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-lms',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Payment Gateways', 'skylearn-billing-pro'),
            __('Payment Gateways', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-payments',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Products', 'skylearn-billing-pro'),
            __('Products', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-products',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Bundles', 'skylearn-billing-pro'),
            __('Bundles', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-bundles',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Email Settings', 'skylearn-billing-pro'),
            __('Email Settings', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-email',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Reports & Analytics', 'skylearn-billing-pro'),
            __('Reports', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-reports',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Subscriptions', 'skylearn-billing-pro'),
            __('Subscriptions', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-subscriptions',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Memberships', 'skylearn-billing-pro'),
            __('Memberships', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-memberships',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Loyalty & Rewards', 'skylearn-billing-pro'),
            __('Loyalty', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-loyalty',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Automation & Integrations', 'skylearn-billing-pro'),
            __('Automation', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-automation',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Addons & Extensions', 'skylearn-billing-pro'),
            __('Addons', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-addons',
            array($this, 'admin_page')
        );
        
        // Security and compliance menu items
        add_submenu_page(
            'skylearn-billing-pro',
            __('Audit Logs', 'skylearn-billing-pro'),
            __('Audit Logs', 'skylearn-billing-pro'),
            'skylearn_view_logs',
            'skylearn-billing-pro-audit-logs',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Privacy Settings', 'skylearn-billing-pro'),
            __('Privacy', 'skylearn-billing-pro'),
            'skylearn_manage_privacy',
            'skylearn-billing-pro-privacy',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Status & Logs', 'skylearn-billing-pro'),
            __('Status', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-status',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('License', 'skylearn-billing-pro'),
            __('License', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-license',
            array($this, 'admin_page')
        );
        
        add_submenu_page(
            'skylearn-billing-pro',
            __('Help & Support', 'skylearn-billing-pro'),
            __('Help', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-help',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'skylearn-billing-pro') === false) {
            return;
        }
        
        wp_enqueue_style(
            'skylearn-billing-pro-admin',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            SKYLEARN_BILLING_PRO_VERSION
        );
        
        // Enqueue course mapping styles on LMS page
        if (strpos($hook, 'skylearn-billing-pro-lms') !== false) {
            wp_enqueue_style(
                'skylearn-billing-pro-course-mapping',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/course-mapping.css',
                array('skylearn-billing-pro-admin'),
                SKYLEARN_BILLING_PRO_VERSION
            );
        }
        
        // Enqueue reports styles and scripts if on reports page
        if (strpos($hook, 'skylearn-billing-pro-reports') !== false) {
            wp_enqueue_style(
                'skylearn-billing-pro-reports',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/reports.css',
                array('skylearn-billing-pro-admin'),
                SKYLEARN_BILLING_PRO_VERSION
            );
            
            // Enqueue Chart.js from CDN
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js',
                array(),
                '3.9.1',
                true
            );
            
            wp_enqueue_script(
                'skylearn-billing-pro-reports',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/reports.js',
                array('jquery', 'chart-js'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            // Localize script with nonces
            wp_localize_script('skylearn-billing-pro-reports', 'skylearn_admin_nonces', array(
                'skylearn_reporting_data' => wp_create_nonce('skylearn_reporting_nonce'),
                'skylearn_export_report' => wp_create_nonce('skylearn_export_nonce'),
                'ajaxurl' => admin_url('admin-ajax.php')
            ));
        }
        
        wp_enqueue_script(
            'skylearn-billing-pro-admin',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            SKYLEARN_BILLING_PRO_VERSION,
            true
        );
        
        // Enqueue course mapping scripts on LMS page
        if (strpos($hook, 'skylearn-billing-pro-lms') !== false) {
            wp_enqueue_script(
                'skylearn-billing-pro-course-mapping',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/course-mapping.js',
                array('jquery'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            // Localize script with course mapping data
            wp_localize_script('skylearn-billing-pro-course-mapping', 'skylernCourseMappingData', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skylearn_course_mapping_nonce'),
                'strings' => array(
                    'saving' => __('Saving...', 'skylearn-billing-pro'),
                    'deleting' => __('Deleting...', 'skylearn-billing-pro'),
                    'addMapping' => __('Add Mapping', 'skylearn-billing-pro'),
                    'delete' => __('Delete', 'skylearn-billing-pro'),
                    'confirmDelete' => __('Are you sure you want to delete this mapping? This action cannot be undone.', 'skylearn-billing-pro'),
                    'noCoursesAvailable' => __('No Courses Available', 'skylearn-billing-pro'),
                    'mappingSaved' => __('Course mapping saved successfully!', 'skylearn-billing-pro'),
                    'mappingDeleted' => __('Mapping deleted successfully.', 'skylearn-billing-pro'),
                    'networkError' => __('Network error occurred. Please check your connection and try again.', 'skylearn-billing-pro'),
                    'permissionDenied' => __('Permission denied. Please refresh the page and try again.', 'skylearn-billing-pro'),
                    'serverError' => __('Server error occurred. Please contact support if this persists.', 'skylearn-billing-pro'),
                    'connectionLost' => __('Connection lost. Please check your internet connection.', 'skylearn-billing-pro')
                )
            ));
        }
        
        // Enqueue licensing scripts on license page
        if (strpos($hook, 'skylearn-billing-pro-license') !== false) {
            wp_enqueue_script(
                'skylearn-billing-pro-licensing',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/licensing.js',
                array('jquery'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            wp_localize_script('skylearn-billing-pro-licensing', 'skylernLicensing', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skylearn_license_nonce'),
                'strings' => array(
                    'validating' => __('Validating license...', 'skylearn-billing-pro'),
                    'deactivating' => __('Deactivating license...', 'skylearn-billing-pro'),
                    'confirm_deactivate' => __('Are you sure you want to deactivate your license?', 'skylearn-billing-pro')
                )
            ));
        }
    }
    
    /**
     * Render admin page
     */
    public function admin_page() {
        // Add error handling for admin page rendering
        try {
            $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
            
            // Check if onboarding should be shown
            if (isset($_GET['onboarding']) && $_GET['onboarding'] === '1') {
                $this->safe_include_template('templates/admin/onboarding.php', 'Onboarding page');
                return;
            }
            
            // Check if help page is requested
            if ($current_page === 'skylearn-billing-pro-help') {
                $this->safe_include_template('templates/admin/help.php', 'Help page');
                return;
            }
            
            if ($current_page === 'skylearn-billing-pro-license') {
                $this->safe_include_template('templates/admin-licensing.php', 'License page');
            } elseif ($current_page === 'skylearn-billing-pro-lms') {
                $this->safe_include_template('templates/admin-lms.php', 'LMS Integration page');
            } elseif ($current_page === 'skylearn-billing-pro-payments') {
                $this->safe_include_template('templates/admin-payments.php', 'Payment Gateways page');
            } elseif ($current_page === 'skylearn-billing-pro-products') {
                $this->render_products_page();
            } elseif ($current_page === 'skylearn-billing-pro-bundles') {
                $this->render_bundles_page();
            } elseif ($current_page === 'skylearn-billing-pro-subscriptions') {
                $this->render_subscriptions_page();
            } elseif ($current_page === 'skylearn-billing-pro-memberships') {
                $this->render_memberships_page();
            } elseif ($current_page === 'skylearn-billing-pro-loyalty') {
                $this->render_loyalty_page();
            } elseif ($current_page === 'skylearn-billing-pro-automation') {
                $this->render_automation_page();
            } elseif ($current_page === 'skylearn-billing-pro-addons') {
                $this->safe_include_template('templates/admin/addons.php', 'Addons page');
            } elseif ($current_page === 'skylearn-billing-pro-audit-logs') {
                // Check capability before including
                if (current_user_can('skylearn_view_logs')) {
                    $this->safe_include_template('templates/admin/audit-log.php', 'Audit logs page');
                } else {
                    wp_die(__('You do not have sufficient permissions to access this page.', 'skylearn-billing-pro'));
                }
            } elseif ($current_page === 'skylearn-billing-pro-privacy') {
                // Check capability before including
                if (current_user_can('skylearn_manage_privacy')) {
                    $this->safe_include_template('templates/admin/privacy-settings.php', 'Privacy settings page');
                } else {
                    wp_die(__('You do not have sufficient permissions to access this page.', 'skylearn-billing-pro'));
                }
            } elseif ($current_page === 'skylearn-billing-pro-status') {
                $this->safe_include_template('templates/admin/status.php', 'Status page');
            } elseif ($current_page === 'skylearn-billing-pro-email') {
                $this->safe_include_template('templates/admin/email-settings.php', 'Email settings page');
            } elseif ($current_page === 'skylearn-billing-pro-reports') {
                $this->safe_include_template('templates/admin-reports.php', 'Reports page');
            } else {
                $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
                $this->safe_include_template('templates/admin-page.php', 'Main admin page');
            }
            
        } catch (Error $e) {
            $this->handle_admin_error('Fatal error in admin page rendering', $e);
        } catch (Exception $e) {
            $this->handle_admin_error('Exception in admin page rendering', $e);
        }
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Handle settings form submission
        $this->handle_settings_submission();
        
        // Register settings
        register_setting('skylearn_billing_pro_general', 'skylearn_billing_pro_options', array($this, 'sanitize_options'));
        register_setting('skylearn_billing_pro_lms', 'skylearn_billing_pro_options', array($this, 'sanitize_options'));
        
        // Add settings sections
        add_settings_section(
            'skylearn_billing_pro_general_section',
            __('General Settings', 'skylearn-billing-pro'),
            array($this, 'general_section_callback'),
            'skylearn_billing_pro_general'
        );
        
        // Add LMS settings section
        add_settings_section(
            'skylearn_billing_pro_lms_section',
            __('LMS Settings', 'skylearn-billing-pro'),
            array($this, 'lms_section_callback'),
            'skylearn_billing_pro_lms'
        );
        
        // Add settings fields
        add_settings_field(
            'company_name',
            __('Company Name', 'skylearn-billing-pro'),
            array($this, 'company_name_callback'),
            'skylearn_billing_pro_general',
            'skylearn_billing_pro_general_section'
        );
        
        add_settings_field(
            'company_email',
            __('Company Email', 'skylearn-billing-pro'),
            array($this, 'company_email_callback'),
            'skylearn_billing_pro_general',
            'skylearn_billing_pro_general_section'
        );
        
        add_settings_field(
            'currency',
            __('Default Currency', 'skylearn-billing-pro'),
            array($this, 'currency_callback'),
            'skylearn_billing_pro_general',
            'skylearn_billing_pro_general_section'
        );
        
        add_settings_field(
            'test_mode',
            __('Test Mode', 'skylearn-billing-pro'),
            array($this, 'test_mode_callback'),
            'skylearn_billing_pro_general',
            'skylearn_billing_pro_general_section'
        );
        
        // LMS settings fields
        add_settings_field(
            'active_lms',
            __('Active LMS', 'skylearn-billing-pro'),
            array($this, 'active_lms_callback'),
            'skylearn_billing_pro_lms',
            'skylearn_billing_pro_lms_section'
        );
        
        add_settings_field(
            'auto_enroll',
            __('Auto Enrollment', 'skylearn-billing-pro'),
            array($this, 'auto_enroll_callback'),
            'skylearn_billing_pro_lms',
            'skylearn_billing_pro_lms_section'
        );
    }
    
    /**
     * Handle settings form submission
     */
    private function handle_settings_submission() {
        // Check if this is a settings form submission
        if (!isset($_POST['submit']) || !isset($_POST['option_page'])) {
            return;
        }
        
        // Verify this is our settings page
        $valid_option_pages = array('skylearn_billing_pro_general', 'skylearn_billing_pro_lms');
        if (!in_array($_POST['option_page'], $valid_option_pages)) {
            return;
        }
        
        // Verify nonce
        $nonce_field = $_POST['option_page'] . '-options';
        if (!wp_verify_nonce($_POST['_wpnonce'], $nonce_field)) {
            add_settings_error(
                'skylearn_billing_pro_options',
                'nonce_failed',
                __('Security verification failed. Please try again.', 'skylearn-billing-pro'),
                'error'
            );
            return;
        }
        
        // Check user permissions
        if (!current_user_can('manage_options')) {
            add_settings_error(
                'skylearn_billing_pro_options',
                'permission_denied',
                __('You do not have permission to modify these settings.', 'skylearn-billing-pro'),
                'error'
            );
            return;
        }
        
        try {
            // Process and save settings
            $input = $_POST['skylearn_billing_pro_options'] ?? array();
            $sanitized_options = $this->sanitize_options($input);
            
            // Update the options
            $result = update_option('skylearn_billing_pro_options', $sanitized_options);
            
            if ($result !== false) {
                add_settings_error(
                    'skylearn_billing_pro_options',
                    'settings_saved',
                    __('Settings have been saved successfully.', 'skylearn-billing-pro'),
                    'success'
                );
                
                // Log successful settings update
                error_log('SkyLearn Billing Pro: Settings updated successfully by user ' . get_current_user_id());
            } else {
                add_settings_error(
                    'skylearn_billing_pro_options',
                    'settings_save_failed',
                    __('Settings could not be saved. Please try again.', 'skylearn-billing-pro'),
                    'error'
                );
                
                // Log failed settings update
                error_log('SkyLearn Billing Pro: Settings update failed for user ' . get_current_user_id());
            }
        } catch (Exception $e) {
            add_settings_error(
                'skylearn_billing_pro_options',
                'settings_exception',
                sprintf(__('An error occurred while saving settings: %s', 'skylearn-billing-pro'), $e->getMessage()),
                'error'
            );
            
            // Log the exception
            error_log('SkyLearn Billing Pro: Settings save exception - ' . $e->getMessage());
        }
    }
    
    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        // Get existing options as base
        $options = get_option('skylearn_billing_pro_options', array());
        
        // Ensure we have the proper structure
        if (!is_array($options)) {
            $options = array();
        }
        
        // Initialize default structure if not present
        if (!isset($options['general_settings'])) {
            $options['general_settings'] = array();
        }
        if (!isset($options['lms_settings'])) {
            $options['lms_settings'] = array();
        }
        
        try {
            // Sanitize general settings
            if (isset($input['general_settings']) && is_array($input['general_settings'])) {
                if (isset($input['general_settings']['company_name'])) {
                    $options['general_settings']['company_name'] = sanitize_text_field($input['general_settings']['company_name']);
                }
                
                if (isset($input['general_settings']['company_email'])) {
                    $email = sanitize_email($input['general_settings']['company_email']);
                    if (!is_email($email)) {
                        add_settings_error(
                            'skylearn_billing_pro_options',
                            'invalid_email',
                            __('Please enter a valid email address.', 'skylearn-billing-pro'),
                            'error'
                        );
                        // Keep the old value
                    } else {
                        $options['general_settings']['company_email'] = $email;
                    }
                }
                
                if (isset($input['general_settings']['currency'])) {
                    $allowed_currencies = array('USD', 'EUR', 'GBP', 'CAD', 'AUD');
                    $currency = sanitize_text_field($input['general_settings']['currency']);
                    if (in_array($currency, $allowed_currencies)) {
                        $options['general_settings']['currency'] = $currency;
                    } else {
                        add_settings_error(
                            'skylearn_billing_pro_options',
                            'invalid_currency',
                            __('Please select a valid currency.', 'skylearn-billing-pro'),
                            'error'
                        );
                    }
                }
                
                // Test mode checkbox
                $options['general_settings']['test_mode'] = isset($input['general_settings']['test_mode']) ? true : false;
            }
            
            // Sanitize LMS settings
            if (isset($input['lms_settings']) && is_array($input['lms_settings'])) {
                if (isset($input['lms_settings']['active_lms'])) {
                    $options['lms_settings']['active_lms'] = sanitize_text_field($input['lms_settings']['active_lms']);
                }
                
                // Auto enroll checkbox
                $options['lms_settings']['auto_enroll'] = isset($input['lms_settings']['auto_enroll']) ? true : false;
            }
            
            // Log successful sanitization
            error_log('SkyLearn Billing Pro: Options sanitized successfully');
            
        } catch (Exception $e) {
            add_settings_error(
                'skylearn_billing_pro_options',
                'sanitization_error',
                sprintf(__('Error processing settings: %s', 'skylearn-billing-pro'), $e->getMessage()),
                'error'
            );
            
            error_log('SkyLearn Billing Pro: Options sanitization failed - ' . $e->getMessage());
        }
        
        return $options;
    }
    
    /**
     * General section callback
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure the general settings for Skylearn Billing Pro.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Company name field callback
     */
    public function company_name_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['general_settings']['company_name']) ? $options['general_settings']['company_name'] : '';
        echo '<input type="text" name="skylearn_billing_pro_options[general_settings][company_name]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Enter your company name as it will appear on invoices and emails.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Company email field callback
     */
    public function company_email_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['general_settings']['company_email']) ? $options['general_settings']['company_email'] : get_option('admin_email');
        echo '<input type="email" name="skylearn_billing_pro_options[general_settings][company_email]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">' . esc_html__('Email address for billing notifications and correspondence.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Currency field callback
     */
    public function currency_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['general_settings']['currency']) ? $options['general_settings']['currency'] : 'USD';
        
        $currencies = array(
            'USD' => __('US Dollar (USD)', 'skylearn-billing-pro'),
            'EUR' => __('Euro (EUR)', 'skylearn-billing-pro'),
            'GBP' => __('British Pound (GBP)', 'skylearn-billing-pro'),
            'CAD' => __('Canadian Dollar (CAD)', 'skylearn-billing-pro'),
            'AUD' => __('Australian Dollar (AUD)', 'skylearn-billing-pro'),
        );
        
        echo '<select name="skylearn_billing_pro_options[general_settings][currency]">';
        foreach ($currencies as $code => $name) {
            echo '<option value="' . esc_attr($code) . '"' . selected($value, $code, false) . '>' . esc_html($name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Default currency for billing and payments.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Test mode field callback
     */
    public function test_mode_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['general_settings']['test_mode']) ? $options['general_settings']['test_mode'] : true;
        echo '<label>';
        echo '<input type="checkbox" name="skylearn_billing_pro_options[general_settings][test_mode]" value="1"' . checked($value, true, false) . ' />';
        echo ' ' . esc_html__('Enable test mode', 'skylearn-billing-pro');
        echo '</label>';
        echo '<p class="description">' . esc_html__('When enabled, all payments will be processed in test mode.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Only show on our admin pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'skylearn-billing-pro') === false) {
            return;
        }
        
        // Display settings errors/success messages
        settings_errors('skylearn_billing_pro_options');
        
        // Skip license notices if licensing manager is not available
        if (!function_exists('skylearn_billing_pro_licensing')) {
            return;
        }
        
        try {
            $licensing_manager = skylearn_billing_pro_licensing();
            
            // Check if license is expired
            if (method_exists($licensing_manager, 'is_license_expired') && $licensing_manager->is_license_expired()) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html__('Skylearn Billing Pro:', 'skylearn-billing-pro') . '</strong> ';
                echo esc_html__('Your license has expired. Please renew your license to continue using Pro features.', 'skylearn-billing-pro');
                echo ' <a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')) . '">' . esc_html__('Manage License', 'skylearn-billing-pro') . '</a></p>';
                echo '</div>';
            }
            
            // Check if license expires soon (within 7 days)
            if (method_exists($licensing_manager, 'get_days_until_expiry')) {
                $days_until_expiry = $licensing_manager->get_days_until_expiry();
                if ($days_until_expiry !== false && $days_until_expiry > 0 && $days_until_expiry <= 7) {
                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p><strong>' . esc_html__('Skylearn Billing Pro:', 'skylearn-billing-pro') . '</strong> ';
                    echo sprintf(
                        esc_html__('Your license expires in %d days. Please renew to avoid service interruption.', 'skylearn-billing-pro'),
                        $days_until_expiry
                    );
                    if (method_exists($licensing_manager, 'get_upgrade_url')) {
                        echo ' <a href="' . esc_url($licensing_manager->get_upgrade_url()) . '" target="_blank">' . esc_html__('Renew License', 'skylearn-billing-pro') . '</a>';
                    }
                    echo '</p>';
                    echo '</div>';
                }
            }
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: License manager error in admin notices - ' . $e->getMessage());
        }
    }
    
    /**
     * LMS section callback
     */
    public function lms_section_callback() {
        echo '<p>' . esc_html__('Configure LMS integration settings for course enrollment.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Active LMS field callback
     */
    public function active_lms_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['lms_settings']['active_lms']) ? $options['lms_settings']['active_lms'] : '';
        
        // Check if LMS manager function exists
        if (!function_exists('skylearn_billing_pro_lms_manager')) {
            echo '<p class="description" style="color: #d63638;">' . esc_html__('LMS Manager is not available. Please check plugin configuration.', 'skylearn-billing-pro') . '</p>';
            echo '<input type="hidden" name="skylearn_billing_pro_options[lms_settings][active_lms]" value="" />';
            return;
        }
        
        try {
            $lms_manager = skylearn_billing_pro_lms_manager();
            
            if (!method_exists($lms_manager, 'get_detected_lms')) {
                echo '<p class="description" style="color: #d63638;">' . esc_html__('LMS detection not available. Please check plugin configuration.', 'skylearn-billing-pro') . '</p>';
                echo '<input type="hidden" name="skylearn_billing_pro_options[lms_settings][active_lms]" value="' . esc_attr($value) . '" />';
                return;
            }
            
            $detected_lms = $lms_manager->get_detected_lms();
            
            if (empty($detected_lms)) {
                echo '<p class="description" style="color: #d63638;">' . esc_html__('No LMS plugins detected. Please install and activate a supported LMS plugin.', 'skylearn-billing-pro') . '</p>';
                echo '<input type="hidden" name="skylearn_billing_pro_options[lms_settings][active_lms]" value="" />';
                return;
            }
            
            echo '<select name="skylearn_billing_pro_options[lms_settings][active_lms]">';
            echo '<option value="">' . esc_html__('Select an LMS...', 'skylearn-billing-pro') . '</option>';
            foreach ($detected_lms as $lms_key => $lms_data) {
                echo '<option value="' . esc_attr($lms_key) . '"' . selected($value, $lms_key, false) . '>' . esc_html($lms_data['name']) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">' . esc_html__('Select the LMS to use for course enrollment. Only one LMS can be active at a time.', 'skylearn-billing-pro') . '</p>';
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: LMS manager error in active_lms_callback - ' . $e->getMessage());
            echo '<p class="description" style="color: #d63638;">' . esc_html__('Error loading LMS options. Please check error logs.', 'skylearn-billing-pro') . '</p>';
            echo '<input type="text" name="skylearn_billing_pro_options[lms_settings][active_lms]" value="' . esc_attr($value) . '" class="regular-text" />';
            echo '<p class="description">' . esc_html__('Manual LMS selection (enter LMS key).', 'skylearn-billing-pro') . '</p>';
        }
    }
    
    /**
     * Auto enroll field callback
     */
    public function auto_enroll_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['lms_settings']['auto_enroll']) ? $options['lms_settings']['auto_enroll'] : true;
        echo '<label>';
        echo '<input type="checkbox" name="skylearn_billing_pro_options[lms_settings][auto_enroll]" value="1"' . checked($value, true, false) . ' />';
        echo ' ' . esc_html__('Enable automatic course enrollment', 'skylearn-billing-pro');
        echo '</label>';
        echo '<p class="description">' . esc_html__('When enabled, users will be automatically enrolled in mapped courses after successful payment.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Render products page
     */
    public function render_products_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        
        if ($action === 'edit' || $action === 'add') {
            $product_ui = skylearn_billing_pro_product_ui();
            $product_ui->render_product_edit();
        } elseif ($tab === 'import-export') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/products-import-export.php';
        } else {
            $product_ui = skylearn_billing_pro_product_ui();
            $product_ui->render_products_list();
        }
    }
    
    /**
     * Render bundles page
     */
    public function render_bundles_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        
        if ($action === 'edit' || $action === 'add') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/bundle-edit.php';
        } else {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/bundles-list.php';
        }
    }
    
    /**
     * Render subscriptions page
     */
    public function render_subscriptions_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'list';
        
        if ($action === 'view') {
            $this->render_subscription_details();
        } elseif ($tab === 'plans') {
            $this->render_subscription_plans();
        } elseif ($tab === 'settings') {
            $this->render_subscription_settings();
        } else {
            $this->render_subscriptions_list();
        }
    }
    
    /**
     * Render subscriptions list
     */
    private function render_subscriptions_list() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Subscriptions', 'skylearn-billing-pro') . '</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=skylearn-billing-pro-subscriptions&tab=list" class="nav-tab nav-tab-active">' . esc_html__('All Subscriptions', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-subscriptions&tab=plans" class="nav-tab">' . esc_html__('Plans', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-subscriptions&tab=settings" class="nav-tab">' . esc_html__('Settings', 'skylearn-billing-pro') . '</a>';
        echo '</nav>';
        
        echo '<div class="subscription-stats" style="margin: 20px 0;">';
        echo '<div class="stat-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';
        
        // Get subscription statistics
        $users = get_users(array('meta_key' => 'skylearn_billing_subscriptions'));
        $total_subscriptions = 0;
        $active_subscriptions = 0;
        $paused_subscriptions = 0;
        $cancelled_subscriptions = 0;
        
        foreach ($users as $user) {
            $subscriptions = skylearn_billing_pro_subscription_manager()->get_user_subscriptions($user->ID);
            $total_subscriptions += count($subscriptions);
            
            foreach ($subscriptions as $subscription) {
                switch ($subscription['status']) {
                    case 'active':
                        $active_subscriptions++;
                        break;
                    case 'paused':
                        $paused_subscriptions++;
                        break;
                    case 'cancelled':
                        $cancelled_subscriptions++;
                        break;
                }
            }
        }
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #333;">' . number_format($total_subscriptions) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Total Subscriptions', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #10b981;">' . number_format($active_subscriptions) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Active Subscriptions', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #f59e0b;">' . number_format($paused_subscriptions) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Paused Subscriptions', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #ef4444;">' . number_format($cancelled_subscriptions) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Cancelled Subscriptions', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Subscriptions table
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('User', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Plan', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Status', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Amount', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Next Payment', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Actions', 'skylearn-billing-pro') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        if (!empty($users)) {
            foreach ($users as $user) {
                $subscriptions = skylearn_billing_pro_subscription_manager()->get_user_subscriptions($user->ID);
                foreach ($subscriptions as $subscription) {
                    echo '<tr>';
                    echo '<td>' . esc_html($user->display_name) . '<br><small>' . esc_html($user->user_email) . '</small></td>';
                    echo '<td>' . esc_html($subscription['plan_id']) . '<br><small>Tier: ' . esc_html(ucfirst($subscription['tier'])) . '</small></td>';
                    echo '<td><span class="status-badge status-' . esc_attr($subscription['status']) . '">' . esc_html(ucfirst($subscription['status'])) . '</span></td>';
                    echo '<td>' . esc_html($subscription['currency']) . ' ' . esc_html(number_format($subscription['amount'], 2)) . '</td>';
                    echo '<td>';
                    if ($subscription['status'] === 'active' && !empty($subscription['next_payment_date'])) {
                        echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription['next_payment_date'])));
                    } else {
                        echo '—';
                    }
                    echo '</td>';
                    echo '<td>';
                    echo '<a href="?page=skylearn-billing-pro-subscriptions&action=view&id=' . esc_attr($subscription['id']) . '" class="button button-small">' . esc_html__('View', 'skylearn-billing-pro') . '</a>';
                    echo '</td>';
                    echo '</tr>';
                }
            }
        } else {
            echo '<tr><td colspan="6">' . esc_html__('No subscriptions found.', 'skylearn-billing-pro') . '</td></tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render subscription plans
     */
    private function render_subscription_plans() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Subscription Plans', 'skylearn-billing-pro') . '</h1>';
        echo '<p>' . esc_html__('Manage your subscription plans and pricing tiers.', 'skylearn-billing-pro') . '</p>';
        echo '<div class="notice notice-info"><p>' . esc_html__('Subscription plans management is coming soon. For now, plans are managed through the available_plans filter.', 'skylearn-billing-pro') . '</p></div>';
        echo '</div>';
    }
    
    /**
     * Render subscription settings
     */
    private function render_subscription_settings() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Subscription Settings', 'skylearn-billing-pro') . '</h1>';
        echo '<p>' . esc_html__('Configure subscription-related settings.', 'skylearn-billing-pro') . '</p>';
        echo '<div class="notice notice-info"><p>' . esc_html__('Subscription settings management is coming soon.', 'skylearn-billing-pro') . '</p></div>';
        echo '</div>';
    }
    
    /**
     * Render memberships page
     */
    public function render_memberships_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'levels';
        
        if ($tab === 'members') {
            $this->render_members_list();
        } elseif ($tab === 'settings') {
            $this->render_membership_settings();
        } else {
            $this->render_membership_levels();
        }
    }
    
    /**
     * Render membership levels
     */
    private function render_membership_levels() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Membership Levels', 'skylearn-billing-pro') . '</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=skylearn-billing-pro-memberships&tab=levels" class="nav-tab nav-tab-active">' . esc_html__('Levels', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-memberships&tab=members" class="nav-tab">' . esc_html__('Members', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-memberships&tab=settings" class="nav-tab">' . esc_html__('Settings', 'skylearn-billing-pro') . '</a>';
        echo '</nav>';
        
        $membership_levels = skylearn_billing_pro_membership_manager()->get_membership_levels();
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Level', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Description', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Priority', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Course Limit', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Download Limit', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Support Level', 'skylearn-billing-pro') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        foreach ($membership_levels as $level_id => $level) {
            echo '<tr>';
            echo '<td><strong>' . esc_html($level['name']) . '</strong><br><small>' . esc_html($level_id) . '</small></td>';
            echo '<td>' . esc_html($level['description']) . '</td>';
            echo '<td>' . esc_html($level['priority']) . '</td>';
            echo '<td>' . ($level['restrictions']['course_limit'] === -1 ? esc_html__('Unlimited', 'skylearn-billing-pro') : esc_html($level['restrictions']['course_limit'])) . '</td>';
            echo '<td>' . ($level['restrictions']['download_limit'] === -1 ? esc_html__('Unlimited', 'skylearn-billing-pro') : esc_html($level['restrictions']['download_limit'])) . '</td>';
            echo '<td>' . esc_html(ucfirst(str_replace('_', ' ', $level['restrictions']['support_level']))) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render members list
     */
    private function render_members_list() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Members', 'skylearn-billing-pro') . '</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=skylearn-billing-pro-memberships&tab=levels" class="nav-tab">' . esc_html__('Levels', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-memberships&tab=members" class="nav-tab nav-tab-active">' . esc_html__('Members', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-memberships&tab=settings" class="nav-tab">' . esc_html__('Settings', 'skylearn-billing-pro') . '</a>';
        echo '</nav>';
        
        $users = get_users(array('meta_key' => 'skylearn_billing_membership_level'));
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('User', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Membership Level', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Assigned Date', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Actions', 'skylearn-billing-pro') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        if (!empty($users)) {
            foreach ($users as $user) {
                $membership_data = skylearn_billing_pro_membership_manager()->get_user_membership_data($user->ID);
                echo '<tr>';
                echo '<td>' . esc_html($user->display_name) . '<br><small>' . esc_html($user->user_email) . '</small></td>';
                echo '<td>' . esc_html($membership_data['level_name']) . '<br><small>' . esc_html($membership_data['level_id']) . '</small></td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($membership_data['assigned_at']))) . '</td>';
                echo '<td><a href="' . esc_url(get_edit_user_link($user->ID)) . '" class="button button-small">' . esc_html__('Edit User', 'skylearn-billing-pro') . '</a></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="4">' . esc_html__('No members found.', 'skylearn-billing-pro') . '</td></tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render loyalty page
     */
    public function render_loyalty_page() {
        $tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
        
        if ($tab === 'rewards') {
            $this->render_loyalty_rewards();
        } elseif ($tab === 'settings') {
            $this->render_loyalty_settings();
        } else {
            $this->render_loyalty_overview();
        }
    }
    
    /**
     * Render loyalty overview
     */
    private function render_loyalty_overview() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Loyalty & Rewards', 'skylearn-billing-pro') . '</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=skylearn-billing-pro-loyalty&tab=overview" class="nav-tab nav-tab-active">' . esc_html__('Overview', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-loyalty&tab=rewards" class="nav-tab">' . esc_html__('Rewards', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-loyalty&tab=settings" class="nav-tab">' . esc_html__('Settings', 'skylearn-billing-pro') . '</a>';
        echo '</nav>';
        
        // Get loyalty statistics
        $users = get_users(array('meta_key' => 'skylearn_billing_loyalty_points'));
        $total_points_awarded = 0;
        $total_points_redeemed = 0;
        $active_members = count($users);
        
        foreach ($users as $user) {
            $current_points = skylearn_billing_pro_loyalty()->get_user_points($user->ID);
            $history = skylearn_billing_pro_loyalty()->get_user_points_history($user->ID);
            
            foreach ($history as $transaction) {
                if ($transaction['points'] > 0) {
                    $total_points_awarded += $transaction['points'];
                } else {
                    $total_points_redeemed += abs($transaction['points']);
                }
            }
        }
        
        echo '<div class="loyalty-stats" style="margin: 20px 0;">';
        echo '<div class="stat-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">';
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #333;">' . number_format($active_members) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Active Members', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #10b981;">' . number_format($total_points_awarded) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Points Awarded', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #ef4444;">' . number_format($total_points_redeemed) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Points Redeemed', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '<div class="stat-card" style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
        echo '<h3 style="margin: 0; color: #f59e0b;">' . number_format($total_points_awarded - $total_points_redeemed) . '</h3>';
        echo '<p style="margin: 5px 0 0 0; color: #666;">' . esc_html__('Points Outstanding', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Recent activity
        echo '<h2>' . esc_html__('Recent Point Activity', 'skylearn-billing-pro') . '</h2>';
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('User', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Points', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Type', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Description', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Date', 'skylearn-billing-pro') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $recent_activity = array();
        foreach ($users as $user) {
            $history = skylearn_billing_pro_loyalty()->get_user_points_history($user->ID, 5);
            foreach ($history as $transaction) {
                $transaction['user'] = $user;
                $recent_activity[] = $transaction;
            }
        }
        
        // Sort by timestamp
        usort($recent_activity, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        $recent_activity = array_slice($recent_activity, 0, 10);
        
        if (!empty($recent_activity)) {
            foreach ($recent_activity as $activity) {
                echo '<tr>';
                echo '<td>' . esc_html($activity['user']->display_name) . '</td>';
                echo '<td style="color: ' . ($activity['points'] > 0 ? '#10b981' : '#ef4444') . ';">' . 
                     ($activity['points'] > 0 ? '+' : '') . number_format($activity['points']) . '</td>';
                echo '<td>' . esc_html(ucfirst(str_replace('_', ' ', $activity['type']))) . '</td>';
                echo '<td>' . esc_html($activity['description']) . '</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($activity['timestamp']))) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html__('No recent activity found.', 'skylearn-billing-pro') . '</td></tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render loyalty rewards
     */
    private function render_loyalty_rewards() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Loyalty Rewards', 'skylearn-billing-pro') . '</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=skylearn-billing-pro-loyalty&tab=overview" class="nav-tab">' . esc_html__('Overview', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-loyalty&tab=rewards" class="nav-tab nav-tab-active">' . esc_html__('Rewards', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-loyalty&tab=settings" class="nav-tab">' . esc_html__('Settings', 'skylearn-billing-pro') . '</a>';
        echo '</nav>';
        
        $available_rewards = skylearn_billing_pro_loyalty()->get_available_rewards();
        
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th>' . esc_html__('Reward', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Type', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Cost (Points)', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Value', 'skylearn-billing-pro') . '</th>';
        echo '<th>' . esc_html__('Status', 'skylearn-billing-pro') . '</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        if (!empty($available_rewards)) {
            foreach ($available_rewards as $reward_id => $reward) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($reward['name']) . '</strong><br><small>' . esc_html($reward['description']) . '</small></td>';
                echo '<td>' . esc_html(ucfirst(str_replace('_', ' ', $reward['type']))) . '</td>';
                echo '<td>' . number_format($reward['cost']) . '</td>';
                echo '<td>' . esc_html($reward['value']) . '</td>';
                echo '<td><span class="status-badge status-' . ($reward['active'] ? 'active' : 'inactive') . '">' . 
                     ($reward['active'] ? esc_html__('Active', 'skylearn-billing-pro') : esc_html__('Inactive', 'skylearn-billing-pro')) . '</span></td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html__('No rewards configured.', 'skylearn-billing-pro') . '</td></tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Render loyalty settings
     */
    private function render_loyalty_settings() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Loyalty Settings', 'skylearn-billing-pro') . '</h1>';
        echo '<p>' . esc_html__('Configure loyalty program settings and earning rules.', 'skylearn-billing-pro') . '</p>';
        echo '<div class="notice notice-info"><p>' . esc_html__('Loyalty settings management is coming soon.', 'skylearn-billing-pro') . '</p></div>';
        echo '</div>';
    }
    
    /**
     * Render membership settings
     */
    private function render_membership_settings() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Membership Settings', 'skylearn-billing-pro') . '</h1>';
        echo '<p>' . esc_html__('Configure membership-related settings.', 'skylearn-billing-pro') . '</p>';
        echo '<div class="notice notice-info"><p>' . esc_html__('Membership settings management is coming soon.', 'skylearn-billing-pro') . '</p></div>';
        echo '</div>';
    }
    
    /**
     * Render automation page
     */
    public function render_automation_page() {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
        
        if ($action === 'new' || $action === 'edit') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/automation.php';
        } elseif ($action === 'logs') {
            $this->render_automation_logs();
        } else {
            $this->render_automation_list();
        }
    }
    
    /**
     * Render automation list
     */
    private function render_automation_list() {
        $automation_manager = skylearn_billing_pro_automation_manager();
        $automations = $automation_manager->get_automations();
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Automation & Integrations', 'skylearn-billing-pro');
        echo ' <a href="' . esc_url(add_query_arg('action', 'new')) . '" class="page-title-action">' . esc_html__('Add New', 'skylearn-billing-pro') . '</a>';
        echo '</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=skylearn-billing-pro-automation" class="nav-tab nav-tab-active">' . esc_html__('Automations', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-automation&action=logs" class="nav-tab">' . esc_html__('Logs', 'skylearn-billing-pro') . '</a>';
        echo '</nav>';
        
        if (empty($automations)) {
            echo '<div class="notice notice-info">';
            echo '<p>' . esc_html__('No automations found. Create your first automation to get started!', 'skylearn-billing-pro') . '</p>';
            echo '</div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . esc_html__('Name', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Trigger', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Actions', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Status', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Created', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Actions', 'skylearn-billing-pro') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            $available_triggers = $automation_manager->get_available_trigger_types();
            
            foreach ($automations as $automation) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($automation['name']) . '</strong>';
                if (!empty($automation['description'])) {
                    echo '<br><small>' . esc_html($automation['description']) . '</small>';
                }
                echo '</td>';
                echo '<td>' . esc_html($available_triggers[$automation['trigger_type']] ?? $automation['trigger_type']) . '</td>';
                echo '<td>' . count($automation['actions']) . ' ' . esc_html__('actions', 'skylearn-billing-pro') . '</td>';
                echo '<td><span class="status-badge status-' . esc_attr($automation['status']) . '">' . esc_html(ucfirst($automation['status'])) . '</span></td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($automation['created_at']))) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(add_query_arg(array('action' => 'edit', 'edit' => $automation['id']))) . '">' . esc_html__('Edit', 'skylearn-billing-pro') . '</a> | ';
                echo '<a href="#" onclick="return confirm(\'' . esc_js__('Are you sure you want to delete this automation?', 'skylearn-billing-pro') . '\');">' . esc_html__('Delete', 'skylearn-billing-pro') . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render automation logs
     */
    private function render_automation_logs() {
        $automation_manager = skylearn_billing_pro_automation_manager();
        $logs = $automation_manager->get_automation_logs();
        
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Automation Logs', 'skylearn-billing-pro') . '</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=skylearn-billing-pro-automation" class="nav-tab">' . esc_html__('Automations', 'skylearn-billing-pro') . '</a>';
        echo '<a href="?page=skylearn-billing-pro-automation&action=logs" class="nav-tab nav-tab-active">' . esc_html__('Logs', 'skylearn-billing-pro') . '</a>';
        echo '</nav>';
        
        if (empty($logs)) {
            echo '<div class="notice notice-info">';
            echo '<p>' . esc_html__('No automation logs found.', 'skylearn-billing-pro') . '</p>';
            echo '</div>';
        } else {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>' . esc_html__('Automation', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Status', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Execution Time', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Triggered At', 'skylearn-billing-pro') . '</th>';
            echo '<th>' . esc_html__('Error Message', 'skylearn-billing-pro') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            
            foreach ($logs as $log) {
                echo '<tr>';
                echo '<td>' . esc_html($log['automation_name'] ?? 'Unknown') . '</td>';
                echo '<td><span class="status-badge status-' . esc_attr($log['status']) . '">' . esc_html(ucfirst($log['status'])) . '</span></td>';
                echo '<td>' . esc_html($log['execution_time_ms']) . 'ms</td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($log['triggered_at']))) . '</td>';
                echo '<td>' . ($log['error_message'] ? esc_html($log['error_message']) : '—') . '</td>';
                echo '</tr>';
            }
            
            echo '</tbody>';
            echo '</table>';
        }
        
        echo '</div>';
    }
    
    /**
     * Safely include a template file with error handling
     * 
     * @param string $template_path The template file path relative to plugin directory
     * @param string $description Human-readable description for logging
     * @return bool Whether the template was successfully included
     */
    private function safe_include_template($template_path, $description = '') {
        $full_path = SKYLEARN_BILLING_PRO_PLUGIN_DIR . $template_path;
        
        try {
            // Check if file exists
            if (!file_exists($full_path)) {
                $this->handle_template_error("Template file not found: $template_path", $description);
                return false;
            }
            
            // Check if file is readable
            if (!is_readable($full_path)) {
                $this->handle_template_error("Template file not readable: $template_path", $description);
                return false;
            }
            
            // Include the template
            include $full_path;
            return true;
            
        } catch (ParseError $e) {
            $this->handle_template_error("Parse error in template $template_path: " . $e->getMessage(), $description);
            return false;
        } catch (Error $e) {
            $this->handle_template_error("Fatal error in template $template_path: " . $e->getMessage(), $description);
            return false;
        } catch (Exception $e) {
            $this->handle_template_error("Exception in template $template_path: " . $e->getMessage(), $description);
            return false;
        }
    }
    
    /**
     * Handle template errors gracefully
     * 
     * @param string $error_message The error message
     * @param string $description Template description
     */
    private function handle_template_error($error_message, $description = '') {
        // Log the error
        error_log('SkyLearn Billing Pro Admin: ' . $error_message . ($description ? " ($description)" : ''));
        
        // Log to debug logger if available
        if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
            SkyLearn_Billing_Pro_Debug_Logger::error($error_message, $description);
        }
        
        // Display user-friendly error message
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__('SkyLearn Billing Pro Error:', 'skylearn-billing-pro') . '</strong> ';
        echo esc_html__('Unable to load the requested page. Please try again or contact support if the problem persists.', 'skylearn-billing-pro');
        echo '</p></div>';
        
        // Show basic fallback content
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('SkyLearn Billing Pro', 'skylearn-billing-pro') . '</h1>';
        echo '<p>' . esc_html__('The requested page could not be loaded due to a technical issue.', 'skylearn-billing-pro') . '</p>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro')) . '" class="button button-primary">' . 
             esc_html__('Return to Main Page', 'skylearn-billing-pro') . '</a></p>';
        echo '</div>';
    }
    
    /**
     * Handle admin errors gracefully
     * 
     * @param string $context The context where the error occurred
     * @param Throwable $error The error object
     */
    private function handle_admin_error($context, $error) {
        // Log the error
        $error_message = $context . ': ' . $error->getMessage() . ' in ' . $error->getFile() . ':' . $error->getLine();
        error_log('SkyLearn Billing Pro Admin: ' . $error_message);
        
        // Log to debug logger if available
        if (class_exists('SkyLearn_Billing_Pro_Debug_Logger')) {
            if ($error instanceof Exception) {
                SkyLearn_Billing_Pro_Debug_Logger::exception($error, $context);
            } else {
                SkyLearn_Billing_Pro_Debug_Logger::error($error_message, $context);
            }
        }
        
        // Display user-friendly error message
        echo '<div class="notice notice-error"><p>';
        echo '<strong>' . esc_html__('SkyLearn Billing Pro Error:', 'skylearn-billing-pro') . '</strong> ';
        echo esc_html__('A technical error occurred while loading this page. The error has been logged for review.', 'skylearn-billing-pro');
        echo '</p></div>';
        
        // Show basic fallback content
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('SkyLearn Billing Pro', 'skylearn-billing-pro') . '</h1>';
        echo '<p>' . esc_html__('We apologize for the inconvenience. Please try refreshing the page or contact support if the problem persists.', 'skylearn-billing-pro') . '</p>';
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro')) . '" class="button button-primary">' . 
             esc_html__('Return to Main Page', 'skylearn-billing-pro') . '</a></p>';
        echo '</div>';
    }
}