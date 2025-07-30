<?php
/**
 * Products list template
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
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-products"></span>
                <?php esc_html_e('Products', 'skylearn-billing-pro'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products&action=add')); ?>" class="page-title-action">
                    <?php esc_html_e('Add New Product', 'skylearn-billing-pro'); ?>
                </a>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Manage your products and pricing', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item active">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php esc_html_e('All Products', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products&action=add')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Add New', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products&tab=import-export')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Import/Export', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-networking"></span>
                            <?php esc_html_e('Bundles', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Product Stats -->
            <div class="skylearn-billing-sidebar-widget">
                <h3><?php esc_html_e('Product Stats', 'skylearn-billing-pro'); ?></h3>
                <?php
                $product_manager = skylearn_billing_pro_product_manager();
                $all_products = $product_manager->get_products();
                $active_products = array_filter($all_products, function($product) {
                    return $product['status'] === 'active';
                });
                $subscription_products = array_filter($all_products, function($product) {
                    return $product['billing_type'] === 'subscription';
                });
                ?>
                <div class="skylearn-stat-item">
                    <span class="skylearn-stat-number"><?php echo count($all_products); ?></span>
                    <span class="skylearn-stat-label"><?php esc_html_e('Total Products', 'skylearn-billing-pro'); ?></span>
                </div>
                <div class="skylearn-stat-item">
                    <span class="skylearn-stat-number"><?php echo count($active_products); ?></span>
                    <span class="skylearn-stat-label"><?php esc_html_e('Active Products', 'skylearn-billing-pro'); ?></span>
                </div>
                <div class="skylearn-stat-item">
                    <span class="skylearn-stat-number"><?php echo count($subscription_products); ?></span>
                    <span class="skylearn-stat-label"><?php esc_html_e('Subscriptions', 'skylearn-billing-pro'); ?></span>
                </div>
                
                <?php if ($licensing_manager->get_tier() === 'free'): ?>
                    <div class="skylearn-tier-limit">
                        <p><strong><?php esc_html_e('Free Tier Limit:', 'skylearn-billing-pro'); ?></strong></p>
                        <p><?php echo count($all_products); ?>/5 <?php esc_html_e('products used', 'skylearn-billing-pro'); ?></p>
                        <?php if (count($all_products) >= 5): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')); ?>" class="button button-primary">
                                <?php esc_html_e('Upgrade to Pro', 'skylearn-billing-pro'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="skylearn-billing-content">
            <div class="skylearn-billing-content-inner">
                <!-- Notices -->
                <?php if (isset($_GET['deleted']) && $_GET['deleted'] == '1'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e('Product deleted successfully.', 'skylearn-billing-pro'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['duplicated']) && $_GET['duplicated'] == '1'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e('Product duplicated successfully.', 'skylearn-billing-pro'); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['error'])): ?>
                    <div class="notice notice-error is-dismissible">
                        <p>
                            <?php
                            switch ($_GET['error']) {
                                case 'delete':
                                    esc_html_e('Error deleting product.', 'skylearn-billing-pro');
                                    break;
                                case 'duplicate':
                                    esc_html_e('Error duplicating product.', 'skylearn-billing-pro');
                                    break;
                                case 'not_found':
                                    esc_html_e('Product not found.', 'skylearn-billing-pro');
                                    break;
                                default:
                                    esc_html_e('An error occurred.', 'skylearn-billing-pro');
                            }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <!-- Products Table -->
                <form method="post">
                    <?php
                    $table->search_box(__('Search Products', 'skylearn-billing-pro'), 'product');
                    $table->display();
                    ?>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.skylearn-billing-sidebar-widget {
    background: var(--skylearn-light-gray);
    border: 1px solid var(--skylearn-border);
    border-radius: 6px;
    padding: 20px;
    margin-top: 20px;
}

.skylearn-billing-sidebar-widget h3 {
    margin: 0 0 15px 0;
    font-size: 16px;
    color: var(--skylearn-primary);
}

.skylearn-stat-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--skylearn-border);
}

.skylearn-stat-item:last-child {
    border-bottom: none;
}

.skylearn-stat-number {
    font-weight: bold;
    font-size: 18px;
    color: var(--skylearn-primary);
}

.skylearn-stat-label {
    color: var(--skylearn-medium-gray);
    font-size: 14px;
}

.skylearn-tier-limit {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    padding: 15px;
    margin-top: 15px;
    text-align: center;
}

.skylearn-tier-limit p {
    margin: 0 0 10px 0;
    font-size: 13px;
}

.skylearn-price {
    font-weight: bold;
}

.skylearn-price del {
    color: #999;
    text-decoration: line-through;
}

.skylearn-price ins {
    color: var(--skylearn-accent);
    text-decoration: none;
}

.skylearn-status.active {
    color: var(--skylearn-success);
    font-weight: bold;
}

.skylearn-status.inactive {
    color: #999;
}

.skylearn-visibility.hidden {
    color: var(--skylearn-warning);
    font-style: italic;
}

.skylearn-type.subscription {
    color: var(--skylearn-primary);
    font-weight: bold;
}

.skylearn-courses {
    color: var(--skylearn-medium-gray);
}

.na {
    color: #999;
}
</style>