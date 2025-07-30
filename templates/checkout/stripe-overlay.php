<?php
/**
 * Stripe overlay checkout template
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

$payment_fields = skylearn_billing_pro_payment_fields();
$custom_fields = $payment_fields->get_gateway_fields('stripe', $args['product_id'] ?? '');
?>

<!-- Overlay trigger button -->
<button type="button" id="skylearn-stripe-overlay-trigger" class="skylearn-button skylearn-button-primary">
    <?php _e('Purchase Now', 'skylearn-billing-pro'); ?>
    <?php if (!empty($args['price'])): ?>
        - <?php echo esc_html($args['currency'] . ' ' . $args['price']); ?>
    <?php endif; ?>
</button>

<!-- Overlay modal -->
<div id="skylearn-checkout-overlay" class="skylearn-checkout-overlay" style="display: none;">
    <div class="skylearn-overlay-backdrop"></div>
    <div class="skylearn-overlay-content">
        <div class="skylearn-overlay-header">
            <h3><?php _e('Complete Your Purchase', 'skylearn-billing-pro'); ?></h3>
            <button type="button" class="skylearn-overlay-close" aria-label="<?php _e('Close', 'skylearn-billing-pro'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
        </div>
        
        <div class="skylearn-overlay-body">
            <div class="skylearn-product-summary">
                <h4><?php echo esc_html($args['product_name'] ?? __('Course Access', 'skylearn-billing-pro')); ?></h4>
                <?php if (!empty($args['price'])): ?>
                    <div class="skylearn-price-display">
                        <?php echo esc_html($args['currency'] . ' ' . $args['price']); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <form id="skylearn-stripe-overlay-form" class="skylearn-checkout-form">
                <div class="skylearn-form-section">
                    <h4><?php _e('Customer Information', 'skylearn-billing-pro'); ?></h4>
                    
                    <!-- Default required fields -->
                    <div class="skylearn-field-row">
                        <div class="skylearn-field-col">
                            <label for="stripe-overlay-email"><?php _e('Email Address', 'skylearn-billing-pro'); ?> *</label>
                            <input type="email" id="stripe-overlay-email" name="customer_email" required class="skylearn-input" />
                        </div>
                        <div class="skylearn-field-col">
                            <label for="stripe-overlay-name"><?php _e('Full Name', 'skylearn-billing-pro'); ?> *</label>
                            <input type="text" id="stripe-overlay-name" name="customer_name" required class="skylearn-input" />
                        </div>
                    </div>
                    
                    <!-- Custom fields -->
                    <?php if (!empty($custom_fields)): ?>
                        <div class="skylearn-custom-fields">
                            <?php foreach ($custom_fields as $field): ?>
                                <div class="skylearn-field-row">
                                    <?php echo $payment_fields->render_field($field); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="skylearn-form-section">
                    <h4><?php _e('Payment Information', 'skylearn-billing-pro'); ?></h4>
                    
                    <!-- Stripe Elements will be mounted here -->
                    <div id="stripe-overlay-card-element" class="skylearn-stripe-element">
                        <!-- Stripe Elements will be inserted here -->
                    </div>
                    
                    <div id="stripe-overlay-card-errors" class="skylearn-error-message" role="alert"></div>
                </div>
                
                <div class="skylearn-form-actions">
                    <button type="submit" id="stripe-overlay-submit" class="skylearn-button skylearn-button-primary">
                        <span class="button-text"><?php _e('Complete Purchase', 'skylearn-billing-pro'); ?></span>
                        <span class="button-spinner" style="display: none;">
                            <span class="spinner is-active"></span>
                        </span>
                    </button>
                    
                    <button type="button" class="skylearn-button skylearn-button-secondary skylearn-overlay-cancel">
                        <?php _e('Cancel', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
                
                <!-- Hidden fields -->
                <input type="hidden" name="product_id" value="<?php echo esc_attr($args['product_id'] ?? ''); ?>" />
                <input type="hidden" name="amount" value="<?php echo esc_attr($args['amount'] ?? 0); ?>" />
                <input type="hidden" name="currency" value="<?php echo esc_attr($args['currency'] ?? 'USD'); ?>" />
                <input type="hidden" name="gateway" value="stripe" />
                <input type="hidden" name="checkout_type" value="overlay" />
                <?php wp_nonce_field('skylearn_checkout_nonce', 'checkout_nonce'); ?>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Open overlay
    $('#skylearn-stripe-overlay-trigger').on('click', function() {
        $('#skylearn-checkout-overlay').fadeIn(300);
        $('body').addClass('skylearn-overlay-open');
    });
    
    // Close overlay
    $('.skylearn-overlay-close, .skylearn-overlay-cancel, .skylearn-overlay-backdrop').on('click', function() {
        $('#skylearn-checkout-overlay').fadeOut(300);
        $('body').removeClass('skylearn-overlay-open');
    });
    
    // Prevent overlay close when clicking inside content
    $('.skylearn-overlay-content').on('click', function(e) {
        e.stopPropagation();
    });
    
    // Escape key to close
    $(document).on('keydown', function(e) {
        if (e.keyCode === 27 && $('#skylearn-checkout-overlay').is(':visible')) {
            $('#skylearn-checkout-overlay').fadeOut(300);
            $('body').removeClass('skylearn-overlay-open');
        }
    });
});
</script>

<style>
.skylearn-checkout-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.skylearn-overlay-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    cursor: pointer;
}

.skylearn-overlay-content {
    position: relative;
    background: #fff;
    border-radius: 8px;
    max-width: 600px;
    max-height: 90vh;
    width: 90%;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.skylearn-overlay-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid #e1e5e9;
    background: #f8f9fa;
    border-radius: 8px 8px 0 0;
}

.skylearn-overlay-header h3 {
    margin: 0;
    color: #183153;
    font-size: 20px;
}

.skylearn-overlay-close {
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px;
    border-radius: 4px;
    transition: background-color 0.3s ease;
}

.skylearn-overlay-close:hover {
    background: rgba(0, 0, 0, 0.1);
}

.skylearn-overlay-close .dashicons {
    font-size: 20px;
    color: #666;
}

.skylearn-overlay-body {
    padding: 30px;
}

.skylearn-product-summary {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e1e5e9;
}

.skylearn-product-summary h4 {
    margin: 0 0 10px 0;
    color: #183153;
    font-size: 18px;
}

.skylearn-price-display {
    font-size: 24px;
    font-weight: bold;
    color: #FF3B00;
}

.skylearn-form-section {
    margin-bottom: 25px;
}

.skylearn-form-section h4 {
    margin: 0 0 15px 0;
    color: #333;
    font-size: 16px;
}

.skylearn-field-row {
    margin-bottom: 15px;
}

.skylearn-field-col {
    display: inline-block;
    width: 48%;
    margin-right: 4%;
}

.skylearn-field-col:last-child {
    margin-right: 0;
}

.skylearn-input {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.skylearn-stripe-element {
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #fff;
}

.skylearn-button {
    padding: 12px 24px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    cursor: pointer;
    position: relative;
    transition: all 0.3s ease;
}

.skylearn-button-primary {
    background: #FF3B00;
    color: white;
    margin-right: 10px;
}

.skylearn-button-primary:hover {
    background: #e63600;
}

.skylearn-button-secondary {
    background: #f8f9fa;
    color: #333;
    border: 1px solid #ddd;
}

.skylearn-button-secondary:hover {
    background: #e9ecef;
}

.skylearn-error-message {
    color: #e74c3c;
    font-size: 14px;
    margin-top: 10px;
}

.skylearn-form-actions {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e1e5e9;
}

body.skylearn-overlay-open {
    overflow: hidden;
}

@media (max-width: 600px) {
    .skylearn-overlay-content {
        width: 95%;
        max-height: 95vh;
    }
    
    .skylearn-overlay-header {
        padding: 15px 20px;
    }
    
    .skylearn-overlay-body {
        padding: 20px;
    }
    
    .skylearn-field-col {
        width: 100%;
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .skylearn-button {
        width: 100%;
        margin-bottom: 10px;
        margin-right: 0;
    }
}
</style>