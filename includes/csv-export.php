<?php

function ssrSales2000_export_csv($results, $available_fields, $selected_fields) {
    // Ensure the WP_Filesystem is loaded
    if ( empty( $wp_filesystem ) ) {
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        WP_Filesystem();
    }

    if (empty($results)) {
        wp_die('No data found for export.');
    }

    // Set headers for CSV file download
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="order_data.csv"');

    // Open the PHP output stream (not using WP_Filesystem here, because it's for file writing)
    $output = fopen('php://output', 'w');
    
    // Set UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Column headers
    $header = array_map(function($field) use ($available_fields) {
        return $available_fields[$field];
    }, $selected_fields);
    fputcsv($output, $header);

    // Write rows of data
    // die(print_r($results, true));
    foreach ($results as $row) {
        $row_data = [];
        foreach ($selected_fields as $field) {
            $row_data[] = $row[$field];
        }
        fputcsv($output, $row_data);
    }

    // Close the output stream after writing data
    fclose($output);

    exit;
}
