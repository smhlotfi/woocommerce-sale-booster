<?php
// Add an admin menu
add_action('admin_menu', 'custom_exporter_add_menu');

function custom_exporter_add_menu() {
    add_menu_page(
        'Sale Booster',
        'Sale Booster',
        'manage_options',
        'sale-booster',
        'custom_exporter_page',
        'dashicons-database-export',
        25
    );
}


// Add Sales Trend submenu page
function sb_add_sales_trend_page() {
    add_submenu_page(
        'sale-booster',             // Parent menu slug
        'Sales Trend',              // Page title
        'Sales Trend',              // Menu title
        'manage_woocommerce',       // Capability required
        'sb-sales-trend',           // Menu slug
        'sb_display_sales_trend'    // Callback function
    );
}
add_action('admin_menu', 'sb_add_sales_trend_page');

// Callback function to display the sales trend page
function sb_display_sales_trend() {
    ?>
    <div class="wrap">
        <h1>Sales Trend</h1>
        <canvas id="salesTrendChart"></canvas>
    </div>
    <?php
}