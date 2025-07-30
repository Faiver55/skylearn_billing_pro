<?php
/**
 * Cohort analysis report template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$reporting = skylearn_billing_pro_reporting();
$cohort_data = $reporting->get_cohort_analytics();
?>

<div class="skylearn-reports-cohort">
    <!-- Cohort Settings -->
    <div class="cohort-settings">
        <div class="settings-container">
            <div class="settings-header">
                <h3><?php esc_html_e('Cohort Analysis Settings', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Analyze user retention patterns by enrollment period', 'skylearn-billing-pro'); ?></p>
            </div>
            <div class="settings-content">
                <div class="setting-group">
                    <label for="cohort-period"><?php esc_html_e('Cohort Period:', 'skylearn-billing-pro'); ?></label>
                    <select id="cohort-period" name="cohort_period">
                        <option value="monthly"><?php esc_html_e('Monthly', 'skylearn-billing-pro'); ?></option>
                        <option value="weekly"><?php esc_html_e('Weekly', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
                <div class="setting-group">
                    <label for="cohort-metric"><?php esc_html_e('Metric:', 'skylearn-billing-pro'); ?></label>
                    <select id="cohort-metric" name="cohort_metric">
                        <option value="retention"><?php esc_html_e('User Retention', 'skylearn-billing-pro'); ?></option>
                        <option value="revenue"><?php esc_html_e('Revenue Retention', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
                <button type="button" id="update-cohort" class="button button-primary">
                    <?php esc_html_e('Update Analysis', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Cohort Table -->
    <div class="cohort-table-container">
        <div class="table-header">
            <h3><?php esc_html_e('Cohort Retention Table', 'skylearn-billing-pro'); ?></h3>
            <div class="table-actions">
                <button type="button" class="button export-table" data-table="cohort-analysis" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
                <button type="button" class="button export-table" data-table="cohort-analysis" data-format="xls">
                    <?php esc_html_e('Export XLS', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="cohort-table-scroll">
            <table class="cohort-table wp-list-table widefat">
                <thead>
                    <tr>
                        <th class="cohort-period-header"><?php esc_html_e('Cohort', 'skylearn-billing-pro'); ?></th>
                        <th class="cohort-size-header"><?php esc_html_e('Size', 'skylearn-billing-pro'); ?></th>
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                            <th class="retention-period"><?php echo esc_html(sprintf(__('Period %d', 'skylearn-billing-pro'), $i)); ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($cohort_data['cohorts'])): ?>
                        <?php foreach ($cohort_data['cohorts'] as $period => $cohort): ?>
                            <tr>
                                <td class="cohort-period"><?php echo esc_html(date('M Y', strtotime($period))); ?></td>
                                <td class="cohort-size"><?php echo esc_html(number_format($cohort['size'])); ?></td>
                                <?php for ($i = 1; $i <= 12; $i++): ?>
                                    <td class="retention-cell">
                                        <?php if (isset($cohort['retention'][$i])): ?>
                                            <span class="retention-value" style="background-color: <?php echo esc_attr(skylearn_get_retention_color($cohort['retention'][$i])); ?>">
                                                <?php echo esc_html($cohort['retention'][$i]); ?>%
                                            </span>
                                        <?php else: ?>
                                            <span class="retention-value no-data">-</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endfor; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="14" class="no-data-message">
                                <span class="dashicons dashicons-groups"></span><br>
                                <?php esc_html_e('No cohort data available. Users need to be enrolled for cohort analysis.', 'skylearn-billing-pro'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Cohort Insights -->
    <div class="cohort-insights">
        <div class="insights-container">
            <div class="insights-header">
                <h3><?php esc_html_e('Cohort Insights', 'skylearn-billing-pro'); ?></h3>
            </div>
            <div class="insights-content">
                <div class="insight-cards">
                    <div class="insight-card">
                        <div class="insight-icon">
                            <span class="dashicons dashicons-chart-line"></span>
                        </div>
                        <div class="insight-content">
                            <h4><?php esc_html_e('Average First Month Retention', 'skylearn-billing-pro'); ?></h4>
                            <div class="insight-value">72%</div>
                            <p><?php esc_html_e('Users who remain active after first month', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>

                    <div class="insight-card">
                        <div class="insight-icon">
                            <span class="dashicons dashicons-backup"></span>
                        </div>
                        <div class="insight-content">
                            <h4><?php esc_html_e('3-Month Retention Rate', 'skylearn-billing-pro'); ?></h4>
                            <div class="insight-value">45%</div>
                            <p><?php esc_html_e('Long-term user engagement rate', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>

                    <div class="insight-card">
                        <div class="insight-icon">
                            <span class="dashicons dashicons-star-filled"></span>
                        </div>
                        <div class="insight-content">
                            <h4><?php esc_html_e('Best Performing Cohort', 'skylearn-billing-pro'); ?></h4>
                            <div class="insight-value"><?php echo esc_html(date('M Y')); ?></div>
                            <p><?php esc_html_e('Highest retention across all periods', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>

                    <div class="insight-card">
                        <div class="insight-icon">
                            <span class="dashicons dashicons-groups"></span>
                        </div>
                        <div class="insight-content">
                            <h4><?php esc_html_e('Total Cohorts Analyzed', 'skylearn-billing-pro'); ?></h4>
                            <div class="insight-value"><?php echo esc_html(count($cohort_data['cohorts'])); ?></div>
                            <p><?php esc_html_e('Enrollment periods with data', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cohort Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3><?php esc_html_e('Retention Curve Visualization', 'skylearn-billing-pro'); ?></h3>
            <div class="chart-actions">
                <button type="button" class="button export-chart" data-chart="cohort-curve" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="chart-content">
            <canvas id="cohort-chart" width="400" height="300"></canvas>
        </div>
    </div>

    <!-- Cohort Recommendations -->
    <div class="cohort-recommendations">
        <h3><?php esc_html_e('Recommendations', 'skylearn-billing-pro'); ?></h3>
        <div class="recommendation-cards">
            <div class="recommendation-card info">
                <div class="recommendation-icon">
                    <span class="dashicons dashicons-lightbulb"></span>
                </div>
                <div class="recommendation-content">
                    <h4><?php esc_html_e('Improve Onboarding', 'skylearn-billing-pro'); ?></h4>
                    <p><?php esc_html_e('Focus on first-week experience to improve month 1 retention. Consider welcome sequences and guided tutorials.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
            
            <div class="recommendation-card success">
                <div class="recommendation-icon">
                    <span class="dashicons dashicons-yes-alt"></span>
                </div>
                <div class="recommendation-content">
                    <h4><?php esc_html_e('Engagement Campaigns', 'skylearn-billing-pro'); ?></h4>
                    <p><?php esc_html_e('Create targeted re-engagement campaigns for users who drop off after month 2-3.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
            
            <div class="recommendation-card warning">
                <div class="recommendation-icon">
                    <span class="dashicons dashicons-warning"></span>
                </div>
                <div class="recommendation-content">
                    <h4><?php esc_html_e('Monitor Trends', 'skylearn-billing-pro'); ?></h4>
                    <p><?php esc_html_e('Watch for declining retention in recent cohorts and investigate potential causes.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize cohort chart
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('cohort-chart');
        if (ctx) {
            // Sample retention curve data
            var retentionData = {
                labels: ['Month 1', 'Month 2', 'Month 3', 'Month 4', 'Month 5', 'Month 6'],
                datasets: [
                    {
                        label: 'Jan 2024 Cohort',
                        data: [72, 58, 45, 38, 33, 29],
                        borderColor: '#FF3B00',
                        backgroundColor: 'rgba(255, 59, 0, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Feb 2024 Cohort',
                        data: [75, 62, 48, 41, 36, 32],
                        borderColor: '#183153',
                        backgroundColor: 'rgba(24, 49, 83, 0.1)',
                        tension: 0.4,
                        fill: false
                    },
                    {
                        label: 'Mar 2024 Cohort',
                        data: [78, 65, 52, 44, 39, null],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: false
                    }
                ]
            };

            new Chart(ctx, {
                type: 'line',
                data: retentionData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Retention Curves by Cohort'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    }

    // Update cohort analysis
    $('#update-cohort').on('click', function() {
        var period = $('#cohort-period').val();
        var metric = $('#cohort-metric').val();
        
        // In a real implementation, this would trigger an AJAX request
        console.log('Updating cohort analysis:', period, metric);
    });
});

// Helper function to get retention color (would be in PHP in real implementation)
function getRetentionColor(retention) {
    if (retention >= 70) return 'rgba(40, 167, 69, 0.8)'; // Green
    if (retention >= 50) return 'rgba(255, 193, 7, 0.8)'; // Yellow
    if (retention >= 30) return 'rgba(255, 133, 82, 0.8)'; // Orange
    return 'rgba(220, 53, 69, 0.8)'; // Red
}
</script>

<style>
.cohort-settings {
    background: var(--skylearn-white);
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px var(--skylearn-shadow);
}

.settings-container {
    max-width: 800px;
}

.settings-header h3 {
    margin: 0 0 5px 0;
    color: var(--skylearn-primary);
    font-size: 18px;
    font-weight: 600;
}

.settings-header p {
    margin: 0 0 20px 0;
    color: var(--skylearn-medium-gray);
    font-size: 14px;
}

.settings-content {
    display: flex;
    align-items: end;
    gap: 20px;
    flex-wrap: wrap;
}

.setting-group {
    display: flex;
    flex-direction: column;
}

.setting-group label {
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--skylearn-dark-gray);
    font-size: 14px;
}

.setting-group select {
    padding: 8px 12px;
    border: 1px solid var(--skylearn-border);
    border-radius: 4px;
    font-size: 14px;
    min-width: 150px;
}

.cohort-table-container {
    background: var(--skylearn-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--skylearn-shadow);
    overflow: hidden;
    margin-bottom: 20px;
}

.cohort-table-scroll {
    overflow-x: auto;
    max-height: 600px;
    overflow-y: auto;
}

.cohort-table {
    min-width: 800px;
    margin: 0;
    border-collapse: collapse;
}

.cohort-table th,
.cohort-table td {
    padding: 12px 8px;
    text-align: center;
    border: 1px solid var(--skylearn-border);
    font-size: 13px;
}

.cohort-table th {
    background: var(--skylearn-primary);
    color: var(--skylearn-white);
    font-weight: 600;
    position: sticky;
    top: 0;
    z-index: 10;
}

.cohort-period-header,
.cohort-size-header {
    background: var(--skylearn-accent);
    position: sticky;
    left: 0;
    z-index: 11;
}

.cohort-period,
.cohort-size {
    background: var(--skylearn-light-gray);
    font-weight: 600;
    position: sticky;
    left: 0;
    z-index: 2;
}

.cohort-period {
    left: 0;
    min-width: 100px;
}

.cohort-size {
    left: 100px;
    min-width: 80px;
}

.retention-cell {
    min-width: 80px;
}

.retention-value {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    color: white;
    font-weight: 600;
    min-width: 50px;
}

.retention-value.no-data {
    background: var(--skylearn-border);
    color: var(--skylearn-medium-gray);
}

.cohort-insights {
    margin-bottom: 20px;
}

.insights-container {
    background: var(--skylearn-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--skylearn-shadow);
    overflow: hidden;
}

.insights-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--skylearn-border);
    background: var(--skylearn-light-gray);
}

.insights-header h3 {
    margin: 0;
    color: var(--skylearn-primary);
    font-size: 18px;
    font-weight: 600;
}

.insights-content {
    padding: 20px;
}

.insight-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.insight-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border: 1px solid var(--skylearn-border);
    border-radius: 8px;
    background: var(--skylearn-light-gray);
}

.insight-icon {
    width: 50px;
    height: 50px;
    background: var(--skylearn-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.insight-icon .dashicons {
    color: var(--skylearn-white);
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.insight-content h4 {
    margin: 0 0 8px 0;
    font-size: 14px;
    font-weight: 600;
    color: var(--skylearn-dark-gray);
}

.insight-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--skylearn-accent);
    margin-bottom: 5px;
}

.insight-content p {
    margin: 0;
    font-size: 12px;
    color: var(--skylearn-medium-gray);
    line-height: 1.4;
}

.cohort-recommendations {
    background: var(--skylearn-white);
    padding: 25px;
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--skylearn-shadow);
    margin-top: 20px;
}

.cohort-recommendations h3 {
    margin: 0 0 20px 0;
    color: var(--skylearn-primary);
    font-size: 20px;
    font-weight: 600;
}

@media (max-width: 768px) {
    .settings-content {
        flex-direction: column;
        align-items: stretch;
    }
    
    .setting-group select {
        min-width: auto;
    }
    
    .cohort-table th,
    .cohort-table td {
        padding: 8px 4px;
        font-size: 12px;
    }
    
    .insight-cards {
        grid-template-columns: 1fr;
    }
    
    .insight-card {
        flex-direction: column;
        text-align: center;
    }
}
</style>