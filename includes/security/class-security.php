<?php
/**
 * Security utilities for Skylearn Billing Pro
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
 * Security class for handling nonce, capabilities, and sanitization
 */
class SkyLearn_Billing_Pro_Security {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'security_headers'));
        add_filter('skylearn_billing_pro_sanitize_input', array($this, 'sanitize_input'), 10, 2);
        add_action('wp_ajax_nopriv_skylearn_security_check', array($this, 'ajax_security_check'));
        add_action('wp_ajax_skylearn_security_check', array($this, 'ajax_security_check'));
    }
    
    /**
     * Initialize security features
     */
    public function init() {
        // Add security headers
        add_action('send_headers', array($this, 'add_security_headers'));
        
        // Log failed login attempts
        add_action('wp_login_failed', array($this, 'log_failed_login'));
        add_action('wp_authenticate_user', array($this, 'check_brute_force'), 30, 2);
    }
    
    /**
     * Add security headers
     */
    public function add_security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
    
    /**
     * Enhanced security headers for admin pages
     */
    public function security_headers() {
        if (is_admin() && !headers_sent()) {
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
        }
    }
    
    /**
     * Verify nonce with enhanced security
     */
    public function verify_nonce($nonce, $action = -1) {
        if (!wp_verify_nonce($nonce, $action)) {
            // Log potential security issue
            $this->log_security_event('nonce_verification_failed', array(
                'action' => $action,
                'nonce' => $nonce,
                'ip' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
            ));
            return false;
        }
        return true;
    }
    
    /**
     * Enhanced capability check
     */
    public function check_capability($capability, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            $this->log_security_event('capability_check_no_user', array(
                'capability' => $capability,
                'ip' => $this->get_client_ip()
            ));
            return false;
        }
        
        if (!user_can($user_id, $capability)) {
            $this->log_security_event('capability_check_failed', array(
                'capability' => $capability,
                'user_id' => $user_id,
                'ip' => $this->get_client_ip()
            ));
            return false;
        }
        
        return true;
    }
    
    /**
     * Comprehensive input sanitization
     */
    public function sanitize_input($input, $type = 'text') {
        if (is_array($input)) {
            return array_map(function($item) use ($type) {
                return $this->sanitize_input($item, $type);
            }, $input);
        }
        
        switch ($type) {
            case 'email':
                return sanitize_email($input);
                
            case 'url':
                return esc_url_raw($input);
                
            case 'int':
                return intval($input);
                
            case 'float':
                return floatval($input);
                
            case 'bool':
                return (bool) $input;
                
            case 'textarea':
                return sanitize_textarea_field($input);
                
            case 'html':
                return wp_kses_post($input);
                
            case 'key':
                return sanitize_key($input);
                
            case 'slug':
                return sanitize_title($input);
                
            case 'user':
                return sanitize_user($input);
                
            case 'file':
                return sanitize_file_name($input);
                
            case 'hex_color':
                return sanitize_hex_color($input);
                
            case 'meta_key':
                return sanitize_meta($input, '', 'post');
                
            case 'sql':
                global $wpdb;
                return $wpdb->esc_like($input);
                
            case 'text':
            default:
                return sanitize_text_field($input);
        }
    }
    
    /**
     * Validate and sanitize admin form data
     */
    public function validate_admin_form($data, $rules = array()) {
        $validated = array();
        $errors = array();
        
        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? $data[$field] : null;
            
            // Check if required
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = sprintf(__('Field %s is required', 'skylearn-billing-pro'), $field);
                continue;
            }
            
            // Skip if empty and not required
            if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
                $validated[$field] = '';
                continue;
            }
            
            // Sanitize based on type
            $type = isset($rule['type']) ? $rule['type'] : 'text';
            $sanitized_value = $this->sanitize_input($value, $type);
            
            // Additional validation
            if (isset($rule['validate'])) {
                $validation_result = $this->validate_field($sanitized_value, $rule['validate']);
                if (is_wp_error($validation_result)) {
                    $errors[$field] = $validation_result->get_error_message();
                    continue;
                }
            }
            
            $validated[$field] = $sanitized_value;
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', __('Validation failed', 'skylearn-billing-pro'), $errors);
        }
        
        return $validated;
    }
    
    /**
     * Field validation
     */
    private function validate_field($value, $rules) {
        if (is_string($rules)) {
            $rules = array($rules);
        }
        
        foreach ($rules as $rule) {
            switch ($rule) {
                case 'email':
                    if (!is_email($value)) {
                        return new WP_Error('invalid_email', __('Invalid email address', 'skylearn-billing-pro'));
                    }
                    break;
                    
                case 'url':
                    if (!filter_var($value, FILTER_VALIDATE_URL)) {
                        return new WP_Error('invalid_url', __('Invalid URL', 'skylearn-billing-pro'));
                    }
                    break;
                    
                case 'numeric':
                    if (!is_numeric($value)) {
                        return new WP_Error('not_numeric', __('Value must be numeric', 'skylearn-billing-pro'));
                    }
                    break;
                    
                case 'positive':
                    if ($value < 0) {
                        return new WP_Error('not_positive', __('Value must be positive', 'skylearn-billing-pro'));
                    }
                    break;
                    
                case 'not_empty':
                    if (empty($value)) {
                        return new WP_Error('empty_value', __('Value cannot be empty', 'skylearn-billing-pro'));
                    }
                    break;
            }
        }
        
        return true;
    }
    
    /**
     * Rate limiting for sensitive operations
     */
    public function is_rate_limited($action, $user_id = null, $limit = 5, $window = 300) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $transient_key = "skylearn_rate_limit_{$action}_{$user_id}";
        $attempts = get_transient($transient_key);
        
        if ($attempts === false) {
            set_transient($transient_key, 1, $window);
            return false;
        }
        
        if ($attempts >= $limit) {
            $this->log_security_event('rate_limit_exceeded', array(
                'action' => $action,
                'user_id' => $user_id,
                'attempts' => $attempts,
                'limit' => $limit,
                'ip' => $this->get_client_ip()
            ));
            return true;
        }
        
        set_transient($transient_key, $attempts + 1, $window);
        return false;
    }
    
    /**
     * Log failed login attempts
     */
    public function log_failed_login($username) {
        $ip = $this->get_client_ip();
        
        // Track failed attempts per IP
        $transient_key = "skylearn_failed_login_{$ip}";
        $attempts = get_transient($transient_key) ?: 0;
        $attempts++;
        
        set_transient($transient_key, $attempts, 900); // 15 minutes
        
        $this->log_security_event('login_failed', array(
            'username' => $username,
            'ip' => $ip,
            'attempts' => $attempts,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        ));
    }
    
    /**
     * Check for brute force attacks
     */
    public function check_brute_force($user, $password) {
        $ip = $this->get_client_ip();
        $transient_key = "skylearn_failed_login_{$ip}";
        $attempts = get_transient($transient_key) ?: 0;
        
        // Block after 5 failed attempts
        if ($attempts >= 5) {
            $this->log_security_event('brute_force_blocked', array(
                'ip' => $ip,
                'attempts' => $attempts,
                'user' => is_wp_error($user) ? 'unknown' : $user->user_login
            ));
            
            return new WP_Error('brute_force_protection', __('Too many failed login attempts. Please try again later.', 'skylearn-billing-pro'));
        }
        
        return $user;
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
     * Log security events
     */
    private function log_security_event($event, $details = array()) {
        // Use audit logger if available
        if (function_exists('skylearn_billing_pro_audit_logger')) {
            $audit_logger = skylearn_billing_pro_audit_logger();
            $audit_logger->log(
                SkyLearn_Billing_Pro_Audit_Logger::LOG_TYPE_SECURITY,
                $event,
                $details
            );
        }
        
        // Also trigger action for other plugins/systems
        do_action('skylearn_billing_pro_security_event', $event, $details);
    }
    
    /**
     * Generate secure random string
     */
    public function generate_secure_key($length = 32) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length / 2));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length / 2));
        } else {
            return wp_generate_password($length, false);
        }
    }
    
    /**
     * Hash sensitive data
     */
    public function hash_data($data, $salt = '') {
        if (empty($salt)) {
            $salt = wp_salt('auth');
        }
        return hash_hmac('sha256', $data, $salt);
    }
    
    /**
     * Constant time string comparison
     */
    public function compare_strings($str1, $str2) {
        if (function_exists('hash_equals')) {
            return hash_equals($str1, $str2);
        }
        
        // Fallback for older PHP versions
        if (strlen($str1) !== strlen($str2)) {
            return false;
        }
        
        $result = 0;
        for ($i = 0; $i < strlen($str1); $i++) {
            $result |= ord($str1[$i]) ^ ord($str2[$i]);
        }
        
        return $result === 0;
    }
    
    /**
     * AJAX security check
     */
    public function ajax_security_check() {
        // This endpoint can be used to verify security status
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_security_check')) {
            wp_send_json_error(__('Security check failed', 'skylearn-billing-pro'));
        }
        
        wp_send_json_success(array(
            'message' => __('Security check passed', 'skylearn-billing-pro'),
            'timestamp' => current_time('mysql')
        ));
    }
    
    /**
     * Clean expired security data
     */
    public function cleanup_security_data() {
        global $wpdb;
        
        // This method can be called periodically to clean up expired security data
        $expired_transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_skylearn_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        foreach ($expired_transients as $transient) {
            $key = str_replace('_transient_timeout_', '', $transient->option_name);
            delete_transient($key);
        }
    }
}

/**
 * Get single instance of Security
 */
function skylearn_billing_pro_security() {
    static $instance = null;
    if (null === $instance) {
        $instance = new SkyLearn_Billing_Pro_Security();
    }
    return $instance;
}