<?php
/**
 * Downgrade Popup Template
 *
 * Nurture popup for subscription downgrades
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
$title = isset($popup_data['title']) ? $popup_data['title'] : __('Consider your options', 'skylearn-billing-pro');
$subtitle = isset($popup_data['subtitle']) ? $popup_data['subtitle'] : __('Before you downgrade, see what you\'ll be missing out on.', 'skylearn-billing-pro');
$offers = isset($popup_data['offers']) ? $popup_data['offers'] : array();
$cancel_text = isset($popup_data['cancel_text']) ? $popup_data['cancel_text'] : __('Proceed with downgrade', 'skylearn-billing-pro');
?>

<div class="skylearn-popup-content downgrade-popup">
    <div class="popup-header">
        <div class="popup-icon">
            <i class="icon-arrow-down-circle"></i>
        </div>
        <h2 class="popup-title"><?php echo esc_html($title); ?></h2>
        <?php if ($subtitle): ?>
            <p class="popup-subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
    </div>

    <div class="popup-body">
        <!-- What you'll lose -->
        <div class="impact-section">
            <h3 class="section-title"><?php _e('Here\'s what you\'ll lose with a downgrade:', 'skylearn-billing-pro'); ?></h3>
            
            <div class="impact-grid">
                <div class="impact-item losing">
                    <div class="impact-icon">
                        <i class="icon-book-x"></i>
                    </div>
                    <div class="impact-content">
                        <h4><?php _e('Course Access', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('You\'ll lose access to 40+ premium courses', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="impact-item losing">
                    <div class="impact-icon">
                        <i class="icon-download-x"></i>
                    </div>
                    <div class="impact-content">
                        <h4><?php _e('Download Limits', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('Unlimited downloads will be reduced to 25/month', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="impact-item losing">
                    <div class="impact-icon">
                        <i class="icon-user-x"></i>
                    </div>
                    <div class="impact-content">
                        <h4><?php _e('Priority Support', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('Support response time will increase', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="impact-item losing">
                    <div class="impact-icon">
                        <i class="icon-award-x"></i>
                    </div>
                    <div class="impact-content">
                        <h4><?php _e('Certificates', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('No more completion certificates', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="impact-item losing">
                    <div class="impact-icon">
                        <i class="icon-star-x"></i>
                    </div>
                    <div class="impact-content">
                        <h4><?php _e('Exclusive Content', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('Premium workshops and resources', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
                
                <div class="impact-item losing">
                    <div class="impact-icon">
                        <i class="icon-users-x"></i>
                    </div>
                    <div class="impact-content">
                        <h4><?php _e('Community Access', 'skylearn-billing-pro'); ?></h4>
                        <p><?php _e('Premium community forums and events', 'skylearn-billing-pro'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current progress -->
        <div class="progress-section">
            <h3 class="section-title"><?php _e('Your current progress:', 'skylearn-billing-pro'); ?></h3>
            
            <div class="progress-stats">
                <div class="stat-item">
                    <div class="stat-number">12</div>
                    <div class="stat-label"><?php _e('Courses completed', 'skylearn-billing-pro'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">89%</div>
                    <div class="stat-label"><?php _e('Average completion rate', 'skylearn-billing-pro'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">45</div>
                    <div class="stat-label"><?php _e('Hours of learning', 'skylearn-billing-pro'); ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">6</div>
                    <div class="stat-label"><?php _e('Certificates earned', 'skylearn-billing-pro'); ?></div>
                </div>
            </div>
            
            <div class="progress-warning">
                <div class="warning-icon">
                    <i class="icon-alert-triangle"></i>
                </div>
                <div class="warning-content">
                    <h4><?php _e('Don\'t lose your momentum!', 'skylearn-billing-pro'); ?></h4>
                    <p><?php _e('You\'ve made great progress. Downgrading now might slow down your learning journey.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>

        <?php if (!empty($offers)): ?>
            <div class="retention-offers">
                <h3 class="section-title"><?php _e('Consider these alternatives:', 'skylearn-billing-pro'); ?></h3>
                
                <div class="offers-grid">
                    <?php foreach ($offers as $index => $offer): ?>
                        <div class="offer-card" data-offer-type="<?php echo esc_attr($offer['type']); ?>">
                            <div class="offer-icon">
                                <?php 
                                $icon_class = 'icon-' . $offer['icon'];
                                switch($offer['icon']) {
                                    case 'stay':
                                        $icon_class = 'icon-heart';
                                        break;
                                    case 'pause':
                                        $icon_class = 'icon-pause-circle';
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
                                        onclick="acceptDowngradeOffer('<?php echo esc_attr($offer['type']); ?>')">
                                    <?php echo esc_html($offer['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Savings information -->
        <div class="savings-section">
            <div class="savings-box">
                <div class="savings-icon">
                    <i class="icon-dollar-sign"></i>
                </div>
                <div class="savings-content">
                    <h4><?php _e('Cost vs. Value Analysis', 'skylearn-billing-pro'); ?></h4>
                    <div class="savings-breakdown">
                        <div class="savings-item">
                            <span class="label"><?php _e('Premium plan:', 'skylearn-billing-pro'); ?></span>
                            <span class="value">$49/month</span>
                        </div>
                        <div class="savings-item">
                            <span class="label"><?php _e('Value you get:', 'skylearn-billing-pro'); ?></span>
                            <span class="value highlight">$200+/month</span>
                        </div>
                        <div class="savings-item">
                            <span class="label"><?php _e('Your savings:', 'skylearn-billing-pro'); ?></span>
                            <span class="value highlight">$151+/month</span>
                        </div>
                    </div>
                    <p class="savings-note"><?php _e('Based on equivalent course prices from other platforms', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="popup-footer">
        <div class="footer-actions">
            <button type="button" 
                    class="btn btn-secondary btn-dismiss" 
                    onclick="dismissDowngradePopup()">
                <?php _e('Keep my current plan', 'skylearn-billing-pro'); ?>
            </button>
            
            <button type="button" 
                    class="btn btn-outline btn-proceed" 
                    onclick="proceedWithDowngrade()">
                <?php echo esc_html($cancel_text); ?>
            </button>
        </div>
        
        <div class="footer-note">
            <p class="note-text">
                <i class="icon-info"></i>
                <?php _e('You can upgrade again anytime • Your progress will be saved', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>
</div>

<script>
(function($) {
    // Accept downgrade offer function
    window.acceptDowngradeOffer = function(offerType) {
        var $button = $('[data-offer-type="' + offerType + '"]');
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'skylearn-billing-pro')); ?>');
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'downgrade',
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
                            $('#skylearn-nurture-popup-overlay').fadeOut();
                        }
                    }, 2000);
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

    // Proceed with downgrade
    window.proceedWithDowngrade = function() {
        var $button = $('.btn-proceed');
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'skylearn-billing-pro')); ?>');
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'downgrade',
                action: 'proceed_anyway',
                nonce: skyleanNurturePopup.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data);
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showErrorMessage(response.data);
                    $button.prop('disabled', false).text('<?php echo esc_js($cancel_text); ?>');
                }
            },
            error: function() {
                showErrorMessage(skyleanNurturePopup.strings.error);
                $button.prop('disabled', false).text('<?php echo esc_js($cancel_text); ?>');
            }
        });
    };

    // Dismiss popup
    window.dismissDowngradePopup = function() {
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'downgrade',
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
.downgrade-popup .popup-header {
    text-align: center;
    margin-bottom: 2rem;
}

.downgrade-popup .popup-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 1rem;
}

.downgrade-popup .section-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 1.5rem;
    color: #1f2937;
    text-align: center;
}

.downgrade-popup .impact-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.downgrade-popup .impact-item {
    background: #fef2f2;
    border: 1px solid #fca5a5;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.downgrade-popup .impact-icon {
    font-size: 1.5rem;
    color: #dc2626;
    flex-shrink: 0;
}

.downgrade-popup .impact-content h4 {
    margin-bottom: 0.5rem;
    color: #991b1b;
    font-weight: 600;
}

.downgrade-popup .impact-content p {
    color: #7f1d1d;
    margin: 0;
    font-size: 0.875rem;
}

.downgrade-popup .progress-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.downgrade-popup .stat-item {
    text-align: center;
    background: #f0f9ff;
    border: 1px solid #0ea5e9;
    border-radius: 8px;
    padding: 1rem;
}

.downgrade-popup .stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #0369a1;
    margin-bottom: 0.5rem;
}

.downgrade-popup .stat-label {
    font-size: 0.875rem;
    color: #075985;
}

.downgrade-popup .progress-warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 2rem;
}

.downgrade-popup .warning-icon {
    font-size: 1.5rem;
    color: #d97706;
    flex-shrink: 0;
}

.downgrade-popup .warning-content h4 {
    margin-bottom: 0.5rem;
    color: #92400e;
}

.downgrade-popup .warning-content p {
    color: #78350f;
    margin: 0;
}

.downgrade-popup .offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.downgrade-popup .offer-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
}

.downgrade-popup .offer-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.downgrade-popup .offer-icon {
    font-size: 2.5rem;
    color: #3b82f6;
    margin-bottom: 1rem;
}

.downgrade-popup .offer-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.downgrade-popup .offer-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.downgrade-popup .savings-section {
    margin-bottom: 2rem;
}

.downgrade-popup .savings-box {
    background: #f0fdf4;
    border: 1px solid #10b981;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.downgrade-popup .savings-icon {
    font-size: 2rem;
    color: #10b981;
    flex-shrink: 0;
}

.downgrade-popup .savings-content h4 {
    margin-bottom: 1rem;
    color: #065f46;
}

.downgrade-popup .savings-breakdown {
    margin-bottom: 1rem;
}

.downgrade-popup .savings-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
}

.downgrade-popup .savings-item .label {
    color: #047857;
}

.downgrade-popup .savings-item .value {
    font-weight: 600;
    color: #065f46;
}

.downgrade-popup .savings-item .value.highlight {
    color: #059669;
    font-size: 1rem;
}

.downgrade-popup .savings-note {
    font-size: 0.75rem;
    color: #059669;
    margin: 0;
    font-style: italic;
}

.downgrade-popup .popup-footer {
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
}

.downgrade-popup .footer-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 1rem;
}

.downgrade-popup .footer-note {
    text-align: center;
}

.downgrade-popup .note-text {
    color: #6b7280;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.downgrade-popup .popup-message {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.downgrade-popup .popup-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.downgrade-popup .popup-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}

@media (max-width: 768px) {
    .downgrade-popup .impact-grid {
        grid-template-columns: 1fr;
    }
    
    .downgrade-popup .progress-stats {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .downgrade-popup .footer-actions {
        flex-direction: column;
    }
}
</style>