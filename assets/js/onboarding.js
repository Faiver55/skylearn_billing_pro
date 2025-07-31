/**
 * Skylearn Billing Pro - Onboarding Wizard JavaScript
 */

(function($) {
    'use strict';

    var SkyLearnOnboarding = {
        
        init: function() {
            this.bindEvents();
            this.initPaymentGateways();
            this.updateProgressBar();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Next step button
            $(document).on('click', '.skylearn-next-step', function(e) {
                e.preventDefault();
                var nextStep = $(this).data('next');
                if (nextStep) {
                    self.goToStep(nextStep);
                }
            });
            
            // Skip step button
            $(document).on('click', '.skylearn-skip-step', function(e) {
                e.preventDefault();
                var nextStep = $(this).data('next');
                if (nextStep) {
                    self.goToStep(nextStep);
                }
            });
            
            // Skip onboarding
            $(document).on('click', '.skylearn-skip-onboarding', function(e) {
                e.preventDefault();
                if (confirm(skylernOnboarding.strings.skip_confirm)) {
                    self.skipOnboarding();
                }
            });
            
            // Complete onboarding
            $(document).on('click', '.skylearn-complete-onboarding', function(e) {
                e.preventDefault();
                self.processStep('complete', {});
            });
            
            // Form submission
            $(document).on('submit', '.skylearn-step-form', function(e) {
                e.preventDefault();
                var step = $(this).data('step');
                var formData = self.serializeFormData($(this));
                self.processStep(step, formData);
            });
            
            // Payment gateway toggles
            $(document).on('change', '.skylearn-gateway-section input[type="checkbox"]', function() {
                var $section = $(this).closest('.skylearn-gateway-section');
                var $fields = $section.find('.skylearn-gateway-fields');
                
                if ($(this).is(':checked')) {
                    $fields.slideDown(300);
                } else {
                    $fields.slideUp(300);
                }
            });
        },
        
        initPaymentGateways: function() {
            // Initialize payment gateway field visibility
            $('.skylearn-gateway-section input[type="checkbox"]').each(function() {
                var $section = $(this).closest('.skylearn-gateway-section');
                var $fields = $section.find('.skylearn-gateway-fields');
                
                if ($(this).is(':checked')) {
                    $fields.show();
                } else {
                    $fields.hide();
                }
            });
        },
        
        serializeFormData: function($form) {
            var data = {};
            $form.find('input, select, textarea').each(function() {
                var $field = $(this);
                var name = $field.attr('name');
                var value = $field.val();
                
                if (name) {
                    if ($field.attr('type') === 'checkbox') {
                        data[name] = $field.is(':checked') ? 1 : 0;
                    } else {
                        data[name] = value;
                    }
                }
            });
            return data;
        },
        
        processStep: function(step, data) {
            var self = this;
            
            this.showLoading();
            
            $.ajax({
                url: skylernOnboarding.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'skylearn_onboarding_step',
                    step: step,
                    data: data,
                    nonce: skylernOnboarding.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success) {
                        if (response.data.redirect) {
                            window.location.href = response.data.redirect;
                        } else if (response.data.next_step) {
                            self.goToStep(response.data.next_step);
                        }
                        
                        if (response.data.message) {
                            self.showMessage(response.data.message, 'success');
                        }
                    } else {
                        self.showMessage(response.data.message || skylernOnboarding.strings.error, 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showMessage(skylernOnboarding.strings.error, 'error');
                }
            });
        },
        
        goToStep: function(step) {
            var currentUrl = window.location.href;
            var newUrl = this.updateUrlParameter(currentUrl, 'step', step);
            window.location.href = newUrl;
        },
        
        skipOnboarding: function() {
            var self = this;
            
            this.showLoading();
            
            $.ajax({
                url: skylernOnboarding.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'skylearn_skip_onboarding',
                    nonce: skylernOnboarding.nonce
                },
                success: function(response) {
                    self.hideLoading();
                    
                    if (response.success && response.data.redirect) {
                        window.location.href = response.data.redirect;
                    } else {
                        self.showMessage(skylernOnboarding.strings.error, 'error');
                    }
                },
                error: function() {
                    self.hideLoading();
                    self.showMessage(skylernOnboarding.strings.error, 'error');
                }
            });
        },
        
        updateProgressBar: function() {
            var currentStep = skylernOnboarding.currentStep;
            var steps = skylernOnboarding.steps;
            var currentIndex = steps.indexOf(currentStep);
            var progress = (currentIndex / (steps.length - 1)) * 100;
            
            $('.skylearn-progress-fill').css('width', progress + '%');
        },
        
        showLoading: function() {
            $('.skylearn-loading-overlay').fadeIn(200);
        },
        
        hideLoading: function() {
            $('.skylearn-loading-overlay').fadeOut(200);
        },
        
        showMessage: function(message, type) {
            var messageClass = type === 'success' ? 'notice-success' : 'notice-error';
            var $message = $('<div class="notice ' + messageClass + ' is-dismissible"><p>' + message + '</p></div>');
            
            // Remove existing messages
            $('.skylearn-step-content .notice').remove();
            
            // Add new message
            $('.skylearn-step-content').prepend($message);
            
            // Auto-dismiss success messages
            if (type === 'success') {
                setTimeout(function() {
                    $message.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
            }
            
            // Scroll to message
            $('html, body').animate({
                scrollTop: $message.offset().top - 100
            }, 300);
        },
        
        updateUrlParameter: function(url, param, paramVal) {
            var newAdditionalURL = "";
            var tempArray = url.split("?");
            var baseURL = tempArray[0];
            var additionalURL = tempArray[1];
            var temp = "";
            
            if (additionalURL) {
                tempArray = additionalURL.split("&");
                for (var i = 0; i < tempArray.length; i++) {
                    if (tempArray[i].split('=')[0] != param) {
                        newAdditionalURL += temp + tempArray[i];
                        temp = "&";
                    }
                }
            }
            
            var rows_txt = temp + "" + param + "=" + paramVal;
            return baseURL + "?" + newAdditionalURL + rows_txt;
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        if ($('.skylearn-onboarding-wrap').length) {
            SkyLearnOnboarding.init();
        }
    });
    
    // Make it globally accessible
    window.SkyLearnOnboarding = SkyLearnOnboarding;
    
})(jQuery);