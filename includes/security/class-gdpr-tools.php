<?php
/**
 * GDPR/Privacy Tools for Skylearn Billing Pro
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
 * GDPR Tools class for handling privacy compliance
 */
class SkyLearn_Billing_Pro_GDPR_Tools {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_skylearn_export_user_data', array($this, 'ajax_export_user_data'));
        add_action('wp_ajax_skylearn_delete_user_data', array($this, 'ajax_delete_user_data'));
        add_filter('wp_privacy_personal_data_exporters', array($this, 'register_data_exporter'));
        add_filter('wp_privacy_personal_data_erasers', array($this, 'register_data_eraser'));
    }
    
    /**
     * Initialize GDPR tools
     */
    public function init() {
        // Register privacy policy content
        if (function_exists('wp_add_privacy_policy_content')) {
            wp_add_privacy_policy_content(
                'Skylearn Billing Pro',
                $this->get_privacy_policy_content()
            );
        }
    }
    
    /**
     * Get privacy policy content
     */
    public function get_privacy_policy_content() {
        $content = '<h2>' . __('Billing and Payment Information', 'skylearn-billing-pro') . '</h2>';
        $content .= '<p>' . __('We collect billing information including name, email address, payment method details, and transaction history to process payments and manage your account.', 'skylearn-billing-pro') . '</p>';
        $content .= '<h3>' . __('What we collect and store', 'skylearn-billing-pro') . '</h3>';
        $content .= '<ul>';
        $content .= '<li>' . __('Name and contact information', 'skylearn-billing-pro') . '</li>';
        $content .= '<li>' . __('Billing and payment information', 'skylearn-billing-pro') . '</li>';
        $content .= '<li>' . __('Course enrollment and progress data', 'skylearn-billing-pro') . '</li>';
        $content .= '<li>' . __('Communication preferences', 'skylearn-billing-pro') . '</li>';
        $content .= '</ul>';
        
        return $content;
    }
    
    /**
     * Register data exporter for WordPress privacy tools
     */
    public function register_data_exporter($exporters) {
        $exporters['skylearn-billing-pro'] = array(
            'exporter_friendly_name' => __('Skylearn Billing Pro', 'skylearn-billing-pro'),
            'callback' => array($this, 'export_user_data'),
        );
        return $exporters;
    }
    
    /**
     * Register data eraser for WordPress privacy tools
     */
    public function register_data_eraser($erasers) {
        $erasers['skylearn-billing-pro'] = array(
            'eraser_friendly_name' => __('Skylearn Billing Pro', 'skylearn-billing-pro'),
            'callback' => array($this, 'erase_user_data'),
        );
        return $erasers;
    }
    
    /**
     * Export user data
     */
    public function export_user_data($email_address, $page = 1) {
        $data_to_export = array();
        
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return array(
                'data' => array(),
                'done' => true,
            );
        }
        
        // Get user's billing data
        $billing_data = $this->get_user_billing_data($user->ID);
        
        if (!empty($billing_data)) {
            $data_to_export[] = array(
                'group_id' => 'skylearn_billing_data',
                'group_label' => __('Billing Data', 'skylearn-billing-pro'),
                'item_id' => 'billing-' . $user->ID,
                'data' => $billing_data,
            );
        }
        
        // Get user's enrollment data
        $enrollment_data = $this->get_user_enrollment_data($user->ID);
        
        if (!empty($enrollment_data)) {
            $data_to_export[] = array(
                'group_id' => 'skylearn_enrollment_data',
                'group_label' => __('Enrollment Data', 'skylearn-billing-pro'),
                'item_id' => 'enrollment-' . $user->ID,
                'data' => $enrollment_data,
            );
        }
        
        return array(
            'data' => $data_to_export,
            'done' => true,
        );
    }
    
    /**
     * Erase user data
     */
    public function erase_user_data($email_address, $page = 1) {
        $user = get_user_by('email', $email_address);
        if (!$user) {
            return array(
                'items_removed' => 0,
                'items_retained' => 0,
                'messages' => array(),
                'done' => true,
            );
        }
        
        $items_removed = 0;
        $items_retained = 0;
        $messages = array();
        
        // Remove or anonymize billing data
        $billing_removed = $this->anonymize_user_billing_data($user->ID);
        $items_removed += $billing_removed;
        
        // Remove enrollment logs (but keep active enrollments for course access)
        $logs_removed = $this->remove_user_logs($user->ID);
        $items_removed += $logs_removed;
        
        if ($items_removed > 0) {
            $messages[] = sprintf(__('Removed %d billing and log entries.', 'skylearn-billing-pro'), $items_removed);
        }
        
        // Note about retained data
        if ($this->user_has_active_subscriptions($user->ID)) {
            $items_retained++;
            $messages[] = __('Active subscription data retained for service continuity.', 'skylearn-billing-pro');
        }
        
        return array(
            'items_removed' => $items_removed,
            'items_retained' => $items_retained,
            'messages' => $messages,
            'done' => true,
        );
    }
    
    /**
     * Get user billing data
     */
    private function get_user_billing_data($user_id) {
        $options = get_option('skylearn_billing_pro_options', array());
        $billing_data = array();
        
        // Get from user meta and options
        $user_meta = get_user_meta($user_id);
        
        // Check for billing-related meta
        foreach ($user_meta as $key => $value) {
            if (strpos($key, 'skylearn_') === 0 || strpos($key, 'billing_') === 0) {
                $billing_data[$key] = array(
                    'name' => $key,
                    'value' => is_array($value) ? implode(', ', $value) : $value[0],
                );
            }
        }
        
        return $billing_data;
    }
    
    /**
     * Get user enrollment data
     */
    private function get_user_enrollment_data($user_id) {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_data = array();
        
        // Get enrollment log for this user
        if (isset($options['enrollment_log']) && is_array($options['enrollment_log'])) {
            foreach ($options['enrollment_log'] as $log_entry) {
                if (isset($log_entry['user_id']) && $log_entry['user_id'] == $user_id) {
                    $enrollment_data[] = array(
                        'name' => __('Enrollment Date', 'skylearn-billing-pro'),
                        'value' => isset($log_entry['date']) ? $log_entry['date'] : __('Unknown', 'skylearn-billing-pro'),
                    );
                    if (isset($log_entry['course_id'])) {
                        $enrollment_data[] = array(
                            'name' => __('Course ID', 'skylearn-billing-pro'),
                            'value' => $log_entry['course_id'],
                        );
                    }
                }
            }
        }
        
        return $enrollment_data;
    }
    
    /**
     * Anonymize user billing data
     */
    private function anonymize_user_billing_data($user_id) {
        $removed = 0;
        
        // Remove billing-related user meta
        $user_meta_keys = get_user_meta($user_id);
        foreach ($user_meta_keys as $key => $value) {
            if (strpos($key, 'skylearn_') === 0 || strpos($key, 'billing_') === 0) {
                delete_user_meta($user_id, $key);
                $removed++;
            }
        }
        
        return $removed;
    }
    
    /**
     * Remove user logs
     */
    private function remove_user_logs($user_id) {
        $options = get_option('skylearn_billing_pro_options', array());
        $removed = 0;
        
        // Remove from enrollment log
        if (isset($options['enrollment_log']) && is_array($options['enrollment_log'])) {
            $original_count = count($options['enrollment_log']);
            $options['enrollment_log'] = array_filter($options['enrollment_log'], function($entry) use ($user_id) {
                return !isset($entry['user_id']) || $entry['user_id'] != $user_id;
            });
            $removed += $original_count - count($options['enrollment_log']);
        }
        
        // Remove from email log
        if (isset($options['email_log']) && is_array($options['email_log'])) {
            $original_count = count($options['email_log']);
            $options['email_log'] = array_filter($options['email_log'], function($entry) use ($user_id) {
                return !isset($entry['user_id']) || $entry['user_id'] != $user_id;
            });
            $removed += $original_count - count($options['email_log']);
        }
        
        // Remove from user activity log
        if (isset($options['user_activity_log']) && is_array($options['user_activity_log'])) {
            $original_count = count($options['user_activity_log']);
            $options['user_activity_log'] = array_filter($options['user_activity_log'], function($entry) use ($user_id) {
                return !isset($entry['user_id']) || $entry['user_id'] != $user_id;
            });
            $removed += $original_count - count($options['user_activity_log']);
        }
        
        update_option('skylearn_billing_pro_options', $options);
        
        return $removed;
    }
    
    /**
     * Check if user has active subscriptions
     */
    private function user_has_active_subscriptions($user_id) {
        // This would integrate with the subscription manager
        // For now, return false as a safe default
        return false;
    }
    
    /**
     * AJAX handler for exporting user data
     */
    public function ajax_export_user_data() {
        // Verify nonce and capabilities
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'skylearn_gdpr_action')) {
            wp_die(__('Unauthorized access', 'skylearn-billing-pro'));
        }
        
        $email = sanitize_email($_POST['email']);
        if (!$email) {
            wp_send_json_error(__('Invalid email address', 'skylearn-billing-pro'));
        }
        
        $export_data = $this->export_user_data($email);
        
        wp_send_json_success($export_data);
    }
    
    /**
     * AJAX handler for deleting user data
     */
    public function ajax_delete_user_data() {
        // Verify nonce and capabilities
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'skylearn_gdpr_action')) {
            wp_die(__('Unauthorized access', 'skylearn-billing-pro'));
        }
        
        $email = sanitize_email($_POST['email']);
        if (!$email) {
            wp_send_json_error(__('Invalid email address', 'skylearn-billing-pro'));
        }
        
        $erase_result = $this->erase_user_data($email);
        
        wp_send_json_success($erase_result);
    }
}

/**
 * Get single instance of GDPR Tools
 */
function skylearn_billing_pro_gdpr_tools() {
    static $instance = null;
    if (null === $instance) {
        $instance = new SkyLearn_Billing_Pro_GDPR_Tools();
    }
    return $instance;
}