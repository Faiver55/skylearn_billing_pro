<?php
/**
 * Page Setup for SkyLearn Billing Pro
 *
 * Handles setup and management of frontend pages
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
 * Page Setup class
 */
class SkyLearn_Billing_Pro_Page_Setup {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register AJAX handlers early to ensure they're available
        add_action('wp_ajax_skylearn_page_setup_action', array($this, 'handle_ajax_actions'));
        add_action('wp_ajax_nopriv_skylearn_page_setup_action', array($this, 'handle_ajax_actions'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Register activation hook for automatic page creation
        add_action('skylearn_billing_pro_activate', array($this, 'maybe_create_pages'));
        
        // Add body classes for our pages
        add_filter('body_class', array($this, 'add_body_classes'));
        
        // Add custom template hooks
        add_action('wp_head', array($this, 'add_custom_styles'));
        
        // Handle page access restrictions
        add_action('template_redirect', array($this, 'handle_page_access'));
    }
    
    /**
     * Add admin menu for page setup
     */
    public function add_admin_menu() {
        add_submenu_page(
            'skylearn-billing-pro',
            __('Page Setup', 'skylearn-billing-pro'),
            __('Page Setup', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-page-setup',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Maybe create pages on activation
     */
    public function maybe_create_pages() {
        $pages_created = get_option('skylearn_billing_pro_pages_created', false);
        
        if (!$pages_created) {
            $this->create_pages_with_wizard();
            update_option('skylearn_billing_pro_pages_created', true);
        }
    }
    
    /**
     * Create pages with setup wizard
     */
    public function create_pages_with_wizard() {
        $page_generator = skylearn_billing_pro_page_generator();
        $results = $page_generator->create_pages();
        
        // Store setup completion
        update_option('skylearn_billing_pro_setup_completed', time());
        
        // Add admin notice about created pages
        if ($results['created'] > 0) {
            $message = sprintf(
                _n(
                    '%d page has been created for SkyLearn Billing Pro.',
                    '%d pages have been created for SkyLearn Billing Pro.',
                    $results['created'],
                    'skylearn-billing-pro'
                ),
                $results['created']
            );
            
            set_transient('skylearn_billing_pro_pages_created_notice', $message, 300);
        }
        
        return $results;
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        $page_generator = skylearn_billing_pro_page_generator();
        $pages_status = $page_generator->check_pages_status();
        $created_pages = $page_generator->get_created_pages();
        
        ?>
        <div class="wrap">
            <h1><?php _e('Page Setup', 'skylearn-billing-pro'); ?></h1>
            
            <div class="skylearn-admin-content">
                <div class="skylearn-page-setup-container">
                    
                    <!-- Setup Status -->
                    <div class="skylearn-card">
                        <h2><?php _e('Setup Status', 'skylearn-billing-pro'); ?></h2>
                        
                        <?php
                        $total_pages = count($pages_status);
                        $existing_pages = count(array_filter($pages_status, function($page) { return $page['exists']; }));
                        $completion_percentage = $total_pages > 0 ? round(($existing_pages / $total_pages) * 100) : 0;
                        ?>
                        
                        <div class="skylearn-progress-container">
                            <div class="skylearn-progress-bar">
                                <div class="skylearn-progress-fill" style="width: <?php echo esc_attr($completion_percentage); ?>%"></div>
                            </div>
                            <span class="skylearn-progress-text">
                                <?php printf(__('%d%% Complete (%d of %d pages)', 'skylearn-billing-pro'), $completion_percentage, $existing_pages, $total_pages); ?>
                            </span>
                        </div>
                        
                        <?php if ($completion_percentage < 100): ?>
                            <div class="skylearn-setup-actions">
                                <button type="button" class="button button-primary" id="skylearn-create-all-pages">
                                    <?php _e('Create Missing Pages', 'skylearn-billing-pro'); ?>
                                </button>
                                <button type="button" class="button" id="skylearn-recreate-all-pages">
                                    <?php _e('Recreate All Pages', 'skylearn-billing-pro'); ?>
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Pages List -->
                    <div class="skylearn-card">
                        <h2><?php _e('Frontend Pages', 'skylearn-billing-pro'); ?></h2>
                        
                        <div class="skylearn-pages-table">
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php _e('Page', 'skylearn-billing-pro'); ?></th>
                                        <th scope="col"><?php _e('Status', 'skylearn-billing-pro'); ?></th>
                                        <th scope="col"><?php _e('URL', 'skylearn-billing-pro'); ?></th>
                                        <th scope="col"><?php _e('Actions', 'skylearn-billing-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pages_status as $page_key => $page_info): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo esc_html($page_info['title']); ?></strong><br>
                                                <code><?php echo esc_html($page_info['slug']); ?></code>
                                            </td>
                                            <td>
                                                <?php if ($page_info['exists']): ?>
                                                    <span class="skylearn-status skylearn-status-success">
                                                        <?php _e('Created', 'skylearn-billing-pro'); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="skylearn-status skylearn-status-warning">
                                                        <?php _e('Missing', 'skylearn-billing-pro'); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($page_info['url']): ?>
                                                    <a href="<?php echo esc_url($page_info['url']); ?>" target="_blank">
                                                        <?php _e('View Page', 'skylearn-billing-pro'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="description"><?php _e('Not available', 'skylearn-billing-pro'); ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($page_info['exists']): ?>
                                                    <button type="button" class="button button-small skylearn-recreate-page" data-page="<?php echo esc_attr($page_key); ?>">
                                                        <?php _e('Recreate', 'skylearn-billing-pro'); ?>
                                                    </button>
                                                    <a href="<?php echo esc_url(get_edit_post_link($page_info['page_id'])); ?>" class="button button-small">
                                                        <?php _e('Edit', 'skylearn-billing-pro'); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="button button-small button-primary skylearn-create-page" data-page="<?php echo esc_attr($page_key); ?>">
                                                        <?php _e('Create', 'skylearn-billing-pro'); ?>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    
                    <!-- Page Settings -->
                    <div class="skylearn-card">
                        <h2><?php _e('Page Settings', 'skylearn-billing-pro'); ?></h2>
                        
                        <form method="post" action="options.php">
                            <?php settings_fields('skylearn_billing_pro_page_settings'); ?>
                            
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Theme Compatibility Mode', 'skylearn-billing-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="skylearn_billing_pro_theme_compat" value="1" <?php checked(get_option('skylearn_billing_pro_theme_compat', false)); ?> />
                                            <?php _e('Enable enhanced theme compatibility', 'skylearn-billing-pro'); ?>
                                        </label>
                                        <p class="description"><?php _e('Adds extra CSS and JavaScript to ensure better compatibility with different themes.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Responsive Design', 'skylearn-billing-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="skylearn_billing_pro_responsive" value="1" <?php checked(get_option('skylearn_billing_pro_responsive', true)); ?> />
                                            <?php _e('Enable responsive design features', 'skylearn-billing-pro'); ?>
                                        </label>
                                        <p class="description"><?php _e('Ensures all pages work well on mobile devices.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Accessibility Features', 'skylearn-billing-pro'); ?></th>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="skylearn_billing_pro_accessibility" value="1" <?php checked(get_option('skylearn_billing_pro_accessibility', true)); ?> />
                                            <?php _e('Enable accessibility enhancements', 'skylearn-billing-pro'); ?>
                                        </label>
                                        <p class="description"><?php _e('Adds ARIA labels, keyboard navigation, and other accessibility features.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Custom CSS', 'skylearn-billing-pro'); ?></th>
                                    <td>
                                        <textarea name="skylearn_billing_pro_custom_css" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(get_option('skylearn_billing_pro_custom_css', '')); ?></textarea>
                                        <p class="description"><?php _e('Add custom CSS to customize the appearance of frontend pages.', 'skylearn-billing-pro'); ?></p>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php submit_button(); ?>
                        </form>
                    </div>
                    
                    <!-- Advanced Actions -->
                    <div class="skylearn-card">
                        <h2><?php _e('Advanced Actions', 'skylearn-billing-pro'); ?></h2>
                        
                        <div class="skylearn-advanced-actions">
                            <button type="button" class="button button-secondary" id="skylearn-reset-pages">
                                <?php _e('Delete All Pages', 'skylearn-billing-pro'); ?>
                            </button>
                            <p class="description"><?php _e('This will permanently delete all SkyLearn Billing Pro pages. Use with caution!', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>
                    
                </div>
            </div>
        </div>
        
        <style>
        .skylearn-admin-content {
            max-width: 1200px;
        }
        
        .skylearn-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .skylearn-card h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        .skylearn-progress-container {
            margin: 15px 0;
        }
        
        .skylearn-progress-bar {
            background: #e1e1e1;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        
        .skylearn-progress-fill {
            background: linear-gradient(90deg, #00a32a, #4ade80);
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .skylearn-progress-text {
            font-size: 14px;
            color: #666;
        }
        
        .skylearn-setup-actions {
            margin-top: 15px;
        }
        
        .skylearn-setup-actions .button {
            margin-right: 10px;
        }
        
        .skylearn-status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .skylearn-status-success {
            background: #d1fae5;
            color: #065f46;
        }
        
        .skylearn-status-warning {
            background: #fef3c7;
            color: #92400e;
        }
        
        .skylearn-advanced-actions {
            padding: 15px;
            background: #fff2cd;
            border: 1px solid #f0ad4e;
            border-radius: 4px;
        }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Create all pages
            $('#skylearn-create-all-pages').on('click', function() {
                $(this).prop('disabled', true).text('<?php esc_js(_e('Creating...', 'skylearn-billing-pro')); ?>');
                performPageAction('create_all');
            });
            
            // Recreate all pages
            $('#skylearn-recreate-all-pages').on('click', function() {
                if (confirm('<?php esc_js(_e('This will recreate all pages. Any custom changes will be lost. Continue?', 'skylearn-billing-pro')); ?>')) {
                    $(this).prop('disabled', true).text('<?php esc_js(_e('Recreating...', 'skylearn-billing-pro')); ?>');
                    performPageAction('recreate_all');
                }
            });
            
            // Create individual page
            $('.skylearn-create-page').on('click', function() {
                var $button = $(this);
                var pageKey = $button.data('page');
                $button.prop('disabled', true).text('<?php esc_js(_e('Creating...', 'skylearn-billing-pro')); ?>');
                performPageAction('create_page', {page: pageKey});
            });
            
            // Recreate individual page
            $('.skylearn-recreate-page').on('click', function() {
                var $button = $(this);
                var pageKey = $button.data('page');
                if (confirm('<?php esc_js(_e('This will recreate the page. Any custom changes will be lost. Continue?', 'skylearn-billing-pro')); ?>')) {
                    $button.prop('disabled', true).text('<?php esc_js(_e('Recreating...', 'skylearn-billing-pro')); ?>');
                    performPageAction('recreate_page', {page: pageKey});
                }
            });
            
            // Reset all pages
            $('#skylearn-reset-pages').on('click', function() {
                if (confirm('<?php esc_js(_e('This will permanently delete all SkyLearn Billing Pro pages. This action cannot be undone. Are you sure?', 'skylearn-billing-pro')); ?>')) {
                    $(this).prop('disabled', true).text('<?php esc_js(_e('Deleting...', 'skylearn-billing-pro')); ?>');
                    performPageAction('delete_all');
                }
            });
            
            function performPageAction(action, data = {}) {
                console.log('SkyLearn Page Setup: Performing action', action, data);
                
                $.post(ajaxurl, {
                    action: 'skylearn_page_setup_action',
                    nonce: '<?php echo wp_create_nonce('skylearn_page_setup_nonce'); ?>',
                    page_action: action,
                    ...data
                }, function(response) {
                    console.log('SkyLearn Page Setup: AJAX response', response);
                    
                    if (response.success) {
                        // Show success message before reload
                        if (response.data && response.data.created) {
                            alert('<?php esc_js(_e('Success!', 'skylearn-billing-pro')); ?> ' + response.data.created + ' <?php esc_js(_e('pages processed.', 'skylearn-billing-pro')); ?>');
                        }
                        location.reload();
                    } else {
                        console.error('SkyLearn Page Setup: Action failed', response);
                        alert(response.data.message || '<?php esc_js(_e('An error occurred', 'skylearn-billing-pro')); ?>');
                        location.reload();
                    }
                }).fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('SkyLearn Page Setup: AJAX failed', {
                        status: jqXHR.status,
                        statusText: jqXHR.statusText,
                        textStatus: textStatus,
                        errorThrown: errorThrown,
                        responseText: jqXHR.responseText
                    });
                    
                    if (jqXHR.status === 404) {
                        alert('<?php esc_js(_e('Error: AJAX handler not found (404). Please check that the plugin is properly activated.', 'skylearn-billing-pro')); ?>');
                    } else if (jqXHR.status === 500) {
                        alert('<?php esc_js(_e('Server error (500). Please check the error logs for details.', 'skylearn-billing-pro')); ?>');
                    } else {
                        alert('<?php esc_js(_e('Network error occurred', 'skylearn-billing-pro')); ?>' + ' (Status: ' + jqXHR.status + ')');
                    }
                    location.reload();
                });
            }
        });
        </script>
        <?php
    }
    
    /**
     * Handle AJAX actions
     */
    public function handle_ajax_actions() {
        // Add debugging log entry
        error_log('SkyLearn Billing Pro: AJAX handler called for skylearn_page_setup_action');
        
        // Check if this is an admin request
        if (!is_admin() && !wp_doing_ajax()) {
            error_log('SkyLearn Billing Pro: AJAX handler called outside admin context');
            wp_send_json_error(array('message' => __('Invalid request context', 'skylearn-billing-pro')));
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            error_log('SkyLearn Billing Pro: AJAX handler - insufficient permissions for user ' . get_current_user_id());
            wp_send_json_error(array('message' => __('Insufficient permissions', 'skylearn-billing-pro')));
        }
        
        // Verify nonce
        $nonce = sanitize_text_field($_POST['nonce'] ?? '');
        if (!wp_verify_nonce($nonce, 'skylearn_page_setup_nonce')) {
            error_log('SkyLearn Billing Pro: AJAX handler - nonce verification failed. Provided: ' . $nonce);
            wp_send_json_error(array('message' => __('Invalid nonce', 'skylearn-billing-pro')));
        }
        
        $action = sanitize_text_field($_POST['page_action'] ?? '');
        error_log('SkyLearn Billing Pro: AJAX handler - processing action: ' . $action);
        
        // Check if page generator function exists
        if (!function_exists('skylearn_billing_pro_page_generator')) {
            error_log('SkyLearn Billing Pro: AJAX handler - page generator function not found');
            wp_send_json_error(array('message' => __('Page generator not available', 'skylearn-billing-pro')));
        }
        
        // Get page generator instance
        try {
            $page_generator = skylearn_billing_pro_page_generator();
            if (!$page_generator) {
                throw new Exception('Page generator instance is null');
            }
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: AJAX handler - failed to get page generator: ' . $e->getMessage());
            wp_send_json_error(array('message' => __('Failed to initialize page generator', 'skylearn-billing-pro')));
        }
        
        // Process the action
        try {
            switch ($action) {
                case 'create_all':
                    error_log('SkyLearn Billing Pro: Creating all pages');
                    $results = $page_generator->create_pages(false);
                    error_log('SkyLearn Billing Pro: Create all pages result: ' . json_encode($results));
                    wp_send_json_success($results);
                    break;
                    
                case 'recreate_all':
                    error_log('SkyLearn Billing Pro: Recreating all pages');
                    $results = $page_generator->create_pages(true);
                    error_log('SkyLearn Billing Pro: Recreate all pages result: ' . json_encode($results));
                    wp_send_json_success($results);
                    break;
                    
                case 'create_page':
                case 'recreate_page':
                    $page_key = sanitize_text_field($_POST['page'] ?? '');
                    if (empty($page_key)) {
                        error_log('SkyLearn Billing Pro: AJAX handler - page key is required for action: ' . $action);
                        wp_send_json_error(array('message' => __('Page key is required', 'skylearn-billing-pro')));
                    }
                    
                    error_log('SkyLearn Billing Pro: Processing ' . $action . ' for page: ' . $page_key);
                    $force = ($action === 'recreate_page');
                    $results = $page_generator->create_pages($force);
                    error_log('SkyLearn Billing Pro: ' . ucfirst($action) . ' result: ' . json_encode($results));
                    wp_send_json_success($results);
                    break;
                    
                case 'delete_all':
                    error_log('SkyLearn Billing Pro: Deleting all pages');
                    $results = $page_generator->delete_pages();
                    error_log('SkyLearn Billing Pro: Delete all pages result: ' . json_encode($results));
                    wp_send_json_success($results);
                    break;
                    
                default:
                    error_log('SkyLearn Billing Pro: AJAX handler - invalid action: ' . $action);
                    wp_send_json_error(array('message' => __('Invalid action', 'skylearn-billing-pro')));
            }
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: AJAX handler - exception during action processing: ' . $e->getMessage());
            error_log('SkyLearn Billing Pro: AJAX handler - exception stack trace: ' . $e->getTraceAsString());
            wp_send_json_error(array('message' => __('An error occurred while processing the request', 'skylearn-billing-pro')));
        }
    }
    
    /**
     * Add body classes for our pages
     */
    public function add_body_classes($classes) {
        global $post;
        
        if ($post && get_post_meta($post->ID, '_skylearn_billing_page', true)) {
            $classes[] = 'skylearn-billing-page';
            $classes[] = 'skylearn-page-' . get_post_meta($post->ID, '_skylearn_billing_page', true);
            
            // Add accessibility class if enabled
            if (get_option('skylearn_billing_pro_accessibility', true)) {
                $classes[] = 'skylearn-accessibility-enabled';
            }
            
            // Add responsive class if enabled
            if (get_option('skylearn_billing_pro_responsive', true)) {
                $classes[] = 'skylearn-responsive-enabled';
            }
        }
        
        return $classes;
    }
    
    /**
     * Add custom styles to head
     */
    public function add_custom_styles() {
        global $post;
        
        // Only add styles on our pages
        if (!$post || !get_post_meta($post->ID, '_skylearn_billing_page', true)) {
            return;
        }
        
        $custom_css = get_option('skylearn_billing_pro_custom_css', '');
        $accessibility_enabled = get_option('skylearn_billing_pro_accessibility', true);
        $responsive_enabled = get_option('skylearn_billing_pro_responsive', true);
        
        ?>
        <style id="skylearn-billing-custom-styles">
        <?php if ($responsive_enabled): ?>
        /* Responsive Design */
        .skylearn-billing-page .skylearn-form-field,
        .skylearn-billing-page .skylearn-field-col {
            width: 100%;
            margin-bottom: 15px;
        }
        
        @media (min-width: 768px) {
            .skylearn-billing-page .skylearn-field-row {
                display: flex;
                gap: 20px;
            }
            
            .skylearn-billing-page .skylearn-field-col {
                flex: 1;
            }
        }
        
        .skylearn-billing-page .skylearn-dashboard-grid,
        .skylearn-billing-page .skylearn-downloads-grid,
        .skylearn-billing-page .skylearn-addresses-grid {
            display: grid;
            gap: 20px;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        }
        
        .skylearn-billing-page .skylearn-orders-table-container {
            overflow-x: auto;
        }
        
        @media (max-width: 768px) {
            .skylearn-billing-page .skylearn-orders-table,
            .skylearn-billing-page .skylearn-orders-table thead,
            .skylearn-billing-page .skylearn-orders-table tbody,
            .skylearn-billing-page .skylearn-orders-table th,
            .skylearn-billing-page .skylearn-orders-table td,
            .skylearn-billing-page .skylearn-orders-table tr {
                display: block;
            }
            
            .skylearn-billing-page .skylearn-orders-table thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .skylearn-billing-page .skylearn-orders-table tr {
                border: 1px solid #ccc;
                margin-bottom: 10px;
                padding: 10px;
            }
            
            .skylearn-billing-page .skylearn-orders-table td {
                border: none;
                position: relative;
                padding-left: 50% !important;
            }
            
            .skylearn-billing-page .skylearn-orders-table td:before {
                content: attr(data-label) ": ";
                position: absolute;
                left: 6px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
            }
        }
        <?php endif; ?>
        
        <?php if ($accessibility_enabled): ?>
        /* Accessibility Enhancements */
        .skylearn-billing-page *:focus {
            outline: 2px solid #005cee;
            outline-offset: 2px;
        }
        
        .skylearn-billing-page .skylearn-button:focus,
        .skylearn-billing-page .skylearn-input:focus {
            box-shadow: 0 0 0 3px rgba(0, 92, 238, 0.3);
        }
        
        .skylearn-billing-page .skylearn-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        
        .skylearn-billing-page [aria-disabled="true"] {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .skylearn-billing-page .skylearn-error,
        .skylearn-billing-page .skylearn-notice {
            padding: 12px 16px;
            margin: 10px 0;
            border-radius: 4px;
        }
        
        .skylearn-billing-page .skylearn-error {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }
        
        .skylearn-billing-page .skylearn-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #166534;
        }
        
        .skylearn-billing-page .skylearn-notice {
            background: #fefce8;
            border: 1px solid #fde68a;
            color: #92400e;
        }
        <?php endif; ?>
        
        <?php echo $custom_css; ?>
        </style>
        <?php
    }
    
    /**
     * Handle page access restrictions
     */
    public function handle_page_access() {
        global $post;
        
        if (!$post || !get_post_meta($post->ID, '_skylearn_billing_page', true)) {
            return;
        }
        
        $page_key = get_post_meta($post->ID, '_skylearn_billing_page', true);
        
        // Check if page requires login
        $protected_pages = array('portal', 'portal_dashboard', 'portal_orders', 'portal_plans', 'portal_downloads', 'portal_addresses', 'portal_account');
        
        if (in_array($page_key, $protected_pages) && !is_user_logged_in()) {
            wp_redirect(wp_login_url(get_permalink()));
            exit;
        }
    }
}

/**
 * Initialize page setup
 */
function skylearn_billing_pro_page_setup() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Page_Setup();
    }
    
    return $instance;
}