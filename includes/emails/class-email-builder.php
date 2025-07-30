<?php
/**
 * Email Builder for Skylearn Billing Pro
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

/**
 * Email Builder class
 */
class SkyLearn_Billing_Pro_Email_Builder {
    
    /**
     * Available builder blocks
     */
    const BUILDER_BLOCKS = [
        'header' => [
            'name' => 'Header',
            'icon' => 'dashicons-heading',
            'category' => 'content'
        ],
        'text' => [
            'name' => 'Text',
            'icon' => 'dashicons-text',
            'category' => 'content'
        ],
        'button' => [
            'name' => 'Button',
            'icon' => 'dashicons-button',
            'category' => 'content'
        ],
        'image' => [
            'name' => 'Image',
            'icon' => 'dashicons-format-image',
            'category' => 'media'
        ],
        'divider' => [
            'name' => 'Divider',
            'icon' => 'dashicons-minus',
            'category' => 'layout'
        ],
        'spacer' => [
            'name' => 'Spacer',
            'icon' => 'dashicons-ellipsis',
            'category' => 'layout'
        ],
        'columns' => [
            'name' => 'Columns',
            'icon' => 'dashicons-columns',
            'category' => 'layout'
        ],
        'footer' => [
            'name' => 'Footer',
            'icon' => 'dashicons-admin-generic',
            'category' => 'content'
        ]
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_skylearn_email_builder_save', array($this, 'ajax_save_builder_template'));
        add_action('wp_ajax_skylearn_email_builder_load', array($this, 'ajax_load_builder_template'));
        add_action('wp_ajax_skylearn_email_builder_preview', array($this, 'ajax_preview_builder_template'));
        add_action('wp_ajax_skylearn_email_builder_export', array($this, 'ajax_export_template'));
        add_action('wp_ajax_skylearn_email_builder_import', array($this, 'ajax_import_template'));
    }
    
    /**
     * Render email builder interface
     */
    public function render_builder($email_type, $language = 'en') {
        ?>
        <div id="skylearn-email-builder" class="skylearn-email-builder-container">
            <!-- Builder Toolbar -->
            <div class="skylearn-builder-toolbar">
                <div class="skylearn-toolbar-left">
                    <button type="button" class="skylearn-btn skylearn-btn-secondary" id="skylearn-builder-back">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php esc_html_e('Back to Templates', 'skylearn-billing-pro'); ?>
                    </button>
                    <h3 class="skylearn-builder-title">
                        <?php printf(esc_html__('Editing: %s', 'skylearn-billing-pro'), esc_html(ucwords(str_replace('_', ' ', $email_type)))); ?>
                    </h3>
                </div>
                
                <div class="skylearn-toolbar-center">
                    <div class="skylearn-device-preview">
                        <button type="button" class="skylearn-device-btn active" data-device="desktop">
                            <span class="dashicons dashicons-desktop"></span>
                        </button>
                        <button type="button" class="skylearn-device-btn" data-device="tablet">
                            <span class="dashicons dashicons-tablet"></span>
                        </button>
                        <button type="button" class="skylearn-device-btn" data-device="mobile">
                            <span class="dashicons dashicons-smartphone"></span>
                        </button>
                    </div>
                </div>
                
                <div class="skylearn-toolbar-right">
                    <button type="button" class="skylearn-btn skylearn-btn-secondary" id="skylearn-builder-preview">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Preview', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" class="skylearn-btn skylearn-btn-secondary" id="skylearn-builder-test">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php esc_html_e('Send Test', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" class="skylearn-btn skylearn-btn-primary" id="skylearn-builder-save">
                        <span class="dashicons dashicons-saved"></span>
                        <?php esc_html_e('Save Template', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
            
            <!-- Builder Content -->
            <div class="skylearn-builder-content">
                <!-- Sidebar with blocks -->
                <div class="skylearn-builder-sidebar">
                    <div class="skylearn-sidebar-header">
                        <h4><?php esc_html_e('Email Blocks', 'skylearn-billing-pro'); ?></h4>
                    </div>
                    
                    <div class="skylearn-block-categories">
                        <div class="skylearn-block-category">
                            <h5><?php esc_html_e('Content', 'skylearn-billing-pro'); ?></h5>
                            <div class="skylearn-blocks-grid">
                                <?php $this->render_blocks_by_category('content'); ?>
                            </div>
                        </div>
                        
                        <div class="skylearn-block-category">
                            <h5><?php esc_html_e('Media', 'skylearn-billing-pro'); ?></h5>
                            <div class="skylearn-blocks-grid">
                                <?php $this->render_blocks_by_category('media'); ?>
                            </div>
                        </div>
                        
                        <div class="skylearn-block-category">
                            <h5><?php esc_html_e('Layout', 'skylearn-billing-pro'); ?></h5>
                            <div class="skylearn-blocks-grid">
                                <?php $this->render_blocks_by_category('layout'); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="skylearn-tokens-panel">
                        <h5><?php esc_html_e('Dynamic Tokens', 'skylearn-billing-pro'); ?></h5>
                        <div class="skylearn-tokens-list">
                            <?php $this->render_dynamic_tokens($email_type); ?>
                        </div>
                    </div>
                </div>
                
                <!-- Canvas -->
                <div class="skylearn-builder-canvas">
                    <div class="skylearn-canvas-wrapper" data-device="desktop">
                        <div class="skylearn-canvas-header">
                            <div class="skylearn-subject-editor">
                                <label for="email-subject"><?php esc_html_e('Subject:', 'skylearn-billing-pro'); ?></label>
                                <input type="text" id="email-subject" class="skylearn-subject-input" placeholder="<?php esc_attr_e('Enter email subject...', 'skylearn-billing-pro'); ?>" />
                            </div>
                        </div>
                        
                        <div class="skylearn-canvas-body">
                            <div id="skylearn-email-canvas" class="skylearn-email-canvas" data-email-type="<?php echo esc_attr($email_type); ?>" data-language="<?php echo esc_attr($language); ?>">
                                <!-- Email blocks will be added here -->
                                <div class="skylearn-canvas-placeholder">
                                    <div class="skylearn-placeholder-content">
                                        <span class="dashicons dashicons-email-alt"></span>
                                        <h4><?php esc_html_e('Start Building Your Email', 'skylearn-billing-pro'); ?></h4>
                                        <p><?php esc_html_e('Drag blocks from the sidebar to start creating your email template.', 'skylearn-billing-pro'); ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Properties Panel -->
                <div class="skylearn-builder-properties">
                    <div class="skylearn-properties-header">
                        <h4><?php esc_html_e('Block Properties', 'skylearn-billing-pro'); ?></h4>
                    </div>
                    
                    <div class="skylearn-properties-content">
                        <div class="skylearn-no-selection">
                            <span class="dashicons dashicons-admin-settings"></span>
                            <p><?php esc_html_e('Select a block to edit its properties', 'skylearn-billing-pro'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Block Templates -->
        <div id="skylearn-block-templates" style="display: none;">
            <?php $this->render_block_templates(); ?>
        </div>
        
        <!-- Property Templates -->
        <div id="skylearn-property-templates" style="display: none;">
            <?php $this->render_property_templates(); ?>
        </div>
        
        <!-- Preview Modal -->
        <div id="skylearn-preview-modal" class="skylearn-modal" style="display: none;">
            <div class="skylearn-modal-content skylearn-preview-modal-content">
                <div class="skylearn-modal-header">
                    <h3><?php esc_html_e('Email Preview', 'skylearn-billing-pro'); ?></h3>
                    <button type="button" class="skylearn-modal-close">&times;</button>
                </div>
                <div class="skylearn-modal-body">
                    <div class="skylearn-preview-container">
                        <iframe id="skylearn-preview-frame" class="skylearn-preview-frame"></iframe>
                    </div>
                </div>
                <div class="skylearn-modal-footer">
                    <button type="button" class="button" id="skylearn-close-preview">
                        <?php esc_html_e('Close', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Test Email Modal -->
        <div id="skylearn-test-email-modal" class="skylearn-modal" style="display: none;">
            <div class="skylearn-modal-content">
                <div class="skylearn-modal-header">
                    <h3><?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?></h3>
                    <button type="button" class="skylearn-modal-close">&times;</button>
                </div>
                <div class="skylearn-modal-body">
                    <label for="test-email-address">
                        <?php esc_html_e('Email Address:', 'skylearn-billing-pro'); ?>
                    </label>
                    <input type="email" id="test-email-address" class="large-text" value="<?php echo esc_attr(get_option('admin_email')); ?>" />
                    <p class="description"><?php esc_html_e('Enter the email address where you want to send the test email.', 'skylearn-billing-pro'); ?></p>
                </div>
                <div class="skylearn-modal-footer">
                    <button type="button" class="button button-primary" id="skylearn-send-test-email-btn">
                        <?php esc_html_e('Send Test Email', 'skylearn-billing-pro'); ?>
                    </button>
                    <button type="button" class="button" id="skylearn-cancel-test-email">
                        <?php esc_html_e('Cancel', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render blocks by category
     */
    private function render_blocks_by_category($category) {
        foreach (self::BUILDER_BLOCKS as $block_type => $block_info) {
            if ($block_info['category'] === $category) {
                ?>
                <div class="skylearn-block-item" data-block-type="<?php echo esc_attr($block_type); ?>" draggable="true">
                    <span class="dashicons <?php echo esc_attr($block_info['icon']); ?>"></span>
                    <span class="skylearn-block-name"><?php echo esc_html($block_info['name']); ?></span>
                </div>
                <?php
            }
        }
    }
    
    /**
     * Render dynamic tokens
     */
    private function render_dynamic_tokens($email_type) {
        $tokens = $this->get_available_tokens($email_type);
        
        foreach ($tokens as $token => $description) {
            ?>
            <div class="skylearn-token-item" data-token="{{<?php echo esc_attr($token); ?>}}">
                <code>{{<?php echo esc_html($token); ?>}}</code>
                <span class="skylearn-token-desc"><?php echo esc_html($description); ?></span>
            </div>
            <?php
        }
    }
    
    /**
     * Get available tokens for email type
     */
    private function get_available_tokens($email_type) {
        $common_tokens = [
            'site_name' => __('Website name', 'skylearn-billing-pro'),
            'site_url' => __('Website URL', 'skylearn-billing-pro'),
            'current_date' => __('Current date', 'skylearn-billing-pro'),
            'current_time' => __('Current time', 'skylearn-billing-pro'),
            'username' => __('User login name', 'skylearn-billing-pro'),
            'user_email' => __('User email address', 'skylearn-billing-pro'),
            'display_name' => __('User display name', 'skylearn-billing-pro')
        ];
        
        $type_specific_tokens = [
            'welcome' => [
                'password' => __('User password', 'skylearn-billing-pro'),
                'login_url' => __('Login page URL', 'skylearn-billing-pro')
            ],
            'order_confirmation' => [
                'order_id' => __('Order ID', 'skylearn-billing-pro'),
                'order_total' => __('Order total amount', 'skylearn-billing-pro'),
                'product_name' => __('Product name', 'skylearn-billing-pro'),
                'customer_name' => __('Customer name', 'skylearn-billing-pro')
            ],
            'invoice' => [
                'invoice_id' => __('Invoice ID', 'skylearn-billing-pro'),
                'invoice_total' => __('Invoice total', 'skylearn-billing-pro'),
                'due_date' => __('Due date', 'skylearn-billing-pro')
            ],
            'payment_confirmation' => [
                'order_id' => __('Order ID', 'skylearn-billing-pro'),
                'amount' => __('Payment amount', 'skylearn-billing-pro'),
                'payment_method' => __('Payment method', 'skylearn-billing-pro'),
                'transaction_id' => __('Transaction ID', 'skylearn-billing-pro')
            ],
            'course_enrollment' => [
                'course_title' => __('Course title', 'skylearn-billing-pro'),
                'course_url' => __('Course URL', 'skylearn-billing-pro'),
                'instructor_name' => __('Instructor name', 'skylearn-billing-pro')
            ],
            'subscription_created' => [
                'subscription_id' => __('Subscription ID', 'skylearn-billing-pro'),
                'plan_name' => __('Plan name', 'skylearn-billing-pro'),
                'amount' => __('Subscription amount', 'skylearn-billing-pro'),
                'billing_cycle' => __('Billing cycle', 'skylearn-billing-pro')
            ]
        ];
        
        return array_merge($common_tokens, $type_specific_tokens[$email_type] ?? []);
    }
    
    /**
     * Render block templates (simplified for brevity)
     */
    private function render_block_templates() {
        // Block templates would be rendered here
        // This is a simplified implementation
        echo '<!-- Block templates will be implemented via JavaScript -->';
    }
    
    /**
     * Render property templates (simplified for brevity)
     */
    private function render_property_templates() {
        // Property templates would be rendered here
        // This is a simplified implementation  
        echo '<!-- Property templates will be implemented via JavaScript -->';
    }
    
    /**
     * AJAX: Save builder template
     */
    public function ajax_save_builder_template() {
        check_ajax_referer('skylearn_email_builder', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $email_type = sanitize_text_field($_POST['email_type']);
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        $subject = sanitize_text_field($_POST['subject']);
        $blocks = json_decode(stripslashes($_POST['blocks']), true);
        
        // Generate HTML from blocks
        $content = $this->generate_html_from_blocks($blocks);
        
        $template_data = array(
            'subject' => $subject,
            'content' => $content,
            'blocks' => $blocks,
            'enabled' => true,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $email_manager = skylearn_billing_pro_email();
        $result = $email_manager->save_email_template($email_type, $template_data, $language);
        
        if ($result) {
            wp_send_json_success(__('Email template saved successfully!', 'skylearn-billing-pro'));
        } else {
            wp_send_json_error(__('Failed to save email template.', 'skylearn-billing-pro'));
        }
    }
    
    /**
     * AJAX: Load builder template
     */
    public function ajax_load_builder_template() {
        check_ajax_referer('skylearn_email_builder', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $email_type = sanitize_text_field($_POST['email_type']);
        $language = sanitize_text_field($_POST['language'] ?? 'en');
        
        $email_manager = skylearn_billing_pro_email();
        $template = $email_manager->get_email_template($email_type, $language);
        
        // If no saved template, generate blocks from default content
        if (empty($template['blocks'])) {
            $template['blocks'] = $this->generate_blocks_from_html($template['content'] ?? '');
        }
        
        wp_send_json_success($template);
    }
    
    /**
     * AJAX: Preview builder template
     */
    public function ajax_preview_builder_template() {
        check_ajax_referer('skylearn_email_builder', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.', 'skylearn-billing-pro'));
        }
        
        $email_type = sanitize_text_field($_POST['email_type']);
        $subject = sanitize_text_field($_POST['subject']);
        $blocks = json_decode(stripslashes($_POST['blocks']), true);
        
        // Generate HTML from blocks
        $content = $this->generate_html_from_blocks($blocks);
        
        // Generate preview with sample data
        $email_manager = skylearn_billing_pro_email();
        $sample_data = $this->get_sample_data($email_type);
        
        $preview_content = $email_manager->replace_tokens($content, $sample_data);
        $preview_subject = $email_manager->replace_tokens($subject, $sample_data);
        
        wp_send_json_success(array(
            'subject' => $preview_subject,
            'content' => $preview_content
        ));
    }
    
    /**
     * Generate HTML from blocks
     */
    private function generate_html_from_blocks($blocks) {
        if (empty($blocks)) {
            return '';
        }
        
        $html = '<div style="max-width: 600px; margin: 0 auto; font-family: Arial, sans-serif; line-height: 1.6;">';
        
        foreach ($blocks as $block) {
            $html .= $this->generate_block_html($block);
        }
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Generate HTML for individual block
     */
    private function generate_block_html($block) {
        $type = $block['type'];
        $settings = $block['settings'] ?? array();
        
        switch ($type) {
            case 'header':
                return sprintf(
                    '<h1 style="font-size: %s; color: %s; text-align: %s; margin: 20px 0;">%s</h1>',
                    esc_attr($settings['fontSize'] ?? '32px'),
                    esc_attr($settings['color'] ?? '#333333'),
                    esc_attr($settings['textAlign'] ?? 'left'),
                    esc_html($settings['content'] ?? 'Your Heading Here')
                );
                
            case 'text':
                return sprintf(
                    '<div style="font-size: %s; color: %s; line-height: %s; margin: 15px 0;">%s</div>',
                    esc_attr($settings['fontSize'] ?? '16px'),
                    esc_attr($settings['color'] ?? '#333333'),
                    esc_attr($settings['lineHeight'] ?? '1.6'),
                    wp_kses_post($settings['content'] ?? 'Enter your text here.')
                );
                
            case 'button':
                return sprintf(
                    '<div style="text-align: center; margin: 20px 0;"><a href="%s" style="display: inline-block; background: %s; color: %s; padding: 12px 24px; text-decoration: none; border-radius: %s;">%s</a></div>',
                    esc_url($settings['url'] ?? '#'),
                    esc_attr($settings['backgroundColor'] ?? '#0073aa'),
                    esc_attr($settings['textColor'] ?? '#ffffff'),
                    esc_attr($settings['borderRadius'] ?? '4px'),
                    esc_html($settings['text'] ?? 'Button Text')
                );
                
            case 'image':
                return sprintf(
                    '<div style="text-align: %s; margin: 20px 0;"><img src="%s" alt="%s" style="max-width: %s; height: auto;" /></div>',
                    esc_attr($settings['align'] ?? 'center'),
                    esc_url($settings['src'] ?? ''),
                    esc_attr($settings['alt'] ?? ''),
                    esc_attr($settings['width'] ?? '100%')
                );
                
            case 'divider':
                return sprintf(
                    '<hr style="border: none; border-top: %s %s %s; margin: 20px 0;" />',
                    esc_attr($settings['borderWidth'] ?? '1px'),
                    esc_attr($settings['borderStyle'] ?? 'solid'),
                    esc_attr($settings['borderColor'] ?? '#dddddd')
                );
                
            case 'spacer':
                return sprintf(
                    '<div style="height: %s;"></div>',
                    esc_attr($settings['height'] ?? '40px')
                );
                
            case 'footer':
                return sprintf(
                    '<div style="text-align: center; font-size: 12px; color: #666; margin-top: 40px; padding-top: 20px; border-top: 1px solid #eee;">%s</div>',
                    wp_kses_post($settings['content'] ?? '<p>© {{current_year}} {{site_name}}. All rights reserved.</p>')
                );
                
            default:
                return '';
        }
    }
    
    /**
     * Generate blocks from HTML (basic implementation)
     */
    private function generate_blocks_from_html($html) {
        // This is a simplified implementation
        // In a production environment, you'd want a more robust HTML parser
        $blocks = array();
        
        // Simple pattern matching for common elements
        if (preg_match('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $html, $matches)) {
            $blocks[] = array(
                'type' => 'header',
                'settings' => array(
                    'content' => strip_tags($matches[1])
                )
            );
        }
        
        if (preg_match_all('/<p[^>]*>(.*?)<\/p>/i', $html, $matches)) {
            foreach ($matches[1] as $text) {
                $blocks[] = array(
                    'type' => 'text',
                    'settings' => array(
                        'content' => strip_tags($text)
                    )
                );
            }
        }
        
        // If no blocks found, create a default text block
        if (empty($blocks)) {
            $blocks[] = array(
                'type' => 'text',
                'settings' => array(
                    'content' => strip_tags($html)
                )
            );
        }
        
        return $blocks;
    }
    
    /**
     * Get sample data for email previews
     */
    private function get_sample_data($type) {
        // Use the same method from the main email class
        $email_manager = skylearn_billing_pro_email();
        return $email_manager->get_sample_data($type);
    }
}

/**
 * Get the Email Builder instance
 *
 * @return SkyLearn_Billing_Pro_Email_Builder
 */
function skylearn_billing_pro_email_builder() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new SkyLearn_Billing_Pro_Email_Builder();
    }
    
    return $instance;
}