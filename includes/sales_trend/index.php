<?php

// Callback function to display the sales trend page
function sb_display_sales_trend() {

    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'paid-customers';
    ?>
    <div class="wrap">
        <h2>Sales Trend</h2>
        <h2 class="nav-tab-wrapper">
            <a href="?page=sb-sales-trend&tab=paid-customers" class="nav-tab <?php echo $active_tab == 'paid-customers' ? 'nav-tab-active' : ''; ?>">Paid Customers</a>
            <a href="?page=sb-sales-trend&tab=cancelled-customers" class="nav-tab <?php echo $active_tab == 'cancelled-customers' ? 'nav-tab-active' : ''; ?>">Cancelled Customers</a>
            <a href="?page=sb-sales-trend&tab=ordered-before-not-recent-days" class="nav-tab <?php echo $active_tab == 'ordered-before-not-recent-days' ? 'nav-tab-active' : ''; ?>">No Recent Purchase</a>
            <a href="?page=sb-sales-trend&tab=big-purchase-customers" class="nav-tab <?php echo $active_tab == 'big-purchase-customers' ? 'nav-tab-active' : ''; ?>">Big Purchase Customers</a>
        </h2>

        <div class="tab-content">
            <?php
            if ($active_tab == 'paid-customers') {
                echo '<h3>Paid Customers</h3>';
                ?>
                <div class="wrap">
                    <canvas id="salesChart" width="400" height="200"></canvas>

                </div>
                <?php
                // paid_customers_page();
            } elseif ($active_tab == 'cancelled-customers') {
                echo '<h3>Cancelled Customers</h3>';
                // cancelled_customers_page();
            } elseif ($active_tab == 'ordered-before-not-recent-days') {
                echo '<h3>Customers Who Ordered Before, but not in these last days</h3>';
                // ordered_before_not_recent_days_customers_page();
            } elseif ($active_tab == 'big-purchase-customers') {
                echo '<h3>Customers Who have spent big on their purchases</h3>';
                // ordered_big_customers_page();
            }
            ?>
        </div>
    </div>


    <?php
}


// Fetch completed orders per day
function sb_get_sales_data() {
    global $wpdb;
    // Get order counts per day for completed and canceled orders
    $results = $wpdb->get_results("
        SELECT DATE(o.date_created_gmt) as order_date,
               SUM(CASE WHEN o.status IN ('wc-completed', 'wc-processing') THEN 1 ELSE 0 END) AS completed_orders,
               SUM(CASE WHEN o.status = 'wc-cancelled' THEN 1 ELSE 0 END) AS cancelled_orders
        FROM {$wpdb->prefix}wc_orders AS o
        WHERE o.status IN ('wc-completed', 'wc-processing', 'wc-cancelled')
        GROUP BY order_date
        ORDER BY order_date ASC
    ", ARRAY_A);

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
