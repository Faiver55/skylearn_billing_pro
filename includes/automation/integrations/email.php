<?php
/**
 * Email Marketing Integrations for Skylearn Billing Pro
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
 * Base Email Marketing Integration class
 */
abstract class SkyLearn_Billing_Pro_Email_Integration {
    
    /**
     * Provider name
     *
     * @var string
     */
    protected $provider_name = '';
    
    /**
     * Settings
     *
     * @var array
     */
    protected $settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = $this->get_settings();
        add_filter('skylearn_billing_pro_execute_automation_action', array($this, 'handle_email_action'), 10, 3);
    }
    
    /**
     * Get settings
     *
     * @return array
     */
    protected function get_settings() {
        $options = get_option('skylearn_billing_pro_options', array());
        return $options['email_integrations'][$this->provider_name] ?? array();
    }
    
    /**
     * Handle email action
     *
     * @param array $result
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    public function handle_email_action($result, $action, $trigger_data) {
        if (($action['type'] ?? '') !== 'email_marketing' || ($action['provider'] ?? '') !== $this->provider_name) {
            return $result;
        }
        
        if (!$this->is_configured()) {
            return array('success' => false, 'error' => ucfirst($this->provider_name) . ' is not configured');
        }
        
        return $this->execute_action($action, $trigger_data);
    }
    
    /**
     * Check if provider is configured
     *
     * @return bool
     */
    abstract protected function is_configured();
    
    /**
     * Execute action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    abstract protected function execute_action($action, $trigger_data);
    
    /**
     * Replace placeholders in text
     *
     * @param string $text
     * @param array $trigger_data
     * @return string
     */
    protected function replace_placeholders($text, $trigger_data) {
        return preg_replace_callback('/\{\{([^}]+)\}\}/', function($matches) use ($trigger_data) {
            $field = trim($matches[1]);
            $value = $this->get_trigger_data_value($trigger_data, $field);
            return $value !== null ? $value : $matches[0];
        }, $text);
    }
    
    /**
     * Get value from trigger data
     *
     * @param array $trigger_data
     * @param string $field
     * @return mixed
     */
    protected function get_trigger_data_value($trigger_data, $field) {
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
}

/**
 * Mailchimp Integration
 */
class SkyLearn_Billing_Pro_Mailchimp_Integration extends SkyLearn_Billing_Pro_Email_Integration {
    
    protected $provider_name = 'mailchimp';
    
    /**
     * Check if Mailchimp is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['api_key']);
    }
    
    /**
     * Execute Mailchimp action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $email_action = $action['action'] ?? '';
        
        switch ($email_action) {
            case 'subscribe':
                return $this->subscribe_contact($action, $trigger_data);
            case 'unsubscribe':
                return $this->unsubscribe_contact($action, $trigger_data);
            case 'update_tags':
                return $this->update_tags($action, $trigger_data);
            default:
                return array('success' => false, 'error' => 'Unknown Mailchimp action: ' . $email_action);
        }
    }
    
    /**
     * Subscribe contact to Mailchimp list
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function subscribe_contact($action, $trigger_data) {
        $list_id = $action['list_id'] ?? '';
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        $first_name = $this->replace_placeholders($action['first_name'] ?? '{{user.first_name}}', $trigger_data);
        $last_name = $this->replace_placeholders($action['last_name'] ?? '{{user.last_name}}', $trigger_data);
        
        if (empty($list_id) || empty($email)) {
            return array('success' => false, 'error' => 'List ID and email are required');
        }
        
        $api_key = $this->settings['api_key'];
        $datacenter = substr($api_key, strpos($api_key, '-') + 1);
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members";
        
        $data = array(
            'email_address' => $email,
            'status' => 'subscribed',
            'merge_fields' => array()
        );
        
        if (!empty($first_name)) {
            $data['merge_fields']['FNAME'] = $first_name;
        }
        
        if (!empty($last_name)) {
            $data['merge_fields']['LNAME'] = $last_name;
        }
        
        $response = $this->make_api_request($url, 'POST', $data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Contact subscribed to Mailchimp list successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Unsubscribe contact from Mailchimp list
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function unsubscribe_contact($action, $trigger_data) {
        $list_id = $action['list_id'] ?? '';
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        
        if (empty($list_id) || empty($email)) {
            return array('success' => false, 'error' => 'List ID and email are required');
        }
        
        $api_key = $this->settings['api_key'];
        $datacenter = substr($api_key, strpos($api_key, '-') + 1);
        $subscriber_hash = md5(strtolower($email));
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}";
        
        $data = array('status' => 'unsubscribed');
        
        $response = $this->make_api_request($url, 'PATCH', $data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Contact unsubscribed from Mailchimp list successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Update tags for contact
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function update_tags($action, $trigger_data) {
        $list_id = $action['list_id'] ?? '';
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        $tags = $action['tags'] ?? array();
        
        if (empty($list_id) || empty($email) || empty($tags)) {
            return array('success' => false, 'error' => 'List ID, email, and tags are required');
        }
        
        $api_key = $this->settings['api_key'];
        $datacenter = substr($api_key, strpos($api_key, '-') + 1);
        $subscriber_hash = md5(strtolower($email));
        $url = "https://{$datacenter}.api.mailchimp.com/3.0/lists/{$list_id}/members/{$subscriber_hash}/tags";
        
        $tags_data = array();
        foreach ($tags as $tag) {
            $tags_data[] = array('name' => $tag, 'status' => 'active');
        }
        
        $data = array('tags' => $tags_data);
        
        $response = $this->make_api_request($url, 'POST', $data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Tags updated for Mailchimp contact successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Make API request to Mailchimp
     *
     * @param string $url
     * @param string $method
     * @param array $data
     * @return array
     */
    private function make_api_request($url, $method = 'GET', $data = array()) {
        $api_key = $this->settings['api_key'];
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode('user:' . $api_key),
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        if (!empty($data) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($data);
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($response_body, true);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array('success' => true, 'data' => $decoded_body);
        } else {
            $error_message = $decoded_body['detail'] ?? "HTTP {$response_code}: {$response_body}";
            return array('success' => false, 'error' => $error_message);
        }
    }
}

/**
 * ConvertKit Integration
 */
class SkyLearn_Billing_Pro_ConvertKit_Integration extends SkyLearn_Billing_Pro_Email_Integration {
    
    protected $provider_name = 'convertkit';
    
    /**
     * Check if ConvertKit is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['api_key']);
    }
    
    /**
     * Execute ConvertKit action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $email_action = $action['action'] ?? '';
        
        switch ($email_action) {
            case 'subscribe':
                return $this->subscribe_contact($action, $trigger_data);
            case 'unsubscribe':
                return $this->unsubscribe_contact($action, $trigger_data);
            case 'add_tag':
                return $this->add_tag($action, $trigger_data);
            default:
                return array('success' => false, 'error' => 'Unknown ConvertKit action: ' . $email_action);
        }
    }
    
    /**
     * Subscribe contact to ConvertKit form
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function subscribe_contact($action, $trigger_data) {
        $form_id = $action['form_id'] ?? '';
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        $first_name = $this->replace_placeholders($action['first_name'] ?? '{{user.first_name}}', $trigger_data);
        
        if (empty($form_id) || empty($email)) {
            return array('success' => false, 'error' => 'Form ID and email are required');
        }
        
        $url = "https://api.convertkit.com/v3/forms/{$form_id}/subscribe";
        
        $data = array(
            'api_key' => $this->settings['api_key'],
            'email' => $email
        );
        
        if (!empty($first_name)) {
            $data['first_name'] = $first_name;
        }
        
        $response = $this->make_api_request($url, 'POST', $data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Contact subscribed to ConvertKit form successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Unsubscribe contact from ConvertKit
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function unsubscribe_contact($action, $trigger_data) {
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        
        if (empty($email)) {
            return array('success' => false, 'error' => 'Email is required');
        }
        
        $url = 'https://api.convertkit.com/v3/unsubscribe';
        
        $data = array(
            'api_key' => $this->settings['api_key'],
            'email' => $email
        );
        
        $response = $this->make_api_request($url, 'PUT', $data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Contact unsubscribed from ConvertKit successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Add tag to contact
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function add_tag($action, $trigger_data) {
        $tag_id = $action['tag_id'] ?? '';
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        
        if (empty($tag_id) || empty($email)) {
            return array('success' => false, 'error' => 'Tag ID and email are required');
        }
        
        $url = "https://api.convertkit.com/v3/tags/{$tag_id}/subscribe";
        
        $data = array(
            'api_key' => $this->settings['api_key'],
            'email' => $email
        );
        
        $response = $this->make_api_request($url, 'POST', $data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Tag added to ConvertKit contact successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Make API request to ConvertKit
     *
     * @param string $url
     * @param string $method
     * @param array $data
     * @return array
     */
    private function make_api_request($url, $method = 'GET', $data = array()) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );
        
        if (!empty($data)) {
            if ($method === 'GET') {
                $url .= '?' . http_build_query($data);
            } else {
                $args['body'] = json_encode($data);
            }
        }
        
        $response = wp_remote_request($url, $args);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($response_body, true);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array('success' => true, 'data' => $decoded_body);
        } else {
            $error_message = $decoded_body['message'] ?? "HTTP {$response_code}: {$response_body}";
            return array('success' => false, 'error' => $error_message);
        }
    }
}

// Initialize email marketing integrations
new SkyLearn_Billing_Pro_Mailchimp_Integration();
new SkyLearn_Billing_Pro_ConvertKit_Integration();