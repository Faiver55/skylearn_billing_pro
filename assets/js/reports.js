/**
 * Skylearn Billing Pro Reports JavaScript
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';

    var SkyLearnReports = {
        
        /**
         * Initialize the reports functionality
         */
        init: function() {
            this.bindEvents();
            this.loadInitialData();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).on('click', '#apply-filters', this.applyFilters);
            $(document).on('click', '#reset-filters', this.resetFilters);
            $(document).on('click', '.export-chart', this.exportChart);
            $(document).on('click', '.export-table', this.exportTable);
            $(document).on('click', '.export-all-reports', this.exportAllReports);
            $(document).on('click', '.refresh-data', this.refreshData);
            $(document).on('change', '#date-from, #date-to', this.updateDateRange);
        },

        /**
         * Load initial data
         */
        loadInitialData: function() {
            this.populateProductFilter();
            this.populateCourseFilter();
            this.loadCurrentTabData();
        },

        /**
         * Apply filters to current report
         */
        applyFilters: function(e) {
            e.preventDefault();
            
            var filters = SkyLearnReports.getFilterValues();
            SkyLearnReports.loadReportData(SkyLearnReports.getCurrentReportType(), filters);
        },

        /**
         * Reset all filters
         */
        resetFilters: function(e) {
            e.preventDefault();
            
            $('#date-from').val(SkyLearnReports.getMonthStart());
            $('#date-to').val(SkyLearnReports.getToday());
            $('#product-filter').val('');
            $('#course-filter').val('');
            $('#status-filter').val('');
            
            SkyLearnReports.applyFilters(e);
        },

        /**
         * Get current filter values
         */
        getFilterValues: function() {
            return {
                date_from: $('#date-from').val(),
                date_to: $('#date-to').val(),
                product_id: $('#product-filter').val(),
                course_id: $('#course-filter').val(),
                status: $('#status-filter').val()
            };
        },

        /**
         * Get current report type from URL
         */
        getCurrentReportType: function() {
            var urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('tab') || 'dashboard';
        },

        /**
         * Load report data via AJAX
         */
        loadReportData: function(reportType, filters) {
            var data = {
                action: 'skylearn_reporting_data',
                nonce: skylearn_admin_nonces.skylearn_reporting_data || '',
                report_type: reportType,
                filters: filters
            };

            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    SkyLearnReports.updateReportDisplay(reportType, response.data);
                } else {
                    console.error('Failed to load report data:', response.data);
                }
            }).fail(function() {
                console.error('AJAX request failed');
            });
        },

        /**
         * Update report display with new data
         */
        updateReportDisplay: function(reportType, data) {
            switch (reportType) {
                case 'sales':
                    this.updateSalesReport(data);
                    break;
                case 'enrollments':
                    this.updateEnrollmentsReport(data);
                    break;
                case 'revenue':
                    this.updateRevenueReport(data);
                    break;
                case 'emails':
                    this.updateEmailsReport(data);
                    break;
                case 'cohort':
                    this.updateCohortReport(data);
                    break;
                case 'retention':
                    this.updateRetentionReport(data);
                    break;
                default:
                    this.updateDashboard(data);
                    break;
            }
        },

        /**
         * Export chart data
         */
        exportChart: function(e) {
            e.preventDefault();
            
            var chartType = $(this).data('chart');
            var format = $(this).data('format');
            var filters = SkyLearnReports.getFilterValues();
            
            SkyLearnReports.performExport(chartType, format, filters);
        },

        /**
         * Export table data
         */
        exportTable: function(e) {
            e.preventDefault();
            
            var tableType = $(this).data('table');
            var format = $(this).data('format');
            var filters = SkyLearnReports.getFilterValues();
            
            SkyLearnReports.performExport(tableType, format, filters);
        },

        /**
         * Export all reports
         */
        exportAllReports: function(e) {
            e.preventDefault();
            
            var filters = SkyLearnReports.getFilterValues();
            var reportTypes = ['sales', 'enrollments', 'revenue', 'emails'];
            
            reportTypes.forEach(function(reportType) {
                setTimeout(function() {
                    SkyLearnReports.performExport(reportType, 'csv', filters);
                }, 500);
            });
        },

        /**
         * Perform export action
         */
        performExport: function(reportType, format, filters) {
            $('#export-report-type').val(reportType);
            $('#export-format').val(format);
            $('#export-filters').val(JSON.stringify(filters));
            
            $('#export-form').submit();
        },

        /**
         * Refresh current data
         */
        refreshData: function(e) {
            e.preventDefault();
            
            var reportType = SkyLearnReports.getCurrentReportType();
            var filters = SkyLearnReports.getFilterValues();
            
            SkyLearnReports.loadReportData(reportType, filters);
        },

        /**
         * Handle date range updates
         */
        updateDateRange: function() {
            // Auto-apply filters when date changes
            SkyLearnReports.applyFilters({ preventDefault: function() {} });
        },

        /**
         * Load current tab data
         */
        loadCurrentTabData: function() {
            var reportType = this.getCurrentReportType();
            var filters = this.getFilterValues();
            
            if (reportType !== 'dashboard') {
                this.loadReportData(reportType, filters);
            }
        },

        /**
         * Populate product filter dropdown
         */
        populateProductFilter: function() {
            // In a real implementation, this would fetch products via AJAX
            var products = [
                { id: '1', name: 'WordPress Course' },
                { id: '2', name: 'React Course' },
                { id: '3', name: 'PHP Course' }
            ];

            var $select = $('#product-filter');
            products.forEach(function(product) {
                $select.append('<option value="' + product.id + '">' + product.name + '</option>');
            });
        },

        /**
         * Populate course filter dropdown
         */
        populateCourseFilter: function() {
            // In a real implementation, this would fetch courses via AJAX
            var courses = [
                { id: '1', name: 'Beginner WordPress' },
                { id: '2', name: 'Advanced React' },
                { id: '3', name: 'PHP Fundamentals' }
            ];

            var $select = $('#course-filter');
            courses.forEach(function(course) {
                $select.append('<option value="' + course.id + '">' + course.name + '</option>');
            });
        },

        /**
         * Initialize dashboard charts
         */
        initDashboardCharts: function(data) {
            if (typeof Chart === 'undefined') {
                console.warn('Chart.js not loaded. Charts will not be displayed.');
                return;
            }

            this.createSalesTrendChart(data.salesData);
            this.createRevenueTrendChart(data.revenueData);
        },

        /**
         * Create sales trend chart
         */
        createSalesTrendChart: function(salesData) {
            var ctx = document.getElementById('sales-trend-chart');
            if (!ctx) return;

            var labels = Object.keys(salesData);
            var data = Object.values(salesData);

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Sales',
                        data: data,
                        borderColor: '#FF3B00',
                        backgroundColor: 'rgba(255, 59, 0, 0.1)',
                        borderWidth: 2,
                        fill: true
                    }]
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
         * Create revenue trend chart
         */
        createRevenueTrendChart: function(revenueData) {
            var ctx = document.getElementById('revenue-trend-chart');
            if (!ctx) return;

            var labels = Object.keys(revenueData);
            var data = Object.values(revenueData);

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue ($)',
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
                    }
                }
            });
        },

        /**
         * Update sales report
         */
        updateSalesReport: function(data) {
            // Update metrics
            $('.sales-total-sales').text(data.total_sales.toLocaleString());
            $('.sales-successful').text(data.successful_sales.toLocaleString());
            $('.sales-failed').text(data.failed_sales.toLocaleString());
            $('.sales-conversion-rate').text(data.conversion_rate + '%');
        },

        /**
         * Update enrollments report
         */
        updateEnrollmentsReport: function(data) {
            $('.enrollments-total').text(data.total_enrollments.toLocaleString());
            $('.enrollments-successful').text(data.successful_enrollments.toLocaleString());
            $('.enrollments-failed').text(data.failed_enrollments.toLocaleString());
        },

        /**
         * Update revenue report
         */
        updateRevenueReport: function(data) {
            $('.revenue-total').text('$' + data.total_revenue.toLocaleString());
            $('.revenue-average').text('$' + data.average_order_value.toLocaleString());
            $('.revenue-net').text('$' + data.net_revenue.toLocaleString());
        },

        /**
         * Update emails report
         */
        updateEmailsReport: function(data) {
            $('.emails-total').text(data.total_emails.toLocaleString());
            $('.emails-successful').text(data.successful_emails.toLocaleString());
            $('.emails-failed').text(data.failed_emails.toLocaleString());
            $('.emails-delivery-rate').text(data.delivery_rate + '%');
        },

        /**
         * Update cohort report
         */
        updateCohortReport: function(data) {
            // Implementation for cohort analysis display
            console.log('Cohort data:', data);
        },

        /**
         * Update retention report
         */
        updateRetentionReport: function(data) {
            $('.retention-day-1').text(data.day_1_retention + '%');
            $('.retention-day-7').text(data.day_7_retention + '%');
            $('.retention-day-30').text(data.day_30_retention + '%');
            $('.retention-average').text(data.average_retention + '%');
        },

        /**
         * Update dashboard
         */
        updateDashboard: function(data) {
            // Dashboard is updated on page load, but this could refresh specific sections
        },

        /**
         * Get month start date
         */
        getMonthStart: function() {
            var now = new Date();
            return new Date(now.getFullYear(), now.getMonth(), 1).toISOString().split('T')[0];
        },

        /**
         * Get today's date
         */
        getToday: function() {
            return new Date().toISOString().split('T')[0];
        }
    };

    // Make SkyLearnReports globally available
    window.SkyLearnReports = SkyLearnReports;

    // Auto-initialize when DOM is ready
    $(document).ready(function() {
        if ($('.skylearn-reports').length > 0) {
            SkyLearnReports.init();
        }
    });

})(jQuery);