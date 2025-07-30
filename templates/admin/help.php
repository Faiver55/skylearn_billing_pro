<?php
/**
 * Contextual help and support links template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$current_page = sanitize_text_field($_GET['page'] ?? '');
$help_page = str_replace('skylearn-billing-pro-', '', $current_page);
if ($help_page === 'skylearn-billing-pro') {
    $help_page = 'general';
}

$onboarding = skylearn_billing_pro_onboarding();
?>

<div class="skylearn-help-container">
    
    <!-- Help Header -->
    <div class="skylearn-help-header">
        <h1><?php esc_html_e('Help & Documentation', 'skylearn-billing-pro'); ?></h1>
        <p><?php esc_html_e('Get support and learn how to make the most of Skylearn Billing Pro.', 'skylearn-billing-pro'); ?></p>
    </div>

    <!-- Quick Links -->
    <div class="skylearn-help-quick-links">
        <div class="skylearn-help-card">
            <span class="dashicons dashicons-book-alt"></span>
            <h3><?php esc_html_e('Documentation', 'skylearn-billing-pro'); ?></h3>
            <p><?php esc_html_e('Complete guides for installation, setup, and advanced features.', 'skylearn-billing-pro'); ?></p>
            <a href="https://skyian.com/skylearn-billing/doc/" target="_blank" class="button button-primary">
                <?php esc_html_e('View Documentation', 'skylearn-billing-pro'); ?>
            </a>
        </div>

        <div class="skylearn-help-card">
            <span class="dashicons dashicons-sos"></span>
            <h3><?php esc_html_e('Get Support', 'skylearn-billing-pro'); ?></h3>
            <p><?php esc_html_e('Need help? Our support team is here to assist you.', 'skylearn-billing-pro'); ?></p>
            <a href="https://skyian.com/skylearn-billing/support/" target="_blank" class="button button-primary">
                <?php esc_html_e('Contact Support', 'skylearn-billing-pro'); ?>
            </a>
        </div>

        <div class="skylearn-help-card">
            <span class="dashicons dashicons-video-alt3"></span>
            <h3><?php esc_html_e('Video Tutorials', 'skylearn-billing-pro'); ?></h3>
            <p><?php esc_html_e('Watch step-by-step video guides and tutorials.', 'skylearn-billing-pro'); ?></p>
            <a href="https://skyian.com/skylearn-billing/tutorials/" target="_blank" class="button button-primary">
                <?php esc_html_e('Watch Videos', 'skylearn-billing-pro'); ?>
            </a>
        </div>

        <div class="skylearn-help-card">
            <span class="dashicons dashicons-groups"></span>
            <h3><?php esc_html_e('Community', 'skylearn-billing-pro'); ?></h3>
            <p><?php esc_html_e('Join our community forum to connect with other users.', 'skylearn-billing-pro'); ?></p>
            <a href="https://skyian.com/skylearn-billing/community/" target="_blank" class="button button-primary">
                <?php esc_html_e('Join Community', 'skylearn-billing-pro'); ?>
            </a>
        </div>
    </div>

    <!-- Page-Specific Help -->
    <?php if ($help = $onboarding->get_contextual_help($help_page)) : ?>
        <div class="skylearn-contextual-help">
            <h2><?php echo esc_html($help['title']); ?></h2>
            <p><?php echo esc_html($help['content']); ?></p>
            
            <div class="skylearn-help-links">
                <a href="<?php echo esc_url($help['links']['docs']); ?>" target="_blank" class="button">
                    <span class="dashicons dashicons-external"></span>
                    <?php esc_html_e('Read Documentation', 'skylearn-billing-pro'); ?>
                </a>
                <a href="<?php echo esc_url($help['links']['support']); ?>" target="_blank" class="button">
                    <span class="dashicons dashicons-sos"></span>
                    <?php esc_html_e('Get Support', 'skylearn-billing-pro'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- Common Questions -->
    <div class="skylearn-help-section">
        <h2><?php esc_html_e('Frequently Asked Questions', 'skylearn-billing-pro'); ?></h2>
        
        <div class="skylearn-faq-grid">
            <div class="skylearn-faq-item">
                <h3><?php esc_html_e('How do I set up payment gateways?', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Configure Stripe and Lemon Squeezy in the Payment Gateways section. You\'ll need your API keys from each provider.', 'skylearn-billing-pro'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-payments')); ?>"><?php esc_html_e('Setup Payment Gateways', 'skylearn-billing-pro'); ?></a>
            </div>

            <div class="skylearn-faq-item">
                <h3><?php esc_html_e('How do I integrate with my LMS?', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Connect LearnDash or other supported LMS platforms in the LMS Integration section for automatic course enrollment.', 'skylearn-billing-pro'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-lms')); ?>"><?php esc_html_e('Setup LMS Integration', 'skylearn-billing-pro'); ?></a>
            </div>

            <div class="skylearn-faq-item">
                <h3><?php esc_html_e('How do I create products?', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Add courses, digital products, and subscription plans in the Products section. Map them to your LMS courses for automatic enrollment.', 'skylearn-billing-pro'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products')); ?>"><?php esc_html_e('Manage Products', 'skylearn-billing-pro'); ?></a>
            </div>

            <div class="skylearn-faq-item">
                <h3><?php esc_html_e('How do I activate my license?', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Enter your license key in the License section to unlock Pro features and receive automatic updates.', 'skylearn-billing-pro'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-license')); ?>"><?php esc_html_e('Activate License', 'skylearn-billing-pro'); ?></a>
            </div>

            <div class="skylearn-faq-item">
                <h3><?php esc_html_e('How do I view sales reports?', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Access comprehensive sales reports, subscription metrics, and revenue analytics in the Reports section.', 'skylearn-billing-pro'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports')); ?>"><?php esc_html_e('View Reports', 'skylearn-billing-pro'); ?></a>
            </div>

            <div class="skylearn-faq-item">
                <h3><?php esc_html_e('How do I customize email templates?', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Use the drag-and-drop email builder to customize welcome emails, invoices, and notifications.', 'skylearn-billing-pro'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-email')); ?>"><?php esc_html_e('Customize Emails', 'skylearn-billing-pro'); ?></a>
            </div>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div class="skylearn-help-section">
        <h2><?php esc_html_e('Troubleshooting Guide', 'skylearn-billing-pro'); ?></h2>
        
        <div class="skylearn-troubleshooting">
            <div class="skylearn-trouble-item">
                <h3><?php esc_html_e('Payments not processing', 'skylearn-billing-pro'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Check if your payment gateway credentials are correct', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Ensure test mode is disabled for live payments', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Verify your webhook endpoints are configured', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Check the Status page for any API connection issues', 'skylearn-billing-pro'); ?></li>
                </ul>
            </div>

            <div class="skylearn-trouble-item">
                <h3><?php esc_html_e('Course enrollment not working', 'skylearn-billing-pro'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Verify your LMS is properly connected in LMS Integration', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Check if products are mapped to the correct courses', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Ensure auto-enrollment is enabled', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Review the enrollment logs for error messages', 'skylearn-billing-pro'); ?></li>
                </ul>
            </div>

            <div class="skylearn-trouble-item">
                <h3><?php esc_html_e('Email notifications not sending', 'skylearn-billing-pro'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Test your WordPress email functionality', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Check if email notifications are enabled in settings', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Verify your email templates are properly configured', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Consider setting up SMTP for better email delivery', 'skylearn-billing-pro'); ?></li>
                </ul>
            </div>

            <div class="skylearn-trouble-item">
                <h3><?php esc_html_e('License activation issues', 'skylearn-billing-pro'); ?></h3>
                <ul>
                    <li><?php esc_html_e('Ensure your license key is entered correctly', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Check if the license is already activated on another site', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Verify your site has internet access for license validation', 'skylearn-billing-pro'); ?></li>
                    <li><?php esc_html_e('Contact support if you continue to have issues', 'skylearn-billing-pro'); ?></li>
                </ul>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="skylearn-help-section">
        <h2><?php esc_html_e('System Status', 'skylearn-billing-pro'); ?></h2>
        <p><?php esc_html_e('Check your system status and configuration for optimal performance.', 'skylearn-billing-pro'); ?></p>
        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-status')); ?>" class="button button-secondary">
            <?php esc_html_e('View System Status', 'skylearn-billing-pro'); ?>
        </a>
    </div>

    <!-- Contact Support -->
    <div class="skylearn-help-section skylearn-support-section">
        <h2><?php esc_html_e('Still Need Help?', 'skylearn-billing-pro'); ?></h2>
        <p><?php esc_html_e('Our support team is here to help you succeed with Skylearn Billing Pro.', 'skylearn-billing-pro'); ?></p>
        
        <div class="skylearn-support-options">
            <div class="skylearn-support-option">
                <span class="dashicons dashicons-email-alt"></span>
                <h3><?php esc_html_e('Email Support', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Get personalized help via email within 24 hours.', 'skylearn-billing-pro'); ?></p>
                <a href="mailto:support@skyian.com" class="button button-primary"><?php esc_html_e('Email Us', 'skylearn-billing-pro'); ?></a>
            </div>

            <div class="skylearn-support-option">
                <span class="dashicons dashicons-format-chat"></span>
                <h3><?php esc_html_e('Live Chat', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Chat with our support team in real-time.', 'skylearn-billing-pro'); ?></p>
                <a href="https://skyian.com/skylearn-billing/chat/" target="_blank" class="button button-primary"><?php esc_html_e('Start Chat', 'skylearn-billing-pro'); ?></a>
            </div>

            <div class="skylearn-support-option">
                <span class="dashicons dashicons-phone"></span>
                <h3><?php esc_html_e('Priority Support', 'skylearn-billing-pro'); ?></h3>
                <p><?php esc_html_e('Premium support for urgent issues and custom solutions.', 'skylearn-billing-pro'); ?></p>
                <a href="https://skyian.com/skylearn-billing/priority-support/" target="_blank" class="button button-secondary"><?php esc_html_e('Learn More', 'skylearn-billing-pro'); ?></a>
            </div>
        </div>
    </div>

</div>