<?php
/**
 * Performance optimization for Skylearn Billing Pro
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
 * Performance class for caching and optimization
 */
class SkyLearn_Billing_Pro_Performance {
    
    /**
     * Cache group
     */
    const CACHE_GROUP = 'skylearn_billing_pro';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_loaded', array($this, 'optimize_queries'));
        add_action('wp_ajax_skylearn_clear_cache', array($this, 'ajax_clear_cache'));
        add_action('skylearn_billing_pro_clear_cache', array($this, 'clear_all_cache'));
    }
    
    /**
     * Initialize performance optimizations
     */
    public function init() {
        // Enable object caching for custom data
        wp_cache_add_global_groups(array(self::CACHE_GROUP));
        
        // Hook into WordPress actions for cache invalidation
        add_action('updated_option', array($this, 'maybe_clear_cache_on_option_update'));
        add_action('user_register', array($this, 'clear_user_related_cache'));
        add_action('profile_update', array($this, 'clear_user_related_cache'));
        
        // Schedule cache cleanup
        if (!wp_next_scheduled('skylearn_billing_pro_cache_cleanup')) {
            wp_schedule_event(time(), 'daily', 'skylearn_billing_pro_cache_cleanup');
        }
        add_action('skylearn_billing_pro_cache_cleanup', array($this, 'cleanup_expired_cache'));
    }
    
    /**
     * Optimize database queries
     */
    public function optimize_queries() {
        // Add hooks to prevent unnecessary queries
        add_filter('skylearn_billing_pro_get_products', array($this, 'cache_products'), 10, 2);
        add_filter('skylearn_billing_pro_get_user_enrollments', array($this, 'cache_user_enrollments'), 10, 2);
        add_filter('skylearn_billing_pro_get_payment_gateways', array($this, 'cache_payment_gateways'));
    }
    
    /**
     * Get cached data or execute callback
     */
    public function get_cached($key, $callback, $expiration = 3600, $group = self::CACHE_GROUP) {
        $cached_data = wp_cache_get($key, $group);
        
        if (false !== $cached_data) {
            return $cached_data;
        }
        
        // Execute callback to get fresh data
        $data = call_user_func($callback);
        
        // Cache the result
        wp_cache_set($key, $data, $group, $expiration);
        
        return $data;
    }
    
    /**
     * Cache products data
     */
    public function cache_products($products, $args) {
        $cache_key = 'products_' . md5(serialize($args));
        
        return $this->get_cached($cache_key, function() use ($args) {
            // This would integrate with the actual product manager
            return $this->get_products_from_db($args);
        }, 1800); // Cache for 30 minutes
    }
    
    /**
     * Cache user enrollments
     */
    public function cache_user_enrollments($enrollments, $user_id) {
        $cache_key = 'user_enrollments_' . $user_id;
        
        return $this->get_cached($cache_key, function() use ($user_id) {
            return $this->get_user_enrollments_from_db($user_id);
        }, 900); // Cache for 15 minutes
    }
    
    /**
     * Cache payment gateways
     */
    public function cache_payment_gateways($gateways = null) {
        $cache_key = 'payment_gateways';
        
        return $this->get_cached($cache_key, function() {
            return $this->get_payment_gateways_from_db();
        }, 3600); // Cache for 1 hour
    }
    
    /**
     * Batch processing for heavy operations
     */
    public function batch_process($items, $callback, $batch_size = 50) {
        $total_items = count($items);
        $processed = 0;
        $batches = array_chunk($items, $batch_size);
        
        foreach ($batches as $batch) {
            // Process batch
            call_user_func($callback, $batch);
            
            $processed += count($batch);
            
            // Allow other processes to run
            if ($processed < $total_items) {
                usleep(100000); // 0.1 second delay
            }
        }
        
        return $processed;
    }
    
    /**
     * Async processing using WordPress background jobs
     */
    public function schedule_async_task($hook, $args = array(), $delay = 0) {
        if ($delay > 0) {
            wp_schedule_single_event(time() + $delay, $hook, $args);
        } else {
            // Use wp_schedule_single_event for immediate execution
            wp_schedule_single_event(time(), $hook, $args);
            
            // Trigger cron immediately for development
            if (defined('WP_DEBUG') && WP_DEBUG) {
                spawn_cron();
            }
        }
    }
    
    /**
     * Optimize database tables
     */
    public function optimize_database_tables() {
        global $wpdb;
        
        $tables_to_optimize = array(
            $wpdb->prefix . 'skylearn_audit_log',
            // Add other custom tables as needed
        );
        
        $optimized = 0;
        
        foreach ($tables_to_optimize as $table) {
            // Check if table exists
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $wpdb->query("OPTIMIZE TABLE $table");
                $optimized++;
            }
        }
        
        // Log optimization
        if (function_exists('skylearn_billing_pro_audit_logger')) {
            $audit_logger = skylearn_billing_pro_audit_logger();
            $audit_logger->log(
                'admin',
                'database_optimized',
                array('tables_optimized' => $optimized)
            );
        }
        
        return $optimized;
    }
    
    /**
     * Minimize database queries for admin pages
     */
    public function optimize_admin_queries() {
        // Combine multiple option calls into one
        add_filter('pre_option_skylearn_billing_pro_options', array($this, 'cache_plugin_options'));
        
        // Optimize user queries
        add_action('pre_user_query', array($this, 'optimize_user_queries'));
    }
    
    /**
     * Cache plugin options
     */
    public function cache_plugin_options($value) {
        static $cached_options = null;
        
        if (null === $cached_options) {
            $cached_options = get_option('skylearn_billing_pro_options', array());
        }
        
        return $cached_options;
    }
    
    /**
     * Optimize user queries
     */
    public function optimize_user_queries($query) {
        // Add LIMIT if not specified to prevent large result sets
        if (empty($query->query_vars['number']) && empty($query->query_vars['offset'])) {
            $query->query_vars['number'] = 100;
        }
    }
    
    /**
     * Lazy load heavy content
     */
    public function lazy_load_content($content_id, $loader_callback) {
        $cache_key = 'lazy_content_' . $content_id;
        $cached = wp_cache_get($cache_key, self::CACHE_GROUP);
        
        if (false !== $cached) {
            return $cached;
        }
        
        // Schedule loading for next request
        wp_schedule_single_event(time() + 1, 'skylearn_billing_pro_lazy_load', array($content_id));
        
        // Return placeholder for now
        return array('loading' => true, 'content_id' => $content_id);
    }
    
    /**
     * Preload critical data
     */
    public function preload_critical_data() {
        $critical_data = array(
            'active_payment_gateways' => function() {
                return $this->get_active_payment_gateways();
            },
            'plugin_settings' => function() {
                return get_option('skylearn_billing_pro_options', array());
            },
            'recent_transactions' => function() {
                return $this->get_recent_transactions(10);
            }
        );
        
        foreach ($critical_data as $key => $callback) {
            $this->get_cached($key, $callback, 1800);
        }
    }
    
    /**
     * Clear specific cache
     */
    public function clear_cache($key, $group = self::CACHE_GROUP) {
        wp_cache_delete($key, $group);
    }
    
    /**
     * Clear all plugin cache
     */
    public function clear_all_cache() {
        // Clear WordPress object cache for our group
        wp_cache_flush_group(self::CACHE_GROUP);
        
        // Clear specific transients
        $this->clear_plugin_transients();
        
        // Log cache clear
        if (function_exists('skylearn_billing_pro_audit_logger')) {
            $audit_logger = skylearn_billing_pro_audit_logger();
            $audit_logger->log(
                'admin',
                'cache_cleared',
                array('timestamp' => current_time('mysql'))
            );
        }
    }
    
    /**
     * Clear plugin-specific transients
     */
    private function clear_plugin_transients() {
        global $wpdb;
        
        $transients = $wpdb->get_results(
            "SELECT option_name FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_skylearn_%' 
             OR option_name LIKE '_transient_timeout_skylearn_%'"
        );
        
        foreach ($transients as $transient) {
            $key = str_replace(array('_transient_', '_transient_timeout_'), '', $transient->option_name);
            delete_transient($key);
        }
    }
    
    /**
     * Maybe clear cache on option update
     */
    public function maybe_clear_cache_on_option_update($option_name) {
        if (strpos($option_name, 'skylearn_billing_pro') === 0) {
            $this->clear_all_cache();
        }
    }
    
    /**
     * Clear user-related cache
     */
    public function clear_user_related_cache($user_id) {
        $this->clear_cache('user_enrollments_' . $user_id);
        $this->clear_cache('user_subscriptions_' . $user_id);
    }
    
    /**
     * Cleanup expired cache
     */
    public function cleanup_expired_cache() {
        $this->clear_plugin_transients();
        $this->optimize_database_tables();
    }
    
    /**
     * Get performance metrics
     */
    public function get_performance_metrics() {
        $metrics = array();
        
        // Database query count
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            global $wpdb;
            $metrics['db_queries'] = count($wpdb->queries);
            $metrics['db_query_time'] = array_sum(array_column($wpdb->queries, 1));
        }
        
        // Memory usage
        $metrics['memory_usage'] = memory_get_usage(true);
        $metrics['memory_peak'] = memory_get_peak_usage(true);
        
        // Cache hit rate (if available)
        if (function_exists('wp_cache_get_stats')) {
            $cache_stats = wp_cache_get_stats();
            if (isset($cache_stats[self::CACHE_GROUP])) {
                $stats = $cache_stats[self::CACHE_GROUP];
                $metrics['cache_hits'] = $stats['hits'] ?? 0;
                $metrics['cache_misses'] = $stats['misses'] ?? 0;
                $metrics['cache_hit_rate'] = $stats['hits'] > 0 ? 
                    round(($stats['hits'] / ($stats['hits'] + $stats['misses'])) * 100, 2) : 0;
            }
        }
        
        return $metrics;
    }
    
    /**
     * Database helper methods (these would integrate with actual data sources)
     */
    private function get_products_from_db($args) {
        // Placeholder - would integrate with actual product manager
        return array();
    }
    
    private function get_user_enrollments_from_db($user_id) {
        // Placeholder - would integrate with actual enrollment system
        return array();
    }
    
    private function get_payment_gateways_from_db() {
        // Placeholder - would integrate with actual payment manager
        return array();
    }
    
    private function get_active_payment_gateways() {
        $options = get_option('skylearn_billing_pro_options', array());
        return isset($options['active_gateways']) ? $options['active_gateways'] : array();
    }
    
    private function get_recent_transactions($limit) {
        // Placeholder - would integrate with actual transaction system
        return array();
    }
    
    /**
     * AJAX handler for clearing cache
     */
    public function ajax_clear_cache() {
        // Verify nonce and capabilities
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'skylearn_clear_cache')) {
            wp_die(__('Unauthorized access', 'skylearn-billing-pro'));
        }
        
        $this->clear_all_cache();
        
        wp_send_json_success(array(
            'message' => __('Cache cleared successfully', 'skylearn-billing-pro'),
            'timestamp' => current_time('mysql')
        ));
    }
}

/**
 * Get single instance of Performance
 */
function skylearn_billing_pro_performance() {
    static $instance = null;
    if (null === $instance) {
        $instance = new SkyLearn_Billing_Pro_Performance();
    }
    return $instance;
}