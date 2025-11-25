<?php
/**
 * Plugin Name: WEBHOUSE
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins/gmail-smtp
 * Description: Make use of Gmail SMTP instead of the default WordPress mail system to send all outgoing emails from your website.
 *  Version: 1.1.0
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPLv2 or later
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Modern_Gmail_SMTP {
    /** Option name used to store all plugin settings as one array */
    private $option_name = 'modern_gmail_smtp_options';

    /** Admin page slug */
    private $page_slug = 'modern-gmail-smtp';

    /** Current version */
    private $version = '1.1.0';

    public function __construct() {
        // Settings and admin UI
        add_action( 'admin_menu', [ $this, 'maybe_add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // PHPMailer integration
        add_action( 'phpmailer_init', [ $this, 'configure_phpmailer' ] );

        // Form handlers for testing (admin-post)
        add_action( 'admin_post_modern_gmail_smtp_test', [ $this, 'handle_send_test_email' ] );
        add_action( 'admin_post_modern_gmail_smtp_connection_test', [ $this, 'handle_connection_test' ] );
    }

    /**
     * Adds admin menu and subpages. We use a top-level menu with a single settings page.
     */
    public function maybe_add_admin_menu() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Main menu: Webhouse
        add_menu_page(
            'Webhouse',
            'Webhouse',
            'manage_options',
            'webhouse-main',
            function() {
                echo '<div class="wrap"><h1>Welcome to Webhouse</h1><p>Select a module from the submenu.</p></div>';
            },
            'dashicons-admin-generic',
            59
        );

        // Submenu: Gmail SMTP
        add_submenu_page(
            'webhouse-main',
            'Gmail SMTP Settings',
            'Gmail SMTP',
            'manage_options',
            'gmail-smtp',
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Register a single option and the settings sections/fields (Settings API)
     */
    public function register_settings() {
        // Register the single option with a sanitize callback
        register_setting( 'modern_gmail_smtp_group', $this->option_name, [ $this, 'sanitize_options' ] );

        // === SMTP Section ===
        add_settings_section(
            'mgs_section_smtp',
            'SMTP Connection',
            function() {
                echo '<p>Configure SMTP host, port and credentials.</p>';
            },
            'modern_gmail_smtp_tab_smtp'
        );

        $this->add_field( 'gmail_address', 'Gmail Address', 'text', 'modern_gmail_smtp_tab_smtp', 'mgs_section_smtp' );
        $this->add_field( 'gmail_password', 'App Password', 'password', 'modern_gmail_smtp_tab_smtp', 'mgs_section_smtp' );
        $this->add_field( 'encryption', 'Encryption', 'select', 'modern_gmail_smtp_tab_smtp', 'mgs_section_smtp', [ 'tls' => 'TLS', 'ssl' => 'SSL' ] );
        $this->add_field( 'port', 'SMTP Port', 'number', 'modern_gmail_smtp_tab_smtp', 'mgs_section_smtp' );

        // === Sender Section ===
        add_settings_section(
            'mgs_section_sender',
            'Sender Settings',
            function() {
                echo '<p>Customize the sender email and name.</p>';
            },
            'modern_gmail_smtp_tab_sender'
        );

        $this->add_field( 'from_email', 'From Email', 'text', 'modern_gmail_smtp_tab_sender', 'mgs_section_sender' );
        $this->add_field( 'from_name', 'From Name', 'text', 'modern_gmail_smtp_tab_sender', 'mgs_section_sender' );
        $this->add_field( 'force_override', 'Force Override From', 'checkbox', 'modern_gmail_smtp_tab_sender', 'mgs_section_sender', [], 'Force plugin to always override the From email/name.' );

        // === Advanced Section ===
        add_settings_section(
            'mgs_section_advanced',
            'Advanced',
            function() {
                echo '<p>Debug and advanced options.</p>';
            },
            'modern_gmail_smtp_tab_advanced'
        );

        $this->add_field( 'debug_mode', 'Debug Mode', 'checkbox', 'modern_gmail_smtp_tab_advanced', 'mgs_section_advanced', [], 'Enable SMTP debug output (admins only).' );
    }

    /**
     * Small helper to register a field with a common callback.
     */
    private function add_field( $id, $title, $type, $page, $section, $options = [], $desc = '' ) {
        $args = [
            'id' => $id,
            'type' => $type,
            'options' => $options,
            'desc' => $desc,
        ];

        add_settings_field( $id, $title, [ $this, 'render_field_callback' ], $page, $section, $args );
    }

    /**
     * Generic field renderer used for all field types we registered.
     */
    public function render_field_callback( $args ) {
        $options = get_option( $this->option_name, [] );
        $id = $args['id'];
        $type = $args['type'] ?? 'text';
        $value = $options[ $id ] ?? '';
        $desc = $args['desc'] ?? '';

        switch ( $type ) {
            case 'text':
                printf( "<input type='text' id='%1\$s' name='%2\$s[%1\$s]' value='%3\$s' class='regular-text' />", esc_attr( $id ), esc_attr( $this->option_name ), esc_attr( $value ) );
                break;

            case 'password':
                printf( "<input type='password' id='%1\$s' name='%2\$s[%1\$s]' value='%3\$s' class='regular-text' autocomplete='new-password' />", esc_attr( $id ), esc_attr( $this->option_name ), esc_attr( $value ) );
                break;

            case 'number':
                printf( "<input type='number' id='%1\$s' name='%2\$s[%1\$s]' value='%3\$s' />", esc_attr( $id ), esc_attr( $this->option_name ), esc_attr( $value ) );
                break;

            case 'checkbox':
                $checked = checked( 1, intval( $value ), false );
                printf( "<label><input type='checkbox' id='%1\$s' name='%2\$s[%1\$s]' value='1' %3\$s> %4\$s</label>", esc_attr( $id ), esc_attr( $this->option_name ), $checked, esc_html( $desc ) );
                break;

            case 'select':
                $opts = $args['options'] ?? [];
                printf( "<select id='%1\$s' name='%2\$s[%1\$s]'>", esc_attr( $id ), esc_attr( $this->option_name ) );
                foreach ( $opts as $k => $label ) {
                    $sel = selected( $value, $k, false );
                    printf( "<option value='%s' %s>%s</option>", esc_attr( $k ), $sel, esc_html( $label ) );
                }
                echo '</select>';
                break;

            default:
                printf( "<input type='text' id='%1\$s' name='%2\$s[%1\$s]' value='%3\$s' />", esc_attr( $id ), esc_attr( $this->option_name ), esc_attr( $value ) );
                break;
        }

        if ( $type !== 'checkbox' && ! empty( $desc ) ) {
            printf( '<p class="description">%s</p>', esc_html( $desc ) );
        }
    }

    /**
     * Sanitization function for the single option array.
     */
    public function sanitize_options( $input ) {
        $output = [];

        if ( ! is_array( $input ) ) {
            return $output;
        }

        // Strings
        $output['gmail_address'] = isset( $input['gmail_address'] ) ? sanitize_email( $input['gmail_address'] ) : '';
        $output['gmail_password'] = isset( $input['gmail_password'] ) ? sanitize_textarea_field( $input['gmail_password'] ) : '';
        $output['encryption'] = in_array( $input['encryption'] ?? '', [ 'tls', 'ssl' ], true ) ? $input['encryption'] : 'tls';
        $output['port'] = isset( $input['port'] ) ? absint( $input['port'] ) : 587;

        $output['from_email'] = isset( $input['from_email'] ) ? sanitize_email( $input['from_email'] ) : '';
        $output['from_name'] = isset( $input['from_name'] ) ? sanitize_text_field( $input['from_name'] ) : '';
        $output['force_override'] = ! empty( $input['force_override'] ) ? 1 : 0;

        $output['debug_mode'] = ! empty( $input['debug_mode'] ) ? 1 : 0;

        return $output;
    }

    /**
     * Configure PHPMailer using saved options. This runs on every mail operation.
     */
    public function configure_phpmailer( $phpmailer ) {
        $options = get_option( $this->option_name, [] );

        if ( empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
            // Nothing to do — let WP handle mail normally
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = 'smtp.gmail.com';
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $options['gmail_address'];
        $phpmailer->Password = $options['gmail_password'];
        $phpmailer->SMTPSecure = $options['encryption'] ?? 'tls';
        $phpmailer->Port = intval( $options['port'] ?? 587 );

        // Manage From and FromName respecting force override and WP defaults
        $default_wp_email = 'wordpress@' . preg_replace( '/^www\./', '', strtolower( $_SERVER['SERVER_NAME'] ?? '' ) );

        $current_from = $phpmailer->From ?: $default_wp_email;
        $current_from_name = $phpmailer->FromName ?: 'WordPress';

        $force = ! empty( $options['force_override'] );

        // From email
        if ( $force || strtolower( $current_from ) === strtolower( $default_wp_email ) ) {
            if ( ! empty( $options['from_email'] ) ) {
                $phpmailer->From = $options['from_email'];
            } else {
                $phpmailer->From = $options['gmail_address'];
            }
        }

        // From name
        if ( $force || strtolower( $current_from_name ) === 'wordpress' ) {
            $phpmailer->FromName = $options['from_name'] ?: 'WordPress';
        }

        // Optional debug
        if ( ! empty( $options['debug_mode'] ) ) {
            $phpmailer->SMTPDebug = 2; // level that produces useful info for admins
        }
    }

    /**
     * Render the settings page with tabs and correct virtual page mapping for sections.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // tabs: smtp, sender, connection-test, advanced, test-email
        $tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'smtp';
        $base = 'gmail-smtp';

        // Show notices if present
        $this->maybe_render_notices();

        ?>
        <div class="wrap">
            <h1>Modern Gmail SMTP <small style="font-size:12px">v<?php echo esc_html( $this->version ); ?></small></h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo $base; ?>&tab=smtp" class="nav-tab <?php echo $tab === 'smtp' ? 'nav-tab-active' : ''; ?>">SMTP Settings</a>
                <a href="?page=<?php echo $base; ?>&tab=sender" class="nav-tab <?php echo $tab === 'sender' ? 'nav-tab-active' : ''; ?>">Sender Settings</a>
                <a href="?page=<?php echo $base; ?>&tab=connection-test" class="nav-tab <?php echo $tab === 'connection-test' ? 'nav-tab-active' : ''; ?>">SMTP Connection Test</a>
                <a href="?page=<?php echo $base; ?>&tab=advanced" class="nav-tab <?php echo $tab === 'advanced' ? 'nav-tab-active' : ''; ?>">Advanced</a>
            </h2>

            <div style="margin-top:20px;">
                <?php if ( 'smtp' === $tab ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'modern_gmail_smtp_group' );
                        // Virtual page mapping
                        do_settings_sections( 'modern_gmail_smtp_tab_smtp' );
                        submit_button();
                        ?>
                    </form>

                <?php elseif ( 'sender' === $tab ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'modern_gmail_smtp_group' );
                        do_settings_sections( 'modern_gmail_smtp_tab_sender' );
                        submit_button();
                        ?>
                    </form>

                <?php elseif ( 'connection-test' === $tab ) : ?>
                    <h2>SMTP Connection Test</h2>
                    <p>This test only attempts to connect and authenticate to the SMTP server. It will not send an email.</p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'modern_gmail_smtp_conn_test', 'modern_gmail_smtp_conn_test_field' ); ?>
                        <input type="hidden" name="action" value="modern_gmail_smtp_connection_test">
                        <?php submit_button( 'Run Connection Test' ); ?>
                    </form>

                <?php elseif ( 'advanced' === $tab ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'modern_gmail_smtp_group' );
                        do_settings_sections( 'modern_gmail_smtp_tab_advanced' );
                        submit_button();
                        ?>
                        <hr>
                        <h3>Reset settings</h3>
                        <p>To reset plugin settings, delete the option <code><?php echo esc_html( $this->option_name ); ?></code> from the database or use WP-CLI.</p>
                    </form>

                <?php elseif ( 'test-email' === $tab ) : ?>
                    <h2>Send Test Email</h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'modern_gmail_smtp_test', 'modern_gmail_smtp_test_field' ); ?>
                        <input type="hidden" name="action" value="modern_gmail_smtp_test">

                        <table class="form-table">
                            <tr>
                                <th><label for="mgs_test_to">Recipient Email</label></th>
                                <td><input type="email" id="mgs_test_to" name="mgs_test_to" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th><label for="mgs_test_subject">Subject</label></th>
                                <td><input type="text" id="mgs_test_subject" name="mgs_test_subject" class="regular-text" value="Test Email from your site"></td>
                            </tr>
                            <tr>
                                <th><label for="mgs_test_message">Message</label></th>
                                <td><textarea id="mgs_test_message" name="mgs_test_message" rows="6" class="large-text">This is a test email sent from your WordPress site using Modern Gmail SMTP plugin.</textarea></td>
                            </tr>
                        </table>

                        <?php submit_button( 'Send Test Email' ); ?>
                    </form>

                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin notices based on query args after redirects from handlers
     */
    private function maybe_render_notices() {
        if ( isset( $_GET['mgs_test'] ) ) {
            $status = sanitize_text_field( wp_unslash( $_GET['mgs_test'] ) );
            if ( 'success' === $status ) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ Test email sent successfully.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to send test email. Check logs and settings.</p></div>';
            }
        }

        if ( isset( $_GET['mgs_conn'] ) ) {
            $status = sanitize_text_field( wp_unslash( $_GET['mgs_conn'] ) );
            $msg = '';
            if ( isset( $_GET['mgs_msg'] ) ) {
                $msg = base64_decode( rawurldecode( sanitize_text_field( wp_unslash( $_GET['mgs_msg'] ) ) ) );
                $msg = esc_html( $msg );
            }

            if ( 'success' === $status ) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ SMTP connection successful.</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>❌ SMTP connection failed.</p>' . ( $msg ? '<p><strong>Details:</strong> ' . $msg . '</p>' : '' ) . '</div>';
            }
        }
    }

    /**
     * Handle admin-post for sending a test email.
     */
    public function handle_send_test_email() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized', '', [ 'response' => 403 ] );
        }

        if ( ! isset( $_POST['modern_gmail_smtp_test_field'] ) || ! wp_verify_nonce( $_POST['modern_gmail_smtp_test_field'], 'modern_gmail_smtp_test' ) ) {
            wp_die( 'Invalid nonce', '', [ 'response' => 400 ] );
        }

        $to = isset( $_POST['mgs_test_to'] ) ? sanitize_email( wp_unslash( $_POST['mgs_test_to'] ) ) : '';
        $subject = isset( $_POST['mgs_test_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['mgs_test_subject'] ) ) : 'Website Test Email';
        $message = isset( $_POST['mgs_test_message'] ) ? wp_kses_post( wp_unslash( $_POST['mgs_test_message'] ) ) : '';

        if ( empty( $to ) || ! is_email( $to ) ) {
            wp_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=test-email&mgs_test=fail' ) );
            exit;
        }

        $sent = wp_mail( $to, $subject, $message );

        if ( $sent ) {
            wp_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=test-email&mgs_test=success' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=' . $this->page_slug . '&tab=test-email&mgs_test=fail' ) );
        }
        exit;
    }

    /**
     * Handle admin-post for testing SMTP connection (no email sent).
     */
    public function handle_connection_test() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized', '', [ 'response' => 403 ] );
        }

        if ( ! isset( $_POST['modern_gmail_smtp_conn_test_field'] ) || ! wp_verify_nonce( $_POST['modern_gmail_smtp_conn_test_field'], 'modern_gmail_smtp_conn_test' ) ) {
            wp_die( 'Invalid nonce', '', [ 'response' => 400 ] );
        }

        $options = get_option( $this->option_name, [] );

        if ( empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
            $this->redirect_with_conn_result( 'fail', 'Gmail address or App Password missing in settings.' );
        }

        // Ensure PHPMailer and SMTP classes available
        if ( ! class_exists( 'PHPMailer' ) ) {
            if ( file_exists( ABSPATH . WPINC . '/class-phpmailer.php' ) ) {
                require_once ABSPATH . WPINC . '/class-phpmailer.php';
            }
        }
        if ( ! class_exists( 'SMTP' ) ) {
            if ( file_exists( ABSPATH . WPINC . '/class-smtp.php' ) ) {
                require_once ABSPATH . WPINC . '/class-smtp.php';
            }
        }

        if ( ! class_exists( 'PHPMailer' ) ) {
            $this->redirect_with_conn_result( 'fail', 'PHPMailer library not available.' );
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $options['gmail_address'];
            $mail->Password = $options['gmail_password'];
            $mail->SMTPSecure = $options['encryption'] ?? 'tls';
            $mail->Port = intval( $options['port'] ?? 587 );

            $mail->Timeout = 10; // short timeout for tests
            $mail->SMTPDebug = ! empty( $options['debug_mode'] ) ? 2 : 0;

            $connected = false;
            try {
                $connected = $mail->smtpConnect();
            } catch ( Exception $e ) {
                $connected = false;
            }

            if ( $connected ) {
                try {
                    $mail->smtpClose();
                } catch ( Exception $e ) {
                    // ignore
                }
                $this->redirect_with_conn_result( 'success' );
            }

            // collect error
            $error_info = $mail->ErrorInfo ?? '';
            $smtp_inst = method_exists( $mail, 'getSMTPInstance' ) ? $mail->getSMTPInstance() : null;
            if ( $smtp_inst && ! empty( $smtp_inst->error ) ) {
                $error_info = $smtp_inst->error;
            }

            if ( empty( $error_info ) ) {
                $error_info = 'Unable to establish SMTP connection. Check host/port/encryption and credentials.';
            }

            $this->redirect_with_conn_result( 'fail', $error_info );

        } catch ( Exception $e ) {
            $this->redirect_with_conn_result( 'fail', $e->getMessage() );
        }
    }

    /**
     * Helper: redirect back to settings page with encoded message
     */
    private function redirect_with_conn_result( $status, $message = '' ) {
        $base = admin_url( 'admin.php?page=' . $this->page_slug . '&tab=connection-test' );
        if ( ! empty( $message ) ) {
            $encoded = rawurlencode( base64_encode( $message ) );
            wp_redirect( $base . '&mgs_conn=' . rawurlencode( $status ) . '&mgs_msg=' . $encoded );
        } else {
            wp_redirect( $base . '&mgs_conn=' . rawurlencode( $status ) );
        }
        exit;
    }
}

new Modern_Gmail_SMTP();
