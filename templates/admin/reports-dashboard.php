<?php
/**
 * Reports dashboard template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$reporting = skylearn_billing_pro_reporting();

// Get current data
$sales_data = $reporting->get_sales_analytics();
$enrollment_data = $reporting->get_enrollment_analytics();
$email_data = $reporting->get_email_analytics();
$revenue_data = $reporting->get_revenue_analytics();
?>

<div class="skylearn-reports-dashboard">
    <!-- Key Metrics Cards -->
    <div class="skylearn-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo esc_html(number_format($sales_data['total_sales'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Total Sales', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend success">
                    <?php echo esc_html($sales_data['conversion_rate']); ?>% <?php esc_html_e('conversion', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo esc_html(number_format($enrollment_data['total_enrollments'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Total Enrollments', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend success">
                    <?php echo esc_html(number_format($enrollment_data['successful_enrollments'])); ?> <?php esc_html_e('successful', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value">$<?php echo esc_html(number_format($revenue_data['total_revenue'], 2)); ?></h3>
                <p class="metric-label"><?php esc_html_e('Total Revenue', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend success">
                    $<?php echo esc_html(number_format($revenue_data['average_order_value'], 2)); ?> <?php esc_html_e('avg order', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo esc_html(number_format($email_data['total_emails'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Emails Sent', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend success">
                    <?php echo esc_html($email_data['delivery_rate']); ?>% <?php esc_html_e('delivered', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="skylearn-charts-section">
        <div class="chart-container">
            <div class="chart-header">
                <h3><?php esc_html_e('Sales Trend', 'skylearn-billing-pro'); ?></h3>
                <div class="chart-actions">
                    <button type="button" class="button export-chart" data-chart="sales-trend" data-format="csv">
                        <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
            <div class="chart-content">
                <canvas id="sales-trend-chart" width="400" height="200"></canvas>
            </div>
        </div>

        <div class="chart-container">
            <div class="chart-header">
                <h3><?php esc_html_e('Revenue by Day', 'skylearn-billing-pro'); ?></h3>
                <div class="chart-actions">
                    <button type="button" class="button export-chart" data-chart="revenue-trend" data-format="csv">
                        <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
            <div class="chart-content">
                <canvas id="revenue-trend-chart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Tables Section -->
    <div class="skylearn-tables-section">
        <div class="table-container">
            <div class="table-header">
                <h3><?php esc_html_e('Most Popular Courses', 'skylearn-billing-pro'); ?></h3>
                <div class="table-actions">
                    <button type="button" class="button export-table" data-table="popular-courses" data-format="csv">
                        <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
            <div class="table-content">
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Course', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Enrollments', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Revenue', 'skylearn-billing-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($enrollment_data['most_popular_courses'])): ?>
                            <?php foreach ($enrollment_data['most_popular_courses'] as $course => $enrollments): ?>
                                <tr>
                                    <td><?php echo esc_html($course); ?></td>
                                    <td><?php echo esc_html(number_format($enrollments)); ?></td>
                                    <td>$<?php echo esc_html(number_format($enrollments * 99.99, 2)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php esc_html_e('No data available', 'skylearn-billing-pro'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="table-container">
            <div class="table-header">
                <h3><?php esc_html_e('Sales by Product', 'skylearn-billing-pro'); ?></h3>
                <div class="table-actions">
                    <button type="button" class="button export-table" data-table="sales-by-product" data-format="csv">
                        <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
            <div class="table-content">
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Product', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Sales', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Conversion Rate', 'skylearn-billing-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($sales_data['sales_by_product'])): ?>
                            <?php foreach ($sales_data['sales_by_product'] as $product => $sales): ?>
                                <tr>
                                    <td><?php echo esc_html($product); ?></td>
                                    <td><?php echo esc_html(number_format($sales)); ?></td>
                                    <td>85%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3"><?php esc_html_e('No data available', 'skylearn-billing-pro'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="skylearn-quick-actions">
        <h3><?php esc_html_e('Quick Actions', 'skylearn-billing-pro'); ?></h3>
        <div class="action-buttons">
            <button type="button" class="button button-primary export-all-reports">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export All Reports', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button refresh-data">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh Data', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button schedule-report">
                <span class="dashicons dashicons-calendar-alt"></span>
                <?php esc_html_e('Schedule Report', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize dashboard charts
    SkyLearnReports.initDashboardCharts({
        salesData: <?php echo json_encode($sales_data['sales_by_day']); ?>,
        revenueData: <?php echo json_encode($revenue_data['revenue_by_day']); ?>
    });
});
</script>