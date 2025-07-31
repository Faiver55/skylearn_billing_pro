<?php
/**
 * Unit tests for User Enrollment functionality
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/class-user-enrollment.php';

class Test_User_Enrollment extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * User Enrollment instance
     *
     * @var SkyLearn_Billing_Pro_User_Enrollment
     */
    private $user_enrollment;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        $this->user_enrollment = new SkyLearn_Billing_Pro_User_Enrollment();
    }
    
    /**
     * Test User Enrollment instantiation
     */
    public function test_user_enrollment_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_User_Enrollment', $this->user_enrollment);
    }
    
    /**
     * Test creating new user from payment data
     */
    public function test_create_user_from_payment() {
        $payment_data = array(
            'customer_email' => 'newuser@example.com',
            'customer_name' => 'John Doe',
            'billing_address' => array(
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US'
            )
        );
        
        $result = $this->user_enrollment->create_user_from_payment($payment_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('user_id', $result);
            $this->assertArrayHasKey('password', $result);
            $this->assertIsInt($result['user_id']);
            $this->assertIsString($result['password']);
        }
    }
    
    /**
     * Test enrolling user in course
     */
    public function test_enroll_user_in_course() {
        $user_id = 123;
        $course_id = 456;
        $enrollment_data = array(
            'payment_id' => 'payment_test_123',
            'product_id' => 'product_test_456'
        );
        
        $result = $this->user_enrollment->enroll_user_in_course($user_id, $course_id, $enrollment_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('enrollment_id', $result);
            $this->assertArrayHasKey('enrollment_date', $result);
        }
    }
    
    /**
     * Test bulk user enrollment
     */
    public function test_bulk_enroll_users() {
        $enrollments = array(
            array('user_id' => 123, 'course_id' => 456),
            array('user_id' => 124, 'course_id' => 456),
            array('user_id' => 125, 'course_id' => 457)
        );
        
        $result = $this->user_enrollment->bulk_enroll_users($enrollments);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success_count', $result);
        $this->assertArrayHasKey('failure_count', $result);
        $this->assertArrayHasKey('results', $result);
        
        $this->assertIsInt($result['success_count']);
        $this->assertIsInt($result['failure_count']);
        $this->assertIsArray($result['results']);
    }
    
    /**
     * Test user role assignment
     */
    public function test_assign_user_role() {
        $user_id = 123;
        $role = 'course_member';
        
        $result = $this->user_enrollment->assign_user_role($user_id, $role);
        
        $this->assertIsBool($result);
    }
    
    /**
     * Test getting user enrollments
     */
    public function test_get_user_enrollments() {
        $user_id = 123;
        
        $enrollments = $this->user_enrollment->get_user_enrollments($user_id);
        
        $this->assertIsArray($enrollments);
        
        foreach ($enrollments as $enrollment) {
            $this->assertArrayHasKey('course_id', $enrollment);
            $this->assertArrayHasKey('enrollment_date', $enrollment);
            $this->assertArrayHasKey('status', $enrollment);
        }
    }
    
    /**
     * Test enrollment validation
     */
    public function test_validate_enrollment_data() {
        // Valid enrollment data
        $valid_data = array(
            'user_id' => 123,
            'course_id' => 456,
            'payment_id' => 'payment_test_123'
        );
        
        $validation = $this->user_enrollment->validate_enrollment_data($valid_data);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        
        // Invalid enrollment data
        $invalid_data = array(
            'user_id' => 0,
            'course_id' => '',
            'payment_id' => ''
        );
        
        $validation = $this->user_enrollment->validate_enrollment_data($invalid_data);
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }
    
    /**
     * Test enrollment logging
     */
    public function test_enrollment_logging() {
        $enrollment_data = array(
            'user_id' => 123,
            'course_id' => 456,
            'action' => 'enrolled',
            'source' => 'payment_webhook'
        );
        
        $logged = $this->user_enrollment->log_enrollment_activity($enrollment_data);
        $this->assertTrue($logged);
        
        // Check if log entry was created
        $this->assertLogEntryCreated('enrollment', 'enrolled');
    }
}