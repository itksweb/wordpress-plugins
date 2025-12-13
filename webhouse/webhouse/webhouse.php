<?php
/**
 * Plugin Name: WebHOUSE
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins/
 * Description: A custom plugin created to house other small plugins that help us carry out maintenance on clients website
 * Version: 1.4.2
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2 or later
 * Text Domain: webhouse
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants for easy path referencing
define( 'WEBHOUSE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEBHOUSE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load the core bootstrap class
require_once WEBHOUSE_PLUGIN_DIR . 'includes/class-webhouse-core.php';

// Kickstart the plugin
new WebHOUSE_Core();