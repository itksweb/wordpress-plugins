<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class BK_Referral_Admin {
    public function __construct() {
        // Run admin_menu at priority 99 to ensure Dokan's menu exists first
        add_action( 'admin_menu', array( $this, 'add_menu' ), 99 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_menu() {
        // We use 'dokan' as the parent slug. 
        // If 'dokan' doesn't work, we'll try a top-level menu as a test.
        add_submenu_page(
            'dokan', 
            __( 'Referral Settings', 'webhouse' ),
            __( 'Referral Settings', 'webhouse' ),
            'manage_options',
            'bk-referral-settings', 
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'bk_referral_options_group', 'bk_referral_commission_rate' );
        register_setting( 'bk_referral_options_group', 'bk_referral_cookie_expiry' );
    }

    public function render_settings_page() {
        // Double check permissions inside the method
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php _e( 'Referral Program Settings', 'webhouse' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'bk_referral_options_group' );
                do_settings_sections( 'bk_referral_options_group' );
                ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e( 'Commission Rate (%)', 'webhouse' ); ?></th>
                        <td>
                            <input type="number" step="0.01" name="bk_referral_commission_rate" value="<?php echo esc_attr( get_option('bk_referral_commission_rate', '5') ); ?>" class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e( 'Cookie Duration (Days)', 'webhouse' ); ?></th>
                        <td>
                            <input type="number" name="bk_referral_cookie_expiry" value="<?php echo esc_attr( get_option('bk_referral_cookie_expiry', '30') ); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}