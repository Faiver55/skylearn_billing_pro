<?php
/**
 * Status UI Template for Skylearn Billing Pro
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

$addon_manager = skylearn_billing_pro_addon_manager();
$license_manager = skylearn_billing_pro_license_manager();
$licensing_manager = skylearn_billing_pro_licensing();

// Get system information
$active_addons = $addon_manager->get_active_addons();
$installed_addons = $addon_manager->get_installed_addons();
$available_addons = $addon_manager->get_available_addons();

// Get license information
$current_tier = $licensing_manager->get_current_tier();
$license_active = $licensing_manager->is_license_active();
$license_data = $licensing_manager->get_license_data();

// Get addon access log
$access_log = $license_manager->get_addon_access_log();

// System checks
$system_checks = array(
    'php_version' => array(
        'name' => __('PHP Version', 'skylearn-billing-pro'),
        'status' => version_compare(PHP_VERSION, '7.4', '>=') ? 'pass' : 'fail',
        'value' => PHP_VERSION,
        'required' => '7.4+',
        'description' => __('PHP version compatibility check', 'skylearn-billing-pro')
    ),
    'wordpress_version' => array(
        'name' => __('WordPress Version', 'skylearn-billing-pro'),
        'status' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'pass' : 'fail',
        'value' => get_bloginfo('version'),
        'required' => '5.0+',
        'description' => __('WordPress version compatibility check', 'skylearn-billing-pro')
    ),
    'ssl_enabled' => array(
        'name' => __('SSL/HTTPS', 'skylearn-billing-pro'),
        'status' => is_ssl() ? 'pass' : 'warning',
        'value' => is_ssl() ? __('Enabled', 'skylearn-billing-pro') : __('Disabled', 'skylearn-billing-pro'),
        'required' => __('Enabled', 'skylearn-billing-pro'),
        'description' => __('SSL is required for secure payment processing', 'skylearn-billing-pro')
    ),
    'curl_available' => array(
        'name' => __('cURL Extension', 'skylearn-billing-pro'),
        'status' => function_exists('curl_init') ? 'pass' : 'fail',
        'value' => function_exists('curl_init') ? __('Available', 'skylearn-billing-pro') : __('Not Available', 'skylearn-billing-pro'),
        'required' => __('Required', 'skylearn-billing-pro'),
        'description' => __('cURL is required for API communications', 'skylearn-billing-pro')
    ),
    'memory_limit' => array(
        'name' => __('Memory Limit', 'skylearn-billing-pro'),
        'status' => wp_convert_hr_to_bytes(ini_get('memory_limit')) >= wp_convert_hr_to_bytes('128M') ? 'pass' : 'warning',
        'value' => ini_get('memory_limit'),
        'required' => '128M+',
        'description' => __('Recommended memory limit for optimal performance', 'skylearn-billing-pro')
    )
);
?>

<div class="skylearn-billing-status-page">
    <div class="skylearn-billing-status-header">
        <h2><?php esc_html_e('System Status', 'skylearn-billing-pro'); ?></h2>
        <p><?php esc_html_e('Monitor the health and status of your Skylearn Billing Pro installation.', 'skylearn-billing-pro'); ?></p>
    </div>

    <!-- License Status Section -->
    <div class="skylearn-billing-status-section">
        <h3><?php esc_html_e('License Status', 'skylearn-billing-pro'); ?></h3>
        
        <div class="skylearn-billing-status-card">
            <div class="skylearn-billing-status-grid">
                <div class="skylearn-billing-status-item">
                    <span class="skylearn-billing-status-label"><?php esc_html_e('Current Tier:', 'skylearn-billing-pro'); ?></span>
                    <span class="skylearn-billing-status-value skylearn-billing-tier-<?php echo esc_attr($current_tier); ?>">
                        <?php echo esc_html($licensing_manager->get_tier_display_name()); ?>
                    </span>
                </div>
                
                <div class="skylearn-billing-status-item">
                    <span class="skylearn-billing-status-label"><?php esc_html_e('License Status:', 'skylearn-billing-pro'); ?></span>
                    <span class="skylearn-billing-status-value <?php echo $license_active ? 'skylearn-billing-status-active' : 'skylearn-billing-status-inactive'; ?>">
                        <?php echo $license_active ? __('Active', 'skylearn-billing-pro') : __('Inactive', 'skylearn-billing-pro'); ?>
                    </span>
                </div>
                
                <?php if ($license_data && isset($license_data['expires_at'])): ?>
                <div class="skylearn-billing-status-item">
                    <span class="skylearn-billing-status-label"><?php esc_html_e('Expires:', 'skylearn-billing-pro'); ?></span>
                    <span class="skylearn-billing-status-value">
                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($license_data['expires_at']))); ?>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="skylearn-billing-status-item">
                    <span class="skylearn-billing-status-label"><?php esc_html_e('Site URL:', 'skylearn-billing-pro'); ?></span>
                    <span class="skylearn-billing-status-value">
                        <?php echo esc_html(home_url()); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Addon Status Section -->
    <div class="skylearn-billing-status-section">
        <h3><?php esc_html_e('Addon Status', 'skylearn-billing-pro'); ?></h3>
        
        <div class="skylearn-billing-status-card">
            <div class="skylearn-billing-status-summary">
                <div class="skylearn-billing-status-stat">
                    <span class="skylearn-billing-stat-number"><?php echo count($active_addons); ?></span>
                    <span class="skylearn-billing-stat-label"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                </div>
                <div class="skylearn-billing-status-stat">
                    <span class="skylearn-billing-stat-number"><?php echo count($installed_addons); ?></span>
                    <span class="skylearn-billing-stat-label"><?php esc_html_e('Installed', 'skylearn-billing-pro'); ?></span>
                </div>
                <div class="skylearn-billing-status-stat">
                    <span class="skylearn-billing-stat-number"><?php echo count($available_addons); ?></span>
                    <span class="skylearn-billing-stat-label"><?php esc_html_e('Available', 'skylearn-billing-pro'); ?></span>
                </div>
            </div>
            
            <div class="skylearn-billing-addon-status-list">
                <h4><?php esc_html_e('Active Addons', 'skylearn-billing-pro'); ?></h4>
                
                <?php if (empty($active_addons)): ?>
                    <p class="skylearn-billing-status-empty"><?php esc_html_e('No addons are currently active.', 'skylearn-billing-pro'); ?></p>
                <?php else: ?>
                    <table class="skylearn-billing-status-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Addon Name', 'skylearn-billing-pro'); ?></th>
                                <th><?php esc_html_e('Version', 'skylearn-billing-pro'); ?></th>
                                <th><?php esc_html_e('Type', 'skylearn-billing-pro'); ?></th>
                                <th><?php esc_html_e('License Status', 'skylearn-billing-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_addons as $addon_id): ?>
                                <?php 
                                $addon = $available_addons[$addon_id] ?? null;
                                if (!$addon) continue;
                                
                                $license_status = $license_manager->get_addon_license_status($addon_id);
                                ?>
                                <tr>
                                    <td><?php echo esc_html($addon['name']); ?></td>
                                    <td><?php echo esc_html($addon['version']); ?></td>
                                    <td>
                                        <?php echo $license_manager->get_addon_tier_badge($addon['type'], $addon['required_tier']); ?>
                                    </td>
                                    <td>
                                        <span class="skylearn-billing-license-status <?php echo $license_status['valid'] ? 'valid' : 'invalid'; ?>">
                                            <?php echo $license_status['valid'] ? __('Valid', 'skylearn-billing-pro') : __('Invalid', 'skylearn-billing-pro'); ?>
                                        </span>
                                        <?php if (!$license_status['valid'] && $license_status['action_required']): ?>
                                            <div class="skylearn-billing-license-message">
                                                <?php echo $license_status['message']; ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Automation Status Section -->
    <div class="skylearn-billing-status-section">
        <h3><?php esc_html_e('Automation Status', 'skylearn-billing-pro'); ?></h3>
        
        <?php
        $automation_manager = skylearn_billing_pro_automation_manager();
        $total_automations = count($automation_manager->get_automations());
        $active_automations = count($automation_manager->get_automations('active'));
        $recent_logs = $automation_manager->get_automation_logs(null, 10, 0);
        
        // Calculate success rate from recent logs
        $success_count = 0;
        foreach ($recent_logs as $log) {
            if ($log['status'] === 'success') {
                $success_count++;
            }
        }
        $success_rate = count($recent_logs) > 0 ? round(($success_count / count($recent_logs)) * 100, 1) : 0;
        ?>
        
        <div class="skylearn-billing-status-card">
            <div class="skylearn-billing-status-summary">
                <div class="skylearn-billing-status-stat">
                    <span class="skylearn-billing-stat-number"><?php echo $total_automations; ?></span>
                    <span class="skylearn-billing-stat-label"><?php esc_html_e('Total Automations', 'skylearn-billing-pro'); ?></span>
                </div>
                <div class="skylearn-billing-status-stat">
                    <span class="skylearn-billing-stat-number"><?php echo $active_automations; ?></span>
                    <span class="skylearn-billing-stat-label"><?php esc_html_e('Active', 'skylearn-billing-pro'); ?></span>
                </div>
                <div class="skylearn-billing-status-stat">
                    <span class="skylearn-billing-stat-number"><?php echo $success_rate; ?>%</span>
                    <span class="skylearn-billing-stat-label"><?php esc_html_e('Success Rate', 'skylearn-billing-pro'); ?></span>
                </div>
            </div>
            
            <div class="skylearn-billing-automation-recent-logs">
                <h4><?php esc_html_e('Recent Automation Logs', 'skylearn-billing-pro'); ?></h4>
                
                <?php if (empty($recent_logs)): ?>
                    <p class="skylearn-billing-status-empty"><?php esc_html_e('No automation logs found.', 'skylearn-billing-pro'); ?></p>
                <?php else: ?>
                    <table class="skylearn-billing-status-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Automation', 'skylearn-billing-pro'); ?></th>
                                <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                                <th><?php esc_html_e('Execution Time', 'skylearn-billing-pro'); ?></th>
                                <th><?php esc_html_e('Triggered At', 'skylearn-billing-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_logs as $log): ?>
                                <tr>
                                    <td><?php echo esc_html($log['automation_name'] ?? 'Unknown'); ?></td>
                                    <td>
                                        <span class="skylearn-billing-status-badge <?php echo esc_attr($log['status']); ?>">
                                            <?php echo esc_html(ucfirst($log['status'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($log['execution_time_ms']); ?>ms</td>
                                    <td><?php echo esc_html(date_i18n(get_option('time_format'), strtotime($log['triggered_at']))); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="skylearn-billing-status-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-automation&action=logs')); ?>" class="button button-secondary">
                            <?php esc_html_e('View All Logs', 'skylearn-billing-pro'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-automation')); ?>" class="button button-primary">
                            <?php esc_html_e('Manage Automations', 'skylearn-billing-pro'); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- System Requirements Section -->
    <div class="skylearn-billing-status-section">
        <h3><?php esc_html_e('System Requirements', 'skylearn-billing-pro'); ?></h3>
        
        <div class="skylearn-billing-status-card">
            <table class="skylearn-billing-status-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Check', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Current', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Required', 'skylearn-billing-pro'); ?></th>
                        <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($system_checks as $check): ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html($check['name']); ?></strong>
                                <div class="skylearn-billing-check-description"><?php echo esc_html($check['description']); ?></div>
                            </td>
                            <td><?php echo esc_html($check['value']); ?></td>
                            <td><?php echo esc_html($check['required']); ?></td>
                            <td>
                                <span class="skylearn-billing-check-status skylearn-billing-check-<?php echo esc_attr($check['status']); ?>">
                                    <?php
                                    switch ($check['status']) {
                                        case 'pass':
                                            echo '<span class="dashicons dashicons-yes-alt"></span> ' . __('Pass', 'skylearn-billing-pro');
                                            break;
                                        case 'warning':
                                            echo '<span class="dashicons dashicons-warning"></span> ' . __('Warning', 'skylearn-billing-pro');
                                            break;
                                        case 'fail':
                                            echo '<span class="dashicons dashicons-no-alt"></span> ' . __('Fail', 'skylearn-billing-pro');
                                            break;
                                    }
                                    ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Debug Information Section -->
    <div class="skylearn-billing-status-section">
        <h3><?php esc_html_e('Debug Information', 'skylearn-billing-pro'); ?></h3>
        
        <div class="skylearn-billing-status-card">
            <div class="skylearn-billing-debug-info">
                <h4><?php esc_html_e('Plugin Information', 'skylearn-billing-pro'); ?></h4>
                <ul>
                    <li><strong><?php esc_html_e('Plugin Version:', 'skylearn-billing-pro'); ?></strong> <?php echo esc_html(SKYLEARN_BILLING_VERSION); ?></li>
                    <li><strong><?php esc_html_e('Plugin Directory:', 'skylearn-billing-pro'); ?></strong> <?php echo esc_html(SKYLEARN_BILLING_PLUGIN_DIR); ?></li>
                    <li><strong><?php esc_html_e('Upload Directory Writable:', 'skylearn-billing-pro'); ?></strong> 
                        <?php echo wp_is_writable(wp_upload_dir()['path']) ? __('Yes', 'skylearn-billing-pro') : __('No', 'skylearn-billing-pro'); ?>
                    </li>
                    <li><strong><?php esc_html_e('Debug Mode:', 'skylearn-billing-pro'); ?></strong> 
                        <?php echo defined('WP_DEBUG') && WP_DEBUG ? __('Enabled', 'skylearn-billing-pro') : __('Disabled', 'skylearn-billing-pro'); ?>
                    </li>
                </ul>
                
                <?php if (!empty($access_log)): ?>
                    <h4><?php esc_html_e('Recent Addon Access Log', 'skylearn-billing-pro'); ?></h4>
                    <div class="skylearn-billing-access-log">
                        <?php foreach (array_slice(array_reverse($access_log), 0, 10) as $log_entry): ?>
                            <div class="skylearn-billing-log-entry">
                                <span class="skylearn-billing-log-time">
                                    <?php echo esc_html(date_i18n(get_option('time_format'), $log_entry['timestamp'])); ?>
                                </span>
                                <span class="skylearn-billing-log-addon"><?php echo esc_html($log_entry['addon_id']); ?></span>
                                <span class="skylearn-billing-log-status <?php echo $log_entry['access_granted'] ? 'granted' : 'denied'; ?>">
                                    <?php echo $log_entry['access_granted'] ? __('GRANTED', 'skylearn-billing-pro') : __('DENIED', 'skylearn-billing-pro'); ?>
                                </span>
                                <?php if (!empty($log_entry['reason'])): ?>
                                    <span class="skylearn-billing-log-reason">(<?php echo esc_html($log_entry['reason']); ?>)</span>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        
                        <button type="button" id="clear-access-log" class="skylearn-billing-btn skylearn-billing-btn-secondary">
                            <?php esc_html_e('Clear Log', 'skylearn-billing-pro'); ?>
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Actions Section -->
    <div class="skylearn-billing-status-section">
        <h3><?php esc_html_e('System Actions', 'skylearn-billing-pro'); ?></h3>
        
        <div class="skylearn-billing-status-card">
            <div class="skylearn-billing-status-actions">
                <button type="button" id="refresh-status" class="skylearn-billing-btn skylearn-billing-btn-primary">
                    <?php esc_html_e('Refresh Status', 'skylearn-billing-pro'); ?>
                </button>
                
                <button type="button" id="export-status" class="skylearn-billing-btn skylearn-billing-btn-secondary">
                    <?php esc_html_e('Export Status Report', 'skylearn-billing-pro'); ?>
                </button>
                
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')); ?>" class="skylearn-billing-btn skylearn-billing-btn-secondary">
                    <?php esc_html_e('Manage License', 'skylearn-billing-pro'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Refresh status
    $('#refresh-status').on('click', function() {
        location.reload();
    });
    
    // Export status report
    $('#export-status').on('click', function() {
        // This would generate and download a status report
        alert('<?php esc_html_e('Status export functionality would be implemented here.', 'skylearn-billing-pro'); ?>');
    });
    
    // Clear access log
    $('#clear-access-log').on('click', function() {
        if (confirm('<?php esc_html_e('Are you sure you want to clear the access log?', 'skylearn-billing-pro'); ?>')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'skylearn_billing_clear_access_log',
                    nonce: '<?php echo wp_create_nonce('skylearn_billing_status_action'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    }
                }
            });
        }
    });
});
</script>

<style>
.skylearn-billing-status-page {
    max-width: 1200px;
}

.skylearn-billing-status-header {
    margin-bottom: 30px;
}

.skylearn-billing-status-section {
    margin-bottom: 30px;
}

.skylearn-billing-status-section h3 {
    margin: 0 0 15px 0;
    color: #23282d;
    border-bottom: 2px solid #e1e1e1;
    padding-bottom: 10px;
}

.skylearn-billing-status-card {
    background: #fff;
    border: 1px solid #e1e1e1;
    border-radius: 8px;
    padding: 20px;
}

.skylearn-billing-status-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.skylearn-billing-status-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
}

.skylearn-billing-status-label {
    font-weight: 600;
    color: #555;
}

.skylearn-billing-status-value {
    font-weight: 600;
}

.skylearn-billing-status-active {
    color: #4caf50;
}

.skylearn-billing-status-inactive {
    color: #f44336;
}

.skylearn-billing-tier-free {
    color: #4caf50;
}

.skylearn-billing-tier-pro {
    color: #2196f3;
}

.skylearn-billing-tier-pro_plus {
    color: #ff9800;
}

.skylearn-billing-status-summary {
    display: flex;
    gap: 30px;
    margin-bottom: 25px;
}

.skylearn-billing-status-stat {
    text-align: center;
}

.skylearn-billing-stat-number {
    display: block;
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
}

.skylearn-billing-stat-label {
    display: block;
    color: #666;
    font-size: 14px;
}

.skylearn-billing-status-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.skylearn-billing-status-table th,
.skylearn-billing-status-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e1e1e1;
}

.skylearn-billing-status-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.skylearn-billing-check-description {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
}

.skylearn-billing-check-status {
    display: flex;
    align-items: center;
    gap: 5px;
}

.skylearn-billing-check-pass {
    color: #4caf50;
}

.skylearn-billing-check-warning {
    color: #ff9800;
}

.skylearn-billing-check-fail {
    color: #f44336;
}

.skylearn-billing-license-status.valid {
    color: #4caf50;
    font-weight: 600;
}

.skylearn-billing-license-status.invalid {
    color: #f44336;
    font-weight: 600;
}

.skylearn-billing-license-message {
    font-size: 12px;
    color: #666;
    margin-top: 3px;
}

.skylearn-billing-debug-info ul {
    list-style: none;
    padding: 0;
}

.skylearn-billing-debug-info li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.skylearn-billing-access-log {
    background: #f8f9fa;
    border-radius: 6px;
    padding: 15px;
    margin-top: 10px;
}

.skylearn-billing-log-entry {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 5px 0;
    font-family: monospace;
    font-size: 12px;
}

.skylearn-billing-log-time {
    color: #666;
}

.skylearn-billing-log-addon {
    font-weight: 600;
}

.skylearn-billing-log-status.granted {
    color: #4caf50;
    font-weight: 600;
}

.skylearn-billing-log-status.denied {
    color: #f44336;
    font-weight: 600;
}

.skylearn-billing-log-reason {
    color: #666;
    font-style: italic;
}

.skylearn-billing-status-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.skylearn-billing-status-empty {
    color: #666;
    font-style: italic;
    text-align: center;
    padding: 20px;
}

.skylearn-billing-addon-status-list h4 {
    margin: 20px 0 10px 0;
    color: #23282d;
}

.skylearn-billing-btn {
    padding: 8px 16px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
    text-decoration: none;
    display: inline-block;
    transition: background-color 0.2s ease;
}

.skylearn-billing-btn-primary {
    background: #0073aa;
    color: white;
}

.skylearn-billing-btn-primary:hover {
    background: #005a87;
}

.skylearn-billing-btn-secondary {
    background: #666;
    color: white;
}

.skylearn-billing-btn-secondary:hover {
    background: #555;
}

.skylearn-billing-addon-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.skylearn-billing-addon-free {
    background: #4caf50;
    color: white;
}

.skylearn-billing-addon-pro {
    background: #2196f3;
    color: white;
}

.skylearn-billing-addon-plus {
    background: #ff9800;
    color: white;
}
</style>