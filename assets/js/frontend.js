/**
 * SkyLearn Billing Pro - Frontend JavaScript
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';
    
    // Global skylearn object
    window.skylearn = window.skylearn || {};
    
    // Initialize when document is ready
    $(document).ready(function() {
        skylearn.init();
    });
    
    /**
     * Initialize SkyLearn frontend functionality
     */
    skylearn.init = function() {
        this.initAccessibility();
        this.initFormValidation();
        this.initAjaxForms();
        this.initPortalFeatures();
        this.initNotifications();
        this.initLoadingStates();
    };
    
    /**
     * Initialize accessibility features
     */
    skylearn.initAccessibility = function() {
        // Skip to content link
        this.addSkipToContent();
        
        // Enhanced keyboard navigation
        this.initKeyboardNavigation();
        
        // Announce dynamic content changes
        this.initAriaLive();
        
        // Focus management for modals
        this.initFocusManagement();
    };
    
    /**
     * Add skip to content link
     */
    skylearn.addSkipToContent = function() {
        if ($('.skylearn-billing-page').length && !$('#skylearn-skip-to-content').length) {
            $('body').prepend('<a href="#main" id="skylearn-skip-to-content" class="skylearn-sr-only">' + 
                             skylearn_frontend.strings.skip_to_content + '</a>');
            
            $('#skylearn-skip-to-content').on('focus', function() {
                $(this).removeClass('skylearn-sr-only');
            }).on('blur', function() {
                $(this).addClass('skylearn-sr-only');
            });
        }
    };
    
    /**
     * Initialize keyboard navigation
     */
    skylearn.initKeyboardNavigation = function() {
        // Arrow key navigation for button groups
        $('.skylearn-button-group').on('keydown', '.skylearn-button', function(e) {
            var $buttons = $(this).parent().find('.skylearn-button');
            var currentIndex = $buttons.index(this);
            var $target;
            
            switch(e.key) {
                case 'ArrowRight':
                case 'ArrowDown':
                    e.preventDefault();
                    $target = $buttons.eq((currentIndex + 1) % $buttons.length);
                    break;
                case 'ArrowLeft':
                case 'ArrowUp':
                    e.preventDefault();
                    $target = $buttons.eq((currentIndex - 1 + $buttons.length) % $buttons.length);
                    break;
                case 'Home':
                    e.preventDefault();
                    $target = $buttons.first();
                    break;
                case 'End':
                    e.preventDefault();
                    $target = $buttons.last();
                    break;
            }
            
            if ($target) {
                $target.focus();
            }
        });
        
        // Table keyboard navigation
        $('.skylearn-table').on('keydown', 'tr', function(e) {
            var $rows = $(this).closest('table').find('tr');
            var currentIndex = $rows.index(this);
            var $target;
            
            switch(e.key) {
                case 'ArrowDown':
                    e.preventDefault();
                    $target = $rows.eq(currentIndex + 1);
                    break;
                case 'ArrowUp':
                    e.preventDefault();
                    $target = $rows.eq(currentIndex - 1);
                    break;
            }
            
            if ($target && $target.length) {
                $target.focus();
            }
        });
    };
    
    /**
     * Initialize ARIA live regions
     */
    skylearn.initAriaLive = function() {
        if (!$('#skylearn-aria-live').length) {
            $('body').append('<div id="skylearn-aria-live" aria-live="polite" aria-atomic="true" class="skylearn-sr-only"></div>');
        }
    };
    
    /**
     * Announce message to screen readers
     */
    skylearn.announce = function(message) {
        $('#skylearn-aria-live').text(message);
    };
    
    /**
     * Initialize focus management
     */
    skylearn.initFocusManagement = function() {
        var focusStack = [];
        
        // Store focus when opening modals
        $(document).on('skylearn:modal:open', function(e, modalId) {
            focusStack.push(document.activeElement);
            
            // Focus first focusable element in modal
            setTimeout(function() {
                var $modal = $('#' + modalId);
                var $focusable = $modal.find('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])').first();
                if ($focusable.length) {
                    $focusable.focus();
                }
            }, 100);
        });
        
        // Restore focus when closing modals
        $(document).on('skylearn:modal:close', function() {
            var previousFocus = focusStack.pop();
            if (previousFocus) {
                $(previousFocus).focus();
            }
        });
    };
    
    /**
     * Initialize form validation
     */
    skylearn.initFormValidation = function() {
        $('.skylearn-form').each(function() {
            var $form = $(this);
            
            // Real-time validation
            $form.on('blur', '.skylearn-input[required], .skylearn-select[required]', function() {
                skylearn.validateField($(this));
            });
            
            $form.on('input', '.skylearn-input, .skylearn-select', function() {
                if ($(this).hasClass('skylearn-field-error')) {
                    skylearn.validateField($(this));
                }
            });
            
            // Form submission validation
            $form.on('submit', function(e) {
                if (!skylearn.validateForm($form)) {
                    e.preventDefault();
                }
            });
        });
    };
    
    /**
     * Validate individual field
     */
    skylearn.validateField = function($field) {
        var isValid = true;
        var value = $field.val();
        var type = $field.attr('type');
        
        // Required field validation
        if ($field.attr('required') && !value.trim()) {
            isValid = false;
        }
        
        // Email validation
        if (type === 'email' && value) {
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                isValid = false;
            }
        }
        
        // URL validation
        if (type === 'url' && value) {
            try {
                new URL(value);
            } catch (e) {
                isValid = false;
            }
        }
        
        // Update field state
        if (isValid) {
            $field.removeClass('skylearn-field-error');
            $field.next('.skylearn-field-error-message').remove();
        } else {
            $field.addClass('skylearn-field-error');
            
            // Add error message if not exists
            if (!$field.next('.skylearn-field-error-message').length) {
                var errorMessage = this.getFieldErrorMessage($field);
                $field.after('<div class="skylearn-field-error-message">' + errorMessage + '</div>');
            }
        }
        
        return isValid;
    };
    
    /**
     * Get error message for field
     */
    skylearn.getFieldErrorMessage = function($field) {
        var type = $field.attr('type');
        var fieldName = $field.attr('name') || 'field';
        
        if ($field.attr('required') && !$field.val().trim()) {
            return skylearn_frontend.strings.field_required.replace('%s', fieldName);
        }
        
        if (type === 'email') {
            return skylearn_frontend.strings.invalid_email;
        }
        
        if (type === 'url') {
            return skylearn_frontend.strings.invalid_url;
        }
        
        return skylearn_frontend.strings.field_invalid.replace('%s', fieldName);
    };
    
    /**
     * Validate entire form
     */
    skylearn.validateForm = function($form) {
        var isValid = true;
        var $requiredFields = $form.find('[required]');
        
        $requiredFields.each(function() {
            if (!skylearn.validateField($(this))) {
                isValid = false;
            }
        });
        
        if (!isValid) {
            // Focus first invalid field
            var $firstError = $form.find('.skylearn-field-error').first();
            if ($firstError.length) {
                $firstError.focus();
                skylearn.announce(skylearn_frontend.strings.form_validation_failed);
            }
        }
        
        return isValid;
    };
    
    /**
     * Initialize AJAX forms
     */
    skylearn.initAjaxForms = function() {
        $(document).on('submit', '.skylearn-ajax-form', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitButton = $form.find('[type="submit"]');
            
            // Validate form first
            if (!skylearn.validateForm($form)) {
                return;
            }
            
            // Show loading state
            skylearn.setLoadingState($submitButton, true);
            
            // Prepare form data
            var formData = new FormData(this);
            formData.append('action', $form.data('action') || 'skylearn_ajax_form');
            formData.append('nonce', skylearn_frontend.nonce);
            
            // Submit form
            $.ajax({
                url: skylearn_frontend.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        skylearn.handleFormSuccess($form, response.data);
                    } else {
                        skylearn.handleFormError($form, response.data.message || skylearn_frontend.strings.error);
                    }
                },
                error: function() {
                    skylearn.handleFormError($form, skylearn_frontend.strings.error);
                },
                complete: function() {
                    skylearn.setLoadingState($submitButton, false);
                }
            });
        });
    };
    
    /**
     * Handle form success
     */
    skylearn.handleFormSuccess = function($form, data) {
        // Show success message
        this.showNotification(data.message || skylearn_frontend.strings.success, 'success');
        
        // Redirect if specified
        if (data.redirect_url) {
            setTimeout(function() {
                window.location.href = data.redirect_url;
            }, 1500);
        }
        
        // Reset form if specified
        if (data.reset_form) {
            $form[0].reset();
            $form.find('.skylearn-field-error').removeClass('skylearn-field-error');
            $form.find('.skylearn-field-error-message').remove();
        }
        
        // Trigger custom event
        $(document).trigger('skylearn:form:success', [$form, data]);
    };
    
    /**
     * Handle form error
     */
    skylearn.handleFormError = function($form, message) {
        this.showNotification(message, 'error');
        $(document).trigger('skylearn:form:error', [$form, message]);
    };
    
    /**
     * Set loading state for button
     */
    skylearn.setLoadingState = function($button, loading) {
        if (loading) {
            $button.prop('disabled', true);
            $button.data('original-text', $button.text());
            $button.html('<span class="skylearn-spinner"></span>' + skylearn_frontend.strings.loading);
        } else {
            $button.prop('disabled', false);
            $button.text($button.data('original-text') || $button.text());
        }
    };
    
    /**
     * Initialize portal features
     */
    skylearn.initPortalFeatures = function() {
        // Copy to clipboard functionality
        $('.skylearn-copy-to-clipboard').on('click', function() {
            var text = $(this).data('copy-text') || $(this).text();
            skylearn.copyToClipboard(text);
        });
        
        // Confirmation dialogs
        $('.skylearn-confirm').on('click', function(e) {
            var message = $(this).data('confirm') || skylearn_frontend.strings.confirm_action;
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
        
        // Toggle visibility
        $('.skylearn-toggle').on('click', function() {
            var target = $(this).data('target');
            $(target).toggle();
            $(this).attr('aria-expanded', $(target).is(':visible'));
        });
    };
    
    /**
     * Copy text to clipboard
     */
    skylearn.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                skylearn.showNotification(skylearn_frontend.strings.copied_to_clipboard, 'success');
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                skylearn.showNotification(skylearn_frontend.strings.copied_to_clipboard, 'success');
            } catch (err) {
                skylearn.showNotification(skylearn_frontend.strings.copy_failed, 'error');
            }
            document.body.removeChild(textArea);
        }
    };
    
    /**
     * Initialize notifications
     */
    skylearn.initNotifications = function() {
        if (!$('#skylearn-notifications').length) {
            $('body').append('<div id="skylearn-notifications" aria-live="polite"></div>');
        }
    };
    
    /**
     * Show notification
     */
    skylearn.showNotification = function(message, type, duration) {
        type = type || 'info';
        duration = duration || 5000;
        
        var $notification = $('<div class="skylearn-notification skylearn-notification-' + type + '">' +
                             '<span class="skylearn-notification-message">' + message + '</span>' +
                             '<button type="button" class="skylearn-notification-close" aria-label="' + skylearn_frontend.strings.close + '">&times;</button>' +
                             '</div>');
        
        $('#skylearn-notifications').append($notification);
        
        // Auto dismiss
        setTimeout(function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        }, duration);
        
        // Manual dismiss
        $notification.find('.skylearn-notification-close').on('click', function() {
            $notification.fadeOut(function() {
                $(this).remove();
            });
        });
        
        // Announce to screen readers
        this.announce(message);
    };
    
    /**
     * Initialize loading states
     */
    skylearn.initLoadingStates = function() {
        // Show loading overlay for long operations
        $(document).on('skylearn:loading:start', function() {
            if (!$('#skylearn-loading-overlay').length) {
                $('body').append('<div id="skylearn-loading-overlay" class="skylearn-loading-overlay">' +
                               '<div class="skylearn-loading-content">' +
                               '<div class="skylearn-spinner"></div>' +
                               '<span>' + skylearn_frontend.strings.loading + '</span>' +
                               '</div></div>');
            }
            $('#skylearn-loading-overlay').fadeIn();
        });
        
        $(document).on('skylearn:loading:stop', function() {
            $('#skylearn-loading-overlay').fadeOut();
        });
    };
    
    /**
     * Utility functions
     */
    skylearn.utils = {
        /**
         * Debounce function
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },
        
        /**
         * Throttle function
         */
        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },
        
        /**
         * Generate random ID
         */
        generateId: function(prefix) {
            prefix = prefix || 'skylearn';
            return prefix + '-' + Math.random().toString(36).substr(2, 9);
        },
        
        /**
         * Format currency
         */
        formatCurrency: function(amount, currency) {
            currency = currency || 'USD';
            return new Intl.NumberFormat('en-US', {
                style: 'currency',
                currency: currency
            }).format(amount);
        },
        
        /**
         * Format date
         */
        formatDate: function(date) {
            return new Intl.DateTimeFormat('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            }).format(new Date(date));
        }
    };
    
})(jQuery);