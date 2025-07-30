<?php
/**
 * Frontend Shortcodes for SkyLearn Billing Pro
 *
 * Comprehensive shortcodes for checkout, portal, and other frontend features
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
 * Frontend Shortcodes class
 */
class SkyLearn_Billing_Pro_Frontend_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Initialize shortcodes
     */
    public function init() {
        // Checkout shortcodes
        add_shortcode('skylearn_checkout', array($this, 'checkout_shortcode'));
        add_shortcode('skylearn_checkout_button', array($this, 'checkout_button_shortcode'));
        
        // Thank you page
        add_shortcode('skylearn_thank_you', array($this, 'thank_you_shortcode'));
        
        // Portal shortcodes
        add_shortcode('skylearn_portal', array($this, 'portal_shortcode'));
        add_shortcode('skylearn_portal_dashboard', array($this, 'portal_dashboard_shortcode'));
        add_shortcode('skylearn_portal_orders', array($this, 'portal_orders_shortcode'));
        add_shortcode('skylearn_portal_plans', array($this, 'portal_plans_shortcode'));
        add_shortcode('skylearn_portal_downloads', array($this, 'portal_downloads_shortcode'));
        add_shortcode('skylearn_portal_addresses', array($this, 'portal_addresses_shortcode'));
        add_shortcode('skylearn_portal_account', array($this, 'portal_account_shortcode'));
        
        // Utility shortcodes
        add_shortcode('skylearn_login_form', array($this, 'login_form_shortcode'));
        add_shortcode('skylearn_user_info', array($this, 'user_info_shortcode'));
        add_shortcode('skylearn_course_access', array($this, 'course_access_shortcode'));
    }
    
    /**
     * Enqueue scripts and styles
     */
    public function enqueue_scripts() {
        // Only enqueue on pages that use our shortcodes or on our generated pages
        if ($this->should_enqueue_assets()) {
            wp_enqueue_style(
                'skylearn-frontend',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                SKYLEARN_BILLING_PRO_VERSION
            );
            
            wp_enqueue_script(
                'skylearn-frontend',
                SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/js/frontend.js',
                array('jquery'),
                SKYLEARN_BILLING_PRO_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('skylearn-frontend', 'skylearn_frontend', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('skylearn_frontend_nonce'),
                'strings' => array(
                    'loading' => __('Loading...', 'skylearn-billing-pro'),
                    'error' => __('An error occurred. Please try again.', 'skylearn-billing-pro'),
                    'confirm_cancel' => __('Are you sure you want to cancel this subscription?', 'skylearn-billing-pro'),
                    'login_required' => __('Please log in to access this feature.', 'skylearn-billing-pro')
                )
            ));
        }
    }
    
    /**
     * Check if we should enqueue assets
     */
    private function should_enqueue_assets() {
        global $post;
        
        // Check if current page has our shortcodes
        if ($post && has_shortcode($post->post_content, 'skylearn_')) {
            return true;
        }
        
        // Check if it's one of our generated pages
        $our_pages = get_option('skylearn_billing_pro_pages', array());
        if ($post && in_array($post->ID, $our_pages)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Main checkout shortcode
     */
    public function checkout_shortcode($atts) {
        // Use existing checkout shortcode logic
        if (class_exists('SkyLearn_Billing_Pro_Checkout_Shortcodes')) {
            $checkout_shortcodes = skylearn_billing_pro_checkout_shortcodes();
            return $checkout_shortcodes->checkout_shortcode($atts);
        }
        
        return '<div class="skylearn-error">' . __('Checkout system not available.', 'skylearn-billing-pro') . '</div>';
    }
    
    /**
     * Checkout button shortcode
     */
    public function checkout_button_shortcode($atts) {
        // Use existing checkout button shortcode logic
        if (class_exists('SkyLearn_Billing_Pro_Checkout_Shortcodes')) {
            $checkout_shortcodes = skylearn_billing_pro_checkout_shortcodes();
            return $checkout_shortcodes->checkout_button_shortcode($atts);
        }
        
        return '<div class="skylearn-error">' . __('Checkout system not available.', 'skylearn-billing-pro') . '</div>';
    }
    
    /**
     * Thank you page shortcode
     */
    public function thank_you_shortcode($atts) {
        $atts = shortcode_atts(array(
            'order_id' => '',
            'session_id' => ''
        ), $atts, 'skylearn_thank_you');
        
        // Get order details from URL parameters or attributes
        $order_id = !empty($atts['order_id']) ? $atts['order_id'] : ($_GET['order_id'] ?? '');
        $session_id = !empty($atts['session_id']) ? $atts['session_id'] : ($_GET['session_id'] ?? '');
        
        ob_start();
        ?>
        <div class="skylearn-thank-you-container" role="main" aria-labelledby="skylearn-thank-you-title">
            <div class="skylearn-thank-you-content">
                <div class="skylearn-success-icon" aria-hidden="true">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" fill="#4ade80"/>
                        <path d="M8 12l2 2 4-4" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                
                <h1 id="skylearn-thank-you-title" class="skylearn-thank-you-title">
                    <?php _e('Thank You for Your Purchase!', 'skylearn-billing-pro'); ?>
                </h1>
                
                <p class="skylearn-thank-you-message">
                    <?php _e('Your payment has been processed successfully. You will receive a confirmation email shortly.', 'skylearn-billing-pro'); ?>
                </p>
                
                <?php if ($order_id): ?>
                    <div class="skylearn-order-details">
                        <p><strong><?php _e('Order ID:', 'skylearn-billing-pro'); ?></strong> <?php echo esc_html($order_id); ?></p>
                    </div>
                <?php endif; ?>
                
                <div class="skylearn-thank-you-actions">
                    <a href="<?php echo esc_url($this->get_portal_url()); ?>" class="skylearn-button skylearn-button-primary">
                        <?php _e('Access Your Dashboard', 'skylearn-billing-pro'); ?>
                    </a>
                    
                    <a href="<?php echo esc_url(home_url()); ?>" class="skylearn-button skylearn-button-secondary">
                        <?php _e('Back to Home', 'skylearn-billing-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Main portal shortcode
     */
    public function portal_shortcode($atts) {
        if (!is_user_logged_in()) {
            return $this->login_form_shortcode($atts);
        }
        
        ob_start();
        ?>
        <div class="skylearn-portal-container" role="main">
            <div class="skylearn-portal-navigation" role="navigation" aria-label="<?php esc_attr_e('Portal Navigation', 'skylearn-billing-pro'); ?>">
                <ul class="skylearn-portal-nav-list">
                    <li><a href="<?php echo esc_url($this->get_portal_url('dashboard')); ?>" class="skylearn-portal-nav-link">
                        <?php _e('Dashboard', 'skylearn-billing-pro'); ?>
                    </a></li>
                    <li><a href="<?php echo esc_url($this->get_portal_url('orders')); ?>" class="skylearn-portal-nav-link">
                        <?php _e('Order History', 'skylearn-billing-pro'); ?>
                    </a></li>
                    <li><a href="<?php echo esc_url($this->get_portal_url('plans')); ?>" class="skylearn-portal-nav-link">
                        <?php _e('My Plans', 'skylearn-billing-pro'); ?>
                    </a></li>
                    <li><a href="<?php echo esc_url($this->get_portal_url('downloads')); ?>" class="skylearn-portal-nav-link">
                        <?php _e('Downloads', 'skylearn-billing-pro'); ?>
                    </a></li>
                    <li><a href="<?php echo esc_url($this->get_portal_url('addresses')); ?>" class="skylearn-portal-nav-link">
                        <?php _e('Addresses', 'skylearn-billing-pro'); ?>
                    </a></li>
                    <li><a href="<?php echo esc_url($this->get_portal_url('account')); ?>" class="skylearn-portal-nav-link">
                        <?php _e('Account Settings', 'skylearn-billing-pro'); ?>
                    </a></li>
                </ul>
            </div>
            
            <div class="skylearn-portal-content">
                <?php echo do_shortcode('[skylearn_portal_dashboard]'); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Portal dashboard shortcode
     */
    public function portal_dashboard_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="skylearn-error">' . __('Please log in to access your dashboard.', 'skylearn-billing-pro') . '</div>';
        }
        
        $user = wp_get_current_user();
        $user_id = $user->ID;
        
        // Get user data
        $recent_orders = $this->get_user_recent_orders($user_id, 3);
        $active_subscriptions = $this->get_user_active_subscriptions($user_id);
        $available_downloads = $this->get_user_downloads($user_id, 5);
        
        ob_start();
        ?>
        <div class="skylearn-dashboard" role="main" aria-labelledby="skylearn-dashboard-title">
            <header class="skylearn-dashboard-header">
                <h1 id="skylearn-dashboard-title" class="skylearn-dashboard-title">
                    <?php printf(__('Welcome back, %s!', 'skylearn-billing-pro'), esc_html($user->display_name)); ?>
                </h1>
            </header>
            
            <div class="skylearn-dashboard-grid">
                <!-- Recent Orders -->
                <div class="skylearn-dashboard-card">
                    <h2 class="skylearn-card-title"><?php _e('Recent Orders', 'skylearn-billing-pro'); ?></h2>
                    <?php if (!empty($recent_orders)): ?>
                        <div class="skylearn-orders-list">
                            <?php foreach ($recent_orders as $order): ?>
                                <div class="skylearn-order-item">
                                    <span class="skylearn-order-name"><?php echo esc_html($order['name']); ?></span>
                                    <span class="skylearn-order-date"><?php echo esc_html($order['date']); ?></span>
                                    <span class="skylearn-order-status skylearn-status-<?php echo esc_attr($order['status']); ?>">
                                        <?php echo esc_html($order['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo esc_url($this->get_portal_url('orders')); ?>" class="skylearn-view-all-link">
                            <?php _e('View All Orders', 'skylearn-billing-pro'); ?>
                        </a>
                    <?php else: ?>
                        <p class="skylearn-empty-state"><?php _e('No orders found.', 'skylearn-billing-pro'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Active Subscriptions -->
                <div class="skylearn-dashboard-card">
                    <h2 class="skylearn-card-title"><?php _e('Active Subscriptions', 'skylearn-billing-pro'); ?></h2>
                    <?php if (!empty($active_subscriptions)): ?>
                        <div class="skylearn-subscriptions-list">
                            <?php foreach ($active_subscriptions as $subscription): ?>
                                <div class="skylearn-subscription-item">
                                    <span class="skylearn-subscription-name"><?php echo esc_html($subscription['name']); ?></span>
                                    <span class="skylearn-subscription-next"><?php printf(__('Next: %s', 'skylearn-billing-pro'), esc_html($subscription['next_payment'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo esc_url($this->get_portal_url('plans')); ?>" class="skylearn-view-all-link">
                            <?php _e('Manage Plans', 'skylearn-billing-pro'); ?>
                        </a>
                    <?php else: ?>
                        <p class="skylearn-empty-state"><?php _e('No active subscriptions.', 'skylearn-billing-pro'); ?></p>
                    <?php endif; ?>
                </div>
                
                <!-- Downloads -->
                <div class="skylearn-dashboard-card">
                    <h2 class="skylearn-card-title"><?php _e('Available Downloads', 'skylearn-billing-pro'); ?></h2>
                    <?php if (!empty($available_downloads)): ?>
                        <div class="skylearn-downloads-list">
                            <?php foreach ($available_downloads as $download): ?>
                                <div class="skylearn-download-item">
                                    <a href="<?php echo esc_url($download['url']); ?>" class="skylearn-download-link" download>
                                        <?php echo esc_html($download['name']); ?>
                                    </a>
                                    <span class="skylearn-download-size"><?php echo esc_html($download['size']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="<?php echo esc_url($this->get_portal_url('downloads')); ?>" class="skylearn-view-all-link">
                            <?php _e('View All Downloads', 'skylearn-billing-pro'); ?>
                        </a>
                    <?php else: ?>
                        <p class="skylearn-empty-state"><?php _e('No downloads available.', 'skylearn-billing-pro'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Portal orders shortcode
     */
    public function portal_orders_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="skylearn-error">' . __('Please log in to view your orders.', 'skylearn-billing-pro') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $orders = $this->get_user_orders($user_id);
        
        ob_start();
        ?>
        <div class="skylearn-orders-page" role="main" aria-labelledby="skylearn-orders-title">
            <header class="skylearn-page-header">
                <h1 id="skylearn-orders-title"><?php _e('Order History', 'skylearn-billing-pro'); ?></h1>
            </header>
            
            <?php if (!empty($orders)): ?>
                <div class="skylearn-orders-table-container">
                    <table class="skylearn-orders-table" role="table" aria-label="<?php esc_attr_e('Order History', 'skylearn-billing-pro'); ?>">
                        <thead>
                            <tr role="row">
                                <th scope="col"><?php _e('Order', 'skylearn-billing-pro'); ?></th>
                                <th scope="col"><?php _e('Date', 'skylearn-billing-pro'); ?></th>
                                <th scope="col"><?php _e('Status', 'skylearn-billing-pro'); ?></th>
                                <th scope="col"><?php _e('Total', 'skylearn-billing-pro'); ?></th>
                                <th scope="col"><?php _e('Actions', 'skylearn-billing-pro'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr role="row">
                                    <td data-label="<?php esc_attr_e('Order', 'skylearn-billing-pro'); ?>">
                                        <strong><?php echo esc_html($order['id']); ?></strong><br>
                                        <span class="skylearn-order-products"><?php echo esc_html($order['products']); ?></span>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Date', 'skylearn-billing-pro'); ?>">
                                        <?php echo esc_html($order['date']); ?>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Status', 'skylearn-billing-pro'); ?>">
                                        <span class="skylearn-status skylearn-status-<?php echo esc_attr($order['status']); ?>">
                                            <?php echo esc_html(ucfirst($order['status'])); ?>
                                        </span>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Total', 'skylearn-billing-pro'); ?>">
                                        <?php echo esc_html($order['total']); ?>
                                    </td>
                                    <td data-label="<?php esc_attr_e('Actions', 'skylearn-billing-pro'); ?>">
                                        <button type="button" class="skylearn-button skylearn-button-small" onclick="skylearn.viewOrder('<?php echo esc_js($order['id']); ?>')">
                                            <?php _e('View', 'skylearn-billing-pro'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="skylearn-empty-state">
                    <h2><?php _e('No Orders Found', 'skylearn-billing-pro'); ?></h2>
                    <p><?php _e('You haven\'t made any purchases yet.', 'skylearn-billing-pro'); ?></p>
                    <a href="<?php echo esc_url(home_url()); ?>" class="skylearn-button skylearn-button-primary">
                        <?php _e('Browse Courses', 'skylearn-billing-pro'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Portal plans shortcode
     */
    public function portal_plans_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="skylearn-error">' . __('Please log in to view your plans.', 'skylearn-billing-pro') . '</div>';
        }
        
        // Load existing plans template
        ob_start();
        include SKYLEARN_BILLING_PRO_PLUGIN_DIR . 'templates/portal/plans.php';
        return ob_get_clean();
    }
    
    /**
     * Portal downloads shortcode
     */
    public function portal_downloads_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="skylearn-error">' . __('Please log in to access downloads.', 'skylearn-billing-pro') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $downloads = $this->get_user_downloads($user_id);
        
        ob_start();
        ?>
        <div class="skylearn-downloads-page" role="main" aria-labelledby="skylearn-downloads-title">
            <header class="skylearn-page-header">
                <h1 id="skylearn-downloads-title"><?php _e('Downloads', 'skylearn-billing-pro'); ?></h1>
            </header>
            
            <?php if (!empty($downloads)): ?>
                <div class="skylearn-downloads-grid">
                    <?php foreach ($downloads as $download): ?>
                        <div class="skylearn-download-card">
                            <div class="skylearn-download-icon" aria-hidden="true">
                                <?php echo $this->get_file_icon($download['type']); ?>
                            </div>
                            <h3 class="skylearn-download-title"><?php echo esc_html($download['name']); ?></h3>
                            <p class="skylearn-download-description"><?php echo esc_html($download['description']); ?></p>
                            <div class="skylearn-download-meta">
                                <span class="skylearn-download-size"><?php echo esc_html($download['size']); ?></span>
                                <span class="skylearn-download-type"><?php echo esc_html(strtoupper($download['type'])); ?></span>
                            </div>
                            <a href="<?php echo esc_url($download['url']); ?>" class="skylearn-button skylearn-button-primary" download>
                                <?php _e('Download', 'skylearn-billing-pro'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="skylearn-empty-state">
                    <h2><?php _e('No Downloads Available', 'skylearn-billing-pro'); ?></h2>
                    <p><?php _e('Downloads will appear here when you purchase courses with downloadable content.', 'skylearn-billing-pro'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Portal addresses shortcode
     */
    public function portal_addresses_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="skylearn-error">' . __('Please log in to manage addresses.', 'skylearn-billing-pro') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $addresses = $this->get_user_addresses($user_id);
        
        ob_start();
        ?>
        <div class="skylearn-addresses-page" role="main" aria-labelledby="skylearn-addresses-title">
            <header class="skylearn-page-header">
                <h1 id="skylearn-addresses-title"><?php _e('Addresses', 'skylearn-billing-pro'); ?></h1>
                <button type="button" class="skylearn-button skylearn-button-primary" onclick="skylearn.addAddress()">
                    <?php _e('Add New Address', 'skylearn-billing-pro'); ?>
                </button>
            </header>
            
            <?php if (!empty($addresses)): ?>
                <div class="skylearn-addresses-grid">
                    <?php foreach ($addresses as $address): ?>
                        <div class="skylearn-address-card" data-address-id="<?php echo esc_attr($address['id']); ?>">
                            <div class="skylearn-address-content">
                                <h3 class="skylearn-address-type"><?php echo esc_html($address['type']); ?></h3>
                                <div class="skylearn-address-details">
                                    <p><?php echo esc_html($address['name']); ?></p>
                                    <p><?php echo esc_html($address['street']); ?></p>
                                    <p><?php echo esc_html($address['city'] . ', ' . $address['state'] . ' ' . $address['zip']); ?></p>
                                    <p><?php echo esc_html($address['country']); ?></p>
                                </div>
                            </div>
                            <div class="skylearn-address-actions">
                                <button type="button" class="skylearn-button skylearn-button-small" onclick="skylearn.editAddress('<?php echo esc_js($address['id']); ?>')">
                                    <?php _e('Edit', 'skylearn-billing-pro'); ?>
                                </button>
                                <button type="button" class="skylearn-button skylearn-button-small skylearn-button-danger" onclick="skylearn.deleteAddress('<?php echo esc_js($address['id']); ?>')">
                                    <?php _e('Delete', 'skylearn-billing-pro'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="skylearn-empty-state">
                    <h2><?php _e('No Addresses Saved', 'skylearn-billing-pro'); ?></h2>
                    <p><?php _e('Add addresses to speed up your checkout process.', 'skylearn-billing-pro'); ?></p>
                    <button type="button" class="skylearn-button skylearn-button-primary" onclick="skylearn.addAddress()">
                        <?php _e('Add Your First Address', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Portal account shortcode
     */
    public function portal_account_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="skylearn-error">' . __('Please log in to manage your account.', 'skylearn-billing-pro') . '</div>';
        }
        
        $user = wp_get_current_user();
        
        ob_start();
        ?>
        <div class="skylearn-account-page" role="main" aria-labelledby="skylearn-account-title">
            <header class="skylearn-page-header">
                <h1 id="skylearn-account-title"><?php _e('Account Settings', 'skylearn-billing-pro'); ?></h1>
            </header>
            
            <form class="skylearn-account-form" method="post" action="">
                <?php wp_nonce_field('skylearn_update_account', 'skylearn_account_nonce'); ?>
                
                <div class="skylearn-form-section">
                    <h2><?php _e('Personal Information', 'skylearn-billing-pro'); ?></h2>
                    
                    <div class="skylearn-field-row">
                        <div class="skylearn-field-col">
                            <label for="first_name"><?php _e('First Name', 'skylearn-billing-pro'); ?></label>
                            <input type="text" id="first_name" name="first_name" value="<?php echo esc_attr($user->first_name); ?>" class="skylearn-input" />
                        </div>
                        <div class="skylearn-field-col">
                            <label for="last_name"><?php _e('Last Name', 'skylearn-billing-pro'); ?></label>
                            <input type="text" id="last_name" name="last_name" value="<?php echo esc_attr($user->last_name); ?>" class="skylearn-input" />
                        </div>
                    </div>
                    
                    <div class="skylearn-field-row">
                        <div class="skylearn-field-col">
                            <label for="display_name"><?php _e('Display Name', 'skylearn-billing-pro'); ?></label>
                            <input type="text" id="display_name" name="display_name" value="<?php echo esc_attr($user->display_name); ?>" class="skylearn-input" />
                        </div>
                        <div class="skylearn-field-col">
                            <label for="user_email"><?php _e('Email Address', 'skylearn-billing-pro'); ?></label>
                            <input type="email" id="user_email" name="user_email" value="<?php echo esc_attr($user->user_email); ?>" class="skylearn-input" />
                        </div>
                    </div>
                </div>
                
                <div class="skylearn-form-section">
                    <h2><?php _e('Change Password', 'skylearn-billing-pro'); ?></h2>
                    
                    <div class="skylearn-field-row">
                        <div class="skylearn-field-col">
                            <label for="current_password"><?php _e('Current Password', 'skylearn-billing-pro'); ?></label>
                            <input type="password" id="current_password" name="current_password" class="skylearn-input" />
                        </div>
                    </div>
                    
                    <div class="skylearn-field-row">
                        <div class="skylearn-field-col">
                            <label for="new_password"><?php _e('New Password', 'skylearn-billing-pro'); ?></label>
                            <input type="password" id="new_password" name="new_password" class="skylearn-input" />
                        </div>
                        <div class="skylearn-field-col">
                            <label for="confirm_password"><?php _e('Confirm New Password', 'skylearn-billing-pro'); ?></label>
                            <input type="password" id="confirm_password" name="confirm_password" class="skylearn-input" />
                        </div>
                    </div>
                </div>
                
                <div class="skylearn-form-actions">
                    <button type="submit" name="update_account" class="skylearn-button skylearn-button-primary">
                        <?php _e('Update Account', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Login form shortcode
     */
    public function login_form_shortcode($atts) {
        if (is_user_logged_in()) {
            return '<div class="skylearn-notice">' . __('You are already logged in.', 'skylearn-billing-pro') . '</div>';
        }
        
        $atts = shortcode_atts(array(
            'redirect' => '',
            'form_id' => 'skylearn-login-form'
        ), $atts, 'skylearn_login_form');
        
        $redirect_url = !empty($atts['redirect']) ? $atts['redirect'] : get_permalink();
        
        ob_start();
        ?>
        <div class="skylearn-login-container" role="main" aria-labelledby="skylearn-login-title">
            <form id="<?php echo esc_attr($atts['form_id']); ?>" class="skylearn-login-form" method="post" action="<?php echo esc_url(wp_login_url($redirect_url)); ?>">
                <h2 id="skylearn-login-title" class="skylearn-login-title"><?php _e('Login to Your Account', 'skylearn-billing-pro'); ?></h2>
                
                <div class="skylearn-form-field">
                    <label for="user_login"><?php _e('Username or Email', 'skylearn-billing-pro'); ?></label>
                    <input type="text" id="user_login" name="log" class="skylearn-input" required />
                </div>
                
                <div class="skylearn-form-field">
                    <label for="user_pass"><?php _e('Password', 'skylearn-billing-pro'); ?></label>
                    <input type="password" id="user_pass" name="pwd" class="skylearn-input" required />
                </div>
                
                <div class="skylearn-form-field skylearn-form-checkbox">
                    <label for="rememberme">
                        <input type="checkbox" id="rememberme" name="rememberme" value="forever" />
                        <?php _e('Remember Me', 'skylearn-billing-pro'); ?>
                    </label>
                </div>
                
                <div class="skylearn-form-actions">
                    <button type="submit" name="wp-submit" class="skylearn-button skylearn-button-primary">
                        <?php _e('Log In', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
                
                <div class="skylearn-login-links">
                    <a href="<?php echo esc_url(wp_lostpassword_url($redirect_url)); ?>"><?php _e('Forgot Password?', 'skylearn-billing-pro'); ?></a>
                    <?php if (get_option('users_can_register')): ?>
                        | <a href="<?php echo esc_url(wp_registration_url()); ?>"><?php _e('Register', 'skylearn-billing-pro'); ?></a>
                    <?php endif; ?>
                </div>
                
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_url); ?>" />
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * User info shortcode
     */
    public function user_info_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '';
        }
        
        $atts = shortcode_atts(array(
            'field' => 'display_name',
            'default' => ''
        ), $atts, 'skylearn_user_info');
        
        $user = wp_get_current_user();
        $value = '';
        
        switch ($atts['field']) {
            case 'display_name':
                $value = $user->display_name;
                break;
            case 'first_name':
                $value = $user->first_name;
                break;
            case 'last_name':
                $value = $user->last_name;
                break;
            case 'email':
                $value = $user->user_email;
                break;
            case 'username':
                $value = $user->user_login;
                break;
            default:
                $value = get_user_meta($user->ID, $atts['field'], true);
        }
        
        return !empty($value) ? esc_html($value) : esc_html($atts['default']);
    }
    
    /**
     * Course access shortcode
     */
    public function course_access_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="skylearn-error">' . __('Please log in to check course access.', 'skylearn-billing-pro') . '</div>';
        }
        
        $atts = shortcode_atts(array(
            'course_id' => '',
            'message' => __('You have access to this course.', 'skylearn-billing-pro'),
            'no_access_message' => __('You do not have access to this course.', 'skylearn-billing-pro'),
            'purchase_url' => ''
        ), $atts, 'skylearn_course_access');
        
        if (empty($atts['course_id'])) {
            return '<div class="skylearn-error">' . __('Course ID is required.', 'skylearn-billing-pro') . '</div>';
        }
        
        $user_id = get_current_user_id();
        $has_access = $this->user_has_course_access($user_id, $atts['course_id']);
        
        if ($has_access) {
            return '<div class="skylearn-success">' . esc_html($atts['message']) . '</div>';
        } else {
            $output = '<div class="skylearn-notice">' . esc_html($atts['no_access_message']);
            if (!empty($atts['purchase_url'])) {
                $output .= ' <a href="' . esc_url($atts['purchase_url']) . '" class="skylearn-button skylearn-button-small">' . __('Purchase Now', 'skylearn-billing-pro') . '</a>';
            }
            $output .= '</div>';
            return $output;
        }
    }
    
    // Helper methods
    
    /**
     * Get portal URL
     */
    private function get_portal_url($section = '') {
        $pages = get_option('skylearn_billing_pro_pages', array());
        
        if (!empty($section) && isset($pages['portal_' . $section])) {
            return get_permalink($pages['portal_' . $section]);
        } elseif (isset($pages['portal'])) {
            return get_permalink($pages['portal']);
        }
        
        return home_url('/skylearn-portal/');
    }
    
    /**
     * Get user recent orders
     */
    private function get_user_recent_orders($user_id, $limit = 5) {
        // This would integrate with the actual order system
        return array();
    }
    
    /**
     * Get user orders
     */
    private function get_user_orders($user_id) {
        // This would integrate with the actual order system
        return array();
    }
    
    /**
     * Get user active subscriptions
     */
    private function get_user_active_subscriptions($user_id) {
        if (function_exists('skylearn_billing_pro_subscription_manager')) {
            $subscription_manager = skylearn_billing_pro_subscription_manager();
            return method_exists($subscription_manager, 'get_user_active_subscriptions') ? 
                   $subscription_manager->get_user_active_subscriptions($user_id) : array();
        }
        return array();
    }
    
    /**
     * Get user downloads
     */
    private function get_user_downloads($user_id, $limit = null) {
        // This would integrate with the actual download system
        return array();
    }
    
    /**
     * Get user addresses
     */
    private function get_user_addresses($user_id) {
        // This would integrate with the actual address system
        return array();
    }
    
    /**
     * Check if user has course access
     */
    private function user_has_course_access($user_id, $course_id) {
        if (function_exists('skylearn_billing_pro_user_enrollment')) {
            $enrollment = skylearn_billing_pro_user_enrollment();
            return method_exists($enrollment, 'user_has_access') ? 
                   $enrollment->user_has_access($user_id, $course_id) : false;
        }
        return false;
    }
    
    /**
     * Get file icon for downloads
     */
    private function get_file_icon($file_type) {
        $icons = array(
            'pdf' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#ff4444"/><polyline points="14,2 14,8 20,8" fill="#fff"/></svg>',
            'zip' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#ffaa00"/><polyline points="14,2 14,8 20,8" fill="#fff"/></svg>',
            'mp4' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#00aa44"/><polyline points="14,2 14,8 20,8" fill="#fff"/></svg>',
            'default' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#666"/><polyline points="14,2 14,8 20,8" fill="#fff"/></svg>'
        );
        
        return $icons[$file_type] ?? $icons['default'];
    }
}

/**
 * Initialize frontend shortcodes
 */
function skylearn_billing_pro_frontend_shortcodes() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Frontend_Shortcodes();
    }
    
    return $instance;
}