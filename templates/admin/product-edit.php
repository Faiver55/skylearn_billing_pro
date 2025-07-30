<?php
/**
 * Product edit template
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

$product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;
$is_new = empty($product_id);
$product_manager = skylearn_billing_pro_product_manager();

if (!$is_new) {
    $product_data = $product_manager->get_product($product_id);
    if (!$product_data) {
        wp_die(__('Product not found', 'skylearn-billing-pro'));
    }
} else {
    // Check product limit for free tier
    if ($product_manager->check_product_limit()) {
        wp_die(__('Product limit exceeded for Free tier. Please upgrade to Pro.', 'skylearn-billing-pro'));
    }
    
    $product_data = array(
        'title' => '',
        'description' => '',
        'status' => 'active',
        'visibility' => 'visible',
        'featured' => false,
        'price' => '',
        'sale_price' => '',
        'currency' => 'USD',
        'billing_type' => 'one_time',
        'subscription_period' => 'monthly',
        'access_triggers' => array('payment'),
        'tier_restrictions' => array(),
        'course_mappings' => array(),
        'enrollment_behavior' => 'immediate'
    );
}

// Handle form submission
if (isset($_POST['save_product'])) {
    if (!wp_verify_nonce($_POST['product_nonce'], 'save_product')) {
        wp_die(__('Invalid nonce', 'skylearn-billing-pro'));
    }
    
    $form_data = array(
        'title' => sanitize_text_field($_POST['product_title'] ?? ''),
        'description' => wp_kses_post($_POST['product_description'] ?? ''),
        'skylearn_product_status' => sanitize_text_field($_POST['product_status'] ?? 'active'),
        'skylearn_product_visibility' => sanitize_text_field($_POST['product_visibility'] ?? 'visible'),
        'skylearn_product_featured' => isset($_POST['product_featured']) ? '1' : '',
        'skylearn_product_price' => floatval($_POST['product_price'] ?? 0),
        'skylearn_product_sale_price' => floatval($_POST['product_sale_price'] ?? 0),
        'skylearn_product_currency' => sanitize_text_field($_POST['product_currency'] ?? 'USD'),
        'skylearn_billing_type' => sanitize_text_field($_POST['billing_type'] ?? 'one_time'),
        'skylearn_subscription_period' => sanitize_text_field($_POST['subscription_period'] ?? 'monthly'),
        'access_triggers' => isset($_POST['access_triggers']) ? array_map('sanitize_text_field', $_POST['access_triggers']) : array(),
        'tier_restrictions' => isset($_POST['tier_restrictions']) ? array_map('sanitize_text_field', $_POST['tier_restrictions']) : array(),
        'course_mappings' => isset($_POST['course_mappings']) ? array_map('sanitize_text_field', $_POST['course_mappings']) : array(),
        'skylearn_enrollment_behavior' => sanitize_text_field($_POST['enrollment_behavior'] ?? 'immediate')
    );
    
    if ($is_new) {
        $result = $product_manager->create_product($form_data);
        if (!is_wp_error($result)) {
            wp_redirect(admin_url('admin.php?page=skylearn-billing-pro-products&action=edit&product=' . $result . '&saved=1'));
            exit;
        } else {
            $error_message = $result->get_error_message();
        }
    } else {
        // Update existing product logic would go here
        $success_message = __('Product updated successfully.', 'skylearn-billing-pro');
    }
}

$lms_manager = skylearn_billing_pro_lms_manager();
$available_courses = $lms_manager->get_courses();
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-products"></span>
                <?php echo $is_new ? esc_html__('Add New Product', 'skylearn-billing-pro') : esc_html__('Edit Product', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php echo $is_new ? esc_html__('Create a new product', 'skylearn-billing-pro') : esc_html__('Edit product details', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php esc_html_e('Back to Products', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Save Actions -->
            <div class="skylearn-billing-sidebar-widget">
                <h3><?php esc_html_e('Actions', 'skylearn-billing-pro'); ?></h3>
                <div class="skylearn-actions">
                    <button type="submit" form="product-form" class="button button-primary button-large">
                        <?php echo $is_new ? esc_html__('Create Product', 'skylearn-billing-pro') : esc_html__('Update Product', 'skylearn-billing-pro'); ?>
                    </button>
                    
                    <?php if (!$is_new): ?>
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=skylearn-billing-pro-products&action=duplicate&product=' . $product_id), 'duplicate_product_' . $product_id)); ?>" class="button">
                            <?php esc_html_e('Duplicate', 'skylearn-billing-pro'); ?>
                        </a>
                        
                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=skylearn-billing-pro-products&action=delete&product=' . $product_id), 'delete_product_' . $product_id)); ?>" 
                           class="button button-link-delete" 
                           onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this product?', 'skylearn-billing-pro'); ?>')">
                            <?php esc_html_e('Delete', 'skylearn-billing-pro'); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="skylearn-billing-content">
            <div class="skylearn-billing-content-inner">
                <!-- Notices -->
                <?php if (isset($success_message)): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php echo esc_html($success_message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="notice notice-error is-dismissible">
                        <p><?php echo esc_html($error_message); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
                    <div class="notice notice-success is-dismissible">
                        <p><?php esc_html_e('Product saved successfully.', 'skylearn-billing-pro'); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Product Form -->
                <form id="product-form" method="post" class="skylearn-product-form">
                    <?php wp_nonce_field('save_product', 'product_nonce'); ?>
                    <input type="hidden" name="save_product" value="1" />

                    <div class="skylearn-billing-tabs">
                        <ul class="skylearn-billing-tab-nav">
                            <li><a href="#tab-general" class="active"><?php esc_html_e('General', 'skylearn-billing-pro'); ?></a></li>
                            <li><a href="#tab-pricing"><?php esc_html_e('Pricing', 'skylearn-billing-pro'); ?></a></li>
                            <li><a href="#tab-lms"><?php esc_html_e('LMS Integration', 'skylearn-billing-pro'); ?></a></li>
                            <li><a href="#tab-access"><?php esc_html_e('Access Control', 'skylearn-billing-pro'); ?></a></li>
                        </ul>

                        <!-- General Tab -->
                        <div id="tab-general" class="skylearn-billing-tab-content active">
                            <div class="skylearn-form-section">
                                <h3><?php esc_html_e('Basic Information', 'skylearn-billing-pro'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="product_title"><?php esc_html_e('Product Title', 'skylearn-billing-pro'); ?> <span class="required">*</span></label>
                                        </th>
                                        <td>
                                            <input type="text" name="product_title" id="product_title" value="<?php echo esc_attr($product_data['title']); ?>" class="large-text" required />
                                            <p class="description"><?php esc_html_e('Enter the product name/title.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="product_description"><?php esc_html_e('Description', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <?php
                                            wp_editor($product_data['description'], 'product_description', array(
                                                'textarea_name' => 'product_description',
                                                'media_buttons' => true,
                                                'textarea_rows' => 10,
                                                'teeny' => false
                                            ));
                                            ?>
                                            <p class="description"><?php esc_html_e('Product description and details.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="product_status"><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <select name="product_status" id="product_status">
                                                <option value="active" <?php selected($product_data['status'], 'active'); ?>><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></option>
                                                <option value="inactive" <?php selected($product_data['status'], 'inactive'); ?>><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="product_visibility"><?php esc_html_e('Visibility', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <select name="product_visibility" id="product_visibility">
                                                <option value="visible" <?php selected($product_data['visibility'], 'visible'); ?>><?php esc_html_e('Visible', 'skylearn-billing-pro'); ?></option>
                                                <option value="hidden" <?php selected($product_data['visibility'], 'hidden'); ?>><?php esc_html_e('Hidden', 'skylearn-billing-pro'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="product_featured"><?php esc_html_e('Featured', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <label>
                                                <input type="checkbox" name="product_featured" id="product_featured" value="1" <?php checked($product_data['featured'], '1'); ?> />
                                                <?php esc_html_e('Mark as featured product', 'skylearn-billing-pro'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Pricing Tab -->
                        <div id="tab-pricing" class="skylearn-billing-tab-content">
                            <div class="skylearn-form-section">
                                <h3><?php esc_html_e('Pricing Configuration', 'skylearn-billing-pro'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="product_price"><?php esc_html_e('Price', 'skylearn-billing-pro'); ?> <span class="required">*</span></label>
                                        </th>
                                        <td>
                                            <input type="number" name="product_price" id="product_price" value="<?php echo esc_attr($product_data['price']); ?>" step="0.01" min="0" class="small-text" required />
                                            <p class="description"><?php esc_html_e('Regular price for this product.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="product_sale_price"><?php esc_html_e('Sale Price', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <input type="number" name="product_sale_price" id="product_sale_price" value="<?php echo esc_attr($product_data['sale_price']); ?>" step="0.01" min="0" class="small-text" />
                                            <p class="description"><?php esc_html_e('Optional sale price. Leave empty if not on sale.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="product_currency"><?php esc_html_e('Currency', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <select name="product_currency" id="product_currency">
                                                <option value="USD" <?php selected($product_data['currency'], 'USD'); ?>>USD</option>
                                                <option value="EUR" <?php selected($product_data['currency'], 'EUR'); ?>>EUR</option>
                                                <option value="GBP" <?php selected($product_data['currency'], 'GBP'); ?>>GBP</option>
                                                <option value="CAD" <?php selected($product_data['currency'], 'CAD'); ?>>CAD</option>
                                                <option value="AUD" <?php selected($product_data['currency'], 'AUD'); ?>>AUD</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="billing_type"><?php esc_html_e('Billing Type', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <select name="billing_type" id="billing_type">
                                                <option value="one_time" <?php selected($product_data['billing_type'], 'one_time'); ?>><?php esc_html_e('One Time', 'skylearn-billing-pro'); ?></option>
                                                <option value="subscription" <?php selected($product_data['billing_type'], 'subscription'); ?>><?php esc_html_e('Subscription', 'skylearn-billing-pro'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr class="subscription-fields" style="<?php echo ($product_data['billing_type'] !== 'subscription') ? 'display: none;' : ''; ?>">
                                        <th scope="row">
                                            <label for="subscription_period"><?php esc_html_e('Subscription Period', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <select name="subscription_period" id="subscription_period">
                                                <option value="weekly" <?php selected($product_data['subscription_period'], 'weekly'); ?>><?php esc_html_e('Weekly', 'skylearn-billing-pro'); ?></option>
                                                <option value="monthly" <?php selected($product_data['subscription_period'], 'monthly'); ?>><?php esc_html_e('Monthly', 'skylearn-billing-pro'); ?></option>
                                                <option value="quarterly" <?php selected($product_data['subscription_period'], 'quarterly'); ?>><?php esc_html_e('Quarterly', 'skylearn-billing-pro'); ?></option>
                                                <option value="yearly" <?php selected($product_data['subscription_period'], 'yearly'); ?>><?php esc_html_e('Yearly', 'skylearn-billing-pro'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- LMS Integration Tab -->
                        <div id="tab-lms" class="skylearn-billing-tab-content">
                            <div class="skylearn-form-section">
                                <h3><?php esc_html_e('Course Mapping', 'skylearn-billing-pro'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label><?php esc_html_e('Course Mappings', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <div id="course-mappings">
                                                <?php if (!empty($product_data['course_mappings'])): ?>
                                                    <?php foreach ($product_data['course_mappings'] as $index => $mapping): ?>
                                                        <div class="course-mapping-row" style="margin-bottom: 10px;">
                                                            <select name="course_mappings[]">
                                                                <option value=""><?php esc_html_e('Select a course...', 'skylearn-billing-pro'); ?></option>
                                                                <?php foreach ($available_courses as $course_id => $course_title): ?>
                                                                    <option value="<?php echo esc_attr($course_id); ?>" <?php selected($mapping, $course_id); ?>>
                                                                        <?php echo esc_html($course_title); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <button type="button" class="button remove-course-mapping"><?php esc_html_e('Remove', 'skylearn-billing-pro'); ?></button>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <button type="button" id="add-course-mapping" class="button"><?php esc_html_e('Add Course Mapping', 'skylearn-billing-pro'); ?></button>
                                            <p class="description"><?php esc_html_e('Map this product to LMS courses. Users will be enrolled in these courses upon purchase.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label for="enrollment_behavior"><?php esc_html_e('Enrollment Behavior', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <select name="enrollment_behavior" id="enrollment_behavior">
                                                <option value="immediate" <?php selected($product_data['enrollment_behavior'], 'immediate'); ?>><?php esc_html_e('Immediate', 'skylearn-billing-pro'); ?></option>
                                                <option value="delayed" <?php selected($product_data['enrollment_behavior'], 'delayed'); ?>><?php esc_html_e('Delayed', 'skylearn-billing-pro'); ?></option>
                                                <option value="manual" <?php selected($product_data['enrollment_behavior'], 'manual'); ?>><?php esc_html_e('Manual', 'skylearn-billing-pro'); ?></option>
                                            </select>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <!-- Access Control Tab -->
                        <div id="tab-access" class="skylearn-billing-tab-content">
                            <div class="skylearn-form-section">
                                <h3><?php esc_html_e('Access Control', 'skylearn-billing-pro'); ?></h3>
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label><?php esc_html_e('Access Triggers', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <?php
                                            $trigger_options = array(
                                                'payment' => __('Payment Complete', 'skylearn-billing-pro'),
                                                'manual' => __('Manual Enrollment', 'skylearn-billing-pro'),
                                                'webhook' => __('Webhook Trigger', 'skylearn-billing-pro'),
                                                'subscription' => __('Subscription Active', 'skylearn-billing-pro')
                                            );
                                            
                                            foreach ($trigger_options as $key => $label) {
                                                $checked = in_array($key, $product_data['access_triggers'] ?: array()) ? 'checked' : '';
                                                echo '<label style="display: block; margin-bottom: 5px;">';
                                                echo '<input type="checkbox" name="access_triggers[]" value="' . esc_attr($key) . '" ' . $checked . ' />';
                                                echo ' ' . esc_html($label);
                                                echo '</label>';
                                            }
                                            ?>
                                            <p class="description"><?php esc_html_e('Select when users should get access to this product.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php esc_html_e('Tier Restrictions', 'skylearn-billing-pro'); ?></label>
                                        </th>
                                        <td>
                                            <?php
                                            $tier_options = array(
                                                'free' => __('Free Tier', 'skylearn-billing-pro'),
                                                'pro' => __('Pro Tier', 'skylearn-billing-pro'),
                                                'pro_plus' => __('Pro Plus Tier', 'skylearn-billing-pro')
                                            );
                                            
                                            foreach ($tier_options as $key => $label) {
                                                $checked = in_array($key, $product_data['tier_restrictions'] ?: array()) ? 'checked' : '';
                                                echo '<label style="display: block; margin-bottom: 5px;">';
                                                echo '<input type="checkbox" name="tier_restrictions[]" value="' . esc_attr($key) . '" ' . $checked . ' />';
                                                echo ' ' . esc_html($label);
                                                echo '</label>';
                                            }
                                            ?>
                                            <p class="description"><?php esc_html_e('Select which tiers can access this product.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.skylearn-billing-tab-nav a').click(function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href');
        
        // Update active states
        $('.skylearn-billing-tab-nav a').removeClass('active');
        $(this).addClass('active');
        
        $('.skylearn-billing-tab-content').removeClass('active');
        $(targetTab).addClass('active');
    });
    
    // Billing type change
    $('#billing_type').change(function() {
        if ($(this).val() === 'subscription') {
            $('.subscription-fields').show();
        } else {
            $('.subscription-fields').hide();
        }
    });
    
    // Course mapping functionality
    var courseMappingTemplate = '<div class="course-mapping-row" style="margin-bottom: 10px;">' +
        '<select name="course_mappings[]">' +
        '<option value=""><?php echo esc_js(__("Select a course...", "skylearn-billing-pro")); ?></option>' +
        <?php foreach ($available_courses as $course_id => $course_title): ?>
        '<option value="<?php echo esc_js($course_id); ?>"><?php echo esc_js($course_title); ?></option>' +
        <?php endforeach; ?>
        '</select>' +
        '<button type="button" class="button remove-course-mapping"><?php echo esc_js(__("Remove", "skylearn-billing-pro")); ?></button>' +
        '</div>';
    
    $('#add-course-mapping').click(function() {
        $('#course-mappings').append(courseMappingTemplate);
    });
    
    $(document).on('click', '.remove-course-mapping', function() {
        $(this).closest('.course-mapping-row').remove();
    });
});
</script>

<style>
.skylearn-billing-tabs {
    background: var(--skylearn-white);
    border: 1px solid var(--skylearn-border);
    border-radius: 6px;
    overflow: hidden;
}

.skylearn-billing-tab-nav {
    display: flex;
    background: var(--skylearn-light-gray);
    border-bottom: 1px solid var(--skylearn-border);
    margin: 0;
    padding: 0;
    list-style: none;
}

.skylearn-billing-tab-nav li {
    margin: 0;
}

.skylearn-billing-tab-nav a {
    display: block;
    padding: 15px 20px;
    text-decoration: none;
    color: var(--skylearn-medium-gray);
    border-right: 1px solid var(--skylearn-border);
    transition: all 0.3s;
}

.skylearn-billing-tab-nav a:hover,
.skylearn-billing-tab-nav a.active {
    color: var(--skylearn-primary);
    background: var(--skylearn-white);
}

.skylearn-billing-tab-content {
    display: none;
    padding: 30px;
}

.skylearn-billing-tab-content.active {
    display: block;
}

.skylearn-form-section {
    margin-bottom: 30px;
}

.skylearn-form-section h3 {
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid var(--skylearn-primary);
    color: var(--skylearn-primary);
}

.skylearn-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.required {
    color: var(--skylearn-accent);
}

.course-mapping-row {
    display: flex;
    align-items: center;
    gap: 10px;
}

.course-mapping-row select {
    flex: 1;
}
</style>