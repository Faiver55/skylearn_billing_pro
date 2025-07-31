<?php
/**
 * Product Manager class for handling product CRUD and management
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
 * Product Manager class
 */
class SkyLearn_Billing_Pro_Product_Manager {
    
    /**
     * Post type slug for products
     */
    const POST_TYPE = 'skylearn_product';
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Product_Manager
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Product_Manager
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
        add_action('save_post', array($this, 'save_product_meta'));
        add_filter('post_row_actions', array($this, 'modify_row_actions'), 10, 2);
        add_action('admin_notices', array($this, 'product_limit_notice'));
    }
    
    /**
     * Register custom post type for products
     */
    public function register_post_type() {
        $labels = array(
            'name'               => _x('Products', 'post type general name', 'skylearn-billing-pro'),
            'singular_name'      => _x('Product', 'post type singular name', 'skylearn-billing-pro'),
            'menu_name'          => _x('Products', 'admin menu', 'skylearn-billing-pro'),
            'name_admin_bar'     => _x('Product', 'add new on admin bar', 'skylearn-billing-pro'),
            'add_new'            => _x('Add New', 'product', 'skylearn-billing-pro'),
            'add_new_item'       => __('Add New Product', 'skylearn-billing-pro'),
            'new_item'           => __('New Product', 'skylearn-billing-pro'),
            'edit_item'          => __('Edit Product', 'skylearn-billing-pro'),
            'view_item'          => __('View Product', 'skylearn-billing-pro'),
            'all_items'          => __('All Products', 'skylearn-billing-pro'),
            'search_items'       => __('Search Products', 'skylearn-billing-pro'),
            'parent_item_colon'  => __('Parent Products:', 'skylearn-billing-pro'),
            'not_found'          => __('No products found.', 'skylearn-billing-pro'),
            'not_found_in_trash' => __('No products found in Trash.', 'skylearn-billing-pro')
        );

        $args = array(
            'labels'             => $labels,
            'description'        => __('Products for Skylearn Billing', 'skylearn-billing-pro'),
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => false, // We'll handle UI ourselves
            'show_in_menu'       => false,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'skylearn-product'),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => null,
            'supports'           => array('title', 'editor', 'thumbnail', 'custom-fields')
        );

        register_post_type(self::POST_TYPE, $args);
    }
    
    /**
     * Add meta boxes for product editing
     */
    public function add_meta_boxes() {
        add_meta_box(
            'skylearn_product_settings',
            __('Product Settings', 'skylearn-billing-pro'),
            array($this, 'product_settings_meta_box'),
            self::POST_TYPE,
            'normal',
            'high'
        );
        
        add_meta_box(
            'skylearn_product_pricing',
            __('Pricing', 'skylearn-billing-pro'),
            array($this, 'product_pricing_meta_box'),
            self::POST_TYPE,
            'side',
            'high'
        );
        
        add_meta_box(
            'skylearn_product_lms',
            __('LMS Integration', 'skylearn-billing-pro'),
            array($this, 'product_lms_meta_box'),
            self::POST_TYPE,
            'normal',
            'default'
        );
    }
    
    /**
     * Product settings meta box
     */
    public function product_settings_meta_box($post) {
        wp_nonce_field('skylearn_product_meta_nonce', 'skylearn_product_meta_nonce');
        
        $product_status = get_post_meta($post->ID, '_skylearn_product_status', true) ?: 'active';
        $product_visibility = get_post_meta($post->ID, '_skylearn_product_visibility', true) ?: 'visible';
        $product_featured = get_post_meta($post->ID, '_skylearn_product_featured', true);
        $access_triggers = get_post_meta($post->ID, '_skylearn_access_triggers', true) ?: array();
        $tier_restrictions = get_post_meta($post->ID, '_skylearn_tier_restrictions', true) ?: array();
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="skylearn_product_status"><?php esc_html_e('Product Status', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_product_status" id="skylearn_product_status">
                        <option value="active" <?php selected($product_status, 'active'); ?>><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></option>
                        <option value="inactive" <?php selected($product_status, 'inactive'); ?>><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Set product status. Only active products are available for purchase.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="skylearn_product_visibility"><?php esc_html_e('Visibility', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_product_visibility" id="skylearn_product_visibility">
                        <option value="visible" <?php selected($product_visibility, 'visible'); ?>><?php esc_html_e('Visible', 'skylearn-billing-pro'); ?></option>
                        <option value="hidden" <?php selected($product_visibility, 'hidden'); ?>><?php esc_html_e('Hidden', 'skylearn-billing-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Control product visibility in listings and catalogs.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="skylearn_product_featured"><?php esc_html_e('Featured Product', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="skylearn_product_featured" id="skylearn_product_featured" value="1" <?php checked($product_featured, '1'); ?> />
                        <?php esc_html_e('Mark as featured product', 'skylearn-billing-pro'); ?>
                    </label>
                    <p class="description"><?php esc_html_e('Featured products appear prominently in listings.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
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
                        $checked = in_array($key, $access_triggers) ? 'checked' : '';
                        echo '<label style="display: block; margin-bottom: 5px;">';
                        echo '<input type="checkbox" name="skylearn_access_triggers[]" value="' . esc_attr($key) . '" ' . $checked . ' />';
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
                        $checked = in_array($key, $tier_restrictions) ? 'checked' : '';
                        echo '<label style="display: block; margin-bottom: 5px;">';
                        echo '<input type="checkbox" name="skylearn_tier_restrictions[]" value="' . esc_attr($key) . '" ' . $checked . ' />';
                        echo ' ' . esc_html($label);
                        echo '</label>';
                    }
                    ?>
                    <p class="description"><?php esc_html_e('Select which tiers can access this product.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }
    
    /**
     * Product pricing meta box
     */
    public function product_pricing_meta_box($post) {
        $price = get_post_meta($post->ID, '_skylearn_product_price', true);
        $sale_price = get_post_meta($post->ID, '_skylearn_product_sale_price', true);
        $currency = get_post_meta($post->ID, '_skylearn_product_currency', true) ?: 'USD';
        $billing_type = get_post_meta($post->ID, '_skylearn_billing_type', true) ?: 'one_time';
        $subscription_period = get_post_meta($post->ID, '_skylearn_subscription_period', true) ?: 'monthly';
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="skylearn_product_price"><?php esc_html_e('Price', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <input type="number" name="skylearn_product_price" id="skylearn_product_price" value="<?php echo esc_attr($price); ?>" step="0.01" min="0" class="small-text" />
                    <p class="description"><?php esc_html_e('Regular price for this product.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="skylearn_product_sale_price"><?php esc_html_e('Sale Price', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <input type="number" name="skylearn_product_sale_price" id="skylearn_product_sale_price" value="<?php echo esc_attr($sale_price); ?>" step="0.01" min="0" class="small-text" />
                    <p class="description"><?php esc_html_e('Optional sale price. Leave empty if not on sale.', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="skylearn_product_currency"><?php esc_html_e('Currency', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_product_currency" id="skylearn_product_currency">
                        <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
                        <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
                        <option value="GBP" <?php selected($currency, 'GBP'); ?>>GBP</option>
                        <option value="CAD" <?php selected($currency, 'CAD'); ?>>CAD</option>
                        <option value="AUD" <?php selected($currency, 'AUD'); ?>>AUD</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="skylearn_billing_type"><?php esc_html_e('Billing Type', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_billing_type" id="skylearn_billing_type">
                        <option value="one_time" <?php selected($billing_type, 'one_time'); ?>><?php esc_html_e('One Time', 'skylearn-billing-pro'); ?></option>
                        <option value="subscription" <?php selected($billing_type, 'subscription'); ?>><?php esc_html_e('Subscription', 'skylearn-billing-pro'); ?></option>
                    </select>
                </td>
            </tr>
            <tr class="subscription-fields" style="<?php echo ($billing_type !== 'subscription') ? 'display: none;' : ''; ?>">
                <th scope="row">
                    <label for="skylearn_subscription_period"><?php esc_html_e('Subscription Period', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_subscription_period" id="skylearn_subscription_period">
                        <option value="weekly" <?php selected($subscription_period, 'weekly'); ?>><?php esc_html_e('Weekly', 'skylearn-billing-pro'); ?></option>
                        <option value="monthly" <?php selected($subscription_period, 'monthly'); ?>><?php esc_html_e('Monthly', 'skylearn-billing-pro'); ?></option>
                        <option value="quarterly" <?php selected($subscription_period, 'quarterly'); ?>><?php esc_html_e('Quarterly', 'skylearn-billing-pro'); ?></option>
                        <option value="yearly" <?php selected($subscription_period, 'yearly'); ?>><?php esc_html_e('Yearly', 'skylearn-billing-pro'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            $('#skylearn_billing_type').change(function() {
                if ($(this).val() === 'subscription') {
                    $('.subscription-fields').show();
                } else {
                    $('.subscription-fields').hide();
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * Product LMS integration meta box
     */
    public function product_lms_meta_box($post) {
        $course_mappings = get_post_meta($post->ID, '_skylearn_course_mappings', true) ?: array();
        $enrollment_behavior = get_post_meta($post->ID, '_skylearn_enrollment_behavior', true) ?: 'immediate';
        
        // Get available courses from LMS
        $lms_manager = skylearn_billing_pro_lms_manager();
        $available_courses = $lms_manager->get_courses();
        
        ?>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label><?php esc_html_e('Course Mappings', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <div id="skylearn-course-mappings">
                        <?php if (!empty($course_mappings)): ?>
                            <?php foreach ($course_mappings as $index => $mapping): ?>
                                <div class="course-mapping-row" style="margin-bottom: 10px;">
                                    <select name="skylearn_course_mappings[]">
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
                    <label for="skylearn_enrollment_behavior"><?php esc_html_e('Enrollment Behavior', 'skylearn-billing-pro'); ?></label>
                </th>
                <td>
                    <select name="skylearn_enrollment_behavior" id="skylearn_enrollment_behavior">
                        <option value="immediate" <?php selected($enrollment_behavior, 'immediate'); ?>><?php esc_html_e('Immediate', 'skylearn-billing-pro'); ?></option>
                        <option value="delayed" <?php selected($enrollment_behavior, 'delayed'); ?>><?php esc_html_e('Delayed', 'skylearn-billing-pro'); ?></option>
                        <option value="manual" <?php selected($enrollment_behavior, 'manual'); ?>><?php esc_html_e('Manual', 'skylearn-billing-pro'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('When should users be enrolled in mapped courses?', 'skylearn-billing-pro'); ?></p>
                </td>
            </tr>
        </table>
        
        <script>
        jQuery(document).ready(function($) {
            // Template for new course mapping row
            var courseMappingTemplate = '<div class="course-mapping-row" style="margin-bottom: 10px;">' +
                '<select name="skylearn_course_mappings[]">' +
                '<option value=""><?php echo esc_js(__("Select a course...", "skylearn-billing-pro")); ?></option>' +
                <?php foreach ($available_courses as $course_id => $course_title): ?>
                '<option value="<?php echo esc_js($course_id); ?>"><?php echo esc_js($course_title); ?></option>' +
                <?php endforeach; ?>
                '</select>' +
                '<button type="button" class="button remove-course-mapping"><?php echo esc_js(__("Remove", "skylearn-billing-pro")); ?></button>' +
                '</div>';
            
            // Add course mapping
            $('#add-course-mapping').click(function() {
                $('#skylearn-course-mappings').append(courseMappingTemplate);
            });
            
            // Remove course mapping
            $(document).on('click', '.remove-course-mapping', function() {
                $(this).closest('.course-mapping-row').remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save product meta data
     */
    public function save_product_meta($post_id) {
        // Check if nonce is valid
        if (!isset($_POST['skylearn_product_meta_nonce']) || !wp_verify_nonce($_POST['skylearn_product_meta_nonce'], 'skylearn_product_meta_nonce')) {
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
        
        // Save product settings
        $fields = array(
            'skylearn_product_status' => 'sanitize_text_field',
            'skylearn_product_visibility' => 'sanitize_text_field',
            'skylearn_product_featured' => 'sanitize_text_field',
            'skylearn_product_price' => 'floatval',
            'skylearn_product_sale_price' => 'floatval',
            'skylearn_product_currency' => 'sanitize_text_field',
            'skylearn_billing_type' => 'sanitize_text_field',
            'skylearn_subscription_period' => 'sanitize_text_field',
            'skylearn_enrollment_behavior' => 'sanitize_text_field'
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
            'skylearn_access_triggers',
            'skylearn_tier_restrictions',
            'skylearn_course_mappings'
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
        do_action('skylearn_billing_product_saved', $post_id, $_POST);
    }
    
    /**
     * Modify row actions for products in admin
     */
    public function modify_row_actions($actions, $post) {
        if ($post->post_type === self::POST_TYPE) {
            // Remove quick edit for products
            unset($actions['inline hide-if-no-js']);
            
            // Add custom actions
            $actions['duplicate'] = sprintf(
                '<a href="%s" aria-label="%s">%s</a>',
                wp_nonce_url(
                    admin_url('admin.php?page=skylearn-billing-pro-products&action=duplicate&product=' . $post->ID),
                    'duplicate_product_' . $post->ID
                ),
                esc_attr__('Duplicate this product', 'skylearn-billing-pro'),
                esc_html__('Duplicate', 'skylearn-billing-pro')
            );
        }
        
        return $actions;
    }
    
    /**
     * Check product limits for free tier
     */
    public function check_product_limit() {
        $licensing_manager = skylearn_billing_pro_licensing();
        
        if ($licensing_manager->get_current_tier() === 'free') {
            $product_count = wp_count_posts(self::POST_TYPE);
            $total_products = $product_count->publish + $product_count->draft + $product_count->private;
            
            return $total_products >= 5; // Free tier limit
        }
        
        return false;
    }
    
    /**
     * Display product limit notice
     */
    public function product_limit_notice() {
        if ($this->check_product_limit()) {
            $screen = get_current_screen();
            if ($screen && ($screen->post_type === self::POST_TYPE || strpos($screen->id, 'skylearn-billing-pro-products') !== false)) {
                echo '<div class="notice notice-warning is-dismissible">';
                echo '<p><strong>' . esc_html__('Skylearn Billing Pro:', 'skylearn-billing-pro') . '</strong> ';
                echo esc_html__('You have reached the maximum number of products (5) for the Free tier.', 'skylearn-billing-pro');
                echo ' <a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')) . '">' . esc_html__('Upgrade to Pro', 'skylearn-billing-pro') . '</a></p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Get product by ID
     */
    public function get_product($product_id) {
        $product = get_post($product_id);
        
        if (!$product || $product->post_type !== self::POST_TYPE) {
            return false;
        }
        
        $product_data = array(
            'id' => $product->ID,
            'title' => $product->post_title,
            'description' => $product->post_content,
            'status' => get_post_meta($product->ID, '_skylearn_product_status', true),
            'visibility' => get_post_meta($product->ID, '_skylearn_product_visibility', true),
            'featured' => get_post_meta($product->ID, '_skylearn_product_featured', true),
            'price' => get_post_meta($product->ID, '_skylearn_product_price', true),
            'sale_price' => get_post_meta($product->ID, '_skylearn_product_sale_price', true),
            'currency' => get_post_meta($product->ID, '_skylearn_product_currency', true),
            'billing_type' => get_post_meta($product->ID, '_skylearn_billing_type', true),
            'subscription_period' => get_post_meta($product->ID, '_skylearn_subscription_period', true),
            'access_triggers' => get_post_meta($product->ID, '_skylearn_access_triggers', true),
            'tier_restrictions' => get_post_meta($product->ID, '_skylearn_tier_restrictions', true),
            'course_mappings' => get_post_meta($product->ID, '_skylearn_course_mappings', true),
            'enrollment_behavior' => get_post_meta($product->ID, '_skylearn_enrollment_behavior', true)
        );
        
        return apply_filters('skylearn_billing_get_product', $product_data, $product);
    }
    
    /**
     * Create new product
     */
    public function create_product($product_data) {
        // Check product limit for free tier
        if ($this->check_product_limit()) {
            return new WP_Error('product_limit_exceeded', __('Product limit exceeded for Free tier', 'skylearn-billing-pro'));
        }
        
        $post_data = array(
            'post_title' => sanitize_text_field($product_data['title']),
            'post_content' => wp_kses_post($product_data['description']),
            'post_status' => 'publish',
            'post_type' => self::POST_TYPE
        );
        
        $product_id = wp_insert_post($post_data);
        
        if (is_wp_error($product_id)) {
            return $product_id;
        }
        
        // Save product meta
        $meta_fields = array(
            'skylearn_product_status', 'skylearn_product_visibility', 'skylearn_product_featured',
            'skylearn_product_price', 'skylearn_product_sale_price', 'skylearn_product_currency',
            'skylearn_billing_type', 'skylearn_subscription_period', 'skylearn_enrollment_behavior'
        );
        
        foreach ($meta_fields as $field) {
            if (isset($product_data[$field])) {
                update_post_meta($product_id, '_' . $field, $product_data[$field]);
            }
        }
        
        // Save array fields
        $array_fields = array('access_triggers', 'tier_restrictions', 'course_mappings');
        
        foreach ($array_fields as $field) {
            if (isset($product_data[$field]) && is_array($product_data[$field])) {
                update_post_meta($product_id, '_skylearn_' . $field, $product_data[$field]);
            }
        }
        
        do_action('skylearn_billing_product_created', $product_id, $product_data);
        
        return $product_id;
    }
    
    /**
     * Delete product
     */
    public function delete_product($product_id) {
        $product = get_post($product_id);
        
        if (!$product || $product->post_type !== self::POST_TYPE) {
            return false;
        }
        
        do_action('skylearn_billing_product_before_delete', $product_id);
        
        $deleted = wp_delete_post($product_id, true);
        
        if ($deleted) {
            do_action('skylearn_billing_product_deleted', $product_id);
        }
        
        return $deleted;
    }
    
    /**
     * Get products with filters
     */
    public function get_products($args = array()) {
        $defaults = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array()
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $products = get_posts($args);
        $product_data = array();
        
        foreach ($products as $product) {
            $product_data[] = $this->get_product($product->ID);
        }
        
        return apply_filters('skylearn_billing_get_products', $product_data, $args);
    }
}

/**
 * Get product manager instance
 */
function skylearn_billing_pro_product_manager() {
    return SkyLearn_Billing_Pro_Product_Manager::instance();
}