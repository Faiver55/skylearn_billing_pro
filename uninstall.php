<?php
/**
 * Skylearn Billing Pro Uninstall
 * 
 * This file is called when the plugin is uninstalled via WordPress admin.
 * It handles the cleanup of plugin data, options, and database tables.
 *
 * @package SkyLearn_Billing_Pro
 * @since 1.0.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove plugin options and data
 */
function skylearn_billing_uninstall_cleanup() {
    // Remove plugin options
    $options_to_remove = array(
        'skylearn_billing_settings',
        'skylearn_billing_stripe_settings',
        'skylearn_billing_lemonsqueezy_settings',
        'skylearn_billing_email_settings',
        'skylearn_billing_tax_settings',
        'skylearn_billing_version',
        'skylearn_billing_installed',
        'skylearn_billing_onboarding_completed'
    );
    
    foreach ($options_to_remove as $option) {
        delete_option($option);
        delete_site_option($option); // For multisite
    }
    
    // Remove user meta data related to billing
    delete_metadata('user', 0, 'skylearn_billing_customer_id', '', true);
    delete_metadata('user', 0, 'skylearn_billing_subscription_status', '', true);
    
    // Remove transients
    delete_transient('skylearn_billing_license_check');
    delete_transient('skylearn_billing_update_check');
    
    // Remove scheduled events
    wp_clear_scheduled_hook('skylearn_billing_daily_cleanup');
    wp_clear_scheduled_hook('skylearn_billing_subscription_check');
    wp_clear_scheduled_hook('skylearn_billing_license_check');
}

/**
 * Remove custom database tables
 */
function skylearn_billing_remove_tables() {
    global $wpdb;
    
    // List of custom tables to remove
    $tables_to_remove = array(
        $wpdb->prefix . 'skylearn_billing_transactions',
        $wpdb->prefix . 'skylearn_billing_subscriptions',
        $wpdb->prefix . 'skylearn_billing_customers',
        $wpdb->prefix . 'skylearn_billing_invoices',
        $wpdb->prefix . 'skylearn_billing_logs'
    );
    
    foreach ($tables_to_remove as $table) {
        $wpdb->query("DROP TABLE IF EXISTS {$table}");
    }
}

/**
 * Remove custom post types and their data
 */
function skylearn_billing_remove_post_types() {
    // Get all posts of our custom post types
    $post_types = array(
        'sb_product',
        'sb_subscription',
        'sb_invoice',
        'sb_customer'
    );
    
    foreach ($post_types as $post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any'
        ));
        
        foreach ($posts as $post) {
            wp_delete_post($post->ID, true); // Force delete
        }
    }
}

/**
 * Remove custom taxonomies
 */
function skylearn_billing_remove_taxonomies() {
    $taxonomies = array(
        'sb_product_category',
        'sb_product_tag'
    );
    
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false
        ));
        
        if (!is_wp_error($terms)) {
            foreach ($terms as $term) {
                wp_delete_term($term->term_id, $taxonomy);
            }
        }
    }
}

/**
 * Remove uploaded files and directories
 */
function skylearn_billing_remove_uploads() {
    $upload_dir = wp_upload_dir();
    $billing_dir = $upload_dir['basedir'] . '/skylearn-billing/';
    
    if (is_dir($billing_dir)) {
        skylearn_billing_recursive_remove_directory($billing_dir);
    }
}

/**
 * Recursively remove directory and its contents
 */
function skylearn_billing_recursive_remove_directory($dir) {
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != "." && $object != "..") {
                if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                    skylearn_billing_recursive_remove_directory($dir . DIRECTORY_SEPARATOR . $object);
                } else {
                    unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
        }
        rmdir($dir);
    }
}

/**
 * Check if we should keep user data
 */
function skylearn_billing_should_remove_data() {
    // Check for a setting that determines whether to keep data on uninstall
    $keep_data = get_option('skylearn_billing_keep_data_on_uninstall', false);
    
    // Allow filtering of this decision
    return apply_filters('skylearn_billing_remove_data_on_uninstall', !$keep_data);
}

// Only proceed with cleanup if we should remove data
if (skylearn_billing_should_remove_data()) {
    // Perform the cleanup
    skylearn_billing_uninstall_cleanup();
    skylearn_billing_remove_tables();
    skylearn_billing_remove_post_types();
    skylearn_billing_remove_taxonomies();
    skylearn_billing_remove_uploads();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Clear any caches
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // Allow other plugins/themes to hook into our uninstall process
    do_action('skylearn_billing_uninstalled');
}