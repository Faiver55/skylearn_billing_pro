<?php
/**
 * Unit tests for Subscription Manager
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/subscriptions/class-subscription-manager.php';

class Test_Subscription_Manager extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Subscription Manager instance
     *
     * @var SkyLearn_Billing_Pro_Subscription_Manager
     */
    private $subscription_manager;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        $this->subscription_manager = new SkyLearn_Billing_Pro_Subscription_Manager();
    }
    
    /**
     * Test Subscription Manager instantiation
     */
    public function test_subscription_manager_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Subscription_Manager', $this->subscription_manager);
    }
    
    /**
     * Test creating subscription
     */
    public function test_create_subscription() {
        $subscription_data = array(
            'customer_id' => 123,
            'customer_email' => 'test@example.com',
            'plan_id' => 'monthly_plan',
            'payment_method_id' => 'pm_test_123',
            'trial_days' => 7,
            'coupon_code' => 'SAVE10'
        );
        
        $result = $this->subscription_manager->create_subscription($subscription_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('subscription_id', $result);
            $this->assertArrayHasKey('subscription_data', $result);
            $this->assertIsString($result['subscription_id']);
            $this->assertIsArray($result['subscription_data']);
        } else {
            $this->assertArrayHasKey('error', $result);
        }
    }
    
    /**
     * Test getting subscription details
     */
    public function test_get_subscription() {
        $subscription_id = 'sub_test_123';
        
        $subscription = $this->subscription_manager->get_subscription($subscription_id);
        
        // Should return array or null
        $this->assertTrue($subscription === null || is_array($subscription));
        
        if (is_array($subscription)) {
            $this->assertArrayHasKey('id', $subscription);
            $this->assertArrayHasKey('customer_id', $subscription);
            $this->assertArrayHasKey('plan_id', $subscription);
            $this->assertArrayHasKey('status', $subscription);
            $this->assertArrayHasKey('current_period_start', $subscription);
            $this->assertArrayHasKey('current_period_end', $subscription);
        }
    }
    
    /**
     * Test updating subscription
     */
    public function test_update_subscription() {
        $subscription_id = 'sub_test_123';
        $update_data = array(
            'plan_id' => 'yearly_plan',
            'quantity' => 2
        );
        
        $result = $this->subscription_manager->update_subscription($subscription_id, $update_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if (!$result['success']) {
            $this->assertArrayHasKey('error', $result);
        }
    }
    
    /**
     * Test canceling subscription
     */
    public function test_cancel_subscription() {
        $subscription_id = 'sub_test_123';
        $cancel_at_period_end = true;
        $reason = 'Customer request';
        
        $result = $this->subscription_manager->cancel_subscription($subscription_id, $cancel_at_period_end, $reason);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('canceled_at', $result);
            $this->assertArrayHasKey('cancel_at_period_end', $result);
        }
    }
    
    /**
     * Test pausing subscription
     */
    public function test_pause_subscription() {
        $subscription_id = 'sub_test_123';
        $pause_behavior = 'keep_as_draft'; // or 'mark_uncollectible'
        
        $result = $this->subscription_manager->pause_subscription($subscription_id, $pause_behavior);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('paused_at', $result);
            $this->assertArrayHasKey('pause_behavior', $result);
        }
    }
    
    /**
     * Test resuming subscription
     */
    public function test_resume_subscription() {
        $subscription_id = 'sub_test_123';
        $proration_behavior = 'create_prorations';
        
        $result = $this->subscription_manager->resume_subscription($subscription_id, $proration_behavior);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('resumed_at', $result);
        }
    }
    
    /**
     * Test subscription plan management
     */
    public function test_subscription_plans() {
        // Get all plans
        $plans = $this->subscription_manager->get_subscription_plans();
        
        $this->assertIsArray($plans);
        
        // Test creating a plan
        $plan_data = array(
            'id' => 'test_plan_123',
            'name' => 'Test Plan',
            'amount' => 2999, // $29.99
            'currency' => 'USD',
            'interval' => 'month',
            'interval_count' => 1,
            'trial_period_days' => 7
        );
        
        $plan_result = $this->subscription_manager->create_subscription_plan($plan_data);
        
        $this->assertIsArray($plan_result);
        $this->assertArrayHasKey('success', $plan_result);
        $this->assertIsBool($plan_result['success']);
        
        if ($plan_result['success']) {
            $this->assertArrayHasKey('plan_id', $plan_result);
            $this->assertArrayHasKey('plan_data', $plan_result);
        }
    }
    
    /**
     * Test subscription invoice management
     */
    public function test_subscription_invoices() {
        $subscription_id = 'sub_test_123';
        
        // Get subscription invoices
        $invoices = $this->subscription_manager->get_subscription_invoices($subscription_id);
        
        $this->assertIsArray($invoices);
        
        foreach ($invoices as $invoice) {
            $this->assertArrayHasKey('id', $invoice);
            $this->assertArrayHasKey('amount_paid', $invoice);
            $this->assertArrayHasKey('amount_due', $invoice);
            $this->assertArrayHasKey('status', $invoice);
            $this->assertArrayHasKey('created', $invoice);
        }
        
        // Test retrieving specific invoice
        if (!empty($invoices)) {
            $invoice_id = $invoices[0]['id'];
            $invoice = $this->subscription_manager->get_invoice($invoice_id);
            
            $this->assertIsArray($invoice);
            $this->assertArrayHasKey('id', $invoice);
            $this->assertArrayHasKey('lines', $invoice);
        }
    }
    
    /**
     * Test subscription metrics and analytics
     */
    public function test_subscription_metrics() {
        $metrics = $this->subscription_manager->get_subscription_metrics();
        
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('total_subscriptions', $metrics);
        $this->assertArrayHasKey('active_subscriptions', $metrics);
        $this->assertArrayHasKey('canceled_subscriptions', $metrics);
        $this->assertArrayHasKey('paused_subscriptions', $metrics);
        $this->assertArrayHasKey('monthly_recurring_revenue', $metrics);
        $this->assertArrayHasKey('annual_recurring_revenue', $metrics);
        $this->assertArrayHasKey('churn_rate', $metrics);
        $this->assertArrayHasKey('average_subscription_value', $metrics);
        
        $this->assertIsInt($metrics['total_subscriptions']);
        $this->assertIsInt($metrics['active_subscriptions']);
        $this->assertIsInt($metrics['canceled_subscriptions']);
        $this->assertIsInt($metrics['paused_subscriptions']);
        $this->assertIsFloat($metrics['monthly_recurring_revenue']);
        $this->assertIsFloat($metrics['annual_recurring_revenue']);
        $this->assertIsFloat($metrics['churn_rate']);
        $this->assertIsFloat($metrics['average_subscription_value']);
    }
    
    /**
     * Test subscription webhooks
     */
    public function test_subscription_webhooks() {
        $webhook_data = array(
            'type' => 'invoice.payment_succeeded',
            'data' => array(
                'object' => array(
                    'id' => 'in_test_123',
                    'subscription' => 'sub_test_123',
                    'amount_paid' => 2999,
                    'status' => 'paid'
                )
            )
        );
        
        $result = $this->subscription_manager->handle_subscription_webhook($webhook_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('actions_taken', $result);
            $this->assertIsArray($result['actions_taken']);
        }
    }
    
    /**
     * Test subscription customer management
     */
    public function test_subscription_customers() {
        $customer_id = 123;
        
        // Get customer subscriptions
        $subscriptions = $this->subscription_manager->get_customer_subscriptions($customer_id);
        
        $this->assertIsArray($subscriptions);
        
        // Get customer subscription history
        $history = $this->subscription_manager->get_customer_subscription_history($customer_id);
        
        $this->assertIsArray($history);
        
        foreach ($history as $history_item) {
            $this->assertArrayHasKey('subscription_id', $history_item);
            $this->assertArrayHasKey('event_type', $history_item);
            $this->assertArrayHasKey('event_date', $history_item);
            $this->assertArrayHasKey('details', $history_item);
        }
    }
    
    /**
     * Test subscription trial management
     */
    public function test_subscription_trials() {
        $subscription_id = 'sub_test_123';
        
        // Extend trial
        $extend_days = 7;
        $result = $this->subscription_manager->extend_trial($subscription_id, $extend_days);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        // End trial early
        $end_result = $this->subscription_manager->end_trial($subscription_id);
        
        $this->assertIsArray($end_result);
        $this->assertArrayHasKey('success', $end_result);
        $this->assertIsBool($end_result['success']);
    }
    
    /**
     * Test subscription coupons and discounts
     */
    public function test_subscription_coupons() {
        $subscription_id = 'sub_test_123';
        $coupon_code = 'SAVE20';
        
        // Apply coupon to subscription
        $result = $this->subscription_manager->apply_coupon($subscription_id, $coupon_code);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('discount_amount', $result);
            $this->assertArrayHasKey('coupon_details', $result);
        }
        
        // Remove coupon from subscription
        $remove_result = $this->subscription_manager->remove_coupon($subscription_id);
        
        $this->assertIsArray($remove_result);
        $this->assertArrayHasKey('success', $remove_result);
        $this->assertIsBool($remove_result['success']);
    }
    
    /**
     * Test subscription add-ons and usage-based billing
     */
    public function test_subscription_addons() {
        $subscription_id = 'sub_test_123';
        
        // Add usage-based item
        $usage_item = array(
            'price_id' => 'price_usage_123',
            'quantity' => 10
        );
        
        $result = $this->subscription_manager->add_usage_item($subscription_id, $usage_item);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        // Update usage quantity
        $usage_record = array(
            'quantity' => 15,
            'timestamp' => time(),
            'action' => 'set'
        );
        
        $usage_result = $this->subscription_manager->record_usage($subscription_id, 'price_usage_123', $usage_record);
        
        $this->assertIsArray($usage_result);
        $this->assertArrayHasKey('success', $usage_result);
        $this->assertIsBool($usage_result['success']);
    }
    
    /**
     * Test subscription renewal notifications
     */
    public function test_subscription_notifications() {
        $subscription_id = 'sub_test_123';
        
        // Check for upcoming renewals
        $upcoming_renewals = $this->subscription_manager->get_upcoming_renewals(7); // Next 7 days
        
        $this->assertIsArray($upcoming_renewals);
        
        foreach ($upcoming_renewals as $renewal) {
            $this->assertArrayHasKey('subscription_id', $renewal);
            $this->assertArrayHasKey('customer_email', $renewal);
            $this->assertArrayHasKey('renewal_date', $renewal);
            $this->assertArrayHasKey('amount', $renewal);
        }
        
        // Send renewal reminder
        $reminder_result = $this->subscription_manager->send_renewal_reminder($subscription_id);
        
        $this->assertIsArray($reminder_result);
        $this->assertArrayHasKey('success', $reminder_result);
        $this->assertIsBool($reminder_result['success']);
    }
    
    /**
     * Test subscription dunning management
     */
    public function test_subscription_dunning() {
        $subscription_id = 'sub_test_123';
        
        // Get failed payments
        $failed_payments = $this->subscription_manager->get_failed_payments($subscription_id);
        
        $this->assertIsArray($failed_payments);
        
        // Retry failed payment
        if (!empty($failed_payments)) {
            $payment_intent_id = $failed_payments[0]['payment_intent_id'];
            $retry_result = $this->subscription_manager->retry_payment($payment_intent_id);
            
            $this->assertIsArray($retry_result);
            $this->assertArrayHasKey('success', $retry_result);
            $this->assertIsBool($retry_result['success']);
        }
        
        // Update dunning settings
        $dunning_settings = array(
            'max_retries' => 3,
            'retry_schedule' => array(3, 5, 7), // Days between retries
            'send_notifications' => true
        );
        
        $settings_result = $this->subscription_manager->update_dunning_settings($subscription_id, $dunning_settings);
        
        $this->assertIsArray($settings_result);
        $this->assertArrayHasKey('success', $settings_result);
        $this->assertIsBool($settings_result['success']);
    }
    
    /**
     * Test subscription validation
     */
    public function test_subscription_validation() {
        // Test valid subscription data
        $valid_data = array(
            'customer_email' => 'test@example.com',
            'plan_id' => 'monthly_plan',
            'payment_method_id' => 'pm_test_123'
        );
        
        $validation = $this->subscription_manager->validate_subscription_data($valid_data);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        
        // Test invalid subscription data
        $invalid_data = array(
            'customer_email' => 'invalid-email',
            'plan_id' => '',
            'payment_method_id' => ''
        );
        
        $validation = $this->subscription_manager->validate_subscription_data($invalid_data);
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }
    
    /**
     * Test bulk subscription operations
     */
    public function test_bulk_subscription_operations() {
        $subscription_ids = array('sub_1', 'sub_2', 'sub_3');
        
        // Bulk cancel
        $cancel_result = $this->subscription_manager->bulk_cancel_subscriptions($subscription_ids, true, 'Bulk cancellation');
        
        $this->assertIsArray($cancel_result);
        $this->assertArrayHasKey('success_count', $cancel_result);
        $this->assertArrayHasKey('failure_count', $cancel_result);
        $this->assertArrayHasKey('results', $cancel_result);
        
        // Bulk update
        $update_data = array('metadata' => array('bulk_updated' => 'true'));
        $update_result = $this->subscription_manager->bulk_update_subscriptions($subscription_ids, $update_data);
        
        $this->assertIsArray($update_result);
        $this->assertArrayHasKey('success_count', $update_result);
        $this->assertArrayHasKey('failure_count', $update_result);
    }
}