<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BK_Referral_Assets {
    public function __construct() {
        // We use priority 20 to ensure we run AFTER Dokan registers its variables
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), 20 );
    }

    public function enqueue_assets() {
        // Let's make the check more robust
        $is_affiliate_page = get_query_var( 'affiliate-center' );
        $is_dokan = function_exists( 'dokan_is_seller_dashboard' ) && dokan_is_seller_dashboard();

        if ( $is_affiliate_page || $is_dokan ) {
            
            // Calculate the URL dynamically to avoid constant issues
            $plugin_url = plugin_dir_url( dirname( __FILE__ ) );

            // 1. JS
            wp_enqueue_script( 
                'bk-referral-js', 
                $plugin_url . 'assets/js/referral-dashboard.js', 
                array('jquery'), // Add jquery as dependency just in case
                '1.3', 
                true 
            );

            wp_localize_script( 'bk-referral-js', 'bk_vars', array(
                'vendorRefId' => get_current_user_id(),
                'siteDomain'  => $_SERVER['HTTP_HOST'],
                'ajax_url'    => admin_url( 'admin-ajax.php' )
            ) );
            
            // 2. CSS
            wp_enqueue_style( 
                'bk-referral-style', 
                $plugin_url . 'assets/css/referral-dashboard.css', 
                array(), 
                '1.3' 
            );
            
            wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' );
        }
    }
}