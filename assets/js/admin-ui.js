/**
 * Skylearn Billing Pro - Admin UI Enhancements JavaScript
 */

(function($) {
    'use strict';

    var SkyLearnAdminUI = {
        
        init: function() {
            this.initTooltips();
            this.initHelpSystem();
            this.bindEvents();
        },
        
        initTooltips: function() {
            // Initialize help tooltips
            this.setupTooltips();
            
            // Setup field help tooltips
            $('.skylearn-field-help').each(function() {
                var $tooltip = $(this);
                var dismissed = $tooltip.data('dismissed');
                
                if (!dismissed) {
                    $tooltip.show();
                }
            });
        },
        
        setupTooltips: function() {
            var self = this;
            
            // Show tooltip on hover
            $(document).on('mouseenter', '.skylearn-help-tooltip, .skylearn-field-help', function() {
                var $tooltip = $(this).find('.skylearn-tooltip-content, .skylearn-field-tooltip');
                clearTimeout(self.hideTooltipTimeout);
                $tooltip.stop(true, true).fadeIn(200);
            });
            
            // Hide tooltip on mouse leave
            $(document).on('mouseleave', '.skylearn-help-tooltip, .skylearn-field-help', function() {
                var $tooltip = $(this).find('.skylearn-tooltip-content, .skylearn-field-tooltip');
                self.hideTooltipTimeout = setTimeout(function() {
                    $tooltip.stop(true, true).fadeOut(200);
                }, 300);
            });
            
            // Keep tooltip visible when hovering over tooltip content
            $(document).on('mouseenter', '.skylearn-tooltip-content, .skylearn-field-tooltip', function() {
                clearTimeout(self.hideTooltipTimeout);
            });
            
            $(document).on('mouseleave', '.skylearn-tooltip-content, .skylearn-field-tooltip', function() {
                var $tooltip = $(this);
                self.hideTooltipTimeout = setTimeout(function() {
                    $tooltip.stop(true, true).fadeOut(200);
                }, 300);
            });
        },
        
        initHelpSystem: function() {
            var self = this;
            
            // Help header icon toggle
            $(document).on('click', '#skylearn-help-toggle', function(e) {
                e.preventDefault();
                self.toggleHelpPanel();
            });
            
            // Initialize help panel
            this.createHelpPanel();
        },
        
        createHelpPanel: function() {
            if ($('#skylearn-help-panel').length) {
                return;
            }
            
            var helpContent = this.getHelpContent();
            
            var $helpPanel = $('<div id="skylearn-help-panel" class="skylearn-help-panel">' +
                '<div class="skylearn-help-panel-header">' +
                    '<h3>' + skylernAdminUI.strings.help + '</h3>' +
                    '<button class="skylearn-help-close" type="button">' +
                        '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                '</div>' +
                '<div class="skylearn-help-panel-content">' +
                    helpContent +
                '</div>' +
            '</div>');
            
            $('body').append($helpPanel);
            
            // Add styles for help panel
            this.addHelpPanelStyles();
        },
        
        getHelpContent: function() {
            var currentPage = this.getCurrentPage();
            var helpData = this.getHelpData();
            
            if (helpData[currentPage]) {
                var help = helpData[currentPage];
                return '<div class="skylearn-help-section">' +
                    '<h4>' + help.title + '</h4>' +
                    '<p>' + help.content + '</p>' +
                    '<div class="skylearn-help-actions">' +
                        '<a href="' + help.links.docs + '" target="_blank" class="button button-secondary">' +
                            '<span class="dashicons dashicons-external"></span> ' +
                            'Documentation' +
                        '</a>' +
                        '<a href="' + help.links.support + '" target="_blank" class="button button-primary">' +
                            '<span class="dashicons dashicons-sos"></span> ' +
                            'Get Support' +
                        '</a>' +
                    '</div>' +
                '</div>';
            }
            
            return '<div class="skylearn-help-section">' +
                '<h4>Need Help?</h4>' +
                '<p>Get assistance with Skylearn Billing Pro.</p>' +
                '<div class="skylearn-help-actions">' +
                    '<a href="https://skyian.com/skylearn-billing/doc/" target="_blank" class="button button-secondary">' +
                        '<span class="dashicons dashicons-external"></span> ' +
                        'Documentation' +
                    '</a>' +
                    '<a href="https://skyian.com/skylearn-billing/support/" target="_blank" class="button button-primary">' +
                        '<span class="dashicons dashicons-sos"></span> ' +
                        'Get Support' +
                    '</a>' +
                '</div>' +
            '</div>';
        },
        
        getCurrentPage: function() {
            var urlParams = new URLSearchParams(window.location.search);
            var page = urlParams.get('page');
            
            if (!page || page === 'skylearn-billing-pro') {
                return 'general';
            }
            
            return page.replace('skylearn-billing-pro-', '');
        },
        
        getHelpData: function() {
            return {
                'general': {
                    title: 'General Settings Help',
                    content: 'Configure your basic billing settings including company information, currency, and test mode.',
                    links: {
                        docs: 'https://skyian.com/skylearn-billing/doc/general-settings/',
                        support: 'https://skyian.com/skylearn-billing/support/'
                    }
                },
                'license': {
                    title: 'License Management Help',
                    content: 'Activate your license to unlock Pro features and receive automatic updates.',
                    links: {
                        docs: 'https://skyian.com/skylearn-billing/doc/license/',
                        support: 'https://skyian.com/skylearn-billing/support/'
                    }
                },
                'lms': {
                    title: 'LMS Integration Help',
                    content: 'Connect your Learning Management System to automatically enroll students after successful payments.',
                    links: {
                        docs: 'https://skyian.com/skylearn-billing/doc/lms-integration/',
                        support: 'https://skyian.com/skylearn-billing/support/'
                    }
                },
                'payments': {
                    title: 'Payment Gateways Help',
                    content: 'Configure Stripe, Lemon Squeezy, and other payment processors to accept payments from your customers.',
                    links: {
                        docs: 'https://skyian.com/skylearn-billing/doc/payment-gateways/',
                        support: 'https://skyian.com/skylearn-billing/support/'
                    }
                },
                'products': {
                    title: 'Product Management Help',
                    content: 'Create and manage your courses, digital products, and subscription plans.',
                    links: {
                        docs: 'https://skyian.com/skylearn-billing/doc/products/',
                        support: 'https://skyian.com/skylearn-billing/support/'
                    }
                }
            };
        },
        
        addHelpPanelStyles: function() {
            if ($('#skylearn-help-panel-styles').length) {
                return;
            }
            
            var styles = `
                <style id="skylearn-help-panel-styles">
                    .skylearn-help-panel {
                        position: fixed;
                        top: 32px;
                        right: -350px;
                        width: 350px;
                        height: calc(100vh - 32px);
                        background: #fff;
                        border-left: 1px solid #ddd;
                        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.1);
                        z-index: 9998;
                        display: flex;
                        flex-direction: column;
                        transition: right 0.3s ease;
                    }
                    
                    .skylearn-help-panel.open {
                        right: 0;
                    }
                    
                    .skylearn-help-panel-header {
                        display: flex;
                        align-items: center;
                        justify-content: space-between;
                        padding: 20px;
                        border-bottom: 1px solid #ddd;
                        background: #f8f9fa;
                    }
                    
                    .skylearn-help-panel-header h3 {
                        margin: 0;
                        font-size: 18px;
                        font-weight: 600;
                        color: #333;
                    }
                    
                    .skylearn-help-close {
                        background: none;
                        border: none;
                        cursor: pointer;
                        padding: 5px;
                        color: #666;
                        font-size: 16px;
                    }
                    
                    .skylearn-help-close:hover {
                        color: #333;
                    }
                    
                    .skylearn-help-panel-content {
                        flex: 1;
                        padding: 20px;
                        overflow-y: auto;
                    }
                    
                    .skylearn-help-section h4 {
                        margin: 0 0 10px;
                        font-size: 16px;
                        font-weight: 600;
                        color: #333;
                    }
                    
                    .skylearn-help-section p {
                        margin: 0 0 20px;
                        font-size: 14px;
                        color: #666;
                        line-height: 1.5;
                    }
                    
                    .skylearn-help-actions {
                        display: flex;
                        flex-direction: column;
                        gap: 10px;
                    }
                    
                    .skylearn-help-actions .button {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        gap: 8px;
                        padding: 10px 16px;
                        text-decoration: none;
                        border-radius: 4px;
                        font-size: 13px;
                        font-weight: 500;
                    }
                    
                    @media (max-width: 768px) {
                        .skylearn-help-panel {
                            width: 100%;
                            right: -100%;
                        }
                        
                        .skylearn-help-panel.open {
                            right: 0;
                        }
                    }
                </style>
            `;
            
            $('head').append(styles);
        },
        
        toggleHelpPanel: function() {
            var $panel = $('#skylearn-help-panel');
            $panel.toggleClass('open');
        },
        
        bindEvents: function() {
            var self = this;
            
            // Close help panel
            $(document).on('click', '.skylearn-help-close', function(e) {
                e.preventDefault();
                $('#skylearn-help-panel').removeClass('open');
            });
            
            // Close help panel on escape key
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape key
                    $('#skylearn-help-panel').removeClass('open');
                }
            });
            
            // Dismiss tooltip functionality
            $(document).on('click', '.skylearn-tooltip-dismiss', function(e) {
                e.preventDefault();
                var tooltipId = $(this).data('tooltip-id');
                self.dismissTooltip(tooltipId);
            });
            
            // Enhanced form field interactions
            this.enhanceFormFields();
            
            // Status indicator interactions
            this.enhanceStatusIndicators();
        },
        
        enhanceFormFields: function() {
            // Add focus effects to form fields
            $('input[type="text"], input[type="email"], input[type="password"], input[type="number"], select, textarea')
                .on('focus', function() {
                    $(this).closest('tr, .form-field').addClass('skylearn-field-focused');
                })
                .on('blur', function() {
                    $(this).closest('tr, .form-field').removeClass('skylearn-field-focused');
                });
            
            // Add validation feedback
            $('input[required], select[required], textarea[required]')
                .on('blur', function() {
                    var $field = $(this);
                    if ($field.val().trim() === '') {
                        $field.addClass('skylearn-field-error');
                    } else {
                        $field.removeClass('skylearn-field-error');
                    }
                });
        },
        
        enhanceStatusIndicators: function() {
            // Add click handlers to status indicators
            $('.skylearn-status-indicator').on('click', function() {
                var $indicator = $(this);
                var status = $indicator.data('status');
                var type = $indicator.data('type');
                
                if (type && status) {
                    // Show more detailed status information
                    this.showStatusDetails(type, status);
                }
            }.bind(this));
        },
        
        showStatusDetails: function(type, status) {
            // This could open a modal or navigate to a detailed status page
            console.log('Status details for:', type, status);
        },
        
        dismissTooltip: function(tooltipId) {
            var self = this;
            
            $.ajax({
                url: skylernAdminUI.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'skylearn_dismiss_help',
                    tooltip_id: tooltipId,
                    nonce: skylernAdminUI.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('[data-tooltip-id="' + tooltipId + '"]').fadeOut(300);
                    }
                },
                error: function() {
                    console.log('Error dismissing tooltip');
                }
            });
        },
        
        // Utility functions
        showNotice: function(message, type) {
            var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap').prepend($notice);
            
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },
        
        addSpinner: function($element) {
            $element.addClass('skylearn-loading');
            $element.append('<span class="skylearn-spinner"></span>');
        },
        
        removeSpinner: function($element) {
            $element.removeClass('skylearn-loading');
            $element.find('.skylearn-spinner').remove();
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('body.toplevel_page_skylearn-billing-pro, body[class*="skylearn-billing-pro"]').length) {
            SkyLearnAdminUI.init();
        }
    });
    
    // Make it globally accessible
    window.SkyLearnAdminUI = SkyLearnAdminUI;
    
})(jQuery);