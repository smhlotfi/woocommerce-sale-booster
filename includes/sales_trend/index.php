<?php

// Callback function to display the sales trend page
function sb_display_sales_trend() {
    ?>
    <div class="wrap">
        <h1>Sales Trend</h1>
        <canvas id="salesTrendChart"></canvas>
    </div>
    <?php
}


// Fetch completed orders per day
function sb_get_sales_data() {
    global $wpdb;
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT DATE(o.date_created_gmt) as order_date, COUNT(*) as order_count
        FROM {$wpdb->prefix}wc_orders AS o
        WHERE o.status IN (%s, %s)
        GROUP BY DATE(o.date_created_gmt)
        ORDER BY order_date ASC;
    ", 'wc-completed', 'wc-processing'));

    // Debugging: Output raw results and stop execution
    // Check on : DOMAIN/wp-admin/admin-ajax.php?action=sb_get_sales_data
    // echo '<pre>';
    // print_r($results);
    // echo '</pre>';
    // wp_die();

    
    wp_send_json($results); // Send data as JSON
}
add_action('wp_ajax_sb_get_sales_data', 'sb_get_sales_data');



// Enqueue scripts for Chart.js
function sb_enqueue_admin_scripts($hook) {
    if ($hook !== 'sale-booster_page_sb-sales-trend') {
        return;
    }
    
    wp_enqueue_script('chartjs', 'https://cdn.jsdelivr.net/npm/chart.js', array(), null, true);
    
    // Load fallback local version in case CDN fails
    wp_add_inline_script('chartjs-cdn', '
        if (typeof Chart === "undefined") {
            var script = document.createElement("script");
            script.src = "' . plugin_dir_url(__FILE__) . 'assets/js/chart.js";
            document.head.appendChild(script);
        }
    ');


    wp_enqueue_script('sb-sales-chart', plugin_dir_url(__FILE__) . 'sales-trend.js', array('jquery', 'chartjs'), null, true);
    
    wp_localize_script('sb-sales-chart', 'sb_sales_data', array(
        'ajax_url' => admin_url('admin-ajax.php'),
    ));
}
add_action('admin_enqueue_scripts', 'sb_enqueue_admin_scripts');
