<?php
/**
 * LMS admin page template for Skylearn Billing Pro
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

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'lms-settings';
$lms_manager = skylearn_billing_pro_lms_manager();
$course_mapping = skylearn_billing_pro_course_mapping();
$webhook_handler = skylearn_billing_pro_webhook_handler();
$licensing_manager = skylearn_billing_pro_licensing();

// Ensure course_mapping is properly initialized
if (!$course_mapping || !is_object($course_mapping)) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('SkyLearn Billing Pro: course_mapping not properly initialized');
    }
    // Create a fallback object with minimal functionality
    $course_mapping = new stdClass();
    $course_mapping->get_course_mappings = function() { return array(); };
}
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-welcome-learn-more"></span>
                <?php esc_html_e('LMS Integration', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Manage Learning Management System integration and course mappings', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'lms-settings') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-lms&tab=lms-settings')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php esc_html_e('LMS Settings', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'course-mapping') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-lms&tab=course-mapping')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php esc_html_e('Course Mapping', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'webhook-settings') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-lms&tab=webhook-settings')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-rest-api"></span>
                            <?php esc_html_e('Webhook Settings', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item <?php echo ($active_tab === 'enrollment-log') ? 'active' : ''; ?>">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-lms&tab=enrollment-log')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php esc_html_e('Enrollment Log', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Back to Main -->
            <div class="skylearn-billing-back-link">
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro')); ?>" class="skylearn-billing-btn skylearn-billing-btn-secondary">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php esc_html_e('Back to Dashboard', 'skylearn-billing-pro'); ?>
                </a>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="skylearn-billing-content">
            <?php
            switch ($active_tab) {
                case 'lms-settings':
                    render_lms_settings_tab($lms_manager);
                    break;
                case 'course-mapping':
                    render_course_mapping_tab($course_mapping);
                    break;
                case 'webhook-settings':
                    render_webhook_settings_tab($webhook_handler);
                    break;
                case 'enrollment-log':
                    render_enrollment_log_tab($course_mapping);
                    break;
                default:
                    render_lms_settings_tab($lms_manager);
                    break;
            }
            ?>
        </div>
    </div>
</div>

<?php
/**
 * Render LMS Settings tab
 */
function render_lms_settings_tab($lms_manager) {
    $lms_status = $lms_manager->get_integration_status();
    
    // Handle manual override form submission
    if (isset($_POST['save_manual_override']) && wp_verify_nonce($_POST['manual_override_nonce'], 'save_manual_override')) {
        $messages = array();
        $errors = array();
        
        // Process each LMS override setting
        $supported_lms = $lms_manager->get_supported_lms();
        foreach ($supported_lms as $lms_key => $lms_data) {
            $override_enabled = isset($_POST['manual_override_' . $lms_key]) && $_POST['manual_override_' . $lms_key] === '1';
            $current_override = $lms_manager->get_lms_manual_override($lms_key);
            
            if ($override_enabled !== $current_override) {
                $result = $lms_manager->set_lms_manual_override($lms_key, $override_enabled);
                if (is_wp_error($result)) {
                    $errors[] = sprintf(__('Failed to update manual override for %s: %s', 'skylearn-billing-pro'), $lms_data['name'], $result->get_error_message());
                } elseif ($result === true) {
                    if ($override_enabled) {
                        $messages[] = sprintf(__('Manual override enabled for %s', 'skylearn-billing-pro'), $lms_data['name']);
                    } else {
                        $messages[] = sprintf(__('Manual override disabled for %s', 'skylearn-billing-pro'), $lms_data['name']);
                    }
                } else {
                    $errors[] = sprintf(__('Unexpected result when updating manual override for %s', 'skylearn-billing-pro'), $lms_data['name']);
                }
            }
        }
        
        // Show messages
        if (!empty($messages)) {
            echo '<div class="notice notice-success"><p>' . implode('<br>', array_map('esc_html', $messages)) . '</p></div>';
        }
        if (!empty($errors)) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__('Errors:', 'skylearn-billing-pro') . '</strong><br>' . implode('<br>', array_map('esc_html', $errors)) . '</p></div>';
        }
        
        // Refresh LMS status after changes (only if there were no errors)
        if (empty($errors)) {
            $lms_status = $lms_manager->get_integration_status();
        }
    }
    ?>
    <div class="skylearn-billing-tab-content">
        <!-- LMS Detection Card -->
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('LMS Detection', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Detected Learning Management System plugins on your site.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <?php if ($lms_status['detected_count'] === 0): ?>
                    <div class="skylearn-billing-notice skylearn-billing-notice-warning">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong><?php esc_html_e('No LMS plugins detected', 'skylearn-billing-pro'); ?></strong><br>
                            <?php esc_html_e('To use course enrollment features, please install one of the supported LMS plugins:', 'skylearn-billing-pro'); ?>
                            <ul style="margin-top: 10px;">
                                <li>• <strong>LearnDash</strong> - Professional WordPress LMS</li>
                                <li>• <strong>TutorLMS</strong> - Complete Learning Management System</li>
                                <li>• <strong>LifterLMS</strong> - WordPress LMS Plugin</li>
                                <li>• <strong>LearnPress</strong> - WordPress LMS Plugin</li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="skylearn-billing-lms-grid">
                        <?php foreach ($lms_status['detected_lms'] as $lms_key => $lms_data): ?>
                            <div class="skylearn-billing-lms-item <?php echo ($lms_status['active_lms'] === $lms_key) ? 'active' : ''; ?>">
                                <div class="skylearn-billing-lms-header">
                                    <h4><?php echo esc_html($lms_data['name']); ?></h4>
                                    <?php if ($lms_status['active_lms'] === $lms_key): ?>
                                        <span class="skylearn-billing-status-active"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                                    <?php else: ?>
                                        <span class="skylearn-billing-status-inactive"><?php esc_html_e('Detected', 'skylearn-billing-pro'); ?></span>
                                    <?php endif; ?>
                                </div>
                                <p><?php printf(esc_html__('Plugin: %s', 'skylearn-billing-pro'), $lms_data['plugin_path']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Manual LMS Override Card -->
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('Manual LMS Override', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Force enable LMS detection for troubleshooting. Use this if your LMS is installed but not being detected automatically.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <form method="post">
                    <?php wp_nonce_field('save_manual_override', 'manual_override_nonce'); ?>
                    
                    <div class="skylearn-billing-override-grid">
                        <?php 
                        $supported_lms = $lms_manager->get_supported_lms();
                        foreach ($supported_lms as $lms_key => $lms_data): 
                            $is_detected = isset($lms_status['detected_lms'][$lms_key]);
                            $manual_override = $lms_manager->get_lms_manual_override($lms_key);
                            $is_naturally_active = $is_detected && !$manual_override;
                        ?>
                            <div class="skylearn-billing-override-item">
                                <div class="skylearn-billing-override-header">
                                    <div class="skylearn-billing-override-info">
                                        <h4><?php echo esc_html($lms_data['name']); ?></h4>
                                        <p class="skylearn-billing-override-status">
                                            <?php if ($is_naturally_active): ?>
                                                <span class="skylearn-billing-status-active"><?php esc_html_e('Naturally Detected', 'skylearn-billing-pro'); ?></span>
                                            <?php elseif ($manual_override): ?>
                                                <span class="skylearn-billing-status-override"><?php esc_html_e('Manual Override Active', 'skylearn-billing-pro'); ?></span>
                                            <?php else: ?>
                                                <span class="skylearn-billing-status-inactive"><?php esc_html_e('Not Detected', 'skylearn-billing-pro'); ?></span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <div class="skylearn-billing-override-control">
                                        <label class="skylearn-billing-toggle">
                                            <input type="checkbox" 
                                                   name="manual_override_<?php echo esc_attr($lms_key); ?>" 
                                                   value="1" 
                                                   <?php checked($manual_override, true); ?>
                                                   <?php echo $is_naturally_active ? 'disabled title="' . esc_attr__('This LMS is already naturally detected', 'skylearn-billing-pro') . '"' : ''; ?> />
                                            <span class="skylearn-billing-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="skylearn-billing-override-details">
                                    <p><small><?php printf(esc_html__('Primary plugin path: %s', 'skylearn-billing-pro'), $lms_data['plugin_path']); ?></small></p>
                                    <?php if ($manual_override): ?>
                                        <div class="skylearn-billing-notice skylearn-billing-notice-info" style="margin-top: 8px; padding: 8px 12px;">
                                            <small><?php esc_html_e('⚠️ Manual override is active. The LMS will be treated as installed even if not detected.', 'skylearn-billing-pro'); ?></small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="skylearn-billing-form-actions" style="margin-top: 20px;">
                        <button type="submit" name="save_manual_override" class="skylearn-billing-btn skylearn-billing-btn-primary">
                            <?php esc_html_e('Save Manual Override Settings', 'skylearn-billing-pro'); ?>
                        </button>
                    </div>
                    
                    <div class="skylearn-billing-notice skylearn-billing-notice-warning" style="margin-top: 15px;">
                        <span class="dashicons dashicons-warning"></span>
                        <div>
                            <strong><?php esc_html_e('Important Note:', 'skylearn-billing-pro'); ?></strong><br>
                            <?php esc_html_e('Manual override should only be used for troubleshooting when you know the LMS is properly installed. Using override without the actual LMS installed may cause errors.', 'skylearn-billing-pro'); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($lms_status['detected_count'] > 0): ?>
            <!-- LMS Configuration Card -->
            <div class="skylearn-billing-card">
                <div class="skylearn-billing-card-header">
                    <h3><?php esc_html_e('LMS Configuration', 'skylearn-billing-pro'); ?></h3>
                    <p><?php esc_html_e('Configure which LMS to use for course enrollment.', 'skylearn-billing-pro'); ?></p>
                </div>
                
                <div class="skylearn-billing-card-body">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('skylearn_billing_pro_lms');
                        do_settings_sections('skylearn_billing_pro_lms');
                        ?>
                        
                        <div class="skylearn-billing-form-actions">
                            <?php submit_button(__('Save LMS Settings', 'skylearn-billing-pro'), 'primary', 'submit', false, array('class' => 'skylearn-billing-btn skylearn-billing-btn-primary')); ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- LMS Status Card -->
            <?php if ($lms_status['active_lms']): ?>
                <div class="skylearn-billing-card">
                    <div class="skylearn-billing-card-header">
                        <h3><?php esc_html_e('Integration Status', 'skylearn-billing-pro'); ?></h3>
                    </div>
                    <div class="skylearn-billing-card-body">
                        <div class="skylearn-billing-stats-grid">
                            <div class="skylearn-billing-stat-item">
                                <div class="skylearn-billing-stat-icon">
                                    <span class="dashicons dashicons-admin-plugins"></span>
                                </div>
                                <div class="skylearn-billing-stat-content">
                                    <div class="skylearn-billing-stat-label"><?php esc_html_e('Active LMS', 'skylearn-billing-pro'); ?></div>
                                    <div class="skylearn-billing-stat-number"><?php echo esc_html($lms_status['active_lms_name']); ?></div>
                                </div>
                            </div>
                            <div class="skylearn-billing-stat-item">
                                <div class="skylearn-billing-stat-icon">
                                    <span class="dashicons dashicons-book"></span>
                                </div>
                                <div class="skylearn-billing-stat-content">
                                    <div class="skylearn-billing-stat-label"><?php esc_html_e('Available Courses', 'skylearn-billing-pro'); ?></div>
                                    <div class="skylearn-billing-stat-number">
                                        <?php echo intval($lms_status['course_count']); ?>
                                        <?php if (isset($lms_status['course_error']) && $lms_status['course_count'] === 0): ?>
                                            <br><small style="color: #d63638;">Error: <?php echo esc_html($lms_status['course_error']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="skylearn-billing-stat-item">
                                <div class="skylearn-billing-stat-icon">
                                    <span class="dashicons dashicons-admin-links"></span>
                                </div>
                                <div class="skylearn-billing-stat-content">
                                    <div class="skylearn-billing-stat-label"><?php esc_html_e('Course Mappings', 'skylearn-billing-pro'); ?></div>
                                    <div class="skylearn-billing-stat-number"><?php echo ($course_mapping && method_exists($course_mapping, 'get_course_mappings')) ? count($course_mapping->get_course_mappings()) : 0; ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (isset($lms_status['error']) || (isset($lms_status['course_error']) && $lms_status['course_count'] === 0)): ?>
                            <div class="skylearn-billing-notice skylearn-billing-notice-warning" style="margin-top: 15px;">
                                <span class="dashicons dashicons-warning"></span>
                                <div>
                                    <strong><?php esc_html_e('LMS Integration Issues Detected', 'skylearn-billing-pro'); ?></strong><br>
                                    <?php if (isset($lms_status['error'])): ?>
                                        <?php esc_html_e('General error: ', 'skylearn-billing-pro'); ?><?php echo esc_html($lms_status['error']); ?><br>
                                    <?php endif; ?>
                                    <?php if (isset($lms_status['course_error']) && $lms_status['course_count'] === 0): ?>
                                        <?php esc_html_e('Course detection error: ', 'skylearn-billing-pro'); ?><?php echo esc_html($lms_status['course_error']); ?><br>
                                    <?php endif; ?>
                                    <?php esc_html_e('Check the error logs for more details. If issues persist, the problem may be with LearnDash installation or database connectivity.', 'skylearn-billing-pro'); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Render Course Mapping tab
 */
function render_course_mapping_tab($course_mapping) {
    $course_mapping->render_mapping_ui();
}

/**
 * Render Webhook Settings tab
 */
function render_webhook_settings_tab($webhook_handler) {
    $webhook_settings = $webhook_handler->get_webhook_settings();
    $webhook_url = $webhook_handler->get_webhook_url();
    
    // Handle form submission
    if (isset($_POST['save_webhook_settings']) && wp_verify_nonce($_POST['webhook_nonce'], 'save_webhook_settings')) {
        $new_settings = array(
            'secret' => sanitize_text_field($_POST['webhook_secret']),
            'send_welcome_email' => isset($_POST['send_welcome_email']),
            'enabled' => isset($_POST['webhook_enabled'])
        );
        
        $result = $webhook_handler->save_webhook_settings($new_settings);
        
        if (is_wp_error($result)) {
            echo '<div class="notice notice-error"><p><strong>' . esc_html__('Error:', 'skylearn-billing-pro') . '</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
        } elseif ($result === true) {
            echo '<div class="notice notice-success"><p>' . esc_html__('Webhook settings saved successfully.', 'skylearn-billing-pro') . '</p></div>';
            $webhook_settings = $new_settings;
        } else {
            echo '<div class="notice notice-error"><p>' . esc_html__('Failed to save webhook settings. Please try again.', 'skylearn-billing-pro') . '</p></div>';
        }
    }
    ?>
    <div class="skylearn-billing-tab-content">
        <!-- Webhook Configuration Card -->
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('Webhook Configuration', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Configure webhook endpoint for third-party automation tools like Zapier, Pabbly Connect, and ActivePiece.', 'skylearn-billing-pro'); ?></p>
            </div>
            
            <div class="skylearn-billing-card-body">
                <form method="post">
                    <?php wp_nonce_field('save_webhook_settings', 'webhook_nonce'); ?>
                    
                    <div class="skylearn-billing-form-group">
                        <label for="webhook_enabled">
                            <input type="checkbox" id="webhook_enabled" name="webhook_enabled" <?php checked($webhook_settings['enabled'], true); ?> />
                            <?php esc_html_e('Enable webhook endpoint', 'skylearn-billing-pro'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Enable or disable the webhook endpoint for third-party integrations.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="skylearn-billing-form-group">
                        <label for="webhook_secret"><?php esc_html_e('Webhook Secret', 'skylearn-billing-pro'); ?></label>
                        <input type="text" id="webhook_secret" name="webhook_secret" value="<?php echo esc_attr($webhook_settings['secret']); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e('Secret key for webhook authentication. Include this as X-API-Key header or api_key parameter.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="skylearn-billing-form-group">
                        <label for="send_welcome_email">
                            <input type="checkbox" id="send_welcome_email" name="send_welcome_email" <?php checked($webhook_settings['send_welcome_email'], true); ?> />
                            <?php esc_html_e('Send welcome email to new users', 'skylearn-billing-pro'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Automatically send welcome email with login credentials to users created via webhook.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="skylearn-billing-form-actions">
                        <button type="submit" name="save_webhook_settings" class="skylearn-billing-btn skylearn-billing-btn-primary">
                            <?php esc_html_e('Save Webhook Settings', 'skylearn-billing-pro'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Webhook Information Card -->
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h3><?php esc_html_e('Webhook Information', 'skylearn-billing-pro'); ?></h3>
            </div>
            <div class="skylearn-billing-card-body">
                <div class="skylearn-billing-form-group">
                    <label><?php esc_html_e('Webhook URL:', 'skylearn-billing-pro'); ?></label>
                    <div class="skylearn-billing-webhook-url">
                        <input type="text" value="<?php echo esc_attr($webhook_url); ?>" readonly class="large-text" />
                        <button type="button" class="button" onclick="navigator.clipboard.writeText('<?php echo esc_js($webhook_url); ?>')"><?php esc_html_e('Copy', 'skylearn-billing-pro'); ?></button>
                    </div>
                </div>
                
                <div class="skylearn-billing-webhook-docs">
                    <h4><?php esc_html_e('Required Data Format', 'skylearn-billing-pro'); ?></h4>
                    <p><?php esc_html_e('Send POST requests with the following JSON data:', 'skylearn-billing-pro'); ?></p>
                    <pre><code>{
  "email": "customer@example.com",
  "name": "John Doe",
  "product_id": "your_product_id"
}</code></pre>
                    
                    <h4><?php esc_html_e('Optional Fields', 'skylearn-billing-pro'); ?></h4>
                    <ul>
                        <li><strong>first_name</strong> - Customer first name</li>
                        <li><strong>last_name</strong> - Customer last name</li>
                        <li><strong>phone</strong> - Customer phone number</li>
                        <li><strong>company</strong> - Customer company</li>
                    </ul>
                    
                    <h4><?php esc_html_e('Authentication', 'skylearn-billing-pro'); ?></h4>
                    <p><?php esc_html_e('Include the webhook secret in one of these ways:', 'skylearn-billing-pro'); ?></p>
                    <ul>
                        <li><strong>Header:</strong> X-API-Key: <?php echo esc_html($webhook_settings['secret']); ?></li>
                        <li><strong>Query parameter:</strong> ?api_key=<?php echo esc_html($webhook_settings['secret']); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render Enrollment Log tab
 */
function render_enrollment_log_tab($course_mapping) {
    // Handle log clearing if requested
    if (isset($_POST['clear_enrollment_log']) && wp_verify_nonce($_POST['clear_log_nonce'], 'clear_enrollment_log')) {
        if (current_user_can('manage_options')) {
            $options = get_option('skylearn_billing_pro_options', array());
            $options['enrollment_log'] = array();
            update_option('skylearn_billing_pro_options', $options);
            echo '<div class="notice notice-success"><p>' . esc_html__('Enrollment log cleared successfully.', 'skylearn-billing-pro') . '</p></div>';
        }
    }
    
    $enrollment_log = $course_mapping->get_enrollment_log(100);
    $total_entries = count($course_mapping->get_enrollment_log(0)); // Get total count
    ?>
    <div class="skylearn-billing-tab-content">
        <div class="skylearn-billing-card">
            <div class="skylearn-billing-card-header">
                <h2><?php esc_html_e('Enrollment Log', 'skylearn-billing-pro'); ?></h2>
                <p><?php esc_html_e('Track all course enrollment activities and their status.', 'skylearn-billing-pro'); ?></p>
                
                <?php if ($total_entries > 0): ?>
                    <div class="skylearn-billing-log-stats">
                        <p><?php printf(esc_html__('Showing latest %d of %d total entries.', 'skylearn-billing-pro'), min(100, $total_entries), $total_entries); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="skylearn-billing-card-body">
                <?php if (empty($enrollment_log)): ?>
                    <div class="skylearn-billing-empty-state">
                        <span class="dashicons dashicons-list-view"></span>
                        <h4><?php esc_html_e('No enrollment activity yet', 'skylearn-billing-pro'); ?></h4>
                        <p><?php esc_html_e('Enrollment activities will appear here once users start purchasing courses.', 'skylearn-billing-pro'); ?></p>
                        <p><small><?php esc_html_e('Activities are logged for payment completions, webhook enrollments, and manual enrollments.', 'skylearn-billing-pro'); ?></small></p>
                    </div>
                <?php else: ?>
                    <!-- Quick stats -->
                    <?php
                    $successful_enrollments = array_filter($enrollment_log, function($entry) { return $entry['status'] === 'success'; });
                    $failed_enrollments = array_filter($enrollment_log, function($entry) { return $entry['status'] === 'failed'; });
                    $payment_triggers = array_filter($enrollment_log, function($entry) { return $entry['trigger'] === 'payment'; });
                    $webhook_triggers = array_filter($enrollment_log, function($entry) { return $entry['trigger'] === 'webhook'; });
                    ?>
                    
                    <div class="skylearn-billing-log-summary">
                        <div class="skylearn-billing-stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); margin-bottom: 20px;">
                            <div class="skylearn-billing-stat-item" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                <div class="skylearn-billing-stat-number" style="font-size: 24px; font-weight: bold; color: #28a745;"><?php echo count($successful_enrollments); ?></div>
                                <div class="skylearn-billing-stat-label" style="font-size: 12px; color: #666;"><?php esc_html_e('Successful', 'skylearn-billing-pro'); ?></div>
                            </div>
                            <div class="skylearn-billing-stat-item" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                <div class="skylearn-billing-stat-number" style="font-size: 24px; font-weight: bold; color: #dc3545;"><?php echo count($failed_enrollments); ?></div>
                                <div class="skylearn-billing-stat-label" style="font-size: 12px; color: #666;"><?php esc_html_e('Failed', 'skylearn-billing-pro'); ?></div>
                            </div>
                            <div class="skylearn-billing-stat-item" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                <div class="skylearn-billing-stat-number" style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo count($payment_triggers); ?></div>
                                <div class="skylearn-billing-stat-label" style="font-size: 12px; color: #666;"><?php esc_html_e('Payment', 'skylearn-billing-pro'); ?></div>
                            </div>
                            <div class="skylearn-billing-stat-item" style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 6px;">
                                <div class="skylearn-billing-stat-number" style="font-size: 24px; font-weight: bold; color: #6f42c1;"><?php echo count($webhook_triggers); ?></div>
                                <div class="skylearn-billing-stat-label" style="font-size: 12px; color: #666;"><?php esc_html_e('Webhook', 'skylearn-billing-pro'); ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter and actions -->
                    <div class="skylearn-billing-log-actions" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div class="skylearn-billing-log-filters">
                            <select id="status-filter" onchange="filterEnrollmentLog()">
                                <option value=""><?php esc_html_e('All Statuses', 'skylearn-billing-pro'); ?></option>
                                <option value="success"><?php esc_html_e('Success Only', 'skylearn-billing-pro'); ?></option>
                                <option value="failed"><?php esc_html_e('Failed Only', 'skylearn-billing-pro'); ?></option>
                            </select>
                            <select id="trigger-filter" onchange="filterEnrollmentLog()">
                                <option value=""><?php esc_html_e('All Triggers', 'skylearn-billing-pro'); ?></option>
                                <option value="payment"><?php esc_html_e('Payment', 'skylearn-billing-pro'); ?></option>
                                <option value="webhook"><?php esc_html_e('Webhook', 'skylearn-billing-pro'); ?></option>
                                <option value="manual"><?php esc_html_e('Manual', 'skylearn-billing-pro'); ?></option>
                            </select>
                        </div>
                        
                        <?php if (current_user_can('manage_options') && $total_entries > 0): ?>
                            <form method="post" style="display: inline;" onsubmit="return confirm('<?php esc_js_e('Are you sure you want to clear the enrollment log? This action cannot be undone.', 'skylearn-billing-pro'); ?>');">
                                <?php wp_nonce_field('clear_enrollment_log', 'clear_log_nonce'); ?>
                                <button type="submit" name="clear_enrollment_log" class="button button-secondary">
                                    <?php esc_html_e('Clear Log', 'skylearn-billing-pro'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>

                    <div class="skylearn-billing-log-table">
                        <table class="wp-list-table widefat fixed striped" id="enrollment-log-table">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('User', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Course', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Product ID', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Trigger', 'skylearn-billing-pro'); ?></th>
                                    <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($enrollment_log as $entry): ?>
                                    <tr data-status="<?php echo esc_attr($entry['status']); ?>" data-trigger="<?php echo esc_attr($entry['trigger']); ?>">
                                        <td>
                                            <span title="<?php echo esc_attr(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($entry['timestamp']))); ?>">
                                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($entry['timestamp']))); ?>
                                            </span>
                                            <br><small style="color: #666;"><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($entry['timestamp']))); ?></small>
                                        </td>
                                        <td>
                                            <?php if (!empty($entry['user_email'])): ?>
                                                <strong><?php echo esc_html($entry['user_email']); ?></strong>
                                                <br><small style="color: #666;">ID: <?php echo esc_html($entry['user_id']); ?></small>
                                            <?php else: ?>
                                                <span style="color: #666;"><?php esc_html_e('User ID:', 'skylearn-billing-pro'); ?> <?php echo esc_html($entry['user_id']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($entry['course_title'])): ?>
                                                <strong><?php echo esc_html($entry['course_title']); ?></strong>
                                                <br><small style="color: #666;">ID: <?php echo esc_html($entry['course_id']); ?></small>
                                            <?php else: ?>
                                                <span style="color: #666;"><?php esc_html_e('Course ID:', 'skylearn-billing-pro'); ?> <?php echo esc_html($entry['course_id']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px;"><?php echo esc_html($entry['product_id']); ?></code>
                                        </td>
                                        <td>
                                            <span class="skylearn-billing-badge" style="display: inline-block; padding: 4px 8px; font-size: 11px; font-weight: 600; text-transform: uppercase; border-radius: 3px; background: #e9ecef; color: #495057;">
                                                <?php echo esc_html(ucfirst($entry['trigger'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($entry['status'] === 'success'): ?>
                                                <span class="skylearn-billing-status-active" style="color: #155724; background: #d4edda; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                                    <?php esc_html_e('Success', 'skylearn-billing-pro'); ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="skylearn-billing-status-inactive" style="color: #721c24; background: #f8d7da; padding: 4px 8px; border-radius: 3px; font-size: 11px; font-weight: 600; text-transform: uppercase;">
                                                    <?php esc_html_e('Failed', 'skylearn-billing-pro'); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <script>
                    function filterEnrollmentLog() {
                        const statusFilter = document.getElementById('status-filter').value;
                        const triggerFilter = document.getElementById('trigger-filter').value;
                        const rows = document.querySelectorAll('#enrollment-log-table tbody tr');
                        
                        rows.forEach(row => {
                            const status = row.getAttribute('data-status');
                            const trigger = row.getAttribute('data-trigger');
                            
                            const statusMatch = !statusFilter || status === statusFilter;
                            const triggerMatch = !triggerFilter || trigger === triggerFilter;
                            
                            if (statusMatch && triggerMatch) {
                                row.style.display = '';
                            } else {
                                row.style.display = 'none';
                            }
                        });
                    }
                    </script>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
}
?>