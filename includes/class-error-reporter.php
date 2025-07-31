<?php
/**
 * Error Reporting and Feedback System
 * 
 * Handles error reporting, usage tracking, and feedback collection
 * for Skylearn Billing Pro plugin
 *
 * @package SkyLearn_Billing_Pro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skylearn Billing Error Reporter
 */
class Skylearn_Billing_Error_Reporter {
    
    /**
     * Initialize error reporting
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Error reporting hooks
        add_action('wp_ajax_skylearn_billing_report_error', array($this, 'handle_error_report'));
        add_action('wp_ajax_nopriv_skylearn_billing_report_error', array($this, 'handle_error_report'));
        
        // Feedback hooks
        add_action('wp_ajax_skylearn_billing_feedback', array($this, 'handle_feedback'));
        add_action('wp_ajax_nopriv_skylearn_billing_feedback', array($this, 'handle_feedback'));
        
        // Usage tracking hooks (privacy-compliant)
        add_action('skylearn_billing_track_usage', array($this, 'track_usage'));
        add_action('init', array($this, 'schedule_usage_tracking'));
        
        // Admin notices for feedback
        add_action('admin_notices', array($this, 'feedback_admin_notice'));
        
        // Error logging
        add_action('skylearn_billing_log_error', array($this, 'log_error'), 10, 3);
    }
    
    /**
     * Handle error reports from users
     */
    public function handle_error_report() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_billing_error_report')) {
            wp_die('Security check failed');
        }
        
        $error_data = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'site_url' => site_url(),
            'wp_version' => get_bloginfo('version'),
            'plugin_version' => SKYLEARN_BILLING_VERSION,
            'php_version' => PHP_VERSION,
            'error_message' => sanitize_textarea_field($_POST['error_message']),
            'error_context' => sanitize_textarea_field($_POST['error_context']),
            'user_email' => sanitize_email($_POST['user_email']),
            'steps_to_reproduce' => sanitize_textarea_field($_POST['steps_to_reproduce']),
            'expected_behavior' => sanitize_textarea_field($_POST['expected_behavior']),
            'actual_behavior' => sanitize_textarea_field($_POST['actual_behavior']),
            'browser_info' => sanitize_text_field($_POST['browser_info']),
            'additional_plugins' => $this->get_active_plugins(),
            'theme_info' => $this->get_theme_info(),
            'server_info' => $this->get_server_info()
        );
        
        // Save error report locally
        $this->save_error_report($error_data);
        
        // Send to remote server if enabled
        if (get_option('skylearn_billing_remote_error_reporting', false)) {
            $this->send_error_report($error_data);
        }
        
        wp_send_json_success(array(
            'message' => __('Error report submitted successfully. Thank you for helping us improve!', 'skylearn-billing')
        ));
    }
    
    /**
     * Handle user feedback
     */
    public function handle_feedback() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'skylearn_billing_feedback')) {
            wp_die('Security check failed');
        }
        
        $feedback_data = array(
            'timestamp' => current_time('mysql'),
            'user_id' => get_current_user_id(),
            'site_url' => site_url(),
            'plugin_version' => SKYLEARN_BILLING_VERSION,
            'feedback_type' => sanitize_text_field($_POST['feedback_type']),
            'rating' => intval($_POST['rating']),
            'message' => sanitize_textarea_field($_POST['message']),
            'user_email' => sanitize_email($_POST['user_email']),
            'feature_request' => sanitize_textarea_field($_POST['feature_request']),
            'user_role' => $this->get_user_role()
        );
        
        // Save feedback locally
        $this->save_feedback($feedback_data);
        
        // Send to remote server if enabled
        if (get_option('skylearn_billing_remote_feedback_collection', false)) {
            $this->send_feedback($feedback_data);
        }
        
        wp_send_json_success(array(
            'message' => __('Feedback submitted successfully. Thank you!', 'skylearn-billing')
        ));
    }
    
    /**
     * Track usage statistics (privacy-compliant)
     */
    public function track_usage() {
        // Only track if user has opted in
        if (!get_option('skylearn_billing_usage_tracking_enabled', false)) {
            return;
        }
        
        $usage_data = array(
            'timestamp' => current_time('mysql'),
            'site_hash' => md5(site_url()), // Anonymous site identifier
            'plugin_version' => SKYLEARN_BILLING_VERSION,
            'wp_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'active_gateways' => $this->get_active_gateways(),
            'total_transactions' => $this->get_transaction_count(),
            'total_customers' => $this->get_customer_count(),
            'features_used' => $this->get_used_features(),
            'locale' => get_locale(),
            'timezone' => wp_timezone_string()
        );
        
        // Save locally
        update_option('skylearn_billing_last_usage_data', $usage_data);
        
        // Send to analytics server if enabled
        if (get_option('skylearn_billing_remote_usage_tracking', false)) {
            $this->send_usage_data($usage_data);
        }
    }
    
    /**
     * Schedule usage tracking
     */
    public function schedule_usage_tracking() {
        if (!wp_next_scheduled('skylearn_billing_track_usage')) {
            wp_schedule_event(time(), 'weekly', 'skylearn_billing_track_usage');
        }
    }
    
    /**
     * Show feedback admin notice
     */
    public function feedback_admin_notice() {
        // Only show to administrators
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Don't show if dismissed
        if (get_option('skylearn_billing_feedback_notice_dismissed', false)) {
            return;
        }
        
        // Only show after plugin has been active for a week
        $installed_time = get_option('skylearn_billing_installed_time', time());
        if ((time() - $installed_time) < WEEK_IN_SECONDS) {
            return;
        }
        
        ?>
        <div class="notice notice-info is-dismissible" data-notice="skylearn-billing-feedback">
            <h3><?php _e('How is Skylearn Billing Pro working for you?', 'skylearn-billing'); ?></h3>
            <p><?php _e('We\'d love to hear your feedback! Your input helps us improve the plugin for everyone.', 'skylearn-billing'); ?></p>
            <p>
                <a href="#" class="button button-primary skylearn-billing-feedback-btn" data-type="positive">
                    <?php _e('Leave Feedback', 'skylearn-billing'); ?>
                </a>
                <a href="#" class="button skylearn-billing-feedback-btn" data-type="suggestion">
                    <?php _e('Suggest a Feature', 'skylearn-billing'); ?>
                </a>
                <a href="#" class="button skylearn-billing-dismiss-notice">
                    <?php _e('Maybe Later', 'skylearn-billing'); ?>
                </a>
            </p>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            $('.skylearn-billing-dismiss-notice').on('click', function(e) {
                e.preventDefault();
                $.post(ajaxurl, {
                    action: 'skylearn_billing_dismiss_notice',
                    notice: 'feedback',
                    nonce: '<?php echo wp_create_nonce('skylearn_billing_dismiss_notice'); ?>'
                });
                $(this).closest('.notice').fadeOut();
            });
            
            $('.skylearn-billing-feedback-btn').on('click', function(e) {
                e.preventDefault();
                var type = $(this).data('type');
                // Open feedback modal or redirect to feedback form
                window.open('https://skyian.com/skylearn-billing/feedback/?type=' + type, '_blank');
                $(this).closest('.notice').fadeOut();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Save error report locally
     */
    private function save_error_report($error_data) {
        $reports = get_option('skylearn_billing_error_reports', array());
        
        // Keep only last 50 reports to avoid database bloat
        if (count($reports) >= 50) {
            array_shift($reports);
        }
        
        $reports[] = $error_data;
        update_option('skylearn_billing_error_reports', $reports);
    }
    
    /**
     * Save feedback locally
     */
    private function save_feedback($feedback_data) {
        $feedback = get_option('skylearn_billing_user_feedback', array());
        
        // Keep only last 100 feedback entries
        if (count($feedback) >= 100) {
            array_shift($feedback);
        }
        
        $feedback[] = $feedback_data;
        update_option('skylearn_billing_user_feedback', $feedback);
    }
    
    /**
     * Send error report to remote server
     */
    private function send_error_report($error_data) {
        $endpoint = 'https://analytics.skyian.com/skylearn-billing/error-report';
        
        wp_remote_post($endpoint, array(
            'body' => json_encode($error_data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30,
            'blocking' => false // Non-blocking request
        ));
    }
    
    /**
     * Send feedback to remote server
     */
    private function send_feedback($feedback_data) {
        $endpoint = 'https://analytics.skyian.com/skylearn-billing/feedback';
        
        wp_remote_post($endpoint, array(
            'body' => json_encode($feedback_data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30,
            'blocking' => false // Non-blocking request
        ));
    }
    
    /**
     * Send usage data to analytics server
     */
    private function send_usage_data($usage_data) {
        $endpoint = 'https://analytics.skyian.com/skylearn-billing/usage';
        
        wp_remote_post($endpoint, array(
            'body' => json_encode($usage_data),
            'headers' => array(
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30,
            'blocking' => false // Non-blocking request
        ));
    }
    
    /**
     * Get active plugins list
     */
    private function get_active_plugins() {
        $active_plugins = get_option('active_plugins', array());
        $plugin_names = array();
        
        foreach ($active_plugins as $plugin) {
            $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
            $plugin_names[] = $plugin_data['Name'] . ' v' . $plugin_data['Version'];
        }
        
        return $plugin_names;
    }
    
    /**
     * Get theme information
     */
    private function get_theme_info() {
        $theme = wp_get_theme();
        return array(
            'name' => $theme->get('Name'),
            'version' => $theme->get('Version'),
            'author' => $theme->get('Author')
        );
    }
    
    /**
     * Get server information
     */
    private function get_server_info() {
        return array(
            'php_version' => PHP_VERSION,
            'mysql_version' => $GLOBALS['wpdb']->db_version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time')
        );
    }
    
    /**
     * Get user role
     */
    private function get_user_role() {
        $user = wp_get_current_user();
        return !empty($user->roles) ? $user->roles[0] : 'guest';
    }
    
    /**
     * Get active payment gateways
     */
    private function get_active_gateways() {
        // This would be implemented based on actual gateway configuration
        return array('stripe', 'lemonsqueezy'); // Placeholder
    }
    
    /**
     * Get transaction count (anonymized)
     */
    private function get_transaction_count() {
        // This would query actual transaction data
        return 0; // Placeholder
    }
    
    /**
     * Get customer count (anonymized)
     */
    private function get_customer_count() {
        // This would query actual customer data
        return 0; // Placeholder
    }
    
    /**
     * Get features being used
     */
    private function get_used_features() {
        return array('subscriptions', 'one-time-payments', 'customer-portal'); // Placeholder
    }
    
    /**
     * Log errors for debugging
     */
    public function log_error($message, $context = array(), $level = 'error') {
        if (!get_option('skylearn_billing_error_logging_enabled', true)) {
            return;
        }
        
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'user_id' => get_current_user_id(),
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'backtrace' => wp_debug_backtrace_summary()
        );
        
        // Save to database
        $logs = get_option('skylearn_billing_error_logs', array());
        
        // Keep only last 500 log entries
        if (count($logs) >= 500) {
            array_shift($logs);
        }
        
        $logs[] = $log_entry;
        update_option('skylearn_billing_error_logs', $logs);
        
        // Also log to WordPress error log if WP_DEBUG is enabled
        if (WP_DEBUG && WP_DEBUG_LOG) {
            error_log('Skylearn Billing: ' . $message . ' | Context: ' . wp_json_encode($context));
        }
    }
}

// Initialize error reporter
new Skylearn_Billing_Error_Reporter();