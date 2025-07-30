# Skylearn Billing Pro - Developer Documentation

This guide provides technical documentation for developers working with Skylearn Billing Pro, including API hooks, addon development, and contributing guidelines.

## Table of Contents

1. [API Hooks & Filters](#api-hooks--filters)
2. [Addon Development](#addon-development)
3. [REST API Endpoints](#rest-api-endpoints)
4. [Database Schema](#database-schema)
5. [Contributing Guidelines](#contributing-guidelines)
6. [Code Standards](#code-standards)
7. [Testing](#testing)

## API Hooks & Filters

### Action Hooks

#### Payment Processing

```php
// Fired before payment processing starts
do_action('skylearn_billing_before_payment_process', $payment_data);

// Fired after successful payment
do_action('skylearn_billing_payment_successful', $payment_id, $customer_id, $product_id);

// Fired after failed payment
do_action('skylearn_billing_payment_failed', $payment_data, $error_message);

// Fired before refund processing
do_action('skylearn_billing_before_refund', $payment_id, $refund_amount);

// Fired after successful refund
do_action('skylearn_billing_refund_processed', $payment_id, $refund_id, $refund_amount);
```

#### Subscription Management

```php
// Fired when subscription is created
do_action('skylearn_billing_subscription_created', $subscription_id, $customer_id);

// Fired when subscription status changes
do_action('skylearn_billing_subscription_status_changed', $subscription_id, $old_status, $new_status);

// Fired before subscription cancellation
do_action('skylearn_billing_before_subscription_cancel', $subscription_id);

// Fired after subscription cancellation
do_action('skylearn_billing_subscription_cancelled', $subscription_id, $cancellation_reason);

// Fired on subscription renewal
do_action('skylearn_billing_subscription_renewed', $subscription_id, $payment_id);
```

#### User Enrollment

```php
// Fired before user enrollment
do_action('skylearn_billing_before_user_enrollment', $user_id, $course_id, $product_id);

// Fired after successful enrollment
do_action('skylearn_billing_user_enrolled', $user_id, $course_id, $product_id, $enrollment_data);

// Fired when enrollment fails
do_action('skylearn_billing_enrollment_failed', $user_id, $course_id, $error_message);

// Fired when user access is revoked
do_action('skylearn_billing_access_revoked', $user_id, $course_id, $reason);
```

#### Email System

```php
// Fired before email is sent
do_action('skylearn_billing_before_email_send', $email_type, $recipient, $email_data);

// Fired after email is sent
do_action('skylearn_billing_email_sent', $email_type, $recipient, $email_data, $result);

// Fired when email fails to send
do_action('skylearn_billing_email_failed', $email_type, $recipient, $error_message);
```

### Filter Hooks

#### Payment Processing

```php
// Filter payment data before processing
$payment_data = apply_filters('skylearn_billing_payment_data', $payment_data, $product_id);

// Filter payment gateway selection
$gateway = apply_filters('skylearn_billing_payment_gateway', $gateway, $payment_data);

// Filter payment success redirect URL
$redirect_url = apply_filters('skylearn_billing_payment_success_redirect', $redirect_url, $payment_id);

// Filter refund amount calculation
$refund_amount = apply_filters('skylearn_billing_refund_amount', $refund_amount, $payment_id);
```

#### Product Management

```php
// Filter product data before saving
$product_data = apply_filters('skylearn_billing_product_data', $product_data, $product_id);

// Filter product price calculation
$price = apply_filters('skylearn_billing_product_price', $price, $product_id, $user_id);

// Filter available products
$products = apply_filters('skylearn_billing_available_products', $products, $user_id);

// Filter product access check
$has_access = apply_filters('skylearn_billing_user_has_product_access', $has_access, $user_id, $product_id);
```

#### Subscription Management

```php
// Filter subscription plans
$plans = apply_filters('skylearn_billing_subscription_plans', $plans);

// Filter subscription trial period
$trial_days = apply_filters('skylearn_billing_subscription_trial_days', $trial_days, $plan_id);

// Filter subscription pricing
$price = apply_filters('skylearn_billing_subscription_price', $price, $plan_id, $user_id);

// Filter subscription renewal date
$renewal_date = apply_filters('skylearn_billing_subscription_renewal_date', $renewal_date, $subscription_id);
```

#### Email System

```php
// Filter email template content
$content = apply_filters('skylearn_billing_email_content', $content, $email_type, $email_data);

// Filter email subject
$subject = apply_filters('skylearn_billing_email_subject', $subject, $email_type, $email_data);

// Filter email recipient
$recipient = apply_filters('skylearn_billing_email_recipient', $recipient, $email_type, $email_data);

// Filter email headers
$headers = apply_filters('skylearn_billing_email_headers', $headers, $email_type);
```

### Example Usage

```php
// Add custom data to payment processing
add_action('skylearn_billing_payment_successful', 'my_custom_payment_handler', 10, 3);
function my_custom_payment_handler($payment_id, $customer_id, $product_id) {
    // Log payment to custom system
    error_log("Payment successful: {$payment_id}");
    
    // Send to analytics
    my_track_conversion($payment_id, $product_id);
    
    // Custom enrollment logic
    if ($product_id === 'special_course') {
        my_special_enrollment_process($customer_id);
    }
}

// Modify product pricing
add_filter('skylearn_billing_product_price', 'my_custom_pricing', 10, 3);
function my_custom_pricing($price, $product_id, $user_id) {
    // Apply member discount
    if (user_has_membership($user_id)) {
        $price = $price * 0.8; // 20% discount
    }
    
    // Regional pricing
    $user_country = get_user_country($user_id);
    if ($user_country === 'IN') {
        $price = convert_to_inr($price);
    }
    
    return $price;
}
```

## Addon Development

### Creating an Addon

Addons extend Skylearn Billing Pro with additional functionality. Here's the basic structure:

```php
<?php
/**
 * Plugin Name: Skylearn Billing Pro - Custom Addon
 * Description: Custom functionality for Skylearn Billing Pro
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if Skylearn Billing Pro is active
if (!function_exists('skylearn_billing_pro')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Skylearn Billing Pro is required for this addon.</p></div>';
    });
    return;
}

class SkyLearn_Custom_Addon {
    
    public function __construct() {
        $this->init_hooks();
    }
    
    private function init_hooks() {
        add_action('skylearn_billing_pro_init', array($this, 'init'));
        add_filter('skylearn_billing_available_addons', array($this, 'register_addon'));
    }
    
    public function init() {
        // Initialize addon functionality
        add_action('skylearn_billing_payment_successful', array($this, 'handle_payment'));
        add_filter('skylearn_billing_product_price', array($this, 'modify_price'), 10, 3);
    }
    
    public function register_addon($addons) {
        $addons['custom_addon'] = array(
            'name' => 'Custom Addon',
            'description' => 'Adds custom functionality',
            'version' => '1.0.0',
            'status' => 'active'
        );
        return $addons;
    }
    
    public function handle_payment($payment_id, $customer_id, $product_id) {
        // Custom payment handling logic
    }
    
    public function modify_price($price, $product_id, $user_id) {
        // Custom pricing logic
        return $price;
    }
}

new SkyLearn_Custom_Addon();
```

### Addon Best Practices

1. **Namespace Your Code**
   - Use unique function and class names
   - Prefix all functions with your addon name
   - Use PHP namespaces when possible

2. **Check Dependencies**
   - Verify Skylearn Billing Pro is active
   - Check for required PHP extensions
   - Validate WordPress version compatibility

3. **Follow WordPress Standards**
   - Use WordPress coding standards
   - Sanitize all input data
   - Escape all output data
   - Use WordPress APIs when available

4. **Handle Errors Gracefully**
   - Implement proper error handling
   - Log errors appropriately
   - Provide user-friendly error messages

### Payment Gateway Addon

Example of creating a custom payment gateway:

```php
class SkyLearn_Custom_Gateway {
    
    public function __construct() {
        add_filter('skylearn_billing_payment_gateways', array($this, 'register_gateway'));
        add_action('skylearn_billing_process_payment_custom_gateway', array($this, 'process_payment'));
    }
    
    public function register_gateway($gateways) {
        $gateways['custom_gateway'] = array(
            'name' => 'Custom Gateway',
            'description' => 'Process payments through Custom Gateway',
            'supports' => array('subscriptions', 'refunds'),
            'settings' => array(
                'api_key' => array(
                    'title' => 'API Key',
                    'type' => 'text',
                    'required' => true
                ),
                'secret_key' => array(
                    'title' => 'Secret Key',
                    'type' => 'password',
                    'required' => true
                )
            )
        );
        return $gateways;
    }
    
    public function process_payment($payment_data) {
        try {
            // Process payment with custom gateway
            $result = $this->make_payment_request($payment_data);
            
            if ($result['success']) {
                return array(
                    'success' => true,
                    'transaction_id' => $result['transaction_id'],
                    'message' => 'Payment processed successfully'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => $result['error_message']
                );
            }
            
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => $e->getMessage()
            );
        }
    }
    
    private function make_payment_request($payment_data) {
        // Implementation of payment processing
        // Return success/failure with appropriate data
    }
}

new SkyLearn_Custom_Gateway();
```

## REST API Endpoints

### Authentication

All API endpoints require authentication using WordPress REST API authentication methods:

- **Basic Authentication** (for development)
- **Application Passwords** (recommended)
- **JWT Authentication** (with plugin)
- **OAuth** (with plugin)

### Available Endpoints

#### Products

```
GET    /wp-json/skylearn-billing/v1/products
POST   /wp-json/skylearn-billing/v1/products
GET    /wp-json/skylearn-billing/v1/products/{id}
PUT    /wp-json/skylearn-billing/v1/products/{id}
DELETE /wp-json/skylearn-billing/v1/products/{id}
```

#### Customers

```
GET    /wp-json/skylearn-billing/v1/customers
POST   /wp-json/skylearn-billing/v1/customers
GET    /wp-json/skylearn-billing/v1/customers/{id}
PUT    /wp-json/skylearn-billing/v1/customers/{id}
DELETE /wp-json/skylearn-billing/v1/customers/{id}
```

#### Payments

```
GET    /wp-json/skylearn-billing/v1/payments
POST   /wp-json/skylearn-billing/v1/payments
GET    /wp-json/skylearn-billing/v1/payments/{id}
POST   /wp-json/skylearn-billing/v1/payments/{id}/refund
```

#### Subscriptions

```
GET    /wp-json/skylearn-billing/v1/subscriptions
POST   /wp-json/skylearn-billing/v1/subscriptions
GET    /wp-json/skylearn-billing/v1/subscriptions/{id}
PUT    /wp-json/skylearn-billing/v1/subscriptions/{id}
POST   /wp-json/skylearn-billing/v1/subscriptions/{id}/cancel
POST   /wp-json/skylearn-billing/v1/subscriptions/{id}/pause
POST   /wp-json/skylearn-billing/v1/subscriptions/{id}/resume
```

### Example API Usage

```javascript
// Create a new product
fetch('/wp-json/skylearn-billing/v1/products', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Authorization': 'Bearer YOUR_JWT_TOKEN'
    },
    body: JSON.stringify({
        name: 'Advanced WordPress Course',
        description: 'Master WordPress development',
        price: 199.00,
        type: 'one_time',
        course_mappings: [123, 456]
    })
})
.then(response => response.json())
.then(data => console.log(data));

// Get customer subscriptions
fetch('/wp-json/skylearn-billing/v1/customers/123/subscriptions', {
    headers: {
        'Authorization': 'Bearer YOUR_JWT_TOKEN'
    }
})
.then(response => response.json())
.then(subscriptions => {
    subscriptions.forEach(subscription => {
        console.log(`Subscription: ${subscription.plan_name} - ${subscription.status}`);
    });
});
```

## Database Schema

### Custom Tables

Skylearn Billing Pro uses WordPress custom tables for optimal performance:

#### `wp_skylearn_payments`

```sql
CREATE TABLE wp_skylearn_payments (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    transaction_id varchar(255) NOT NULL,
    customer_id bigint(20) unsigned NOT NULL,
    product_id bigint(20) unsigned NOT NULL,
    amount decimal(10,2) NOT NULL,
    currency varchar(3) NOT NULL DEFAULT 'USD',
    gateway varchar(50) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'pending',
    payment_date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    refund_amount decimal(10,2) DEFAULT 0.00,
    metadata longtext,
    PRIMARY KEY (id),
    KEY customer_id (customer_id),
    KEY product_id (product_id),
    KEY transaction_id (transaction_id),
    KEY status (status)
);
```

#### `wp_skylearn_subscriptions`

```sql
CREATE TABLE wp_skylearn_subscriptions (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    subscription_id varchar(255) NOT NULL,
    customer_id bigint(20) unsigned NOT NULL,
    plan_id varchar(100) NOT NULL,
    status varchar(20) NOT NULL DEFAULT 'active',
    amount decimal(10,2) NOT NULL,
    currency varchar(3) NOT NULL DEFAULT 'USD',
    billing_interval varchar(20) NOT NULL,
    trial_end_date timestamp NULL,
    current_period_start timestamp NOT NULL,
    current_period_end timestamp NOT NULL,
    cancel_at_period_end tinyint(1) DEFAULT 0,
    cancelled_at timestamp NULL,
    created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    metadata longtext,
    PRIMARY KEY (id),
    UNIQUE KEY subscription_id (subscription_id),
    KEY customer_id (customer_id),
    KEY plan_id (plan_id),
    KEY status (status)
);
```

## Contributing Guidelines

### Getting Started

1. **Fork the Repository**
   - Fork the main repository to your GitHub account
   - Clone your fork locally
   - Set up the upstream remote

2. **Development Environment**
   - Install WordPress locally (Docker/Local WP/XAMPP)
   - Install required PHP extensions
   - Set up debugging and logging

3. **Branch Naming**
   - Feature branches: `feature/feature-name`
   - Bug fixes: `bugfix/issue-description`
   - Documentation: `docs/topic-name`

### Development Workflow

1. **Create Feature Branch**
   ```bash
   git checkout -b feature/new-payment-gateway
   ```

2. **Make Changes**
   - Write clean, documented code
   - Follow WordPress coding standards
   - Add appropriate hooks and filters

3. **Test Changes**
   - Run automated tests
   - Test manually across different scenarios
   - Verify backwards compatibility

4. **Commit Changes**
   ```bash
   git add .
   git commit -m "Add support for XYZ payment gateway"
   ```

5. **Push and Create PR**
   ```bash
   git push origin feature/new-payment-gateway
   ```
   - Create pull request on GitHub
   - Provide detailed description
   - Reference related issues

### Code Review Process

1. **Automated Checks**
   - PHP syntax validation
   - WordPress coding standards
   - Security scanning
   - Unit test execution

2. **Manual Review**
   - Code quality assessment
   - Architecture review
   - Security audit
   - Performance impact

3. **Testing**
   - Feature testing
   - Regression testing
   - Cross-browser testing
   - Device compatibility

## Code Standards

### PHP Standards

Follow WordPress PHP Coding Standards:

```php
// Good
class SkyLearn_Billing_Payment_Gateway {
    
    private $api_key;
    
    public function __construct( $api_key ) {
        $this->api_key = sanitize_text_field( $api_key );
    }
    
    public function process_payment( $payment_data ) {
        $amount = floatval( $payment_data['amount'] );
        
        if ( $amount <= 0 ) {
            return new WP_Error( 'invalid_amount', __( 'Invalid payment amount.', 'skylearn-billing-pro' ) );
        }
        
        // Process payment logic here
        return array(
            'success'        => true,
            'transaction_id' => $transaction_id,
        );
    }
}

// Bad
class skylernPaymentGateway {
    private $apiKey;
    
    function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    function processPayment($paymentData) {
        if($paymentData['amount'] <= 0) {
            return false;
        }
        
        return true;
    }
}
```

### JavaScript Standards

Follow WordPress JavaScript Coding Standards:

```javascript
// Good
( function( $ ) {
    'use strict';
    
    var SkyLearnBilling = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            $( document ).on( 'click', '.skylearn-payment-button', this.processPayment.bind( this ) );
        },
        
        processPayment: function( event ) {
            event.preventDefault();
            
            var $button = $( event.currentTarget );
            var paymentData = this.getPaymentData( $button );
            
            this.makePaymentRequest( paymentData );
        }
    };
    
    $( document ).ready( function() {
        SkyLearnBilling.init();
    } );
    
} )( jQuery );

// Bad
jQuery(document).ready(function($) {
    $('.skylearn-payment-button').click(function() {
        var data = $(this).data();
        $.post(ajaxurl, data, function(response) {
            if(response.success) {
                alert('Payment successful!');
            }
        });
    });
});
```

### CSS Standards

```css
/* Good */
.skylearn-billing-form {
    max-width: 600px;
    margin: 0 auto;
    padding: 20px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.skylearn-billing-form__field {
    margin-bottom: 20px;
}

.skylearn-billing-form__label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.skylearn-billing-form__input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.skylearn-billing-form__input:focus {
    border-color: #0073aa;
    outline: none;
    box-shadow: 0 0 0 2px rgba( 0, 115, 170, 0.1 );
}

/* Bad */
.form {
    width: 600px;
    margin: auto;
    padding: 20px;
}

.field {
    margin-bottom: 20px;
}

input {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
}
```

## Testing

### Unit Testing

We use PHPUnit for unit testing:

```php
<?php
class SkyLearn_Billing_Payment_Test extends WP_UnitTestCase {
    
    public function setUp() {
        parent::setUp();
        $this->payment_gateway = new SkyLearn_Billing_Payment_Gateway();
    }
    
    public function test_payment_processing() {
        $payment_data = array(
            'amount'      => 99.99,
            'currency'    => 'USD',
            'customer_id' => 123,
            'product_id'  => 456,
        );
        
        $result = $this->payment_gateway->process_payment( $payment_data );
        
        $this->assertTrue( $result['success'] );
        $this->assertArrayHasKey( 'transaction_id', $result );
    }
    
    public function test_invalid_payment_amount() {
        $payment_data = array(
            'amount' => -10,
        );
        
        $result = $this->payment_gateway->process_payment( $payment_data );
        
        $this->assertInstanceOf( 'WP_Error', $result );
        $this->assertEquals( 'invalid_amount', $result->get_error_code() );
    }
}
```

### Integration Testing

Test complete workflows:

```php
<?php
class SkyLearn_Billing_Integration_Test extends WP_UnitTestCase {
    
    public function test_complete_purchase_flow() {
        // Create test user
        $user_id = $this->factory->user->create();
        
        // Create test product
        $product_id = $this->create_test_product();
        
        // Process payment
        $payment_result = $this->process_test_payment( $user_id, $product_id );
        
        // Verify payment was successful
        $this->assertTrue( $payment_result['success'] );
        
        // Verify user was enrolled in course
        $this->assertTrue( $this->user_has_course_access( $user_id, $product_id ) );
        
        // Verify email was sent
        $this->assertEmailSent( $user_id, 'purchase_confirmation' );
    }
}
```

### Running Tests

```bash
# Install PHPUnit
composer install

# Run all tests
vendor/bin/phpunit

# Run specific test file
vendor/bin/phpunit tests/test-payment-gateway.php

# Run tests with coverage
vendor/bin/phpunit --coverage-html coverage/
```

---

This developer documentation provides the foundation for extending and contributing to Skylearn Billing Pro. For additional technical questions, consult the source code or reach out to our development team.