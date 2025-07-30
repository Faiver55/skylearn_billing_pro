<?php
/**
 * CRM Integrations for Skylearn Billing Pro
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
 * Base CRM Integration class
 */
abstract class SkyLearn_Billing_Pro_CRM_Integration {
    
    /**
     * CRM provider name
     *
     * @var string
     */
    protected $provider_name = '';
    
    /**
     * CRM settings
     *
     * @var array
     */
    protected $settings = array();
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = $this->get_settings();
        add_filter('skylearn_billing_pro_execute_crm_action', array($this, 'handle_crm_action'), 10, 3);
    }
    
    /**
     * Get CRM settings
     *
     * @return array
     */
    protected function get_settings() {
        $options = get_option('skylearn_billing_pro_options', array());
        return $options['crm_integrations'][$this->provider_name] ?? array();
    }
    
    /**
     * Handle CRM action
     *
     * @param array $result
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    public function handle_crm_action($result, $action, $trigger_data) {
        if (($action['provider'] ?? '') !== $this->provider_name) {
            return $result;
        }
        
        if (!$this->is_configured()) {
            return array('success' => false, 'error' => ucfirst($this->provider_name) . ' CRM is not configured');
        }
        
        return $this->execute_action($action, $trigger_data);
    }
    
    /**
     * Check if CRM is configured
     *
     * @return bool
     */
    abstract protected function is_configured();
    
    /**
     * Execute CRM action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    abstract protected function execute_action($action, $trigger_data);
}

/**
 * HubSpot CRM Integration
 */
class SkyLearn_Billing_Pro_HubSpot_Integration extends SkyLearn_Billing_Pro_CRM_Integration {
    
    protected $provider_name = 'hubspot';
    
    /**
     * Check if HubSpot is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['api_key']) || !empty($this->settings['access_token']);
    }
    
    /**
     * Execute HubSpot action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $crm_action = $action['action'] ?? '';
        $data = $action['data'] ?? array();
        
        // Replace placeholders in data
        $processed_data = $this->replace_placeholders_in_data($data, $trigger_data);
        
        switch ($crm_action) {
            case 'create_contact':
                return $this->create_contact($processed_data);
            case 'update_contact':
                return $this->update_contact($processed_data);
            case 'add_to_list':
                return $this->add_to_list($processed_data);
            default:
                return array('success' => false, 'error' => 'Unknown HubSpot action: ' . $crm_action);
        }
    }
    
    /**
     * Create contact in HubSpot
     *
     * @param array $data
     * @return array
     */
    private function create_contact($data) {
        $url = 'https://api.hubapi.com/crm/v3/objects/contacts';
        
        $body = array(
            'properties' => $data
        );
        
        $response = $this->make_api_request($url, 'POST', $body);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'contact_id' => $response['data']['id'] ?? null,
                'message' => 'Contact created successfully in HubSpot'
            );
        }
        
        return $response;
    }
    
    /**
     * Update contact in HubSpot
     *
     * @param array $data
     * @return array
     */
    private function update_contact($data) {
        $email = $data['email'] ?? '';
        if (empty($email)) {
            return array('success' => false, 'error' => 'Email is required to update contact');
        }
        
        // First, find the contact by email
        $search_url = 'https://api.hubapi.com/crm/v3/objects/contacts/search';
        $search_body = array(
            'filterGroups' => array(
                array(
                    'filters' => array(
                        array(
                            'propertyName' => 'email',
                            'operator' => 'EQ',
                            'value' => $email
                        )
                    )
                )
            )
        );
        
        $search_response = $this->make_api_request($search_url, 'POST', $search_body);
        
        if (!$search_response['success'] || empty($search_response['data']['results'])) {
            return array('success' => false, 'error' => 'Contact not found in HubSpot');
        }
        
        $contact_id = $search_response['data']['results'][0]['id'];
        
        // Update the contact
        $update_url = "https://api.hubapi.com/crm/v3/objects/contacts/{$contact_id}";
        $update_body = array(
            'properties' => $data
        );
        
        $response = $this->make_api_request($update_url, 'PATCH', $update_body);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'contact_id' => $contact_id,
                'message' => 'Contact updated successfully in HubSpot'
            );
        }
        
        return $response;
    }
    
    /**
     * Add contact to list in HubSpot
     *
     * @param array $data
     * @return array
     */
    private function add_to_list($data) {
        $list_id = $data['list_id'] ?? '';
        $email = $data['email'] ?? '';
        
        if (empty($list_id) || empty($email)) {
            return array('success' => false, 'error' => 'List ID and email are required');
        }
        
        // Implementation would depend on HubSpot's list API
        return array('success' => false, 'error' => 'List functionality not yet implemented');
    }
    
    /**
     * Make API request to HubSpot
     *
     * @param string $url
     * @param string $method
     * @param array $body
     * @return array
     */
    private function make_api_request($url, $method = 'GET', $body = array()) {
        $headers = array(
            'Content-Type' => 'application/json'
        );
        
        // Use API key or access token
        if (!empty($this->settings['access_token'])) {
            $headers['Authorization'] = 'Bearer ' . $this->settings['access_token'];
        } elseif (!empty($this->settings['api_key'])) {
            $url .= (strpos($url, '?') !== false ? '&' : '?') . 'hapikey=' . $this->settings['api_key'];
        }
        
        $args = array(
            'method' => $method,
            'headers' => $headers,
            'timeout' => 30
        );
        
        if (!empty($body) && in_array($method, array('POST', 'PUT', 'PATCH'))) {
            $args['body'] = json_encode($body);
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
    
    /**
     * Replace placeholders in data array
     *
     * @param array $data
     * @param array $trigger_data
     * @return array
     */
    private function replace_placeholders_in_data($data, $trigger_data) {
        $processed_data = array();
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $processed_data[$key] = $this->replace_placeholders($value, $trigger_data);
            } else {
                $processed_data[$key] = $value;
            }
        }
        
        return $processed_data;
    }
    
    /**
     * Replace placeholders in text
     *
     * @param string $text
     * @param array $trigger_data
     * @return string
     */
    private function replace_placeholders($text, $trigger_data) {
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
}

/**
 * Salesforce CRM Integration
 */
class SkyLearn_Billing_Pro_Salesforce_Integration extends SkyLearn_Billing_Pro_CRM_Integration {
    
    protected $provider_name = 'salesforce';
    
    /**
     * Check if Salesforce is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['client_id']) && 
               !empty($this->settings['client_secret']) && 
               !empty($this->settings['access_token']);
    }
    
    /**
     * Execute Salesforce action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        // Basic Salesforce implementation - would need OAuth flow for production
        return array('success' => false, 'error' => 'Salesforce integration requires full OAuth implementation');
    }
}

// Initialize CRM integrations
new SkyLearn_Billing_Pro_HubSpot_Integration();
new SkyLearn_Billing_Pro_Salesforce_Integration();