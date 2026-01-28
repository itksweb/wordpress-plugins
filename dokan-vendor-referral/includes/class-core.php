<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BK_Referral_Core {
    public function __construct() {
        add_action( 'init', array( $this, 'set_cookie' ) );
        add_action( 'dokan_new_seller_created', array( $this, 'assign_referrer' ), 10, 2 );
        add_filter( 'dokan_get_dashboard_nav', array( $this, 'add_dashboard_menu' ) );
        add_filter( 'dokan_query_var_filter', array( $this, 'register_query_var' ) );
        add_filter( 'dokan_dashboard_nav_active', array( $this, 'fix_active_state' ) );
        add_action( 'dokan_load_custom_template', array( $this, 'trigger_template' ) );
        add_filter( 'dokan_get_template_part', array( $this, 'locate_template' ), 10, 3 );
        add_action( 'woocommerce_order_status_completed', array( $this, 'log_commission' ), 20, 1 );
        add_filter( 'dokan_get_seller_balance', array( $this, 'inject_balance' ), 10, 2 );
        add_filter( 'dokan_get_seller_earnings', array( $this, 'inject_stats' ), 10, 2 );
    }

    public function set_cookie() {
        if ( isset( $_GET['ref'] ) ) {
            $days = get_option( 'bk_referral_cookie_expiry', 30 );
            setcookie( 'dokan_affiliate_id', intval( $_GET['ref'] ), time() + ( 86400 * $days ), "/" );
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

    public function add_dashboard_menu( $urls ) {
        $urls['affiliate-center'] = array(
            'title' => __( 'Affiliate Center', 'webhouse' ),
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

    public function fix_active_state( $active_menu ) {
        global $wp;
        return isset( $wp->query_vars['affiliate-center'] ) ? 'affiliate-center' : $active_menu;
    }

    public function trigger_template( $query_vars ) {
        if ( isset( $query_vars['affiliate-center'] ) ) {
            dokan_get_template_part( 'referrals' );
        }
    }

    public function locate_template( $template, $slug, $name ) {
        if ( $slug === 'referrals' ) {
            $path = plugin_dir_path( dirname(__FILE__) ) . 'templates/referrals.php';
            return file_exists( $path ) ? $path : $template;
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
            $rate = get_option( 'bk_referral_commission_rate', 5 ) / 100;
            $amount = $order->get_total() * $rate;
            update_post_meta( $order_id, '_referral_commission_amount', $amount );
            update_post_meta( $order_id, '_referral_commission_recipient', $referrer_id );
        }
    }

    public function inject_balance( $balance, $seller_id ) { return $balance + $this->get_earnings( $seller_id ); }
    public function inject_stats( $earnings, $seller_id ) { return $earnings + $this->get_earnings( $seller_id ); }

    private function get_earnings( $user_id ) {
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