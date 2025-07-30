<?php
/**
 * Retention report template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$reporting = skylearn_billing_pro_reporting();
$retention_data = $reporting->get_retention_analytics();
?>

<div class="skylearn-reports-retention">
    <!-- Retention Metrics -->
    <div class="skylearn-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-backup"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value retention-day-1"><?php echo esc_html($retention_data['day_1_retention']); ?>%</h3>
                <p class="metric-label"><?php esc_html_e('Day 1 Retention', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend <?php echo $retention_data['day_1_retention'] >= 80 ? 'success' : 'warning'; ?>">
                    <?php esc_html_e('Next day return rate', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value retention-day-7"><?php echo esc_html($retention_data['day_7_retention']); ?>%</h3>
                <p class="metric-label"><?php esc_html_e('Week 1 Retention', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend <?php echo $retention_data['day_7_retention'] >= 50 ? 'success' : 'warning'; ?>">
                    <?php esc_html_e('7-day engagement', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-calendar"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value retention-day-30"><?php echo esc_html($retention_data['day_30_retention']); ?>%</h3>
                <p class="metric-label"><?php esc_html_e('Month 1 Retention', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend <?php echo $retention_data['day_30_retention'] >= 30 ? 'success' : 'warning'; ?>">
                    <?php esc_html_e('Long-term engagement', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-chart-line"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value retention-average"><?php echo esc_html($retention_data['average_retention']); ?>%</h3>
                <p class="metric-label"><?php esc_html_e('Average Retention', 'skylearn-billing-pro'); ?></p>
                <span class="metric-trend">
                    <?php esc_html_e('Overall retention score', 'skylearn-billing-pro'); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Retention Funnel Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3><?php esc_html_e('Retention Funnel', 'skylearn-billing-pro'); ?></h3>
            <div class="chart-actions">
                <button type="button" class="button export-chart" data-chart="retention-funnel" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
                <button type="button" class="button export-chart" data-chart="retention-funnel" data-format="xls">
                    <?php esc_html_e('Export XLS', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="chart-content">
            <canvas id="retention-funnel-chart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Retention Breakdown -->
    <div class="retention-breakdown">
        <div class="breakdown-container">
            <div class="breakdown-header">
                <h3><?php esc_html_e('Retention Breakdown', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Detailed analysis of user retention patterns', 'skylearn-billing-pro'); ?></p>
            </div>
            <div class="breakdown-content">
                <div class="retention-stages">
                    <div class="stage-item">
                        <div class="stage-visual">
                            <div class="stage-bar">
                                <div class="stage-fill" style="width: 100%"></div>
                            </div>
                            <div class="stage-label">Day 0</div>
                        </div>
                        <div class="stage-info">
                            <div class="stage-value">100%</div>
                            <div class="stage-description"><?php esc_html_e('Initial Enrollment', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>

                    <div class="stage-item">
                        <div class="stage-visual">
                            <div class="stage-bar">
                                <div class="stage-fill" style="width: <?php echo esc_attr($retention_data['day_1_retention']); ?>%"></div>
                            </div>
                            <div class="stage-label">Day 1</div>
                        </div>
                        <div class="stage-info">
                            <div class="stage-value"><?php echo esc_html($retention_data['day_1_retention']); ?>%</div>
                            <div class="stage-description"><?php esc_html_e('Returned Next Day', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>

                    <div class="stage-item">
                        <div class="stage-visual">
                            <div class="stage-bar">
                                <div class="stage-fill" style="width: <?php echo esc_attr($retention_data['day_7_retention']); ?>%"></div>
                            </div>
                            <div class="stage-label">Day 7</div>
                        </div>
                        <div class="stage-info">
                            <div class="stage-value"><?php echo esc_html($retention_data['day_7_retention']); ?>%</div>
                            <div class="stage-description"><?php esc_html_e('Active After Week', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>

                    <div class="stage-item">
                        <div class="stage-visual">
                            <div class="stage-bar">
                                <div class="stage-fill" style="width: <?php echo esc_attr($retention_data['day_30_retention']); ?>%"></div>
                            </div>
                            <div class="stage-label">Day 30</div>
                        </div>
                        <div class="stage-info">
                            <div class="stage-value"><?php echo esc_html($retention_data['day_30_retention']); ?>%</div>
                            <div class="stage-description"><?php esc_html_e('Monthly Active Users', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Retention Factors -->
    <div class="retention-factors">
        <div class="factors-container">
            <div class="factors-header">
                <h3><?php esc_html_e('Retention Factors Analysis', 'skylearn-billing-pro'); ?></h3>
            </div>
            <div class="factors-content">
                <div class="factor-cards">
                    <div class="factor-card">
                        <div class="factor-icon">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                        </div>
                        <div class="factor-content">
                            <h4><?php esc_html_e('Course Completion Impact', 'skylearn-billing-pro'); ?></h4>
                            <div class="factor-stat">
                                <span class="stat-value">+35%</span>
                                <span class="stat-label"><?php esc_html_e('retention increase', 'skylearn-billing-pro'); ?></span>
                            </div>
                            <p><?php esc_html_e('Users who complete their first lesson have significantly higher retention rates.', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>

                    <div class="factor-card">
                        <div class="factor-icon">
                            <span class="dashicons dashicons-email-alt"></span>
                        </div>
                        <div class="factor-content">
                            <h4><?php esc_html_e('Welcome Email Engagement', 'skylearn-billing-pro'); ?></h4>
                            <div class="factor-stat">
                                <span class="stat-value">+28%</span>
                                <span class="stat-label"><?php esc_html_e('retention increase', 'skylearn-billing-pro'); ?></span>
                            </div>
                            <p><?php esc_html_e('Users who open welcome emails show better long-term engagement.', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>

                    <div class="factor-card">
                        <div class="factor-icon">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="factor-content">
                            <h4><?php esc_html_e('Time to First Action', 'skylearn-billing-pro'); ?></h4>
                            <div class="factor-stat">
                                <span class="stat-value">&lt; 24h</span>
                                <span class="stat-label"><?php esc_html_e('optimal window', 'skylearn-billing-pro'); ?></span>
                            </div>
                            <p><?php esc_html_e('Users who take action within 24 hours have the highest retention rates.', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>

                    <div class="factor-card">
                        <div class="factor-icon">
                            <span class="dashicons dashicons-smartphone"></span>
                        </div>
                        <div class="factor-content">
                            <h4><?php esc_html_e('Mobile Usage Impact', 'skylearn-billing-pro'); ?></h4>
                            <div class="factor-stat">
                                <span class="stat-value">67%</span>
                                <span class="stat-label"><?php esc_html_e('mobile users', 'skylearn-billing-pro'); ?></span>
                            </div>
                            <p><?php esc_html_e('Mobile-friendly experience is crucial for modern user retention.', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Retention Improvement Strategies -->
    <div class="retention-strategies">
        <div class="strategies-container">
            <div class="strategies-header">
                <h3><?php esc_html_e('Retention Improvement Strategies', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Actionable recommendations to improve user retention', 'skylearn-billing-pro'); ?></p>
            </div>
            <div class="strategies-content">
                <div class="strategy-list">
                    <div class="strategy-item">
                        <div class="strategy-priority high">
                            <span><?php esc_html_e('High', 'skylearn-billing-pro'); ?></span>
                        </div>
                        <div class="strategy-content">
                            <h4><?php esc_html_e('Improve Onboarding Flow', 'skylearn-billing-pro'); ?></h4>
                            <p><?php esc_html_e('Create a guided onboarding sequence that helps users complete their first lesson within 24 hours of enrollment.', 'skylearn-billing-pro'); ?></p>
                            <div class="strategy-impact">
                                <?php esc_html_e('Expected impact: +15-20% Day 1 retention', 'skylearn-billing-pro'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="strategy-item">
                        <div class="strategy-priority medium">
                            <span><?php esc_html_e('Medium', 'skylearn-billing-pro'); ?></span>
                        </div>
                        <div class="strategy-content">
                            <h4><?php esc_html_e('Implement Push Notifications', 'skylearn-billing-pro'); ?></h4>
                            <p><?php esc_html_e('Send timely reminders and motivation messages to keep users engaged with their learning journey.', 'skylearn-billing-pro'); ?></p>
                            <div class="strategy-impact">
                                <?php esc_html_e('Expected impact: +10-15% Week 1 retention', 'skylearn-billing-pro'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="strategy-item">
                        <div class="strategy-priority high">
                            <span><?php esc_html_e('High', 'skylearn-billing-pro'); ?></span>
                        </div>
                        <div class="strategy-content">
                            <h4><?php esc_html_e('Personalized Content Recommendations', 'skylearn-billing-pro'); ?></h4>
                            <p><?php esc_html_e('Use user behavior data to suggest relevant courses and content that match their interests and progress.', 'skylearn-billing-pro'); ?></p>
                            <div class="strategy-impact">
                                <?php esc_html_e('Expected impact: +20-25% Month 1 retention', 'skylearn-billing-pro'); ?>
                            </div>
                        </div>
                    </div>

                    <div class="strategy-item">
                        <div class="strategy-priority low">
                            <span><?php esc_html_e('Low', 'skylearn-billing-pro'); ?></span>
                        </div>
                        <div class="strategy-content">
                            <h4><?php esc_html_e('Community Features', 'skylearn-billing-pro'); ?></h4>
                            <p><?php esc_html_e('Add discussion forums, study groups, and peer interaction features to create a sense of community.', 'skylearn-billing-pro'); ?></p>
                            <div class="strategy-impact">
                                <?php esc_html_e('Expected impact: +5-10% overall retention', 'skylearn-billing-pro'); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize retention funnel chart
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('retention-funnel-chart');
        if (ctx) {
            var retentionData = {
                labels: ['Day 0', 'Day 1', 'Day 7', 'Day 30'],
                datasets: [{
                    label: 'Retention Rate (%)',
                    data: [100, <?php echo esc_js($retention_data['day_1_retention']); ?>, <?php echo esc_js($retention_data['day_7_retention']); ?>, <?php echo esc_js($retention_data['day_30_retention']); ?>],
                    backgroundColor: [
                        '#28a745',
                        '<?php echo $retention_data["day_1_retention"] >= 80 ? "#28a745" : ($retention_data["day_1_retention"] >= 60 ? "#ffc107" : "#dc3545"); ?>',
                        '<?php echo $retention_data["day_7_retention"] >= 50 ? "#28a745" : ($retention_data["day_7_retention"] >= 30 ? "#ffc107" : "#dc3545"); ?>',
                        '<?php echo $retention_data["day_30_retention"] >= 30 ? "#28a745" : ($retention_data["day_30_retention"] >= 20 ? "#ffc107" : "#dc3545"); ?>'
                    ],
                    borderColor: '#183153',
                    borderWidth: 2
                }]
            };

            new Chart(ctx, {
                type: 'bar',
                data: retentionData,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'User Retention Funnel'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    },
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
                    }
                }
            });
        }
    }
});
</script>

<style>
.retention-breakdown {
    margin: 20px 0;
}

.breakdown-container {
    background: var(--skylearn-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--skylearn-shadow);
    overflow: hidden;
}

.breakdown-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--skylearn-border);
    background: var(--skylearn-light-gray);
}

.breakdown-header h3 {
    margin: 0 0 5px 0;
    color: var(--skylearn-primary);
    font-size: 18px;
    font-weight: 600;
}

.breakdown-header p {
    margin: 0;
    color: var(--skylearn-medium-gray);
    font-size: 14px;
}

.breakdown-content {
    padding: 30px;
}

.retention-stages {
    display: flex;
    flex-direction: column;
    gap: 25px;
}

.stage-item {
    display: flex;
    align-items: center;
    gap: 30px;
}

.stage-visual {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 15px;
}

.stage-bar {
    flex: 1;
    height: 20px;
    background: var(--skylearn-border);
    border-radius: 10px;
    overflow: hidden;
    position: relative;
}

.stage-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--skylearn-success), var(--skylearn-primary));
    border-radius: 10px;
    transition: width 0.3s ease;
}

.stage-label {
    font-weight: 600;
    color: var(--skylearn-primary);
    min-width: 60px;
    text-align: center;
}

.stage-info {
    min-width: 200px;
    text-align: right;
}

.stage-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--skylearn-primary);
}

.stage-description {
    font-size: 14px;
    color: var(--skylearn-medium-gray);
    margin-top: 5px;
}

.retention-factors,
.retention-strategies {
    margin: 20px 0;
}

.factors-container,
.strategies-container {
    background: var(--skylearn-white);
    border-radius: 12px;
    box-shadow: 0 2px 8px var(--skylearn-shadow);
    overflow: hidden;
}

.factors-header,
.strategies-header {
    padding: 20px 25px;
    border-bottom: 1px solid var(--skylearn-border);
    background: var(--skylearn-light-gray);
}

.factors-header h3,
.strategies-header h3 {
    margin: 0 0 5px 0;
    color: var(--skylearn-primary);
    font-size: 18px;
    font-weight: 600;
}

.strategies-header p {
    margin: 0;
    color: var(--skylearn-medium-gray);
    font-size: 14px;
}

.factors-content,
.strategies-content {
    padding: 20px;
}

.factor-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.factor-card {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    padding: 20px;
    border: 1px solid var(--skylearn-border);
    border-radius: 8px;
    background: var(--skylearn-light-gray);
}

.factor-icon {
    width: 50px;
    height: 50px;
    background: var(--skylearn-primary);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.factor-icon .dashicons {
    color: var(--skylearn-white);
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.factor-content h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--skylearn-dark-gray);
}

.factor-stat {
    display: flex;
    align-items: baseline;
    gap: 8px;
    margin-bottom: 10px;
}

.stat-value {
    font-size: 20px;
    font-weight: 700;
    color: var(--skylearn-accent);
}

.stat-label {
    font-size: 12px;
    color: var(--skylearn-medium-gray);
}

.factor-content p {
    margin: 0;
    font-size: 14px;
    color: var(--skylearn-medium-gray);
    line-height: 1.5;
}

.strategy-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.strategy-item {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    padding: 20px;
    border: 1px solid var(--skylearn-border);
    border-radius: 8px;
    background: var(--skylearn-light-gray);
}

.strategy-priority {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}

.strategy-priority.high {
    background: rgba(220, 53, 69, 0.1);
    color: #dc3545;
}

.strategy-priority.medium {
    background: rgba(255, 193, 7, 0.1);
    color: #ffc107;
}

.strategy-priority.low {
    background: rgba(40, 167, 69, 0.1);
    color: #28a745;
}

.strategy-content h4 {
    margin: 0 0 10px 0;
    font-size: 16px;
    font-weight: 600;
    color: var(--skylearn-dark-gray);
}

.strategy-content p {
    margin: 0 0 10px 0;
    font-size: 14px;
    color: var(--skylearn-medium-gray);
    line-height: 1.5;
}

.strategy-impact {
    font-size: 12px;
    color: var(--skylearn-accent);
    font-weight: 600;
    font-style: italic;
}

@media (max-width: 768px) {
    .stage-item {
        flex-direction: column;
        gap: 15px;
    }
    
    .stage-visual {
        width: 100%;
    }
    
    .stage-info {
        text-align: center;
        min-width: auto;
    }
    
    .factor-cards {
        grid-template-columns: 1fr;
    }
    
    .factor-card,
    .strategy-item {
        flex-direction: column;
        text-align: center;
    }
    
    .factor-stat {
        justify-content: center;
    }
}
</style>