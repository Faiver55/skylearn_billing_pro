<?php
/**
 * Email Management System for Skylearn Billing Pro
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
 * Email Management class
 */
class SkyLearn_Billing_Pro_Email {
    
    /**
     * Email types
     */
    const EMAIL_TYPES = [
        'welcome' => 'Welcome Email',
        'order_confirmation' => 'Order Confirmation',
        'invoice' => 'Invoice Email',
        'payment_confirmation' => 'Payment Confirmation',
        'payment_failed' => 'Payment Failed',
        'refund_notification' => 'Refund Notification',
        'course_enrollment' => 'Course Enrollment',
        'subscription_created' => 'Subscription Created',
        'subscription_cancelled' => 'Subscription Cancelled',
        'subscription_renewed' => 'Subscription Renewed'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_skylearn_preview_email', array($this, 'ajax_preview_email'));
        add_action('wp_ajax_skylearn_send_test_email', array($this, 'ajax_send_test_email'));
        add_action('wp_ajax_skylearn_save_email_template', array($this, 'ajax_save_email_template'));
        add_action('wp_ajax_skylearn_get_email_template', array($this, 'ajax_get_email_template'));
        add_action('wp_ajax_skylearn_get_email_analytics', array($this, 'ajax_get_email_analytics'));
        
        // Hook into WordPress mail to log emails
        add_action('wp_mail', array($this, 'log_wp_mail'), 10, 1);
        
        // Add email triggers
        $this->setup_email_triggers();
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Register email settings
        register_setting('skylearn_billing_pro_email', 'skylearn_billing_pro_email_options', array($this, 'sanitize_email_options'));
        
        // Add email settings sections
        foreach (self::EMAIL_TYPES as $type => $label) {
            add_settings_section(
                'skylearn_billing_pro_email_' . $type,
                sprintf(__('%s Settings', 'skylearn-billing-pro'), $label),
                array($this, 'email_section_callback'),
                'skylearn_billing_pro_email_' . $type
            );
        }
        
        // Add SMTP settings section
        add_settings_section(
            'skylearn_billing_pro_smtp_settings',
            __('SMTP & Email Provider Settings', 'skylearn-billing-pro'),
            array($this, 'smtp_section_callback'),
            'skylearn_billing_pro_smtp'
        );
        
        $this->add_smtp_fields();
    }
    
    /**
     * Add SMTP settings fields
     */
    private function add_smtp_fields() {
        $smtp_fields = [
            'smtp_enabled' => __('Enable SMTP', 'skylearn-billing-pro'),
            'smtp_host' => __('SMTP Host', 'skylearn-billing-pro'),
            'smtp_port' => __('SMTP Port', 'skylearn-billing-pro'),
            'smtp_secure' => __('Encryption', 'skylearn-billing-pro'),
            'smtp_auth' => __('Authentication', 'skylearn-billing-pro'),
            'smtp_username' => __('Username', 'skylearn-billing-pro'),
            'smtp_password' => __('Password', 'skylearn-billing-pro'),
            'from_email' => __('From Email', 'skylearn-billing-pro'),
            'from_name' => __('From Name', 'skylearn-billing-pro')
        ];
        
        foreach ($smtp_fields as $field => $label) {
            add_settings_field(
                $field,
                $label,
                array($this, 'smtp_field_callback'),
                'skylearn_billing_pro_smtp',
                'skylearn_billing_pro_smtp_settings',
                ['field' => $field, 'label' => $label]
            );
        }
    }
    
    /**
     * Setup email triggers
     */
    private function setup_email_triggers() {
        // Payment-related triggers
        add_action('skylearn_billing_payment_completed', array($this, 'trigger_payment_confirmation'), 10, 2);
        add_action('skylearn_billing_payment_failed', array($this, 'trigger_payment_failed'), 10, 2);
        add_action('skylearn_billing_refund_processed', array($this, 'trigger_refund_notification'), 10, 2);
        
        // User enrollment triggers
        add_action('skylearn_billing_user_enrolled', array($this, 'trigger_course_enrollment'), 10, 3);
        add_action('skylearn_billing_user_created', array($this, 'trigger_welcome_email'), 10, 3);
        
        // Subscription triggers
        add_action('skylearn_billing_subscription_created', array($this, 'trigger_subscription_created'), 10, 2);
        add_action('skylearn_billing_subscription_cancelled', array($this, 'trigger_subscription_cancelled'), 10, 2);
        add_action('skylearn_billing_subscription_renewed', array($this, 'trigger_subscription_renewed'), 10, 2);
    }
    
    /**
     * Sanitize email options
     */
    public function sanitize_email_options($input) {
        $sanitized = array();
        
        if (isset($input['smtp_enabled'])) {
            $sanitized['smtp_enabled'] = (bool) $input['smtp_enabled'];
        }
        
        if (isset($input['smtp_host'])) {
            $sanitized['smtp_host'] = sanitize_text_field($input['smtp_host']);
        }
        
        if (isset($input['smtp_port'])) {
            $sanitized['smtp_port'] = absint($input['smtp_port']);
        }
        
        if (isset($input['smtp_secure'])) {
            $sanitized['smtp_secure'] = sanitize_text_field($input['smtp_secure']);
        }
        
        if (isset($input['smtp_auth'])) {
            $sanitized['smtp_auth'] = (bool) $input['smtp_auth'];
        }
        
        if (isset($input['smtp_username'])) {
            $sanitized['smtp_username'] = sanitize_text_field($input['smtp_username']);
        }
        
        if (isset($input['smtp_password'])) {
            $sanitized['smtp_password'] = $input['smtp_password']; // Don't sanitize password
        }
        
        if (isset($input['from_email'])) {
            $sanitized['from_email'] = sanitize_email($input['from_email']);
        }
        
        if (isset($input['from_name'])) {
            $sanitized['from_name'] = sanitize_text_field($input['from_name']);
        }
        
        return $sanitized;
    }
    
    /**
     * Email section callback
     */
    public function email_section_callback($args) {
        echo '<p>' . esc_html__('Configure email templates and settings.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * SMTP section callback
     */
    public function smtp_section_callback() {
        echo '<p>' . esc_html__('Configure SMTP settings for reliable email delivery.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * SMTP field callback
     */
    public function smtp_field_callback($args) {
        $options = get_option('skylearn_billing_pro_email_options', array());
        $field = $args['field'];
        $value = isset($options[$field]) ? $options[$field] : '';
        
        switch ($field) {
            case 'smtp_enabled':
            case 'smtp_auth':
                echo '<label>';
                echo '<input type="checkbox" name="skylearn_billing_pro_email_options[' . esc_attr($field) . ']" value="1"' . checked($value, true, false) . ' />';
                echo ' ' . esc_html($args['label']);
                echo '</label>';
                break;
                
            case 'smtp_secure':
                echo '<select name="skylearn_billing_pro_email_options[' . esc_attr($field) . ']">';
                echo '<option value=""' . selected($value, '', false) . '>' . esc_html__('None', 'skylearn-billing-pro') . '</option>';
                echo '<option value="ssl"' . selected($value, 'ssl', false) . '>SSL</option>';
                echo '<option value="tls"' . selected($value, 'tls', false) . '>TLS</option>';
                echo '</select>';
                break;
                
            case 'smtp_port':
                echo '<input type="number" name="skylearn_billing_pro_email_options[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" min="1" max="65535" />';
                echo '<p class="description">' . esc_html__('Common ports: 25, 465 (SSL), 587 (TLS)', 'skylearn-billing-pro') . '</p>';
                break;
                
            case 'smtp_password':
                echo '<input type="password" name="skylearn_billing_pro_email_options[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
                break;
                
            default:
                echo '<input type="text" name="skylearn_billing_pro_email_options[' . esc_attr($field) . ']" value="' . esc_attr($value) . '" class="regular-text" />';
        }
    }
    
    /**
     * Get email template
     */
    public function get_email_template($type, $language = 'en') {
        $options = get_option('skylearn_billing_pro_email_templates', array());
        
        if (isset($options[$type][$language])) {
            return $options[$type][$language];
        }
        
        // Return default template if not found
        return $this->get_default_email_template($type);
    }
    
    /**
     * Save email template
     */
    public function save_email_template($type, $template_data, $language = 'en') {
        $options = get_option('skylearn_billing_pro_email_templates', array());
        
        if (!isset($options[$type])) {
            $options[$type] = array();
        }
        
        $options[$type][$language] = $template_data;
        
        return update_option('skylearn_billing_pro_email_templates', $options);
    }
    
    /**
     * Get default email template
     */
    public function get_default_email_template($type) {
        $templates = array(
            'welcome' => array(
                'subject' => __('Welcome to {{site_name}}!', 'skylearn-billing-pro'),
                'content' => $this->get_welcome_template_content(),
                'enabled' => true
            ),
            'order_confirmation' => array(
                'subject' => __('Order Confirmation - {{order_id}}', 'skylearn-billing-pro'),
                'content' => $this->get_order_confirmation_template_content(),
                'enabled' => true
            ),
            'invoice' => array(
                'subject' => __('Invoice {{invoice_id}} from {{site_name}}', 'skylearn-billing-pro'),
                'content' => $this->get_invoice_template_content(),
                'enabled' => true
            ),
            'payment_confirmation' => array(
                'subject' => __('Payment Received - {{order_id}}', 'skylearn-billing-pro'),
                'content' => $this->get_payment_confirmation_template_content(),
                'enabled' => true
            ),
            'payment_failed' => array(
                'subject' => __('Payment Failed - {{order_id}}', 'skylearn-billing-pro'),
                'content' => $this->get_payment_failed_template_content(),
                'enabled' => true
            ),
            'refund_notification' => array(
                'subject' => __('Refund Processed - {{order_id}}', 'skylearn-billing-pro'),
                'content' => $this->get_refund_notification_template_content(),
                'enabled' => true
            ),
            'course_enrollment' => array(
                'subject' => __('You\'re enrolled in {{course_title}}!', 'skylearn-billing-pro'),
                'content' => $this->get_course_enrollment_template_content(),
                'enabled' => true
            ),
            'subscription_created' => array(
                'subject' => __('Subscription Confirmed - {{subscription_id}}', 'skylearn-billing-pro'),
                'content' => $this->get_subscription_created_template_content(),
                'enabled' => true
            ),
            'subscription_cancelled' => array(
                'subject' => __('Subscription Cancelled - {{subscription_id}}', 'skylearn-billing-pro'),
                'content' => $this->get_subscription_cancelled_template_content(),
                'enabled' => true
            ),
            'subscription_renewed' => array(
                'subject' => __('Subscription Renewed - {{subscription_id}}', 'skylearn-billing-pro'),
                'content' => $this->get_subscription_renewed_template_content(),
                'enabled' => true
            )
        );
        
        return isset($templates[$type]) ? $templates[$type] : array();
    }
    
    /**
     * Send email with template
     */
    public function send_email($type, $recipient, $data = array(), $language = 'en') {
        $template = $this->get_email_template($type, $language);
        
        if (empty($template) || !isset($template['enabled']) || !$template['enabled']) {
            return false;
        }
        
        // Prepare email data with defaults
        $email_data = array_merge(array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
            'current_date' => current_time('F j, Y'),
            'current_time' => current_time('g:i A')
        ), $data);
        
        // Replace tokens in subject and content
        $subject = $this->replace_tokens($template['subject'], $email_data);
        $content = $this->replace_tokens($template['content'], $email_data);
        
        // Setup SMTP if enabled
        $this->setup_smtp();
        
        // Set content type
        add_filter('wp_mail_content_type', function() {
            return 'text/html';
        });
        
        // Set from name and email
        $this->setup_from_headers();
        
        $result = wp_mail($recipient, $subject, $content);
        
        // Remove filters
        remove_all_filters('wp_mail_content_type');
        remove_all_filters('wp_mail_from');
        remove_all_filters('wp_mail_from_name');
        
        // Log email
        $this->log_email($type, $recipient, $subject, $result, $email_data);
        
        return $result;
    }
    
    /**
     * Setup SMTP configuration
     */
    private function setup_smtp() {
        $options = get_option('skylearn_billing_pro_email_options', array());
        
        if (!isset($options['smtp_enabled']) || !$options['smtp_enabled']) {
            return;
        }
        
        add_action('phpmailer_init', function($phpmailer) use ($options) {
            $phpmailer->isSMTP();
            $phpmailer->Host = isset($options['smtp_host']) ? $options['smtp_host'] : '';
            $phpmailer->Port = isset($options['smtp_port']) ? $options['smtp_port'] : 587;
            
            if (isset($options['smtp_secure']) && $options['smtp_secure']) {
                $phpmailer->SMTPSecure = $options['smtp_secure'];
            }
            
            if (isset($options['smtp_auth']) && $options['smtp_auth']) {
                $phpmailer->SMTPAuth = true;
                $phpmailer->Username = isset($options['smtp_username']) ? $options['smtp_username'] : '';
                $phpmailer->Password = isset($options['smtp_password']) ? $options['smtp_password'] : '';
            }
        });
    }
    
    /**
     * Setup from headers
     */
    private function setup_from_headers() {
        $options = get_option('skylearn_billing_pro_email_options', array());
        
        if (isset($options['from_email']) && $options['from_email']) {
            add_filter('wp_mail_from', function() use ($options) {
                return $options['from_email'];
            });
        }
        
        if (isset($options['from_name']) && $options['from_name']) {
            add_filter('wp_mail_from_name', function() use ($options) {
                return $options['from_name'];
            });
        }
    }
    
    /**
     * Replace tokens in content
     */
    public function replace_tokens($content, $data) {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
    
    /**
     * Log email activity
     */
    public function log_email($type, $recipient, $subject, $success, $data = array()) {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'type' => $type,
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => $success ? 'sent' : 'failed',
            'data' => $data,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        );
        
        $this->save_email_log($log_entry);
    }
    
    /**
     * Save email log entry
     */
    private function save_email_log($entry) {
        $logs = get_option('skylearn_billing_pro_email_logs', array());
        $logs[] = $entry;
        
        // Keep only last 5000 entries
        if (count($logs) > 5000) {
            $logs = array_slice($logs, -5000);
        }
        
        update_option('skylearn_billing_pro_email_logs', $logs);
    }
    
    /**
     * Get email analytics
     */
    public function get_email_analytics($period = '30days') {
        $logs = get_option('skylearn_billing_pro_email_logs', array());
        
        $analytics = array(
            'total_emails' => 0,
            'successful_emails' => 0,
            'failed_emails' => 0,
            'emails_by_type' => array(),
            'emails_by_day' => array(),
            'delivery_rate' => 0
        );
        
        $cutoff_date = $this->get_cutoff_date($period);
        
        foreach ($logs as $log) {
            if (strtotime($log['timestamp']) < strtotime($cutoff_date)) {
                continue;
            }
            
            $analytics['total_emails']++;
            
            if ($log['status'] === 'sent') {
                $analytics['successful_emails']++;
            } else {
                $analytics['failed_emails']++;
            }
            
            // Count by type
            $type = $log['type'];
            if (!isset($analytics['emails_by_type'][$type])) {
                $analytics['emails_by_type'][$type] = 0;
            }
            $analytics['emails_by_type'][$type]++;
            
            // Count by day
            $day = date('Y-m-d', strtotime($log['timestamp']));
            if (!isset($analytics['emails_by_day'][$day])) {
                $analytics['emails_by_day'][$day] = 0;
            }
            $analytics['emails_by_day'][$day]++;
        }
        
        // Calculate delivery rate
        if ($analytics['total_emails'] > 0) {
            $analytics['delivery_rate'] = round(($analytics['successful_emails'] / $analytics['total_emails']) * 100, 2);
        }
        
        return $analytics;
    }
    
    /**
     * Get cutoff date for analytics period
     */
    private function get_cutoff_date($period) {
        switch ($period) {
            case '7days':
                return date('Y-m-d H:i:s', strtotime('-7 days'));
            case '30days':
                return date('Y-m-d H:i:s', strtotime('-30 days'));
            case '90days':
                return date('Y-m-d H:i:s', strtotime('-90 days'));
            case '1year':
                return date('Y-m-d H:i:s', strtotime('-1 year'));
            default:
                return date('Y-m-d H:i:s', strtotime('-30 days'));
        }
    }
    
    // Email trigger methods
    public function trigger_welcome_email($user_id, $password, $additional_data = array()) {
        $user = get_user_by('id', $user_id);
        if (!$user) return;
        
        $data = array_merge(array(
            'username' => $user->user_login,
            'password' => $password,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name ?: $user->user_login,
            'login_url' => wp_login_url()
        ), $additional_data);
        
        return $this->send_email('welcome', $user->user_email, $data);
    }
    
    public function trigger_payment_confirmation($order_data, $payment_data) {
        $data = array_merge($order_data, $payment_data);
        return $this->send_email('payment_confirmation', $data['customer_email'], $data);
    }
    
    public function trigger_payment_failed($order_data, $payment_data) {
        $data = array_merge($order_data, $payment_data);
        return $this->send_email('payment_failed', $data['customer_email'], $data);
    }
    
    public function trigger_refund_notification($order_data, $refund_data) {
        $data = array_merge($order_data, $refund_data);
        return $this->send_email('refund_notification', $data['customer_email'], $data);
    }
    
    public function trigger_course_enrollment($user_id, $course_data, $order_data = array()) {
        $user = get_user_by('id', $user_id);
        if (!$user) return;
        
        $data = array_merge(array(
            'user_id' => $user_id,
            'username' => $user->user_login,
            'user_email' => $user->user_email,
            'display_name' => $user->display_name ?: $user->user_login
        ), $course_data, $order_data);
        
        return $this->send_email('course_enrollment', $user->user_email, $data);
    }
    
    public function trigger_subscription_created($subscription_data, $customer_data) {
        $data = array_merge($subscription_data, $customer_data);
        return $this->send_email('subscription_created', $data['customer_email'], $data);
    }
    
    public function trigger_subscription_cancelled($subscription_data, $customer_data) {
        $data = array_merge($subscription_data, $customer_data);
        return $this->send_email('subscription_cancelled', $data['customer_email'], $data);
    }
    
    public function trigger_subscription_renewed($subscription_data, $customer_data) {
        $data = array_merge($subscription_data, $customer_data);
        return $this->send_email('subscription_renewed', $data['customer_email'], $data);
    }
    
    // AJAX handlers
    public function ajax_preview_email() {
        check_ajax_referer('skylearn_email_preview', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $type = sanitize_text_field($_POST['type']);
        $content = wp_kses_post($_POST['content']);
        $subject = sanitize_text_field($_POST['subject']);
        
        // Generate preview with sample data
        $sample_data = $this->get_sample_data($type);
        
        $preview_content = $this->replace_tokens($content, $sample_data);
        $preview_subject = $this->replace_tokens($subject, $sample_data);
        
        wp_send_json_success(array(
            'subject' => $preview_subject,
            'content' => $preview_content
        ));
    }
    
    public function ajax_send_test_email() {
        check_ajax_referer('skylearn_email_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $email = sanitize_email($_POST['email']);
        $type = sanitize_text_field($_POST['type']);
        
        if (!is_email($email)) {
            wp_send_json_error(__('Please enter a valid email address.', 'skylearn-billing-pro'));
        }
        
        $sample_data = $this->get_sample_data($type);
        $result = $this->send_email($type, $email, $sample_data);
        
        if ($result) {
            wp_send_json_success(__('Test email sent successfully!', 'skylearn-billing-pro'));
        } else {
            wp_send_json_error(__('Failed to send test email.', 'skylearn-billing-pro'));
        }
    }
    
    public function ajax_save_email_template() {
        check_ajax_referer('skylearn_email_save', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $type = sanitize_text_field($_POST['type']);
        $template_data = array(
            'subject' => sanitize_text_field($_POST['subject']),
            'content' => wp_kses_post($_POST['content']),
            'enabled' => isset($_POST['enabled']) ? (bool) $_POST['enabled'] : true
        );
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        
        $result = $this->save_email_template($type, $template_data, $language);
        
        if ($result) {
            wp_send_json_success(__('Email template saved successfully!', 'skylearn-billing-pro'));
        } else {
            wp_send_json_error(__('Failed to save email template.', 'skylearn-billing-pro'));
        }
    }
    
    public function ajax_get_email_template() {
        check_ajax_referer('skylearn_email_get', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $type = sanitize_text_field($_POST['type']);
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        
        $template = $this->get_email_template($type, $language);
        
        wp_send_json_success($template);
    }
    
    public function ajax_get_email_analytics() {
        check_ajax_referer('skylearn_email_analytics', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $period = sanitize_text_field($_POST['period'] ?? '30days');
        $analytics = $this->get_email_analytics($period);
        
        wp_send_json_success($analytics);
    }
    
    /**
     * Get sample data for email previews
     */
    private function get_sample_data($type) {
        $base_data = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
            'current_date' => current_time('F j, Y'),
            'current_time' => current_time('g:i A'),
            'username' => 'johndoe',
            'user_email' => 'john@example.com',
            'display_name' => 'John Doe',
            'login_url' => wp_login_url()
        );
        
        $type_specific_data = array(
            'welcome' => array(
                'password' => 'sample-password-123'
            ),
            'order_confirmation' => array(
                'order_id' => 'ORD-12345',
                'order_total' => '$99.00',
                'product_name' => 'Sample Course',
                'customer_name' => 'John Doe'
            ),
            'invoice' => array(
                'invoice_id' => 'INV-12345',
                'invoice_total' => '$99.00',
                'due_date' => date('F j, Y', strtotime('+30 days'))
            ),
            'payment_confirmation' => array(
                'order_id' => 'ORD-12345',
                'amount' => '$99.00',
                'payment_method' => 'Credit Card',
                'transaction_id' => 'TXN-67890'
            ),
            'course_enrollment' => array(
                'course_title' => 'Advanced WordPress Development',
                'course_url' => home_url('/courses/advanced-wordpress/'),
                'instructor_name' => 'Jane Smith'
            ),
            'subscription_created' => array(
                'subscription_id' => 'SUB-12345',
                'plan_name' => 'Pro Monthly',
                'amount' => '$29.00',
                'billing_cycle' => 'monthly'
            )
        );
        
        return array_merge($base_data, $type_specific_data[$type] ?? array());
    }
    
    /**
     * Log wp_mail calls for analytics
     */
    public function log_wp_mail($atts) {
        // Only log if this is a Skylearn Billing Pro email
        if (strpos($atts['subject'], get_bloginfo('name')) !== false) {
            $this->log_email(
                'general',
                is_array($atts['to']) ? implode(', ', $atts['to']) : $atts['to'],
                $atts['subject'],
                true, // We log before sending, so assume success
                array('headers' => $atts['headers'] ?? '')
            );
        }
    }
    
    // Template content methods (simplified for brevity)
    private function get_welcome_template_content() {
        return '<h2>Welcome to {{site_name}}!</h2>
<p>Hello {{display_name}},</p>
<p>Welcome to {{site_name}}! Your account has been created successfully.</p>
<div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
    <h3>Your Login Credentials:</h3>
    <p><strong>Username:</strong> {{username}}</p>
    <p><strong>Password:</strong> {{password}}</p>
    <p><a href="{{login_url}}" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Login Now</a></p>
</div>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_order_confirmation_template_content() {
        return '<h2>Order Confirmation</h2>
<p>Hello {{customer_name}},</p>
<p>Thank you for your order! Here are the details:</p>
<div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
    <h3>Order Details:</h3>
    <p><strong>Order ID:</strong> {{order_id}}</p>
    <p><strong>Product:</strong> {{product_name}}</p>
    <p><strong>Total:</strong> {{order_total}}</p>
</div>
<p>You will receive access information shortly.</p>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_invoice_template_content() {
        return '<h2>Invoice {{invoice_id}}</h2>
<p>Hello,</p>
<p>Please find your invoice details below:</p>
<div style="background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
    <h3>Invoice Details:</h3>
    <p><strong>Invoice ID:</strong> {{invoice_id}}</p>
    <p><strong>Amount:</strong> {{invoice_total}}</p>
    <p><strong>Due Date:</strong> {{due_date}}</p>
</div>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_payment_confirmation_template_content() {
        return '<h2>Payment Confirmed</h2>
<p>Hello,</p>
<p>Your payment has been successfully processed:</p>
<div style="background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745;">
    <h3>Payment Details:</h3>
    <p><strong>Order ID:</strong> {{order_id}}</p>
    <p><strong>Amount:</strong> {{amount}}</p>
    <p><strong>Payment Method:</strong> {{payment_method}}</p>
    <p><strong>Transaction ID:</strong> {{transaction_id}}</p>
</div>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_payment_failed_template_content() {
        return '<h2>Payment Failed</h2>
<p>Hello,</p>
<p>Unfortunately, your payment could not be processed:</p>
<div style="background: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #dc3545;">
    <h3>Order Details:</h3>
    <p><strong>Order ID:</strong> {{order_id}}</p>
    <p><strong>Amount:</strong> {{amount}}</p>
</div>
<p>Please contact us or try again with a different payment method.</p>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_refund_notification_template_content() {
        return '<h2>Refund Processed</h2>
<p>Hello,</p>
<p>Your refund has been processed successfully:</p>
<div style="background: #d1ecf1; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #17a2b8;">
    <h3>Refund Details:</h3>
    <p><strong>Order ID:</strong> {{order_id}}</p>
    <p><strong>Refund Amount:</strong> {{refund_amount}}</p>
    <p><strong>Refund Reason:</strong> {{refund_reason}}</p>
</div>
<p>The refund will appear in your account within 3-5 business days.</p>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_course_enrollment_template_content() {
        return '<h2>Course Enrollment Confirmed</h2>
<p>Hello {{display_name}},</p>
<p>Congratulations! You have been successfully enrolled in:</p>
<div style="background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745;">
    <h3>{{course_title}}</h3>
    <p><strong>Instructor:</strong> {{instructor_name}}</p>
    <p><a href="{{course_url}}" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Start Learning</a></p>
</div>
<p>You can access your course anytime from your dashboard.</p>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_subscription_created_template_content() {
        return '<h2>Subscription Confirmed</h2>
<p>Hello,</p>
<p>Your subscription has been created successfully:</p>
<div style="background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745;">
    <h3>Subscription Details:</h3>
    <p><strong>Subscription ID:</strong> {{subscription_id}}</p>
    <p><strong>Plan:</strong> {{plan_name}}</p>
    <p><strong>Amount:</strong> {{amount}} / {{billing_cycle}}</p>
</div>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    /**
     * Get sample data for email previews
     */
    private function get_sample_data($type) {
        $base_data = array(
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
            'current_date' => current_time('F j, Y'),
            'current_time' => current_time('g:i A'),
            'username' => 'johndoe',
            'user_email' => 'john@example.com',
            'display_name' => 'John Doe',
            'login_url' => wp_login_url()
        );
        
        $type_specific_data = array(
            'welcome' => array(
                'password' => 'sample-password-123'
            ),
            'order_confirmation' => array(
                'order_id' => 'ORD-12345',
                'order_total' => '$99.00',
                'product_name' => 'Sample Course',
                'customer_name' => 'John Doe'
            ),
            'invoice' => array(
                'invoice_id' => 'INV-12345',
                'invoice_total' => '$99.00',
                'due_date' => date('F j, Y', strtotime('+30 days'))
            ),
            'payment_confirmation' => array(
                'order_id' => 'ORD-12345',
                'amount' => '$99.00',
                'payment_method' => 'Credit Card',
                'transaction_id' => 'TXN-67890'
            ),
            'course_enrollment' => array(
                'course_title' => 'Advanced WordPress Development',
                'course_url' => home_url('/courses/advanced-wordpress/'),
                'instructor_name' => 'Jane Smith'
            ),
            'subscription_created' => array(
                'subscription_id' => 'SUB-12345',
                'plan_name' => 'Pro Monthly',
                'amount' => '$29.00',
                'billing_cycle' => 'monthly'
            )
        );
        
        return array_merge($base_data, $type_specific_data[$type] ?? array());
    }
    
    private function get_subscription_cancelled_template_content() {
        return '<h2>Subscription Cancelled</h2>
<p>Hello,</p>
<p>Your subscription has been cancelled:</p>
<div style="background: #f8d7da; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #dc3545;">
    <h3>Subscription Details:</h3>
    <p><strong>Subscription ID:</strong> {{subscription_id}}</p>
    <p><strong>Plan:</strong> {{plan_name}}</p>
    <p><strong>Cancelled Date:</strong> {{cancelled_date}}</p>
</div>
<p>You will retain access until your current billing period ends.</p>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
    
    private function get_subscription_renewed_template_content() {
        return '<h2>Subscription Renewed</h2>
<p>Hello,</p>
<p>Your subscription has been successfully renewed:</p>
<div style="background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px; border-left: 4px solid #28a745;">
    <h3>Subscription Details:</h3>
    <p><strong>Subscription ID:</strong> {{subscription_id}}</p>
    <p><strong>Plan:</strong> {{plan_name}}</p>
    <p><strong>Next Billing Date:</strong> {{next_billing_date}}</p>
    <p><strong>Amount:</strong> {{amount}}</p>
</div>
<p>Best regards,<br>The {{site_name}} Team</p>';
    }
}

/**
 * Get the Email Management instance
 *
 * @return SkyLearn_Billing_Pro_Email
 */
function skylearn_billing_pro_email() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Email();
    }
    
    return $instance;
}