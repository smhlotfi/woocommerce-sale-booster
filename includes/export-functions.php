<?php
require_once plugin_dir_path(__FILE__) . 'csv-export.php';

global $results;

// Main function to display export page
function display_export_page($selected_fields, $type) {
    global $wpdb, $results;

    $available_fields = get_available_fields();
    

    switch ($type) {
        case 'paid-customers':
            $sql_fields = generate_sql_fields($selected_fields);
            $results = build_get_completed_or_processing_query($sql_fields);
            break;
        case 'cancelled-customers':
            $sql_fields = generate_sql_fields($selected_fields);
            $results = build_get_cancelled_orders_query($sql_fields);
            break;
        case 'no-recent-purchase':
            // Here we assume you are passing the number of days as $days
            
            // die ($_POST['woo_sale_booster_days']);
            $days = isset($_POST['woo_sale_booster_days']) ? ($_POST['woo_sale_booster_days']) : 30;
            $sql_fields = generate_sql_fields($selected_fields);
            $results = build_get_customers_no_recent_purchase_query($sql_fields, $days);
            break;

        case 'big-purchase-customers':

            $min_value = ($_POST['woo_sale_booster_min_value']) ? ($_POST['woo_sale_booster_min_value']) : 100;
            
            $sql_fields = generate_sql_fields($selected_fields);
            $results = build_get_high_value_customers_query($sql_fields, $min_value);
            break;

    }
    // $query = build_get_completed_or_processing_query($sql_fields);

    // $results = $wpdb->get_results($query, ARRAY_A);

    echo '<div class="wrap">';
    echo '<h1>Order Data Export</h1>';

    display_field_selection_form($selected_fields, $available_fields);

    if (!empty($results)) {
        display_results_table($selected_fields, $available_fields, $results);
        // Store the results in a hidden input
        echo '<form method="post">';
        echo '<input type="hidden" name="export_data" value="' . esc_attr(json_encode($results, JSON_UNESCAPED_UNICODE)) . '">';
        echo '<input type="hidden" name="selected_fields" value="' . esc_attr(json_encode($selected_fields, JSON_UNESCAPED_UNICODE)) . '">';
        echo '<button type="submit" name="export_csv">Export CSV</button>';
        echo '</form>';
    } else {
        echo '<p>No data found.</p>';
    }

    echo '</div>';    
}


// Function to get available fields for export
function get_available_fields() {
    return [
        'order_id' => 'Order ID',
        'phone_number' => 'Phone',
        'email' => 'Email',
        'first_name' => 'First Name',
        'last_name' => 'Last Name',
        'total_amount' => 'Total Amount',
        'order_date' => 'Order Date',
        'status' => 'Order Status'
    ];
}

// // Function to generate SQL fields based on selected fields
function generate_sql_fields($selected_fields) {
    $sql_fields = [];

    if (in_array('order_id', $selected_fields)) $sql_fields[] = "o.id AS order_id";
    if (in_array('phone_number', $selected_fields)) $sql_fields[] = "COALESCE(oa.phone, '') AS phone_number";
    if (in_array('email', $selected_fields)) $sql_fields[] = "COALESCE(oa.email, '') AS email";
    if (in_array('first_name', $selected_fields)) $sql_fields[] = "oa.first_name AS first_name";
    if (in_array('last_name', $selected_fields)) $sql_fields[] = "oa.last_name AS last_name";
    if (in_array('total_amount', $selected_fields)) $sql_fields[] = "o.total_amount AS total_amount";
    if (in_array('order_date', $selected_fields)) $sql_fields[] = "o.date_created_gmt AS order_date"; // Use date_created_gmt for Order Date
    if (in_array('status', $selected_fields)) $sql_fields[] = "o.status AS status";

    return $sql_fields;
}

// Function to build SQL query
function build_get_completed_or_processing_query($fields) {
    global $wpdb;
    
    // Escape only table and column names, but leave SQL functions like COALESCE untouched
    $escaped_fields = array_map(function($field) use ($wpdb) {
        // Only escape if it's a simple table or column name
        if (preg_match('/^[a-zA-Z0-9_\.]+$/', $field)) {
            return esc_sql($field);
        }
        // Otherwise, leave the SQL function as is
        return $field;
    }, $fields);

    // Join fields into a comma-separated string for SQL
    $fields_sql = implode(', ', $escaped_fields);



    // die ("<pre>".var_dump($fields)."</pre>");

    return $wpdb->get_results(
        $wpdb->prepare("
        SELECT DISTINCT $fields_sql
        FROM {$wpdb->prefix}wc_orders AS o
        LEFT JOIN {$wpdb->prefix}wc_order_addresses AS oa 
            ON o.id = oa.order_id AND oa.address_type = 'billing'
        WHERE o.status IN (%s, %s);
    ", 'wc-processing', 'wc-completed'), ARRAY_A);
}


// Function to get customers with cancelled orders
function build_get_cancelled_orders_query($fields) {
    global $wpdb;

    // Escape only table and column names, but leave SQL functions like COALESCE untouched
    $escaped_fields = array_map(function($field) use ($wpdb) {
        // Only escape if it's a simple table or column name
        if (preg_match('/^[a-zA-Z0-9_\.]+$/', $field)) {
            return esc_sql($field);
        }
        // Otherwise, leave the SQL function as is
        return $field;
    }, $fields);

    // Join fields into a comma-separated string for SQL
    $fields_sql = implode(', ', $escaped_fields);

    return $wpdb->get_results(
        $wpdb->prepare("
        SELECT DISTINCT $fields_sql
        FROM {$wpdb->prefix}wc_orders AS o
        LEFT JOIN {$wpdb->prefix}wc_order_addresses AS oa 
            ON o.id = oa.order_id AND oa.address_type = 'billing'
        WHERE o.status = %s
    ", 'wc-cancelled'), ARRAY_A);
}

// Function to get customers who ordered before but not in the last recent days
function build_get_customers_no_recent_purchase_query($fields, $days) {
    global $wpdb;

    // Escape only table and column names, but leave SQL functions like COALESCE untouched
    $escaped_fields = array_map(function($field) use ($wpdb) {
        // Only escape if it's a simple table or column name
        if (preg_match('/^[a-zA-Z0-9_\.]+$/', $field)) {
            return esc_sql($field);
        }
        // Otherwise, leave the SQL function as is
        return $field;
    }, $fields);

    // Join fields into a comma-separated string for SQL
    $fields_sql = implode(', ', $escaped_fields);


    // Get the date X days ago
    $date_x_days_ago = gmdate('Y-m-d', strtotime("-$days days"));
    

    return $wpdb->get_results(
        $wpdb->prepare("
        SELECT DISTINCT $fields_sql
        FROM {$wpdb->prefix}wc_orders AS o
        LEFT JOIN {$wpdb->prefix}wc_order_addresses AS oa 
            ON o.id = oa.order_id AND oa.address_type = 'billing'
        WHERE o.status IN ('wc-completed', 'wc-processing')
        AND o.customer_id IS NOT NULL
        AND o.date_created_gmt < %s
        AND NOT EXISTS (
            SELECT 1 
            FROM {$wpdb->prefix}wc_orders AS o2
            WHERE o2.customer_id = o.customer_id
            AND o2.date_created_gmt >= %s
            AND o2.status IN ('wc-completed', 'wc-processing')
        )
    ", $date_x_days_ago, $date_x_days_ago), ARRAY_A);
}

// Function to get customers with high-value purchases
function build_get_high_value_customers_query($fields, $min_value) {
    global $wpdb;

    // Escape only table and column names, but leave SQL functions like COALESCE untouched
    $escaped_fields = array_map(function($field) use ($wpdb) {
        // Only escape if it's a simple table or column name
        if (preg_match('/^[a-zA-Z0-9_\.]+$/', $field)) {
            return esc_sql($field);
        }
        // Otherwise, leave the SQL function as is
        return $field;
    }, $fields);

    // Join fields into a comma-separated string for SQL
    $fields_sql = implode(', ', $escaped_fields);


    return $wpdb->get_results(
        $wpdb->prepare("
        SELECT DISTINCT $fields_sql
        FROM {$wpdb->prefix}wc_orders AS o
        LEFT JOIN {$wpdb->prefix}wc_order_addresses AS oa 
            ON o.id = oa.order_id AND oa.address_type = 'billing'
        WHERE o.status IN ('wc-completed', 'wc-processing')
        AND o.total_amount >= %d
    ", $min_value), ARRAY_A);
}














// Function to display the form for selecting fields to export
function display_field_selection_form($selected_fields, $available_fields) {
    echo '<form method="post" id="export-fields-form">';
    echo '<input type="submit" name="export_csv" class="button button-primary" value="Export to CSV">';
    echo '</form>';
}

// Function to display results in a table
function display_results_table($selected_fields, $available_fields, $results) {
    echo '<table class="widefat striped"><thead><tr>';
    foreach ($selected_fields as $field) {
        echo "<th>".esc_html($available_fields[$field])."</th>";
    }
    echo '</tr></thead><tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        foreach ($selected_fields as $field) {
            display_field_value($field, $row);
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
}

// Function to display the value for each field, formatting "order_date"
function display_field_value($field, $row) {
    if ($field == 'order_date') {
        $formatted_date = isset($row[$field]) ? gmdate('Y-m-d H:i:s', strtotime($row[$field])) : '';
        echo "<td>".esc_html($formatted_date)."</td>";
    } else {
        echo '<td>' . esc_html($row[$field]) . '</td>';
    }
}




// Handle CSV Export
if (isset($_POST['export_csv'])) {
    if (!empty($_POST['export_data']) && !empty($_POST['selected_fields'])) {
        $export_results = json_decode(stripslashes($_POST['export_data']), true);
        $selected_fields = json_decode(stripslashes($_POST['selected_fields']), true);

        array_walk_recursive($export_results, function (&$value) {
            $value = mb_convert_encoding($value, 'UTF-8', 'auto');
        });
        

        if (!empty($export_results)) {
            export_csv($export_results, get_available_fields(), $selected_fields);
            exit;
        }
    }
    echo '<p>No data found for export.</p>';
}