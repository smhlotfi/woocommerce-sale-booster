<?php
// Enqueue styles for the admin panel
function ssr_enqueue_admin_styles($hook) {
    if ($hook !== 'smart-sales-report_page_ssr-sales-trend') { // Adjust based on your menu slug
        return;
    }

    wp_enqueue_style('ssr-admin-style', plugin_dir_url(dirname(__FILE__, 2)) . 'assets/css/admin-style.css', array(), '1.0.0');
}
add_action('admin_enqueue_scripts', 'ssr_enqueue_admin_styles');
