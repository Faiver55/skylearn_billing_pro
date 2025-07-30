<?php
/**
 * Analytics Overview template for Skylearn Billing Pro
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

$widgets = get_option('skylearn_billing_pro_dashboard_widgets', array());
?>

<div class="analytics-overview">
    <div class="analytics-widgets-grid">
        
        <?php if (isset($widgets['overview_metrics']) && $widgets['overview_metrics']['enabled']): ?>
        <div class="skylearn-analytics-widget overview-metrics-widget" data-widget-id="overview_metrics">
            <div class="widget-header">
                <h3><?php esc_html_e('Key Metrics Overview', 'skylearn-billing-pro'); ?></h3>
                <div class="widget-actions">
                    <button class="widget-action-btn" data-action="refresh" title="<?php esc_attr_e('Refresh', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="loading-placeholder">
                    <div class="spinner"></div>
                    <p><?php esc_html_e('Loading metrics...', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($widgets['revenue_chart']) && $widgets['revenue_chart']['enabled']): ?>
        <div class="skylearn-analytics-widget revenue-chart-widget" data-widget-id="revenue_chart">
            <div class="widget-header">
                <h3><?php esc_html_e('Revenue Trends', 'skylearn-billing-pro'); ?></h3>
                <div class="widget-actions">
                    <button class="widget-action-btn" data-action="refresh" title="<?php esc_attr_e('Refresh', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                    <button class="widget-action-btn" data-action="fullscreen" title="<?php esc_attr_e('Fullscreen', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-fullscreen-alt"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="revenue-chart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($widgets['product_sales']) && $widgets['product_sales']['enabled']): ?>
        <div class="skylearn-analytics-widget product-sales-widget" data-widget-id="product_sales">
            <div class="widget-header">
                <h3><?php esc_html_e('Product Sales Distribution', 'skylearn-billing-pro'); ?></h3>
                <div class="widget-actions">
                    <button class="widget-action-btn" data-action="refresh" title="<?php esc_attr_e('Refresh', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="product-sales-chart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($widgets['subscriptions_chart']) && $widgets['subscriptions_chart']['enabled']): ?>
        <div class="skylearn-analytics-widget subscriptions-widget" data-widget-id="subscriptions_chart">
            <div class="widget-header">
                <h3><?php esc_html_e('Subscriptions Activity', 'skylearn-billing-pro'); ?></h3>
                <div class="widget-actions">
                    <button class="widget-action-btn" data-action="refresh" title="<?php esc_attr_e('Refresh', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="chart-container">
                    <canvas id="subscriptions-chart"></canvas>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($widgets['top_products']) && $widgets['top_products']['enabled']): ?>
        <div class="skylearn-analytics-widget top-products-widget" data-widget-id="top_products">
            <div class="widget-header">
                <h3><?php esc_html_e('Top Performing Products', 'skylearn-billing-pro'); ?></h3>
                <div class="widget-actions">
                    <button class="widget-action-btn" data-action="refresh" title="<?php esc_attr_e('Refresh', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                    <button class="widget-action-btn" data-action="export" title="<?php esc_attr_e('Export', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-download"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="loading-placeholder">
                    <div class="spinner"></div>
                    <p><?php esc_html_e('Loading products...', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($widgets['recent_activity']) && $widgets['recent_activity']['enabled']): ?>
        <div class="skylearn-analytics-widget recent-activity-widget" data-widget-id="recent_activity">
            <div class="widget-header">
                <h3><?php esc_html_e('Recent Activity', 'skylearn-billing-pro'); ?></h3>
                <div class="widget-actions">
                    <button class="widget-action-btn" data-action="refresh" title="<?php esc_attr_e('Refresh', 'skylearn-billing-pro'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </button>
                </div>
            </div>
            <div class="widget-content">
                <div class="loading-placeholder">
                    <div class="spinner"></div>
                    <p><?php esc_html_e('Loading activity...', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
    
    <!-- Additional Insights Section -->
    <div class="analytics-insights">
        <div class="insights-header">
            <h2><?php esc_html_e('Business Insights', 'skylearn-billing-pro'); ?></h2>
            <p><?php esc_html_e('Key insights and recommendations based on your data', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="insights-grid">
            <div class="insight-card churn-analysis-widget" data-widget-id="churn_analysis">
                <div class="insight-header">
                    <div class="insight-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <h4><?php esc_html_e('Churn Analysis', 'skylearn-billing-pro'); ?></h4>
                </div>
                <div class="widget-content">
                    <div class="loading-placeholder">
                        <div class="spinner"></div>
                        <p><?php esc_html_e('Analyzing churn...', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="insight-card ltv-metrics-widget" data-widget-id="ltv_metrics">
                <div class="insight-header">
                    <div class="insight-icon">
                        <span class="dashicons dashicons-money-alt"></span>
                    </div>
                    <h4><?php esc_html_e('Customer Lifetime Value', 'skylearn-billing-pro'); ?></h4>
                </div>
                <div class="widget-content">
                    <div class="loading-placeholder">
                        <div class="spinner"></div>
                        <p><?php esc_html_e('Calculating LTV...', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="insight-card conversion-funnel-widget" data-widget-id="conversion_funnel">
                <div class="insight-header">
                    <div class="insight-icon">
                        <span class="dashicons dashicons-filter"></span>
                    </div>
                    <h4><?php esc_html_e('Conversion Funnel', 'skylearn-billing-pro'); ?></h4>
                </div>
                <div class="widget-content">
                    <div class="loading-placeholder">
                        <div class="spinner"></div>
                        <p><?php esc_html_e('Loading funnel...', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Real-time Updates Section -->
    <div class="realtime-section">
        <div class="realtime-header">
            <h3><?php esc_html_e('Real-time Updates', 'skylearn-billing-pro'); ?></h3>
            <div class="realtime-controls">
                <button type="button" class="button button-secondary realtime-toggle">
                    <?php esc_html_e('Start Real-time', 'skylearn-billing-pro'); ?>
                </button>
                <span class="last-refresh-time"></span>
            </div>
        </div>
        
        <div class="realtime-status">
            <div class="status-indicator"></div>
            <span class="status-text"><?php esc_html_e('Real-time updates disabled', 'skylearn-billing-pro'); ?></span>
        </div>
    </div>
</div>

<style>
.analytics-overview {
    .analytics-widgets-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 40px;
    }
    
    .skylearn-analytics-widget {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 6px;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
        
        &:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        
        &.fullscreen {
            position: fixed;
            top: 32px;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 100000;
            border-radius: 0;
            
            .chart-container {
                height: calc(100vh - 180px);
            }
        }
        
        .widget-header {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            
            h3 {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .widget-actions {
                display: flex;
                gap: 5px;
                
                .widget-action-btn {
                    background: none;
                    border: none;
                    padding: 5px;
                    cursor: pointer;
                    color: #646970;
                    border-radius: 3px;
                    font-size: 14px;
                    
                    &:hover {
                        background: rgba(0, 0, 0, 0.05);
                        color: #0073aa;
                    }
                }
            }
        }
        
        .widget-content {
            padding: 20px;
            
            .loading-placeholder {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                height: 200px;
                color: #646970;
                
                .spinner {
                    border: 3px solid #f3f3f3;
                    border-top: 3px solid #0073aa;
                    border-radius: 50%;
                    width: 30px;
                    height: 30px;
                    animation: spin 1s linear infinite;
                    margin-bottom: 10px;
                }
                
                p {
                    margin: 0;
                    font-size: 14px;
                }
            }
            
            .chart-container {
                height: 300px;
                position: relative;
            }
            
            .metrics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 20px;
                
                .metric-item {
                    text-align: center;
                    padding: 15px;
                    background: #f8f9fa;
                    border-radius: 6px;
                    
                    .metric-value {
                        font-size: 28px;
                        font-weight: 700;
                        color: #1d2327;
                        margin-bottom: 5px;
                        
                        &.positive {
                            color: #00a32a;
                        }
                        
                        &.negative {
                            color: #d63638;
                        }
                    }
                    
                    .metric-label {
                        font-size: 13px;
                        color: #646970;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        margin-bottom: 5px;
                    }
                    
                    .metric-change {
                        font-size: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
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
            }
            
            .product-list {
                .product-item {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    padding: 12px 0;
                    border-bottom: 1px solid #f0f0f1;
                    
                    &:last-child {
                        border-bottom: none;
                    }
                    
                    .product-info {
                        flex: 1;
                        
                        .product-name {
                            font-weight: 600;
                            color: #1d2327;
                            margin-bottom: 3px;
                        }
                        
                        .product-stats {
                            font-size: 13px;
                            color: #646970;
                        }
                    }
                    
                    .product-revenue {
                        font-weight: 600;
                        color: #0073aa;
                        font-size: 16px;
                    }
                }
            }
            
            .activity-feed {
                max-height: 300px;
                overflow-y: auto;
                
                .activity-item {
                    display: flex;
                    align-items: flex-start;
                    padding: 12px 0;
                    border-bottom: 1px solid #f0f0f1;
                    
                    &:last-child {
                        border-bottom: none;
                    }
                    
                    .activity-icon {
                        width: 32px;
                        height: 32px;
                        border-radius: 50%;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        margin-right: 12px;
                        font-size: 14px;
                        flex-shrink: 0;
                    }
                    
                    &.success .activity-icon {
                        background: rgba(0, 163, 42, 0.1);
                        color: #00a32a;
                    }
                    
                    &.failed .activity-icon {
                        background: rgba(214, 54, 56, 0.1);
                        color: #d63638;
                    }
                    
                    .activity-details {
                        flex: 1;
                        
                        .activity-description {
                            font-size: 14px;
                            color: #1d2327;
                            margin-bottom: 3px;
                            
                            strong {
                                color: #0073aa;
                            }
                            
                            em {
                                color: #646970;
                            }
                        }
                        
                        .activity-meta {
                            display: flex;
                            gap: 10px;
                            font-size: 12px;
                            color: #646970;
                            
                            .activity-amount {
                                font-weight: 600;
                            }
                        }
                    }
                }
            }
        }
    }
    
    .analytics-insights {
        margin-bottom: 40px;
        
        .insights-header {
            margin-bottom: 25px;
            
            h2 {
                margin: 0 0 5px 0;
                color: #1d2327;
            }
            
            p {
                margin: 0;
                color: #646970;
            }
        }
        
        .insights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            
            .insight-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 6px;
                overflow: hidden;
                
                .insight-header {
                    background: linear-gradient(135deg, #0073aa 0%, #005177 100%);
                    color: white;
                    padding: 20px;
                    display: flex;
                    align-items: center;
                    gap: 15px;
                    
                    .insight-icon {
                        font-size: 24px;
                        opacity: 0.9;
                    }
                    
                    h4 {
                        margin: 0;
                        font-size: 16px;
                        font-weight: 600;
                    }
                }
                
                .widget-content {
                    padding: 20px;
                    
                    .churn-overview,
                    .ltv-overview {
                        display: flex;
                        gap: 20px;
                        
                        .churn-rate,
                        .ltv-item {
                            flex: 1;
                            text-align: center;
                            
                            .metric-value {
                                font-size: 32px;
                                font-weight: 700;
                                margin-bottom: 5px;
                            }
                            
                            .metric-label {
                                font-size: 13px;
                                color: #646970;
                                text-transform: uppercase;
                                letter-spacing: 0.5px;
                            }
                        }
                        
                        .churn-trend {
                            display: flex;
                            flex-direction: column;
                            align-items: center;
                            
                            .trend-indicator {
                                display: flex;
                                align-items: center;
                                gap: 5px;
                                padding: 8px 12px;
                                border-radius: 4px;
                                font-size: 12px;
                                font-weight: 600;
                                text-transform: uppercase;
                                
                                &.improving {
                                    background: rgba(0, 163, 42, 0.1);
                                    color: #00a32a;
                                }
                                
                                &.stable {
                                    background: rgba(255, 193, 7, 0.1);
                                    color: #996800;
                                }
                                
                                &.increasing {
                                    background: rgba(214, 54, 56, 0.1);
                                    color: #d63638;
                                }
                            }
                        }
                    }
                    
                    .funnel-stages {
                        .funnel-stage {
                            margin-bottom: 15px;
                            
                            &:last-child {
                                margin-bottom: 0;
                            }
                            
                            .stage-info {
                                display: flex;
                                justify-content: space-between;
                                align-items: center;
                                margin-bottom: 5px;
                                
                                .stage-label {
                                    font-weight: 600;
                                    color: #1d2327;
                                }
                                
                                .stage-value {
                                    font-size: 14px;
                                    color: #646970;
                                }
                            }
                            
                            .stage-bar {
                                height: 8px;
                                background: #f0f0f1;
                                border-radius: 4px;
                                overflow: hidden;
                                
                                .stage-fill {
                                    height: 100%;
                                    background: linear-gradient(90deg, #0073aa 0%, #005177 100%);
                                    transition: width 0.5s ease;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    .realtime-section {
        background: #fff;
        border: 1px solid #ccd0d4;
        border-radius: 6px;
        padding: 20px;
        
        .realtime-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            
            h3 {
                margin: 0;
                color: #1d2327;
            }
            
            .realtime-controls {
                display: flex;
                align-items: center;
                gap: 15px;
                
                .realtime-toggle {
                    &.active {
                        background: #d63638;
                        border-color: #d63638;
                        color: white;
                    }
                }
                
                .last-refresh-time {
                    font-size: 12px;
                    color: #646970;
                }
            }
        }
        
        .realtime-status {
            display: flex;
            align-items: center;
            gap: 10px;
            
            .status-indicator {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #ddd;
                
                &.active {
                    background: #00a32a;
                    animation: pulse 2s infinite;
                }
            }
            
            .status-text {
                font-size: 14px;
                color: #646970;
            }
        }
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
}
</style>