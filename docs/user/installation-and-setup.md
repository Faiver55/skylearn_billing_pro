# Skylearn Billing Pro - User Documentation

Welcome to the comprehensive user guide for Skylearn Billing Pro, the ultimate billing solution for WordPress course creators.

## Table of Contents

1. [Installation & Setup](#installation--setup)
2. [Getting Started](#getting-started)
3. [License Management](#license-management)
4. [LMS Integration](#lms-integration)
5. [Payment Gateways](#payment-gateways)
6. [Product Management](#product-management)
7. [Subscription Management](#subscription-management)
8. [Email System](#email-system)
9. [Customer Portal](#customer-portal)
10. [Reports & Analytics](#reports--analytics)
11. [Troubleshooting](#troubleshooting)

## Installation & Setup

### System Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.4 or higher
- **MySQL:** 5.6 or higher
- **HTTPS:** Required for payment processing
- **Memory Limit:** Minimum 128MB (256MB recommended)

### Installation Steps

1. **Download the Plugin**
   - Purchase and download Skylearn Billing Pro from [skyian.com](https://skyian.com/skylearn-billing/)
   - You'll receive a ZIP file containing the plugin

2. **Upload to WordPress**
   - Log into your WordPress admin dashboard
   - Navigate to Plugins > Add New > Upload Plugin
   - Choose the downloaded ZIP file and click "Install Now"
   - Activate the plugin after installation

3. **Alternative Installation**
   - Extract the ZIP file to your `/wp-content/plugins/` directory
   - Activate the plugin from the WordPress admin

### Initial Configuration

After activation, you'll see a welcome notice prompting you to complete the setup wizard. Click "Start Setup" to begin the onboarding process.

## Getting Started

### Setup Wizard

The setup wizard guides you through essential configuration in 5 simple steps:

1. **Welcome** - Overview of features
2. **License** - Activate your Pro license
3. **LMS Integration** - Connect your learning management system
4. **Payment Gateways** - Configure Stripe, Lemon Squeezy, etc.
5. **Products** - Create your first product

### Dashboard Overview

After setup, access your billing dashboard at **WordPress Admin > Skylearn Billing**. The main dashboard provides:

- Quick stats and revenue overview
- Recent activity and transactions
- System status indicators
- Quick action buttons

## License Management

### Activating Your License

1. Navigate to **Skylearn Billing > License**
2. Enter your license key (found in your purchase email)
3. Click "Activate License"
4. Your license status will show as "Active"

### License Benefits

- Access to all Pro features
- Automatic plugin updates
- Priority support
- Premium payment gateways
- Advanced reporting

### License Troubleshooting

**License won't activate:**
- Verify the license key is entered correctly
- Check if the license is already active on another site
- Ensure your site can connect to our license server
- Contact support if issues persist

## LMS Integration

### Supported LMS Platforms

- **LearnDash** - Full integration
- **LifterLMS** - Full integration
- **TutorLMS** - Basic integration
- **LearnPress** - Basic integration

### Setting Up LMS Integration

1. **Install and activate your LMS plugin**
2. **Navigate to Skylearn Billing > LMS Integration**
3. **Select your active LMS** from the dropdown
4. **Enable auto-enrollment** (recommended)
5. **Configure course mappings** in the mapping section

### Course Mapping

Course mapping connects your billing products to LMS courses:

1. Go to **LMS Integration > Course Mapping**
2. Select a product from the dropdown
3. Choose the corresponding course(s)
4. Set enrollment options (immediate, delayed, etc.)
5. Save the mapping

### Auto-Enrollment Settings

- **Immediate Enrollment:** Students are enrolled instantly after payment
- **Manual Enrollment:** Admin must manually enroll students
- **Delayed Enrollment:** Enrollment happens after a specified delay

## Payment Gateways

### Stripe Integration

**Prerequisites:**
- Stripe account (stripe.com)
- SSL certificate on your website

**Setup Steps:**
1. Log into your Stripe dashboard
2. Go to Developers > API Keys
3. Copy your Publishable and Secret keys
4. In WordPress, go to **Skylearn Billing > Payment Gateways > Stripe**
5. Enter your API keys
6. Configure webhook endpoints
7. Test the connection

**Webhook Configuration:**
- Endpoint URL: `https://yoursite.com/skylearn-billing/webhook`
- Events to send: `payment_intent.succeeded`, `customer.subscription.updated`, etc.

### Lemon Squeezy Integration

**Prerequisites:**
- Lemon Squeezy account
- Store configured on Lemon Squeezy

**Setup Steps:**
1. Log into your Lemon Squeezy dashboard
2. Go to Settings > API
3. Generate an API key
4. In WordPress, go to **Skylearn Billing > Payment Gateways > Lemon Squeezy**
5. Enter your Store ID and API key
6. Configure webhook endpoints

### Test Mode

Always test your payment setup before going live:

1. Enable test mode in **General Settings**
2. Use test API keys from your payment provider
3. Process test transactions
4. Verify course enrollment works
5. Switch to live mode when ready

## Product Management

### Creating Products

1. **Navigate to Skylearn Billing > Products**
2. **Click "Add New Product"**
3. **Fill in product details:**
   - Name and description
   - Price and currency
   - Product type (one-time or subscription)
   - Course mappings
4. **Configure advanced settings:**
   - Trial periods
   - Setup fees
   - Tax settings
5. **Publish the product**

### Product Types

**One-Time Products:**
- Single payment for lifetime access
- Perfect for courses and digital downloads
- No recurring billing

**Subscription Products:**
- Recurring payments (monthly, yearly, etc.)
- Continuous access while active
- Automatic renewal handling

**Bundle Products:**
- Multiple courses in one package
- Discounted pricing
- Flexible enrollment options

### Product Settings

**Pricing:**
- Set base price in your default currency
- Configure multi-currency if needed
- Add setup fees or trial periods

**Access Control:**
- Define course access rules
- Set enrollment triggers
- Configure access expiration

**Tax Settings:**
- Enable/disable tax collection
- Set tax rates by region
- Configure VAT handling

## Subscription Management

### Creating Subscription Plans

1. **Go to Skylearn Billing > Subscriptions > Plans**
2. **Click "Add New Plan"**
3. **Configure plan details:**
   - Name and description
   - Billing interval (monthly, yearly)
   - Price and currency
   - Trial period settings
4. **Set access levels and restrictions**
5. **Save the plan**

### Managing Active Subscriptions

**View All Subscriptions:**
- Go to **Skylearn Billing > Subscriptions**
- See active, paused, and cancelled subscriptions
- Search and filter by status or user

**Subscription Actions:**
- Pause/resume subscriptions
- Change billing amounts
- Update payment methods
- Cancel subscriptions

### Subscription Lifecycle

**New Subscription:**
1. Customer signs up for plan
2. Payment is processed
3. Course access is granted
4. Welcome email is sent

**Recurring Billing:**
1. Automatic payment attempt
2. Success: Access continues
3. Failure: Dunning process begins
4. Extended failure: Access suspended

**Cancellation:**
1. Customer or admin cancels
2. Access continues until period end
3. No future billing attempts
4. Cancellation email sent

## Email System

### Email Templates

Skylearn Billing Pro includes professional email templates:

- **Welcome Email** - Sent after successful purchase
- **Invoice Email** - Payment confirmation with invoice
- **Subscription Emails** - Renewal, cancellation, etc.
- **Dunning Emails** - Failed payment reminders

### Customizing Emails

1. **Go to Skylearn Billing > Email Settings**
2. **Select template to customize**
3. **Use the drag-and-drop editor:**
   - Add text blocks
   - Insert images
   - Customize colors and fonts
   - Add dynamic content
4. **Preview and test emails**
5. **Save changes**

### Dynamic Content

Use merge tags to personalize emails:
- `{{customer_name}}` - Customer's name
- `{{product_name}}` - Purchased product
- `{{invoice_url}}` - Link to invoice
- `{{login_url}}` - Link to course portal

### Email Delivery

**SMTP Configuration:**
- Install an SMTP plugin (recommended)
- Configure SMTP settings
- Test email delivery

**Troubleshooting Email Issues:**
- Check spam folders
- Verify SMTP configuration
- Test with different email providers
- Review email logs

## Customer Portal

### Portal Features

The customer portal provides self-service access to:
- Order history and invoices
- Subscription management
- Course access links
- Account information
- Download products

### Portal Customization

1. **Go to Skylearn Billing > Customer Portal**
2. **Customize portal pages:**
   - Dashboard layout
   - Color scheme
   - Logo and branding
   - Available sections
3. **Configure access settings:**
   - Registration requirements
   - Password reset options
   - Profile editing permissions

### Portal Shortcodes

Embed portal elements anywhere:
- `[skylearn_portal]` - Full portal
- `[skylearn_orders]` - Order history
- `[skylearn_subscriptions]` - Subscription list
- `[skylearn_downloads]` - Available downloads

## Reports & Analytics

### Available Reports

**Revenue Reports:**
- Total revenue by period
- Payment gateway breakdown
- Product performance
- Subscription metrics

**Customer Reports:**
- New customer acquisition
- Customer lifetime value
- Churn analysis
- Geographic distribution

**Product Reports:**
- Best-selling products
- Conversion rates
- Refund analysis
- Bundle performance

### Generating Reports

1. **Navigate to Skylearn Billing > Reports**
2. **Select report type**
3. **Choose date range**
4. **Apply filters (optional)**
5. **Generate report**
6. **Export to CSV/PDF if needed**

### Dashboard Widgets

Add billing widgets to your WordPress dashboard:
- Revenue overview
- Recent transactions
- Subscription status
- Quick statistics

## Troubleshooting

### Common Issues

**Payments Not Processing:**
1. Verify payment gateway credentials
2. Check SSL certificate status
3. Test webhook endpoints
4. Review error logs
5. Contact payment provider if needed

**Course Enrollment Failed:**
1. Verify LMS integration settings
2. Check course mapping configuration
3. Test with manual enrollment
4. Review enrollment logs
5. Ensure user permissions are correct

**Emails Not Sending:**
1. Test WordPress email functionality
2. Check SMTP configuration
3. Verify email template settings
4. Review email service logs
5. Consider using dedicated email service

**License Issues:**
1. Verify license key accuracy
2. Check site URL matches license
3. Ensure internet connectivity
4. Clear any caching
5. Contact support for assistance

### Getting Support

**Before Contacting Support:**
1. Check this documentation
2. Review FAQ section
3. Search community forums
4. Test in staging environment

**When Contacting Support:**
1. Describe the issue clearly
2. Include steps to reproduce
3. Provide error messages
4. Share relevant logs
5. Mention your license key

**Support Channels:**
- Email: support@skyian.com
- Live Chat: Available on our website
- Community Forum: Connect with other users
- Knowledge Base: Searchable help articles

### System Status

Monitor your system health:
1. **Go to Skylearn Billing > Status**
2. **Review system checks:**
   - WordPress version
   - PHP configuration
   - Database status
   - Payment gateway connectivity
   - LMS integration status
3. **Address any warnings or errors**

---

## Need More Help?

This documentation covers the essential features of Skylearn Billing Pro. For additional help:

- **Developer Documentation:** Technical integration guides
- **FAQ:** Frequently asked questions
- **Video Tutorials:** Step-by-step video guides
- **Support:** Contact our expert support team

Visit [skyian.com/skylearn-billing/doc/](https://skyian.com/skylearn-billing/doc/) for the latest documentation updates.