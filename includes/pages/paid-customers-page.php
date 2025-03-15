<?php
function ssrSales2000_paid_customers_page(){
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
            // Display checkboxes for available fields
            foreach ($available_fields as $key => $label) {
                $checked = in_array($key, $selected_fields) ? 'checked' : '';
                echo "<label><input type='checkbox' name='selected_fields[]' value='" . esc_attr($key) . "' ". esc_attr($checked). "> ".esc_html($label)."</label><br>";
            }
            ?>
            <br>
            <!-- Nonce and action fields for security -->
            <?php wp_nonce_field('export_paid_customers', 'export_paid_nonce'); ?>
            <input type="hidden" name="action" value="export_paid_customers">
            <!-- Submit Button to trigger the export -->
            <input type="submit" class="button button-primary" value="Export Data">
        </form>

        <?php
        // If the export button is clicked, call the function to display the results
        if (isset($_POST['selected_fields']) && !empty($_POST['selected_fields'])) {
            ssrSales2000_handle_export_paid_customers();
        }
        ?>
    </div>
    <?php
}



function ssrSales2000_handle_export_paid_customers() {
    // Check nonce for security
    if (
        !isset($_POST['export_paid_nonce']) || 
        !wp_verify_nonce($_POST['export_paid_nonce'], 'export_paid_customers')
    ) {
        wp_die('Security check failed.');
    }

    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die('You do not have permission to export this data.');
    }

    // Sanitize input data
    $selected_fields = array_map('sanitize_text_field', $_POST['selected_fields']);

    if (!empty($_POST['selected_fields'])) {
        $selected_fields = array_map('sanitize_text_field', $_POST['selected_fields']);

        // Handle export here
        ssrSales2000_display_export_page($selected_fields, 'paid-customers');
        exit;
    }

    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>Please select at least one field to export.</p></div>';
    });
    exit;
}