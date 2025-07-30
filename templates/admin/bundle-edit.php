<?php
/**
 * Bundle edit template
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

$bundle_id = isset($_GET['bundle']) ? intval($_GET['bundle']) : 0;
$is_new = empty($bundle_id);
$bundle_manager = skylearn_billing_pro_bundle_manager();

if (!$is_new) {
    $bundle_data = $bundle_manager->get_bundle($bundle_id);
    if (!$bundle_data) {
        wp_die(__('Bundle not found', 'skylearn-billing-pro'));
    }
} else {
    $bundle_data = array(
        'title' => '',
        'description' => '',
        'type' => 'product_group',
        'products' => array(),
        'courses' => array(),
        'status' => 'active',
        'auto_calculate_price' => true,
        'price' => '',
        'discount' => '',
        'discount_type' => 'percentage',
        'unlock_behavior' => 'all_at_once'
    );
}

// Get available products and courses
$product_manager = skylearn_billing_pro_product_manager();
$available_products = $product_manager->get_products();
$lms_manager = skylearn_billing_pro_lms_manager();
$available_courses = $lms_manager->get_courses();
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-networking"></span>
                <?php echo $is_new ? esc_html__('Add New Bundle', 'skylearn-billing-pro') : esc_html__('Edit Bundle', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php echo $is_new ? esc_html__('Create a new product bundle', 'skylearn-billing-pro') : esc_html__('Edit bundle details', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-bundles')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php esc_html_e('Back to Bundles', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Save Actions -->
            <div class="skylearn-billing-sidebar-widget">
                <h3><?php esc_html_e('Actions', 'skylearn-billing-pro'); ?></h3>
                <div class="skylearn-actions">
                    <button type="submit" form="bundle-form" class="button button-primary button-large">
                        <?php echo $is_new ? esc_html__('Create Bundle', 'skylearn-billing-pro') : esc_html__('Update Bundle', 'skylearn-billing-pro'); ?>
                    </button>
                    
                    <?php if (!$is_new): ?>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=skylearn-billing-pro-bundles&action=delete&bundle=' . $bundle_id), 'delete_bundle_' . $bundle_id)); ?>" 
                           class="button button-link-delete" 
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this bundle?', 'skylearn-billing-pro'); ?>')">
                            <?php esc_html_e('Delete Bundle', 'skylearn-billing-pro'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="skylearn-billing-content">
            <div class="skylearn-billing-content-inner">
                <!-- Bundle Form (Simplified for demo) -->
                <div class="skylearn-bundle-editor">
                    <h2><?php echo $is_new ? esc_html__('Create New Bundle', 'skylearn-billing-pro') : esc_html__('Edit Bundle', 'skylearn-billing-pro'); ?></h2>
                    <p><?php esc_html_e('This is a simplified bundle editor. The full functionality would include the meta boxes from the Bundle Manager class.', 'skylearn-billing-pro'); ?></p>
                    
                    <form id="bundle-form" method="post" class="skylearn-bundle-form">
                        <?php wp_nonce_field('save_bundle', 'bundle_nonce'); ?>
                        <input type="hidden" name="save_bundle" value="1" />

                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Basic Information', 'skylearn-billing-pro'); ?></h3>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="bundle_title"><?php esc_html_e('Bundle Title', 'skylearn-billing-pro'); ?> <span class="required">*</span></label>
                                    </th>
                                    <td>
                                        <input type="text" name="bundle_title" id="bundle_title" value="<?php echo esc_attr($bundle_data['title']); ?>" class="large-text" required />
                                        <p class="description"><?php esc_html_e('Enter the bundle name/title.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="bundle_type"><?php esc_html_e('Bundle Type', 'skylearn-billing-pro'); ?></label>
                                    </th>
                                    <td>
                                        <select name="bundle_type" id="bundle_type">
                                            <option value="product_group" <?php selected($bundle_data['type'], 'product_group'); ?>><?php esc_html_e('Product Group', 'skylearn-billing-pro'); ?></option>
                                            <option value="course_bundle" <?php selected($bundle_data['type'], 'course_bundle'); ?>><?php esc_html_e('Course Bundle', 'skylearn-billing-pro'); ?></option>
                                            <option value="addon_bundle" <?php selected($bundle_data['type'], 'addon_bundle'); ?>><?php esc_html_e('Addon Bundle', 'skylearn-billing-pro'); ?></option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Select the type of bundle to create.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="bundle_status"><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></label>
                                    </th>
                                    <td>
                                        <select name="bundle_status" id="bundle_status">
                                            <option value="active" <?php selected($bundle_data['status'], 'active'); ?>><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></option>
                                            <option value="inactive" <?php selected($bundle_data['status'], 'inactive'); ?>><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Bundle Products', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Select products to include in this bundle.', 'skylearn-billing-pro'); ?></p>
                            
                            <?php if (!empty($available_products)): ?>
                                <div class="skylearn-product-selection">
                                    <?php foreach ($available_products as $product): ?>
                                        <label class="skylearn-product-option">
                                            <input type="checkbox" name="bundle_products[]" value="<?php echo esc_attr($product['id']); ?>" 
                                                   <?php checked(in_array($product['id'], $bundle_data['products'] ?: array())); ?> />
                                            <span class="skylearn-product-title"><?php echo esc_html($product['title']); ?></span>
                                            <span class="skylearn-product-price"><?php echo esc_html($product['currency'] . ' ' . number_format($product['price'], 2)); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="description"><?php esc_html_e('No products available. Create some products first.', 'skylearn-billing-pro'); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Bundle Courses', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Select additional courses that this bundle should unlock.', 'skylearn-billing-pro'); ?></p>
                            
                            <?php if (!empty($available_courses)): ?>
                                <div class="skylearn-course-selection">
                                    <?php foreach ($available_courses as $course_id => $course_title): ?>
                                        <label class="skylearn-course-option">
                                            <input type="checkbox" name="bundle_courses[]" value="<?php echo esc_attr($course_id); ?>" 
                                                   <?php checked(in_array($course_id, $bundle_data['courses'] ?: array())); ?> />
                                            <span class="skylearn-course-title"><?php echo esc_html($course_title); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="description"><?php esc_html_e('No courses available. Configure LMS integration first.', 'skylearn-billing-pro'); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Pricing Settings', 'skylearn-billing-pro'); ?></h3>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="auto_calculate_price"><?php esc_html_e('Auto Calculate Price', 'skylearn-billing-pro'); ?></label>
                                    </th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="auto_calculate_price" id="auto_calculate_price" value="1" <?php checked($bundle_data['auto_calculate_price'], true); ?> />
                                            <?php esc_html_e('Automatically calculate bundle price from products', 'skylearn-billing-pro'); ?>
                                        </label>
                                    </td>
                                </tr>
                                <tr class="manual-price-fields" style="<?php echo ($bundle_data['auto_calculate_price']) ? 'display: none;' : ''; ?>">
                                    <th scope="row">
                                        <label for="bundle_price"><?php esc_html_e('Bundle Price', 'skylearn-billing-pro'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" name="bundle_price" id="bundle_price" value="<?php echo esc_attr($bundle_data['price']); ?>" step="0.01" min="0" class="small-text" />
                                        <p class="description"><?php esc_html_e('Manual bundle price.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                                <tr class="discount-fields" style="<?php echo (!$bundle_data['auto_calculate_price']) ? 'display: none;' : ''; ?>">
                                    <th scope="row">
                                        <label for="bundle_discount"><?php esc_html_e('Bundle Discount', 'skylearn-billing-pro'); ?></label>
                                    </th>
                                    <td>
                                        <input type="number" name="bundle_discount" id="bundle_discount" value="<?php echo esc_attr($bundle_data['discount']); ?>" step="0.01" min="0" class="small-text" />
                                        <select name="bundle_discount_type" id="bundle_discount_type">
                                            <option value="percentage" <?php selected($bundle_data['discount_type'], 'percentage'); ?>><?php esc_html_e('%', 'skylearn-billing-pro'); ?></option>
                                            <option value="fixed" <?php selected($bundle_data['discount_type'], 'fixed'); ?>><?php esc_html_e('Fixed', 'skylearn-billing-pro'); ?></option>
                                        </select>
                                        <p class="description"><?php esc_html_e('Discount to apply to total product prices.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </form>
                    
                    <div class="skylearn-note">
                        <h4><?php esc_html_e('Note', 'skylearn-billing-pro'); ?></h4>
                        <p><?php esc_html_e('This is a simplified bundle editor for demonstration purposes. The full implementation would use the complete meta box system from the Bundle Manager class with all advanced features like course unlock behavior, pricing previews, and more.', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Auto calculate price toggle
    $('#auto_calculate_price').change(function() {
        if ($(this).is(':checked')) {
            $('.manual-price-fields').hide();
            $('.discount-fields').show();
        } else {
            $('.manual-price-fields').show();
            $('.discount-fields').hide();
        }
    });
});
</script>

<style>
.skylearn-bundle-editor {
    background: var(--skylearn-white);
    border: 1px solid var(--skylearn-border);
    border-radius: 6px;
    padding: 30px;
}

.skylearn-form-section {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid var(--skylearn-border);
}

.skylearn-form-section:last-child {
    border-bottom: none;
}

.skylearn-form-section h3 {
    margin: 0 0 20px 0;
    color: var(--skylearn-primary);
    border-bottom: 2px solid var(--skylearn-primary);
    padding-bottom: 10px;
}

.skylearn-product-selection,
.skylearn-course-selection {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 10px;
    margin-top: 15px;
}

.skylearn-product-option,
.skylearn-course-option {
    display: flex;
    align-items: center;
    padding: 10px;
    background: var(--skylearn-light-gray);
    border: 1px solid var(--skylearn-border);
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.skylearn-product-option:hover,
.skylearn-course-option:hover {
    background: #e9ecef;
}

.skylearn-product-option input,
.skylearn-course-option input {
    margin-right: 10px;
}

.skylearn-product-title,
.skylearn-course-title {
    flex: 1;
    font-weight: 500;
}

.skylearn-product-price {
    font-size: 14px;
    color: var(--skylearn-medium-gray);
    font-weight: bold;
}

.skylearn-note {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 4px;
    padding: 20px;
    margin-top: 30px;
}

.skylearn-note h4 {
    margin: 0 0 10px 0;
    color: var(--skylearn-primary);
}

.skylearn-note p {
    margin: 0;
    color: var(--skylearn-medium-gray);
}

.required {
    color: var(--skylearn-accent);
}
</style>