<?php
/**
 * Overlay checkout form template
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
$unique_id = 'skylearn-overlay-' . wp_generate_password(8, false);
?>

<div id="<?php echo esc_attr($unique_id); ?>" class="skylearn-checkout-overlay" role="dialog" aria-labelledby="<?php echo esc_attr($unique_id); ?>-title" aria-modal="true" style="display: none;">
    <div class="skylearn-overlay-backdrop" aria-hidden="true"></div>
    
    <div class="skylearn-overlay-container">
        <div class="skylearn-overlay-content">
            <!-- Close button -->
            <button type="button" class="skylearn-overlay-close" aria-label="<?php esc_attr_e('Close checkout', 'skylearn-billing-pro'); ?>">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
            
            <!-- Header -->
            <div class="skylearn-overlay-header">
                <h2 id="<?php echo esc_attr($unique_id); ?>-title" class="skylearn-overlay-title">
                    <?php _e('Complete Your Purchase', 'skylearn-billing-pro'); ?>
                </h2>
                <div class="skylearn-product-summary">
                    <div class="skylearn-product-info">
                        <h3 class="skylearn-product-name"><?php echo esc_html($product_name); ?></h3>
                        <div class="skylearn-product-price">
                            <?php echo esc_html($currency . ' ' . $formatted_amount); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Form -->
            <form id="<?php echo esc_attr($unique_id); ?>-form" class="skylearn-overlay-form" method="post" action="" novalidate>
                <?php wp_nonce_field('skylearn_checkout_nonce', 'skylearn_checkout_nonce'); ?>
                
                <input type="hidden" name="gateway" value="<?php echo esc_attr($gateway); ?>" />
                <input type="hidden" name="product_id" value="<?php echo esc_attr($product_id); ?>" />
                <input type="hidden" name="amount" value="<?php echo esc_attr($amount); ?>" />
                <input type="hidden" name="currency" value="<?php echo esc_attr($currency); ?>" />
                <input type="hidden" name="redirect_url" value="<?php echo esc_attr($redirect_url); ?>" />
                
                <!-- Customer Information -->
                <div class="skylearn-form-section">
                    <h3 class="skylearn-section-title"><?php _e('Customer Information', 'skylearn-billing-pro'); ?></h3>
                    
                    <div class="skylearn-field-group">
                        <div class="skylearn-field">
                            <label for="<?php echo esc_attr($unique_id); ?>-email" class="skylearn-field-label">
                                <?php _e('Email Address', 'skylearn-billing-pro'); ?>
                                <span class="skylearn-required">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="<?php echo esc_attr($unique_id); ?>-email" 
                                name="customer_email" 
                                class="skylearn-input" 
                                required 
                                autocomplete="email"
                                placeholder="<?php esc_attr_e('Enter your email address', 'skylearn-billing-pro'); ?>"
                            />
                        </div>
                        
                        <div class="skylearn-field">
                            <label for="<?php echo esc_attr($unique_id); ?>-name" class="skylearn-field-label">
                                <?php _e('Full Name', 'skylearn-billing-pro'); ?>
                                <span class="skylearn-required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="<?php echo esc_attr($unique_id); ?>-name" 
                                name="customer_name" 
                                class="skylearn-input" 
                                required
                                autocomplete="name"
                                placeholder="<?php esc_attr_e('Enter your full name', 'skylearn-billing-pro'); ?>"
                            />
                        </div>
                    </div>
                </div>
                
                <!-- Payment Method -->
                <div class="skylearn-form-section">
                    <h3 class="skylearn-section-title"><?php _e('Payment Method', 'skylearn-billing-pro'); ?></h3>
                    <div id="<?php echo esc_attr($unique_id); ?>-payment-element" class="skylearn-payment-element">
                        <!-- Payment gateway specific elements will be inserted here -->
                    </div>
                </div>
                
                <!-- Terms -->
                <div class="skylearn-form-section">
                    <label class="skylearn-checkbox-label">
                        <input 
                            type="checkbox" 
                            id="<?php echo esc_attr($unique_id); ?>-terms" 
                            name="terms_agreement" 
                            class="skylearn-checkbox" 
                            required
                        />
                        <span class="skylearn-checkbox-mark"></span>
                        <span class="skylearn-checkbox-text">
                            <?php _e('I agree to the', 'skylearn-billing-pro'); ?>
                            <a href="#" target="_blank" rel="noopener"><?php _e('Terms of Service', 'skylearn-billing-pro'); ?></a>
                            <?php _e('and', 'skylearn-billing-pro'); ?>
                            <a href="#" target="_blank" rel="noopener"><?php _e('Privacy Policy', 'skylearn-billing-pro'); ?></a>
                        </span>
                    </label>
                </div>
                
                <!-- Error Messages -->
                <div id="<?php echo esc_attr($unique_id); ?>-errors" class="skylearn-error-container" role="alert" aria-live="polite" style="display: none;">
                </div>
                
                <!-- Submit Button -->
                <div class="skylearn-form-actions">
                    <button 
                        type="submit" 
                        id="<?php echo esc_attr($unique_id); ?>-submit" 
                        class="skylearn-button skylearn-button-primary skylearn-button-large"
                    >
                        <span class="skylearn-button-text">
                            <?php printf(__('Pay %s %s', 'skylearn-billing-pro'), esc_html($currency), esc_html($formatted_amount)); ?>
                        </span>
                        <span class="skylearn-button-spinner" style="display: none;">
                            <svg width="20" height="20" viewBox="0 0 50 50" aria-hidden="true">
                                <circle cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                    <animate attributeName="stroke-array" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </span>
                    </button>
                    
                    <button type="button" class="skylearn-button skylearn-button-secondary skylearn-overlay-cancel">
                        <?php _e('Cancel', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
                
                <!-- Security Badge -->
                <div class="skylearn-security-info">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <span><?php _e('Secure 256-bit SSL encryption', 'skylearn-billing-pro'); ?></span>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Overlay checkout styles */
.skylearn-checkout-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 10000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.skylearn-overlay-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.75);
    backdrop-filter: blur(4px);
}

.skylearn-overlay-container {
    position: relative;
    width: 100%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
    background: #ffffff;
    border-radius: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    animation: skylEarnFadeInUp 0.3s ease-out;
}

@keyframes skylEarnFadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.skylearn-overlay-content {
    padding: 30px;
    position: relative;
}

.skylearn-overlay-close {
    position: absolute;
    top: 20px;
    right: 20px;
    width: 40px;
    height: 40px;
    background: #f3f4f6;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    z-index: 1;
}

.skylearn-overlay-close:hover {
    background: #e5e7eb;
    transform: scale(1.05);
}

.skylearn-overlay-close:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

.skylearn-overlay-header {
    text-align: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e5e7eb;
}

.skylearn-overlay-title {
    margin: 0 0 15px 0;
    font-size: 24px;
    font-weight: 700;
    color: #111827;
}

.skylearn-product-summary {
    background: #f9fafb;
    padding: 15px;
    border-radius: 8px;
}

.skylearn-product-info {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.skylearn-product-name {
    margin: 0;
    font-size: 16px;
    font-weight: 600;
    color: #374151;
}

.skylearn-product-price {
    font-size: 20px;
    font-weight: 700;
    color: #059669;
}

.skylearn-form-section {
    margin-bottom: 25px;
}

.skylearn-section-title {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 15px 0;
}

.skylearn-field-group {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.skylearn-field {
    display: flex;
    flex-direction: column;
}

.skylearn-field-label {
    font-weight: 500;
    color: #374151;
    margin-bottom: 5px;
    font-size: 14px;
}

.skylearn-required {
    color: #dc2626;
    margin-left: 3px;
}

.skylearn-input {
    width: 100%;
    padding: 12px 16px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 16px;
    transition: all 0.2s ease;
    background: #ffffff;
}

.skylearn-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.skylearn-input.skylearn-field-error {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
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
    accent-color: #3b82f6;
}

.skylearn-checkbox-text a {
    color: #3b82f6;
    text-decoration: none;
}

.skylearn-checkbox-text a:hover {
    text-decoration: underline;
}

.skylearn-form-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 25px;
}

.skylearn-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
    position: relative;
}

.skylearn-button-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
}

.skylearn-button-primary:hover:not(:disabled) {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.skylearn-button-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.skylearn-button-secondary:hover {
    background: #e5e7eb;
}

.skylearn-button-large {
    padding: 16px 32px;
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
    margin-bottom: 15px;
    font-size: 14px;
}

.skylearn-security-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e5e7eb;
    font-size: 12px;
    color: #6b7280;
}

/* Mobile responsive */
@media (max-width: 640px) {
    .skylearn-checkout-overlay {
        padding: 10px;
    }
    
    .skylearn-overlay-container {
        max-height: 95vh;
    }
    
    .skylearn-overlay-content {
        padding: 20px;
    }
    
    .skylearn-overlay-title {
        font-size: 20px;
    }
    
    .skylearn-product-info {
        flex-direction: column;
        gap: 10px;
        text-align: center;
    }
    
    .skylearn-form-actions {
        gap: 15px;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .skylearn-overlay-backdrop {
        background: rgba(0, 0, 0, 0.9);
    }
    
    .skylearn-overlay-container {
        border: 2px solid #000000;
    }
    
    .skylearn-input {
        border: 2px solid #000000;
    }
    
    .skylearn-button-primary {
        background: #000000;
        border: 2px solid #000000;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .skylearn-overlay-container {
        animation: none;
    }
    
    .skylearn-button,
    .skylearn-input,
    .skylearn-overlay-close {
        transition: none;
    }
    
    .skylearn-button-spinner svg animate {
        animation-duration: 0s;
    }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .skylearn-overlay-container {
        background: #1f2937;
        color: #f9fafb;
    }
    
    .skylearn-overlay-title,
    .skylearn-section-title {
        color: #f9fafb;
    }
    
    .skylearn-product-summary {
        background: #374151;
    }
    
    .skylearn-input {
        background: #374151;
        border-color: #4b5563;
        color: #f9fafb;
    }
    
    .skylearn-input:focus {
        border-color: #60a5fa;
    }
    
    .skylearn-field-label {
        color: #d1d5db;
    }
    
    .skylearn-button-secondary {
        background: #374151;
        color: #f9fafb;
        border-color: #4b5563;
    }
    
    .skylearn-security-info {
        color: #9ca3af;
        border-color: #4b5563;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.getElementById('<?php echo esc_js($unique_id); ?>');
    const form = document.getElementById('<?php echo esc_js($unique_id); ?>-form');
    const closeButton = overlay.querySelector('.skylearn-overlay-close');
    const cancelButton = overlay.querySelector('.skylearn-overlay-cancel');
    const submitButton = document.getElementById('<?php echo esc_js($unique_id); ?>-submit');
    const errorContainer = document.getElementById('<?php echo esc_js($unique_id); ?>-errors');
    
    // Show overlay
    function showOverlay() {
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Focus management
        const firstInput = form.querySelector('input[type="email"]');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
        
        // Trap focus within overlay
        trapFocus(overlay);
    }
    
    // Hide overlay
    function hideOverlay() {
        overlay.style.display = 'none';
        document.body.style.overflow = '';
        
        // Return focus to trigger element if available
        const triggerElement = document.querySelector('[data-skylearn-trigger="<?php echo esc_js($unique_id); ?>"]');
        if (triggerElement) {
            triggerElement.focus();
        }
    }
    
    // Event listeners
    closeButton.addEventListener('click', hideOverlay);
    cancelButton.addEventListener('click', hideOverlay);
    
    // Close on backdrop click
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay || e.target.classList.contains('skylearn-overlay-backdrop')) {
            hideOverlay();
        }
    });
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.style.display === 'flex') {
            hideOverlay();
        }
    });
    
    // Form submission
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
        formData.append('action', 'skylearn_process_overlay_checkout');
        
        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.data.redirect_url) {
                    window.location.href = data.data.redirect_url;
                } else {
                    showSuccess(data.data.message || '<?php esc_js(_e("Payment processed successfully!", "skylearn-billing-pro")); ?>');
                    setTimeout(hideOverlay, 2000);
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
        const emailField = form.querySelector('input[type="email"]');
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
        errorContainer.focus();
    }
    
    function showSuccess(message) {
        errorContainer.innerHTML = '<div style="background: #f0fdf4; border-color: #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 8px;"><p>' + message + '</p></div>';
        errorContainer.style.display = 'block';
    }
    
    function hideErrors() {
        errorContainer.style.display = 'none';
        errorContainer.innerHTML = '';
    }
    
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        lastElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        firstElement.focus();
                        e.preventDefault();
                    }
                }
            }
        });
    }
    
    // Real-time validation
    const inputs = form.querySelectorAll('.skylearn-input');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.hasAttribute('required') && !this.value.trim()) {
                this.classList.add('skylearn-field-error');
            } else {
                this.classList.remove('skylearn-field-error');
            }
            
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
    
    // Expose show function globally for trigger buttons
    window.skylearn_show_overlay_<?php echo esc_js(str_replace('-', '_', $unique_id)); ?> = showOverlay;
});
</script>