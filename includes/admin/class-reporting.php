<?php
/**
 * Reporting class for Skylearn Billing Pro
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
 * Admin Reporting class for analytics and dashboard functionality
 */
class SkyLearn_Billing_Pro_Reporting {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_skylearn_reporting_data', array($this, 'ajax_get_reporting_data'));
        add_action('wp_ajax_skylearn_export_report', array($this, 'ajax_export_report'));
    }
    
    /**
     * Initialize admin functionality
     */
    public function admin_init() {
        // Register settings if needed
    }
    
    /**
     * Get sales analytics
     *
     * @param array $filters Filter parameters
     * @return array Sales data
     */
    public function get_sales_analytics($filters = array()) {
        $defaults = array(
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-d'),
            'product_id' => '',
            'course_id' => '',
            'status' => '',
            'user_id' => ''
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        // Get enrollment data for sales analysis
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $sales_data = array(
            'total_sales' => 0,
            'successful_sales' => 0,
            'failed_sales' => 0,
            'revenue' => 0,
            'sales_by_day' => array(),
            'sales_by_product' => array(),
            'sales_by_course' => array(),
            'conversion_rate' => 0
        );
        
        foreach ($enrollment_log as $entry) {
            // Apply date filter
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $filters['date_from'] || $entry_date > $filters['date_to']) {
                continue;
            }
            
            // Apply other filters
            if (!empty($filters['product_id']) && $entry['product_id'] != $filters['product_id']) {
                continue;
            }
            
            if (!empty($filters['course_id']) && $entry['course_id'] != $filters['course_id']) {
                continue;
            }
            
            if (!empty($filters['status']) && $entry['status'] != $filters['status']) {
                continue;
            }
            
            if (!empty($filters['user_id']) && $entry['user_id'] != $filters['user_id']) {
                continue;
            }
            
            $sales_data['total_sales']++;
            
            if ($entry['status'] === 'success') {
                $sales_data['successful_sales']++;
                // In a real implementation, you'd get product price from product data
                $sales_data['revenue'] += $this->get_product_price($entry['product_id']);
            } else {
                $sales_data['failed_sales']++;
            }
            
            // Group by day
            if (!isset($sales_data['sales_by_day'][$entry_date])) {
                $sales_data['sales_by_day'][$entry_date] = 0;
            }
            $sales_data['sales_by_day'][$entry_date]++;
            
            // Group by product
            $product_name = $this->get_product_name($entry['product_id']);
            if (!isset($sales_data['sales_by_product'][$product_name])) {
                $sales_data['sales_by_product'][$product_name] = 0;
            }
            $sales_data['sales_by_product'][$product_name]++;
            
            // Group by course
            if (!empty($entry['course_title'])) {
                if (!isset($sales_data['sales_by_course'][$entry['course_title']])) {
                    $sales_data['sales_by_course'][$entry['course_title']] = 0;
                }
                $sales_data['sales_by_course'][$entry['course_title']]++;
            }
        }
        
        // Calculate conversion rate
        if ($sales_data['total_sales'] > 0) {
            $sales_data['conversion_rate'] = round(($sales_data['successful_sales'] / $sales_data['total_sales']) * 100, 2);
        }
        
        return $sales_data;
    }
    
    /**
     * Get enrollment analytics
     *
     * @param array $filters Filter parameters
     * @return array Enrollment data
     */
    public function get_enrollment_analytics($filters = array()) {
        $defaults = array(
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-d'),
            'course_id' => '',
            'trigger' => ''
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $enrollment_data = array(
            'total_enrollments' => 0,
            'successful_enrollments' => 0,
            'failed_enrollments' => 0,
            'enrollments_by_day' => array(),
            'enrollments_by_course' => array(),
            'enrollments_by_trigger' => array(),
            'most_popular_courses' => array()
        );
        
        foreach ($enrollment_log as $entry) {
            // Apply date filter
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $filters['date_from'] || $entry_date > $filters['date_to']) {
                continue;
            }
            
            // Apply other filters
            if (!empty($filters['course_id']) && $entry['course_id'] != $filters['course_id']) {
                continue;
            }
            
            if (!empty($filters['trigger']) && $entry['trigger'] != $filters['trigger']) {
                continue;
            }
            
            $enrollment_data['total_enrollments']++;
            
            if ($entry['status'] === 'success') {
                $enrollment_data['successful_enrollments']++;
            } else {
                $enrollment_data['failed_enrollments']++;
            }
            
            // Group by day
            if (!isset($enrollment_data['enrollments_by_day'][$entry_date])) {
                $enrollment_data['enrollments_by_day'][$entry_date] = 0;
            }
            $enrollment_data['enrollments_by_day'][$entry_date]++;
            
            // Group by course
            if (!empty($entry['course_title'])) {
                if (!isset($enrollment_data['enrollments_by_course'][$entry['course_title']])) {
                    $enrollment_data['enrollments_by_course'][$entry['course_title']] = 0;
                }
                $enrollment_data['enrollments_by_course'][$entry['course_title']]++;
            }
            
            // Group by trigger
            if (!isset($enrollment_data['enrollments_by_trigger'][$entry['trigger']])) {
                $enrollment_data['enrollments_by_trigger'][$entry['trigger']] = 0;
            }
            $enrollment_data['enrollments_by_trigger'][$entry['trigger']]++;
        }
        
        // Get most popular courses
        arsort($enrollment_data['enrollments_by_course']);
        $enrollment_data['most_popular_courses'] = array_slice($enrollment_data['enrollments_by_course'], 0, 10, true);
        
        return $enrollment_data;
    }
    
    /**
     * Get email analytics
     *
     * @param array $filters Filter parameters
     * @return array Email data
     */
    public function get_email_analytics($filters = array()) {
        $defaults = array(
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-d'),
            'email_type' => '',
            'status' => ''
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        $logs = get_option('skylearn_billing_pro_email_logs', array());
        
        $email_data = array(
            'total_emails' => 0,
            'successful_emails' => 0,
            'failed_emails' => 0,
            'emails_by_day' => array(),
            'emails_by_type' => array(),
            'delivery_rate' => 0,
            'open_rate' => 0, // Future feature
            'click_rate' => 0 // Future feature
        );
        
        foreach ($logs as $entry) {
            // Apply date filter
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $filters['date_from'] || $entry_date > $filters['date_to']) {
                continue;
            }
            
            // Apply other filters
            if (!empty($filters['email_type']) && $entry['type'] != $filters['email_type']) {
                continue;
            }
            
            if (!empty($filters['status']) && $entry['status'] != $filters['status']) {
                continue;
            }
            
            $email_data['total_emails']++;
            
            if ($entry['status'] === 'sent') {
                $email_data['successful_emails']++;
            } else {
                $email_data['failed_emails']++;
            }
            
            // Group by day
            if (!isset($email_data['emails_by_day'][$entry_date])) {
                $email_data['emails_by_day'][$entry_date] = 0;
            }
            $email_data['emails_by_day'][$entry_date]++;
            
            // Group by type
            if (!isset($email_data['emails_by_type'][$entry['type']])) {
                $email_data['emails_by_type'][$entry['type']] = 0;
            }
            $email_data['emails_by_type'][$entry['type']]++;
        }
        
        // Calculate delivery rate
        if ($email_data['total_emails'] > 0) {
            $email_data['delivery_rate'] = round(($email_data['successful_emails'] / $email_data['total_emails']) * 100, 2);
        }
        
        return $email_data;
    }
    
    /**
     * Get revenue analytics
     *
     * @param array $filters Filter parameters
     * @return array Revenue data
     */
    public function get_revenue_analytics($filters = array()) {
        $defaults = array(
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-d'),
            'product_id' => ''
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $revenue_data = array(
            'total_revenue' => 0,
            'revenue_by_day' => array(),
            'revenue_by_product' => array(),
            'average_order_value' => 0,
            'refunds' => 0,
            'net_revenue' => 0
        );
        
        $order_count = 0;
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') {
                continue;
            }
            
            // Apply date filter
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $filters['date_from'] || $entry_date > $filters['date_to']) {
                continue;
            }
            
            // Apply product filter
            if (!empty($filters['product_id']) && $entry['product_id'] != $filters['product_id']) {
                continue;
            }
            
            $product_price = $this->get_product_price($entry['product_id']);
            $revenue_data['total_revenue'] += $product_price;
            $order_count++;
            
            // Group by day
            if (!isset($revenue_data['revenue_by_day'][$entry_date])) {
                $revenue_data['revenue_by_day'][$entry_date] = 0;
            }
            $revenue_data['revenue_by_day'][$entry_date] += $product_price;
            
            // Group by product
            $product_name = $this->get_product_name($entry['product_id']);
            if (!isset($revenue_data['revenue_by_product'][$product_name])) {
                $revenue_data['revenue_by_product'][$product_name] = 0;
            }
            $revenue_data['revenue_by_product'][$product_name] += $product_price;
        }
        
        // Calculate average order value
        if ($order_count > 0) {
            $revenue_data['average_order_value'] = round($revenue_data['total_revenue'] / $order_count, 2);
        }
        
        // Calculate net revenue (total - refunds)
        $revenue_data['net_revenue'] = $revenue_data['total_revenue'] - $revenue_data['refunds'];
        
        return $revenue_data;
    }
    
    /**
     * Get cohort analysis data
     *
     * @param array $filters Filter parameters
     * @return array Cohort data
     */
    public function get_cohort_analytics($filters = array()) {
        $defaults = array(
            'period' => 'monthly', // monthly, weekly
            'date_from' => date('Y-m-01', strtotime('-6 months')),
            'date_to' => date('Y-m-d')
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        $user_activity_log = isset($options['user_activity_log']) ? $options['user_activity_log'] : array();
        
        $cohort_data = array(
            'cohorts' => array(),
            'retention_rates' => array(),
            'periods' => array()
        );
        
        // Group users by enrollment period
        $user_cohorts = array();
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') {
                continue;
            }
            
            $cohort_period = $this->get_cohort_period($entry['timestamp'], $filters['period']);
            
            if (!isset($user_cohorts[$cohort_period])) {
                $user_cohorts[$cohort_period] = array();
            }
            
            if (!in_array($entry['user_id'], $user_cohorts[$cohort_period])) {
                $user_cohorts[$cohort_period][] = $entry['user_id'];
            }
        }
        
        // Calculate retention for each cohort
        foreach ($user_cohorts as $cohort_period => $users) {
            $cohort_data['cohorts'][$cohort_period] = array(
                'size' => count($users),
                'retention' => array()
            );
            
            // Calculate retention for subsequent periods
            for ($i = 1; $i <= 12; $i++) { // Up to 12 periods ahead
                $target_period = $this->add_periods_to_date($cohort_period, $i, $filters['period']);
                $active_users = $this->get_active_users_in_period($users, $target_period, $filters['period'], $user_activity_log);
                
                $retention_rate = count($users) > 0 ? round((count($active_users) / count($users)) * 100, 2) : 0;
                $cohort_data['cohorts'][$cohort_period]['retention'][$i] = $retention_rate;
            }
        }
        
        return $cohort_data;
    }
    
    /**
     * Get retention metrics
     *
     * @param array $filters Filter parameters
     * @return array Retention data
     */
    public function get_retention_analytics($filters = array()) {
        $defaults = array(
            'period' => '30', // days
            'date_from' => date('Y-m-01', strtotime('-3 months')),
            'date_to' => date('Y-m-d')
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        $user_activity_log = isset($options['user_activity_log']) ? $options['user_activity_log'] : array();
        
        $retention_data = array(
            'day_1_retention' => 0,
            'day_7_retention' => 0,
            'day_30_retention' => 0,
            'average_retention' => 0,
            'retention_by_cohort' => array()
        );
        
        // Get newly enrolled users
        $new_users = array();
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') {
                continue;
            }
            
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date < $filters['date_from'] || $entry_date > $filters['date_to']) {
                continue;
            }
            
            if (!isset($new_users[$entry_date])) {
                $new_users[$entry_date] = array();
            }
            
            if (!in_array($entry['user_id'], $new_users[$entry_date])) {
                $new_users[$entry_date][] = $entry['user_id'];
            }
        }
        
        // Calculate retention rates
        $total_users = 0;
        $day_1_retained = 0;
        $day_7_retained = 0;
        $day_30_retained = 0;
        
        foreach ($new_users as $date => $users) {
            $total_users += count($users);
            
            // Check retention after 1, 7, and 30 days
            $day_1_retained += $this->count_retained_users($users, $date, 1, $user_activity_log);
            $day_7_retained += $this->count_retained_users($users, $date, 7, $user_activity_log);
            $day_30_retained += $this->count_retained_users($users, $date, 30, $user_activity_log);
        }
        
        if ($total_users > 0) {
            $retention_data['day_1_retention'] = round(($day_1_retained / $total_users) * 100, 2);
            $retention_data['day_7_retention'] = round(($day_7_retained / $total_users) * 100, 2);
            $retention_data['day_30_retention'] = round(($day_30_retained / $total_users) * 100, 2);
            $retention_data['average_retention'] = round(($retention_data['day_1_retention'] + $retention_data['day_7_retention'] + $retention_data['day_30_retention']) / 3, 2);
        }
        
        return $retention_data;
    }
    
    /**
     * AJAX handler for getting reporting data
     */
    public function ajax_get_reporting_data() {
        check_ajax_referer('skylearn_reporting_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $filters = isset($_POST['filters']) ? (array) $_POST['filters'] : array();
        
        // Sanitize filters
        foreach ($filters as $key => $value) {
            $filters[$key] = sanitize_text_field($value);
        }
        
        $data = array();
        
        switch ($report_type) {
            case 'sales':
                $data = $this->get_sales_analytics($filters);
                break;
            case 'enrollments':
                $data = $this->get_enrollment_analytics($filters);
                break;
            case 'emails':
                $data = $this->get_email_analytics($filters);
                break;
            case 'revenue':
                $data = $this->get_revenue_analytics($filters);
                break;
            case 'cohort':
                $data = $this->get_cohort_analytics($filters);
                break;
            case 'retention':
                $data = $this->get_retention_analytics($filters);
                break;
            default:
                wp_die(__('Invalid report type.'));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for exporting reports
     */
    public function ajax_export_report() {
        check_ajax_referer('skylearn_export_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $format = sanitize_text_field($_POST['format']); // csv or xls
        $filters = isset($_POST['filters']) ? (array) $_POST['filters'] : array();
        
        // Sanitize filters
        foreach ($filters as $key => $value) {
            $filters[$key] = sanitize_text_field($value);
        }
        
        // Get data based on report type
        switch ($report_type) {
            case 'sales':
                $data = $this->get_sales_analytics($filters);
                break;
            case 'enrollments':
                $data = $this->get_enrollment_analytics($filters);
                break;
            case 'emails':
                $data = $this->get_email_analytics($filters);
                break;
            case 'revenue':
                $data = $this->get_revenue_analytics($filters);
                break;
            default:
                wp_die(__('Invalid report type.'));
        }
        
        if ($format === 'csv') {
            $this->export_csv($data, $report_type);
        } else {
            $this->export_xls($data, $report_type);
        }
    }
    
    /**
     * Export data as CSV
     *
     * @param array $data Report data
     * @param string $report_type Report type
     */
    private function export_csv($data, $report_type) {
        $filename = 'skylearn-' . $report_type . '-report-' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Write CSV headers and data based on report type
        switch ($report_type) {
            case 'sales':
                fputcsv($output, array('Metric', 'Value'));
                fputcsv($output, array('Total Sales', $data['total_sales']));
                fputcsv($output, array('Successful Sales', $data['successful_sales']));
                fputcsv($output, array('Failed Sales', $data['failed_sales']));
                fputcsv($output, array('Conversion Rate (%)', $data['conversion_rate']));
                fputcsv($output, array(''));
                fputcsv($output, array('Sales by Day'));
                fputcsv($output, array('Date', 'Sales'));
                foreach ($data['sales_by_day'] as $date => $count) {
                    fputcsv($output, array($date, $count));
                }
                break;
                
            case 'enrollments':
                fputcsv($output, array('Metric', 'Value'));
                fputcsv($output, array('Total Enrollments', $data['total_enrollments']));
                fputcsv($output, array('Successful Enrollments', $data['successful_enrollments']));
                fputcsv($output, array('Failed Enrollments', $data['failed_enrollments']));
                fputcsv($output, array(''));
                fputcsv($output, array('Popular Courses'));
                fputcsv($output, array('Course', 'Enrollments'));
                foreach ($data['most_popular_courses'] as $course => $count) {
                    fputcsv($output, array($course, $count));
                }
                break;
                
            case 'emails':
                fputcsv($output, array('Metric', 'Value'));
                fputcsv($output, array('Total Emails', $data['total_emails']));
                fputcsv($output, array('Successful Emails', $data['successful_emails']));
                fputcsv($output, array('Failed Emails', $data['failed_emails']));
                fputcsv($output, array('Delivery Rate (%)', $data['delivery_rate']));
                break;
                
            case 'revenue':
                fputcsv($output, array('Metric', 'Value'));
                fputcsv($output, array('Total Revenue', '$' . number_format($data['total_revenue'], 2)));
                fputcsv($output, array('Average Order Value', '$' . number_format($data['average_order_value'], 2)));
                fputcsv($output, array('Net Revenue', '$' . number_format($data['net_revenue'], 2)));
                break;
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export data as XLS (simplified)
     *
     * @param array $data Report data
     * @param string $report_type Report type
     */
    private function export_xls($data, $report_type) {
        // For now, export as CSV with .xls extension
        // In a full implementation, you would use a library like PhpSpreadsheet
        $filename = 'skylearn-' . $report_type . '-report-' . date('Y-m-d') . '.xls';
        
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $this->export_csv($data, $report_type);
    }
    
    /**
     * Helper method to get product price
     *
     * @param string $product_id Product ID
     * @return float Product price
     */
    private function get_product_price($product_id) {
        // In a real implementation, this would get the actual product price
        // For now, return a default value
        return 99.99; // Default price
    }
    
    /**
     * Helper method to get product name
     *
     * @param string $product_id Product ID
     * @return string Product name
     */
    private function get_product_name($product_id) {
        // In a real implementation, this would get the actual product name
        // For now, return a formatted name
        return "Product #" . $product_id;
    }
    
    /**
     * Helper method to get cohort period
     *
     * @param string $timestamp Timestamp
     * @param string $period Period type (monthly/weekly)
     * @return string Cohort period
     */
    private function get_cohort_period($timestamp, $period) {
        $date = date('Y-m-d', strtotime($timestamp));
        
        if ($period === 'weekly') {
            $week_start = date('Y-m-d', strtotime('monday this week', strtotime($date)));
            return $week_start;
        } else {
            return date('Y-m-01', strtotime($date));
        }
    }
    
    /**
     * Helper method to add periods to date
     *
     * @param string $date Base date
     * @param int $periods Number of periods to add
     * @param string $period_type Period type
     * @return string New date
     */
    private function add_periods_to_date($date, $periods, $period_type) {
        if ($period_type === 'weekly') {
            return date('Y-m-d', strtotime($date . " +{$periods} weeks"));
        } else {
            return date('Y-m-d', strtotime($date . " +{$periods} months"));
        }
    }
    
    /**
     * Helper method to get active users in a period
     *
     * @param array $users User IDs
     * @param string $period Target period
     * @param string $period_type Period type
     * @param array $activity_log User activity log
     * @return array Active user IDs
     */
    private function get_active_users_in_period($users, $period, $period_type, $activity_log) {
        $active_users = array();
        
        $period_start = $period;
        if ($period_type === 'weekly') {
            $period_end = date('Y-m-d', strtotime($period . ' +1 week'));
        } else {
            $period_end = date('Y-m-d', strtotime($period . ' +1 month'));
        }
        
        foreach ($activity_log as $entry) {
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            
            if ($entry_date >= $period_start && $entry_date < $period_end) {
                if (in_array($entry['user_id'], $users) && !in_array($entry['user_id'], $active_users)) {
                    $active_users[] = $entry['user_id'];
                }
            }
        }
        
        return $active_users;
    }
    
    /**
     * Helper method to count retained users
     *
     * @param array $users User IDs
     * @param string $start_date Start date
     * @param int $days Days after start date
     * @param array $activity_log User activity log
     * @return int Count of retained users
     */
    private function count_retained_users($users, $start_date, $days, $activity_log) {
        $target_date = date('Y-m-d', strtotime($start_date . " +{$days} days"));
        $retained_users = array();
        
        foreach ($activity_log as $entry) {
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            
            if ($entry_date >= $target_date) {
                if (in_array($entry['user_id'], $users) && !in_array($entry['user_id'], $retained_users)) {
                    $retained_users[] = $entry['user_id'];
                }
            }
        }
        
        return count($retained_users);
    }
}

/**
 * Get instance of reporting class
 *
 * @return SkyLearn_Billing_Pro_Reporting
 */
function skylearn_billing_pro_reporting() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Reporting();
    }
    
    return $instance;
}