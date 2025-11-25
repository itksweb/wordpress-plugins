<?php
/**
 * Plugin Name: Sharp WP Gmail SMTP
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins/gmail-smtp
 * Description: Make use of Gmail SMTP instead of the default WordPress mail system to send all outgoing emails from your website.
 * Version: 2.1.0
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class KingstarWEBHOUSEMaintenance {

    private $option_name = 'wp_gmail_smtp_options';
    private $plugin_page  = 'wp-gmail-smtp';
    private $version      = '2.1.0';

    public function __construct() {
        // Hook to configure PHPMailer
        add_action( 'phpmailer_init', [ $this, 'configure_phpmailer' ] );

        // Admin menu (Top-Level Menu)
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Handle test email
        add_action( 'admin_post_wp_gmail_smtp_test', [ $this, 'send_test_email' ] );

        // Handle SMTP connection test
        add_action( 'admin_post_wp_gmail_smtp_connection_test', [ $this, 'test_smtp_connection' ] );
    }

    /**
     * Configure PHPMailer to use Gmail SMTP
     */
    public function configure_phpmailer( $phpmailer ) {
        $options = get_option( $this->option_name );

        // Added safety check for option existence and credentials
        if ( ! is_array( $options ) || empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp.gmail.com';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $options['gmail_address'];
        $phpmailer->Password   = $options['gmail_password']; // Use App Password
        $phpmailer->SMTPSecure = $options['encryption'] ?? 'tls';
        $phpmailer->Port       = intval( $options['port'] ?? 587 );

        // Get default WP email derived value
        $default_wp_email = 'wordpress@' . preg_replace( '/^www\./', '', strtolower( $_SERVER['SERVER_NAME'] ?? '' ) );

        // Determine current From and FromName
        $current_from = $phpmailer->From ?: $default_wp_email;
        $current_from_name = $phpmailer->FromName ?: 'WordPress';

        // Check Force Override setting (Logic Fix)
        $force = ! empty( $options['force_override'] );

        // Override From Email if forced or if WordPress is using its default
        if ( $force || strtolower( $current_from ) === strtolower( $default_wp_email ) ) {
            if ( ! empty( $options['from_email'] ) ) {
                $phpmailer->From = $options['from_email'];
            } else {
                // fallback to gmail address if no custom from_email provided
                $phpmailer->From = $options['gmail_address'];
            }
        }

        // Override From Name if forced or if WordPress is using its default
        if ( $force || strtolower( $current_from_name ) === 'wordpress' ) {
            if ( ! empty( $options['from_name'] ) ) {
                $phpmailer->FromName = $options['from_name'];
            } else {
                $phpmailer->FromName = 'WordPress';
            }
        }
    }

    // Redirects the parent menu link to the first submenu item (Gmail SMTP).
    public function redirect_parent_to_first_submenu() {
        // We redirect the parent slug (webhouse) to the child slug (wp-gmail-smtp).
        wp_safe_redirect( admin_url( 'admin.php?page=' . $this->plugin_page ) );
        exit;
    }

    //Add top-level parent menu and register the plugin as a submenu.
    public function add_settings_page() {
        // 1. Register the Top-Level Parent Menu: "Webhouse"
        add_menu_page(
            'Webhouse Dashboard',       // Page Title
            'WEBHOUSE',                 // Menu Title
            'manage_options',           // Capability
            'webhouse',                 // Menu Slug (The parent slug)
            [ $this, 'redirect_parent_to_first_submenu' ],                       // Callback: Set to null if the first submenu should load by default
            'dashicons-admin-home',     // Icon for the parent menu
            26                          // Position
        );

        // 2. Register the Submenu: "Gmail SMTP"
        add_submenu_page(
            'webhouse',                     // Parent Slug (Must match the slug above)
            'WP Gmail SMTP Settings',       // Page Title
            'Gmail SMTP',                   // Menu Title
            'manage_options',               // Capability
            $this->plugin_page,             // Menu Slug ('wp-gmail-smtp')
            [ $this, 'render_GMail_SMTP_settings_page' ]// Callback (Your existing render function)
        );

        // 2. Register the Submenu: "Auto Update Email Notification"
        add_submenu_page(
            'webhouse',                     // Parent Slug (Must match the slug above)
            'Auto Update Email Notification Settings',       // Page Title
            'Auto Update Email Notification',                   // Menu Title
            'manage_options',               // Capability
            $this->plugin_page,             // Menu Slug
            [ $this, 'render_settings_page' ]// Callback (Your existing render function)
        );
    }

    /**
     * Register plugin settings using "Virtual Page" slugs for tabs (Tab Fix)
     */
    public function register_settings() {
        register_setting( 'wp_gmail_smtp_group', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_options' ]
        ] );

        // --- 1. SMTP Section (Virtual Page: wp_gmail_smtp_tab_smtp) ---
        add_settings_section( 'wp_gmail_smtp_section_smtp', 'SMTP Settings', null, 'wp_gmail_smtp_tab_smtp' );
        add_settings_field( 'gmail_address', 'Gmail Address', [ $this, 'render_text_field' ], 'wp_gmail_smtp_tab_smtp', 'wp_gmail_smtp_section_smtp', ['id' => 'gmail_address'] );
        add_settings_field( 'gmail_password', 'App Password', [ $this, 'render_password_field' ], 'wp_gmail_smtp_tab_smtp', 'wp_gmail_smtp_section_smtp', ['id' => 'gmail_password'] );
        add_settings_field( 'encryption', 'Encryption', [ $this, 'render_select_field' ], 'wp_gmail_smtp_tab_smtp', 'wp_gmail_smtp_section_smtp', ['id' => 'encryption', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL']] );
        add_settings_field( 'port', 'SMTP Port', [ $this, 'render_text_field' ], 'wp_gmail_smtp_tab_smtp', 'wp_gmail_smtp_section_smtp', ['id' => 'port'] );

        // --- 2. Sender Section (Virtual Page: wp_gmail_smtp_tab_sender) ---
        add_settings_section( 'wp_gmail_smtp_section_sender', 'Sender Settings', null, 'wp_gmail_smtp_tab_sender' );
        add_settings_field( 'from_email', 'From Email', [ $this, 'render_text_field' ], 'wp_gmail_smtp_tab_sender', 'wp_gmail_smtp_section_sender', ['id' => 'from_email'] );
        add_settings_field( 'from_name', 'From Name', [ $this, 'render_text_field' ], 'wp_gmail_smtp_tab_sender', 'wp_gmail_smtp_section_sender', ['id' => 'from_name'] );
        add_settings_field( 'force_override', 'Force Override From', [ $this, 'render_checkbox_field' ], 'wp_gmail_smtp_tab_sender', 'wp_gmail_smtp_section_sender', ['id' => 'force_override', 'desc' => 'Force plugin to always override From email/name.'] );

        // --- 3. Advanced Section (Virtual Page: wp_gmail_smtp_tab_advanced) ---
        add_settings_section( 'wp_gmail_smtp_section_advanced', 'Advanced', null, 'wp_gmail_smtp_tab_advanced' );
        add_settings_field( 'debug_mode', 'Debug Mode', [ $this, 'render_checkbox_field' ], 'wp_gmail_smtp_tab_advanced', 'wp_gmail_smtp_section_advanced', ['id' => 'debug_mode', 'desc' => 'Enable PHPMailer debug output and basic logging (only for admin use).'] );
    }

    /**
     * Sanitize input (Adjusted for password field)
     */
    public function ( $input ) {
        $output = [];
        if ( ! is_array( $input ) ) {
            return $output;
        }
        foreach ( $input as $key => $value ) {
            if ( in_array( $key, ['force_override', 'debug_mode'], true ) ) {
                $output[$key] = ! empty( $value ) ? 1 : 0;
            } elseif ( $key === 'gmail_password' ) {
                // Use sanitize_textarea_field to avoid stripping characters that might be valid in an App Password
                $output[$key] = sanitize_textarea_field( $value );
            } else {
                $output[$key] = sanitize_text_field( $value );
            }
        }
        return $output;
    }


    /**
     * Render helper functions (unchanged)
     */
    public function render_text_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? '';
        echo "<input type='text' id='$id' name='{$this->option_name}[$id]' value='" . esc_attr( $value ) . "' class='regular-text' />";
        if ( ! empty( $args['desc'] ) ) {
            echo "<p class='description'>" . esc_html( $args['desc'] ) . "</p>";
        }
    }

    public function render_password_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? '';
        echo "<input type='password' id='$id' name='{$this->option_name}[$id]' value='" . esc_attr( $value ) . "' class='regular-text' autocomplete='new-password' />";
        if ( ! empty( $args['desc'] ) ) {
            echo "<p class='description'>" . esc_html( $args['desc'] ) . "</p>";
        }
    }

    public function render_select_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? '';
        $opts = $args['options'] ?? [];
        echo "<select id='$id' name='{$this->option_name}[$id]'>";
        foreach ( $opts as $k => $label ) {
            $selected = selected( $value, $k, false );
            echo "<option value='" . esc_attr( $k ) . "' $selected>" . esc_html( $label ) . "</option>";
        }
        echo "</select>";
        if ( ! empty( $args['desc'] ) ) {
            echo "<p class='description'>" . esc_html( $args['desc'] ) . "</p>";
        }
    }

    public function render_checkbox_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = ! empty( $options[$id] ) ? 1 : 0;
        $checked = checked( 1, $value, false );
        echo "<label><input type='checkbox' id='$id' name='{$this->option_name}[$id]' value='1' $checked> " . ( $args['desc'] ?? '' ) . "</label>";
    }

    /**
     * Render settings page with tabs and correct virtual page calling
     */
    public function render_GMail_SMTP_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'smtp';

        // Handle admin notices for test actions
        $this->render_admin_notices();
        $base_url = admin_url( 'admin.php?page=' . $this->plugin_page );
        ?>
        <div class="wrap">
            <h1>WP Gmail SMTP Settings <small style="font-size:12px">v<?php echo esc_html( $this->version ); ?></small></h1>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( $base_url . '&tab=smtp' ); ?>" class="nav-tab <?php echo $active_tab === 'smtp' ? 'nav-tab-active' : ''; ?>">SMTP Settings</a>
                <a href="<?php echo esc_url( $base_url . '&tab=sender' ); ?>" class="nav-tab <?php echo $active_tab === 'sender' ? 'nav-tab-active' : ''; ?>">Sender Settings</a>
                <a href="<?php echo esc_url( $base_url . '&tab=connection-test' ); ?>" class="nav-tab <?php echo $active_tab === 'connection-test' ? 'nav-tab-active' : ''; ?>">SMTP Connection Test</a>
                <a href="<?php echo esc_url( $base_url . '&tab=advanced' ); ?>" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">Advanced</a>
                <a href="<?php echo esc_url( $base_url . '&tab=test-email' ); ?>" class="nav-tab <?php echo $active_tab === 'test-email' ? 'nav-tab-active' : ''; ?>">Send Test Email</a>
            </h2>

            <div style="margin-top:20px;">

                <?php if ( $active_tab === 'smtp' ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'wp_gmail_smtp_group' );
                        // Call the VIRTUAL page slug for SMTP
                        do_settings_sections( 'wp_gmail_smtp_tab_smtp' );
                        submit_button();
                        ?>
                    </form>

                <?php elseif ( $active_tab === 'sender' ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'wp_gmail_smtp_group' );
                        // Call the VIRTUAL page slug for Sender
                        do_settings_sections( 'wp_gmail_smtp_tab_sender' );
                        submit_button();
                        ?>
                        <p class="description">If <strong>Force Override From</strong> is unchecked, the plugin will only override From Email/Name when WordPress is using its defaults (wordpress@yourdomain.com / WordPress).</p>
                    </form>

                <?php elseif ( $active_tab === 'test-email' ) : ?>
                    <h2>Send Test Email</h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'wp_gmail_smtp_test_nonce', 'wp_gmail_smtp_test_nonce_field' ); ?>
                        <input type="hidden" name="action" value="wp_gmail_smtp_test">
                        <table class="form-table">
                            <tr>
                                <th><label for="test_email_to">Recipient Email</label></th>
                                <td><input type="email" name="test_email_to" id="test_email_to" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th></th>
                                <td><p class="description">This will send a real email using your Gmail SMTP settings.</p></td>
                            </tr>
                        </table>
                        <?php submit_button( 'Send Test Email' ); ?>
                    </form>

                <?php elseif ( $active_tab === 'connection-test' ) : ?>
                    <h2>SMTP Connection Test</h2>
                    <p>This test attempts to connect and authenticate to Gmail SMTP using your saved settings. <strong>No email will be sent.</strong></p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( 'wp_gmail_smtp_conn_test_nonce', 'wp_gmail_smtp_conn_test_nonce_field' ); ?>
                        <input type="hidden" name="action" value="wp_gmail_smtp_connection_test">

                        <?php submit_button( 'Run SMTP Connection Test', 'primary', 'run_smtp_test' ); ?>
                    </form>

                <?php elseif ( $active_tab === 'advanced' ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'wp_gmail_smtp_group' );
                        // Call the VIRTUAL page slug for Advanced
                        do_settings_sections( 'wp_gmail_smtp_tab_advanced' );
                        submit_button();
                        ?>
                        <hr>
                        <h3>Reset plugin settings</h3>
                        <p>If you want to reset plugin settings to default, delete the option <code><?php echo esc_html( $this->option_name ); ?></code> from the database or use WP CLI.</p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin notices based on URL flags (unchanged)
     */
    private function render_admin_notices() {
        if ( isset( $_GET['test_email'] ) && $_GET['test_email'] === 'success' ) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ Test email sent successfully!</p></div>';
        } elseif ( isset( $_GET['test_email'] ) && $_GET['test_email'] === 'fail' ) {
            echo '<div class="notice notice-error is-dismissible"><p>❌ Failed to send test email. Please check your settings.</p></div>';
        }

        if ( isset( $_GET['smtp_test'] ) ) {
            $status = sanitize_text_field( wp_unslash( $_GET['smtp_test'] ) );
            $msg = '';
            if ( isset( $_GET['smtp_msg'] ) ) {
                $msg = base64_decode( rawurldecode( sanitize_text_field( wp_unslash( $_GET['smtp_msg'] ) ) ) );
                $msg = esc_html( $msg );
            }

            if ( $status === 'success' ) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ SMTP connection successful.</p></div>';
            } elseif ( $status === 'fail' ) {
                echo '<div class="notice notice-error is-dismissible"><p>❌ SMTP connection failed.</p>' . ( $msg ? '<p><strong>Details:</strong> ' . $msg . '</p>' : '' ) . '</div>';
            }
        }
    }

    /**
     * Send test email (Updated Redirect URL)
     */
    public function send_test_email() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['wp_gmail_smtp_test_nonce_field'] ) || ! wp_verify_nonce( $_POST['wp_gmail_smtp_test_nonce_field'], 'wp_gmail_smtp_test_nonce' ) ) {
            wp_die( 'Unauthorized request' );
        }

        $to = sanitize_email( $_POST['test_email_to'] ?? '' );
        if ( empty( $to ) || ! is_email( $to ) ) {
            // URL Fix: Changed options-general.php to admin.php
            wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_page . '&tab=test-email&test_email=fail' ) );
            exit;
        }

        $subject = 'WP Gmail SMTP Test Email';
        $message = 'This is a test email sent from your WordPress site using Gmail SMTP settings.';

        $sent = wp_mail( $to, $subject, $message );

        if ( $sent ) {
            // URL Fix: Changed options-general.php to admin.php
            wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_page . '&tab=test-email&test_email=success' ) );
        } else {
            // URL Fix: Changed options-general.php to admin.php
            wp_redirect( admin_url( 'admin.php?page=' . $this->plugin_page . '&tab=test-email&test_email=fail' ) );
        }
        exit;
    }

    /**
     * Test SMTP connection & authentication without sending an email (Updated Redirect URL)
     */
    public function test_smtp_connection() {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['wp_gmail_smtp_conn_test_nonce_field'] ) || ! wp_verify_nonce( $_POST['wp_gmail_smtp_conn_test_nonce_field'], 'wp_gmail_smtp_conn_test_nonce' ) ) {
            wp_die( 'Unauthorized request' );
        }

        $options = get_option( $this->option_name );

        // Basic checks
        if ( empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
            $msg = 'Gmail address or App Password is missing in settings.';
            $this->redirect_with_smtp_result( 'fail', $msg );
        }

        // Load WP's bundled PHPMailer/SMTP classes if needed
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
            $msg = 'PHPMailer class not available.';
            $this->redirect_with_smtp_result( 'fail', $msg );
        }

        // Create PHPMailer instance
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $options['gmail_address'];
            $mail->Password   = $options['gmail_password'];
            $mail->SMTPSecure = $options['encryption'] ?? 'tls';
            $mail->Port       = intval( $options['port'] ?? 587 );

            // Short timeout so test finishes quickly
            $mail->Timeout = 10;
            $mail->SMTPDebug = 0;

            // If debug mode requested in plugin settings, set debug level
            if ( ! empty( $options['debug_mode'] ) ) {
                $mail->SMTPDebug = 2;
            }

            // Attempt to connect to the SMTP server (does not send mail)
            $connected = false;
            try {
                // smtpConnect can throw or return false
                $connected = $mail->smtpConnect();
            } catch ( Exception $e ) {
                $connected = false;
            }

            if ( $connected ) {
                // If connected we should close the connection cleanly
                try {
                    $mail->smtpClose();
                } catch ( Exception $e ) {
                    // ignore
                }
                $this->redirect_with_smtp_result( 'success' );
            } else {
                // Try to capture an error message
                $error_info = $mail->ErrorInfo ?? '';
                $smtp_instance = method_exists( $mail, 'getSMTPInstance' ) ? $mail->getSMTPInstance() : null;
                if ( $smtp_instance && ! empty( $smtp_instance->error ) ) {
                    $error_info = $smtp_instance->error;
                }
                if ( empty( $error_info ) ) {
                    $error_info = 'Unable to establish SMTP connection. Check host/port/encryption and credentials.';
                }
                $this->redirect_with_smtp_result( 'fail', $error_info );
            }

        } catch ( Exception $e ) {
            $this->redirect_with_smtp_result( 'fail', $e->getMessage() );
        }
    }

    /**
     * Helper: redirect back to settings with encoded result (Updated Base URL)
     */
    private function redirect_with_smtp_result( $status, $message = '' ) {
        // URL Fix: Changed options-general.php to admin.php
        $base = admin_url( 'admin.php?page=' . $this->plugin_page . '&tab=connection-test' );
        if ( ! empty( $message ) ) {
            $encoded = rawurlencode( base64_encode( $message ) );
            wp_redirect( $base . "&smtp_test={$status}&smtp_msg={$encoded}" );
        } else {
            wp_redirect( $base . "&smtp_test={$status}" );
        }
        exit;
    }
}

new KingstarWEBHOUSEMaintenance();