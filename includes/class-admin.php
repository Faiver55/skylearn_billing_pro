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
            __('License', 'skylearn-billing-pro'),
            __('License', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-pro-license',
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
        $current_page = sanitize_text_field($_GET['page']);
        
        if ($current_page === 'skylearn-billing-pro-license') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin-licensing.php';
        } elseif ($current_page === 'skylearn-billing-pro-lms') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin-lms.php';
        } elseif ($current_page === 'skylearn-billing-pro-payments') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin-payments.php';
        } elseif ($current_page === 'skylearn-billing-pro-products') {
            $this->render_products_page();
        } elseif ($current_page === 'skylearn-billing-pro-bundles') {
            $this->render_bundles_page();
        } elseif ($current_page === 'skylearn-billing-pro-email') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/email-settings.php';
        } elseif ($current_page === 'skylearn-billing-pro-reports') {
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin-reports.php';
        } else {
            $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
            include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin-page.php';
        }
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
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
     * Sanitize options
     */
    public function sanitize_options($input) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (isset($input['general_settings'])) {
            $options['general_settings']['company_name'] = sanitize_text_field($input['general_settings']['company_name']);
            $options['general_settings']['company_email'] = sanitize_email($input['general_settings']['company_email']);
            $options['general_settings']['currency'] = sanitize_text_field($input['general_settings']['currency']);
            $options['general_settings']['test_mode'] = isset($input['general_settings']['test_mode']) ? true : false;
        }
        
        if (isset($input['lms_settings'])) {
            $options['lms_settings']['active_lms'] = sanitize_text_field($input['lms_settings']['active_lms']);
            $options['lms_settings']['auto_enroll'] = isset($input['lms_settings']['auto_enroll']) ? true : false;
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
        
        $licensing_manager = skylearn_billing_pro_licensing();
        
        // Check if license is expired
        if ($licensing_manager->is_license_expired()) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>' . esc_html__('Skylearn Billing Pro:', 'skylearn-billing-pro') . '</strong> ';
            echo esc_html__('Your license has expired. Please renew your license to continue using Pro features.', 'skylearn-billing-pro');
            echo ' <a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')) . '">' . esc_html__('Manage License', 'skylearn-billing-pro') . '</a></p>';
            echo '</div>';
        }
        
        // Check if license expires soon (within 7 days)
        $days_until_expiry = $licensing_manager->get_days_until_expiry();
        if ($days_until_expiry !== false && $days_until_expiry > 0 && $days_until_expiry <= 7) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('Skylearn Billing Pro:', 'skylearn-billing-pro') . '</strong> ';
            echo sprintf(
                esc_html__('Your license expires in %d days. Please renew to avoid service interruption.', 'skylearn-billing-pro'),
                $days_until_expiry
            );
            echo ' <a href="' . esc_url($licensing_manager->get_upgrade_url()) . '" target="_blank">' . esc_html__('Renew License', 'skylearn-billing-pro') . '</a></p>';
            echo '</div>';
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
        
        $lms_manager = skylearn_billing_pro_lms_manager();
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
}