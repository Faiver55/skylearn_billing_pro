<?php
/**
 * Renew Popup Template
 *
 * Nurture popup for subscription renewals
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

// Extract popup data
$title = isset($popup_data['title']) ? $popup_data['title'] : __('Your subscription expires soon', 'skylearn-billing-pro');
$subtitle = isset($popup_data['subtitle']) ? $popup_data['subtitle'] : __('Don\'t lose access to your courses and progress. Renew today!', 'skylearn-billing-pro');
$offers = isset($popup_data['offers']) ? $popup_data['offers'] : array();
$cancel_text = isset($popup_data['cancel_text']) ? $popup_data['cancel_text'] : __('Let it expire', 'skylearn-billing-pro');

// Get user's current subscription info for the popup
$user_id = get_current_user_id();
$subscription = skylearn_billing_pro_subscription_manager()->get_user_active_subscription($user_id);
$expires_in_days = 0;
if ($subscription && !empty($subscription['next_payment_date'])) {
    $expires_date = new DateTime($subscription['next_payment_date']);
    $now = new DateTime();
    $diff = $now->diff($expires_date);
    $expires_in_days = $diff->days;
}
?>

<div class="skylearn-popup-content renew-popup">
    <div class="popup-header">
        <div class="popup-icon">
            <i class="icon-clock"></i>
        </div>
        <h2 class="popup-title"><?php echo esc_html($title); ?></h2>
        <?php if ($subtitle): ?>
            <p class="popup-subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
        
        <?php if ($expires_in_days >= 0): ?>
            <div class="expiry-countdown">
                <div class="countdown-number"><?php echo $expires_in_days; ?></div>
                <div class="countdown-label">
                    <?php echo $expires_in_days === 1 ? __('day left', 'skylearn-billing-pro') : __('days left', 'skylearn-billing-pro'); ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="popup-body">
        <!-- Current progress and benefits -->
        <div class="benefits-section">
            <h3 class="section-title"><?php _e('Don\'t lose what you\'ve built:', 'skylearn-billing-pro'); ?></h3>
            
            <div class="current-benefits">
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="icon-book-open"></i>
                    </div>
                    <div class="benefit-content">
                        <h4><?php _e('Course Progress', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('You\'re currently enrolled in 8 courses with 73% average completion', 'skylearn-billing-pro'); ?></p>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: 73%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="icon-award"></i>
                    </div>
                    <div class="benefit-content">
                        <h4><?php _e('Achievements', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('6 certificates earned and 450 loyalty points accumulated', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="icon-trending-up"></i>
                    </div>
                    <div class="benefit-content">
                        <h4><?php _e('Learning Streak', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('You\'ve been consistently learning for 42 days in a row', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- What happens if subscription expires -->
        <div class="consequences-section">
            <h3 class="section-title"><?php _e('What happens if your subscription expires:', 'skylearn-billing-pro'); ?></h3>
            
            <div class="consequences-grid">
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="icon-lock"></i>
                    </div>
                    <div class="consequence-content">
                        <h4><?php _e('Immediate Access Loss', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('All premium courses will be locked immediately', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="icon-pause-circle"></i>
                    </div>
                    <div class="consequence-content">
                        <h4><?php _e('Progress Frozen', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('Your learning progress will be saved but inaccessible', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="icon-download-off"></i>
                    </div>
                    <div class="consequence-content">
                        <h4><?php _e('No Downloads', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('Resources and materials will no longer be downloadable', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="consequence-item">
                    <div class="consequence-icon">
                        <i class="icon-users-off"></i>
                    </div>
                    <div class="consequence-content">
                        <h4><?php _e('Community Access', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('Premium community features will be disabled', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($offers)): ?>
            <div class="renewal-offers">
                <h3 class="section-title"><?php _e('Choose how to continue:', 'skylearn-billing-pro'); ?></h3>
                
                <div class="offers-grid">
                    <?php foreach ($offers as $index => $offer): ?>
                        <div class="offer-card <?php echo $index === 0 ? 'recommended' : ''; ?>" data-offer-type="<?php echo esc_attr($offer['type']); ?>">
                            <?php if ($index === 0): ?>
                                <div class="offer-badge">
                                    <?php _e('Recommended', 'skylearn-billing-pro'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="offer-icon">
                                <?php 
                                $icon_class = 'icon-' . $offer['icon'];
                                switch($offer['icon']) {
                                    case 'auto-renew':
                                        $icon_class = 'icon-refresh-cw';
                                        break;
                                    case 'renew':
                                        $icon_class = 'icon-credit-card';
                                        break;
                                    default:
                                        $icon_class = 'icon-' . $offer['icon'];
                                }
                                ?>
                                <i class="<?php echo esc_attr($icon_class); ?>"></i>
                            </div>
                            
                            <div class="offer-content">
                                <h4 class="offer-title"><?php echo esc_html($offer['title']); ?></h4>
                                <p class="offer-description"><?php echo esc_html($offer['description']); ?></p>
                            </div>
                            
                            <div class="offer-action">
                                <button type="button" 
                                        class="btn btn-primary btn-offer" 
                                        data-offer-type="<?php echo esc_attr($offer['type']); ?>"
                                        onclick="acceptRenewalOffer('<?php echo esc_attr($offer['type']); ?>')">
                                    <?php echo esc_html($offer['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Testimonial section -->
        <div class="testimonial-section">
            <h3 class="section-title"><?php _e('What our continued learners say:', 'skylearn-billing-pro'); ?></h3>
            
            <div class="testimonial-card">
                <div class="testimonial-content">
                    <p>"<?php _e('Staying subscribed was the best decision. The continuous access to new courses and updates has been invaluable for my career growth.', 'skylearn-billing-pro'); ?>"</p>
                </div>
                <div class="testimonial-author">
                    <div class="author-avatar">
                        <img src="<?php echo SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/images/testimonial-avatar.png'; ?>" alt="<?php _e('Student testimonial', 'skylearn-billing-pro'); ?>">
                    </div>
                    <div class="author-info">
                        <strong><?php _e('Alex Thompson', 'skylearn-billing-pro'); ?></strong>
                        <span><?php _e('Continuous learner for 2+ years', 'skylearn-billing-pro'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pricing reminder -->
        <div class="pricing-section">
            <div class="pricing-box">
                <div class="pricing-icon">
                    <i class="icon-tag"></i>
                </div>
                <div class="pricing-content">
                    <h4><?php _e('Current Plan Pricing', 'skylearn-billing-pro'); ?></h4>
                    <div class="price-display">
                        <?php if ($subscription): ?>
                            <span class="currency"><?php echo esc_html($subscription['currency']); ?></span>
                            <span class="amount"><?php echo esc_html(number_format($subscription['amount'])); ?></span>
                            <span class="period">/<?php echo esc_html($subscription['billing_cycle']); ?></span>
                        <?php else: ?>
                            <span class="amount">$49</span>
                            <span class="period">/month</span>
                        <?php endif; ?>
                    </div>
                    <p class="pricing-note"><?php _e('Same great price • No price increases', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-footer">
        <div class="footer-actions">
            <button type="button" 
                    class="btn btn-outline btn-expire" 
                    onclick="letExpire()">
                <?php echo esc_html($cancel_text); ?>
            </button>
            
            <button type="button" 
                    class="btn btn-primary btn-renew-now" 
                    onclick="renewNow()">
                <i class="icon-credit-card"></i>
                <?php _e('Renew Now', 'skylearn-billing-pro'); ?>
            </button>
        </div>
        
        <div class="footer-note">
            <p class="note-text">
                <i class="icon-shield"></i>
                <?php _e('Secure payment • Cancel anytime • Instant reactivation', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>
</div>

<script>
(function($) {
    // Accept renewal offer function
    window.acceptRenewalOffer = function(offerType) {
        var $button = $('[data-offer-type="' + offerType + '"]');
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'skylearn-billing-pro')); ?>');
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'renew',
                action: 'accept_offer',
                offer_type: offerType,
                nonce: skyleanNurturePopup.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data);
                    setTimeout(function() {
                        if (response.data && response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 1500);
                } else {
                    showErrorMessage(response.data);
                    $button.prop('disabled', false).text($button.data('original-text') || '<?php echo esc_js(__('Try Again', 'skylearn-billing-pro')); ?>');
                }
            },
            error: function() {
                showErrorMessage(skyleanNurturePopup.strings.error);
                $button.prop('disabled', false).text($button.data('original-text') || '<?php echo esc_js(__('Try Again', 'skylearn-billing-pro')); ?>');
            }
        });
    };

    // Renew now function
    window.renewNow = function() {
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'renew',
                action: 'manual_renew',
                nonce: skyleanNurturePopup.nonce
            }
        });
        
        window.location.href = '<?php echo home_url('/portal/renew/'); ?>';
    };

    // Let expire function
    window.letExpire = function() {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to let your subscription expire? You\'ll lose access to all premium features.', 'skylearn-billing-pro')); ?>')) {
            return;
        }
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'renew',
                action: 'proceed_anyway',
                nonce: skyleanNurturePopup.nonce
            },
            success: function(response) {
                showSuccessMessage('<?php echo esc_js(__('Your subscription will expire as scheduled.', 'skylearn-billing-pro')); ?>');
                setTimeout(function() {
                    $('#skylearn-nurture-popup-overlay').fadeOut();
                }, 2000);
            }
        });
    };

    // Utility functions for messages
    function showSuccessMessage(message) {
        var $popup = $('.skylearn-popup-content');
        $popup.prepend('<div class="popup-message success"><i class="icon-check"></i> ' + message + '</div>');
        setTimeout(function() {
            $('.popup-message').fadeOut();
        }, 3000);
    }

    function showErrorMessage(message) {
        var $popup = $('.skylearn-popup-content');
        $popup.prepend('<div class="popup-message error"><i class="icon-x"></i> ' + message + '</div>');
        setTimeout(function() {
            $('.popup-message').fadeOut();
        }, 5000);
    }
    
})(jQuery);
</script>

<style>
.renew-popup .popup-header {
    text-align: center;
    margin-bottom: 2rem;
}

.renew-popup .popup-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 1rem;
}

.renew-popup .expiry-countdown {
    background: #fef3c7;
    border: 2px solid #f59e0b;
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
    display: inline-block;
}

.renew-popup .countdown-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #d97706;
    line-height: 1;
}

.renew-popup .countdown-label {
    font-size: 0.875rem;
    color: #92400e;
    font-weight: 500;
}

.renew-popup .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #1f2937;
    text-align: center;
}

.renew-popup .current-benefits {
    margin-bottom: 2rem;
}

.renew-popup .benefit-item {
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.renew-popup .benefit-icon {
    font-size: 1.5rem;
    color: #0284c7;
    flex-shrink: 0;
}

.renew-popup .benefit-content h4 {
    margin-bottom: 0.5rem;
    color: #0c4a6e;
    font-weight: 600;
}

.renew-popup .benefit-content p {
    color: #075985;
    margin-bottom: 0.75rem;
    font-size: 0.875rem;
}

.renew-popup .progress-bar {
    background: #e0f2fe;
    border-radius: 4px;
    height: 8px;
    overflow: hidden;
}

.renew-popup .progress-fill {
    background: #0284c7;
    height: 100%;
    transition: width 0.3s ease;
}

.renew-popup .consequences-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.renew-popup .consequence-item {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.renew-popup .consequence-icon {
    font-size: 1.5rem;
    color: #dc2626;
    flex-shrink: 0;
}

.renew-popup .consequence-content h4 {
    margin-bottom: 0.5rem;
    color: #991b1b;
    font-weight: 600;
}

.renew-popup .consequence-content p {
    color: #7f1d1d;
    margin: 0;
    font-size: 0.875rem;
}

.renew-popup .offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.renew-popup .offer-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.renew-popup .offer-card.recommended {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.renew-popup .offer-card:hover {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.renew-popup .offer-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: #10b981;
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.renew-popup .offer-icon {
    font-size: 2.5rem;
    color: #10b981;
    margin-bottom: 1rem;
}

.renew-popup .offer-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.renew-popup .offer-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.renew-popup .testimonial-card {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.renew-popup .testimonial-content p {
    font-style: italic;
    color: #4b5563;
    margin-bottom: 1.5rem;
    font-size: 1.125rem;
    line-height: 1.6;
    text-align: center;
}

.renew-popup .testimonial-author {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.renew-popup .author-avatar img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.renew-popup .author-info strong {
    display: block;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.renew-popup .author-info span {
    color: #6b7280;
    font-size: 0.875rem;
}

.renew-popup .pricing-box {
    background: #ecfdf5;
    border: 1px solid #10b981;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 2rem;
}

.renew-popup .pricing-icon {
    font-size: 2rem;
    color: #10b981;
    margin-bottom: 1rem;
}

.renew-popup .pricing-content h4 {
    margin-bottom: 1rem;
    color: #065f46;
}

.renew-popup .price-display {
    margin-bottom: 0.5rem;
}

.renew-popup .price-display .currency {
    font-size: 1.25rem;
    color: #047857;
}

.renew-popup .price-display .amount {
    font-size: 2.5rem;
    font-weight: 700;
    color: #065f46;
}

.renew-popup .price-display .period {
    font-size: 1.25rem;
    color: #047857;
}

.renew-popup .pricing-note {
    color: #059669;
    margin: 0;
    font-size: 0.875rem;
}

.renew-popup .popup-footer {
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
}

.renew-popup .footer-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 1rem;
}

.renew-popup .footer-note {
    text-align: center;
}

.renew-popup .note-text {
    color: #6b7280;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.renew-popup .popup-message {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.renew-popup .popup-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.renew-popup .popup-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

@media (max-width: 768px) {
    .renew-popup .consequences-grid {
        grid-template-columns: 1fr;
    }
    
    .renew-popup .offers-grid {
        grid-template-columns: 1fr;
    }
    
    .renew-popup .footer-actions {
        flex-direction: column;
    }
    
    .renew-popup .testimonial-author {
        flex-direction: column;
        text-align: center;
    }
}
</style>