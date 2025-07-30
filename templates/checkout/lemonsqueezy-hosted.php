<?php
/**
 * Lemon Squeezy hosted checkout template
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

$lemon_squeezy = skylearn_billing_pro_payment_manager()->get_gateway_connector('lemonsqueezy');
?>

<div class="skylearn-checkout-container skylearn-lemonsqueezy-checkout">
    <div class="skylearn-checkout-header">
        <h3><?php _e('Secure Checkout', 'skylearn-billing-pro'); ?></h3>
        <p class="skylearn-checkout-description">
            <?php echo esc_html($args['product_name'] ?? __('Course Access', 'skylearn-billing-pro')); ?>
            <?php if (!empty($args['price'])): ?>
                - <span class="skylearn-price"><?php echo esc_html($args['currency'] . ' ' . $args['price']); ?></span>
            <?php endif; ?>
        </p>
    </div>
    
    <div class="skylearn-hosted-notice">
        <div class="skylearn-notice-icon">
            <span class="dashicons dashicons-info"></span>
        </div>
        <div class="skylearn-notice-content">
            <h4><?php _e('Hosted Checkout', 'skylearn-billing-pro'); ?></h4>
            <p><?php echo esc_html($lemon_squeezy->get_checkout_notice()); ?></p>
        </div>
    </div>
    
    <div class="skylearn-form-actions">
        <button type="button" id="lemonsqueezy-checkout-button" class="skylearn-button skylearn-button-primary">
            <span class="button-text"><?php _e('Proceed to Checkout', 'skylearn-billing-pro'); ?></span>
            <span class="button-spinner" style="display: none;">
                <span class="spinner is-active"></span>
            </span>
        </button>
        
        <div class="skylearn-payment-security">
            <span class="dashicons dashicons-lock"></span>
            <?php _e('Secured by Lemon Squeezy', 'skylearn-billing-pro'); ?>
        </div>
    </div>
    
    <div class="skylearn-checkout-features">
        <div class="skylearn-feature">
            <span class="dashicons dashicons-yes"></span>
            <?php _e('SSL Encrypted', 'skylearn-billing-pro'); ?>
        </div>
        <div class="skylearn-feature">
            <span class="dashicons dashicons-yes"></span>
            <?php _e('PCI Compliant', 'skylearn-billing-pro'); ?>
        </div>
        <div class="skylearn-feature">
            <span class="dashicons dashicons-yes"></span>
            <?php _e('Instant Access', 'skylearn-billing-pro'); ?>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    $('#lemonsqueezy-checkout-button').on('click', function() {
        var $button = $(this);
        var $buttonText = $button.find('.button-text');
        var $buttonSpinner = $button.find('.button-spinner');
        
        $button.prop('disabled', true);
        $buttonText.hide();
        $buttonSpinner.show();
        
        // Create checkout URL via AJAX
        $.post('<?php echo admin_url("admin-ajax.php"); ?>', {
            action: 'skylearn_create_lemonsqueezy_checkout',
            nonce: '<?php echo wp_create_nonce("skylearn_checkout_nonce"); ?>',
            product_id: '<?php echo esc_js($args["product_id"] ?? ""); ?>',
            amount: '<?php echo esc_js($args["amount"] ?? 0); ?>',
            currency: '<?php echo esc_js($args["currency"] ?? "USD"); ?>',
            product_name: '<?php echo esc_js($args["product_name"] ?? ""); ?>'
        }, function(response) {
            if (response.success && response.data.checkout_url) {
                window.location.href = response.data.checkout_url;
            } else {
                alert('<?php _e("Error creating checkout. Please try again.", "skylearn-billing-pro"); ?>');
                $button.prop('disabled', false);
                $buttonText.show();
                $buttonSpinner.hide();
            }
        }).fail(function() {
            alert('<?php _e("Network error. Please try again.", "skylearn-billing-pro"); ?>');
            $button.prop('disabled', false);
            $buttonText.show();
            $buttonSpinner.hide();
        });
    });
});
</script>

<style>
.skylearn-checkout-container {
    max-width: 500px;
    margin: 0 auto;
    background: #fff;
    border: 1px solid #e1e5e9;
    border-radius: 8px;
    padding: 30px;
    box-shadow: 0 2px 10px rgba(24, 49, 83, 0.1);
}

.skylearn-checkout-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e1e5e9;
}

.skylearn-checkout-header h3 {
    margin: 0 0 10px 0;
    color: #183153;
    font-size: 24px;
}

.skylearn-price {
    font-weight: bold;
    color: #FF3B00;
}

.skylearn-hosted-notice {
    display: flex;
    align-items: flex-start;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 20px;
    margin-bottom: 30px;
}

.skylearn-notice-icon {
    margin-right: 15px;
    color: #856404;
}

.skylearn-notice-icon .dashicons {
    font-size: 24px;
}

.skylearn-notice-content h4 {
    margin: 0 0 10px 0;
    color: #856404;
    font-size: 16px;
}

.skylearn-notice-content p {
    margin: 0;
    color: #856404;
    line-height: 1.5;
}

.skylearn-button {
    width: 100%;
    padding: 15px;
    background: #FF3B00;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    position: relative;
    margin-bottom: 20px;
}

.skylearn-button:hover {
    background: #e63600;
}

.skylearn-button:disabled {
    background: #ccc;
    cursor: not-allowed;
}

.skylearn-payment-security {
    text-align: center;
    margin-bottom: 25px;
    color: #666;
    font-size: 12px;
}

.skylearn-checkout-features {
    display: flex;
    justify-content: space-around;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

.skylearn-feature {
    text-align: center;
    color: #28a745;
    font-size: 12px;
}

.skylearn-feature .dashicons {
    display: block;
    margin-bottom: 5px;
    font-size: 16px;
}

@media (max-width: 600px) {
    .skylearn-checkout-features {
        flex-direction: column;
        align-items: center;
    }
    
    .skylearn-feature {
        margin-bottom: 10px;
    }
}
</style>