<?php
// Function to build the SQL query for fetching orders
function ssrSales2000_build_get_completed_or_processing_query($sql_fields) {
    global $wpdb;
    $fields = implode(', ', $sql_fields);
    return "
        SELECT $fields
        FROM {$wpdb->prefix}posts AS p
        INNER JOIN {$wpdb->prefix}postmeta AS pm ON p.ID = pm.post_id
        WHERE p.post_type = 'shop_order'
        AND p.post_status IN ('wc-completed', 'wc-processing')
    ";
}

// Function to generate the SQL fields based on the selected ones
// function ssrSales2000_generate_sql_fields($selected_fields) {
//     return array_map(function ($field) {
//         return "`$field`";
//     }, $selected_fields);
// }
