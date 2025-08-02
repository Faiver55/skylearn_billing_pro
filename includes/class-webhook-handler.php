<?php
/**
 * Webhook Handler for third-party automation tools
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
 * Webhook Handler class
 */
class SkyLearn_Billing_Pro_Webhook_Handler {
    
    /**
     * Course Mapping instance
     *
     * @var SkyLearn_Billing_Pro_Course_Mapping
     */
    private $course_mapping;
    
    /**
     * LMS Manager instance
     *
     * @var SkyLearn_Billing_Pro_LMS_Manager
     */
    private $lms_manager;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->course_mapping = skylearn_billing_pro_course_mapping();
        $this->lms_manager = skylearn_billing_pro_lms_manager();
        
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize webhook handler
     */
    public function init() {
        add_action('wp_loaded', array($this, 'handle_webhook_request'));
        add_action('wp_loaded', array($this, 'handle_payment_webhook_request'));
    }
    
    /**
     * Handle incoming webhook requests
     */
    public function handle_webhook_request() {
        // Check if this is a webhook request
        if (!$this->is_webhook_request()) {
            return;
        }
        
        // Verify webhook security
        if (!$this->verify_webhook_security()) {
            $this->send_webhook_response(401, array('error' => 'Unauthorized'));
            return;
        }
        
        // Get webhook data
        $data = $this->get_webhook_data();
        
        if (empty($data)) {
            $this->send_webhook_response(400, array('error' => 'Invalid or empty data'));
            return;
        }
        
        // Process webhook
        $result = $this->process_webhook($data);
        
        if ($result['success']) {
            $this->send_webhook_response(200, $result);
        } else {
            $this->send_webhook_response(400, $result);
        }
    }
    
    /**
     * Check if current request is a webhook request
     *
     * @return bool
     */
    private function is_webhook_request() {
        // Check for webhook query variable first
        if (get_query_var('skylearn_webhook')) {
            return true;
        }
        
        // Fallback: Check URL pattern
        $request_uri = $_SERVER['REQUEST_URI'];
        return strpos($request_uri, '/skylearn-billing/webhook') !== false;
    }
    
    /**
     * Verify webhook security
     *
     * @return bool
     */
    private function verify_webhook_security() {
        // Get webhook secret from settings
        $options = get_option('skylearn_billing_pro_options', array());
        $webhook_secret = isset($options['webhook_settings']['secret']) ? $options['webhook_settings']['secret'] : '';
        
        // If no secret is set, allow any request (for testing)
        if (empty($webhook_secret)) {
            return true;
        }
        
        // Check for API key in headers
        $api_key = $this->get_request_header('X-API-Key');
        if ($api_key && $api_key === $webhook_secret) {
            return true;
        }
        
        // Check for API key in query parameters
        if (isset($_GET['api_key']) && $_GET['api_key'] === $webhook_secret) {
            return true;
        }
        
        // Check for signature verification (for future implementation)
        $signature = $this->get_request_header('X-Signature');
        if ($signature) {
            return $this->verify_signature($signature);
        }
        
        return false;
    }
    
    /**
     * Get request header value
     *
     * @param string $header Header name
     * @return string|null
     */
    private function get_request_header($header) {
        $header = strtoupper(str_replace('-', '_', $header));
        
        if (isset($_SERVER['HTTP_' . $header])) {
            return $_SERVER['HTTP_' . $header];
        }
        
        return null;
    }
    
    /**
     * Verify webhook signature (for future implementation)
     *
     * @param string $signature Signature to verify
     * @return bool
     */
    private function verify_signature($signature) {
        // Placeholder for signature verification
        // This can be implemented for specific payment processors
        return true;
    }
    
    /**
     * Get webhook data from request
     *
     * @return array
     */
    private function get_webhook_data() {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return array();
        }
        
        // Try to decode JSON
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If JSON decode fails, try to parse as form data
            parse_str($input, $data);
        }
        
        // Log webhook data for debugging
        $this->log_webhook_data($data);
        
        return is_array($data) ? $data : array();
    }
    
    /**
     * Process webhook data
     *
     * @param array $data Webhook data
     * @return array Processing result
     */
    public function process_webhook($data) {
        try {
            // Validate required fields
            $validation_result = $this->validate_webhook_data($data);
            if (!$validation_result['valid']) {
                return array(
                    'success' => false,
                    'error' => $validation_result['error'],
                    'code' => 'validation_failed'
                );
            }
            
            // Extract user and product information
            $user_data = $this->extract_user_data($data);
            $product_id = $this->extract_product_id($data);
            
            if (!$product_id) {
                return array(
                    'success' => false,
                    'error' => 'Product ID not found in webhook data',
                    'code' => 'missing_product_id'
                );
            }
            
            // Create or get user
            $user_id = $this->create_or_get_user($user_data);
            
            if (!$user_id) {
                return array(
                    'success' => false,
                    'error' => 'Failed to create or retrieve user',
                    'code' => 'user_creation_failed'
                );
            }
            
            // Process course enrollment
            $enrollment_result = $this->course_mapping->process_enrollment($product_id, $user_id, 'webhook');
            
            if ($enrollment_result) {
                $response = array(
                    'success' => true,
                    'message' => 'User successfully enrolled in course',
                    'user_id' => $user_id,
                    'product_id' => $product_id,
                    'enrollment_status' => 'completed'
                );
                
                // Add course information if available
                $mapping = $this->course_mapping->get_course_mapping($product_id);
                if ($mapping) {
                    $course_details = $this->lms_manager->get_course_details($mapping['course_id']);
                    if ($course_details) {
                        $response['course_id'] = $mapping['course_id'];
                        $response['course_title'] = $course_details['title'];
                    }
                }
                
                return $response;
            } else {
                return array(
                    'success' => false,
                    'error' => 'Enrollment failed - no course mapping found or enrollment error',
                    'code' => 'enrollment_failed',
                    'user_id' => $user_id,
                    'product_id' => $product_id
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'error' => 'Internal error: ' . $e->getMessage(),
                'code' => 'internal_error'
            );
        }
    }
    
    /**
     * Validate webhook data
     *
     * @param array $data Webhook data
     * @return array Validation result
     */
    private function validate_webhook_data($data) {
        // Required fields
        $required_fields = array('email');
        
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return array(
                    'valid' => false,
                    'error' => "Required field '{$field}' is missing or empty"
                );
            }
        }
        
        // Validate email format
        if (!is_email($data['email'])) {
            return array(
                'valid' => false,
                'error' => 'Invalid email format'
            );
        }
        
        return array('valid' => true);
    }
    
    /**
     * Extract user data from webhook
     *
     * @param array $data Webhook data
     * @return array User data
     */
    private function extract_user_data($data) {
        $user_data = array(
            'email' => sanitize_email($data['email'])
        );
        
        // Extract name fields
        if (!empty($data['name'])) {
            $user_data['display_name'] = sanitize_text_field($data['name']);
            $user_data['first_name'] = sanitize_text_field($data['name']);
        } elseif (!empty($data['first_name']) || !empty($data['last_name'])) {
            $user_data['first_name'] = sanitize_text_field($data['first_name'] ?? '');
            $user_data['last_name'] = sanitize_text_field($data['last_name'] ?? '');
            $user_data['display_name'] = trim($user_data['first_name'] . ' ' . $user_data['last_name']);
        }
        
        // Additional fields
        if (!empty($data['phone'])) {
            $user_data['phone'] = sanitize_text_field($data['phone']);
        }
        
        if (!empty($data['company'])) {
            $user_data['company'] = sanitize_text_field($data['company']);
        }
        
        return $user_data;
    }
    
    /**
     * Extract product ID from webhook data
     *
     * @param array $data Webhook data
     * @return string|false Product ID or false
     */
    private function extract_product_id($data) {
        // Common product ID fields
        $product_fields = array(
            'product_id',
            'productId',
            'variant_id',
            'variantId',
            'item_id',
            'itemId',
            'sku',
            'product_sku'
        );
        
        foreach ($product_fields as $field) {
            if (!empty($data[$field])) {
                return sanitize_text_field($data[$field]);
            }
        }
        
        return false;
    }
    
    /**
     * Create or get WordPress user
     *
     * @param array $user_data User data
     * @return int|false User ID or false on failure
     */
    private function create_or_get_user($user_data) {
        // Check if user already exists
        $existing_user = get_user_by('email', $user_data['email']);
        
        if ($existing_user) {
            return $existing_user->ID;
        }
        
        // Create new user
        $username = $this->generate_username($user_data['email']);
        $password = wp_generate_password();
        
        $user_id = wp_create_user($username, $password, $user_data['email']);
        
        if (is_wp_error($user_id)) {
            $this->log_error('User creation failed: ' . $user_id->get_error_message());
            return false;
        }
        
        // Update user meta
        if (!empty($user_data['first_name'])) {
            update_user_meta($user_id, 'first_name', $user_data['first_name']);
        }
        
        if (!empty($user_data['last_name'])) {
            update_user_meta($user_id, 'last_name', $user_data['last_name']);
        }
        
        if (!empty($user_data['display_name'])) {
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $user_data['display_name']
            ));
        }
        
        if (!empty($user_data['phone'])) {
            update_user_meta($user_id, 'phone', $user_data['phone']);
        }
        
        if (!empty($user_data['company'])) {
            update_user_meta($user_id, 'company', $user_data['company']);
        }
        
        // Send welcome email if enabled
        $this->send_welcome_email($user_id, $password, $user_data);
        
        // Fire action hook
        do_action('skylearn_billing_pro_user_created_via_webhook', $user_id, $user_data);
        
        return $user_id;
    }
    
    /**
     * Generate unique username from email
     *
     * @param string $email Email address
     * @return string Username
     */
    private function generate_username($email) {
        $username = sanitize_user(substr($email, 0, strpos($email, '@')));
        
        // Ensure username is unique
        $original_username = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Send welcome email to new user
     *
     * @param int $user_id User ID
     * @param string $password User password
     * @param array $user_data User data
     */
    private function send_welcome_email($user_id, $password, $user_data) {
        $options = get_option('skylearn_billing_pro_options', array());
        $send_welcome = isset($options['webhook_settings']['send_welcome_email']) ? $options['webhook_settings']['send_welcome_email'] : true;
        
        if (!$send_welcome) {
            return;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $subject = sprintf(__('Welcome to %s', 'skylearn-billing-pro'), get_bloginfo('name'));
        $login_url = wp_login_url();
        
        $message = sprintf(
            __('Hello %s,

Welcome to %s! Your account has been created successfully.

Your login credentials:
Username: %s
Password: %s

You can login here: %s

Best regards,
%s', 'skylearn-billing-pro'),
            $user_data['display_name'] ?? $user->user_email,
            get_bloginfo('name'),
            $user->user_login,
            $password,
            $login_url,
            get_bloginfo('name')
        );
        
        wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Send webhook response
     *
     * @param int $status_code HTTP status code
     * @param array $data Response data
     */
    private function send_webhook_response($status_code, $data) {
        status_header($status_code);
        header('Content-Type: application/json');
        
        echo json_encode($data);
        exit;
    }
    
    /**
     * Log webhook data for debugging
     *
     * @param array $data Webhook data
     */
    private function log_webhook_data($data) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        $sanitized_data = $data;
        
        // Remove sensitive information from logs
        $sensitive_fields = array('password', 'api_key', 'secret', 'token');
        foreach ($sensitive_fields as $field) {
            if (isset($sanitized_data[$field])) {
                $sanitized_data[$field] = '[REDACTED]';
            }
        }
        
        error_log('Skylearn Billing Pro Webhook Data: ' . json_encode($sanitized_data));
    }
    
    /**
     * Log error message
     *
     * @param string $message Error message
     */
    private function log_error($message) {
        error_log('Skylearn Billing Pro Webhook Error: ' . $message);
    }
    
    /**
     * Handle payment webhook requests
     */
    public function handle_payment_webhook_request() {
        // Check if this is a payment webhook request
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Handle payment gateway specific webhooks
        if (strpos($request_uri, '/skylearn-billing/webhook/stripe') !== false) {
            $this->handle_payment_webhook('stripe');
        } elseif (strpos($request_uri, '/skylearn-billing/webhook/paddle') !== false) {
            $this->handle_payment_webhook('paddle');
        } elseif (strpos($request_uri, '/skylearn-billing/webhook/lemonsqueezy') !== false) {
            $this->handle_payment_webhook('lemonsqueezy');
        } elseif (strpos($request_uri, '/skylearn-billing/webhook/woocommerce') !== false) {
            $this->handle_payment_webhook('woocommerce');
        }
    }
    
    /**
     * Handle payment gateway webhook
     *
     * @param string $gateway_id Gateway ID
     */
    private function handle_payment_webhook($gateway_id) {
        // Only handle POST requests
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        
        // Get payment manager
        $payment_manager = skylearn_billing_pro_payment_manager();
        
        if (!$payment_manager->is_gateway_enabled($gateway_id)) {
            http_response_code(400);
            wp_die('Gateway not enabled');
        }
        
        // Get raw input
        $input = file_get_contents('php://input');
        
        // Process webhook with payment manager
        $result = $payment_manager->process_payment_webhook($gateway_id, $input);
        
        // Return response
        if ($result['success']) {
            http_response_code(200);
            wp_die(json_encode($result), '', array('response' => 200));
        } else {
            http_response_code(400);
            wp_die(json_encode($result), '', array('response' => 400));
        }
    }
    
    /**
     * Get webhook endpoint URL
     *
     * @return string Webhook URL
     */
    public function get_webhook_url() {
        return home_url('/skylearn-billing/webhook');
    }
    
    /**
     * Get payment webhook endpoint URL for gateway
     *
     * @param string $gateway_id Gateway ID
     * @return string Webhook URL
     */
    public function get_payment_webhook_url($gateway_id) {
        return home_url('/skylearn-billing/webhook/' . $gateway_id);
    }
    
    /**
     * Get webhook settings for admin
     *
     * @return array Webhook settings
     */
    public function get_webhook_settings() {
        $options = get_option('skylearn_billing_pro_options', array());
        return isset($options['webhook_settings']) ? $options['webhook_settings'] : array(
            'secret' => '',
            'send_welcome_email' => true,
            'enabled' => true
        );
    }
    
    /**
     * Save webhook settings
     *
     * @param array $settings Webhook settings
     * @return bool|WP_Error Success status or WP_Error on failure
     */
    public function save_webhook_settings($settings) {
        try {
            $options = get_option('skylearn_billing_pro_options', array());
            $current_settings = isset($options['webhook_settings']) ? $options['webhook_settings'] : array();
            
            // Check if settings have actually changed
            if ($current_settings === $settings) {
                // No change needed, but this is still successful
                return true;
            }
            
            $options['webhook_settings'] = $settings;
            
            $result = update_option('skylearn_billing_pro_options', $options);
            
            if ($result === false) {
                global $wpdb;
                $last_error = $wpdb->last_error;
                
                error_log('SkyLearn Billing Pro: Failed to save webhook settings - Error: ' . $last_error);
                
                if (!empty($last_error)) {
                    return new WP_Error('save_failed', sprintf(__('Failed to save webhook settings: Database error (%s).', 'skylearn-billing-pro'), esc_html($last_error)));
                } else {
                    return new WP_Error('save_failed', __('Failed to save webhook settings: WordPress unable to update options.', 'skylearn-billing-pro'));
                }
            }
            
            // Verify the save was successful
            $verification_options = get_option('skylearn_billing_pro_options', array());
            if (!isset($verification_options['webhook_settings']) || 
                $verification_options['webhook_settings'] !== $settings) {
                
                error_log('SkyLearn Billing Pro: Webhook settings save verification failed');
                return new WP_Error('save_failed', __('Failed to save webhook settings: Data verification failed.', 'skylearn-billing-pro'));
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Exception saving webhook settings - ' . $e->getMessage());
            return new WP_Error('exception', __('An unexpected error occurred while saving webhook settings.', 'skylearn-billing-pro'));
        }
    }
}

/**
 * Get the Webhook Handler instance
 *
 * @return SkyLearn_Billing_Pro_Webhook_Handler
 */
function skylearn_billing_pro_webhook_handler() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Webhook_Handler();
    }
    
    return $instance;
}