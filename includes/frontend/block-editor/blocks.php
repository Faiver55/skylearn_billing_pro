<?php
/**
 * Gutenberg Blocks for SkyLearn Billing Pro
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
 * Block Editor integration class
 */
class SkyLearn_Billing_Pro_Block_Editor {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_block_editor_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_block_assets'));
    }
    
    /**
     * Initialize blocks
     */
    public function init() {
        // Register blocks
        $this->register_blocks();
        
        // Add block category
        add_filter('block_categories_all', array($this, 'add_block_category'), 10, 2);
    }
    
    /**
     * Register all blocks
     */
    private function register_blocks() {
        // Check if block registration is available
        if (!function_exists('register_block_type')) {
            return;
        }
        
        // Checkout Block
        register_block_type('skylearn-billing/checkout', array(
            'attributes' => array(
                'gateway' => array(
                    'type' => 'string',
                    'default' => 'stripe'
                ),
                'productId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'productName' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'amount' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'currency' => array(
                    'type' => 'string',
                    'default' => 'USD'
                ),
                'type' => array(
                    'type' => 'string',
                    'default' => 'inline'
                ),
                'redirectUrl' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => array($this, 'render_checkout_block'),
            'editor_script' => 'skylearn-blocks-editor',
            'editor_style' => 'skylearn-blocks-editor',
            'style' => 'skylearn-blocks'
        ));
        
        // Checkout Button Block
        register_block_type('skylearn-billing/checkout-button', array(
            'attributes' => array(
                'gateway' => array(
                    'type' => 'string',
                    'default' => 'stripe'
                ),
                'productId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'productName' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'amount' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'currency' => array(
                    'type' => 'string',
                    'default' => 'USD'
                ),
                'buttonText' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'buttonClass' => array(
                    'type' => 'string',
                    'default' => 'skylearn-checkout-button'
                ),
                'redirectUrl' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => array($this, 'render_checkout_button_block'),
            'editor_script' => 'skylearn-blocks-editor',
            'editor_style' => 'skylearn-blocks-editor',
            'style' => 'skylearn-blocks'
        ));
        
        // Portal Block
        register_block_type('skylearn-billing/portal', array(
            'attributes' => array(
                'section' => array(
                    'type' => 'string',
                    'default' => 'dashboard'
                )
            ),
            'render_callback' => array($this, 'render_portal_block'),
            'editor_script' => 'skylearn-blocks-editor',
            'editor_style' => 'skylearn-blocks-editor',
            'style' => 'skylearn-blocks'
        ));
        
        // Login Form Block
        register_block_type('skylearn-billing/login-form', array(
            'attributes' => array(
                'redirect' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'formId' => array(
                    'type' => 'string',
                    'default' => 'skylearn-login-form'
                )
            ),
            'render_callback' => array($this, 'render_login_form_block'),
            'editor_script' => 'skylearn-blocks-editor',
            'editor_style' => 'skylearn-blocks-editor',
            'style' => 'skylearn-blocks'
        ));
        
        // User Info Block
        register_block_type('skylearn-billing/user-info', array(
            'attributes' => array(
                'field' => array(
                    'type' => 'string',
                    'default' => 'display_name'
                ),
                'default' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => array($this, 'render_user_info_block'),
            'editor_script' => 'skylearn-blocks-editor',
            'editor_style' => 'skylearn-blocks-editor',
            'style' => 'skylearn-blocks'
        ));
        
        // Course Access Block
        register_block_type('skylearn-billing/course-access', array(
            'attributes' => array(
                'courseId' => array(
                    'type' => 'string',
                    'default' => ''
                ),
                'message' => array(
                    'type' => 'string',
                    'default' => 'You have access to this course.'
                ),
                'noAccessMessage' => array(
                    'type' => 'string',
                    'default' => 'You do not have access to this course.'
                ),
                'purchaseUrl' => array(
                    'type' => 'string',
                    'default' => ''
                )
            ),
            'render_callback' => array($this, 'render_course_access_block'),
            'editor_script' => 'skylearn-blocks-editor',
            'editor_style' => 'skylearn-blocks-editor',
            'style' => 'skylearn-blocks'
        ));
    }
    
    /**
     * Add custom block category
     */
    public function add_block_category($categories, $post) {
        return array_merge(
            $categories,
            array(
                array(
                    'slug' => 'skylearn-billing',
                    'title' => __('SkyLearn Billing', 'skylearn-billing-pro'),
                    'icon' => 'money-alt'
                )
            )
        );
    }
    
    /**
     * Enqueue block editor assets
     */
    public function enqueue_block_editor_assets() {
        wp_enqueue_script(
            'skylearn-blocks-editor',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/blocks-editor.js',
            array('wp-blocks', 'wp-i18n', 'wp-element', 'wp-components', 'wp-editor'),
            SKYLEARN_BILLING_PRO_VERSION,
            true
        );
        
        wp_enqueue_style(
            'skylearn-blocks-editor',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/blocks-editor.css',
            array('wp-edit-blocks'),
            SKYLEARN_BILLING_PRO_VERSION
        );
        
        // Localize script with data
        wp_localize_script('skylearn-blocks-editor', 'skylEarnBlocks', array(
            'pluginUrl' => SKYLEARN_BILLING_PRO_PLUGIN_URL,
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('skylearn_blocks_nonce'),
            'strings' => array(
                'checkout' => __('Checkout', 'skylearn-billing-pro'),
                'checkoutButton' => __('Checkout Button', 'skylearn-billing-pro'),
                'portal' => __('Customer Portal', 'skylearn-billing-pro'),
                'loginForm' => __('Login Form', 'skylearn-billing-pro'),
                'userInfo' => __('User Info', 'skylearn-billing-pro'),
                'courseAccess' => __('Course Access', 'skylearn-billing-pro'),
                'gateway' => __('Payment Gateway', 'skylearn-billing-pro'),
                'productId' => __('Product ID', 'skylearn-billing-pro'),
                'productName' => __('Product Name', 'skylearn-billing-pro'),
                'amount' => __('Amount', 'skylearn-billing-pro'),
                'currency' => __('Currency', 'skylearn-billing-pro'),
                'type' => __('Checkout Type', 'skylearn-billing-pro'),
                'buttonText' => __('Button Text', 'skylearn-billing-pro'),
                'redirectUrl' => __('Redirect URL', 'skylearn-billing-pro'),
                'section' => __('Portal Section', 'skylearn-billing-pro'),
                'field' => __('User Field', 'skylearn-billing-pro'),
                'courseId' => __('Course ID', 'skylearn-billing-pro'),
                'message' => __('Access Message', 'skylearn-billing-pro'),
                'noAccessMessage' => __('No Access Message', 'skylearn-billing-pro'),
                'purchaseUrl' => __('Purchase URL', 'skylearn-billing-pro')
            ),
            'options' => array(
                'gateways' => array(
                    array('value' => 'stripe', 'label' => 'Stripe'),
                    array('value' => 'paddle', 'label' => 'Paddle'),
                    array('value' => 'lemonsqueezy', 'label' => 'Lemon Squeezy')
                ),
                'checkoutTypes' => array(
                    array('value' => 'inline', 'label' => __('Inline', 'skylearn-billing-pro')),
                    array('value' => 'overlay', 'label' => __('Overlay', 'skylearn-billing-pro')),
                    array('value' => 'hosted', 'label' => __('Hosted', 'skylearn-billing-pro'))
                ),
                'currencies' => array(
                    array('value' => 'USD', 'label' => 'USD'),
                    array('value' => 'EUR', 'label' => 'EUR'),
                    array('value' => 'GBP', 'label' => 'GBP'),
                    array('value' => 'CAD', 'label' => 'CAD'),
                    array('value' => 'AUD', 'label' => 'AUD')
                ),
                'portalSections' => array(
                    array('value' => 'dashboard', 'label' => __('Dashboard', 'skylearn-billing-pro')),
                    array('value' => 'orders', 'label' => __('Orders', 'skylearn-billing-pro')),
                    array('value' => 'plans', 'label' => __('Plans', 'skylearn-billing-pro')),
                    array('value' => 'downloads', 'label' => __('Downloads', 'skylearn-billing-pro')),
                    array('value' => 'addresses', 'label' => __('Addresses', 'skylearn-billing-pro')),
                    array('value' => 'account', 'label' => __('Account', 'skylearn-billing-pro'))
                ),
                'userFields' => array(
                    array('value' => 'display_name', 'label' => __('Display Name', 'skylearn-billing-pro')),
                    array('value' => 'first_name', 'label' => __('First Name', 'skylearn-billing-pro')),
                    array('value' => 'last_name', 'label' => __('Last Name', 'skylearn-billing-pro')),
                    array('value' => 'email', 'label' => __('Email', 'skylearn-billing-pro')),
                    array('value' => 'username', 'label' => __('Username', 'skylearn-billing-pro'))
                )
            )
        ));
    }
    
    /**
     * Enqueue block assets for frontend
     */
    public function enqueue_block_assets() {
        wp_enqueue_style(
            'skylearn-blocks',
            SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/blocks.css',
            array(),
            SKYLEARN_BILLING_PRO_VERSION
        );
    }
    
    // Block render callbacks
    
    /**
     * Render checkout block
     */
    public function render_checkout_block($attributes) {
        $shortcode_atts = array(
            'gateway' => $attributes['gateway'] ?? 'stripe',
            'product_id' => $attributes['productId'] ?? '',
            'product_name' => $attributes['productName'] ?? '',
            'amount' => $attributes['amount'] ?? '',
            'currency' => $attributes['currency'] ?? 'USD',
            'type' => $attributes['type'] ?? 'inline',
            'redirect_url' => $attributes['redirectUrl'] ?? ''
        );
        
        if (function_exists('skylearn_billing_pro_frontend_shortcodes')) {
            $shortcodes = skylearn_billing_pro_frontend_shortcodes();
            return $shortcodes->checkout_shortcode($shortcode_atts);
        }
        
        return '<div class="skylearn-error">' . __('SkyLearn Billing Pro shortcodes not available.', 'skylearn-billing-pro') . '</div>';
    }
    
    /**
     * Render checkout button block
     */
    public function render_checkout_button_block($attributes) {
        $shortcode_atts = array(
            'gateway' => $attributes['gateway'] ?? 'stripe',
            'product_id' => $attributes['productId'] ?? '',
            'product_name' => $attributes['productName'] ?? '',
            'amount' => $attributes['amount'] ?? '',
            'currency' => $attributes['currency'] ?? 'USD',
            'button_text' => $attributes['buttonText'] ?? '',
            'button_class' => $attributes['buttonClass'] ?? 'skylearn-checkout-button',
            'redirect_url' => $attributes['redirectUrl'] ?? ''
        );
        
        if (function_exists('skylearn_billing_pro_frontend_shortcodes')) {
            $shortcodes = skylearn_billing_pro_frontend_shortcodes();
            return $shortcodes->checkout_button_shortcode($shortcode_atts);
        }
        
        return '<div class="skylearn-error">' . __('SkyLearn Billing Pro shortcodes not available.', 'skylearn-billing-pro') . '</div>';
    }
    
    /**
     * Render portal block
     */
    public function render_portal_block($attributes) {
        $section = $attributes['section'] ?? 'dashboard';
        
        if (function_exists('skylearn_billing_pro_frontend_shortcodes')) {
            $shortcodes = skylearn_billing_pro_frontend_shortcodes();
            
            switch ($section) {
                case 'orders':
                    return $shortcodes->portal_orders_shortcode(array());
                case 'plans':
                    return $shortcodes->portal_plans_shortcode(array());
                case 'downloads':
                    return $shortcodes->portal_downloads_shortcode(array());
                case 'addresses':
                    return $shortcodes->portal_addresses_shortcode(array());
                case 'account':
                    return $shortcodes->portal_account_shortcode(array());
                case 'dashboard':
                default:
                    return $shortcodes->portal_dashboard_shortcode(array());
            }
        }
        
        return '<div class="skylearn-error">' . __('SkyLearn Billing Pro shortcodes not available.', 'skylearn-billing-pro') . '</div>';
    }
    
    /**
     * Render login form block
     */
    public function render_login_form_block($attributes) {
        $shortcode_atts = array(
            'redirect' => $attributes['redirect'] ?? '',
            'form_id' => $attributes['formId'] ?? 'skylearn-login-form'
        );
        
        if (function_exists('skylearn_billing_pro_frontend_shortcodes')) {
            $shortcodes = skylearn_billing_pro_frontend_shortcodes();
            return $shortcodes->login_form_shortcode($shortcode_atts);
        }
        
        return '<div class="skylearn-error">' . __('SkyLearn Billing Pro shortcodes not available.', 'skylearn-billing-pro') . '</div>';
    }
    
    /**
     * Render user info block
     */
    public function render_user_info_block($attributes) {
        $shortcode_atts = array(
            'field' => $attributes['field'] ?? 'display_name',
            'default' => $attributes['default'] ?? ''
        );
        
        if (function_exists('skylearn_billing_pro_frontend_shortcodes')) {
            $shortcodes = skylearn_billing_pro_frontend_shortcodes();
            return $shortcodes->user_info_shortcode($shortcode_atts);
        }
        
        return '<div class="skylearn-error">' . __('SkyLearn Billing Pro shortcodes not available.', 'skylearn-billing-pro') . '</div>';
    }
    
    /**
     * Render course access block
     */
    public function render_course_access_block($attributes) {
        $shortcode_atts = array(
            'course_id' => $attributes['courseId'] ?? '',
            'message' => $attributes['message'] ?? __('You have access to this course.', 'skylearn-billing-pro'),
            'no_access_message' => $attributes['noAccessMessage'] ?? __('You do not have access to this course.', 'skylearn-billing-pro'),
            'purchase_url' => $attributes['purchaseUrl'] ?? ''
        );
        
        if (function_exists('skylearn_billing_pro_frontend_shortcodes')) {
            $shortcodes = skylearn_billing_pro_frontend_shortcodes();
            return $shortcodes->course_access_shortcode($shortcode_atts);
        }
        
        return '<div class="skylearn-error">' . __('SkyLearn Billing Pro shortcodes not available.', 'skylearn-billing-pro') . '</div>';
    }
}

/**
 * Initialize block editor
 */
function skylearn_billing_pro_block_editor() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Block_Editor();
    }
    
    return $instance;
}