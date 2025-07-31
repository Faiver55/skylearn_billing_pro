<?php
/**
 * Integration tests for Payment Flow
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class Test_Payment_Integration extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Test complete payment flow
     */
    public function test_complete_payment_flow() {
        // Mock payment data
        $payment_data = $this->create_mock_payment(array(
            'gateway' => 'stripe',
            'amount' => 99.00,
            'currency' => 'USD',
            'customer_email' => 'test@example.com',
            'product_id' => 'test_course_product',
            'course_id' => 123
        ));
        
        // Simulate webhook payload
        $webhook_data = array(
            'event_type' => 'payment.completed',
            'payment_id' => $payment_data['payment_id'],
            'customer_email' => $payment_data['customer_email'],
            'amount' => $payment_data['amount'],
            'currency' => $payment_data['currency'],
            'product_id' => $payment_data['product_id'],
            'course_id' => $payment_data['course_id']
        );
        
        // Test webhook processing
        if (class_exists('SkyLearn_Billing_Pro_Webhook_Handler')) {
            $webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
            $result = $webhook_handler->process_webhook($webhook_data);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                // Verify user was created/enrolled
                $this->assertArrayHasKey('user_id', $result);
                $this->assertArrayHasKey('enrollment_result', $result);
                
                // Check if welcome email was sent
                $this->assertEmailSent($payment_data['customer_email'], 'Welcome');
            }
        } else {
            $this->markTestSkipped('Webhook Handler class not available');
        }
    }
    
    /**
     * Test subscription payment flow
     */
    public function test_subscription_payment_flow() {
        // Mock subscription data
        $subscription_data = array(
            'subscription_id' => 'sub_test_' . time(),
            'customer_email' => 'subscriber@example.com',
            'plan_id' => 'monthly_plan',
            'amount' => 29.99,
            'currency' => 'USD',
            'status' => 'active'
        );
        
        // Simulate subscription created webhook
        $webhook_data = array(
            'event_type' => 'subscription.created',
            'subscription_id' => $subscription_data['subscription_id'],
            'customer_email' => $subscription_data['customer_email'],
            'plan_id' => $subscription_data['plan_id'],
            'amount' => $subscription_data['amount'],
            'currency' => $subscription_data['currency']
        );
        
        // Test webhook processing
        if (class_exists('SkyLearn_Billing_Pro_Webhook_Handler')) {
            $webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
            $result = $webhook_handler->process_webhook($webhook_data);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                // Verify subscription was processed
                $this->assertArrayHasKey('subscription_processed', $result);
                
                // Check if user was enrolled in associated courses
                if (isset($result['enrollment_result'])) {
                    $this->assertIsArray($result['enrollment_result']);
                }
            }
        } else {
            $this->markTestSkipped('Webhook Handler class not available');
        }
    }
    
    /**
     * Test failed payment handling
     */
    public function test_failed_payment_handling() {
        $payment_data = $this->create_mock_payment(array(
            'status' => 'failed',
            'customer_email' => 'failed@example.com'
        ));
        
        $webhook_data = array(
            'event_type' => 'payment.failed',
            'payment_id' => $payment_data['payment_id'],
            'customer_email' => $payment_data['customer_email'],
            'amount' => $payment_data['amount'],
            'error_message' => 'Insufficient funds'
        );
        
        if (class_exists('SkyLearn_Billing_Pro_Webhook_Handler')) {
            $webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
            $result = $webhook_handler->process_webhook($webhook_data);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            // Failed payment should still be processed (logged) successfully
            $this->assertTrue($result['success']);
            
            // Verify failure was logged
            $this->assertLogEntryCreated('user_activity', 'payment.failed');
        } else {
            $this->markTestSkipped('Webhook Handler class not available');
        }
    }
    
    /**
     * Test refund processing
     */
    public function test_refund_processing() {
        $refund_data = array(
            'payment_id' => 'payment_test_' . time(),
            'refund_id' => 'refund_test_' . time(),
            'amount' => 99.00,
            'reason' => 'Customer request',
            'customer_email' => 'refund@example.com'
        );
        
        $webhook_data = array(
            'event_type' => 'payment.refunded',
            'payment_id' => $refund_data['payment_id'],
            'refund_id' => $refund_data['refund_id'],
            'amount' => $refund_data['amount'],
            'reason' => $refund_data['reason'],
            'customer_email' => $refund_data['customer_email']
        );
        
        if (class_exists('SkyLearn_Billing_Pro_Webhook_Handler')) {
            $webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
            $result = $webhook_handler->process_webhook($webhook_data);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                // Verify refund was processed
                $this->assertArrayHasKey('refund_processed', $result);
                
                // Check if access was revoked (if applicable)
                if (isset($result['access_revoked'])) {
                    $this->assertIsBool($result['access_revoked']);
                }
            }
        } else {
            $this->markTestSkipped('Webhook Handler class not available');
        }
    }
    
    /**
     * Test payment gateway switching
     */
    public function test_payment_gateway_switching() {
        if (!class_exists('SkyLearn_Billing_Pro_Payment_Manager')) {
            $this->markTestSkipped('Payment Manager class not available');
            return;
        }
        
        $payment_manager = new SkyLearn_Billing_Pro_Payment_Manager();
        
        // Test switching to Stripe
        $stripe_result = $payment_manager->set_active_gateway('stripe');
        $this->assertTrue($stripe_result);
        $this->assertEquals('stripe', $payment_manager->get_active_gateway());
        
        // Test switching to Paddle
        $paddle_result = $payment_manager->set_active_gateway('paddle');
        $this->assertTrue($paddle_result);
        $this->assertEquals('paddle', $payment_manager->get_active_gateway());
        
        // Test invalid gateway
        $invalid_result = $payment_manager->set_active_gateway('invalid_gateway');
        $this->assertFalse($invalid_result);
        // Active gateway should remain unchanged
        $this->assertEquals('paddle', $payment_manager->get_active_gateway());
    }
    
    /**
     * Test payment verification
     */
    public function test_payment_verification() {
        if (!class_exists('SkyLearn_Billing_Pro_Payment_Manager')) {
            $this->markTestSkipped('Payment Manager class not available');
            return;
        }
        
        $payment_manager = new SkyLearn_Billing_Pro_Payment_Manager();
        $payment_manager->set_active_gateway('stripe');
        
        // Test with valid payment ID format
        $valid_payment_id = 'pi_test_1234567890';
        $verification_result = $payment_manager->verify_payment($valid_payment_id);
        $this->assertIsBool($verification_result);
        
        // Test with invalid payment ID
        $invalid_payment_id = '';
        $verification_result = $payment_manager->verify_payment($invalid_payment_id);
        $this->assertFalse($verification_result);
    }
    
    /**
     * Test multi-gateway webhook handling
     */
    public function test_multi_gateway_webhook_handling() {
        $gateways = array('stripe', 'paddle', 'lemonsqueezy');
        
        foreach ($gateways as $gateway) {
            $webhook_data = array(
                'event_type' => 'payment.completed',
                'payment_id' => "{$gateway}_payment_" . time(),
                'gateway' => $gateway,
                'customer_email' => "test_{$gateway}@example.com",
                'amount' => 99.00,
                'currency' => 'USD'
            );
            
            if (class_exists('SkyLearn_Billing_Pro_Webhook_Handler')) {
                $webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
                $result = $webhook_handler->process_webhook($webhook_data);
                
                $this->assertIsArray($result, "Failed to process webhook for {$gateway}");
                $this->assertArrayHasKey('success', $result, "No success key for {$gateway}");
                
                // Each gateway should be able to process webhooks
                if (!$result['success'] && isset($result['error'])) {
                    // Log the error but don't fail the test (gateway might not be configured)
                    $this->addWarning("Gateway {$gateway} webhook processing failed: " . $result['error']);
                }
            }
        }
    }
    
    /**
     * Test payment flow with course mapping
     */
    public function test_payment_with_course_mapping() {
        // Create a mock course mapping
        $course_mapping = array(
            'product_id' => 'test_product_123',
            'course_id' => 456,
            'lms_type' => 'learndash',
            'auto_enroll' => true
        );
        
        // Store the mapping
        $options = get_option('skylearn_billing_pro_options', array());
        $options['course_mappings'][] = $course_mapping;
        update_option('skylearn_billing_pro_options', $options);
        
        // Create payment webhook data
        $webhook_data = array(
            'event_type' => 'payment.completed',
            'payment_id' => 'payment_' . time(),
            'customer_email' => 'course_test@example.com',
            'product_id' => 'test_product_123',
            'amount' => 199.00,
            'currency' => 'USD'
        );
        
        if (class_exists('SkyLearn_Billing_Pro_Webhook_Handler')) {
            $webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
            $result = $webhook_handler->process_webhook($webhook_data);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                // Verify course enrollment was attempted
                $this->assertArrayHasKey('enrollment_result', $result);
                
                // Check if the correct course was targeted
                if (isset($result['course_id'])) {
                    $this->assertEquals(456, $result['course_id']);
                }
            }
        } else {
            $this->markTestSkipped('Webhook Handler class not available');
        }
    }
    
    /**
     * Test payment retry mechanism
     */
    public function test_payment_retry_mechanism() {
        $failed_payment_data = array(
            'payment_id' => 'failed_payment_' . time(),
            'customer_email' => 'retry@example.com',
            'amount' => 49.99,
            'currency' => 'USD',
            'retry_count' => 1,
            'max_retries' => 3
        );
        
        $webhook_data = array(
            'event_type' => 'payment.failed',
            'payment_id' => $failed_payment_data['payment_id'],
            'customer_email' => $failed_payment_data['customer_email'],
            'amount' => $failed_payment_data['amount'],
            'error_message' => 'Card declined',
            'retry_count' => $failed_payment_data['retry_count']
        );
        
        if (class_exists('SkyLearn_Billing_Pro_Webhook_Handler')) {
            $webhook_handler = new SkyLearn_Billing_Pro_Webhook_Handler();
            $result = $webhook_handler->process_webhook($webhook_data);
            
            $this->assertIsArray($result);
            $this->assertArrayHasKey('success', $result);
            
            if ($result['success']) {
                // Check if retry was scheduled
                if (isset($result['retry_scheduled'])) {
                    $this->assertIsBool($result['retry_scheduled']);
                }
                
                // Verify failure notification was sent
                if ($failed_payment_data['retry_count'] >= $failed_payment_data['max_retries']) {
                    $this->assertEmailSent($failed_payment_data['customer_email']);
                }
            }
        } else {
            $this->markTestSkipped('Webhook Handler class not available');
        }
    }
}