<?php
/**
 * User Enrollment class for Skylearn Billing Pro
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
 * User Enrollment class
 */
class SkyLearn_Billing_Pro_User_Enrollment {
    
    /**
     * LMS Manager instance
     *
     * @var SkyLearn_Billing_Pro_LMS_Manager
     */
    private $lms_manager;
    
    /**
     * Course Mapping instance
     *
     * @var SkyLearn_Billing_Pro_Course_Mapping
     */
    private $course_mapping;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->lms_manager = skylearn_billing_pro_lms_manager();
        $this->course_mapping = skylearn_billing_pro_course_mapping();
        
        // Hook into common enrollment triggers
        add_action('skylearn_billing_pro_payment_completed', array($this, 'handle_payment_enrollment'), 10, 2);
        add_action('skylearn_billing_pro_manual_enrollment', array($this, 'handle_manual_enrollment'), 10, 2);
    }
    
    /**
     * Create WordPress user account
     *
     * @param array $user_data User data
     * @return int|WP_Error User ID on success, WP_Error on failure
     */
    public function create_user_account($user_data) {
        // Validate required fields
        if (empty($user_data['email']) || !is_email($user_data['email'])) {
            return new WP_Error('invalid_email', __('Valid email address is required.', 'skylearn-billing-pro'));
        }
        
        // Check if user already exists
        $existing_user = get_user_by('email', $user_data['email']);
        if ($existing_user) {
            return $existing_user->ID;
        }
        
        // Generate username from email
        $username = $this->generate_username($user_data['email']);
        
        // Generate password
        $password = wp_generate_password();
        
        // Create user
        $user_id = wp_create_user($username, $password, $user_data['email']);
        
        if (is_wp_error($user_id)) {
            return $user_id;
        }
        
        // Update user profile
        $this->update_user_profile($user_id, $user_data);
        
        // Send welcome email if enabled
        $this->maybe_send_welcome_email($user_id, $password, $user_data);
        
        // Fire action hook
        do_action('skylearn_billing_pro_user_created', $user_id, $user_data);
        
        // Log user creation for analytics
        $this->log_user_activity($user_id, 'created', $user_data);
        
        return $user_id;
    }
    
    /**
     * Enroll user in course by product ID
     *
     * @param int $user_id User ID
     * @param string $product_id Product ID
     * @param string $trigger Trigger type
     * @return bool Success status
     */
    public function enroll_user_by_product($user_id, $product_id, $trigger = 'payment') {
        return $this->course_mapping->process_enrollment($product_id, $user_id, $trigger);
    }
    
    /**
     * Enroll user directly in course
     *
     * @param int $user_id User ID
     * @param int $course_id Course ID
     * @return bool Success status
     */
    public function enroll_user_in_course($user_id, $course_id) {
        if (!$this->lms_manager->has_active_lms()) {
            return false;
        }
        
        return $this->lms_manager->enroll_user($user_id, $course_id);
    }
    
    /**
     * Process full enrollment (create account + enroll in course)
     *
     * @param array $user_data User data
     * @param string $product_id Product ID
     * @param string $trigger Trigger type
     * @return array Result with success status and details
     */
    public function process_full_enrollment($user_data, $product_id, $trigger = 'payment') {
        // Create or get user account
        $user_id = $this->create_user_account($user_data);
        
        if (is_wp_error($user_id)) {
            return array(
                'success' => false,
                'error' => $user_id->get_error_message(),
                'code' => 'user_creation_failed'
            );
        }
        
        // Enroll user in course
        $enrollment_result = $this->enroll_user_by_product($user_id, $product_id, $trigger);
        
        if ($enrollment_result) {
            $result = array(
                'success' => true,
                'user_id' => $user_id,
                'product_id' => $product_id,
                'enrollment_status' => 'completed'
            );
            
            // Add course information
            $mapping = $this->course_mapping->get_course_mapping($product_id);
            if ($mapping) {
                $course_details = $this->lms_manager->get_course_details($mapping['course_id']);
                if ($course_details) {
                    $result['course_id'] = $mapping['course_id'];
                    $result['course_title'] = $course_details['title'];
                }
            }
            
            // Log enrollment for analytics
            $this->log_enrollment_activity($user_id, $product_id, 'success', $trigger, $result);
            
            // Send course enrollment email if enhanced email system is available
            if (function_exists('skylearn_billing_pro_email') && isset($result['course_title'])) {
                $email_manager = skylearn_billing_pro_email();
                $course_data = array(
                    'course_title' => $result['course_title'],
                    'course_id' => $result['course_id'] ?? '',
                    'product_id' => $product_id
                );
                
                // Add course URL and instructor if available
                $mapping = $this->course_mapping->get_course_mapping($product_id);
                if ($mapping) {
                    $course_details = $this->lms_manager->get_course_details($mapping['course_id']);
                    if ($course_details) {
                        $course_data['course_url'] = $course_details['url'] ?? '';
                        $course_data['instructor_name'] = $course_details['instructor'] ?? '';
                    }
                }
                
                // Trigger course enrollment email
                $email_manager->trigger_course_enrollment($user_id, $course_data);
            }
            
            return $result;
        } else {
            $result = array(
                'success' => false,
                'error' => 'Course enrollment failed',
                'code' => 'enrollment_failed',
                'user_id' => $user_id,
                'product_id' => $product_id
            );
            
            // Log failed enrollment
            $this->log_enrollment_activity($user_id, $product_id, 'failed', $trigger, $result);
            
            return $result;
        }
    }
    
    /**
     * Handle payment-triggered enrollment
     *
     * @param string $product_id Product ID
     * @param array $user_data User data
     */
    public function handle_payment_enrollment($product_id, $user_data) {
        $this->process_full_enrollment($user_data, $product_id, 'payment');
    }
    
    /**
     * Handle manual enrollment
     *
     * @param string $product_id Product ID
     * @param array $user_data User data
     */
    public function handle_manual_enrollment($product_id, $user_data) {
        $this->process_full_enrollment($user_data, $product_id, 'manual');
    }
    
    /**
     * Generate unique username from email
     *
     * @param string $email Email address
     * @return string Username
     */
    private function generate_username($email) {
        $username = sanitize_user(substr($email, 0, strpos($email, '@')));
        
        // Ensure username is unique
        $original_username = $username;
        $counter = 1;
        
        while (username_exists($username)) {
            $username = $original_username . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Update user profile with additional data
     *
     * @param int $user_id User ID
     * @param array $user_data User data
     */
    private function update_user_profile($user_id, $user_data) {
        $update_data = array('ID' => $user_id);
        
        // Update display name
        if (!empty($user_data['display_name'])) {
            $update_data['display_name'] = sanitize_text_field($user_data['display_name']);
        } elseif (!empty($user_data['first_name']) || !empty($user_data['last_name'])) {
            $display_name = trim(($user_data['first_name'] ?? '') . ' ' . ($user_data['last_name'] ?? ''));
            if (!empty($display_name)) {
                $update_data['display_name'] = $display_name;
            }
        }
        
        // Update user if there are changes
        if (count($update_data) > 1) {
            wp_update_user($update_data);
        }
        
        // Update user meta
        if (!empty($user_data['first_name'])) {
            update_user_meta($user_id, 'first_name', sanitize_text_field($user_data['first_name']));
        }
        
        if (!empty($user_data['last_name'])) {
            update_user_meta($user_id, 'last_name', sanitize_text_field($user_data['last_name']));
        }
        
        if (!empty($user_data['phone'])) {
            update_user_meta($user_id, 'phone', sanitize_text_field($user_data['phone']));
        }
        
        if (!empty($user_data['company'])) {
            update_user_meta($user_id, 'company', sanitize_text_field($user_data['company']));
        }
        
        // Store source information
        update_user_meta($user_id, 'skylearn_billing_source', 'billing_plugin');
        update_user_meta($user_id, 'skylearn_billing_created_date', current_time('mysql'));
    }
    
    /**
     * Maybe send welcome email to new user
     *
     * @param int $user_id User ID
     * @param string $password User password
     * @param array $user_data User data
     */
    private function maybe_send_welcome_email($user_id, $password, $user_data) {
        $options = get_option('skylearn_billing_pro_options', array());
        $send_welcome = isset($options['email_settings']['welcome_email_enabled']) ? $options['email_settings']['welcome_email_enabled'] : true;
        
        if (!$send_welcome) {
            return;
        }

        // Use the enhanced email system if available
        if (function_exists('skylearn_billing_pro_email')) {
            $email_manager = skylearn_billing_pro_email();
            $additional_data = array();
            
            // Add course information if available
            if (isset($user_data['product_id'])) {
                $mapping = $this->course_mapping->get_course_mapping($user_data['product_id']);
                if ($mapping) {
                    $course_details = $this->lms_manager->get_course_details($mapping['course_id']);
                    if ($course_details) {
                        $additional_data['course_title'] = $course_details['title'];
                        $additional_data['course_url'] = $course_details['url'] ?? '';
                        $additional_data['instructor_name'] = $course_details['instructor'] ?? '';
                    }
                }
            }
            
            // Add any additional order/payment data
            if (isset($user_data['order_id'])) {
                $additional_data['order_id'] = $user_data['order_id'];
            }
            if (isset($user_data['product_name'])) {
                $additional_data['product_name'] = $user_data['product_name'];
            }
            
            return $email_manager->trigger_welcome_email($user_id, $password, $additional_data);
        }

        // Use the new welcome email admin class if available
        if (function_exists('skylearn_billing_pro_welcome_email_admin')) {
            $welcome_email_admin = skylearn_billing_pro_welcome_email_admin();
            $additional_data = array();
            
            // Add course information if available
            if (isset($user_data['product_id'])) {
                $mapping = $this->course_mapping->get_course_mapping($user_data['product_id']);
                if ($mapping) {
                    $course_details = $this->lms_manager->get_course_details($mapping['course_id']);
                    if ($course_details) {
                        $additional_data['course_title'] = $course_details['title'];
                    }
                }
            }
            
            return $welcome_email_admin->send_welcome_email($user_id, $password, $additional_data);
        }
        
        // Fallback to basic email (backward compatibility)
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }
        
        $subject = sprintf(__('Welcome to %s', 'skylearn-billing-pro'), get_bloginfo('name'));
        $login_url = wp_login_url();
        
        $message = sprintf(
            __('Hello %s,

Welcome to %s! Your account has been created successfully.

Your login credentials:
Username: %s
Password: %s

You can login here: %s

Best regards,
%s', 'skylearn-billing-pro'),
            $user_data['display_name'] ?? $user->user_email,
            get_bloginfo('name'),
            $user->user_login,
            $password,
            $login_url,
            get_bloginfo('name')
        );
        
        return wp_mail($user->user_email, $subject, $message);
    }
    
    /**
     * Get enrollment statistics
     *
     * @return array Enrollment statistics
     */
    public function get_enrollment_stats() {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        
        $stats = array(
            'total_enrollments' => count($enrollment_log),
            'successful_enrollments' => 0,
            'failed_enrollments' => 0,
            'enrollments_today' => 0,
            'enrollments_this_week' => 0,
            'enrollments_this_month' => 0
        );
        
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');
        
        foreach ($enrollment_log as $entry) {
            // Count by status
            if ($entry['status'] === 'success') {
                $stats['successful_enrollments']++;
            } else {
                $stats['failed_enrollments']++;
            }
            
            // Count by date
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            
            if ($entry_date === $today) {
                $stats['enrollments_today']++;
            }
            
            if ($entry_date >= $week_start) {
                $stats['enrollments_this_week']++;
            }
            
            if ($entry_date >= $month_start) {
                $stats['enrollments_this_month']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Manual enrollment via admin interface
     *
     * @param string $email User email
     * @param string $product_id Product ID
     * @param array $additional_data Additional user data
     * @return array Result
     */
    public function manual_admin_enrollment($email, $product_id, $additional_data = array()) {
        $user_data = array_merge(array('email' => $email), $additional_data);
        
        return $this->process_full_enrollment($user_data, $product_id, 'manual');
    }
    
    /**
     * Log user activity for analytics
     *
     * @param int $user_id User ID
     * @param string $action Action performed
     * @param array $user_data User data
     */
    private function log_user_activity($user_id, $action, $user_data) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['user_activity_log'])) {
            $options['user_activity_log'] = array();
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'action' => $action,
            'email' => $user_data['email'] ?? '',
            'display_name' => $user_data['display_name'] ?? '',
            'source' => 'billing_plugin',
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : ''
        );
        
        $options['user_activity_log'][] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($options['user_activity_log']) > 1000) {
            $options['user_activity_log'] = array_slice($options['user_activity_log'], -1000);
        }
        
        update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Log enrollment activity for analytics
     *
     * @param int $user_id User ID
     * @param string $product_id Product ID
     * @param string $status Status (success/failed)
     * @param string $trigger Trigger type
     * @param array $result Enrollment result
     */
    private function log_enrollment_activity($user_id, $product_id, $status, $trigger, $result) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['enrollment_log'])) {
            $options['enrollment_log'] = array();
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'product_id' => $product_id,
            'course_id' => $result['course_id'] ?? '',
            'course_title' => $result['course_title'] ?? '',
            'status' => $status,
            'trigger' => $trigger,
            'error_message' => $result['error'] ?? '',
            'ip_address' => $this->get_user_ip()
        );
        
        $options['enrollment_log'][] = $log_entry;
        
        // Keep only last 1000 entries  
        if (count($options['enrollment_log']) > 1000) {
            $options['enrollment_log'] = array_slice($options['enrollment_log'], -1000);
        }
        
        update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Get user IP address
     *
     * @return string IP address
     */
    private function get_user_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return sanitize_text_field($_SERVER['HTTP_CLIENT_IP']);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return sanitize_text_field($_SERVER['HTTP_X_FORWARDED_FOR']);
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            return sanitize_text_field($_SERVER['REMOTE_ADDR']);
        }
        return '';
    }
    
    /**
     * Get detailed analytics data
     *
     * @return array Analytics data
     */
    public function get_detailed_analytics() {
        $options = get_option('skylearn_billing_pro_options', array());
        $enrollment_log = isset($options['enrollment_log']) ? $options['enrollment_log'] : array();
        $user_activity_log = isset($options['user_activity_log']) ? $options['user_activity_log'] : array();
        
        $analytics = array(
            'enrollment_stats' => $this->get_enrollment_stats(),
            'user_creation_stats' => $this->get_user_creation_stats(),
            'recent_enrollments' => array_slice(array_reverse($enrollment_log), 0, 10),
            'recent_user_activity' => array_slice(array_reverse($user_activity_log), 0, 10),
            'popular_courses' => $this->get_popular_courses($enrollment_log),
            'conversion_by_trigger' => $this->get_conversion_by_trigger($enrollment_log)
        );
        
        return $analytics;
    }
    
    /**
     * Get user creation statistics
     *
     * @return array User creation stats
     */
    private function get_user_creation_stats() {
        $options = get_option('skylearn_billing_pro_options', array());
        $user_activity_log = isset($options['user_activity_log']) ? $options['user_activity_log'] : array();
        
        $stats = array(
            'total_users_created' => 0,
            'users_today' => 0,
            'users_this_week' => 0,
            'users_this_month' => 0
        );
        
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');
        
        foreach ($user_activity_log as $entry) {
            if ($entry['action'] === 'created') {
                $stats['total_users_created']++;
                
                $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
                
                if ($entry_date === $today) {
                    $stats['users_today']++;
                }
                
                if ($entry_date >= $week_start) {
                    $stats['users_this_week']++;
                }
                
                if ($entry_date >= $month_start) {
                    $stats['users_this_month']++;
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Get popular courses from enrollment data
     *
     * @param array $enrollment_log Enrollment log
     * @return array Popular courses
     */
    private function get_popular_courses($enrollment_log) {
        $course_counts = array();
        
        foreach ($enrollment_log as $entry) {
            if ($entry['status'] === 'success' && !empty($entry['course_id'])) {
                $course_key = $entry['course_id'];
                if (!isset($course_counts[$course_key])) {
                    $course_counts[$course_key] = array(
                        'course_id' => $entry['course_id'],
                        'course_title' => $entry['course_title'],
                        'enrollment_count' => 0
                    );
                }
                $course_counts[$course_key]['enrollment_count']++;
            }
        }
        
        // Sort by enrollment count
        uasort($course_counts, function($a, $b) {
            return $b['enrollment_count'] - $a['enrollment_count'];
        });
        
        return array_slice($course_counts, 0, 5);
    }
    
    /**
     * Get conversion rates by trigger type
     *
     * @param array $enrollment_log Enrollment log
     * @return array Conversion data
     */
    private function get_conversion_by_trigger($enrollment_log) {
        $trigger_stats = array();
        
        foreach ($enrollment_log as $entry) {
            $trigger = $entry['trigger'];
            if (!isset($trigger_stats[$trigger])) {
                $trigger_stats[$trigger] = array(
                    'total' => 0,
                    'successful' => 0,
                    'failed' => 0
                );
            }
            
            $trigger_stats[$trigger]['total']++;
            
            if ($entry['status'] === 'success') {
                $trigger_stats[$trigger]['successful']++;
            } else {
                $trigger_stats[$trigger]['failed']++;
            }
        }
        
        // Calculate conversion rates
        foreach ($trigger_stats as $trigger => &$stats) {
            $stats['conversion_rate'] = $stats['total'] > 0 ? round(($stats['successful'] / $stats['total']) * 100, 2) : 0;
        }
        
        return $trigger_stats;
    }
}

/**
 * Get the User Enrollment instance
 *
 * @return SkyLearn_Billing_Pro_User_Enrollment
 */
function skylearn_billing_pro_user_enrollment() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_User_Enrollment();
    }
    
    return $instance;
}