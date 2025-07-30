<?php
/**
 * Hosted checkout warning template
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
$gateway = $args['gateway'] ?? 'lemonsqueezy';
$product_id = $args['product_id'] ?? '';
$product_name = $args['product_name'] ?? __('Course Access', 'skylearn-billing-pro');
$amount = $args['amount'] ?? 0;
$currency = $args['currency'] ?? 'USD';
$redirect_url = $args['redirect_url'] ?? '';
$checkout_url = $args['checkout_url'] ?? '';

$formatted_amount = number_format($amount, 2);
$gateway_name = ucfirst($gateway);
?>

<div class="skylearn-hosted-checkout-warning" role="main" aria-labelledby="hosted-checkout-title">
    <div class="skylearn-warning-container">
        <div class="skylearn-warning-icon" aria-hidden="true">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                <path d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        
        <div class="skylearn-warning-content">
            <h2 id="hosted-checkout-title" class="skylearn-warning-title">
                <?php printf(__('Redirecting to %s Checkout', 'skylearn-billing-pro'), esc_html($gateway_name)); ?>
            </h2>
            
            <div class="skylearn-product-summary">
                <h3 class="skylearn-product-name"><?php echo esc_html($product_name); ?></h3>
                <div class="skylearn-product-price">
                    <?php echo esc_html($currency . ' ' . $formatted_amount); ?>
                </div>
            </div>
            
            <div class="skylearn-warning-message">
                <p>
                    <?php printf(
                        __('You will be redirected to %s to complete your purchase. This is a secure, encrypted connection.', 'skylearn-billing-pro'),
                        '<strong>' . esc_html($gateway_name) . '</strong>'
                    ); ?>
                </p>
                
                <div class="skylearn-security-features">
                    <div class="skylearn-feature">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="#059669" stroke-width="2"/>
                        </svg>
                        <span><?php _e('256-bit SSL encryption', 'skylearn-billing-pro'); ?></span>
                    </div>
                    
                    <div class="skylearn-feature">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="#059669" stroke-width="2"/>
                        </svg>
                        <span><?php _e('PCI DSS compliant', 'skylearn-billing-pro'); ?></span>
                    </div>
                    
                    <div class="skylearn-feature">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" stroke="#059669" stroke-width="2"/>
                        </svg>
                        <span><?php _e('Secure payment processing', 'skylearn-billing-pro'); ?></span>
                    </div>
                </div>
            </div>
            
            <div class="skylearn-redirect-info">
                <p class="skylearn-redirect-note">
                    <strong><?php _e('Important:', 'skylearn-billing-pro'); ?></strong>
                    <?php _e('After completing your payment, you will be automatically redirected back to our site to access your course.', 'skylearn-billing-pro'); ?>
                </p>
                
                <div class="skylearn-redirect-steps">
                    <div class="skylearn-step">
                        <div class="skylearn-step-number" aria-hidden="true">1</div>
                        <div class="skylearn-step-content">
                            <h4><?php _e('Complete Payment', 'skylearn-billing-pro'); ?></h4>
                            <p><?php printf(__('Enter your payment details on the secure %s checkout page', 'skylearn-billing-pro'), esc_html($gateway_name)); ?></p>
                        </div>
                    </div>
                    
                    <div class="skylearn-step">
                        <div class="skylearn-step-number" aria-hidden="true">2</div>
                        <div class="skylearn-step-content">
                            <h4><?php _e('Automatic Redirect', 'skylearn-billing-pro'); ?></h4>
                            <p><?php _e('You\'ll be redirected back to our site automatically after payment', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>
                    
                    <div class="skylearn-step">
                        <div class="skylearn-step-number" aria-hidden="true">3</div>
                        <div class="skylearn-step-content">
                            <h4><?php _e('Access Course', 'skylearn-billing-pro'); ?></h4>
                            <p><?php _e('Log in to your account and start learning immediately', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="skylearn-warning-actions">
                <?php if (!empty($checkout_url)): ?>
                    <a 
                        href="<?php echo esc_url($checkout_url); ?>" 
                        class="skylearn-button skylearn-button-primary skylearn-button-large"
                        role="button"
                        aria-describedby="proceed-button-desc"
                    >
                        <span class="skylearn-button-text">
                            <?php printf(__('Proceed to %s Checkout', 'skylearn-billing-pro'), esc_html($gateway_name)); ?>
                        </span>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M13 7l5 5m0 0l-5 5m5-5H6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                    <div id="proceed-button-desc" class="skylearn-button-description">
                        <?php printf(__('Opens %s checkout in a new window', 'skylearn-billing-pro'), esc_html($gateway_name)); ?>
                    </div>
                <?php endif; ?>
                
                <button type="button" class="skylearn-button skylearn-button-secondary" onclick="history.back()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M11 7l-5 5m0 0l5 5m-5-5h12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php _e('Go Back', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Auto-redirect countdown (optional) -->
    <?php if (!empty($checkout_url) && !isset($args['no_auto_redirect'])): ?>
        <div class="skylearn-auto-redirect" id="skylearn-auto-redirect">
            <div class="skylearn-countdown-container">
                <p><?php _e('Automatically redirecting in', 'skylearn-billing-pro'); ?> <span id="skylearn-countdown">10</span> <?php _e('seconds...', 'skylearn-billing-pro'); ?></p>
                <button type="button" class="skylearn-cancel-redirect" id="skylearn-cancel-redirect">
                    <?php _e('Cancel Auto-Redirect', 'skylearn-billing-pro'); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
/* Hosted checkout warning styles */
.skylearn-hosted-checkout-warning {
    max-width: 700px;
    margin: 40px auto;
    padding: 20px;
}

.skylearn-warning-container {
    background: #ffffff;
    border-radius: 16px;
    padding: 40px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    border: 1px solid #e5e7eb;
    text-align: center;
}

.skylearn-warning-icon {
    margin: 0 auto 20px auto;
    width: 72px;
    height: 72px;
    background: #fef3c7;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.skylearn-warning-title {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 20px 0;
}

.skylearn-product-summary {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 20px;
    margin: 20px 0 30px 0;
}

.skylearn-product-name {
    font-size: 20px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 8px 0;
}

.skylearn-product-price {
    font-size: 32px;
    font-weight: 700;
    color: #059669;
    margin: 0;
}

.skylearn-warning-message {
    text-align: left;
    margin: 30px 0;
}

.skylearn-warning-message p {
    font-size: 16px;
    color: #374151;
    line-height: 1.6;
    margin-bottom: 20px;
}

.skylearn-security-features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.skylearn-feature {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px;
    background: #f0fdf4;
    border: 1px solid #bbf7d0;
    border-radius: 8px;
    font-size: 14px;
    color: #166534;
}

.skylearn-redirect-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 25px;
    margin: 30px 0;
    text-align: left;
}

.skylearn-redirect-note {
    font-size: 16px;
    color: #1e40af;
    margin-bottom: 20px;
}

.skylearn-redirect-steps {
    display: grid;
    gap: 20px;
}

.skylearn-step {
    display: flex;
    gap: 15px;
    align-items: flex-start;
}

.skylearn-step-number {
    width: 32px;
    height: 32px;
    background: #3b82f6;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 14px;
    flex-shrink: 0;
}

.skylearn-step-content h4 {
    font-size: 16px;
    font-weight: 600;
    color: #1e40af;
    margin: 0 0 5px 0;
}

.skylearn-step-content p {
    font-size: 14px;
    color: #374151;
    margin: 0;
    line-height: 1.5;
}

.skylearn-warning-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
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
    min-width: 200px;
}

.skylearn-button-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: #ffffff;
}

.skylearn-button-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.3);
    color: #ffffff;
    text-decoration: none;
}

.skylearn-button-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.skylearn-button-secondary:hover {
    background: #e5e7eb;
    transform: translateY(-1px);
}

.skylearn-button-large {
    padding: 18px 36px;
    font-size: 18px;
    min-width: 250px;
}

.skylearn-button-description {
    font-size: 12px;
    color: #6b7280;
    text-align: center;
    margin-top: 5px;
}

.skylearn-auto-redirect {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: #1f2937;
    color: #ffffff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    z-index: 1000;
    min-width: 280px;
}

.skylearn-countdown-container {
    text-align: center;
}

.skylearn-countdown-container p {
    margin: 0 0 10px 0;
    font-size: 14px;
}

.skylearn-countdown-container #skylearn-countdown {
    font-weight: 700;
    color: #3b82f6;
    font-size: 16px;
}

.skylearn-cancel-redirect {
    background: #374151;
    color: #ffffff;
    border: none;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 12px;
    cursor: pointer;
    transition: background 0.2s ease;
}

.skylearn-cancel-redirect:hover {
    background: #4b5563;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .skylearn-hosted-checkout-warning {
        margin: 20px 10px;
        padding: 10px;
    }
    
    .skylearn-warning-container {
        padding: 30px 20px;
    }
    
    .skylearn-warning-title {
        font-size: 24px;
    }
    
    .skylearn-product-price {
        font-size: 28px;
    }
    
    .skylearn-security-features {
        grid-template-columns: 1fr;
    }
    
    .skylearn-step {
        flex-direction: column;
        text-align: center;
    }
    
    .skylearn-step-number {
        align-self: center;
    }
    
    .skylearn-warning-actions {
        gap: 10px;
    }
    
    .skylearn-button {
        min-width: auto;
        width: 100%;
    }
    
    .skylearn-auto-redirect {
        bottom: 10px;
        right: 10px;
        left: 10px;
        min-width: auto;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .skylearn-warning-container {
        border: 2px solid #000000;
    }
    
    .skylearn-product-summary,
    .skylearn-redirect-info {
        border: 2px solid #000000;
    }
    
    .skylearn-button-primary {
        background: #000000;
        border: 2px solid #000000;
    }
    
    .skylearn-button-secondary {
        border: 2px solid #000000;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .skylearn-button {
        transition: none;
    }
    
    .skylearn-button:hover {
        transform: none;
    }
}

/* Print styles */
@media print {
    .skylearn-auto-redirect {
        display: none;
    }
    
    .skylearn-warning-actions {
        display: none;
    }
}
</style>

<?php if (!empty($checkout_url) && !isset($args['no_auto_redirect'])): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    let countdown = 10;
    let countdownInterval;
    let isRedirectCanceled = false;
    
    const countdownElement = document.getElementById('skylearn-countdown');
    const cancelButton = document.getElementById('skylearn-cancel-redirect');
    const autoRedirectContainer = document.getElementById('skylearn-auto-redirect');
    
    if (!countdownElement || !cancelButton || !autoRedirectContainer) {
        return;
    }
    
    // Start countdown
    function startCountdown() {
        countdownInterval = setInterval(function() {
            countdown--;
            countdownElement.textContent = countdown;
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                if (!isRedirectCanceled) {
                    window.location.href = '<?php echo esc_js($checkout_url); ?>';
                }
            }
        }, 1000);
    }
    
    // Cancel redirect
    cancelButton.addEventListener('click', function() {
        isRedirectCanceled = true;
        clearInterval(countdownInterval);
        autoRedirectContainer.style.display = 'none';
        
        // Show a brief confirmation
        const confirmation = document.createElement('div');
        confirmation.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #059669;
            color: white;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 14px;
            z-index: 1001;
        `;
        confirmation.textContent = '<?php esc_js(_e("Auto-redirect canceled", "skylearn-billing-pro")); ?>';
        document.body.appendChild(confirmation);
        
        setTimeout(() => {
            document.body.removeChild(confirmation);
        }, 3000);
    });
    
    // Pause countdown when user is not active
    let isPageVisible = true;
    
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            isPageVisible = false;
            clearInterval(countdownInterval);
        } else {
            isPageVisible = true;
            if (!isRedirectCanceled && countdown > 0) {
                startCountdown();
            }
        }
    });
    
    // Start the countdown
    startCountdown();
    
    // Add keyboard support for cancel button
    cancelButton.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            this.click();
        }
    });
    
    // Add accessible live region for countdown
    const liveRegion = document.createElement('div');
    liveRegion.setAttribute('aria-live', 'polite');
    liveRegion.setAttribute('aria-atomic', 'true');
    liveRegion.style.position = 'absolute';
    liveRegion.style.left = '-10000px';
    liveRegion.style.width = '1px';
    liveRegion.style.height = '1px';
    liveRegion.style.overflow = 'hidden';
    document.body.appendChild(liveRegion);
    
    // Update live region every 5 seconds
    setInterval(function() {
        if (!isRedirectCanceled && countdown > 0) {
            liveRegion.textContent = '<?php esc_js(_e("Redirecting in", "skylearn-billing-pro")); ?> ' + countdown + ' <?php esc_js(_e("seconds", "skylearn-billing-pro")); ?>';
        }
    }, 5000);
});
</script>
<?php endif; ?>