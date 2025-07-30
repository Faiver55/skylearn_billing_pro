<?php
/**
 * Advanced Reporting Addon for Skylearn Billing Pro
 *
 * Addon ID: reporting-addon
 * Addon Name: Advanced Reporting Addon
 * Description: Advanced analytics and custom reports
 * Version: 1.0.0
 * Author: Skylearn Team
 * Type: paid
 * Required Tier: pro_plus
 * Category: analytics
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
 * Advanced Reporting Addon Class
 */
class SkyLearn_Billing_Pro_Reporting_Addon {
    
    /**
     * Addon ID
     */
    const ADDON_ID = 'reporting-addon';
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Reporting_Addon
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Reporting_Addon
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
        
        // Check license eligibility
        $license_manager = skylearn_billing_pro_license_manager();
        if (!$license_manager->is_addon_accessible(self::ADDON_ID)) {
            return;
        }
        
        $this->init_hooks();
        $this->init_settings();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_skylearn_billing_generate_report', array($this, 'generate_report'));
        add_action('wp_ajax_skylearn_billing_export_report', array($this, 'export_report'));
        add_action('wp_ajax_skylearn_billing_save_report_template', array($this, 'save_report_template'));
        
        // Dashboard widget
        add_action('wp_dashboard_setup', array($this, 'add_dashboard_widget'));
        
        // Scheduled reports
        add_action('skylearn_billing_send_scheduled_report', array($this, 'send_scheduled_report'));
    }
    
    /**
     * Initialize settings
     */
    private function init_settings() {
        register_setting('skylearn_billing_reporting_addon', 'skylearn_billing_reporting_addon_settings');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'skylearn-billing-pro',
            __('Advanced Reporting', 'skylearn-billing-pro'),
            __('Advanced Reports', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-reporting-addon',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'skylearn-billing-reporting-addon') === false) {
            return;
        }
        
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js', array(), '3.9.1', true);
        
        wp_enqueue_script(
            'skylearn-billing-reporting-addon',
            SKYLEARN_BILLING_PLUGIN_URL . 'assets/js/reporting-addon-admin.js',
            array('jquery', 'chart-js'),
            SKYLEARN_BILLING_VERSION,
            true
        );
        
        wp_localize_script('skylearn-billing-reporting-addon', 'skylearn_billing_reporting_addon', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_billing_reporting_addon'),
            'strings' => array(
                'generating' => __('Generating report...', 'skylearn-billing-pro'),
                'exporting' => __('Exporting report...', 'skylearn-billing-pro'),
                'error' => __('Error generating report.', 'skylearn-billing-pro')
            )
        ));
    }
    
    /**
     * Add dashboard widget
     */
    public function add_dashboard_widget() {
        wp_add_dashboard_widget(
            'skylearn_billing_advanced_stats',
            __('Skylearn Billing - Advanced Stats', 'skylearn-billing-pro'),
            array($this, 'render_dashboard_widget')
        );
    }
    
    /**
     * Render dashboard widget
     */
    public function render_dashboard_widget() {
        $stats = $this->get_quick_stats();
        ?>
        <div class="skylearn-billing-dashboard-widget">
            <div class="widget-stat">
                <span class="stat-label"><?php esc_html_e('Monthly Revenue:', 'skylearn-billing-pro'); ?></span>
                <span class="stat-value">$<?php echo esc_html(number_format($stats['monthly_revenue'], 2)); ?></span>
            </div>
            <div class="widget-stat">
                <span class="stat-label"><?php esc_html_e('Active Subscriptions:', 'skylearn-billing-pro'); ?></span>
                <span class="stat-value"><?php echo esc_html($stats['active_subscriptions']); ?></span>
            </div>
            <div class="widget-stat">
                <span class="stat-label"><?php esc_html_e('Conversion Rate:', 'skylearn-billing-pro'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($stats['conversion_rate'], 2)); ?>%</span>
            </div>
            <div class="widget-stat">
                <span class="stat-label"><?php esc_html_e('Churn Rate:', 'skylearn-billing-pro'); ?></span>
                <span class="stat-value"><?php echo esc_html(number_format($stats['churn_rate'], 2)); ?>%</span>
            </div>
        </div>
        <?php
    }
    
    /**
     * Get quick stats for dashboard
     *
     * @return array Stats data
     */
    private function get_quick_stats() {
        // This would typically pull from your billing data
        // For now, returning mock data
        return array(
            'monthly_revenue' => 12500.00,
            'active_subscriptions' => 245,
            'conversion_rate' => 3.25,
            'churn_rate' => 1.5
        );
    }
    
    /**
     * Generate report
     */
    public function generate_report() {
        check_ajax_referer('skylearn_billing_reporting_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $filters = $_POST['filters'] ?? array();
        
        $report_data = $this->get_report_data($report_type, $date_from, $date_to, $filters);
        
        wp_send_json_success($report_data);
    }
    
    /**
     * Get report data
     *
     * @param string $report_type Report type
     * @param string $date_from Start date
     * @param string $date_to End date
     * @param array $filters Filters
     * @return array Report data
     */
    private function get_report_data($report_type, $date_from, $date_to, $filters = array()) {
        switch ($report_type) {
            case 'revenue':
                return $this->get_revenue_report($date_from, $date_to, $filters);
            case 'subscriptions':
                return $this->get_subscriptions_report($date_from, $date_to, $filters);
            case 'customers':
                return $this->get_customers_report($date_from, $date_to, $filters);
            case 'products':
                return $this->get_products_report($date_from, $date_to, $filters);
            case 'cohort':
                return $this->get_cohort_report($date_from, $date_to, $filters);
            default:
                return array();
        }
    }
    
    /**
     * Get revenue report
     *
     * @param string $date_from Start date
     * @param string $date_to End date
     * @param array $filters Filters
     * @return array Revenue report data
     */
    private function get_revenue_report($date_from, $date_to, $filters) {
        // Mock data - in real implementation, this would query your billing database
        $daily_revenue = array();
        $start = new DateTime($date_from);
        $end = new DateTime($date_to);
        $interval = new DateInterval('P1D');
        $period = new DatePeriod($start, $interval, $end);
        
        foreach ($period as $date) {
            $daily_revenue[] = array(
                'date' => $date->format('Y-m-d'),
                'revenue' => rand(200, 800),
                'orders' => rand(5, 25),
                'avg_order_value' => rand(25, 50)
            );
        }
        
        return array(
            'type' => 'revenue',
            'title' => __('Revenue Report', 'skylearn-billing-pro'),
            'data' => $daily_revenue,
            'summary' => array(
                'total_revenue' => array_sum(array_column($daily_revenue, 'revenue')),
                'total_orders' => array_sum(array_column($daily_revenue, 'orders')),
                'avg_order_value' => array_sum(array_column($daily_revenue, 'avg_order_value')) / count($daily_revenue)
            )
        );
    }
    
    /**
     * Get subscriptions report
     *
     * @param string $date_from Start date
     * @param string $date_to End date
     * @param array $filters Filters
     * @return array Subscriptions report data
     */
    private function get_subscriptions_report($date_from, $date_to, $filters) {
        // Mock data
        return array(
            'type' => 'subscriptions',
            'title' => __('Subscriptions Report', 'skylearn-billing-pro'),
            'data' => array(
                'new_subscriptions' => 45,
                'cancelled_subscriptions' => 12,
                'active_subscriptions' => 234,
                'mrr' => 15000,
                'churn_rate' => 2.3,
                'ltv' => 485.50
            ),
            'chart_data' => array(
                'labels' => array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'),
                'datasets' => array(
                    array(
                        'label' => 'New Subscriptions',
                        'data' => array(23, 34, 45, 56, 67, 45),
                        'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                        'borderColor' => 'rgba(54, 162, 235, 1)'
                    ),
                    array(
                        'label' => 'Cancelled Subscriptions',
                        'data' => array(5, 8, 12, 15, 18, 12),
                        'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                        'borderColor' => 'rgba(255, 99, 132, 1)'
                    )
                )
            )
        );
    }
    
    /**
     * Get customers report
     *
     * @param string $date_from Start date
     * @param string $date_to End date
     * @param array $filters Filters
     * @return array Customers report data
     */
    private function get_customers_report($date_from, $date_to, $filters) {
        // Mock data
        return array(
            'type' => 'customers',
            'title' => __('Customers Report', 'skylearn-billing-pro'),
            'data' => array(
                'new_customers' => 67,
                'returning_customers' => 145,
                'total_customers' => 1245,
                'customer_lifetime_value' => 485.50,
                'acquisition_cost' => 45.25
            ),
            'segments' => array(
                array('name' => 'High Value', 'count' => 23, 'percentage' => 18.5),
                array('name' => 'Medium Value', 'count' => 89, 'percentage' => 71.2),
                array('name' => 'Low Value', 'count' => 13, 'percentage' => 10.3)
            )
        );
    }
    
    /**
     * Get products report
     *
     * @param string $date_from Start date
     * @param string $date_to End date
     * @param array $filters Filters
     * @return array Products report data
     */
    private function get_products_report($date_from, $date_to, $filters) {
        // Mock data
        return array(
            'type' => 'products',
            'title' => __('Products Report', 'skylearn-billing-pro'),
            'data' => array(
                array('name' => 'Course Bundle A', 'sales' => 45, 'revenue' => 2250),
                array('name' => 'Single Course B', 'sales' => 67, 'revenue' => 1340),
                array('name' => 'Membership Plan', 'sales' => 23, 'revenue' => 1150),
                array('name' => 'Advanced Course C', 'sales' => 34, 'revenue' => 3400)
            )
        );
    }
    
    /**
     * Get cohort report
     *
     * @param string $date_from Start date
     * @param string $date_to End date
     * @param array $filters Filters
     * @return array Cohort report data
     */
    private function get_cohort_report($date_from, $date_to, $filters) {
        // Mock cohort analysis data
        return array(
            'type' => 'cohort',
            'title' => __('Cohort Analysis', 'skylearn-billing-pro'),
            'data' => array(
                'cohorts' => array(
                    array('month' => 'Jan 2024', 'customers' => 100, 'retention' => array(100, 85, 75, 68, 62, 58)),
                    array('month' => 'Feb 2024', 'customers' => 120, 'retention' => array(100, 88, 78, 72, 65)),
                    array('month' => 'Mar 2024', 'customers' => 95, 'retention' => array(100, 82, 70, 63)),
                    array('month' => 'Apr 2024', 'customers' => 110, 'retention' => array(100, 90, 80)),
                    array('month' => 'May 2024', 'customers' => 130, 'retention' => array(100, 85)),
                    array('month' => 'Jun 2024', 'customers' => 115, 'retention' => array(100))
                )
            )
        );
    }
    
    /**
     * Export report
     */
    public function export_report() {
        check_ajax_referer('skylearn_billing_reporting_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $date_from = sanitize_text_field($_POST['date_from']);
        $date_to = sanitize_text_field($_POST['date_to']);
        $format = sanitize_text_field($_POST['format']); // csv, pdf
        $filters = $_POST['filters'] ?? array();
        
        $report_data = $this->get_report_data($report_type, $date_from, $date_to, $filters);
        
        if ($format === 'csv') {
            $file_url = $this->export_to_csv($report_data, $report_type);
        } else {
            $file_url = $this->export_to_pdf($report_data, $report_type);
        }
        
        wp_send_json_success(array('file_url' => $file_url));
    }
    
    /**
     * Export report to CSV
     *
     * @param array $report_data Report data
     * @param string $report_type Report type
     * @return string File URL
     */
    private function export_to_csv($report_data, $report_type) {
        $upload_dir = wp_upload_dir();
        $filename = 'skylearn-billing-' . $report_type . '-' . date('Y-m-d-H-i-s') . '.csv';
        $file_path = $upload_dir['path'] . '/' . $filename;
        
        $file = fopen($file_path, 'w');
        
        // Write headers and data based on report type
        switch ($report_type) {
            case 'revenue':
                fputcsv($file, array('Date', 'Revenue', 'Orders', 'Avg Order Value'));
                foreach ($report_data['data'] as $row) {
                    fputcsv($file, array($row['date'], $row['revenue'], $row['orders'], $row['avg_order_value']));
                }
                break;
            case 'products':
                fputcsv($file, array('Product Name', 'Sales', 'Revenue'));
                foreach ($report_data['data'] as $row) {
                    fputcsv($file, array($row['name'], $row['sales'], $row['revenue']));
                }
                break;
            // Add more cases as needed
        }
        
        fclose($file);
        
        return $upload_dir['url'] . '/' . $filename;
    }
    
    /**
     * Export report to PDF
     *
     * @param array $report_data Report data
     * @param string $report_type Report type
     * @return string File URL
     */
    private function export_to_pdf($report_data, $report_type) {
        // For simplicity, returning a placeholder URL
        // In real implementation, you'd use a PDF library like TCPDF or mPDF
        return '';
    }
    
    /**
     * Save report template
     */
    public function save_report_template() {
        check_ajax_referer('skylearn_billing_reporting_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $template_name = sanitize_text_field($_POST['template_name']);
        $template_config = $_POST['template_config'];
        
        $settings = get_option('skylearn_billing_reporting_addon_settings', array());
        $settings['templates'] = $settings['templates'] ?? array();
        $settings['templates'][$template_name] = $template_config;
        
        update_option('skylearn_billing_reporting_addon_settings', $settings);
        
        wp_send_json_success(__('Report template saved successfully.', 'skylearn-billing-pro'));
    }
    
    /**
     * Send scheduled report
     *
     * @param array $report_config Report configuration
     */
    public function send_scheduled_report($report_config) {
        $report_data = $this->get_report_data(
            $report_config['type'],
            $report_config['date_from'],
            $report_config['date_to'],
            $report_config['filters']
        );
        
        // Generate report email
        $subject = sprintf(__('Scheduled Report: %s', 'skylearn-billing-pro'), $report_data['title']);
        $message = $this->generate_report_email($report_data);
        
        // Send to configured recipients
        foreach ($report_config['recipients'] as $email) {
            wp_mail($email, $subject, $message, array('Content-Type: text/html; charset=UTF-8'));
        }
    }
    
    /**
     * Generate report email
     *
     * @param array $report_data Report data
     * @return string Email HTML
     */
    private function generate_report_email($report_data) {
        ob_start();
        ?>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .header { background: #0073aa; color: white; padding: 20px; }
                .content { padding: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1><?php echo esc_html($report_data['title']); ?></h1>
            </div>
            <div class="content">
                <p><?php esc_html_e('Here is your scheduled report from Skylearn Billing Pro.', 'skylearn-billing-pro'); ?></p>
                
                <!-- Report content would be generated here based on report type -->
                
                <p><?php esc_html_e('This is an automated email. Please do not reply.', 'skylearn-billing-pro'); ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Advanced Reporting', 'skylearn-billing-pro'); ?></h1>
            
            <div class="skylearn-billing-reporting">
                <div class="report-controls">
                    <h2><?php esc_html_e('Generate Report', 'skylearn-billing-pro'); ?></h2>
                    
                    <form id="report-generator-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="report-type"><?php esc_html_e('Report Type', 'skylearn-billing-pro'); ?></label></th>
                                <td>
                                    <select id="report-type" name="report_type">
                                        <option value="revenue"><?php esc_html_e('Revenue Report', 'skylearn-billing-pro'); ?></option>
                                        <option value="subscriptions"><?php esc_html_e('Subscriptions Report', 'skylearn-billing-pro'); ?></option>
                                        <option value="customers"><?php esc_html_e('Customers Report', 'skylearn-billing-pro'); ?></option>
                                        <option value="products"><?php esc_html_e('Products Report', 'skylearn-billing-pro'); ?></option>
                                        <option value="cohort"><?php esc_html_e('Cohort Analysis', 'skylearn-billing-pro'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="date-from"><?php esc_html_e('Date From', 'skylearn-billing-pro'); ?></label></th>
                                <td><input type="date" id="date-from" name="date_from" value="<?php echo esc_attr(date('Y-m-01')); ?>" /></td>
                            </tr>
                            <tr>
                                <th><label for="date-to"><?php esc_html_e('Date To', 'skylearn-billing-pro'); ?></label></th>
                                <td><input type="date" id="date-to" name="date_to" value="<?php echo esc_attr(date('Y-m-d')); ?>" /></td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button-primary"><?php esc_html_e('Generate Report', 'skylearn-billing-pro'); ?></button>
                            <button type="button" id="export-csv" class="button"><?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?></button>
                            <button type="button" id="export-pdf" class="button"><?php esc_html_e('Export PDF', 'skylearn-billing-pro'); ?></button>
                        </p>
                    </form>
                </div>
                
                <div id="report-results" class="report-results">
                    <p><?php esc_html_e('Select report parameters and click "Generate Report" to view results.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
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
                'templates' => array(),
                'scheduled_reports' => array(),
                'default_recipients' => array(get_option('admin_email'))
            );
            
            add_option('skylearn_billing_reporting_addon_settings', $default_settings);
            
            // Trigger activation hook for other integrations
            do_action('skylearn_billing_reporting_addon_activated');
        }
    }
    
    /**
     * Handle addon deactivation
     *
     * @param string $addon_id Addon ID
     */
    public function on_addon_deactivated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Clean up scheduled events
            wp_clear_scheduled_hook('skylearn_billing_send_scheduled_report');
            
            do_action('skylearn_billing_reporting_addon_deactivated');
        }
    }
}

// Initialize the addon
SkyLearn_Billing_Pro_Reporting_Addon::instance();