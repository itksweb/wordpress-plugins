<?php
/**
 * Plugin Name: DelightEDU Pro
 * Description: High-performance School Management System.
 * Version: 1.0.0
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Security first

// Autoloading (We'll set this up so we don't have to 'include' every file)
require_once plugin_dir_path( __FILE__ ) . 'includes/Autoloader.php';

require_once __DIR__ . '/includes/Autoloader.php';

// Now you can call classes directly without manual 'include' statements!
use DelightEDU\Database\Schema;

register_activation_hook( __FILE__, [ Schema::class, 'install' ] );

final class DelightEDU {

    /**
     * Singleton Instance
     */
    private static $instance = null;

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->define_constants();
        $this->init_hooks();
    }

    private function define_constants() {
        define( 'SEDU_PATH', plugin_dir_path( __FILE__ ) );
        define( 'SEDU_URL', plugin_dir_url( __FILE__ ) );
    }

    private function init_hooks() {
        // This is where we will trigger our Database and Controller classes
        register_activation_hook( __FILE__, [ 'SEDU\Database\Schema', 'install' ] );
    }
}

// Start the engine
DelightEDU::instance();