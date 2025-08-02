/**
 * Skylearn Billing Pro Admin JavaScript
 * 
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';
    
    /**
     * Initialize admin functionality when document is ready
     */
    $(document).ready(function() {
        SkyLearnBillingAdmin.init();
    });
    
    /**
     * Main admin object
     */
    var SkyLearnBillingAdmin = {
        
        /**
         * Initialize all admin functionality
         */
        init: function() {
            this.bindEvents();
            this.initTooltips();
            this.initFormValidation();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Reset to defaults button - only target buttons that are specifically for resetting
            $('.skylearn-billing-btn-secondary:contains("Reset to Defaults")').on('click', this.resetToDefaults);
            
            // Tab navigation (for future use)
            $('.skylearn-billing-nav-link').on('click', this.handleTabClick);
            
            // Form submission with validation
            $('form').on('submit', this.handleFormSubmit);
        },
        
        /**
         * Initialize tooltips for form fields
         */
        initTooltips: function() {
            // Add hover tooltips for form fields with descriptions
            $('.description').each(function() {
                var $this = $(this);
                var $field = $this.prev('input, select, textarea');
                
                if ($field.length) {
                    $field.attr('title', $this.text());
                }
            });
        },
        
        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            // Add required field indicators
            $('input[required], select[required]').each(function() {
                var $label = $('label[for="' + this.id + '"]');
                if ($label.length === 0) {
                    $label = $(this).closest('tr').find('th label');
                }
                if ($label.length && $label.find('.required').length === 0) {
                    $label.append(' <span class="required" style="color: #FF3B00;">*</span>');
                }
            });
        },
        
        /**
         * Handle tab navigation clicks
         */
        handleTabClick: function(e) {
            var $link = $(this);
            var href = $link.attr('href');
            
            // Check if this is a coming soon tab
            if ($link.find('.skylearn-billing-badge').length > 0) {
                e.preventDefault();
                SkyLearnBillingAdmin.showComingSoonNotice($link.text().trim());
                return false;
            }
        },
        
        /**
         * Show coming soon notice
         */
        showComingSoonNotice: function(featureName) {
            var message = 'The "' + featureName + '" feature is coming soon! Stay tuned for updates.';
            SkyLearnBillingAdmin.showNotice(message, 'info');
        },
        
        /**
         * Handle form submission
         */
        handleFormSubmit: function(e) {
            var $form = $(this);
            var isValid = true;
            
            // Basic validation for required fields
            $form.find('input[required], select[required]').each(function() {
                var $field = $(this);
                var value = $field.val().trim();
                
                if (!value) {
                    isValid = false;
                    $field.addClass('error');
                    $field.focus();
                } else {
                    $field.removeClass('error');
                }
            });
            
            // Email validation
            $form.find('input[type="email"]').each(function() {
                var $field = $(this);
                var email = $field.val().trim();
                
                if (email && !SkyLearnBillingAdmin.isValidEmail(email)) {
                    isValid = false;
                    $field.addClass('error');
                    SkyLearnBillingAdmin.showNotice('Please enter a valid email address.', 'error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                SkyLearnBillingAdmin.showNotice('Please fill in all required fields correctly.', 'error');
                return false;
            }
            
            // Show saving indicator
            SkyLearnBillingAdmin.showSavingIndicator();
        },
        
        /**
         * Reset form to default values
         */
        resetToDefaults: function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to reset all settings to their default values? This action cannot be undone.')) {
                // Reset form fields to default values
                var $form = $(this).closest('form');
                
                // Reset text inputs
                $form.find('input[type="text"]').each(function() {
                    var defaultValue = $(this).data('default') || '';
                    $(this).val(defaultValue);
                });
                
                // Reset email inputs
                $form.find('input[type="email"]').each(function() {
                    var defaultValue = $(this).data('default') || '';
                    $(this).val(defaultValue);
                });
                
                // Reset select dropdowns
                $form.find('select').each(function() {
                    var defaultValue = $(this).data('default') || $(this).find('option').first().val();
                    $(this).val(defaultValue);
                });
                
                // Reset checkboxes
                $form.find('input[type="checkbox"]').each(function() {
                    var defaultChecked = $(this).data('default') === true || $(this).data('default') === 'true';
                    $(this).prop('checked', defaultChecked);
                });
                
                SkyLearnBillingAdmin.showNotice('Settings have been reset to default values.', 'success');
            }
        },
        
        /**
         * Show saving indicator
         */
        showSavingIndicator: function() {
            var $submitBtn = $('.skylearn-billing-btn-primary');
            var originalText = $submitBtn.text();
            
            $submitBtn.text('Saving...').prop('disabled', true);
            
            // Re-enable button after a delay (WordPress will handle the actual saving)
            setTimeout(function() {
                $submitBtn.text(originalText).prop('disabled', false);
            }, 2000);
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var noticeClass = 'notice notice-' + type;
            if (type === 'error') {
                noticeClass += ' notice-error';
            }
            
            var $notice = $('<div class="' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Remove existing notices
            $('.skylearn-billing-admin .notice').remove();
            
            // Add new notice
            $('.skylearn-billing-content').prepend($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
            
            // Make notice dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            });
        },
        
        /**
         * Validate email address format
         */
        isValidEmail: function(email) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
    };
    
    // Make admin object globally available
    window.SkyLearnBillingAdmin = SkyLearnBillingAdmin;
    
})(jQuery);