<?php
/**
 * Automation Manager for visual automation builder, templates, and logs
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
 * Automation Manager class
 */
class SkyLearn_Billing_Pro_Automation_Manager {
    
    /**
     * Automation table name
     *
     * @var string
     */
    private $automation_table;
    
    /**
     * Automation logs table name
     *
     * @var string
     */
    private $automation_logs_table;
    
    /**
     * Constructor
     */
    public function __construct() {
        global $wpdb;
        $this->automation_table = $wpdb->prefix . 'skylearn_automations';
        $this->automation_logs_table = $wpdb->prefix . 'skylearn_automation_logs';
        
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'create_database_tables'));
    }
    
    /**
     * Initialize automation manager
     */
    public function init() {
        // Hook into common events for automation triggers
        add_action('skylearn_billing_pro_payment_completed', array($this, 'trigger_payment_completed'), 10, 2);
        add_action('skylearn_billing_pro_subscription_created', array($this, 'trigger_subscription_created'), 10, 2);
        add_action('skylearn_billing_pro_user_enrolled', array($this, 'trigger_user_enrolled'), 10, 3);
        add_action('skylearn_billing_pro_subscription_cancelled', array($this, 'trigger_subscription_cancelled'), 10, 2);
        add_action('skylearn_billing_pro_subscription_renewed', array($this, 'trigger_subscription_renewed'), 10, 2);
    }
    
    /**
     * Create database tables for automations
     */
    public function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Automations table
        $sql_automations = "CREATE TABLE IF NOT EXISTS {$this->automation_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            trigger_type varchar(100) NOT NULL,
            trigger_conditions longtext,
            actions longtext NOT NULL,
            status enum('active','inactive','draft') DEFAULT 'draft',
            created_by bigint(20) UNSIGNED,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY trigger_type (trigger_type),
            KEY status (status),
            KEY created_by (created_by)
        ) $charset_collate;";
        
        // Automation logs table
        $sql_logs = "CREATE TABLE IF NOT EXISTS {$this->automation_logs_table} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            automation_id mediumint(9) NOT NULL,
            trigger_data longtext,
            actions_executed longtext,
            status enum('success','failure','partial') DEFAULT 'success',
            error_message text,
            execution_time_ms int(11),
            triggered_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY automation_id (automation_id),
            KEY status (status),
            KEY triggered_at (triggered_at),
            FOREIGN KEY (automation_id) REFERENCES {$this->automation_table}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_automations);
        dbDelta($sql_logs);
    }
    
    /**
     * Get all automations
     *
     * @param string $status Filter by status
     * @return array
     */
    public function get_automations($status = '') {
        global $wpdb;
        
        $sql = "SELECT * FROM {$this->automation_table}";
        $where_conditions = array();
        $params = array();
        
        if (!empty($status)) {
            $where_conditions[] = "status = %s";
            $params[] = $status;
        }
        
        if (!empty($where_conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql .= ' ORDER BY created_at DESC';
        
        if (!empty($params)) {
            $sql = $wpdb->prepare($sql, $params);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode JSON fields
        foreach ($results as &$automation) {
            $automation['trigger_conditions'] = json_decode($automation['trigger_conditions'], true);
            $automation['actions'] = json_decode($automation['actions'], true);
        }
        
        return $results;
    }
    
    /**
     * Get automation by ID
     *
     * @param int $automation_id
     * @return array|null
     */
    public function get_automation($automation_id) {
        global $wpdb;
        
        $automation = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$this->automation_table} WHERE id = %d", $automation_id),
            ARRAY_A
        );
        
        if ($automation) {
            $automation['trigger_conditions'] = json_decode($automation['trigger_conditions'], true);
            $automation['actions'] = json_decode($automation['actions'], true);
        }
        
        return $automation;
    }
    
    /**
     * Save automation
     *
     * @param array $data Automation data
     * @return int|false Automation ID or false on failure
     */
    public function save_automation($data) {
        global $wpdb;
        
        $automation_data = array(
            'name' => sanitize_text_field($data['name']),
            'description' => sanitize_textarea_field($data['description'] ?? ''),
            'trigger_type' => sanitize_text_field($data['trigger_type']),
            'trigger_conditions' => json_encode($data['trigger_conditions'] ?? array()),
            'actions' => json_encode($data['actions'] ?? array()),
            'status' => sanitize_text_field($data['status'] ?? 'draft'),
            'updated_at' => current_time('mysql')
        );
        
        if (!empty($data['id'])) {
            // Update existing automation
            $result = $wpdb->update(
                $this->automation_table,
                $automation_data,
                array('id' => intval($data['id'])),
                array('%s', '%s', '%s', '%s', '%s', '%s', '%s'),
                array('%d')
            );
            
            return $result !== false ? intval($data['id']) : false;
        } else {
            // Create new automation
            $automation_data['created_by'] = get_current_user_id();
            $automation_data['created_at'] = current_time('mysql');
            
            $result = $wpdb->insert(
                $this->automation_table,
                $automation_data,
                array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s')
            );
            
            return $result ? $wpdb->insert_id : false;
        }
    }
    
    /**
     * Delete automation
     *
     * @param int $automation_id
     * @return bool
     */
    public function delete_automation($automation_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->automation_table,
            array('id' => intval($automation_id)),
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Trigger automation based on event
     *
     * @param string $trigger_type
     * @param array $trigger_data
     * @return array Results of triggered automations
     */
    public function trigger_automations($trigger_type, $trigger_data = array()) {
        $automations = $this->get_automations('active');
        $results = array();
        
        foreach ($automations as $automation) {
            if ($automation['trigger_type'] === $trigger_type) {
                if ($this->check_trigger_conditions($automation, $trigger_data)) {
                    $result = $this->execute_automation($automation, $trigger_data);
                    $results[] = $result;
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Check if trigger conditions are met
     *
     * @param array $automation
     * @param array $trigger_data
     * @return bool
     */
    private function check_trigger_conditions($automation, $trigger_data) {
        $conditions = $automation['trigger_conditions'];
        
        if (empty($conditions)) {
            return true; // No conditions means always trigger
        }
        
        foreach ($conditions as $condition) {
            $field = $condition['field'] ?? '';
            $operator = $condition['operator'] ?? '=';
            $value = $condition['value'] ?? '';
            
            $actual_value = $this->get_trigger_data_value($trigger_data, $field);
            
            if (!$this->evaluate_condition($actual_value, $operator, $value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get value from trigger data
     *
     * @param array $trigger_data
     * @param string $field
     * @return mixed
     */
    private function get_trigger_data_value($trigger_data, $field) {
        if (strpos($field, '.') !== false) {
            $keys = explode('.', $field);
            $value = $trigger_data;
            
            foreach ($keys as $key) {
                if (is_array($value) && isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
            
            return $value;
        }
        
        return $trigger_data[$field] ?? null;
    }
    
    /**
     * Evaluate condition
     *
     * @param mixed $actual_value
     * @param string $operator
     * @param mixed $expected_value
     * @return bool
     */
    private function evaluate_condition($actual_value, $operator, $expected_value) {
        switch ($operator) {
            case '=':
            case 'equals':
                return $actual_value == $expected_value;
            case '!=':
            case 'not_equals':
                return $actual_value != $expected_value;
            case '>':
            case 'greater_than':
                return $actual_value > $expected_value;
            case '>=':
            case 'greater_than_or_equal':
                return $actual_value >= $expected_value;
            case '<':
            case 'less_than':
                return $actual_value < $expected_value;
            case '<=':
            case 'less_than_or_equal':
                return $actual_value <= $expected_value;
            case 'contains':
                return strpos((string)$actual_value, (string)$expected_value) !== false;
            case 'not_contains':
                return strpos((string)$actual_value, (string)$expected_value) === false;
            case 'starts_with':
                return strpos((string)$actual_value, (string)$expected_value) === 0;
            case 'ends_with':
                return substr((string)$actual_value, -strlen((string)$expected_value)) === (string)$expected_value;
            default:
                return false;
        }
    }
    
    /**
     * Execute automation actions
     *
     * @param array $automation
     * @param array $trigger_data
     * @return array
     */
    private function execute_automation($automation, $trigger_data) {
        $start_time = microtime(true);
        $execution_result = array(
            'automation_id' => $automation['id'],
            'status' => 'success',
            'actions_executed' => array(),
            'errors' => array()
        );
        
        foreach ($automation['actions'] as $action) {
            try {
                $action_result = $this->execute_action($action, $trigger_data);
                $execution_result['actions_executed'][] = array(
                    'action' => $action,
                    'result' => $action_result
                );
                
                if (!$action_result['success']) {
                    $execution_result['errors'][] = $action_result['error'] ?? 'Unknown error';
                    $execution_result['status'] = 'partial';
                }
            } catch (Exception $e) {
                $execution_result['errors'][] = $e->getMessage();
                $execution_result['status'] = 'failure';
                $execution_result['actions_executed'][] = array(
                    'action' => $action,
                    'result' => array('success' => false, 'error' => $e->getMessage())
                );
            }
        }
        
        if (!empty($execution_result['errors']) && $execution_result['status'] === 'success') {
            $execution_result['status'] = 'partial';
        }
        
        $execution_time = (microtime(true) - $start_time) * 1000; // Convert to milliseconds
        
        // Log execution
        $this->log_automation_execution($automation['id'], $trigger_data, $execution_result, $execution_time);
        
        return $execution_result;
    }
    
    /**
     * Execute individual action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function execute_action($action, $trigger_data) {
        $action_type = $action['type'] ?? '';
        
        switch ($action_type) {
            case 'webhook':
                return $this->execute_webhook_action($action, $trigger_data);
            case 'email':
                return $this->execute_email_action($action, $trigger_data);
            case 'crm':
                return $this->execute_crm_action($action, $trigger_data);
            case 'sms':
                return $this->execute_sms_action($action, $trigger_data);
            case 'marketing':
                return $this->execute_marketing_action($action, $trigger_data);
            default:
                // Allow custom action types via filter
                return apply_filters('skylearn_billing_pro_execute_automation_action', 
                    array('success' => false, 'error' => 'Unknown action type'), 
                    $action, 
                    $trigger_data
                );
        }
    }
    
    /**
     * Execute webhook action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function execute_webhook_action($action, $trigger_data) {
        $url = $action['url'] ?? '';
        $method = $action['method'] ?? 'POST';
        $headers = $action['headers'] ?? array();
        $body_template = $action['body'] ?? '';
        
        if (empty($url)) {
            return array('success' => false, 'error' => 'Webhook URL is required');
        }
        
        // Replace placeholders in body template
        $body = $this->replace_placeholders($body_template, $trigger_data);
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'body' => $body,
            'timeout' => 30
        );
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true, 
                'response_code' => $response_code,
                'response_body' => $response_body
            );
        } else {
            return array(
                'success' => false, 
                'error' => "HTTP {$response_code}: {$response_body}"
            );
        }
    }
    
    /**
     * Execute email action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function execute_email_action($action, $trigger_data) {
        $to = $this->replace_placeholders($action['to'] ?? '', $trigger_data);
        $subject = $this->replace_placeholders($action['subject'] ?? '', $trigger_data);
        $message = $this->replace_placeholders($action['message'] ?? '', $trigger_data);
        
        if (empty($to) || !is_email($to)) {
            return array('success' => false, 'error' => 'Valid recipient email is required');
        }
        
        $sent = wp_mail($to, $subject, $message);
        
        return array(
            'success' => $sent,
            'error' => $sent ? null : 'Failed to send email'
        );
    }
    
    /**
     * Execute CRM action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function execute_crm_action($action, $trigger_data) {
        // This will be implemented by individual CRM integrations
        return apply_filters('skylearn_billing_pro_execute_crm_action', 
            array('success' => false, 'error' => 'CRM integration not configured'), 
            $action, 
            $trigger_data
        );
    }
    
    /**
     * Execute SMS action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function execute_sms_action($action, $trigger_data) {
        // This will be implemented by individual SMS integrations
        return apply_filters('skylearn_billing_pro_execute_sms_action', 
            array('success' => false, 'error' => 'SMS integration not configured'), 
            $action, 
            $trigger_data
        );
    }
    
    /**
     * Execute marketing action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function execute_marketing_action($action, $trigger_data) {
        // This will be implemented by individual marketing integrations
        return apply_filters('skylearn_billing_pro_execute_marketing_action', 
            array('success' => false, 'error' => 'Marketing integration not configured'), 
            $action, 
            $trigger_data
        );
    }
    
    /**
     * Replace placeholders in text with trigger data
     *
     * @param string $text
     * @param array $trigger_data
     * @return string
     */
    private function replace_placeholders($text, $trigger_data) {
        if (empty($text)) {
            return '';
        }
        
        // Replace simple placeholders like {{field_name}}
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($trigger_data) {
            $field = trim($matches[1]);
            $value = $this->get_trigger_data_value($trigger_data, $field);
            return $value !== null ? $value : $matches[0];
        }, $text);
    }
    
    /**
     * Log automation execution
     *
     * @param int $automation_id
     * @param array $trigger_data
     * @param array $execution_result
     * @param float $execution_time_ms
     */
    private function log_automation_execution($automation_id, $trigger_data, $execution_result, $execution_time_ms) {
        global $wpdb;
        
        $status = $execution_result['status'];
        $error_message = !empty($execution_result['errors']) ? implode('; ', $execution_result['errors']) : null;
        
        $wpdb->insert(
            $this->automation_logs_table,
            array(
                'automation_id' => $automation_id,
                'trigger_data' => json_encode($trigger_data),
                'actions_executed' => json_encode($execution_result['actions_executed']),
                'status' => $status,
                'error_message' => $error_message,
                'execution_time_ms' => round($execution_time_ms),
                'triggered_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%d', '%s')
        );
    }
    
    /**
     * Get automation logs
     *
     * @param int $automation_id Optional automation ID to filter by
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function get_automation_logs($automation_id = null, $limit = 50, $offset = 0) {
        global $wpdb;
        
        $sql = "SELECT l.*, a.name as automation_name 
                FROM {$this->automation_logs_table} l 
                LEFT JOIN {$this->automation_table} a ON l.automation_id = a.id";
        
        $where_conditions = array();
        $params = array();
        
        if ($automation_id) {
            $where_conditions[] = "l.automation_id = %d";
            $params[] = $automation_id;
        }
        
        if (!empty($where_conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql .= ' ORDER BY l.triggered_at DESC LIMIT %d OFFSET %d';
        $params[] = $limit;
        $params[] = $offset;
        
        $sql = $wpdb->prepare($sql, $params);
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        // Decode JSON fields
        foreach ($results as &$log) {
            $log['trigger_data'] = json_decode($log['trigger_data'], true);
            $log['actions_executed'] = json_decode($log['actions_executed'], true);
        }
        
        return $results;
    }
    
    /**
     * Get available trigger types
     *
     * @return array
     */
    public function get_available_trigger_types() {
        return apply_filters('skylearn_billing_pro_automation_trigger_types', array(
            'payment_completed' => __('Payment Completed', 'skylearn-billing-pro'),
            'subscription_created' => __('Subscription Created', 'skylearn-billing-pro'),
            'subscription_cancelled' => __('Subscription Cancelled', 'skylearn-billing-pro'),
            'subscription_renewed' => __('Subscription Renewed', 'skylearn-billing-pro'),
            'user_enrolled' => __('User Enrolled in Course', 'skylearn-billing-pro'),
            'user_registered' => __('User Registered', 'skylearn-billing-pro')
        ));
    }
    
    /**
     * Get available action types
     *
     * @return array
     */
    public function get_available_action_types() {
        return apply_filters('skylearn_billing_pro_automation_action_types', array(
            'webhook' => __('Send Webhook', 'skylearn-billing-pro'),
            'email' => __('Send Email', 'skylearn-billing-pro'),
            'crm' => __('Update CRM', 'skylearn-billing-pro'),
            'sms' => __('Send SMS', 'skylearn-billing-pro'),
            'marketing' => __('Marketing Action', 'skylearn-billing-pro')
        ));
    }
    
    /**
     * Get automation templates
     *
     * @return array
     */
    public function get_automation_templates() {
        return apply_filters('skylearn_billing_pro_automation_templates', array(
            'welcome_email' => array(
                'name' => __('Welcome Email on Payment', 'skylearn-billing-pro'),
                'description' => __('Send a welcome email when payment is completed', 'skylearn-billing-pro'),
                'trigger_type' => 'payment_completed',
                'actions' => array(
                    array(
                        'type' => 'email',
                        'to' => '{{user.email}}',
                        'subject' => 'Welcome to {{site.name}}!',
                        'message' => 'Thank you for your purchase! You now have access to {{product.name}}.'
                    )
                )
            ),
            'crm_contact' => array(
                'name' => __('Add to CRM on Subscription', 'skylearn-billing-pro'),
                'description' => __('Add contact to CRM when subscription is created', 'skylearn-billing-pro'),
                'trigger_type' => 'subscription_created',
                'actions' => array(
                    array(
                        'type' => 'crm',
                        'provider' => 'hubspot',
                        'action' => 'create_contact',
                        'data' => array(
                            'email' => '{{user.email}}',
                            'firstname' => '{{user.first_name}}',
                            'lastname' => '{{user.last_name}}'
                        )
                    )
                )
            ),
            'zapier_webhook' => array(
                'name' => __('Zapier Integration', 'skylearn-billing-pro'),
                'description' => __('Send data to Zapier webhook on any event', 'skylearn-billing-pro'),
                'trigger_type' => 'payment_completed',
                'actions' => array(
                    array(
                        'type' => 'webhook',
                        'url' => 'https://hooks.zapier.com/hooks/catch/YOUR_WEBHOOK_ID/',
                        'method' => 'POST',
                        'body' => json_encode(array(
                            'event' => '{{trigger.type}}',
                            'user_email' => '{{user.email}}',
                            'product_id' => '{{product.id}}',
                            'amount' => '{{payment.amount}}'
                        ))
                    )
                )
            )
        ));
    }
    
    // Event trigger methods
    
    /**
     * Trigger payment completed automation
     *
     * @param array $payment_data
     * @param array $user_data
     */
    public function trigger_payment_completed($payment_data, $user_data) {
        $this->trigger_automations('payment_completed', array(
            'payment' => $payment_data,
            'user' => $user_data,
            'trigger' => array('type' => 'payment_completed'),
            'site' => array('name' => get_bloginfo('name'))
        ));
    }
    
    /**
     * Trigger subscription created automation
     *
     * @param array $subscription_data
     * @param array $user_data
     */
    public function trigger_subscription_created($subscription_data, $user_data) {
        $this->trigger_automations('subscription_created', array(
            'subscription' => $subscription_data,
            'user' => $user_data,
            'trigger' => array('type' => 'subscription_created'),
            'site' => array('name' => get_bloginfo('name'))
        ));
    }
    
    /**
     * Trigger user enrolled automation
     *
     * @param int $user_id
     * @param string $product_id
     * @param array $course_data
     */
    public function trigger_user_enrolled($user_id, $product_id, $course_data) {
        $user = get_user_by('id', $user_id);
        if (!$user) return;
        
        $this->trigger_automations('user_enrolled', array(
            'user' => array(
                'id' => $user->ID,
                'email' => $user->user_email,
                'first_name' => get_user_meta($user->ID, 'first_name', true),
                'last_name' => get_user_meta($user->ID, 'last_name', true)
            ),
            'product' => array('id' => $product_id),
            'course' => $course_data,
            'trigger' => array('type' => 'user_enrolled'),
            'site' => array('name' => get_bloginfo('name'))
        ));
    }
    
    /**
     * Trigger subscription cancelled automation
     *
     * @param array $subscription_data
     * @param array $user_data
     */
    public function trigger_subscription_cancelled($subscription_data, $user_data) {
        $this->trigger_automations('subscription_cancelled', array(
            'subscription' => $subscription_data,
            'user' => $user_data,
            'trigger' => array('type' => 'subscription_cancelled'),
            'site' => array('name' => get_bloginfo('name'))
        ));
    }
    
    /**
     * Trigger subscription renewed automation
     *
     * @param array $subscription_data
     * @param array $user_data
     */
    public function trigger_subscription_renewed($subscription_data, $user_data) {
        $this->trigger_automations('subscription_renewed', array(
            'subscription' => $subscription_data,
            'user' => $user_data,
            'trigger' => array('type' => 'subscription_renewed'),
            'site' => array('name' => get_bloginfo('name'))
        ));
    }
}

/**
 * Get the Automation Manager instance
 *
 * @return SkyLearn_Billing_Pro_Automation_Manager
 */
function skylearn_billing_pro_automation_manager() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Automation_Manager();
    }
    
    return $instance;
}