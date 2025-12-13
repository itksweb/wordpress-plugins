<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dedicated Login Redirect Module Class
 * Allows redirection of users after login based on their role.
 */
class WebHOUSE_Module_Login_Redirect {

    /**
     * Parent menu slug provided by the Core.
     * @var string
     */
    private $parent_slug;

    /**
     * Option name for storing settings in wp_options.
     * Stores an array of role_slug => redirect_url.
     * @var string
     */
    private $option_name = 'webhouse_login_redirect_options';

    /**
     * Submenu page slug.
     * @var string
     */
    private $plugin_page = 'webhouse-login-redirect-settings';

    public function __construct( $parent_slug ) {
        $this->parent_slug = $parent_slug;

        // Admin menu registration (sub-menu)
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Apply filter to handle redirection after successful login
        add_filter( 'login_redirect', [ $this, 'filter_login_redirect' ], 10, 3 );
    }

    /**
     * Retrieves all available user roles (slug => name).
     * @return array
     */
    private function get_user_roles() {
        if ( ! function_exists( 'get_editable_roles' ) ) {
            return [];
        }

        $roles = get_editable_roles();
        $role_names = [];
        
        foreach ( $roles as $role_slug => $role_data ) {
            $role_names[ $role_slug ] = translate_user_role( $role_data['name'] );
        }

        return $role_names;
    }

    /**
     * Filters the redirection URL after a user successfully logs in.
     * * @param string $redirect_to The default URL to redirect to (calculated by WP).
     * @param string $request The requested redirect URL (from the 'redirect_to' query param).
     * @param WP_User|WP_Error $user The user object or WP_Error on failure.
     * @return string The modified redirection URL.
     */
    public function filter_login_redirect( $redirect_to, $request, $user ) {
        // 1. Basic validation: Exit early if the user is invalid or not logged in.
        if ( is_wp_error( $user ) || ! $user || ! $user->exists() ) {
            return $redirect_to;
        }

        $options = get_option( $this->option_name, [] );

        // 2. Get user's primary role (first role in the roles array)
        $primary_role = $user->roles[0];

        if ( empty( $primary_role ) ) {
            return $redirect_to;
        }

        $custom_url = $options[ $primary_role ] ?? '';
        
        // 3. Apply custom redirect if set and valid
        if ( ! empty( $custom_url ) && filter_var( $custom_url, FILTER_VALIDATE_URL ) ) {
            
            // Define the default admin targets we typically want to override.
            // When $request is present and points to the admin, $redirect_to often becomes the default admin_url().
            $default_targets = [
                admin_url(), 
                admin_url( 'profile.php' ), 
                home_url(),
                // Add common default targets derived from $request when empty
                get_dashboard_url( $user->ID ),
            ];

            // If $request is NOT empty, the user was trying to reach a specific page.
            if ( ! empty( $request ) ) {
                
                // If the final destination ($redirect_to) is NOT one of the default targets, 
                // it means the user was trying to reach a specific, non-default admin page, 
                // so we respect the original request.
                if ( ! in_array( $redirect_to, $default_targets ) ) {
                    return $redirect_to;
                }
            }
            
            // If $request was empty, OR the calculated $redirect_to was a default admin page, we override it.
            return $custom_url;
        }

        return $redirect_to;
    }

    /**
     * Adds the Login Redirect submenu page under the WebHOUSE parent menu.
     */
    public function add_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_submenu_page(
            $this->parent_slug,                               // Parent Slug
            __( 'WebHOUSE Login Redirect', 'webhouse' ),      // Page Title
            __( 'Login Redirect', 'webhouse' ),               // Menu Title
            'manage_options',                                   // Capability
            $this->plugin_page,                                // Menu Slug
            [ $this, 'render_settings_page' ]                 // Callback
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'webhouse_login_redirect_group', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_options' ],
            'default'           => [],
        ] );

        add_settings_section( 'webhouse_redirect_section', __( 'Redirection URLs by User Role', 'webhouse' ), [ $this, 'render_section_info' ], $this->plugin_page );

        // Dynamically add a settings field for each role
        $roles = $this->get_user_roles();
        foreach ( $roles as $role_slug => $role_name ) {
            add_settings_field( 
                $role_slug, 
                esc_html( $role_name ), 
                [ $this, 'render_text_field' ], 
                $this->plugin_page, 
                'webhouse_redirect_section', 
                [
                    'id' => $role_slug,
                    'description' => sprintf( __( 'Redirection URL for the %s role.', 'webhouse' ), esc_html( $role_name ) )
                ]
            );
        }
    }
    
    /**
     * Renders section description.
     */
    public function render_section_info() {
        echo '<p>' . esc_html__( 'Enter a full URL (starting with http:// or https://) for the page where users should be redirected immediately after logging in. Leave a field blank to use the default WordPress behavior.', 'webhouse' ) . '</p>';
    }


    /**
     * Sanitize and validate the module's settings.
     * @param array $input The submitted options.
     * @return array The sanitized options.
     */
    public function sanitize_options( $input ) {
        $output = [];
        $roles = $this->get_user_roles();

        foreach ( array_keys( $roles ) as $role_slug ) {
            $url = $input[ $role_slug ] ?? '';
            
            // Trim whitespace and check if URL is provided
            $trimmed_url = trim( $url );

            if ( ! empty( $trimmed_url ) ) {
                // Sanitize as a URL and ensure it's absolute
                $sanitized_url = esc_url_raw( $trimmed_url );
                
                // Add scheme if missing and it's not a relative path starting with /
                if ( substr( $sanitized_url, 0, 1 ) !== '/' && ! preg_match( '#^https?://#i', $sanitized_url ) ) {
                    $sanitized_url = 'http://' . $sanitized_url;
                }

                $output[ $role_slug ] = $sanitized_url;
            } else {
                $output[ $role_slug ] = '';
            }
        }

        return $output;
    }

    /**
     * Renders the settings page.
     */
    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'WebHOUSE Login Redirect', 'webhouse' ); ?></h1>

            <p class="description"><?php esc_html_e( 'Set custom login destinations for different user roles. Users will be redirected to the URL associated with their highest-level role.', 'webhouse' ); ?></p>
            <hr>
            
            <?php settings_errors(); // Displays all success/error notices ?>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'webhouse_login_redirect_group' );
                do_settings_sections( $this->plugin_page );
                submit_button();
                ?>
            </form>
            <h3><?php esc_html_e( 'How it Works', 'webhouse' ); ?></h3>
            <ul>
                <li><?php esc_html_e( 'This module overrides the default WordPress redirect to the admin dashboard.', 'webhouse' ); ?></li>
                <li><?php esc_html_e( 'If a user attempts to reach a specific page (e.g., /wp-admin/options-general.php) and is redirected to login, this module respects that original destination.', 'webhouse' ); ?></li>
                <li><?php esc_html_e( 'You must use a full URL (including the scheme: https:// or http://).', 'webhouse' ); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Renders a simple text field for a redirection URL.
     * @param array $args Field arguments including the 'id' and 'description'.
     */
    public function render_text_field( $args ) {
        $options = get_option( $this->option_name, [] );
        $id = esc_attr( $args['id'] );
        $description = esc_html( $args['description'] ?? '' );
        $value = $options[$id] ?? '';

        echo "<input type='url' id='$id' name='{$this->option_name}[$id]' value='" . esc_attr( $value ) . "' class='regular-text' placeholder='" . esc_attr( home_url() ) . "'>";
        if ( $description ) {
            echo "<p class='description'>$description</p>";
        }
    }
}