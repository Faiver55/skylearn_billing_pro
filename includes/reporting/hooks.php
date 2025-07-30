<?php
/**
 * Reporting Hooks for Skylearn Billing Pro
 *
 * Developer hooks for custom reporting modules
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
 * Developer Hooks for Custom Reporting Modules
 */

/**
 * Filter to register custom report types
 *
 * @param array $report_types Existing report types
 * @return array Modified report types
 */
function skylearn_billing_register_custom_report_types($report_types) {
    // Default report types
    $default_types = array(
        'sales' => array(
            'name' => 'Sales Report',
            'description' => 'Detailed sales data with customer and product information',
            'icon' => 'dashicons-cart',
            'columns' => array(
                'date' => 'Date',
                'order_id' => 'Order ID',
                'customer_name' => 'Customer',
                'customer_email' => 'Email',
                'product_name' => 'Product',
                'amount' => 'Amount',
                'status' => 'Status',
                'gateway' => 'Gateway'
            )
        ),
        'customers' => array(
            'name' => 'Customer Report',
            'description' => 'Customer analytics and lifetime value data',
            'icon' => 'dashicons-groups',
            'columns' => array(
                'customer_name' => 'Customer',
                'customer_email' => 'Email',
                'registration_date' => 'Registered',
                'total_orders' => 'Orders',
                'total_spent' => 'Total Spent',
                'last_purchase_date' => 'Last Purchase'
            )
        ),
        'products' => array(
            'name' => 'Product Report',
            'description' => 'Product performance and sales analytics',
            'icon' => 'dashicons-products',
            'columns' => array(
                'product_name' => 'Product',
                'successful_sales' => 'Sales',
                'total_revenue' => 'Revenue',
                'conversion_rate' => 'Conversion Rate',
                'unique_customers' => 'Unique Buyers'
            )
        ),
        'revenue' => array(
            'name' => 'Revenue Report',
            'description' => 'Revenue trends and period-based analysis',
            'icon' => 'dashicons-chart-line',
            'columns' => array(
                'period' => 'Period',
                'revenue' => 'Revenue',
                'orders' => 'Orders',
                'average_order_value' => 'AOV',
                'unique_customers' => 'Customers'
            )
        ),
        'subscriptions' => array(
            'name' => 'Subscription Report',
            'description' => 'Subscription analytics and churn data',
            'icon' => 'dashicons-update',
            'columns' => array(
                'subscription_id' => 'Subscription ID',
                'customer_name' => 'Customer',
                'plan_name' => 'Plan',
                'status' => 'Status',
                'start_date' => 'Started',
                'mrr' => 'MRR'
            )
        )
    );
    
    return array_merge($default_types, $report_types);
}
add_filter('skylearn_billing_report_types', 'skylearn_billing_register_custom_report_types');

/**
 * Action hook for custom report data generation
 *
 * Allows developers to add custom logic for generating report data
 *
 * @param array $report_data Current report data
 * @param array $config Report configuration
 * @param string $report_type Type of report being generated
 */
// do_action('skylearn_billing_generate_report_data', $report_data, $config, $report_type);

/**
 * Filter for custom report data
 *
 * Allows developers to modify or replace report data completely
 *
 * @param array $report_data Generated report data
 * @param array $config Report configuration
 * @return array Modified report data
 */
function skylearn_billing_custom_report_data_filter($report_data, $config) {
    return apply_filters('skylearn_billing_custom_report_data', $report_data, $config);
}

/**
 * Filter for custom analytics widgets
 *
 * Allows developers to add custom dashboard widgets
 *
 * @param array $widgets Existing widgets
 * @return array Modified widgets
 */
function skylearn_billing_register_custom_analytics_widgets($widgets) {
    // Example custom widget registration
    $custom_widgets = array(
        'custom_kpi' => array(
            'title' => 'Custom KPI Widget',
            'type' => 'custom_metrics',
            'description' => 'Display custom key performance indicators',
            'position' => 7,
            'enabled' => false,
            'callback' => 'render_custom_kpi_widget',
            'data_callback' => 'get_custom_kpi_data'
        )
    );
    
    return array_merge($widgets, $custom_widgets);
}
add_filter('skylearn_billing_analytics_widgets', 'skylearn_billing_register_custom_analytics_widgets');

/**
 * Action hook for custom widget rendering
 *
 * @param string $widget_type Widget type
 * @param array $widget_config Widget configuration
 * @param array $widget_data Widget data
 */
// do_action('skylearn_billing_render_analytics_widget', $widget_type, $widget_config, $widget_data);

/**
 * Filter for custom export formats
 *
 * Allows developers to add custom export formats beyond CSV and PDF
 *
 * @param array $formats Existing export formats
 * @return array Modified export formats
 */
function skylearn_billing_register_custom_export_formats($formats) {
    $default_formats = array(
        'csv' => array(
            'name' => 'CSV',
            'mime_type' => 'text/csv',
            'extension' => 'csv',
            'callback' => 'export_csv'
        ),
        'pdf' => array(
            'name' => 'PDF',
            'mime_type' => 'application/pdf',
            'extension' => 'pdf',
            'callback' => 'export_pdf'
        ),
        'xlsx' => array(
            'name' => 'Excel',
            'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'extension' => 'xlsx',
            'callback' => 'export_xlsx'
        )
    );
    
    return array_merge($default_formats, $formats);
}
add_filter('skylearn_billing_export_formats', 'skylearn_billing_register_custom_export_formats');

/**
 * Action hook for custom export processing
 *
 * @param array $report_data Report data to export
 * @param string $format Export format
 * @param array $options Export options
 */
// do_action('skylearn_billing_custom_export', $report_data, $format, $options);

/**
 * Filter for custom report filters
 *
 * Allows developers to add custom filter options to reports
 *
 * @param array $filters Existing filters
 * @param string $report_type Report type
 * @return array Modified filters
 */
function skylearn_billing_register_custom_report_filters($filters, $report_type) {
    $custom_filters = array();
    
    // Add custom filters based on report type
    switch ($report_type) {
        case 'sales':
            $custom_filters['payment_method'] = array(
                'label' => 'Payment Method',
                'type' => 'select',
                'options' => array(
                    '' => 'All Methods',
                    'stripe' => 'Stripe',
                    'paypal' => 'PayPal',
                    'paddle' => 'Paddle'
                )
            );
            break;
        case 'customers':
            $custom_filters['customer_segment'] = array(
                'label' => 'Customer Segment',
                'type' => 'select',
                'options' => array(
                    '' => 'All Segments',
                    'new' => 'New Customers',
                    'returning' => 'Returning Customers',
                    'vip' => 'VIP Customers'
                )
            );
            break;
    }
    
    return array_merge($filters, $custom_filters);
}
add_filter('skylearn_billing_report_filters', 'skylearn_billing_register_custom_report_filters', 10, 2);

/**
 * Action hook for custom report scheduling
 *
 * @param array $schedule_config Schedule configuration
 * @param array $report_config Report configuration
 */
// do_action('skylearn_billing_schedule_custom_report', $schedule_config, $report_config);

/**
 * Filter for custom chart types
 *
 * Allows developers to add custom chart types for analytics dashboard
 *
 * @param array $chart_types Existing chart types
 * @return array Modified chart types
 */
function skylearn_billing_register_custom_chart_types($chart_types) {
    $default_chart_types = array(
        'line_chart' => array(
            'name' => 'Line Chart',
            'description' => 'Line chart for trend visualization',
            'library' => 'chartjs',
            'type' => 'line'
        ),
        'bar_chart' => array(
            'name' => 'Bar Chart',
            'description' => 'Bar chart for comparative data',
            'library' => 'chartjs',
            'type' => 'bar'
        ),
        'doughnut_chart' => array(
            'name' => 'Doughnut Chart',
            'description' => 'Doughnut chart for proportional data',
            'library' => 'chartjs',
            'type' => 'doughnut'
        ),
        'pie_chart' => array(
            'name' => 'Pie Chart',
            'description' => 'Pie chart for proportional data',
            'library' => 'chartjs',
            'type' => 'pie'
        )
    );
    
    return array_merge($default_chart_types, $chart_types);
}
add_filter('skylearn_billing_chart_types', 'skylearn_billing_register_custom_chart_types');

/**
 * Helper Functions for Developers
 */

/**
 * Register a custom report type
 *
 * @param string $type Report type identifier
 * @param array $config Report type configuration
 */
function skylearn_billing_register_report_type($type, $config) {
    add_filter('skylearn_billing_report_types', function($report_types) use ($type, $config) {
        $report_types[$type] = $config;
        return $report_types;
    });
}

/**
 * Register a custom analytics widget
 *
 * @param string $widget_id Widget identifier
 * @param array $widget_config Widget configuration
 */
function skylearn_billing_register_analytics_widget($widget_id, $widget_config) {
    add_filter('skylearn_billing_analytics_widgets', function($widgets) use ($widget_id, $widget_config) {
        $widgets[$widget_id] = $widget_config;
        return $widgets;
    });
}

/**
 * Add custom data to a report
 *
 * @param string $report_type Report type to modify
 * @param callable $callback Function to generate custom data
 */
function skylearn_billing_add_custom_report_data($report_type, $callback) {
    add_filter('skylearn_billing_custom_report_data', function($data, $config) use ($report_type, $callback) {
        if ($config['type'] === $report_type) {
            $custom_data = call_user_func($callback, $config);
            if (is_array($custom_data)) {
                $data = array_merge($data, $custom_data);
            }
        }
        return $data;
    }, 10, 2);
}

/**
 * Register a custom export format
 *
 * @param string $format_id Format identifier
 * @param array $format_config Format configuration
 */
function skylearn_billing_register_export_format($format_id, $format_config) {
    add_filter('skylearn_billing_export_formats', function($formats) use ($format_id, $format_config) {
        $formats[$format_id] = $format_config;
        return $formats;
    });
}

/**
 * Add custom filter to reports
 *
 * @param string $filter_id Filter identifier
 * @param array $filter_config Filter configuration
 * @param array $report_types Report types to apply to (empty for all)
 */
function skylearn_billing_add_report_filter($filter_id, $filter_config, $report_types = array()) {
    add_filter('skylearn_billing_report_filters', function($filters, $report_type) use ($filter_id, $filter_config, $report_types) {
        if (empty($report_types) || in_array($report_type, $report_types)) {
            $filters[$filter_id] = $filter_config;
        }
        return $filters;
    }, 10, 2);
}

/**
 * Get report data using custom queries
 *
 * Helper function for developers to create custom report data sources
 *
 * @param string $query_type Type of query (sales, customers, products, etc.)
 * @param array $filters Filters to apply
 * @return array Query results
 */
function skylearn_billing_get_custom_report_data($query_type, $filters = array()) {
    global $wpdb;
    
    $data = array();
    
    switch ($query_type) {
        case 'sales':
            // Example custom sales query
            // In a real implementation, this would query actual sales tables
            $data = apply_filters('skylearn_billing_custom_sales_query', $data, $filters);
            break;
            
        case 'customers':
            // Example custom customers query
            $data = apply_filters('skylearn_billing_custom_customers_query', $data, $filters);
            break;
            
        case 'products':
            // Example custom products query
            $data = apply_filters('skylearn_billing_custom_products_query', $data, $filters);
            break;
    }
    
    return $data;
}

/**
 * Example Custom Widget Implementation
 */

/**
 * Render custom KPI widget
 *
 * @param array $widget_config Widget configuration
 * @param array $widget_data Widget data
 */
function render_custom_kpi_widget($widget_config, $widget_data) {
    ?>
    <div class="skylearn-analytics-widget custom-kpi-widget">
        <div class="widget-header">
            <h3><?php echo esc_html($widget_config['title']); ?></h3>
        </div>
        <div class="widget-content">
            <div class="kpi-grid">
                <?php foreach ($widget_data as $kpi => $value): ?>
                    <div class="kpi-item">
                        <div class="kpi-value"><?php echo esc_html($value); ?></div>
                        <div class="kpi-label"><?php echo esc_html(ucwords(str_replace('_', ' ', $kpi))); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Get custom KPI data
 *
 * @param array $filters Data filters
 * @return array KPI data
 */
function get_custom_kpi_data($filters = array()) {
    // Example custom KPI calculation
    return array(
        'custom_metric_1' => 123,
        'custom_metric_2' => 456,
        'custom_metric_3' => 789
    );
}

/**
 * Example Custom Report Type Implementation
 */

/**
 * Register affiliate report type
 */
function register_affiliate_report_type() {
    skylearn_billing_register_report_type('affiliates', array(
        'name' => 'Affiliate Report',
        'description' => 'Affiliate performance and commissions data',
        'icon' => 'dashicons-networking',
        'columns' => array(
            'affiliate_name' => 'Affiliate',
            'affiliate_email' => 'Email',
            'referrals' => 'Referrals',
            'conversions' => 'Conversions',
            'commission_earned' => 'Commission',
            'conversion_rate' => 'Conversion Rate'
        )
    ));
}
// Uncomment to register the affiliate report type
// add_action('init', 'register_affiliate_report_type');

/**
 * Example Custom Export Format Implementation
 */

/**
 * Register JSON export format
 */
function register_json_export_format() {
    skylearn_billing_register_export_format('json', array(
        'name' => 'JSON',
        'mime_type' => 'application/json',
        'extension' => 'json',
        'callback' => 'export_report_as_json'
    ));
}
// Uncomment to register the JSON export format
// add_action('init', 'register_json_export_format');

/**
 * Export report as JSON
 *
 * @param array $report_data Report data
 * @param array $options Export options
 */
function export_report_as_json($report_data, $options = array()) {
    $filename = sanitize_file_name($report_data['config']['name']) . '_' . date('Y-m-d') . '.json';
    
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo json_encode($report_data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Documentation for Developers
 */

/**
 * DEVELOPER DOCUMENTATION
 * 
 * This file provides hooks and helper functions for extending the Skylearn Billing Pro
 * reporting system with custom functionality.
 * 
 * CUSTOM REPORT TYPES:
 * 
 * To add a custom report type:
 * 
 * skylearn_billing_register_report_type('my_custom_report', array(
 *     'name' => 'My Custom Report',
 *     'description' => 'Description of the report',
 *     'icon' => 'dashicons-chart-bar',
 *     'columns' => array(
 *         'column1' => 'Column 1 Label',
 *         'column2' => 'Column 2 Label'
 *     )
 * ));
 * 
 * CUSTOM ANALYTICS WIDGETS:
 * 
 * To add a custom dashboard widget:
 * 
 * skylearn_billing_register_analytics_widget('my_widget', array(
 *     'title' => 'My Custom Widget',
 *     'type' => 'custom',
 *     'description' => 'Widget description',
 *     'position' => 10,
 *     'enabled' => true,
 *     'callback' => 'my_widget_render_function',
 *     'data_callback' => 'my_widget_data_function'
 * ));
 * 
 * CUSTOM EXPORT FORMATS:
 * 
 * To add a custom export format:
 * 
 * skylearn_billing_register_export_format('xml', array(
 *     'name' => 'XML',
 *     'mime_type' => 'application/xml',
 *     'extension' => 'xml',
 *     'callback' => 'my_xml_export_function'
 * ));
 * 
 * CUSTOM REPORT FILTERS:
 * 
 * To add custom filters to reports:
 * 
 * skylearn_billing_add_report_filter('my_filter', array(
 *     'label' => 'My Filter',
 *     'type' => 'select',
 *     'options' => array(
 *         'option1' => 'Option 1',
 *         'option2' => 'Option 2'
 *     )
 * ), array('sales', 'customers')); // Apply to specific report types
 * 
 * CUSTOM DATA SOURCES:
 * 
 * To add custom data to reports:
 * 
 * skylearn_billing_add_custom_report_data('sales', function($config) {
 *     // Your custom data generation logic
 *     return array(
 *         array('column1' => 'value1', 'column2' => 'value2'),
 *         array('column1' => 'value3', 'column2' => 'value4')
 *     );
 * });
 * 
 * AVAILABLE HOOKS:
 * 
 * Actions:
 * - skylearn_billing_generate_report_data: Called when generating report data
 * - skylearn_billing_render_analytics_widget: Called when rendering dashboard widgets
 * - skylearn_billing_custom_export: Called when exporting reports
 * - skylearn_billing_schedule_custom_report: Called when scheduling reports
 * 
 * Filters:
 * - skylearn_billing_report_types: Modify available report types
 * - skylearn_billing_analytics_widgets: Modify dashboard widgets
 * - skylearn_billing_custom_report_data: Modify report data
 * - skylearn_billing_export_formats: Modify export formats
 * - skylearn_billing_report_filters: Modify report filters
 * - skylearn_billing_chart_types: Modify chart types
 */