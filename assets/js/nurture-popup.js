/**
 * Skylearn Billing Pro - Nurture Popup JavaScript
 *
 * Handles the interactive functionality for nurture popups
 */

(function($) {
    'use strict';

    /**
     * Nurture Popup Manager
     */
    window.skylernNurturePopupManager = {
        
        // Configuration
        config: {
            overlaySelector: '#skylearn-nurture-popup-overlay',
            containerSelector: '.skylearn-popup-container',
            contentSelector: '.skylearn-popup-content',
            closeSelector: '.skylearn-popup-close',
            animationDuration: 300
        },
        
        // Current popup data
        currentPopup: null,
        isOpen: false,
        
        /**
         * Initialize the popup manager
         */
        init: function() {
            this.bindEvents();
            this.setupEscapeKey();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Close button
            $(document).on('click', this.config.closeSelector, function(e) {
                e.preventDefault();
                self.close();
            });
            
            // Overlay click to close
            $(document).on('click', this.config.overlaySelector, function(e) {
                if (e.target === this) {
                    self.close();
                }
            });
            
            // Prevent container clicks from closing
            $(document).on('click', this.config.containerSelector, function(e) {
                e.stopPropagation();
            });
        },
        
        /**
         * Setup escape key to close popup
         */
        setupEscapeKey: function() {
            var self = this;
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27 && self.isOpen) { // Escape key
                    self.close();
                }
            });
        },
        
        /**
         * Show popup with specified type
         */
        show: function(popupType, options) {
            var self = this;
            options = options || {};
            
            // Prevent body scroll
            $('body').addClass('skylearn-popup-open');
            
            // Show loading state
            this.showLoading();
            
            // Fetch popup content
            $.ajax({
                url: skyleanNurturePopup.ajax_url,
                type: 'POST',
                data: {
                    action: 'skylearn_get_nurture_popup',
                    popup_type: popupType,
                    nonce: skyleanNurturePopup.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.currentPopup = {
                            type: popupType,
                            data: response.data
                        };
                        self.renderPopup(response.data);
                        self.open();
                    } else {
                        self.showError(response.data || skyleanNurturePopup.strings.error);
                    }
                },
                error: function() {
                    self.showError(skyleanNurturePopup.strings.error);
                }
            });
        },
        
        /**
         * Render popup content
         */
        renderPopup: function(popupData) {
            var template = this.getTemplate(this.currentPopup.type);
            var html = this.processTemplate(template, popupData);
            $(this.config.contentSelector).html(html);
        },
        
        /**
         * Get template based on popup type
         */
        getTemplate: function(popupType) {
            // Templates would be loaded from the server or defined inline
            // For now, we'll handle this in the PHP templates
            return '';
        },
        
        /**
         * Process template with data
         */
        processTemplate: function(template, data) {
            // Simple template processing - replace placeholders
            var html = template;
            for (var key in data) {
                if (data.hasOwnProperty(key)) {
                    var regex = new RegExp('{{' + key + '}}', 'g');
                    html = html.replace(regex, data[key]);
                }
            }
            return html;
        },
        
        /**
         * Open the popup
         */
        open: function() {
            var $overlay = $(this.config.overlaySelector);
            $overlay.show();
            
            // Trigger reflow for animation
            $overlay[0].offsetHeight;
            
            $overlay.addClass('show');
            this.isOpen = true;
            
            // Focus management
            this.manageFocus();
            
            // Track popup display
            this.trackEvent('popup_displayed', {
                popup_type: this.currentPopup ? this.currentPopup.type : 'unknown'
            });
        },
        
        /**
         * Close the popup
         */
        close: function() {
            var self = this;
            var $overlay = $(this.config.overlaySelector);
            
            $overlay.removeClass('show');
            
            setTimeout(function() {
                $overlay.hide();
                self.isOpen = false;
                $('body').removeClass('skylearn-popup-open');
                $(self.config.contentSelector).empty();
                self.currentPopup = null;
            }, this.config.animationDuration);
            
            // Track popup close
            this.trackEvent('popup_closed');
        },
        
        /**
         * Show loading state
         */
        showLoading: function() {
            var loadingHtml = '<div class="popup-loading">' +
                '<div class="loading-spinner"></div>' +
                '<p>' + skyleanNurturePopup.strings.loading + '</p>' +
                '</div>';
            
            $(this.config.contentSelector).html(loadingHtml);
            $(this.config.overlaySelector).show().addClass('show');
            this.isOpen = true;
        },
        
        /**
         * Show error message
         */
        showError: function(message) {
            var errorHtml = '<div class="popup-error">' +
                '<div class="error-icon">⚠️</div>' +
                '<h3>Error</h3>' +
                '<p>' + message + '</p>' +
                '<button type="button" class="btn btn-primary" onclick="skylernNurturePopupManager.close()">Close</button>' +
                '</div>';
            
            $(this.config.contentSelector).html(errorHtml);
        },
        
        /**
         * Manage focus for accessibility
         */
        manageFocus: function() {
            var $container = $(this.config.containerSelector);
            var $focusableElements = $container.find('button, input, select, textarea, a[href]');
            
            if ($focusableElements.length > 0) {
                $focusableElements.first().focus();
            }
        },
        
        /**
         * Track events for analytics
         */
        trackEvent: function(eventType, data) {
            data = data || {};
            data.timestamp = new Date().toISOString();
            
            // Send to analytics (implement as needed)
            console.log('Popup Event:', eventType, data);
        }
    };
    
    /**
     * Global functions for popup interactions
     */
    
    // Show nurture popup
    window.showNurturePopup = function(popupType) {
        skylernNurturePopupManager.show(popupType);
    };
    
    // Accept offer
    window.acceptOffer = function(offerType) {
        if (!skylernNurturePopupManager.currentPopup) return;
        
        var $button = $('[data-offer-type="' + offerType + '"]');
        if ($button.length === 0) return;
        
        var originalText = $button.text();
        $button.prop('disabled', true).text(skyleanNurturePopup.strings.loading);
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: skylernNurturePopupManager.currentPopup.type,
                action: 'accept_offer',
                offer_type: offerType,
                nonce: skyleanNurturePopup.nonce
            },
            success: function(response) {
                if (response.success) {
                    showPopupMessage(response.data, 'success');
                    setTimeout(function() {
                        if (response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            window.location.reload();
                        }
                    }, 2000);
                } else {
                    showPopupMessage(response.data, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showPopupMessage(skyleanNurturePopup.strings.error, 'error');
                $button.prop('disabled', false).text(originalText);
            }
        });
        
        skylernNurturePopupManager.trackEvent('offer_accepted', {
            popup_type: skylernNurturePopupManager.currentPopup.type,
            offer_type: offerType
        });
    };
    
    // Proceed anyway (dismiss with action)
    window.proceedAnyway = function() {
        if (!skylernNurturePopupManager.currentPopup) return;
        
        var $button = $('.btn-proceed');
        var originalText = $button.text();
        $button.prop('disabled', true).text(skyleanNurturePopup.strings.loading);
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: skylernNurturePopupManager.currentPopup.type,
                action: 'proceed_anyway',
                nonce: skyleanNurturePopup.nonce
            },
            success: function(response) {
                if (response.success) {
                    showPopupMessage(response.data, 'success');
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    showPopupMessage(response.data, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showPopupMessage(skyleanNurturePopup.strings.error, 'error');
                $button.prop('disabled', false).text(originalText);
            }
        });
        
        skylernNurturePopupManager.trackEvent('proceeded_anyway', {
            popup_type: skylernNurturePopupManager.currentPopup.type
        });
    };
    
    // Dismiss popup
    window.dismissPopup = function() {
        if (!skylernNurturePopupManager.currentPopup) return;
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_track_popup_action',
                popup_type: skylernNurturePopupManager.currentPopup.type,
                action: 'dismiss',
                nonce: skyleanNurturePopup.nonce
            }
        });
        
        skylernNurturePopupManager.trackEvent('popup_dismissed', {
            popup_type: skylernNurturePopupManager.currentPopup.type
        });
        
        skylernNurturePopupManager.close();
    };
    
    // Show message in popup
    window.showPopupMessage = function(message, type) {
        type = type || 'info';
        var iconClass = type === 'success' ? 'icon-check' : 'icon-x';
        
        var messageHtml = '<div class="popup-message ' + type + '">' +
            '<i class="' + iconClass + '"></i> ' + message +
            '</div>';
        
        var $popup = $('.skylearn-popup-content');
        var $existingMessage = $popup.find('.popup-message');
        
        if ($existingMessage.length > 0) {
            $existingMessage.remove();
        }
        
        $popup.prepend(messageHtml);
        
        // Auto-remove success messages
        if (type === 'success') {
            setTimeout(function() {
                $popup.find('.popup-message').fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    /**
     * Portal specific functions
     */
    
    // Resume subscription
    window.resumeSubscription = function(subscriptionId) {
        if (!confirm(skyleanNurturePopup.strings.confirm_resume || 'Are you sure you want to resume your subscription?')) {
            return;
        }
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_subscription_action',
                subscription_action: 'resume',
                subscription_id: subscriptionId,
                nonce: wp.ajax.settings.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert(skyleanNurturePopup.strings.error);
            }
        });
    };
    
    // Redeem reward
    window.redeemReward = function(rewardId) {
        if (!confirm(skyleanNurturePopup.strings.confirm_redeem || 'Are you sure you want to redeem this reward?')) {
            return;
        }
        
        var $button = $('[onclick="redeemReward(\'' + rewardId + '\')"]');
        var originalText = $button.text();
        $button.prop('disabled', true).text('Redeeming...');
        
        $.ajax({
            url: skyleanNurturePopup.ajax_url,
            type: 'POST',
            data: {
                action: 'skylearn_redeem_reward',
                reward_id: rewardId,
                nonce: wp.ajax.settings.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    window.location.reload();
                } else {
                    alert(response.data);
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                alert(skyleanNurturePopup.strings.error);
                $button.prop('disabled', false).text(originalText);
            }
        });
    };
    
    // Select plan
    window.selectPlan = function(planId) {
        // This would redirect to checkout or open upgrade modal
        var checkoutUrl = '/checkout/?plan=' + planId;
        window.location.href = checkoutUrl;
    };
    
    /**
     * CSS Styles for loading and error states
     */
    var styles = `
        <style>
        .popup-loading {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .loading-spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .popup-error {
            text-align: center;
            padding: 3rem 2rem;
        }
        
        .error-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .popup-error h3 {
            color: #991b1b;
            margin-bottom: 1rem;
        }
        
        .popup-error p {
            color: #6b7280;
            margin-bottom: 2rem;
        }
        
        body.skylearn-popup-open {
            overflow: hidden;
        }
        </style>
    `;
    
    // Inject styles
    $('head').append(styles);
    
    /**
     * Initialize when document is ready
     */
    $(document).ready(function() {
        skylernNurturePopupManager.init();
        
        // Auto-trigger popups based on URL parameters or conditions
        var urlParams = new URLSearchParams(window.location.search);
        var autoPopup = urlParams.get('show_popup');
        
        if (autoPopup && ['cancel', 'upgrade', 'downgrade', 'renew', 'pause'].indexOf(autoPopup) !== -1) {
            setTimeout(function() {
                showNurturePopup(autoPopup);
            }, 1000);
        }
    });
    
})(jQuery);