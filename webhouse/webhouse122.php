<?php
/**
 * Plugin Name: WebHOUSE
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins/
 * Description: A custom plugin created to house other small plugins that help us carry out maintenance on clients website
 * Version: 1.2.2
 * Author: Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License: GPL2 or later
 * Text Domain: webhouse
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =================================================================
// 1. Core Loader and Bootstrap Class (Updated for Dashboard)
// =================================================================

class WebHOUSE_Core {

    /**
     * Parent menu slug for all WebHOUSE modules.
     * @var string
     */
    const PARENT_SLUG = 'webhouse';

    /**
     * Stores instances of active modules.
     * @var array
     */
    private $modules = [];

    public function __construct() {
        // Load dependencies and modules
        add_action( 'plugins_loaded', [ $this, 'load_modules' ] );
        // Register the parent menu
        add_action( 'admin_menu', [ $this, 'add_parent_menu' ] );
    }

    /**
     * Load necessary module classes.
     */
    public function load_modules() {
        // Since all code is in one file, we ensure the classes are defined before instantiation.
        if ( method_exists( $this, 'load_module_classes' ) ) {
            $this->load_module_classes();
        }
        
        // Instantiate modules and pass the core's parent slug
        // 1. SMTP Module
        if ( class_exists( 'WebHOUSE_Module_SMTP' ) ) {
            $this->modules['smtp'] = new WebHOUSE_Module_SMTP( self::PARENT_SLUG );
        }

        // 2. Auto-Update Email Customization Module
        if ( class_exists( 'WebHOUSE_Module_Updates' ) ) {
            $this->modules['updates'] = new WebHOUSE_Module_Updates( self::PARENT_SLUG );
        }
    }

    /**
     * Register the Top-Level Parent Menu: "Webhouse"
     */
    public function add_parent_menu() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_menu_page(
            __( 'Webhouse Dashboard', 'webhouse' ),  // Page Title
            'WEBHOUSE',                               // Menu Title
            'manage_options',                         // Capability
            self::PARENT_SLUG,                        // Menu Slug
            [ $this, 'render_dashboard_page' ],       // Callback
            'dashicons-admin-generic',                // Icon
            19                                        // Position
        );
    }

    /**
     * Renders the main WebHOUSE dashboard page with cards.
     */
    public function render_dashboard_page() {
        // Define module data for rendering
        $modules_data = [
            [
                'title' => __( 'Gmail SMTP', 'webhouse' ),
                'description' => __( 'Configure all WordPress emails to be sent reliably via a secure Gmail App Password connection.', 'webhouse' ),
                'icon' => 'dashicons-email-alt',
                'link_slug' => 'webhouse-smtp-settings',
                'link_text' => __( 'Configure SMTP', 'webhouse' ),
            ],
            [
                'title' => __( 'Auto Update Emails', 'webhouse' ),
                'description' => __( 'Customize recipient, sender, and subject for all WordPress core, plugin, and theme update notifications.', 'webhouse' ),
                'icon' => 'dashicons-update',
                'link_slug' => 'webhouse-update-settings',
                'link_text' => __( 'Customize Emails', 'webhouse' ),
            ],
            // Add other modules here
        ];

        ?>
        <div class="wrap webhouse-dashboard">
            <h1><?php esc_html_e( 'WebHOUSE Dashboard', 'webhouse' ); ?></h1>
            <p class="description"><?php esc_html_e( 'Manage and configure your WebHOUSE utility modules below.', 'webhouse' ); ?></p>

            <style>
                .webhouse-cards-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 30px;
                }
                .webhouse-card {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 6px;
                    padding: 20px;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                    transition: all 0.2s ease-in-out;
                    display: flex;
                    flex-direction: column;
                }
                .webhouse-card:hover {
                    border-color: #007cba;
                    box-shadow: 0 0 10px rgba(0, 124, 186, 0.1);
                }
                .webhouse-card-header {
                    display: flex;
                    align-items: center;
                    margin-bottom: 15px;
                }
                .webhouse-card-icon {
                    font-size: 36px;
                    width: 40px;
                    height: 40px;
                    line-height: 40px;
                    margin-right: 15px;
                    color: #007cba;
                }
                .webhouse-card h2 {
                    margin: 0;
                    font-size: 1.5em;
                    font-weight: 600;
                    color: #2c3338;
                }
                .webhouse-card p {
                    flex-grow: 1; /* Ensures buttons align at the bottom */
                    margin-bottom: 20px;
                    color: #50575e;
                }
                .webhouse-card-link {
                    display: block;
                    width: 100%;
                    text-align: center;
                }
            </style>

            <div class="webhouse-cards-grid">
                <?php foreach ( $modules_data as $module ) : ?>
                    <div class="webhouse-card">
                        <div class="webhouse-card-header">
                            <span class="webhouse-card-icon <?php echo esc_attr( $module['icon'] ); ?>"></span>
                            <h2><?php echo esc_html( $module['title'] ); ?></h2>
                        </div>
                        <p><?php echo esc_html( $module['description'] ); ?></p>
                        <div class="webhouse-card-link">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $module['link_slug'] ) ); ?>" class="button button-primary button-large">
                                <?php echo esc_html( $module['link_text'] ); ?>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Defines all WebHOUSE module classes (for single file setup).
     */
    private function load_module_classes() {
        // Define WebHOUSE_Module_SMTP class (already defined below)
        // Define WebHOUSE_Module_Updates class (defined below)
    }
}


// =================================================================
// 2. Dedicated SMTP Module Class (Existing)
// =================================================================

class WebHOUSE_Module_SMTP {

    /**
     * Parent menu slug provided by the Core.
     * @var string
     */
    private $parent_slug;

    /**
     * Option name for storing settings in wp_options.
     * @var string
     */
    private $option_name = 'webhouse_smtp_options';

    /**
     * Submenu page slug.
     * @var string
     */
    private $plugin_page = 'webhouse-smtp-settings';

    /**
     * Nonce fields and action hooks as constants for maintainability.
     */
    const NONCE_TEST_EMAIL = 'webhouse_smtp_test_email_nonce';
    const NONCE_CONN_TEST = 'webhouse_smtp_conn_test_nonce';
    const ACTION_TEST_EMAIL = 'admin_post_webhouse_smtp_test';
    const ACTION_CONN_TEST = 'admin_post_webhouse_smtp_connection_test';


    public function __construct( $parent_slug ) {
        $this->parent_slug = $parent_slug;

        // Hook to configure PHPMailer on every request
        add_action( 'phpmailer_init', [ $this, 'configure_phpmailer' ] );

        // Admin menu registration (sub-menu)
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Handle custom form actions
        add_action( self::ACTION_TEST_EMAIL, [ $this, 'send_test_email' ] );
        add_action( self::ACTION_CONN_TEST, [ $this, 'test_smtp_connection' ] );
    }

    /**
     * Adds the SMTP submenu page under the WebHOUSE parent menu.
     */
    public function add_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_submenu_page(
            $this->parent_slug,                             // Parent Slug
            __( 'WebHOUSE Gmail SMTP Settings', 'webhouse' ),// Page Title
            __( 'Gmail SMTP', 'webhouse' ),                  // Menu Title
            'manage_options',                               // Capability
            $this->plugin_page,                             // Menu Slug
            [ $this, 'render_settings_page' ]              // Callback
        );
    }

    /**
     * Configure PHPMailer to use Gmail SMTP
     * @param \PHPMailer $phpmailer
     */
    public function configure_phpmailer( $phpmailer ) {
        $options = get_option( $this->option_name );

        // Safety check for option existence and required credentials
        if ( ! is_array( $options ) || empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
            return;
        }

        // --- SMTP Configuration ---
        $phpmailer->isSMTP();
        $phpmailer->Host       = 'smtp.gmail.com';
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = $options['gmail_address'];
        $phpmailer->Password   = $options['gmail_password'];
        $phpmailer->SMTPSecure = $options['encryption'] ?? 'tls';
        $phpmailer->Port       = intval( $options['port'] ?? 587 );
        $phpmailer->SMTPKeepAlive = true; // Recommended for performance

        // Enable debug mode if set (only for authenticated admin users)
        if ( ! empty( $options['debug_mode'] ) && current_user_can( 'manage_options' ) ) {
             $phpmailer->SMTPDebug = 2;
             $phpmailer->Debugoutput = 'html'; // Output debug info to HTML
        }

        // --- From/FromName Overrides ---
        $default_wp_email = 'wordpress@' . preg_replace( '/^www\./', '', strtolower( $_SERVER['SERVER_NAME'] ?? '' ) );
        $current_from     = $phpmailer->From ?: $default_wp_email;
        $current_from_name = $phpmailer->FromName ?: 'WordPress';
        $force            = ! empty( $options['force_override'] );

        // Override From Email
        if ( $force || strtolower( $current_from ) === strtolower( $default_wp_email ) ) {
            $from_email = $options['from_email'] ?? $options['gmail_address'];
            $phpmailer->From = sanitize_email( $from_email );
        }

        // Override From Name
        if ( $force || strtolower( $current_from_name ) === 'wordpress' ) {
            $from_name = $options['from_name'] ?? 'WordPress';
            $phpmailer->FromName = sanitize_text_field( $from_name );
        }
    }

    /**
     * Register plugin settings using "Virtual Page" slugs for tabs
     */
    public function register_settings() {
        register_setting( 'webhouse_smtp_group', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_options' ]
        ] );

        // --- 1. SMTP Section (Virtual Page: webhouse_smtp_tab_smtp) ---
        add_settings_section( 'webhouse_smtp_section_smtp', __( 'SMTP Credentials & Settings', 'webhouse' ), null, 'webhouse_smtp_tab_smtp' );
        add_settings_field( 'gmail_address', __( 'Gmail Address', 'webhouse' ), [ $this, 'render_text_field' ], 'webhouse_smtp_tab_smtp', 'webhouse_smtp_section_smtp', ['id' => 'gmail_address', 'type' => 'email'] );
        // IMPORTANT: Using custom render for password field to handle blank submissions
        add_settings_field( 'gmail_password', __( 'App Password', 'webhouse' ), [ $this, 'render_secure_password_field' ], 'webhouse_smtp_tab_smtp', 'webhouse_smtp_section_smtp', ['id' => 'gmail_password', 'desc' => __( 'Only fill this field if you want to change your saved App Password.', 'webhouse' )] );
        add_settings_field( 'encryption', __( 'Encryption', 'webhouse' ), [ $this, 'render_select_field' ], 'webhouse_smtp_tab_smtp', 'webhouse_smtp_section_smtp', ['id' => 'encryption', 'options' => ['tls' => 'TLS (Recommended)', 'ssl' => 'SSL']] );
        add_settings_field( 'port', __( 'SMTP Port', 'webhouse' ), [ $this, 'render_text_field' ], 'webhouse_smtp_tab_smtp', 'webhouse_smtp_section_smtp', ['id' => 'port', 'type' => 'number', 'default' => 587] );
        add_settings_field( 'from_email', __( 'Custom From Email', 'webhouse' ), [ $this, 'render_text_field' ], 'webhouse_smtp_tab_smtp', 'webhouse_smtp_section_smtp', ['id' => 'from_email', 'type' => 'email', 'desc' => __( 'Optional: The email address emails should appear to come from.', 'webhouse' )] );
        add_settings_field( 'from_name', __( 'Custom From Name', 'webhouse' ), [ $this, 'render_text_field' ], 'webhouse_smtp_tab_smtp', 'webhouse_smtp_section_smtp', ['id' => 'from_name', 'desc' => __( 'Optional: The sender name that should appear.', 'webhouse' )] );
        add_settings_field( 'force_override', __( 'Force Override From', 'webhouse' ), [ $this, 'render_checkbox_field' ], 'webhouse_smtp_tab_smtp', 'webhouse_smtp_section_smtp', ['id' => 'force_override', 'desc' => __( 'Force plugin to always override From email/name, even if another plugin/theme has set one.', 'webhouse' )] );

        // --- 2. Advanced Section (Virtual Page: webhouse_smtp_tab_advanced) ---
        add_settings_section( 'webhouse_smtp_section_advanced', __( 'Advanced', 'webhouse' ), null, 'webhouse_smtp_tab_advanced' );
        add_settings_field( 'debug_mode', __( 'Debug Mode', 'webhouse' ), [ $this, 'render_checkbox_field' ], 'webhouse_smtp_tab_advanced', 'webhouse_smtp_section_advanced', ['id' => 'debug_mode', 'desc' => __( 'Enable PHPMailer debug output on the screen during tests/send attempts (for admin only).', 'webhouse' )] );
    }

    /**
     * Sanitize input. Handles preserving password if left empty.
     */
    public function sanitize_options( $input ) {
        $output = get_option( $this->option_name, [] );

        if ( ! is_array( $input ) ) {
            return $output;
        }

        foreach ( $input as $key => $value ) {
            switch ( $key ) {
                case 'force_override':
                case 'debug_mode':
                    $output[$key] = ! empty( $value ) ? 1 : 0;
                    break;

                case 'gmail_password':
                    // CRUCIAL: Preserve existing password if the submitted value is empty.
                    // This prevents blanking the password when saving other settings.
                    if ( ! empty( $value ) ) {
                        // Use sanitize_textarea_field for App Passwords which can contain complex chars
                        $output[$key] = sanitize_textarea_field( wp_unslash( $value ) );
                    }
                    break;

                case 'gmail_address':
                case 'from_email':
                    $output[$key] = sanitize_email( $value );
                    break;

                case 'port':
                    $output[$key] = absint( $value );
                    break;

                default:
                    $output[$key] = sanitize_text_field( $value );
                    break;
            }
        }
        return $output;
    }


    // --- Render Fields ---

    public function render_text_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? ( $args['default'] ?? '' );
        $type = esc_attr( $args['type'] ?? 'text' );
        $desc = $args['desc'] ?? '';
        echo "<input type='$type' id='$id' name='{$this->option_name}[$id]' value='" . esc_attr( $value ) . "' class='regular-text' />";
        if ( ! empty( $desc ) ) {
            echo "<p class='description'>" . esc_html( $desc ) . "</p>";
        }
    }

    /**
     * Renders password field without outputting the stored value.
     */
    public function render_secure_password_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $desc = $args['desc'] ?? '';
        $has_value = ! empty( $options[$id] );
        $placeholder = $has_value ? '••••••••••••••••' : '';

        // Note: The 'value' attribute is intentionally left empty.
        echo "<input type='password' id='$id' name='{$this->option_name}[$id]' value='' class='regular-text' autocomplete='new-password' placeholder='$placeholder' />";
        if ( ! empty( $desc ) ) {
            echo "<p class='description'>" . esc_html( $desc ) . "</p>";
        }
        if ( $has_value ) {
             echo '<p class="description" style="color: green;">' . esc_html__( 'A password is currently saved.', 'webhouse' ) . '</p>';
        } else {
             echo '<p class="description" style="color: red;">' . esc_html__( 'No password saved. Emails will fail.', 'webhouse' ) . '</p>';
        }
    }

    public function render_select_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? '';
        $opts = $args['options'] ?? [];
        $desc = $args['desc'] ?? '';

        echo "<select id='$id' name='{$this->option_name}[$id]'>";
        foreach ( $opts as $k => $label ) {
            $selected = selected( $value, $k, false );
            echo "<option value='" . esc_attr( $k ) . "' $selected>" . esc_html( $label ) . "</option>";
        }
        echo "</select>";
        if ( ! empty( $desc ) ) {
            echo "<p class='description'>" . esc_html( $desc ) . "</p>";
        }
    }

    public function render_checkbox_field( $args ) {
        $options = get_option( $this->option_name );
        $id = esc_attr( $args['id'] );
        $value = ! empty( $options[$id] ) ? 1 : 0;
        $checked = checked( 1, $value, false );
        $desc = $args['desc'] ?? '';
        // CRUCIAL FIX: Added esc_html to the description
        echo "<label><input type='checkbox' id='$id' name='{$this->option_name}[$id]' value='1' $checked> " . esc_html( $desc ) . "</label>";
    }

    /**
     * Renders settings page with tabs
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'smtp';
        $this->render_admin_notices();
        $base_url = admin_url( 'admin.php?page=' . $this->plugin_page );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WebHOUSE Gmail SMTP Settings', 'webhouse' ); ?> <small style="font-size:12px">v1.0.3</small></h1>

            <h2 class="nav-tab-wrapper">
                <a href="<?php echo esc_url( $base_url . '&tab=smtp' ); ?>" class="nav-tab <?php echo $active_tab === 'smtp' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'SMTP Credentials', 'webhouse' ); ?></a>
                <a href="<?php echo esc_url( $base_url . '&tab=connection-test' ); ?>" class="nav-tab <?php echo $active_tab === 'connection-test' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'SMTP Connection Test', 'webhouse' ); ?></a>
                <a href="<?php echo esc_url( $base_url . '&tab=test-email' ); ?>" class="nav-tab <?php echo $active_tab === 'test-email' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Send Test Email', 'webhouse' ); ?></a>
                <a href="<?php echo esc_url( $base_url . '&tab=advanced' ); ?>" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Advanced', 'webhouse' ); ?></a>
            </h2>

            <div style="margin-top:20px;">

                <?php if ( $active_tab === 'smtp' ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'webhouse_smtp_group' );
                        // Call the VIRTUAL page slug for SMTP
                        do_settings_sections( 'webhouse_smtp_tab_smtp' );
                        submit_button();
                        ?>
                        <p class="description"><?php esc_html_e( 'You must use a Google App Password here, not your regular Gmail password. You can generate one in your Google Security settings.', 'webhouse' ); ?></p>
                    </form>

                <?php elseif ( $active_tab === 'test-email' ) : ?>
                    <h2><?php esc_html_e( 'Send Test Email', 'webhouse' ); ?></h2>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( self::NONCE_TEST_EMAIL, self::NONCE_TEST_EMAIL . '_field' ); ?>
                        <input type="hidden" name="action" value="webhouse_smtp_test">
                        <table class="form-table">
                            <tr>
                                <th><label for="test_email_to"><?php esc_html_e( 'Recipient Email', 'webhouse' ); ?></label></th>
                                <td><input type="email" name="test_email_to" id="test_email_to" class="regular-text" required value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>"></td>
                            </tr>
                            <tr>
                                <th></th>
                                <td><p class="description"><?php esc_html_e( 'This will send a real email using your Gmail SMTP settings.', 'webhouse' ); ?></p></td>
                            </tr>
                        </table>
                        <?php submit_button( __( 'Send Test Email', 'webhouse' ) ); ?>
                    </form>

                <?php elseif ( $active_tab === 'connection-test' ) : ?>
                    <h2><?php esc_html_e( 'SMTP Connection Test', 'webhouse' ); ?></h2>
                    <p><?php esc_html_e( 'This test attempts to connect and authenticate to Gmail SMTP using your saved settings. No email will be sent.', 'webhouse' ); ?></p>

                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <?php wp_nonce_field( self::NONCE_CONN_TEST, self::NONCE_CONN_TEST . '_field' ); ?>
                        <input type="hidden" name="action" value="webhouse_smtp_connection_test">

                        <?php submit_button( __( 'Run SMTP Connection Test', 'webhouse' ), 'primary', 'run_smtp_test' ); ?>
                    </form>

                <?php elseif ( $active_tab === 'advanced' ) : ?>
                    <form method="post" action="options.php">
                        <?php
                        settings_fields( 'webhouse_smtp_group' );
                        // Call the VIRTUAL page slug for Advanced
                        do_settings_sections( 'webhouse_smtp_tab_advanced' );
                        submit_button();
                        ?>
                        <hr>
                        <h3><?php esc_html_e( 'Reset Plugin Settings', 'webhouse' ); ?></h3>
                        <p><?php printf( esc_html__( 'To completely reset all settings for the SMTP module, you must manually delete the option %s from the database.', 'webhouse' ), '<code>' . esc_html( $this->option_name ) . '</code>' ); ?></p>
                        <p class="description"><?php esc_html_e( 'Future versions will include a one-click reset button here.', 'webhouse' ); ?></p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render admin notices based on URL flags
     */
    private function render_admin_notices() {
        // Test Email Notices
        if ( isset( $_GET['test_email'] ) ) {
            $status = sanitize_key( wp_unslash( $_GET['test_email'] ) );
            if ( $status === 'success' ) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html__( 'Test email sent successfully!', 'webhouse' ) . '</p></div>';
            } elseif ( $status === 'fail' ) {
                echo '<div class="notice notice-error is-dismissible"><p>❌ ' . esc_html__( 'Failed to send test email. Please check your SMTP settings and App Password.', 'webhouse' ) . '</p></div>';
            }
        }

        // Connection Test Notices
        if ( isset( $_GET['smtp_test'] ) ) {
            $status = sanitize_key( wp_unslash( $_GET['smtp_test'] ) );
            $msg = '';
            if ( isset( $_GET['smtp_msg'] ) ) {
                // Decode and escape the potential error message
                $msg_raw = rawurldecode( sanitize_text_field( wp_unslash( $_GET['smtp_msg'] ) ) );
                $msg = base64_decode( $msg_raw );
                // Note: We use esc_html here because PHPMailer error messages are usually plain text.
                $msg = esc_html( $msg );
            }

            if ( $status === 'success' ) {
                echo '<div class="notice notice-success is-dismissible"><p>✅ ' . esc_html__( 'SMTP connection successful. Credentials verified.', 'webhouse' ) . '</p></div>';
            } elseif ( $status === 'fail' ) {
                echo '<div class="notice notice-error is-dismissible"><p>❌ ' . esc_html__( 'SMTP connection failed.', 'webhouse' ) . '</p>' . ( $msg ? '<p><strong>' . esc_html__( 'Details:', 'webhouse' ) . '</strong> ' . $msg . '</p>' : '' ) . '</div>';
            }
        }
    }

    /**
     * Send test email
     */
    public function send_test_email() {
        $nonce_name = self::NONCE_TEST_EMAIL . '_field';

        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], self::NONCE_TEST_EMAIL ) ) {
            wp_die( 'Unauthorized request', 403 );
        }

        $to = sanitize_email( wp_unslash( $_POST['test_email_to'] ?? '' ) );
        if ( empty( $to ) || ! is_email( $to ) ) {
            $this->redirect_to_settings( '&tab=test-email&test_email=fail' );
        }

        $subject = 'WebHOUSE SMTP Test Email';
        $message = 'This is a test email sent from your WordPress site using Gmail SMTP settings configured via the WebHOUSE plugin.';

        // wp_mail automatically triggers the phpmailer_init hook where the settings are configured
        $sent = wp_mail( $to, $subject, $message );

        $redirect_status = $sent ? 'success' : 'fail';
        $this->redirect_to_settings( "&tab=test-email&test_email={$redirect_status}" );
    }

    /**
     * Test SMTP connection & authentication without sending an email
     */
    public function test_smtp_connection() {
        $nonce_name = self::NONCE_CONN_TEST . '_field';

        if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST[$nonce_name] ) || ! wp_verify_nonce( $_POST[$nonce_name], self::NONCE_CONN_TEST ) ) {
            wp_die( 'Unauthorized request', 403 );
        }

        $options = get_option( $this->option_name );

        // Basic checks
        if ( empty( $options['gmail_address'] ) || empty( $options['gmail_password'] ) ) {
            $msg = 'Gmail address or App Password is missing in settings.';
            $this->redirect_with_smtp_result( 'fail', $msg );
        }

        // Ensure PHPMailer and SMTP classes are loaded
        require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
        require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
        require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

        if ( ! class_exists( 'PHPMailer\PHPMailer\PHPMailer' ) ) {
            $msg = 'PHPMailer classes not available.';
            $this->redirect_with_smtp_result( 'fail', $msg );
        }

        // Create PHPMailer instance
        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $options['gmail_address'];
            $mail->Password   = $options['gmail_password'];
            $mail->SMTPSecure = $options['encryption'] ?? 'tls';
            $mail->Port       = intval( $options['port'] ?? 587 );
            $mail->Timeout    = 10;
            $mail->SMTPDebug  = ! empty( $options['debug_mode'] ) ? 2 : 0;
            $mail->Debugoutput = 'error_log'; // Prevent debug output from breaking the redirect

            // Attempt connection
            $connected = $mail->smtpConnect();

            if ( $connected ) {
                $mail->smtpClose();
                $this->redirect_with_smtp_result( 'success' );
            } else {
                $error_info = $mail->ErrorInfo ?? 'Unknown error during connection/authentication.';
                $this->redirect_with_smtp_result( 'fail', $error_info );
            }

        } catch ( Exception $e ) {
            $this->redirect_with_smtp_result( 'fail', $e->getMessage() );
        }
    }

    /**
     * Helper: redirect back to settings page.
     */
    private function redirect_to_settings( $query_params = '' ) {
        $base = admin_url( 'admin.php?page=' . $this->plugin_page );
        wp_redirect( $base . $query_params );
        exit;
    }

    /**
     * Helper: redirect back to connection test tab with encoded result.
     */
    private function redirect_with_smtp_result( $status, $message = '' ) {
        $base = admin_url( 'admin.php?page=' . $this->plugin_page . '&tab=connection-test' );
        if ( ! empty( $message ) ) {
            // Encode message for safe transmission via URL
            $encoded = rawurlencode( base64_encode( $message ) );
            wp_redirect( $base . "&smtp_test={$status}&smtp_msg={$encoded}" );
        } else {
            wp_redirect( $base . "&smtp_test={$status}" );
        }
        exit;
    }
}


// =================================================================
// 3. Auto-Update Email Customization Module (Updated for Core)
// =================================================================

class WebHOUSE_Module_Updates {

    /**
     * Parent menu slug provided by the Core.
     * @var string
     */
    private $parent_slug;

    /**
     * Option name for storing settings in wp_options.
     * @var string
     */
    private $option_name = 'webhouse_update_options';

    /**
     * Submenu page slug.
     * @var string
     */
    private $plugin_page = 'webhouse-update-settings';

    public function __construct( $parent_slug ) {
        $this->parent_slug = $parent_slug;

        // Admin menu registration (sub-menu)
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Hook into the auto-update email filter for Plugins/Themes
        add_filter( 'auto_plugin_theme_update_email', [ $this, 'customize_update_email_shared' ], 999, 4 );

        // Hook into the auto-update email filter for WordPress Core
        add_filter( 'auto_core_update_email', [ $this, 'customize_update_email_shared' ], 999, 3 );
    }

    /**
     * Adds the Update Settings submenu page under the WebHOUSE parent menu.
     */
    public function add_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_submenu_page(
            $this->parent_slug,                               // Parent Slug
            __( 'WebHOUSE Auto Update Email Settings', 'webhouse' ),// Page Title
            __( 'Update Emails', 'webhouse' ),                  // Menu Title
            'manage_options',                                   // Capability
            $this->plugin_page,                                // Menu Slug
            [ $this, 'render_settings_page' ]                 // Callback
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'webhouse_update_group', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_options' ],
            'default'           => [],
        ] );

        // Note: No need for add_settings_section/field since we use custom rendering in render_settings_page.
        // We register the main group only.
    }

    /**
     * Sanitize and validate the module's settings.
     * @param array $input The submitted options.
     * @return array The sanitized options.
     */
    public function sanitize_options( $input ) {
        $output = [];

        if ( ! is_array( $input ) ) {
            return [];
        }

        $output['email_to']         = sanitize_email( $input['email_to'] ?? '' );
        $output['email_subject']    = sanitize_text_field( $input['email_subject'] ?? '' );
        $output['email_from_name']  = sanitize_text_field( $input['email_from_name'] ?? '' );
        $output['email_from_email'] = sanitize_email( $input['email_from_email'] ?? '' );

        return $output;
    }

    /**
     * Renders the settings page for auto-update email customization.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $options = get_option( $this->option_name, [] );
        $email_to = $options['email_to'] ?? '';
        $email_subject = $options['email_subject'] ?? '';
        $from_name = $options['email_from_name'] ?? '';
        $from_email = $options['email_from_email'] ?? '';
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WebHOUSE Auto Update Email Settings', 'webhouse' ); ?></h1>

            <p class="description"><?php esc_html_e( 'Use these settings to customize the recipient, subject, and sender information for the automated emails WordPress sends after core, plugin, or theme auto-updates.', 'webhouse' ); ?></p>
            <hr>

            <form method="post" action="options.php">
                <?php settings_fields( 'webhouse_update_group' ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="webhouse_update_email_to"><?php esc_html_e( 'Recipient Email', 'webhouse' ); ?></label></th>
                        <td>
                            <input type="email" name="<?php echo esc_attr( $this->option_name ); ?>[email_to]"
                                value="<?php echo esc_attr( $email_to ); ?>"
                                id="webhouse_update_email_to"
                                class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Leave empty to use the default WordPress admin email.', 'webhouse' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="webhouse_update_email_from_name"><?php esc_html_e( 'From Name', 'webhouse' ); ?></label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[email_from_name]"
                                value="<?php echo esc_attr( $from_name ); ?>"
                                id="webhouse_update_email_from_name"
                                class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Leave empty to use the site title.', 'webhouse' ); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><label for="webhouse_update_email_from_email"><?php esc_html_e( 'From Email', 'webhouse' ); ?></label></th>
                        <td>
                            <input type="email" name="<?php echo esc_attr( $this->option_name ); ?>[email_from_email]"
                                value="<?php echo esc_attr( $from_email ); ?>"
                                id="webhouse_update_email_from_email"
                                class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Leave empty to use the default WordPress admin email.', 'webhouse' ); ?></p>
                        </td>
                    </tr>


                    <tr>
                        <th scope="row"><label for="webhouse_update_email_subject"><?php esc_html_e( 'Email Subject', 'webhouse' ); ?></label></th>
                        <td>
                            <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[email_subject]"
                                value="<?php echo esc_attr( $email_subject ); ?>"
                                id="webhouse_update_email_subject"
                                class="regular-text" />
                            <p class="description"><?php esc_html_e( 'Leave empty to use the default subject generated by WordPress. You can use the placeholder [{site_title}].', 'webhouse' ); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Shared function to customize the auto-update email for Core, Plugins, and Themes.
     * Hooked to 'auto_plugin_theme_update_email' (4 args) and 'auto_core_update_email' (3 args).
     *
     * @param array $email The email array with 'to', 'subject', 'body', 'headers'.
     * @return array The filtered email array.
     */
    public function customize_update_email_shared( $email ) {
        $options = get_option( $this->option_name, [] );

        $custom_to          = trim( $options['email_to'] ?? '' );
        $custom_subject     = trim( $options['email_subject'] ?? '' );
        $custom_from_name   = trim( $options['email_from_name'] ?? '' );
        $custom_from_email  = trim( $options['email_from_email'] ?? '' );

        // 1. Recipient
        if ( ! empty( $custom_to ) && is_email( $custom_to ) ) {
            $email['to'] = $custom_to;
        }

        // 2. Subject
        if ( ! empty( $custom_subject ) ) {
            // Use str_replace for common update placeholders
            $site_title = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
            $new_subject = str_replace( '[{site_title}]', $site_title, $custom_subject );

            $email['subject'] = $new_subject;
        }

        // 3. From Name/Email (Requires modifying headers)
        $default_from_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
        $default_from_email = get_option( 'admin_email' );

        $from_name  = ! empty( $custom_from_name )  ? $custom_from_name  : $default_from_name;
        $from_email = ! empty( $custom_from_email ) ? $custom_from_email : $default_from_email;
        
        // Ensure $email['headers'] is an array
        if ( ! isset( $email['headers'] ) || ! is_array( $email['headers'] ) ) {
             $email['headers'] = [];
        }

        // Remove existing From header if it exists
        foreach ( $email['headers'] as $i => $header ) {
            if ( stripos( $header, 'From:' ) === 0 ) {
                unset( $email['headers'][ $i ] );
            }
        }
        // Reindex array after removal
        $email['headers'] = array_values( $email['headers'] );

        // Add the custom From header
        $email['headers'][] = sprintf(
            'From: %s <%s>',
            sanitize_text_field( $from_name ),
            sanitize_email( $from_email )
        );

        return $email;
    }
}

// Kickstart the plugin
new WebHOUSE_Core();