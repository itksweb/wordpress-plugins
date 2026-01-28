<?php
/**
 * Plugin Name: Dokan Bespoke Vendor Referral
 * Description: Automated Vendor-to-Vendor affiliate system with Dokan Dashboard integration.
 * Version: 2.0
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2 or later
 * Text Domain: webhouse
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Dokan_Bespoke_Referral {

    public function __construct() {
        // 1. Tracking & Registration
        add_action( 'init', array( $this, 'set_cookie' ) );
        add_action( 'dokan_new_seller_created', array( $this, 'assign_referrer' ), 10, 2 );

        // 2. Dashboard Navigation & Permissions
        add_filter( 'dokan_get_dashboard_nav', array( $this, 'add_menu' ) );
        add_filter( 'dokan_query_var_filter', array( $this, 'register_query_var' ) );
        add_filter( 'dokan_dashboard_nav_active', array( $this, 'fix_permission_and_active_state' ) );
        
        //3. The Template Loader Hook
            /**** 1. Tell Dokan to trigger our template when the URL is hit ****/
            add_action( 'dokan_load_custom_template', array( $this, 'trigger_referral_template' ) );
            /**** 2. Tell Dokan WHERE the file actually lives (The GPS) ****/ 
            add_filter( 'dokan_get_template_part', array( $this, 'locate_template_in_plugin' ), 10, 3 );
        
        // 4. Endpoints & Rewrites
        add_action( 'init', array( $this, 'add_endpoint' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        // 5. Commission Engine
        add_action( 'woocommerce_order_status_completed', array( $this, 'log_commission' ), 20, 1 );
        
        // 6. Financial Integration
        add_filter( 'dokan_get_seller_balance', array( $this, 'inject_into_balance' ), 10, 2 );
        add_filter( 'dokan_get_seller_earnings', array( $this, 'inject_into_stats' ), 10, 2 );

        // 7. Hook to load CSS and js
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_referral_assets' ) );

    }

    public function set_cookie() {
        if ( isset( $_GET['ref'] ) ) {
            setcookie( 'dokan_affiliate_id', intval( $_GET['ref'] ), time() + ( 86400 * 30 ), "/" );
        }
    }

    public function assign_referrer( $vendor_id, $data ) {
        if ( isset( $_COOKIE['dokan_affiliate_id'] ) ) {
            $referrer_id = intval( $_COOKIE['dokan_affiliate_id'] );
            if ( $vendor_id !== $referrer_id ) {
                update_user_meta( $vendor_id, 'referred_by_vendor', $referrer_id );
            }
        }
    }

    public function add_menu( $urls ) {
        $urls['affiliate-center'] = array(
            'title' => __( 'Affiliate Center', 'dokan-lite' ),
            'icon'  => '<i class="fas fa-share-alt"></i>',
            'url'   => dokan_get_navigation_url( 'affiliate-center' ),
            'pos'   => 55,
        );
        return $urls;
    }

    public function register_query_var( $vars ) {
        $vars[] = 'affiliate-center';
        return $vars;
    }

    public function add_endpoint() {
        add_rewrite_endpoint( 'affiliate-center', EP_PAGES );
    }

    public function activate() {
        $this->add_endpoint();
        flush_rewrite_rules();
    }

    // THIS FIXES THE "NO PERMISSION" ERROR
    public function fix_permission_and_active_state( $active_menu ) {
        global $wp;
        if ( isset( $wp->query_vars['affiliate-center'] ) ) {
            return 'affiliate-center';
        }
        return $active_menu;
    }

    /****** Professional Template Loader  *******/
    //Part A: Trigger the loading process
    public function trigger_referral_template( $query_vars ) {
        if ( isset( $query_vars['affiliate-center'] ) ) {
            // We call the template. The Filter (Part B) will intercept this and point it to the plugin.
            dokan_get_template_part( 'referrals' );
        }
    }
    //Part B: Intercept the path and point to your plugin folder
    public function locate_template_in_plugin( $template, $slug, $name ) {
        if ( $slug === 'affiliate-center' ) {
            $plugin_path = plugin_dir_path( __FILE__ ) . 'templates/referrals.php';
            
            if ( file_exists( $plugin_path ) ) {
                return $plugin_path;
            }
        }
        return $template;
    }

    public function log_commission( $order_id ) {
        $order = wc_get_order( $order_id );
        $items = $order->get_items();
        $first_item = reset( $items );
        if ( ! $first_item ) return;

        $seller_id = get_post_field( 'post_author', $first_item->get_product_id() );
        $referrer_id = get_user_meta( $seller_id, 'referred_by_vendor', true );

        if ( $referrer_id ) {
            $commission_rate = 0.05; // 5%
            $referral_amount = $order->get_total() * $commission_rate;
            update_post_meta( $order_id, '_referral_commission_amount', $referral_amount );
            update_post_meta( $order_id, '_referral_commission_recipient', $referrer_id );
        }
    }

    public function inject_into_balance( $balance, $seller_id ) {
        return $balance + $this->get_total_referral_earnings( $seller_id );
    }

    public function inject_into_stats( $earnings, $seller_id ) {
        return $earnings + $this->get_total_referral_earnings( $seller_id );
    }

    private function get_total_referral_earnings( $user_id ) {
        global $wpdb;
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(pm.meta_value) FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->postmeta} pm2 ON pm.post_id = pm2.post_id
             WHERE pm.meta_key = '_referral_commission_amount'
             AND pm2.meta_key = '_referral_commission_recipient'
             AND pm2.meta_value = %d", $user_id
        ) );
        return (float) $total;
    }

    //Enqueue js and CSS only on the Dokan Dashboard
    public function enqueue_referral_assets() {
        // Professional check: Only load on Dokan Dashboard pages
        if ( function_exists( 'dokan_is_seller_dashboard' ) && get_query_var( 'affiliate-center') ) {

            //1. Enqueue the Script
            wp_enqueue_script( 
                'bk-referral-js', 
                plugin_dir_url( __FILE__ ) . 'assets/js/referral-dashboard.js', 
                array(), // No dependencies
                '1.0.0', 
                true // Load in footer for better performance
            );

            // 2. Localize (The Bridge)
            wp_localize_script( 'bk-referral-js', 'bk_vars', array(
                'vendorRefId' => get_current_user_id(),
                'siteDomain'  => $_SERVER['HTTP_HOST'],
                'ajax_url'    => admin_url( 'admin-ajax.php' ) // Future proofing for AJAX
            ) );
            
            wp_enqueue_style( 
                'bk-referral-style', 
                plugin_dir_url( __FILE__ ) . 'assets/css/referral-dashboard.css', 
                array(), 
                '1.0.0', 
                'all' 
            );
            
            // Also ensure FontAwesome is available for your icons
            wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css' );
        }
    }

}

new Dokan_Bespoke_Referral();