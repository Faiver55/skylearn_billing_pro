/**
 * Skylearn Billing Pro Analytics JavaScript
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';

    var SkyLearnAnalytics = {
        
        charts: {},
        realTimeInterval: null,
        
        /**
         * Initialize the analytics functionality
         */
        init: function() {
            this.bindEvents();
            this.loadChartLibrary();
            this.loadInitialData();
            this.initializeSortableWidgets();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).on('click', '#apply-analytics-filters', this.applyFilters.bind(this));
            $(document).on('click', '#reset-analytics-filters', this.resetFilters.bind(this));
            $(document).on('click', '#refresh-analytics', this.refreshAllData.bind(this));
            $(document).on('click', '#configure-dashboard', this.openDashboardConfig.bind(this));
            $(document).on('click', '#save-widget-config', this.saveWidgetConfig.bind(this));
            $(document).on('click', '#cancel-widget-config', this.closeDashboardConfig.bind(this));
            $(document).on('click', '.skylearn-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.widget-action-btn', this.handleWidgetAction.bind(this));
            $(document).on('change', '#analytics-period', this.updatePeriod.bind(this));
            
            // Real-time updates
            $(document).on('click', '.realtime-toggle', this.toggleRealTime.bind(this));
        },

        /**
         * Load Chart.js library dynamically
         */
        loadChartLibrary: function() {
            if (typeof Chart === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
                script.onload = function() {
                    SkyLearnAnalytics.initializeCharts();
                };
                document.head.appendChild(script);
            } else {
                this.initializeCharts();
            }
        },

        /**
         * Load initial data
         */
        loadInitialData: function() {
            this.showLoading();
            
            var filters = this.getFilters();
            
            $.ajax({
                url: skylearn_analytics_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_analytics_data',
                    nonce: skylearn_analytics_ajax.nonce,
                    filters: filters
                },
                success: function(response) {
                    if (response.success) {
                        SkyLearnAnalytics.renderDashboard(response.data);
                    } else {
                        SkyLearnAnalytics.showError('Failed to load analytics data');
                    }
                },
                error: function() {
                    SkyLearnAnalytics.showError('Network error occurred');
                },
                complete: function() {
                    SkyLearnAnalytics.hideLoading();
                }
            });
        },

        /**
         * Initialize charts after Chart.js is loaded
         */
        initializeCharts: function() {
            // Set default Chart.js configuration
            Chart.defaults.responsive = true;
            Chart.defaults.maintainAspectRatio = false;
            Chart.defaults.plugins.legend.display = true;
            Chart.defaults.plugins.tooltip.intersect = false;
            Chart.defaults.plugins.tooltip.mode = 'index';
            
            // Custom color scheme
            this.colorScheme = [
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 99, 132, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)',
                'rgba(199, 199, 199, 0.8)',
                'rgba(83, 102, 255, 0.8)'
            ];
        },

        /**
         * Render dashboard with data
         */
        renderDashboard: function(data) {
            this.renderOverviewMetrics(data.overview);
            this.renderRevenueChart(data.revenue_chart);
            this.renderSubscriptionsChart(data.subscriptions_chart);
            this.renderProductSalesChart(data.product_sales_chart);
            this.renderTopProducts(data.top_products);
            this.renderRecentActivity(data.recent_activity);
            this.renderChurnAnalysis(data.churn_analysis);
            this.renderLTVMetrics(data.ltv_metrics);
            this.renderConversionFunnel(data.conversion_funnel);
        },

        /**
         * Render overview metrics
         */
        renderOverviewMetrics: function(data) {
            var container = $('.overview-metrics-widget .widget-content');
            if (container.length === 0) return;

            var html = '<div class="metrics-grid">';
            
            // Total Revenue
            html += '<div class="metric-item">';
            html += '<div class="metric-value' + (data.revenue_change > 0 ? ' positive' : (data.revenue_change < 0 ? ' negative' : '')) + '">$' + this.formatNumber(data.total_revenue) + '</div>';
            html += '<div class="metric-label">Total Revenue</div>';
            html += '<div class="metric-change ' + (data.revenue_change > 0 ? 'positive' : (data.revenue_change < 0 ? 'negative' : '')) + '">';
            html += '<span class="change-icon dashicons ' + (data.revenue_change > 0 ? 'dashicons-arrow-up-alt' : (data.revenue_change < 0 ? 'dashicons-arrow-down-alt' : 'dashicons-minus')) + '"></span>';
            html += Math.abs(data.revenue_change) + '%';
            html += '</div>';
            html += '</div>';
            
            // Active Customers
            html += '<div class="metric-item">';
            html += '<div class="metric-value">' + this.formatNumber(data.active_customers) + '</div>';
            html += '<div class="metric-label">Active Customers</div>';
            html += '<div class="metric-change ' + (data.customer_change > 0 ? 'positive' : (data.customer_change < 0 ? 'negative' : '')) + '">';
            html += '<span class="change-icon dashicons ' + (data.customer_change > 0 ? 'dashicons-arrow-up-alt' : (data.customer_change < 0 ? 'dashicons-arrow-down-alt' : 'dashicons-minus')) + '"></span>';
            html += Math.abs(data.customer_change) + '%';
            html += '</div>';
            html += '</div>';
            
            // Subscriptions
            html += '<div class="metric-item">';
            html += '<div class="metric-value">' + this.formatNumber(data.total_subscriptions) + '</div>';
            html += '<div class="metric-label">Subscriptions</div>';
            html += '<div class="metric-change ' + (data.subscription_change > 0 ? 'positive' : (data.subscription_change < 0 ? 'negative' : '')) + '">';
            html += '<span class="change-icon dashicons ' + (data.subscription_change > 0 ? 'dashicons-arrow-up-alt' : (data.subscription_change < 0 ? 'dashicons-arrow-down-alt' : 'dashicons-minus')) + '"></span>';
            html += Math.abs(data.subscription_change) + '%';
            html += '</div>';
            html += '</div>';
            
            // Conversion Rate
            html += '<div class="metric-item">';
            html += '<div class="metric-value">' + data.conversion_rate + '%</div>';
            html += '<div class="metric-label">Conversion Rate</div>';
            html += '<div class="metric-change ' + (data.conversion_change > 0 ? 'positive' : (data.conversion_change < 0 ? 'negative' : '')) + '">';
            html += '<span class="change-icon dashicons ' + (data.conversion_change > 0 ? 'dashicons-arrow-up-alt' : (data.conversion_change < 0 ? 'dashicons-arrow-down-alt' : 'dashicons-minus')) + '"></span>';
            html += Math.abs(data.conversion_change) + '%';
            html += '</div>';
            html += '</div>';
            
            // LTV
            html += '<div class="metric-item">';
            html += '<div class="metric-value">$' + this.formatNumber(data.ltv) + '</div>';
            html += '<div class="metric-label">Customer LTV</div>';
            html += '<div class="metric-change ' + (data.ltv_change > 0 ? 'positive' : (data.ltv_change < 0 ? 'negative' : '')) + '">';
            html += '<span class="change-icon dashicons ' + (data.ltv_change > 0 ? 'dashicons-arrow-up-alt' : (data.ltv_change < 0 ? 'dashicons-arrow-down-alt' : 'dashicons-minus')) + '"></span>';
            html += Math.abs(data.ltv_change) + '%';
            html += '</div>';
            html += '</div>';
            
            // Churn Rate
            html += '<div class="metric-item">';
            html += '<div class="metric-value' + (data.churn_rate > 5 ? ' negative' : ' positive') + '">' + data.churn_rate + '%</div>';
            html += '<div class="metric-label">Churn Rate</div>';
            html += '<div class="metric-change ' + (data.churn_change < 0 ? 'positive' : 'negative') + '">';
            html += '<span class="change-icon dashicons ' + (data.churn_change < 0 ? 'dashicons-arrow-down-alt' : 'dashicons-arrow-up-alt') + '"></span>';
            html += Math.abs(data.churn_change) + '%';
            html += '</div>';
            html += '</div>';
            
            html += '</div>';
            
            container.html(html);
        },

        /**
         * Render revenue chart
         */
        renderRevenueChart: function(data) {
            var canvas = document.getElementById('revenue-chart');
            if (!canvas) return;

            // Destroy existing chart
            if (this.charts.revenue) {
                this.charts.revenue.destroy();
            }

            var ctx = canvas.getContext('2d');
            this.charts.revenue = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(function(dataset, index) {
                        return {
                            ...dataset,
                            borderColor: SkyLearnAnalytics.colorScheme[index % SkyLearnAnalytics.colorScheme.length],
                            backgroundColor: SkyLearnAnalytics.colorScheme[index % SkyLearnAnalytics.colorScheme.length].replace('0.8', '0.1'),
                            tension: 0.4
                        };
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + SkyLearnAnalytics.formatNumber(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + SkyLearnAnalytics.formatNumber(context.parsed.y);
                                }
                            }
                        }
                    }
                }
            });
        },

        /**
         * Render subscriptions chart
         */
        renderSubscriptionsChart: function(data) {
            var canvas = document.getElementById('subscriptions-chart');
            if (!canvas) return;

            // Destroy existing chart
            if (this.charts.subscriptions) {
                this.charts.subscriptions.destroy();
            }

            var ctx = canvas.getContext('2d');
            this.charts.subscriptions = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(function(dataset, index) {
                        return {
                            ...dataset,
                            backgroundColor: SkyLearnAnalytics.colorScheme[index % SkyLearnAnalytics.colorScheme.length],
                            borderColor: SkyLearnAnalytics.colorScheme[index % SkyLearnAnalytics.colorScheme.length].replace('0.8', '1'),
                            borderWidth: 1
                        };
                    })
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        },

        /**
         * Render product sales chart
         */
        renderProductSalesChart: function(data) {
            var canvas = document.getElementById('product-sales-chart');
            if (!canvas) return;

            // Destroy existing chart
            if (this.charts.products) {
                this.charts.products.destroy();
            }

            var ctx = canvas.getContext('2d');
            this.charts.products = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: data.labels,
                    datasets: [{
                        data: data.datasets[0].data,
                        backgroundColor: this.colorScheme,
                        borderColor: this.colorScheme.map(color => color.replace('0.8', '1')),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        },

        /**
         * Render top products list
         */
        renderTopProducts: function(data) {
            var container = $('.top-products-widget .widget-content');
            if (container.length === 0) return;

            var html = '<div class="product-list">';
            
            $.each(data, function(productId, product) {
                html += '<div class="product-item">';
                html += '<div class="product-info">';
                html += '<div class="product-name">' + product.name + '</div>';
                html += '<div class="product-stats">' + product.sales + ' sales • $' + SkyLearnAnalytics.formatNumber(product.revenue) + '</div>';
                html += '</div>';
                html += '<div class="product-revenue">$' + SkyLearnAnalytics.formatNumber(product.revenue) + '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            container.html(html);
        },

        /**
         * Render recent activity feed
         */
        renderRecentActivity: function(data) {
            var container = $('.recent-activity-widget .widget-content');
            if (container.length === 0) return;

            var html = '<div class="activity-feed">';
            
            $.each(data, function(index, activity) {
                var iconClass = activity.type === 'purchase' ? 'dashicons-cart' : 'dashicons-dismiss';
                var statusClass = activity.type === 'purchase' ? 'success' : 'failed';
                
                html += '<div class="activity-item ' + statusClass + '">';
                html += '<div class="activity-icon"><span class="dashicons ' + iconClass + '"></span></div>';
                html += '<div class="activity-details">';
                html += '<div class="activity-description">';
                html += '<strong>' + activity.user_name + '</strong> ';
                html += activity.type === 'purchase' ? 'purchased' : 'failed to purchase';
                html += ' <em>' + activity.product_name + '</em>';
                html += '</div>';
                html += '<div class="activity-meta">';
                html += '<span class="activity-amount">$' + SkyLearnAnalytics.formatNumber(activity.amount) + '</span>';
                html += '<span class="activity-time">' + activity.formatted_time + '</span>';
                html += '</div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';
            
            container.html(html);
        },

        /**
         * Render churn analysis
         */
        renderChurnAnalysis: function(data) {
            var container = $('.churn-analysis-widget .widget-content');
            if (container.length === 0) return;

            var html = '<div class="churn-overview">';
            html += '<div class="churn-rate">';
            html += '<div class="metric-value' + (data.current_churn_rate > 5 ? ' negative' : ' positive') + '">' + data.current_churn_rate.toFixed(1) + '%</div>';
            html += '<div class="metric-label">Current Churn Rate</div>';
            html += '</div>';
            
            html += '<div class="churn-trend">';
            html += '<div class="trend-indicator ' + data.churn_trend + '">';
            html += '<span class="dashicons dashicons-arrow-' + (data.churn_trend === 'improving' ? 'down' : (data.churn_trend === 'increasing' ? 'up' : 'right')) + '-alt"></span>';
            html += '<span class="trend-text">' + data.churn_trend.charAt(0).toUpperCase() + data.churn_trend.slice(1) + '</span>';
            html += '</div>';
            html += '</div>';
            html += '</div>';

            container.html(html);
        },

        /**
         * Render LTV metrics
         */
        renderLTVMetrics: function(data) {
            var container = $('.ltv-metrics-widget .widget-content');
            if (container.length === 0) return;

            var html = '<div class="ltv-overview">';
            html += '<div class="ltv-item">';
            html += '<div class="metric-value">$' + this.formatNumber(data.average_ltv) + '</div>';
            html += '<div class="metric-label">Average LTV</div>';
            html += '</div>';
            
            html += '<div class="ltv-item">';
            html += '<div class="metric-value">$' + this.formatNumber(data.median_ltv) + '</div>';
            html += '<div class="metric-label">Median LTV</div>';
            html += '</div>';
            
            html += '<div class="ltv-item">';
            html += '<div class="metric-value">' + this.formatNumber(data.total_customers) + '</div>';
            html += '<div class="metric-label">Total Customers</div>';
            html += '</div>';
            html += '</div>';

            container.html(html);
        },

        /**
         * Render conversion funnel
         */
        renderConversionFunnel: function(data) {
            var container = $('.conversion-funnel-widget .widget-content');
            if (container.length === 0) return;

            var html = '<div class="funnel-stages">';
            
            var stages = [
                { label: 'Visitors', value: data.visitors },
                { label: 'Product Views', value: data.product_views },
                { label: 'Checkout Started', value: data.checkout_started },
                { label: 'Checkout Completed', value: data.checkout_completed }
            ];
            
            $.each(stages, function(index, stage) {
                var percentage = index === 0 ? 100 : ((stage.value / stages[0].value) * 100);
                
                html += '<div class="funnel-stage">';
                html += '<div class="stage-info">';
                html += '<div class="stage-label">' + stage.label + '</div>';
                html += '<div class="stage-value">' + SkyLearnAnalytics.formatNumber(stage.value) + ' (' + percentage.toFixed(1) + '%)</div>';
                html += '</div>';
                html += '<div class="stage-bar">';
                html += '<div class="stage-fill" style="width: ' + percentage + '%"></div>';
                html += '</div>';
                html += '</div>';
            });
            
            html += '</div>';

            container.html(html);
        },

        /**
         * Apply filters
         */
        applyFilters: function(e) {
            e.preventDefault();
            this.loadInitialData();
        },

        /**
         * Reset filters
         */
        resetFilters: function(e) {
            e.preventDefault();
            $('#analytics-date-from').val(new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0]);
            $('#analytics-date-to').val(new Date().toISOString().split('T')[0]);
            $('#analytics-period').val('monthly');
            this.loadInitialData();
        },

        /**
         * Refresh all data
         */
        refreshAllData: function(e) {
            e.preventDefault();
            this.loadInitialData();
        },

        /**
         * Update period
         */
        updatePeriod: function(e) {
            this.loadInitialData();
        },

        /**
         * Get current filters
         */
        getFilters: function() {
            return {
                date_from: $('#analytics-date-from').val() || new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0],
                date_to: $('#analytics-date-to').val() || new Date().toISOString().split('T')[0],
                period: $('#analytics-period').val() || 'monthly'
            };
        },

        /**
         * Real-time data refresh
         */
        refreshRealTimeData: function() {
            var self = this;
            
            $.ajax({
                url: skylearn_analytics_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_realtime_update',
                    nonce: skylearn_analytics_ajax.nonce,
                    widget_type: 'overview_metrics',
                    filters: this.getFilters()
                },
                success: function(response) {
                    if (response.success) {
                        self.renderOverviewMetrics(response.data);
                        self.updateLastRefreshTime();
                    }
                }
            });
        },

        /**
         * Toggle real-time updates
         */
        toggleRealTime: function(e) {
            var $btn = $(e.currentTarget);
            
            if (this.realTimeInterval) {
                clearInterval(this.realTimeInterval);
                this.realTimeInterval = null;
                $btn.removeClass('active').text('Start Real-time');
            } else {
                this.realTimeInterval = setInterval(this.refreshRealTimeData.bind(this), 30000);
                $btn.addClass('active').text('Stop Real-time');
                this.refreshRealTimeData();
            }
        },

        /**
         * Update last refresh time
         */
        updateLastRefreshTime: function() {
            var now = new Date();
            var timeString = now.toLocaleTimeString();
            $('.last-refresh-time').text('Last updated: ' + timeString);
        },

        /**
         * Open dashboard configuration modal
         */
        openDashboardConfig: function(e) {
            e.preventDefault();
            $('#dashboard-config-modal').show();
        },

        /**
         * Close dashboard configuration modal
         */
        closeDashboardConfig: function(e) {
            e.preventDefault();
            $('#dashboard-config-modal').hide();
        },

        /**
         * Save widget configuration
         */
        saveWidgetConfig: function(e) {
            e.preventDefault();
            
            var widgets = {};
            var position = 1;
            
            $('#sortable-widgets .widget-config-item').each(function() {
                var $item = $(this);
                var widgetId = $item.data('widget-id');
                var enabled = $item.find('input[type="checkbox"]').is(':checked');
                
                widgets[widgetId] = {
                    title: $item.find('.widget-config-info strong').text(),
                    type: $item.find('.widget-type').text(),
                    position: position++,
                    enabled: enabled
                };
            });
            
            $.ajax({
                url: skylearn_analytics_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_widget_config',
                    nonce: skylearn_analytics_ajax.nonce,
                    widgets: widgets
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        SkyLearnAnalytics.showError('Failed to save widget configuration');
                    }
                },
                error: function() {
                    SkyLearnAnalytics.showError('Network error occurred');
                }
            });
        },

        /**
         * Initialize sortable widgets
         */
        initializeSortableWidgets: function() {
            if (typeof $.fn.sortable !== 'undefined') {
                $('#sortable-widgets').sortable({
                    handle: '.widget-config-handle',
                    cursor: 'move',
                    opacity: 0.8
                });
            }
        },

        /**
         * Handle widget actions
         */
        handleWidgetAction: function(e) {
            e.preventDefault();
            var $btn = $(e.currentTarget);
            var action = $btn.data('action');
            var widgetId = $btn.closest('.skylearn-analytics-widget').data('widget-id');
            
            switch (action) {
                case 'refresh':
                    this.refreshWidget(widgetId);
                    break;
                case 'fullscreen':
                    this.toggleWidgetFullscreen(widgetId);
                    break;
                case 'export':
                    this.exportWidget(widgetId);
                    break;
            }
        },

        /**
         * Refresh individual widget
         */
        refreshWidget: function(widgetId) {
            var self = this;
            
            $.ajax({
                url: skylearn_analytics_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_realtime_update',
                    nonce: skylearn_analytics_ajax.nonce,
                    widget_type: widgetId,
                    filters: this.getFilters()
                },
                success: function(response) {
                    if (response.success) {
                        // Re-render specific widget based on type
                        switch (widgetId) {
                            case 'overview_metrics':
                                self.renderOverviewMetrics(response.data);
                                break;
                            case 'revenue_chart':
                                self.renderRevenueChart(response.data);
                                break;
                            case 'recent_activity':
                                self.renderRecentActivity(response.data);
                                break;
                        }
                    }
                }
            });
        },

        /**
         * Toggle widget fullscreen
         */
        toggleWidgetFullscreen: function(widgetId) {
            var $widget = $('.skylearn-analytics-widget[data-widget-id="' + widgetId + '"]');
            $widget.toggleClass('fullscreen');
            
            // Resize charts when entering/exiting fullscreen
            if (this.charts[widgetId]) {
                setTimeout(function() {
                    SkyLearnAnalytics.charts[widgetId].resize();
                }, 300);
            }
        },

        /**
         * Export widget data
         */
        exportWidget: function(widgetId) {
            // Create a temporary form to trigger export
            var form = $('<form>').attr({
                method: 'POST',
                action: skylearn_analytics_ajax.ajax_url
            });
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'action',
                value: 'skylearn_export_widget'
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'nonce',
                value: skylearn_analytics_ajax.nonce
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'widget_id',
                value: widgetId
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'filters',
                value: JSON.stringify(this.getFilters())
            }));
            
            $('body').append(form);
            form.submit();
            form.remove();
        },

        /**
         * Close modal
         */
        closeModal: function(e) {
            $(e.currentTarget).closest('.skylearn-modal').hide();
        },

        /**
         * Show loading overlay
         */
        showLoading: function() {
            $('#analytics-loading').show();
        },

        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('#analytics-loading').hide();
        },

        /**
         * Show error message
         */
        showError: function(message) {
            // Create temporary error notification
            var $error = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
            $('.skylearn-billing-header').after($error);
            
            setTimeout(function() {
                $error.fadeOut(function() {
                    $error.remove();
                });
            }, 5000);
        },

        /**
         * Format number for display
         */
        formatNumber: function(num) {
            if (num >= 1000000) {
                return (num / 1000000).toFixed(1) + 'M';
            } else if (num >= 1000) {
                return (num / 1000).toFixed(1) + 'K';
            } else {
                return num.toLocaleString();
            }
        }
    };

    // Make available globally
    window.SkyLearnAnalytics = SkyLearnAnalytics;

})(jQuery);