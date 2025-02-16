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


// function my_custom_plugin_menu() {
//     add_menu_page('My Custom Plugin Settings', 'My Custom Plugin', 'manage_options', 'my-custom-plugin', 'my_custom_plugin_settings_page');
// }
// add_action('admin_menu', 'my_custom_plugin_menu');

// function my_custom_plugin_settings_page() {
//     echo '<h1>My Custom Plugin Settings</h1>';
//     // Add your settings form here
// }

