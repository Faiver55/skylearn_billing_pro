<?php
/**
 * Stripe inline checkout template
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

<div class="skylearn-checkout-container skylearn-stripe-checkout">
    <div class="skylearn-checkout-header">
        <h3><?php _e('Complete Your Purchase', 'skylearn-billing-pro'); ?></h3>
        <p class="skylearn-checkout-description">
            <?php echo esc_html($args['product_name'] ?? __('Course Access', 'skylearn-billing-pro')); ?>
            <?php if (!empty($args['price'])): ?>
                - <span class="skylearn-price"><?php echo esc_html($args['currency'] . ' ' . $args['price']); ?></span>
            <?php endif; ?>
        </p>
    </div>
    
    <form id="skylearn-stripe-form" class="skylearn-checkout-form">
        <div class="skylearn-form-section">
            <h4><?php _e('Customer Information', 'skylearn-billing-pro'); ?></h4>
            
            <!-- Default required fields -->
            <div class="skylearn-field-row">
                <div class="skylearn-field-col">
                    <label for="stripe-customer-email"><?php _e('Email Address', 'skylearn-billing-pro'); ?> *</label>
                    <input type="email" id="stripe-customer-email" name="customer_email" required class="skylearn-input" />
                </div>
                <div class="skylearn-field-col">
                    <label for="stripe-customer-name"><?php _e('Full Name', 'skylearn-billing-pro'); ?> *</label>
                    <input type="text" id="stripe-customer-name" name="customer_name" required class="skylearn-input" />
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
            <div id="stripe-card-element" class="skylearn-stripe-element">
                <!-- Stripe Elements will be inserted here -->
            </div>
            
            <div id="stripe-card-errors" class="skylearn-error-message" role="alert"></div>
        </div>
        
        <div class="skylearn-form-actions">
            <button type="submit" id="stripe-submit-button" class="skylearn-button skylearn-button-primary">
                <span class="button-text"><?php _e('Complete Purchase', 'skylearn-billing-pro'); ?></span>
                <span class="button-spinner" style="display: none;">
                    <span class="spinner is-active"></span>
                </span>
            </button>
            
            <div class="skylearn-payment-security">
                <span class="dashicons dashicons-lock"></span>
                <?php _e('Secured by Stripe', 'skylearn-billing-pro'); ?>
            </div>
        </div>
        
        <!-- Hidden fields -->
        <input type="hidden" name="product_id" value="<?php echo esc_attr($args['product_id'] ?? ''); ?>" />
        <input type="hidden" name="amount" value="<?php echo esc_attr($args['amount'] ?? 0); ?>" />
        <input type="hidden" name="currency" value="<?php echo esc_attr($args['currency'] ?? 'USD'); ?>" />
        <input type="hidden" name="gateway" value="stripe" />
        <input type="hidden" name="checkout_type" value="inline" />
        <?php wp_nonce_field('skylearn_checkout_nonce', 'checkout_nonce'); ?>
    </form>
    
    <div id="skylearn-checkout-success" class="skylearn-checkout-success" style="display: none;">
        <div class="skylearn-success-content">
            <span class="dashicons dashicons-yes-alt"></span>
            <h3><?php _e('Payment Successful!', 'skylearn-billing-pro'); ?></h3>
            <p><?php _e('Thank you for your purchase. You will receive an email confirmation shortly.', 'skylearn-billing-pro'); ?></p>
        </div>
    </div>
</div>

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
    width: 100%;
    padding: 15px;
    background: #FF3B00;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 16px;
    cursor: pointer;
    position: relative;
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
    margin-top: 15px;
    color: #666;
    font-size: 12px;
}

.skylearn-error-message {
    color: #e74c3c;
    font-size: 14px;
    margin-top: 10px;
}

.skylearn-checkout-success {
    text-align: center;
    padding: 40px 20px;
}

.skylearn-success-content .dashicons {
    font-size: 48px;
    color: #28a745;
    margin-bottom: 20px;
}

@media (max-width: 600px) {
    .skylearn-field-col {
        width: 100%;
        margin-right: 0;
        margin-bottom: 15px;
    }
}
</style>