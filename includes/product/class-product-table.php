<?php
/**
 * Product Table class for handling product listing and editing UI
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

// Include WP_List_Table if not loaded
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Product Table class extending WP_List_Table
 */
class SkyLearn_Billing_Pro_Product_Table extends WP_List_Table {
    
    /**
     * Product manager instance
     */
    private $product_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct(array(
            'singular' => 'product',
            'plural'   => 'products',
            'ajax'     => false
        ));
        
        $this->product_manager = skylearn_billing_pro_product_manager();
    }
    
    /**
     * Get table columns
     */
    public function get_columns() {
        return array(
            'cb'          => '<input type="checkbox" />',
            'title'       => __('Product', 'skylearn-billing-pro'),
            'price'       => __('Price', 'skylearn-billing-pro'),
            'type'        => __('Type', 'skylearn-billing-pro'),
            'status'      => __('Status', 'skylearn-billing-pro'),
            'courses'     => __('Courses', 'skylearn-billing-pro'),
            'date'        => __('Date', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Get sortable columns
     */
    public function get_sortable_columns() {
        return array(
            'title' => array('title', false),
            'price' => array('price', false),
            'date'  => array('date', false)
        );
    }
    
    /**
     * Get bulk actions
     */
    public function get_bulk_actions() {
        return array(
            'delete'        => __('Delete', 'skylearn-billing-pro'),
            'activate'      => __('Activate', 'skylearn-billing-pro'),
            'deactivate'    => __('Deactivate', 'skylearn-billing-pro'),
            'export'        => __('Export', 'skylearn-billing-pro')
        );
    }
    
    /**
     * Prepare table items
     */
    public function prepare_items() {
        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        // Handle search
        $search = isset($_REQUEST['s']) ? sanitize_text_field($_REQUEST['s']) : '';
        
        // Handle filters
        $status_filter = isset($_REQUEST['status']) ? sanitize_text_field($_REQUEST['status']) : '';
        $type_filter = isset($_REQUEST['type']) ? sanitize_text_field($_REQUEST['type']) : '';
        
        // Build query args
        $args = array(
            'posts_per_page' => $per_page,
            'offset' => ($current_page - 1) * $per_page,
            'orderby' => isset($_REQUEST['orderby']) ? sanitize_text_field($_REQUEST['orderby']) : 'date',
            'order' => isset($_REQUEST['order']) ? sanitize_text_field($_REQUEST['order']) : 'DESC'
        );
        
        // Add search to query
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Add meta query for filters
        if (!empty($status_filter) || !empty($type_filter)) {
            $args['meta_query'] = array();
            
            if (!empty($status_filter)) {
                $args['meta_query'][] = array(
                    'key' => '_skylearn_product_status',
                    'value' => $status_filter,
                    'compare' => '='
                );
            }
            
            if (!empty($type_filter)) {
                $args['meta_query'][] = array(
                    'key' => '_skylearn_billing_type',
                    'value' => $type_filter,
                    'compare' => '='
                );
            }
        }
        
        // Get products
        $products = $this->product_manager->get_products($args);
        
        // Get total products for pagination
        $total_args = $args;
        $total_args['posts_per_page'] = -1;
        unset($total_args['offset']);
        $total_products = count($this->product_manager->get_products($total_args));
        
        // Set table data
        $this->items = $products;
        
        // Set pagination
        $this->set_pagination_args(array(
            'total_items' => $total_products,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_products / $per_page)
        ));
    }
    
    /**
     * Column default handler
     */
    public function column_default($item, $column_name) {
        switch ($column_name) {
            case 'price':
                return $this->column_price($item);
            case 'type':
                return $this->column_type($item);
            case 'status':
                return $this->column_status($item);
            case 'courses':
                return $this->column_courses($item);
            case 'date':
                return $this->column_date($item);
            default:
                return '';
        }
    }
    
    /**
     * Checkbox column
     */
    public function column_cb($item) {
        return sprintf('<input type="checkbox" name="product[]" value="%s" />', esc_attr($item['id']));
    }
    
    /**
     * Title column
     */
    public function column_title($item) {
        $edit_url = admin_url('admin.php?page=skylearn-billing-pro-products&action=edit&product=' . $item['id']);
        $duplicate_url = wp_nonce_url(
            admin_url('admin.php?page=skylearn-billing-pro-products&action=duplicate&product=' . $item['id']),
            'duplicate_product_' . $item['id']
        );
        $delete_url = wp_nonce_url(
            admin_url('admin.php?page=skylearn-billing-pro-products&action=delete&product=' . $item['id']),
            'delete_product_' . $item['id']
        );
        
        $title = sprintf('<strong><a href="%s">%s</a></strong>', esc_url($edit_url), esc_html($item['title']));
        
        if ($item['featured']) {
            $title .= ' <span class="dashicons dashicons-star-filled" style="color: #ffb900;" title="' . esc_attr__('Featured Product', 'skylearn-billing-pro') . '"></span>';
        }
        
        $actions = array(
            'edit' => sprintf('<a href="%s">%s</a>', esc_url($edit_url), __('Edit', 'skylearn-billing-pro')),
            'duplicate' => sprintf('<a href="%s">%s</a>', esc_url($duplicate_url), __('Duplicate', 'skylearn-billing-pro')),
            'delete' => sprintf('<a href="%s" onclick="return confirm(\'%s\')">%s</a>', 
                esc_url($delete_url), 
                esc_attr__('Are you sure you want to delete this product?', 'skylearn-billing-pro'),
                __('Delete', 'skylearn-billing-pro')
            )
        );
        
        return $title . $this->row_actions($actions);
    }
    
    /**
     * Price column
     */
    public function column_price($item) {
        $currency = $item['currency'] ?: 'USD';
        $price = $item['price'];
        $sale_price = $item['sale_price'];
        
        if (empty($price)) {
            return '<span class="na">&mdash;</span>';
        }
        
        $price_html = '<span class="skylearn-price">';
        
        if (!empty($sale_price) && $sale_price < $price) {
            $price_html .= '<del>' . esc_html($currency . ' ' . number_format($price, 2)) . '</del> ';
            $price_html .= '<ins>' . esc_html($currency . ' ' . number_format($sale_price, 2)) . '</ins>';
        } else {
            $price_html .= esc_html($currency . ' ' . number_format($price, 2));
        }
        
        $price_html .= '</span>';
        
        return $price_html;
    }
    
    /**
     * Type column
     */
    public function column_type($item) {
        $type = $item['billing_type'] ?: 'one_time';
        
        if ($type === 'subscription') {
            $period = $item['subscription_period'] ?: 'monthly';
            return sprintf(
                '<span class="skylearn-type subscription">%s</span><br><small>%s</small>',
                __('Subscription', 'skylearn-billing-pro'),
                ucfirst($period)
            );
        }
        
        return '<span class="skylearn-type one-time">' . __('One Time', 'skylearn-billing-pro') . '</span>';
    }
    
    /**
     * Status column
     */
    public function column_status($item) {
        $status = $item['status'] ?: 'active';
        $visibility = $item['visibility'] ?: 'visible';
        
        $status_class = $status === 'active' ? 'active' : 'inactive';
        $status_text = $status === 'active' ? __('Active', 'skylearn-billing-pro') : __('Inactive', 'skylearn-billing-pro');
        
        $html = sprintf('<span class="skylearn-status %s">%s</span>', esc_attr($status_class), esc_html($status_text));
        
        if ($visibility === 'hidden') {
            $html .= '<br><small class="skylearn-visibility hidden">' . __('Hidden', 'skylearn-billing-pro') . '</small>';
        }
        
        return $html;
    }
    
    /**
     * Courses column
     */
    public function column_courses($item) {
        $course_mappings = $item['course_mappings'] ?: array();
        
        if (empty($course_mappings)) {
            return '<span class="na">&mdash;</span>';
        }
        
        $count = count($course_mappings);
        return sprintf(
            '<span class="skylearn-courses">%d %s</span>',
            $count,
            _n('course', 'courses', $count, 'skylearn-billing-pro')
        );
    }
    
    /**
     * Date column
     */
    public function column_date($item) {
        $post = get_post($item['id']);
        if (!$post) {
            return '<span class="na">&mdash;</span>';
        }
        
        $date = get_the_date('Y/m/d', $post);
        $time = get_the_time('g:i a', $post);
        
        return sprintf('%s<br><small>%s</small>', esc_html($date), esc_html($time));
    }
    
    /**
     * Extra table navigation
     */
    public function extra_tablenav($which) {
        if ($which === 'top') {
            echo '<div class="alignleft actions">';
            
            // Status filter
            echo '<select name="status">';
            echo '<option value="">' . __('All Statuses', 'skylearn-billing-pro') . '</option>';
            echo '<option value="active"' . selected($_REQUEST['status'] ?? '', 'active', false) . '>' . __('Active', 'skylearn-billing-pro') . '</option>';
            echo '<option value="inactive"' . selected($_REQUEST['status'] ?? '', 'inactive', false) . '>' . __('Inactive', 'skylearn-billing-pro') . '</option>';
            echo '</select>';
            
            // Type filter
            echo '<select name="type">';
            echo '<option value="">' . __('All Types', 'skylearn-billing-pro') . '</option>';
            echo '<option value="one_time"' . selected($_REQUEST['type'] ?? '', 'one_time', false) . '>' . __('One Time', 'skylearn-billing-pro') . '</option>';
            echo '<option value="subscription"' . selected($_REQUEST['type'] ?? '', 'subscription', false) . '>' . __('Subscription', 'skylearn-billing-pro') . '</option>';
            echo '</select>';
            
            submit_button(__('Filter', 'skylearn-billing-pro'), 'secondary', 'filter_action', false);
            
            echo '</div>';
        }
    }
    
    /**
     * Handle bulk actions
     */
    public function process_bulk_action() {
        $action = $this->current_action();
        
        if (!$action) {
            return;
        }
        
        $product_ids = isset($_REQUEST['product']) ? array_map('intval', $_REQUEST['product']) : array();
        
        if (empty($product_ids)) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_REQUEST['_wpnonce'], 'bulk-products')) {
            wp_die(__('Invalid nonce', 'skylearn-billing-pro'));
        }
        
        $processed = 0;
        
        switch ($action) {
            case 'delete':
                foreach ($product_ids as $product_id) {
                    if ($this->product_manager->delete_product($product_id)) {
                        $processed++;
                    }
                }
                $message = sprintf(_n('%d product deleted.', '%d products deleted.', $processed, 'skylearn-billing-pro'), $processed);
                break;
                
            case 'activate':
                foreach ($product_ids as $product_id) {
                    update_post_meta($product_id, '_skylearn_product_status', 'active');
                    $processed++;
                }
                $message = sprintf(_n('%d product activated.', '%d products activated.', $processed, 'skylearn-billing-pro'), $processed);
                break;
                
            case 'deactivate':
                foreach ($product_ids as $product_id) {
                    update_post_meta($product_id, '_skylearn_product_status', 'inactive');
                    $processed++;
                }
                $message = sprintf(_n('%d product deactivated.', '%d products deactivated.', $processed, 'skylearn-billing-pro'), $processed);
                break;
                
            case 'export':
                $this->export_products($product_ids);
                return; // Export doesn't need a message as it triggers download
                
            default:
                $message = '';
        }
        
        if (!empty($message)) {
            add_action('admin_notices', function() use ($message) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
            });
        }
    }
    
    /**
     * Export products to CSV
     */
    private function export_products($product_ids) {
        $filename = 'skylearn-products-' . date('Y-m-d-H-i-s') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, array(
            'ID', 'Title', 'Description', 'Price', 'Sale Price', 'Currency',
            'Billing Type', 'Subscription Period', 'Status', 'Visibility',
            'Featured', 'Access Triggers', 'Tier Restrictions', 'Course Mappings'
        ));
        
        // Export data
        foreach ($product_ids as $product_id) {
            $product = $this->product_manager->get_product($product_id);
            if ($product) {
                fputcsv($output, array(
                    $product['id'],
                    $product['title'],
                    $product['description'],
                    $product['price'],
                    $product['sale_price'],
                    $product['currency'],
                    $product['billing_type'],
                    $product['subscription_period'],
                    $product['status'],
                    $product['visibility'],
                    $product['featured'] ? 'Yes' : 'No',
                    implode(';', $product['access_triggers'] ?: array()),
                    implode(';', $product['tier_restrictions'] ?: array()),
                    implode(';', $product['course_mappings'] ?: array())
                ));
            }
        }
        
        fclose($output);
        exit;
    }
}

/**
 * Product Table UI class for admin pages
 */
class SkyLearn_Billing_Pro_Product_UI {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Product_UI
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Product_UI
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
        add_action('admin_init', array($this, 'handle_actions'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Handle admin actions
     */
    public function handle_actions() {
        if (!isset($_GET['page']) || $_GET['page'] !== 'skylearn-billing-pro-products') {
            return;
        }
        
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : '';
        $product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;
        
        switch ($action) {
            case 'delete':
                $this->handle_delete($product_id);
                break;
                
            case 'duplicate':
                $this->handle_duplicate($product_id);
                break;
        }
    }
    
    /**
     * Handle product deletion
     */
    private function handle_delete($product_id) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'delete_product_' . $product_id)) {
            wp_die(__('Invalid nonce', 'skylearn-billing-pro'));
        }
        
        if (!current_user_can('delete_posts')) {
            wp_die(__('You do not have permission to delete products', 'skylearn-billing-pro'));
        }
        
        $product_manager = skylearn_billing_pro_product_manager();
        
        if ($product_manager->delete_product($product_id)) {
            wp_redirect(admin_url('admin.php?page=skylearn-billing-pro-products&deleted=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=skylearn-billing-pro-products&error=delete'));
        }
        
        exit;
    }
    
    /**
     * Handle product duplication
     */
    private function handle_duplicate($product_id) {
        if (!wp_verify_nonce($_GET['_wpnonce'] ?? '', 'duplicate_product_' . $product_id)) {
            wp_die(__('Invalid nonce', 'skylearn-billing-pro'));
        }
        
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to duplicate products', 'skylearn-billing-pro'));
        }
        
        $product_manager = skylearn_billing_pro_product_manager();
        $original_product = $product_manager->get_product($product_id);
        
        if (!$original_product) {
            wp_redirect(admin_url('admin.php?page=skylearn-billing-pro-products&error=not_found'));
            exit;
        }
        
        // Create duplicate product data
        $duplicate_data = $original_product;
        $duplicate_data['title'] = $original_product['title'] . ' (Copy)';
        unset($duplicate_data['id']); // Remove ID so new product is created
        
        $new_product_id = $product_manager->create_product($duplicate_data);
        
        if (!is_wp_error($new_product_id)) {
            wp_redirect(admin_url('admin.php?page=skylearn-billing-pro-products&action=edit&product=' . $new_product_id . '&duplicated=1'));
        } else {
            wp_redirect(admin_url('admin.php?page=skylearn-billing-pro-products&error=duplicate'));
        }
        
        exit;
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'skylearn-billing-pro-products') === false) {
            return;
        }
        
        wp_enqueue_style('skylearn-billing-pro-products', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/admin.css', array(), SKYLEARN_BILLING_PRO_VERSION);
        wp_enqueue_script('skylearn-billing-pro-products', SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SKYLEARN_BILLING_PRO_VERSION, true);
        
        wp_localize_script('skylearn-billing-pro-products', 'skylernProducts', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_products_nonce'),
            'strings' => array(
                'confirm_delete' => __('Are you sure you want to delete this product?', 'skylearn-billing-pro'),
                'bulk_delete_confirm' => __('Are you sure you want to delete the selected products?', 'skylearn-billing-pro')
            )
        ));
    }
    
    /**
     * Render products list page
     */
    public function render_products_list() {
        $table = new SkyLearn_Billing_Pro_Product_Table();
        $table->process_bulk_action();
        $table->prepare_items();
        
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/products-list.php';
    }
    
    /**
     * Render product edit page
     */
    public function render_product_edit() {
        $product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;
        $product_manager = skylearn_billing_pro_product_manager();
        
        if ($product_id) {
            $product = $product_manager->get_product($product_id);
            if (!$product) {
                wp_die(__('Product not found', 'skylearn-billing-pro'));
            }
        } else {
            $product = null;
        }
        
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/admin/product-edit.php';
    }
}

/**
 * Get product UI instance
 */
function skylearn_billing_pro_product_ui() {
    return SkyLearn_Billing_Pro_Product_UI::instance();
}