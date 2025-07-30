# Skylearn Billing Pro - Phase 8 Email System Documentation

## Overview

Phase 8 implements a comprehensive email management and notification system for Skylearn Billing Pro. This system provides:

- Advanced email builder with drag-and-drop functionality
- Multiple email template types for different events
- SMTP integration and third-party email provider support
- Email logging and analytics
- Multi-language support
- Dynamic token replacement
- Automated email triggers

## Features Implemented

### 1. Email Builder (`includes/emails/class-email-builder.php`)

A sophisticated drag-and-drop email builder that allows admins to create professional email templates without coding knowledge.

**Key Features:**
- Visual drag-and-drop interface
- Multiple content blocks (header, text, button, image, divider, spacer, footer)
- Responsive design preview (desktop, tablet, mobile)
- Real-time preview functionality
- Block property editing panel
- Dynamic token insertion

**Available Blocks:**
- **Header**: Customizable headings with font size, color, and alignment options
- **Text**: Rich text content with formatting options
- **Button**: Call-to-action buttons with customizable styling and URLs
- **Image**: Image blocks with alignment and sizing options
- **Divider**: Horizontal dividers with customizable styling
- **Spacer**: Adjustable spacing elements
- **Footer**: Footer content for legal/unsubscribe information

### 2. Email Management System (`includes/admin/class-email.php`)

The core email management system that handles template storage, email sending, and analytics.

**Key Features:**
- Template management for 10 different email types
- SMTP configuration support
- Email logging and analytics
- Multi-language template support
- Dynamic token replacement
- Email triggers for various events

**Supported Email Types:**
1. **Welcome Email** - Sent when new users are created
2. **Order Confirmation** - Sent after successful order placement
3. **Invoice Email** - Invoice notifications
4. **Payment Confirmation** - Payment success notifications
5. **Payment Failed** - Payment failure notifications
6. **Refund Notification** - Refund processing notifications
7. **Course Enrollment** - Course enrollment confirmations
8. **Subscription Created** - New subscription notifications
9. **Subscription Cancelled** - Subscription cancellation notifications
10. **Subscription Renewed** - Subscription renewal notifications

### 3. Enhanced Admin Interface (`templates/admin/enhanced-email-settings.php`)

A modern, intuitive admin interface that follows StoreEngine's design patterns.

**Key Features:**
- Email analytics dashboard
- Template management grid
- SMTP settings configuration
- Email activity logs
- Template import/export functionality
- Test email functionality

### 4. Dynamic Token System

Comprehensive token replacement system for personalizing emails:

**Common Tokens:**
- `{{site_name}}` - Website name
- `{{site_url}}` - Website URL
- `{{current_date}}` - Current date
- `{{current_time}}` - Current time
- `{{username}}` - User login name
- `{{user_email}}` - User email address
- `{{display_name}}` - User display name

**Context-Specific Tokens:**
- Order-related: `{{order_id}}`, `{{order_total}}`, `{{product_name}}`
- Payment-related: `{{amount}}`, `{{payment_method}}`, `{{transaction_id}}`
- Course-related: `{{course_title}}`, `{{course_url}}`, `{{instructor_name}}`
- Subscription-related: `{{subscription_id}}`, `{{plan_name}}`, `{{billing_cycle}}`

### 5. Email Analytics and Logging

Comprehensive tracking and analytics system:

**Tracked Metrics:**
- Total emails sent
- Delivery success rate
- Failed deliveries
- Emails by type
- Daily/weekly/monthly statistics

**Logged Information:**
- Timestamp
- Email type
- Recipient
- Subject line
- Delivery status
- User agent and IP address

### 6. SMTP Integration

Support for reliable email delivery through SMTP providers:

**Configurable Options:**
- SMTP host and port
- Encryption (SSL/TLS)
- Authentication credentials
- Custom from name and email
- Test email functionality

## Usage Instructions

### For Administrators

1. **Access Email Settings:**
   - Navigate to Skylearn Billing Pro admin panel
   - Click on the "Email" tab

2. **Configure Email Templates:**
   - View the email templates grid
   - Click "Edit Template" to open the email builder
   - Use drag-and-drop to add content blocks
   - Customize block properties in the right panel
   - Insert dynamic tokens from the sidebar
   - Preview your email before saving

3. **Configure SMTP:**
   - Scroll to SMTP settings section
   - Enter your email provider's SMTP details
   - Test the configuration with a test email

4. **Monitor Email Activity:**
   - View analytics dashboard for delivery statistics
   - Check email logs for detailed activity
   - Export logs for external analysis

### For Developers

#### Triggering Emails Programmatically

```php
// Get email manager instance
$email_manager = skylearn_billing_pro_email();

// Send welcome email
$email_manager->trigger_welcome_email($user_id, $password, $additional_data);

// Send payment confirmation
$email_manager->trigger_payment_confirmation($order_data, $payment_data);

// Send course enrollment notification
$email_manager->trigger_course_enrollment($user_id, $course_data, $order_data);
```

#### Custom Email Triggers

```php
// Hook into WordPress actions
add_action('your_custom_action', function($data) {
    $email_manager = skylearn_billing_pro_email();
    $email_manager->send_email('welcome', $data['email'], $data);
});
```

#### Adding Custom Tokens

```php
// Filter email data before sending
add_filter('skylearn_email_data', function($data, $email_type) {
    $data['custom_token'] = 'Custom Value';
    return $data;
}, 10, 2);
```

## File Structure

```
includes/
├── admin/
│   └── class-email.php              # Core email management
├── emails/
│   └── class-email-builder.php     # Email builder functionality
assets/
├── css/
│   └── email-builder.css           # Email builder styles
├── js/
│   └── email-builder.js            # Email builder JavaScript
└── images/
    └── README.md                    # Image assets directory
templates/
└── admin/
    └── enhanced-email-settings.php # Admin interface template
```

## Technical Requirements

- **PHP Version**: 7.4 or higher
- **WordPress Version**: 5.0 or higher
- **Dependencies**: jQuery, jQuery UI Sortable
- **Browser Support**: Modern browsers with ES6 support

## Security Features

- **Nonce Verification**: All AJAX requests use WordPress nonces
- **Permission Checks**: Admin capabilities required for all operations
- **Input Sanitization**: All user inputs are properly sanitized
- **XSS Protection**: Output is properly escaped

## Integration Points

The email system integrates with:

1. **User Enrollment System**: Automatic welcome emails
2. **Payment Processing**: Payment confirmation/failure notifications
3. **Course Management**: Enrollment notifications
4. **Subscription System**: Subscription lifecycle emails
5. **WordPress Mail**: Extends wp_mail with SMTP support

## Customization Options

### Template Customization
- Visual email builder for non-technical users
- HTML editing for developers
- CSS styling options
- Responsive design templates

### Multi-language Support
- Language-specific templates
- Automatic language detection
- Translation-ready token system

### Analytics and Reporting
- Delivery rate monitoring
- Email performance tracking
- Export capabilities for external analysis

## Troubleshooting

### Common Issues

1. **Emails Not Sending:**
   - Check SMTP configuration
   - Verify email templates are enabled
   - Review email logs for error messages

2. **Tokens Not Replacing:**
   - Ensure correct token syntax: `{{token_name}}`
   - Verify token data is being passed to email function
   - Check for typos in token names

3. **Email Builder Not Loading:**
   - Check JavaScript console for errors
   - Ensure all assets are properly enqueued
   - Verify admin permissions

### Debug Mode

Enable WordPress debug mode to see detailed error messages:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Future Enhancements

Planned improvements for future versions:

1. **Email Open/Click Tracking**: Advanced analytics with pixel tracking
2. **A/B Testing**: Template performance comparison
3. **Email Automation**: Drip campaigns and automated sequences
4. **Third-party Integrations**: Mailchimp, SendGrid, etc.
5. **Advanced Segmentation**: Targeted email campaigns

## Support

For technical support or questions about the email system:

1. Check the documentation first
2. Review error logs
3. Test with simple templates
4. Contact support with specific error messages and steps to reproduce

---

*This documentation covers Phase 8 implementation of the Skylearn Billing Pro email system. For updates and additional features, refer to future phase documentation.*