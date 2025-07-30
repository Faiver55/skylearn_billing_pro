<?php
/**
 * Customer Portal Dashboard Template
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

$user = wp_get_current_user();
$user_id = $user->ID;

// Get user data and statistics
$subscription_manager = function_exists('skylearn_billing_pro_subscription_manager') ? skylearn_billing_pro_subscription_manager() : null;
$membership_manager = function_exists('skylearn_billing_pro_membership_manager') ? skylearn_billing_pro_membership_manager() : null;

// Sample data - replace with actual data fetching
$dashboard_data = array(
    'recent_orders' => array(
        array(
            'id' => 'ORD-001',
            'name' => 'Advanced WordPress Development',
            'date' => '2024-01-15',
            'status' => 'completed',
            'amount' => '$99.00'
        ),
        array(
            'id' => 'ORD-002', 
            'name' => 'React.js Masterclass',
            'date' => '2024-01-10',
            'status' => 'completed',
            'amount' => '$149.00'
        )
    ),
    'active_subscriptions' => array(
        array(
            'name' => 'Pro Monthly Plan',
            'next_payment' => '2024-02-15',
            'status' => 'active',
            'amount' => '$29.00'
        )
    ),
    'available_downloads' => array(
        array(
            'name' => 'Course Materials - WordPress Development',
            'size' => '45 MB',
            'type' => 'zip',
            'url' => '#'
        ),
        array(
            'name' => 'Bonus eBook - Performance Optimization',
            'size' => '12 MB', 
            'type' => 'pdf',
            'url' => '#'
        )
    ),
    'stats' => array(
        'total_purchases' => 5,
        'active_courses' => 3,
        'completion_rate' => 78,
        'loyalty_points' => 1250
    )
);
?>

<div class="skylearn-portal-dashboard" role="main" aria-labelledby="dashboard-title">
    <!-- Dashboard Header -->
    <header class="skylearn-dashboard-header">
        <div class="skylearn-welcome-section">
            <h1 id="dashboard-title" class="skylearn-dashboard-title">
                <?php printf(__('Welcome back, %s!', 'skylearn-billing-pro'), esc_html($user->display_name)); ?>
            </h1>
            <p class="skylearn-dashboard-subtitle">
                <?php _e('Here\'s an overview of your learning journey and account activity.', 'skylearn-billing-pro'); ?>
            </p>
        </div>
        
        <div class="skylearn-user-avatar">
            <?php echo get_avatar($user_id, 64, '', '', array('class' => 'skylearn-avatar-img')); ?>
        </div>
    </header>
    
    <!-- Stats Overview -->
    <section class="skylearn-stats-section" aria-labelledby="stats-title">
        <h2 id="stats-title" class="skylearn-sr-only"><?php _e('Account Statistics', 'skylearn-billing-pro'); ?></h2>
        
        <div class="skylearn-stats-grid">
            <div class="skylearn-stat-card">
                <div class="skylearn-stat-icon" aria-hidden="true">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                        <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="skylearn-stat-content">
                    <span class="skylearn-stat-number"><?php echo esc_html($dashboard_data['stats']['total_purchases']); ?></span>
                    <span class="skylearn-stat-label"><?php _e('Total Purchases', 'skylearn-billing-pro'); ?></span>
                </div>
            </div>
            
            <div class="skylearn-stat-card">
                <div class="skylearn-stat-icon" aria-hidden="true">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                        <path d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="skylearn-stat-content">
                    <span class="skylearn-stat-number"><?php echo esc_html($dashboard_data['stats']['active_courses']); ?></span>
                    <span class="skylearn-stat-label"><?php _e('Active Courses', 'skylearn-billing-pro'); ?></span>
                </div>
            </div>
            
            <div class="skylearn-stat-card">
                <div class="skylearn-stat-icon" aria-hidden="true">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="skylearn-stat-content">
                    <span class="skylearn-stat-number"><?php echo esc_html($dashboard_data['stats']['completion_rate']); ?>%</span>
                    <span class="skylearn-stat-label"><?php _e('Completion Rate', 'skylearn-billing-pro'); ?></span>
                </div>
            </div>
            
            <div class="skylearn-stat-card">
                <div class="skylearn-stat-icon" aria-hidden="true">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none">
                        <path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" fill="currentColor"/>
                    </svg>
                </div>
                <div class="skylearn-stat-content">
                    <span class="skylearn-stat-number"><?php echo esc_html(number_format($dashboard_data['stats']['loyalty_points'])); ?></span>
                    <span class="skylearn-stat-label"><?php _e('Loyalty Points', 'skylearn-billing-pro'); ?></span>
                </div>
            </div>
        </div>
    </section>
    
    <!-- Main Dashboard Content -->
    <div class="skylearn-dashboard-content">
        
        <!-- Recent Orders -->
        <section class="skylearn-dashboard-section" aria-labelledby="recent-orders-title">
            <div class="skylearn-section-header">
                <h2 id="recent-orders-title" class="skylearn-section-title">
                    <?php _e('Recent Orders', 'skylearn-billing-pro'); ?>
                </h2>
                <a href="<?php echo esc_url(get_permalink(get_option('skylearn_billing_pro_pages')['portal_orders'] ?? '')); ?>" class="skylearn-view-all-link">
                    <?php _e('View All', 'skylearn-billing-pro'); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </a>
            </div>
            
            <div class="skylearn-orders-container">
                <?php if (!empty($dashboard_data['recent_orders'])): ?>
                    <?php foreach ($dashboard_data['recent_orders'] as $order): ?>
                        <div class="skylearn-order-item">
                            <div class="skylearn-order-info">
                                <h3 class="skylearn-order-title"><?php echo esc_html($order['name']); ?></h3>
                                <div class="skylearn-order-meta">
                                    <span class="skylearn-order-id"><?php echo esc_html($order['id']); ?></span>
                                    <span class="skylearn-order-date"><?php echo esc_html(date('M j, Y', strtotime($order['date']))); ?></span>
                                </div>
                            </div>
                            <div class="skylearn-order-status-amount">
                                <span class="skylearn-status skylearn-status-<?php echo esc_attr($order['status']); ?>">
                                    <?php echo esc_html(ucfirst($order['status'])); ?>
                                </span>
                                <span class="skylearn-order-amount"><?php echo esc_html($order['amount']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="skylearn-empty-state">
                        <div class="skylearn-empty-icon" aria-hidden="true">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                                <path d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                        </div>
                        <h3><?php _e('No Orders Yet', 'skylearn-billing-pro'); ?></h3>
                        <p><?php _e('Your order history will appear here once you make your first purchase.', 'skylearn-billing-pro'); ?></p>
                        <a href="<?php echo esc_url(home_url()); ?>" class="skylearn-button skylearn-button-primary">
                            <?php _e('Browse Courses', 'skylearn-billing-pro'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Active Subscriptions -->  
        <section class="skylearn-dashboard-section" aria-labelledby="subscriptions-title">
            <div class="skylearn-section-header">
                <h2 id="subscriptions-title" class="skylearn-section-title">
                    <?php _e('Active Subscriptions', 'skylearn-billing-pro'); ?>
                </h2>
                <a href="<?php echo esc_url(get_permalink(get_option('skylearn_billing_pro_pages')['portal_plans'] ?? '')); ?>" class="skylearn-view-all-link">
                    <?php _e('Manage Plans', 'skylearn-billing-pro'); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </a>
            </div>
            
            <div class="skylearn-subscriptions-container">
                <?php if (!empty($dashboard_data['active_subscriptions'])): ?>
                    <?php foreach ($dashboard_data['active_subscriptions'] as $subscription): ?>
                        <div class="skylearn-subscription-item">
                            <div class="skylearn-subscription-info">
                                <h3 class="skylearn-subscription-name"><?php echo esc_html($subscription['name']); ?></h3>
                                <div class="skylearn-subscription-meta">
                                    <span class="skylearn-subscription-amount"><?php echo esc_html($subscription['amount']); ?>/month</span>
                                    <span class="skylearn-subscription-next"><?php printf(__('Next payment: %s', 'skylearn-billing-pro'), esc_html(date('M j, Y', strtotime($subscription['next_payment'])))); ?></span>
                                </div>
                            </div>
                            <div class="skylearn-subscription-actions">
                                <span class="skylearn-status skylearn-status-<?php echo esc_attr($subscription['status']); ?>">
                                    <?php echo esc_html(ucfirst($subscription['status'])); ?>
                                </span>
                                <button type="button" class="skylearn-button skylearn-button-small skylearn-button-secondary">
                                    <?php _e('Manage', 'skylearn-billing-pro'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="skylearn-empty-state">
                        <div class="skylearn-empty-icon" aria-hidden="true">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                                <path d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                        </div>
                        <h3><?php _e('No Active Subscriptions', 'skylearn-billing-pro'); ?></h3>
                        <p><?php _e('Subscribe to a plan to get access to premium features and courses.', 'skylearn-billing-pro'); ?></p>
                        <a href="#" class="skylearn-button skylearn-button-primary">
                            <?php _e('View Plans', 'skylearn-billing-pro'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
        <!-- Available Downloads -->
        <section class="skylearn-dashboard-section" aria-labelledby="downloads-title">
            <div class="skylearn-section-header">
                <h2 id="downloads-title" class="skylearn-section-title">
                    <?php _e('Available Downloads', 'skylearn-billing-pro'); ?>
                </h2>
                <a href="<?php echo esc_url(get_permalink(get_option('skylearn_billing_pro_pages')['portal_downloads'] ?? '')); ?>" class="skylearn-view-all-link">
                    <?php _e('View All', 'skylearn-billing-pro'); ?>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M9 18l6-6-6-6" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </a>
            </div>
            
            <div class="skylearn-downloads-container">
                <?php if (!empty($dashboard_data['available_downloads'])): ?>
                    <?php foreach ($dashboard_data['available_downloads'] as $download): ?>
                        <div class="skylearn-download-item">
                            <div class="skylearn-download-icon" aria-hidden="true">
                                <?php 
                                $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#3b82f6"/><polyline points="14,2 14,8 20,8" fill="#fff"/></svg>';
                                if ($download['type'] === 'pdf') {
                                    $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#dc2626"/><polyline points="14,2 14,8 20,8" fill="#fff"/></svg>';
                                } elseif ($download['type'] === 'zip') {
                                    $icon = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" fill="#f59e0b"/><polyline points="14,2 14,8 20,8" fill="#fff"/></svg>';
                                }
                                echo $icon;
                                ?>
                            </div>
                            <div class="skylearn-download-info">
                                <h3 class="skylearn-download-name"><?php echo esc_html($download['name']); ?></h3>
                                <div class="skylearn-download-meta">
                                    <span class="skylearn-download-size"><?php echo esc_html($download['size']); ?></span>
                                    <span class="skylearn-download-type"><?php echo esc_html(strtoupper($download['type'])); ?></span>
                                </div>
                            </div>
                            <a href="<?php echo esc_url($download['url']); ?>" class="skylearn-button skylearn-button-small skylearn-button-primary" download>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <?php _e('Download', 'skylearn-billing-pro'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="skylearn-empty-state">
                        <div class="skylearn-empty-icon" aria-hidden="true">
                            <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" stroke="currentColor" stroke-width="1.5"/>
                            </svg>
                        </div>
                        <h3><?php _e('No Downloads Available', 'skylearn-billing-pro'); ?></h3>
                        <p><?php _e('Downloadable resources will appear here when you purchase courses that include them.', 'skylearn-billing-pro'); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
        
    </div>
    
    <!-- Quick Actions -->
    <aside class="skylearn-quick-actions" aria-labelledby="quick-actions-title">
        <h2 id="quick-actions-title" class="skylearn-section-title"><?php _e('Quick Actions', 'skylearn-billing-pro'); ?></h2>
        
        <div class="skylearn-actions-grid">
            <a href="<?php echo esc_url(get_permalink(get_option('skylearn_billing_pro_pages')['portal_account'] ?? '')); ?>" class="skylearn-action-card">
                <div class="skylearn-action-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="skylearn-action-content">
                    <h3><?php _e('Account Settings', 'skylearn-billing-pro'); ?></h3>
                    <p><?php _e('Update your profile and preferences', 'skylearn-billing-pro'); ?></p>
                </div>
            </a>
            
            <a href="<?php echo esc_url(get_permalink(get_option('skylearn_billing_pro_pages')['portal_addresses'] ?? '')); ?>" class="skylearn-action-card">
                <div class="skylearn-action-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" stroke="currentColor" stroke-width="2"/>
                        <path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="skylearn-action-content">
                    <h3><?php _e('Manage Addresses', 'skylearn-billing-pro'); ?></h3>
                    <p><?php _e('Update billing and shipping addresses', 'skylearn-billing-pro'); ?></p>
                </div>
            </a>
            
            <a href="#" class="skylearn-action-card">
                <div class="skylearn-action-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="skylearn-action-content">
                    <h3><?php _e('Get Support', 'skylearn-billing-pro'); ?></h3>
                    <p><?php _e('Contact our support team for help', 'skylearn-billing-pro'); ?></p>
                </div>
            </a>
            
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>" class="skylearn-action-card">
                <div class="skylearn-action-icon" aria-hidden="true">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" stroke="currentColor" stroke-width="2"/>
                    </svg>
                </div>
                <div class="skylearn-action-content">
                    <h3><?php _e('Sign Out', 'skylearn-billing-pro'); ?></h3>
                    <p><?php _e('Securely log out of your account', 'skylearn-billing-pro'); ?></p>
                </div>
            </a>
        </div>
    </aside>
</div>

<style>
/* Portal Dashboard Styles */
.skylearn-portal-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.skylearn-dashboard-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding: 30px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 16px;
    color: white;
}

.skylearn-welcome-section h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 700;
}

.skylearn-dashboard-subtitle {
    margin: 0;
    font-size: 16px;
    opacity: 0.9;
}

.skylearn-user-avatar .skylearn-avatar-img {
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
}

.skylearn-stats-section {
    margin-bottom: 40px;
}

.skylearn-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.skylearn-stat-card {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s ease;
}

.skylearn-stat-card:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    transform: translateY(-2px);
}

.skylearn-stat-icon {
    width: 48px;
    height: 48px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.skylearn-stat-content {
    display: flex;
    flex-direction: column;
}

.skylearn-stat-number {
    font-size: 28px;
    font-weight: 700;
    color: #111827;
    line-height: 1;
}

.skylearn-stat-label {
    font-size: 14px;
    color: #6b7280;
    margin-top: 4px;
}

.skylearn-dashboard-content {
    display: grid;
    gap: 30px;
    margin-bottom: 40px;
}

.skylearn-dashboard-section {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 30px;
}

.skylearn-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.skylearn-section-title {
    font-size: 20px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.skylearn-view-all-link {
    display: flex;
    align-items: center;
    gap: 4px;
    color: #3b82f6;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.skylearn-view-all-link:hover {
    color: #1d4ed8;
    text-decoration: none;
}

.skylearn-orders-container,
.skylearn-subscriptions-container,
.skylearn-downloads-container {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.skylearn-order-item,
.skylearn-subscription-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.skylearn-order-item:hover,
.skylearn-subscription-item:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.skylearn-order-info,
.skylearn-subscription-info {
    flex: 1;
}

.skylearn-order-title,
.skylearn-subscription-name {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 5px 0;
}

.skylearn-order-meta,
.skylearn-subscription-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #6b7280;
}

.skylearn-order-status-amount,
.skylearn-subscription-actions {
    display: flex;
    align-items: center;
    gap: 15px;
}

.skylearn-status {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.skylearn-status-completed,
.skylearn-status-active {
    background: #d1fae5;
    color: #065f46;
}

.skylearn-status-pending {
    background: #fef3c7;
    color: #92400e;
}

.skylearn-status-cancelled {
    background: #fee2e2;
    color: #991b1b;
}

.skylearn-order-amount {
    font-weight: 600;
    color: #059669;
}

.skylearn-download-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    transition: all 0.2s ease;
}

.skylearn-download-item:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.skylearn-download-icon {
    width: 48px;
    height: 48px;
    background: #e5e7eb;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.skylearn-download-info {
    flex: 1;
}

.skylearn-download-name {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 5px 0;
}

.skylearn-download-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #6b7280;
}

.skylearn-button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s ease;
}

.skylearn-button-primary {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
}

.skylearn-button-primary:hover {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    transform: translateY(-1px);
    color: white;
    text-decoration: none;
}

.skylearn-button-secondary {
    background: #f3f4f6;
    color: #374151;
    border: 1px solid #d1d5db;
}

.skylearn-button-secondary:hover {
    background: #e5e7eb;
    color: #374151;
    text-decoration: none;
}

.skylearn-button-small {
    padding: 6px 12px;
    font-size: 12px;
}

.skylearn-empty-state {
    text-align: center;
    padding: 40px 20px;
}

.skylearn-empty-icon {
    width: 64px;
    height: 64px;
    margin: 0 auto 20px auto;  
    color: #9ca3af;
}

.skylearn-empty-state h3 {
    font-size: 18px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 8px 0;
}

.skylearn-empty-state p {
    color: #6b7280;
    margin: 0 0 20px 0;
}

.skylearn-quick-actions {
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    padding: 30px;
}

.skylearn-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.skylearn-action-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
}

.skylearn-action-card:hover {
    background: #f3f4f6;
    border-color: #3b82f6;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.1);
    text-decoration: none;
    color: inherit;
}

.skylearn-action-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.skylearn-action-content h3 {
    font-size: 16px;
    font-weight: 600;
    color: #111827;
    margin: 0 0 4px 0;
}

.skylearn-action-content p {
    font-size: 14px;
    color: #6b7280;
    margin: 0;
}

.skylearn-sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .skylearn-portal-dashboard {
        padding: 10px;
    }
    
    .skylearn-dashboard-header {
        flex-direction: column;
        text-align: center;
        gap: 20px;
        padding: 20px;
    }
    
    .skylearn-welcome-section h1 {
        font-size: 24px;
    }
    
    .skylearn-stats-grid {
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }
    
    .skylearn-stat-card {
        padding: 20px;
    }
    
    .skylearn-stat-number {
        font-size: 24px;
    }
    
    .skylearn-dashboard-section {
        padding: 20px;
    }
    
    .skylearn-section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .skylearn-order-item,
    .skylearn-subscription-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .skylearn-order-status-amount,
    .skylearn-subscription-actions {
        width: 100%;
        justify-content: space-between;
    }
    
    .skylearn-download-item {
        flex-direction: column;
        text-align: center;
    }
    
    .skylearn-actions-grid {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .skylearn-action-card {
        padding: 15px;
    }
}

/* High contrast support */
@media (prefers-contrast: high) {
    .skylearn-dashboard-section,
    .skylearn-quick-actions,
    .skylearn-stat-card,
    .skylearn-order-item,
    .skylearn-subscription-item,
    .skylearn-download-item,
    .skylearn-action-card {
        border: 2px solid #000000;
    }
    
    .skylearn-button-primary {
        background: #000000;
        border: 2px solid #000000;
    }
}

/* Reduced motion */
@media (prefers-reduced-motion: reduce) {
    .skylearn-stat-card,
    .skylearn-order-item,
    .skylearn-subscription-item,
    .skylearn-download-item,
    .skylearn-action-card,
    .skylearn-button {
        transition: none;
    }
    
    .skylearn-stat-card:hover,
    .skylearn-action-card:hover,
    .skylearn-button:hover {
        transform: none;
    }
}
</style>