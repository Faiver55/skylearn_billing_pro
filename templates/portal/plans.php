<?php
/**
 * Portal Plans Template
 *
 * Displays user's subscription plans and available actions
 *
 * @package SkyLearnBillingPro
 * @author Ferdous Khalifa
 * @copyright 2024 Skyian LLC
 * @license GPLv3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Ensure user is logged in
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user_id = get_current_user_id();
$subscription_manager = skylearn_billing_pro_subscription_manager();
$membership_manager = skylearn_billing_pro_membership_manager();
$loyalty = skylearn_billing_pro_loyalty();

// Get user data
$active_subscription = $subscription_manager->get_user_active_subscription($user_id);
$all_subscriptions = $subscription_manager->get_user_subscriptions($user_id);
$membership_data = $membership_manager->get_user_membership_data($user_id);
$loyalty_points = $loyalty->get_user_points($user_id);
$available_rewards = $loyalty->get_available_rewards();

// Get available plans (this would come from a plans/products system)
$available_plans = apply_filters('skylearn_billing_available_plans', array(
    'basic' => array(
        'name' => __('Basic Plan', 'skylearn-billing-pro'),
        'price' => 29,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'features' => array(
            __('Access to 10 courses', 'skylearn-billing-pro'),
            __('Basic support', 'skylearn-billing-pro'),
            __('Monthly group calls', 'skylearn-billing-pro')
        ),
        'tier' => 'basic'
    ),
    'premium' => array(
        'name' => __('Premium Plan', 'skylearn-billing-pro'),
        'price' => 49,
        'currency' => 'USD', 
        'billing_cycle' => 'monthly',
        'features' => array(
            __('Access to 50+ courses', 'skylearn-billing-pro'),
            __('Priority support', 'skylearn-billing-pro'),
            __('Weekly group calls', 'skylearn-billing-pro'),
            __('Downloadable resources', 'skylearn-billing-pro'),
            __('Certificate of completion', 'skylearn-billing-pro')
        ),
        'tier' => 'premium'
    ),
    'pro' => array(
        'name' => __('Pro Plan', 'skylearn-billing-pro'),
        'price' => 99,
        'currency' => 'USD',
        'billing_cycle' => 'monthly',
        'features' => array(
            __('Unlimited course access', 'skylearn-billing-pro'),
            __('1-on-1 mentoring sessions', 'skylearn-billing-pro'),
            __('Advanced workshops', 'skylearn-billing-pro'),
            __('Custom learning paths', 'skylearn-billing-pro'),
            __('API access', 'skylearn-billing-pro'),
            __('White-label resources', 'skylearn-billing-pro')
        ),
        'tier' => 'pro'
    )
));
?>

<div class="skylearn-portal-plans">
    <div class="plans-header">
        <h1><?php _e('My Plans & Subscriptions', 'skylearn-billing-pro'); ?></h1>
        <p class="plans-subtitle"><?php _e('Manage your subscription, view benefits, and explore upgrade options.', 'skylearn-billing-pro'); ?></p>
    </div>

    <!-- Current Subscription Status -->
    <div class="current-subscription-card">
        <div class="card-header">
            <h2><?php _e('Current Subscription', 'skylearn-billing-pro'); ?></h2>
            <?php if ($active_subscription): ?>
                <span class="status-badge status-<?php echo esc_attr($active_subscription['status']); ?>">
                    <?php echo esc_html(ucfirst($active_subscription['status'])); ?>
                </span>
            <?php else: ?>
                <span class="status-badge status-none"><?php _e('No Active Subscription', 'skylearn-billing-pro'); ?></span>
            <?php endif; ?>
        </div>

        <?php if ($active_subscription): ?>
            <div class="subscription-details">
                <div class="subscription-info">
                    <div class="plan-name">
                        <h3><?php echo esc_html($active_subscription['plan_id']); ?></h3>
                        <span class="tier-badge tier-<?php echo esc_attr($active_subscription['tier']); ?>">
                            <?php echo esc_html(ucfirst($active_subscription['tier'])); ?>
                        </span>
                    </div>
                    
                    <div class="subscription-meta">
                        <div class="meta-item">
                            <strong><?php _e('Amount:', 'skylearn-billing-pro'); ?></strong>
                            <?php echo esc_html($active_subscription['currency']); ?> <?php echo esc_html(number_format($active_subscription['amount'], 2)); ?>
                            <span class="billing-cycle">/<?php echo esc_html($active_subscription['billing_cycle']); ?></span>
                        </div>
                        
                        <?php if ($active_subscription['status'] === 'active'): ?>
                            <div class="meta-item">
                                <strong><?php _e('Next Payment:', 'skylearn-billing-pro'); ?></strong>
                                <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($active_subscription['next_payment_date']))); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="meta-item">
                            <strong><?php _e('Started:', 'skylearn-billing-pro'); ?></strong>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($active_subscription['start_date']))); ?>
                        </div>
                    </div>
                </div>

                <div class="subscription-actions">
                    <?php if ($active_subscription['status'] === 'active'): ?>
                        <button class="btn btn-secondary" onclick="showNurturePopup('pause')">
                            <i class="icon-pause"></i> <?php _e('Pause', 'skylearn-billing-pro'); ?>
                        </button>
                        
                        <?php if ($active_subscription['tier'] !== 'pro'): ?>
                            <button class="btn btn-primary" onclick="showNurturePopup('upgrade')">
                                <i class="icon-arrow-up"></i> <?php _e('Upgrade', 'skylearn-billing-pro'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <?php if ($active_subscription['tier'] !== 'basic'): ?>
                            <button class="btn btn-secondary" onclick="showNurturePopup('downgrade')">
                                <i class="icon-arrow-down"></i> <?php _e('Downgrade', 'skylearn-billing-pro'); ?>
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn btn-danger" onclick="showNurturePopup('cancel')">
                            <i class="icon-x"></i> <?php _e('Cancel', 'skylearn-billing-pro'); ?>
                        </button>
                    <?php elseif ($active_subscription['status'] === 'paused'): ?>
                        <button class="btn btn-primary" onclick="resumeSubscription('<?php echo esc_attr($active_subscription['id']); ?>')">
                            <i class="icon-play"></i> <?php _e('Resume', 'skylearn-billing-pro'); ?>
                        </button>
                        
                        <button class="btn btn-danger" onclick="showNurturePopup('cancel')">
                            <i class="icon-x"></i> <?php _e('Cancel', 'skylearn-billing-pro'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-subscription">
                <div class="empty-state">
                    <i class="icon-credit-card"></i>
                    <h3><?php _e('No Active Subscription', 'skylearn-billing-pro'); ?></h3>
                    <p><?php _e('Start your learning journey by choosing a plan below.', 'skylearn-billing-pro'); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Membership & Benefits -->
    <div class="membership-benefits-card">
        <div class="card-header">
            <h2><?php _e('Membership Benefits', 'skylearn-billing-pro'); ?></h2>
            <span class="membership-level"><?php echo esc_html($membership_data['level_name']); ?></span>
        </div>

        <div class="benefits-grid">
            <?php
            $membership_level = $membership_manager->get_membership_level($membership_data['level_id']);
            if ($membership_level && isset($membership_level['restrictions'])):
            ?>
                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="icon-book"></i>
                    </div>
                    <div class="benefit-content">
                        <h4><?php _e('Course Access', 'skylearn-billing-pro'); ?></h4>
                        <p>
                            <?php if ($membership_level['restrictions']['course_limit'] === -1): ?>
                                <?php _e('Unlimited courses', 'skylearn-billing-pro'); ?>
                            <?php else: ?>
                                <?php echo sprintf(__('Up to %d courses', 'skylearn-billing-pro'), $membership_level['restrictions']['course_limit']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="icon-download"></i>
                    </div>
                    <div class="benefit-content">
                        <h4><?php _e('Downloads', 'skylearn-billing-pro'); ?></h4>
                        <p>
                            <?php if ($membership_level['restrictions']['download_limit'] === -1): ?>
                                <?php _e('Unlimited downloads', 'skylearn-billing-pro'); ?>
                            <?php else: ?>
                                <?php echo sprintf(__('%d downloads per month', 'skylearn-billing-pro'), $membership_level['restrictions']['download_limit']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div class="benefit-item">
                    <div class="benefit-icon">
                        <i class="icon-support"></i>
                    </div>
                    <div class="benefit-content">
                        <h4><?php _e('Support Level', 'skylearn-billing-pro'); ?></h4>
                        <p><?php echo esc_html(ucfirst(str_replace('_', ' ', $membership_level['restrictions']['support_level']))); ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Loyalty & Rewards -->
    <div class="loyalty-rewards-card">
        <div class="card-header">
            <h2><?php _e('Loyalty & Rewards', 'skylearn-billing-pro'); ?></h2>
            <div class="points-display">
                <span class="points-number"><?php echo number_format($loyalty_points); ?></span>
                <span class="points-label"><?php _e('points', 'skylearn-billing-pro'); ?></span>
            </div>
        </div>

        <?php if (!empty($available_rewards)): ?>
            <div class="rewards-grid">
                <?php foreach ($available_rewards as $reward_id => $reward): ?>
                    <?php $can_redeem = $loyalty->can_user_redeem_reward($user_id, $reward_id); ?>
                    <div class="reward-item <?php echo $can_redeem === true ? 'redeemable' : 'locked'; ?>">
                        <div class="reward-content">
                            <h4><?php echo esc_html($reward['name']); ?></h4>
                            <p class="reward-description"><?php echo esc_html($reward['description']); ?></p>
                            <div class="reward-cost">
                                <span class="cost-amount"><?php echo number_format($reward['cost']); ?></span>
                                <span class="cost-label"><?php _e('points', 'skylearn-billing-pro'); ?></span>
                            </div>
                        </div>
                        <div class="reward-action">
                            <?php if ($can_redeem === true): ?>
                                <button class="btn btn-primary btn-sm" onclick="redeemReward('<?php echo esc_attr($reward_id); ?>')">
                                    <?php _e('Redeem', 'skylearn-billing-pro'); ?>
                                </button>
                            <?php else: ?>
                                <span class="btn btn-disabled btn-sm" title="<?php echo esc_attr($can_redeem); ?>">
                                    <?php _e('Locked', 'skylearn-billing-pro'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-rewards">
                <p><?php _e('No rewards available at the moment.', 'skylearn-billing-pro'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Available Plans -->
    <?php if (!$active_subscription || $active_subscription['status'] !== 'active'): ?>
        <div class="available-plans-card">
            <div class="card-header">
                <h2><?php _e('Choose Your Plan', 'skylearn-billing-pro'); ?></h2>
                <p><?php _e('Select the plan that best fits your learning goals.', 'skylearn-billing-pro'); ?></p>
            </div>

            <div class="plans-grid">
                <?php foreach ($available_plans as $plan_id => $plan): ?>
                    <div class="plan-card plan-<?php echo esc_attr($plan['tier']); ?>">
                        <div class="plan-header">
                            <h3 class="plan-name"><?php echo esc_html($plan['name']); ?></h3>
                            <div class="plan-price">
                                <span class="currency"><?php echo esc_html($plan['currency']); ?></span>
                                <span class="amount"><?php echo esc_html(number_format($plan['price'])); ?></span>
                                <span class="period">/<?php echo esc_html($plan['billing_cycle']); ?></span>
                            </div>
                        </div>

                        <div class="plan-features">
                            <ul>
                                <?php foreach ($plan['features'] as $feature): ?>
                                    <li><i class="icon-check"></i> <?php echo esc_html($feature); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <div class="plan-action">
                            <button class="btn btn-primary btn-full" onclick="selectPlan('<?php echo esc_attr($plan_id); ?>')">
                                <?php _e('Choose Plan', 'skylearn-billing-pro'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Subscription History -->
    <?php if (!empty($all_subscriptions)): ?>
        <div class="subscription-history-card">
            <div class="card-header">
                <h2><?php _e('Subscription History', 'skylearn-billing-pro'); ?></h2>
            </div>

            <div class="history-table-wrapper">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th><?php _e('Plan', 'skylearn-billing-pro'); ?></th>
                            <th><?php _e('Tier', 'skylearn-billing-pro'); ?></th>
                            <th><?php _e('Status', 'skylearn-billing-pro'); ?></th>
                            <th><?php _e('Amount', 'skylearn-billing-pro'); ?></th>
                            <th><?php _e('Start Date', 'skylearn-billing-pro'); ?></th>
                            <th><?php _e('Next Payment', 'skylearn-billing-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($all_subscriptions as $subscription): ?>
                            <tr class="<?php echo $subscription['id'] === $active_subscription['id'] ? 'active-row' : ''; ?>">
                                <td><?php echo esc_html($subscription['plan_id']); ?></td>
                                <td>
                                    <span class="tier-badge tier-<?php echo esc_attr($subscription['tier']); ?>">
                                        <?php echo esc_html(ucfirst($subscription['tier'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo esc_attr($subscription['status']); ?>">
                                        <?php echo esc_html(ucfirst($subscription['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo esc_html($subscription['currency']); ?> <?php echo esc_html(number_format($subscription['amount'], 2)); ?>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription['start_date']))); ?></td>
                                <td>
                                    <?php if ($subscription['status'] === 'active' && !empty($subscription['next_payment_date'])): ?>
                                        <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($subscription['next_payment_date']))); ?>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- JavaScript for interactions -->
<script>
jQuery(document).ready(function($) {
    // Subscription action handlers
    window.resumeSubscription = function(subscriptionId) {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to resume your subscription?', 'skylearn-billing-pro')); ?>')) {
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'skylearn_subscription_action',
                subscription_action: 'resume',
                subscription_id: subscriptionId,
                nonce: '<?php echo wp_create_nonce('skylearn_subscription_action'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Something went wrong. Please try again.', 'skylearn-billing-pro')); ?>');
            }
        });
    };

    // Reward redemption handler
    window.redeemReward = function(rewardId) {
        if (!confirm('<?php echo esc_js(__('Are you sure you want to redeem this reward?', 'skylearn-billing-pro')); ?>')) {
            return;
        }

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'skylearn_redeem_reward',
                reward_id: rewardId,
                nonce: '<?php echo wp_create_nonce('skylearn_redeem_reward'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert(response.data);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Something went wrong. Please try again.', 'skylearn-billing-pro')); ?>');
            }
        });
    };

    // Plan selection handler
    window.selectPlan = function(planId) {
        // This would redirect to checkout or open upgrade modal
        window.location.href = '<?php echo home_url('/checkout/'); ?>?plan=' + planId;
    };

    // Nurture popup handler
    window.showNurturePopup = function(popupType) {
        // This would be handled by the nurture popup script
        if (typeof window.skylernNurturePopup !== 'undefined') {
            window.skylernNurturePopup.show(popupType);
        }
    };
});
</script>