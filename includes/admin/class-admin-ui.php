<?php
/**
 * Enhanced Admin UI class with contextual help integration
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
 * Enhanced Admin UI class
 */
class SkyLearn_Billing_Pro_Admin_UI {
    
    /**
     * Single instance of the class
     *
     * @var SkyLearn_Billing_Pro_Admin_UI
     */
    private static $_instance = null;
    
    /**
     * Get single instance
     *
     * @return SkyLearn_Billing_Pro_Admin_UI
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
        add_action('admin_init', array($this, 'init_admin_ui'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_ui_scripts'));
        add_filter('admin_footer_text', array($this, 'admin_footer_text'));
        add_action('in_admin_header', array($this, 'add_help_tab'));
    }
    
    /**
     * Initialize admin UI enhancements
     */
    public function init_admin_ui() {
        // Add help contextual help to all our admin pages
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'skylearn-billing-pro') !== false) {
            $this->add_contextual_help($screen);
        }
    }
    
    /**
     * Enqueue UI enhancement scripts and styles
     */
    public function enqueue_ui_scripts($hook) {
        // Only load on our admin pages
        if (strpos($hook, 'skylearn-billing-pro') === false) {
            return;
        }
        
        wp_enqueue_style(
            'skylearn-admin-ui',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/admin-ui.css',
            array(),
            SKYLEARN_BILLING_PRO_VERSION
        );
        
        wp_enqueue_script(
            'skylearn-admin-ui',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/admin-ui.js',
            array('jquery'),
            SKYLEARN_BILLING_PRO_VERSION,
            true
        );
        
        // Localize script with help data
        wp_localize_script('skylearn-admin-ui', 'skylernAdminUI', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_admin_ui_nonce'),
            'strings' => array(
                'help' => __('Help', 'skylearn-billing-pro'),
                'close' => __('Close', 'skylearn-billing-pro'),
                'loading' => __('Loading...', 'skylearn-billing-pro'),
                'error' => __('Error loading help content.', 'skylearn-billing-pro')
            )
        ));
    }
    
    /**
     * Add contextual help to admin screens
     */
    public function add_contextual_help($screen) {
        $onboarding = skylearn_billing_pro_onboarding();
        $page = $this->get_page_from_screen($screen);
        $help = $onboarding->get_contextual_help($page);
        
        if (!$help) {
            return;
        }
        
        // Add help tab
        $screen->add_help_tab(array(
            'id' => 'skylearn-help-' . $page,
            'title' => __('Help', 'skylearn-billing-pro'),
            'content' => '<h3>' . esc_html($help['title']) . '</h3>' .
                        '<p>' . esc_html($help['content']) . '</p>' .
                        '<p><a href="' . esc_url($help['links']['docs']) . '" target="_blank">' . __('Read Documentation', 'skylearn-billing-pro') . '</a> | ' .
                        '<a href="' . esc_url($help['links']['support']) . '" target="_blank">' . __('Get Support', 'skylearn-billing-pro') . '</a></p>'
        ));
        
        // Add help sidebar
        $screen->set_help_sidebar(
            '<h4>' . __('More Resources', 'skylearn-billing-pro') . '</h4>' .
            '<p><a href="https://skyian.com/skylearn-billing/doc/" target="_blank">' . __('Complete Documentation', 'skylearn-billing-pro') . '</a></p>' .
            '<p><a href="https://skyian.com/skylearn-billing/support/" target="_blank">' . __('Contact Support', 'skylearn-billing-pro') . '</a></p>' .
            '<p><a href="https://skyian.com/skylearn-billing/community/" target="_blank">' . __('Community Forum', 'skylearn-billing-pro') . '</a></p>' .
            '<p><a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro-help')) . '">' . __('Help Center', 'skylearn-billing-pro') . '</a></p>'
        );
    }
    
    /**
     * Get page identifier from screen
     */
    private function get_page_from_screen($screen) {
        $page_map = array(
            'skylearn-billing-pro_page_skylearn-billing-pro-license' => 'license',
            'skylearn-billing-pro_page_skylearn-billing-pro-lms' => 'lms',
            'skylearn-billing-pro_page_skylearn-billing-pro-payments' => 'payments',
            'skylearn-billing-pro_page_skylearn-billing-pro-products' => 'products',
            'skylearn-billing-pro_page_skylearn-billing-pro-bundles' => 'products',
            'skylearn-billing-pro_page_skylearn-billing-pro-email' => 'email',
            'skylearn-billing-pro_page_skylearn-billing-pro-reports' => 'reports',
            'skylearn-billing-pro_page_skylearn-billing-pro-subscriptions' => 'subscriptions',
            'skylearn-billing-pro_page_skylearn-billing-pro-memberships' => 'memberships',
            'skylearn-billing-pro_page_skylearn-billing-pro-automation' => 'automation',
            'skylearn-billing-pro_page_skylearn-billing-pro-addons' => 'addons',
            'toplevel_page_skylearn-billing-pro' => 'general'
        );
        
        return isset($page_map[$screen->id]) ? $page_map[$screen->id] : 'general';
    }
    
    /**
     * Add help tab to admin header
     */
    public function add_help_tab() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'skylearn-billing-pro') === false) {
            return;
        }
        
        // Add help icon to admin header
        echo '<style>
            .skylearn-help-header-icon {
                position: fixed;
                top: 32px;
                right: 20px;
                z-index: 9999;
                width: 40px;
                height: 40px;
                background: #0073aa;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                transition: background-color 0.2s;
            }
            .skylearn-help-header-icon:hover {
                background: #005a87;
            }
            .skylearn-help-header-icon .dashicons {
                color: white;
                font-size: 18px;
            }
        </style>';
        
        echo '<div class="skylearn-help-header-icon" id="skylearn-help-toggle" title="' . esc_attr__('Get Help', 'skylearn-billing-pro') . '">';
        echo '<span class="dashicons dashicons-editor-help"></span>';
        echo '</div>';
    }
    
    /**
     * Add help tooltips to form fields
     */
    public function render_field_help($field_id, $content, $links = array()) {
        $tooltip_id = 'skylearn-field-help-' . sanitize_key($field_id);
        
        echo '<span class="skylearn-field-help" data-tooltip-id="' . esc_attr($tooltip_id) . '">';
        echo '<span class="dashicons dashicons-editor-help"></span>';
        echo '<div class="skylearn-field-tooltip" id="' . esc_attr($tooltip_id) . '">';
        echo '<p>' . esc_html($content) . '</p>';
        
        if (!empty($links)) {
            echo '<div class="skylearn-field-help-links">';
            foreach ($links as $link_text => $link_url) {
                echo '<a href="' . esc_url($link_url) . '" target="_blank">' . esc_html($link_text) . '</a>';
                if ($link_text !== array_key_last($links)) {
                    echo ' | ';
                }
            }
            echo '</div>';
        }
        
        echo '</div>';
        echo '</span>';
    }
    
    /**
     * Render quick action buttons
     */
    public function render_quick_actions() {
        $current_page = sanitize_text_field($_GET['page'] ?? '');
        
        echo '<div class="skylearn-quick-actions">';
        echo '<h3>' . esc_html__('Quick Actions', 'skylearn-billing-pro') . '</h3>';
        
        $actions = array();
        
        switch ($current_page) {
            case 'skylearn-billing-pro':
                $actions = array(
                    'setup_wizard' => array(
                        'title' => __('Run Setup Wizard', 'skylearn-billing-pro'),
                        'url' => admin_url('admin.php?page=skylearn-billing-pro&onboarding=1'),
                        'icon' => 'dashicons-welcome-learn-more'
                    ),
                    'test_payments' => array(
                        'title' => __('Test Payment Gateway', 'skylearn-billing-pro'),
                        'url' => admin_url('admin.php?page=skylearn-billing-pro-payments&action=test'),
                        'icon' => 'dashicons-credit-card'
                    )
                );
                break;
                
            case 'skylearn-billing-pro-products':
                $actions = array(
                    'add_product' => array(
                        'title' => __('Add New Product', 'skylearn-billing-pro'),
                        'url' => admin_url('admin.php?page=skylearn-billing-pro-products&action=add'),
                        'icon' => 'dashicons-plus-alt'
                    ),
                    'import_products' => array(
                        'title' => __('Import Products', 'skylearn-billing-pro'),
                        'url' => admin_url('admin.php?page=skylearn-billing-pro-products&tab=import-export'),
                        'icon' => 'dashicons-upload'
                    )
                );
                break;
                
            case 'skylearn-billing-pro-lms':
                $actions = array(
                    'map_courses' => array(
                        'title' => __('Map Courses', 'skylearn-billing-pro'),
                        'url' => admin_url('admin.php?page=skylearn-billing-pro-lms&tab=mapping'),
                        'icon' => 'dashicons-networking'
                    ),
                    'test_enrollment' => array(
                        'title' => __('Test Enrollment', 'skylearn-billing-pro'),
                        'url' => admin_url('admin.php?page=skylearn-billing-pro-lms&action=test'),
                        'icon' => 'dashicons-groups'
                    )
                );
                break;
        }
        
        if (!empty($actions)) {
            echo '<div class="skylearn-action-buttons">';
            foreach ($actions as $action) {
                echo '<a href="' . esc_url($action['url']) . '" class="button button-secondary">';
                echo '<span class="dashicons ' . esc_attr($action['icon']) . '"></span>';
                echo esc_html($action['title']);
                echo '</a>';
            }
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Render status indicators
     */
    public function render_status_indicators() {
        echo '<div class="skylearn-status-indicators">';
        echo '<h3>' . esc_html__('System Status', 'skylearn-billing-pro') . '</h3>';
        
        $indicators = array();
        
        // License status
        $licensing_manager = skylearn_billing_pro_licensing();
        if ($licensing_manager->is_license_active()) {
            $indicators['license'] = array(
                'status' => 'good',
                'title' => __('License Active', 'skylearn-billing-pro'),
                'description' => __('Your license is active and valid.', 'skylearn-billing-pro')
            );
        } else {
            $indicators['license'] = array(
                'status' => 'warning',
                'title' => __('License Inactive', 'skylearn-billing-pro'),
                'description' => __('Please activate your license to unlock Pro features.', 'skylearn-billing-pro')
            );
        }
        
        // LMS connection status
        $lms_manager = skylearn_billing_pro_lms_manager();
        $detected_lms = $lms_manager->get_detected_lms();
        if (!empty($detected_lms)) {
            $indicators['lms'] = array(
                'status' => 'good',
                'title' => __('LMS Connected', 'skylearn-billing-pro'),
                'description' => sprintf(__('%d LMS platform(s) detected.', 'skylearn-billing-pro'), count($detected_lms))
            );
        } else {
            $indicators['lms'] = array(
                'status' => 'error',
                'title' => __('No LMS Detected', 'skylearn-billing-pro'),
                'description' => __('Install an LMS plugin like LearnDash for course enrollment.', 'skylearn-billing-pro')
            );
        }
        
        // Payment gateway status
        $options = get_option('skylearn_billing_pro_options', array());
        $payment_configured = false;
        if (isset($options['payment_settings'])) {
            $payment_configured = !empty($options['payment_settings']['stripe']['enabled']) || 
                                 !empty($options['payment_settings']['lemonsqueezy']['enabled']);
        }
        
        if ($payment_configured) {
            $indicators['payment'] = array(
                'status' => 'good',
                'title' => __('Payment Gateway Ready', 'skylearn-billing-pro'),
                'description' => __('Payment gateways are configured and ready.', 'skylearn-billing-pro')
            );
        } else {
            $indicators['payment'] = array(
                'status' => 'warning',
                'title' => __('Payment Gateway Not Configured', 'skylearn-billing-pro'),
                'description' => __('Configure Stripe or Lemon Squeezy to accept payments.', 'skylearn-billing-pro')
            );
        }
        
        echo '<div class="skylearn-indicators-grid">';
        foreach ($indicators as $key => $indicator) {
            $status_class = 'skylearn-status-' . $indicator['status'];
            echo '<div class="skylearn-status-indicator ' . esc_attr($status_class) . '">';
            echo '<div class="skylearn-status-icon">';
            
            switch ($indicator['status']) {
                case 'good':
                    echo '<span class="dashicons dashicons-yes-alt"></span>';
                    break;
                case 'warning':
                    echo '<span class="dashicons dashicons-warning"></span>';
                    break;
                case 'error':
                    echo '<span class="dashicons dashicons-dismiss"></span>';
                    break;
            }
            
            echo '</div>';
            echo '<div class="skylearn-status-content">';
            echo '<h4>' . esc_html($indicator['title']) . '</h4>';
            echo '<p>' . esc_html($indicator['description']) . '</p>';
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
        
        echo '<p><a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro-status')) . '" class="button button-secondary">' . esc_html__('View Detailed Status', 'skylearn-billing-pro') . '</a></p>';
        
        echo '</div>';
    }
    
    /**
     * Custom admin footer text
     */
    public function admin_footer_text($text) {
        $screen = get_current_screen();
        if ($screen && strpos($screen->id, 'skylearn-billing-pro') !== false) {
            $text = sprintf(
                __('Thank you for using %1$s. Need help? %2$s', 'skylearn-billing-pro'),
                '<strong>Skylearn Billing Pro</strong>',
                '<a href="https://skyian.com/skylearn-billing/support/" target="_blank">' . __('Contact Support', 'skylearn-billing-pro') . '</a>'
            );
        }
        
        return $text;
    }
    
    /**
     * Render dashboard widgets
     */
    public function render_dashboard_widgets() {
        echo '<div class="skylearn-dashboard-widgets">';
        
        // Revenue widget
        echo '<div class="skylearn-widget skylearn-revenue-widget">';
        echo '<h3>' . esc_html__('Revenue Overview', 'skylearn-billing-pro') . '</h3>';
        echo '<div class="skylearn-widget-content">';
        echo '<div class="skylearn-revenue-stats">';
        echo '<div class="skylearn-stat">';
        echo '<span class="skylearn-stat-value">$0</span>';
        echo '<span class="skylearn-stat-label">' . esc_html__('This Month', 'skylearn-billing-pro') . '</span>';
        echo '</div>';
        echo '<div class="skylearn-stat">';
        echo '<span class="skylearn-stat-value">$0</span>';
        echo '<span class="skylearn-stat-label">' . esc_html__('Last Month', 'skylearn-billing-pro') . '</span>';
        echo '</div>';
        echo '</div>';
        echo '<a href="' . esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports')) . '" class="button button-secondary">' . esc_html__('View Reports', 'skylearn-billing-pro') . '</a>';
        echo '</div>';
        echo '</div>';
        
        // Recent activity widget
        echo '<div class="skylearn-widget skylearn-activity-widget">';
        echo '<h3>' . esc_html__('Recent Activity', 'skylearn-billing-pro') . '</h3>';
        echo '<div class="skylearn-widget-content">';
        echo '<p>' . esc_html__('No recent activity found.', 'skylearn-billing-pro') . '</p>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>';
    }
}

/**
 * Get the admin UI instance
 */
function skylearn_billing_pro_admin_ui() {
    return SkyLearn_Billing_Pro_Admin_UI::instance();
}

// Initialize the admin UI system
skylearn_billing_pro_admin_ui();