<?php
/**
 * Reporting template for Skylearn Billing Pro
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

$reporting_engine = skylearn_billing_pro_reporting_engine();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'builder';
$report_templates = get_option('skylearn_billing_report_templates', array());
$scheduled_reports = get_option('skylearn_billing_scheduled_reports', array());
?>

<div class="wrap skylearn-billing-admin skylearn-reporting">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-media-spreadsheet"></span>
                <?php esc_html_e('Report Builder & Export', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Create, customize, and schedule detailed reports for your business data', 'skylearn-billing-pro'); ?>
            </p>
        </div>
        <div class="skylearn-billing-header-actions">
            <button type="button" class="button button-secondary" id="save-report-template">
                <span class="dashicons dashicons-saved"></span>
                <?php esc_html_e('Save as Template', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button button-primary" id="generate-report">
                <span class="dashicons dashicons-analytics"></span>
                <?php esc_html_e('Generate Report', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'builder') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reporting&tab=builder')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e('Report Builder', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'templates') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reporting&tab=templates')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php esc_html_e('Templates', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'scheduled') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reporting&tab=scheduled')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Scheduled Reports', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'history') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reporting&tab=history')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-backup"></span>
                            <?php esc_html_e('Report History', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="skylearn-billing-main">
            <!-- Tab Content -->
            <div class="skylearn-reporting-content">
                <?php
                switch ($active_tab) {
                    case 'builder':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reporting-builder.php';
                        break;
                    case 'templates':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reporting-templates.php';
                        break;
                    case 'scheduled':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reporting-scheduled.php';
                        break;
                    case 'history':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reporting-history.php';
                        break;
                    default:
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/reporting-builder.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Report Builder Tab -->
<?php if ($active_tab === 'builder'): ?>
<div class="report-builder-container">
    <div class="report-builder-form">
        <div class="report-config-section">
            <h3><?php esc_html_e('Report Configuration', 'skylearn-billing-pro'); ?></h3>
            
            <div class="config-row">
                <div class="config-group">
                    <label for="report-name"><?php esc_html_e('Report Name:', 'skylearn-billing-pro'); ?></label>
                    <input type="text" id="report-name" name="report_name" placeholder="Enter report name">
                </div>
                
                <div class="config-group">
                    <label for="report-type"><?php esc_html_e('Report Type:', 'skylearn-billing-pro'); ?></label>
                    <select id="report-type" name="report_type">
                        <option value=""><?php esc_html_e('Select Report Type', 'skylearn-billing-pro'); ?></option>
                        <option value="sales"><?php esc_html_e('Sales Report', 'skylearn-billing-pro'); ?></option>
                        <option value="customers"><?php esc_html_e('Customer Report', 'skylearn-billing-pro'); ?></option>
                        <option value="products"><?php esc_html_e('Product Report', 'skylearn-billing-pro'); ?></option>
                        <option value="revenue"><?php esc_html_e('Revenue Report', 'skylearn-billing-pro'); ?></option>
                        <option value="subscriptions"><?php esc_html_e('Subscription Report', 'skylearn-billing-pro'); ?></option>
                        <?php
                        // Add custom report types via hook
                        $custom_types = apply_filters('skylearn_billing_report_types', array());
                        foreach ($custom_types as $type_id => $type_config) {
                            if (!in_array($type_id, array('sales', 'customers', 'products', 'revenue', 'subscriptions'))) {
                                echo '<option value="' . esc_attr($type_id) . '">' . esc_html($type_config['name']) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <div class="config-row">
                <div class="config-group">
                    <label for="date-from"><?php esc_html_e('Date From:', 'skylearn-billing-pro'); ?></label>
                    <input type="date" id="date-from" name="date_from" value="<?php echo esc_attr(date('Y-m-01')); ?>">
                </div>
                
                <div class="config-group">
                    <label for="date-to"><?php esc_html_e('Date To:', 'skylearn-billing-pro'); ?></label>
                    <input type="date" id="date-to" name="date_to" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
            </div>
        </div>

        <div class="report-filters-section">
            <h3><?php esc_html_e('Filters & Options', 'skylearn-billing-pro'); ?></h3>
            
            <div id="dynamic-filters">
                <!-- Filters will be populated based on report type -->
            </div>
            
            <div class="config-row">
                <div class="config-group">
                    <label for="sort-by"><?php esc_html_e('Sort By:', 'skylearn-billing-pro'); ?></label>
                    <select id="sort-by" name="sort_by">
                        <option value="date"><?php esc_html_e('Date', 'skylearn-billing-pro'); ?></option>
                        <option value="amount"><?php esc_html_e('Amount', 'skylearn-billing-pro'); ?></option>
                        <option value="customer_name"><?php esc_html_e('Customer Name', 'skylearn-billing-pro'); ?></option>
                        <option value="product_name"><?php esc_html_e('Product Name', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
                
                <div class="config-group">
                    <label for="sort-order"><?php esc_html_e('Sort Order:', 'skylearn-billing-pro'); ?></label>
                    <select id="sort-order" name="sort_order">
                        <option value="DESC"><?php esc_html_e('Descending', 'skylearn-billing-pro'); ?></option>
                        <option value="ASC"><?php esc_html_e('Ascending', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="config-row">
                <div class="config-group">
                    <label for="group-by"><?php esc_html_e('Group By:', 'skylearn-billing-pro'); ?></label>
                    <select id="group-by" name="group_by">
                        <option value=""><?php esc_html_e('No Grouping', 'skylearn-billing-pro'); ?></option>
                        <option value="daily"><?php esc_html_e('Daily', 'skylearn-billing-pro'); ?></option>
                        <option value="weekly"><?php esc_html_e('Weekly', 'skylearn-billing-pro'); ?></option>
                        <option value="monthly"><?php esc_html_e('Monthly', 'skylearn-billing-pro'); ?></option>
                        <option value="product"><?php esc_html_e('By Product', 'skylearn-billing-pro'); ?></option>
                        <option value="customer"><?php esc_html_e('By Customer', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
                
                <div class="config-group">
                    <label for="format"><?php esc_html_e('Format:', 'skylearn-billing-pro'); ?></label>
                    <select id="format" name="format">
                        <option value="table"><?php esc_html_e('Table View', 'skylearn-billing-pro'); ?></option>
                        <option value="chart"><?php esc_html_e('Chart View', 'skylearn-billing-pro'); ?></option>
                        <option value="summary"><?php esc_html_e('Summary View', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="report-columns-section">
            <h3><?php esc_html_e('Select Columns', 'skylearn-billing-pro'); ?></h3>
            
            <div id="column-selector">
                <!-- Columns will be populated based on report type -->
            </div>
        </div>

        <div class="report-actions-section">
            <h3><?php esc_html_e('Export & Schedule Options', 'skylearn-billing-pro'); ?></h3>
            
            <div class="action-buttons">
                <div class="export-options">
                    <h4><?php esc_html_e('Export Options:', 'skylearn-billing-pro'); ?></h4>
                    <button type="button" class="button button-secondary export-btn" data-format="csv">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <?php esc_html_e('Export CSV', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" class="button button-secondary export-btn" data-format="pdf">
                        <span class="dashicons dashicons-pdf"></span>
                        <?php esc_html_e('Export PDF', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" class="button button-secondary export-btn" data-format="xlsx">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <?php esc_html_e('Export Excel', 'skylearn-billing-pro'); ?>
                    </button>
                    <?php
                    // Add custom export formats via hook
                    $custom_formats = apply_filters('skylearn_billing_export_formats', array());
                    foreach ($custom_formats as $format_id => $format_config) {
                        if (!in_array($format_id, array('csv', 'pdf', 'xlsx'))) {
                            echo '<button type="button" class="button button-secondary export-btn" data-format="' . esc_attr($format_id) . '">';
                            echo '<span class="dashicons dashicons-download"></span>';
                            echo esc_html($format_config['name']);
                            echo '</button>';
                        }
                    }
                    ?>
                </div>
                
                <div class="schedule-options">
                    <h4><?php esc_html_e('Schedule Options:', 'skylearn-billing-pro'); ?></h4>
                    <button type="button" class="button button-primary" id="schedule-report">
                        <span class="dashicons dashicons-clock"></span>
                        <?php esc_html_e('Schedule Report', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="report-preview-section">
        <h3><?php esc_html_e('Report Preview', 'skylearn-billing-pro'); ?></h3>
        
        <div id="report-preview-container">
            <div class="preview-placeholder">
                <div class="preview-icon">
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>
                <p><?php esc_html_e('Configure your report settings and click "Generate Report" to see a preview.', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Schedule Report Modal -->
<div id="schedule-report-modal" class="skylearn-modal" style="display: none;">
    <div class="skylearn-modal-content">
        <div class="skylearn-modal-header">
            <h3><?php esc_html_e('Schedule Report', 'skylearn-billing-pro'); ?></h3>
            <span class="skylearn-modal-close">&times;</span>
        </div>
        <div class="skylearn-modal-body">
            <form id="schedule-report-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="schedule-frequency"><?php esc_html_e('Frequency:', 'skylearn-billing-pro'); ?></label>
                        <select id="schedule-frequency" name="frequency">
                            <option value="daily"><?php esc_html_e('Daily', 'skylearn-billing-pro'); ?></option>
                            <option value="weekly"><?php esc_html_e('Weekly', 'skylearn-billing-pro'); ?></option>
                            <option value="monthly"><?php esc_html_e('Monthly', 'skylearn-billing-pro'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-time"><?php esc_html_e('Time:', 'skylearn-billing-pro'); ?></label>
                        <input type="time" id="schedule-time" name="time" value="09:00">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="schedule-format"><?php esc_html_e('Format:', 'skylearn-billing-pro'); ?></label>
                        <select id="schedule-format" name="format">
                            <option value="csv"><?php esc_html_e('CSV', 'skylearn-billing-pro'); ?></option>
                            <option value="pdf"><?php esc_html_e('PDF', 'skylearn-billing-pro'); ?></option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="schedule-enabled"><?php esc_html_e('Status:', 'skylearn-billing-pro'); ?></label>
                        <select id="schedule-enabled" name="enabled">
                            <option value="true"><?php esc_html_e('Enabled', 'skylearn-billing-pro'); ?></option>
                            <option value="false"><?php esc_html_e('Disabled', 'skylearn-billing-pro'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="schedule-recipients"><?php esc_html_e('Email Recipients:', 'skylearn-billing-pro'); ?></label>
                        <textarea id="schedule-recipients" name="recipients" placeholder="Enter email addresses separated by commas" rows="3"></textarea>
                        <small><?php esc_html_e('Enter multiple email addresses separated by commas', 'skylearn-billing-pro'); ?></small>
                    </div>
                </div>
            </form>
        </div>
        <div class="skylearn-modal-footer">
            <button type="button" class="button button-primary" id="save-scheduled-report">
                <?php esc_html_e('Schedule Report', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button button-secondary" id="cancel-schedule">
                <?php esc_html_e('Cancel', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Save Template Modal -->
<div id="save-template-modal" class="skylearn-modal" style="display: none;">
    <div class="skylearn-modal-content">
        <div class="skylearn-modal-header">
            <h3><?php esc_html_e('Save Report Template', 'skylearn-billing-pro'); ?></h3>
            <span class="skylearn-modal-close">&times;</span>
        </div>
        <div class="skylearn-modal-body">
            <form id="save-template-form">
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="template-name"><?php esc_html_e('Template Name:', 'skylearn-billing-pro'); ?></label>
                        <input type="text" id="template-name" name="template_name" placeholder="Enter template name" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group full-width">
                        <label for="template-description"><?php esc_html_e('Description:', 'skylearn-billing-pro'); ?></label>
                        <textarea id="template-description" name="template_description" placeholder="Enter template description" rows="3"></textarea>
                    </div>
                </div>
            </form>
        </div>
        <div class="skylearn-modal-footer">
            <button type="button" class="button button-primary" id="save-template">
                <?php esc_html_e('Save Template', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button button-secondary" id="cancel-template">
                <?php esc_html_e('Cancel', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize reporting system
    SkyLearnReporting.init();
});
</script>

<style>
.skylearn-reporting {
    .report-builder-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 30px;
        margin-top: 20px;
    }
    
    .report-builder-form {
        .report-config-section,
        .report-filters-section,
        .report-columns-section,
        .report-actions-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
            
            h3 {
                margin-top: 0;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
                color: #1d2327;
            }
            
            h4 {
                margin: 15px 0 10px 0;
                color: #646970;
                font-size: 14px;
            }
        }
        
        .config-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            
            .config-group {
                flex: 1;
                
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                    color: #1d2327;
                }
                
                input, select, textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    font-size: 14px;
                }
                
                small {
                    display: block;
                    margin-top: 5px;
                    color: #646970;
                    font-size: 12px;
                }
                
                &.full-width {
                    flex: 100%;
                }
            }
        }
        
        .export-options,
        .schedule-options {
            margin-bottom: 20px;
            
            .export-btn {
                margin-right: 10px;
                margin-bottom: 10px;
                
                .dashicons {
                    margin-right: 5px;
                }
            }
        }
        
        #column-selector {
            .column-item {
                display: flex;
                align-items: center;
                padding: 8px 0;
                border-bottom: 1px solid #f0f0f1;
                
                input[type="checkbox"] {
                    margin-right: 10px;
                }
                
                label {
                    flex: 1;
                    margin: 0;
                    cursor: pointer;
                    
                    .column-description {
                        font-size: 12px;
                        color: #646970;
                        display: block;
                        margin-top: 2px;
                    }
                }
            }
        }
    }
    
    .report-preview-section {
        background: #fff;
        border: 1px solid #ccd0d4;  
        border-radius: 4px;
        padding: 20px;
        
        h3 {
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            color: #1d2327;
        }
        
        #report-preview-container {
            min-height: 400px;
            
            .preview-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 400px;
                color: #646970;
                text-align: center;
                
                .preview-icon {
                    font-size: 48px;
                    margin-bottom: 15px;
                    opacity: 0.5;
                }
                
                p {
                    max-width: 300px;
                    line-height: 1.5;
                }
            }
            
            .report-table {
                width: 100%;
                border-collapse: collapse;
                
                th, td {
                    padding: 12px;
                    text-align: left;
                    border-bottom: 1px solid #eee;
                }
                
                th {
                    background: #f9f9f9;
                    font-weight: 600;
                    color: #1d2327;
                }
                
                tbody tr:hover {
                    background: #f9f9f9;
                }
            }
            
            .report-summary {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 20px;
                
                .summary-item {
                    background: #f9f9f9;
                    padding: 15px;
                    border-radius: 4px;
                    text-align: center;
                    
                    .summary-value {
                        font-size: 24px;
                        font-weight: 700;
                        color: #0073aa;
                        margin-bottom: 5px;
                    }
                    
                    .summary-label {
                        font-size: 12px;
                        color: #646970;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    }
                }
            }
        }
    }
    
    // Modal form styles
    .skylearn-modal {
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            
            .form-group {
                flex: 1;
                
                label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 600;
                    color: #1d2327;
                }
                
                input, select, textarea {
                    width: 100%;
                    padding: 8px 12px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    font-size: 14px;
                }
                
                small {
                    display: block;
                    margin-top: 5px;
                    color: #646970;
                    font-size: 12px;
                }
                
                &.full-width {
                    flex: 100%;
                }
            }
        }
    }
    
    // Template grid for templates tab
    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
        
        .template-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            cursor: pointer;
            transition: box-shadow 0.2s;
            
            &:hover {
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            
            .template-header {
                display: flex;
                align-items: center;
                margin-bottom: 10px;
                
                .template-icon {
                    font-size: 24px;
                    margin-right: 10px;
                    color: #0073aa;
                }
                
                .template-title {
                    font-size: 16px;
                    font-weight: 600;
                    color: #1d2327;
                    margin: 0;
                }
            }
            
            .template-description {
                color: #646970;
                font-size: 14px;
                line-height: 1.4;
                margin-bottom: 15px;
            }
            
            .template-actions {
                display: flex;
                gap: 10px;
                
                .button {
                    flex: 1;
                    text-align: center;
                    font-size: 12px;
                    padding: 6px 12px;
                }
            }
        }
    }
    
    // Scheduled reports table
    .scheduled-reports-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #1d2327;
        }
        
        tbody tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            
            &.enabled {
                background: #00a32a;
                color: white;
            }
            
            &.disabled {
                background: #ddd;  
                color: #666;
            }
        }
        
        .actions {
            display: flex;
            gap: 5px;
            
            .action-btn {
                padding: 4px 8px;
                font-size: 12px;
                border: none;
                border-radius: 3px;
                cursor: pointer;
                
                &.edit {
                    background: #0073aa;
                    color: white;
                }
                
                &.delete {
                    background: #d63638;
                    color: white;
                }
                
                &.toggle {
                    background: #f0f0f1;
                    color: #646970;
                }
            }
        }
    }
}
</style>