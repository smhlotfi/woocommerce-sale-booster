<?php
function paid_customers_page(){
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

    // Get selected fields from POST request (or use default fields)
    $selected_fields = isset($_POST['selected_fields']) ? $_POST['selected_fields'] : ['order_id', 'phone_number', 'email', 'first_name', 'last_name'];

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
            <!-- Submit Button to trigger the export -->
            <input type="submit" class="button button-primary" value="Export Data">
        </form>

        <?php
        // If the export button is clicked, call the function to display the results
        if (isset($_POST['selected_fields']) && !empty($_POST['selected_fields'])) {
            display_export_page($selected_fields, 'paid-customers');
        }
        ?>
    </div>
    <?php
}
