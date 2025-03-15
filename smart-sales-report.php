<?php
/**
 * Plugin Name: Smart Sales Report - Boost Sales & Retain Customers
 * Plugin URI: https://github.com/smhlotfi/woocommerce-sale-booster.git
 * Description: Increase your sale 70% by getting valuable customer insights like paid customers, canceled customers, inactive customers, and high-value buyers.
 * Version: 1.0.0
 * Author: smhlotfizadeh
 * Author URI: https://profiles.wordpress.org/smhlotfizadeh/
 * Tested up to: 6.7
 * Stable tag: 1.0.0
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Include necessary files
require_once plugin_dir_path(__FILE__) . 'includes/asset_controller/css-import-handler.php';

require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/export-functions.php';
require_once plugin_dir_path(__FILE__) . 'includes/pages/paid-customers-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/pages/cancelled-customers-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/pages/ordered-before-not-recent-days-customers-page.php';
require_once plugin_dir_path(__FILE__) . 'includes/pages/ordered-big-customers-page.php';

require_once plugin_dir_path(__FILE__) . 'includes/sales_trend/index.php';


$allowed_tabs = ['paid-customers', 'cancelled-customers', 'big-purchase-customers', 'ordered-before-not-recent-days'];


// Plugin page content
function custom_exporter_page() {

    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'paid-customers';

    // Validation
    if (!in_array($active_tab, $allowed_tabs)) {
        $active_tab = 'paid-customers'; // Default value
    }

    ?>
    <div class="wrap">
        <h2>WooCommerce Marketing Settings</h2>
        <h2 class="nav-tab-wrapper">
            <a href="?page=smart-sales-report&tab=paid-customers" class="nav-tab <?php echo $active_tab == 'paid-customers' ? 'nav-tab-active' : ''; ?>">Paid Customers</a>
            <a href="?page=smart-sales-report&tab=cancelled-customers" class="nav-tab <?php echo $active_tab == 'cancelled-customers' ? 'nav-tab-active' : ''; ?>">Cancelled Customers</a>
            <a href="?page=smart-sales-report&tab=ordered-before-not-recent-days" class="nav-tab <?php echo $active_tab == 'ordered-before-not-recent-days' ? 'nav-tab-active' : ''; ?>">No Recent Purchase</a>
            <a href="?page=smart-sales-report&tab=big-purchase-customers" class="nav-tab <?php echo $active_tab == 'big-purchase-customers' ? 'nav-tab-active' : ''; ?>">Big Purchase Customers</a>
        </h2>

        <div class="tab-content">
            <?php
            if ($active_tab == 'paid-customers') {
                echo '<h3>Paid Customers</h3>';
                paid_customers_page();
            } elseif ($active_tab == 'cancelled-customers') {
                echo '<h3>Cancelled Customers</h3>';
                cancelled_customers_page();
            } elseif ($active_tab == 'ordered-before-not-recent-days') {
                echo '<h3>Customers Who Ordered Before, but not in these last days</h3>';
                ordered_before_not_recent_days_customers_page();
            } elseif ($active_tab == 'big-purchase-customers') {
                echo '<h3>Customers Who have spent big on their purchases</h3>';
                ordered_big_customers_page();
            }
            ?>
        </div>
    </div>


    <?php
    
    
}










