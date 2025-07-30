<?php
/**
 * Automation Builder UI Template for Skylearn Billing Pro
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

$automation_manager = skylearn_billing_pro_automation_manager();
$available_triggers = $automation_manager->get_available_trigger_types();
$available_actions = $automation_manager->get_available_action_types();
$automation_templates = $automation_manager->get_automation_templates();

// Get existing automation if editing
$automation_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$automation = $automation_id ? $automation_manager->get_automation($automation_id) : null;

// Handle form submissions
if ($_POST && wp_verify_nonce($_POST['_wpnonce'] ?? '', 'skylearn_automation_builder')) {
    $automation_data = array(
        'name' => sanitize_text_field($_POST['automation_name'] ?? ''),
        'description' => sanitize_textarea_field($_POST['automation_description'] ?? ''),
        'trigger_type' => sanitize_text_field($_POST['trigger_type'] ?? ''),
        'trigger_conditions' => json_decode(stripslashes($_POST['trigger_conditions'] ?? '[]'), true),
        'actions' => json_decode(stripslashes($_POST['actions'] ?? '[]'), true),
        'status' => sanitize_text_field($_POST['automation_status'] ?? 'draft')
    );
    
    if ($automation_id) {
        $automation_data['id'] = $automation_id;
    }
    
    $result = $automation_manager->save_automation($automation_data);
    
    if ($result) {
        $redirect_url = add_query_arg(array(
            'page' => 'skylearn-billing-automation',
            'edit' => $result,
            'saved' => 1
        ), admin_url('admin.php'));
        wp_redirect($redirect_url);
        exit;
    } else {
        $error_message = __('Failed to save automation. Please try again.', 'skylearn-billing-pro');
    }
}

// Handle template loading
if (isset($_GET['template']) && isset($automation_templates[$_GET['template']])) {
    $template = $automation_templates[$_GET['template']];
    $automation = array(
        'name' => $template['name'],
        'description' => $template['description'],
        'trigger_type' => $template['trigger_type'],
        'trigger_conditions' => $template['trigger_conditions'] ?? array(),
        'actions' => $template['actions'],
        'status' => 'draft'
    );
}
?>

<div class="skylearn-billing-automation-builder">
    <div class="skylearn-billing-header">
        <h1><?php echo $automation_id ? __('Edit Automation', 'skylearn-billing-pro') : __('Create New Automation', 'skylearn-billing-pro'); ?></h1>
        <p><?php _e('Build powerful automations that trigger on events and execute actions across your favorite tools.', 'skylearn-billing-pro'); ?></p>
    </div>
    
    <?php if (isset($_GET['saved'])): ?>
        <div class="notice notice-success">
            <p><?php _e('Automation saved successfully!', 'skylearn-billing-pro'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="notice notice-error">
            <p><?php echo esc_html($error_message); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Template Library -->
    <?php if (!$automation_id): ?>
    <div class="skylearn-automation-templates">
        <h2><?php _e('Start with a Template', 'skylearn-billing-pro'); ?></h2>
        <div class="template-grid">
            <?php foreach ($automation_templates as $template_key => $template): ?>
                <div class="template-card">
                    <h3><?php echo esc_html($template['name']); ?></h3>
                    <p><?php echo esc_html($template['description']); ?></p>
                    <div class="template-meta">
                        <span class="trigger-type"><?php echo esc_html($available_triggers[$template['trigger_type']] ?? $template['trigger_type']); ?></span>
                        <span class="action-count"><?php echo count($template['actions']); ?> <?php _e('actions', 'skylearn-billing-pro'); ?></span>
                    </div>
                    <a href="<?php echo esc_url(add_query_arg('template', $template_key)); ?>" class="button button-secondary">
                        <?php _e('Use Template', 'skylearn-billing-pro'); ?>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="template-divider">
            <span><?php _e('or', 'skylearn-billing-pro'); ?></span>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Automation Builder Form -->
    <form method="post" id="automation-builder-form" class="skylearn-automation-form">
        <?php wp_nonce_field('skylearn_automation_builder'); ?>
        
        <!-- Basic Information -->
        <div class="automation-section">
            <h2><?php _e('Basic Information', 'skylearn-billing-pro'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="automation_name"><?php _e('Automation Name', 'skylearn-billing-pro'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="automation_name" name="automation_name" 
                               value="<?php echo esc_attr($automation['name'] ?? ''); ?>" 
                               class="regular-text" required>
                        <p class="description"><?php _e('Give your automation a descriptive name.', 'skylearn-billing-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="automation_description"><?php _e('Description', 'skylearn-billing-pro'); ?></label>
                    </th>
                    <td>
                        <textarea id="automation_description" name="automation_description" rows="3" class="large-text"><?php echo esc_textarea($automation['description'] ?? ''); ?></textarea>
                        <p class="description"><?php _e('Optional description of what this automation does.', 'skylearn-billing-pro'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="automation_status"><?php _e('Status', 'skylearn-billing-pro'); ?></label>
                    </th>
                    <td>
                        <select id="automation_status" name="automation_status">
                            <option value="draft" <?php selected($automation['status'] ?? 'draft', 'draft'); ?>><?php _e('Draft', 'skylearn-billing-pro'); ?></option>
                            <option value="active" <?php selected($automation['status'] ?? 'draft', 'active'); ?>><?php _e('Active', 'skylearn-billing-pro'); ?></option>
                            <option value="inactive" <?php selected($automation['status'] ?? 'draft', 'inactive'); ?>><?php _e('Inactive', 'skylearn-billing-pro'); ?></option>
                        </select>
                        <p class="description"><?php _e('Only active automations will run.', 'skylearn-billing-pro'); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Trigger Configuration -->
        <div class="automation-section">
            <h2><?php _e('When this happens...', 'skylearn-billing-pro'); ?></h2>
            
            <div class="trigger-builder">
                <div class="trigger-type-selector">
                    <label for="trigger_type"><?php _e('Choose a trigger:', 'skylearn-billing-pro'); ?></label>
                    <select id="trigger_type" name="trigger_type" required>
                        <option value=""><?php _e('Select a trigger event', 'skylearn-billing-pro'); ?></option>
                        <?php foreach ($available_triggers as $trigger_key => $trigger_name): ?>
                            <option value="<?php echo esc_attr($trigger_key); ?>" 
                                    <?php selected($automation['trigger_type'] ?? '', $trigger_key); ?>>
                                <?php echo esc_html($trigger_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="trigger-conditions" id="trigger-conditions-container">
                    <h3><?php _e('Conditions (optional)', 'skylearn-billing-pro'); ?></h3>
                    <p class="description"><?php _e('Add conditions to make the trigger more specific.', 'skylearn-billing-pro'); ?></p>
                    
                    <div id="conditions-list">
                        <!-- Conditions will be populated here via JavaScript -->
                    </div>
                    
                    <button type="button" id="add-condition" class="button button-secondary">
                        <?php _e('Add Condition', 'skylearn-billing-pro'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Actions Configuration -->
        <div class="automation-section">
            <h2><?php _e('Do this...', 'skylearn-billing-pro'); ?></h2>
            
            <div class="actions-builder">
                <div id="actions-list">
                    <!-- Actions will be populated here via JavaScript -->
                </div>
                
                <div class="add-action-buttons">
                    <?php foreach ($available_actions as $action_key => $action_name): ?>
                        <button type="button" class="button button-secondary add-action-btn" 
                                data-action-type="<?php echo esc_attr($action_key); ?>">
                            <?php echo esc_html($action_name); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Hidden fields for JSON data -->
        <input type="hidden" id="trigger_conditions_json" name="trigger_conditions" value="">
        <input type="hidden" id="actions_json" name="actions" value="">
        
        <!-- Submit Buttons -->
        <div class="automation-submit">
            <input type="submit" name="save_automation" class="button button-primary" 
                   value="<?php echo $automation_id ? __('Update Automation', 'skylearn-billing-pro') : __('Save Automation', 'skylearn-billing-pro'); ?>">
            <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-automation')); ?>" class="button button-secondary">
                <?php _e('Cancel', 'skylearn-billing-pro'); ?>
            </a>
            
            <?php if ($automation_id): ?>
                <button type="button" id="test-automation" class="button button-secondary" style="margin-left: 20px;">
                    <?php _e('Test Automation', 'skylearn-billing-pro'); ?>
                </button>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Action Templates (hidden, used by JavaScript) -->
<script type="text/template" id="webhook-action-template">
    <div class="action-card" data-action-type="webhook">
        <div class="action-header">
            <h4><?php _e('Send Webhook', 'skylearn-billing-pro'); ?></h4>
            <button type="button" class="remove-action">&times;</button>
        </div>
        <div class="action-content">
            <label><?php _e('Webhook URL:', 'skylearn-billing-pro'); ?></label>
            <input type="url" class="action-field regular-text" data-field="url" placeholder="https://example.com/webhook">
            
            <label><?php _e('Method:', 'skylearn-billing-pro'); ?></label>
            <select class="action-field" data-field="method">
                <option value="POST">POST</option>
                <option value="PUT">PUT</option>
                <option value="PATCH">PATCH</option>
            </select>
            
            <label><?php _e('Request Body:', 'skylearn-billing-pro'); ?></label>
            <textarea class="action-field large-text" data-field="body" rows="4" 
                      placeholder='{"user_email": "{{user.email}}", "product_id": "{{product.id}}"}'></textarea>
            <p class="description"><?php _e('Use {{field.name}} placeholders for dynamic data.', 'skylearn-billing-pro'); ?></p>
        </div>
    </div>
</script>

<script type="text/template" id="email-action-template">
    <div class="action-card" data-action-type="email">
        <div class="action-header">
            <h4><?php _e('Send Email', 'skylearn-billing-pro'); ?></h4>
            <button type="button" class="remove-action">&times;</button>
        </div>
        <div class="action-content">
            <label><?php _e('To:', 'skylearn-billing-pro'); ?></label>
            <input type="text" class="action-field regular-text" data-field="to" placeholder="{{user.email}}">
            
            <label><?php _e('Subject:', 'skylearn-billing-pro'); ?></label>
            <input type="text" class="action-field regular-text" data-field="subject" placeholder="Welcome to {{site.name}}!">
            
            <label><?php _e('Message:', 'skylearn-billing-pro'); ?></label>
            <textarea class="action-field large-text" data-field="message" rows="6" 
                      placeholder="Thank you for your purchase of {{product.name}}!"></textarea>
            <p class="description"><?php _e('Use {{field.name}} placeholders for dynamic content.', 'skylearn-billing-pro'); ?></p>
        </div>
    </div>
</script>

<script type="text/template" id="crm-action-template">
    <div class="action-card" data-action-type="crm">
        <div class="action-header">
            <h4><?php _e('Update CRM', 'skylearn-billing-pro'); ?></h4>
            <button type="button" class="remove-action">&times;</button>
        </div>
        <div class="action-content">
            <label><?php _e('CRM Provider:', 'skylearn-billing-pro'); ?></label>
            <select class="action-field" data-field="provider">
                <option value="hubspot">HubSpot</option>
                <option value="salesforce">Salesforce</option>
            </select>
            
            <label><?php _e('Action:', 'skylearn-billing-pro'); ?></label>
            <select class="action-field" data-field="action">
                <option value="create_contact"><?php _e('Create Contact', 'skylearn-billing-pro'); ?></option>
                <option value="update_contact"><?php _e('Update Contact', 'skylearn-billing-pro'); ?></option>
                <option value="add_to_list"><?php _e('Add to List', 'skylearn-billing-pro'); ?></option>
            </select>
            
            <label><?php _e('Data (JSON):', 'skylearn-billing-pro'); ?></label>
            <textarea class="action-field large-text" data-field="data" rows="4" 
                      placeholder='{"email": "{{user.email}}", "firstname": "{{user.first_name}}"}'></textarea>
        </div>
    </div>
</script>

<script type="text/template" id="condition-template">
    <div class="condition-row">
        <select class="condition-field" data-field="field">
            <option value="user.email"><?php _e('User Email', 'skylearn-billing-pro'); ?></option>
            <option value="product.id"><?php _e('Product ID', 'skylearn-billing-pro'); ?></option>
            <option value="payment.amount"><?php _e('Payment Amount', 'skylearn-billing-pro'); ?></option>
        </select>
        
        <select class="condition-operator" data-field="operator">
            <option value="equals"><?php _e('equals', 'skylearn-billing-pro'); ?></option>
            <option value="not_equals"><?php _e('does not equal', 'skylearn-billing-pro'); ?></option>
            <option value="contains"><?php _e('contains', 'skylearn-billing-pro'); ?></option>
            <option value="greater_than"><?php _e('is greater than', 'skylearn-billing-pro'); ?></option>
            <option value="less_than"><?php _e('is less than', 'skylearn-billing-pro'); ?></option>
        </select>
        
        <input type="text" class="condition-value" data-field="value" placeholder="<?php _e('Value', 'skylearn-billing-pro'); ?>">
        
        <button type="button" class="remove-condition button-link-delete">&times;</button>
    </div>
</script>

<style>
.skylearn-billing-automation-builder {
    max-width: 1200px;
}

.skylearn-automation-templates {
    margin-bottom: 30px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 5px;
}

.template-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.template-card {
    background: white;
    padding: 20px;
    border-radius: 5px;
    border: 1px solid #ddd;
}

.template-card h3 {
    margin: 0 0 10px 0;
    color: #23282d;
}

.template-meta {
    margin: 15px 0;
    font-size: 12px;
    color: #666;
}

.template-meta span {
    display: inline-block;
    margin-right: 15px;
    padding: 2px 8px;
    background: #e9ecef;
    border-radius: 3px;
}

.template-divider {
    text-align: center;
    margin: 30px 0;
    position: relative;
}

.template-divider:before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: #ddd;
}

.template-divider span {
    background: white;
    padding: 0 20px;
    color: #666;
    position: relative;
}

.automation-section {
    margin-bottom: 30px;
    padding: 20px;
    background: white;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.automation-section h2 {
    margin: 0 0 20px 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
    color: #23282d;
}

.trigger-builder, .actions-builder {
    margin-top: 20px;
}

.action-card, .condition-row {
    background: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 15px;
    overflow: hidden;
}

.action-header {
    background: #0073aa;
    color: white;
    padding: 10px 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.action-header h4 {
    margin: 0;
    font-size: 14px;
}

.remove-action, .remove-condition {
    background: none;
    border: none;
    color: white;
    font-size: 18px;
    cursor: pointer;
    padding: 0;
    width: 20px;
    height: 20px;
    text-align: center;
}

.action-content {
    padding: 15px;
}

.action-content label {
    display: block;
    margin: 10px 0 5px 0;
    font-weight: 600;
}

.condition-row {
    padding: 15px;
    display: flex;
    gap: 10px;
    align-items: center;
}

.condition-row select, .condition-row input {
    flex: 1;
}

.add-action-buttons {
    margin-top: 20px;
}

.add-action-btn {
    margin-right: 10px;
    margin-bottom: 10px;
}

.automation-submit {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Initialize existing automation data
    var existingAutomation = <?php echo json_encode($automation ?? array()); ?>;
    
    // Initialize conditions and actions from existing data
    if (existingAutomation.trigger_conditions) {
        loadConditions(existingAutomation.trigger_conditions);
    }
    
    if (existingAutomation.actions) {
        loadActions(existingAutomation.actions);
    }
    
    // Add condition functionality
    $('#add-condition').on('click', function() {
        addCondition();
    });
    
    // Add action functionality
    $('.add-action-btn').on('click', function() {
        var actionType = $(this).data('action-type');
        addAction(actionType);
    });
    
    // Remove condition functionality
    $(document).on('click', '.remove-condition', function() {
        $(this).closest('.condition-row').remove();
        updateConditionsJSON();
    });
    
    // Remove action functionality
    $(document).on('click', '.remove-action', function() {
        $(this).closest('.action-card').remove();
        updateActionsJSON();
    });
    
    // Update JSON on field changes
    $(document).on('change keyup', '.condition-field, .condition-operator, .condition-value', function() {
        updateConditionsJSON();
    });
    
    $(document).on('change keyup', '.action-field', function() {
        updateActionsJSON();
    });
    
    // Form submission
    $('#automation-builder-form').on('submit', function() {
        updateConditionsJSON();
        updateActionsJSON();
    });
    
    // Test automation
    $('#test-automation').on('click', function() {
        testAutomation();
    });
    
    function addCondition(data = {}) {
        var template = $('#condition-template').html();
        var $condition = $(template);
        
        if (data.field) $condition.find('[data-field="field"]').val(data.field);
        if (data.operator) $condition.find('[data-field="operator"]').val(data.operator);
        if (data.value) $condition.find('[data-field="value"]').val(data.value);
        
        $('#conditions-list').append($condition);
        updateConditionsJSON();
    }
    
    function addAction(actionType, data = {}) {
        var template = $('#' + actionType + '-action-template').html();
        if (!template) return;
        
        var $action = $(template);
        
        // Populate existing data
        Object.keys(data).forEach(function(key) {
            var $field = $action.find('[data-field="' + key + '"]');
            if ($field.length) {
                if (typeof data[key] === 'object') {
                    $field.val(JSON.stringify(data[key], null, 2));
                } else {
                    $field.val(data[key]);
                }
            }
        });
        
        $('#actions-list').append($action);
        updateActionsJSON();
    }
    
    function loadConditions(conditions) {
        conditions.forEach(function(condition) {
            addCondition(condition);
        });
    }
    
    function loadActions(actions) {
        actions.forEach(function(action) {
            addAction(action.type, action);
        });
    }
    
    function updateConditionsJSON() {
        var conditions = [];
        
        $('#conditions-list .condition-row').each(function() {
            var $row = $(this);
            var condition = {
                field: $row.find('[data-field="field"]').val(),
                operator: $row.find('[data-field="operator"]').val(),
                value: $row.find('[data-field="value"]').val()
            };
            
            if (condition.field && condition.operator) {
                conditions.push(condition);
            }
        });
        
        $('#trigger_conditions_json').val(JSON.stringify(conditions));
    }
    
    function updateActionsJSON() {
        var actions = [];
        
        $('#actions-list .action-card').each(function() {
            var $card = $(this);
            var actionType = $card.data('action-type');
            var action = { type: actionType };
            
            $card.find('.action-field').each(function() {
                var $field = $(this);
                var fieldName = $field.data('field');
                var value = $field.val();
                
                if (fieldName && value) {
                    // Try to parse JSON for data fields
                    if (fieldName === 'data' || fieldName === 'body') {
                        try {
                            action[fieldName] = JSON.parse(value);
                        } catch (e) {
                            action[fieldName] = value;
                        }
                    } else {
                        action[fieldName] = value;
                    }
                }
            });
            
            if (Object.keys(action).length > 1) {
                actions.push(action);
            }
        });
        
        $('#actions_json').val(JSON.stringify(actions));
    }
    
    function testAutomation() {
        var automationId = <?php echo $automation_id; ?>;
        
        if (!automationId) {
            alert('<?php _e('Please save the automation first before testing.', 'skylearn-billing-pro'); ?>');
            return;
        }
        
        // Here you would make an AJAX call to test the automation
        alert('<?php _e('Test functionality will be implemented in a future update.', 'skylearn-billing-pro'); ?>');
    }
});
</script>