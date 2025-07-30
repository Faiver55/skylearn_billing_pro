/**
 * Paddle integration JavaScript
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
        if (typeof Paddle !== 'undefined' && typeof skylernPaddle !== 'undefined') {
            initPaddleCheckout();
        }
    });
    
    /**
     * Initialize Paddle checkout
     */
    function initPaddleCheckout() {
        // Setup Paddle
        Paddle.Setup({
            vendor: parseInt(skylernPaddle.vendorId),
            environment: skylernPaddle.environment
        });
        
        // Handle Paddle checkout buttons
        $('.skylearn-paddle-checkout-button').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const productId = $button.data('product-id');
            const customerEmail = $button.data('customer-email') || '';
            
            if (!productId) {
                alert('Product ID is required for checkout');
                return;
            }
            
            // Get checkout data from server
            $.post(skylernPaddle.ajaxUrl, {
                action: 'skylearn_create_paddle_checkout',
                nonce: skylernPaddle.nonce,
                product_id: productId,
                customer_email: customerEmail
            })
            .done(function(response) {
                if (response.success && response.data.checkout_data) {
                    // Open Paddle checkout
                    Paddle.Checkout.open(response.data.checkout_data);
                } else {
                    alert(response.data.message || 'Error creating checkout');
                }
            })
            .fail(function() {
                alert('Network error. Please try again.');
            });
        });
        
        // Handle Paddle events
        Paddle.Setup({
            eventCallback: function(data) {
                switch(data.event) {
                    case 'Checkout.Complete':
                        handlePaddleSuccess(data);
                        break;
                    case 'Checkout.Close':
                        handlePaddleClose(data);
                        break;
                }
            }
        });
    }
    
    /**
     * Handle successful Paddle payment
     */
    function handlePaddleSuccess(data) {
        // Show success message
        const successHtml = '<div class="skylearn-checkout-success">' +
            '<div class="skylearn-success-content">' +
            '<span class="dashicons dashicons-yes-alt"></span>' +
            '<h3>Payment Successful!</h3>' +
            '<p>Thank you for your purchase. You will receive an email confirmation shortly.</p>' +
            '</div>' +
            '</div>';
        
        $('.skylearn-paddle-checkout-container').html(successHtml);
        
        // Optional: redirect after success
        const redirectUrl = $('.skylearn-paddle-checkout-button').data('redirect-url');
        if (redirectUrl) {
            setTimeout(function() {
                window.location.href = redirectUrl;
            }, 3000);
        }
    }
    
    /**
     * Handle Paddle checkout close
     */
    function handlePaddleClose(data) {
        console.log('Paddle checkout closed', data);
    }
    
})(jQuery);