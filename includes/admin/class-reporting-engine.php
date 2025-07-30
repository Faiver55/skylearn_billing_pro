<?php
/**
 * Reporting Engine class for Skylearn Billing Pro
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
 * Reporting Engine class for report generation, export, and scheduling
 */
class SkyLearn_Billing_Pro_Reporting_Engine {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_skylearn_generate_report', array($this, 'ajax_generate_report'));
        add_action('wp_ajax_skylearn_export_report_pdf', array($this, 'ajax_export_pdf'));
        add_action('wp_ajax_skylearn_schedule_report', array($this, 'ajax_schedule_report'));
        add_action('wp_ajax_skylearn_get_scheduled_reports', array($this, 'ajax_get_scheduled_reports'));
        
        // Schedule hook for automated reports
        add_action('skylearn_send_scheduled_report', array($this, 'send_scheduled_report'));
        
        // Register custom post type for saved reports
        add_action('init', array($this, 'register_report_post_type'));
    }
    
    /**
     * Initialize admin functionality
     */
    public function admin_init() {
        // Register report templates
        $this->register_report_templates();
    }
    
    /**
     * Register custom post type for saved reports
     */
    public function register_report_post_type() {
        register_post_type('skylearn_report', array(
            'labels' => array(
                'name' => 'Saved Reports',
                'singular_name' => 'Saved Report'
            ),
            'public' => false,
            'show_ui' => false,
            'supports' => array('title'),
            'capability_type' => 'post',
            'capabilities' => array(
                'create_posts' => 'manage_options',
                'read_post' => 'manage_options',
                'edit_post' => 'manage_options',
                'delete_post' => 'manage_options'
            )
        ));
    }
    
    /**
     * Generate custom report
     *
     * @param array $config Report configuration
     * @return array Report data
     */
    public function generate_report($config) {
        $defaults = array(
            'name' => 'Custom Report',
            'type' => 'sales',
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-d'),
            'filters' => array(),
            'columns' => array(),
            'format' => 'table',
            'group_by' => null,
            'sort_by' => 'date',
            'sort_order' => 'DESC'
        );
        
        $config = wp_parse_args($config, $defaults);
        
        $report_data = array(
            'config' => $config,
            'generated_at' => current_time('mysql'),
            'data' => array(),
            'summary' => array(),
            'total_rows' => 0
        );
        
        switch ($config['type']) {
            case 'sales':
                $report_data = $this->generate_sales_report($config);
                break;
            case 'customers':
                $report_data = $this->generate_customers_report($config);
                break;
            case 'products':
                $report_data = $this->generate_products_report($config);
                break;
            case 'subscriptions':
                $report_data = $this->generate_subscriptions_report($config);
                break;
            case 'revenue':
                $report_data = $this->generate_revenue_report($config);
                break;
            case 'custom':
                $report_data = $this->generate_custom_report($config);
                break;
        }
        
        return $report_data;
    }
    
    /**
     * Generate sales report
     *
     * @param array $config Report configuration
     * @return array Report data
     */
    private function generate_sales_report($config) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $data = array();
        $summary = array(
            'total_sales' => 0,
            'successful_sales' => 0,
            'failed_sales' => 0,
            'total_revenue' => 0,
            'average_order_value' => 0
        );
        
        foreach ($enrollment_log as $entry) {
            // Apply date filter
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $config['date_from'] || $entry_date > $config['date_to']) {
                continue;
            }
            
            // Apply additional filters
            if (!$this->passes_filters($entry, $config['filters'])) {
                continue;
            }
            
            $user = get_user_by('ID', $entry['user_id']);
            $product_name = $this->get_product_name($entry['product_id']);
            $product_price = $this->get_product_price($entry['product_id']);
            
            $row = array(
                'date' => date('Y-m-d H:i:s', strtotime($entry['timestamp'])),
                'order_id' => $entry['product_id'] . '-' . $entry['user_id'] . '-' . strtotime($entry['timestamp']),
                'customer_name' => $user ? $user->display_name : 'Unknown User',
                'customer_email' => $user ? $user->user_email : 'unknown@example.com',
                'product_name' => $product_name,
                'product_id' => $entry['product_id'],
                'amount' => $product_price,
                'status' => $entry['status'],
                'gateway' => isset($entry['gateway']) ? $entry['gateway'] : 'unknown',
                'course_title' => isset($entry['course_title']) ? $entry['course_title'] : '',
                'trigger' => $entry['trigger']
            );
            
            // Apply column filtering
            if (!empty($config['columns'])) {
                $filtered_row = array();
                foreach ($config['columns'] as $column) {
                    if (isset($row[$column])) {
                        $filtered_row[$column] = $row[$column];
                    }
                }
                $row = $filtered_row;
            }
            
            $data[] = $row;
            
            // Update summary
            $summary['total_sales']++;
            if ($entry['status'] === 'success') {
                $summary['successful_sales']++;
                $summary['total_revenue'] += $product_price;
            } else {
                $summary['failed_sales']++;
            }
        }
        
        // Calculate average order value
        if ($summary['successful_sales'] > 0) {
            $summary['average_order_value'] = round($summary['total_revenue'] / $summary['successful_sales'], 2);
        }
        
        // Apply sorting
        $data = $this->sort_report_data($data, $config['sort_by'], $config['sort_order']);
        
        // Apply grouping if specified
        if ($config['group_by']) {
            $data = $this->group_report_data($data, $config['group_by']);
        }
        
        return array(
            'config' => $config,
            'generated_at' => current_time('mysql'),
            'data' => $data,
            'summary' => $summary,
            'total_rows' => count($data)
        );
    }
    
    /**
     * Generate customers report
     *
     * @param array $config Report configuration
     * @return array Report data
     */
    private function generate_customers_report($config) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $customers = array();
        $summary = array(
            'total_customers' => 0,
            'active_customers' => 0,
            'total_ltv' => 0,
            'average_ltv' => 0
        );
        
        // Aggregate customer data
        foreach ($enrollment_log as $entry) {
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $config['date_from'] || $entry_date > $config['date_to']) {
                continue;
            }
            
            if (!$this->passes_filters($entry, $config['filters'])) {
                continue;
            }
            
            $user_id = $entry['user_id'];
            
            if (!isset($customers[$user_id])) {
                $user = get_user_by('ID', $user_id);
                $customers[$user_id] = array(
                    'customer_id' => $user_id,
                    'customer_name' => $user ? $user->display_name : 'Unknown User',
                    'customer_email' => $user ? $user->user_email : 'unknown@example.com',
                    'registration_date' => $user ? $user->user_registered : '',
                    'total_orders' => 0,
                    'successful_orders' => 0,
                    'failed_orders' => 0,
                    'total_spent' => 0,
                    'last_purchase_date' => '',
                    'products_purchased' => array(),
                    'courses_enrolled' => array()
                );
            }
            
            $customers[$user_id]['total_orders']++;
            $customers[$user_id]['last_purchase_date'] = max($customers[$user_id]['last_purchase_date'], $entry['timestamp']);
            
            if ($entry['status'] === 'success') {
                $customers[$user_id]['successful_orders']++;
                $customers[$user_id]['total_spent'] += $this->get_product_price($entry['product_id']);
                
                if (!in_array($entry['product_id'], $customers[$user_id]['products_purchased'])) {
                    $customers[$user_id]['products_purchased'][] = $entry['product_id'];
                }
                
                if (!empty($entry['course_title']) && !in_array($entry['course_title'], $customers[$user_id]['courses_enrolled'])) {
                    $customers[$user_id]['courses_enrolled'][] = $entry['course_title'];
                }
            } else {
                $customers[$user_id]['failed_orders']++;
            }
        }
        
        // Convert to array and calculate summary
        $data = array_values($customers);
        
        foreach ($data as &$customer) {
            $customer['products_purchased'] = count($customer['products_purchased']);
            $customer['courses_enrolled'] = count($customer['courses_enrolled']);
            $customer['last_purchase_date'] = date('Y-m-d H:i:s', strtotime($customer['last_purchase_date']));
        }
        
        $summary['total_customers'] = count($data);
        $summary['active_customers'] = count(array_filter($data, function($c) { return $c['successful_orders'] > 0; }));
        $summary['total_ltv'] = array_sum(array_column($data, 'total_spent'));
        $summary['average_ltv'] = $summary['total_customers'] > 0 ? round($summary['total_ltv'] / $summary['total_customers'], 2) : 0;
        
        // Apply sorting
        $data = $this->sort_report_data($data, $config['sort_by'], $config['sort_order']);
        
        return array(
            'config' => $config,
            'generated_at' => current_time('mysql'),
            'data' => $data,
            'summary' => $summary,
            'total_rows' => count($data)
        );
    }
    
    /**
     * Generate products report
     *
     * @param array $config Report configuration
     * @return array Report data
     */
    private function generate_products_report($config) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $products = array();
        $summary = array(
            'total_products' => 0,
            'total_sales' => 0,
            'total_revenue' => 0,
            'average_revenue_per_product' => 0
        );
        
        // Aggregate product data
        foreach ($enrollment_log as $entry) {
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $config['date_from'] || $entry_date > $config['date_to']) {
                continue;
            }
            
            if (!$this->passes_filters($entry, $config['filters'])) {
                continue;
            }
            
            $product_id = $entry['product_id'];
            
            if (!isset($products[$product_id])) {
                $products[$product_id] = array(
                    'product_id' => $product_id,
                    'product_name' => $this->get_product_name($product_id),
                    'product_price' => $this->get_product_price($product_id),
                    'total_attempts' => 0,
                    'successful_sales' => 0,
                    'failed_sales' => 0,
                    'total_revenue' => 0,
                    'conversion_rate' => 0,
                    'unique_customers' => array()
                );
            }
            
            $products[$product_id]['total_attempts']++;
            
            if ($entry['status'] === 'success') {
                $products[$product_id]['successful_sales']++;
                $products[$product_id]['total_revenue'] += $this->get_product_price($product_id);
                
                if (!in_array($entry['user_id'], $products[$product_id]['unique_customers'])) {
                    $products[$product_id]['unique_customers'][] = $entry['user_id'];
                }
            } else {
                $products[$product_id]['failed_sales']++;
            }
        }
        
        // Convert to array and calculate metrics
        $data = array_values($products);
        
        foreach ($data as &$product) {
            $product['unique_customers'] = count($product['unique_customers']);
            $product['conversion_rate'] = $product['total_attempts'] > 0 ? 
                round(($product['successful_sales'] / $product['total_attempts']) * 100, 2) : 0;
        }
        
        $summary['total_products'] = count($data);
        $summary['total_sales'] = array_sum(array_column($data, 'successful_sales'));
        $summary['total_revenue'] = array_sum(array_column($data, 'total_revenue'));
        $summary['average_revenue_per_product'] = $summary['total_products'] > 0 ? 
            round($summary['total_revenue'] / $summary['total_products'], 2) : 0;
        
        // Apply sorting
        $data = $this->sort_report_data($data, $config['sort_by'], $config['sort_order']);
        
        return array(
            'config' => $config,
            'generated_at' => current_time('mysql'),
            'data' => $data,
            'summary' => $summary,
            'total_rows' => count($data)
        );
    }
    
    /**
     * Generate subscriptions report
     *
     * @param array $config Report configuration
     * @return array Report data
     */
    private function generate_subscriptions_report($config) {
        // For now, return simplified subscription data
        // In a full implementation, this would query actual subscription data
        $data = array();
        $summary = array(
            'active_subscriptions' => 0,
            'cancelled_subscriptions' => 0,
            'total_mrr' => 0,
            'churn_rate' => 0
        );
        
        return array(
            'config' => $config,
            'generated_at' => current_time('mysql'),
            'data' => $data,
            'summary' => $summary,
            'total_rows' => 0
        );
    }
    
    /**
     * Generate revenue report
     *
     * @param array $config Report configuration
     * @return array Report data
     */
    private function generate_revenue_report($config) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $revenue_data = array();
        $summary = array(
            'total_revenue' => 0,
            'total_orders' => 0,
            'average_order_value' => 0,
            'revenue_growth' => 0
        );
        
        // Group revenue by period (daily, weekly, monthly based on config)
        $period_format = $config['group_by'] ?: 'daily';
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') continue;
            
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $config['date_from'] || $entry_date > $config['date_to']) {
                continue;
            }
            
            if (!$this->passes_filters($entry, $config['filters'])) {
                continue;
            }
            
            $period_key = $this->get_period_key($entry_date, $period_format);
            
            if (!isset($revenue_data[$period_key])) {
                $revenue_data[$period_key] = array(
                    'period' => $period_key,
                    'formatted_period' => $this->format_period_label($period_key, $period_format),
                    'revenue' => 0,
                    'orders' => 0,
                    'unique_customers' => array()
                );
            }
            
            $product_price = $this->get_product_price($entry['product_id']);
            $revenue_data[$period_key]['revenue'] += $product_price;
            $revenue_data[$period_key]['orders']++;
            
            if (!in_array($entry['user_id'], $revenue_data[$period_key]['unique_customers'])) {
                $revenue_data[$period_key]['unique_customers'][] = $entry['user_id'];
            }
        }
        
        // Convert to array and calculate averages
        $data = array_values($revenue_data);
        
        foreach ($data as &$period) {
            $period['unique_customers'] = count($period['unique_customers']);
            $period['average_order_value'] = $period['orders'] > 0 ? 
                round($period['revenue'] / $period['orders'], 2) : 0;
        }
        
        $summary['total_revenue'] = array_sum(array_column($data, 'revenue'));
        $summary['total_orders'] = array_sum(array_column($data, 'orders'));
        $summary['average_order_value'] = $summary['total_orders'] > 0 ? 
            round($summary['total_revenue'] / $summary['total_orders'], 2) : 0;
        
        // Apply sorting
        $data = $this->sort_report_data($data, $config['sort_by'], $config['sort_order']);
        
        return array(
            'config' => $config,
            'generated_at' => current_time('mysql'),
            'data' => $data,
            'summary' => $summary,
            'total_rows' => count($data)
        );
    }
    
    /**
     * Generate custom report using developer hooks
     *
     * @param array $config Report configuration
     * @return array Report data
     */
    private function generate_custom_report($config) {
        // Allow developers to register custom report generators
        $custom_data = apply_filters('skylearn_billing_custom_report_data', array(), $config);
        
        return array(
            'config' => $config,
            'generated_at' => current_time('mysql'),
            'data' => $custom_data,
            'summary' => array(),
            'total_rows' => count($custom_data)
        );
    }
    
    /**
     * Export report as PDF
     *
     * @param array $report_data Report data
     * @param array $options Export options
     */
    public function export_pdf($report_data, $options = array()) {
        $defaults = array(
            'title' => 'Report',
            'orientation' => 'P', // P or L
            'format' => 'A4'
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // For this implementation, we'll create a simple HTML-to-PDF conversion
        // In a full implementation, you'd use a library like TCPDF or mPDF
        
        $html = $this->generate_report_html($report_data, $options);
        
        // Simple PDF generation using HTML
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($options['title']) . '.pdf"');
        
        // For demonstration, output HTML (in real implementation, convert to PDF)
        echo $html;
        exit;
    }
    
    /**
     * Schedule report to be sent via email
     *
     * @param array $config Report configuration
     * @param array $schedule Schedule settings
     * @return bool Success status
     */
    public function schedule_report($config, $schedule) {
        $defaults = array(
            'frequency' => 'weekly', // daily, weekly, monthly
            'time' => '09:00',
            'recipients' => array(),
            'format' => 'csv', // csv, pdf
            'enabled' => true
        );
        
        $schedule = wp_parse_args($schedule, $defaults);
        
        // Save scheduled report
        $scheduled_reports = get_option('skylearn_billing_scheduled_reports', array());
        $report_id = uniqid('report_');
        
        $scheduled_reports[$report_id] = array(
            'id' => $report_id,
            'config' => $config,
            'schedule' => $schedule,
            'created_at' => current_time('mysql'),
            'last_sent' => null,
            'next_send' => $this->calculate_next_send_time($schedule)
        );
        
        update_option('skylearn_billing_scheduled_reports', $scheduled_reports);
        
        // Schedule cron event if not already scheduled
        if (!wp_next_scheduled('skylearn_send_scheduled_report')) {
            wp_schedule_event(time(), 'hourly', 'skylearn_send_scheduled_report');
        }
        
        return true;
    }
    
    /**
     * Send scheduled report
     */
    public function send_scheduled_report() {
        $scheduled_reports = get_option('skylearn_billing_scheduled_reports', array());
        $current_time = current_time('timestamp');
        
        foreach ($scheduled_reports as $report_id => &$scheduled_report) {
            if (!$scheduled_report['schedule']['enabled']) {
                continue;
            }
            
            $next_send_time = strtotime($scheduled_report['next_send']);
            
            if ($current_time >= $next_send_time) {
                // Generate report
                $report_data = $this->generate_report($scheduled_report['config']);
                
                // Send email
                $this->send_report_email($report_data, $scheduled_report['schedule']);
                
                // Update schedule
                $scheduled_report['last_sent'] = current_time('mysql');
                $scheduled_report['next_send'] = $this->calculate_next_send_time($scheduled_report['schedule']);
            }
        }
        
        update_option('skylearn_billing_scheduled_reports', $scheduled_reports);
    }
    
    /**
     * AJAX handler for generating reports
     */
    public function ajax_generate_report() {
        check_ajax_referer('skylearn_reporting_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $config = isset($_POST['config']) ? (array) $_POST['config'] : array();
        
        // Sanitize config
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $config[$key] = array_map('sanitize_text_field', $value);
            } else {
                $config[$key] = sanitize_text_field($value);
            }
        }
        
        $report_data = $this->generate_report($config);
        
        wp_send_json_success($report_data);
    }
    
    /**
     * AJAX handler for PDF export
     */
    public function ajax_export_pdf() {
        check_ajax_referer('skylearn_reporting_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $config = isset($_POST['config']) ? (array) $_POST['config'] : array();
        $options = isset($_POST['options']) ? (array) $_POST['options'] : array();
        
        $report_data = $this->generate_report($config);
        $this->export_pdf($report_data, $options);
    }
    
    /**
     * AJAX handler for scheduling reports
     */
    public function ajax_schedule_report() {
        check_ajax_referer('skylearn_reporting_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $config = isset($_POST['config']) ? (array) $_POST['config'] : array();
        $schedule = isset($_POST['schedule']) ? (array) $_POST['schedule'] : array();
        
        $success = $this->schedule_report($config, $schedule);
        
        if ($success) {
            wp_send_json_success(array('message' => 'Report scheduled successfully.'));
        } else {
            wp_send_json_error(array('message' => 'Failed to schedule report.'));
        }
    }
    
    /**
     * AJAX handler for getting scheduled reports
     */
    public function ajax_get_scheduled_reports() {
        check_ajax_referer('skylearn_reporting_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $scheduled_reports = get_option('skylearn_billing_scheduled_reports', array());
        
        wp_send_json_success($scheduled_reports);
    }
    
    // Helper methods
    
    private function passes_filters($entry, $filters) {
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;
            
            switch ($key) {
                case 'product_id':
                    if ($entry['product_id'] != $value) return false;
                    break;
                case 'user_id':
                    if ($entry['user_id'] != $value) return false;
                    break;
                case 'status':
                    if ($entry['status'] != $value) return false;
                    break;
                case 'trigger':
                    if ($entry['trigger'] != $value) return false;
                    break;
            }
        }
        
        return true;
    }
    
    private function sort_report_data($data, $sort_by, $sort_order) {
        if (empty($data) || !$sort_by) return $data;
        
        usort($data, function($a, $b) use ($sort_by, $sort_order) {
            $val_a = isset($a[$sort_by]) ? $a[$sort_by] : '';
            $val_b = isset($b[$sort_by]) ? $b[$sort_by] : '';
            
            if ($sort_order === 'DESC') {
                return $val_b <=> $val_a;
            } else {
                return $val_a <=> $val_b;
            }
        });
        
        return $data;
    }
    
    private function group_report_data($data, $group_by) {
        if (empty($data) || !$group_by) return $data;
        
        $grouped = array();
        
        foreach ($data as $row) {
            $group_key = isset($row[$group_by]) ? $row[$group_by] : 'Other';
            
            if (!isset($grouped[$group_key])) {
                $grouped[$group_key] = array();
            }
            
            $grouped[$group_key][] = $row;
        }
        
        return $grouped;
    }
    
    private function get_product_name($product_id) {
        return "Product #" . $product_id;
    }
    
    private function get_product_price($product_id) {
        return 99.99;
    }
    
    private function get_period_key($date, $period) {
        switch ($period) {
            case 'weekly':
                return date('Y-W', strtotime($date));
            case 'monthly':
                return date('Y-m', strtotime($date));
            default:
                return $date;
        }
    }
    
    private function format_period_label($period_key, $period) {
        switch ($period) {
            case 'weekly':
                return 'Week ' . substr($period_key, -2);
            case 'monthly':
                return date('M Y', strtotime($period_key . '-01'));
            default:
                return date('M j', strtotime($period_key));
        }
    }
    
    private function calculate_next_send_time($schedule) {
        $time_parts = explode(':', $schedule['time']);
        $hour = (int) $time_parts[0];
        $minute = (int) $time_parts[1];
        
        $next_send = mktime($hour, $minute, 0);
        
        switch ($schedule['frequency']) {
            case 'daily':
                if ($next_send <= time()) {
                    $next_send = strtotime('+1 day', $next_send);
                }
                break;
            case 'weekly':
                $next_send = strtotime('next monday', $next_send);
                break;
            case 'monthly':
                $next_send = strtotime('first day of next month', $next_send);
                break;
        }
        
        return date('Y-m-d H:i:s', $next_send);
    }
    
    private function send_report_email($report_data, $schedule) {
        $subject = 'Scheduled Report: ' . $report_data['config']['name'];
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $message = $this->generate_email_content($report_data);
        
        foreach ($schedule['recipients'] as $recipient) {
            wp_mail($recipient, $subject, $message, $headers);
        }
    }
    
    private function generate_email_content($report_data) {
        ob_start();
        ?>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                table { border-collapse: collapse; width: 100%; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .summary { background-color: #f9f9f9; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <h2><?php echo esc_html($report_data['config']['name']); ?></h2>
            <p>Generated on: <?php echo esc_html($report_data['generated_at']); ?></p>
            
            <div class="summary">
                <h3>Summary</h3>
                <?php foreach ($report_data['summary'] as $key => $value): ?>
                    <p><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong> <?php echo esc_html($value); ?></p>
                <?php endforeach; ?>
            </div>
            
            <h3>Data (First 50 rows)</h3>
            <table>
                <?php if (!empty($report_data['data'])): ?>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($report_data['data'][0]) as $column): ?>
                                <th><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($report_data['data'], 0, 50) as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo esc_html($value); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function generate_report_html($report_data, $options) {
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php echo esc_html($options['title']); ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                h1 { color: #333; border-bottom: 2px solid #0073aa; padding-bottom: 10px; }
                table { border-collapse: collapse; width: 100%; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .summary { background-color: #f9f9f9; padding: 15px; margin: 20px 0; border-left: 4px solid #0073aa; }
                .summary h3 { margin-top: 0; }
            </style>
        </head>
        <body>
            <h1><?php echo esc_html($options['title']); ?></h1>
            <p><strong>Generated:</strong> <?php echo esc_html($report_data['generated_at']); ?></p>
            <p><strong>Date Range:</strong> <?php echo esc_html($report_data['config']['date_from']); ?> to <?php echo esc_html($report_data['config']['date_to']); ?></p>
            
            <div class="summary">
                <h3>Summary</h3>
                <?php foreach ($report_data['summary'] as $key => $value): ?>
                    <p><strong><?php echo esc_html(ucwords(str_replace('_', ' ', $key))); ?>:</strong> <?php echo esc_html($value); ?></p>
                <?php endforeach; ?>
            </div>
            
            <h3>Report Data</h3>
            <table>
                <?php if (!empty($report_data['data'])): ?>
                    <thead>
                        <tr>
                            <?php foreach (array_keys($report_data['data'][0]) as $column): ?>
                                <th><?php echo esc_html(ucwords(str_replace('_', ' ', $column))); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report_data['data'] as $row): ?>
                            <tr>
                                <?php foreach ($row as $value): ?>
                                    <td><?php echo esc_html($value); ?></td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php else: ?>
                    <tr><td colspan="100%">No data available for the selected criteria.</td></tr>
                <?php endif; ?>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
    
    private function register_report_templates() {
        // Register default report templates
        $templates = array(
            'sales_summary' => array(
                'name' => 'Sales Summary',
                'type' => 'sales',
                'columns' => array('date', 'customer_name', 'product_name', 'amount', 'status'),
                'default_filters' => array()
            ),
            'customer_ltv' => array(
                'name' => 'Customer Lifetime Value',
                'type' => 'customers',
                'columns' => array('customer_name', 'total_spent', 'total_orders', 'last_purchase_date'),
                'default_filters' => array()
            ),
            'product_performance' => array(
                'name' => 'Product Performance',
                'type' => 'products',
                'columns' => array('product_name', 'successful_sales', 'total_revenue', 'conversion_rate'),
                'default_filters' => array()
            ),
            'revenue_trends' => array(
                'name' => 'Revenue Trends',
                'type' => 'revenue',
                'columns' => array('period', 'revenue', 'orders', 'average_order_value'),
                'default_filters' => array(),
                'group_by' => 'monthly'
            )
        );
        
        update_option('skylearn_billing_report_templates', $templates);
    }
}

/**
 * Get instance of reporting engine class
 *
 * @return SkyLearn_Billing_Pro_Reporting_Engine
 */
function skylearn_billing_pro_reporting_engine() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Reporting_Engine();
    }
    
    return $instance;
}