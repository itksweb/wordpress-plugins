<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dedicated Heartbeat Control Module Class
 */
class WebHOUSE_Module_Heartbeat {

    /**
     * Parent menu slug provided by the Core.
     * @var string
     */
    private $parent_slug;

    /**
     * Option name for storing settings in wp_options.
     * @var string
     */
    private $option_name = 'webhouse_heartbeat_options';

    /**
     * Submenu page slug.
     * @var string
     */
    private $plugin_page = 'webhouse-heartbeat-settings';

    /**
     * Available frequencies in seconds.
     * @var array
     */
    private $frequencies = [
        'default' => 'Default (15s Dashboard / 60s Frontend)',
        '30'      => 'Every 30 seconds',
        '45'      => 'Every 45 seconds',
        '60'      => 'Every 60 seconds (Recommended)',
        '120'     => 'Every 120 seconds (2 minutes)',
        '300'     => 'Every 300 seconds (5 minutes)',
        'disable' => 'Disable Heartbeat',
    ];

    public function __construct( $parent_slug ) {
        $this->parent_slug = $parent_slug;

        // Admin menu registration (sub-menu)
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Apply Heartbeat frequency filters
        add_filter( 'heartbeat_settings', [ $this, 'apply_heartbeat_frequency' ] );
    }

    /**
     * Adds the Heartbeat submenu page under the WebHOUSE parent menu.
     */
    public function add_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_submenu_page(
            $this->parent_slug,                               // Parent Slug
            __( 'WebHOUSE Heartbeat Control', 'webhouse' ),   // Page Title
            __( 'Heartbeat Control', 'webhouse' ),            // Menu Title
            'manage_options',                                   // Capability
            $this->plugin_page,                                // Menu Slug
            [ $this, 'render_settings_page' ]                 // Callback
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'webhouse_heartbeat_group', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_options' ],
            'default'           => [
                'dashboard' => 'default',
                'editor'    => 'default',
                'frontend'  => 'default',
            ],
        ] );

        add_settings_section( 'webhouse_heartbeat_section', __( 'Heartbeat Frequency Settings', 'webhouse' ), null, $this->plugin_page );

        add_settings_field( 'dashboard', __( 'Dashboard/Admin Pages', 'webhouse' ), [ $this, 'render_select_field' ], $this->plugin_page, 'webhouse_heartbeat_section', ['id' => 'dashboard'] );
        add_settings_field( 'editor', __( 'Post/Page Editor', 'webhouse' ), [ $this, 'render_select_field' ], $this->plugin_page, 'webhouse_heartbeat_section', ['id' => 'editor'] );
        add_settings_field( 'frontend', __( 'Frontend Pages', 'webhouse' ), [ $this, 'render_select_field' ], $this->plugin_page, 'webhouse_heartbeat_section', ['id' => 'frontend'] );
    }

    /**
     * Sanitize and validate the module's settings.
     * @param array $input The submitted options.
     * @return array The sanitized options.
     */
    public function sanitize_options( $input ) {
        $output = [];
        $valid_keys = array_keys( $this->frequencies );

        $output['dashboard'] = in_array( $input['dashboard'] ?? '', $valid_keys, true ) ? sanitize_key( $input['dashboard'] ) : 'default';
        $output['editor']    = in_array( $input['editor'] ?? '', $valid_keys, true ) ? sanitize_key( $input['editor'] ) : 'default';
        $output['frontend']  = in_array( $input['frontend'] ?? '', $valid_keys, true ) ? sanitize_key( $input['frontend'] ) : 'default';

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
            <h1><?php esc_html_e( 'WebHOUSE Heartbeat Control', 'webhouse' ); ?></h1>
            <?php settings_errors(); ?>
            <p class="description"><?php esc_html_e( 'The WordPress Heartbeat API uses AJAX calls to ensure real-time communication between the browser and the server. Reducing the frequency can save resources and reduce host usage.', 'webhouse' ); ?></p>
            <hr>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'webhouse_heartbeat_group' );
                do_settings_sections( $this->plugin_page );
                submit_button();
                ?>
            </form>
            <h3><?php esc_html_e( 'Notes on Frequency', 'webhouse' ); ?></h3>
            <ul>
                <li><strong>Dashboard/Admin:</strong> Controls activity like checking user session status.</li>
                <li><strong>Post/Page Editor:</strong> Controls features like auto-save and concurrent editor locking. Setting this too low may prevent auto-saving.</li>
                <li><strong>Frontend:</strong> Only runs when the admin bar is visible (logged-in users).</li>
                <li><strong>"Disable Heartbeat"</strong> prevents any AJAX calls, stopping the related features.</li>
            </ul>
        </div>
        <?php
    }

    /**
     * Renders a select dropdown field for frequency.
     * @param array $args Field arguments including the 'id'.
     */
    public function render_select_field( $args ) {
        $options = get_option( $this->option_name, [] );
        $id = esc_attr( $args['id'] );
        $value = $options[$id] ?? 'default';

        echo "<select id='$id' name='{$this->option_name}[$id]'>";
        foreach ( $this->frequencies as $k => $label ) {
            $selected = selected( $value, $k, false );
            echo "<option value='" . esc_attr( $k ) . "' $selected>" . esc_html( $label ) . "</option>";
        }
        echo "</select>";
    }

    /**
     * Applies the Heartbeat frequency settings.
     * @param array $settings Default Heartbeat settings.
     * @return array Modified settings.
     */
    public function apply_heartbeat_frequency( $settings ) {
        $options = get_option( $this->option_name, [] );

        // Determine the current context
        if ( is_admin() ) {
            if ( function_exists( 'get_current_screen' ) ) {
                $screen = get_current_screen();
                // Check if we are on a post/page edit screen (includes CPTs)
                if ( $screen && in_array( $screen->base, ['post', 'post-new'], true ) ) {
                    $context = 'editor';
                } else {
                    $context = 'dashboard';
                }
            } else {
                $context = 'dashboard';
            }
        } else {
            $context = 'frontend';
        }

        $frequency_key = $options[$context] ?? 'default';

        if ( $frequency_key === 'disable' ) {
            // Disable Heartbeat entirely for this context
            if ( $context === 'frontend' ) {
                add_action( 'init', [ $this, 'disable_heartbeat_on_frontend' ] );
            } else {
                // For admin/editor, set interval to a very large number effectively disabling it,
                // and the "suspend" setting to true
                $settings['interval'] = 999999;
                $settings['suspension'] = true;
            }

        } elseif ( $frequency_key !== 'default' ) {
            // Apply custom interval
            $interval = absint( $frequency_key );
            if ( $interval > 0 ) {
                $settings['interval'] = $interval;
            }
        }

        return $settings;
    }

    /**
     * Disables heartbeat specifically on the frontend using JS to avoid running it when admin bar is present.
     */
    public function disable_heartbeat_on_frontend() {
        if ( is_user_logged_in() && ! is_admin() ) {
            remove_action( 'wp_enqueue_scripts', 'wp_just_in_time_script_injection' );
            wp_deregister_script( 'heartbeat' );
        }
    }
}