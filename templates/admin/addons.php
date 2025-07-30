<?php
/**
 * Addons Management Template for Skylearn Billing Pro
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

$addon_manager = skylearn_billing_pro_addon_manager();
$license_manager = skylearn_billing_pro_license_manager();
$available_addons = $addon_manager->get_available_addons();

// Group addons by type
$free_addons = array_filter($available_addons, function($addon) {
    return $addon['type'] === 'free';
});

$paid_addons = array_filter($available_addons, function($addon) {
    return $addon['type'] === 'paid';
});

// Further group paid addons by tier
$pro_addons = array_filter($paid_addons, function($addon) {
    return $addon['required_tier'] === SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO;
});

$pro_plus_addons = array_filter($paid_addons, function($addon) {
    return $addon['required_tier'] === SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS;
});
?>

<div class="skylearn-billing-addons-manager">
    <div class="skylearn-billing-addons-header">
        <h2><?php esc_html_e('Addons & Extensions', 'skylearn-billing-pro'); ?></h2>
        <p><?php esc_html_e('Extend Skylearn Billing Pro with powerful addons and integrations.', 'skylearn-billing-pro'); ?></p>
    </div>

    <!-- Free Addons Section -->
    <div class="skylearn-billing-addons-section">
        <div class="skylearn-billing-section-header">
            <h3><?php esc_html_e('Free Addons', 'skylearn-billing-pro'); ?></h3>
            <p><?php esc_html_e('These addons are included with your Skylearn Billing Pro installation.', 'skylearn-billing-pro'); ?></p>
        </div>
        
        <div class="skylearn-billing-addons-grid">
            <?php foreach ($free_addons as $addon): ?>
                <div class="skylearn-billing-addon-card" data-addon-id="<?php echo esc_attr($addon['id']); ?>">
                    <div class="skylearn-billing-addon-header">
                        <div class="skylearn-billing-addon-title">
                            <h4><?php echo esc_html($addon['name']); ?></h4>
                            <?php echo $license_manager->get_addon_tier_badge($addon['type'], $addon['required_tier']); ?>
                        </div>
                        <div class="skylearn-billing-addon-version">
                            <?php printf(__('v%s', 'skylearn-billing-pro'), esc_html($addon['version'])); ?>
                        </div>
                    </div>
                    
                    <div class="skylearn-billing-addon-content">
                        <p class="skylearn-billing-addon-description"><?php echo esc_html($addon['description']); ?></p>
                        
                        <div class="skylearn-billing-addon-meta">
                            <span class="skylearn-billing-addon-author"><?php printf(__('by %s', 'skylearn-billing-pro'), esc_html($addon['author'])); ?></span>
                            <span class="skylearn-billing-addon-category"><?php echo esc_html(ucfirst($addon['category'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="skylearn-billing-addon-footer">
                        <div class="skylearn-billing-addon-status">
                            <?php
                            $status_class = 'skylearn-billing-status-' . $addon['status'];
                            $status_text = ucfirst($addon['status']);
                            
                            if ($addon['status'] === 'active') {
                                $status_text = __('Active', 'skylearn-billing-pro');
                            } elseif ($addon['status'] === 'installed') {
                                $status_text = __('Installed', 'skylearn-billing-pro');
                            } elseif ($addon['status'] === 'available') {
                                $status_text = __('Available', 'skylearn-billing-pro');
                            }
                            ?>
                            <span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                        </div>
                        
                        <div class="skylearn-billing-addon-actions">
                            <?php if ($addon['status'] === 'active'): ?>
                                <button type="button" class="skylearn-billing-btn skylearn-billing-btn-secondary skylearn-billing-addon-action" data-action="deactivate">
                                    <?php esc_html_e('Deactivate', 'skylearn-billing-pro'); ?>
                                </button>
                            <?php elseif ($addon['status'] === 'installed'): ?>
                                <button type="button" class="skylearn-billing-btn skylearn-billing-btn-primary skylearn-billing-addon-action" data-action="activate">
                                    <?php esc_html_e('Activate', 'skylearn-billing-pro'); ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="skylearn-billing-btn skylearn-billing-btn-primary skylearn-billing-addon-action" data-action="install">
                                    <?php esc_html_e('Install', 'skylearn-billing-pro'); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Pro Addons Section -->
    <?php if (!empty($pro_addons)): ?>
        <div class="skylearn-billing-addons-section">
            <div class="skylearn-billing-section-header">
                <h3><?php esc_html_e('Pro Addons', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('These addons require a Pro license or higher.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <?php if (!$license_manager->is_feature_eligible('premium_addons')): ?>
                <div class="skylearn-billing-upgrade-notice">
                    <div class="skylearn-billing-upgrade-icon">
                        <span class="dashicons dashicons-lock"></span>
                    </div>
                    <div class="skylearn-billing-upgrade-content">
                        <h4><?php esc_html_e('Pro License Required', 'skylearn-billing-pro'); ?></h4>
                        <p><?php esc_html_e('Upgrade to Pro to unlock these powerful addons and integrations.', 'skylearn-billing-pro'); ?></p>
                        <a href="<?php echo esc_url($license_manager->get_upgrade_url(SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO)); ?>" class="skylearn-billing-btn skylearn-billing-btn-upgrade" target="_blank">
                            <?php esc_html_e('Upgrade to Pro', 'skylearn-billing-pro'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="skylearn-billing-addons-grid <?php echo !$license_manager->is_feature_eligible('premium_addons') ? 'skylearn-billing-addons-locked' : ''; ?>">
                <?php foreach ($pro_addons as $addon): ?>
                    <div class="skylearn-billing-addon-card" data-addon-id="<?php echo esc_attr($addon['id']); ?>">
                        <div class="skylearn-billing-addon-header">
                            <div class="skylearn-billing-addon-title">
                                <h4><?php echo esc_html($addon['name']); ?></h4>
                                <?php echo $license_manager->get_addon_tier_badge($addon['type'], $addon['required_tier']); ?>
                            </div>
                            <div class="skylearn-billing-addon-version">
                                <?php printf(__('v%s', 'skylearn-billing-pro'), esc_html($addon['version'])); ?>
                            </div>
                        </div>
                        
                        <div class="skylearn-billing-addon-content">
                            <p class="skylearn-billing-addon-description"><?php echo esc_html($addon['description']); ?></p>
                            
                            <div class="skylearn-billing-addon-meta">
                                <span class="skylearn-billing-addon-author"><?php printf(__('by %s', 'skylearn-billing-pro'), esc_html($addon['author'])); ?></span>
                                <span class="skylearn-billing-addon-category"><?php echo esc_html(ucfirst($addon['category'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="skylearn-billing-addon-footer">
                            <div class="skylearn-billing-addon-status">
                                <?php if ($license_manager->is_addon_accessible($addon['id'])): ?>
                                    <?php
                                    $status_class = 'skylearn-billing-status-' . $addon['status'];
                                    $status_text = ucfirst($addon['status']);
                                    
                                    if ($addon['status'] === 'active') {
                                        $status_text = __('Active', 'skylearn-billing-pro');
                                    } elseif ($addon['status'] === 'installed') {
                                        $status_text = __('Installed', 'skylearn-billing-pro');
                                    } elseif ($addon['status'] === 'available') {
                                        $status_text = __('Available', 'skylearn-billing-pro');
                                    }
                                    ?>
                                    <span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                                <?php else: ?>
                                    <span class="skylearn-billing-status-locked"><?php esc_html_e('Locked', 'skylearn-billing-pro'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="skylearn-billing-addon-actions">
                                <?php if ($license_manager->is_addon_accessible($addon['id'])): ?>
                                    <?php if ($addon['status'] === 'active'): ?>
                                        <button type="button" class="skylearn-billing-btn skylearn-billing-btn-secondary skylearn-billing-addon-action" data-action="deactivate">
                                            <?php esc_html_e('Deactivate', 'skylearn-billing-pro'); ?>
                                        </button>
                                    <?php elseif ($addon['status'] === 'installed'): ?>
                                        <button type="button" class="skylearn-billing-btn skylearn-billing-btn-primary skylearn-billing-addon-action" data-action="activate">
                                            <?php esc_html_e('Activate', 'skylearn-billing-pro'); ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="skylearn-billing-btn skylearn-billing-btn-primary skylearn-billing-addon-action" data-action="install">
                                            <?php esc_html_e('Install', 'skylearn-billing-pro'); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($license_manager->get_upgrade_url($addon['required_tier'])); ?>" class="skylearn-billing-btn skylearn-billing-btn-upgrade" target="_blank">
                                        <?php esc_html_e('Upgrade Required', 'skylearn-billing-pro'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Pro Plus Addons Section -->
    <?php if (!empty($pro_plus_addons)): ?>
        <div class="skylearn-billing-addons-section">
            <div class="skylearn-billing-section-header">
                <h3><?php esc_html_e('Pro Plus Addons', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('These addons require a Pro Plus license for maximum functionality.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <?php if (!$license_manager->is_feature_eligible('white_label')): ?>
                <div class="skylearn-billing-upgrade-notice">
                    <div class="skylearn-billing-upgrade-icon">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="skylearn-billing-upgrade-content">
                        <h4><?php esc_html_e('Pro Plus License Required', 'skylearn-billing-pro'); ?></h4>
                        <p><?php esc_html_e('Upgrade to Pro Plus for the most advanced features and premium support.', 'skylearn-billing-pro'); ?></p>
                        <a href="<?php echo esc_url($license_manager->get_upgrade_url(SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS)); ?>" class="skylearn-billing-btn skylearn-billing-btn-upgrade" target="_blank">
                            <?php esc_html_e('Upgrade to Pro Plus', 'skylearn-billing-pro'); ?>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="skylearn-billing-addons-grid <?php echo !$license_manager->is_feature_eligible('white_label') ? 'skylearn-billing-addons-locked' : ''; ?>">
                <?php foreach ($pro_plus_addons as $addon): ?>
                    <div class="skylearn-billing-addon-card">
                        <div class="skylearn-billing-addon-header">
                            <div class="skylearn-billing-addon-title">
                                <h4><?php echo esc_html($addon['name']); ?></h4>
                                <?php echo $license_manager->get_addon_tier_badge($addon['type'], $addon['required_tier']); ?>
                            </div>
                            <div class="skylearn-billing-addon-version">
                                <?php printf(__('v%s', 'skylearn-billing-pro'), esc_html($addon['version'])); ?>
                            </div>
                        </div>
                        
                        <div class="skylearn-billing-addon-content">
                            <p class="skylearn-billing-addon-description"><?php echo esc_html($addon['description']); ?></p>
                            
                            <div class="skylearn-billing-addon-meta">
                                <span class="skylearn-billing-addon-author"><?php printf(__('by %s', 'skylearn-billing-pro'), esc_html($addon['author'])); ?></span>
                                <span class="skylearn-billing-addon-category"><?php echo esc_html(ucfirst($addon['category'])); ?></span>
                            </div>
                        </div>
                        
                        <div class="skylearn-billing-addon-footer">
                            <div class="skylearn-billing-addon-status">
                                <?php if ($license_manager->is_addon_accessible($addon['id'])): ?>
                                    <?php
                                    $status_class = 'skylearn-billing-status-' . $addon['status'];
                                    $status_text = ucfirst($addon['status']);
                                    
                                    if ($addon['status'] === 'active') {
                                        $status_text = __('Active', 'skylearn-billing-pro');
                                    } elseif ($addon['status'] === 'installed') {
                                        $status_text = __('Installed', 'skylearn-billing-pro');
                                    } elseif ($addon['status'] === 'available') {
                                        $status_text = __('Available', 'skylearn-billing-pro');
                                    }
                                    ?>
                                    <span class="<?php echo esc_attr($status_class); ?>"><?php echo esc_html($status_text); ?></span>
                                <?php else: ?>
                                    <span class="skylearn-billing-status-locked"><?php esc_html_e('Locked', 'skylearn-billing-pro'); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="skylearn-billing-addon-actions">
                                <?php if ($license_manager->is_addon_accessible($addon['id'])): ?>
                                    <?php if ($addon['status'] === 'active'): ?>
                                        <button type="button" class="skylearn-billing-btn skylearn-billing-btn-secondary skylearn-billing-addon-action" data-action="deactivate">
                                            <?php esc_html_e('Deactivate', 'skylearn-billing-pro'); ?>
                                        </button>
                                    <?php elseif ($addon['status'] === 'installed'): ?>
                                        <button type="button" class="skylearn-billing-btn skylearn-billing-btn-primary skylearn-billing-addon-action" data-action="activate">
                                            <?php esc_html_e('Activate', 'skylearn-billing-pro'); ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="skylearn-billing-btn skylearn-billing-btn-primary skylearn-billing-addon-action" data-action="install">
                                            <?php esc_html_e('Install', 'skylearn-billing-pro'); ?>
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($license_manager->get_upgrade_url($addon['required_tier'])); ?>" class="skylearn-billing-btn skylearn-billing-btn-upgrade" target="_blank">
                                        <?php esc_html_e('Upgrade Required', 'skylearn-billing-pro'); ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Handle addon actions
    $('.skylearn-billing-addon-action').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var $card = $button.closest('.skylearn-billing-addon-card');
        var addonId = $card.data('addon-id');
        var action = $button.data('action');
        
        if (!addonId || !action) {
            return;
        }
        
        $button.prop('disabled', true).text('<?php esc_html_e('Processing...', 'skylearn-billing-pro'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'skylearn_billing_manage_addon',
                addon_action: action,
                addon_id: addonId,
                nonce: '<?php echo wp_create_nonce('skylearn_billing_addon_action'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Update UI based on new status
                    location.reload(); // Simple reload for now
                } else {
                    alert(response.data || '<?php esc_html_e('Error processing addon action.', 'skylearn-billing-pro'); ?>');
                }
            },
            error: function() {
                alert('<?php esc_html_e('Error processing addon action.', 'skylearn-billing-pro'); ?>');
            },
            complete: function() {
                $button.prop('disabled', false);
            }
        });
    });
});
</script>

<style>
.skylearn-billing-addons-manager {
    max-width: 1200px;
}

.skylearn-billing-addons-header {
    margin-bottom: 30px;
}

.skylearn-billing-addons-section {
    margin-bottom: 40px;
}

.skylearn-billing-section-header {
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #e1e1e1;
}

.skylearn-billing-section-header h3 {
    margin: 0 0 5px 0;
    color: #23282d;
}

.skylearn-billing-section-header p {
    margin: 0;
    color: #666;
}

.skylearn-billing-addons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 20px;
}

.skylearn-billing-addons-locked {
    opacity: 0.6;
    pointer-events: none;
}

.skylearn-billing-addon-card {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    transition: box-shadow 0.2s ease;
}

.skylearn-billing-addon-card:hover {
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.skylearn-billing-addon-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.skylearn-billing-addon-title h4 {
    margin: 0 0 5px 0;
    color: #23282d;
}

.skylearn-billing-addon-version {
    color: #666;
    font-size: 12px;
}

.skylearn-billing-addon-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.skylearn-billing-addon-free {
    background: #4caf50;
    color: white;
}

.skylearn-billing-addon-pro {
    background: #2196f3;
    color: white;
}

.skylearn-billing-addon-plus {
    background: #ff9800;
    color: white;
}

.skylearn-billing-addon-content {
    margin-bottom: 20px;
}

.skylearn-billing-addon-description {
    color: #555;
    margin-bottom: 10px;
}

.skylearn-billing-addon-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
}

.skylearn-billing-addon-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.skylearn-billing-status-active {
    color: #4caf50;
    font-weight: 600;
}

.skylearn-billing-status-installed {
    color: #ff9800;
    font-weight: 600;
}

.skylearn-billing-status-available {
    color: #2196f3;
    font-weight: 600;
}

.skylearn-billing-status-locked {
    color: #f44336;
    font-weight: 600;
}

.skylearn-billing-btn {
    padding: 6px 12px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 12px;
    text-decoration: none;
    display: inline-block;
    transition: background-color 0.2s ease;
}

.skylearn-billing-btn-primary {
    background: #0073aa;
    color: white;
}

.skylearn-billing-btn-primary:hover {
    background: #005a87;
}

.skylearn-billing-btn-secondary {
    background: #666;
    color: white;
}

.skylearn-billing-btn-secondary:hover {
    background: #555;
}

.skylearn-billing-btn-upgrade {
    background: #ff9800;
    color: white;
}

.skylearn-billing-btn-upgrade:hover {
    background: #f57c00;
}

.skylearn-billing-upgrade-notice {
    background: #f8f9fa;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 15px;
}

.skylearn-billing-upgrade-icon .dashicons {
    font-size: 24px;
    color: #666;
}

.skylearn-billing-upgrade-content h4 {
    margin: 0 0 5px 0;
    color: #23282d;
}

.skylearn-billing-upgrade-content p {
    margin: 0 0 10px 0;
    color: #666;
}
</style>