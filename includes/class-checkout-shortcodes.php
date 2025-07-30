<?php
/**
 * Checkout Shortcodes for frontend integration
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
 * Checkout Shortcodes class
 */
class SkyLearn_Billing_Pro_Checkout_Shortcodes {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialize shortcodes
     */
    public function init() {
        add_shortcode('skylearn_checkout', array($this, 'checkout_shortcode'));
        add_shortcode('skylearn_checkout_button', array($this, 'checkout_button_shortcode'));
    }
    
    /**
     * Main checkout shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Checkout form HTML
     */
    public function checkout_shortcode($atts) {
        $atts = shortcode_atts(array(
            'gateway' => 'stripe',
            'product_id' => '',
            'product_name' => '',
            'amount' => '',
            'currency' => 'USD',
            'type' => 'inline', // inline, overlay, hosted
            'redirect_url' => '',
        ), $atts, 'skylearn_checkout');
        
        // Validate required attributes
        if (empty($atts['product_id']) || empty($atts['amount'])) {
            return '<div class="skylearn-checkout-error">' . 
                   __('Error: Product ID and amount are required for checkout.', 'skylearn-billing-pro') . 
                   '</div>';
        }
        
        // Check if gateway is available
        $payment_manager = skylearn_billing_pro_payment_manager();
        if (!$payment_manager->is_gateway_enabled($atts['gateway'])) {
            return '<div class="skylearn-checkout-error">' . 
                   sprintf(__('Error: %s payment gateway is not available.', 'skylearn-billing-pro'), ucfirst($atts['gateway'])) . 
                   '</div>';
        }
        
        // Get gateway connector
        $connector = $payment_manager->get_gateway_connector($atts['gateway']);
        if (!$connector) {
            return '<div class="skylearn-checkout-error">' . 
                   __('Error: Payment gateway connector not found.', 'skylearn-billing-pro') . 
                   '</div>';
        }
        
        // Prepare checkout arguments
        $checkout_args = array(
            'product_id' => sanitize_text_field($atts['product_id']),
            'product_name' => !empty($atts['product_name']) ? sanitize_text_field($atts['product_name']) : __('Course Access', 'skylearn-billing-pro'),
            'amount' => floatval($atts['amount']),
            'currency' => strtoupper(sanitize_text_field($atts['currency'])),
            'redirect_url' => !empty($atts['redirect_url']) ? esc_url($atts['redirect_url']) : '',
            'checkout_type' => sanitize_text_field($atts['type'])
        );
        
        // Check if gateway supports the requested checkout type
        $supported_types = $connector->get_supported_checkout_types();
        if (!in_array($atts['type'], $supported_types)) {
            // Fallback to first supported type
            $checkout_args['checkout_type'] = $supported_types[0];
        }
        
        // Render checkout based on type
        switch ($checkout_args['checkout_type']) {
            case 'overlay':
                if (method_exists($connector, 'render_overlay_checkout')) {
                    return $connector->render_overlay_checkout($checkout_args);
                }
                // Fallback to inline
                return $connector->render_inline_checkout($checkout_args);
                
            case 'hosted':
                if (method_exists($connector, 'render_hosted_checkout')) {
                    return $connector->render_hosted_checkout($checkout_args);
                }
                // Fallback to inline
                return $connector->render_inline_checkout($checkout_args);
                
            case 'inline':
            default:
                return $connector->render_inline_checkout($checkout_args);
        }
    }
    
    /**
     * Simple checkout button shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string Button HTML
     */
    public function checkout_button_shortcode($atts) {
        $atts = shortcode_atts(array(
            'gateway' => 'stripe',
            'product_id' => '',
            'product_name' => '',
            'amount' => '',
            'currency' => 'USD',
            'button_text' => '',
            'button_class' => 'skylearn-checkout-button',
            'redirect_url' => '',
        ), $atts, 'skylearn_checkout_button');
        
        // Validate required attributes
        if (empty($atts['product_id']) || empty($atts['amount'])) {
            return '<div class="skylearn-checkout-error">' . 
                   __('Error: Product ID and amount are required.', 'skylearn-billing-pro') . 
                   '</div>';
        }
        
        // Check if gateway is available
        $payment_manager = skylearn_billing_pro_payment_manager();
        if (!$payment_manager->is_gateway_enabled($atts['gateway'])) {
            return '<div class="skylearn-checkout-error">' . 
                   sprintf(__('Error: %s payment gateway is not available.', 'skylearn-billing-pro'), ucfirst($atts['gateway'])) . 
                   '</div>';
        }
        
        // Prepare button text
        $button_text = !empty($atts['button_text']) ? 
                      sanitize_text_field($atts['button_text']) : 
                      sprintf(__('Buy Now - %s %s', 'skylearn-billing-pro'), 
                             strtoupper($atts['currency']), 
                             number_format(floatval($atts['amount']), 2));
        
        // Generate unique ID for this button
        $button_id = 'skylearn-button-' . wp_generate_password(8, false);
        
        ob_start();
        ?>
        <button type="button" 
                id="<?php echo esc_attr($button_id); ?>" 
                class="<?php echo esc_attr($atts['button_class']); ?>"
                data-gateway="<?php echo esc_attr($atts['gateway']); ?>"
                data-product-id="<?php echo esc_attr($atts['product_id']); ?>"
                data-product-name="<?php echo esc_attr($atts['product_name']); ?>"
                data-amount="<?php echo esc_attr($atts['amount']); ?>"
                data-currency="<?php echo esc_attr($atts['currency']); ?>"
                data-redirect-url="<?php echo esc_attr($atts['redirect_url']); ?>">
            <?php echo esc_html($button_text); ?>
        </button>
        
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#<?php echo esc_js($button_id); ?>').on('click', function() {
                var $button = $(this);
                var gateway = $button.data('gateway');
                var productId = $button.data('product-id');
                var productName = $button.data('product-name');
                var amount = $button.data('amount');
                var currency = $button.data('currency');
                var redirectUrl = $button.data('redirect-url');
                
                // Create checkout session via AJAX
                $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
                    action: 'skylearn_create_checkout_session',
                    nonce: '<?php echo wp_create_nonce("skylearn_checkout_nonce"); ?>',
                    gateway: gateway,
                    product_id: productId,
                    product_name: productName,
                    amount: amount,
                    currency: currency,
                    redirect_url: redirectUrl
                }, function(response) {
                    if (response.success) {
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else if (response.data.html) {
                            // Show inline checkout in modal
                            var modal = $('<div class="skylearn-checkout-modal">' + response.data.html + '</div>');
                            $('body').append(modal);
                            modal.fadeIn();
                        }
                    } else {
                        alert(response.data.message || '<?php _e("Error creating checkout session", "skylearn-billing-pro"); ?>');
                    }
                }).fail(function() {
                    alert('<?php _e("Network error. Please try again.", "skylearn-billing-pro"); ?>');
                });
            });
        });
        </script>
        
        <style>
        .skylearn-checkout-button {
            background: #FF3B00;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-block;
            text-decoration: none;
        }
        
        .skylearn-checkout-button:hover {
            background: #e63600;
            color: white;
            text-decoration: none;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(255, 59, 0, 0.3);
        }
        
        .skylearn-checkout-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px 16px;
            border-radius: 6px;
            margin: 10px 0;
        }
        
        .skylearn-checkout-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        </style>
        <?php
        return ob_get_clean();
    }
}

/**
 * Initialize checkout shortcodes
 */
function skylearn_billing_pro_checkout_shortcodes() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Checkout_Shortcodes();
    }
    
    return $instance;
}