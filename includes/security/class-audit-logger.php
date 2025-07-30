<?php
/**
 * Audit Logging for Skylearn Billing Pro
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
 * Audit Logger class for tracking critical events
 */
class SkyLearn_Billing_Pro_Audit_Logger {
    
    /**
     * Log types
     */
    const LOG_TYPE_ADMIN = 'admin';
    const LOG_TYPE_USER = 'user';
    const LOG_TYPE_PAYMENT = 'payment';
    const LOG_TYPE_ENROLLMENT = 'enrollment';
    const LOG_TYPE_SECURITY = 'security';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_login', array($this, 'log_user_login'), 10, 2);
        add_action('wp_logout', array($this, 'log_user_logout'));
        add_action('user_register', array($this, 'log_user_registration'));
        add_action('skylearn_billing_pro_payment_completed', array($this, 'log_payment'), 10, 2);
        add_action('skylearn_billing_pro_user_enrolled', array($this, 'log_enrollment'), 10, 3);
        add_action('wp_ajax_skylearn_get_audit_logs', array($this, 'ajax_get_audit_logs'));
    }
    
    /**
     * Initialize audit logger
     */
    public function init() {
        // Create audit log table if needed
        $this->maybe_create_audit_table();
        
        // Hook into admin actions
        add_action('admin_init', array($this, 'hook_admin_actions'));
    }
    
    /**
     * Hook into admin actions for logging
     */
    public function hook_admin_actions() {
        // Log settings changes
        add_action('update_option_skylearn_billing_pro_options', array($this, 'log_settings_change'), 10, 2);
        
        // Log product changes
        add_action('skylearn_billing_pro_product_created', array($this, 'log_product_created'));
        add_action('skylearn_billing_pro_product_updated', array($this, 'log_product_updated'));
        add_action('skylearn_billing_pro_product_deleted', array($this, 'log_product_deleted'));
    }
    
    /**
     * Create audit log table
     */
    private function maybe_create_audit_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'skylearn_audit_log';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            $charset_collate = $wpdb->get_charset_collate();
            
            $sql = "CREATE TABLE $table_name (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                timestamp datetime DEFAULT CURRENT_TIMESTAMP,
                user_id bigint(20) DEFAULT NULL,
                user_email varchar(100) DEFAULT NULL,
                log_type varchar(50) NOT NULL,
                action varchar(100) NOT NULL,
                object_type varchar(50) DEFAULT NULL,
                object_id bigint(20) DEFAULT NULL,
                details longtext,
                ip_address varchar(45) DEFAULT NULL,
                user_agent text,
                PRIMARY KEY (id),
                KEY user_id (user_id),
                KEY log_type (log_type),
                KEY timestamp (timestamp)
            ) $charset_collate;";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }
    
    /**
     * Log an event
     */
    public function log($type, $action, $details = array(), $object_type = null, $object_id = null) {
        global $wpdb;
        
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID ?: null;
        $user_email = $current_user->user_email ?: null;
        
        // Get IP address
        $ip_address = $this->get_client_ip();
        
        // Get user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '';
        
        $table_name = $wpdb->prefix . 'skylearn_audit_log';
        
        $wpdb->insert(
            $table_name,
            array(
                'user_id' => $user_id,
                'user_email' => $user_email,
                'log_type' => $type,
                'action' => $action,
                'object_type' => $object_type,
                'object_id' => $object_id,
                'details' => json_encode($details),
                'ip_address' => $ip_address,
                'user_agent' => $user_agent,
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        // Trigger action for extensibility
        do_action('skylearn_billing_pro_audit_logged', $type, $action, $details, $object_type, $object_id);
    }
    
    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
    }
    
    /**
     * Get audit logs
     */
    public function get_logs($filters = array(), $limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'skylearn_audit_log';
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['type'])) {
            $where_conditions[] = 'log_type = %s';
            $where_values[] = $filters['type'];
        }
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'timestamp >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'timestamp <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = '(action LIKE %s OR details LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT * FROM $table_name WHERE $where_clause ORDER BY timestamp DESC LIMIT %d OFFSET %d";
        $where_values[] = $limit;
        $where_values[] = $offset;
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode details JSON
        foreach ($results as &$result) {
            $result['details'] = json_decode($result['details'], true);
        }
        
        return $results;
    }
    
    /**
     * Get total count of logs
     */
    public function get_logs_count($filters = array()) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'skylearn_audit_log';
        
        $where_conditions = array('1=1');
        $where_values = array();
        
        // Apply same filters as get_logs
        if (!empty($filters['type'])) {
            $where_conditions[] = 'log_type = %s';
            $where_values[] = $filters['type'];
        }
        
        if (!empty($filters['user_id'])) {
            $where_conditions[] = 'user_id = %d';
            $where_values[] = $filters['user_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $where_conditions[] = 'timestamp >= %s';
            $where_values[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where_conditions[] = 'timestamp <= %s';
            $where_values[] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = '(action LIKE %s OR details LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT COUNT(*) FROM $table_name WHERE $where_clause";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Log user login
     */
    public function log_user_login($user_login, $user) {
        $this->log(
            self::LOG_TYPE_USER,
            'login',
            array('user_login' => $user_login),
            'user',
            $user->ID
        );
    }
    
    /**
     * Log user logout
     */
    public function log_user_logout() {
        $current_user = wp_get_current_user();
        $this->log(
            self::LOG_TYPE_USER,
            'logout',
            array('user_login' => $current_user->user_login),
            'user',
            $current_user->ID
        );
    }
    
    /**
     * Log user registration
     */
    public function log_user_registration($user_id) {
        $user = get_user_by('id', $user_id);
        $this->log(
            self::LOG_TYPE_USER,
            'registration',
            array('user_login' => $user->user_login, 'user_email' => $user->user_email),
            'user',
            $user_id
        );
    }
    
    /**
     * Log payment
     */
    public function log_payment($payment_data, $user_id) {
        $this->log(
            self::LOG_TYPE_PAYMENT,
            'payment_completed',
            $payment_data,
            'payment',
            isset($payment_data['transaction_id']) ? $payment_data['transaction_id'] : null
        );
    }
    
    /**
     * Log enrollment
     */
    public function log_enrollment($user_id, $course_id, $enrollment_data) {
        $this->log(
            self::LOG_TYPE_ENROLLMENT,
            'course_enrolled',
            array_merge($enrollment_data, array('course_id' => $course_id)),
            'enrollment',
            $course_id
        );
    }
    
    /**
     * Log settings change
     */
    public function log_settings_change($old_value, $value) {
        $current_user = wp_get_current_user();
        
        $this->log(
            self::LOG_TYPE_ADMIN,
            'settings_updated',
            array(
                'changed_by' => $current_user->user_login,
                'old_value_hash' => md5(serialize($old_value)),
                'new_value_hash' => md5(serialize($value))
            ),
            'settings',
            null
        );
    }
    
    /**
     * Log product created
     */
    public function log_product_created($product_id) {
        $this->log(
            self::LOG_TYPE_ADMIN,
            'product_created',
            array('product_id' => $product_id),
            'product',
            $product_id
        );
    }
    
    /**
     * Log product updated
     */
    public function log_product_updated($product_id) {
        $this->log(
            self::LOG_TYPE_ADMIN,
            'product_updated',
            array('product_id' => $product_id),
            'product',
            $product_id
        );
    }
    
    /**
     * Log product deleted
     */
    public function log_product_deleted($product_id) {
        $this->log(
            self::LOG_TYPE_ADMIN,
            'product_deleted',
            array('product_id' => $product_id),
            'product',
            $product_id
        );
    }
    
    /**
     * AJAX handler for getting audit logs
     */
    public function ajax_get_audit_logs() {
        // Verify nonce and capabilities
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'skylearn_audit_logs')) {
            wp_die(__('Unauthorized access', 'skylearn-billing-pro'));
        }
        
        $filters = array();
        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 20);
        $offset = ($page - 1) * $per_page;
        
        // Sanitize filters
        if (!empty($_POST['type'])) {
            $filters['type'] = sanitize_text_field($_POST['type']);
        }
        
        if (!empty($_POST['user_id'])) {
            $filters['user_id'] = intval($_POST['user_id']);
        }
        
        if (!empty($_POST['date_from'])) {
            $filters['date_from'] = sanitize_text_field($_POST['date_from']);
        }
        
        if (!empty($_POST['date_to'])) {
            $filters['date_to'] = sanitize_text_field($_POST['date_to']);
        }
        
        if (!empty($_POST['search'])) {
            $filters['search'] = sanitize_text_field($_POST['search']);
        }
        
        $logs = $this->get_logs($filters, $per_page, $offset);
        $total = $this->get_logs_count($filters);
        
        wp_send_json_success(array(
            'logs' => $logs,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ));
    }
    
    /**
     * Clean old logs (optional method for maintenance)
     */
    public function clean_old_logs($days = 90) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'skylearn_audit_log';
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deleted = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < %s",
                $cutoff_date
            )
        );
        
        $this->log(
            self::LOG_TYPE_ADMIN,
            'audit_logs_cleaned',
            array('deleted_count' => $deleted, 'cutoff_date' => $cutoff_date),
            'maintenance',
            null
        );
        
        return $deleted;
    }
}

/**
 * Get single instance of Audit Logger
 */
function skylearn_billing_pro_audit_logger() {
    static $instance = null;
    if (null === $instance) {
        $instance = new SkyLearn_Billing_Pro_Audit_Logger();
    }
    return $instance;
}