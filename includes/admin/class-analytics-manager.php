<?php
/**
 * Analytics Manager class for Skylearn Billing Pro
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
 * Analytics Manager class for dashboard and chart widgets
 */
class SkyLearn_Billing_Pro_Analytics_Manager {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_skylearn_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_skylearn_widget_config', array($this, 'ajax_save_widget_config'));
        add_action('wp_ajax_skylearn_realtime_update', array($this, 'ajax_realtime_update'));
    }
    
    /**
     * Initialize admin functionality
     */
    public function admin_init() {
        // Register widget configurations
        $this->register_default_widgets();
    }
    
    /**
     * Get analytics dashboard data
     *
     * @param array $filters Filter parameters
     * @return array Dashboard data
     */
    public function get_dashboard_analytics($filters = array()) {
        $defaults = array(
            'date_from' => date('Y-m-01'),
            'date_to' => date('Y-m-d'),
            'period' => 'daily' // daily, weekly, monthly
        );
        
        $filters = wp_parse_args($filters, $defaults);
        
        return array(
            'overview' => $this->get_overview_metrics($filters),
            'revenue_chart' => $this->get_revenue_chart_data($filters),
            'subscriptions_chart' => $this->get_subscriptions_chart_data($filters),
            'product_sales_chart' => $this->get_product_sales_chart_data($filters),
            'churn_analysis' => $this->get_churn_analysis($filters),
            'ltv_metrics' => $this->get_ltv_metrics($filters),
            'conversion_funnel' => $this->get_conversion_funnel($filters),
            'top_products' => $this->get_top_products($filters),
            'recent_activity' => $this->get_recent_activity($filters)
        );
    }
    
    /**
     * Get overview metrics
     *
     * @param array $filters Filter parameters
     * @return array Overview metrics
     */
    private function get_overview_metrics($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $metrics = array(
            'total_revenue' => 0,
            'revenue_change' => 0,
            'total_subscriptions' => 0,
            'subscription_change' => 0,
            'active_customers' => 0,
            'customer_change' => 0,
            'churn_rate' => 0,
            'churn_change' => 0,
            'ltv' => 0,
            'ltv_change' => 0,
            'conversion_rate' => 0,
            'conversion_change' => 0
        );
        
        // Calculate current period metrics
        $current_revenue = 0;
        $current_subscriptions = 0;
        $active_customers = array();
        $successful_enrollments = 0;
        $total_enrollments = 0;
        
        foreach ($enrollment_log as $entry) {
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $filters['date_from'] && $entry_date <= $filters['date_to']) {
                $total_enrollments++;
                
                if ($entry['status'] === 'success') {
                    $successful_enrollments++;
                    $current_revenue += $this->get_product_price($entry['product_id']);
                    
                    if ($this->is_subscription_product($entry['product_id'])) {
                        $current_subscriptions++;
                    }
                    
                    if (!in_array($entry['user_id'], $active_customers)) {
                        $active_customers[] = $entry['user_id'];
                    }
                }
            }
        }
        
        $metrics['total_revenue'] = $current_revenue;
        $metrics['total_subscriptions'] = $current_subscriptions;
        $metrics['active_customers'] = count($active_customers);
        $metrics['conversion_rate'] = $total_enrollments > 0 ? round(($successful_enrollments / $total_enrollments) * 100, 2) : 0;
        
        // Calculate LTV (simplified)
        $metrics['ltv'] = $metrics['active_customers'] > 0 ? round($current_revenue / $metrics['active_customers'], 2) : 0;
        
        // Calculate previous period for comparison
        $previous_period = $this->get_previous_period($filters['date_from'], $filters['date_to']);
        $previous_metrics = $this->calculate_period_metrics($previous_period['from'], $previous_period['to']);
        
        // Calculate changes
        $metrics['revenue_change'] = $this->calculate_percentage_change($previous_metrics['revenue'], $current_revenue);
        $metrics['subscription_change'] = $this->calculate_percentage_change($previous_metrics['subscriptions'], $current_subscriptions);
        $metrics['customer_change'] = $this->calculate_percentage_change($previous_metrics['customers'], count($active_customers));
        
        return $metrics;
    }
    
    /**
     * Get revenue chart data
     *
     * @param array $filters Filter parameters
     * @return array Chart data
     */
    private function get_revenue_chart_data($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => 'Revenue',
                    'data' => array(),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 2,
                    'fill' => true
                )
            )
        );
        
        $revenue_by_period = array();
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') continue;
            
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $filters['date_from'] && $entry_date <= $filters['date_to']) {
                $period_key = $this->get_period_key($entry_date, $filters['period']);
                
                if (!isset($revenue_by_period[$period_key])) {
                    $revenue_by_period[$period_key] = 0;
                }
                
                $revenue_by_period[$period_key] += $this->get_product_price($entry['product_id']);
            }
        }
        
        ksort($revenue_by_period);
        
        foreach ($revenue_by_period as $period => $revenue) {
            $chart_data['labels'][] = $this->format_period_label($period, $filters['period']);
            $chart_data['datasets'][0]['data'][] = $revenue;
        }
        
        return $chart_data;
    }
    
    /**
     * Get subscriptions chart data
     *
     * @param array $filters Filter parameters
     * @return array Chart data
     */
    private function get_subscriptions_chart_data($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $chart_data = array(
            'labels' => array(),
            'datasets' => array(
                array(
                    'label' => 'New Subscriptions',
                    'data' => array(),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 2
                ),
                array(
                    'label' => 'Cancelled Subscriptions',
                    'data' => array(),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2
                )
            )
        );
        
        $new_subscriptions = array();
        $cancelled_subscriptions = array();
        
        foreach ($enrollment_log as $entry) {
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $filters['date_from'] && $entry_date <= $filters['date_to']) {
                if ($this->is_subscription_product($entry['product_id'])) {
                    $period_key = $this->get_period_key($entry_date, $filters['period']);
                    
                    if ($entry['status'] === 'success') {
                        if (!isset($new_subscriptions[$period_key])) {
                            $new_subscriptions[$period_key] = 0;
                        }
                        $new_subscriptions[$period_key]++;
                    }
                }
            }
        }
        
        // Get cancellation data (simplified - would need subscription tracking)
        $all_periods = array_unique(array_merge(array_keys($new_subscriptions), array_keys($cancelled_subscriptions)));
        sort($all_periods);
        
        foreach ($all_periods as $period) {
            $chart_data['labels'][] = $this->format_period_label($period, $filters['period']);
            $chart_data['datasets'][0]['data'][] = isset($new_subscriptions[$period]) ? $new_subscriptions[$period] : 0;
            $chart_data['datasets'][1]['data'][] = isset($cancelled_subscriptions[$period]) ? $cancelled_subscriptions[$period] : 0;
        }
        
        return $chart_data;
    }
    
    /**
     * Get product sales chart data
     *
     * @param array $filters Filter parameters
     * @return array Chart data
     */
    private function get_product_sales_chart_data($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $product_sales = array();
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') continue;
            
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $filters['date_from'] && $entry_date <= $filters['date_to']) {
                $product_name = $this->get_product_name($entry['product_id']);
                
                if (!isset($product_sales[$product_name])) {
                    $product_sales[$product_name] = 0;
                }
                $product_sales[$product_name]++;
            }
        }
        
        arsort($product_sales);
        $top_products = array_slice($product_sales, 0, 10, true);
        
        $colors = array(
            'rgba(255, 99, 132, 0.8)',
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 205, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(199, 199, 199, 0.8)',
            'rgba(83, 102, 255, 0.8)',
            'rgba(255, 99, 255, 0.8)',
            'rgba(99, 255, 132, 0.8)'
        );
        
        $chart_data = array(
            'labels' => array_keys($top_products),
            'datasets' => array(
                array(
                    'label' => 'Sales',
                    'data' => array_values($top_products),
                    'backgroundColor' => array_slice($colors, 0, count($top_products)),
                    'borderColor' => array_slice($colors, 0, count($top_products)),
                    'borderWidth' => 1
                )
            )
        );
        
        return $chart_data;
    }
    
    /**
     * Get churn analysis data
     *
     * @param array $filters Filter parameters
     * @return array Churn data
     */
    private function get_churn_analysis($filters) {
        // Simplified churn calculation
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $monthly_cohorts = array();
        $churn_by_month = array();
        
        // Calculate monthly churn rates
        for ($i = 0; $i < 12; $i++) {
            $month = date('Y-m', strtotime($filters['date_from'] . " +{$i} months"));
            $churn_by_month[$month] = rand(2, 8); // Placeholder - would calculate actual churn
        }
        
        return array(
            'current_churn_rate' => array_sum($churn_by_month) / count($churn_by_month),
            'churn_by_month' => $churn_by_month,
            'churn_trend' => 'improving' // decreasing, stable, increasing
        );
    }
    
    /**
     * Get LTV metrics
     *
     * @param array $filters Filter parameters
     * @return array LTV data
     */
    private function get_ltv_metrics($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $customer_values = array();
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') continue;
            
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $filters['date_from'] && $entry_date <= $filters['date_to']) {
                if (!isset($customer_values[$entry['user_id']])) {
                    $customer_values[$entry['user_id']] = 0;
                }
                $customer_values[$entry['user_id']] += $this->get_product_price($entry['product_id']);
            }
        }
        
        $average_ltv = count($customer_values) > 0 ? array_sum($customer_values) / count($customer_values) : 0;
        $median_ltv = count($customer_values) > 0 ? $this->calculate_median($customer_values) : 0;
        
        return array(
            'average_ltv' => round($average_ltv, 2),
            'median_ltv' => round($median_ltv, 2),
            'total_customers' => count($customer_values),
            'ltv_distribution' => $this->get_ltv_distribution($customer_values)
        );
    }
    
    /**
     * Get conversion funnel data
     *
     * @param array $filters Filter parameters
     * @return array Funnel data
     */
    private function get_conversion_funnel($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $funnel_data = array(
            'visitors' => 1000, // Would track actual visitors
            'product_views' => 500, // Would track product page views
            'checkout_started' => 150, // Would track checkout initiations
            'checkout_completed' => 0
        );
        
        // Count completed checkouts from enrollment log
        foreach ($enrollment_log as $entry) {
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $filters['date_from'] && $entry_date <= $filters['date_to']) {
                if ($entry['status'] === 'success') {
                    $funnel_data['checkout_completed']++;
                }
            }
        }
        
        return $funnel_data;
    }
    
    /**
     * Get top products
     *
     * @param array $filters Filter parameters
     * @return array Top products
     */
    private function get_top_products($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $product_stats = array();
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') continue;
            
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $filters['date_from'] && $entry_date <= $filters['date_to']) {
                $product_id = $entry['product_id'];
                
                if (!isset($product_stats[$product_id])) {
                    $product_stats[$product_id] = array(
                        'name' => $this->get_product_name($product_id),
                        'sales' => 0,
                        'revenue' => 0
                    );
                }
                
                $product_stats[$product_id]['sales']++;
                $product_stats[$product_id]['revenue'] += $this->get_product_price($product_id);
            }
        }
        
        // Sort by revenue
        uasort($product_stats, function($a, $b) {
            return $b['revenue'] - $a['revenue'];
        });
        
        return array_slice($product_stats, 0, 5, true);
    }
    
    /**
     * Get recent activity
     *
     * @param array $filters Filter parameters
     * @return array Recent activity
     */
    private function get_recent_activity($filters) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $recent_activity = array();
        
        // Get last 10 activities
        $sorted_log = $enrollment_log;
        usort($sorted_log, function($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });
        
        $recent_entries = array_slice($sorted_log, 0, 10);
        
        foreach ($recent_entries as $entry) {
            $user = get_user_by('ID', $entry['user_id']);
            $recent_activity[] = array(
                'type' => $entry['status'] === 'success' ? 'purchase' : 'failed_purchase',
                'user_name' => $user ? $user->display_name : 'Unknown User',
                'product_name' => $this->get_product_name($entry['product_id']),
                'amount' => $this->get_product_price($entry['product_id']),
                'timestamp' => $entry['timestamp'],
                'formatted_time' => human_time_diff(strtotime($entry['timestamp']), current_time('timestamp')) . ' ago'
            );
        }
        
        return $recent_activity;
    }
    
    /**
     * Register default dashboard widgets
     */
    private function register_default_widgets() {
        $default_widgets = array(
            'overview_metrics' => array(
                'title' => 'Overview Metrics',
                'type' => 'metrics',
                'position' => 1,
                'enabled' => true
            ),
            'revenue_chart' => array(
                'title' => 'Revenue Chart',
                'type' => 'line_chart',
                'position' => 2,
                'enabled' => true
            ),
            'product_sales' => array(
                'title' => 'Product Sales',
                'type' => 'doughnut_chart',
                'position' => 3,
                'enabled' => true
            ),
            'subscriptions_chart' => array(
                'title' => 'Subscriptions',
                'type' => 'bar_chart',
                'position' => 4,
                'enabled' => true
            ),
            'top_products' => array(
                'title' => 'Top Products',
                'type' => 'table',
                'position' => 5,
                'enabled' => true
            ),
            'recent_activity' => array(
                'title' => 'Recent Activity',
                'type' => 'activity_feed',
                'position' => 6,
                'enabled' => true
            )
        );
        
        $saved_widgets = get_option('skylearn_billing_pro_dashboard_widgets', $default_widgets);
        update_option('skylearn_billing_pro_dashboard_widgets', array_merge($default_widgets, $saved_widgets));
    }
    
    /**
     * AJAX handler for getting analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('skylearn_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $filters = isset($_POST['filters']) ? (array) $_POST['filters'] : array();
        
        // Sanitize filters
        foreach ($filters as $key => $value) {
            $filters[$key] = sanitize_text_field($value);
        }
        
        $data = $this->get_dashboard_analytics($filters);
        
        wp_send_json_success($data);
    }
    
    /**
     * AJAX handler for saving widget configuration
     */
    public function ajax_save_widget_config() {
        check_ajax_referer('skylearn_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $widgets = isset($_POST['widgets']) ? (array) $_POST['widgets'] : array();
        
        update_option('skylearn_billing_pro_dashboard_widgets', $widgets);
        
        wp_send_json_success(array('message' => 'Widget configuration saved.'));
    }
    
    /**
     * AJAX handler for real-time updates
     */
    public function ajax_realtime_update() {
        check_ajax_referer('skylearn_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $widget_type = sanitize_text_field($_POST['widget_type']);
        $filters = isset($_POST['filters']) ? (array) $_POST['filters'] : array();
        
        // Sanitize filters
        foreach ($filters as $key => $value) {
            $filters[$key] = sanitize_text_field($value);
        }
        
        $data = array();
        
        switch ($widget_type) {
            case 'overview_metrics':
                $data = $this->get_overview_metrics($filters);
                break;
            case 'revenue_chart':
                $data = $this->get_revenue_chart_data($filters);
                break;
            case 'subscriptions_chart':
                $data = $this->get_subscriptions_chart_data($filters);
                break;
            case 'product_sales_chart':
                $data = $this->get_product_sales_chart_data($filters);
                break;
            case 'recent_activity':
                $data = $this->get_recent_activity($filters);
                break;
        }
        
        wp_send_json_success($data);
    }
    
    // Helper methods
    
    private function get_product_price($product_id) {
        // In a real implementation, get actual product price
        return 99.99;
    }
    
    private function get_product_name($product_id) {
        return "Product #" . $product_id;
    }
    
    private function is_subscription_product($product_id) {
        // In a real implementation, check if product is subscription-based
        return rand(0, 1) === 1;
    }
    
    private function get_previous_period($date_from, $date_to) {
        $days_diff = (strtotime($date_to) - strtotime($date_from)) / (60 * 60 * 24);
        $from = date('Y-m-d', strtotime($date_from . " -{$days_diff} days"));
        $to = date('Y-m-d', strtotime($date_from . " -1 day"));
        
        return array('from' => $from, 'to' => $to);
    }
    
    private function calculate_period_metrics($date_from, $date_to) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $revenue = 0;
        $subscriptions = 0;
        $customers = array();
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] !== 'success') continue;
            
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            if ($entry_date >= $date_from && $entry_date <= $date_to) {
                $revenue += $this->get_product_price($entry['product_id']);
                
                if ($this->is_subscription_product($entry['product_id'])) {
                    $subscriptions++;
                }
                
                if (!in_array($entry['user_id'], $customers)) {
                    $customers[] = $entry['user_id'];
                }
            }
        }
        
        return array(
            'revenue' => $revenue,
            'subscriptions' => $subscriptions,
            'customers' => count($customers)
        );
    }
    
    private function calculate_percentage_change($old_value, $new_value) {
        if ($old_value == 0) {
            return $new_value > 0 ? 100 : 0;
        }
        
        return round((($new_value - $old_value) / $old_value) * 100, 2);
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
    
    private function calculate_median($values) {
        sort($values);
        $count = count($values);
        
        if ($count == 0) return 0;
        
        $middle = floor($count / 2);
        
        if ($count % 2) {
            return $values[$middle];
        } else {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
    }
    
    private function get_ltv_distribution($customer_values) {
        $ranges = array(
            '0-50' => 0,
            '51-100' => 0,
            '101-250' => 0,
            '251-500' => 0,
            '500+' => 0
        );
        
        foreach ($customer_values as $value) {
            if ($value <= 50) {
                $ranges['0-50']++;
            } elseif ($value <= 100) {
                $ranges['51-100']++;
            } elseif ($value <= 250) {
                $ranges['101-250']++;
            } elseif ($value <= 500) {
                $ranges['251-500']++;
            } else {
                $ranges['500+']++;
            }
        }
        
        return $ranges;
    }
}

/**
 * Get instance of analytics manager class
 *
 * @return SkyLearn_Billing_Pro_Analytics_Manager
 */
function skylearn_billing_pro_analytics_manager() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Analytics_Manager();
    }
    
    return $instance;
}