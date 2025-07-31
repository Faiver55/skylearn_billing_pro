<?php
/**
 * Test helper functions and utilities
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

/**
 * Base test case class for SkyLearn Billing Pro tests
 */
class SkyLearn_Billing_Pro_Test_Case extends PHPUnit\Framework\TestCase {
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        
        // Reset options before each test
        $this->reset_plugin_options();
    }
    
    /**
     * Reset plugin options to defaults
     */
    protected function reset_plugin_options() {
        // Mock WordPress options
        global $skylearn_test_options;
        $skylearn_test_options = array(
            'skylearn_billing_pro_options' => array(
                'version' => SKYLEARN_BILLING_PRO_VERSION,
                'general_settings' => array(
                    'company_name' => 'Test Company',
                    'company_email' => 'test@example.com',
                    'currency' => 'USD',
                    'test_mode' => true,
                ),
                'lms_settings' => array(
                    'active_lms' => '',
                    'auto_enroll' => true,
                ),
                'email_settings' => array(
                    'welcome_email_enabled' => true,
                    'welcome_email_subject' => 'Welcome to Test Site',
                    'welcome_email_template' => '<p>Welcome!</p>',
                    'welcome_email_format' => 'html',
                ),
                'webhook_settings' => array(
                    'secret' => 'test_webhook_secret',
                    'send_welcome_email' => true,
                    'enabled' => true,
                ),
                'course_mappings' => array(),
                'enrollment_log' => array(),
                'email_log' => array(),
                'user_activity_log' => array()
            )
        );
    }
    
    /**
     * Create mock user data
     */
    protected function create_mock_user($args = array()) {
        $defaults = array(
            'user_login' => 'testuser',
            'user_email' => 'test@example.com',
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_pass' => 'password123'
        );
        
        return array_merge($defaults, $args);
    }
    
    /**
     * Create mock payment data
     */
    protected function create_mock_payment($args = array()) {
        $defaults = array(
            'payment_id' => 'test_payment_' . time(),
            'amount' => 99.00,
            'currency' => 'USD',
            'status' => 'completed',
            'gateway' => 'stripe',
            'customer_email' => 'test@example.com',
            'product_id' => 'test_product_1',
            'course_id' => 123
        );
        
        return array_merge($defaults, $args);
    }
    
    /**
     * Create mock webhook data
     */
    protected function create_mock_webhook($args = array()) {
        $defaults = array(
            'event_type' => 'payment.completed',
            'payment_id' => 'test_payment_' . time(),
            'customer_email' => 'test@example.com',
            'amount' => 99.00,
            'currency' => 'USD',
            'product_id' => 'test_product_1'
        );
        
        return array_merge($defaults, $args);
    }
    
    /**
     * Mock WordPress get_option function
     */
    protected function mock_get_option($option, $default = false) {
        global $skylearn_test_options;
        return isset($skylearn_test_options[$option]) ? $skylearn_test_options[$option] : $default;
    }
    
    /**
     * Mock WordPress update_option function
     */
    protected function mock_update_option($option, $value) {
        global $skylearn_test_options;
        $skylearn_test_options[$option] = $value;
        return true;
    }
    
    /**
     * Assert that a log entry was created
     */
    protected function assertLogEntryCreated($log_type, $message = null) {
        $options = $this->mock_get_option('skylearn_billing_pro_options');
        $log_key = $log_type . '_log';
        
        $this->assertArrayHasKey($log_key, $options);
        $this->assertNotEmpty($options[$log_key]);
        
        if ($message !== null) {
            $found = false;
            foreach ($options[$log_key] as $entry) {
                if (strpos($entry['message'], $message) !== false) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Log message '{$message}' not found in {$log_type} log");
        }
    }
    
    /**
     * Assert that an email was sent
     */
    protected function assertEmailSent($to_email, $subject = null) {
        $options = $this->mock_get_option('skylearn_billing_pro_options');
        $email_log = $options['email_log'];
        
        $this->assertNotEmpty($email_log);
        
        $found = false;
        foreach ($email_log as $entry) {
            if ($entry['to'] === $to_email) {
                if ($subject === null || $entry['subject'] === $subject) {
                    $found = true;
                    break;
                }
            }
        }
        $this->assertTrue($found, "Email to '{$to_email}' not found in email log");
    }
}

/**
 * Mock global functions for testing
 */
if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        global $skylearn_test_options;
        return isset($skylearn_test_options[$option]) ? $skylearn_test_options[$option] : $default;
    }
}

if (!function_exists('update_option')) {
    function update_option($option, $value) {
        global $skylearn_test_options;
        $skylearn_test_options[$option] = $value;
        return true;
    }
}

if (!function_exists('wp_create_user')) {
    function wp_create_user($username, $password, $email = '') {
        return rand(1000, 9999); // Return random user ID
    }
}

if (!function_exists('wp_mail')) {
    function wp_mail($to, $subject, $message, $headers = '', $attachments = array()) {
        // Log email for testing
        global $skylearn_test_options;
        if (!isset($skylearn_test_options['skylearn_billing_pro_options']['email_log'])) {
            $skylearn_test_options['skylearn_billing_pro_options']['email_log'] = array();
        }
        
        $skylearn_test_options['skylearn_billing_pro_options']['email_log'][] = array(
            'to' => $to,
            'subject' => $subject,
            'message' => $message,
            'headers' => $headers,
            'timestamp' => time()
        );
        
        return true;
    }
}

/**
 * Test utilities for mocking HTTP requests
 */
class SkyLearn_Test_HTTP_Mock {
    
    public static $responses = array();
    
    public static function set_response($url, $response) {
        self::$responses[$url] = $response;
    }
    
    public static function get_response($url) {
        return isset(self::$responses[$url]) ? self::$responses[$url] : null;
    }
    
    public static function clear_responses() {
        self::$responses = array();
    }
}