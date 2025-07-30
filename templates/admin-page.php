<?php
/**
 * Admin page template for Skylearn Billing Pro
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

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
$licensing_manager = skylearn_billing_pro_licensing();
$feature_flags = skylearn_billing_pro_features();
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-credit-card"></span>
                <?php esc_html_e('Skylearn Billing Pro', 'skylearn-billing-pro'); ?>
                <span class="skylearn-billing-pro-badge" style="background-color: <?php echo esc_attr($licensing_manager->get_tier_color()); ?>">
                    <?php echo esc_html($licensing_manager->get_tier_display_name()); ?>
                </span>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Ultimate billing solution for WordPress course creators', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'general') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=general')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('General', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'payment-gateways') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=payment-gateways')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-money-alt"></span>
                            <?php esc_html_e('Payment Gateways', 'skylearn-billing-pro'); ?>
                            <?php if (!$feature_flags->is_feature_available('stripe_gateway')): ?>
                                <span class="skylearn-billing-badge skylearn-billing-pro-badge"><?php esc_html_e('Pro', 'skylearn-billing-pro'); ?></span>
                            <?php else: ?>
                                <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'subscriptions') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=subscriptions')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Subscriptions', 'skylearn-billing-pro'); ?>
                            <?php if (!$feature_flags->is_feature_available('recurring_subscriptions')): ?>
                                <span class="skylearn-billing-badge skylearn-billing-pro-badge"><?php esc_html_e('Pro', 'skylearn-billing-pro'); ?></span>
                            <?php else: ?>
                                <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'lms') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-lms')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <?php esc_html_e('LMS Integration', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-products"></span>
                            <?php esc_html_e('Products', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-networking"></span>
                            <?php esc_html_e('Bundles', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-email')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-email-alt"></span>
                            <?php esc_html_e('Email Settings', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'customers') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=customers')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-groups"></span>
                            <?php esc_html_e('Customers', 'skylearn-billing-pro'); ?>
                            <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'addons') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=addons')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-plugins"></span>
                            <?php esc_html_e('Addons', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'status') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=status')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-tools"></span>
                            <?php esc_html_e('Status', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'reports') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=reports')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php esc_html_e('Reports', 'skylearn-billing-pro'); ?>
                            <?php if (!$feature_flags->is_feature_available('advanced_analytics')): ?>
                                <span class="skylearn-billing-badge skylearn-billing-pro-badge"><?php esc_html_e('Pro+', 'skylearn-billing-pro'); ?></span>
                            <?php else: ?>
                                <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'help') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=help')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-editor-help"></span>
                            <?php esc_html_e('Help', 'skylearn-billing-pro'); ?>
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
                            <?php if ($licensing_manager->is_license_active()): ?>
                                <span class="skylearn-billing-status-active"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                            <?php else: ?>
                                <span class="skylearn-billing-status-inactive"><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')); ?>" class="skylearn-billing-upgrade-btn">
                    <?php esc_html_e('Manage License', 'skylearn-billing-pro'); ?>
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="skylearn-billing-content">
            <?php
            switch ($active_tab) {
                case 'general':
                    render_general_tab();
                    break;
                case 'payment-gateways':
                    render_payment_gateways_tab();
                    break;
                case 'subscriptions':
                    render_subscriptions_tab();
                    break;
                case 'customers':
                    render_coming_soon_tab(__('Customers', 'skylearn-billing-pro'));
                    break;
                case 'addons':
                    render_addons_tab();
                    break;
                case 'status':
                    render_status_tab();
                    break;
                case 'reports':
                    render_reports_tab();
                    break;
                case 'help':
                    render_help_tab();
                    break;
                default:
                    render_general_tab();
                    break;
            }
            ?>
        </div>
    </div>
</div>

<?php
/**
 * Render the General settings tab
 */
function render_general_tab() {
    $feature_flags = skylearn_billing_pro_features();
    $licensing_manager = skylearn_billing_pro_licensing();
    $product_limit = $feature_flags->get_feature_limit('unlimited_products');
    ?>
    <div class="skylearn-billing-tab-content">
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('General Settings', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Configure the basic settings for your billing system.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('skylearn_billing_pro_general');
                    do_settings_sections('skylearn_billing_pro_general');
                    ?>
                    
                    <div class="skylearn-billing-form-actions">
                        <?php submit_button(__('Save Settings', 'skylearn-billing-pro'), 'primary', 'submit', false, array('class' => 'skylearn-billing-btn skylearn-billing-btn-primary')); ?>
                        <button type="button" class="skylearn-billing-btn skylearn-billing-btn-secondary">
                            <?php esc_html_e('Reset to Defaults', 'skylearn-billing-pro'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Product Limits Card -->
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h3><?php esc_html_e('Product Limits', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Current product creation limits based on your plan.', 'skylearn-billing-pro'); ?></p>
            </div>
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-limit-info">
                    <div class="skylearn-billing-limit-item">
                        <strong><?php esc_html_e('Current Plan:', 'skylearn-billing-pro'); ?></strong>
                        <span class="skylearn-billing-tier-label" style="background-color: <?php echo esc_attr($licensing_manager->get_tier_color()); ?>">
                            <?php echo esc_html($licensing_manager->get_tier_display_name()); ?>
                        </span>
                    </div>
                    
                    <div class="skylearn-billing-limit-item">
                        <strong><?php esc_html_e('Product Limit:', 'skylearn-billing-pro'); ?></strong>
                        <span><?php 
                            if ($product_limit === -1) {
                                esc_html_e('Unlimited', 'skylearn-billing-pro');
                            } elseif ($product_limit === false) {
                                esc_html_e('Not Available', 'skylearn-billing-pro');
                            } else {
                                echo sprintf(esc_html__('%d products', 'skylearn-billing-pro'), $product_limit);
                            }
                        ?></span>
                    </div>
                    
                    <div class="skylearn-billing-limit-item">
                        <strong><?php esc_html_e('Products Created:', 'skylearn-billing-pro'); ?></strong>
                        <span>0 / <?php 
                            if ($product_limit === -1) {
                                esc_html_e('∞', 'skylearn-billing-pro');
                            } elseif ($product_limit === false) {
                                esc_html_e('0', 'skylearn-billing-pro');
                            } else {
                                echo esc_html($product_limit);
                            }
                        ?></span>
                    </div>
                </div>
                
                <?php if ($product_limit !== -1 && $product_limit !== false): ?>
                    <div class="skylearn-billing-upgrade-notice">
                        <span class="dashicons dashicons-info"></span>
                        <div>
                            <?php esc_html_e('You are limited to 3 products on the Free plan.', 'skylearn-billing-pro'); ?>
                            <a href="<?php echo esc_url($licensing_manager->get_upgrade_url()); ?>" target="_blank">
                                <?php esc_html_e('Upgrade to Pro for unlimited products', 'skylearn-billing-pro'); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats Card -->
        <div class="skylearn-billing-card skylearn-billing-quick-stats">
            <div class="skylearn-billing-card-header">
                <h3><?php esc_html_e('Quick Overview', 'skylearn-billing-pro'); ?></h3>
            </div>
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-stats-grid">
                    <div class="skylearn-billing-stat-item">
                        <div class="skylearn-billing-stat-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <div class="skylearn-billing-stat-content">
                            <div class="skylearn-billing-stat-number">0</div>
                            <div class="skylearn-billing-stat-label"><?php esc_html_e('Active Customers', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>
                    <div class="skylearn-billing-stat-item">
                        <div class="skylearn-billing-stat-icon">
                            <span class="dashicons dashicons-update"></span>
                        </div>
                        <div class="skylearn-billing-stat-content">
                            <div class="skylearn-billing-stat-number">0</div>
                            <div class="skylearn-billing-stat-label"><?php esc_html_e('Active Subscriptions', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>
                    <div class="skylearn-billing-stat-item">
                        <div class="skylearn-billing-stat-icon">
                            <span class="dashicons dashicons-money-alt"></span>
                        </div>
                        <div class="skylearn-billing-stat-content">
                            <div class="skylearn-billing-stat-number">$0</div>
                            <div class="skylearn-billing-stat-label"><?php esc_html_e('Monthly Revenue', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render coming soon tabs
 */
function render_coming_soon_tab($tab_name) {
    ?>
    <div class="skylearn-billing-tab-content">
        <div class="skylearn-billing-card skylearn-billing-coming-soon">
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-coming-soon-content">
                    <div class="skylearn-billing-coming-soon-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <h3><?php echo esc_html($tab_name); ?> - <?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></h3>
                    <p><?php esc_html_e('This feature is currently under development and will be available in a future release.', 'skylearn-billing-pro'); ?></p>
                    <p><?php esc_html_e('Stay tuned for updates!', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render Payment Gateways tab with tier restrictions
 */
function render_payment_gateways_tab() {
    $feature_flags = skylearn_billing_pro_features();
    $licensing_manager = skylearn_billing_pro_licensing();
    ?>
    <div class="skylearn-billing-tab-content">
        <?php if (!$feature_flags->is_feature_available('stripe_gateway')): ?>
            <div class="skylearn-billing-upgrade-notice">
                <span class="dashicons dashicons-lock"></span>
                <div>
                    <strong><?php esc_html_e('Payment Gateways require Pro or higher', 'skylearn-billing-pro'); ?></strong><br>
                    <?php echo $feature_flags->get_upgrade_message('stripe_gateway'); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="skylearn-billing-card <?php echo !$feature_flags->is_feature_available('stripe_gateway') ? 'skylearn-billing-feature-locked' : ''; ?>">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('Payment Gateways', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Configure payment gateways to accept payments from customers.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-gateway-grid">
                    <!-- Stripe Gateway -->
                    <div class="skylearn-billing-gateway-item">
                        <div class="skylearn-billing-gateway-header">
                            <h4><?php esc_html_e('Stripe', 'skylearn-billing-pro'); ?></h4>
                            <?php if (!$feature_flags->is_feature_available('stripe_gateway')): ?>
                                <span class="skylearn-billing-pro-badge"><?php esc_html_e('Pro', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        <p><?php esc_html_e('Accept credit card payments with Stripe.', 'skylearn-billing-pro'); ?></p>
                        <div class="skylearn-billing-gateway-status">
                            <span class="skylearn-billing-status-inactive"><?php esc_html_e('Not Configured', 'skylearn-billing-pro'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Lemon Squeezy Gateway -->
                    <div class="skylearn-billing-gateway-item">
                        <div class="skylearn-billing-gateway-header">
                            <h4><?php esc_html_e('Lemon Squeezy', 'skylearn-billing-pro'); ?></h4>
                            <?php if (!$feature_flags->is_feature_available('lemonsqueezy_gateway')): ?>
                                <span class="skylearn-billing-pro-badge"><?php esc_html_e('Pro', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        <p><?php esc_html_e('Simplified payments and tax handling with Lemon Squeezy.', 'skylearn-billing-pro'); ?></p>
                        <div class="skylearn-billing-gateway-status">
                            <span class="skylearn-billing-status-inactive"><?php esc_html_e('Not Configured', 'skylearn-billing-pro'); ?></span>
                        </div>
                    </div>
                    
                    <!-- PayPal Gateway -->
                    <div class="skylearn-billing-gateway-item">
                        <div class="skylearn-billing-gateway-header">
                            <h4><?php esc_html_e('PayPal', 'skylearn-billing-pro'); ?></h4>
                            <?php if (!$feature_flags->is_feature_available('paypal_gateway')): ?>
                                <span class="skylearn-billing-pro-badge"><?php esc_html_e('Pro+', 'skylearn-billing-pro'); ?></span>
                            <?php endif; ?>
                        </div>
                        <p><?php esc_html_e('Accept PayPal payments from customers worldwide.', 'skylearn-billing-pro'); ?></p>
                        <div class="skylearn-billing-gateway-status">
                            <span class="skylearn-billing-status-inactive"><?php esc_html_e('Not Configured', 'skylearn-billing-pro'); ?></span>
                        </div>
                    </div>
                </div>
                
                <p class="description"><?php esc_html_e('Gateway configuration will be available in a future update.', 'skylearn-billing-pro'); ?></p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render Subscriptions tab with tier restrictions
 */
function render_subscriptions_tab() {
    $feature_flags = skylearn_billing_pro_features();
    ?>
    <div class="skylearn-billing-tab-content">
        <?php if (!$feature_flags->is_feature_available('recurring_subscriptions')): ?>
            <div class="skylearn-billing-upgrade-notice">
                <span class="dashicons dashicons-lock"></span>
                <div>
                    <strong><?php esc_html_e('Subscriptions require Pro or higher', 'skylearn-billing-pro'); ?></strong><br>
                    <?php echo $feature_flags->get_upgrade_message('recurring_subscriptions'); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="skylearn-billing-card <?php echo !$feature_flags->is_feature_available('recurring_subscriptions') ? 'skylearn-billing-feature-locked' : ''; ?>">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('Subscription Management', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Create and manage recurring subscription plans for your courses.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-coming-soon-content">
                    <div class="skylearn-billing-coming-soon-icon">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <h3><?php esc_html_e('Subscription Features Coming Soon', 'skylearn-billing-pro'); ?></h3>
                    <p><?php esc_html_e('This feature is currently under development and will be available in a future release.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render Reports tab with tier restrictions
 */
function render_reports_tab() {
    $feature_flags = skylearn_billing_pro_features();
    ?>
    <div class="skylearn-billing-tab-content">
        <?php if (!$feature_flags->is_feature_available('advanced_analytics')): ?>
            <div class="skylearn-billing-upgrade-notice">
                <span class="dashicons dashicons-lock"></span>
                <div>
                    <strong><?php esc_html_e('Advanced Analytics require Pro Plus', 'skylearn-billing-pro'); ?></strong><br>
                    <?php echo $feature_flags->get_upgrade_message('advanced_analytics'); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Basic Analytics (always available) -->
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('Basic Analytics', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('View basic sales and customer statistics.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-stats-grid">
                    <div class="skylearn-billing-stat-item">
                        <div class="skylearn-billing-stat-icon">
                            <span class="dashicons dashicons-money-alt"></span>
                        </div>
                        <div class="skylearn-billing-stat-content">
                            <div class="skylearn-billing-stat-number">$0</div>
                            <div class="skylearn-billing-stat-label"><?php esc_html_e('Total Revenue', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>
                    <div class="skylearn-billing-stat-item">
                        <div class="skylearn-billing-stat-icon">
                            <span class="dashicons dashicons-admin-users"></span>
                        </div>
                        <div class="skylearn-billing-stat-content">
                            <div class="skylearn-billing-stat-number">0</div>
                            <div class="skylearn-billing-stat-label"><?php esc_html_e('Total Customers', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>
                    <div class="skylearn-billing-stat-item">
                        <div class="skylearn-billing-stat-icon">
                            <span class="dashicons dashicons-cart"></span>
                        </div>
                        <div class="skylearn-billing-stat-content">
                            <div class="skylearn-billing-stat-number">0</div>
                            <div class="skylearn-billing-stat-label"><?php esc_html_e('Total Orders', 'skylearn-billing-pro'); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Advanced Analytics (Pro Plus) -->
        <div class="skylearn-billing-card <?php echo !$feature_flags->is_feature_available('advanced_analytics') ? 'skylearn-billing-feature-locked' : ''; ?>">
            <div class="skylearn-billing-card-header">
                <h3><?php esc_html_e('Advanced Analytics', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Detailed insights, conversion tracking, and advanced reporting.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-coming-soon-content">
                    <div class="skylearn-billing-coming-soon-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <h4><?php esc_html_e('Advanced Analytics Coming Soon', 'skylearn-billing-pro'); ?></h4>
                    <p><?php esc_html_e('This feature is currently under development and will be available in a future release.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render Addons tab with dynamic addon management
 */
function render_addons_tab() {
    require_once SKYLEARN_BILLING_TEMPLATES_DIR . 'admin/addons.php';
}

/**
 * Render Status tab
 */
function render_status_tab() {
    require_once SKYLEARN_BILLING_TEMPLATES_DIR . 'admin/status.php';
}

/**
 * Render Help tab
 */
function render_help_tab() {
    $help_section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : 'addon-development';
    ?>
    <div class="skylearn-billing-help-page">
        <div class="skylearn-billing-help-nav">
            <h2><?php esc_html_e('Help & Documentation', 'skylearn-billing-pro'); ?></h2>
            <nav class="skylearn-billing-help-sections">
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=help&section=addon-development')); ?>" 
                   class="<?php echo $help_section === 'addon-development' ? 'active' : ''; ?>">
                    <?php esc_html_e('Addon Development', 'skylearn-billing-pro'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=help&section=getting-started')); ?>" 
                   class="<?php echo $help_section === 'getting-started' ? 'active' : ''; ?>">
                    <?php esc_html_e('Getting Started', 'skylearn-billing-pro'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=help&section=troubleshooting')); ?>" 
                   class="<?php echo $help_section === 'troubleshooting' ? 'active' : ''; ?>">
                    <?php esc_html_e('Troubleshooting', 'skylearn-billing-pro'); ?>
                </a>
            </nav>
        </div>
        
        <div class="skylearn-billing-help-content">
            <?php
            switch ($help_section) {
                case 'addon-development':
                    require_once SKYLEARN_BILLING_TEMPLATES_DIR . 'admin/help/addon-development.php';
                    break;
                case 'getting-started':
                    echo '<div class="skylearn-billing-help-placeholder">';
                    echo '<h3>' . esc_html__('Getting Started', 'skylearn-billing-pro') . '</h3>';
                    echo '<p>' . esc_html__('Getting started documentation will be available soon.', 'skylearn-billing-pro') . '</p>';
                    echo '</div>';
                    break;
                case 'troubleshooting':
                    echo '<div class="skylearn-billing-help-placeholder">';
                    echo '<h3>' . esc_html__('Troubleshooting', 'skylearn-billing-pro') . '</h3>';
                    echo '<p>' . esc_html__('Troubleshooting guide will be available soon.', 'skylearn-billing-pro') . '</p>';
                    echo '</div>';
                    break;
                default:
                    require_once SKYLEARN_BILLING_TEMPLATES_DIR . 'admin/help/addon-development.php';
                    break;
            }
            ?>
        </div>
    </div>
    
    <style>
    .skylearn-billing-help-page {
        max-width: 1200px;
    }
    
    .skylearn-billing-help-nav {
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #e1e1e1;
    }
    
    .skylearn-billing-help-nav h2 {
        margin: 0 0 15px 0;
        color: #23282d;
    }
    
    .skylearn-billing-help-sections {
        display: flex;
        gap: 20px;
    }
    
    .skylearn-billing-help-sections a {
        padding: 8px 16px;
        background: #f1f1f1;
        color: #555;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.2s ease;
    }
    
    .skylearn-billing-help-sections a:hover {
        background: #e1e1e1;
    }
    
    .skylearn-billing-help-sections a.active {
        background: #0073aa;
        color: white;
    }
    
    .skylearn-billing-help-content {
        background: #fff;
        border: 1px solid #e1e1e1;
        border-radius: 6px;
        padding: 30px;
    }
    
    .skylearn-billing-help-placeholder {
        text-align: center;
        padding: 40px 20px;
    }
    
    .skylearn-billing-help-placeholder h3 {
        color: #23282d;
        margin-bottom: 10px;
    }
    
    .skylearn-billing-help-placeholder p {
        color: #666;
    }
    </style>
    <?php
}
?>