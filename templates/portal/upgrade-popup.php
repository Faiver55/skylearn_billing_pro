<?php
/**
 * Upgrade Popup Template
 *
 * Nurture popup for subscription upgrades
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
$title = isset($popup_data['title']) ? $popup_data['title'] : __('Unlock your full potential', 'skylearn-billing-pro');
$subtitle = isset($popup_data['subtitle']) ? $popup_data['subtitle'] : __('Upgrade to access premium features and accelerate your learning.', 'skylearn-billing-pro');
$offers = isset($popup_data['offers']) ? $popup_data['offers'] : array();
$cancel_text = isset($popup_data['cancel_text']) ? $popup_data['cancel_text'] : __('Maybe later', 'skylearn-billing-pro');
?>

<div class="skylearn-popup-content upgrade-popup">
    <div class="popup-header">
        <div class="popup-icon">
            <i class="icon-trending-up"></i>
        </div>
        <h2 class="popup-title"><?php echo esc_html($title); ?></h2>
        <?php if ($subtitle): ?>
            <p class="popup-subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
    </div>

    <div class="popup-body">
        <!-- Feature Comparison -->
        <div class="feature-comparison">
            <h3 class="section-title"><?php _e('See what you\'re missing:', 'skylearn-billing-pro'); ?></h3>
            
            <div class="comparison-table">
                <div class="comparison-header">
                    <div class="plan-column current-plan">
                        <h4><?php _e('Your Current Plan', 'skylearn-billing-pro'); ?></h4>
                        <span class="plan-badge basic"><?php _e('Basic', 'skylearn-billing-pro'); ?></span>
                    </div>
                    <div class="plan-column upgrade-plan">
                        <h4><?php _e('Premium Plan', 'skylearn-billing-pro'); ?></h4>
                        <span class="plan-badge premium"><?php _e('Premium', 'skylearn-billing-pro'); ?></span>
                    </div>
                </div>
                
                <div class="comparison-features">
                    <div class="feature-row">
                        <div class="feature-name"><?php _e('Course Access', 'skylearn-billing-pro'); ?></div>
                        <div class="feature-value current">
                            <i class="icon-check"></i> <?php _e('10 courses', 'skylearn-billing-pro'); ?>
                        </div>
                        <div class="feature-value upgrade highlighted">
                            <i class="icon-check"></i> <?php _e('50+ courses', 'skylearn-billing-pro'); ?>
                        </div>
                    </div>
                    
                    <div class="feature-row">
                        <div class="feature-name"><?php _e('Downloads', 'skylearn-billing-pro'); ?></div>
                        <div class="feature-value current">
                            <i class="icon-check"></i> <?php _e('25 per month', 'skylearn-billing-pro'); ?>
                        </div>
                        <div class="feature-value upgrade highlighted">
                            <i class="icon-check"></i> <?php _e('Unlimited', 'skylearn-billing-pro'); ?>
                        </div>
                    </div>
                    
                    <div class="feature-row">
                        <div class="feature-name"><?php _e('Support Level', 'skylearn-billing-pro'); ?></div>
                        <div class="feature-value current">
                            <i class="icon-check"></i> <?php _e('Email support', 'skylearn-billing-pro'); ?>
                        </div>
                        <div class="feature-value upgrade highlighted">
                            <i class="icon-check"></i> <?php _e('Priority support', 'skylearn-billing-pro'); ?>
                        </div>
                    </div>
                    
                    <div class="feature-row">
                        <div class="feature-name"><?php _e('Certificates', 'skylearn-billing-pro'); ?></div>
                        <div class="feature-value current">
                            <i class="icon-x"></i> <?php _e('Not included', 'skylearn-billing-pro'); ?>
                        </div>
                        <div class="feature-value upgrade highlighted">
                            <i class="icon-check"></i> <?php _e('Included', 'skylearn-billing-pro'); ?>
                        </div>
                    </div>
                    
                    <div class="feature-row">
                        <div class="feature-name"><?php _e('Exclusive Content', 'skylearn-billing-pro'); ?></div>
                        <div class="feature-value current">
                            <i class="icon-x"></i> <?php _e('Not included', 'skylearn-billing-pro'); ?>
                        </div>
                        <div class="feature-value upgrade highlighted">
                            <i class="icon-check"></i> <?php _e('Premium workshops', 'skylearn-billing-pro'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($offers)): ?>
            <div class="upgrade-offers">
                <h3 class="section-title"><?php _e('Special upgrade offers:', 'skylearn-billing-pro'); ?></h3>
                
                <div class="offers-grid">
                    <?php foreach ($offers as $index => $offer): ?>
                        <div class="offer-card <?php echo $index === 0 ? 'featured' : ''; ?>" data-offer-type="<?php echo esc_attr($offer['type']); ?>">
                            <?php if ($index === 0): ?>
                                <div class="offer-badge">
                                    <?php _e('Most Popular', 'skylearn-billing-pro'); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="offer-icon">
                                <?php 
                                $icon_class = 'icon-' . $offer['icon'];
                                switch($offer['icon']) {
                                    case 'trial':
                                        $icon_class = 'icon-clock';
                                        break;
                                    case 'discount':
                                        $icon_class = 'icon-percent';
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
                                        onclick="acceptUpgradeOffer('<?php echo esc_attr($offer['type']); ?>')">
                                    <?php echo esc_html($offer['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Success Stories -->
        <div class="success-stories">
            <h3 class="section-title"><?php _e('What our Premium members say:', 'skylearn-billing-pro'); ?></h3>
            
            <div class="testimonials-grid">
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"<?php _e('The premium courses helped me advance my career significantly. Best investment I\'ve made!', 'skylearn-billing-pro'); ?>"</p>
                    </div>
                    <div class="testimonial-author">
                        <strong><?php _e('Sarah J.', 'skylearn-billing-pro'); ?></strong>
                        <span><?php _e('Software Developer', 'skylearn-billing-pro'); ?></span>
                    </div>
                </div>
                
                <div class="testimonial">
                    <div class="testimonial-content">
                        <p>"<?php _e('The exclusive workshops and priority support made all the difference in my learning journey.', 'skylearn-billing-pro'); ?>"</p>
                    </div>
                    <div class="testimonial-author">
                        <strong><?php _e('Mike R.', 'skylearn-billing-pro'); ?></strong>
                        <span><?php _e('Product Manager', 'skylearn-billing-pro'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Money-back guarantee -->
        <div class="guarantee-section">
            <div class="guarantee-box">
                <div class="guarantee-icon">
                    <i class="icon-shield-check"></i>
                </div>
                <div class="guarantee-content">
                    <h4><?php _e('30-Day Money-Back Guarantee', 'skylearn-billing-pro'); ?></h4>
                    <p><?php _e('Try Premium risk-free. If you\'re not satisfied within 30 days, get a full refund.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-footer">
        <div class="footer-actions">
            <button type="button" 
                    class="btn btn-secondary btn-dismiss" 
                    onclick="dismissUpgradePopup()">
                <?php echo esc_html($cancel_text); ?>
            </button>
            
            <button type="button" 
                    class="btn btn-primary btn-upgrade" 
                    onclick="upgradeNow()">
                <i class="icon-arrow-up"></i>
                <?php _e('Upgrade to Premium', 'skylearn-billing-pro'); ?>
            </button>
        </div>
        
        <div class="footer-note">
            <p class="note-text">
                <i class="icon-lock"></i>
                <?php _e('Secure payment • Cancel anytime • Instant access', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>
</div>

<script>
(function($) {
    // Accept upgrade offer function
    window.acceptUpgradeOffer = function(offerType) {
        var $button = $('[data-offer-type="' + offerType + '"]');
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'skylearn-billing-pro')); ?>');
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'upgrade',
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
                            window.location.href = '<?php echo home_url('/checkout/?plan=premium'); ?>';
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

    // Upgrade now function
    window.upgradeNow = function() {
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'upgrade',
                action: 'upgrade_now',
                nonce: skyleanNurturePopup.nonce
            }
        });
        
        window.location.href = '<?php echo home_url('/checkout/?plan=premium'); ?>';
    };

    // Dismiss popup
    window.dismissUpgradePopup = function() {
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'upgrade',
                action: 'dismiss',
                nonce: skyleanNurturePopup.nonce
            }
        });
        
        $('#skylearn-nurture-popup-overlay').fadeOut();
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
.upgrade-popup .popup-header {
    text-align: center;
    margin-bottom: 2rem;
}

.upgrade-popup .popup-icon {
    font-size: 3rem;
    color: #10b981;
    margin-bottom: 1rem;
}

.upgrade-popup .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #1f2937;
    text-align: center;
}

.upgrade-popup .comparison-table {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 2rem;
}

.upgrade-popup .comparison-header {
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: #f9fafb;
}

.upgrade-popup .plan-column {
    padding: 1rem;
    text-align: center;
    border-right: 1px solid #e5e7eb;
}

.upgrade-popup .plan-column:last-child {
    border-right: none;
}

.upgrade-popup .plan-column h4 {
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.upgrade-popup .plan-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.upgrade-popup .plan-badge.basic {
    background: #dbeafe;
    color: #1e40af;
}

.upgrade-popup .plan-badge.premium {
    background: #d1fae5;
    color: #065f46;
}

.upgrade-popup .comparison-features {
    background: white;
}

.upgrade-popup .feature-row {
    display: grid;
    grid-template-columns: 1fr 1fr 1fr;
    border-bottom: 1px solid #f3f4f6;
    align-items: center;
}

.upgrade-popup .feature-row:last-child {
    border-bottom: none;
}

.upgrade-popup .feature-name {
    padding: 1rem;
    font-weight: 500;
    color: #374151;
    background: #f9fafb;
    border-right: 1px solid #e5e7eb;
}

.upgrade-popup .feature-value {
    padding: 1rem;
    text-align: center;
    border-right: 1px solid #e5e7eb;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.upgrade-popup .feature-value:last-child {
    border-right: none;
}

.upgrade-popup .feature-value.highlighted {
    background: #f0fdf4;
    color: #15803d;
    font-weight: 500;
}

.upgrade-popup .feature-value i.icon-check {
    color: #10b981;
}

.upgrade-popup .feature-value i.icon-x {
    color: #ef4444;
}

.upgrade-popup .offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.upgrade-popup .offer-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    position: relative;
    transition: all 0.3s ease;
}

.upgrade-popup .offer-card.featured {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.upgrade-popup .offer-card:hover {
    border-color: #10b981;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.upgrade-popup .offer-badge {
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

.upgrade-popup .offer-icon {
    font-size: 2.5rem;
    color: #10b981;
    margin-bottom: 1rem;
}

.upgrade-popup .offer-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.upgrade-popup .offer-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.upgrade-popup .testimonials-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.upgrade-popup .testimonial {
    background: #f9fafb;
    border-radius: 8px;
    padding: 1.5rem;
}

.upgrade-popup .testimonial-content p {
    font-style: italic;
    color: #4b5563;
    margin-bottom: 1rem;
    line-height: 1.6;
}

.upgrade-popup .testimonial-author strong {
    display: block;
    color: #1f2937;
    margin-bottom: 0.25rem;
}

.upgrade-popup .testimonial-author span {
    color: #6b7280;
    font-size: 0.875rem;
}

.upgrade-popup .guarantee-section {
    margin-bottom: 2rem;
}

.upgrade-popup .guarantee-box {
    background: #ecfdf5;
    border: 1px solid #10b981;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.upgrade-popup .guarantee-icon {
    font-size: 2rem;
    color: #10b981;
    flex-shrink: 0;
}

.upgrade-popup .guarantee-content h4 {
    margin-bottom: 0.5rem;
    color: #065f46;
}

.upgrade-popup .guarantee-content p {
    color: #047857;
    margin: 0;
}

.upgrade-popup .popup-footer {
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
}

.upgrade-popup .footer-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 1rem;
}

.upgrade-popup .footer-note {
    text-align: center;
}

.upgrade-popup .note-text {
    color: #6b7280;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.upgrade-popup .popup-message {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.upgrade-popup .popup-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.upgrade-popup .popup-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

@media (max-width: 768px) {
    .upgrade-popup .comparison-header,
    .upgrade-popup .feature-row {
        grid-template-columns: 1fr;
    }
    
    .upgrade-popup .feature-row {
        text-align: center;
    }
    
    .upgrade-popup .feature-name {
        background: #1f2937;
        color: white;
        border-right: none;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .upgrade-popup .feature-value {
        border-right: none;
        border-bottom: 1px solid #f3f4f6;
    }
}
</style>