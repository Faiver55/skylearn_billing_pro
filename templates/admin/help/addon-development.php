<?php
/**
 * Developer Documentation for Addon Development - Skylearn Billing Pro
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
?>

<div class="skylearn-billing-dev-docs">
    <div class="skylearn-billing-docs-header">
        <h2><?php esc_html_e('Addon Development Documentation', 'skylearn-billing-pro'); ?></h2>
        <p><?php esc_html_e('Learn how to build powerful addons for Skylearn Billing Pro.', 'skylearn-billing-pro'); ?></p>
    </div>

    <!-- Table of Contents -->
    <div class="skylearn-billing-docs-toc">
        <h3><?php esc_html_e('Table of Contents', 'skylearn-billing-pro'); ?></h3>
        <ul>
            <li><a href="#getting-started"><?php esc_html_e('Getting Started', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#addon-structure"><?php esc_html_e('Addon Structure', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#headers-metadata"><?php esc_html_e('Headers & Metadata', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#activation-deactivation"><?php esc_html_e('Activation & Deactivation', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#hooks-filters"><?php esc_html_e('Hooks & Filters', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#license-checks"><?php esc_html_e('License Checks', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#admin-integration"><?php esc_html_e('Admin Integration', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#best-practices"><?php esc_html_e('Best Practices', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="#examples"><?php esc_html_e('Code Examples', 'skylearn-billing-pro'); ?></a></li>
        </ul>
    </div>

    <!-- Getting Started -->
    <div id="getting-started" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Getting Started', 'skylearn-billing-pro'); ?></h3>
        
        <p><?php esc_html_e('Skylearn Billing Pro addons are powerful extensions that integrate seamlessly with the main plugin. They follow WordPress coding standards and use a structured approach for maximum compatibility.', 'skylearn-billing-pro'); ?></p>
        
        <h4><?php esc_html_e('Requirements', 'skylearn-billing-pro'); ?></h4>
        <ul>
            <li><?php esc_html_e('PHP 7.4 or higher', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('WordPress 5.0 or higher', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Skylearn Billing Pro main plugin installed and activated', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Basic understanding of WordPress plugin development', 'skylearn-billing-pro'); ?></li>
        </ul>
    </div>

    <!-- Addon Structure -->
    <div id="addon-structure" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Addon Structure', 'skylearn-billing-pro'); ?></h3>
        
        <p><?php esc_html_e('All addons must be placed in the', 'skylearn-billing-pro'); ?> <code>addons/</code> <?php esc_html_e('directory and follow this naming convention:', 'skylearn-billing-pro'); ?></p>
        
        <div class="skylearn-billing-code-example">
            <pre><code>addons/
├── your-addon-name.php          # Main addon file
├── assets/                      # Optional: CSS, JS, images
├── templates/                   # Optional: Template files
└── includes/                    # Optional: Additional classes</code></pre>
        </div>
        
        <p><?php esc_html_e('The main addon file must end with', 'skylearn-billing-pro'); ?> <code>-addon.php</code> <?php esc_html_e('to be automatically discovered by the addon manager.', 'skylearn-billing-pro'); ?></p>
    </div>

    <!-- Headers & Metadata -->
    <div id="headers-metadata" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Headers & Metadata', 'skylearn-billing-pro'); ?></h3>
        
        <p><?php esc_html_e('Every addon file must start with a proper header containing metadata:', 'skylearn-billing-pro'); ?></p>
        
        <div class="skylearn-billing-code-example">
            <pre><code>&lt;?php
/**
 * Your Addon Name
 *
 * Addon ID: your-addon-id
 * Addon Name: Your Addon Name
 * Description: Brief description of what your addon does
 * Version: 1.0.0
 * Author: Your Name
 * Type: free
 * Required Tier: free
 * Category: category_name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}</code></pre>
        </div>
        
        <h4><?php esc_html_e('Header Fields Explained', 'skylearn-billing-pro'); ?></h4>
        <table class="skylearn-billing-docs-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Field', 'skylearn-billing-pro'); ?></th>
                    <th><?php esc_html_e('Required', 'skylearn-billing-pro'); ?></th>
                    <th><?php esc_html_e('Description', 'skylearn-billing-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>Addon ID</code></td>
                    <td><?php esc_html_e('Yes', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Unique identifier for your addon (lowercase, hyphens only)', 'skylearn-billing-pro'); ?></td>
                </tr>
                <tr>
                    <td><code>Addon Name</code></td>
                    <td><?php esc_html_e('Yes', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Display name for your addon', 'skylearn-billing-pro'); ?></td>
                </tr>
                <tr>
                    <td><code>Description</code></td>
                    <td><?php esc_html_e('Yes', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Brief description of addon functionality', 'skylearn-billing-pro'); ?></td>
                </tr>
                <tr>
                    <td><code>Version</code></td>
                    <td><?php esc_html_e('Yes', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Addon version (semantic versioning)', 'skylearn-billing-pro'); ?></td>
                </tr>
                <tr>
                    <td><code>Type</code></td>
                    <td><?php esc_html_e('Yes', 'skylearn-billing-pro'); ?></td>
                    <td><code>free</code> <?php esc_html_e('or', 'skylearn-billing-pro'); ?> <code>paid</code></td>
                </tr>
                <tr>
                    <td><code>Required Tier</code></td>
                    <td><?php esc_html_e('Yes', 'skylearn-billing-pro'); ?></td>
                    <td><code>free</code>, <code>pro</code>, <?php esc_html_e('or', 'skylearn-billing-pro'); ?> <code>pro_plus</code></td>
                </tr>
                <tr>
                    <td><code>Category</code></td>
                    <td><?php esc_html_e('No', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Addon category for organization', 'skylearn-billing-pro'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Activation & Deactivation -->
    <div id="activation-deactivation" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Activation & Deactivation', 'skylearn-billing-pro'); ?></h3>
        
        <p><?php esc_html_e('Handle addon activation and deactivation events properly:', 'skylearn-billing-pro'); ?></p>
        
        <div class="skylearn-billing-code-example">
            <pre><code>class Your_Addon_Class {
    
    const ADDON_ID = 'your-addon-id';
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('skylearn_billing_addon_activated', array($this, 'on_addon_activated'));
        add_action('skylearn_billing_addon_deactivated', array($this, 'on_addon_deactivated'));
    }
    
    public function init() {
        // Only initialize if addon is active
        $addon_manager = skylearn_billing_pro_addon_manager();
        $active_addons = $addon_manager->get_active_addons();
        
        if (!in_array(self::ADDON_ID, $active_addons)) {
            return;
        }
        
        // Check license eligibility for paid addons
        if ($this->is_paid_addon()) {
            $license_manager = skylearn_billing_pro_license_manager();
            if (!$license_manager->is_addon_accessible(self::ADDON_ID)) {
                return;
            }
        }
        
        $this->init_hooks();
        $this->init_settings();
    }
    
    public function on_addon_activated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Setup default settings, create database tables, etc.
            $this->setup_defaults();
        }
    }
    
    public function on_addon_deactivated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Cleanup if needed
            $this->cleanup();
        }
    }
}</code></pre>
        </div>
    </div>

    <!-- Hooks & Filters -->
    <div id="hooks-filters" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Available Hooks & Filters', 'skylearn-billing-pro'); ?></h3>
        
        <h4><?php esc_html_e('Core Hooks', 'skylearn-billing-pro'); ?></h4>
        <table class="skylearn-billing-docs-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Hook Name', 'skylearn-billing-pro'); ?></th>
                    <th><?php esc_html_e('Type', 'skylearn-billing-pro'); ?></th>
                    <th><?php esc_html_e('Description', 'skylearn-billing-pro'); ?></th>
                    <th><?php esc_html_e('Parameters', 'skylearn-billing-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>skylearn_billing_payment_completed</code></td>
                    <td><?php esc_html_e('Action', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Fired when a payment is completed', 'skylearn-billing-pro'); ?></td>
                    <td><code>$payment_data</code></td>
                </tr>
                <tr>
                    <td><code>skylearn_billing_subscription_created</code></td>
                    <td><?php esc_html_e('Action', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Fired when a subscription is created', 'skylearn-billing-pro'); ?></td>
                    <td><code>$subscription_data</code></td>
                </tr>
                <tr>
                    <td><code>skylearn_billing_subscription_cancelled</code></td>
                    <td><?php esc_html_e('Action', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Fired when a subscription is cancelled', 'skylearn-billing-pro'); ?></td>
                    <td><code>$subscription_data</code></td>
                </tr>
                <tr>
                    <td><code>skylearn_billing_customer_created</code></td>
                    <td><?php esc_html_e('Action', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Fired when a new customer is created', 'skylearn-billing-pro'); ?></td>
                    <td><code>$customer_data</code></td>
                </tr>
                <tr>
                    <td><code>skylearn_billing_available_addons</code></td>
                    <td><?php esc_html_e('Filter', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Filter available addons list', 'skylearn-billing-pro'); ?></td>
                    <td><code>$addons</code></td>
                </tr>
            </tbody>
        </table>
        
        <h4><?php esc_html_e('Addon-Specific Hooks', 'skylearn-billing-pro'); ?></h4>
        <table class="skylearn-billing-docs-table">
            <thead>
                <tr>
                    <th><?php esc_html_e('Hook Name', 'skylearn-billing-pro'); ?></th>
                    <th><?php esc_html_e('Type', 'skylearn-billing-pro'); ?></th>
                    <th><?php esc_html_e('Description', 'skylearn-billing-pro'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>skylearn_billing_addon_activated</code></td>
                    <td><?php esc_html_e('Action', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Fired when any addon is activated', 'skylearn-billing-pro'); ?></td>
                </tr>
                <tr>
                    <td><code>skylearn_billing_addon_deactivated</code></td>
                    <td><?php esc_html_e('Action', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Fired when any addon is deactivated', 'skylearn-billing-pro'); ?></td>
                </tr>
                <tr>
                    <td><code>skylearn_billing_addon_loaded</code></td>
                    <td><?php esc_html_e('Action', 'skylearn-billing-pro'); ?></td>
                    <td><?php esc_html_e('Fired when an addon is loaded', 'skylearn-billing-pro'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- License Checks -->
    <div id="license-checks" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('License Checks', 'skylearn-billing-pro'); ?></h3>
        
        <p><?php esc_html_e('For paid addons, always implement proper license checking:', 'skylearn-billing-pro'); ?></p>
        
        <div class="skylearn-billing-code-example">
            <pre><code>public function init() {
    // Check if addon is active
    $addon_manager = skylearn_billing_pro_addon_manager();
    $active_addons = $addon_manager->get_active_addons();
    
    if (!in_array(self::ADDON_ID, $active_addons)) {
        return;
    }
    
    // Check license eligibility for paid addons
    $license_manager = skylearn_billing_pro_license_manager();
    if (!$license_manager->is_addon_accessible(self::ADDON_ID)) {
        // Log access attempt
        $license_manager->log_addon_access(
            self::ADDON_ID, 
            false, 
            'License tier insufficient'
        );
        return;
    }
    
    // Initialize addon functionality
    $this->init_hooks();
}</code></pre>
        </div>
        
        <h4><?php esc_html_e('License Helper Functions', 'skylearn-billing-pro'); ?></h4>
        <ul>
            <li><code>$license_manager->is_addon_accessible($addon_id)</code> - <?php esc_html_e('Check if addon is accessible', 'skylearn-billing-pro'); ?></li>
            <li><code>$license_manager->get_addon_license_status($addon_id)</code> - <?php esc_html_e('Get detailed license status', 'skylearn-billing-pro'); ?></li>
            <li><code>$license_manager->get_addon_upgrade_message($addon_id)</code> - <?php esc_html_e('Get upgrade message', 'skylearn-billing-pro'); ?></li>
            <li><code>$license_manager->is_feature_eligible($feature_key, $addon_id)</code> - <?php esc_html_e('Check feature eligibility', 'skylearn-billing-pro'); ?></li>
        </ul>
    </div>

    <!-- Admin Integration -->
    <div id="admin-integration" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Admin Integration', 'skylearn-billing-pro'); ?></h3>
        
        <p><?php esc_html_e('Add admin settings pages for your addon:', 'skylearn-billing-pro'); ?></p>
        
        <div class="skylearn-billing-code-example">
            <pre><code>public function add_admin_menu() {
    add_submenu_page(
        'skylearn-billing-pro',
        __('Your Addon Settings', 'your-addon'),
        __('Your Addon', 'your-addon'),
        'manage_options',
        'skylearn-billing-your-addon',
        array($this, 'render_admin_page')
    );
}

public function enqueue_admin_scripts($hook) {
    if (strpos($hook, 'skylearn-billing-your-addon') === false) {
        return;
    }
    
    wp_enqueue_script(
        'your-addon-admin',
        plugin_dir_url(__FILE__) . 'assets/js/admin.js',
        array('jquery'),
        '1.0.0',
        true
    );
}</code></pre>
        </div>
    </div>

    <!-- Best Practices -->
    <div id="best-practices" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Best Practices', 'skylearn-billing-pro'); ?></h3>
        
        <h4><?php esc_html_e('Security', 'skylearn-billing-pro'); ?></h4>
        <ul>
            <li><?php esc_html_e('Always sanitize and validate user input', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Use WordPress nonces for AJAX requests', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Check user capabilities before allowing actions', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Prevent direct file access', 'skylearn-billing-pro'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Performance', 'skylearn-billing-pro'); ?></h4>
        <ul>
            <li><?php esc_html_e('Only load addon code when necessary', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Use proper WordPress caching mechanisms', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Minimize database queries', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Load assets only on relevant pages', 'skylearn-billing-pro'); ?></li>
        </ul>
        
        <h4><?php esc_html_e('Compatibility', 'skylearn-billing-pro'); ?></h4>
        <ul>
            <li><?php esc_html_e('Follow WordPress coding standards', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Use WordPress hooks instead of modifying core files', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Test with different WordPress and PHP versions', 'skylearn-billing-pro'); ?></li>
            <li><?php esc_html_e('Handle errors gracefully to avoid breaking the main plugin', 'skylearn-billing-pro'); ?></li>
        </ul>
    </div>

    <!-- Code Examples -->
    <div id="examples" class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Complete Addon Example', 'skylearn-billing-pro'); ?></h3>
        
        <p><?php esc_html_e('Here\'s a complete minimal addon example:', 'skylearn-billing-pro'); ?></p>
        
        <div class="skylearn-billing-code-example">
            <pre><code>&lt;?php
/**
 * Sample Addon for Skylearn Billing Pro
 *
 * Addon ID: sample-addon
 * Addon Name: Sample Addon
 * Description: A sample addon demonstrating best practices
 * Version: 1.0.0
 * Author: Your Name
 * Type: free
 * Required Tier: free
 * Category: sample
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class SkyLearn_Billing_Pro_Sample_Addon {
    
    const ADDON_ID = 'sample-addon';
    
    private static $_instance = null;
    
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('skylearn_billing_addon_activated', array($this, 'on_addon_activated'));
        add_action('skylearn_billing_addon_deactivated', array($this, 'on_addon_deactivated'));
    }
    
    public function init() {
        $addon_manager = skylearn_billing_pro_addon_manager();
        $active_addons = $addon_manager->get_active_addons();
        
        if (!in_array(self::ADDON_ID, $active_addons)) {
            return;
        }
        
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('skylearn_billing_payment_completed', array($this, 'handle_payment'));
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
    }
    
    public function handle_payment($payment_data) {
        // Process payment completion
        error_log('Sample Addon: Payment completed for ' . $payment_data['customer_email']);
    }
    
    public function add_admin_menu() {
        add_submenu_page(
            'skylearn-billing-pro',
            __('Sample Addon', 'skylearn-billing-pro'),
            __('Sample Addon', 'skylearn-billing-pro'),
            'manage_options',
            'skylearn-billing-sample-addon',
            array($this, 'render_admin_page')
        );
    }
    
    public function render_admin_page() {
        echo '&lt;div class="wrap"&gt;';
        echo '&lt;h1&gt;' . esc_html__('Sample Addon', 'skylearn-billing-pro') . '&lt;/h1&gt;';
        echo '&lt;p&gt;' . esc_html__('This is a sample addon page.', 'skylearn-billing-pro') . '&lt;/p&gt;';
        echo '&lt;/div&gt;';
    }
    
    public function on_addon_activated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Setup default settings
            add_option('sample_addon_settings', array(
                'option1' => 'value1',
                'option2' => 'value2'
            ));
        }
    }
    
    public function on_addon_deactivated($addon_id) {
        if ($addon_id === self::ADDON_ID) {
            // Cleanup if needed
        }
    }
}

// Initialize the addon
SkyLearn_Billing_Pro_Sample_Addon::instance();</code></pre>
        </div>
    </div>

    <!-- Additional Resources -->
    <div class="skylearn-billing-docs-section">
        <h3><?php esc_html_e('Additional Resources', 'skylearn-billing-pro'); ?></h3>
        
        <ul>
            <li><a href="https://developer.wordpress.org/coding-standards/" target="_blank"><?php esc_html_e('WordPress Coding Standards', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="https://developer.wordpress.org/plugins/" target="_blank"><?php esc_html_e('WordPress Plugin Developer Handbook', 'skylearn-billing-pro'); ?></a></li>
            <li><a href="https://developer.wordpress.org/reference/" target="_blank"><?php esc_html_e('WordPress Developer Reference', 'skylearn-billing-pro'); ?></a></li>
        </ul>
    </div>
</div>

<style>
.skylearn-billing-dev-docs {
    max-width: 1000px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.skylearn-billing-docs-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e1e1e1;
}

.skylearn-billing-docs-header h2 {
    color: #23282d;
    margin-bottom: 10px;
}

.skylearn-billing-docs-toc {
    background: #f8f9fa;
    border: 1px solid #e1e1e1;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 30px;
}

.skylearn-billing-docs-toc h3 {
    margin-top: 0;
    color: #23282d;
}

.skylearn-billing-docs-toc ul {
    margin: 0;
    padding-left: 20px;
}

.skylearn-billing-docs-toc li {
    margin-bottom: 5px;
}

.skylearn-billing-docs-toc a {
    color: #0073aa;
    text-decoration: none;
}

.skylearn-billing-docs-toc a:hover {
    text-decoration: underline;
}

.skylearn-billing-docs-section {
    margin-bottom: 40px;
    padding-bottom: 30px;
    border-bottom: 1px solid #e1e1e1;
}

.skylearn-billing-docs-section:last-child {
    border-bottom: none;
}

.skylearn-billing-docs-section h3 {
    color: #23282d;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 1px solid #e1e1e1;
}

.skylearn-billing-docs-section h4 {
    color: #555;
    margin: 25px 0 15px 0;
}

.skylearn-billing-code-example {
    background: #f6f8fa;
    border: 1px solid #e1e4e8;
    border-radius: 6px;
    margin: 15px 0;
}

.skylearn-billing-code-example pre {
    margin: 0;
    padding: 16px;
    overflow-x: auto;
    font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 13px;
    line-height: 1.45;
}

.skylearn-billing-code-example code {
    background: transparent;
    padding: 0;
    margin: 0;
    border: none;
}

.skylearn-billing-docs-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
    background: #fff;
    border: 1px solid #e1e1e1;
}

.skylearn-billing-docs-table th,
.skylearn-billing-docs-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e1e1e1;
    vertical-align: top;
}

.skylearn-billing-docs-table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #555;
}

.skylearn-billing-docs-table code {
    background: #f1f3f4;
    padding: 2px 4px;
    border-radius: 3px;
    font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
    font-size: 13px;
}

.skylearn-billing-docs-section ul,
.skylearn-billing-docs-section ol {
    padding-left: 20px;
}

.skylearn-billing-docs-section li {
    margin-bottom: 8px;
    line-height: 1.6;
}

.skylearn-billing-docs-section p {
    line-height: 1.6;
    margin-bottom: 15px;
}

.skylearn-billing-docs-section a {
    color: #0073aa;
    text-decoration: none;
}

.skylearn-billing-docs-section a:hover {
    text-decoration: underline;
}
</style>