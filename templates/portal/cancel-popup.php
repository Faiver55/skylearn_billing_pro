<?php
/**
 * Cancel Popup Template
 *
 * Nurture popup for subscription cancellation
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
$title = isset($popup_data['title']) ? $popup_data['title'] : __('Wait! Before you cancel...', 'skylearn-billing-pro');
$subtitle = isset($popup_data['subtitle']) ? $popup_data['subtitle'] : __('We hate to see you go. Let us help you find the right solution.', 'skylearn-billing-pro');
$offers = isset($popup_data['offers']) ? $popup_data['offers'] : array();
$cancel_text = isset($popup_data['cancel_text']) ? $popup_data['cancel_text'] : __('No thanks, cancel anyway', 'skylearn-billing-pro');
?>

<div class="skylearn-popup-content cancel-popup">
    <div class="popup-header">
        <div class="popup-icon">
            <i class="icon-alert-triangle"></i>
        </div>
        <h2 class="popup-title"><?php echo esc_html($title); ?></h2>
        <?php if ($subtitle): ?>
            <p class="popup-subtitle"><?php echo esc_html($subtitle); ?></p>
        <?php endif; ?>
    </div>

    <div class="popup-body">
        <?php if (!empty($offers)): ?>
            <div class="retention-offers">
                <h3 class="offers-title"><?php _e('Before you go, consider these options:', 'skylearn-billing-pro'); ?></h3>
                
                <div class="offers-grid">
                    <?php foreach ($offers as $index => $offer): ?>
                        <div class="offer-card" data-offer-type="<?php echo esc_attr($offer['type']); ?>">
                            <div class="offer-icon">
                                <?php 
                                $icon_class = 'icon-' . $offer['icon'];
                                switch($offer['icon']) {
                                    case 'pause':
                                        $icon_class = 'icon-pause-circle';
                                        break;
                                    case 'discount':
                                        $icon_class = 'icon-percent';
                                        break;
                                    case 'downgrade':
                                        $icon_class = 'icon-arrow-down-circle';
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
                                        onclick="acceptOffer('<?php echo esc_attr($offer['type']); ?>')">
                                    <?php echo esc_html($offer['button_text']); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="popup-divider">
                <span class="divider-text"><?php _e('or', 'skylearn-billing-pro'); ?></span>
            </div>
        <?php endif; ?>

        <div class="cancellation-info">
            <div class="info-box warning">
                <div class="info-icon">
                    <i class="icon-info"></i>
                </div>
                <div class="info-content">
                    <h4><?php _e('What happens when you cancel:', 'skylearn-billing-pro'); ?></h4>
                    <ul class="consequence-list">
                        <li><i class="icon-x"></i> <?php _e('You\'ll lose access to all premium courses', 'skylearn-billing-pro'); ?></li>
                        <li><i class="icon-x"></i> <?php _e('Your progress will be saved but locked', 'skylearn-billing-pro'); ?></li>
                        <li><i class="icon-x"></i> <?php _e('No more downloads or resources', 'skylearn-billing-pro'); ?></li>
                        <li><i class="icon-x"></i> <?php _e('Support access will be limited', 'skylearn-billing-pro'); ?></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="feedback-section">
            <h4><?php _e('Help us improve - Why are you cancelling?', 'skylearn-billing-pro'); ?></h4>
            <div class="feedback-options">
                <label class="feedback-option">
                    <input type="radio" name="cancel_reason" value="too_expensive">
                    <span class="option-text"><?php _e('Too expensive', 'skylearn-billing-pro'); ?></span>
                </label>
                <label class="feedback-option">
                    <input type="radio" name="cancel_reason" value="not_using">
                    <span class="option-text"><?php _e('Not using it enough', 'skylearn-billing-pro'); ?></span>
                </label>
                <label class="feedback-option">
                    <input type="radio" name="cancel_reason" value="technical_issues">
                    <span class="option-text"><?php _e('Technical issues', 'skylearn-billing-pro'); ?></span>
                </label>
                <label class="feedback-option">
                    <input type="radio" name="cancel_reason" value="found_alternative">
                    <span class="option-text"><?php _e('Found better alternative', 'skylearn-billing-pro'); ?></span>
                </label>
                <label class="feedback-option">
                    <input type="radio" name="cancel_reason" value="other">
                    <span class="option-text"><?php _e('Other reason', 'skylearn-billing-pro'); ?></span>
                </label>
            </div>
            
            <div class="feedback-text" style="display: none;">
                <textarea name="cancel_feedback" 
                          placeholder="<?php _e('Please tell us more about your reason...', 'skylearn-billing-pro'); ?>"
                          rows="3"></textarea>
            </div>
        </div>
    </div>

    <div class="popup-footer">
        <div class="footer-actions">
            <button type="button" 
                    class="btn btn-secondary btn-dismiss" 
                    onclick="dismissPopup()">
                <?php _e('Keep my subscription', 'skylearn-billing-pro'); ?>
            </button>
            
            <button type="button" 
                    class="btn btn-danger btn-proceed" 
                    onclick="proceedWithCancellation()">
                <?php echo esc_html($cancel_text); ?>
            </button>
        </div>
        
        <div class="footer-note">
            <p class="note-text">
                <i class="icon-shield"></i>
                <?php _e('You can always reactivate your subscription later.', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>
</div>

<script>
(function($) {
    // Handle feedback option changes
    $('input[name="cancel_reason"]').on('change', function() {
        var $feedbackText = $('.feedback-text');
        if ($(this).val() === 'other') {
            $feedbackText.slideDown();
        } else {
            $feedbackText.slideUp();
        }
    });

    // Accept offer function
    window.acceptOffer = function(offerType) {
        var $button = $('[data-offer-type="' + offerType + '"]');
        $button.prop('disabled', true).text('<?php echo esc_js(__('Processing...', 'skylearn-billing-pro')); ?>');
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'cancel',
                action: 'accept_offer',
                offer_type: offerType,
                nonce: skyleanNurturePopup.nonce
            },
            success: function(response) {
                if (response.success) {
                    showSuccessMessage(response.data);
                    setTimeout(function() {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else {
                            window.location.reload();
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

    // Proceed with cancellation
    window.proceedWithCancellation = function() {
        var reason = $('input[name="cancel_reason"]:checked').val() || '';
        var feedback = $('textarea[name="cancel_feedback"]').val() || '';
        
        if (!reason) {
            alert('<?php echo esc_js(__('Please select a reason for cancellation.', 'skylearn-billing-pro')); ?>');
            return;
        }
        
        var $button = $('.btn-proceed');
        $button.prop('disabled', true).text('<?php echo esc_js(__('Cancelling...', 'skylearn-billing-pro')); ?>');
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'cancel',
                action: 'proceed_anyway',
                cancel_reason: reason,
                cancel_feedback: feedback,
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
    window.dismissPopup = function() {
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: 'cancel',
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
.cancel-popup .popup-header {
    text-align: center;
    margin-bottom: 2rem;
}

.cancel-popup .popup-icon {
    font-size: 3rem;
    color: #f59e0b;
    margin-bottom: 1rem;
}

.cancel-popup .offers-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.cancel-popup .offer-card {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
}

.cancel-popup .offer-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
}

.cancel-popup .offer-icon {
    font-size: 2.5rem;
    color: #3b82f6;
    margin-bottom: 1rem;
}

.cancel-popup .offer-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
    color: #1f2937;
}

.cancel-popup .offer-description {
    color: #6b7280;
    margin-bottom: 1.5rem;
    line-height: 1.5;
}

.cancel-popup .popup-divider {
    text-align: center;
    margin: 2rem 0;
    position: relative;
}

.cancel-popup .popup-divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #e5e7eb;
}

.cancel-popup .divider-text {
    background: white;
    padding: 0 1rem;
    color: #6b7280;
    position: relative;
}

.cancel-popup .info-box {
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.cancel-popup .info-box.warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
}

.cancel-popup .info-box .info-content h4 {
    margin-bottom: 1rem;
    color: #92400e;
}

.cancel-popup .consequence-list {
    list-style: none;
    padding: 0;
}

.cancel-popup .consequence-list li {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    color: #92400e;
}

.cancel-popup .consequence-list li i {
    margin-right: 0.5rem;
    color: #dc2626;
}

.cancel-popup .feedback-section {
    margin-bottom: 2rem;
}

.cancel-popup .feedback-section h4 {
    margin-bottom: 1rem;
    color: #1f2937;
}

.cancel-popup .feedback-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1rem;
}

.cancel-popup .feedback-option {
    display: flex;
    align-items: center;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 6px;
    transition: background-color 0.2s;
}

.cancel-popup .feedback-option:hover {
    background: #f9fafb;
}

.cancel-popup .feedback-option input[type="radio"] {
    margin-right: 0.75rem;
}

.cancel-popup .feedback-text textarea {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    padding: 0.75rem;
    font-family: inherit;
    resize: vertical;
}

.cancel-popup .popup-footer {
    border-top: 1px solid #e5e7eb;
    padding-top: 1.5rem;
}

.cancel-popup .footer-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin-bottom: 1rem;
}

.cancel-popup .footer-note {
    text-align: center;
}

.cancel-popup .note-text {
    color: #6b7280;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.cancel-popup .popup-message {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.cancel-popup .popup-message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
}

.cancel-popup .popup-message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #ef4444;
}
</style>