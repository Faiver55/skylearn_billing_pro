<?php
/**
 * Marketing Tool Integrations for Skylearn Billing Pro
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
 * Base Marketing Integration class
 */
abstract class SkyLearn_Billing_Pro_Marketing_Integration {
    
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
        add_filter('skylearn_billing_pro_execute_marketing_action', array($this, 'handle_marketing_action'), 10, 3);
    }
    
    /**
     * Get settings
     *
     * @return array
     */
    protected function get_settings() {
        $options = get_option('skylearn_billing_pro_options', array());
        return $options['marketing_integrations'][$this->provider_name] ?? array();
    }
    
    /**
     * Handle marketing action
     *
     * @param array $result
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    public function handle_marketing_action($result, $action, $trigger_data) {
        if (($action['provider'] ?? '') !== $this->provider_name) {
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
     * Execute marketing action
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
 * Facebook Pixel Integration
 */
class SkyLearn_Billing_Pro_Facebook_Pixel_Integration extends SkyLearn_Billing_Pro_Marketing_Integration {
    
    protected $provider_name = 'facebook_pixel';
    
    /**
     * Check if Facebook Pixel is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['pixel_id']) && !empty($this->settings['access_token']);
    }
    
    /**
     * Execute Facebook Pixel action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $event_name = $action['event'] ?? 'Purchase';
        $event_data = $action['data'] ?? array();
        
        // Process event data with placeholders
        $processed_data = $this->process_event_data($event_data, $trigger_data);
        
        return $this->send_conversion_event($event_name, $processed_data, $trigger_data);
    }
    
    /**
     * Process event data by replacing placeholders
     *
     * @param array $event_data
     * @param array $trigger_data
     * @return array
     */
    private function process_event_data($event_data, $trigger_data) {
        $processed = array();
        
        foreach ($event_data as $key => $value) {
            if (is_string($value)) {
                $processed[$key] = $this->replace_placeholders($value, $trigger_data);
            } else {
                $processed[$key] = $value;
            }
        }
        
        return $processed;
    }
    
    /**
     * Send conversion event to Facebook Pixel
     *
     * @param string $event_name
     * @param array $event_data
     * @param array $trigger_data
     * @return array
     */
    private function send_conversion_event($event_name, $event_data, $trigger_data) {
        $pixel_id = $this->settings['pixel_id'];
        $access_token = $this->settings['access_token'];
        
        $url = "https://graph.facebook.com/v18.0/{$pixel_id}/events";
        
        // Prepare event data
        $event = array(
            'event_name' => $event_name,
            'event_time' => time(),
            'action_source' => 'website',
            'event_source_url' => home_url(),
            'custom_data' => $event_data
        );
        
        // Add user data if available
        $user_email = $this->get_trigger_data_value($trigger_data, 'user.email');
        if ($user_email) {
            $event['user_data'] = array(
                'em' => hash('sha256', strtolower(trim($user_email)))
            );
            
            $user_phone = $this->get_trigger_data_value($trigger_data, 'user.phone');
            if ($user_phone) {
                $event['user_data']['ph'] = hash('sha256', preg_replace('/[^0-9]/', '', $user_phone));
            }
        }
        
        $data = array(
            'data' => array($event),
            'access_token' => $access_token
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $decoded_body = json_decode($response_body, true);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'events_received' => $decoded_body['events_received'] ?? 1,
                'message' => 'Facebook Pixel event sent successfully'
            );
        } else {
            $error_message = $decoded_body['error']['message'] ?? "HTTP {$response_code}: {$response_body}";
            return array('success' => false, 'error' => $error_message);
        }
    }
}

/**
 * Google Analytics 4 Integration
 */
class SkyLearn_Billing_Pro_GA4_Integration extends SkyLearn_Billing_Pro_Marketing_Integration {
    
    protected $provider_name = 'google_analytics';
    
    /**
     * Check if Google Analytics is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['measurement_id']) && !empty($this->settings['api_secret']);
    }
    
    /**
     * Execute Google Analytics action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $event_name = $action['event'] ?? 'purchase';
        $event_parameters = $action['parameters'] ?? array();
        
        // Process event parameters with placeholders
        $processed_parameters = $this->process_event_data($event_parameters, $trigger_data);
        
        return $this->send_measurement_event($event_name, $processed_parameters, $trigger_data);
    }
    
    /**
     * Process event data by replacing placeholders
     *
     * @param array $event_data
     * @param array $trigger_data
     * @return array
     */
    private function process_event_data($event_data, $trigger_data) {
        $processed = array();
        
        foreach ($event_data as $key => $value) {
            if (is_string($value)) {
                $processed[$key] = $this->replace_placeholders($value, $trigger_data);
            } else {
                $processed[$key] = $value;
            }
        }
        
        return $processed;
    }
    
    /**
     * Send measurement event to Google Analytics 4
     *
     * @param string $event_name
     * @param array $event_parameters
     * @param array $trigger_data
     * @return array
     */
    private function send_measurement_event($event_name, $event_parameters, $trigger_data) {
        $measurement_id = $this->settings['measurement_id'];
        $api_secret = $this->settings['api_secret'];
        
        $url = "https://www.google-analytics.com/mp/collect?measurement_id={$measurement_id}&api_secret={$api_secret}";
        
        // Generate client ID (should be more sophisticated in production)
        $user_email = $this->get_trigger_data_value($trigger_data, 'user.email');
        $client_id = $user_email ? md5($user_email) : uniqid();
        
        $data = array(
            'client_id' => $client_id,
            'events' => array(
                array(
                    'name' => $event_name,
                    'parameters' => $event_parameters
                )
            )
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($data),
            'timeout' => 30
        );
        
        $response = wp_remote_post($url, $args);
        
        if (is_wp_error($response)) {
            return array('success' => false, 'error' => $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'message' => 'Google Analytics event sent successfully'
            );
        } else {
            $response_body = wp_remote_retrieve_body($response);
            return array('success' => false, 'error' => "HTTP {$response_code}: {$response_body}");
        }
    }
}

/**
 * ActiveCampaign Integration
 */
class SkyLearn_Billing_Pro_ActiveCampaign_Integration extends SkyLearn_Billing_Pro_Marketing_Integration {
    
    protected $provider_name = 'activecampaign';
    
    /**
     * Check if ActiveCampaign is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['api_url']) && !empty($this->settings['api_key']);
    }
    
    /**
     * Execute ActiveCampaign action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $ac_action = $action['action'] ?? '';
        
        switch ($ac_action) {
            case 'add_contact':
                return $this->add_contact($action, $trigger_data);
            case 'add_tag':
                return $this->add_tag($action, $trigger_data);
            case 'trigger_automation':
                return $this->trigger_automation($action, $trigger_data);
            default:
                return array('success' => false, 'error' => 'Unknown ActiveCampaign action: ' . $ac_action);
        }
    }
    
    /**
     * Add contact to ActiveCampaign
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function add_contact($action, $trigger_data) {
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        $first_name = $this->replace_placeholders($action['first_name'] ?? '{{user.first_name}}', $trigger_data);
        $last_name = $this->replace_placeholders($action['last_name'] ?? '{{user.last_name}}', $trigger_data);
        
        if (empty($email)) {
            return array('success' => false, 'error' => 'Email is required');
        }
        
        $contact_data = array(
            'contact' => array(
                'email' => $email,
                'firstName' => $first_name,
                'lastName' => $last_name
            )
        );
        
        $response = $this->make_api_request('contacts', 'POST', $contact_data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'contact_id' => $response['data']['contact']['id'] ?? null,
                'message' => 'Contact added to ActiveCampaign successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Add tag to contact in ActiveCampaign
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function add_tag($action, $trigger_data) {
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        $tag = $action['tag'] ?? '';
        
        if (empty($email) || empty($tag)) {
            return array('success' => false, 'error' => 'Email and tag are required');
        }
        
        // First, find the contact
        $contact_response = $this->make_api_request("contacts?email={$email}", 'GET');
        
        if (!$contact_response['success'] || empty($contact_response['data']['contacts'])) {
            return array('success' => false, 'error' => 'Contact not found');
        }
        
        $contact_id = $contact_response['data']['contacts'][0]['id'];
        
        // Add tag to contact
        $tag_data = array(
            'contactTag' => array(
                'contact' => $contact_id,
                'tag' => $tag
            )
        );
        
        $response = $this->make_api_request('contactTags', 'POST', $tag_data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'Tag added to ActiveCampaign contact successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Trigger automation in ActiveCampaign
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    private function trigger_automation($action, $trigger_data) {
        $email = $this->replace_placeholders($action['email'] ?? '{{user.email}}', $trigger_data);
        $automation_id = $action['automation_id'] ?? '';
        
        if (empty($email) || empty($automation_id)) {
            return array('success' => false, 'error' => 'Email and automation ID are required');
        }
        
        // First, find the contact
        $contact_response = $this->make_api_request("contacts?email={$email}", 'GET');
        
        if (!$contact_response['success'] || empty($contact_response['data']['contacts'])) {
            return array('success' => false, 'error' => 'Contact not found');
        }
        
        $contact_id = $contact_response['data']['contacts'][0]['id'];
        
        // Add contact to automation
        $automation_data = array(
            'contactAutomation' => array(
                'contact' => $contact_id,
                'automation' => $automation_id
            )
        );
        
        $response = $this->make_api_request('contactAutomations', 'POST', $automation_data);
        
        if ($response['success']) {
            return array(
                'success' => true,
                'message' => 'ActiveCampaign automation triggered successfully'
            );
        }
        
        return $response;
    }
    
    /**
     * Make API request to ActiveCampaign
     *
     * @param string $endpoint
     * @param string $method
     * @param array $data
     * @return array
     */
    private function make_api_request($endpoint, $method = 'GET', $data = array()) {
        $api_url = rtrim($this->settings['api_url'], '/');
        $api_key = $this->settings['api_key'];
        
        $url = "{$api_url}/api/3/{$endpoint}";
        
        $args = array(
            'method' => $method,
            'headers' => array(
                'Api-Token' => $api_key,
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
            $error_message = $decoded_body['message'] ?? "HTTP {$response_code}: {$response_body}";
            return array('success' => false, 'error' => $error_message);
        }
    }
}

// Initialize marketing integrations
new SkyLearn_Billing_Pro_Facebook_Pixel_Integration();
new SkyLearn_Billing_Pro_GA4_Integration();
new SkyLearn_Billing_Pro_ActiveCampaign_Integration();