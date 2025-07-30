<?php
/**
 * Affiliate Addon for Skylearn Billing Pro
 *
 * Addon ID: affiliate-addon
 * Addon Name: Affiliate Addon
 * Description: Complete affiliate management system
 * Version: 1.0.0
 * Author: Skylearn Team
 * Type: paid
 * Required Tier: pro
 * Category: marketing
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
 * Affiliate Addon Class
 */
class SkyLearn_Billing_Pro_Affiliate_Addon {
    
    /**
     * Addon ID
     */
    const ADDON_ID = 'affiliate-addon';
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Affiliate_Addon
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Affiliate_Addon
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
        add_action('init', array($this, 'init'));
        
        // Addon activation hook
        add_action('skylearn_billing_addon_activated', array($this, 'on_addon_activated'));
        add_action('skylearn_billing_addon_deactivated', array($this, 'on_addon_deactivated'));
    }
    
    /**
     * Initialize addon
     */
    public function init() {
        // Only initialize if addon is active
        $addon_manager = skylearn_billing_pro_addon_manager();
        $active_addons = $addon_manager->get_active_addons();
        
        if (!in_array(self::ADDON_ID, $active_addons)) {
            return;
        }
        
        // Check license eligibility
        $license_manager = skylearn_billing_pro_license_manager();
        if (!$license_manager->is_addon_accessible(self::ADDON_ID)) {
            return;
        }
        
        $this->init_hooks();
        $this->init_settings();
        $this->create_database_tables();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Affiliate tracking hooks
        add_action('init', array($this, 'track_affiliate_visits'));
        add_action('skylearn_billing_payment_completed', array($this, 'process_affiliate_commission'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Frontend hooks
        add_shortcode('skylearn_affiliate_dashboard', array($this, 'render_affiliate_dashboard'));
        add_shortcode('skylearn_affiliate_registration', array($this, 'render_affiliate_registration'));
        
        // AJAX hooks
        add_action('wp_ajax_skylearn_billing_affiliate_register', array($this, 'register_affiliate'));
        add_action('wp_ajax_nopriv_skylearn_billing_affiliate_register', array($this, 'register_affiliate'));
        add_action('wp_ajax_skylearn_billing_approve_affiliate', array($this, 'approve_affiliate'));
        add_action('wp_ajax_skylearn_billing_generate_affiliate_link', array($this, 'generate_affiliate_link'));
        
        // Query vars for affiliate tracking
        add_filter('query_vars', array($this, 'add_query_vars'));
    }
    
    /**
     * Initialize settings
     */
    private function init_settings() {
        register_setting('skylearn_billing_affiliate_addon', 'skylearn_billing_affiliate_addon_settings');
    }
    
    /**
     * Create database tables
     */
    private function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Affiliates table
        $affiliates_table = $wpdb->prefix . 'skylearn_billing_affiliates';
        $affiliates_sql = "CREATE TABLE $affiliates_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            affiliate_code varchar(50) NOT NULL UNIQUE,
            commission_rate decimal(5,2) NOT NULL DEFAULT 10.00,
            status enum('pending','approved','suspended') NOT NULL DEFAULT 'pending',
            total_earnings decimal(10,2) NOT NULL DEFAULT 0.00,
            total_referrals int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY affiliate_code (affiliate_code),
            KEY status (status)
        ) $charset_collate;";
        
        // Affiliate visits table
        $visits_table = $wpdb->prefix . 'skylearn_billing_affiliate_visits';
        $visits_sql = "CREATE TABLE $visits_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) unsigned NOT NULL,
            visitor_ip varchar(45) NOT NULL,
            referrer_url text,
            landing_page text,
            user_agent text,
            converted tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY visitor_ip (visitor_ip),
            KEY converted (converted),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Affiliate commissions table
        $commissions_table = $wpdb->prefix . 'skylearn_billing_affiliate_commissions';
        $commissions_sql = "CREATE TABLE $commissions_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            affiliate_id bigint(20) unsigned NOT NULL,
            visit_id bigint(20) unsigned,
            order_id varchar(100) NOT NULL,
            commission_amount decimal(10,2) NOT NULL,
            commission_rate decimal(5,2) NOT NULL,
            order_total decimal(10,2) NOT NULL,
            status enum('pending','approved','paid') NOT NULL DEFAULT 'pending',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY affiliate_id (affiliate_id),
            KEY visit_id (visit_id),
            KEY order_id (order_id),
            KEY status (status)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($affiliates_sql);
        dbDelta($visits_sql);
        dbDelta($commissions_sql);
    }
    
    /**
     * Add query vars for affiliate tracking
     *
     * @param array $vars Query vars
     * @return array Modified query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'ref';
        $vars[] = 'affiliate';
        return $vars;
    }
    
    /**
     * Track affiliate visits
     */
    public function track_affiliate_visits() {
        $affiliate_code = get_query_var('ref') ?: get_query_var('affiliate');
        
        if (empty($affiliate_code)) {
            return;
        }
        
        $affiliate = $this->get_affiliate_by_code($affiliate_code);
        
        if (!$affiliate || $affiliate->status !== 'approved') {
            return;
        }
        
        // Set cookie for affiliate tracking
        setcookie('skylearn_affiliate_ref', $affiliate_code, time() + (30 * DAY_IN_SECONDS), '/');
        
        // Record visit
        $this->record_affiliate_visit($affiliate->id);
    }
    
    /**
     * Record affiliate visit
     *
     * @param int $affiliate_id Affiliate ID
     */
    private function record_affiliate_visit($affiliate_id) {
        global $wpdb;
        
        $visitor_ip = $this->get_visitor_ip();
        $referrer_url = wp_get_referer();
        $landing_page = home_url($_SERVER['REQUEST_URI']);
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check if this IP already visited today
        $existing_visit = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}skylearn_billing_affiliate_visits 
             WHERE affiliate_id = %d AND visitor_ip = %s AND DATE(created_at) = CURDATE()",
            $affiliate_id,
            $visitor_ip
        ));
        
        if ($existing_visit) {
            return; // Don't record duplicate visits from same IP on same day
        }
        
        $wpdb->insert(
            $wpdb->prefix . 'skylearn_billing_affiliate_visits',
            array(
                'affiliate_id' => $affiliate_id,
                'visitor_ip' => $visitor_ip,
                'referrer_url' => $referrer_url,
                'landing_page' => $landing_page,
                'user_agent' => $user_agent
            )
        );
    }
    
    /**
     * Process affiliate commission
     *
     * @param array $payment_data Payment data
     */
    public function process_affiliate_commission($payment_data) {
        $affiliate_code = $_COOKIE['skylearn_affiliate_ref'] ?? '';
        
        if (empty($affiliate_code)) {
            return;
        }
        
        $affiliate = $this->get_affiliate_by_code($affiliate_code);
        
        if (!$affiliate || $affiliate->status !== 'approved') {
            return;
        }
        
        $order_total = $payment_data['amount'] ?? 0;
        $commission_rate = $affiliate->commission_rate;
        $commission_amount = ($order_total * $commission_rate) / 100;
        
        $this->record_commission($affiliate->id, $payment_data, $commission_amount, $commission_rate, $order_total);
        
        // Clear affiliate cookie after conversion
        setcookie('skylearn_affiliate_ref', '', time() - 3600, '/');
    }
    
    /**
     * Record commission
     *
     * @param int $affiliate_id Affiliate ID
     * @param array $payment_data Payment data
     * @param float $commission_amount Commission amount
     * @param float $commission_rate Commission rate
     * @param float $order_total Order total
     */
    private function record_commission($affiliate_id, $payment_data, $commission_amount, $commission_rate, $order_total) {
        global $wpdb;
        
        $order_id = $payment_data['order_id'] ?? '';
        
        // Find related visit
        $visitor_ip = $this->get_visitor_ip();
        $visit_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}skylearn_billing_affiliate_visits 
             WHERE affiliate_id = %d AND visitor_ip = %s 
             ORDER BY created_at DESC LIMIT 1",
            $affiliate_id,
            $visitor_ip
        ));
        
        // Record commission
        $wpdb->insert(
            $wpdb->prefix . 'skylearn_billing_affiliate_commissions',
            array(
                'affiliate_id' => $affiliate_id,
                'visit_id' => $visit_id,
                'order_id' => $order_id,
                'commission_amount' => $commission_amount,
                'commission_rate' => $commission_rate,
                'order_total' => $order_total
            )
        );
        
        // Update affiliate totals
        $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->prefix}skylearn_billing_affiliates 
             SET total_earnings = total_earnings + %f, total_referrals = total_referrals + 1 
             WHERE id = %d",
            $commission_amount,
            $affiliate_id
        ));
        
        // Mark visit as converted
        if ($visit_id) {
            $wpdb->update(
                $wpdb->prefix . 'skylearn_billing_affiliate_visits',
                array('converted' => 1),
                array('id' => $visit_id)
            );
        }
    }
    
    /**
     * Get affiliate by code
     *
     * @param string $code Affiliate code
     * @return object|null Affiliate data
     */
    private function get_affiliate_by_code($code) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}skylearn_billing_affiliates WHERE affiliate_code = %s",
            $code
        ));
    }
    
    /**
     * Get visitor IP
     *
     * @return string Visitor IP address
     */
    private function get_visitor_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'skylearn-billing-pro',
            __('Affiliate Addon Settings', 'skylearn-billing-pro'),
            __('Affiliate Addon', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-affiliate-addon',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'skylearn-billing-affiliate-addon') === false) {
            return;
        }
        
        wp_enqueue_script(
            'skylearn-billing-affiliate-addon',
            SKYLEARN_BILLING_PLUGIN_URL . 'assets/js/affiliate-addon-admin.js',
            array('jquery'),
            SKYLEARN_BILLING_VERSION,
            true
        );
        
        wp_localize_script('skylearn-billing-affiliate-addon', 'skylearn_billing_affiliate_addon', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_billing_affiliate_addon'),
            'strings' => array(
                'confirm_approve' => __('Are you sure you want to approve this affiliate?', 'skylearn-billing-pro'),
                'confirm_reject' => __('Are you sure you want to reject this affiliate?', 'skylearn-billing-pro')
            )
        ));
    }
    
    /**
     * Register affiliate
     */
    public function register_affiliate() {
        check_ajax_referer('skylearn_billing_affiliate_addon', 'nonce');
        
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            wp_send_json_error(__('You must be logged in to register as an affiliate.', 'skylearn-billing-pro'));
        }
        
        // Check if user is already an affiliate
        global $wpdb;
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}skylearn_billing_affiliates WHERE user_id = %d",
            $user_id
        ));
        
        if ($existing) {
            wp_send_json_error(__('You are already registered as an affiliate.', 'skylearn-billing-pro'));
        }
        
        // Generate unique affiliate code
        $affiliate_code = $this->generate_affiliate_code($user_id);
        
        // Create affiliate record
        $wpdb->insert(
            $wpdb->prefix . 'skylearn_billing_affiliates',
            array(
                'user_id' => $user_id,
                'affiliate_code' => $affiliate_code,
                'commission_rate' => 10.00, // Default 10%
                'status' => 'pending'
            )
        );
        
        wp_send_json_success(__('Affiliate registration submitted for approval.', 'skylearn-billing-pro'));
    }
    
    /**
     * Generate affiliate code
     *
     * @param int $user_id User ID
     * @return string Affiliate code
     */
    private function generate_affiliate_code($user_id) {
        $user = get_user_by('id', $user_id);
        $base_code = substr($user->user_login, 0, 8) . $user_id;
        $base_code = preg_replace('/[^a-zA-Z0-9]/', '', $base_code);
        
        global $wpdb;
        $code = $base_code;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}skylearn_billing_affiliates WHERE affiliate_code = %s",
            $code
        ))) {
            $code = $base_code . $counter;
            $counter++;
        }
        
        return strtoupper($code);
    }
    
    /**
     * Approve affiliate
     */
    public function approve_affiliate() {
        check_ajax_referer('skylearn_billing_affiliate_addon', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $affiliate_id = intval($_POST['affiliate_id']);
        
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'skylearn_billing_affiliates',
            array('status' => 'approved'),
            array('id' => $affiliate_id)
        );
        
        wp_send_json_success(__('Affiliate approved successfully.', 'skylearn-billing-pro'));
    }
    
    /**
     * Render affiliate dashboard shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Dashboard HTML
     */
    public function render_affiliate_dashboard($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to access the affiliate dashboard.', 'skylearn-billing-pro') . '</p>';
        }
        
        $user_id = get_current_user_id();
        $affiliate = $this->get_affiliate_by_user_id($user_id);
        
        if (!$affiliate) {
            return '<p>' . __('You are not registered as an affiliate.', 'skylearn-billing-pro') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="skylearn-affiliate-dashboard">
            <h3><?php esc_html_e('Affiliate Dashboard', 'skylearn-billing-pro'); ?></h3>
            
            <div class="affiliate-stats">
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Total Earnings:', 'skylearn-billing-pro'); ?></span>
                    <span class="stat-value">$<?php echo esc_html(number_format($affiliate->total_earnings, 2)); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Total Referrals:', 'skylearn-billing-pro'); ?></span>
                    <span class="stat-value"><?php echo esc_html($affiliate->total_referrals); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-label"><?php esc_html_e('Commission Rate:', 'skylearn-billing-pro'); ?></span>
                    <span class="stat-value"><?php echo esc_html($affiliate->commission_rate); ?>%</span>
                </div>
            </div>
            
            <div class="affiliate-link">
                <label><?php esc_html_e('Your Affiliate Link:', 'skylearn-billing-pro'); ?></label>
                <input type="text" value="<?php echo esc_attr(home_url('?ref=' . $affiliate->affiliate_code)); ?>" readonly onclick="this.select()" />
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render affiliate registration shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Registration form HTML
     */
    public function render_affiliate_registration($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . __('You must be logged in to register as an affiliate.', 'skylearn-billing-pro') . '</p>';
        }
        
        ob_start();
        ?>
        <div class="skylearn-affiliate-registration">
            <h3><?php esc_html_e('Become an Affiliate', 'skylearn-billing-pro'); ?></h3>
            
            <form id="affiliate-registration-form">
                <p><?php esc_html_e('Join our affiliate program and earn commissions for referring customers.', 'skylearn-billing-pro'); ?></p>
                
                <button type="submit" class="button"><?php esc_html_e('Register as Affiliate', 'skylearn-billing-pro'); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get affiliate by user ID
     *
     * @param int $user_id User ID
     * @return object|null Affiliate data
     */
    private function get_affiliate_by_user_id($user_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}skylearn_billing_affiliates WHERE user_id = %d",
            $user_id
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        global $wpdb;
        
        $affiliates = $wpdb->get_results(
            "SELECT a.*, u.user_login, u.user_email 
             FROM {$wpdb->prefix}skylearn_billing_affiliates a 
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID 
             ORDER BY a.created_at DESC"
        );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Affiliate Addon Settings', 'skylearn-billing-pro'); ?></h1>
            
            <h2><?php esc_html_e('Affiliate Management', 'skylearn-billing-pro'); ?></h2>
            
            <?php if (empty($affiliates)): ?>
                <p><?php esc_html_e('No affiliates registered yet.', 'skylearn-billing-pro'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('User', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Code', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Commission Rate', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Earnings', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Referrals', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Status', 'skylearn-billing-pro'); ?></th>
                            <th><?php esc_html_e('Actions', 'skylearn-billing-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($affiliates as $affiliate): ?>
                            <tr>
                                <td><?php echo esc_html($affiliate->user_login . ' (' . $affiliate->user_email . ')'); ?></td>
                                <td><?php echo esc_html($affiliate->affiliate_code); ?></td>
                                <td><?php echo esc_html($affiliate->commission_rate); ?>%</td>
                                <td>$<?php echo esc_html(number_format($affiliate->total_earnings, 2)); ?></td>
                                <td><?php echo esc_html($affiliate->total_referrals); ?></td>
                                <td>
                                    <span class="status-<?php echo esc_attr($affiliate->status); ?>">
                                        <?php echo esc_html(ucfirst($affiliate->status)); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($affiliate->status === 'pending'): ?>
                                        <button type="button" class="button approve-affiliate" data-affiliate-id="<?php echo esc_attr($affiliate->id); ?>">
                                            <?php esc_html_e('Approve', 'skylearn-billing-pro'); ?>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Handle addon activation
     *
     * @param string $addon_id Addon ID
     */
    public function on_addon_activated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            $this->create_database_tables();
            
            // Set default settings
            $default_settings = array(
                'default_commission_rate' => 10.00,
                'cookie_duration' => 30,
                'auto_approve' => false
            );
            
            add_option('skylearn_billing_affiliate_addon_settings', $default_settings);
            
            // Trigger activation hook for other integrations
            do_action('skylearn_billing_affiliate_addon_activated');
        }
    }
    
    /**
     * Handle addon deactivation
     *
     * @param string $addon_id Addon ID
     */
    public function on_addon_deactivated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Clean up if needed
            do_action('skylearn_billing_affiliate_addon_deactivated');
        }
    }
}

// Initialize the addon
SkyLearn_Billing_Pro_Affiliate_Addon::instance();