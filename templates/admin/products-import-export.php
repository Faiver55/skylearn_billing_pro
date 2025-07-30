<?php
/**
 * Products import/export template
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

$product_manager = skylearn_billing_pro_product_manager();
$migration_tool = skylearn_billing_pro_migration_tool();
?>

<div class="wrap skylearn-billing-admin">
    <div class="skylearn-billing-header">
        <div class="skylearn-billing-header-content">
            <h1 class="skylearn-billing-title">
                <span class="skylearn-billing-icon dashicons dashicons-upload"></span>
                <?php esc_html_e('Import & Export Products', 'skylearn-billing-pro'); ?>
            </h1>
            <p class="skylearn-billing-subtitle">
                <?php esc_html_e('Import products from CSV/JSON or export existing products', 'skylearn-billing-pro'); ?>
            </p>
        </div>
    </div>

    <div class="skylearn-billing-container">
        <!-- Sidebar Navigation -->
        <div class="skylearn-billing-sidebar">
            <nav class="skylearn-billing-nav">
                <ul class="skylearn-billing-nav-list">
                    <li class="skylearn-billing-nav-item">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=skylearn-billing-pro-products')); ?>" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php esc_html_e('Back to Products', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                    <li class="skylearn-billing-nav-item active">
                        <a href="#" class="skylearn-billing-nav-link">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Import/Export', 'skylearn-billing-pro'); ?>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <!-- Quick Stats -->
            <div class="skylearn-billing-sidebar-widget">
                <h3><?php esc_html_e('Quick Stats', 'skylearn-billing-pro'); ?></h3>
                <?php
                $all_products = $product_manager->get_products();
                $total_products = count($all_products);
                ?>
                <div class="skylearn-stat-item">
                    <span class="skylearn-stat-number"><?php echo $total_products; ?></span>
                    <span class="skylearn-stat-label"><?php esc_html_e('Total Products', 'skylearn-billing-pro'); ?></span>
                </div>
                
                <div class="skylearn-export-all">
                    <h4><?php esc_html_e('Export All Products', 'skylearn-billing-pro'); ?></h4>
                    <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>" style="margin-bottom: 10px;">
                        <input type="hidden" name="action" value="skylearn_export_products" />
                        <input type="hidden" name="format" value="csv" />
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('skylearn_products_nonce'); ?>" />
                        <button type="submit" class="button button-secondary button-small"><?php esc_html_e('Export as CSV', 'skylearn-billing-pro'); ?></button>
                    </form>
                    
                    <form method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                        <input type="hidden" name="action" value="skylearn_export_products" />
                        <input type="hidden" name="format" value="json" />
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('skylearn_products_nonce'); ?>" />
                        <button type="submit" class="button button-secondary button-small"><?php esc_html_e('Export as JSON', 'skylearn-billing-pro'); ?></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="skylearn-billing-content">
            <div class="skylearn-billing-content-inner">
                <div class="skylearn-billing-tabs">
                    <ul class="skylearn-billing-tab-nav">
                        <li><a href="#tab-import" class="active"><?php esc_html_e('Import Products', 'skylearn-billing-pro'); ?></a></li>
                        <li><a href="#tab-export"><?php esc_html_e('Export Products', 'skylearn-billing-pro'); ?></a></li>
                        <li><a href="#tab-template"><?php esc_html_e('Download Template', 'skylearn-billing-pro'); ?></a></li>
                    </ul>

                    <!-- Import Tab -->
                    <div id="tab-import" class="skylearn-billing-tab-content active">
                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Import Products from File', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Upload a CSV or JSON file to import products. Make sure your file follows the correct format.', 'skylearn-billing-pro'); ?></p>
                            
                            <form method="post" enctype="multipart/form-data" class="skylearn-import-form">
                                <?php wp_nonce_field('skylearn_import_products', 'skylearn_import_nonce'); ?>
                                <input type="hidden" name="skylearn_import_products" value="1" />
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label for="import_file"><?php esc_html_e('Import File', 'skylearn-billing-pro'); ?> <span class="required">*</span></label>
                                        </th>
                                        <td>
                                            <input type="file" name="import_file" id="import_file" accept=".csv,.json" required />
                                            <p class="description"><?php esc_html_e('Select a CSV or JSON file to import. Maximum file size: 2MB.', 'skylearn-billing-pro'); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Import Options', 'skylearn-billing-pro'); ?></th>
                                        <td>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="checkbox" name="update_existing" value="1" />
                                                <?php esc_html_e('Update existing products', 'skylearn-billing-pro'); ?>
                                            </label>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="checkbox" name="skip_errors" value="1" checked />
                                                <?php esc_html_e('Skip rows with errors', 'skylearn-billing-pro'); ?>
                                            </label>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="checkbox" name="import_courses" value="1" checked />
                                                <?php esc_html_e('Import course mappings', 'skylearn-billing-pro'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php esc_html_e('Import Products', 'skylearn-billing-pro'); ?>
                                    </button>
                                </p>
                            </form>
                        </div>
                        
                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Import Guidelines', 'skylearn-billing-pro'); ?></h3>
                            <div class="skylearn-guidelines">
                                <ul>
                                    <li><?php esc_html_e('CSV files should include headers in the first row', 'skylearn-billing-pro'); ?></li>
                                    <li><?php esc_html_e('Product titles are required and must be unique', 'skylearn-billing-pro'); ?></li>
                                    <li><?php esc_html_e('Prices should be numeric values without currency symbols', 'skylearn-billing-pro'); ?></li>
                                    <li><?php esc_html_e('Use semicolons (;) to separate multiple values in fields like access triggers', 'skylearn-billing-pro'); ?></li>
                                    <li><?php esc_html_e('Boolean fields (Featured) should be "Yes" or "No"', 'skylearn-billing-pro'); ?></li>
                                    <li><?php esc_html_e('Course mappings should reference existing course IDs', 'skylearn-billing-pro'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Export Tab -->
                    <div id="tab-export" class="skylearn-billing-tab-content">
                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Export Products', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Select products to export and choose your preferred format.', 'skylearn-billing-pro'); ?></p>
                            
                            <form id="export-form" method="post" action="<?php echo admin_url('admin-ajax.php'); ?>">
                                <input type="hidden" name="action" value="skylearn_export_products" />
                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('skylearn_products_nonce'); ?>" />
                                
                                <table class="form-table">
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Export Format', 'skylearn-billing-pro'); ?></th>
                                        <td>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="radio" name="format" value="csv" checked />
                                                <?php esc_html_e('CSV (Comma Separated Values)', 'skylearn-billing-pro'); ?>
                                            </label>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="radio" name="format" value="json" />
                                                <?php esc_html_e('JSON (JavaScript Object Notation)', 'skylearn-billing-pro'); ?>
                                            </label>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row"><?php esc_html_e('Products to Export', 'skylearn-billing-pro'); ?></th>
                                        <td>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="radio" name="export_selection" value="all" checked />
                                                <?php esc_html_e('All products', 'skylearn-billing-pro'); ?>
                                            </label>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="radio" name="export_selection" value="active" />
                                                <?php esc_html_e('Active products only', 'skylearn-billing-pro'); ?>
                                            </label>
                                            <label style="display: block; margin-bottom: 10px;">
                                                <input type="radio" name="export_selection" value="custom" />
                                                <?php esc_html_e('Select specific products', 'skylearn-billing-pro'); ?>
                                            </label>
                                            
                                            <div id="custom-product-selection" style="display: none; margin-top: 15px; max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px;">
                                                <?php if (!empty($all_products)): ?>
                                                    <?php foreach ($all_products as $product): ?>
                                                        <label style="display: block; margin-bottom: 5px;">
                                                            <input type="checkbox" name="product_ids[]" value="<?php echo esc_attr($product['id']); ?>" />
                                                            <?php echo esc_html($product['title']); ?>
                                                            <small>(<?php echo esc_html($product['status']); ?>)</small>
                                                        </label>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <p><?php esc_html_e('No products available to export.', 'skylearn-billing-pro'); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                                
                                <p class="submit">
                                    <button type="submit" class="button button-primary">
                                        <?php esc_html_e('Export Products', 'skylearn-billing-pro'); ?>
                                    </button>
                                </p>
                            </form>
                        </div>
                    </div>

                    <!-- Template Tab -->
                    <div id="tab-template" class="skylearn-billing-tab-content">
                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Download Import Template', 'skylearn-billing-pro'); ?></h3>
                            <p><?php esc_html_e('Download a template file to see the correct format for importing products.', 'skylearn-billing-pro'); ?></p>
                            
                            <div class="skylearn-template-options">
                                <div class="skylearn-template-option">
                                    <h4><?php esc_html_e('CSV Template', 'skylearn-billing-pro'); ?></h4>
                                    <p><?php esc_html_e('Download a CSV template with sample data and proper headers.', 'skylearn-billing-pro'); ?></p>
                                    <a href="#" id="download-csv-template" class="button button-secondary">
                                        <?php esc_html_e('Download CSV Template', 'skylearn-billing-pro'); ?>
                                    </a>
                                </div>
                                
                                <div class="skylearn-template-option">
                                    <h4><?php esc_html_e('JSON Template', 'skylearn-billing-pro'); ?></h4>
                                    <p><?php esc_html_e('Download a JSON template with sample product structure.', 'skylearn-billing-pro'); ?></p>
                                    <a href="#" id="download-json-template" class="button button-secondary">
                                        <?php esc_html_e('Download JSON Template', 'skylearn-billing-pro'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div class="skylearn-form-section">
                            <h3><?php esc_html_e('Field Reference', 'skylearn-billing-pro'); ?></h3>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th><?php esc_html_e('Field Name', 'skylearn-billing-pro'); ?></th>
                                        <th><?php esc_html_e('Description', 'skylearn-billing-pro'); ?></th>
                                        <th><?php esc_html_e('Example', 'skylearn-billing-pro'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><code>Title</code></td>
                                        <td><?php esc_html_e('Product name/title (required)', 'skylearn-billing-pro'); ?></td>
                                        <td>My Course Product</td>
                                    </tr>
                                    <tr>
                                        <td><code>Price</code></td>
                                        <td><?php esc_html_e('Product price (required)', 'skylearn-billing-pro'); ?></td>
                                        <td>99.99</td>
                                    </tr>
                                    <tr>
                                        <td><code>Currency</code></td>
                                        <td><?php esc_html_e('Currency code', 'skylearn-billing-pro'); ?></td>
                                        <td>USD</td>
                                    </tr>
                                    <tr>
                                        <td><code>Billing Type</code></td>
                                        <td><?php esc_html_e('one_time or subscription', 'skylearn-billing-pro'); ?></td>
                                        <td>one_time</td>
                                    </tr>
                                    <tr>
                                        <td><code>Status</code></td>
                                        <td><?php esc_html_e('active or inactive', 'skylearn-billing-pro'); ?></td>
                                        <td>active</td>
                                    </tr>
                                    <tr>
                                        <td><code>Featured</code></td>
                                        <td><?php esc_html_e('Yes or No', 'skylearn-billing-pro'); ?></td>
                                        <td>No</td>
                                    </tr>
                                    <tr>
                                        <td><code>Access Triggers</code></td>
                                        <td><?php esc_html_e('Semicolon separated: payment;manual;webhook', 'skylearn-billing-pro'); ?></td>
                                        <td>payment;webhook</td>
                                    </tr>
                                    <tr>
                                        <td><code>Course Mappings</code></td>
                                        <td><?php esc_html_e('Semicolon separated course IDs', 'skylearn-billing-pro'); ?></td>
                                        <td>123;456;789</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.skylearn-billing-tab-nav a').click(function(e) {
        e.preventDefault();
        
        var targetTab = $(this).attr('href');
        
        // Update active states
        $('.skylearn-billing-tab-nav a').removeClass('active');
        $(this).addClass('active');
        
        $('.skylearn-billing-tab-content').removeClass('active');
        $(targetTab).addClass('active');
    });
    
    // Export selection
    $('input[name="export_selection"]').change(function() {
        if ($(this).val() === 'custom') {
            $('#custom-product-selection').show();
        } else {
            $('#custom-product-selection').hide();
        }
    });
    
    // Handle export form submission
    $('#export-form').submit(function(e) {
        var selection = $('input[name="export_selection"]:checked').val();
        var productIds = [];
        
        if (selection === 'active') {
            // Add only active product IDs
            <?php foreach ($all_products as $product): ?>
                <?php if ($product['status'] === 'active'): ?>
                    productIds.push(<?php echo $product['id']; ?>);
                <?php endif; ?>
            <?php endforeach; ?>
        } else if (selection === 'custom') {
            $('input[name="product_ids[]"]:checked').each(function() {
                productIds.push($(this).val());
            });
        }
        
        // Add product IDs to form
        $('input[name="product_ids[]"]').remove();
        $.each(productIds, function(index, id) {
            $('#export-form').append('<input type="hidden" name="product_ids[]" value="' + id + '">');
        });
    });
    
    // Template downloads
    $('#download-csv-template').click(function(e) {
        e.preventDefault();
        
        // Create CSV template content
        var csvContent = 'ID,Title,Description,Price,Sale Price,Currency,Billing Type,Subscription Period,Status,Visibility,Featured,Access Triggers,Tier Restrictions,Course Mappings,Enrollment Behavior\n';
        csvContent += ',"Sample Product","A sample product description",99.99,79.99,USD,one_time,,active,visible,No,payment;webhook,free;pro,123;456,immediate\n';
        csvContent += ',"Subscription Product","A sample subscription product",29.99,,USD,subscription,monthly,active,visible,Yes,payment;subscription,pro;pro_plus,789,immediate';
        
        downloadFile('skylearn-products-template.csv', csvContent, 'text/csv');
    });
    
    $('#download-json-template').click(function(e) {
        e.preventDefault();
        
        // Create JSON template content
        var jsonContent = {
            "version": "<?php echo SKYLEARN_BILLING_PRO_VERSION; ?>",
            "export_date": new Date().toISOString(),
            "products": [
                {
                    "title": "Sample Product",
                    "description": "A sample product description",
                    "price": 99.99,
                    "sale_price": 79.99,
                    "currency": "USD",
                    "billing_type": "one_time",
                    "status": "active",
                    "visibility": "visible",
                    "featured": false,
                    "access_triggers": ["payment", "webhook"],
                    "tier_restrictions": ["free", "pro"],
                    "course_mappings": ["123", "456"],
                    "enrollment_behavior": "immediate"
                }
            ]
        };
        
        downloadFile('skylearn-products-template.json', JSON.stringify(jsonContent, null, 2), 'application/json');
    });
    
    function downloadFile(filename, content, mimeType) {
        var blob = new Blob([content], { type: mimeType });
        var url = window.URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
    }
});
</script>

<style>
.skylearn-guidelines ul {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 4px;
    padding: 20px 20px 20px 40px;
    margin: 0;
}

.skylearn-guidelines li {
    margin-bottom: 8px;
    color: var(--skylearn-medium-gray);
}

.skylearn-template-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin: 20px 0;
}

.skylearn-template-option {
    background: var(--skylearn-light-gray);
    border: 1px solid var(--skylearn-border);
    border-radius: 6px;
    padding: 20px;
    text-align: center;
}

.skylearn-template-option h4 {
    margin: 0 0 10px 0;
    color: var(--skylearn-primary);
}

.skylearn-template-option p {
    margin: 0 0 15px 0;
    color: var(--skylearn-medium-gray);
}

#custom-product-selection {
    background: #fafafa;
}

#custom-product-selection label {
    padding: 5px 0;
    border-bottom: 1px solid #eee;
}

#custom-product-selection label:last-child {
    border-bottom: none;
}

.required {
    color: var(--skylearn-accent);
}
</style>