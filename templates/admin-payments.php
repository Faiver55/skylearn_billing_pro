<?php
/**
 * Admin payment gateways template
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

// Get current tab
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'gateways';

// Get payment manager instance
$payment_manager = skylearn_billing_pro_payment_manager();
$payment_fields = skylearn_billing_pro_payment_fields();

// Process form submission
if (isset($_POST['submit']) && check_admin_referer('skylearn_payments_save', 'skylearn_payments_nonce')) {
    $options = get_option('skylearn_billing_pro_options', array());
    
    if ($active_tab === 'gateways') {
        // Save gateway settings
        if (!isset($options['payment_settings'])) {
            $options['payment_settings'] = array();
        }
        
        $options['payment_settings']['enabled_gateways'] = array_map('sanitize_key', $_POST['enabled_gateways'] ?? array());
        
        // Save gateway credentials
        $gateways = $payment_manager->get_supported_gateways();
        foreach ($gateways as $gateway_id => $gateway_data) {
            if (isset($_POST['gateways'][$gateway_id])) {
                $credentials = array();
                foreach ($gateway_data['credentials'] as $field => $label) {
                    $credentials[$field] = sanitize_text_field($_POST['gateways'][$gateway_id][$field] ?? '');
                }
                $options['payment_settings']['gateways'][$gateway_id] = $credentials;
            }
        }
        
        update_option('skylearn_billing_pro_options', $options);
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Payment gateway settings saved successfully.', 'skylearn-billing-pro') . '</p></div>';
    }
}

$options = get_option('skylearn_billing_pro_options', array());
$enabled_gateways = $options['payment_settings']['enabled_gateways'] ?? array();
?>

<div class="skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="dashicons dashicons-credit-card"></span>
                <?php _e('Payment Gateways', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-description">
                <?php _e('Configure payment processors and customize checkout experience for your courses.', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-content">
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-nav-tabs">
                <a href="<?php echo admin_url('admin.php?page=skylearn-billing-pro-payments&tab=gateways'); ?>" 
                   class="skylearn-nav-tab <?php echo $active_tab === 'gateways' ? 'skylearn-nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Gateway Settings', 'skylearn-billing-pro'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=skylearn-billing-pro-payments&tab=fields'); ?>" 
                   class="skylearn-nav-tab <?php echo $active_tab === 'fields' ? 'skylearn-nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-editor-table"></span>
                    <?php _e('Checkout Fields', 'skylearn-billing-pro'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=skylearn-billing-pro-payments&tab=templates'); ?>" 
                   class="skylearn-nav-tab <?php echo $active_tab === 'templates' ? 'skylearn-nav-tab-active' : ''; ?>">
                    <span class="dashicons dashicons-layout"></span>
                    <?php _e('Checkout Templates', 'skylearn-billing-pro'); ?>
                </a>
            </nav>
            
            <div class="skylearn-sidebar-info">
                <h3><?php _e('Quick Links', 'skylearn-billing-pro'); ?></h3>
                <ul>
                    <li><a href="<?php echo admin_url('admin.php?page=skylearn-billing-pro-lms'); ?>"><?php _e('LMS Integration', 'skylearn-billing-pro'); ?></a></li>
                    <li><a href="<?php echo admin_url('admin.php?page=skylearn-billing-pro-license'); ?>"><?php _e('License Management', 'skylearn-billing-pro'); ?></a></li>
                </ul>
            </div>
        </div>

        <div class="skylearn-billing-main">
            <?php if ($active_tab === 'gateways'): ?>
                <!-- Gateway Settings Tab -->
                <div class="skylearn-tab-content">
                    <form method="post" action="">
                        <?php wp_nonce_field('skylearn_payments_save', 'skylearn_payments_nonce'); ?>
                        
                        <div class="skylearn-section">
                            <h2><?php _e('Available Payment Gateways', 'skylearn-billing-pro'); ?></h2>
                            <p class="description">
                                <?php _e('Enable and configure payment gateways for processing course payments. Gateway availability depends on your license tier.', 'skylearn-billing-pro'); ?>
                            </p>
                            
                            <div class="skylearn-gateway-cards">
                                <?php
                                $available_gateways = $payment_manager->get_available_gateways();
                                $all_gateways = $payment_manager->get_supported_gateways();
                                
                                foreach ($all_gateways as $gateway_id => $gateway_data):
                                    $is_available = isset($available_gateways[$gateway_id]);
                                    $is_enabled = in_array($gateway_id, $enabled_gateways);
                                    $has_credentials = $payment_manager->has_gateway_credentials($gateway_id);
                                    $notices = $payment_manager->get_gateway_notices($gateway_id);
                                    $credentials = $payment_manager->get_gateway_credentials($gateway_id);
                                ?>
                                    <div class="skylearn-gateway-card <?php echo $is_available ? '' : 'skylearn-gateway-disabled'; ?>">
                                        <div class="skylearn-gateway-header">
                                            <h3>
                                                <label>
                                                    <input type="checkbox" 
                                                           name="enabled_gateways[]" 
                                                           value="<?php echo esc_attr($gateway_id); ?>"
                                                           <?php checked($is_enabled && $is_available); ?>
                                                           <?php disabled(!$is_available); ?> />
                                                    <?php echo esc_html($gateway_data['name']); ?>
                                                </label>
                                            </h3>
                                            <div class="skylearn-gateway-status">
                                                <?php if (!$is_available): ?>
                                                    <span class="skylearn-status-badge skylearn-status-disabled">
                                                        <?php _e('Upgrade Required', 'skylearn-billing-pro'); ?>
                                                    </span>
                                                <?php elseif ($is_enabled && $has_credentials): ?>
                                                    <span class="skylearn-status-badge skylearn-status-active">
                                                        <?php _e('Active', 'skylearn-billing-pro'); ?>
                                                    </span>
                                                <?php elseif ($is_enabled): ?>
                                                    <span class="skylearn-status-badge skylearn-status-warning">
                                                        <?php _e('Needs Setup', 'skylearn-billing-pro'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="skylearn-status-badge skylearn-status-inactive">
                                                        <?php _e('Inactive', 'skylearn-billing-pro'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <p class="skylearn-gateway-description">
                                            <?php echo esc_html($gateway_data['description']); ?>
                                        </p>
                                        
                                        <!-- Gateway Notices -->
                                        <?php if (!empty($notices)): ?>
                                            <div class="skylearn-gateway-notices">
                                                <?php foreach ($notices as $notice): ?>
                                                    <div class="skylearn-notice skylearn-notice-<?php echo esc_attr($notice['type']); ?>">
                                                        <?php echo esc_html($notice['message']); ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <!-- Supported Checkout Types -->
                                        <div class="skylearn-gateway-features">
                                            <h4><?php _e('Supported Checkout Types:', 'skylearn-billing-pro'); ?></h4>
                                            <ul class="skylearn-feature-list">
                                                <?php if ($gateway_data['supports_inline']): ?>
                                                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Inline Checkout', 'skylearn-billing-pro'); ?></li>
                                                <?php endif; ?>
                                                <?php if ($gateway_data['supports_overlay']): ?>
                                                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Overlay Checkout', 'skylearn-billing-pro'); ?></li>
                                                <?php endif; ?>
                                                <?php if ($gateway_data['supports_hosted']): ?>
                                                    <li><span class="dashicons dashicons-yes"></span> <?php _e('Hosted Checkout', 'skylearn-billing-pro'); ?></li>
                                                <?php endif; ?>
                                                <?php if ($gateway_data['requires_hosted_only']): ?>
                                                    <li><span class="dashicons dashicons-info"></span> <?php _e('Hosted Only', 'skylearn-billing-pro'); ?></li>
                                                <?php endif; ?>
                                            </ul>
                                        </div>
                                        
                                        <!-- Gateway Credentials -->
                                        <?php if ($is_available && $is_enabled): ?>
                                            <div class="skylearn-gateway-credentials">
                                                <h4><?php _e('API Credentials', 'skylearn-billing-pro'); ?></h4>
                                                <div class="skylearn-credentials-fields">
                                                    <?php foreach ($gateway_data['credentials'] as $field => $label): ?>
                                                        <div class="skylearn-field-group">
                                                            <label for="<?php echo esc_attr($gateway_id . '_' . $field); ?>">
                                                                <?php echo esc_html($label); ?>
                                                            </label>
                                                            <input type="<?php echo strpos($field, 'secret') !== false || strpos($field, 'key') !== false ? 'password' : 'text'; ?>"
                                                                   id="<?php echo esc_attr($gateway_id . '_' . $field); ?>"
                                                                   name="gateways[<?php echo esc_attr($gateway_id); ?>][<?php echo esc_attr($field); ?>]"
                                                                   value="<?php echo esc_attr($credentials[$field] ?? ''); ?>"
                                                                   class="regular-text" />
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <?php submit_button(__('Save Gateway Settings', 'skylearn-billing-pro')); ?>
                    </form>
                </div>
                
            <?php elseif ($active_tab === 'fields'): ?>
                <!-- Checkout Fields Tab -->
                <div class="skylearn-tab-content">
                    <div class="skylearn-section">
                        <h2><?php _e('Checkout Field Builder', 'skylearn-billing-pro'); ?></h2>
                        <p class="description">
                            <?php _e('Customize checkout fields for each payment gateway. Add custom fields to collect additional customer information.', 'skylearn-billing-pro'); ?>
                        </p>
                        
                        <div class="skylearn-fields-interface">
                            <div class="skylearn-field-types">
                                <h3><?php _e('Available Field Types', 'skylearn-billing-pro'); ?></h3>
                                <div class="skylearn-field-type-list">
                                    <?php foreach ($payment_fields->get_field_types() as $type => $type_data): ?>
                                        <div class="skylearn-field-type" data-type="<?php echo esc_attr($type); ?>">
                                            <span class="dashicons <?php echo esc_attr($type_data['icon']); ?>"></span>
                                            <span class="field-name"><?php echo esc_html($type_data['name']); ?></span>
                                            <span class="field-description"><?php echo esc_html($type_data['description']); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="skylearn-field-builder">
                                <div class="skylearn-gateway-selector">
                                    <label for="field-gateway-select"><?php _e('Select Gateway:', 'skylearn-billing-pro'); ?></label>
                                    <select id="field-gateway-select" class="regular-text">
                                        <option value=""><?php _e('Choose a gateway...', 'skylearn-billing-pro'); ?></option>
                                        <?php foreach ($available_gateways as $gateway_id => $gateway_data): ?>
                                            <option value="<?php echo esc_attr($gateway_id); ?>">
                                                <?php echo esc_html($gateway_data['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div id="skylearn-field-list" class="skylearn-field-list">
                                    <!-- Fields will be loaded here via AJAX -->
                                </div>
                                
                                <button type="button" id="add-new-field" class="button button-secondary" disabled>
                                    <?php _e('Add New Field', 'skylearn-billing-pro'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php elseif ($active_tab === 'templates'): ?>
                <!-- Checkout Templates Tab -->
                <div class="skylearn-tab-content">
                    <div class="skylearn-section">
                        <h2><?php _e('Checkout Templates', 'skylearn-billing-pro'); ?></h2>
                        <p class="description">
                            <?php _e('Preview and configure checkout templates for different payment scenarios.', 'skylearn-billing-pro'); ?>
                        </p>
                        
                        <div class="skylearn-template-previews">
                            <div class="skylearn-template-card">
                                <h3><?php _e('Inline Checkout', 'skylearn-billing-pro'); ?></h3>
                                <div class="skylearn-template-preview">
                                    <div class="skylearn-preview-placeholder">
                                        <p><?php _e('Checkout form embedded directly in your page content.', 'skylearn-billing-pro'); ?></p>
                                    </div>
                                </div>
                                <div class="skylearn-template-actions">
                                    <button type="button" class="button button-secondary">
                                        <?php _e('Preview Template', 'skylearn-billing-pro'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="skylearn-template-card">
                                <h3><?php _e('Overlay Checkout', 'skylearn-billing-pro'); ?></h3>
                                <div class="skylearn-template-preview">
                                    <div class="skylearn-preview-placeholder">
                                        <p><?php _e('Modal overlay checkout that appears over your content.', 'skylearn-billing-pro'); ?></p>
                                    </div>
                                </div>
                                <div class="skylearn-template-actions">
                                    <button type="button" class="button button-secondary">
                                        <?php _e('Preview Template', 'skylearn-billing-pro'); ?>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="skylearn-template-card">
                                <h3><?php _e('Hosted Checkout', 'skylearn-billing-pro'); ?></h3>
                                <div class="skylearn-template-preview">
                                    <div class="skylearn-preview-placeholder">
                                        <p><?php _e('Customers are redirected to payment provider\'s secure checkout page.', 'skylearn-billing-pro'); ?></p>
                                    </div>
                                </div>
                                <div class="skylearn-template-actions">
                                    <button type="button" class="button button-secondary">
                                        <?php _e('Preview Template', 'skylearn-billing-pro'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Payment gateway field builder functionality
    $('#field-gateway-select').on('change', function() {
        var gatewayId = $(this).val();
        var $addButton = $('#add-new-field');
        var $fieldList = $('#skylearn-field-list');
        
        if (gatewayId) {
            $addButton.prop('disabled', false);
            // Load existing fields for gateway via AJAX
            loadGatewayFields(gatewayId);
        } else {
            $addButton.prop('disabled', true);
            $fieldList.empty();
        }
    });
    
    function loadGatewayFields(gatewayId) {
        // This would normally load via AJAX
        $('#skylearn-field-list').html('<p>Loading fields for ' + gatewayId + '...</p>');
    }
});
</script>