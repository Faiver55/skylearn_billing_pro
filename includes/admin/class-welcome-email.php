<?php
/**
 * Welcome Email Admin class for Skylearn Billing Pro
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
 * Welcome Email Admin class
 */
class SkyLearn_Billing_Pro_Welcome_Email_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_skylearn_preview_welcome_email', array($this, 'ajax_preview_email'));
        add_action('wp_ajax_skylearn_send_test_email', array($this, 'ajax_send_test_email'));
    }
    
    /**
     * Initialize admin settings
     */
    public function admin_init() {
        // Register email settings
        register_setting('skylearn_billing_pro_email', 'skylearn_billing_pro_options', array($this, 'sanitize_email_options'));
        
        // Add email settings section
        add_settings_section(
            'skylearn_billing_pro_welcome_email_section',
            __('Welcome Email Settings', 'skylearn-billing-pro'),
            array($this, 'email_section_callback'),
            'skylearn_billing_pro_email'
        );
        
        // Add email settings fields
        add_settings_field(
            'welcome_email_enabled',
            __('Enable Welcome Email', 'skylearn-billing-pro'),
            array($this, 'welcome_email_enabled_callback'),
            'skylearn_billing_pro_email',
            'skylearn_billing_pro_welcome_email_section'
        );
        
        add_settings_field(
            'welcome_email_subject',
            __('Email Subject', 'skylearn-billing-pro'),
            array($this, 'welcome_email_subject_callback'),
            'skylearn_billing_pro_email',
            'skylearn_billing_pro_welcome_email_section'
        );
        
        add_settings_field(
            'welcome_email_template',
            __('Email Template', 'skylearn-billing-pro'),
            array($this, 'welcome_email_template_callback'),
            'skylearn_billing_pro_email',
            'skylearn_billing_pro_welcome_email_section'
        );
        
        add_settings_field(
            'welcome_email_format',
            __('Email Format', 'skylearn-billing-pro'),
            array($this, 'welcome_email_format_callback'),
            'skylearn_billing_pro_email',
            'skylearn_billing_pro_welcome_email_section'
        );
    }
    
    /**
     * Sanitize email options
     */
    public function sanitize_email_options($input) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (isset($input['email_settings'])) {
            $options['email_settings']['welcome_email_enabled'] = isset($input['email_settings']['welcome_email_enabled']) ? true : false;
            $options['email_settings']['welcome_email_subject'] = sanitize_text_field($input['email_settings']['welcome_email_subject']);
            $options['email_settings']['welcome_email_template'] = wp_kses_post($input['email_settings']['welcome_email_template']);
            $options['email_settings']['welcome_email_format'] = sanitize_text_field($input['email_settings']['welcome_email_format']);
        }
        
        return $options;
    }
    
    /**
     * Email section callback
     */
    public function email_section_callback() {
        echo '<p>' . esc_html__('Configure welcome email settings for new user accounts.', 'skylearn-billing-pro') . '</p>';
        echo '<div class="skylearn-email-tokens-info">';
        echo '<h4>' . esc_html__('Available Tokens:', 'skylearn-billing-pro') . '</h4>';
        echo '<ul class="skylearn-tokens-list">';
        echo '<li><code>{{username}}</code> - ' . esc_html__('User login name', 'skylearn-billing-pro') . '</li>';
        echo '<li><code>{{password}}</code> - ' . esc_html__('User password (only for new accounts)', 'skylearn-billing-pro') . '</li>';
        echo '<li><code>{{user_email}}</code> - ' . esc_html__('User email address', 'skylearn-billing-pro') . '</li>';
        echo '<li><code>{{display_name}}</code> - ' . esc_html__('User display name', 'skylearn-billing-pro') . '</li>';
        echo '<li><code>{{site_name}}</code> - ' . esc_html__('Website name', 'skylearn-billing-pro') . '</li>';
        echo '<li><code>{{login_url}}</code> - ' . esc_html__('Login page URL', 'skylearn-billing-pro') . '</li>';
        echo '<li><code>{{course_title}}</code> - ' . esc_html__('Enrolled course title (if available)', 'skylearn-billing-pro') . '</li>';
        echo '<li><code>{{company_name}}</code> - ' . esc_html__('Company name from settings', 'skylearn-billing-pro') . '</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Welcome email enabled field callback
     */
    public function welcome_email_enabled_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['email_settings']['welcome_email_enabled']) ? $options['email_settings']['welcome_email_enabled'] : true;
        
        echo '<label>';
        echo '<input type="checkbox" name="skylearn_billing_pro_options[email_settings][welcome_email_enabled]" value="1"' . checked($value, true, false) . ' />';
        echo ' ' . esc_html__('Send welcome email to new users', 'skylearn-billing-pro');
        echo '</label>';
        echo '<p class="description">' . esc_html__('When enabled, new users will receive a welcome email with their login credentials.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Welcome email subject field callback
     */
    public function welcome_email_subject_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['email_settings']['welcome_email_subject']) ? $options['email_settings']['welcome_email_subject'] : __('Welcome to {{site_name}}', 'skylearn-billing-pro');
        
        echo '<input type="text" name="skylearn_billing_pro_options[email_settings][welcome_email_subject]" value="' . esc_attr($value) . '" class="large-text" />';
        echo '<p class="description">' . esc_html__('Use tokens like {{site_name}}, {{username}}, etc. to personalize the subject.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Welcome email template field callback
     */
    public function welcome_email_template_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['email_settings']['welcome_email_template']) ? $options['email_settings']['welcome_email_template'] : $this->get_default_template();
        
        echo '<div class="skylearn-email-editor-container">';
        echo '<div class="skylearn-email-editor-tabs">';
        echo '<button type="button" class="skylearn-tab-button active" data-tab="editor">' . esc_html__('Edit Template', 'skylearn-billing-pro') . '</button>';
        echo '<button type="button" class="skylearn-tab-button" data-tab="preview">' . esc_html__('Preview', 'skylearn-billing-pro') . '</button>';
        echo '</div>';
        
        echo '<div class="skylearn-email-editor-content">';
        echo '<div class="skylearn-tab-content active" id="editor-tab">';
        wp_editor($value, 'welcome_email_template', array(
            'textarea_name' => 'skylearn_billing_pro_options[email_settings][welcome_email_template]',
            'media_buttons' => false,
            'textarea_rows' => 15,
            'teeny' => false,
            'tinymce' => array(
                'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,bullist,numlist,link,unlink,undo,redo',
                'toolbar2' => 'forecolor,backcolor,alignleft,aligncenter,alignright,alignjustify,outdent,indent,removeformat,code'
            )
        ));
        echo '</div>';
        
        echo '<div class="skylearn-tab-content" id="preview-tab">';
        echo '<div class="skylearn-email-preview-container">';
        echo '<div class="skylearn-email-preview-header">';
        echo '<button type="button" class="button skylearn-refresh-preview">' . esc_html__('Refresh Preview', 'skylearn-billing-pro') . '</button>';
        echo '<button type="button" class="button skylearn-send-test-email">' . esc_html__('Send Test Email', 'skylearn-billing-pro') . '</button>';
        echo '</div>';
        echo '<div id="skylearn-email-preview" class="skylearn-email-preview"></div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        echo '<p class="description">' . esc_html__('Design your welcome email template using the editor above. Use the available tokens to personalize the content.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Welcome email format field callback
     */
    public function welcome_email_format_callback() {
        $options = get_option('skylearn_billing_pro_options', array());
        $value = isset($options['email_settings']['welcome_email_format']) ? $options['email_settings']['welcome_email_format'] : 'html';
        
        echo '<label>';
        echo '<input type="radio" name="skylearn_billing_pro_options[email_settings][welcome_email_format]" value="html"' . checked($value, 'html', false) . ' />';
        echo ' ' . esc_html__('HTML', 'skylearn-billing-pro');
        echo '</label><br>';
        
        echo '<label>';
        echo '<input type="radio" name="skylearn_billing_pro_options[email_settings][welcome_email_format]" value="text"' . checked($value, 'text', false) . ' />';
        echo ' ' . esc_html__('Plain Text', 'skylearn-billing-pro');
        echo '</label>';
        
        echo '<p class="description">' . esc_html__('Choose the format for welcome emails. HTML allows rich formatting, while plain text is more compatible.', 'skylearn-billing-pro') . '</p>';
    }
    
    /**
     * Get default email template
     */
    private function get_default_template() {
        return self::get_default_email_template();
    }
    
    /**
     * Get default email template (static)
     */
    public static function get_default_email_template() {
        return sprintf(__('
<h2>Welcome to {{site_name}}!</h2>

<p>Hello {{display_name}},</p>

<p>Welcome to {{site_name}}! Your account has been created successfully and you now have access to our platform.</p>

<div style="background-color: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 5px;">
    <h3>Your Login Credentials:</h3>
    <p><strong>Username:</strong> {{username}}</p>
    <p><strong>Password:</strong> {{password}}</p>
    <p><strong>Login URL:</strong> <a href="{{login_url}}">{{login_url}}</a></p>
</div>

<p>You have been enrolled in: <strong>{{course_title}}</strong></p>

<p>We recommend that you log in and change your password to something more memorable.</p>

<p>If you have any questions or need assistance, please don\'t hesitate to contact us.</p>

<p>Best regards,<br>
The {{company_name}} Team</p>
', 'skylearn-billing-pro'));
    }
    
    /**
     * Ajax preview email
     */
    public function ajax_preview_email() {
        check_ajax_referer('skylearn_email_preview', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'skylearn-billing-pro'));
        }
        
        $template = wp_kses_post($_POST['template']);
        $subject = sanitize_text_field($_POST['subject']);
        $format = sanitize_text_field($_POST['format']);
        
        // Generate preview with sample data
        $preview_data = array(
            'username' => 'johndoe',
            'password' => 'sample-password',
            'user_email' => 'john@example.com',
            'display_name' => 'John Doe',
            'site_name' => get_bloginfo('name'),
            'login_url' => wp_login_url(),
            'course_title' => 'Sample Course Name',
            'company_name' => 'Your Company'
        );
        
        $preview_content = $this->replace_tokens($template, $preview_data);
        $preview_subject = $this->replace_tokens($subject, $preview_data);
        
        if ($format === 'text') {
            $preview_content = wp_strip_all_tags($preview_content);
        }
        
        wp_send_json_success(array(
            'subject' => $preview_subject,
            'content' => $preview_content,
            'format' => $format
        ));
    }
    
    /**
     * Ajax send test email
     */
    public function ajax_send_test_email() {
        check_ajax_referer('skylearn_email_test', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'skylearn-billing-pro'));
        }
        
        $email = sanitize_email($_POST['email']);
        $template = wp_kses_post($_POST['template']);
        $subject = sanitize_text_field($_POST['subject']);
        $format = sanitize_text_field($_POST['format']);
        
        if (!is_email($email)) {
            wp_send_json_error(esc_html__('Please enter a valid email address.', 'skylearn-billing-pro'));
        }
        
        // Generate test email with sample data
        $test_data = array(
            'username' => 'testuser',
            'password' => 'test-password-123',
            'user_email' => $email,
            'display_name' => 'Test User',
            'site_name' => get_bloginfo('name'),
            'login_url' => wp_login_url(),
            'course_title' => 'Test Course Name',
            'company_name' => 'Your Company'
        );
        
        $email_content = $this->replace_tokens($template, $test_data);
        $email_subject = $this->replace_tokens($subject, $test_data);
        
        // Set content type
        add_filter('wp_mail_content_type', function() use ($format) {
            return $format === 'html' ? 'text/html' : 'text/plain';
        });
        
        if ($format === 'text') {
            $email_content = wp_strip_all_tags($email_content);
        }
        
        $result = wp_mail($email, $email_subject, $email_content);
        
        // Remove filter
        remove_all_filters('wp_mail_content_type');
        
        if ($result) {
            wp_send_json_success(esc_html__('Test email sent successfully!', 'skylearn-billing-pro'));
        } else {
            wp_send_json_error(esc_html__('Failed to send test email. Please check your email configuration.', 'skylearn-billing-pro'));
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
     * Send welcome email to user
     */
    public function send_welcome_email($user_id, $password = '', $additional_data = array()) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        // Check if welcome emails are enabled
        $enabled = isset($options['email_settings']['welcome_email_enabled']) ? $options['email_settings']['welcome_email_enabled'] : true;
        if (!$enabled) {
            return false;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }
        
        // Get email settings
        $subject = isset($options['email_settings']['welcome_email_subject']) ? $options['email_settings']['welcome_email_subject'] : __('Welcome to {{site_name}}', 'skylearn-billing-pro');
        $template = isset($options['email_settings']['welcome_email_template']) ? $options['email_settings']['welcome_email_template'] : $this->get_default_template();
        $format = isset($options['email_settings']['welcome_email_format']) ? $options['email_settings']['welcome_email_format'] : 'html';
        
        // Prepare email data
        $email_data = array(
            'username' => $user->user_login,
            'password' => $password ? $password : esc_html__('[Password Reset Required]', 'skylearn-billing-pro'),
            'user_email' => $user->user_email,
            'display_name' => $user->display_name ? $user->display_name : $user->user_login,
            'site_name' => get_bloginfo('name'),
            'login_url' => wp_login_url(),
            'course_title' => isset($additional_data['course_title']) ? $additional_data['course_title'] : '',
            'company_name' => isset($options['general_settings']['company_name']) ? $options['general_settings']['company_name'] : get_bloginfo('name')
        );
        
        // Replace tokens
        $email_subject = $this->replace_tokens($subject, $email_data);
        $email_content = $this->replace_tokens($template, $email_data);
        
        // Set content type
        add_filter('wp_mail_content_type', function() use ($format) {
            return $format === 'html' ? 'text/html' : 'text/plain';
        });
        
        if ($format === 'text') {
            $email_content = wp_strip_all_tags($email_content);
        }
        
        $result = wp_mail($user->user_email, $email_subject, $email_content);
        
        // Remove filter
        remove_all_filters('wp_mail_content_type');
        
        // Log email send attempt
        $this->log_email_activity($user_id, $result, $email_data);
        
        return $result;
    }
    
    /**
     * Log email activity for analytics
     */
    private function log_email_activity($user_id, $success, $email_data) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['email_log'])) {
            $options['email_log'] = array();
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'user_id' => $user_id,
            'email_type' => 'welcome',
            'recipient' => $email_data['user_email'],
            'status' => $success ? 'sent' : 'failed',
            'subject' => $email_data['site_name'] ? sprintf(__('Welcome to %s', 'skylearn-billing-pro'), $email_data['site_name']) : 'Welcome Email'
        );
        
        $options['email_log'][] = $log_entry;
        
        // Keep only last 1000 entries
        if (count($options['email_log']) > 1000) {
            $options['email_log'] = array_slice($options['email_log'], -1000);
        }
        
        update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Get email statistics
     */
    public function get_email_stats() {
        $options = get_option('skylearn_billing_pro_options', array());
        $email_log = isset($options['email_log']) ? $options['email_log'] : array();
        
        $stats = array(
            'total_emails' => 0,
            'welcome_emails' => 0,
            'successful_emails' => 0,
            'failed_emails' => 0,
            'emails_today' => 0,
            'emails_this_week' => 0,
            'emails_this_month' => 0
        );
        
        $today = date('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = date('Y-m-01');
        
        foreach ($email_log as $entry) {
            $stats['total_emails']++;
            
            if ($entry['email_type'] === 'welcome') {
                $stats['welcome_emails']++;
            }
            
            if ($entry['status'] === 'sent') {
                $stats['successful_emails']++;
            } else {
                $stats['failed_emails']++;
            }
            
            // Count by date
            $entry_date = date('Y-m-d', strtotime($entry['timestamp']));
            
            if ($entry_date === $today) {
                $stats['emails_today']++;
            }
            
            if ($entry_date >= $week_start) {
                $stats['emails_this_week']++;
            }
            
            if ($entry_date >= $month_start) {
                $stats['emails_this_month']++;
            }
        }
        
        return $stats;
    }
}

/**
 * Get the Welcome Email Admin instance
 *
 * @return SkyLearn_Billing_Pro_Welcome_Email_Admin
 */
function skylearn_billing_pro_welcome_email_admin() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Welcome_Email_Admin();
    }
    
    return $instance;
}