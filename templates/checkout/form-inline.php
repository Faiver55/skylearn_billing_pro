<?php
/**
 * Inline checkout form template
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

// Extract arguments
$gateway = $args['gateway'] ?? 'stripe';
$product_id = $args['product_id'] ?? '';
$product_name = $args['product_name'] ?? __('Course Access', 'skylearn-billing-pro');
$amount = $args['amount'] ?? 0;
$currency = $args['currency'] ?? 'USD';
$redirect_url = $args['redirect_url'] ?? '';

$formatted_amount = number_format($amount, 2);
?>

<div class="skylearn-checkout-container skylearn-inline-checkout" role="main" aria-labelledby="skylearn-checkout-title">
    <div class="skylearn-checkout-header">
        <h2 id="skylearn-checkout-title"><?php _e('Complete Your Purchase', 'skylearn-billing-pro'); ?></h2>
        <div class="skylearn-product-info">
            <h3 class="skylearn-product-name"><?php echo esc_html($product_name); ?></h3>
            <div class="skylearn-product-price">
                <span class="skylearn-currency"><?php echo esc_html($currency); ?></span>
                <span class="skylearn-amount"><?php echo esc_html($formatted_amount); ?></span>
            </div>
        </div>
    </div>
    
    <form id="skylearn-inline-checkout-form" class="skylearn-checkout-form" method="post" action="" novalidate>
        <?php wp_nonce_field('skylearn_checkout_nonce', 'skylearn_checkout_nonce'); ?>
        
        <input type="hidden" name="gateway" value="<?php echo esc_attr($gateway); ?>" />
        <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>" />
        <input type="hidden" name="amount" value="<?php echo esc_attr($amount); ?>" />
        <input type="hidden" name="currency" value="<?php echo esc_attr($currency); ?>" />
        <input type="hidden" name="redirect_url" value="<?php echo esc_attr($redirect_url); ?>" />
        
        <!-- Customer Information -->
        <fieldset class="skylearn-form-section" aria-labelledby="customer-info-title">
            <legend id="customer-info-title" class="skylearn-section-title">
                <?php _e('Customer Information', 'skylearn-billing-pro'); ?>
            </legend>
            
            <div class="skylearn-field-row">
                <div class="skylearn-field-col">
                    <label for="customer-email" class="skylearn-field-label">
                        <?php _e('Email Address', 'skylearn-billing-pro'); ?>
                        <span class="skylearn-required" aria-label="<?php esc_attr_e('Required', 'skylearn-billing-pro'); ?>">*</span>
                    </label>
                    <input 
                        type="email" 
                        id="customer-email" 
                        name="customer_email" 
                        class="skylearn-input" 
                        required 
                        aria-describedby="customer-email-desc"
                        autocomplete="email"
                    />
                    <div id="customer-email-desc" class="skylearn-field-description">
                        <?php _e('We\'ll send your receipt and course access information here.', 'skylearn-billing-pro'); ?>
                    </div>
                </div>
                
                <div class="skylearn-field-col">
                    <label for="customer-name" class="skylearn-field-label">
                        <?php _e('Full Name', 'skylearn-billing-pro'); ?>
                        <span class="skylearn-required" aria-label="<?php esc_attr_e('Required', 'skylearn-billing-pro'); ?>">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="customer-name" 
                        name="customer_name" 
                        class="skylearn-input" 
                        required
                        autocomplete="name"
                    />
                </div>
            </div>
        </fieldset>
        
        <!-- Payment Information -->
        <fieldset class="skylearn-form-section" aria-labelledby="payment-info-title">
            <legend id="payment-info-title" class="skylearn-section-title">
                <?php _e('Payment Information', 'skylearn-billing-pro'); ?>
            </legend>
            
            <div id="skylearn-payment-element" class="skylearn-payment-element">
                <!-- Payment gateway specific elements will be inserted here -->
            </div>
        </fieldset>
        
        <!-- Billing Address (if required) -->
        <fieldset class="skylearn-form-section skylearn-billing-address" aria-labelledby="billing-address-title" style="display: none;">
            <legend id="billing-address-title" class="skylearn-section-title">
                <?php _e('Billing Address', 'skylearn-billing-pro'); ?>
            </legend>
            
            <div class="skylearn-field-row">
                <div class="skylearn-field-col skylearn-field-col-full">
                    <label for="billing-address-1" class="skylearn-field-label">
                        <?php _e('Address Line 1', 'skylearn-billing-pro'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="billing-address-1" 
                        name="billing_address_1" 
                        class="skylearn-input"
                        autocomplete="address-line1"
                    />
                </div>
            </div>
            
            <div class="skylearn-field-row">
                <div class="skylearn-field-col">
                    <label for="billing-city" class="skylearn-field-label">
                        <?php _e('City', 'skylearn-billing-pro'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="billing-city" 
                        name="billing_city" 
                        class="skylearn-input"
                        autocomplete="address-level2"
                    />
                </div>
                
                <div class="skylearn-field-col">
                    <label for="billing-state" class="skylearn-field-label">
                        <?php _e('State/Province', 'skylearn-billing-pro'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="billing-state" 
                        name="billing_state" 
                        class="skylearn-input"
                        autocomplete="address-level1"
                    />
                </div>
                
                <div class="skylearn-field-col">
                    <label for="billing-zip" class="skylearn-field-label">
                        <?php _e('ZIP/Postal Code', 'skylearn-billing-pro'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="billing-zip" 
                        name="billing_zip" 
                        class="skylearn-input"
                        autocomplete="postal-code"
                    />
                </div>
            </div>
            
            <div class="skylearn-field-row">
                <div class="skylearn-field-col">
                    <label for="billing-country" class="skylearn-field-label">
                        <?php _e('Country', 'skylearn-billing-pro'); ?>
                    </label>
                    <select 
                        id="billing-country" 
                        name="billing_country" 
                        class="skylearn-select"
                        autocomplete="country"
                    >
                        <option value=""><?php _e('Select Country', 'skylearn-billing-pro'); ?></option>
                        <option value="US"><?php _e('United States', 'skylearn-billing-pro'); ?></option>
                        <option value="CA"><?php _e('Canada', 'skylearn-billing-pro'); ?></option>
                        <option value="GB"><?php _e('United Kingdom', 'skylearn-billing-pro'); ?></option>
                        <!-- Add more countries as needed -->
                    </select>
                </div>
            </div>
        </fieldset>
        
        <!-- Terms and Conditions -->
        <div class="skylearn-form-section">
            <div class="skylearn-checkbox-field">
                <label for="terms-agreement" class="skylearn-checkbox-label">
                    <input 
                        type="checkbox" 
                        id="terms-agreement" 
                        name="terms_agreement" 
                        class="skylearn-checkbox" 
                        required
                        aria-describedby="terms-desc"
                    />
                    <span class="skylearn-checkbox-checkmark" aria-hidden="true"></span>
                    <?php _e('I agree to the Terms of Service and Privacy Policy', 'skylearn-billing-pro'); ?>
                    <span class="skylearn-required" aria-label="<?php esc_attr_e('Required', 'skylearn-billing-pro'); ?>">*</span>
                </label>
                <div id="terms-desc" class="skylearn-field-description">
                    <a href="#" target="_blank" rel="noopener"><?php _e('Terms of Service', 'skylearn-billing-pro'); ?></a> | 
                    <a href="#" target="_blank" rel="noopener"><?php _e('Privacy Policy', 'skylearn-billing-pro'); ?></a>
                </div>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div class="skylearn-form-actions">
            <button 
                type="submit" 
                id="skylearn-submit-payment" 
                class="skylearn-button skylearn-button-primary skylearn-button-large"
                aria-describedby="submit-button-desc"
            >
                <span class="skylearn-button-text">
                    <?php printf(__('Pay %s %s', 'skylearn-billing-pro'), esc_html($currency), esc_html($formatted_amount)); ?>
                </span>
                <span class="skylearn-button-spinner" aria-hidden="true" style="display: none;">
                    <svg width="20" height="20" viewBox="0 0 50 50">
                        <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                            <animate attributeName="stroke-array" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                            <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                        </circle>
                    </svg>
                </span>
            </button>
            <div id="submit-button-desc" class="skylearn-field-description">
                <?php _e('Your payment is secured with SSL encryption.', 'skylearn-billing-pro'); ?>
            </div>
        </div>
        
        <!-- Error Messages -->
        <div id="skylearn-checkout-errors" class="skylearn-error-container" role="alert" aria-live="polite" style="display: none;">
        </div>
    </form>
    
    <!-- Security Badges -->
    <div class="skylearn-security-badges" role="img" aria-label="<?php esc_attr_e('Security badges', 'skylearn-billing-pro'); ?>">
        <div class="skylearn-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 1l3 9h9l-7 5 3 9-8-6-8 6 3-9-7-5h9l3-9z" fill="#ffd700"/>
            </svg>
            <span><?php _e('SSL Secured', 'skylearn-billing-pro'); ?></span>
        </div>
        <div class="skylearn-badge">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" stroke-width="2"/>
            </svg>
            <span><?php _e('256-bit Encryption', 'skylearn-billing-pro'); ?></span>
        </div>
    </div>
</div>

<style>
/* Inline checkout styles */
.skylearn-inline-checkout {
    max-width: 600px;
    margin: 0 auto;
    padding: 30px;
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

.skylearn-checkout-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.skylearn-checkout-header h2 {
    margin: 0 0 15px 0;
    font-size: 28px;
    font-weight: 700;
    color: #111827;
}

.skylearn-product-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
}

.skylearn-product-name {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #374151;
}

.skylearn-product-price {
    font-size: 24px;
    font-weight: 700;
    color: #059669;
}

.skylearn-form-section {
    margin-bottom: 30px;
    border: none;
    padding: 0;
}

.skylearn-section-title {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #e5e7eb;
}

.skylearn-field-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.skylearn-field-col {
    flex: 1;
    min-width: 250px;
}

.skylearn-field-col-full {
    flex: 1 1 100%;
}

.skylearn-field-label {
    display: block;
    font-weight: 500;
    color: #374151;
    margin-bottom: 5px;
}

.skylearn-required {
    color: #dc2626;
    margin-left: 3px;
}

.skylearn-input,
.skylearn-select {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.2s ease;
    background: #ffffff;
}

.skylearn-input:focus,
.skylearn-select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.skylearn-field-description {
    font-size: 14px;
    color: #6b7280;
    margin-top: 5px;
}

.skylearn-checkbox-field {
    margin: 20px 0;
}

.skylearn-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    font-size: 14px;
    line-height: 1.5;
}

.skylearn-checkbox {
    width: 18px;
    height: 18px;
    margin: 0;
}

.skylearn-form-actions {
    text-align: center;
    margin-top: 30px;
}

.skylearn-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 16px 32px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
    overflow: hidden;
}

.skylearn-button-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
}

.skylearn-button-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.skylearn-button-primary:active {
    transform: translateY(0);
}

.skylearn-button-large {
    padding: 18px 36px;
    font-size: 18px;
}

.skylearn-button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none !important;
}

.skylearn-error-container {
    background: #fef2f2;
    border: 1px solid #fecaca;
    color: #991b1b;
    padding: 12px 16px;
    border-radius: 8px;
    margin-top: 15px;
}

.skylearn-security-badges {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
}

.skylearn-badge {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 14px;
    color: #6b7280;
}

/* Responsive design */
@media (max-width: 768px) {
    .skylearn-inline-checkout {
        padding: 20px;
        margin: 10px;
    }
    
    .skylearn-field-row {
        flex-direction: column;
        gap: 0;
    }
    
    .skylearn-field-col {
        min-width: auto;
    }
    
    .skylearn-product-info {
        flex-direction: column;
        text-align: center;
    }
    
    .skylearn-security-badges {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
}

/* High contrast mode support */
@media (prefers-contrast: high) {
    .skylearn-inline-checkout {
        border: 2px solid #000000;
    }
    
    .skylearn-input,
    .skylearn-select {
        border: 2px solid #000000;
    }
    
    .skylearn-button-primary {
        background: #000000;
        border: 2px solid #000000;
    }
}

/* Reduced motion support */
@media (prefers-reduced-motion: reduce) {
    .skylearn-button,
    .skylearn-input,
    .skylearn-select {
        transition: none;
    }
    
    .skylearn-button-spinner svg animate {
        animation-duration: 0s;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('skylearn-inline-checkout-form');
    const submitButton = document.getElementById('skylearn-submit-payment');
    const errorContainer = document.getElementById('skylearn-checkout-errors');
    
    if (!form || !submitButton) return;
    
    // Form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Clear previous errors
        hideErrors();
        
        // Validate form
        if (!validateForm()) {
            return;
        }
        
        // Show loading state
        setLoadingState(true);
        
        // Submit form via AJAX
        const formData = new FormData(form);
        formData.append('action', 'skylearn_process_inline_checkout');
        
        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Handle successful payment
                if (data.data.redirect_url) {
                    window.location.href = data.data.redirect_url;
                } else {
                    showSuccess(data.data.message || '<?php esc_js(_e("Payment processed successfully!", "skylearn-billing-pro")); ?>');
                }
            } else {
                showError(data.data.message || '<?php esc_js(_e("An error occurred during payment processing.", "skylearn-billing-pro")); ?>');
            }
        })
        .catch(error => {
            console.error('Payment error:', error);
            showError('<?php esc_js(_e("A network error occurred. Please try again.", "skylearn-billing-pro")); ?>');
        })
        .finally(() => {
            setLoadingState(false);
        });
    });
    
    function validateForm() {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('skylearn-field-error');
                isValid = false;
            } else {
                field.classList.remove('skylearn-field-error');
            }
        });
        
        // Email validation
        const emailField = form.querySelector('#customer-email');
        if (emailField && emailField.value) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(emailField.value)) {
                emailField.classList.add('skylearn-field-error');
                isValid = false;
            }
        }
        
        if (!isValid) {
            showError('<?php esc_js(_e("Please fill in all required fields correctly.", "skylearn-billing-pro")); ?>');
        }
        
        return isValid;
    }
    
    function setLoadingState(loading) {
        const buttonText = submitButton.querySelector('.skylearn-button-text');
        const buttonSpinner = submitButton.querySelector('.skylearn-button-spinner');
        
        if (loading) {
            submitButton.disabled = true;
            buttonText.textContent = '<?php esc_js(_e("Processing...", "skylearn-billing-pro")); ?>';
            buttonSpinner.style.display = 'inline-block';
        } else {
            submitButton.disabled = false;
            buttonText.textContent = '<?php printf(esc_js(__("Pay %s %s", "skylearn-billing-pro")), esc_js($currency), esc_js($formatted_amount)); ?>';
            buttonSpinner.style.display = 'none';
        }
    }
    
    function showError(message) {
        errorContainer.innerHTML = '<p>' + message + '</p>';
        errorContainer.style.display = 'block';
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Set focus to error for screen readers
        errorContainer.setAttribute('tabindex', '-1');
        errorContainer.focus();
    }
    
    function showSuccess(message) {
        errorContainer.innerHTML = '<p style="background: #f0fdf4; border-color: #bbf7d0; color: #166534;">' + message + '</p>';
        errorContainer.style.display = 'block';
        errorContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    function hideErrors() {
        errorContainer.style.display = 'none';
        errorContainer.innerHTML = '';
    }
    
    // Real-time validation
    const inputs = form.querySelectorAll('.skylearn-input, .skylearn-select');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('skylearn-field-error');
            } else {
                this.classList.remove('skylearn-field-error');
            }
            
            // Email validation on blur
            if (this.type === 'email' && this.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(this.value)) {
                    this.classList.add('skylearn-field-error');
                } else {
                    this.classList.remove('skylearn-field-error');
                }
            }
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('skylearn-field-error') && this.value.trim()) {
                this.classList.remove('skylearn-field-error');
            }
        });
    });
});
</script>