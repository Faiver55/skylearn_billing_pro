<?php
/**
 * Email Settings admin template for Skylearn Billing Pro
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

$welcome_email_admin = skylearn_billing_pro_welcome_email_admin();
$email_stats = $welcome_email_admin->get_email_stats();
?>

<div class="skylearn-billing-tab-content">
    <!-- Email Statistics Card -->
    <div class="skylearn-billing-card">
        <div class="skylearn-billing-card-header">
            <h2><?php esc_html_e('Email Statistics', 'skylearn-billing-pro'); ?></h2>
            <p><?php esc_html_e('Overview of email activity and deliverability.', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="skylearn-billing-card-body">
            <div class="skylearn-billing-stats-grid">
                <div class="skylearn-billing-stat-item">
                    <div class="skylearn-billing-stat-icon">
                        <span class="dashicons dashicons-email-alt"></span>
                    </div>
                    <div class="skylearn-billing-stat-content">
                        <div class="skylearn-billing-stat-number"><?php echo esc_html($email_stats['total_emails']); ?></div>
                        <div class="skylearn-billing-stat-label"><?php esc_html_e('Total Emails Sent', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
                
                <div class="skylearn-billing-stat-item">
                    <div class="skylearn-billing-stat-icon">
                        <span class="dashicons dashicons-welcome-write-blog"></span>
                    </div>
                    <div class="skylearn-billing-stat-content">
                        <div class="skylearn-billing-stat-number"><?php echo esc_html($email_stats['welcome_emails']); ?></div>
                        <div class="skylearn-billing-stat-label"><?php esc_html_e('Welcome Emails', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
                
                <div class="skylearn-billing-stat-item">
                    <div class="skylearn-billing-stat-icon">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="skylearn-billing-stat-content">
                        <div class="skylearn-billing-stat-number"><?php echo esc_html($email_stats['successful_emails']); ?></div>
                        <div class="skylearn-billing-stat-label"><?php esc_html_e('Successful Deliveries', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
                
                <div class="skylearn-billing-stat-item">
                    <div class="skylearn-billing-stat-icon">
                        <span class="dashicons dashicons-warning"></span>
                    </div>
                    <div class="skylearn-billing-stat-content">
                        <div class="skylearn-billing-stat-number"><?php echo esc_html($email_stats['failed_emails']); ?></div>
                        <div class="skylearn-billing-stat-label"><?php esc_html_e('Failed Deliveries', 'skylearn-billing-pro'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="skylearn-billing-stats-row">
                <div class="skylearn-billing-stat-item-small">
                    <strong><?php esc_html_e('Today:', 'skylearn-billing-pro'); ?></strong>
                    <span><?php echo esc_html($email_stats['emails_today']); ?></span>
                </div>
                <div class="skylearn-billing-stat-item-small">
                    <strong><?php esc_html_e('This Week:', 'skylearn-billing-pro'); ?></strong>
                    <span><?php echo esc_html($email_stats['emails_this_week']); ?></span>
                </div>
                <div class="skylearn-billing-stat-item-small">
                    <strong><?php esc_html_e('This Month:', 'skylearn-billing-pro'); ?></strong>
                    <span><?php echo esc_html($email_stats['emails_this_month']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Welcome Email Settings Card -->
    <div class="skylearn-billing-card">
        <div class="skylearn-billing-card-header">
            <h2><?php esc_html_e('Welcome Email Settings', 'skylearn-billing-pro'); ?></h2>
            <p><?php esc_html_e('Configure the welcome email sent to new users after account creation.', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="skylearn-billing-card-body">
            <form method="post" action="options.php">
                <?php
                settings_fields('skylearn_billing_pro_email');
                do_settings_sections('skylearn_billing_pro_email');
                ?>
                
                <div class="skylearn-billing-form-actions">
                    <?php submit_button(__('Save Email Settings', 'skylearn-billing-pro'), 'primary', 'submit', false, array('class' => 'skylearn-billing-btn skylearn-billing-btn-primary')); ?>
                    <button type="button" class="skylearn-billing-btn skylearn-billing-btn-secondary" id="skylearn-reset-email-template">
                        <?php esc_html_e('Reset to Default Template', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Test Email Modal -->
<div id="skylearn-test-email-modal" class="skylearn-modal" style="display: none;">
    <div class="skylearn-modal-content">
        <div class="skylearn-modal-header">
            <h3><?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?></h3>
            <button type="button" class="skylearn-modal-close">&times;</button>
        </div>
        <div class="skylearn-modal-body">
            <label for="test-email-address">
                <?php esc_html_e('Email Address:', 'skylearn-billing-pro'); ?>
            </label>
            <input type="email" id="test-email-address" class="large-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" />
            <p class="description"><?php esc_html_e('Enter the email address where you want to send the test email.', 'skylearn-billing-pro'); ?></p>
        </div>
        <div class="skylearn-modal-footer">
            <button type="button" class="button button-primary" id="skylearn-send-test-email-btn">
                <?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?>
            </button>
            <button type="button" class="button" id="skylearn-cancel-test-email">
                <?php esc_html_e('Cancel', 'skylearn-billing-pro'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab switching functionality
    $('.skylearn-tab-button').on('click', function() {
        var tab = $(this).data('tab');
        
        // Update tab buttons
        $('.skylearn-tab-button').removeClass('active');
        $(this).addClass('active');
        
        // Update tab content
        $('.skylearn-tab-content').removeClass('active');
        $('#' + tab + '-tab').addClass('active');
        
        // If switching to preview tab, refresh preview
        if (tab === 'preview') {
            refreshEmailPreview();
        }
    });
    
    // Refresh preview functionality
    $('.skylearn-refresh-preview').on('click', function() {
        refreshEmailPreview();
    });
    
    // Test email functionality
    $('.skylearn-send-test-email').on('click', function() {
        $('#skylearn-test-email-modal').show();
    });
    
    // Modal close functionality
    $('.skylearn-modal-close, #skylearn-cancel-test-email').on('click', function() {
        $('#skylearn-test-email-modal').hide();
    });
    
    // Send test email
    $('#skylearn-send-test-email-btn').on('click', function() {
        var $btn = $(this);
        var email = $('#test-email-address').val();
        
        if (!email || !isValidEmail(email)) {
            alert('<?php esc_html_e('Please enter a valid email address.', 'skylearn-billing-pro'); ?>');
            return;
        }
        
        $btn.prop('disabled', true).text('<?php esc_html_e('Sending...', 'skylearn-billing-pro'); ?>');
        
        var template = getEmailTemplate();
        var subject = getEmailSubject();
        var format = getEmailFormat();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_send_test_email',
                nonce: '<?php echo wp_create_nonce('skylearn_email_test'); ?>',
                email: email,
                template: template,
                subject: subject,
                format: format
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    $('#skylearn-test-email-modal').hide();
                } else {
                    alert('<?php esc_html_e('Error:', 'skylearn-billing-pro'); ?> ' + response.data);
                }
            },
            error: function() {
                alert('<?php esc_html_e('Failed to send test email. Please try again.', 'skylearn-billing-pro'); ?>');
            },
            complete: function() {
                $btn.prop('disabled', false).text('<?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?>');
            }
        });
    });
    
    // Reset template functionality
    $('#skylearn-reset-email-template').on('click', function() {
        if (confirm('<?php esc_html_e('Are you sure you want to reset the email template to default? This will overwrite your current template.', 'skylearn-billing-pro'); ?>')) {
            // Reset the editor content
            var defaultTemplate = '<?php echo addslashes(str_replace(array("\n", "\r"), array("\\n", "\\r"), SkyLearn_Billing_Pro_Welcome_Email_Admin::get_default_email_template())); ?>';
            
            if (typeof tinymce !== 'undefined' && tinymce.get('welcome_email_template')) {
                tinymce.get('welcome_email_template').setContent(defaultTemplate);
            } else {
                $('#welcome_email_template').val(defaultTemplate);
            }
            
            alert('<?php esc_html_e('Email template has been reset to default.', 'skylearn-billing-pro'); ?>');
        }
    });
    
    // Helper functions
    function refreshEmailPreview() {
        var template = getEmailTemplate();
        var subject = getEmailSubject();
        var format = getEmailFormat();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_preview_welcome_email',
                nonce: '<?php echo wp_create_nonce('skylearn_email_preview'); ?>',
                template: template,
                subject: subject,
                format: format
            },
            success: function(response) {
                if (response.success) {
                    var previewHtml = '<div class="skylearn-email-preview-subject"><strong><?php esc_html_e('Subject:', 'skylearn-billing-pro'); ?></strong> ' + response.data.subject + '</div>';
                    previewHtml += '<div class="skylearn-email-preview-content">' + response.data.content + '</div>';
                    $('#skylearn-email-preview').html(previewHtml);
                } else {
                    $('#skylearn-email-preview').html('<div class="error"><?php esc_html_e('Error generating preview:', 'skylearn-billing-pro'); ?> ' + response.data + '</div>');
                }
            },
            error: function() {
                $('#skylearn-email-preview').html('<div class="error"><?php esc_html_e('Failed to generate preview. Please try again.', 'skylearn-billing-pro'); ?></div>');
            }
        });
    }
    
    function getEmailTemplate() {
        if (typeof tinymce !== 'undefined' && tinymce.get('welcome_email_template')) {
            return tinymce.get('welcome_email_template').getContent();
        } else {
            return $('#welcome_email_template').val();
        }
    }
    
    function getEmailSubject() {
        return $('input[name="skylearn_billing_pro_options[email_settings][welcome_email_subject]"]').val();
    }
    
    function getEmailFormat() {
        return $('input[name="skylearn_billing_pro_options[email_settings][welcome_email_format]"]:checked').val();
    }
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
    
    // Initial preview load
    if ($('#preview-tab').length) {
        setTimeout(refreshEmailPreview, 1000);
    }
});
</script>

<style>
.skylearn-email-editor-container {
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 10px;
}

.skylearn-email-editor-tabs {
    background: #f1f1f1;
    border-bottom: 1px solid #ccd0d4;
    padding: 0;
    margin: 0;
}

.skylearn-tab-button {
    background: none;
    border: none;
    padding: 10px 20px;
    cursor: pointer;
    font-size: 14px;
    color: #555;
    transition: background-color 0.2s;
}

.skylearn-tab-button:hover {
    background: #e1e1e1;
}

.skylearn-tab-button.active {
    background: #fff;
    color: #0073aa;
    font-weight: 600;
}

.skylearn-email-editor-content {
    position: relative;
}

.skylearn-tab-content {
    display: none;
    padding: 20px;
}

.skylearn-tab-content.active {
    display: block;
}

.skylearn-email-preview-container {
    min-height: 400px;
}

.skylearn-email-preview-header {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.skylearn-email-preview-header .button {
    margin-right: 10px;
}

.skylearn-email-preview {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 20px;
    background: #fff;
    min-height: 300px;
}

.skylearn-email-preview-subject {
    background: #f8f9fa;
    padding: 10px;
    margin-bottom: 15px;
    border-left: 4px solid #0073aa;
    font-size: 14px;
}

.skylearn-email-preview-content {
    font-family: Arial, sans-serif;
    line-height: 1.6;
}

.skylearn-email-tokens-info {
    background: #f8f9fa;
    border: 1px solid #e1e5e9;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.skylearn-tokens-list {
    margin: 10px 0 0 20px;
    list-style: disc;
}

.skylearn-tokens-list li {
    margin-bottom: 5px;
}

.skylearn-tokens-list code {
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #ddd;
    font-size: 12px;
}

.skylearn-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100000;
}

.skylearn-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #fff;
    border-radius: 4px;
    min-width: 400px;
    max-width: 600px;
}

.skylearn-modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    position: relative;
}

.skylearn-modal-header h3 {
    margin: 0;
    font-size: 18px;
}

.skylearn-modal-close {
    position: absolute;
    top: 15px;
    right: 20px;
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.skylearn-modal-body {
    padding: 20px;
}

.skylearn-modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.skylearn-modal-footer .button {
    margin-left: 10px;
}

.skylearn-billing-stats-row {
    display: flex;
    gap: 20px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.skylearn-billing-stat-item-small {
    flex: 1;
    text-align: center;
}

.skylearn-billing-stat-item-small strong {
    display: block;
    margin-bottom: 5px;
    color: #555;
    font-size: 14px;
}

.skylearn-billing-stat-item-small span {
    font-size: 18px;
    font-weight: 600;
    color: #0073aa;
}
</style>