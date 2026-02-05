<?php
/**
 * Plugin Name: DelightEDU Pro
 * Description: High-performance School Management System.
 * Version: 1.0.0
 * Author: Kingsley Ikpefan
 * Author URI: https://wa.me/2348060719978
 * Plugin URI: https://github.com/itksweb/wordpress-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. GLOBAL CONSTANTS
// Defined at the very top so they are available to all namespaced files immediately.
define( 'DEDU_PATH', plugin_dir_path( __FILE__ ) );
define( 'DEDU_URL',  plugin_dir_url( __FILE__ ) );
define( 'DEDU_FILE', __FILE__ );

// 2. LOAD AUTOLOADER 
// We only need to do this once.
require_once DEDU_PATH . 'includes/Autoloader.php';

// 3. NAMESPACES
use DelightEDU\Database\Schema;
use DelightEDU\Controllers\Admin\Menu;
use DelightEDU\Controllers\Admin\PostHandler;

// 4. ACTIVATION HOOK
// Moved outside the class to ensure it registers correctly with WordPress.
register_activation_hook( DEDU_FILE, [ Schema::class, 'install' ] );

// 5. MAIN PLUGIN CLASS
final class DelightEDU {

    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init_hooks();
    }

    private function init_hooks() {
        // Initialize Admin components only when in the dashboard
        if ( is_admin() ) {
            new Menu();
            new PostHandler();
        }
    }
}

// 6. KICKSTART
DelightEDU::instance();