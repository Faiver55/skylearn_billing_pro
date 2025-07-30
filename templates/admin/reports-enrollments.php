<?php
/**
 * Enrollments report template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$reporting = skylearn_billing_pro_reporting();
$enrollment_data = $reporting->get_enrollment_analytics();
?>

<div class="skylearn-reports-enrollments">
    <!-- Enrollment Metrics -->
    <div class="skylearn-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-welcome-learn-more"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value enrollments-total"><?php echo esc_html(number_format($enrollment_data['total_enrollments'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Total Enrollments', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value enrollments-successful"><?php echo esc_html(number_format($enrollment_data['successful_enrollments'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Successful Enrollments', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-dismiss"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value enrollments-failed"><?php echo esc_html(number_format($enrollment_data['failed_enrollments'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Failed Enrollments', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon">
                <span class="dashicons dashicons-star-filled"></span>
            </div>
            <div class="metric-content">
                <h3 class="metric-value"><?php echo esc_html(count($enrollment_data['most_popular_courses'])); ?></h3>
                <p class="metric-label"><?php esc_html_e('Active Courses', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>
    </div>

    <!-- Enrollments Chart -->
    <div class="chart-container">
        <div class="chart-header">
            <h3><?php esc_html_e('Enrollments Over Time', 'skylearn-billing-pro'); ?></h3>
            <div class="chart-actions">
                <button type="button" class="button export-chart" data-chart="enrollments" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
                <button type="button" class="button export-chart" data-chart="enrollments" data-format="xls">
                    <?php esc_html_e('Export XLS', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="chart-content">
            <canvas id="enrollments-chart" width="400" height="200"></canvas>
        </div>
    </div>

    <!-- Most Popular Courses Table -->
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
                        <th><?php esc_html_e('Success Rate', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Avg. Completion', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($enrollment_data['most_popular_courses'])): ?>
                        <?php foreach ($enrollment_data['most_popular_courses'] as $course => $enrollments): ?>
                            <tr>
                                <td><?php echo esc_html($course); ?></td>
                                <td><?php echo esc_html(number_format($enrollments)); ?></td>
                                <td>92%</td>
                                <td>78%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="no-data-message">
                                <span class="dashicons dashicons-welcome-learn-more"></span><br>
                                <?php esc_html_e('No enrollment data available for the selected period', 'skylearn-billing-pro'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Enrollments by Trigger Table -->
    <div class="table-container">
        <div class="table-header">
            <h3><?php esc_html_e('Enrollments by Trigger', 'skylearn-billing-pro'); ?></h3>
            <div class="table-actions">
                <button type="button" class="button export-table" data-table="enrollments-by-trigger" data-format="csv">
                    <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
        <div class="table-content">
            <table class="wp-list-table widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Trigger', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Enrollments', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Percentage', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($enrollment_data['enrollments_by_trigger'])): ?>
                        <?php 
                        $total_trigger = array_sum($enrollment_data['enrollments_by_trigger']);
                        foreach ($enrollment_data['enrollments_by_trigger'] as $trigger => $count): 
                            $percentage = $total_trigger > 0 ? round(($count / $total_trigger) * 100, 1) : 0;
                        ?>
                            <tr>
                                <td><?php echo esc_html(ucfirst(str_replace('_', ' ', $trigger))); ?></td>
                                <td><?php echo esc_html(number_format($count)); ?></td>
                                <td><?php echo esc_html($percentage); ?>%</td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="no-data-message">
                                <span class="dashicons dashicons-admin-tools"></span><br>
                                <?php esc_html_e('No trigger data available', 'skylearn-billing-pro'); ?>
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
    // Initialize enrollments chart
    if (typeof Chart !== 'undefined') {
        var ctx = document.getElementById('enrollments-chart');
        if (ctx) {
            var enrollmentData = <?php echo json_encode($enrollment_data['enrollments_by_day']); ?>;
            var labels = Object.keys(enrollmentData);
            var data = Object.values(enrollmentData);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Enrollments',
                        data: data,
                        backgroundColor: '#183153',
                        borderColor: '#183153',
                        borderWidth: 1
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
                            text: 'Daily Enrollments'
                        }
                    }
                }
            });
        }
    }
});
</script>