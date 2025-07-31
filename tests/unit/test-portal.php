<?php
/**
 * Unit tests for Portal functionality
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/portal/class-nurture-popup.php';

class Test_Portal extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Portal instance
     *
     * @var SkyLearn_Billing_Pro_Nurture_Popup
     */
    private $portal;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        $this->portal = new SkyLearn_Billing_Pro_Nurture_Popup();
    }
    
    /**
     * Test Portal instantiation
     */
    public function test_portal_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Nurture_Popup', $this->portal);
    }
    
    /**
     * Test customer authentication
     */
    public function test_customer_authentication() {
        $customer_email = 'test@example.com';
        $password = 'password123';
        
        // Test login validation
        $auth_result = $this->portal->authenticate_customer($customer_email, $password);
        
        $this->assertIsArray($auth_result);
        $this->assertArrayHasKey('success', $auth_result);
        $this->assertIsBool($auth_result['success']);
        
        if (!$auth_result['success']) {
            $this->assertArrayHasKey('error', $auth_result);
        } else {
            $this->assertArrayHasKey('customer_id', $auth_result);
            $this->assertArrayHasKey('customer_data', $auth_result);
        }
    }
    
    /**
     * Test customer dashboard data
     */
    public function test_get_customer_dashboard_data() {
        $customer_id = 123;
        
        $dashboard_data = $this->portal->get_customer_dashboard_data($customer_id);
        
        $this->assertIsArray($dashboard_data);
        $this->assertArrayHasKey('customer_info', $dashboard_data);
        $this->assertArrayHasKey('recent_orders', $dashboard_data);
        $this->assertArrayHasKey('active_subscriptions', $dashboard_data);
        $this->assertArrayHasKey('available_courses', $dashboard_data);
        $this->assertArrayHasKey('download_links', $dashboard_data);
        
        $this->assertIsArray($dashboard_data['customer_info']);
        $this->assertIsArray($dashboard_data['recent_orders']);
        $this->assertIsArray($dashboard_data['active_subscriptions']);
        $this->assertIsArray($dashboard_data['available_courses']);
        $this->assertIsArray($dashboard_data['download_links']);
    }
    
    /**
     * Test order history retrieval
     */
    public function test_get_order_history() {
        $customer_id = 123;
        $page = 1;
        $per_page = 10;
        
        $order_history = $this->portal->get_order_history($customer_id, $page, $per_page);
        
        $this->assertIsArray($order_history);
        $this->assertArrayHasKey('orders', $order_history);
        $this->assertArrayHasKey('total_count', $order_history);
        $this->assertArrayHasKey('page', $order_history);
        $this->assertArrayHasKey('per_page', $order_history);
        $this->assertArrayHasKey('total_pages', $order_history);
        
        $this->assertIsArray($order_history['orders']);
        $this->assertIsInt($order_history['total_count']);
        $this->assertIsInt($order_history['page']);
        $this->assertIsInt($order_history['per_page']);
        $this->assertIsInt($order_history['total_pages']);
    }
    
    /**
     * Test subscription management
     */
    public function test_subscription_management() {
        $customer_id = 123;
        
        // Get active subscriptions
        $subscriptions = $this->portal->get_customer_subscriptions($customer_id);
        
        $this->assertIsArray($subscriptions);
        
        // Test subscription cancellation
        $subscription_id = 'sub_test_123';
        $cancel_result = $this->portal->cancel_subscription($subscription_id, $customer_id);
        
        $this->assertIsArray($cancel_result);
        $this->assertArrayHasKey('success', $cancel_result);
        $this->assertIsBool($cancel_result['success']);
        
        // Test subscription pause
        $pause_result = $this->portal->pause_subscription($subscription_id, $customer_id);
        
        $this->assertIsArray($pause_result);
        $this->assertArrayHasKey('success', $pause_result);
        $this->assertIsBool($pause_result['success']);
        
        // Test subscription resume
        $resume_result = $this->portal->resume_subscription($subscription_id, $customer_id);
        
        $this->assertIsArray($resume_result);
        $this->assertArrayHasKey('success', $resume_result);
        $this->assertIsBool($resume_result['success']);
    }
    
    /**
     * Test profile update
     */
    public function test_update_customer_profile() {
        $customer_id = 123;
        $profile_data = array(
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@example.com',
            'phone' => '+1234567890'
        );
        
        $result = $this->portal->update_customer_profile($customer_id, $profile_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if (!$result['success']) {
            $this->assertArrayHasKey('errors', $result);
            $this->assertIsArray($result['errors']);
        }
    }
    
    /**
     * Test address management
     */
    public function test_address_management() {
        $customer_id = 123;
        
        // Test getting addresses
        $addresses = $this->portal->get_customer_addresses($customer_id);
        
        $this->assertIsArray($addresses);
        
        // Test adding new address
        $address_data = array(
            'type' => 'billing',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US'
        );
        
        $add_result = $this->portal->add_customer_address($customer_id, $address_data);
        
        $this->assertIsArray($add_result);
        $this->assertArrayHasKey('success', $add_result);
        $this->assertIsBool($add_result['success']);
        
        if ($add_result['success']) {
            $this->assertArrayHasKey('address_id', $add_result);
        }
        
        // Test updating address
        if (isset($add_result['address_id'])) {
            $address_id = $add_result['address_id'];
            $updated_data = array('city' => 'Brooklyn');
            
            $update_result = $this->portal->update_customer_address($customer_id, $address_id, $updated_data);
            
            $this->assertIsArray($update_result);
            $this->assertArrayHasKey('success', $update_result);
            $this->assertIsBool($update_result['success']);
        }
    }
    
    /**
     * Test download links
     */
    public function test_download_links() {
        $customer_id = 123;
        
        $downloads = $this->portal->get_customer_downloads($customer_id);
        
        $this->assertIsArray($downloads);
        
        // Each download should have required fields
        foreach ($downloads as $download) {
            $this->assertArrayHasKey('product_name', $download);
            $this->assertArrayHasKey('download_url', $download);
            $this->assertArrayHasKey('expires_at', $download);
            $this->assertArrayHasKey('download_limit', $download);
            $this->assertArrayHasKey('downloads_remaining', $download);
        }
    }
    
    /**
     * Test nurture popup functionality
     */
    public function test_nurture_popup() {
        $customer_id = 123;
        $action = 'cancel_subscription';
        $subscription_id = 'sub_test_123';
        
        // Test getting nurture popup content
        $popup_content = $this->portal->get_nurture_popup_content($action, $customer_id, $subscription_id);
        
        $this->assertIsArray($popup_content);
        $this->assertArrayHasKey('title', $popup_content);
        $this->assertArrayHasKey('content', $popup_content);
        $this->assertArrayHasKey('offers', $popup_content);
        $this->assertArrayHasKey('actions', $popup_content);
        
        $this->assertIsString($popup_content['title']);
        $this->assertIsString($popup_content['content']);
        $this->assertIsArray($popup_content['offers']);
        $this->assertIsArray($popup_content['actions']);
    }
    
    /**
     * Test cancellation flow
     */
    public function test_cancellation_flow() {
        $customer_id = 123;
        $subscription_id = 'sub_test_123';
        $reason = 'Too expensive';
        
        // Start cancellation process
        $cancel_flow = $this->portal->start_cancellation_flow($customer_id, $subscription_id, $reason);
        
        $this->assertIsArray($cancel_flow);
        $this->assertArrayHasKey('flow_id', $cancel_flow);
        $this->assertArrayHasKey('steps', $cancel_flow);
        $this->assertArrayHasKey('current_step', $cancel_flow);
        
        $this->assertIsString($cancel_flow['flow_id']);
        $this->assertIsArray($cancel_flow['steps']);
        $this->assertIsInt($cancel_flow['current_step']);
    }
    
    /**
     * Test upgrade/downgrade offers
     */
    public function test_upgrade_downgrade_offers() {
        $customer_id = 123;
        $current_plan = 'basic_monthly';
        
        // Get upgrade offers
        $upgrade_offers = $this->portal->get_upgrade_offers($customer_id, $current_plan);
        
        $this->assertIsArray($upgrade_offers);
        
        foreach ($upgrade_offers as $offer) {
            $this->assertArrayHasKey('plan_id', $offer);
            $this->assertArrayHasKey('plan_name', $offer);
            $this->assertArrayHasKey('price', $offer);
            $this->assertArrayHasKey('features', $offer);
            $this->assertArrayHasKey('discount', $offer);
        }
        
        // Get downgrade offers
        $downgrade_offers = $this->portal->get_downgrade_offers($customer_id, $current_plan);
        
        $this->assertIsArray($downgrade_offers);
        
        foreach ($downgrade_offers as $offer) {
            $this->assertArrayHasKey('plan_id', $offer);
            $this->assertArrayHasKey('plan_name', $offer);
            $this->assertArrayHasKey('price', $offer);
            $this->assertArrayHasKey('features', $offer);
        }
    }
    
    /**
     * Test portal access validation
     */
    public function test_portal_access_validation() {
        $customer_id = 123;
        $resource = 'order_123';
        
        // Test access to own resource
        $has_access = $this->portal->validate_customer_access($customer_id, $resource);
        $this->assertIsBool($has_access);
        
        // Test access to another customer's resource
        $other_customer_id = 456;
        $has_access = $this->portal->validate_customer_access($other_customer_id, $resource);
        $this->assertIsBool($has_access);
    }
    
    /**
     * Test portal session management
     */
    public function test_portal_session_management() {
        $customer_id = 123;
        
        // Create portal session
        $session_token = $this->portal->create_portal_session($customer_id);
        
        $this->assertIsString($session_token);
        $this->assertNotEmpty($session_token);
        
        // Validate session
        $session_data = $this->portal->validate_portal_session($session_token);
        
        $this->assertIsArray($session_data);
        $this->assertArrayHasKey('customer_id', $session_data);
        $this->assertArrayHasKey('expires_at', $session_data);
        
        // Destroy session
        $destroyed = $this->portal->destroy_portal_session($session_token);
        $this->assertTrue($destroyed);
        
        // Validate destroyed session (should fail)
        $invalid_session = $this->portal->validate_portal_session($session_token);
        $this->assertFalse($invalid_session);
    }
    
    /**
     * Test portal notifications
     */
    public function test_portal_notifications() {
        $customer_id = 123;
        
        // Get notifications
        $notifications = $this->portal->get_customer_notifications($customer_id);
        
        $this->assertIsArray($notifications);
        
        foreach ($notifications as $notification) {
            $this->assertArrayHasKey('id', $notification);
            $this->assertArrayHasKey('type', $notification);
            $this->assertArrayHasKey('title', $notification);
            $this->assertArrayHasKey('message', $notification);
            $this->assertArrayHasKey('created_at', $notification);
            $this->assertArrayHasKey('read', $notification);
        }
        
        // Mark notification as read
        if (!empty($notifications)) {
            $notification_id = $notifications[0]['id'];
            $marked_read = $this->portal->mark_notification_read($customer_id, $notification_id);
            $this->assertTrue($marked_read);
        }
    }
    
    /**
     * Test portal activity logging
     */
    public function test_portal_activity_logging() {
        $customer_id = 123;
        $activity = 'profile_updated';
        $details = array('field' => 'email', 'old_value' => 'old@example.com', 'new_value' => 'new@example.com');
        
        $logged = $this->portal->log_customer_activity($customer_id, $activity, $details);
        $this->assertTrue($logged);
        
        // Get activity log
        $activity_log = $this->portal->get_customer_activity_log($customer_id);
        
        $this->assertIsArray($activity_log);
        
        foreach ($activity_log as $log_entry) {
            $this->assertArrayHasKey('activity', $log_entry);
            $this->assertArrayHasKey('details', $log_entry);
            $this->assertArrayHasKey('timestamp', $log_entry);
            $this->assertArrayHasKey('ip_address', $log_entry);
        }
    }
}