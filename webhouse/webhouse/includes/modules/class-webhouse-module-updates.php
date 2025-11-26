<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Auto-Update Email Customization Module
 */
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

        // We use custom rendering, so we only need to register the group.
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