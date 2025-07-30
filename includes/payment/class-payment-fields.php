<?php
/**
 * Payment Fields class for managing customizable checkout fields
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
 * Payment Fields class
 */
class SkyLearn_Billing_Pro_Payment_Fields {
    
    /**
     * Default field types
     *
     * @var array
     */
    private $field_types = array(
        'text' => array(
            'name' => 'Text Field',
            'description' => 'Single line text input',
            'icon' => 'dashicons-editor-textcolor'
        ),
        'email' => array(
            'name' => 'Email Field',
            'description' => 'Email address input with validation',
            'icon' => 'dashicons-email'
        ),
        'tel' => array(
            'name' => 'Phone Field',
            'description' => 'Phone number input',
            'icon' => 'dashicons-phone'
        ),
        'textarea' => array(
            'name' => 'Textarea',
            'description' => 'Multi-line text input',
            'icon' => 'dashicons-editor-alignleft'
        ),
        'select' => array(
            'name' => 'Dropdown',
            'description' => 'Select from predefined options',
            'icon' => 'dashicons-arrow-down-alt2'
        ),
        'radio' => array(
            'name' => 'Radio Buttons',
            'description' => 'Choose one from multiple options',
            'icon' => 'dashicons-marker'
        ),
        'checkbox' => array(
            'name' => 'Checkbox',
            'description' => 'Single checkbox for yes/no',
            'icon' => 'dashicons-yes'
        ),
        'country' => array(
            'name' => 'Country Selector',
            'description' => 'Dropdown with countries',
            'icon' => 'dashicons-admin-site'
        ),
        'hidden' => array(
            'name' => 'Hidden Field',
            'description' => 'Hidden field for tracking data',
            'icon' => 'dashicons-visibility'
        )
    );
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_ajax_skylearn_save_payment_fields', array($this, 'ajax_save_fields'));
        add_action('wp_ajax_skylearn_delete_payment_field', array($this, 'ajax_delete_field'));
    }
    
    /**
     * Initialize payment fields
     */
    public function init() {
        // Initialize field management
    }
    
    /**
     * Get available field types
     *
     * @return array
     */
    public function get_field_types() {
        return apply_filters('skylearn_billing_pro_field_types', $this->field_types);
    }
    
    /**
     * Get custom fields for a gateway
     *
     * @param string $gateway_id Gateway ID
     * @param string $product_id Product ID (optional)
     * @return array
     */
    public function get_gateway_fields($gateway_id, $product_id = '') {
        $options = get_option('skylearn_billing_pro_options', array());
        $fields = isset($options['payment_fields'][$gateway_id]) ? $options['payment_fields'][$gateway_id] : array();
        
        // Filter fields based on conditional logic
        $filtered_fields = array();
        
        foreach ($fields as $field) {
            if ($this->should_display_field($field, $gateway_id, $product_id)) {
                $filtered_fields[] = $field;
            }
        }
        
        return apply_filters('skylearn_billing_pro_gateway_fields', $filtered_fields, $gateway_id, $product_id);
    }
    
    /**
     * Check if field should be displayed based on conditions
     *
     * @param array $field Field data
     * @param string $gateway_id Gateway ID
     * @param string $product_id Product ID
     * @return bool
     */
    private function should_display_field($field, $gateway_id, $product_id) {
        // Check gateway condition
        if (!empty($field['conditions']['gateways']) && !in_array($gateway_id, $field['conditions']['gateways'])) {
            return false;
        }
        
        // Check product condition
        if (!empty($field['conditions']['products']) && !empty($product_id) && !in_array($product_id, $field['conditions']['products'])) {
            return false;
        }
        
        // Check tier condition
        if (!empty($field['conditions']['tiers'])) {
            $licensing_manager = skylearn_billing_pro_licensing();
            $current_tier = $licensing_manager->get_license_tier();
            
            if (!in_array($current_tier, $field['conditions']['tiers'])) {
                return false;
            }
        }
        
        return apply_filters('skylearn_billing_pro_should_display_field', true, $field, $gateway_id, $product_id);
    }
    
    /**
     * Save payment fields
     *
     * @param string $gateway_id Gateway ID
     * @param array $fields Fields data
     * @return bool
     */
    public function save_gateway_fields($gateway_id, $fields) {
        $options = get_option('skylearn_billing_pro_options', array());
        
        if (!isset($options['payment_fields'])) {
            $options['payment_fields'] = array();
        }
        
        $options['payment_fields'][$gateway_id] = $this->sanitize_fields($fields);
        
        return update_option('skylearn_billing_pro_options', $options);
    }
    
    /**
     * Sanitize fields data
     *
     * @param array $fields Fields data
     * @return array
     */
    private function sanitize_fields($fields) {
        $sanitized = array();
        
        foreach ($fields as $field) {
            $sanitized_field = array(
                'id' => sanitize_key($field['id'] ?? ''),
                'type' => sanitize_key($field['type'] ?? 'text'),
                'label' => sanitize_text_field($field['label'] ?? ''),
                'placeholder' => sanitize_text_field($field['placeholder'] ?? ''),
                'description' => sanitize_text_field($field['description'] ?? ''),
                'required' => (bool) ($field['required'] ?? false),
                'options' => array(),
                'default_value' => sanitize_text_field($field['default_value'] ?? ''),
                'css_class' => sanitize_text_field($field['css_class'] ?? ''),
                'conditions' => array(
                    'gateways' => array_map('sanitize_key', $field['conditions']['gateways'] ?? array()),
                    'products' => array_map('sanitize_key', $field['conditions']['products'] ?? array()),
                    'tiers' => array_map('sanitize_key', $field['conditions']['tiers'] ?? array())
                ),
                'order' => (int) ($field['order'] ?? 0)
            );
            
            // Sanitize options for select/radio fields
            if (isset($field['options']) && is_array($field['options'])) {
                foreach ($field['options'] as $option) {
                    $sanitized_field['options'][] = array(
                        'value' => sanitize_key($option['value'] ?? ''),
                        'label' => sanitize_text_field($option['label'] ?? '')
                    );
                }
            }
            
            $sanitized[] = $sanitized_field;
        }
        
        // Sort by order
        usort($sanitized, function($a, $b) {
            return $a['order'] - $b['order'];
        });
        
        return $sanitized;
    }
    
    /**
     * Render field HTML
     *
     * @param array $field Field data
     * @param mixed $value Current value
     * @return string
     */
    public function render_field($field, $value = '') {
        $field_id = 'skylearn_field_' . $field['id'];
        $field_name = 'skylearn_fields[' . $field['id'] . ']';
        $required_attr = $field['required'] ? 'required' : '';
        $css_class = 'skylearn-checkout-field ' . ($field['css_class'] ?? '');
        
        ob_start();
        ?>
        <div class="skylearn-field-wrapper <?php echo esc_attr($css_class); ?>" data-field-type="<?php echo esc_attr($field['type']); ?>">
            <?php if (!empty($field['label'])): ?>
                <label for="<?php echo esc_attr($field_id); ?>" class="skylearn-field-label">
                    <?php echo esc_html($field['label']); ?>
                    <?php if ($field['required']): ?>
                        <span class="skylearn-required">*</span>
                    <?php endif; ?>
                </label>
            <?php endif; ?>
            
            <?php
            switch ($field['type']) {
                case 'text':
                case 'email':
                case 'tel':
                    ?>
                    <input 
                        type="<?php echo esc_attr($field['type']); ?>"
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_name); ?>"
                        value="<?php echo esc_attr($value ?: $field['default_value']); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                        class="skylearn-input"
                        <?php echo $required_attr; ?>
                    />
                    <?php
                    break;
                    
                case 'textarea':
                    ?>
                    <textarea 
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_name); ?>"
                        placeholder="<?php echo esc_attr($field['placeholder']); ?>"
                        class="skylearn-textarea"
                        rows="4"
                        <?php echo $required_attr; ?>
                    ><?php echo esc_textarea($value ?: $field['default_value']); ?></textarea>
                    <?php
                    break;
                    
                case 'select':
                case 'country':
                    ?>
                    <select 
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_name); ?>"
                        class="skylearn-select"
                        <?php echo $required_attr; ?>
                    >
                        <?php if (!empty($field['placeholder'])): ?>
                            <option value=""><?php echo esc_html($field['placeholder']); ?></option>
                        <?php endif; ?>
                        
                        <?php
                        $options = $field['type'] === 'country' ? $this->get_countries() : $field['options'];
                        foreach ($options as $option):
                            $option_value = $option['value'] ?? $option;
                            $option_label = $option['label'] ?? $option;
                        ?>
                            <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value ?: $field['default_value'], $option_value); ?>>
                                <?php echo esc_html($option_label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php
                    break;
                    
                case 'radio':
                    foreach ($field['options'] as $option):
                        $option_value = $option['value'] ?? $option;
                        $option_label = $option['label'] ?? $option;
                        $option_id = $field_id . '_' . sanitize_key($option_value);
                    ?>
                        <label class="skylearn-radio-label" for="<?php echo esc_attr($option_id); ?>">
                            <input 
                                type="radio"
                                id="<?php echo esc_attr($option_id); ?>"
                                name="<?php echo esc_attr($field_name); ?>"
                                value="<?php echo esc_attr($option_value); ?>"
                                <?php checked($value ?: $field['default_value'], $option_value); ?>
                                <?php echo $required_attr; ?>
                            />
                            <?php echo esc_html($option_label); ?>
                        </label>
                    <?php endforeach;
                    break;
                    
                case 'checkbox':
                    ?>
                    <label class="skylearn-checkbox-label" for="<?php echo esc_attr($field_id); ?>">
                        <input 
                            type="checkbox"
                            id="<?php echo esc_attr($field_id); ?>"
                            name="<?php echo esc_attr($field_name); ?>"
                            value="1"
                            <?php checked($value, 1); ?>
                            <?php echo $required_attr; ?>
                        />
                        <?php echo esc_html($field['description'] ?: $field['label']); ?>
                    </label>
                    <?php
                    break;
                    
                case 'hidden':
                    ?>
                    <input 
                        type="hidden"
                        id="<?php echo esc_attr($field_id); ?>"
                        name="<?php echo esc_attr($field_name); ?>"
                        value="<?php echo esc_attr($value ?: $field['default_value']); ?>"
                    />
                    <?php
                    break;
            }
            ?>
            
            <?php if (!empty($field['description']) && $field['type'] !== 'checkbox'): ?>
                <p class="skylearn-field-description"><?php echo esc_html($field['description']); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Get list of countries
     *
     * @return array
     */
    private function get_countries() {
        return array(
            'US' => 'United States',
            'CA' => 'Canada',
            'GB' => 'United Kingdom',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'NL' => 'Netherlands',
            'BR' => 'Brazil',
            'IN' => 'India',
            'JP' => 'Japan',
            'CN' => 'China',
            // Add more countries as needed
        );
    }
    
    /**
     * AJAX handler for saving fields
     */
    public function ajax_save_fields() {
        check_ajax_referer('skylearn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $gateway_id = sanitize_key($_POST['gateway_id'] ?? '');
        $fields = json_decode(stripslashes($_POST['fields'] ?? '[]'), true);
        
        if (empty($gateway_id)) {
            wp_send_json_error('Invalid gateway ID');
        }
        
        $result = $this->save_gateway_fields($gateway_id, $fields);
        
        if ($result) {
            wp_send_json_success('Fields saved successfully');
        } else {
            wp_send_json_error('Failed to save fields');
        }
    }
    
    /**
     * AJAX handler for deleting field
     */
    public function ajax_delete_field() {
        check_ajax_referer('skylearn_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $gateway_id = sanitize_key($_POST['gateway_id'] ?? '');
        $field_id = sanitize_key($_POST['field_id'] ?? '');
        
        if (empty($gateway_id) || empty($field_id)) {
            wp_send_json_error('Invalid parameters');
        }
        
        $fields = $this->get_gateway_fields($gateway_id);
        $filtered_fields = array_filter($fields, function($field) use ($field_id) {
            return $field['id'] !== $field_id;
        });
        
        $result = $this->save_gateway_fields($gateway_id, array_values($filtered_fields));
        
        if ($result) {
            wp_send_json_success('Field deleted successfully');
        } else {
            wp_send_json_error('Failed to delete field');
        }
    }
}

/**
 * Get global payment fields instance
 *
 * @return SkyLearn_Billing_Pro_Payment_Fields
 */
function skylearn_billing_pro_payment_fields() {
    static $instance = null;
    
    if (is_null($instance)) {
        $instance = new SkyLearn_Billing_Pro_Payment_Fields();
    }
    
    return $instance;
}