<?php
/**
 * Bundle Manager class for handling product bundles and groups
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

/**
 * Bundle Manager class
 */
class SkyLearn_Billing_Pro_Bundle_Manager {
    
    /**
     * Post type slug for bundles
     */
    const POST_TYPE = 'skylearn_bundle';
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Bundle_Manager
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Bundle_Manager
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_bundle_meta'));
        add_filter('skylearn_billing_product_saved', array($this, 'update_bundle_on_product_save'), 10, 2);
    }
    
    /**
     * Register custom post type for bundles
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Bundles', 'post type general name', 'skylearn-billing-pro'),
            'singular_name'      => _x('Bundle', 'post type singular name', 'skylearn-billing-pro'),
            'menu_name'          => _x('Bundles', 'admin menu', 'skylearn-billing-pro'),
            'name_admin_bar'     => _x('Bundle', 'add new on admin bar', 'skylearn-billing-pro'),
            'add_new'            => _x('Add New', 'bundle', 'skylearn-billing-pro'),
            'add_new_item'       => __('Add New Bundle', 'skylearn-billing-pro'),
            'new_item'           => __('New Bundle', 'skylearn-billing-pro'),
            'edit_item'          => __('Edit Bundle', 'skylearn-billing-pro'),
            'view_item'          => __('View Bundle', 'skylearn-billing-pro'),
            'all_items'          => __('All Bundles', 'skylearn-billing-pro'),
            'search_items'       => __('Search Bundles', 'skylearn-billing-pro'),
            'parent_item_colon'  => __('Parent Bundles:', 'skylearn-billing-pro'),
            'not_found'          => __('No bundles found.', 'skylearn-billing-pro'),
            'not_found_in_trash' => __('No bundles found in Trash.', 'skylearn-billing-pro')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Product bundles for Skylearn Billing', 'skylearn-billing-pro'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false, // We'll handle UI ourselves
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'skylearn-bundle'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail')
        );

        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Add meta boxes for bundle editing
     */
    public function add_meta_boxes() {
        add_meta_box(
            'skylearn_bundle_products',
            __('Bundle Products', 'skylearn-billing-pro'),
            array($this, 'bundle_products_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
        
        add_meta_box(
            'skylearn_bundle_settings',
            __('Bundle Settings', 'skylearn-billing-pro'),
            array($this, 'bundle_settings_meta_box'),
            self::POST_TYPE,
            'side',
            'high'
        );
        
        add_meta_box(
            'skylearn_bundle_courses',
            __('Bundle Courses', 'skylearn-billing-pro'),
            array($this, 'bundle_courses_meta_box'),
            self::POST_TYPE,
            'normal',
            'default'
        );
    }
    
    /**
     * Bundle products meta box
     */
    public function bundle_products_meta_box($post) {
        wp_nonce_field('skylearn_bundle_meta_nonce', 'skylearn_bundle_meta_nonce');
        
        $bundle_products = get_post_meta($post->ID, '_skylearn_bundle_products', true) ?: array();
        $bundle_type = get_post_meta($post->ID, '_skylearn_bundle_type', true) ?: 'product_group';
        
        // Get available products
        $product_manager = skylearn_billing_pro_product_manager();
        $available_products = $product_manager->get_products();
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="skylearn_bundle_type"><?php esc_html_e('Bundle Type', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_bundle_type" id="skylearn_bundle_type">
                        <option value="product_group" <?php selected($bundle_type, 'product_group'); ?>><?php esc_html_e('Product Group', 'skylearn-billing-pro'); ?></option>
                        <option value="course_bundle" <?php selected($bundle_type, 'course_bundle'); ?>><?php esc_html_e('Course Bundle', 'skylearn-billing-pro'); ?></option>
                        <option value="addon_bundle" <?php selected($bundle_type, 'addon_bundle'); ?>><?php esc_html_e('Addon Bundle', 'skylearn-billing-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Select the type of bundle to create.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Bundle Products', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <div id="skylearn-bundle-products">
                        <?php if (!empty($available_products)): ?>
                            <?php foreach ($available_products as $product): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="skylearn_bundle_products[]" value="<?php echo esc_attr($product['id']); ?>" 
                                           <?php checked(in_array($product['id'], $bundle_products)); ?> />
                                    <?php echo esc_html($product['title']); ?>
                                    <small>(<?php echo esc_html($product['currency'] . ' ' . $product['price']); ?>)</small>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php esc_html_e('No products available. Create some products first.', 'skylearn-billing-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                    <p class="description"><?php esc_html_e('Select products to include in this bundle.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Bundle settings meta box
     */
    public function bundle_settings_meta_box($post) {
        $bundle_price = get_post_meta($post->ID, '_skylearn_bundle_price', true);
        $bundle_discount = get_post_meta($post->ID, '_skylearn_bundle_discount', true);
        $bundle_discount_type = get_post_meta($post->ID, '_skylearn_bundle_discount_type', true) ?: 'percentage';
        $bundle_status = get_post_meta($post->ID, '_skylearn_bundle_status', true) ?: 'active';
        $auto_calculate_price = get_post_meta($post->ID, '_skylearn_auto_calculate_price', true);
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="skylearn_bundle_status"><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_bundle_status" id="skylearn_bundle_status">
                        <option value="active" <?php selected($bundle_status, 'active'); ?>><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></option>
                        <option value="inactive" <?php selected($bundle_status, 'inactive'); ?>><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="skylearn_auto_calculate_price"><?php esc_html_e('Auto Calculate Price', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="skylearn_auto_calculate_price" id="skylearn_auto_calculate_price" value="1" <?php checked($auto_calculate_price, '1'); ?> />
                        <?php esc_html_e('Automatically calculate bundle price from products', 'skylearn-billing-pro'); ?>
                    </label>
                </td>
            </tr>
            <tr class="manual-price-fields" style="<?php echo ($auto_calculate_price) ? 'display: none;' : ''; ?>">
                <th scope="row">
                    <label for="skylearn_bundle_price"><?php esc_html_e('Bundle Price', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <input type="number" name="skylearn_bundle_price" id="skylearn_bundle_price" value="<?php echo esc_attr($bundle_price); ?>" step="0.01" min="0" class="small-text" />
                    <p class="description"><?php esc_html_e('Manual bundle price.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
            <tr class="discount-fields" style="<?php echo (!$auto_calculate_price) ? 'display: none;' : ''; ?>">
                <th scope="row">
                    <label for="skylearn_bundle_discount"><?php esc_html_e('Bundle Discount', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <input type="number" name="skylearn_bundle_discount" id="skylearn_bundle_discount" value="<?php echo esc_attr($bundle_discount); ?>" step="0.01" min="0" class="small-text" />
                    <select name="skylearn_bundle_discount_type" id="skylearn_bundle_discount_type">
                        <option value="percentage" <?php selected($bundle_discount_type, 'percentage'); ?>><?php esc_html_e('%', 'skylearn-billing-pro'); ?></option>
                        <option value="fixed" <?php selected($bundle_discount_type, 'fixed'); ?>><?php esc_html_e('Fixed', 'skylearn-billing-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Discount to apply to total product prices.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
        </table>
        
        <div id="skylearn-bundle-pricing-preview">
            <h4><?php esc_html_e('Pricing Preview', 'skylearn-billing-pro'); ?></h4>
            <div id="pricing-calculation"></div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            function togglePriceFields() {
                if ($('#skylearn_auto_calculate_price').is(':checked')) {
                    $('.manual-price-fields').hide();
                    $('.discount-fields').show();
                } else {
                    $('.manual-price-fields').show();
                    $('.discount-fields').hide();
                }
                updatePricingPreview();
            }
            
            function updatePricingPreview() {
                var selectedProducts = [];
                $('input[name="skylearn_bundle_products[]"]:checked').each(function() {
                    var productRow = $(this).closest('label');
                    var productTitle = productRow.text().trim();
                    var priceMatch = productTitle.match(/\((.*?)\)/);
                    if (priceMatch) {
                        var price = parseFloat(priceMatch[1].replace(/[^\d.]/g, ''));
                        selectedProducts.push({
                            title: productTitle.replace(priceMatch[0], '').trim(),
                            price: price
                        });
                    }
                });
                
                var totalPrice = selectedProducts.reduce(function(sum, product) {
                    return sum + product.price;
                }, 0);
                
                var finalPrice = totalPrice;
                var discount = parseFloat($('#skylearn_bundle_discount').val()) || 0;
                var discountType = $('#skylearn_bundle_discount_type').val();
                
                if ($('#skylearn_auto_calculate_price').is(':checked') && discount > 0) {
                    if (discountType === 'percentage') {
                        finalPrice = totalPrice - (totalPrice * discount / 100);
                    } else {
                        finalPrice = totalPrice - discount;
                    }
                } else if (!$('#skylearn_auto_calculate_price').is(':checked')) {
                    finalPrice = parseFloat($('#skylearn_bundle_price').val()) || 0;
                }
                
                var html = '<p><strong>Products:</strong> ' + selectedProducts.length + '</p>';
                html += '<p><strong>Total Product Price:</strong> $' + totalPrice.toFixed(2) + '</p>';
                html += '<p><strong>Bundle Price:</strong> $' + finalPrice.toFixed(2) + '</p>';
                if (finalPrice < totalPrice) {
                    var savings = totalPrice - finalPrice;
                    html += '<p style="color: green;"><strong>Savings:</strong> $' + savings.toFixed(2) + '</p>';
                }
                
                $('#pricing-calculation').html(html);
            }
            
            $('#skylearn_auto_calculate_price').change(togglePriceFields);
            $('input[name="skylearn_bundle_products[]"], #skylearn_bundle_discount, #skylearn_bundle_discount_type, #skylearn_bundle_price').change(updatePricingPreview);
            
            togglePriceFields();
            updatePricingPreview();
        });
        </script>
        <?php
    }
    
    /**
     * Bundle courses meta box
     */
    public function bundle_courses_meta_box($post) {
        $bundle_courses = get_post_meta($post->ID, '_skylearn_bundle_courses', true) ?: array();
        $unlock_behavior = get_post_meta($post->ID, '_skylearn_unlock_behavior', true) ?: 'all_at_once';
        
        // Get available courses from LMS
        $lms_manager = skylearn_billing_pro_lms_manager();
        $available_courses = $lms_manager->get_available_courses();
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="skylearn_unlock_behavior"><?php esc_html_e('Unlock Behavior', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_unlock_behavior" id="skylearn_unlock_behavior">
                        <option value="all_at_once" <?php selected($unlock_behavior, 'all_at_once'); ?>><?php esc_html_e('All at Once', 'skylearn-billing-pro'); ?></option>
                        <option value="sequential" <?php selected($unlock_behavior, 'sequential'); ?>><?php esc_html_e('Sequential', 'skylearn-billing-pro'); ?></option>
                        <option value="on_demand" <?php selected($unlock_behavior, 'on_demand'); ?>><?php esc_html_e('On Demand', 'skylearn-billing-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('How should courses be unlocked for users?', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Bundle Courses', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <div id="skylearn-bundle-courses">
                        <?php if (!empty($available_courses)): ?>
                            <?php foreach ($available_courses as $course_id => $course_title): ?>
                                <label style="display: block; margin-bottom: 5px;">
                                    <input type="checkbox" name="skylearn_bundle_courses[]" value="<?php echo esc_attr($course_id); ?>" 
                                           <?php checked(in_array($course_id, $bundle_courses)); ?> />
                                    <?php echo esc_html($course_title); ?>
                                </label>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p><?php esc_html_e('No courses available. Configure LMS integration first.', 'skylearn-billing-pro'); ?></p>
                        <?php endif; ?>
                    </div>
                    <p class="description"><?php esc_html_e('Select additional courses that this bundle should unlock.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Save bundle meta data
     */
    public function save_bundle_meta($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['skylearn_bundle_meta_nonce']) || !wp_verify_nonce($_POST['skylearn_bundle_meta_nonce'], 'skylearn_bundle_meta_nonce')) {
            return;
        }
        
        // Check if user has permission to edit
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (get_post_type($post_id) !== self::POST_TYPE) {
            return;
        }
        
        // Save bundle settings
        $fields = array(
            'skylearn_bundle_type' => 'sanitize_text_field',
            'skylearn_bundle_price' => 'floatval',
            'skylearn_bundle_discount' => 'floatval',
            'skylearn_bundle_discount_type' => 'sanitize_text_field',
            'skylearn_bundle_status' => 'sanitize_text_field',
            'skylearn_auto_calculate_price' => 'sanitize_text_field',
            'skylearn_unlock_behavior' => 'sanitize_text_field'
        );
        
        foreach ($fields as $field => $sanitize_callback) {
            if (isset($_POST[$field])) {
                $value = $sanitize_callback($_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            } else {
                delete_post_meta($post_id, '_' . $field);
            }
        }
        
        // Save array fields
        $array_fields = array(
            'skylearn_bundle_products',
            'skylearn_bundle_courses'
        );
        
        foreach ($array_fields as $field) {
            if (isset($_POST[$field]) && is_array($_POST[$field])) {
                $value = array_map('sanitize_text_field', $_POST[$field]);
                update_post_meta($post_id, '_' . $field, $value);
            } else {
                delete_post_meta($post_id, '_' . $field);
            }
        }
        
        // Fire action hook for extensibility
        do_action('skylearn_billing_bundle_saved', $post_id, $_POST);
    }
    
    /**
     * Update bundle when product is saved
     */
    public function update_bundle_on_product_save($product_id, $post_data) {
        // Find bundles that contain this product
        $bundles = get_posts(array(
            'post_type' => self::POST_TYPE,
            'meta_query' => array(
                array(
                    'key' => '_skylearn_bundle_products',
                    'value' => '"' . $product_id . '"',
                    'compare' => 'LIKE'
                )
            ),
            'posts_per_page' => -1
        ));
        
        foreach ($bundles as $bundle) {
            // Recalculate bundle pricing if auto-calculate is enabled
            $auto_calculate = get_post_meta($bundle->ID, '_skylearn_auto_calculate_price', true);
            if ($auto_calculate) {
                $this->recalculate_bundle_price($bundle->ID);
            }
        }
    }
    
    /**
     * Recalculate bundle price based on products
     */
    public function recalculate_bundle_price($bundle_id) {
        $bundle_products = get_post_meta($bundle_id, '_skylearn_bundle_products', true) ?: array();
        $discount = get_post_meta($bundle_id, '_skylearn_bundle_discount', true) ?: 0;
        $discount_type = get_post_meta($bundle_id, '_skylearn_bundle_discount_type', true) ?: 'percentage';
        
        $total_price = 0;
        $product_manager = skylearn_billing_pro_product_manager();
        
        foreach ($bundle_products as $product_id) {
            $product = $product_manager->get_product($product_id);
            if ($product) {
                $price = $product['sale_price'] ?: $product['price'];
                $total_price += floatval($price);
            }
        }
        
        // Apply discount
        if ($discount > 0) {
            if ($discount_type === 'percentage') {
                $final_price = $total_price - ($total_price * $discount / 100);
            } else {
                $final_price = $total_price - $discount;
            }
        } else {
            $final_price = $total_price;
        }
        
        update_post_meta($bundle_id, '_skylearn_calculated_price', $final_price);
        
        return $final_price;
    }
    
    /**
     * Get bundle by ID
     */
    public function get_bundle($bundle_id) {
        $bundle = get_post($bundle_id);
        
        if (!$bundle || $bundle->post_type !== self::POST_TYPE) {
            return false;
        }
        
        $bundle_data = array(
            'id' => $bundle->ID,
            'title' => $bundle->post_title,
            'description' => $bundle->post_content,
            'type' => get_post_meta($bundle->ID, '_skylearn_bundle_type', true),
            'products' => get_post_meta($bundle->ID, '_skylearn_bundle_products', true),
            'courses' => get_post_meta($bundle->ID, '_skylearn_bundle_courses', true),
            'price' => get_post_meta($bundle->ID, '_skylearn_bundle_price', true),
            'discount' => get_post_meta($bundle->ID, '_skylearn_bundle_discount', true),
            'discount_type' => get_post_meta($bundle->ID, '_skylearn_bundle_discount_type', true),
            'status' => get_post_meta($bundle->ID, '_skylearn_bundle_status', true),
            'auto_calculate_price' => get_post_meta($bundle->ID, '_skylearn_auto_calculate_price', true),
            'unlock_behavior' => get_post_meta($bundle->ID, '_skylearn_unlock_behavior', true),
            'calculated_price' => get_post_meta($bundle->ID, '_skylearn_calculated_price', true)
        );
        
        return apply_filters('skylearn_billing_get_bundle', $bundle_data, $bundle);
    }
    
    /**
     * Get all courses unlocked by a bundle
     */
    public function get_bundle_courses($bundle_id) {
        $bundle = $this->get_bundle($bundle_id);
        if (!$bundle) {
            return array();
        }
        
        $all_courses = array();
        
        // Add direct bundle courses
        $bundle_courses = $bundle['courses'] ?: array();
        $all_courses = array_merge($all_courses, $bundle_courses);
        
        // Add courses from bundle products
        $product_manager = skylearn_billing_pro_product_manager();
        $bundle_products = $bundle['products'] ?: array();
        
        foreach ($bundle_products as $product_id) {
            $product = $product_manager->get_product($product_id);
            if ($product && !empty($product['course_mappings'])) {
                $all_courses = array_merge($all_courses, $product['course_mappings']);
            }
        }
        
        // Remove duplicates
        $all_courses = array_unique($all_courses);
        
        return apply_filters('skylearn_billing_bundle_courses', $all_courses, $bundle_id);
    }
    
    /**
     * Check if user has access to bundle
     */
    public function user_has_bundle_access($user_id, $bundle_id) {
        $user_bundles = get_user_meta($user_id, '_skylearn_purchased_bundles', true) ?: array();
        return in_array($bundle_id, $user_bundles);
    }
    
    /**
     * Grant bundle access to user
     */
    public function grant_bundle_access($user_id, $bundle_id) {
        $user_bundles = get_user_meta($user_id, '_skylearn_purchased_bundles', true) ?: array();
        
        if (!in_array($bundle_id, $user_bundles)) {
            $user_bundles[] = $bundle_id;
            update_user_meta($user_id, '_skylearn_purchased_bundles', $user_bundles);
            
            // Enroll user in bundle courses
            $this->enroll_user_in_bundle_courses($user_id, $bundle_id);
            
            do_action('skylearn_billing_bundle_access_granted', $user_id, $bundle_id);
        }
    }
    
    /**
     * Enroll user in bundle courses
     */
    private function enroll_user_in_bundle_courses($user_id, $bundle_id) {
        $bundle_courses = $this->get_bundle_courses($bundle_id);
        $bundle = $this->get_bundle($bundle_id);
        
        if (empty($bundle_courses)) {
            return;
        }
        
        $lms_manager = skylearn_billing_pro_lms_manager();
        $unlock_behavior = $bundle['unlock_behavior'] ?: 'all_at_once';
        
        switch ($unlock_behavior) {
            case 'all_at_once':
                foreach ($bundle_courses as $course_id) {
                    $lms_manager->enroll_user($user_id, $course_id);
                }
                break;
                
            case 'sequential':
                // Only enroll in first course, others will be unlocked as they complete courses
                if (!empty($bundle_courses)) {
                    $lms_manager->enroll_user($user_id, $bundle_courses[0]);
                    update_user_meta($user_id, '_skylearn_bundle_' . $bundle_id . '_progress', array($bundle_courses[0]));
                }
                break;
                
            case 'on_demand':
                // Don't enroll automatically, wait for user action
                update_user_meta($user_id, '_skylearn_bundle_' . $bundle_id . '_available_courses', $bundle_courses);
                break;
        }
    }
}

/**
 * Get bundle manager instance
 */
function skylearn_billing_pro_bundle_manager() {
    return SkyLearn_Billing_Pro_Bundle_Manager::instance();
}