<?php
/**
 * Admin reports template for Skylearn Billing Pro
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

$reporting = skylearn_billing_pro_reporting();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
?>

<div class="wrap skylearn-billing-admin skylearn-reports">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-chart-bar"></span>
                <?php esc_html_e('Reports & Analytics', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Comprehensive insights into your sales, enrollments, and performance', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'dashboard') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports&tab=dashboard')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-dashboard"></span>
                            <?php esc_html_e('Dashboard', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'sales') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports&tab=sales')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php esc_html_e('Sales', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'enrollments') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports&tab=enrollments')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <?php esc_html_e('Enrollments', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'revenue') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports&tab=revenue')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php esc_html_e('Revenue', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'emails') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports&tab=emails')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-email-alt"></span>
                            <?php esc_html_e('Email Stats', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'cohort') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports&tab=cohort')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-groups"></span>
                            <?php esc_html_e('Cohort Analysis', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'retention') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports&tab=retention')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-backup"></span>
                            <?php esc_html_e('Retention', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="skylearn-billing-main">
            <!-- Filters Bar -->
            <div class="skylearn-reports-filters">
                <div class="filter-group">
                    <label for="date-from"><?php esc_html_e('From:', 'skylearn-billing-pro'); ?></label>
                    <input type="date" id="date-from" name="date_from" value="<?php echo esc_attr(date('Y-m-01')); ?>">
                </div>
                <div class="filter-group">
                    <label for="date-to"><?php esc_html_e('To:', 'skylearn-billing-pro'); ?></label>
                    <input type="date" id="date-to" name="date_to" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
                <div class="filter-group">
                    <label for="product-filter"><?php esc_html_e('Product:', 'skylearn-billing-pro'); ?></label>
                    <select id="product-filter" name="product_id">
                        <option value=""><?php esc_html_e('All Products', 'skylearn-billing-pro'); ?></option>
                        <!-- Products will be populated via JavaScript -->
                    </select>
                </div>
                <div class="filter-group">
                    <label for="course-filter"><?php esc_html_e('Course:', 'skylearn-billing-pro'); ?></label>
                    <select id="course-filter" name="course_id">
                        <option value=""><?php esc_html_e('All Courses', 'skylearn-billing-pro'); ?></option>
                        <!-- Courses will be populated via JavaScript -->
                    </select>
                </div>
                <div class="filter-group">
                    <label for="status-filter"><?php esc_html_e('Status:', 'skylearn-billing-pro'); ?></label>
                    <select id="status-filter" name="status">
                        <option value=""><?php esc_html_e('All Statuses', 'skylearn-billing-pro'); ?></option>
                        <option value="success"><?php esc_html_e('Success', 'skylearn-billing-pro'); ?></option>
                        <option value="failed"><?php esc_html_e('Failed', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="button" id="apply-filters" class="button button-primary">
                        <?php esc_html_e('Apply Filters', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" id="reset-filters" class="button">
                        <?php esc_html_e('Reset', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>

            <!-- Content Based on Active Tab -->
            <?php
            switch ($active_tab) {
                case 'dashboard':
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-dashboard.php';
                    break;
                case 'sales':
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-sales.php';
                    break;
                case 'enrollments':
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-enrollments.php';
                    break;
                case 'revenue':
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-revenue.php';
                    break;
                case 'emails':
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-emails.php';
                    break;
                case 'cohort':
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-cohort.php';
                    break;
                case 'retention':
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-retention.php';
                    break;
                default:
                    include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reports-dashboard.php';
                    break;
            }
            ?>
        </div>
    </div>
</div>

<!-- Hidden export form -->
<form id="export-form" method="post" style="display: none;">
    <input type="hidden" name="action" value="skylearn_export_report">
    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('skylearn_export_nonce'); ?>">
    <input type="hidden" name="report_type" id="export-report-type">
    <input type="hidden" name="format" id="export-format">
    <input type="hidden" name="filters" id="export-filters">
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize reporting dashboard
    SkyLearnReports.init();
});
</script>