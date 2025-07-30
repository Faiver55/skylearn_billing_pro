/**
 * Stripe integration JavaScript
 * 
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

(function($) {
    'use strict';
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        if (typeof Stripe !== 'undefined' && typeof skylernStripe !== 'undefined') {
            initStripeCheckout();
        }
    });
    
    /**
     * Initialize Stripe checkout
     */
    function initStripeCheckout() {
        const stripe = Stripe(skylernStripe.publishableKey);
        const elements = stripe.elements();
        
        // Create card element
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    '::placeholder': {
                        color: '#aab7c4',
                    },
                },
                invalid: {
                    color: '#9e2146',
                },
            },
        });
        
        // Mount card elements
        const cardMountPoints = ['#stripe-card-element', '#stripe-overlay-card-element'];
        cardMountPoints.forEach(function(mountPoint) {
            if ($(mountPoint).length) {
                cardElement.mount(mountPoint);
            }
        });
        
        // Handle real-time validation errors from the card Element
        cardElement.on('change', function(event) {
            const errorElements = ['#stripe-card-errors', '#stripe-overlay-card-errors'];
            errorElements.forEach(function(errorElement) {
                const displayError = $(errorElement);
                if (displayError.length) {
                    if (event.error) {
                        displayError.text(event.error.message);
                    } else {
                        displayError.text('');
                    }
                }
            });
        });
        
        // Handle form submission
        const forms = ['#skylearn-stripe-form', '#skylearn-stripe-overlay-form'];
        forms.forEach(function(formSelector) {
            $(formSelector).on('submit', function(event) {
                event.preventDefault();
                handleStripeSubmit(stripe, cardElement, $(this));
            });
        });
    }
    
    /**
     * Handle Stripe form submission
     */
    function handleStripeSubmit(stripe, cardElement, $form) {
        const $submitButton = $form.find('[type="submit"]');
        const $buttonText = $submitButton.find('.button-text');
        const $buttonSpinner = $submitButton.find('.button-spinner');
        
        // Disable submit button and show loading
        $submitButton.prop('disabled', true);
        $buttonText.hide();
        $buttonSpinner.show();
        
        // Get form data
        const formData = {
            action: 'skylearn_create_stripe_payment_intent',
            nonce: skylernStripe.nonce,
            customer_email: $form.find('[name="customer_email"]').val(),
            customer_name: $form.find('[name="customer_name"]').val(),
            product_id: $form.find('[name="product_id"]').val(),
            amount: $form.find('[name="amount"]').val(),
            currency: $form.find('[name="currency"]').val(),
        };
        
        // Add custom fields
        $form.find('[name^="skylearn_fields"]').each(function() {
            formData[$(this).attr('name')] = $(this).val();
        });
        
        // Create payment intent
        $.post(skylernStripe.ajaxUrl, formData)
            .done(function(response) {
                if (response.success && response.data.client_secret) {
                    // Confirm payment with Stripe
                    stripe.confirmCardPayment(response.data.client_secret, {
                        payment_method: {
                            card: cardElement,
                            billing_details: {
                                name: formData.customer_name,
                                email: formData.customer_email,
                            },
                        }
                    }).then(function(result) {
                        if (result.error) {
                            // Show error to customer
                            showStripeError(result.error.message, $form);
                            resetSubmitButton($submitButton, $buttonText, $buttonSpinner);
                        } else {
                            // Payment succeeded
                            showStripeSuccess($form);
                        }
                    });
                } else {
                    showStripeError(response.data.message || 'Payment failed', $form);
                    resetSubmitButton($submitButton, $buttonText, $buttonSpinner);
                }
            })
            .fail(function() {
                showStripeError('Network error. Please try again.', $form);
                resetSubmitButton($submitButton, $buttonText, $buttonSpinner);
            });
    }
    
    /**
     * Show Stripe error message
     */
    function showStripeError(message, $form) {
        const errorElements = ['#stripe-card-errors', '#stripe-overlay-card-errors'];
        errorElements.forEach(function(errorElement) {
            const $errorDisplay = $form.find(errorElement);
            if ($errorDisplay.length) {
                $errorDisplay.text(message);
            }
        });
    }
    
    /**
     * Show success message
     */
    function showStripeSuccess($form) {
        // Hide form and show success message
        $form.hide();
        const $success = $('#skylearn-checkout-success');
        if ($success.length) {
            $success.fadeIn();
        } else {
            // Create success message if not exists
            const successHtml = '<div class="skylearn-checkout-success">' +
                '<div class="skylearn-success-content">' +
                '<span class="dashicons dashicons-yes-alt"></span>' +
                '<h3>Payment Successful!</h3>' +
                '<p>Thank you for your purchase. You will receive an email confirmation shortly.</p>' +
                '</div>' +
                '</div>';
            $form.after(successHtml);
        }
        
        // Close overlay if it's an overlay checkout
        if ($('#skylearn-checkout-overlay').is(':visible')) {
            setTimeout(function() {
                $('#skylearn-checkout-overlay').fadeOut();
                $('body').removeClass('skylearn-overlay-open');
            }, 3000);
        }
    }
    
    /**
     * Reset submit button state
     */
    function resetSubmitButton($submitButton, $buttonText, $buttonSpinner) {
        $submitButton.prop('disabled', false);
        $buttonSpinner.hide();
        $buttonText.show();
    }
    
})(jQuery);