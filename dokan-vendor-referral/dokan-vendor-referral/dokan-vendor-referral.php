<?php
/**
 * Plugin Name: Dokan Bespoke Vendor Referral
 * Description: Automated Vendor-to-Vendor affiliate system with Dokan Dashboard integration.
 * Version: 1.3
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
        
        // 3. Template Loading
        add_action( 'dokan_load_custom_template', array( $this, 'load_template' ) );
        
        // 4. Endpoints & Rewrites
        add_action( 'init', array( $this, 'add_endpoint' ) );
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        // 5. Commission Engine
        add_action( 'woocommerce_order_status_completed', array( $this, 'log_commission' ), 20, 1 );
        
        // 6. Financial Integration
        add_filter( 'dokan_get_seller_balance', array( $this, 'inject_into_balance' ), 10, 2 );
        add_filter( 'dokan_get_seller_earnings', array( $this, 'inject_into_stats' ), 10, 2 );
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

    public function load_template( $query_vars ) {
        if ( isset( $query_vars['affiliate-center'] ) ) {
            // Looks for referrals.php in your-theme/dokan/referrals.php
            dokan_get_template_part( 'referrals', '', array( 'is_plugin' => false ) );
        }
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
}

/**
 * TEMPORARY: Wipe all referral data for a clean start
 * Refresh your dashboard ONCE after adding this, then DELETE it.
 */
// add_action( 'init', function() {
//     if ( ! current_user_can( 'manage_options' ) ) return;

//     global $wpdb;

//     // 1. Clear the "Who referred whom" connections
//     $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'referred_by_vendor'" );

//     // 2. Clear the "Lifetime Earnings" cache field
//     $wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = 'affiliate_total_earnings'" );

//     // 3. Clear the commission logs from all Orders
//     $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_referral_commission_amount', '_referral_commission_recipient', '_referral_paid_to')" );

//     // 4. Clear the referral cookie from your current browser
//     setcookie('dokan_affiliate_id', '', time() - 3600, "/");
    
//     // Optional: Log to error log so you know it worked
//     error_log('Dokan Referral System: Database Cleared.');
// });

new Dokan_Bespoke_Referral();