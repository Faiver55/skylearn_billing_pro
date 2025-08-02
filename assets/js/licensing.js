/**
 * Licensing JavaScript for Skylearn Billing Pro
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // License activation form
        $('#skylearn-license-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('button[type="submit"]');
            var $btnText = $submitBtn.find('.skylearn-billing-btn-text');
            var $btnLoading = $submitBtn.find('.skylearn-billing-btn-loading');
            var licenseKey = $form.find('#skylearn-license-key').val().trim();
            
            if (!licenseKey) {
                showMessage('error', 'Please enter a license key.');
                return;
            }
            
            // Prevent multiple submissions
            if ($submitBtn.prop('disabled')) {
                return;
            }
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            $form.addClass('skylearn-billing-form-submitting');
            
            // Clear previous messages
            clearMessages();
            
            // Make AJAX request
            $.ajax({
                url: skylernLicensing.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'skylearn_validate_license',
                    license_key: licenseKey,
                    nonce: skylernLicensing.nonce
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    if (response && response.success) {
                        showMessage('success', response.message || 'License activated successfully!');
                        
                        // Clear the form
                        $form.find('#skylearn-license-key').val('');
                        
                        // Reload page after 2 seconds to show activated state
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        var errorMessage = response && response.message ? response.message : 'An error occurred while validating the license. Please try again.';
                        showMessage('error', errorMessage);
                        
                        // Re-focus on input for easy retry
                        $form.find('#skylearn-license-key').focus().select();
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = 'An error occurred while validating the license. Please try again.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please check your connection and try again.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Access denied. Please refresh the page and try again.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    } else if (xhr.status === 0) {
                        errorMessage = 'Connection error. Please check your internet connection.';
                    }
                    
                    showMessage('error', errorMessage);
                    
                    // Re-focus on input for easy retry
                    $form.find('#skylearn-license-key').focus().select();
                    
                    console.error('License validation error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        readyState: xhr.readyState
                    });
                },
                complete: function() {
                    // Reset button state
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
                    
                    // Ensure form is responsive again
                    $form.removeClass('skylearn-billing-form-submitting');
                }
            });
        });
        
        // License deactivation
        $('#skylearn-deactivate-license').on('click', function(e) {
            e.preventDefault();
            
            if (!confirm(skylernLicensing.strings.confirm_deactivate)) {
                return;
            }
            
            var $btn = $(this);
            var originalText = $btn.text();
            
            // Prevent multiple clicks
            if ($btn.prop('disabled')) {
                return;
            }
            
            // Show loading state
            $btn.prop('disabled', true).text(skylernLicensing.strings.deactivating);
            
            // Clear previous messages
            clearMessages();
            
            // Make AJAX request
            $.ajax({
                url: skylernLicensing.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'skylearn_deactivate_license',
                    nonce: skylernLicensing.nonce
                },
                timeout: 30000, // 30 second timeout
                success: function(response) {
                    if (response && response.success) {
                        showMessage('success', response.message || 'License deactivated successfully!');
                        // Reload page after 2 seconds to show deactivated state
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        var errorMessage = response && response.message ? response.message : 'An error occurred while deactivating the license.';
                        showMessage('error', errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = 'An error occurred while deactivating the license. Please try again.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please check your connection and try again.';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Access denied. Please refresh the page and try again.';
                    } else if (xhr.status >= 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    
                    showMessage('error', errorMessage);
                    console.error('License deactivation error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                },
                complete: function() {
                    // Reset button state
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
        
        // Demo license key quick insertion
        $('.skylearn-billing-demo-keys code').on('click', function() {
            var licenseKey = $(this).text();
            var $input = $('#skylearn-license-key');
            var $form = $('#skylearn-license-form');
            
            $input.val(licenseKey).focus();
            
            // Add visual feedback
            $(this).addClass('demo-key-copied');
            setTimeout(function() {
                $('.skylearn-billing-demo-keys code').removeClass('demo-key-copied');
            }, 1000);
            
            // Clear any previous messages
            clearMessages();
            
            // Show helpful message with auto-submit option
            showMessage('info', 'Demo license key copied to input field. Click "Activate License" to test, or press Enter to activate immediately.');
            
            // Auto-submit after 3 seconds if no other action is taken
            var autoSubmitTimer = setTimeout(function() {
                if ($input.val() === licenseKey && !$form.hasClass('skylearn-billing-form-submitting')) {
                    showMessage('info', 'Auto-activating demo license...');
                    $form.submit();
                }
            }, 3000);
            
            // Cancel auto-submit if user makes changes
            $input.off('input.autosubmit').on('input.autosubmit', function() {
                clearTimeout(autoSubmitTimer);
            });
        });
        
        // Add keyboard shortcut for quick demo activation
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + D to quickly insert first demo key
            if ((e.ctrlKey || e.metaKey) && e.key === 'd' && $('#skylearn-license-key').is(':visible')) {
                e.preventDefault();
                var firstDemoKey = $('.skylearn-billing-demo-keys code').first().text();
                if (firstDemoKey) {
                    $('#skylearn-license-key').val(firstDemoKey).focus();
                    showMessage('info', 'Demo license key inserted. Press Enter to activate.');
                }
            }
        });
        
        // Handle Enter key in license input
        $('#skylearn-license-key').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#skylearn-license-form').submit();
            }
        });
    });
    
    /**
     * Show message to user
     */
    function showMessage(type, message) {
        var $container = $('#skylearn-license-messages');
        var alertClass = 'notice-info'; // default
        
        if (type === 'success') {
            alertClass = 'notice-success';
        } else if (type === 'error') {
            alertClass = 'notice-error';
        } else if (type === 'warning') {
            alertClass = 'notice-warning';
        }
        
        var html = '<div class="notice ' + alertClass + ' is-dismissible">' +
                   '<p>' + message + '</p>' +
                   '<button type="button" class="notice-dismiss">' +
                   '<span class="screen-reader-text">Dismiss this notice.</span>' +
                   '</button>' +
                   '</div>';
        
        $container.html(html);
        
        // Handle dismiss button
        $container.find('.notice-dismiss').on('click', function() {
            $(this).parent().fadeOut();
        });
        
        // Auto-dismiss success and info messages after 5 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $container.find('.notice').fadeOut();
            }, 5000);
        }
        
        // Scroll to message if it's not visible
        $container[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    /**
     * Clear all messages
     */
    function clearMessages() {
        $('#skylearn-license-messages').empty();
    }

})(jQuery);