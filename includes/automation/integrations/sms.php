<?php
/**
 * SMS Integrations for Skylearn Billing Pro
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
 * Base SMS Integration class
 */
abstract class SkyLearn_Billing_Pro_SMS_Integration {
    
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
        add_filter('skylearn_billing_pro_execute_sms_action', array($this, 'handle_sms_action'), 10, 3);
    }
    
    /**
     * Get settings
     *
     * @return array
     */
    protected function get_settings() {
        $options = get_option('skylearn_billing_pro_options', array());
        return $options['sms_integrations'][$this->provider_name] ?? array();
    }
    
    /**
     * Handle SMS action
     *
     * @param array $result
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    public function handle_sms_action($result, $action, $trigger_data) {
        if (($action['provider'] ?? '') !== $this->provider_name) {
            return $result;
        }
        
        if (!$this->is_configured()) {
            return array('success' => false, 'error' => ucfirst($this->provider_name) . ' SMS is not configured');
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
     * Execute SMS action
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
    
    /**
     * Validate phone number format
     *
     * @param string $phone
     * @return bool
     */
    protected function validate_phone_number($phone) {
        // Basic phone number validation - should start with + and have at least 10 digits
        return preg_match('/^\+[1-9]\d{1,14}$/', $phone);
    }
    
    /**
     * Format phone number for international use
     *
     * @param string $phone
     * @param string $default_country_code
     * @return string
     */
    protected function format_phone_number($phone, $default_country_code = '+1') {
        // Remove all non-digit characters except +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // If phone doesn't start with +, add default country code
        if (!str_starts_with($phone, '+')) {
            $phone = $default_country_code . $phone;
        }
        
        return $phone;
    }
}

/**
 * Twilio SMS Integration
 */
class SkyLearn_Billing_Pro_Twilio_Integration extends SkyLearn_Billing_Pro_SMS_Integration {
    
    protected $provider_name = 'twilio';
    
    /**
     * Check if Twilio is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['account_sid']) && 
               !empty($this->settings['auth_token']) && 
               !empty($this->settings['from_number']);
    }
    
    /**
     * Execute Twilio SMS action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $to = $this->replace_placeholders($action['to'] ?? '', $trigger_data);
        $message = $this->replace_placeholders($action['message'] ?? '', $trigger_data);
        
        if (empty($to)) {
            return array('success' => false, 'error' => 'Recipient phone number is required');
        }
        
        if (empty($message)) {
            return array('success' => false, 'error' => 'Message is required');
        }
        
        // Format phone number
        $to = $this->format_phone_number($to);
        
        if (!$this->validate_phone_number($to)) {
            return array('success' => false, 'error' => 'Invalid phone number format');
        }
        
        return $this->send_sms($to, $message);
    }
    
    /**
     * Send SMS via Twilio
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    private function send_sms($to, $message) {
        $account_sid = $this->settings['account_sid'];
        $auth_token = $this->settings['auth_token'];
        $from_number = $this->settings['from_number'];
        
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$account_sid}/Messages.json";
        
        $data = array(
            'From' => $from_number,
            'To' => $to,
            'Body' => $message
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($account_sid . ':' . $auth_token),
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($data),
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
                'message_sid' => $decoded_body['sid'] ?? null,
                'message' => 'SMS sent successfully via Twilio'
            );
        } else {
            $error_message = $decoded_body['message'] ?? "HTTP {$response_code}: {$response_body}";
            return array('success' => false, 'error' => $error_message);
        }
    }
}

/**
 * Nexmo/Vonage SMS Integration
 */
class SkyLearn_Billing_Pro_Nexmo_Integration extends SkyLearn_Billing_Pro_SMS_Integration {
    
    protected $provider_name = 'nexmo';
    
    /**
     * Check if Nexmo is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['api_key']) && 
               !empty($this->settings['api_secret']) && 
               !empty($this->settings['from_number']);
    }
    
    /**
     * Execute Nexmo SMS action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $to = $this->replace_placeholders($action['to'] ?? '', $trigger_data);
        $message = $this->replace_placeholders($action['message'] ?? '', $trigger_data);
        
        if (empty($to)) {
            return array('success' => false, 'error' => 'Recipient phone number is required');
        }
        
        if (empty($message)) {
            return array('success' => false, 'error' => 'Message is required');
        }
        
        // Format phone number (remove + for Nexmo)
        $to = str_replace('+', '', $this->format_phone_number($to));
        
        return $this->send_sms($to, $message);
    }
    
    /**
     * Send SMS via Nexmo
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    private function send_sms($to, $message) {
        $api_key = $this->settings['api_key'];
        $api_secret = $this->settings['api_secret'];
        $from_number = $this->settings['from_number'];
        
        $url = 'https://rest.nexmo.com/sms/json';
        
        $data = array(
            'api_key' => $api_key,
            'api_secret' => $api_secret,
            'from' => $from_number,
            'to' => $to,
            'text' => $message
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/x-www-form-urlencoded'
            ),
            'body' => http_build_query($data),
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
            $messages = $decoded_body['messages'] ?? array();
            if (!empty($messages) && $messages[0]['status'] === '0') {
                return array(
                    'success' => true,
                    'message_id' => $messages[0]['message-id'] ?? null,
                    'message' => 'SMS sent successfully via Nexmo'
                );
            } else {
                $error_text = $messages[0]['error-text'] ?? 'Unknown error';
                return array('success' => false, 'error' => $error_text);
            }
        } else {
            return array('success' => false, 'error' => "HTTP {$response_code}: {$response_body}");
        }
    }
}

/**
 * TextMagic SMS Integration
 */
class SkyLearn_Billing_Pro_TextMagic_Integration extends SkyLearn_Billing_Pro_SMS_Integration {
    
    protected $provider_name = 'textmagic';
    
    /**
     * Check if TextMagic is configured
     *
     * @return bool
     */
    protected function is_configured() {
        return !empty($this->settings['username']) && !empty($this->settings['api_key']);
    }
    
    /**
     * Execute TextMagic SMS action
     *
     * @param array $action
     * @param array $trigger_data
     * @return array
     */
    protected function execute_action($action, $trigger_data) {
        $to = $this->replace_placeholders($action['to'] ?? '', $trigger_data);
        $message = $this->replace_placeholders($action['message'] ?? '', $trigger_data);
        
        if (empty($to)) {
            return array('success' => false, 'error' => 'Recipient phone number is required');
        }
        
        if (empty($message)) {
            return array('success' => false, 'error' => 'Message is required');
        }
        
        // Format phone number
        $to = $this->format_phone_number($to);
        
        if (!$this->validate_phone_number($to)) {
            return array('success' => false, 'error' => 'Invalid phone number format');
        }
        
        return $this->send_sms($to, $message);
    }
    
    /**
     * Send SMS via TextMagic
     *
     * @param string $to
     * @param string $message
     * @return array
     */
    private function send_sms($to, $message) {
        $username = $this->settings['username'];
        $api_key = $this->settings['api_key'];
        
        $url = 'https://rest.textmagic.com/api/v2/messages';
        
        $data = array(
            'text' => $message,
            'phones' => $to
        );
        
        $args = array(
            'method' => 'POST',
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $api_key),
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
                'message_id' => $decoded_body['id'] ?? null,
                'message' => 'SMS sent successfully via TextMagic'
            );
        } else {
            $error_message = $decoded_body['message'] ?? "HTTP {$response_code}: {$response_body}";
            return array('success' => false, 'error' => $error_message);
        }
    }
}

// Initialize SMS integrations
new SkyLearn_Billing_Pro_Twilio_Integration();
new SkyLearn_Billing_Pro_Nexmo_Integration();
new SkyLearn_Billing_Pro_TextMagic_Integration();