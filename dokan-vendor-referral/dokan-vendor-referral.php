<?php
/**
 * Plugin Name: Dokan Bespoke Vendor Referral
 * Description: Automated Vendor-to-Vendor affiliate system with Dokan Dashboard integration.
 * Version: 2.1
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2 or later
 * Text Domain: webhouse
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Dokan_Bespoke_Referral {

    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
        
        // Add Endpoint and Flush on activation
        add_action( 'init', array( $this, 'add_endpoint' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
    }

    private function define_constants() {
        define( 'BK_REF_PATH', plugin_dir_path( __FILE__ ) );
        define( 'BK_REF_URL', plugin_dir_url( __FILE__ ) );
        define( 'BK_REF_VERSION', '2.1' ); // Matches your plugin version
    }

    private function includes() {
        require_once BK_REF_PATH . 'includes/class-assets.php';
        require_once BK_REF_PATH . 'includes/class-admin.php';
        require_once BK_REF_PATH . 'includes/class-core.php';
    }

    private function init_hooks() {
        // IMPORTANT: These must be instantiated!
        if ( class_exists( 'BK_Referral_Assets' ) ) {
            new BK_Referral_Assets();
        }
        if ( class_exists( 'BK_Referral_Admin' ) ) {
            new BK_Referral_Admin();
        }
        if ( class_exists( 'BK_Referral_Core' ) ) {
            new BK_Referral_Core();
        }
    }

    public function add_endpoint() {
        add_rewrite_endpoint( 'affiliate-center', EP_PAGES );
    }

    public function activate() {
        $this->add_endpoint();
        flush_rewrite_rules();
    }
}

new Dokan_Bespoke_Referral();