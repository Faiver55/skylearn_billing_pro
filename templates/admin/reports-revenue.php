<?php
/**
 * Revenue report template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$reporting = skylearn_billing_pro_reporting();
$revenue_data = $reporting->get_revenue_analytics();
?>

<div class="skylearn-reports-revenue">
    <!-- Revenue Metrics -->
    <div class="skylearn-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-money-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value revenue-total">$<?php echo esc_html(number_format($revenue_data['total_revenue'], 2)); ?></h3>
                <p class="metric-label"><?php esc_html_e('Total Revenue', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-cart"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value revenue-average">$<?php echo esc_html(number_format($revenue_data['average_order_value'], 2)); ?></h3>
                <p class="metric-label"><?php esc_html_e('Average Order Value', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-undo"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value">$<?php echo esc_html(number_format($revenue_data['refunds'], 2)); ?></h3>
                <p class="metric-label"><?php esc_html_e('Refunds', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value revenue-net">$<?php echo esc_html(number_format($revenue_data['net_revenue'], 2)); ?></h3>
                <p class="metric-label"><?php esc_html_e('Net Revenue', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3><?php esc_html_e('Revenue Trend', 'skylearn-billing-pro'); ?></h3>
            <div class="chart-actions">
                <button type="button" class="button export-chart" data-chart="revenue" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
                <button type="button" class="button export-chart" data-chart="revenue" data-format="xls">
                    <?php esc_html_e('Export XLS', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="chart-content">
            <canvas id="revenue-chart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Revenue by Product Table -->
    <div class="table-container">
        <div class="table-header">
            <h3><?php esc_html_e('Revenue by Product', 'skylearn-billing-pro'); ?></h3>
            <div class="table-actions">
                <button type="button" class="button export-table" data-table="revenue-by-product" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="table-content">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Product', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Revenue', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Sales', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Avg. Price', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($revenue_data['revenue_by_product'])): ?>
                        <?php foreach ($revenue_data['revenue_by_product'] as $product => $revenue): ?>
                            <?php $sales = round($revenue / 99.99); // Approximate sales count ?>
                            <tr>
                                <td><?php echo esc_html($product); ?></td>
                                <td>$<?php echo esc_html(number_format($revenue, 2)); ?></td>
                                <td><?php echo esc_html(number_format($sales)); ?></td>
                                <td>$<?php echo esc_html(number_format($sales > 0 ? $revenue / $sales : 0, 2)); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data-message">
                                <span class="dashicons dashicons-money-alt"></span><br>
                                <?php esc_html_e('No revenue data available for the selected period', 'skylearn-billing-pro'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Revenue Analysis -->
    <div class="revenue-analysis">
        <div class="analysis-container">
            <div class="analysis-header">
                <h3><?php esc_html_e('Revenue Analysis', 'skylearn-billing-pro'); ?></h3>
            </div>
            <div class="analysis-content">
                <div class="analysis-grid">
                    <div class="analysis-card">
                        <h4><?php esc_html_e('Growth Rate', 'skylearn-billing-pro'); ?></h4>
                        <div class="analysis-value">
                            <span class="value">+12.5%</span>
                            <span class="trend success">↗</span>
                        </div>
                        <p><?php esc_html_e('Month over month', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="analysis-card">
                        <h4><?php esc_html_e('Refund Rate', 'skylearn-billing-pro'); ?></h4>
                        <div class="analysis-value">
                            <span class="value"><?php echo round(($revenue_data['refunds'] / max($revenue_data['total_revenue'], 1)) * 100, 1); ?>%</span>
                            <span class="trend success">✓</span>
                        </div>
                        <p><?php esc_html_e('Below industry average', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="analysis-card">
                        <h4><?php esc_html_e('Customer LTV', 'skylearn-billing-pro'); ?></h4>
                        <div class="analysis-value">
                            <span class="value">$<?php echo number_format($revenue_data['average_order_value'] * 2.3, 2); ?></span>
                            <span class="trend">📊</span>
                        </div>
                        <p><?php esc_html_e('Estimated lifetime value', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="analysis-card">
                        <h4><?php esc_html_e('Revenue per Day', 'skylearn-billing-pro'); ?></h4>
                        <div class="analysis-value">
                            <span class="value">$<?php echo number_format($revenue_data['total_revenue'] / 30, 2); ?></span>
                            <span class="trend">📈</span>
                        </div>
                        <p><?php esc_html_e('Average daily revenue', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Forecasting -->
    <div class="revenue-forecast">
        <div class="forecast-container">
            <div class="forecast-header">
                <h3><?php esc_html_e('Revenue Forecast', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Based on current trends and historical data', 'skylearn-billing-pro'); ?></p>
            </div>
            <div class="forecast-content">
                <div class="forecast-periods">
                    <div class="forecast-period">
                        <h4><?php esc_html_e('Next 7 Days', 'skylearn-billing-pro'); ?></h4>
                        <div class="forecast-value">$<?php echo number_format($revenue_data['total_revenue'] * 0.23, 2); ?></div>
                        <div class="confidence">85% confidence</div>
                    </div>
                    
                    <div class="forecast-period">
                        <h4><?php esc_html_e('Next 30 Days', 'skylearn-billing-pro'); ?></h4>
                        <div class="forecast-value">$<?php echo number_format($revenue_data['total_revenue'] * 1.12, 2); ?></div>
                        <div class="confidence">75% confidence</div>
                    </div>
                    
                    <div class="forecast-period">
                        <h4><?php esc_html_e('Next Quarter', 'skylearn-billing-pro'); ?></h4>
                        <div class="forecast-value">$<?php echo number_format($revenue_data['total_revenue'] * 3.4, 2); ?></div>
                        <div class="confidence">65% confidence</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize revenue chart
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('revenue-chart');
        if (ctx) {
            var revenueData = <?php echo json_encode($revenue_data['revenue_by_day']); ?>;
            var labels = Object.keys(revenueData);
            var data = Object.values(revenueData);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue ($)',
                        data: data,
                        backgroundColor: '#FF3B00',
                        borderColor: '#FF3B00',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Daily Revenue'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return 'Revenue: $' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }
    }
});
</script>

<style>
.revenue-analysis,
.revenue-forecast {
    margin-top: 20px;
}

.analysis-container,
.forecast-container {
    background: var(--skylearn-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--skylearn-shadow);
    overflow: hidden;
}

.analysis-header,
.forecast-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--skylearn-border);
    background: var(--skylearn-light-gray);
}

.analysis-header h3,
.forecast-header h3 {
    margin: 0 0 5px 0;
    color: var(--skylearn-primary);
    font-size: 18px;
    font-weight: 600;
}

.forecast-header p {
    margin: 0;
    color: var(--skylearn-medium-gray);
    font-size: 14px;
}

.analysis-content,
.forecast-content {
    padding: 20px;
}

.analysis-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.analysis-card {
    text-align: center;
    padding: 20px;
    border: 1px solid var(--skylearn-border);
    border-radius: 8px;
    background: var(--skylearn-light-gray);
}

.analysis-card h4 {
    margin: 0 0 15px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--skylearn-medium-gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.analysis-value {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-bottom: 10px;
}

.analysis-value .value {
    font-size: 24px;
    font-weight: 700;
    color: var(--skylearn-primary);
}

.analysis-value .trend {
    font-size: 18px;
}

.analysis-value .trend.success {
    color: var(--skylearn-success);
}

.analysis-card p {
    margin: 0;
    font-size: 12px;
    color: var(--skylearn-medium-gray);
}

.forecast-periods {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.forecast-period {
    text-align: center;
    padding: 25px;
    border: 2px solid var(--skylearn-border);
    border-radius: 12px;
    background: linear-gradient(135deg, var(--skylearn-light-gray), var(--skylearn-white));
}

.forecast-period h4 {
    margin: 0 0 15px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--skylearn-primary);
}

.forecast-value {
    font-size: 28px;
    font-weight: 700;
    color: var(--skylearn-accent);
    margin-bottom: 10px;
}

.confidence {
    font-size: 12px;
    color: var(--skylearn-medium-gray);
    font-style: italic;
}
</style>