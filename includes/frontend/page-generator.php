<?php
/**
 * Page Generator for Frontend Pages
 *
 * Automatically creates necessary frontend pages for the billing system
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
 * Page Generator class
 */
class SkyLearn_Billing_Pro_Page_Generator {
    
    /**
     * Required pages configuration
     */
    private $required_pages = array(
        'checkout' => array(
            'title' => 'Checkout',
            'slug' => 'skylearn-checkout',
            'content' => '[skylearn_checkout]',
            'template' => 'page',
            'is_public' => true,
            'meta' => array(
                'noindex' => true
            )
        ),
        'thank_you' => array(
            'title' => 'Thank You',
            'slug' => 'skylearn-thank-you',
            'content' => '[skylearn_thank_you]',
            'template' => 'page',
            'is_public' => true,
            'meta' => array(
                'noindex' => true
            )
        ),
        'portal' => array(
            'title' => 'Customer Portal',
            'slug' => 'skylearn-portal',
            'content' => '[skylearn_portal]',
            'template' => 'page',
            'is_public' => false,
            'meta' => array(
                'noindex' => true
            )
        ),
        'portal_dashboard' => array(
            'title' => 'Dashboard',
            'slug' => 'skylearn-dashboard',
            'parent' => 'portal',
            'content' => '[skylearn_portal_dashboard]',
            'template' => 'page',
            'is_public' => false,
            'meta' => array(
                'noindex' => true
            )
        ),
        'portal_orders' => array(
            'title' => 'Order History',
            'slug' => 'skylearn-orders',
            'parent' => 'portal',
            'content' => '[skylearn_portal_orders]',
            'template' => 'page',
            'is_public' => false,
            'meta' => array(
                'noindex' => true
            )
        ),
        'portal_plans' => array(
            'title' => 'My Plans',
            'slug' => 'skylearn-plans',
            'parent' => 'portal',
            'content' => '[skylearn_portal_plans]',
            'template' => 'page',
            'is_public' => false,
            'meta' => array(
                'noindex' => true
            )
        ),
        'portal_downloads' => array(
            'title' => 'Downloads',
            'slug' => 'skylearn-downloads',
            'parent' => 'portal',
            'content' => '[skylearn_portal_downloads]',
            'template' => 'page',
            'is_public' => false,
            'meta' => array(
                'noindex' => true
            )
        ),
        'portal_addresses' => array(
            'title' => 'Addresses',
            'slug' => 'skylearn-addresses',
            'parent' => 'portal',
            'content' => '[skylearn_portal_addresses]',
            'template' => 'page',
            'is_public' => false,
            'meta' => array(
                'noindex' => true
            )
        ),
        'portal_account' => array(
            'title' => 'Account Settings',
            'slug' => 'skylearn-account',
            'parent' => 'portal',
            'content' => '[skylearn_portal_account]',
            'template' => 'page',
            'is_public' => false,
            'meta' => array(
                'noindex' => true
            )
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize
     */
    public function init() {
        try {
            // Don't automatically create pages on activation - let onboarding handle it
            // add_action('skylearn_billing_pro_activate', array($this, 'create_pages'));
            
            // Add admin action for manual page creation
            add_action('wp_ajax_skylearn_create_pages', array($this, 'ajax_create_pages'));
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Page generator init failed - ' . $e->getMessage());
        }
    }
    
    /**
     * Create all required pages
     *
     * @param bool $force Whether to force recreation of existing pages
     * @return array Result of page creation
     */
    public function create_pages($force = false) {
        $results = array(
            'created' => 0,
            'skipped' => 0,
            'errors' => array(),
            'pages' => array()
        );
        
        try {
            foreach ($this->required_pages as $page_key => $page_config) {
                try {
                    $result = $this->create_page($page_key, $page_config, $force);
                    
                    if ($result['success']) {
                        $results['created']++;
                        $results['pages'][$page_key] = $result['page_id'];
                    } else {
                        if ($result['skipped']) {
                            $results['skipped']++;
                        } else {
                            $results['errors'][] = $result['error'];
                        }
                    }
                } catch (Exception $e) {
                    $error_msg = sprintf(__('Failed to create page %s: %s', 'skylearn-billing-pro'), $page_key, $e->getMessage());
                    $results['errors'][] = $error_msg;
                    error_log('SkyLearn Billing Pro: ' . $error_msg);
                }
            }
            
            // Store page IDs in options
            if (!empty($results['pages'])) {
                $existing_pages = get_option('skylearn_billing_pro_pages', array());
                $updated_pages = array_merge($existing_pages, $results['pages']);
                update_option('skylearn_billing_pro_pages', $updated_pages);
            }
            
            // Log results
            if (!empty($results['errors'])) {
                error_log('SkyLearn Billing Pro: Page creation completed with errors - ' . implode(', ', $results['errors']));
            } else {
                error_log('SkyLearn Billing Pro: Page creation completed successfully - Created: ' . $results['created'] . ', Skipped: ' . $results['skipped']);
            }
            
        } catch (Exception $e) {
            error_log('SkyLearn Billing Pro: Page creation failed completely - ' . $e->getMessage());
            $results['errors'][] = sprintf(__('Page creation failed: %s', 'skylearn-billing-pro'), $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Create a single page
     *
     * @param string $page_key Page identifier
     * @param array $config Page configuration
     * @param bool $force Whether to force recreation
     * @return array Result of page creation
     */
    private function create_page($page_key, $config, $force = false) {
        // Check if page already exists
        $existing_page = $this->get_existing_page($config['slug']);
        
        if ($existing_page && !$force) {
            return array(
                'success' => true,
                'skipped' => true,
                'page_id' => $existing_page->ID,
                'message' => sprintf(__('Page "%s" already exists', 'skylearn-billing-pro'), $config['title'])
            );
        }
        
        // Prepare page data
        $page_data = array(
            'post_title' => $config['title'],
            'post_name' => $config['slug'],
            'post_content' => $config['content'],
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_author' => get_current_user_id() ?: 1,
            'comment_status' => 'closed',
            'ping_status' => 'closed'
        );
        
        // Set parent page if specified
        if (!empty($config['parent'])) {
            $parent_page = $this->get_page_by_key($config['parent']);
            if ($parent_page) {
                $page_data['post_parent'] = $parent_page;
            }
        }
        
        // Create or update page
        if ($existing_page && $force) {
            $page_data['ID'] = $existing_page->ID;
            $page_id = wp_update_post($page_data);
        } else {
            $page_id = wp_insert_post($page_data);
        }
        
        if (is_wp_error($page_id)) {
            return array(
                'success' => false,
                'skipped' => false,
                'error' => sprintf(__('Failed to create page "%s": %s', 'skylearn-billing-pro'), $config['title'], $page_id->get_error_message())
            );
        }
        
        // Add meta data
        if (!empty($config['meta'])) {
            foreach ($config['meta'] as $meta_key => $meta_value) {
                update_post_meta($page_id, '_skylearn_' . $meta_key, $meta_value);
            }
        }
        
        // Set template if specified
        if (!empty($config['template'])) {
            update_post_meta($page_id, '_wp_page_template', $config['template']);
        }
        
        // Mark as SkyLearn page
        update_post_meta($page_id, '_skylearn_billing_page', $page_key);
        
        return array(
            'success' => true,
            'skipped' => false,
            'page_id' => $page_id,
            'message' => sprintf(__('Page "%s" created successfully', 'skylearn-billing-pro'), $config['title'])
        );
    }
    
    /**
     * Get existing page by slug
     *
     * @param string $slug Page slug
     * @return WP_Post|null
     */
    private function get_existing_page($slug) {
        $pages = get_posts(array(
            'name' => $slug,
            'post_type' => 'page',
            'post_status' => 'any',
            'numberposts' => 1
        ));
        
        return !empty($pages) ? $pages[0] : null;
    }
    
    /**
     * Get page ID by page key
     *
     * @param string $page_key Page key
     * @return int|null Page ID
     */
    private function get_page_by_key($page_key) {
        $pages = get_option('skylearn_billing_pro_pages', array());
        return isset($pages[$page_key]) ? $pages[$page_key] : null;
    }
    
    /**
     * Get all created page IDs
     *
     * @return array Page IDs indexed by page key
     */
    public function get_created_pages() {
        return get_option('skylearn_billing_pro_pages', array());
    }
    
    /**
     * Delete all created pages
     *
     * @return array Result of deletion
     */
    public function delete_pages() {
        $pages = $this->get_created_pages();
        $results = array(
            'deleted' => 0,
            'errors' => array()
        );
        
        foreach ($pages as $page_key => $page_id) {
            $result = wp_delete_post($page_id, true);
            
            if ($result) {
                $results['deleted']++;
            } else {
                $results['errors'][] = sprintf(__('Failed to delete page with ID %d', 'skylearn-billing-pro'), $page_id);
            }
        }
        
        // Clear stored page IDs
        delete_option('skylearn_billing_pro_pages');
        
        return $results;
    }
    
    /**
     * Check if required pages exist
     *
     * @return array Status of each required page
     */
    public function check_pages_status() {
        $status = array();
        
        foreach ($this->required_pages as $page_key => $config) {
            $existing_page = $this->get_existing_page($config['slug']);
            $status[$page_key] = array(
                'exists' => !empty($existing_page),
                'page_id' => $existing_page ? $existing_page->ID : null,
                'title' => $config['title'],
                'slug' => $config['slug'],
                'url' => $existing_page ? get_permalink($existing_page->ID) : null
            );
        }
        
        return $status;
    }
    
    /**
     * AJAX handler for creating pages
     */
    public function ajax_create_pages() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions', 'skylearn-billing-pro'));
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'skylearn_create_pages')) {
            wp_die(__('Invalid nonce', 'skylearn-billing-pro'));
        }
        
        $force = !empty($_POST['force']);
        $results = $this->create_pages($force);
        
        wp_send_json_success($results);
    }
}

/**
 * Initialize page generator
 */
function skylearn_billing_pro_page_generator() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Page_Generator();
    }
    
    return $instance;
}