<?php
/**
 * Checkout success template
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
$order_id = $args['order_id'] ?? '';
$session_id = $args['session_id'] ?? '';
$product_name = $args['product_name'] ?? __('Course Access', 'skylearn-billing-pro');
$amount = $args['amount'] ?? 0;
$currency = $args['currency'] ?? 'USD';
$customer_email = $args['customer_email'] ?? '';
$next_steps = $args['next_steps'] ?? array();

$formatted_amount = number_format($amount, 2);
?>

<div class="skylearn-checkout-success" role="main" aria-labelledby="success-title">
    <div class="skylearn-success-container">
        
        <!-- Success Animation -->
        <div class="skylearn-success-animation" aria-hidden="true">
            <div class="skylearn-success-circle">
                <svg width="80" height="80" viewBox="0 0 80 80" class="skylearn-success-svg">
                    <circle cx="40" cy="40" r="36" fill="none" stroke="#e5e7eb" stroke-width="4"/>
                    <circle cx="40" cy="40" r="36" fill="none" stroke="#10b981" stroke-width="4" stroke-linecap="round" 
                            stroke-dasharray="226" stroke-dashoffset="226" class="skylearn-success-circle-progress"/>
                    <path d="M28 40l8 8 16-16" stroke="#10b981" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" 
                          fill="none" class="skylearn-success-checkmark"/>
                </svg>
            </div>
        </div>
        
        <!-- Success Content -->
        <div class="skylearn-success-content">
            <h1 id="success-title" class="skylearn-success-title">
                <?php _e('Payment Successful!', 'skylearn-billing-pro'); ?>
            </h1>
            
            <p class="skylearn-success-message">
                <?php _e('Thank you for your purchase. Your payment has been processed successfully and you now have access to your course.', 'skylearn-billing-pro'); ?>
            </p>
            
            <!-- Order Details -->
            <div class="skylearn-order-summary">
                <h2 class="skylearn-summary-title"><?php _e('Order Summary', 'skylearn-billing-pro'); ?></h2>
                
                <div class="skylearn-summary-item">
                    <span class="skylearn-summary-label"><?php _e('Product:', 'skylearn-billing-pro'); ?></span>
                    <span class="skylearn-summary-value"><?php echo esc_html($product_name); ?></span>
                </div>
                
                <?php if ($order_id): ?>
                    <div class="skylearn-summary-item">
                        <span class="skylearn-summary-label"><?php _e('Order ID:', 'skylearn-billing-pro'); ?></span>
                        <span class="skylearn-summary-value"><code><?php echo esc_html($order_id); ?></code></span>
                    </div>
                <?php endif; ?>
                
                <div class="skylearn-summary-item">
                    <span class="skylearn-summary-label"><?php _e('Amount Paid:', 'skylearn-billing-pro'); ?></span>
                    <span class="skylearn-summary-value skylearn-summary-amount">
                        <?php echo esc_html($currency . ' ' . $formatted_amount); ?>
                    </span>
                </div>
                
                <?php if ($customer_email): ?>
                    <div class="skylearn-summary-item">
                        <span class="skylearn-summary-label"><?php _e('Email:', 'skylearn-billing-pro'); ?></span>
                        <span class="skylearn-summary-value"><?php echo esc_html($customer_email); ?></span>
                    </div>
                <?php endif; ?>
                
                <div class="skylearn-summary-item">
                    <span class="skylearn-summary-label"><?php _e('Date:', 'skylearn-billing-pro'); ?></span>
                    <span class="skylearn-summary-value"><?php echo esc_html(current_time('F j, Y \a\t g:i A')); ?></span>
                </div>
            </div>
            
            <!-- Next Steps -->
            <div class="skylearn-next-steps">
                <h2 class="skylearn-steps-title"><?php _e('What\'s Next?', 'skylearn-billing-pro'); ?></h2>
                
                <div class="skylearn-steps-grid">
                    <div class="skylearn-step-card">
                        <div class="skylearn-step-icon" aria-hidden="true">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="skylearn-step-title"><?php _e('Check Your Email', 'skylearn-billing-pro'); ?></h3>
                        <p class="skylearn-step-description">
                            <?php _e('A confirmation email with your receipt and course access instructions has been sent to your email address.', 'skylearn-billing-pro'); ?>
                        </p>
                    </div>
                    
                    <div class="skylearn-step-card">
                        <div class="skylearn-step-icon" aria-hidden="true">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="skylearn-step-title"><?php _e('Create Your Account', 'skylearn-billing-pro'); ?></h3>
                        <p class="skylearn-step-description">
                            <?php _e('If you don\'t have an account yet, check your email for login credentials and set up your account.', 'skylearn-billing-pro'); ?>
                        </p>
                    </div>
                    
                    <div class="skylearn-step-card">
                        <div class="skylearn-step-icon" aria-hidden="true">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                                <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke="currentColor" stroke-width="2"/>
                            </svg>
                        </div>
                        <h3 class="skylearn-step-title"><?php _e('Start Learning', 'skylearn-billing-pro'); ?></h3>
                        <p class="skylearn-step-description">
                            <?php _e('Access your course content immediately and begin your learning journey. All materials are now available in your dashboard.', 'skylearn-billing-pro'); ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="skylearn-success-actions">
                <a href="<?php echo esc_url($this->get_portal_url()); ?>" class="skylearn-button skylearn-button-primary skylearn-button-large">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 19V6l7-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM16 16c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <?php _e('Access Your Course', 'skylearn-billing-pro'); ?>
                </a>
                
                <a href="<?php echo esc_url($this->get_portal_url('orders')); ?>" class="skylearn-button skylearn-button-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <?php _e('View Order History', 'skylearn-billing-pro'); ?>
                </a>
                
                <a href="<?php echo esc_url(home_url()); ?>" class="skylearn-button skylearn-button-tertiary">
                    <?php _e('Back to Home', 'skylearn-billing-pro'); ?>
                </a>
            </div>
            
            <!-- Support Information -->
            <div class="skylearn-support-info">
                <h3 class="skylearn-support-title"><?php _e('Need Help?', 'skylearn-billing-pro'); ?></h3>
                <p class="skylearn-support-description">
                    <?php _e('If you have any questions or need assistance accessing your course, our support team is here to help.', 'skylearn-billing-pro'); ?>
                </p>
                <div class="skylearn-support-options">
                    <a href="mailto:support@example.com" class="skylearn-support-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <?php _e('Email Support', 'skylearn-billing-pro'); ?>
                    </a>
                    <a href="#" class="skylearn-support-link">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/>
                        </svg>
                        <?php _e('Help Center', 'skylearn-billing-pro'); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Success page styles */
.skylearn-checkout-success {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
}

.skylearn-success-container {
    background: #ffffff;
    border-radius: 20px;
    padding: 50px;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
    text-align: center;
}

.skylearn-success-animation {
    margin-bottom: 30px;
}

.skylearn-success-circle {
    display: inline-block;
    position: relative;
}

.skylearn-success-svg {
    animation: skylearn-rotate 0.8s ease-out;
}

.skylearn-success-circle-progress {
    animation: skylearn-draw-circle 1.2s ease-out forwards;
}

.skylearn-success-checkmark {
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: skylearn-draw-checkmark 0.6s ease-out 0.8s forwards;
}

@keyframes skylearn-rotate {
    from { transform: rotate(-90deg); }
    to { transform: rotate(0deg); }
}

@keyframes skylearn-draw-circle {
    to { stroke-dashoffset: 0; }
}

@keyframes skylearn-draw-checkmark {
    to { stroke-dashoffset: 0; }
}

.skylearn-success-content {
    text-align: center;
}

.skylearn-success-title {
    font-size: 36px;
    font-weight: 700;
    color: #111827;
    margin: 0 0 15px 0;
}

.skylearn-success-message {
    font-size: 18px;
    color: #6b7280;
    line-height: 1.6;
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.skylearn-order-summary {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 30px;
    margin: 40px 0;
    text-align: left;
}

.skylearn-summary-title {
    font-size: 20px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 20px 0;
    text-align: center;
}

.skylearn-summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
}

.skylearn-summary-item:last-child {
    border-bottom: none;
}

.skylearn-summary-label {
    font-weight: 500;
    color: #374151;
}

.skylearn-summary-value {
    color: #111827;
    font-weight: 500;
}

.skylearn-summary-amount {
    font-size: 20px;
    font-weight: 700;
    color: #059669;
}

.skylearn-summary-value code {
    background: #e5e7eb;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
    font-family: ui-monospace, SFMono-Regular, "SF Mono", Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}

.skylearn-next-steps {
    margin: 50px 0;
    text-align: left;
}

.skylearn-steps-title {
    font-size: 24px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 30px 0;
    text-align: center;
}

.skylearn-steps-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
}

.skylearn-step-card {
    background: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s ease;
}

.skylearn-step-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    transform: translateY(-2px);
}

.skylearn-step-icon {
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px auto;
    color: #ffffff;
}

.skylearn-step-title {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 10px 0;
}

.skylearn-step-description {
    font-size: 14px;
    color: #6b7280;
    line-height: 1.5;
    margin: 0;
}

.skylearn-success-actions {
    display: flex;
    flex-direction: column;
    gap: 15px;
    align-items: center;
    margin: 40px 0;
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
    color: #374151;
    text-decoration: none;
}

.skylearn-button-tertiary {
    background: transparent;
    color: #6b7280;
    border: none;
    padding: 12px 24px;
}

.skylearn-button-tertiary:hover {
    color: #374151;
    text-decoration: underline;
}

.skylearn-button-large {
    padding: 18px 36px;
    font-size: 18px;
    min-width: 250px;
}

.skylearn-support-info {
    background: #eff6ff;
    border: 1px solid #bfdbfe;
    border-radius: 12px;
    padding: 25px;
    margin-top: 40px;
    text-align: center;
}

.skylearn-support-title {
    font-size: 18px;
    font-weight: 600;
    color: #1e40af;
    margin: 0 0 10px 0;
}

.skylearn-support-description {
    font-size: 14px;
    color: #1e40af;
    margin: 0 0 20px 0;
    line-height: 1.5;
}

.skylearn-support-options {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
}

.skylearn-support-link {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #1d4ed8;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.skylearn-support-link:hover {
    background: #dbeafe;
    text-decoration: none;
    color: #1e40af;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .skylearn-checkout-success {
        margin: 20px 10px;
        padding: 10px;
    }
    
    .skylearn-success-container {
        padding: 30px 20px;
    }
    
    .skylearn-success-title {
        font-size: 28px;
    }
    
    .skylearn-success-message {
        font-size: 16px;
    }
    
    .skylearn-steps-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .skylearn-success-actions {
        gap: 10px;
    }
    
    .skylearn-button {
        min-width: auto;
        width: 100%;
        max-width: 300px;
    }
    
    .skylearn-summary-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
    
    .skylearn-support-options {
        flex-direction: column;
        align-items: center;
        gap: 10px;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .skylearn-success-container {
        border: 2px solid #000000;
    }
    
    .skylearn-order-summary,
    .skylearn-step-card,
    .skylearn-support-info {
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
    .skylearn-success-svg,
    .skylearn-success-circle-progress,
    .skylearn-success-checkmark {
        animation: none;
    }
    
    .skylearn-success-circle-progress {
        stroke-dashoffset: 0;
    }
    
    .skylearn-success-checkmark {
        stroke-dashoffset: 0;
    }
    
    .skylearn-button,
    .skylearn-step-card,
    .skylearn-support-link {
        transition: none;
    }
    
    .skylearn-button:hover,
    .skylearn-step-card:hover {
        transform: none;
    }
}

/* Print styles */
@media print {
    .skylearn-success-actions,
    .skylearn-support-info {
        display: none;
    }
    
    .skylearn-success-container {
        box-shadow: none;
        border: 1px solid #000;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add some interactive enhancements
    
    // Copy order ID functionality
    const orderIdElement = document.querySelector('.skylearn-summary-value code');
    if (orderIdElement) {
        orderIdElement.style.cursor = 'pointer';
        orderIdElement.title = '<?php esc_attr_e("Click to copy order ID", "skylearn-billing-pro"); ?>';
        
        orderIdElement.addEventListener('click', function() {
            if (navigator.clipboard) {
                navigator.clipboard.writeText(this.textContent).then(() => {
                    // Show brief success message
                    const originalText = this.textContent;
                    this.textContent = '<?php esc_js(_e("Copied!", "skylearn-billing-pro")); ?>';
                    this.style.background = '#10b981';
                    this.style.color = 'white';
                    
                    setTimeout(() => {
                        this.textContent = originalText;
                        this.style.background = '#e5e7eb';
                        this.style.color = '';
                    }, 1500);
                });
            }
        });
    }
    
    // Track successful conversion
    if (typeof gtag !== 'undefined') {
        gtag('event', 'purchase', {
            'transaction_id': '<?php echo esc_js($order_id); ?>',
            'value': <?php echo floatval($amount); ?>,
            'currency': '<?php echo esc_js($currency); ?>',
            'items': [{
                'item_id': '<?php echo esc_js($args["product_id"] ?? ""); ?>',
                'item_name': '<?php echo esc_js($product_name); ?>',
                'category': 'Course',
                'quantity': 1,
                'price': <?php echo floatval($amount); ?>
            }]
        });
    }
    
    // Add keyboard navigation enhancement
    const actionButtons = document.querySelectorAll('.skylearn-button');
    actionButtons.forEach((button, index) => {
        button.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' && index < actionButtons.length - 1) {
                e.preventDefault();
                actionButtons[index + 1].focus();
            } else if (e.key === 'ArrowUp' && index > 0) {
                e.preventDefault();
                actionButtons[index - 1].focus();
            }
        });
    });
    
    // Auto-focus the primary action button after animations
    setTimeout(() => {
        const primaryButton = document.querySelector('.skylearn-button-primary');
        if (primaryButton) {
            primaryButton.focus();
        }
    }, 2000);
});
</script>

<?php
// Helper method to get portal URL
if (!function_exists('get_portal_url')) {
    function get_portal_url($section = '') {
        $pages = get_option('skylearn_billing_pro_pages', array());
        
        if (!empty($section) && isset($pages['portal_' . $section])) {
            return get_permalink($pages['portal_' . $section]);
        } elseif (isset($pages['portal'])) {
            return get_permalink($pages['portal']);
        }
        
        return home_url('/skylearn-portal/');
    }
}
?>