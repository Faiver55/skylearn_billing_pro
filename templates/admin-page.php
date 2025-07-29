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
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-credit-card"></span>
                <?php esc_html_e('Skylearn Billing Pro', 'skylearn-billing-pro'); ?>
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
                            <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'subscriptions') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=subscriptions')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Subscriptions', 'skylearn-billing-pro'); ?>
                            <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'customers') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=customers')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-groups"></span>
                            <?php esc_html_e('Customers', 'skylearn-billing-pro'); ?>
                            <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'reports') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro&tab=reports')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php esc_html_e('Reports', 'skylearn-billing-pro'); ?>
                            <span class="skylearn-billing-badge"><?php esc_html_e('Coming Soon', 'skylearn-billing-pro'); ?></span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <!-- Main Content Area -->
        <div class="skylearn-billing-content">
            <?php
            switch ($active_tab) {
                case 'general':
                    render_general_tab();
                    break;
                case 'payment-gateways':
                    render_coming_soon_tab(__('Payment Gateways', 'skylearn-billing-pro'));
                    break;
                case 'subscriptions':
                    render_coming_soon_tab(__('Subscriptions', 'skylearn-billing-pro'));
                    break;
                case 'customers':
                    render_coming_soon_tab(__('Customers', 'skylearn-billing-pro'));
                    break;
                case 'reports':
                    render_coming_soon_tab(__('Reports', 'skylearn-billing-pro'));
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
?>