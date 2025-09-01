<?php
/**
 * Plugin Name: My First Real Plugin
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins
 * Description: This is but my first test plugin in Wordpress development.
 * Version: 1.1.0
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2
 */

// Include mfp-functions.php, use require_once to stop the script if mfp-functions.php is not found
require_once plugin_dir_path(__FILE__) . 'includes/mfp-functions.php';

/*
 * Add my new menu to the Admin Control Panel
 */
// Hook the 'admin_menu' action hook, run the function named 'mfp_Add_My_Admin_Link()'
add_action( 'admin_menu', 'mfp_Add_My_Admin_Link' );
// Add a new top level menu link to the ACP
function mfp_Add_My_Admin_Link()
{
      add_menu_page(
        'My First Page', // Title of the page
        'My First Plugin', // Text to show on the menu link
        'manage_options', // Capability requirement to see the link
        'includes/mfp-first-acp-page.php' // The 'slug' - file to display when clicking the link
    );
}