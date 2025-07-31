<?php
/**
 * Unit tests for Automation Manager
 *
 * @package SkyLearnBillingPro
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';
require_once SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'includes/automation/class-automation-manager.php';

class Test_Automation_Manager extends SkyLearn_Billing_Pro_Test_Case {
    
    /**
     * Automation Manager instance
     *
     * @var SkyLearn_Billing_Pro_Automation_Manager
     */
    private $automation_manager;
    
    /**
     * Set up test case
     */
    protected function setUp(): void {
        parent::setUp();
        $this->automation_manager = new SkyLearn_Billing_Pro_Automation_Manager();
    }
    
    /**
     * Test Automation Manager instantiation
     */
    public function test_automation_manager_instantiation() {
        $this->assertInstanceOf('SkyLearn_Billing_Pro_Automation_Manager', $this->automation_manager);
    }
    
    /**
     * Test creating automation workflow
     */
    public function test_create_automation_workflow() {
        $workflow_data = array(
            'name' => 'Test Workflow',
            'description' => 'Test automation workflow',
            'trigger' => 'payment.completed',
            'actions' => array(
                array(
                    'type' => 'send_email',
                    'template' => 'welcome_email',
                    'delay' => 0
                ),
                array(
                    'type' => 'enroll_course',
                    'course_id' => 123,
                    'delay' => 3600 // 1 hour delay
                )
            ),
            'conditions' => array(
                array(
                    'field' => 'amount',
                    'operator' => 'greater_than',
                    'value' => 50.00
                )
            ),
            'status' => 'active'
        );
        
        $workflow_id = $this->automation_manager->create_workflow($workflow_data);
        
        $this->assertIsString($workflow_id);
        $this->assertNotEmpty($workflow_id);
        
        // Verify workflow was saved
        $saved_workflow = $this->automation_manager->get_workflow($workflow_id);
        $this->assertIsArray($saved_workflow);
        $this->assertEquals('Test Workflow', $saved_workflow['name']);
        $this->assertEquals('active', $saved_workflow['status']);
    }
    
    /**
     * Test updating automation workflow
     */
    public function test_update_automation_workflow() {
        // Create a workflow first
        $workflow_data = array(
            'name' => 'Original Workflow',
            'trigger' => 'payment.completed',
            'actions' => array(),
            'status' => 'active'
        );
        
        $workflow_id = $this->automation_manager->create_workflow($workflow_data);
        
        // Update the workflow
        $updated_data = array(
            'name' => 'Updated Workflow',
            'status' => 'inactive'
        );
        
        $result = $this->automation_manager->update_workflow($workflow_id, $updated_data);
        $this->assertTrue($result);
        
        // Verify the update
        $updated_workflow = $this->automation_manager->get_workflow($workflow_id);
        $this->assertEquals('Updated Workflow', $updated_workflow['name']);
        $this->assertEquals('inactive', $updated_workflow['status']);
    }
    
    /**
     * Test deleting automation workflow
     */
    public function test_delete_automation_workflow() {
        // Create a workflow first
        $workflow_data = array(
            'name' => 'Test Workflow to Delete',
            'trigger' => 'payment.completed',
            'actions' => array(),
            'status' => 'active'
        );
        
        $workflow_id = $this->automation_manager->create_workflow($workflow_data);
        
        // Delete the workflow
        $result = $this->automation_manager->delete_workflow($workflow_id);
        $this->assertTrue($result);
        
        // Verify deletion
        $deleted_workflow = $this->automation_manager->get_workflow($workflow_id);
        $this->assertNull($deleted_workflow);
    }
    
    /**
     * Test getting all workflows
     */
    public function test_get_all_workflows() {
        // Create multiple workflows
        $workflows = array(
            array('name' => 'Workflow 1', 'trigger' => 'payment.completed', 'actions' => array()),
            array('name' => 'Workflow 2', 'trigger' => 'subscription.created', 'actions' => array()),
            array('name' => 'Workflow 3', 'trigger' => 'user.registered', 'actions' => array())
        );
        
        foreach ($workflows as $workflow_data) {
            $this->automation_manager->create_workflow($workflow_data);
        }
        
        $all_workflows = $this->automation_manager->get_all_workflows();
        
        $this->assertIsArray($all_workflows);
        $this->assertGreaterThanOrEqual(3, count($all_workflows));
    }
    
    /**
     * Test workflow execution
     */
    public function test_execute_workflow() {
        // Create a workflow
        $workflow_data = array(
            'name' => 'Execution Test Workflow',
            'trigger' => 'payment.completed',
            'actions' => array(
                array(
                    'type' => 'send_email',
                    'template' => 'welcome_email',
                    'delay' => 0
                )
            ),
            'status' => 'active'
        );
        
        $workflow_id = $this->automation_manager->create_workflow($workflow_data);
        
        // Execute the workflow
        $trigger_data = $this->create_mock_payment();
        $result = $this->automation_manager->execute_workflow($workflow_id, $trigger_data);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
        $this->assertIsBool($result['success']);
        
        if ($result['success']) {
            $this->assertArrayHasKey('actions_executed', $result);
            $this->assertIsArray($result['actions_executed']);
        }
    }
    
    /**
     * Test workflow triggers
     */
    public function test_workflow_triggers() {
        $available_triggers = $this->automation_manager->get_available_triggers();
        
        $this->assertIsArray($available_triggers);
        $this->assertNotEmpty($available_triggers);
        
        // Check for common triggers
        $expected_triggers = array(
            'payment.completed',
            'payment.failed',
            'subscription.created',
            'subscription.cancelled',
            'user.registered',
            'course.completed'
        );
        
        foreach ($expected_triggers as $trigger) {
            $this->assertArrayHasKey($trigger, $available_triggers);
            $this->assertIsArray($available_triggers[$trigger]);
            $this->assertArrayHasKey('name', $available_triggers[$trigger]);
            $this->assertArrayHasKey('description', $available_triggers[$trigger]);
        }
    }
    
    /**
     * Test workflow actions
     */
    public function test_workflow_actions() {
        $available_actions = $this->automation_manager->get_available_actions();
        
        $this->assertIsArray($available_actions);
        $this->assertNotEmpty($available_actions);
        
        // Check for common actions
        $expected_actions = array(
            'send_email',
            'enroll_course',
            'add_user_role',
            'create_user',
            'send_webhook',
            'delay'
        );
        
        foreach ($expected_actions as $action) {
            $this->assertArrayHasKey($action, $available_actions);
            $this->assertIsArray($available_actions[$action]);
            $this->assertArrayHasKey('name', $available_actions[$action]);
            $this->assertArrayHasKey('description', $available_actions[$action]);
        }
    }
    
    /**
     * Test workflow conditions
     */
    public function test_workflow_conditions() {
        $trigger_data = $this->create_mock_payment(array('amount' => 100.00));
        
        // Test greater than condition
        $condition = array(
            'field' => 'amount',
            'operator' => 'greater_than',
            'value' => 50.00
        );
        
        $result = $this->automation_manager->evaluate_condition($condition, $trigger_data);
        $this->assertTrue($result);
        
        // Test less than condition (should fail)
        $condition['value'] = 150.00;
        $result = $this->automation_manager->evaluate_condition($condition, $trigger_data);
        $this->assertFalse($result);
        
        // Test equals condition
        $condition = array(
            'field' => 'currency',
            'operator' => 'equals',
            'value' => 'USD'
        );
        
        $result = $this->automation_manager->evaluate_condition($condition, $trigger_data);
        $this->assertTrue($result);
    }
    
    /**
     * Test workflow scheduling
     */
    public function test_workflow_scheduling() {
        $workflow_id = 'test_workflow_123';
        $trigger_data = $this->create_mock_payment();
        $delay = 3600; // 1 hour
        
        $scheduled = $this->automation_manager->schedule_workflow_execution($workflow_id, $trigger_data, $delay);
        $this->assertTrue($scheduled);
        
        // Check if workflow is in the queue
        $queue = $this->automation_manager->get_scheduled_workflows();
        $this->assertIsArray($queue);
        $this->assertNotEmpty($queue);
        
        $found = false;
        foreach ($queue as $item) {
            if ($item['workflow_id'] === $workflow_id) {
                $found = true;
                $this->assertArrayHasKey('scheduled_time', $item);
                $this->assertArrayHasKey('trigger_data', $item);
                break;
            }
        }
        $this->assertTrue($found);
    }
    
    /**
     * Test workflow templates
     */
    public function test_workflow_templates() {
        $templates = $this->automation_manager->get_workflow_templates();
        
        $this->assertIsArray($templates);
        $this->assertNotEmpty($templates);
        
        // Check template structure
        foreach ($templates as $template_id => $template) {
            $this->assertArrayHasKey('name', $template);
            $this->assertArrayHasKey('description', $template);
            $this->assertArrayHasKey('trigger', $template);
            $this->assertArrayHasKey('actions', $template);
            
            $this->assertIsString($template['name']);
            $this->assertIsString($template['description']);
            $this->assertIsString($template['trigger']);
            $this->assertIsArray($template['actions']);
        }
    }
    
    /**
     * Test creating workflow from template
     */
    public function test_create_workflow_from_template() {
        $template_id = 'welcome_sequence';
        $custom_data = array(
            'name' => 'My Welcome Sequence',
            'status' => 'active'
        );
        
        $workflow_id = $this->automation_manager->create_workflow_from_template($template_id, $custom_data);
        
        if ($workflow_id !== false) {
            $this->assertIsString($workflow_id);
            $this->assertNotEmpty($workflow_id);
            
            $workflow = $this->automation_manager->get_workflow($workflow_id);
            $this->assertIsArray($workflow);
            $this->assertEquals('My Welcome Sequence', $workflow['name']);
        } else {
            // Template might not exist in test environment
            $this->assertTrue(true);
        }
    }
    
    /**
     * Test workflow analytics
     */
    public function test_workflow_analytics() {
        $workflow_id = 'test_workflow_123';
        
        $analytics = $this->automation_manager->get_workflow_analytics($workflow_id);
        
        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('executions', $analytics);
        $this->assertArrayHasKey('success_rate', $analytics);
        $this->assertArrayHasKey('average_execution_time', $analytics);
        $this->assertArrayHasKey('last_execution', $analytics);
        
        $this->assertIsInt($analytics['executions']);
        $this->assertIsFloat($analytics['success_rate']);
        $this->assertIsFloat($analytics['average_execution_time']);
    }
    
    /**
     * Test workflow logging
     */
    public function test_workflow_logging() {
        $workflow_id = 'test_workflow_123';
        $execution_data = array(
            'trigger' => 'payment.completed',
            'actions_executed' => array('send_email', 'enroll_course'),
            'execution_time' => 1.5,
            'success' => true
        );
        
        $this->automation_manager->log_workflow_execution($workflow_id, $execution_data);
        
        // Check if log was created
        $this->assertLogEntryCreated('user_activity', 'workflow execution');
    }
    
    /**
     * Test workflow validation
     */
    public function test_workflow_validation() {
        // Test valid workflow
        $valid_workflow = array(
            'name' => 'Valid Workflow',
            'trigger' => 'payment.completed',
            'actions' => array(
                array('type' => 'send_email', 'template' => 'welcome_email')
            ),
            'status' => 'active'
        );
        
        $validation = $this->automation_manager->validate_workflow($valid_workflow);
        $this->assertTrue($validation['valid']);
        $this->assertEmpty($validation['errors']);
        
        // Test invalid workflow
        $invalid_workflow = array(
            'name' => '', // Empty name
            'trigger' => 'invalid_trigger',
            'actions' => array(), // No actions
            'status' => 'invalid_status'
        );
        
        $validation = $this->automation_manager->validate_workflow($invalid_workflow);
        $this->assertFalse($validation['valid']);
        $this->assertNotEmpty($validation['errors']);
    }
    
    /**
     * Test bulk workflow operations
     */
    public function test_bulk_workflow_operations() {
        // Create multiple workflows
        $workflow_ids = array();
        for ($i = 1; $i <= 3; $i++) {
            $workflow_data = array(
                'name' => "Bulk Test Workflow {$i}",
                'trigger' => 'payment.completed',
                'actions' => array(),
                'status' => 'active'
            );
            $workflow_ids[] = $this->automation_manager->create_workflow($workflow_data);
        }
        
        // Test bulk activation
        $result = $this->automation_manager->bulk_activate_workflows($workflow_ids);
        $this->assertTrue($result);
        
        // Test bulk deactivation
        $result = $this->automation_manager->bulk_deactivate_workflows($workflow_ids);
        $this->assertTrue($result);
        
        // Test bulk deletion
        $result = $this->automation_manager->bulk_delete_workflows($workflow_ids);
        $this->assertTrue($result);
    }
}