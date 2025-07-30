<?php
/**
 * Bundles list template
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

$bundle_manager = skylearn_billing_pro_bundle_manager();
$licensing_manager = skylearn_billing_pro_licensing();

// Get all bundles
$bundles = get_posts(array(
    'post_type' => SkyLearn_Billing_Pro_Bundle_Manager::POST_TYPE,
    'posts_per_page' => -1,
    'post_status' => 'publish'
));
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-networking"></span>
                <?php esc_html_e('Product Bundles', 'skylearn-billing-pro'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles&action=add')); ?>" class="page-title-action">
                    <?php esc_html_e('Add New Bundle', 'skylearn-billing-pro'); ?>
                </a>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Manage product bundles and course groups', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item active">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php esc_html_e('All Bundles', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles&action=add')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Add New', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-products"></span>
                            <?php esc_html_e('Products', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Bundle Stats -->
            <div class="skylearn-billing-sidebar-widget">
                <h3><?php esc_html_e('Bundle Stats', 'skylearn-billing-pro'); ?></h3>
                <?php
                $active_bundles = array_filter($bundles, function($bundle) {
                    return get_post_meta($bundle->ID, '_skylearn_bundle_status', true) === 'active';
                });
                ?>
                <div class="skylearn-stat-item">
                    <span class="skylearn-stat-number"><?php echo count($bundles); ?></span>
                    <span class="skylearn-stat-label"><?php esc_html_e('Total Bundles', 'skylearn-billing-pro'); ?></span>
                </div>
                <div class="skylearn-stat-item">
                    <span class="skylearn-stat-number"><?php echo count($active_bundles); ?></span>
                    <span class="skylearn-stat-label"><?php esc_html_e('Active Bundles', 'skylearn-billing-pro'); ?></span>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="skylearn-billing-content">
            <div class="skylearn-billing-content-inner">
                <?php if (empty($bundles)): ?>
                    <div class="skylearn-empty-state">
                        <div class="skylearn-empty-icon">
                            <span class="dashicons dashicons-networking"></span>
                        </div>
                        <h3><?php esc_html_e('No bundles yet', 'skylearn-billing-pro'); ?></h3>
                        <p><?php esc_html_e('Create your first product bundle to group products and courses together.', 'skylearn-billing-pro'); ?></p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles&action=add')); ?>" class="button button-primary">
                            <?php esc_html_e('Create Bundle', 'skylearn-billing-pro'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="skylearn-bundles-grid">
                        <?php foreach ($bundles as $bundle): ?>
                            <?php
                            $bundle_data = $bundle_manager->get_bundle($bundle->ID);
                            $status = $bundle_data['status'] ?: 'active';
                            $type = $bundle_data['type'] ?: 'product_group';
                            $product_count = count($bundle_data['products'] ?: array());
                            $course_count = count($bundle_data['courses'] ?: array());
                            $price = $bundle_data['auto_calculate_price'] ? $bundle_data['calculated_price'] : $bundle_data['price'];
                            ?>
                            <div class="skylearn-bundle-card <?php echo esc_attr($status); ?>">
                                <div class="skylearn-bundle-header">
                                    <h4 class="skylearn-bundle-title">
                                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles&action=edit&bundle=' . $bundle->ID)); ?>">
                                            <?php echo esc_html($bundle->post_title); ?>
                                        </a>
                                    </h4>
                                    <div class="skylearn-bundle-status status-<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html(ucfirst($status)); ?>
                                    </div>
                                </div>
                                
                                <div class="skylearn-bundle-meta">
                                    <div class="skylearn-bundle-type">
                                        <?php
                                        switch ($type) {
                                            case 'product_group':
                                                esc_html_e('Product Group', 'skylearn-billing-pro');
                                                break;
                                            case 'course_bundle':
                                                esc_html_e('Course Bundle', 'skylearn-billing-pro');
                                                break;
                                            case 'addon_bundle':
                                                esc_html_e('Addon Bundle', 'skylearn-billing-pro');
                                                break;
                                        }
                                        ?>
                                    </div>
                                    
                                    <div class="skylearn-bundle-stats">
                                        <span class="skylearn-bundle-stat">
                                            <strong><?php echo $product_count; ?></strong> 
                                            <?php echo _n('product', 'products', $product_count, 'skylearn-billing-pro'); ?>
                                        </span>
                                        <span class="skylearn-bundle-stat">
                                            <strong><?php echo $course_count; ?></strong> 
                                            <?php echo _n('course', 'courses', $course_count, 'skylearn-billing-pro'); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($price): ?>
                                        <div class="skylearn-bundle-price">
                                            $<?php echo number_format($price, 2); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="skylearn-bundle-actions">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles&action=edit&bundle=' . $bundle->ID)); ?>" class="button button-small">
                                        <?php esc_html_e('Edit', 'skylearn-billing-pro'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=skylearn-billing-pro-bundles&action=delete&bundle=' . $bundle->ID), 'delete_bundle_' . $bundle->ID)); ?>" 
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this bundle?', 'skylearn-billing-pro'); ?>')">
                                        <?php esc_html_e('Delete', 'skylearn-billing-pro'); ?>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.skylearn-empty-state {
    text-align: center;
    padding: 60px 20px;
    background: var(--skylearn-white);
    border: 1px solid var(--skylearn-border);
    border-radius: 6px;
}

.skylearn-empty-icon {
    font-size: 64px;
    color: var(--skylearn-medium-gray);
    margin-bottom: 20px;
}

.skylearn-empty-state h3 {
    color: var(--skylearn-primary);
    margin-bottom: 10px;
}

.skylearn-empty-state p {
    color: var(--skylearn-medium-gray);
    margin-bottom: 20px;
}

.skylearn-bundles-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.skylearn-bundle-card {
    background: var(--skylearn-white);
    border: 1px solid var(--skylearn-border);
    border-radius: 6px;
    padding: 20px;
    transition: box-shadow 0.3s;
}

.skylearn-bundle-card:hover {
    box-shadow: 0 4px 12px var(--skylearn-shadow);
}

.skylearn-bundle-card.inactive {
    opacity: 0.6;
}

.skylearn-bundle-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.skylearn-bundle-title {
    margin: 0;
    font-size: 18px;
}

.skylearn-bundle-title a {
    color: var(--skylearn-primary);
    text-decoration: none;
}

.skylearn-bundle-title a:hover {
    color: var(--skylearn-accent);
}

.skylearn-bundle-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: bold;
    text-transform: uppercase;
}

.status-active {
    background: #d4edda;
    color: #155724;
}

.status-inactive {
    background: #f8d7da;
    color: #721c24;
}

.skylearn-bundle-meta {
    margin-bottom: 20px;
}

.skylearn-bundle-type {
    color: var(--skylearn-medium-gray);
    font-size: 14px;
    margin-bottom: 10px;
}

.skylearn-bundle-stats {
    display: flex;
    gap: 15px;
    margin-bottom: 10px;
}

.skylearn-bundle-stat {
    font-size: 14px;
    color: var(--skylearn-medium-gray);
}

.skylearn-bundle-price {
    font-size: 20px;
    font-weight: bold;
    color: var(--skylearn-primary);
}

.skylearn-bundle-actions {
    display: flex;
    gap: 10px;
}
</style>