<?php
/**
 * Role-based access control for Skylearn Billing Pro
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
 * Role Access class for managing role-based permissions
 */
class SkyLearn_Billing_Pro_Role_Access {
    
    /**
     * Custom capabilities
     */
    const CAP_MANAGE_BILLING = 'skylearn_manage_billing';
    const CAP_VIEW_REPORTS = 'skylearn_view_reports';
    const CAP_MANAGE_PRODUCTS = 'skylearn_manage_products';
    const CAP_MANAGE_ENROLLMENTS = 'skylearn_manage_enrollments';
    const CAP_MANAGE_PAYMENTS = 'skylearn_manage_payments';
    const CAP_VIEW_LOGS = 'skylearn_view_logs';
    const CAP_MANAGE_PRIVACY = 'skylearn_manage_privacy';
    const CAP_EXPORT_DATA = 'skylearn_export_data';
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_init', array($this, 'admin_init'));
        add_filter('user_has_cap', array($this, 'filter_user_capabilities'), 10, 3);
        add_action('wp_ajax_skylearn_update_role_caps', array($this, 'ajax_update_role_capabilities'));
    }
    
    /**
     * Initialize role access
     */
    public function init() {
        // Add custom capabilities on activation
        add_action('skylearn_billing_pro_activate', array($this, 'add_custom_capabilities'));
        
        // Hook into menu display
        add_filter('skylearn_billing_pro_admin_menu_items', array($this, 'filter_admin_menu_items'));
    }
    
    /**
     * Admin initialization
     */
    public function admin_init() {
        // Register settings for role management
        register_setting('skylearn_billing_pro_roles', 'skylearn_billing_pro_role_capabilities');
    }
    
    /**
     * Add custom capabilities to roles
     */
    public function add_custom_capabilities() {
        $capabilities = $this->get_custom_capabilities();
        
        // Add all capabilities to administrator
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap => $label) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add basic capabilities to editor
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_caps = array(
                self::CAP_VIEW_REPORTS,
                self::CAP_MANAGE_PRODUCTS,
                self::CAP_MANAGE_ENROLLMENTS
            );
            foreach ($editor_caps as $cap) {
                $editor_role->add_cap($cap);
            }
        }
        
        // Save default role configuration
        $this->save_default_role_configuration();
    }
    
    /**
     * Get all custom capabilities
     */
    public function get_custom_capabilities() {
        return array(
            self::CAP_MANAGE_BILLING => __('Manage Billing Settings', 'skylearn-billing-pro'),
            self::CAP_VIEW_REPORTS => __('View Reports and Analytics', 'skylearn-billing-pro'),
            self::CAP_MANAGE_PRODUCTS => __('Manage Products and Bundles', 'skylearn-billing-pro'),
            self::CAP_MANAGE_ENROLLMENTS => __('Manage User Enrollments', 'skylearn-billing-pro'),
            self::CAP_MANAGE_PAYMENTS => __('Manage Payment Settings', 'skylearn-billing-pro'),
            self::CAP_VIEW_LOGS => __('View Audit Logs', 'skylearn-billing-pro'),
            self::CAP_MANAGE_PRIVACY => __('Manage Privacy Settings', 'skylearn-billing-pro'),
            self::CAP_EXPORT_DATA => __('Export User Data', 'skylearn-billing-pro'),
        );
    }
    
    /**
     * Get role configurations
     */
    public function get_role_configurations() {
        $default_config = array(
            'administrator' => array_keys($this->get_custom_capabilities()),
            'editor' => array(
                self::CAP_VIEW_REPORTS,
                self::CAP_MANAGE_PRODUCTS,
                self::CAP_MANAGE_ENROLLMENTS
            ),
            'author' => array(
                self::CAP_VIEW_REPORTS
            ),
            'contributor' => array(),
            'subscriber' => array()
        );
        
        return get_option('skylearn_billing_pro_role_capabilities', $default_config);
    }
    
    /**
     * Save default role configuration
     */
    private function save_default_role_configuration() {
        $existing_config = get_option('skylearn_billing_pro_role_capabilities');
        
        if (!$existing_config) {
            $default_config = array(
                'administrator' => array_keys($this->get_custom_capabilities()),
                'editor' => array(
                    self::CAP_VIEW_REPORTS,
                    self::CAP_MANAGE_PRODUCTS,
                    self::CAP_MANAGE_ENROLLMENTS
                ),
                'author' => array(
                    self::CAP_VIEW_REPORTS
                ),
                'contributor' => array(),
                'subscriber' => array()
            );
            
            update_option('skylearn_billing_pro_role_capabilities', $default_config);
        }
    }
    
    /**
     * Check if user has specific capability
     */
    public function user_can($capability, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Always allow administrators full access
        if (user_can($user_id, 'manage_options')) {
            return true;
        }
        
        return user_can($user_id, $capability);
    }
    
    /**
     * Filter user capabilities
     */
    public function filter_user_capabilities($allcaps, $caps, $args) {
        // Get current user
        $user_id = isset($args[1]) ? $args[1] : get_current_user_id();
        
        if (!$user_id) {
            return $allcaps;
        }
        
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return $allcaps;
        }
        
        // Get role configurations
        $role_configs = $this->get_role_configurations();
        
        // Add capabilities based on user roles
        foreach ($user->roles as $role) {
            if (isset($role_configs[$role])) {
                foreach ($role_configs[$role] as $capability) {
                    $allcaps[$capability] = true;
                }
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Filter admin menu items based on capabilities
     */
    public function filter_admin_menu_items($menu_items) {
        $filtered_items = array();
        
        foreach ($menu_items as $item) {
            $required_cap = isset($item['capability']) ? $item['capability'] : 'manage_options';
            
            if ($this->user_can($required_cap)) {
                $filtered_items[] = $item;
            }
        }
        
        return $filtered_items;
    }
    
    /**
     * Get capability requirements for admin pages
     */
    public function get_page_capabilities() {
        return array(
            'skylearn-billing-pro' => 'manage_options',
            'skylearn-billing-products' => self::CAP_MANAGE_PRODUCTS,
            'skylearn-billing-reports' => self::CAP_VIEW_REPORTS,
            'skylearn-billing-payments' => self::CAP_MANAGE_PAYMENTS,
            'skylearn-billing-audit-logs' => self::CAP_VIEW_LOGS,
            'skylearn-billing-privacy' => self::CAP_MANAGE_PRIVACY,
            'skylearn-billing-enrollments' => self::CAP_MANAGE_ENROLLMENTS,
        );
    }
    
    /**
     * Check page access
     */
    public function check_page_access($page_slug) {
        $page_capabilities = $this->get_page_capabilities();
        $required_capability = isset($page_capabilities[$page_slug]) ? $page_capabilities[$page_slug] : 'manage_options';
        
        if (!$this->user_can($required_capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * Get available WordPress roles
     */
    public function get_available_roles() {
        global $wp_roles;
        
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        return $wp_roles->get_names();
    }
    
    /**
     * Update role capabilities
     */
    public function update_role_capabilities($role_capabilities) {
        // Validate input
        $available_roles = array_keys($this->get_available_roles());
        $available_caps = array_keys($this->get_custom_capabilities());
        
        $validated_config = array();
        
        foreach ($role_capabilities as $role => $capabilities) {
            if (!in_array($role, $available_roles)) {
                continue;
            }
            
            $validated_caps = array();
            foreach ($capabilities as $cap) {
                if (in_array($cap, $available_caps)) {
                    $validated_caps[] = $cap;
                }
            }
            
            $validated_config[$role] = $validated_caps;
        }
        
        // Update role capabilities in WordPress
        $this->apply_role_capabilities($validated_config);
        
        // Save configuration
        update_option('skylearn_billing_pro_role_capabilities', $validated_config);
        
        // Log the change
        if (function_exists('skylearn_billing_pro_audit_logger')) {
            $audit_logger = skylearn_billing_pro_audit_logger();
            $audit_logger->log(
                'admin',
                'role_capabilities_updated',
                array('configuration' => $validated_config)
            );
        }
        
        return $validated_config;
    }
    
    /**
     * Apply role capabilities to WordPress roles
     */
    private function apply_role_capabilities($role_configurations) {
        $available_caps = array_keys($this->get_custom_capabilities());
        
        foreach ($role_configurations as $role_name => $capabilities) {
            $role = get_role($role_name);
            if (!$role) {
                continue;
            }
            
            // Remove all custom capabilities first
            foreach ($available_caps as $cap) {
                $role->remove_cap($cap);
            }
            
            // Add the specified capabilities
            foreach ($capabilities as $cap) {
                $role->add_cap($cap);
            }
        }
    }
    
    /**
     * Create custom role (if needed)
     */
    public function create_custom_role($role_name, $display_name, $capabilities = array()) {
        $role = get_role($role_name);
        
        if (!$role) {
            // Base capabilities for the role
            $base_caps = array(
                'read' => true,
            );
            
            // Add custom capabilities
            foreach ($capabilities as $cap) {
                if (array_key_exists($cap, $this->get_custom_capabilities())) {
                    $base_caps[$cap] = true;
                }
            }
            
            add_role($role_name, $display_name, $base_caps);
            
            // Log role creation
            if (function_exists('skylearn_billing_pro_audit_logger')) {
                $audit_logger = skylearn_billing_pro_audit_logger();
                $audit_logger->log(
                    'admin',
                    'custom_role_created',
                    array(
                        'role_name' => $role_name,
                        'display_name' => $display_name,
                        'capabilities' => $capabilities
                    )
                );
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Remove custom role
     */
    public function remove_custom_role($role_name) {
        // Don't allow removal of WordPress default roles
        $default_roles = array('administrator', 'editor', 'author', 'contributor', 'subscriber');
        
        if (in_array($role_name, $default_roles)) {
            return false;
        }
        
        remove_role($role_name);
        
        // Log role removal
        if (function_exists('skylearn_billing_pro_audit_logger')) {
            $audit_logger = skylearn_billing_pro_audit_logger();
            $audit_logger->log(
                'admin',
                'custom_role_removed',
                array('role_name' => $role_name)
            );
        }
        
        return true;
    }
    
    /**
     * Get users by capability
     */
    public function get_users_by_capability($capability, $args = array()) {
        $default_args = array(
            'meta_query' => array(
                'relation' => 'OR',
            )
        );
        
        // Find users with this capability through their roles
        $role_configs = $this->get_role_configurations();
        $roles_with_cap = array();
        
        foreach ($role_configs as $role => $capabilities) {
            if (in_array($capability, $capabilities)) {
                $roles_with_cap[] = $role;
            }
        }
        
        if (!empty($roles_with_cap)) {
            $default_args['role__in'] = $roles_with_cap;
        }
        
        $args = wp_parse_args($args, $default_args);
        
        return get_users($args);
    }
    
    /**
     * AJAX handler for updating role capabilities
     */
    public function ajax_update_role_capabilities() {
        // Verify nonce and capabilities
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['nonce'], 'skylearn_role_capabilities')) {
            wp_die(__('Unauthorized access', 'skylearn-billing-pro'));
        }
        
        $role_capabilities = isset($_POST['role_capabilities']) ? $_POST['role_capabilities'] : array();
        
        // Sanitize input
        $sanitized_config = array();
        foreach ($role_capabilities as $role => $capabilities) {
            $role = sanitize_key($role);
            $capabilities = array_map('sanitize_key', $capabilities);
            $sanitized_config[$role] = $capabilities;
        }
        
        $updated_config = $this->update_role_capabilities($sanitized_config);
        
        wp_send_json_success(array(
            'message' => __('Role capabilities updated successfully', 'skylearn-billing-pro'),
            'configuration' => $updated_config
        ));
    }
    
    /**
     * Remove capabilities on deactivation
     */
    public function remove_custom_capabilities() {
        $capabilities = array_keys($this->get_custom_capabilities());
        
        // Get all roles
        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        
        foreach ($wp_roles->get_names() as $role_name => $display_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
        
        // Remove role configuration
        delete_option('skylearn_billing_pro_role_capabilities');
    }
}

/**
 * Get single instance of Role Access
 */
function skylearn_billing_pro_role_access() {
    static $instance = null;
    if (null === $instance) {
        $instance = new SkyLearn_Billing_Pro_Role_Access();
    }
    return $instance;
}