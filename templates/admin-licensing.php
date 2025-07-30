<?php
/**
 * License management template for Skylearn Billing Pro
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

$licensing_manager = skylearn_billing_pro_licensing();
$feature_flags = skylearn_billing_pro_features();
$current_tier = $licensing_manager->get_current_tier();
$license_status = $licensing_manager->get_license_status();
$license_key = $licensing_manager->get_license_key();
$is_active = $licensing_manager->is_license_active();
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-admin-network"></span>
                <?php esc_html_e('License Management', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Manage your Skylearn Billing Pro license and access premium features', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('General', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item active">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-network"></span>
                            <?php esc_html_e('License', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Tier Badge in Sidebar -->
            <div class="skylearn-billing-tier-badge">
                <div class="skylearn-billing-tier-info">
                    <div class="skylearn-billing-tier-icon" style="background-color: <?php echo esc_attr($licensing_manager->get_tier_color()); ?>">
                        <span class="dashicons dashicons-star-filled"></span>
                    </div>
                    <div class="skylearn-billing-tier-details">
                        <div class="skylearn-billing-tier-name"><?php echo esc_html($licensing_manager->get_tier_display_name()); ?></div>
                        <div class="skylearn-billing-tier-status">
                            <?php if ($is_active): ?>
                                <span class="skylearn-billing-status-active"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                            <?php else: ?>
                                <span class="skylearn-billing-status-inactive"><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <?php if ($current_tier === SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE): ?>
                    <a href="<?php echo esc_url($licensing_manager->get_upgrade_url()); ?>" target="_blank" class="skylearn-billing-upgrade-btn">
                        <?php esc_html_e('Upgrade Now', 'skylearn-billing-pro'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="skylearn-billing-content">
            <!-- License Activation Card -->
            <div class="skylearn-billing-card">
                <div class="skylearn-billing-card-header">
                    <h2><?php esc_html_e('License Activation', 'skylearn-billing-pro'); ?></h2>
                    <p><?php esc_html_e('Enter your license key to unlock premium features and receive updates.', 'skylearn-billing-pro'); ?></p>
                </div>
                
                <div class="skylearn-billing-card-body">
                    <div id="skylearn-license-messages"></div>
                    
                    <?php if ($is_active): ?>
                        <!-- Active License Display -->
                        <div class="skylearn-billing-license-active">
                            <div class="skylearn-billing-license-info">
                                <div class="skylearn-billing-license-key">
                                    <label><?php esc_html_e('License Key:', 'skylearn-billing-pro'); ?></label>
                                    <code><?php echo esc_html($license_key); ?></code>
                                </div>
                                
                                <div class="skylearn-billing-license-details">
                                    <div class="skylearn-billing-license-detail">
                                        <strong><?php esc_html_e('Plan:', 'skylearn-billing-pro'); ?></strong>
                                        <span class="skylearn-billing-tier-label" style="background-color: <?php echo esc_attr($licensing_manager->get_tier_color()); ?>">
                                            <?php echo esc_html($licensing_manager->get_tier_display_name()); ?>
                                        </span>
                                    </div>
                                    
                                    <?php 
                                    $days_until_expiry = $licensing_manager->get_days_until_expiry();
                                    if ($days_until_expiry !== false): 
                                    ?>
                                        <div class="skylearn-billing-license-detail">
                                            <strong><?php esc_html_e('Expires:', 'skylearn-billing-pro'); ?></strong>
                                            <span><?php echo sprintf(esc_html__('in %d days', 'skylearn-billing-pro'), $days_until_expiry); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="skylearn-billing-license-actions">
                                <button type="button" id="skylearn-deactivate-license" class="skylearn-billing-btn skylearn-billing-btn-secondary">
                                    <?php esc_html_e('Deactivate License', 'skylearn-billing-pro'); ?>
                                </button>
                                <a href="<?php echo esc_url($licensing_manager->get_upgrade_url()); ?>" target="_blank" class="skylearn-billing-btn skylearn-billing-btn-primary">
                                    <?php esc_html_e('Manage License', 'skylearn-billing-pro'); ?>
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- License Activation Form -->
                        <form id="skylearn-license-form" class="skylearn-billing-license-form">
                            <div class="skylearn-billing-form-group">
                                <label for="skylearn-license-key"><?php esc_html_e('License Key:', 'skylearn-billing-pro'); ?></label>
                                <input type="text" id="skylearn-license-key" name="license_key" class="skylearn-billing-input" 
                                       placeholder="<?php esc_attr_e('Enter your license key here...', 'skylearn-billing-pro'); ?>" 
                                       value="<?php echo esc_attr($license_key); ?>" />
                            </div>
                            
                            <div class="skylearn-billing-form-actions">
                                <button type="submit" class="skylearn-billing-btn skylearn-billing-btn-primary">
                                    <span class="skylearn-billing-btn-text"><?php esc_html_e('Activate License', 'skylearn-billing-pro'); ?></span>
                                    <span class="skylearn-billing-btn-loading" style="display: none;">
                                        <span class="dashicons dashicons-update-alt"></span>
                                        <?php esc_html_e('Validating...', 'skylearn-billing-pro'); ?>
                                    </span>
                                </button>
                            </div>
                        </form>
                        
                        <!-- Demo License Keys -->
                        <div class="skylearn-billing-demo-keys">
                            <h4><?php esc_html_e('Demo License Keys (for testing):', 'skylearn-billing-pro'); ?></h4>
                            <ul>
                                <li><code>SKYLRN-PRO-DEMO-2024</code> - <?php esc_html_e('Pro Plan', 'skylearn-billing-pro'); ?></li>
                                <li><code>SKYLRN-PLUS-DEMO-2024</code> - <?php esc_html_e('Pro Plus Plan', 'skylearn-billing-pro'); ?></li>
                            </ul>
                            <p class="description"><?php esc_html_e('Use these demo keys to test premium features.', 'skylearn-billing-pro'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Feature Comparison Card -->
            <div class="skylearn-billing-card">
                <div class="skylearn-billing-card-header">
                    <h3><?php esc_html_e('Feature Comparison', 'skylearn-billing-pro'); ?></h3>
                    <p><?php esc_html_e('See what features are available in each plan.', 'skylearn-billing-pro'); ?></p>
                </div>
                
                <div class="skylearn-billing-card-body">
                    <div class="skylearn-billing-feature-comparison">
                        <table class="skylearn-billing-comparison-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Feature', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Free', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Pro', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Pro Plus', 'skylearn-billing-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $comparison_features = array(
                                    'basic_billing' => __('Basic Billing', 'skylearn-billing-pro'),
                                    'unlimited_products' => __('Unlimited Products', 'skylearn-billing-pro'),
                                    'stripe_gateway' => __('Stripe Gateway', 'skylearn-billing-pro'),
                                    'recurring_subscriptions' => __('Recurring Subscriptions', 'skylearn-billing-pro'),
                                    'customer_portal' => __('Customer Portal', 'skylearn-billing-pro'),
                                    'email_templates' => __('Custom Email Templates', 'skylearn-billing-pro'),
                                    'advanced_analytics' => __('Advanced Analytics', 'skylearn-billing-pro'),
                                    'priority_support' => __('Priority Support', 'skylearn-billing-pro'),
                                    'white_label' => __('White Label', 'skylearn-billing-pro')
                                );
                                
                                foreach ($comparison_features as $feature_key => $feature_name):
                                    $feature_info = $feature_flags->get_feature_info($feature_key);
                                    if (!$feature_info) continue;
                                    
                                    $required_tier = $feature_info['required_tier'];
                                ?>
                                    <tr>
                                        <td><?php echo esc_html($feature_name); ?></td>
                                        <td class="skylearn-billing-feature-cell">
                                            <?php if ($required_tier === SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE): ?>
                                                <span class="skylearn-billing-feature-available"><span class="dashicons dashicons-yes"></span></span>
                                            <?php else: ?>
                                                <span class="skylearn-billing-feature-unavailable"><span class="dashicons dashicons-minus"></span></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="skylearn-billing-feature-cell">
                                            <?php if (in_array($required_tier, array(SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE, SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO))): ?>
                                                <span class="skylearn-billing-feature-available"><span class="dashicons dashicons-yes"></span></span>
                                            <?php else: ?>
                                                <span class="skylearn-billing-feature-unavailable"><span class="dashicons dashicons-minus"></span></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="skylearn-billing-feature-cell">
                                            <span class="skylearn-billing-feature-available"><span class="dashicons dashicons-yes"></span></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($current_tier !== SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS): ?>
                        <div class="skylearn-billing-upgrade-cta">
                            <p><?php esc_html_e('Ready to unlock more features?', 'skylearn-billing-pro'); ?></p>
                            <a href="<?php echo esc_url($licensing_manager->get_upgrade_url()); ?>" target="_blank" class="skylearn-billing-btn skylearn-billing-btn-primary">
                                <?php esc_html_e('Upgrade Your Plan', 'skylearn-billing-pro'); ?>
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>