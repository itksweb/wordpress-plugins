<?php
/**
 * Plugin Name: WooCommerce Share Buttons
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins
 * Description: Adds custom social share buttons (Facebook, X, WhatsApp, TikTok, Instagram, Copy Link) to WooCommerce product pages.
 * Version: 1.2.0
 * Author: Kingsley Ikpefan
 * Text Domain: woocommerce-share-buttons
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class WooCommerce_Share_Buttons {

    private $options;

    public function __construct() {
        $this->options = get_option( 'wc_share_buttons_settings', [
            'facebook'  => 1,
            'twitter'   => 1,
            'whatsapp'  => 1,
            'tiktok'    => 1,
            'instagram' => 1,
            'copy'      => 1,
        ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'woocommerce_single_product_summary', [ $this, 'render_share_buttons' ], 35 );
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
    }

    public function enqueue_assets() {
        wp_enqueue_style(
            'wc-share-buttons',
            plugin_dir_url( __FILE__ ) . 'assets/css/custom-share-buttons.css'
        );

        wp_enqueue_script(
            'wc-share-buttons',
            plugin_dir_url( __FILE__ ) . 'assets/js/custom-share-buttons.js',
            [],
            false,
            true
        );

        wp_enqueue_style(
            'font-awesome',
            'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css'
        );
    }

    public function render_share_buttons() {
        global $product;
        if ( ! $product ) return;

        $product_url   = urlencode( get_permalink( $product->get_id() ) );
        $product_title = urlencode( $product->get_name() );
        $product_img   = wp_get_attachment_url( $product->get_image_id() );

        ?>
        <div class="custom-share-buttons" style="margin-top:20px;">
            <h4><?php esc_html_e( 'Share this product:', 'woocommerce-share-buttons' ); ?></h4>

            <?php if ( ! empty( $this->options['facebook'] ) ) : ?>
                <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $product_url; ?>"
                   target="_blank" rel="noopener" class="share-btn fb" title="Share on Facebook">
                    <i class="fab fa-facebook-f"></i>
                </a>
            <?php endif; ?>

            <?php if ( ! empty( $this->options['twitter'] ) ) : ?>
                <a href="https://twitter.com/intent/tweet?text=<?php echo $product_title; ?>&url=<?php echo $product_url; ?>&image=<?php echo $product_image; ?>"
                    target="_blank" rel="noopener" class="share-btn tw" title="Share on X">
                    <i class="fab fa-x-twitter"></i>
                </a>
            <?php endif; ?>

            <?php if ( ! empty( $this->options['whatsapp'] ) ) : ?>
                <a href="https://api.whatsapp.com/send?text=<?php echo $product_title . '%0A' . $product_image . '%0A' . $product_url; ?>"
                    target="_blank" rel="noopener" class="share-btn wa" title="Share on WhatsApp">
                    <i class="fab fa-whatsapp"></i>
                </a>
            <?php endif; ?>

            <?php if ( ! empty( $this->options['tiktok'] ) ) : ?>
                <button class="share-btn tk copy" data-url="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" title="Copy link for TikTok">
                    <i class="fab fa-tiktok"></i>
                </button>
            <?php endif; ?>

            <?php if ( ! empty( $this->options['instagram'] ) ) : ?>
                <button class="share-btn ig copy" data-url="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" title="Copy link for Instagram">
                    <i class="fab fa-instagram"></i>
                </button>
            <?php endif; ?>

            <?php if ( ! empty( $this->options['copy'] ) ) : ?>
                <button class="share-btn copy_link copy" data-url="<?php echo esc_url( get_permalink( $product->get_id() ) ); ?>" title="Copy product link">
                    <i class="fas fa-link"></i>
                </button>
            <?php endif; ?>
        </div>
        <?php
    }

    /* === Admin Settings === */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'Share Buttons',
            'Share Buttons',
            'manage_options',
            'wc-share-buttons',
            [ $this, 'settings_page' ]
        );
    }

    public function register_settings() {
        register_setting( 'wc_share_buttons_group', 'wc_share_buttons_settings' );
    }

    public function settings_page() {
        $options = get_option( 'wc_share_buttons_settings' );
        ?>
        <div class="wrap">
            <h1>WooCommerce Share Buttons</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'wc_share_buttons_group' ); ?>
                <table class="form-table">
                    <tr><th>Enable Networks</th><td>
                        <label><input type="checkbox" name="wc_share_buttons_settings[facebook]" value="1" <?php checked( !empty($options['facebook']) ); ?>> Facebook</label><br>
                        <label><input type="checkbox" name="wc_share_buttons_settings[twitter]" value="1" <?php checked( !empty($options['twitter']) ); ?>> X (Twitter)</label><br>
                        <label><input type="checkbox" name="wc_share_buttons_settings[whatsapp]" value="1" <?php checked( !empty($options['whatsapp']) ); ?>> WhatsApp</label><br>
                        <label><input type="checkbox" name="wc_share_buttons_settings[tiktok]" value="1" <?php checked( !empty($options['tiktok']) ); ?>> TikTok</label><br>
                        <label><input type="checkbox" name="wc_share_buttons_settings[instagram]" value="1" <?php checked( !empty($options['instagram']) ); ?>> Instagram</label><br>
                        <label><input type="checkbox" name="wc_share_buttons_settings[copy]" value="1" <?php checked( !empty($options['copy']) ); ?>> Copy Link</label>
                    </td></tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}

new WooCommerce_Share_Buttons();
