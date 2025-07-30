<?php
/**
 * Analytics Dashboard template for Skylearn Billing Pro
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

$analytics_manager = skylearn_billing_pro_analytics_manager();
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
$widgets = get_option('skylearn_billing_pro_dashboard_widgets', array());
?>

<div class="wrap skylearn-billing-admin skylearn-analytics">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-chart-area"></span>
                <?php esc_html_e('Analytics Dashboard', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Real-time insights and key performance metrics for your business', 'skylearn-billing-pro'); ?>
            </p>
        </div>
        <div class="skylearn-billing-header-actions">
            <button type="button" class="button button-secondary" id="configure-dashboard">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php esc_html_e('Configure Dashboard', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button button-primary" id="refresh-analytics">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh Data', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'overview') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-analytics&tab=overview')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-dashboard"></span>
                            <?php esc_html_e('Overview', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'revenue') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-analytics&tab=revenue')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php esc_html_e('Revenue Analytics', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'subscriptions') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-analytics&tab=subscriptions')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Subscriptions', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'customers') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-analytics&tab=customers')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-groups"></span>
                            <?php esc_html_e('Customer Analytics', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'products') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-analytics&tab=products')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-products"></span>
                            <?php esc_html_e('Product Performance', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'realtime') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-analytics&tab=realtime')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-clock"></span>
                            <?php esc_html_e('Real-time', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="skylearn-billing-main">
            <!-- Filters Bar -->
            <div class="skylearn-analytics-filters" <?php echo ($active_tab === 'realtime') ? 'style="display:none;"' : ''; ?>>
                <div class="filter-group">
                    <label for="analytics-date-from"><?php esc_html_e('From:', 'skylearn-billing-pro'); ?></label>
                    <input type="date" id="analytics-date-from" name="date_from" value="<?php echo esc_attr(date('Y-m-01')); ?>">
                </div>
                <div class="filter-group">
                    <label for="analytics-date-to"><?php esc_html_e('To:', 'skylearn-billing-pro'); ?></label>
                    <input type="date" id="analytics-date-to" name="date_to" value="<?php echo esc_attr(date('Y-m-d')); ?>">
                </div>
                <div class="filter-group">
                    <label for="analytics-period"><?php esc_html_e('Period:', 'skylearn-billing-pro'); ?></label>
                    <select id="analytics-period" name="period">
                        <option value="daily"><?php esc_html_e('Daily', 'skylearn-billing-pro'); ?></option>
                        <option value="weekly"><?php esc_html_e('Weekly', 'skylearn-billing-pro'); ?></option>
                        <option value="monthly" selected><?php esc_html_e('Monthly', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
                <div class="filter-actions">
                    <button type="button" id="apply-analytics-filters" class="button button-primary">
                        <?php esc_html_e('Apply Filters', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" id="reset-analytics-filters" class="button">
                        <?php esc_html_e('Reset', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>

            <!-- Tab Content -->
            <div class="skylearn-analytics-content">
                <?php
                switch ($active_tab) {
                    case 'overview':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/analytics-overview.php';
                        break;
                    case 'revenue':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/analytics-revenue.php';
                        break;
                    case 'subscriptions':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/analytics-subscriptions.php';
                        break;
                    case 'customers':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/analytics-customers.php';
                        break;
                    case 'products':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/analytics-products.php';
                        break;
                    case 'realtime':
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/analytics-realtime.php';
                        break;
                    default:
                        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/analytics-overview.php';
                        break;
                }
                ?>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard Configuration Modal -->
<div id="dashboard-config-modal" class="skylearn-modal" style="display: none;">
    <div class="skylearn-modal-content">
        <div class="skylearn-modal-header">
            <h3><?php esc_html_e('Configure Dashboard Widgets', 'skylearn-billing-pro'); ?></h3>
            <span class="skylearn-modal-close">&times;</span>
        </div>
        <div class="skylearn-modal-body">
            <p><?php esc_html_e('Drag and drop widgets to reorder them, or toggle their visibility.', 'skylearn-billing-pro'); ?></p>
            <div id="widget-configuration">
                <div class="widget-config-list" id="sortable-widgets">
                    <?php foreach ($widgets as $widget_id => $widget_config): ?>
                        <div class="widget-config-item" data-widget-id="<?php echo esc_attr($widget_id); ?>">
                            <div class="widget-config-handle">
                                <span class="dashicons dashicons-menu"></span>
                            </div>
                            <div class="widget-config-info">
                                <strong><?php echo esc_html($widget_config['title']); ?></strong>
                                <span class="widget-type"><?php echo esc_html($widget_config['type']); ?></span>
                            </div>
                            <div class="widget-config-toggle">
                                <label class="switch">
                                    <input type="checkbox" <?php checked($widget_config['enabled']); ?> data-widget-id="<?php echo esc_attr($widget_id); ?>">
                                    <span class="slider"></span>
                                </label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="skylearn-modal-footer">
            <button type="button" class="button button-primary" id="save-widget-config">
                <?php esc_html_e('Save Configuration', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button button-secondary" id="cancel-widget-config">
                <?php esc_html_e('Cancel', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="analytics-loading" class="skylearn-loading-overlay" style="display: none;">
    <div class="skylearn-loading-spinner">
        <div class="spinner"></div>
        <p><?php esc_html_e('Loading analytics data...', 'skylearn-billing-pro'); ?></p>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize analytics dashboard
    SkyLearnAnalytics.init();
    
    // Auto-refresh for real-time tab
    <?php if ($active_tab === 'realtime'): ?>
    setInterval(function() {
        SkyLearnAnalytics.refreshRealTimeData();
    }, 30000); // Refresh every 30 seconds
    <?php endif; ?>
});
</script>

<style>
.skylearn-analytics {
    .skylearn-analytics-filters {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
        
        label {
            font-weight: 600;
            font-size: 12px;
            color: #646970;
        }
        
        input, select {
            min-width: 120px;
            padding: 5px 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
    }
    
    .filter-actions {
        margin-left: auto;
        display: flex;
        gap: 10px;
    }
    
    .skylearn-analytics-content {
        min-height: 500px;
    }
    
    // Widget grid layout
    .analytics-widgets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .skylearn-analytics-widget {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 20px;
        position: relative;
        
        .widget-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            
            h3 {
                margin: 0;
                font-size: 16px;
                color: #1d2327;
            }
            
            .widget-actions {
                display: flex;
                gap: 5px;
                
                .widget-action-btn {
                    padding: 2px 6px;
                    font-size: 12px;
                    border: none;
                    background: none;
                    color: #646970;
                    cursor: pointer;
                    border-radius: 2px;
                    
                    &:hover {
                        background: #f0f0f1;
                        color: #0073aa;
                    }
                }
            }
        }
        
        .widget-content {
            .metric-value {
                font-size: 32px;
                font-weight: 700;
                color: #1d2327;
                line-height: 1;
                margin-bottom: 5px;
                
                &.positive {
                    color: #00a32a;
                }
                
                &.negative {
                    color: #d63638;
                }
            }
            
            .metric-label {
                font-size: 14px;
                color: #646970;
                margin-bottom: 10px;
            }
            
            .metric-change {
                font-size: 12px;
                display: flex;
                align-items: center;
                gap: 3px;
                
                .change-icon {
                    font-size: 10px;
                }
                
                &.positive {
                    color: #00a32a;
                }
                
                &.negative {
                    color: #d63638;
                }
            }
        }
        
        .chart-container {
            height: 250px;
            position: relative;
        }
    }
    
    // Modal styles
    .skylearn-modal {
        display: none;
        position: fixed;
        z-index: 100000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        
        .skylearn-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 0;
            border-radius: 4px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow: auto;
        }
        
        .skylearn-modal-header {
            padding: 20px;
            background: #f9f9f9;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            
            h3 {
                margin: 0;
            }
            
            .skylearn-modal-close {
                font-size: 24px;
                cursor: pointer;
                color: #666;
                
                &:hover {
                    color: #000;
                }
            }
        }
        
        .skylearn-modal-body {
            padding: 20px;
        }
        
        .skylearn-modal-footer {
            padding: 20px;
            background: #f9f9f9;
            border-top: 1px solid #ddd;
            text-align: right;
            
            .button {
                margin-left: 10px;
            }
        }
    }
    
    // Widget configuration
    .widget-config-list {
        .widget-config-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin-bottom: 10px;
            background: #fff;
            cursor: move;
            
            .widget-config-handle {
                margin-right: 15px;
                color: #666;
            }
            
            .widget-config-info {
                flex: 1;
                
                strong {
                    display: block;
                    font-size: 14px;
                    margin-bottom: 3px;
                }
                
                .widget-type {
                    font-size: 12px;
                    color: #666;
                    text-transform: capitalize;
                }
            }
            
            .widget-config-toggle {
                .switch {
                    position: relative;
                    display: inline-block;
                    width: 50px;
                    height: 24px;
                    
                    input {
                        opacity: 0;
                        width: 0;
                        height: 0;
                    }
                    
                    .slider {
                        position: absolute;
                        cursor: pointer;
                        top: 0;
                        left: 0;
                        right: 0;
                        bottom: 0;
                        background-color: #ccc;
                        transition: .4s;
                        border-radius: 24px;
                        
                        &:before {
                            position: absolute;
                            content: "";
                            height: 18px;
                            width: 18px;
                            left: 3px;
                            bottom: 3px;
                            background-color: white;
                            transition: .4s;
                            border-radius: 50%;
                        }
                    }
                    
                    input:checked + .slider {
                        background-color: #0073aa;
                    }
                    
                    input:checked + .slider:before {
                        transform: translateX(26px);
                    }
                }
            }
        }
    }
    
    // Loading overlay
    .skylearn-loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        z-index: 10000;
        display: flex;
        align-items: center;
        justify-content: center;
        
        .skylearn-loading-spinner {
            text-align: center;
            
            .spinner {
                border: 4px solid #f3f3f3;
                border-top: 4px solid #0073aa;
                border-radius: 50%;
                width: 50px;
                height: 50px;
                animation: spin 1s linear infinite;
                margin: 0 auto 15px;
            }
            
            p {
                margin: 0;
                color: #646970;
            }
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
}
</style>