<?php
/**
 * Plugin Name: Sharp WP Gmail SMTP
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins
 * Description: Make use of Gmail SMTP instead of the default WordPress mail system to send all outgoing emails from your website.
 * Version: 1.1.0
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WPGmailSMTPMailer {

    private $option_name = 'wp_gmail_smtp_options';

    public function __construct() {
        // Hook to configure wp_mail
        add_action( 'phpmailer_init', [ $this, 'configure_phpmailer' ] );

        // Admin menu
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Handle test email
        add_action( 'admin_post_wp_gmail_smtp_test', [ $this, 'send_test_email' ] );
    }

    /**
     * Configure PHPMailer to use Gmail SMTP
     */
    public function configure_phpmailer( $phpmailer ) {
        $options = get_option( $this->option_name );

        if ( empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp.gmail.com';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $options['gmail_address'];
        $phpmailer->Password   = $options['gmail_password']; // Use App Password
        $phpmailer->SMTPSecure = $options['encryption'] ?? 'tls';
        $phpmailer->Port       = intval( $options['port'] ?? 587 );
        $phpmailer->From       = $options['from_email'] ?? $options['gmail_address'];
        $phpmailer->FromName   = $options['from_name'] ?? 'WordPress';
    }

    /**
     * Add settings page under Settings menu
     */
    public function add_settings_page() {
        add_options_page(
            'WP Gmail SMTP Settings',
            'WP Gmail SMTP',
            'manage_options',
            'wp-gmail-smtp',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting( 'wp_gmail_smtp_group', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_options' ]
        ] );

        add_settings_section( 'wp_gmail_smtp_section', 'SMTP Settings', null, 'wp-gmail-smtp' );

        add_settings_field( 'gmail_address', 'Gmail Address', [ $this, 'render_text_field' ], 'wp-gmail-smtp', 'wp_gmail_smtp_section', ['id' => 'gmail_address'] );
        add_settings_field( 'gmail_password', 'App Password', [ $this, 'render_password_field' ], 'wp-gmail-smtp', 'wp_gmail_smtp_section', ['id' => 'gmail_password'] );
        add_settings_field( 'from_email', 'From Email', [ $this, 'render_text_field' ], 'wp-gmail-smtp', 'wp_gmail_smtp_section', ['id' => 'from_email'] );
        add_settings_field( 'from_name', 'From Name', [ $this, 'render_text_field' ], 'wp-gmail-smtp', 'wp_gmail_smtp_section', ['id' => 'from_name'] );
        add_settings_field( 'encryption', 'Encryption (ssl/tls)', [ $this, 'render_text_field' ], 'wp-gmail-smtp', 'wp_gmail_smtp_section', ['id' => 'encryption'] );
        add_settings_field( 'port', 'SMTP Port', [ $this, 'render_text_field' ], 'wp-gmail-smtp', 'wp_gmail_smtp_section', ['id' => 'port'] );
    }

    /**
     * Sanitize input
     */
    public function sanitize_options( $input ) {
        $output = [];
        foreach ( $input as $key => $value ) {
            $output[$key] = sanitize_text_field( $value );
        }
        return $output;
    }

    /**
     * Render text input field
     */
    public function render_text_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? '';
        echo "<input type='text' id='$id' name='{$this->option_name}[$id]' value='" . esc_attr($value) . "' class='regular-text' />";
    }

    /**
     * Render password input field
     */
    public function render_password_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? '';
        echo "<input type='password' id='$id' name='{$this->option_name}[$id]' value='" . esc_attr($value) . "' class='regular-text' />";
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>WP Gmail SMTP Settings</h1>

            <?php if ( isset($_GET['test_email']) && $_GET['test_email'] === 'success' ) : ?>
                <div class="notice notice-success"><p>✅ Test email sent successfully!</p></div>
            <?php elseif ( isset($_GET['test_email']) && $_GET['test_email'] === 'fail' ) : ?>
                <div class="notice notice-error"><p>❌ Failed to send test email. Please check your settings.</p></div>
            <?php endif; ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'wp_gmail_smtp_group' );
                do_settings_sections( 'wp-gmail-smtp' );
                submit_button();
                ?>
            </form>

            <hr>
            <h2>Send Test Email</h2>
            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
                <?php wp_nonce_field( 'wp_gmail_smtp_test_nonce', 'wp_gmail_smtp_test_nonce_field' ); ?>
                <input type="hidden" name="action" value="wp_gmail_smtp_test">
                <table class="form-table">
                    <tr>
                        <th><label for="test_email_to">Recipient Email</label></th>
                        <td><input type="email" name="test_email_to" id="test_email_to" class="regular-text" required></td>
                    </tr>
                </table>
                <?php submit_button( 'Send Test Email' ); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Send test email
     */
    public function send_test_email() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['wp_gmail_smtp_test_nonce_field'] ) || ! wp_verify_nonce( $_POST['wp_gmail_smtp_test_nonce_field'], 'wp_gmail_smtp_test_nonce' ) ) {
            wp_die( 'Unauthorized request' );
        }

        $to = sanitize_email( $_POST['test_email_to'] ?? '' );
        $subject = 'WP Gmail SMTP Test Email';
        $message = 'This is a test email sent from your WordPress site using Gmail SMTP settings.';

        $sent = wp_mail( $to, $subject, $message );

        if ( $sent ) {
            wp_redirect( admin_url( 'options-general.php?page=wp-gmail-smtp&test_email=success' ) );
        } else {
            wp_redirect( admin_url( 'options-general.php?page=wp-gmail-smtp&test_email=fail' ) );
        }
        exit;
    }
}

new WPGmailSMTPMailer();
