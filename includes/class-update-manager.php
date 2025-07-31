<?php
/**
 * Plugin Update Mechanism
 * 
 * Handles automatic updates for Skylearn Billing Pro plugin
 * Supports both WordPress.org and custom update servers
 *
 * @package SkyLearn_Billing_Pro
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Skylearn Billing Update Manager
 */
class Skylearn_Billing_Update_Manager {
    
    /**
     * Plugin file path
     */
    private $plugin_file;
    
    /**
     * Plugin slug
     */
    private $plugin_slug;
    
    /**
     * Version
     */
    private $version;
    
    /**
     * Update server URL (for custom updates)
     */
    private $update_server;
    
    /**
     * License key (for Pro updates)
     */
    private $license_key;
    
    /**
     * Constructor
     */
    public function __construct($plugin_file, $version, $update_server = '', $license_key = '') {
        $this->plugin_file = $plugin_file;
        $this->plugin_slug = plugin_basename($plugin_file);
        $this->version = $version;
        $this->update_server = $update_server;
        $this->license_key = $license_key;
        
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // For custom update server
        if (!empty($this->update_server)) {
            add_filter('pre_set_site_transient_update_plugins', array($this, 'check_for_updates'));
            add_filter('plugins_api', array($this, 'plugin_info'), 20, 3);
        }
        
        // Add changelog to plugin row
        add_action('in_plugin_update_message-' . $this->plugin_slug, array($this, 'show_changelog'), 10, 2);
        
        // Add update notifications
        add_action('admin_notices', array($this, 'update_notifications'));
        
        // Schedule update checks
        add_action('wp', array($this, 'schedule_update_checks'));
        add_action('skylearn_billing_check_updates', array($this, 'background_update_check'));
    }
    
    /**
     * Check for plugin updates
     */
    public function check_for_updates($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }
        
        // Get remote version info
        $remote_version = $this->get_remote_version();
        
        if (version_compare($this->version, $remote_version['version'], '<')) {
            $transient->response[$this->plugin_slug] = (object) array(
                'slug' => dirname($this->plugin_slug),
                'plugin' => $this->plugin_slug,
                'new_version' => $remote_version['version'],
                'url' => $remote_version['details_url'],
                'package' => $remote_version['download_url'],
                'tested' => $remote_version['tested'],
                'requires_php' => $remote_version['requires_php'],
                'compatibility' => array()
            );
        }
        
        return $transient;
    }
    
    /**
     * Get plugin information for update popup
     */
    public function plugin_info($result, $action = null, $args = null) {
        if ($action !== 'plugin_information' || 
            empty($args->slug) || 
            $args->slug !== dirname($this->plugin_slug)) {
            return $result;
        }
        
        $remote_version = $this->get_remote_version();
        
        return (object) array(
            'name' => $remote_version['name'],
            'slug' => dirname($this->plugin_slug),
            'version' => $remote_version['version'],
            'author' => $remote_version['author'],
            'homepage' => $remote_version['homepage'],
            'requires' => $remote_version['requires'],
            'tested' => $remote_version['tested'],
            'requires_php' => $remote_version['requires_php'],
            'last_updated' => $remote_version['last_updated'],
            'sections' => array(
                'description' => $remote_version['description'],
                'changelog' => $remote_version['changelog']
            ),
            'download_link' => $remote_version['download_url']
        );
    }
    
    /**
     * Get remote version information
     */
    private function get_remote_version() {
        $cache_key = 'skylearn_billing_remote_version';
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            return $cached;
        }
        
        // Default version info (for WordPress.org or fallback)
        $version_info = array(
            'version' => $this->version,
            'name' => 'Skylearn Billing Pro',
            'slug' => 'skylearn-billing-pro',
            'author' => 'Skyian LLC',
            'homepage' => 'https://skyian.com/skylearn-billing/',
            'requires' => '5.0',
            'tested' => '6.4',
            'requires_php' => '7.4',
            'last_updated' => current_time('Y-m-d'),
            'description' => 'Ultimate billing solution for WordPress course creators.',
            'changelog' => $this->get_changelog(),
            'details_url' => 'https://skyian.com/skylearn-billing/',
            'download_url' => ''
        );
        
        // If we have a custom update server, fetch from there
        if (!empty($this->update_server)) {
            $request = wp_remote_get($this->update_server . '?action=get_version&slug=' . dirname($this->plugin_slug) . '&license=' . $this->license_key);
            
            if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200) {
                $body = wp_remote_retrieve_body($request);
                $remote_info = json_decode($body, true);
                
                if ($remote_info && is_array($remote_info)) {
                    $version_info = array_merge($version_info, $remote_info);
                }
            }
        }
        
        // Cache for 12 hours
        set_transient($cache_key, $version_info, 12 * HOUR_IN_SECONDS);
        
        return $version_info;
    }
    
    /**
     * Show changelog in update notification
     */
    public function show_changelog($plugin_data, $response) {
        if (empty($response->new_version)) {
            return;
        }
        
        $changelog = $this->get_latest_changelog_entry();
        
        if (!empty($changelog)) {
            echo '<div class="skylearn-billing-update-message">';
            echo '<h4>' . sprintf(__('What\'s new in version %s:', 'skylearn-billing'), $response->new_version) . '</h4>';
            echo '<div class="skylearn-billing-changelog">' . wpautop($changelog) . '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Get latest changelog entry
     */
    private function get_latest_changelog_entry() {
        $changelog_file = SKYLEARN_BILLING_PLUGIN_DIR . 'CHANGELOG.md';
        
        if (!file_exists($changelog_file)) {
            return '';
        }
        
        $changelog = file_get_contents($changelog_file);
        
        // Extract the latest version entry
        if (preg_match('/## \[[\d\.]+\] - [\d\-]+\s*\n(.*?)(?=\n## \[|$)/s', $changelog, $matches)) {
            return trim($matches[1]);
        }
        
        return '';
    }
    
    /**
     * Get full changelog
     */
    private function get_changelog() {
        $changelog_file = SKYLEARN_BILLING_PLUGIN_DIR . 'CHANGELOG.md';
        
        if (!file_exists($changelog_file)) {
            return 'No changelog available.';
        }
        
        $changelog = file_get_contents($changelog_file);
        
        // Convert markdown to HTML (basic conversion)
        $changelog = preg_replace('/^## (.+)$/m', '<h3>$1</h3>', $changelog);
        $changelog = preg_replace('/^### (.+)$/m', '<h4>$1</h4>', $changelog);
        $changelog = preg_replace('/^\* (.+)$/m', '<li>$1</li>', $changelog);
        $changelog = preg_replace('/(<li>.*<\/li>)/s', '<ul>$1</ul>', $changelog);
        
        return $changelog;
    }
    
    /**
     * Show update notifications
     */
    public function update_notifications() {
        // Only show to administrators
        if (!current_user_can('update_plugins')) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'plugins') {
            return;
        }
        
        // Check if there's an update available
        $update_plugins = get_site_transient('update_plugins');
        
        if (empty($update_plugins->response[$this->plugin_slug])) {
            return;
        }
        
        $update_info = $update_plugins->response[$this->plugin_slug];
        
        ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <strong><?php _e('Skylearn Billing Pro Update Available', 'skylearn-billing'); ?></strong><br>
                <?php 
                printf(
                    __('Version %s is available. %sView details%s or %supdate now%s.', 'skylearn-billing'),
                    $update_info->new_version,
                    '<a href="' . esc_url(self_admin_url('plugin-install.php?tab=plugin-information&plugin=' . dirname($this->plugin_slug) . '&TB_iframe=true&width=600&height=550')) . '" class="thickbox">',
                    '</a>',
                    '<a href="' . esc_url(wp_nonce_url(self_admin_url('update.php?action=upgrade-plugin&plugin=' . $this->plugin_slug), 'upgrade-plugin_' . $this->plugin_slug)) . '">',
                    '</a>'
                );
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Schedule background update checks
     */
    public function schedule_update_checks() {
        if (!wp_next_scheduled('skylearn_billing_check_updates')) {
            wp_schedule_event(time(), 'daily', 'skylearn_billing_check_updates');
        }
    }
    
    /**
     * Background update check
     */
    public function background_update_check() {
        // Clear the cache to force a fresh check
        delete_transient('skylearn_billing_remote_version');
        
        // Trigger update check
        wp_update_plugins();
        
        // Log the check
        error_log('Skylearn Billing Pro: Background update check completed at ' . current_time('Y-m-d H:i:s'));
    }
    
    /**
     * Clear update caches
     */
    public function clear_update_cache() {
        delete_transient('skylearn_billing_remote_version');
        delete_site_transient('update_plugins');
    }
}

/**
 * Initialize the update manager
 */
function skylearn_billing_init_update_manager() {
    $update_server = get_option('skylearn_billing_update_server', '');
    $license_key = get_option('skylearn_billing_license_key', '');
    
    new Skylearn_Billing_Update_Manager(
        SKYLEARN_BILLING_PLUGIN_FILE,
        SKYLEARN_BILLING_VERSION,
        $update_server,
        $license_key
    );
}

// Initialize on plugins loaded
add_action('plugins_loaded', 'skylearn_billing_init_update_manager');