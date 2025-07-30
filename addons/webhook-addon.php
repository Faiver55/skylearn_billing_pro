<?php
/**
 * Webhook Addon for Skylearn Billing Pro
 *
 * Addon ID: webhook-addon
 * Addon Name: Webhook Addon
 * Description: Advanced webhook management and integrations
 * Version: 1.0.0
 * Author: Skylearn Team
 * Type: free
 * Required Tier: free
 * Category: integrations
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
 * Webhook Addon Class
 */
class SkyLearn_Billing_Pro_Webhook_Addon {
    
    /**
     * Addon ID
     */
    const ADDON_ID = 'webhook-addon';
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Webhook_Addon
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Webhook_Addon
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        
        // Addon activation hook
        add_action('skylearn_billing_addon_activated', array($this, 'on_addon_activated'));
        add_action('skylearn_billing_addon_deactivated', array($this, 'on_addon_deactivated'));
    }
    
    /**
     * Initialize addon
     */
    public function init() {
        // Only initialize if addon is active
        $addon_manager = skylearn_billing_pro_addon_manager();
        $active_addons = $addon_manager->get_active_addons();
        
        if (!in_array(self::ADDON_ID, $active_addons)) {
            return;
        }
        
        $this->init_hooks();
        $this->init_settings();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Webhook trigger hooks
        add_action('skylearn_billing_payment_completed', array($this, 'trigger_payment_webhook'));
        add_action('skylearn_billing_subscription_created', array($this, 'trigger_subscription_webhook'));
        add_action('skylearn_billing_subscription_updated', array($this, 'trigger_subscription_webhook'));
        add_action('skylearn_billing_subscription_cancelled', array($this, 'trigger_subscription_webhook'));
        add_action('skylearn_billing_customer_created', array($this, 'trigger_customer_webhook'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_skylearn_billing_test_webhook', array($this, 'test_webhook'));
        add_action('wp_ajax_skylearn_billing_save_webhook', array($this, 'save_webhook'));
        add_action('wp_ajax_skylearn_billing_delete_webhook', array($this, 'delete_webhook'));
    }
    
    /**
     * Initialize settings
     */
    private function init_settings() {
        register_setting('skylearn_billing_webhook_addon', 'skylearn_billing_webhook_addon_settings');
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'skylearn-billing-pro',
            __('Webhook Addon Settings', 'skylearn-billing-pro'),
            __('Webhook Addon', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-webhook-addon',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'skylearn-billing-webhook-addon') === false) {
            return;
        }
        
        wp_enqueue_script(
            'skylearn-billing-webhook-addon',
            SKYLEARN_BILLING_PLUGIN_URL . 'assets/js/webhook-addon-admin.js',
            array('jquery'),
            SKYLEARN_BILLING_VERSION,
            true
        );
        
        wp_localize_script('skylearn-billing-webhook-addon', 'skylearn_billing_webhook_addon', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_billing_webhook_addon'),
            'strings' => array(
                'testing' => __('Testing webhook...', 'skylearn-billing-pro'),
                'success' => __('Webhook test successful!', 'skylearn-billing-pro'),
                'error' => __('Webhook test failed.', 'skylearn-billing-pro'),
                'confirm_delete' => __('Are you sure you want to delete this webhook?', 'skylearn-billing-pro')
            )
        ));
    }
    
    /**
     * Trigger payment webhook
     *
     * @param array $payment_data Payment data
     */
    public function trigger_payment_webhook($payment_data) {
        $this->trigger_webhooks('payment.completed', $payment_data);
    }
    
    /**
     * Trigger subscription webhook
     *
     * @param array $subscription_data Subscription data
     */
    public function trigger_subscription_webhook($subscription_data) {
        $event = current_action();
        $event_name = str_replace('skylearn_billing_subscription_', 'subscription.', $event);
        
        $this->trigger_webhooks($event_name, $subscription_data);
    }
    
    /**
     * Trigger customer webhook
     *
     * @param array $customer_data Customer data
     */
    public function trigger_customer_webhook($customer_data) {
        $this->trigger_webhooks('customer.created', $customer_data);
    }
    
    /**
     * Trigger webhooks for event
     *
     * @param string $event Event name
     * @param array $data Event data
     */
    private function trigger_webhooks($event, $data) {
        $webhooks = $this->get_webhooks();
        
        foreach ($webhooks as $webhook) {
            if (!$webhook['active']) {
                continue;
            }
            
            // Check if webhook should trigger for this event
            if (!empty($webhook['events']) && !in_array($event, $webhook['events'])) {
                continue;
            }
            
            $this->send_webhook($webhook, $event, $data);
        }
    }
    
    /**
     * Send webhook
     *
     * @param array $webhook Webhook configuration
     * @param string $event Event name
     * @param array $data Event data
     */
    private function send_webhook($webhook, $event, $data) {
        $payload = array(
            'event' => $event,
            'timestamp' => current_time('timestamp'),
            'data' => $data
        );
        
        // Add signature if secret is provided
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'Skylearn-Billing-Pro/1.0'
        );
        
        if (!empty($webhook['secret'])) {
            $signature = hash_hmac('sha256', wp_json_encode($payload), $webhook['secret']);
            $headers['X-Skylearn-Signature'] = 'sha256=' . $signature;
        }
        
        // Send webhook
        $response = wp_remote_post($webhook['url'], array(
            'timeout' => 30,
            'headers' => $headers,
            'body' => wp_json_encode($payload)
        ));
        
        // Log webhook attempt
        $this->log_webhook_attempt($webhook, $event, $response);
    }
    
    /**
     * Log webhook attempt
     *
     * @param array $webhook Webhook configuration
     * @param string $event Event name
     * @param array|WP_Error $response Response data
     */
    private function log_webhook_attempt($webhook, $event, $response) {
        $log_entry = array(
            'timestamp' => current_time('timestamp'),
            'webhook_id' => $webhook['id'],
            'webhook_url' => $webhook['url'],
            'event' => $event,
            'success' => !is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300,
            'response_code' => is_wp_error($response) ? 0 : wp_remote_retrieve_response_code($response),
            'response_message' => is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_response_message($response)
        );
        
        // Store in log
        $log_key = 'skylearn_billing_webhook_log';
        $log = get_transient($log_key) ?: array();
        $log[] = $log_entry;
        
        // Keep only last 100 entries
        $log = array_slice($log, -100);
        
        set_transient($log_key, $log, WEEK_IN_SECONDS);
        
        // Trigger hook for other integrations
        do_action('skylearn_billing_webhook_attempted', $log_entry);
    }
    
    /**
     * Get webhooks
     *
     * @return array Webhooks array
     */
    private function get_webhooks() {
        $settings = get_option('skylearn_billing_webhook_addon_settings', array());
        return $settings['webhooks'] ?? array();
    }
    
    /**
     * Save webhook
     */
    public function save_webhook() {
        check_ajax_referer('skylearn_billing_webhook_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $webhook_data = array(
            'id' => sanitize_text_field($_POST['webhook_id']) ?: uniqid(),
            'name' => sanitize_text_field($_POST['webhook_name']),
            'url' => esc_url_raw($_POST['webhook_url']),
            'events' => array_map('sanitize_text_field', $_POST['webhook_events'] ?? array()),
            'secret' => sanitize_text_field($_POST['webhook_secret']),
            'active' => !empty($_POST['webhook_active'])
        );
        
        // Validate required fields
        if (empty($webhook_data['name']) || empty($webhook_data['url'])) {
            wp_send_json_error(__('Name and URL are required.', 'skylearn-billing-pro'));
        }
        
        $settings = get_option('skylearn_billing_webhook_addon_settings', array());
        $webhooks = $settings['webhooks'] ?? array();
        
        // Find existing webhook or add new one
        $found = false;
        foreach ($webhooks as &$webhook) {
            if ($webhook['id'] === $webhook_data['id']) {
                $webhook = $webhook_data;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $webhooks[] = $webhook_data;
        }
        
        $settings['webhooks'] = $webhooks;
        update_option('skylearn_billing_webhook_addon_settings', $settings);
        
        wp_send_json_success(__('Webhook saved successfully.', 'skylearn-billing-pro'));
    }
    
    /**
     * Delete webhook
     */
    public function delete_webhook() {
        check_ajax_referer('skylearn_billing_webhook_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $webhook_id = sanitize_text_field($_POST['webhook_id']);
        
        $settings = get_option('skylearn_billing_webhook_addon_settings', array());
        $webhooks = $settings['webhooks'] ?? array();
        
        $webhooks = array_filter($webhooks, function($webhook) use ($webhook_id) {
            return $webhook['id'] !== $webhook_id;
        });
        
        $settings['webhooks'] = array_values($webhooks);
        update_option('skylearn_billing_webhook_addon_settings', $settings);
        
        wp_send_json_success(__('Webhook deleted successfully.', 'skylearn-billing-pro'));
    }
    
    /**
     * Test webhook
     */
    public function test_webhook() {
        check_ajax_referer('skylearn_billing_webhook_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $webhook_url = esc_url_raw($_POST['webhook_url']);
        $webhook_secret = sanitize_text_field($_POST['webhook_secret']);
        
        if (empty($webhook_url)) {
            wp_send_json_error(__('Webhook URL is required.', 'skylearn-billing-pro'));
        }
        
        // Create test payload
        $test_payload = array(
            'event' => 'test.webhook',
            'timestamp' => current_time('timestamp'),
            'data' => array(
                'message' => 'This is a test webhook from Skylearn Billing Pro.'
            )
        );
        
        $headers = array(
            'Content-Type' => 'application/json',
            'User-Agent' => 'Skylearn-Billing-Pro/1.0'
        );
        
        if (!empty($webhook_secret)) {
            $signature = hash_hmac('sha256', wp_json_encode($test_payload), $webhook_secret);
            $headers['X-Skylearn-Signature'] = 'sha256=' . $signature;
        }
        
        $response = wp_remote_post($webhook_url, array(
            'timeout' => 15,
            'headers' => $headers,
            'body' => wp_json_encode($test_payload)
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error($response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            wp_send_json_success(__('Webhook test successful!', 'skylearn-billing-pro'));
        } else {
            wp_send_json_error(sprintf(__('Webhook test failed. Response code: %d', 'skylearn-billing-pro'), $response_code));
        }
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $webhooks = $this->get_webhooks();
        $webhook_log = get_transient('skylearn_billing_webhook_log') ?: array();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Webhook Addon Settings', 'skylearn-billing-pro'); ?></h1>
            
            <div class="skylearn-billing-webhook-manager">
                <h2><?php esc_html_e('Webhook Endpoints', 'skylearn-billing-pro'); ?></h2>
                
                <div class="skylearn-billing-webhook-form">
                    <h3><?php esc_html_e('Add/Edit Webhook', 'skylearn-billing-pro'); ?></h3>
                    
                    <form id="webhook-form">
                        <input type="hidden" id="webhook-id" />
                        
                        <table class="form-table">
                            <tr>
                                <th><label for="webhook-name"><?php esc_html_e('Name', 'skylearn-billing-pro'); ?></label></th>
                                <td><input type="text" id="webhook-name" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th><label for="webhook-url"><?php esc_html_e('URL', 'skylearn-billing-pro'); ?></label></th>
                                <td><input type="url" id="webhook-url" class="regular-text" required /></td>
                            </tr>
                            <tr>
                                <th><label for="webhook-events"><?php esc_html_e('Events', 'skylearn-billing-pro'); ?></label></th>
                                <td>
                                    <select id="webhook-events" multiple>
                                        <option value="payment.completed"><?php esc_html_e('Payment Completed', 'skylearn-billing-pro'); ?></option>
                                        <option value="subscription.created"><?php esc_html_e('Subscription Created', 'skylearn-billing-pro'); ?></option>
                                        <option value="subscription.updated"><?php esc_html_e('Subscription Updated', 'skylearn-billing-pro'); ?></option>
                                        <option value="subscription.cancelled"><?php esc_html_e('Subscription Cancelled', 'skylearn-billing-pro'); ?></option>
                                        <option value="customer.created"><?php esc_html_e('Customer Created', 'skylearn-billing-pro'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="webhook-secret"><?php esc_html_e('Secret (Optional)', 'skylearn-billing-pro'); ?></label></th>
                                <td>
                                    <input type="text" id="webhook-secret" class="regular-text" />
                                    <p class="description"><?php esc_html_e('Used to sign webhook payloads for verification.', 'skylearn-billing-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="webhook-active"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></label></th>
                                <td>
                                    <input type="checkbox" id="webhook-active" value="1" />
                                    <label for="webhook-active"><?php esc_html_e('Enable this webhook', 'skylearn-billing-pro'); ?></label>
                                </td>
                            </tr>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button-primary"><?php esc_html_e('Save Webhook', 'skylearn-billing-pro'); ?></button>
                            <button type="button" id="test-webhook" class="button"><?php esc_html_e('Test Webhook', 'skylearn-billing-pro'); ?></button>
                            <button type="button" id="reset-form" class="button"><?php esc_html_e('Reset Form', 'skylearn-billing-pro'); ?></button>
                        </p>
                    </form>
                </div>
                
                <div class="skylearn-billing-webhooks-list">
                    <h3><?php esc_html_e('Configured Webhooks', 'skylearn-billing-pro'); ?></h3>
                    
                    <?php if (empty($webhooks)): ?>
                        <p><?php esc_html_e('No webhooks configured yet.', 'skylearn-billing-pro'); ?></p>
                    <?php else: ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('URL', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Events', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Actions', 'skylearn-billing-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($webhooks as $webhook): ?>
                                    <tr>
                                        <td><?php echo esc_html($webhook['name']); ?></td>
                                        <td><?php echo esc_html($webhook['url']); ?></td>
                                        <td><?php echo esc_html(implode(', ', $webhook['events'])); ?></td>
                                        <td>
                                            <?php if ($webhook['active']): ?>
                                                <span class="status-active"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                                            <?php else: ?>
                                                <span class="status-inactive"><?php esc_html_e('Inactive', 'skylearn-billing-pro'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <button type="button" class="button edit-webhook" data-webhook-id="<?php echo esc_attr($webhook['id']); ?>"><?php esc_html_e('Edit', 'skylearn-billing-pro'); ?></button>
                                            <button type="button" class="button delete-webhook" data-webhook-id="<?php echo esc_attr($webhook['id']); ?>"><?php esc_html_e('Delete', 'skylearn-billing-pro'); ?></button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($webhook_log)): ?>
                    <div class="skylearn-billing-webhook-log">
                        <h3><?php esc_html_e('Recent Webhook Attempts', 'skylearn-billing-pro'); ?></h3>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Timestamp', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('URL', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Event', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Response', 'skylearn-billing-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (array_reverse(array_slice($webhook_log, -20)) as $log_entry): ?>
                                    <tr>
                                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $log_entry['timestamp'])); ?></td>
                                        <td><?php echo esc_html($log_entry['webhook_url']); ?></td>
                                        <td><?php echo esc_html($log_entry['event']); ?></td>
                                        <td>
                                            <?php if ($log_entry['success']): ?>
                                                <span class="status-success"><?php esc_html_e('Success', 'skylearn-billing-pro'); ?></span>
                                            <?php else: ?>
                                                <span class="status-error"><?php esc_html_e('Failed', 'skylearn-billing-pro'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html($log_entry['response_code'] . ' ' . $log_entry['response_message']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    /**
     * Handle addon activation
     *
     * @param string $addon_id Addon ID
     */
    public function on_addon_activated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Set default settings
            $default_settings = array(
                'webhooks' => array()
            );
            
            add_option('skylearn_billing_webhook_addon_settings', $default_settings);
            
            // Trigger activation hook for other integrations
            do_action('skylearn_billing_webhook_addon_activated');
        }
    }
    
    /**
     * Handle addon deactivation
     *
     * @param string $addon_id Addon ID
     */
    public function on_addon_deactivated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Clean up if needed
            do_action('skylearn_billing_webhook_addon_deactivated');
        }
    }
}

// Initialize the addon
SkyLearn_Billing_Pro_Webhook_Addon::instance();