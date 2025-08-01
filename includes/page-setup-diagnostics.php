<?php
/**
 * Page Setup Diagnostics
 *
 * Utility to diagnose page setup issues
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
 * Page Setup Diagnostics class
 */
class SkyLearn_Billing_Pro_Page_Setup_Diagnostics {
    
    /**
     * Run diagnostics
     */
    public static function run_diagnostics() {
        $results = array(
            'ajax_handlers' => self::check_ajax_handlers(),
            'dependencies' => self::check_dependencies(),
            'pages_status' => self::check_pages_status(),
            'permissions' => self::check_permissions(),
            'nonce' => self::check_nonce_generation(),
            'error_logs' => self::get_recent_error_logs()
        );
        
        return $results;
    }
    
    /**
     * Check AJAX handlers registration
     */
    private static function check_ajax_handlers() {
        global $wp_filter;
        
        $results = array(
            'logged_in_handler' => isset($wp_filter['wp_ajax_skylearn_page_setup_action']),
            'logged_out_handler' => isset($wp_filter['wp_ajax_nopriv_skylearn_page_setup_action']),
            'handler_count' => 0,
            'callbacks' => array()
        );
        
        if ($results['logged_in_handler']) {
            $callbacks = $wp_filter['wp_ajax_skylearn_page_setup_action']->callbacks;
            $results['handler_count'] = count($callbacks);
            foreach ($callbacks as $priority => $callback_group) {
                foreach ($callback_group as $callback) {
                    $results['callbacks'][] = array(
                        'priority' => $priority,
                        'function' => is_array($callback['function']) ? 
                            get_class($callback['function'][0]) . '::' . $callback['function'][1] : 
                            $callback['function']
                    );
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Check required dependencies
     */
    private static function check_dependencies() {
        $results = array(
            'page_setup_class' => class_exists('SkyLearn_Billing_Pro_Page_Setup'),
            'page_generator_class' => class_exists('SkyLearn_Billing_Pro_Page_Generator'),
            'page_setup_function' => function_exists('skylearn_billing_pro_page_setup'),
            'page_generator_function' => function_exists('skylearn_billing_pro_page_generator'),
            'instances' => array()
        );
        
        if ($results['page_setup_function']) {
            try {
                $instance = skylearn_billing_pro_page_setup();
                $results['instances']['page_setup'] = array(
                    'created' => !is_null($instance),
                    'class' => $instance ? get_class($instance) : null,
                    'methods' => $instance ? get_class_methods($instance) : array()
                );
            } catch (Exception $e) {
                $results['instances']['page_setup'] = array(
                    'created' => false,
                    'error' => $e->getMessage()
                );
            }
        }
        
        if ($results['page_generator_function']) {
            try {
                $instance = skylearn_billing_pro_page_generator();
                $results['instances']['page_generator'] = array(
                    'created' => !is_null($instance),
                    'class' => $instance ? get_class($instance) : null,
                    'methods' => $instance ? get_class_methods($instance) : array()
                );
            } catch (Exception $e) {
                $results['instances']['page_generator'] = array(
                    'created' => false,
                    'error' => $e->getMessage()
                );
            }
        }
        
        return $results;
    }
    
    /**
     * Check pages status
     */
    private static function check_pages_status() {
        if (!function_exists('skylearn_billing_pro_page_generator')) {
            return array('error' => 'Page generator not available');
        }
        
        try {
            $page_generator = skylearn_billing_pro_page_generator();
            if (!$page_generator || !method_exists($page_generator, 'check_pages_status')) {
                return array('error' => 'Page generator not functional');
            }
            
            return $page_generator->check_pages_status();
        } catch (Exception $e) {
            return array('error' => $e->getMessage());
        }
    }
    
    /**
     * Check user permissions
     */
    private static function check_permissions() {
        return array(
            'current_user_id' => get_current_user_id(),
            'is_admin' => is_admin(),
            'wp_doing_ajax' => wp_doing_ajax(),
            'can_manage_options' => current_user_can('manage_options'),
            'user_roles' => wp_get_current_user()->roles ?? array()
        );
    }
    
    /**
     * Check nonce generation
     */
    private static function check_nonce_generation() {
        return array(
            'page_setup_nonce' => wp_create_nonce('skylearn_page_setup_nonce'),
            'current_time' => current_time('timestamp'),
            'wp_nonce_tick' => wp_nonce_tick()
        );
    }
    
    /**
     * Get recent error logs
     */
    private static function get_recent_error_logs() {
        $log_file = ini_get('error_log');
        if (!$log_file || !file_exists($log_file)) {
            return array('error' => 'Error log file not found');
        }
        
        if (!is_readable($log_file)) {
            return array('error' => 'Error log file not readable');
        }
        
        $lines = file($log_file);
        $skylearn_logs = array();
        
        // Get last 100 lines and filter for SkyLearn entries
        $recent_lines = array_slice($lines, -100);
        foreach ($recent_lines as $line) {
            if (stripos($line, 'skylearn') !== false) {
                $skylearn_logs[] = trim($line);
            }
        }
        
        return array(
            'log_file' => $log_file,
            'total_entries' => count($skylearn_logs),
            'entries' => array_slice($skylearn_logs, -10) // Last 10 SkyLearn entries
        );
    }
    
    /**
     * Output diagnostics report
     */
    public static function output_diagnostics_report() {
        $diagnostics = self::run_diagnostics();
        
        echo "<div class='skylearn-diagnostics-report'>";
        echo "<h3>SkyLearn Billing Pro - Page Setup Diagnostics</h3>";
        
        // AJAX Handlers
        echo "<h4>AJAX Handlers</h4>";
        if ($diagnostics['ajax_handlers']['logged_in_handler']) {
            echo "<span style='color: green;'>✓</span> Logged-in user handler registered<br>";
        } else {
            echo "<span style='color: red;'>✗</span> Logged-in user handler NOT registered<br>";
        }
        
        if ($diagnostics['ajax_handlers']['logged_out_handler']) {
            echo "<span style='color: green;'>✓</span> Logged-out user handler registered<br>";
        } else {
            echo "<span style='color: orange;'>!</span> Logged-out user handler not registered (may be intentional)<br>";
        }
        
        echo "Handler count: " . $diagnostics['ajax_handlers']['handler_count'] . "<br>";
        
        // Dependencies
        echo "<h4>Dependencies</h4>";
        foreach ($diagnostics['dependencies'] as $key => $value) {
            if ($key === 'instances') continue;
            
            if ($value) {
                echo "<span style='color: green;'>✓</span> " . ucfirst(str_replace('_', ' ', $key)) . "<br>";
            } else {
                echo "<span style='color: red;'>✗</span> " . ucfirst(str_replace('_', ' ', $key)) . "<br>";
            }
        }
        
        // Pages Status
        echo "<h4>Pages Status</h4>";
        if (isset($diagnostics['pages_status']['error'])) {
            echo "<span style='color: red;'>Error:</span> " . $diagnostics['pages_status']['error'] . "<br>";
        } else {
            $total_pages = count($diagnostics['pages_status']);
            $existing_pages = count(array_filter($diagnostics['pages_status'], function($page) { 
                return isset($page['exists']) && $page['exists']; 
            }));
            
            echo "Total pages: {$total_pages}<br>";
            echo "Existing pages: {$existing_pages}<br>";
            echo "Missing pages: " . ($total_pages - $existing_pages) . "<br>";
        }
        
        // Permissions
        echo "<h4>Permissions</h4>";
        $perms = $diagnostics['permissions'];
        echo "Current user ID: " . $perms['current_user_id'] . "<br>";
        echo "Is admin: " . ($perms['is_admin'] ? 'Yes' : 'No') . "<br>";
        echo "WP doing AJAX: " . ($perms['wp_doing_ajax'] ? 'Yes' : 'No') . "<br>";
        echo "Can manage options: " . ($perms['can_manage_options'] ? 'Yes' : 'No') . "<br>";
        
        // Error Logs
        echo "<h4>Recent Error Logs</h4>";
        if (isset($diagnostics['error_logs']['error'])) {
            echo "<span style='color: red;'>Error:</span> " . $diagnostics['error_logs']['error'] . "<br>";
        } else {
            echo "Total SkyLearn entries: " . $diagnostics['error_logs']['total_entries'] . "<br>";
            if (!empty($diagnostics['error_logs']['entries'])) {
                echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 200px; overflow-y: auto;'>";
                foreach ($diagnostics['error_logs']['entries'] as $entry) {
                    echo htmlentities($entry) . "\n";
                }
                echo "</pre>";
            } else {
                echo "No recent SkyLearn log entries found.<br>";
            }
        }
        
        echo "</div>";
        
        // Raw data for debugging
        echo "<details style='margin-top: 20px;'>";
        echo "<summary>Raw Diagnostic Data</summary>";
        echo "<pre>" . htmlentities(json_encode($diagnostics, JSON_PRETTY_PRINT)) . "</pre>";
        echo "</details>";
    }
}

/**
 * Add diagnostics page to admin menu
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'skylearn-billing-pro',
        __('Page Setup Diagnostics', 'skylearn-billing-pro'),
        __('Diagnostics', 'skylearn-billing-pro'),
        'manage_options',
        'skylearn-page-diagnostics',
        function() {
            echo '<div class="wrap">';
            echo '<h1>' . __('Page Setup Diagnostics', 'skylearn-billing-pro') . '</h1>';
            SkyLearn_Billing_Pro_Page_Setup_Diagnostics::output_diagnostics_report();
            echo '</div>';
        }
    );
}, 20);