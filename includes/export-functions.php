<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once plugin_dir_path(__FILE__) . 'csv-export.php';

// Main function to display export page
function ssrSales2000_display_export_page($selected_fields, $type) {
    global $wpdb;

    $available_fields = ssrSales2000_get_available_fields();
    
    switch ($type) {
        case 'paid-customers':
            $sql_fields = ssrSales2000_generate_sql_fields($selected_fields);
            $results = ssrSales2000_build_get_completed_or_processing_query($sql_fields);
            foreach ($results as &$row) {
                foreach ($selected_fields as $field) {
                    
                    if ($field === 'phone_number') {
                        // Standardize the phone number
                        $temp = ssrSales2000_standardize_phone_number($row[$field]);
                        $row[$field] = $temp;
                    } else {
                        $row[$field] = $row[$field];
                    }
                    
                }
                
            }
            unset($row);

            // die (print_r($results, true));
            break;
        case 'cancelled-customers':
            $sql_fields = ssrSales2000_generate_sql_fields($selected_fields);
            $results = ssrSales2000_build_get_cancelled_orders_query($sql_fields);
            break;
        case 'no-recent-purchase':
            // Here we assume you are passing the number of days as $days
            
            // die ($_POST['woo_sale_booster_days']);
            $days = isset($_POST['woo_sale_booster_days']) ? absint($_POST['woo_sale_booster_days']) : 30;
            if ($days < 0 || $days > 99999) {
                $days = 30; // Default value
            }
            $sql_fields = ssrSales2000_generate_sql_fields($selected_fields);
            $results = ssrSales2000_build_get_customers_no_recent_purchase_query($sql_fields, $days);
            break;

        case 'big-purchase-customers':

            $min_value = ($_POST['woo_sale_booster_min_value']) ? floatval($_POST['woo_sale_booster_min_value']) : 0;

            if ($min_value < 0 || $min_value > 9999999999) {
                $min_value = 0; // Reset to default if invalid
            }
            
            $sql_fields = ssrSales2000_generate_sql_fields($selected_fields);
            $results = ssrSales2000_build_get_high_value_customers_query($sql_fields, $min_value);
            
            break;

    }
    // $query = ssrSales2000_build_get_completed_or_processing_query($sql_fields);

    // $results = $wpdb->get_results($query, ARRAY_A);

    echo '<div class="wrap">';
    echo '<h1>Order Data Export</h1>';

    if (!empty($results)) {
        ssrSales2000_display_results_table($selected_fields, $available_fields, $results);
        $nonce = wp_create_nonce('export_csv_nonce');
        // Store the results in a hidden input
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="export_csv">';
        echo '<input type="hidden" name="export_data" value="' . esc_attr(json_encode($results, JSON_UNESCAPED_UNICODE)) . '">';
        echo '<input type="hidden" name="selected_fields" value="' . esc_attr(json_encode($selected_fields, JSON_UNESCAPED_UNICODE)) . '">';
        echo '<input type="hidden" name="export_csv_nonce" value="' . $nonce . '">';
        echo '<button type="submit" name="export_csv">Export CSV</button>';
        echo '</form>';
    } else {
        echo '<p>No data found.</p>';
    }

    echo '</div>';    
}


// Function to get available fields for export
function ssrSales2000_get_available_fields() {
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
function ssrSales2000_generate_sql_fields($selected_fields) {
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
function ssrSales2000_build_get_completed_or_processing_query($fields) {
    global $wpdb;
    $allowed_fields = [
        'order_id' => 'o.id AS order_id',
        'phone_number' => "COALESCE(oa.phone, '') AS phone_number",
        'email' => "COALESCE(oa.email, '') AS email",
        'first_name' => 'oa.first_name AS first_name',
        'last_name' => 'oa.last_name AS last_name',
        'status' => 'o.status AS status',
        'order_date' => 'o.date_created_gmt AS order_date',
        'total_amount' => 'o.total_amount AS total_amount',
    ];

    $safe_fields = array_intersect($fields, $allowed_fields);
    

    if (empty($safe_fields)) {
        die("return");
        return [];
    }

    $fields_sql = implode(', ', $safe_fields);

    $legacy_fields_sql = str_replace(
        ['o.id', 'oa.phone', 'oa.email', 'oa.first_name', 'oa.last_name', 'o.status', 'o.date_created_gmt', 'o.total_amount'],
        ['p.ID', 'pm_phone.meta_value', 'pm_email.meta_value', 'pm_first_name.meta_value', 'pm_last_name.meta_value', 'p.post_status', 'p.post_date', 'pm_total_amount.meta_value'],
        $fields_sql
    );
    // die(print_r($fields_sql, true));
    $woo_version = get_option('woocommerce_version');
    $table_prefix = sanitize_key($wpdb->prefix);


    if (version_compare($woo_version, '8.0', '>=')) {
        // For WooCommerce 8.0+ (new table) and pre-8.0 orders (old table)
        $query = "-- New table query for WooCommerce 8.0+
                (SELECT DISTINCT {$fields_sql}
                FROM {$table_prefix}wc_orders AS o
                LEFT JOIN {$table_prefix}wc_order_addresses AS oa 
                    ON o.id = oa.order_id AND oa.address_type = 'billing'
                WHERE o.status IN (%s, %s))
                
                UNION ALL
                
                -- Old table query for pre-8.0 WooCommerce
                (SELECT DISTINCT {$legacy_fields_sql}
                FROM {$table_prefix}posts AS p
                LEFT JOIN {$table_prefix}postmeta AS pm_phone
                    ON p.ID = pm_phone.post_id AND pm_phone.meta_key = '_billing_phone'
                LEFT JOIN {$table_prefix}postmeta AS pm_email
                    ON p.ID = pm_email.post_id AND pm_email.meta_key = '_billing_email'
                LEFT JOIN {$table_prefix}postmeta AS pm_first_name
                    ON p.ID = pm_first_name.post_id AND pm_first_name.meta_key = '_billing_first_name'
                LEFT JOIN {$table_prefix}postmeta AS pm_last_name
                    ON p.ID = pm_last_name.post_id AND pm_last_name.meta_key = '_billing_last_name'
                LEFT JOIN {$table_prefix}postmeta AS pm_total_amount
                    ON p.ID = pm_total_amount.post_id AND pm_total_amount.meta_key = '_order_total' 
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN (%s, %s))";


        return $wpdb->get_results(
            $wpdb->prepare($query, 'wc-processing', 'wc-completed', 'wc-processing', 'wc-completed'),
            ARRAY_A
        );

    } else {


        // Use only old WooCommerce order structure (pre-8.0)
        $allowed_fields = [
            'order_id' => 'p.ID AS order_id',
            'phone_number' => "COALESCE(pm_phone.meta_value, '') AS phone_number",
            'email' => "COALESCE(pm_email.meta_value, '') AS email",
            'first_name' => 'pm_first_name.meta_value AS first_name',
            'last_name' => 'pm_last_name.meta_value AS last_name',
            'status' => 'p.post_status AS status',
            'order_date' => 'p.post_date AS order_date',
            'total_amount' => 'pm_total_amount.meta_value AS total_amount',
        ];
        $safe_fields = array_intersect($fields, $allowed_fields);

        if (empty($safe_fields)) {
            die("return");
            return [];
        }
        $fields_sql = implode(', ', array_map(function ($field) use ($allowed_fields) {
            return $allowed_fields[$field];
        }, $safe_fields));

        $query = "
                SELECT DISTINCT {$fields_sql}
                FROM {$wpdb->prefix}posts AS p
                LEFT JOIN {$wpdb->prefix}postmeta AS pm_phone
                    ON p.ID = pm_phone.post_id AND pm_phone.meta_key = '_billing_phone'
                LEFT JOIN {$wpdb->prefix}postmeta AS pm_email
                    ON p.ID = pm_email.post_id AND pm_email.meta_key = '_billing_email'
                LEFT JOIN {$wpdb->prefix}postmeta AS pm_first_name
                    ON p.ID = pm_first_name.post_id AND pm_first_name.meta_key = '_billing_first_name'
                LEFT JOIN {$wpdb->prefix}postmeta AS pm_last_name
                    ON p.ID = pm_last_name.post_id AND pm_last_name.meta_key = '_billing_last_name'
                LEFT JOIN {$wpdb->prefix}postmeta AS pm_total_amount
                    ON p.ID = pm_total_amount.post_id AND pm_total_amount.meta_key = '_order_total'
                WHERE p.post_type = 'shop_order'
                AND p.post_status IN (%s, %s)";
        
        return $wpdb->get_results(
            $wpdb->prepare($query, 'wc-processing', 'wc-completed'),
            ARRAY_A
        );
    }
}


// Function to get customers with cancelled orders
function ssrSales2000_build_get_cancelled_orders_query($fields) {
    global $wpdb;

    $allowed_fields = [
        'order_id' => 'o.id AS order_id',
        'phone_number' => "COALESCE(oa.phone, '') AS phone_number",
        'email' => "COALESCE(oa.email, '') AS email",
        'first_name' => 'oa.first_name AS first_name',
        'last_name' => 'oa.last_name AS last_name',
        'status' => 'o.status AS status',
        'order_date' => 'o.date_created_gmt AS order_date',
        'total_amount' => 'o.total_amount AS total_amount',
    ];
    $safe_fields = array_intersect($fields, $allowed_fields);

    if (empty($safe_fields)) {
        die("return");
        return [];
    }

    $fields_sql = implode(', ', $safe_fields);
    

    // Join fields into a comma-separated string for SQL
    $query = "
        SELECT DISTINCT {$fields_sql}
        FROM {$wpdb->prefix}wc_orders AS o
        LEFT JOIN {$wpdb->prefix}wc_order_addresses AS oa 
            ON o.id = oa.order_id AND oa.address_type = 'billing'
        WHERE o.status = %s";

    return $wpdb->get_results(
        $wpdb->prepare($query, 'wc-cancelled'),
        ARRAY_A
    );
}

// Function to get customers who ordered before but not in the last recent days
function ssrSales2000_build_get_customers_no_recent_purchase_query($fields, $days) {
    global $wpdb;

    $days = intval($days);
    if ($days < 0 || $days > 99999) {
        die("Invalid number of days");
        return [];
    }

    $allowed_fields = [
        'order_id' => 'o.id AS order_id',
        'phone_number' => "COALESCE(oa.phone, '') AS phone_number",
        'email' => "COALESCE(oa.email, '') AS email",
        'first_name' => 'oa.first_name AS first_name',
        'last_name' => 'oa.last_name AS last_name',
        'status' => 'o.status AS status',
        'order_date' => 'o.date_created_gmt AS order_date',
        'total_amount' => 'o.total_amount AS total_amount',
    ];
    $safe_fields = array_intersect($fields, $allowed_fields);

    if (empty($safe_fields)) {
        die("return");
        return [];
    }

    $fields_sql = implode(', ', $safe_fields);


    // Get the date X days ago
    $date_x_days_ago = gmdate('Y-m-d', strtotime("-$days days"));
    

    $query = "
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
    ";

    return $wpdb->get_results(
        $wpdb->prepare($query, $date_x_days_ago, $date_x_days_ago),
        ARRAY_A
    );
}

// Function to get customers with high-value purchases
function ssrSales2000_build_get_high_value_customers_query($fields, $min_value) {
    global $wpdb;
    $min_value = floatval($min_value);
    // die(print_r($min_value, true));
    if ($min_value < 0 || $min_value > 9999999999) {
        die("Invalid number of min value");
        return [];
    }


    $allowed_fields = [
        'order_id' => 'o.id AS order_id',
        'phone_number' => "COALESCE(oa.phone, '') AS phone_number",
        'email' => "COALESCE(oa.email, '') AS email",
        'first_name' => 'oa.first_name AS first_name',
        'last_name' => 'oa.last_name AS last_name',
        'status' => 'o.status AS status',
        'order_date' => 'o.date_created_gmt AS order_date',
        'total_amount' => 'o.total_amount AS total_amount',
    ];
    $safe_fields = array_intersect($fields, $allowed_fields);

    if (empty($safe_fields)) {
        die("return");
        return [];
    }

    $fields_sql = implode(', ', $safe_fields);


    $query = "
        SELECT DISTINCT $fields_sql
        FROM {$wpdb->prefix}wc_orders AS o
        LEFT JOIN {$wpdb->prefix}wc_order_addresses AS oa 
            ON o.id = oa.order_id AND oa.address_type = 'billing'
        WHERE o.status IN ('wc-completed', 'wc-processing')
        AND o.total_amount >= %f
    ";

    return $wpdb->get_results(
        $wpdb->prepare($query, $min_value),
        ARRAY_A
    );
}



// Function to display results in a table
function ssrSales2000_display_results_table($selected_fields, $available_fields, $results) {
    echo '<table class="widefat striped"><thead><tr>';
    foreach ($selected_fields as $field) {
        echo "<th>".esc_html($available_fields[$field])."</th>";
    }
    echo '</tr></thead><tbody>';

    foreach ($results as $row) {
        echo '<tr>';
        foreach ($selected_fields as $field) {
            ssrSales2000_display_field_value($field, $row);
        }
        echo '</tr>';
    }

    echo '</tbody></table>';
}

// Function to display the value for each field, formatting "order_date"
function ssrSales2000_display_field_value($field, $row) {
    if ($field == 'order_date') {
        $formatted_date = isset($row[$field]) ? gmdate('Y-m-d H:i:s', strtotime($row[$field])) : '';
        echo "<td>".esc_html($formatted_date)."</td>";
    } else {
        if ($field == 'phone_number'){
            // echo "phone number: ". $row[$field] . "<br>";
        }
        echo '<td>' . esc_html($row[$field]) . '</td>';
    }
}


add_action('admin_post_export_csv', 'ssrSales2000_handle_export_csv');

function ssrSales2000_handle_export_csv(){
    
    // Handle CSV Export
    // if (isset($_POST['export_csv'])) {

        if ( ! isset( $_POST['export_csv_nonce'] ) || ! wp_verify_nonce( $_POST['export_csv_nonce'], 'export_csv_nonce' )) {
            wp_die( 'Security check failed.' );
        }
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to export this data.' );
        }

        if (!empty($_POST['export_data']) && !empty($_POST['selected_fields'])) {
            $export_results = json_decode(wp_unslash($_POST['export_data']), true);
            $selected_fields = json_decode(wp_unslash($_POST['selected_fields']), true);

            array_walk_recursive($export_results, function (&$value) {
                $value = mb_convert_encoding($value, 'UTF-8', 'auto');
            });
            

            if (!empty($export_results)) {
                ssrSales2000_export_csv($export_results, ssrSales2000_get_available_fields(), $selected_fields);
                exit;
            }
        }
        echo '<p>No data found for export.</p>';
    // }

}


function ssrSales2000_standardize_phone_number($phone_number) {
    // Remove any non-digit characters (just in case there's anything like spaces or dashes)
    $phone_number = preg_replace('/\D/', '', $phone_number);
    
    // If the number starts with +98, replace it with 0
    if (substr($phone_number, 0, 2) === '98' && strlen($phone_number) === 12) {

        // die("test phone : ". $phone_number);
        // Remove the +98 and prepend 0
        return '0' . substr($phone_number, 2);
    }
    
    // If the number starts with 0 and is 11 digits, we leave it as it is
    if (substr($phone_number, 0, 1) === '0' && strlen($phone_number) === 11) {
        return $phone_number;
    }
    
    // If the number is 10 digits (without +98 or 0), add a 0 at the beginning
    if (strlen($phone_number) === 10) {
        return '0' . $phone_number;
    }

    // If the phone number doesn't match any of the above conditions, return it as is
    // You could also return an empty string or some default message if needed
    return $phone_number;
}