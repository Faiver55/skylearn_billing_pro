<?php
/**
 * Sales report template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$reporting = skylearn_billing_pro_reporting();
$sales_data = $reporting->get_sales_analytics();
?>

<div class="skylearn-reports-sales">
    <!-- Sales Metrics -->
    <div class="skylearn-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value sales-total-sales"><?php echo esc_html(number_format($sales_data['total_sales'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Total Sales', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value sales-successful"><?php echo esc_html(number_format($sales_data['successful_sales'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Successful Sales', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value sales-failed"><?php echo esc_html(number_format($sales_data['failed_sales'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Failed Sales', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value sales-conversion-rate"><?php echo esc_html($sales_data['conversion_rate']); ?>%</h3>
                <p class="metric-label"><?php esc_html_e('Conversion Rate', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>
    </div>

    <!-- Sales Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3><?php esc_html_e('Sales Over Time', 'skylearn-billing-pro'); ?></h3>
            <div class="chart-actions">
                <button type="button" class="button export-chart" data-chart="sales" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
                <button type="button" class="button export-chart" data-chart="sales" data-format="xls">
                    <?php esc_html_e('Export XLS', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="chart-content">
            <canvas id="sales-chart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Sales by Product Table -->
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
                        <th><?php esc_html_e('Percentage', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sales_data['sales_by_product'])): ?>
                        <?php 
                        $total = array_sum($sales_data['sales_by_product']);
                        foreach ($sales_data['sales_by_product'] as $product => $sales): 
                            $percentage = $total > 0 ? round(($sales / $total) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html($product); ?></td>
                                <td><?php echo esc_html(number_format($sales)); ?></td>
                                <td><?php echo esc_html($percentage); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="no-data-message">
                                <span class="dashicons dashicons-chart-bar"></span><br>
                                <?php esc_html_e('No sales data available for the selected period', 'skylearn-billing-pro'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sales by Course Table -->
    <div class="table-container">
        <div class="table-header">
            <h3><?php esc_html_e('Sales by Course', 'skylearn-billing-pro'); ?></h3>
            <div class="table-actions">
                <button type="button" class="button export-table" data-table="sales-by-course" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="table-content">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Course', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Sales', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Success Rate', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($sales_data['sales_by_course'])): ?>
                        <?php foreach ($sales_data['sales_by_course'] as $course => $sales): ?>
                            <tr>
                                <td><?php echo esc_html($course); ?></td>
                                <td><?php echo esc_html(number_format($sales)); ?></td>
                                <td>85%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="no-data-message">
                                <span class="dashicons dashicons-welcome-learn-more"></span><br>
                                <?php esc_html_e('No course sales data available', 'skylearn-billing-pro'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize sales chart
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('sales-chart');
        if (ctx) {
            var salesData = <?php echo json_encode($sales_data['sales_by_day']); ?>;
            var labels = Object.keys(salesData);
            var data = Object.values(salesData);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales',
                        data: data,
                        borderColor: '#FF3B00',
                        backgroundColor: 'rgba(255, 59, 0, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Sales'
                        }
                    }
                }
            });
        }
    }
});
</script>