<?php
/**
 * Enhanced Email Settings admin template for Skylearn Billing Pro
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

// Get email management instance
$email_manager = skylearn_billing_pro_email();
$email_builder = skylearn_billing_pro_email_builder();
$analytics = $email_manager->get_email_analytics();
?>

<div class="skylearn-billing-tab-content" id="email-management-dashboard">
    <!-- Email Analytics Overview -->
    <div class="skylearn-billing-card">
        <div class="skylearn-billing-card-header">
            <h2><?php esc_html_e('Email Analytics Overview', 'skylearn-billing-pro'); ?></h2>
            <p><?php esc_html_e('Monitor email performance and deliverability across all templates.', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="skylearn-billing-card-body">
            <div class="skylearn-email-analytics-grid">
                <div class="skylearn-analytics-card">
                    <div class="skylearn-analytics-icon">
                        <span class="dashicons dashicons-email-alt"></span>
                    </div>
                    <div class="skylearn-analytics-content">
                        <div class="skylearn-analytics-number"><?php echo esc_html($analytics['total_emails']); ?></div>
                        <div class="skylearn-analytics-label"><?php esc_html_e('Total Emails Sent', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
                
                <div class="skylearn-analytics-card">
                    <div class="skylearn-analytics-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="skylearn-analytics-content">
                        <div class="skylearn-analytics-number"><?php echo esc_html($analytics['delivery_rate']); ?>%</div>
                        <div class="skylearn-analytics-label"><?php esc_html_e('Delivery Rate', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
                
                <div class="skylearn-analytics-card">
                    <div class="skylearn-analytics-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="skylearn-analytics-content">
                        <div class="skylearn-analytics-number"><?php echo esc_html($analytics['successful_emails']); ?></div>
                        <div class="skylearn-analytics-label"><?php esc_html_e('Successful Deliveries', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
                
                <div class="skylearn-analytics-card">
                    <div class="skylearn-analytics-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="skylearn-analytics-content">
                        <div class="skylearn-analytics-number"><?php echo esc_html($analytics['failed_emails']); ?></div>
                        <div class="skylearn-analytics-label"><?php esc_html_e('Failed Deliveries', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="skylearn-analytics-filters">
                <select id="analytics-period" class="skylearn-analytics-period">
                    <option value="7days"><?php esc_html_e('Last 7 Days', 'skylearn-billing-pro'); ?></option>
                    <option value="30days" selected><?php esc_html_e('Last 30 Days', 'skylearn-billing-pro'); ?></option>
                    <option value="90days"><?php esc_html_e('Last 90 Days', 'skylearn-billing-pro'); ?></option>
                    <option value="1year"><?php esc_html_e('Last Year', 'skylearn-billing-pro'); ?></option>
                </select>
                <button type="button" class="button" id="refresh-analytics">
                    <?php esc_html_e('Refresh Analytics', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Email Templates Management -->
    <div class="skylearn-billing-card skylearn-email-templates-list">
        <div class="skylearn-billing-card-header">
            <h2><?php esc_html_e('Email Templates', 'skylearn-billing-pro'); ?></h2>
            <p><?php esc_html_e('Manage and customize email templates for different events and notifications.', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="skylearn-billing-card-body">
            <div class="skylearn-email-templates-toolbar">
                <div class="skylearn-templates-filters">
                    <select id="template-language" class="skylearn-template-language">
                        <option value="en"><?php esc_html_e('English', 'skylearn-billing-pro'); ?></option>
                        <option value="es"><?php esc_html_e('Spanish', 'skylearn-billing-pro'); ?></option>
                        <option value="fr"><?php esc_html_e('French', 'skylearn-billing-pro'); ?></option>
                        <option value="de"><?php esc_html_e('German', 'skylearn-billing-pro'); ?></option>
                    </select>
                </div>
                <div class="skylearn-templates-actions">
                    <button type="button" class="button button-secondary" id="export-templates">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Export Templates', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" class="button button-secondary" id="import-templates">
                        <span class="dashicons dashicons-upload"></span>
                        <?php esc_html_e('Import Templates', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
            
            <div class="skylearn-email-templates-grid">
                <?php foreach (SkyLearn_Billing_Pro_Email::EMAIL_TYPES as $type => $label): ?>
                    <div class="skylearn-email-template-card" data-template-type="<?php echo esc_attr($type); ?>">
                        <div class="skylearn-template-card-header">
                            <h3><?php echo esc_html($label); ?></h3>
                            <div class="skylearn-template-status">
                                <?php
                                $template = $email_manager->get_email_template($type);
                                $enabled = isset($template['enabled']) ? $template['enabled'] : true;
                                ?>
                                <label class="skylearn-toggle-switch">
                                    <input type="checkbox" class="skylearn-template-toggle" data-template="<?php echo esc_attr($type); ?>" <?php checked($enabled); ?> />
                                    <span class="skylearn-toggle-slider"></span>
                                </label>
                            </div>
                        </div>
                        
                        <div class="skylearn-template-card-body">
                            <div class="skylearn-template-preview">
                                <?php
                                $subject = isset($template['subject']) ? $template['subject'] : sprintf(__('%s Template', 'skylearn-billing-pro'), $label);
                                $content_preview = isset($template['content']) ? wp_trim_words(strip_tags($template['content']), 15) : __('Default template content...', 'skylearn-billing-pro');
                                ?>
                                <div class="skylearn-template-subject">
                                    <strong><?php esc_html_e('Subject:', 'skylearn-billing-pro'); ?></strong> 
                                    <?php echo esc_html($subject); ?>
                                </div>
                                <div class="skylearn-template-content">
                                    <?php echo esc_html($content_preview); ?>
                                </div>
                            </div>
                            
                            <div class="skylearn-template-actions">
                                <button type="button" class="button button-primary skylearn-edit-template" data-template="<?php echo esc_attr($type); ?>">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php esc_html_e('Edit Template', 'skylearn-billing-pro'); ?>
                                </button>
                                <button type="button" class="button button-secondary skylearn-preview-template" data-template="<?php echo esc_attr($type); ?>">
                                    <span class="dashicons dashicons-visibility"></span>
                                    <?php esc_html_e('Preview', 'skylearn-billing-pro'); ?>
                                </button>
                                <button type="button" class="button button-secondary skylearn-test-template" data-template="<?php echo esc_attr($type); ?>">
                                    <span class="dashicons dashicons-email-alt"></span>
                                    <?php esc_html_e('Test Send', 'skylearn-billing-pro'); ?>
                                </button>
                            </div>
                        </div>
                        
                        <div class="skylearn-template-card-footer">
                            <div class="skylearn-template-stats">
                                <?php
                                $type_stats = isset($analytics['emails_by_type'][$type]) ? $analytics['emails_by_type'][$type] : 0;
                                ?>
                                <span class="skylearn-stat-item">
                                    <span class="dashicons dashicons-email"></span>
                                    <?php printf(esc_html__('%d sent', 'skylearn-billing-pro'), $type_stats); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- SMTP Settings -->
    <div class="skylearn-billing-card">
        <div class="skylearn-billing-card-header">
            <h2><?php esc_html_e('SMTP & Email Provider Settings', 'skylearn-billing-pro'); ?></h2>
            <p><?php esc_html_e('Configure SMTP settings for reliable email delivery and integration with third-party providers.', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="skylearn-billing-card-body">
            <form method="post" action="options.php" id="smtp-settings-form">
                <?php
                settings_fields('skylearn_billing_pro_email');
                do_settings_sections('skylearn_billing_pro_smtp');
                ?>
                
                <div class="skylearn-smtp-test-section">
                    <h4><?php esc_html_e('Test Email Configuration', 'skylearn-billing-pro'); ?></h4>
                    <p class="description"><?php esc_html_e('Send a test email to verify your SMTP configuration is working correctly.', 'skylearn-billing-pro'); ?></p>
                    
                    <div class="skylearn-test-email-form">
                        <input type="email" id="smtp-test-email" placeholder="<?php esc_attr_e('Enter test email address...', 'skylearn-billing-pro'); ?>" value="<?php echo esc_attr(get_option('admin_email')); ?>" />
                        <button type="button" class="button" id="send-smtp-test">
                            <?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="skylearn-billing-form-actions">
                    <?php submit_button(__('Save SMTP Settings', 'skylearn-billing-pro'), 'primary', 'submit', false, array('class' => 'skylearn-billing-btn skylearn-billing-btn-primary')); ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Email Logs -->
    <div class="skylearn-billing-card">
        <div class="skylearn-billing-card-header">
            <h2><?php esc_html_e('Email Activity Log', 'skylearn-billing-pro'); ?></h2>
            <p><?php esc_html_e('Monitor recent email activity and delivery status.', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="skylearn-billing-card-body">
            <div class="skylearn-email-logs-filters">
                <select id="log-type-filter">
                    <option value=""><?php esc_html_e('All Email Types', 'skylearn-billing-pro'); ?></option>
                    <?php foreach (SkyLearn_Billing_Pro_Email::EMAIL_TYPES as $type => $label): ?>
                        <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <select id="log-status-filter">
                    <option value=""><?php esc_html_e('All Statuses', 'skylearn-billing-pro'); ?></option>
                    <option value="sent"><?php esc_html_e('Sent', 'skylearn-billing-pro'); ?></option>
                    <option value="failed"><?php esc_html_e('Failed', 'skylearn-billing-pro'); ?></option>
                </select>
                
                <button type="button" class="button" id="refresh-logs">
                    <?php esc_html_e('Refresh Logs', 'skylearn-billing-pro'); ?>
                </button>
                
                <button type="button" class="button" id="export-logs">
                    <?php esc_html_e('Export Logs', 'skylearn-billing-pro'); ?>
                </button>
            </div>
            
            <div class="skylearn-email-logs-table-container">
                <table class="skylearn-email-logs-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date/Time', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Type', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Recipient', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Subject', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Actions', 'skylearn-billing-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="email-logs-tbody">
                        <!-- Logs will be loaded via AJAX -->
                    </tbody>
                </table>
                
                <div class="skylearn-logs-pagination">
                    <!-- Pagination will be added via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Email Builder Container (Initially Hidden) -->
<div id="email-builder-container" style="display: none;">
    <!-- Email builder will be loaded here -->
</div>

<!-- Template Import Modal -->
<div id="skylearn-import-templates-modal" class="skylearn-modal" style="display: none;">
    <div class="skylearn-modal-content">
        <div class="skylearn-modal-header">
            <h3><?php esc_html_e('Import Email Templates', 'skylearn-billing-pro'); ?></h3>
            <button type="button" class="skylearn-modal-close">&times;</button>
        </div>
        <div class="skylearn-modal-body">
            <div class="skylearn-import-options">
                <label for="template-import-file">
                    <?php esc_html_e('Select Template File:', 'skylearn-billing-pro'); ?>
                </label>
                <input type="file" id="template-import-file" accept=".json" />
                <p class="description"><?php esc_html_e('Upload a JSON file containing email templates exported from another Skylearn Billing Pro installation.', 'skylearn-billing-pro'); ?></p>
                
                <div class="skylearn-import-options">
                    <label>
                        <input type="checkbox" id="overwrite-existing" checked />
                        <?php esc_html_e('Overwrite existing templates', 'skylearn-billing-pro'); ?>
                    </label>
                </div>
            </div>
        </div>
        <div class="skylearn-modal-footer">
            <button type="button" class="button button-primary" id="import-templates-btn">
                <?php esc_html_e('Import Templates', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button" id="cancel-import">
                <?php esc_html_e('Cancel', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Email Management JavaScript
    
    // Template toggle functionality
    $('.skylearn-template-toggle').on('change', function() {
        const template = $(this).data('template');
        const enabled = $(this).is(':checked');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_toggle_email_template',
                nonce: '<?php echo wp_create_nonce('skylearn_email_toggle'); ?>',
                template: template,
                enabled: enabled
            },
            success: function(response) {
                if (!response.success) {
                    alert('Failed to update template status');
                    // Revert toggle
                    $(this).prop('checked', !enabled);
                }
            }.bind(this)
        });
    });
    
    // Edit template functionality
    $('.skylearn-edit-template').on('click', function() {
        const template = $(this).data('template');
        const language = $('#template-language').val();
        openEmailBuilder(template, language);
    });
    
    // Preview template functionality
    $('.skylearn-preview-template').on('click', function() {
        const template = $(this).data('template');
        const language = $('#template-language').val();
        previewEmailTemplate(template, language);
    });
    
    // Test template functionality
    $('.skylearn-test-template').on('click', function() {
        const template = $(this).data('template');
        showTestEmailModal(template);
    });
    
    // Analytics refresh
    $('#refresh-analytics').on('click', function() {
        const period = $('#analytics-period').val();
        refreshAnalytics(period);
    });
    
    // SMTP test email
    $('#send-smtp-test').on('click', function() {
        const email = $('#smtp-test-email').val();
        if (!email || !isValidEmail(email)) {
            alert('Please enter a valid email address.');
            return;
        }
        
        sendSMTPTestEmail(email);
    });
    
    // Template import/export
    $('#export-templates').on('click', exportTemplates);
    $('#import-templates').on('click', function() {
        $('#skylearn-import-templates-modal').show();
    });
    $('#import-templates-btn').on('click', importTemplates);
    
    // Modal close handlers
    $('.skylearn-modal-close, #cancel-import').on('click', function() {
        $(this).closest('.skylearn-modal').hide();
    });
    
    // Email logs
    $('#refresh-logs').on('click', loadEmailLogs);
    $('#export-logs').on('click', exportEmailLogs);
    $('#log-type-filter, #log-status-filter').on('change', loadEmailLogs);
    
    // Load initial data
    loadEmailLogs();
    
    // Functions
    function openEmailBuilder(template, language) {
        $('#email-management-dashboard').hide();
        $('#email-builder-container').show().html(`
            <div id="skylearn-email-builder" data-email-type="${template}" data-language="${language}">
                Loading email builder...
            </div>
        `);
        
        // Load email builder content via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_load_email_builder',
                nonce: '<?php echo wp_create_nonce('skylearn_email_builder'); ?>',
                template: template,
                language: language
            },
            success: function(response) {
                if (response.success) {
                    $('#email-builder-container').html(response.data);
                    // Initialize builder
                    if (typeof window.skyLearnEmailBuilder !== 'undefined') {
                        window.skyLearnEmailBuilder.init();
                    }
                }
            }
        });
    }
    
    function previewEmailTemplate(template, language) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_preview_email',
                nonce: '<?php echo wp_create_nonce('skylearn_email_preview'); ?>',
                type: template,
                language: language
            },
            success: function(response) {
                if (response.success) {
                    // Show preview modal
                    showPreviewModal(response.data.subject, response.data.content);
                }
            }
        });
    }
    
    function showTestEmailModal(template) {
        // Create and show test email modal for specific template
        const modal = $(`
            <div class="skylearn-modal" id="test-email-modal-${template}">
                <div class="skylearn-modal-content">
                    <div class="skylearn-modal-header">
                        <h3>Send Test Email - ${template.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</h3>
                        <button type="button" class="skylearn-modal-close">&times;</button>
                    </div>
                    <div class="skylearn-modal-body">
                        <label for="test-email-address-${template}">Email Address:</label>
                        <input type="email" id="test-email-address-${template}" class="large-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" />
                    </div>
                    <div class="skylearn-modal-footer">
                        <button type="button" class="button button-primary" onclick="sendTestEmail('${template}')">Send Test Email</button>
                        <button type="button" class="button skylearn-modal-close">Cancel</button>
                    </div>
                </div>
            </div>
        `);
        
        $('body').append(modal);
        modal.show();
        
        // Bind close event
        modal.find('.skylearn-modal-close').on('click', function() {
            modal.remove();
        });
    }
    
    function sendTestEmail(template) {
        const email = $(`#test-email-address-${template}`).val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_send_test_email',
                nonce: '<?php echo wp_create_nonce('skylearn_email_test'); ?>',
                email: email,
                type: template
            },
            success: function(response) {
                if (response.success) {
                    alert('Test email sent successfully!');
                    $(`#test-email-modal-${template}`).remove();
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    }
    
    function refreshAnalytics(period) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_get_email_analytics',
                nonce: '<?php echo wp_create_nonce('skylearn_email_analytics'); ?>',
                period: period
            },
            success: function(response) {
                if (response.success) {
                    updateAnalyticsDisplay(response.data);
                }
            }
        });
    }
    
    function updateAnalyticsDisplay(analytics) {
        $('.skylearn-analytics-card').each(function(index) {
            const $number = $(this).find('.skylearn-analytics-number');
            switch(index) {
                case 0:
                    $number.text(analytics.total_emails);
                    break;
                case 1:
                    $number.text(analytics.delivery_rate + '%');
                    break;
                case 2:
                    $number.text(analytics.successful_emails);
                    break;
                case 3:
                    $number.text(analytics.failed_emails);
                    break;
            }
        });
    }
    
    function sendSMTPTestEmail(email) {
        const $btn = $('#send-smtp-test');
        $btn.prop('disabled', true).text('Sending...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_test_smtp',
                nonce: '<?php echo wp_create_nonce('skylearn_smtp_test'); ?>',
                email: email
            },
            success: function(response) {
                if (response.success) {
                    alert('SMTP test email sent successfully!');
                } else {
                    alert('SMTP test failed: ' + response.data);
                }
            },
            complete: function() {
                $btn.prop('disabled', false).text('Send Test Email');
            }
        });
    }
    
    function loadEmailLogs() {
        const typeFilter = $('#log-type-filter').val();
        const statusFilter = $('#log-status-filter').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_get_email_logs',
                nonce: '<?php echo wp_create_nonce('skylearn_email_logs'); ?>',
                type_filter: typeFilter,
                status_filter: statusFilter,
                page: 1,
                per_page: 20
            },
            success: function(response) {
                if (response.success) {
                    displayEmailLogs(response.data.logs);
                    updateLogsPagination(response.data.pagination);
                }
            }
        });
    }
    
    function displayEmailLogs(logs) {
        const tbody = $('#email-logs-tbody');
        tbody.empty();
        
        if (logs.length === 0) {
            tbody.append('<tr><td colspan="6" style="text-align: center; padding: 20px;">No email logs found.</td></tr>');
            return;
        }
        
        logs.forEach(function(log) {
            const statusClass = log.status === 'sent' ? 'success' : 'error';
            const statusIcon = log.status === 'sent' ? 'yes-alt' : 'warning';
            
            const row = $(`
                <tr>
                    <td>${formatDate(log.timestamp)}</td>
                    <td><span class="skylearn-email-type-badge">${log.type.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}</span></td>
                    <td>${log.recipient}</td>
                    <td title="${log.subject}">${truncateText(log.subject, 40)}</td>
                    <td><span class="skylearn-status-badge skylearn-status-${statusClass}"><span class="dashicons dashicons-${statusIcon}"></span> ${log.status.charAt(0).toUpperCase() + log.status.slice(1)}</span></td>
                    <td>
                        <button type="button" class="button button-small" onclick="viewLogDetails('${log.id}')">View Details</button>
                    </td>
                </tr>
            `);
            
            tbody.append(row);
        });
    }
    
    function exportTemplates() {
        window.location.href = ajaxurl + '?action=skylearn_export_templates&nonce=' + '<?php echo wp_create_nonce('skylearn_export_templates'); ?>';
    }
    
    function importTemplates() {
        const fileInput = $('#template-import-file')[0];
        const overwrite = $('#overwrite-existing').is(':checked');
        
        if (!fileInput.files.length) {
            alert('Please select a file to import.');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'skylearn_import_templates');
        formData.append('nonce', '<?php echo wp_create_nonce('skylearn_import_templates'); ?>');
        formData.append('template_file', fileInput.files[0]);
        formData.append('overwrite', overwrite);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    alert('Templates imported successfully!');
                    location.reload();
                } else {
                    alert('Import failed: ' + response.data);
                }
            }
        });
    }
    
    // Utility functions
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    function truncateText(text, length) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }
    
    // Make functions available globally
    window.sendTestEmail = sendTestEmail;
});
</script>

<style>
/* Enhanced Email Management Styles */
.skylearn-email-analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.skylearn-analytics-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.skylearn-analytics-icon {
    font-size: 24px;
    opacity: 0.8;
}

.skylearn-analytics-content {
    flex: 1;
}

.skylearn-analytics-number {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 4px;
}

.skylearn-analytics-label {
    font-size: 12px;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.skylearn-analytics-filters {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.skylearn-email-templates-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 1px solid #eee;
}

.skylearn-templates-actions {
    display: flex;
    gap: 12px;
}

.skylearn-email-templates-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.skylearn-email-template-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.2s ease;
}

.skylearn-email-template-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.skylearn-template-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    background: #f8f9fa;
    border-bottom: 1px solid #eee;
}

.skylearn-template-card-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
}

.skylearn-toggle-switch {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.skylearn-toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.skylearn-toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
    border-radius: 24px;
}

.skylearn-toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 4px;
    bottom: 4px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}

input:checked + .skylearn-toggle-slider {
    background-color: #2271b1;
}

input:checked + .skylearn-toggle-slider:before {
    transform: translateX(20px);
}

.skylearn-template-card-body {
    padding: 20px;
}

.skylearn-template-preview {
    margin-bottom: 16px;
}

.skylearn-template-subject {
    font-size: 13px;
    margin-bottom: 8px;
    color: #1d2327;
}

.skylearn-template-content {
    font-size: 12px;
    color: #646970;
    line-height: 1.4;
}

.skylearn-template-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.skylearn-template-actions .button {
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
    line-height: 1.4;
}

.skylearn-template-card-footer {
    padding: 12px 20px;
    background: #f8f9fa;
    border-top: 1px solid #eee;
}

.skylearn-template-stats {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
    color: #646970;
}

.skylearn-stat-item {
    display: flex;
    align-items: center;
    gap: 4px;
}

.skylearn-email-logs-filters {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.skylearn-email-logs-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.skylearn-email-logs-table th,
.skylearn-email-logs-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.skylearn-email-logs-table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #646970;
}

.skylearn-email-type-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #e7f3ff;
    color: #2271b1;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.skylearn-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 11px;
    font-weight: 500;
}

.skylearn-status-success {
    background: #d4edda;
    color: #155724;
}

.skylearn-status-error {
    background: #f8d7da;
    color: #721c24;
}

.skylearn-smtp-test-section {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.skylearn-test-email-form {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 12px;
}

.skylearn-test-email-form input[type="email"] {
    flex: 1;
    max-width: 300px;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .skylearn-email-analytics-grid {
        grid-template-columns: 1fr;
    }
    
    .skylearn-email-templates-grid {
        grid-template-columns: 1fr;
    }
    
    .skylearn-email-templates-toolbar {
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .skylearn-templates-actions {
        justify-content: center;
    }
    
    .skylearn-email-logs-filters {
        flex-direction: column;
        align-items: stretch;
    }
    
    .skylearn-test-email-form {
        flex-direction: column;
        align-items: stretch;
    }
}
</style>