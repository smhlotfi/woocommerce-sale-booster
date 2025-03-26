<?php
// Add an admin menu
add_action('admin_menu', 'ssrSales2000_exporter_add_menu');

function ssrSales2000_exporter_add_menu() {
    add_menu_page(
        'Smart Sales Report',
        'Smart Sales Report',
        'manage_options',
        'smart-sales-report',
        'ssrSales2000_exporter_page',
        'dashicons-database-export',
        25
    );
}


// Add Sales Trend submenu page
function ssrSales2000_add_sales_trend_page() {
    add_submenu_page(
        'smart-sales-report',       // Parent menu slug
        'Sales Trend',              // Page title
        'Sales Trend',              // Menu title
        'manage_woocommerce',       // Capability required
        'ssr-sales-trend',           // Menu slug
        'ssrSales2000_display_sales_trend'    // Callback function
    );
}
add_action('admin_menu', 'ssrSales2000_add_sales_trend_page');

