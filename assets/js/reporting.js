/**
 * Skylearn Billing Pro Reporting JavaScript
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';

    var SkyLearnReporting = {
        
        currentConfig: {},
        currentData: null,
        
        /**
         * Initialize the reporting functionality
         */
        init: function() {
            this.bindEvents();
            this.loadReportTypes();
            this.initializeDatePickers();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            $(document).on('click', '#generate-report', this.generateReport.bind(this));
            $(document).on('click', '#save-report-template', this.showSaveTemplate.bind(this));
            $(document).on('click', '#schedule-report', this.showScheduleModal.bind(this));
            $(document).on('click', '.export-btn', this.exportReport.bind(this));
            $(document).on('click', '.use-template', this.useTemplate.bind(this));
            $(document).on('click', '.edit-template', this.editTemplate.bind(this));
            $(document).on('click', '.delete-template', this.deleteTemplate.bind(this));
            $(document).on('click', '.edit-scheduled', this.editScheduled.bind(this));
            $(document).on('click', '.delete-scheduled', this.deleteScheduled.bind(this));
            $(document).on('click', '.toggle-scheduled', this.toggleScheduled.bind(this));
            
            // Modal events
            $(document).on('click', '#save-scheduled-report', this.saveScheduledReport.bind(this));
            $(document).on('click', '#save-template', this.saveTemplate.bind(this));
            $(document).on('click', '#cancel-schedule, #cancel-template', this.closeModal.bind(this));
            $(document).on('click', '.skylearn-modal-close', this.closeModal.bind(this));
            
            // Form changes
            $(document).on('change', '#report-type', this.updateReportType.bind(this));
            $(document).on('change', '#date-from, #date-to', this.validateDateRange.bind(this));
            $(document).on('change', '.column-checkbox', this.updateColumns.bind(this));
        },

        /**
         * Load available report types
         */
        loadReportTypes: function() {
            // Report types are populated from PHP, but we can add dynamic behavior here
            this.updateReportType();
        },

        /**
         * Initialize date pickers
         */
        initializeDatePickers: function() {
            // Set default date range to current month
            var today = new Date();
            var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
            
            if (!$('#date-from').val()) {
                $('#date-from').val(firstDay.toISOString().split('T')[0]);
            }
            
            if (!$('#date-to').val()) {
                $('#date-to').val(today.toISOString().split('T')[0]);
            }
        },

        /**
         * Update report type specific options
         */
        updateReportType: function() {
            var reportType = $('#report-type').val();
            
            if (!reportType) {
                $('#dynamic-filters').empty();
                $('#column-selector').empty();
                return;
            }
            
            this.loadReportFilters(reportType);
            this.loadReportColumns(reportType);
            this.updateSortOptions(reportType);
        },

        /**
         * Load filters for specific report type
         */
        loadReportFilters: function(reportType) {
            var filtersHtml = '';
            
            switch (reportType) {
                case 'sales':
                    filtersHtml += this.createFilterGroup('product-filter', 'Product:', 'select', this.getProductOptions());
                    filtersHtml += this.createFilterGroup('customer-filter', 'Customer:', 'select', this.getCustomerOptions());
                    filtersHtml += this.createFilterGroup('status-filter', 'Status:', 'select', {
                        '': 'All Statuses',
                        'success': 'Successful',
                        'failed': 'Failed'
                    });
                    filtersHtml += this.createFilterGroup('gateway-filter', 'Gateway:', 'select', {
                        '': 'All Gateways',
                        'stripe': 'Stripe',
                        'paddle': 'Paddle',
                        'lemonsqueezy': 'Lemon Squeezy'
                    });
                    break;
                    
                case 'customers':
                    filtersHtml += this.createFilterGroup('customer-segment', 'Segment:', 'select', {
                        '': 'All Segments',
                        'new': 'New Customers',
                        'returning': 'Returning Customers',
                        'vip': 'VIP Customers'
                    });
                    filtersHtml += this.createFilterGroup('min-orders', 'Min Orders:', 'number', '', 'placeholder="1"');
                    filtersHtml += this.createFilterGroup('min-spent', 'Min Spent:', 'number', '', 'placeholder="0"');
                    break;
                    
                case 'products':
                    filtersHtml += this.createFilterGroup('product-category', 'Category:', 'select', this.getProductCategories());
                    filtersHtml += this.createFilterGroup('min-sales', 'Min Sales:', 'number', '', 'placeholder="1"');
                    break;
                    
                case 'revenue':
                    filtersHtml += this.createFilterGroup('revenue-type', 'Revenue Type:', 'select', {
                        'all': 'All Revenue',
                        'new': 'New Customer Revenue',
                        'existing': 'Existing Customer Revenue'
                    });
                    break;
                    
                case 'subscriptions':
                    filtersHtml += this.createFilterGroup('subscription-status', 'Status:', 'select', {
                        '': 'All Statuses',
                        'active': 'Active',
                        'cancelled': 'Cancelled',
                        'expired': 'Expired'
                    });
                    filtersHtml += this.createFilterGroup('plan-type', 'Plan Type:', 'select', this.getSubscriptionPlans());
                    break;
            }
            
            $('#dynamic-filters').html(filtersHtml);
        },

        /**
         * Load columns for specific report type
         */
        loadReportColumns: function(reportType) {
            var columns = this.getReportColumns(reportType);
            var columnsHtml = '';
            
            $.each(columns, function(columnId, columnConfig) {
                columnsHtml += '<div class="column-item">';
                columnsHtml += '<input type="checkbox" id="column-' + columnId + '" class="column-checkbox" value="' + columnId + '" checked>';
                columnsHtml += '<label for="column-' + columnId + '">';
                columnsHtml += '<strong>' + columnConfig.label + '</strong>';
                if (columnConfig.description) {
                    columnsHtml += '<span class="column-description">' + columnConfig.description + '</span>';
                }
                columnsHtml += '</label>';
                columnsHtml += '</div>';
            });
            
            $('#column-selector').html(columnsHtml);
        },

        /**
         * Get columns for report type
         */
        getReportColumns: function(reportType) {
            var columns = {};
            
            switch (reportType) {
                case 'sales':
                    columns = {
                        'date': { label: 'Date', description: 'Transaction date' },
                        'order_id': { label: 'Order ID', description: 'Unique order identifier' },
                        'customer_name': { label: 'Customer Name', description: 'Customer full name' },
                        'customer_email': { label: 'Customer Email', description: 'Customer email address' },
                        'product_name': { label: 'Product Name', description: 'Product or service name' },
                        'amount': { label: 'Amount', description: 'Transaction amount' },
                        'status': { label: 'Status', description: 'Transaction status' },
                        'gateway': { label: 'Gateway', description: 'Payment gateway used' },
                        'course_title': { label: 'Course', description: 'Associated course' },
                        'trigger': { label: 'Trigger', description: 'What triggered the enrollment' }
                    };
                    break;
                    
                case 'customers':
                    columns = {
                        'customer_name': { label: 'Customer Name', description: 'Customer full name' },
                        'customer_email': { label: 'Email', description: 'Customer email address' },
                        'registration_date': { label: 'Registration Date', description: 'Account creation date' },
                        'total_orders': { label: 'Total Orders', description: 'Number of orders placed' },
                        'successful_orders': { label: 'Successful Orders', description: 'Number of successful orders' },
                        'total_spent': { label: 'Total Spent', description: 'Lifetime value' },
                        'last_purchase_date': { label: 'Last Purchase', description: 'Date of last purchase' },
                        'products_purchased': { label: 'Products Purchased', description: 'Number of unique products' },
                        'courses_enrolled': { label: 'Courses Enrolled', description: 'Number of courses enrolled' }
                    };
                    break;
                    
                case 'products':
                    columns = {
                        'product_name': { label: 'Product Name', description: 'Product or service name' },
                        'product_price': { label: 'Price', description: 'Product price' },
                        'successful_sales': { label: 'Sales', description: 'Number of successful sales' },
                        'total_revenue': { label: 'Revenue', description: 'Total revenue generated' },
                        'conversion_rate': { label: 'Conversion Rate', description: 'Success rate percentage' },
                        'unique_customers': { label: 'Unique Buyers', description: 'Number of unique customers' }
                    };
                    break;
                    
                case 'revenue':
                    columns = {
                        'period': { label: 'Period', description: 'Time period' },
                        'revenue': { label: 'Revenue', description: 'Total revenue for period' },
                        'orders': { label: 'Orders', description: 'Number of orders' },
                        'average_order_value': { label: 'AOV', description: 'Average order value' },
                        'unique_customers': { label: 'Customers', description: 'Unique customers in period' }
                    };
                    break;
                    
                case 'subscriptions':
                    columns = {
                        'subscription_id': { label: 'Subscription ID', description: 'Unique subscription identifier' },
                        'customer_name': { label: 'Customer', description: 'Customer name' },
                        'plan_name': { label: 'Plan', description: 'Subscription plan' },
                        'status': { label: 'Status', description: 'Current status' },
                        'start_date': { label: 'Start Date', description: 'Subscription start date' },
                        'mrr': { label: 'MRR', description: 'Monthly recurring revenue' }
                    };
                    break;
            }
            
            return columns;
        },

        /**
         * Update sort options based on report type
         */
        updateSortOptions: function(reportType) {
            var sortSelect = $('#sort-by');
            var columns = this.getReportColumns(reportType);
            
            sortSelect.empty();
            
            $.each(columns, function(columnId, columnConfig) {
                sortSelect.append('<option value="' + columnId + '">' + columnConfig.label + '</option>');
            });
        },

        /**
         * Create filter group HTML
         */
        createFilterGroup: function(id, label, type, options, attributes) {
            var html = '<div class="config-row"><div class="config-group">';
            html += '<label for="' + id + '">' + label + '</label>';
            
            if (type === 'select') {
                html += '<select id="' + id + '" name="' + id + '">';
                $.each(options, function(value, text) {
                    html += '<option value="' + value + '">' + text + '</option>';
                });
                html += '</select>';
            } else {
                html += '<input type="' + type + '" id="' + id + '" name="' + id + '" ' + (attributes || '') + '>';
            }
            
            html += '</div></div>';
            return html;
        },

        /**
         * Get product options for filter
         */
        getProductOptions: function() {
            // In a real implementation, this would fetch from the server
            return {
                '': 'All Products',
                '1': 'Product 1',
                '2': 'Product 2',
                '3': 'Product 3'
            };
        },

        /**
         * Get customer options for filter
         */
        getCustomerOptions: function() {
            return {
                '': 'All Customers'
                // Would be populated dynamically
            };
        },

        /**
         * Get product categories
         */
        getProductCategories: function() {
            return {
                '': 'All Categories',
                'course': 'Courses',
                'ebook': 'E-books',
                'membership': 'Memberships'
            };
        },

        /**
         * Get subscription plans
         */
        getSubscriptionPlans: function() {
            return {
                '': 'All Plans',
                'basic': 'Basic Plan',
                'pro': 'Pro Plan',
                'premium': 'Premium Plan'
            };
        },

        /**
         * Generate report
         */
        generateReport: function(e) {
            e.preventDefault();
            
            var config = this.getReportConfig();
            
            if (!this.validateConfig(config)) {
                return;
            }
            
            this.currentConfig = config;
            this.showLoading();
            
            $.ajax({
                url: skylearn_reporting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_generate_report',
                    nonce: skylearn_reporting_ajax.nonce,
                    config: config
                },
                success: function(response) {
                    if (response.success) {
                        SkyLearnReporting.currentData = response.data;
                        SkyLearnReporting.renderReportPreview(response.data);
                    } else {
                        SkyLearnReporting.showError('Failed to generate report: ' + (response.data || 'Unknown error'));
                    }
                },
                error: function() {
                    SkyLearnReporting.showError('Network error occurred while generating report');
                },
                complete: function() {
                    SkyLearnReporting.hideLoading();
                }
            });
        },

        /**
         * Get current report configuration
         */
        getReportConfig: function() {
            var config = {
                name: $('#report-name').val() || 'Untitled Report',
                type: $('#report-type').val(),
                date_from: $('#date-from').val(),
                date_to: $('#date-to').val(),
                sort_by: $('#sort-by').val(),
                sort_order: $('#sort-order').val(),
                group_by: $('#group-by').val(),
                format: $('#format').val(),
                filters: {},
                columns: []
            };
            
            // Get filters
            $('#dynamic-filters input, #dynamic-filters select').each(function() {
                var $input = $(this);
                var value = $input.val();
                if (value && value !== '') {
                    config.filters[$input.attr('name')] = value;
                }
            });
            
            // Get selected columns
            $('.column-checkbox:checked').each(function() {
                config.columns.push($(this).val());
            });
            
            return config;
        },

        /**
         * Validate report configuration
         */
        validateConfig: function(config) {
            if (!config.type) {
                this.showError('Please select a report type');
                return false;
            }
            
            if (!config.date_from || !config.date_to) {
                this.showError('Please select date range');
                return false;
            }
            
            if (new Date(config.date_from) > new Date(config.date_to)) {
                this.showError('Start date must be before end date');
                return false;
            }
            
            if (config.columns.length === 0) {
                this.showError('Please select at least one column');
                return false;
            }
            
            return true;
        },

        /**
         * Render report preview
         */
        renderReportPreview: function(data) {
            var container = $('#report-preview-container');
            var html = '';
            
            // Summary section
            if (data.summary && Object.keys(data.summary).length > 0) {
                html += '<div class="report-summary">';
                $.each(data.summary, function(key, value) {
                    html += '<div class="summary-item">';
                    html += '<div class="summary-value">' + SkyLearnReporting.formatValue(key, value) + '</div>';
                    html += '<div class="summary-label">' + SkyLearnReporting.formatLabel(key) + '</div>';
                    html += '</div>';
                });
                html += '</div>';
            }
            
            // Data table
            if (data.data && data.data.length > 0) {
                html += '<div class="report-data-section">';
                html += '<h4>Report Data (' + data.total_rows + ' rows)</h4>';
                html += '<div class="table-container">';
                html += '<table class="report-table">';
                
                // Table header
                html += '<thead><tr>';
                var firstRow = data.data[0];
                $.each(firstRow, function(column, value) {
                    html += '<th>' + SkyLearnReporting.formatLabel(column) + '</th>';
                });
                html += '</tr></thead>';
                
                // Table body (show first 50 rows)
                html += '<tbody>';
                var displayRows = data.data.slice(0, 50);
                $.each(displayRows, function(index, row) {
                    html += '<tr>';
                    $.each(row, function(column, value) {
                        html += '<td>' + SkyLearnReporting.formatValue(column, value) + '</td>';
                    });
                    html += '</tr>';
                });
                html += '</tbody>';
                
                html += '</table>';
                html += '</div>';
                
                if (data.total_rows > 50) {
                    html += '<p class="table-note">Showing first 50 rows of ' + data.total_rows + ' total rows. Export to see all data.</p>';
                }
                
                html += '</div>';
            } else {
                html += '<div class="no-data-message">';
                html += '<p>No data found for the selected criteria.</p>';
                html += '</div>';
            }
            
            container.html(html);
        },

        /**
         * Format value for display
         */
        formatValue: function(column, value) {
            if (value === null || value === undefined) {
                return '-';
            }
            
            // Format based on column type
            if (column.includes('amount') || column.includes('revenue') || column.includes('spent') || column.includes('price')) {
                return '$' + parseFloat(value).toLocaleString('en-US', { minimumFractionDigits: 2 });
            }
            
            if (column.includes('rate') && typeof value === 'number') {
                return value.toFixed(2) + '%';
            }
            
            if (column.includes('date') && value !== '-') {
                var date = new Date(value);
                return date.toLocaleDateString();
            }
            
            return value.toString();
        },

        /**
         * Format label for display
         */
        formatLabel: function(key) {
            return key.replace(/_/g, ' ').replace(/\b\w/g, function(txt) {
                return txt.charAt(0).toUpperCase() + txt.substr(1).toLowerCase();
            });
        },

        /**
         * Export report
         */
        exportReport: function(e) {
            e.preventDefault();
            
            if (!this.currentData) {
                this.showError('Please generate a report first');
                return;
            }
            
            var format = $(e.currentTarget).data('format');
            var config = this.currentConfig;
            
            if (format === 'pdf') {
                this.exportPDF(config);
            } else {
                this.exportCSV(config, format);
            }
        },

        /**
         * Export as CSV/Excel
         */
        exportCSV: function(config, format) {
            var form = $('<form>').attr({
                method: 'POST',
                action: skylearn_reporting_ajax.ajax_url
            });
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'action',
                value: 'skylearn_export_report'
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'nonce',
                value: skylearn_reporting_ajax.nonce
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'config',
                value: JSON.stringify(config)
            }));
            
            form.append($('<input>').attr({
                type: 'hidden',
                name: 'format',
                value: format
            }));
            
            $('body').append(form);
            form.submit();
            form.remove();
        },

        /**
         * Export as PDF
         */
        exportPDF: function(config) {
            $.ajax({
                url: skylearn_reporting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_export_report_pdf',
                    nonce: skylearn_reporting_ajax.nonce,
                    config: config,
                    options: {
                        title: config.name,
                        orientation: 'P',
                        format: 'A4'
                    }
                },
                xhrFields: {
                    responseType: 'blob'
                },
                success: function(data, status, xhr) {
                    var blob = new Blob([data], { type: 'application/pdf' });
                    var url = window.URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = config.name.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.pdf';
                    document.body.appendChild(a);
                    a.click();
                    window.URL.revokeObjectURL(url);
                    document.body.removeChild(a);
                },
                error: function() {
                    SkyLearnReporting.showError('Failed to export PDF');
                }
            });
        },

        /**
         * Show schedule modal
         */
        showScheduleModal: function(e) {
            e.preventDefault();
            
            if (!this.currentConfig) {
                this.showError('Please configure and generate a report first');
                return;
            }
            
            $('#schedule-report-modal').show();
        },

        /**
         * Save scheduled report
         */
        saveScheduledReport: function(e) {
            e.preventDefault();
            
            var schedule = {
                frequency: $('#schedule-frequency').val(),
                time: $('#schedule-time').val(),
                format: $('#schedule-format').val(),
                enabled: $('#schedule-enabled').val() === 'true',
                recipients: $('#schedule-recipients').val().split(',').map(function(email) {
                    return email.trim();
                }).filter(function(email) {
                    return email !== '';
                })
            };
            
            if (schedule.recipients.length === 0) {
                this.showError('Please enter at least one email recipient');
                return;
            }
            
            $.ajax({
                url: skylearn_reporting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_schedule_report',
                    nonce: skylearn_reporting_ajax.nonce,
                    config: this.currentConfig,
                    schedule: schedule
                },
                success: function(response) {
                    if (response.success) {
                        SkyLearnReporting.closeModal();
                        SkyLearnReporting.showSuccess('Report scheduled successfully');
                        
                        // Refresh scheduled reports if on that tab
                        if (window.location.hash === '#scheduled') {
                            SkyLearnReporting.loadScheduledReports();
                        }
                    } else {
                        SkyLearnReporting.showError('Failed to schedule report: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    SkyLearnReporting.showError('Network error occurred');
                }
            });
        },

        /**
         * Show save template modal
         */
        showSaveTemplate: function(e) {
            e.preventDefault();
            
            if (!this.currentConfig) {
                this.showError('Please configure a report first');
                return;
            }
            
            $('#save-template-modal').show();
        },

        /**
         * Save report template
         */
        saveTemplate: function(e) {
            e.preventDefault();
            
            var templateName = $('#template-name').val();
            var templateDescription = $('#template-description').val();
            
            if (!templateName) {
                this.showError('Please enter a template name');
                return;
            }
            
            var templateData = {
                name: templateName,
                description: templateDescription,
                config: this.currentConfig,
                created_at: new Date().toISOString()
            };
            
            // Save to local storage or send to server
            var templates = JSON.parse(localStorage.getItem('skylearn_report_templates') || '{}');
            var templateId = 'template_' + Date.now();
            templates[templateId] = templateData;
            localStorage.setItem('skylearn_report_templates', JSON.stringify(templates));
            
            this.closeModal();
            this.showSuccess('Template saved successfully');
        },

        /**
         * Use template
         */
        useTemplate: function(e) {
            e.preventDefault();
            
            var templateId = $(e.currentTarget).data('template-id');
            var templates = JSON.parse(localStorage.getItem('skylearn_report_templates') || '{}');
            
            if (templates[templateId]) {
                var template = templates[templateId];
                this.loadConfigFromTemplate(template.config);
                this.showSuccess('Template loaded successfully');
            }
        },

        /**
         * Load configuration from template
         */
        loadConfigFromTemplate: function(config) {
            $('#report-name').val(config.name);
            $('#report-type').val(config.type);
            $('#date-from').val(config.date_from);
            $('#date-to').val(config.date_to);
            $('#sort-by').val(config.sort_by);
            $('#sort-order').val(config.sort_order);
            $('#group-by').val(config.group_by);
            $('#format').val(config.format);
            
            // Trigger report type change to load filters and columns
            this.updateReportType();
            
            // Set filters
            setTimeout(function() {
                $.each(config.filters, function(key, value) {
                    $('#' + key).val(value);
                });
                
                // Set columns
                $('.column-checkbox').prop('checked', false);
                $.each(config.columns, function(index, column) {
                    $('#column-' + column).prop('checked', true);
                });
            }, 100);
        },

        /**
         * Validate date range
         */
        validateDateRange: function() {
            var dateFrom = new Date($('#date-from').val());
            var dateTo = new Date($('#date-to').val());
            
            if (dateFrom > dateTo) {
                this.showError('Start date must be before end date');
                $('#date-from').focus();
            }
        },

        /**
         * Update columns selection
         */
        updateColumns: function() {
            var selectedColumns = $('.column-checkbox:checked').length;
            
            if (selectedColumns === 0) {
                this.showError('At least one column must be selected');
                $(event.target).prop('checked', true);
            }
        },

        /**
         * Load scheduled reports
         */
        loadScheduledReports: function() {
            $.ajax({
                url: skylearn_reporting_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_get_scheduled_reports',
                    nonce: skylearn_reporting_ajax.nonce
                },
                success: function(response) {
                    if (response.success) {
                        SkyLearnReporting.renderScheduledReports(response.data);
                    }
                }
            });
        },

        /**
         * Render scheduled reports table
         */
        renderScheduledReports: function(reports) {
            var container = $('.scheduled-reports-container');
            if (container.length === 0) return;
            
            var html = '<table class="scheduled-reports-table">';
            html += '<thead>';
            html += '<tr>';
            html += '<th>Report Name</th>';
            html += '<th>Type</th>';
            html += '<th>Frequency</th>';
            html += '<th>Next Send</th>';
            html += '<th>Recipients</th>';
            html += '<th>Status</th>';
            html += '<th>Actions</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';
            
            if (Object.keys(reports).length === 0) {
                html += '<tr><td colspan="7" style="text-align: center; padding: 20px;">No scheduled reports found.</td></tr>';
            } else {
                $.each(reports, function(reportId, report) {
                    html += '<tr>';
                    html += '<td>' + report.config.name + '</td>';
                    html += '<td>' + SkyLearnReporting.formatLabel(report.config.type) + '</td>';
                    html += '<td>' + SkyLearnReporting.formatLabel(report.schedule.frequency) + '</td>';
                    html += '<td>' + new Date(report.next_send).toLocaleDateString() + '</td>';
                    html += '<td>' + report.schedule.recipients.length + ' recipients</td>';
                    html += '<td><span class="status-badge ' + (report.schedule.enabled ? 'enabled' : 'disabled') + '">' + (report.schedule.enabled ? 'Enabled' : 'Disabled') + '</span></td>';
                    html += '<td class="actions">';
                    html += '<button class="action-btn edit edit-scheduled" data-report-id="' + reportId + '">Edit</button>';
                    html += '<button class="action-btn toggle toggle-scheduled" data-report-id="' + reportId + '">' + (report.schedule.enabled ? 'Disable' : 'Enable') + '</button>';
                    html += '<button class="action-btn delete delete-scheduled" data-report-id="' + reportId + '">Delete</button>';
                    html += '</td>';
                    html += '</tr>';
                });
            }
            
            html += '</tbody>';
            html += '</table>';
            
            container.html(html);
        },

        /**
         * Close modal
         */
        closeModal: function(e) {
            if (e) e.preventDefault();
            $('.skylearn-modal').hide();
            
            // Clear form fields
            $('#schedule-report-form')[0].reset();
            $('#save-template-form')[0].reset();
        },

        /**
         * Show loading state
         */
        showLoading: function() {
            $('#generate-report').prop('disabled', true).text('Generating...');
        },

        /**
         * Hide loading state
         */
        hideLoading: function() {
            $('#generate-report').prop('disabled', false).text('Generate Report');
        },

        /**
         * Show error message
         */
        showError: function(message) {
            var $error = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
            $('.skylearn-billing-header').after($error);
            
            setTimeout(function() {
                $error.fadeOut(function() {
                    $error.remove();
                });
            }, 5000);
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            var $success = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
            $('.skylearn-billing-header').after($success);
            
            setTimeout(function() {
                $success.fadeOut(function() {
                    $success.remove();
                });
            }, 5000);
        }
    };

    // Make available globally
    window.SkyLearnReporting = SkyLearnReporting;

})(jQuery);