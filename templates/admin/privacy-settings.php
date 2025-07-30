<?php
/**
 * Privacy Settings Admin Template
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

// Check user capabilities
if (!current_user_can('skylearn_manage_privacy')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'skylearn-billing-pro'));
}

// Get GDPR tools instance
$gdpr_tools = skylearn_billing_pro_gdpr_tools();

// Handle form submission
if (isset($_POST['submit_privacy_settings']) && wp_verify_nonce($_POST['_wpnonce'], 'skylearn_privacy_settings')) {
    $options = get_option('skylearn_billing_pro_options', array());
    
    // Update privacy settings
    $options['privacy_settings'] = array(
        'data_retention_days' => intval($_POST['data_retention_days']),
        'auto_delete_logs' => isset($_POST['auto_delete_logs']),
        'anonymize_user_data' => isset($_POST['anonymize_user_data']),
        'cookie_consent_required' => isset($_POST['cookie_consent_required']),
        'tracking_disabled' => isset($_POST['tracking_disabled']),
        'data_export_enabled' => isset($_POST['data_export_enabled']),
        'privacy_policy_url' => esc_url_raw($_POST['privacy_policy_url']),
        'terms_service_url' => esc_url_raw($_POST['terms_service_url']),
    );
    
    update_option('skylearn_billing_pro_options', $options);
    
    echo '<div class="notice notice-success"><p>' . __('Privacy settings saved successfully.', 'skylearn-billing-pro') . '</p></div>';
}

// Handle data export
if (isset($_POST['export_user_data']) && wp_verify_nonce($_POST['_wpnonce'], 'skylearn_gdpr_export')) {
    $email = sanitize_email($_POST['user_email']);
    if ($email) {
        $export_data = $gdpr_tools->export_user_data($email);
        $export_filename = 'skylearn_user_data_' . sanitize_file_name($email) . '_' . date('Y-m-d-H-i-s') . '.json';
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $export_filename . '"');
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    } else {
        echo '<div class="notice notice-error"><p>' . __('Please enter a valid email address.', 'skylearn-billing-pro') . '</p></div>';
    }
}

// Handle data deletion
if (isset($_POST['delete_user_data']) && wp_verify_nonce($_POST['_wpnonce'], 'skylearn_gdpr_delete')) {
    $email = sanitize_email($_POST['user_email_delete']);
    if ($email) {
        $delete_result = $gdpr_tools->erase_user_data($email);
        
        if ($delete_result['items_removed'] > 0) {
            echo '<div class="notice notice-success"><p>' . 
                 sprintf(__('Successfully removed %d items for user %s.', 'skylearn-billing-pro'), 
                         $delete_result['items_removed'], $email) . '</p></div>';
        } else {
            echo '<div class="notice notice-info"><p>' . 
                 sprintf(__('No data found to remove for user %s.', 'skylearn-billing-pro'), $email) . '</p></div>';
        }
        
        if (!empty($delete_result['messages'])) {
            foreach ($delete_result['messages'] as $message) {
                echo '<div class="notice notice-info"><p>' . esc_html($message) . '</p></div>';
            }
        }
    } else {
        echo '<div class="notice notice-error"><p>' . __('Please enter a valid email address.', 'skylearn-billing-pro') . '</p></div>';
    }
}

// Get current settings
$options = get_option('skylearn_billing_pro_options', array());
$privacy_settings = isset($options['privacy_settings']) ? $options['privacy_settings'] : array();

// Default values
$privacy_settings = wp_parse_args($privacy_settings, array(
    'data_retention_days' => 365,
    'auto_delete_logs' => false,
    'anonymize_user_data' => false,
    'cookie_consent_required' => false,
    'tracking_disabled' => false,
    'data_export_enabled' => true,
    'privacy_policy_url' => '',
    'terms_service_url' => '',
));
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Privacy Settings', 'skylearn-billing-pro'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <div class="skylearn-privacy-settings">
        
        <!-- Privacy Configuration -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Privacy Configuration', 'skylearn-billing-pro'); ?></h2>
            </div>
            <div class="inside">
                <form method="post" action="">
                    <?php wp_nonce_field('skylearn_privacy_settings'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="data_retention_days"><?php _e('Data Retention Period', 'skylearn-billing-pro'); ?></label>
                            </th>
                            <td>
                                <input type="number" id="data_retention_days" name="data_retention_days" 
                                       value="<?php echo esc_attr($privacy_settings['data_retention_days']); ?>" 
                                       min="30" max="3650" class="regular-text">
                                <p class="description">
                                    <?php _e('Number of days to retain user data (minimum 30 days).', 'skylearn-billing-pro'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Automatic Data Management', 'skylearn-billing-pro'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="auto_delete_logs" value="1" 
                                               <?php checked($privacy_settings['auto_delete_logs']); ?>>
                                        <?php _e('Automatically delete old audit logs', 'skylearn-billing-pro'); ?>
                                    </label>
                                    <br><br>
                                    <label>
                                        <input type="checkbox" name="anonymize_user_data" value="1" 
                                               <?php checked($privacy_settings['anonymize_user_data']); ?>>
                                        <?php _e('Anonymize user data after retention period', 'skylearn-billing-pro'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Cookie and Tracking', 'skylearn-billing-pro'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="cookie_consent_required" value="1" 
                                               <?php checked($privacy_settings['cookie_consent_required']); ?>>
                                        <?php _e('Require cookie consent', 'skylearn-billing-pro'); ?>
                                    </label>
                                    <br><br>
                                    <label>
                                        <input type="checkbox" name="tracking_disabled" value="1" 
                                               <?php checked($privacy_settings['tracking_disabled']); ?>>
                                        <?php _e('Disable user tracking and analytics', 'skylearn-billing-pro'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row"><?php _e('Data Export Options', 'skylearn-billing-pro'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="data_export_enabled" value="1" 
                                               <?php checked($privacy_settings['data_export_enabled']); ?>>
                                        <?php _e('Enable user data export functionality', 'skylearn-billing-pro'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="privacy_policy_url"><?php _e('Privacy Policy URL', 'skylearn-billing-pro'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="privacy_policy_url" name="privacy_policy_url" 
                                       value="<?php echo esc_attr($privacy_settings['privacy_policy_url']); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('URL to your privacy policy page.', 'skylearn-billing-pro'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="terms_service_url"><?php _e('Terms of Service URL', 'skylearn-billing-pro'); ?></label>
                            </th>
                            <td>
                                <input type="url" id="terms_service_url" name="terms_service_url" 
                                       value="<?php echo esc_attr($privacy_settings['terms_service_url']); ?>" 
                                       class="regular-text">
                                <p class="description">
                                    <?php _e('URL to your terms of service page.', 'skylearn-billing-pro'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Save Privacy Settings', 'skylearn-billing-pro'), 'primary', 'submit_privacy_settings'); ?>
                </form>
            </div>
        </div>
        
        <!-- GDPR Data Management -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('GDPR Data Management', 'skylearn-billing-pro'); ?></h2>
            </div>
            <div class="inside">
                
                <!-- Data Export Section -->
                <div class="skylearn-gdpr-section">
                    <h3><?php _e('Export User Data', 'skylearn-billing-pro'); ?></h3>
                    <p><?php _e('Export all data associated with a user email address in JSON format.', 'skylearn-billing-pro'); ?></p>
                    
                    <form method="post" action="" class="skylearn-inline-form">
                        <?php wp_nonce_field('skylearn_gdpr_export'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="user_email"><?php _e('User Email', 'skylearn-billing-pro'); ?></label>
                                </th>
                                <td>
                                    <input type="email" id="user_email" name="user_email" 
                                           class="regular-text" required 
                                           placeholder="<?php esc_attr_e('user@example.com', 'skylearn-billing-pro'); ?>">
                                    <?php submit_button(__('Export Data', 'skylearn-billing-pro'), 'secondary', 'export_user_data', false); ?>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <hr>
                
                <!-- Data Deletion Section -->
                <div class="skylearn-gdpr-section">
                    <h3><?php _e('Delete User Data', 'skylearn-billing-pro'); ?></h3>
                    <p class="description">
                        <strong><?php _e('Warning:', 'skylearn-billing-pro'); ?></strong>
                        <?php _e('This will permanently delete or anonymize user data. This action cannot be undone.', 'skylearn-billing-pro'); ?>
                    </p>
                    
                    <form method="post" action="" class="skylearn-inline-form" 
                          onsubmit="return confirm('<?php esc_attr_e('Are you sure you want to delete this user\'s data? This action cannot be undone.', 'skylearn-billing-pro'); ?>');">
                        <?php wp_nonce_field('skylearn_gdpr_delete'); ?>
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="user_email_delete"><?php _e('User Email', 'skylearn-billing-pro'); ?></label>
                                </th>
                                <td>
                                    <input type="email" id="user_email_delete" name="user_email_delete" 
                                           class="regular-text" required 
                                           placeholder="<?php esc_attr_e('user@example.com', 'skylearn-billing-pro'); ?>">
                                    <?php submit_button(__('Delete Data', 'skylearn-billing-pro'), 'delete', 'delete_user_data', false); ?>
                                </td>
                            </tr>
                        </table>
                    </form>
                </div>
                
                <hr>
                
                <!-- Privacy Tools Info -->
                <div class="skylearn-gdpr-section">
                    <h3><?php _e('WordPress Privacy Tools', 'skylearn-billing-pro'); ?></h3>
                    <p><?php _e('Skylearn Billing Pro is integrated with WordPress privacy tools.', 'skylearn-billing-pro'); ?></p>
                    <p>
                        <a href="<?php echo admin_url('tools.php?page=export_personal_data'); ?>" class="button">
                            <?php _e('WordPress Data Export Tool', 'skylearn-billing-pro'); ?>
                        </a>
                        <a href="<?php echo admin_url('tools.php?page=remove_personal_data'); ?>" class="button">
                            <?php _e('WordPress Data Erasure Tool', 'skylearn-billing-pro'); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Privacy Compliance Info -->
        <div class="postbox">
            <div class="postbox-header">
                <h2 class="hndle"><?php _e('Privacy Compliance Information', 'skylearn-billing-pro'); ?></h2>
            </div>
            <div class="inside">
                <div class="skylearn-compliance-info">
                    <h3><?php _e('What Data We Collect', 'skylearn-billing-pro'); ?></h3>
                    <ul>
                        <li><?php _e('User registration and profile information', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Payment and billing information', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Course enrollment and progress data', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Email and communication logs', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Audit logs and security events', 'skylearn-billing-pro'); ?></li>
                    </ul>
                    
                    <h3><?php _e('Data Processing Legal Basis', 'skylearn-billing-pro'); ?></h3>
                    <ul>
                        <li><?php _e('Contract performance - Processing payments and delivering courses', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Legitimate interest - Security, fraud prevention, and service improvement', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Consent - Marketing communications and optional features', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Legal obligation - Tax records and compliance requirements', 'skylearn-billing-pro'); ?></li>
                    </ul>
                    
                    <h3><?php _e('User Rights Under GDPR', 'skylearn-billing-pro'); ?></h3>
                    <ul>
                        <li><?php _e('Right to access - Users can request copies of their data', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Right to rectification - Users can request corrections to their data', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Right to erasure - Users can request deletion of their data', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Right to portability - Users can request data in a structured format', 'skylearn-billing-pro'); ?></li>
                        <li><?php _e('Right to object - Users can object to certain processing activities', 'skylearn-billing-pro'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.skylearn-privacy-settings .postbox {
    margin-bottom: 20px;
}

.skylearn-gdpr-section {
    margin-bottom: 30px;
}

.skylearn-gdpr-section h3 {
    margin-top: 0;
    margin-bottom: 10px;
}

.skylearn-inline-form .form-table td {
    display: flex;
    align-items: center;
    gap: 10px;
}

.skylearn-inline-form .regular-text {
    flex: 1;
    max-width: 300px;
}

.skylearn-compliance-info h3 {
    color: #23282d;
    margin-top: 20px;
    margin-bottom: 10px;
}

.skylearn-compliance-info ul {
    margin-left: 20px;
}

.skylearn-compliance-info li {
    margin-bottom: 5px;
}

.button.delete {
    background: #d63638;
    border-color: #d63638;
    color: #fff;
}

.button.delete:hover {
    background: #b32d2e;
    border-color: #b32d2e;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add confirmation for data deletion
    $('form').on('submit', function(e) {
        if ($(this).find('input[name="delete_user_data"]').length > 0) {
            var email = $(this).find('input[name="user_email_delete"]').val();
            if (email && !confirm('<?php _e('Are you sure you want to delete all data for', 'skylearn-billing-pro'); ?> ' + email + '? <?php _e('This action cannot be undone.', 'skylearn-billing-pro'); ?>')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Validate email fields
    $('input[type="email"]').on('blur', function() {
        var email = $(this).val();
        if (email && !isValidEmail(email)) {
            $(this).addClass('error');
            $(this).after('<span class="email-error"><?php _e('Please enter a valid email address.', 'skylearn-billing-pro'); ?></span>');
        } else {
            $(this).removeClass('error');
            $(this).siblings('.email-error').remove();
        }
    });
    
    function isValidEmail(email) {
        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }
});
</script>