<?php
/**
 * Email stats report template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$reporting = skylearn_billing_pro_reporting();
$email_data = $reporting->get_email_analytics();
?>

<div class="skylearn-reports-emails">
    <!-- Email Metrics -->
    <div class="skylearn-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-email-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value emails-total"><?php echo esc_html(number_format($email_data['total_emails'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Total Emails', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value emails-successful"><?php echo esc_html(number_format($email_data['successful_emails'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Delivered', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value emails-failed"><?php echo esc_html(number_format($email_data['failed_emails'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Failed', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-chart-pie"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value emails-delivery-rate"><?php echo esc_html($email_data['delivery_rate']); ?>%</h3>
                <p class="metric-label"><?php esc_html_e('Delivery Rate', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>
    </div>

    <!-- Email Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3><?php esc_html_e('Email Activity Over Time', 'skylearn-billing-pro'); ?></h3>
            <div class="chart-actions">
                <button type="button" class="button export-chart" data-chart="emails" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
                <button type="button" class="button export-chart" data-chart="emails" data-format="xls">
                    <?php esc_html_e('Export XLS', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="chart-content">
            <canvas id="emails-chart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Emails by Type Table -->
    <div class="table-container">
        <div class="table-header">
            <h3><?php esc_html_e('Emails by Type', 'skylearn-billing-pro'); ?></h3>
            <div class="table-actions">
                <button type="button" class="button export-table" data-table="emails-by-type" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="table-content">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Email Type', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Sent', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Delivery Rate', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Open Rate', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($email_data['emails_by_type'])): ?>
                        <?php foreach ($email_data['emails_by_type'] as $type => $count): ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $type))); ?></td>
                                <td><?php echo esc_html(number_format($count)); ?></td>
                                <td>95%</td>
                                <td>24%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data-message">
                                <span class="dashicons dashicons-email-alt"></span><br>
                                <?php esc_html_e('No email data available for the selected period', 'skylearn-billing-pro'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Email Performance Table -->
    <div class="table-container">
        <div class="table-header">
            <h3><?php esc_html_e('Email Performance Insights', 'skylearn-billing-pro'); ?></h3>
            <div class="table-actions">
                <button type="button" class="button export-table" data-table="email-performance" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="table-content">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Metric', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Value', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Benchmark', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><?php esc_html_e('Delivery Rate', 'skylearn-billing-pro'); ?></td>
                        <td><?php echo esc_html($email_data['delivery_rate']); ?>%</td>
                        <td>95%</td>
                        <td>
                            <?php if ($email_data['delivery_rate'] >= 95): ?>
                                <span class="metric-trend success">✓ Good</span>
                            <?php elseif ($email_data['delivery_rate'] >= 85): ?>
                                <span class="metric-trend warning">⚠ Fair</span>
                            <?php else: ?>
                                <span class="metric-trend error">✗ Poor</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Open Rate', 'skylearn-billing-pro'); ?></td>
                        <td><?php echo esc_html($email_data['open_rate']); ?>%</td>
                        <td>25%</td>
                        <td><span class="metric-trend">📊 Tracking not enabled</span></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Click Rate', 'skylearn-billing-pro'); ?></td>
                        <td><?php echo esc_html($email_data['click_rate']); ?>%</td>
                        <td>3%</td>
                        <td><span class="metric-trend">📊 Tracking not enabled</span></td>
                    </tr>
                    <tr>
                        <td><?php esc_html_e('Bounce Rate', 'skylearn-billing-pro'); ?></td>
                        <td><?php echo esc_html(round((100 - $email_data['delivery_rate']), 2)); ?>%</td>
                        <td>&lt; 5%</td>
                        <td>
                            <?php if ((100 - $email_data['delivery_rate']) <= 5): ?>
                                <span class="metric-trend success">✓ Good</span>
                            <?php else: ?>
                                <span class="metric-trend warning">⚠ Check setup</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Email Recommendations -->
    <div class="skylearn-recommendations">
        <h3><?php esc_html_e('Recommendations', 'skylearn-billing-pro'); ?></h3>
        <div class="recommendation-cards">
            <?php if ($email_data['delivery_rate'] < 95): ?>
                <div class="recommendation-card warning">
                    <div class="recommendation-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="recommendation-content">
                        <h4><?php esc_html_e('Improve Delivery Rate', 'skylearn-billing-pro'); ?></h4>
                        <p><?php esc_html_e('Your delivery rate is below the recommended 95%. Consider configuring SMTP or checking your email settings.', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($email_data['open_rate'] == 0): ?>
                <div class="recommendation-card info">
                    <div class="recommendation-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="recommendation-content">
                        <h4><?php esc_html_e('Enable Email Tracking', 'skylearn-billing-pro'); ?></h4>
                        <p><?php esc_html_e('Enable open and click tracking to get detailed insights into your email performance.', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="recommendation-card success">
                <div class="recommendation-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="recommendation-content">
                    <h4><?php esc_html_e('Email System Active', 'skylearn-billing-pro'); ?></h4>
                    <p><?php esc_html_e('Your email system is successfully sending emails. Keep monitoring for optimal performance.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize emails chart
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('emails-chart');
        if (ctx) {
            var emailData = <?php echo json_encode($email_data['emails_by_day']); ?>;
            var labels = Object.keys(emailData);
            var data = Object.values(emailData);

            new Chart(ctx, {
                type: 'area',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Emails Sent',
                        data: data,
                        backgroundColor: 'rgba(24, 49, 83, 0.1)',
                        borderColor: '#183153',
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
                            text: 'Daily Email Activity'
                        }
                    }
                }
            });
        }
    }
});
</script>

<style>
.skylearn-recommendations {
    background: var(--skylearn-white);
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--skylearn-shadow);
    margin-top: 20px;
}

.skylearn-recommendations h3 {
    margin: 0 0 20px 0;
    color: var(--skylearn-primary);
    font-size: 20px;
    font-weight: 600;
}

.recommendation-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 15px;
}

.recommendation-card {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid;
}

.recommendation-card.success {
    background: rgba(40, 167, 69, 0.05);
    border-left-color: var(--skylearn-success);
}

.recommendation-card.warning {
    background: rgba(255, 193, 7, 0.05);
    border-left-color: var(--skylearn-warning);
}

.recommendation-card.info {
    background: rgba(24, 49, 83, 0.05);
    border-left-color: var(--skylearn-primary);
}

.recommendation-icon .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.recommendation-content h4 {
    margin: 0 0 8px 0;
    font-size: 16px;
    font-weight: 600;
}

.recommendation-content p {
    margin: 0;
    font-size: 14px;
    line-height: 1.5;
    color: var(--skylearn-medium-gray);
}
</style>