<?php
/**
 * Unit tests for Webhook Handler
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-webhook-handler.php';

class Test_Webhook_Handler extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Webhook Handler instance
     *
     * @var SkyLearn_Billing_Pro_Webhook_Handler
     */
    private $webhook_handler;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        $this->webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
        
        // Mock server variables for webhook testing
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_CONTENT_TYPE'] = 'application/json';
    }
    
    /**
     * Tear down test case
     */
    protected function tearDown(): void {
        parent::tearDown();
        
        // Clear server variables
        unset($_SERVER['REQUEST_METHOD']);
        unset($_SERVER['HTTP_CONTENT_TYPE']);
        unset($_GET['skylearn_webhook']);
    }
    
    /**
     * Test Webhook Handler instantiation
     */
    public function test_webhook_handler_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Webhook_Handler', $this->webhook_handler);
    }
    
    /**
     * Test webhook request detection
     */
    public function test_is_webhook_request() {
        // Test without webhook query parameter
        $is_webhook = $this->webhook_handler->is_webhook_request();
        $this->assertFalse($is_webhook);
        
        // Test with webhook query parameter
        $_GET['skylearn_webhook'] = '1';
        $is_webhook = $this->webhook_handler->is_webhook_request();
        $this->assertTrue($is_webhook);
    }
    
    /**
     * Test webhook security verification
     */
    public function test_verify_webhook_security() {
        $_GET['skylearn_webhook'] = '1';
        
        // Test without proper headers
        $verified = $this->webhook_handler->verify_webhook_security();
        $this->assertFalse($verified);
        
        // Test with proper signature (mocked)
        $_SERVER['HTTP_X_SKYLEARN_SIGNATURE'] = 'test_signature';
        $verified = $this->webhook_handler->verify_webhook_security();
        
        // Should be boolean (true/false depending on signature validation)
        $this->assertIsBool($verified);
    }
    
    /**
     * Test getting webhook data
     */
    public function test_get_webhook_data() {
        // Mock POST data
        $test_data = array(
            'event_type' => 'payment.completed',
            'payment_id' => 'test_payment_123',
            'customer_email' => 'test@example.com'
        );
        
        // Mock php://input
        if (function_exists('stream_wrapper_unregister')) {
            stream_wrapper_unregister('php');
            stream_wrapper_register('php', 'MockPhpStream');
            file_put_contents('php://input', json_encode($test_data));
        }
        
        $webhook_data = $this->webhook_handler->get_webhook_data();
        
        // Should return array or empty array
        $this->assertIsArray($webhook_data);
        
        // Restore php stream wrapper
        if (function_exists('stream_wrapper_restore')) {
            stream_wrapper_restore('php');
        }
    }
    
    /**
     * Test webhook processing
     */
    public function test_process_webhook() {
        $webhook_data = $this->create_mock_webhook();
        
        $result = $this->webhook_handler->process_webhook($webhook_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if (!$result['success']) {
            $this->assertArrayHasKey('error', $result);
        }
    }
    
    /**
     * Test payment completion webhook processing
     */
    public function test_process_payment_completed_webhook() {
        $webhook_data = $this->create_mock_webhook(array(
            'event_type' => 'payment.completed',
            'payment_id' => 'payment_123',
            'customer_email' => 'test@example.com',
            'amount' => 99.00,
            'currency' => 'USD',
            'product_id' => 'test_product'
        ));
        
        $result = $this->webhook_handler->process_payment_completed($webhook_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        // Check if user enrollment was triggered
        if ($result['success']) {
            $this->assertArrayHasKey('user_id', $result);
            $this->assertArrayHasKey('enrollment_result', $result);
        }
    }
    
    /**
     * Test subscription created webhook processing
     */
    public function test_process_subscription_created_webhook() {
        $webhook_data = $this->create_mock_webhook(array(
            'event_type' => 'subscription.created',
            'subscription_id' => 'sub_123',
            'customer_email' => 'test@example.com',
            'plan_id' => 'monthly_plan'
        ));
        
        $result = $this->webhook_handler->process_subscription_created($webhook_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test subscription cancelled webhook processing
     */
    public function test_process_subscription_cancelled_webhook() {
        $webhook_data = $this->create_mock_webhook(array(
            'event_type' => 'subscription.cancelled',
            'subscription_id' => 'sub_123',
            'customer_email' => 'test@example.com'
        ));
        
        $result = $this->webhook_handler->process_subscription_cancelled($webhook_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test refund processed webhook
     */
    public function test_process_refund_webhook() {
        $webhook_data = $this->create_mock_webhook(array(
            'event_type' => 'payment.refunded',
            'payment_id' => 'payment_123',
            'refund_id' => 'refund_123',
            'amount' => 99.00,
            'reason' => 'Customer request'
        ));
        
        $result = $this->webhook_handler->process_refund($webhook_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
    }
    
    /**
     * Test webhook response sending
     */
    public function test_send_webhook_response() {
        // Mock headers_sent function
        if (!function_exists('headers_sent')) {
            function headers_sent() { return false; }
        }
        
        $response_data = array('message' => 'Success');
        
        // Capture output
        ob_start();
        $this->webhook_handler->send_webhook_response(200, $response_data);
        $output = ob_get_clean();
        
        $this->assertIsString($output);
        $this->assertJson($output);
        
        $decoded = json_decode($output, true);
        $this->assertEquals('Success', $decoded['message']);
    }
    
    /**
     * Test webhook logging
     */
    public function test_log_webhook_event() {
        $webhook_data = $this->create_mock_webhook();
        $result = array('success' => true, 'message' => 'Processed successfully');
        
        $this->webhook_handler->log_webhook_event($webhook_data, $result);
        
        // Check if log entry was created
        $this->assertLogEntryCreated('user_activity', 'webhook');
    }
    
    /**
     * Test webhook event validation
     */
    public function test_validate_webhook_event() {
        // Test valid webhook data
        $valid_data = $this->create_mock_webhook();
        $is_valid = $this->webhook_handler->validate_webhook_event($valid_data);
        $this->assertTrue($is_valid);
        
        // Test invalid webhook data
        $invalid_data = array(
            'event_type' => '', // Missing event type
            'customer_email' => 'invalid-email' // Invalid email
        );
        $is_valid = $this->webhook_handler->validate_webhook_event($invalid_data);
        $this->assertFalse($is_valid);
        
        // Test missing required fields
        $incomplete_data = array(
            'event_type' => 'payment.completed'
            // Missing customer_email and other required fields
        );
        $is_valid = $this->webhook_handler->validate_webhook_event($incomplete_data);
        $this->assertFalse($is_valid);
    }
    
    /**
     * Test webhook rate limiting
     */
    public function test_webhook_rate_limiting() {
        $ip_address = '127.0.0.1';
        
        // Test initial request (should be allowed)
        $allowed = $this->webhook_handler->check_rate_limit($ip_address);
        $this->assertTrue($allowed);
        
        // Test multiple rapid requests (should eventually be limited)
        $request_count = 0;
        for ($i = 0; $i < 20; $i++) {
            if ($this->webhook_handler->check_rate_limit($ip_address)) {
                $request_count++;
            }
        }
        
        // Should have some limit (less than 20 requests allowed)
        $this->assertLessThan(20, $request_count);
    }
    
    /**
     * Test webhook retry mechanism
     */
    public function test_webhook_retry_mechanism() {
        $webhook_data = $this->create_mock_webhook();
        
        // Mock failed processing
        $failed_result = array('success' => false, 'error' => 'Processing failed');
        
        $retry_result = $this->webhook_handler->handle_webhook_retry($webhook_data, $failed_result);
        
        $this->assertIsArray($retry_result);
        $this->assertArrayHasKey('retry_scheduled', $retry_result);
        $this->assertIsBool($retry_result['retry_scheduled']);
    }
    
    /**
     * Test webhook duplicate detection
     */
    public function test_webhook_duplicate_detection() {
        $webhook_data = $this->create_mock_webhook();
        
        // First occurrence should not be duplicate
        $is_duplicate = $this->webhook_handler->is_duplicate_webhook($webhook_data);
        $this->assertFalse($is_duplicate);
        
        // Process the webhook
        $this->webhook_handler->process_webhook($webhook_data);
        
        // Second occurrence should be detected as duplicate
        $is_duplicate = $this->webhook_handler->is_duplicate_webhook($webhook_data);
        $this->assertTrue($is_duplicate);
    }
    
    /**
     * Test webhook error handling
     */
    public function test_webhook_error_handling() {
        // Test with malformed data
        $malformed_data = array(
            'invalid_field' => 'value'
        );
        
        $result = $this->webhook_handler->process_webhook($malformed_data);
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
        $this->assertIsString($result['error']);
    }
    
    /**
     * Test webhook status endpoint
     */
    public function test_webhook_status() {
        $status = $this->webhook_handler->get_webhook_status();
        
        $this->assertIsArray($status);
        $this->assertArrayHasKey('enabled', $status);
        $this->assertArrayHasKey('endpoint_url', $status);
        $this->assertArrayHasKey('recent_events', $status);
        $this->assertArrayHasKey('error_rate', $status);
        
        $this->assertIsBool($status['enabled']);
        $this->assertIsString($status['endpoint_url']);
        $this->assertIsArray($status['recent_events']);
        $this->assertIsFloat($status['error_rate']);
    }
}

/**
 * Mock PHP stream class for testing
 */
class MockPhpStream {
    private $data;
    private $position;
    
    public function stream_open($path, $mode, $options, &$opened_path) {
        return true;
    }
    
    public function stream_read($count) {
        $ret = substr($this->data, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }
    
    public function stream_write($data) {
        $this->data = $data;
        return strlen($data);
    }
    
    public function stream_tell() {
        return $this->position;
    }
    
    public function stream_eof() {
        return $this->position >= strlen($this->data);
    }
    
    public function stream_seek($offset, $whence) {
        switch ($whence) {
            case SEEK_SET:
                $this->position = $offset;
                break;
            case SEEK_CUR:
                $this->position += $offset;
                break;
            case SEEK_END:
                $this->position = strlen($this->data) + $offset;
                break;
        }
        return true;
    }
}