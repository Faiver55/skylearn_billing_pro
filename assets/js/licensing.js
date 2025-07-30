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
            
            // Show loading state
            $submitBtn.prop('disabled', true);
            $btnText.hide();
            $btnLoading.show();
            
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
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.message);
                        // Reload page after 2 seconds to show activated state
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showMessage('error', response.message || 'An error occurred while validating the license.');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while validating the license. Please try again.');
                },
                complete: function() {
                    // Hide loading state
                    $submitBtn.prop('disabled', false);
                    $btnText.show();
                    $btnLoading.hide();
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
                success: function(response) {
                    if (response.success) {
                        showMessage('success', response.message);
                        // Reload page after 2 seconds to show deactivated state
                        setTimeout(function() {
                            window.location.reload();
                        }, 2000);
                    } else {
                        showMessage('error', response.message || 'An error occurred while deactivating the license.');
                    }
                },
                error: function() {
                    showMessage('error', 'An error occurred while deactivating the license. Please try again.');
                },
                complete: function() {
                    // Reset button state
                    $btn.prop('disabled', false).text('Deactivate License');
                }
            });
        });
        
        // Demo license key quick insertion
        $('.skylearn-billing-demo-keys code').on('click', function() {
            var licenseKey = $(this).text();
            $('#skylearn-license-key').val(licenseKey).focus();
        });
    });
    
    /**
     * Show message to user
     */
    function showMessage(type, message) {
        var $container = $('#skylearn-license-messages');
        var alertClass = type === 'success' ? 'notice-success' : 'notice-error';
        
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
        
        // Auto-dismiss success messages after 5 seconds
        if (type === 'success') {
            setTimeout(function() {
                $container.find('.notice').fadeOut();
            }, 5000);
        }
    }
    
    /**
     * Clear all messages
     */
    function clearMessages() {
        $('#skylearn-license-messages').empty();
    }

})(jQuery);