<?php
/**
 * Onboarding wizard template
 *
 * @package SkyLearnBillingPro
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$onboarding = skylearn_billing_pro_onboarding();
$current_step = $onboarding->get_current_step();
$steps = $onboarding->get_onboarding_steps();
$step_keys = array_keys($steps);
$current_step_index = array_search($current_step, $step_keys);
$total_steps = count($steps);
?>

<div class="wrap skylearn-onboarding-wrap">
    <div class="skylearn-onboarding-container">
        
        <!-- Header -->
        <div class="skylearn-onboarding-header">
            <div class="skylearn-logo">
                <img src="<?php echo esc_url(SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/images/logo.png'); ?>" alt="Skylearn Billing Pro" />
                <h1><?php esc_html_e('Skylearn Billing Pro Setup', 'skylearn-billing-pro'); ?></h1>
            </div>
            <div class="skylearn-progress-bar">
                <div class="skylearn-progress-fill" style="width: <?php echo esc_attr(($current_step_index / ($total_steps - 1)) * 100); ?>%"></div>
            </div>
            <div class="skylearn-step-counter">
                <?php echo esc_html(sprintf(__('Step %d of %d', 'skylearn-billing-pro'), $current_step_index + 1, $total_steps)); ?>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="skylearn-onboarding-sidebar">
            <ul class="skylearn-steps-list">
                <?php foreach ($steps as $step_key => $step_data) : 
                    $step_index = array_search($step_key, $step_keys);
                    $is_current = $step_key === $current_step;
                    $is_completed = $step_index < $current_step_index;
                    $step_class = 'skylearn-step';
                    if ($is_current) $step_class .= ' current';
                    if ($is_completed) $step_class .= ' completed';
                ?>
                    <li class="<?php echo esc_attr($step_class); ?>">
                        <span class="skylearn-step-icon">
                            <?php if ($is_completed) : ?>
                                <span class="dashicons dashicons-yes-alt"></span>
                            <?php else : ?>
                                <span class="dashicons <?php echo esc_attr($step_data['icon']); ?>"></span>
                            <?php endif; ?>
                        </span>
                        <span class="skylearn-step-title"><?php echo esc_html($step_data['title']); ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="skylearn-onboarding-content">
            
            <!-- Step: Welcome -->
            <?php if ($current_step === 'welcome') : ?>
                <div class="skylearn-step-content" data-step="welcome">
                    <div class="skylearn-step-header">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <h2><?php esc_html_e('Welcome to Skylearn Billing Pro', 'skylearn-billing-pro'); ?></h2>
                        <p><?php esc_html_e('Let\'s get you set up with the ultimate billing solution for your WordPress courses.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="skylearn-welcome-features">
                        <div class="skylearn-feature">
                            <span class="dashicons dashicons-credit-card"></span>
                            <h3><?php esc_html_e('Payment Processing', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Accept payments through Stripe, Lemon Squeezy, and more.', 'skylearn-billing-pro'); ?></p>
                        </div>
                        <div class="skylearn-feature">
                            <span class="dashicons dashicons-welcome-learn-more"></span>
                            <h3><?php esc_html_e('LMS Integration', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Automatic course enrollment with LearnDash and other LMS platforms.', 'skylearn-billing-pro'); ?></p>
                        </div>
                        <div class="skylearn-feature">
                            <span class="dashicons dashicons-chart-line"></span>
                            <h3><?php esc_html_e('Subscription Management', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Flexible recurring billing and subscription lifecycle management.', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>
                    
                    <div class="skylearn-step-actions">
                        <button type="button" class="button button-primary skylearn-next-step" data-next="license">
                            <?php esc_html_e('Get Started', 'skylearn-billing-pro'); ?>
                        </button>
                        <button type="button" class="button button-secondary skylearn-skip-onboarding">
                            <?php esc_html_e('Skip Setup', 'skylearn-billing-pro'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step: License -->
            <?php if ($current_step === 'license') : ?>
                <div class="skylearn-step-content" data-step="license">
                    <div class="skylearn-step-header">
                        <span class="dashicons dashicons-admin-network"></span>
                        <h2><?php esc_html_e('License Activation', 'skylearn-billing-pro'); ?></h2>
                        <p><?php esc_html_e('Activate your license to unlock all Pro features and receive updates.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <form class="skylearn-step-form" data-step="license">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('License Key', 'skylearn-billing-pro'); ?></th>
                                <td>
                                    <input type="text" name="license_key" class="regular-text" placeholder="<?php esc_attr_e('Enter your license key...', 'skylearn-billing-pro'); ?>" />
                                    <p class="description">
                                        <?php esc_html_e('Don\'t have a license?', 'skylearn-billing-pro'); ?>
                                        <a href="https://skyian.com/skylearn-billing/pricing/" target="_blank"><?php esc_html_e('Get one here', 'skylearn-billing-pro'); ?></a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="skylearn-step-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e('Activate License', 'skylearn-billing-pro'); ?>
                            </button>
                            <button type="button" class="button button-secondary skylearn-skip-step" data-next="lms">
                                <?php esc_html_e('Skip for Now', 'skylearn-billing-pro'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Step: LMS -->
            <?php if ($current_step === 'lms') : ?>
                <div class="skylearn-step-content" data-step="lms">
                    <div class="skylearn-step-header">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <h2><?php esc_html_e('LMS Integration', 'skylearn-billing-pro'); ?></h2>
                        <p><?php esc_html_e('Connect your Learning Management System for automatic course enrollment.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <form class="skylearn-step-form" data-step="lms">
                        <?php
                        $lms_manager = skylearn_billing_pro_lms_manager();
                        $detected_lms = $lms_manager->get_detected_lms();
                        ?>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Active LMS', 'skylearn-billing-pro'); ?></th>
                                <td>
                                    <?php if (empty($detected_lms)) : ?>
                                        <p class="description" style="color: #d63638;">
                                            <?php esc_html_e('No LMS plugins detected. Please install and activate a supported LMS plugin like LearnDash.', 'skylearn-billing-pro'); ?>
                                        </p>
                                        <input type="hidden" name="active_lms" value="" />
                                    <?php else : ?>
                                        <select name="active_lms">
                                            <option value=""><?php esc_html_e('Select an LMS...', 'skylearn-billing-pro'); ?></option>
                                            <?php foreach ($detected_lms as $lms_key => $lms_data) : ?>
                                                <option value="<?php echo esc_attr($lms_key); ?>"><?php echo esc_html($lms_data['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Auto Enrollment', 'skylearn-billing-pro'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="auto_enroll" value="1" checked />
                                        <?php esc_html_e('Automatically enroll users in mapped courses after successful payment', 'skylearn-billing-pro'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="skylearn-step-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e('Continue', 'skylearn-billing-pro'); ?>
                            </button>
                            <button type="button" class="button button-secondary skylearn-skip-step" data-next="payment">
                                <?php esc_html_e('Skip for Now', 'skylearn-billing-pro'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Step: Payment -->
            <?php if ($current_step === 'payment') : ?>
                <div class="skylearn-step-content" data-step="payment">
                    <div class="skylearn-step-header">
                        <span class="dashicons dashicons-credit-card"></span>
                        <h2><?php esc_html_e('Payment Gateways', 'skylearn-billing-pro'); ?></h2>
                        <p><?php esc_html_e('Configure your payment processors to start accepting payments.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <form class="skylearn-step-form" data-step="payment">
                        <div class="skylearn-payment-gateways">
                            
                            <!-- Stripe -->
                            <div class="skylearn-gateway-section">
                                <h3>
                                    <label>
                                        <input type="checkbox" name="stripe_enabled" value="1" />
                                        <img src="<?php echo esc_url(SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/images/stripe-logo.png'); ?>" alt="Stripe" />
                                        Stripe
                                    </label>
                                </h3>
                                <div class="skylearn-gateway-fields" style="display: none;">
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php esc_html_e('Publishable Key', 'skylearn-billing-pro'); ?></th>
                                            <td><input type="text" name="stripe_public_key" class="regular-text" placeholder="pk_test_..." /></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e('Secret Key', 'skylearn-billing-pro'); ?></th>
                                            <td><input type="password" name="stripe_secret_key" class="regular-text" placeholder="sk_test_..." /></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Lemon Squeezy -->
                            <div class="skylearn-gateway-section">
                                <h3>
                                    <label>
                                        <input type="checkbox" name="lemonsqueezy_enabled" value="1" />
                                        <img src="<?php echo esc_url(SKYLEARN_BILLING_PRO_PLUGIN_URL . 'assets/images/lemonsqueezy-logo.png'); ?>" alt="Lemon Squeezy" />
                                        Lemon Squeezy
                                    </label>
                                </h3>
                                <div class="skylearn-gateway-fields" style="display: none;">
                                    <table class="form-table">
                                        <tr>
                                            <th scope="row"><?php esc_html_e('Store ID', 'skylearn-billing-pro'); ?></th>
                                            <td><input type="text" name="lemonsqueezy_store_id" class="regular-text" placeholder="12345" /></td>
                                        </tr>
                                        <tr>
                                            <th scope="row"><?php esc_html_e('API Key', 'skylearn-billing-pro'); ?></th>
                                            <td><input type="password" name="lemonsqueezy_api_key" class="regular-text" placeholder="lemon_api_..." /></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="skylearn-step-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e('Continue', 'skylearn-billing-pro'); ?>
                            </button>
                            <button type="button" class="button button-secondary skylearn-skip-step" data-next="products">
                                <?php esc_html_e('Skip for Now', 'skylearn-billing-pro'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Step: Products -->
            <?php if ($current_step === 'products') : ?>
                <div class="skylearn-step-content" data-step="products">
                    <div class="skylearn-step-header">
                        <span class="dashicons dashicons-products"></span>
                        <h2><?php esc_html_e('Create Your First Product', 'skylearn-billing-pro'); ?></h2>
                        <p><?php esc_html_e('Set up your first course or digital product for sale.', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <form class="skylearn-step-form" data-step="products">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Product Name', 'skylearn-billing-pro'); ?> <span class="required">*</span></th>
                                <td>
                                    <input type="text" name="product_name" class="regular-text" placeholder="<?php esc_attr_e('e.g., WordPress Masterclass', 'skylearn-billing-pro'); ?>" required />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Description', 'skylearn-billing-pro'); ?></th>
                                <td>
                                    <textarea name="product_description" rows="3" class="large-text" placeholder="<?php esc_attr_e('Brief description of your course or product...', 'skylearn-billing-pro'); ?>"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Price', 'skylearn-billing-pro'); ?> <span class="required">*</span></th>
                                <td>
                                    <input type="number" name="product_price" step="0.01" min="0" class="regular-text" placeholder="99.00" required />
                                    <p class="description"><?php esc_html_e('Enter the price in your default currency.', 'skylearn-billing-pro'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><?php esc_html_e('Product Type', 'skylearn-billing-pro'); ?></th>
                                <td>
                                    <select name="product_type">
                                        <option value="one_time"><?php esc_html_e('One-time Payment', 'skylearn-billing-pro'); ?></option>
                                        <option value="subscription"><?php esc_html_e('Subscription', 'skylearn-billing-pro'); ?></option>
                                    </select>
                                </td>
                            </tr>
                        </table>
                        
                        <div class="skylearn-step-actions">
                            <button type="submit" class="button button-primary">
                                <?php esc_html_e('Create Product', 'skylearn-billing-pro'); ?>
                            </button>
                            <button type="button" class="button button-secondary skylearn-skip-step" data-next="complete">
                                <?php esc_html_e('Skip for Now', 'skylearn-billing-pro'); ?>
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Step: Complete -->
            <?php if ($current_step === 'complete') : ?>
                <div class="skylearn-step-content" data-step="complete">
                    <div class="skylearn-step-header">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <h2><?php esc_html_e('Setup Complete!', 'skylearn-billing-pro'); ?></h2>
                        <p><?php esc_html_e('Your billing system is ready. Start selling your courses today!', 'skylearn-billing-pro'); ?></p>
                    </div>
                    
                    <div class="skylearn-completion-summary">
                        <h3><?php esc_html_e('What\'s Next?', 'skylearn-billing-pro'); ?></h3>
                        <ul class="skylearn-next-steps">
                            <li>
                                <span class="dashicons dashicons-products"></span>
                                <strong><?php esc_html_e('Manage Products:', 'skylearn-billing-pro'); ?></strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products')); ?>"><?php esc_html_e('Add more products and courses', 'skylearn-billing-pro'); ?></a>
                            </li>
                            <li>
                                <span class="dashicons dashicons-admin-settings"></span>
                                <strong><?php esc_html_e('Configure Settings:', 'skylearn-billing-pro'); ?></strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro')); ?>"><?php esc_html_e('Fine-tune your billing settings', 'skylearn-billing-pro'); ?></a>
                            </li>
                            <li>
                                <span class="dashicons dashicons-chart-line"></span>
                                <strong><?php esc_html_e('View Reports:', 'skylearn-billing-pro'); ?></strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-reports')); ?>"><?php esc_html_e('Track your revenue and subscriptions', 'skylearn-billing-pro'); ?></a>
                            </li>
                            <li>
                                <span class="dashicons dashicons-book"></span>
                                <strong><?php esc_html_e('Read Documentation:', 'skylearn-billing-pro'); ?></strong>
                                <a href="https://skyian.com/skylearn-billing/doc/" target="_blank"><?php esc_html_e('Learn more about advanced features', 'skylearn-billing-pro'); ?></a>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="skylearn-step-actions">
                        <button type="button" class="button button-primary skylearn-complete-onboarding">
                            <?php esc_html_e('Go to Dashboard', 'skylearn-billing-pro'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
    
    <!-- Loading overlay -->
    <div class="skylearn-loading-overlay" style="display: none;">
        <div class="skylearn-spinner"></div>
        <p><?php esc_html_e('Processing...', 'skylearn-billing-pro'); ?></p>
    </div>
</div>