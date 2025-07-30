# Skylearn Billing Pro - FAQ & Troubleshooting Guide

Find answers to common questions and solutions to frequent issues with Skylearn Billing Pro.

## Table of Contents

1. [Frequently Asked Questions](#frequently-asked-questions)
2. [Installation & Setup Issues](#installation--setup-issues)
3. [License & Activation Problems](#license--activation-problems)
4. [Payment Gateway Issues](#payment-gateway-issues)
5. [LMS Integration Problems](#lms-integration-problems)
6. [Course Enrollment Issues](#course-enrollment-issues)
7. [Email & Notification Problems](#email--notification-problems)
8. [Subscription Management Issues](#subscription-management-issues)
9. [Performance & Compatibility](#performance--compatibility)
10. [Getting Additional Help](#getting-additional-help)

## Frequently Asked Questions

### General Questions

**Q: What is Skylearn Billing Pro?**
A: Skylearn Billing Pro is a comprehensive billing solution designed specifically for WordPress course creators. It handles payments, subscriptions, course enrollment, and customer management with seamless integration to popular LMS platforms.

**Q: Which payment gateways are supported?**
A: Currently supported gateways include:
- Stripe (recommended)
- Lemon Squeezy
- PayPal (coming soon)
- Square (coming soon)

**Q: Which LMS platforms are compatible?**
A: Skylearn Billing Pro integrates with:
- LearnDash (full integration)
- LifterLMS (full integration)
- TutorLMS (basic integration)
- LearnPress (basic integration)

**Q: Can I use multiple payment gateways simultaneously?**
A: Yes, you can configure multiple payment gateways and allow customers to choose their preferred method during checkout.

**Q: Is there a free version available?**
A: Skylearn Billing Pro is a premium plugin. However, we offer a 14-day free trial and a 30-day money-back guarantee.

**Q: Can I customize the checkout process?**
A: Yes, the plugin includes a drag-and-drop checkout builder that allows you to customize forms, fields, and the entire checkout experience.

### Pricing & Licensing

**Q: How much does Skylearn Billing Pro cost?**
A: Pricing starts at $99/year for a single site license. Volume discounts are available for multiple sites. Visit our pricing page for current rates.

**Q: Can I use one license on multiple sites?**
A: Each license is valid for one website. Multi-site licenses are available at discounted rates.

**Q: What happens if my license expires?**
A: Your plugin will continue to work, but you won't receive updates or support. Renewal discounts are available for existing customers.

**Q: Is there a lifetime license option?**
A: Currently, we offer annual licenses only. This ensures continuous development and support.

### Features & Functionality

**Q: Can I create subscription-based courses?**
A: Yes, Skylearn Billing Pro supports both one-time payments and recurring subscriptions with flexible billing intervals.

**Q: Does it support course bundles?**
A: Yes, you can create course bundles that give access to multiple courses with a single purchase.

**Q: Can students access courses immediately after payment?**
A: Yes, the plugin automatically enrolls students in courses immediately after successful payment confirmation.

**Q: Is there a customer portal?**
A: Yes, customers get access to a self-service portal where they can view their purchases, manage subscriptions, download invoices, and access their courses.

**Q: Can I customize email templates?**
A: Yes, the plugin includes a drag-and-drop email builder for customizing all customer communications.

## Installation & Setup Issues

### Plugin Installation Problems

**Problem:** Can't upload the plugin ZIP file
**Solutions:**
1. Check that your file is the correct ZIP file from your purchase
2. Ensure you have sufficient WordPress permissions
3. Try extracting and uploading via FTP to `/wp-content/plugins/`
4. Increase PHP upload limits in your hosting settings

**Problem:** Plugin activation fails
**Solutions:**
1. Check PHP version compatibility (7.4+ required)
2. Ensure WordPress version is 5.0 or higher
3. Verify memory limits (256MB recommended)
4. Check for conflicting plugins
5. Review error logs for specific error messages

**Problem:** Setup wizard doesn't appear
**Solutions:**
1. Clear all caches (plugin, server, CDN)
2. Deactivate conflicting plugins temporarily
3. Switch to a default WordPress theme
4. Navigate manually to the setup: `admin.php?page=skylearn-billing-pro&onboarding=1`

### Database Issues

**Problem:** Database tables not created
**Solutions:**
1. Check database user permissions
2. Manually run the activation process
3. Verify MySQL version compatibility
4. Check for database connection issues

**Problem:** Plugin settings not saving
**Solutions:**
1. Check database write permissions
2. Verify WordPress nonces are working
3. Clear all caches
4. Check for JavaScript errors in browser console

## License & Activation Problems

### License Won't Activate

**Problem:** "Invalid license key" error
**Solutions:**
1. Double-check the license key for typos
2. Ensure you're using the correct license for your domain
3. Verify your site URL matches the licensed domain
4. Check if the license is already active on another site

**Problem:** "License server unreachable" error
**Solutions:**
1. Verify your server can make outbound HTTPS connections
2. Check firewall settings
3. Try activating from a different server location
4. Contact your hosting provider about connection restrictions

**Problem:** License appears active but features are locked
**Solutions:**
1. Clear all caches and refresh the page
2. Deactivate and reactivate the license
3. Check if the license has expired
4. Verify the license type matches your needs

### License Management

**Problem:** Need to move license to a new domain
**Solutions:**
1. Deactivate license from the old site
2. Update domain in your account dashboard
3. Activate license on the new site
4. Contact support if you can't access the old site

**Problem:** License shows as expired but was recently renewed
**Solutions:**
1. Clear all caches
2. Deactivate and reactivate the license
3. Check your account dashboard for renewal status
4. Contact support with your renewal receipt

## Payment Gateway Issues

### Stripe Integration Problems

**Problem:** Payments failing with Stripe
**Troubleshooting Steps:**
1. Verify API keys are correct (live vs test)
2. Check if test mode matches your key type
3. Ensure webhook endpoints are configured
4. Verify SSL certificate is valid
5. Check Stripe dashboard for error details

**Common Stripe Errors:**
- **"No such payment_intent":** Webhook configuration issue
- **"Invalid API key":** Check key format and test/live mode
- **"Card declined":** Customer payment method issue
- **"Webhook signature invalid":** Webhook secret mismatch

### Lemon Squeezy Integration Problems

**Problem:** Lemon Squeezy payments not processing
**Solutions:**
1. Verify Store ID and API key are correct
2. Check product configuration in Lemon Squeezy
3. Ensure webhook URLs are properly configured
4. Verify tax settings match your requirements

**Problem:** Customers redirected but not enrolled
**Solutions:**
1. Check webhook delivery in Lemon Squeezy dashboard
2. Verify product mapping configuration
3. Review error logs for webhook processing issues
4. Test webhook endpoint manually

### General Payment Issues

**Problem:** Customers see "Payment failed" but money was charged
**Solutions:**
1. Check payment gateway dashboard for transaction status
2. Review webhook logs for processing errors
3. Manually verify customer enrollment
4. Process refund if enrollment failed

**Problem:** Payment amounts incorrect
**Solutions:**
1. Verify currency settings match payment gateway
2. Check for tax calculation errors
3. Review product pricing configuration
4. Ensure discount codes are working correctly

## LMS Integration Problems

### LearnDash Integration Issues

**Problem:** Students not enrolled in LearnDash courses
**Troubleshooting:**
1. Verify LearnDash is active and up to date
2. Check course mapping configuration
3. Ensure auto-enrollment is enabled
4. Review user capabilities and permissions
5. Test manual enrollment process

**Problem:** Enrollment works but progress not syncing
**Solutions:**
1. Check LearnDash progress tracking settings
2. Verify user roles have proper permissions
3. Review LearnDash completion triggers
4. Clear LearnDash caches

### LifterLMS Integration Issues

**Problem:** LifterLMS enrollment failing
**Solutions:**
1. Update LifterLMS to latest version
2. Check membership and course relationships
3. Verify enrollment trigger settings
4. Review LifterLMS access plan configuration

### General LMS Issues

**Problem:** Multiple LMS platforms detected
**Solutions:**
1. Choose the primary LMS in settings
2. Deactivate unused LMS plugins
3. Clear plugin caches
4. Reconfigure course mappings

**Problem:** Course access revoked unexpectedly
**Solutions:**
1. Check subscription status
2. Verify payment is current
3. Review access control settings
4. Check for conflicting plugins

## Course Enrollment Issues

### Enrollment Not Working

**Problem:** Payments successful but no course access
**Troubleshooting Steps:**
1. Check course mapping configuration
2. Verify LMS integration is active
3. Review enrollment logs in Status page
4. Test with a different user account
5. Check user permissions and roles

**Problem:** Partial enrollments (some courses missing)
**Solutions:**
1. Review course mapping for all products
2. Check individual course permissions
3. Verify LMS course status (published/draft)
4. Test enrollment manually

### Enrollment Timing Issues

**Problem:** Delayed enrollment after payment
**Solutions:**
1. Check webhook processing delays
2. Review server cron job configuration
3. Verify LMS processing queues
4. Consider server performance issues

**Problem:** Enrollment works in test but not live
**Solutions:**
1. Verify live payment gateway configuration
2. Check webhook URLs for live environment
3. Review SSL certificate for live site
4. Test with small amount first

## Email & Notification Problems

### Emails Not Sending

**Problem:** No emails received after purchase
**Troubleshooting:**
1. Check WordPress email functionality
2. Verify email settings in plugin
3. Test with different email address
4. Review server email logs
5. Consider SMTP plugin installation

**Problem:** Emails going to spam folder
**Solutions:**
1. Set up SPF, DKIM, and DMARC records
2. Use a dedicated SMTP service
3. Improve email content and formatting
4. Request whitelist from email providers

### Email Template Issues

**Problem:** Email templates not displaying correctly
**Solutions:**
1. Clear email template cache
2. Check HTML/CSS syntax
3. Test with different email clients
4. Verify merge tags are correct
5. Use simpler HTML structure

**Problem:** Dynamic content not working
**Solutions:**
1. Verify merge tag syntax
2. Check data availability for tags
3. Test with default template
4. Review template processing logs

## Subscription Management Issues

### Subscription Creation Problems

**Problem:** Subscriptions not created after payment
**Solutions:**
1. Verify payment gateway supports subscriptions
2. Check subscription plan configuration
3. Review webhook processing for subscription events
4. Ensure billing intervals are set correctly

**Problem:** Trial periods not working
**Solutions:**
1. Check trial period settings in plan
2. Verify payment gateway trial support
3. Review trial handling in webhook processing
4. Test with different trial durations

### Subscription Lifecycle Issues

**Problem:** Subscriptions not renewing automatically
**Solutions:**
1. Check payment method validity
2. Verify webhook configuration for renewal events
3. Review payment gateway subscription status
4. Check for failed payment handling

**Problem:** Cancellation not working properly
**Solutions:**
1. Verify cancellation API integration
2. Check immediate vs end-of-period settings
3. Review access revocation timing
4. Test cancellation workflow manually

## Performance & Compatibility

### Performance Issues

**Problem:** Plugin slowing down website
**Solutions:**
1. Enable object caching (Redis/Memcached)
2. Optimize database queries
3. Review plugin conflict testing
4. Consider server resource upgrades
5. Implement page caching

**Problem:** Checkout page loading slowly
**Solutions:**
1. Optimize payment gateway loading
2. Minimize checkout form fields
3. Implement checkout page caching exemptions
4. Review third-party script loading

### Compatibility Issues

**Problem:** Conflicts with other plugins
**Troubleshooting:**
1. Deactivate all plugins except essentials
2. Reactivate plugins one by one
3. Test functionality after each activation
4. Contact plugin authors for compatibility

**Problem:** Theme compatibility issues
**Solutions:**
1. Test with default WordPress theme
2. Check theme's checkout page templates
3. Review CSS conflicts
4. Consider custom styling solutions

### Browser Compatibility

**Problem:** Checkout not working in specific browsers
**Solutions:**
1. Update browser to latest version
2. Clear browser cache and cookies
3. Disable browser extensions
4. Check JavaScript console for errors
5. Test in incognito/private mode

## Getting Additional Help

### Before Contacting Support

1. **Check System Status**
   - Go to Skylearn Billing > Status
   - Review all system checks
   - Address any warnings or errors

2. **Gather Information**
   - Note exact error messages
   - Document steps to reproduce issue
   - Check browser console for JavaScript errors
   - Review server error logs

3. **Test in Clean Environment**
   - Deactivate conflicting plugins
   - Switch to default theme
   - Test with different user account
   - Try on staging site if available

### Contact Information

**Email Support:** support@skyian.com
- Response time: Within 24 hours
- Include license key and site URL
- Provide detailed problem description

**Live Chat:** Available on our website
- Business hours: Mon-Fri 9 AM - 6 PM EST
- Instant response for urgent issues

**Community Forum:** community.skyian.com
- Connect with other users
- Share tips and solutions
- Get community support

**Documentation:** docs.skyian.com
- Comprehensive guides
- Video tutorials
- API documentation

### What to Include in Support Requests

1. **License Information**
   - License key
   - Site URL
   - License status

2. **Technical Details**
   - WordPress version
   - Plugin version
   - PHP version
   - Active plugins list

3. **Problem Description**
   - Exact error messages
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable

4. **Troubleshooting Attempted**
   - List what you've tried
   - Results of each attempt
   - Any temporary workarounds

### Priority Support

For urgent issues affecting live payments:
- Use "URGENT" in email subject
- Provide phone number for callback
- Consider priority support upgrade
- Implement temporary workaround if possible

Remember: Most issues can be resolved quickly with proper troubleshooting. Follow the steps in this guide first, then contact support with detailed information for fastest resolution.