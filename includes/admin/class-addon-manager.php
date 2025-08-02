<?php
/**
 * Addon Manager for Skylearn Billing Pro
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
 * Addon Manager Class
 * Handles addon discovery, installation, activation, and management
 */
class SkyLearn_Billing_Pro_Addon_Manager {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Addon_Manager
     */
    private static $_instance = null;
    
    /**
     * Addons directory path
     *
     * @var string
     */
    private $addons_dir;
    
    /**
     * Active addons option name
     *
     * @var string
     */
    private $active_addons_option = 'skylearn_billing_active_addons';
    
    /**
     * Installed addons option name
     *
     * @var string
     */
    private $installed_addons_option = 'skylearn_billing_installed_addons';
    
    /**
     * Licensing manager instance
     *
     * @var SkyLearn_Billing_Pro_Licensing_Manager
     */
    private $licensing_manager;
    
    /**
     * Feature flags instance
     *
     * @var SkyLearn_Billing_Pro_Feature_Flags
     */
    private $feature_flags;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Addon_Manager
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->addons_dir = SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'addons/';
        $this->licensing_manager = skylearn_billing_pro_licensing();
        $this->feature_flags = skylearn_billing_pro_features();
        
        add_action('init', array($this, 'init_addons'));
        add_action('wp_ajax_skylearn_billing_manage_addon', array($this, 'handle_addon_action'));
    }
    
    /**
     * Initialize active addons
     */
    public function init_addons() {
        $active_addons = $this->get_active_addons();
        
        foreach ($active_addons as $addon_id) {
            $this->load_addon($addon_id);
        }
    }
    
    /**
     * Get all available addons
     *
     * @return array Array of addon data
     */
    public function get_available_addons() {
        $addons = array();
        
        // Core addons included with plugin
        $core_addons = array(
            'email-addon' => array(
                'id' => 'email-addon',
                'name' => __('Email Addon', 'skylearn-billing-pro'),
                'description' => __('Enhanced email notifications and templates', 'skylearn-billing-pro'),
                'version' => '1.0.0',
                'author' => 'Skylearn Team',
                'file' => 'email-addon.php',
                'type' => 'free',
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE,
                'category' => 'notifications',
                'status' => $this->get_addon_status('email-addon')
            ),
            'webhook-addon' => array(
                'id' => 'webhook-addon',
                'name' => __('Webhook Addon', 'skylearn-billing-pro'),
                'description' => __('Advanced webhook management and integrations', 'skylearn-billing-pro'),
                'version' => '1.0.0',
                'author' => 'Skylearn Team',
                'file' => 'webhook-addon.php',
                'type' => 'free',
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_FREE,
                'category' => 'integrations',
                'status' => $this->get_addon_status('webhook-addon')
            ),
            'affiliate-addon' => array(
                'id' => 'affiliate-addon',
                'name' => __('Affiliate Addon', 'skylearn-billing-pro'),
                'description' => __('Complete affiliate management system', 'skylearn-billing-pro'),
                'version' => '1.0.0',
                'author' => 'Skylearn Team',
                'file' => 'affiliate-addon.php',
                'type' => 'paid',
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO,
                'category' => 'marketing',
                'status' => $this->get_addon_status('affiliate-addon')
            ),
            'reporting-addon' => array(
                'id' => 'reporting-addon',
                'name' => __('Advanced Reporting Addon', 'skylearn-billing-pro'),
                'description' => __('Advanced analytics and custom reports', 'skylearn-billing-pro'),
                'version' => '1.0.0',
                'author' => 'Skylearn Team',
                'file' => 'reporting-addon.php',
                'type' => 'paid',
                'required_tier' => SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO_PLUS,
                'category' => 'analytics',
                'status' => $this->get_addon_status('reporting-addon')
            )
        );
        
        // Scan addons directory for additional addons
        if (is_dir($this->addons_dir)) {
            $addon_files = glob($this->addons_dir . '*-addon.php');
            
            foreach ($addon_files as $file) {
                $addon_data = $this->get_addon_header_data($file);
                if ($addon_data && !isset($core_addons[$addon_data['id']])) {
                    $addon_data['status'] = $this->get_addon_status($addon_data['id']);
                    $addons[$addon_data['id']] = $addon_data;
                }
            }
        }
        
        // Merge core addons
        $addons = array_merge($core_addons, $addons);
        
        return apply_filters('skylearn_billing_available_addons', $addons);
    }
    
    /**
     * Get addon header data from file
     *
     * @param string $file Path to addon file
     * @return array|false Addon data or false on failure
     */
    private function get_addon_header_data($file) {
        $addon_data = get_file_data($file, array(
            'id' => 'Addon ID',
            'name' => 'Addon Name',
            'description' => 'Description',
            'version' => 'Version',
            'author' => 'Author',
            'type' => 'Type',
            'required_tier' => 'Required Tier',
            'category' => 'Category'
        ));
        
        if (empty($addon_data['id']) || empty($addon_data['name'])) {
            return false;
        }
        
        $addon_data['file'] = basename($file);
        
        return $addon_data;
    }
    
    /**
     * Get addon status
     *
     * @param string $addon_id Addon ID
     * @return string Status: installed, active, available, or locked
     */
    public function get_addon_status($addon_id) {
        $installed_addons = $this->get_installed_addons();
        $active_addons = $this->get_active_addons();
        
        if (in_array($addon_id, $active_addons)) {
            return 'active';
        }
        
        if (in_array($addon_id, $installed_addons)) {
            return 'installed';
        }
        
        // Check if addon file exists
        $addon_file = $this->addons_dir . $addon_id . '.php';
        if (file_exists($addon_file)) {
            return 'available';
        }
        
        return 'locked';
    }
    
    /**
     * Check if user can manage addon
     *
     * @param string $addon_id Addon ID
     * @return bool True if can manage
     */
    public function can_manage_addon($addon_id) {
        $addons = $this->get_available_addons();
        
        if (!isset($addons[$addon_id])) {
            return false;
        }
        
        $addon = $addons[$addon_id];
        
        // Check if user has required license tier
        if ($addon['type'] === 'paid') {
            $required_tier = $addon['required_tier'] ?? SkyLearn_Billing_Pro_Licensing_Manager::TIER_PRO;
            return $this->licensing_manager->has_tier($required_tier);
        }
        
        return true;
    }
    
    /**
     * Install addon
     *
     * @param string $addon_id Addon ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function install_addon($addon_id) {
        if (!$this->can_manage_addon($addon_id)) {
            return new WP_Error('license_required', __('License upgrade required for this addon.', 'skylearn-billing-pro'));
        }
        
        $addon_file = $this->addons_dir . $addon_id . '.php';
        
        if (!file_exists($addon_file)) {
            return new WP_Error('addon_not_found', __('Addon file not found.', 'skylearn-billing-pro'));
        }
        
        $installed_addons = $this->get_installed_addons();
        
        if (!in_array($addon_id, $installed_addons)) {
            $installed_addons[] = $addon_id;
            update_option($this->installed_addons_option, $installed_addons);
        }
        
        do_action('skylearn_billing_addon_installed', $addon_id);
        
        return true;
    }
    
    /**
     * Activate addon
     *
     * @param string $addon_id Addon ID
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function activate_addon($addon_id) {
        if (!$this->can_manage_addon($addon_id)) {
            return new WP_Error('license_required', __('License upgrade required for this addon.', 'skylearn-billing-pro'));
        }
        
        // Install if not already installed
        $install_result = $this->install_addon($addon_id);
        if (is_wp_error($install_result)) {
            return $install_result;
        }
        
        $active_addons = $this->get_active_addons();
        
        if (!in_array($addon_id, $active_addons)) {
            $active_addons[] = $addon_id;
            update_option($this->active_addons_option, $active_addons);
            
            // Load the addon
            $this->load_addon($addon_id);
        }
        
        do_action('skylearn_billing_addon_activated', $addon_id);
        
        return true;
    }
    
    /**
     * Deactivate addon
     *
     * @param string $addon_id Addon ID
     * @return bool True on success
     */
    public function deactivate_addon($addon_id) {
        $active_addons = $this->get_active_addons();
        $key = array_search($addon_id, $active_addons);
        
        if ($key !== false) {
            unset($active_addons[$key]);
            update_option($this->active_addons_option, array_values($active_addons));
        }
        
        do_action('skylearn_billing_addon_deactivated', $addon_id);
        
        return true;
    }
    
    /**
     * Load addon
     *
     * @param string $addon_id Addon ID
     * @return bool True on success
     */
    private function load_addon($addon_id) {
        $addon_file = $this->addons_dir . $addon_id . '.php';
        
        if (file_exists($addon_file)) {
            try {
                require_once $addon_file;
                do_action('skylearn_billing_addon_loaded', $addon_id);
                return true;
            } catch (Exception $e) {
                error_log('Skylearn Billing Pro: Error loading addon ' . $addon_id . ': ' . $e->getMessage());
                
                // Deactivate faulty addon to prevent breaking the main plugin
                $this->deactivate_addon($addon_id);
                
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Get active addons
     *
     * @return array Array of active addon IDs
     */
    public function get_active_addons() {
        return get_option($this->active_addons_option, array());
    }
    
    /**
     * Get installed addons
     *
     * @return array Array of installed addon IDs
     */
    public function get_installed_addons() {
        return get_option($this->installed_addons_option, array());
    }
    
    /**
     * Handle AJAX addon actions
     */
    public function handle_addon_action() {
        check_ajax_referer('skylearn_billing_addon_action', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $action = sanitize_text_field($_POST['addon_action']);
        $addon_id = sanitize_text_field($_POST['addon_id']);
        
        $result = false;
        
        switch ($action) {
            case 'activate':
                $result = $this->activate_addon($addon_id);
                break;
            case 'deactivate':
                $result = $this->deactivate_addon($addon_id);
                break;
            case 'install':
                $result = $this->install_addon($addon_id);
                break;
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'message' => sprintf(__('Addon %s successfully.', 'skylearn-billing-pro'), $action . 'd'),
                'status' => $this->get_addon_status($addon_id)
            ));
        }
    }
}

/**
 * Get addon manager instance
 *
 * @return SkyLearn_Billing_Pro_Addon_Manager
 */
function skylearn_billing_pro_addon_manager() {
    return SkyLearn_Billing_Pro_Addon_Manager::instance();
}

// Initialize addon manager
// skylearn_billing_pro_addon_manager();