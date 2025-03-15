<?php
function ssrSales2000_ordered_before_not_recent_days_customers_page(){
    // Available fields that can be exported
    $available_fields = [
        'order_id' => 'Order ID',
        'phone_number' => 'Phone',
        'email' => 'Email',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'total_amount' => 'Total Amount',
        'order_date' => 'Order Date',
        'status' => 'Order Status'
    ];

    $allowed_fields = ['order_id', 'phone_number', 'email', 'first_name', 'last_name'];

    // Get selected fields from POST request (or use default fields)
    $selected_fields = isset($_POST['selected_fields']) 
    ? array_intersect(
        array_map('sanitize_text_field', $_POST['selected_fields']), 
        $allowed_fields
    ) 
    : $allowed_fields;

    ?>
    <div class="wrap">
        <h1>Export WooCommerce Data</h1>
        <p>Select the fields to export, then click "Export Data".</p>
        
        <!-- Form to select fields to export -->
        <form method="post" action="">
            <h3>Select Fields to Export</h3>
            <?php 
            $days = get_option('woo_sale_booster_days');
            ?>
            <label for="woo_sale_booster_days">Recent Days:</label>
            <input type="number" name="woo_sale_booster_days" value="<?php echo esc_attr($days); ?>" min="1" />
            <p class="description">Enter the number of days after which users are considered to not have made a purchase.</p>
            <br>
            <?php
            // Display checkboxes for available fields
            foreach ($available_fields as $key => $label) {
                $checked = in_array($key, $selected_fields) ? 'checked' : '';
                echo "<label><input type='checkbox' name='selected_fields[]' value='" . esc_attr($key) . "' ". esc_attr($checked). "> ".esc_html($label)."</label><br>";
            }
            wp_nonce_field('export_no_recent_purchase_nonce', 'export_no_recent_purchase_nonce');
            ?>
            <input type="hidden" name="action" value="export_no_recent_purchase">
            <br>
            <!-- Submit Button to trigger the export -->
            <input type="submit" class="button button-primary" value="Export Data">
        </form>

        <?php
        // If the export button is clicked, call the function to display the results
        if (isset($_POST['selected_fields']) && !empty($_POST['selected_fields'])) {
            ssrSales2000_handle_export_no_recent_purchase();
        }
        ?>
    </div>
    <?php
}

function ssrSales2000_handle_export_no_recent_purchase() {
    // Verify nonce
    if (
        ! isset($_POST['export_no_recent_purchase_nonce']) || 
        ! wp_verify_nonce($_POST['export_no_recent_purchase_nonce'], 'export_no_recent_purchase_nonce')
    ) {
        wp_die('Security check failed.');
    }

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to export this data.');
    }

    // Sanitize and process input
    $days = isset($_POST['woo_sale_booster_days']) ? absint($_POST['woo_sale_booster_days']) : 0;
    $selected_fields = isset($_POST['selected_fields']) ? array_map('sanitize_text_field', $_POST['selected_fields']) : [];

    if (!empty($selected_fields)) {
        // Handle export here
        ssrSales2000_display_export_page($selected_fields, 'no-recent-purchase');
        exit;
    }

    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Please select at least one field to export.</p></div>';
    });
    exit;
}