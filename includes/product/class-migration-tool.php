<?php
/**
 * Migration Tool class for product import/export
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
 * Migration Tool class
 */
class SkyLearn_Billing_Pro_Migration_Tool {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Migration_Tool
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Migration_Tool
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
        add_action('wp_ajax_skylearn_export_products', array($this, 'handle_export_ajax'));
        add_action('wp_ajax_skylearn_import_products', array($this, 'handle_import_ajax'));
        add_action('admin_init', array($this, 'handle_file_upload'));
    }
    
    /**
     * Export products to CSV
     */
    public function export_products_csv($product_ids = array()) {
        $product_manager = skylearn_billing_pro_product_manager();
        
        // If no specific products, export all
        if (empty($product_ids)) {
            $products = $product_manager->get_products();
        } else {
            $products = array();
            foreach ($product_ids as $product_id) {
                $product = $product_manager->get_product($product_id);
                if ($product) {
                    $products[] = $product;
                }
            }
        }
        
        $filename = 'skylearn-products-export-' . date('Y-m-d-H-i-s') . '.csv';
        
        // Set headers for file download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // CSV headers
        $headers = array(
            'ID',
            'Title',
            'Description',
            'Price',
            'Sale Price',
            'Currency',
            'Billing Type',
            'Subscription Period',
            'Status',
            'Visibility',
            'Featured',
            'Access Triggers',
            'Tier Restrictions',
            'Course Mappings',
            'Enrollment Behavior'
        );
        
        fputcsv($output, $headers);
        
        // Export data
        foreach ($products as $product) {
            $row = array(
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
                implode(';', $product['course_mappings'] ?: array()),
                $product['enrollment_behavior']
            );
            
            fputcsv($output, $row);
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Export products to JSON
     */
    public function export_products_json($product_ids = array()) {
        $product_manager = skylearn_billing_pro_product_manager();
        
        // If no specific products, export all
        if (empty($product_ids)) {
            $products = $product_manager->get_products();
        } else {
            $products = array();
            foreach ($product_ids as $product_id) {
                $product = $product_manager->get_product($product_id);
                if ($product) {
                    $products[] = $product;
                }
            }
        }
        
        $filename = 'skylearn-products-export-' . date('Y-m-d-H-i-s') . '.json';
        
        // Prepare export data
        $export_data = array(
            'version' => SKYLEARN_BILLING_PRO_VERSION,
            'export_date' => date('Y-m-d H:i:s'),
            'products' => $products
        );
        
        // Set headers for file download
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Import products from CSV
     */
    public function import_products_csv($file_path, $options = array()) {
        $defaults = array(
            'update_existing' => false,
            'skip_errors' => true,
            'import_courses' => true
        );
        
        $options = wp_parse_args($options, $defaults);
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Import file not found.', 'skylearn-billing-pro'));
        }
        
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('file_read_error', __('Unable to read import file.', 'skylearn-billing-pro'));
        }
        
        $product_manager = skylearn_billing_pro_product_manager();
        $results = array(
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array()
        );
        
        // Skip BOM if present
        $bom = fread($handle, 3);
        if ($bom !== chr(0xEF).chr(0xBB).chr(0xBF)) {
            rewind($handle);
        }
        
        // Read headers
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return new WP_Error('invalid_format', __('Invalid CSV format.', 'skylearn-billing-pro'));
        }
        
        // Map headers to fields
        $field_map = array(
            'ID' => 'id',
            'Title' => 'title',
            'Description' => 'description',
            'Price' => 'skylearn_product_price',
            'Sale Price' => 'skylearn_product_sale_price',
            'Currency' => 'skylearn_product_currency',
            'Billing Type' => 'skylearn_billing_type',
            'Subscription Period' => 'skylearn_subscription_period',
            'Status' => 'skylearn_product_status',
            'Visibility' => 'skylearn_product_visibility',
            'Featured' => 'skylearn_product_featured',
            'Access Triggers' => 'access_triggers',
            'Tier Restrictions' => 'tier_restrictions',
            'Course Mappings' => 'course_mappings',
            'Enrollment Behavior' => 'skylearn_enrollment_behavior'
        );
        
        $row_number = 1;
        
        while (($row = fgetcsv($handle)) !== false) {
            $row_number++;
            
            if (count($row) !== count($headers)) {
                if (!$options['skip_errors']) {
                    fclose($handle);
                    return new WP_Error('invalid_row', sprintf(__('Invalid row format at line %d.', 'skylearn-billing-pro'), $row_number));
                }
                $results['errors'][] = sprintf(__('Skipped line %d: Invalid format.', 'skylearn-billing-pro'), $row_number);
                $results['skipped']++;
                continue;
            }
            
            // Build product data from CSV row
            $product_data = array();
            
            for ($i = 0; $i < count($headers); $i++) {
                $header = $headers[$i];
                $value = $row[$i];
                
                if (isset($field_map[$header])) {
                    $field = $field_map[$header];
                    
                    // Handle special fields
                    switch ($field) {
                        case 'skylearn_product_featured':
                            $product_data[$field] = (strtolower($value) === 'yes' || $value === '1') ? '1' : '';
                            break;
                            
                        case 'access_triggers':
                        case 'tier_restrictions':
                        case 'course_mappings':
                            $product_data[$field] = !empty($value) ? explode(';', $value) : array();
                            break;
                            
                        case 'skylearn_product_price':
                        case 'skylearn_product_sale_price':
                            $product_data[$field] = !empty($value) ? floatval($value) : '';
                            break;
                            
                        default:
                            $product_data[$field] = $value;
                    }
                }
            }
            
            // Check if product exists (by ID or title)
            $existing_product_id = null;
            if (!empty($product_data['id'])) {
                $existing_product = $product_manager->get_product($product_data['id']);
                if ($existing_product) {
                    $existing_product_id = $product_data['id'];
                }
            }
            
            // If no ID match, check by title
            if (!$existing_product_id && !empty($product_data['title'])) {
                $existing_posts = get_posts(array(
                    'post_type' => SkyLearn_Billing_Pro_Product_Manager::POST_TYPE,
                    'title' => $product_data['title'],
                    'posts_per_page' => 1
                ));
                
                if (!empty($existing_posts)) {
                    $existing_product_id = $existing_posts[0]->ID;
                }
            }
            
            try {
                if ($existing_product_id && $options['update_existing']) {
                    // Update existing product
                    $product_data['id'] = $existing_product_id;
                    $this->update_product_from_import($existing_product_id, $product_data);
                    $results['updated']++;
                } elseif (!$existing_product_id) {
                    // Create new product
                    unset($product_data['id']); // Remove ID for new products
                    $new_product_id = $product_manager->create_product($product_data);
                    
                    if (is_wp_error($new_product_id)) {
                        if (!$options['skip_errors']) {
                            fclose($handle);
                            return $new_product_id;
                        }
                        $results['errors'][] = sprintf(__('Line %d: %s', 'skylearn-billing-pro'), $row_number, $new_product_id->get_error_message());
                        $results['skipped']++;
                    } else {
                        $results['imported']++;
                    }
                } else {
                    // Product exists but update not allowed
                    $results['skipped']++;
                }
            } catch (Exception $e) {
                if (!$options['skip_errors']) {
                    fclose($handle);
                    return new WP_Error('import_error', $e->getMessage());
                }
                $results['errors'][] = sprintf(__('Line %d: %s', 'skylearn-billing-pro'), $row_number, $e->getMessage());
                $results['skipped']++;
            }
        }
        
        fclose($handle);
        
        return $results;
    }
    
    /**
     * Import products from JSON
     */
    public function import_products_json($file_path, $options = array()) {
        $defaults = array(
            'update_existing' => false,
            'skip_errors' => true,
            'import_courses' => true
        );
        
        $options = wp_parse_args($options, $defaults);
        
        if (!file_exists($file_path)) {
            return new WP_Error('file_not_found', __('Import file not found.', 'skylearn-billing-pro'));
        }
        
        $json_content = file_get_contents($file_path);
        if (!$json_content) {
            return new WP_Error('file_read_error', __('Unable to read import file.', 'skylearn-billing-pro'));
        }
        
        $import_data = json_decode($json_content, true);
        if (!$import_data) {
            return new WP_Error('invalid_json', __('Invalid JSON format.', 'skylearn-billing-pro'));
        }
        
        if (!isset($import_data['products']) || !is_array($import_data['products'])) {
            return new WP_Error('invalid_format', __('Invalid import format. Missing products data.', 'skylearn-billing-pro'));
        }
        
        $product_manager = skylearn_billing_pro_product_manager();
        $results = array(
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => array()
        );
        
        foreach ($import_data['products'] as $index => $product_data) {
            // Check if product exists
            $existing_product_id = null;
            if (!empty($product_data['id'])) {
                $existing_product = $product_manager->get_product($product_data['id']);
                if ($existing_product) {
                    $existing_product_id = $product_data['id'];
                }
            }
            
            // If no ID match, check by title
            if (!$existing_product_id && !empty($product_data['title'])) {
                $existing_posts = get_posts(array(
                    'post_type' => SkyLearn_Billing_Pro_Product_Manager::POST_TYPE,
                    'title' => $product_data['title'],
                    'posts_per_page' => 1
                ));
                
                if (!empty($existing_posts)) {
                    $existing_product_id = $existing_posts[0]->ID;
                }
            }
            
            try {
                if ($existing_product_id && $options['update_existing']) {
                    // Update existing product
                    $product_data['id'] = $existing_product_id;
                    $this->update_product_from_import($existing_product_id, $product_data);
                    $results['updated']++;
                } elseif (!$existing_product_id) {
                    // Create new product
                    unset($product_data['id']); // Remove ID for new products
                    $new_product_id = $product_manager->create_product($product_data);
                    
                    if (is_wp_error($new_product_id)) {
                        if (!$options['skip_errors']) {
                            return $new_product_id;
                        }
                        $results['errors'][] = sprintf(__('Product %d: %s', 'skylearn-billing-pro'), $index + 1, $new_product_id->get_error_message());
                        $results['skipped']++;
                    } else {
                        $results['imported']++;
                    }
                } else {
                    // Product exists but update not allowed
                    $results['skipped']++;
                }
            } catch (Exception $e) {
                if (!$options['skip_errors']) {
                    return new WP_Error('import_error', $e->getMessage());
                }
                $results['errors'][] = sprintf(__('Product %d: %s', 'skylearn-billing-pro'), $index + 1, $e->getMessage());
                $results['skipped']++;
            }
        }
        
        return $results;
    }
    
    /**
     * Update product from import data
     */
    private function update_product_from_import($product_id, $product_data) {
        // Update post data
        $post_data = array(
            'ID' => $product_id
        );
        
        if (isset($product_data['title'])) {
            $post_data['post_title'] = sanitize_text_field($product_data['title']);
        }
        
        if (isset($product_data['description'])) {
            $post_data['post_content'] = wp_kses_post($product_data['description']);
        }
        
        wp_update_post($post_data);
        
        // Update meta fields
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
        
        // Update array fields
        $array_fields = array('access_triggers', 'tier_restrictions', 'course_mappings');
        
        foreach ($array_fields as $field) {
            if (isset($product_data[$field]) && is_array($product_data[$field])) {
                update_post_meta($product_id, '_skylearn_' . $field, $product_data[$field]);
            }
        }
        
        do_action('skylearn_billing_product_imported', $product_id, $product_data);
    }
    
    /**
     * Handle export AJAX request
     */
    public function handle_export_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'skylearn_products_nonce')) {
            wp_die(__('Invalid nonce', 'skylearn-billing-pro'));
        }
        
        // Check permissions
        if (!current_user_can('export')) {
            wp_die(__('You do not have permission to export products', 'skylearn-billing-pro'));
        }
        
        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : array();
        
        if ($format === 'json') {
            $this->export_products_json($product_ids);
        } else {
            $this->export_products_csv($product_ids);
        }
    }
    
    /**
     * Handle import AJAX request
     */
    public function handle_import_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'skylearn_products_nonce')) {
            wp_send_json_error(__('Invalid nonce', 'skylearn-billing-pro'));
        }
        
        // Check permissions
        if (!current_user_can('import')) {
            wp_send_json_error(__('You do not have permission to import products', 'skylearn-billing-pro'));
        }
        
        $import_options = array(
            'update_existing' => isset($_POST['update_existing']) && $_POST['update_existing'] === '1',
            'skip_errors' => isset($_POST['skip_errors']) && $_POST['skip_errors'] === '1',
            'import_courses' => isset($_POST['import_courses']) && $_POST['import_courses'] === '1'
        );
        
        $file_path = sanitize_text_field($_POST['file_path'] ?? '');
        
        if (empty($file_path) || !file_exists($file_path)) {
            wp_send_json_error(__('Import file not found', 'skylearn-billing-pro'));
        }
        
        // Determine file format
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if ($file_extension === 'json') {
            $results = $this->import_products_json($file_path, $import_options);
        } elseif ($file_extension === 'csv') {
            $results = $this->import_products_csv($file_path, $import_options);
        } else {
            wp_send_json_error(__('Unsupported file format', 'skylearn-billing-pro'));
        }
        
        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Handle file upload
     */
    public function handle_file_upload() {
        if (!isset($_POST['skylearn_import_products']) || !isset($_FILES['import_file'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['skylearn_import_nonce'] ?? '', 'skylearn_import_products')) {
            wp_die(__('Invalid nonce', 'skylearn-billing-pro'));
        }
        
        // Check permissions
        if (!current_user_can('import')) {
            wp_die(__('You do not have permission to import products', 'skylearn-billing-pro'));
        }
        
        $uploaded_file = $_FILES['import_file'];
        
        if ($uploaded_file['error'] !== UPLOAD_ERR_OK) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('File upload error.', 'skylearn-billing-pro') . '</p></div>';
            });
            return;
        }
        
        // Validate file type
        $allowed_types = array('text/csv', 'application/json', 'text/plain');
        $file_type = wp_check_filetype($uploaded_file['name']);
        
        if (!in_array($uploaded_file['type'], $allowed_types) && !in_array($file_type['type'], $allowed_types)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Invalid file type. Only CSV and JSON files are allowed.', 'skylearn-billing-pro') . '</p></div>';
            });
            return;
        }
        
        // Move uploaded file to temporary location
        $upload_dir = wp_upload_dir();
        $temp_file = $upload_dir['path'] . '/skylearn-import-' . time() . '-' . $uploaded_file['name'];
        
        if (!move_uploaded_file($uploaded_file['tmp_name'], $temp_file)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__('Failed to upload file.', 'skylearn-billing-pro') . '</p></div>';
            });
            return;
        }
        
        // Process import
        $import_options = array(
            'update_existing' => isset($_POST['update_existing']) && $_POST['update_existing'] === '1',
            'skip_errors' => isset($_POST['skip_errors']) && $_POST['skip_errors'] === '1',
            'import_courses' => isset($_POST['import_courses']) && $_POST['import_courses'] === '1'
        );
        
        $file_extension = strtolower(pathinfo($temp_file, PATHINFO_EXTENSION));
        
        if ($file_extension === 'json') {
            $results = $this->import_products_json($temp_file, $import_options);
        } else {
            $results = $this->import_products_csv($temp_file, $import_options);
        }
        
        // Clean up temporary file
        unlink($temp_file);
        
        if (is_wp_error($results)) {
            add_action('admin_notices', function() use ($results) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($results->get_error_message()) . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($results) {
                $message = sprintf(
                    __('Import completed: %d imported, %d updated, %d skipped.', 'skylearn-billing-pro'),
                    $results['imported'],
                    $results['updated'],
                    $results['skipped']
                );
                
                if (!empty($results['errors'])) {
                    $message .= ' ' . sprintf(__('%d errors occurred.', 'skylearn-billing-pro'), count($results['errors']));
                }
                
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
                
                if (!empty($results['errors'])) {
                    echo '<div class="notice notice-warning is-dismissible">';
                    echo '<p><strong>' . esc_html__('Import Errors:', 'skylearn-billing-pro') . '</strong></p>';
                    echo '<ul>';
                    foreach ($results['errors'] as $error) {
                        echo '<li>' . esc_html($error) . '</li>';
                    }
                    echo '</ul>';
                    echo '</div>';
                }
            });
        }
        
        // Redirect to prevent resubmission
        wp_redirect(admin_url('admin.php?page=skylearn-billing-pro-products&tab=import-export'));
        exit;
    }
}

/**
 * Get migration tool instance
 */
function skylearn_billing_pro_migration_tool() {
    return SkyLearn_Billing_Pro_Migration_Tool::instance();
}