<?php
/**
 * Audit Log Admin Template
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

// Check user capabilities
if (!current_user_can('skylearn_view_logs')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'skylearn-billing-pro'));
}

// Get audit logger instance
$audit_logger = skylearn_billing_pro_audit_logger();

// Handle filters
$filters = array();
if (isset($_GET['type']) && !empty($_GET['type'])) {
    $filters['type'] = sanitize_text_field($_GET['type']);
}
if (isset($_GET['user_id']) && !empty($_GET['user_id'])) {
    $filters['user_id'] = intval($_GET['user_id']);
}
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = sanitize_text_field($_GET['date_from']);
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = sanitize_text_field($_GET['date_to']);
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = sanitize_text_field($_GET['search']);
}

// Pagination
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$per_page = 20;
$offset = ($current_page - 1) * $per_page;

// Get logs
$logs = $audit_logger->get_logs($filters, $per_page, $offset);
$total_logs = $audit_logger->get_logs_count($filters);
$total_pages = ceil($total_logs / $per_page);

// Get available log types
$log_types = array(
    '' => __('All Types', 'skylearn-billing-pro'),
    'admin' => __('Admin Actions', 'skylearn-billing-pro'),
    'user' => __('User Actions', 'skylearn-billing-pro'),
    'payment' => __('Payment Events', 'skylearn-billing-pro'),
    'enrollment' => __('Enrollment Events', 'skylearn-billing-pro'),
    'security' => __('Security Events', 'skylearn-billing-pro'),
);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <?php _e('Audit Logs', 'skylearn-billing-pro'); ?>
    </h1>
    
    <hr class="wp-header-end">
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                
                <select name="type" id="filter-by-type">
                    <?php foreach ($log_types as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($filters['type'] ?? '', $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <input type="date" name="date_from" value="<?php echo esc_attr($filters['date_from'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('From Date', 'skylearn-billing-pro'); ?>">
                
                <input type="date" name="date_to" value="<?php echo esc_attr($filters['date_to'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('To Date', 'skylearn-billing-pro'); ?>">
                
                <?php submit_button(__('Filter', 'skylearn-billing-pro'), 'secondary', 'filter', false); ?>
                
                <?php if (!empty($filters)) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $_GET['page'])); ?>" class="button">
                        <?php _e('Clear Filters', 'skylearn-billing-pro'); ?>
                    </a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="alignright actions">
            <form method="get" action="">
                <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page']); ?>">
                <?php foreach ($filters as $key => $value) : ?>
                    <input type="hidden" name="<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($value); ?>">
                <?php endforeach; ?>
                
                <input type="search" name="search" value="<?php echo esc_attr($filters['search'] ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Search logs...', 'skylearn-billing-pro'); ?>">
                <?php submit_button(__('Search', 'skylearn-billing-pro'), 'secondary', 'search', false); ?>
            </form>
        </div>
    </div>
    
    <!-- Logs Table -->
    <table class="wp-list-table widefat fixed striped" id="audit-logs-table">
        <thead>
            <tr>
                <th scope="col" class="manage-column column-timestamp">
                    <?php _e('Timestamp', 'skylearn-billing-pro'); ?>
                </th>
                <th scope="col" class="manage-column column-user">
                    <?php _e('User', 'skylearn-billing-pro'); ?>
                </th>
                <th scope="col" class="manage-column column-type">
                    <?php _e('Type', 'skylearn-billing-pro'); ?>
                </th>
                <th scope="col" class="manage-column column-action">
                    <?php _e('Action', 'skylearn-billing-pro'); ?>
                </th>
                <th scope="col" class="manage-column column-details">
                    <?php _e('Details', 'skylearn-billing-pro'); ?>
                </th>
                <th scope="col" class="manage-column column-ip">
                    <?php _e('IP Address', 'skylearn-billing-pro'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($logs)) : ?>
                <?php foreach ($logs as $log) : ?>
                    <tr>
                        <td class="column-timestamp">
                            <strong><?php echo esc_html(mysql2date('Y/m/d g:i:s A', $log['timestamp'])); ?></strong>
                        </td>
                        <td class="column-user">
                            <?php if ($log['user_id']) : ?>
                                <?php $user = get_user_by('id', $log['user_id']); ?>
                                <?php if ($user) : ?>
                                    <a href="<?php echo esc_url(admin_url('user-edit.php?user_id=' . $log['user_id'])); ?>">
                                        <?php echo esc_html($user->display_name); ?>
                                    </a>
                                    <br><small><?php echo esc_html($user->user_email); ?></small>
                                <?php else : ?>
                                    <?php _e('Unknown User', 'skylearn-billing-pro'); ?>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php if ($log['user_email']) : ?>
                                    <?php echo esc_html($log['user_email']); ?>
                                <?php else : ?>
                                    <?php _e('System', 'skylearn-billing-pro'); ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="column-type">
                            <span class="log-type log-type-<?php echo esc_attr($log['log_type']); ?>">
                                <?php echo esc_html(ucfirst($log['log_type'])); ?>
                            </span>
                        </td>
                        <td class="column-action">
                            <strong><?php echo esc_html($log['action']); ?></strong>
                            <?php if ($log['object_type']) : ?>
                                <br><small><?php printf(__('Object: %s', 'skylearn-billing-pro'), esc_html($log['object_type'])); ?></small>
                            <?php endif; ?>
                        </td>
                        <td class="column-details">
                            <?php if (!empty($log['details'])) : ?>
                                <button type="button" class="button button-small view-details" 
                                        data-details="<?php echo esc_attr(json_encode($log['details'])); ?>">
                                    <?php _e('View Details', 'skylearn-billing-pro'); ?>
                                </button>
                            <?php endif; ?>
                        </td>
                        <td class="column-ip">
                            <?php echo esc_html($log['ip_address']); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="6" class="no-items">
                        <?php _e('No audit logs found.', 'skylearn-billing-pro'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1) : ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <span class="displaying-num">
                    <?php printf(_n('%s item', '%s items', $total_logs, 'skylearn-billing-pro'), number_format_i18n($total_logs)); ?>
                </span>
                <?php
                $pagination_args = array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => __('&laquo;'),
                    'next_text' => __('&raquo;'),
                    'total' => $total_pages,
                    'current' => $current_page,
                    'add_args' => $filters
                );
                echo paginate_links($pagination_args);
                ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Details Modal -->
<div id="log-details-modal" class="skylearn-modal" style="display: none;">
    <div class="skylearn-modal-content">
        <div class="skylearn-modal-header">
            <h3><?php _e('Log Details', 'skylearn-billing-pro'); ?></h3>
            <span class="skylearn-modal-close">&times;</span>
        </div>
        <div class="skylearn-modal-body">
            <pre id="log-details-content"></pre>
        </div>
    </div>
</div>

<style>
.log-type {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
}

.log-type-admin {
    background-color: #0073aa;
    color: white;
}

.log-type-user {
    background-color: #00a32a;
    color: white;
}

.log-type-payment {
    background-color: #d63638;
    color: white;
}

.log-type-enrollment {
    background-color: #ff8c00;
    color: white;
}

.log-type-security {
    background-color: #8c8c8c;
    color: white;
}

.skylearn-modal {
    position: fixed;
    z-index: 999999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.skylearn-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 0;
    border: 1px solid #888;
    width: 80%;
    max-width: 600px;
    border-radius: 4px;
}

.skylearn-modal-header {
    padding: 15px 20px;
    background-color: #f1f1f1;
    border-bottom: 1px solid #ddd;
    border-radius: 4px 4px 0 0;
}

.skylearn-modal-header h3 {
    margin: 0;
    display: inline-block;
}

.skylearn-modal-close {
    float: right;
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
    cursor: pointer;
}

.skylearn-modal-close:hover {
    color: #000;
}

.skylearn-modal-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

#log-details-content {
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    padding: 10px;
    border-radius: 3px;
    white-space: pre-wrap;
    word-wrap: break-word;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle view details button
    $('.view-details').on('click', function() {
        var details = $(this).data('details');
        $('#log-details-content').text(JSON.stringify(details, null, 2));
        $('#log-details-modal').show();
    });
    
    // Handle modal close
    $('.skylearn-modal-close, .skylearn-modal').on('click', function(e) {
        if (e.target === this) {
            $('#log-details-modal').hide();
        }
    });
    
    // Prevent modal content click from closing modal
    $('.skylearn-modal-content').on('click', function(e) {
        e.stopPropagation();
    });
});
</script>