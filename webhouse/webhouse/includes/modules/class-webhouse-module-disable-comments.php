<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Dedicated Disable Comments Module Class
 */
class WebHOUSE_Module_Disable_Comments {

    /**
     * Parent menu slug provided by the Core.
     * @var string
     */
    private $parent_slug;

    /**
     * Option name for storing settings in wp_options.
     * Stores an array of post types where comments should be disabled.
     * @var string
     */
    private $option_name = 'webhouse_disable_comments_options';

    /**
     * Submenu page slug.
     * @var string
     */
    private $plugin_page = 'webhouse-disable-comments-settings';

    /**
     * Array of post types where comments are disabled.
     * @var array
     */
    private $disabled_post_types = [];

    public function __construct( $parent_slug ) {
        $this->parent_slug = $parent_slug;
        $options = get_option( $this->option_name, [] );
        $this->disabled_post_types = $options['post_types'] ?? [];

        // Admin menu registration (sub-menu)
        add_action( 'admin_menu', [ $this, 'add_settings_page' ] );

        // Register settings
        add_action( 'admin_init', [ $this, 'register_settings' ] );

        // Apply filters to disable comments if post types are selected
        if ( ! empty( $this->disabled_post_types ) ) {
            // Filter to close comments on selected post types
            add_filter( 'comments_open', [ $this, 'filter_comments_open' ], 10, 2 );

            // Remove comments menu from admin bar and meta box from editor for selected types
            add_action( 'admin_menu', [ $this, 'remove_admin_comment_features' ] );
        }
    }

    /**
     * Gets a list of public post types suitable for modification.
     * Excludes built-in attachment, revisions, and menu items.
     * @return array Post type objects.
     */
    private function get_modifiable_post_types() {
        $args = [
            'public' => true,
        ];
        $post_types = get_post_types( $args, 'objects' );
        
        $excluded = ['attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset'];

        foreach ( $post_types as $post_type => $object ) {
            if ( in_array( $post_type, $excluded, true ) ) {
                unset( $post_types[$post_type] );
            }
        }

        return $post_types;
    }

    /**
     * Filters whether comments are open for a given post.
     * @param bool $open Whether comments are open.
     * @param int|object $post The post ID or object.
     * @return bool
     */
    public function filter_comments_open( $open, $post ) {
        $post = get_post( $post );
        if ( in_array( $post->post_type, $this->disabled_post_types, true ) ) {
            return false;
        }
        return $open;
    }

    /**
     * Removes admin comment features for selected post types.
     */
    public function remove_admin_comment_features() {
        foreach ( $this->disabled_post_types as $post_type ) {
            // Remove the Discussion settings meta box from the editor screen
            remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
        }
    }


    /**
     * Adds the Disable Comments submenu page under the WebHOUSE parent menu.
     */
    public function add_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        add_submenu_page(
            $this->parent_slug,                               // Parent Slug
            __( 'WebHOUSE Disable Comments', 'webhouse' ),    // Page Title
            __( 'Disable Comments', 'webhouse' ),             // Menu Title
            'manage_options',                                   // Capability
            $this->plugin_page,                                // Menu Slug
            [ $this, 'render_settings_page' ]                 // Callback
        );
    }

    /**
     * Register plugin settings.
     */
    public function register_settings() {
        register_setting( 'webhouse_disable_comments_group', $this->option_name, [
            'sanitize_callback' => [ $this, 'sanitize_options' ],
            'default'           => [],
        ] );

        add_settings_section( 'webhouse_disable_comments_section', __( 'Select Post Types to Disable Comments', 'webhouse' ), null, $this->plugin_page );

        add_settings_field( 'post_types', __( 'Post Types', 'webhouse' ), [ $this, 'render_checkboxes' ], $this->plugin_page, 'webhouse_disable_comments_section', ['id' => 'post_types'] );
    }

    /**
     * Sanitize and validate the module's settings.
     * @param array $input The submitted options.
     * @return array The sanitized options.
     */
    public function sanitize_options( $input ) {
        $output = [];
        $valid_post_types = array_keys( $this->get_modifiable_post_types() );

        if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
            // Filter the submitted post types to ensure they are valid keys
            $sanitized_post_types = array_intersect( array_map( 'sanitize_key', $input['post_types'] ), $valid_post_types );
            $output['post_types'] = $sanitized_post_types;
        } else {
            $output['post_types'] = [];
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
            <h1><?php esc_html_e( 'WebHOUSE Disable Comments', 'webhouse' ); ?></h1>
            <?php settings_errors(); ?>
            <p class="description"><?php esc_html_e( 'Select the post types below where comments should be automatically disabled. This will close comments on all existing and future posts of the selected types.', 'webhouse' ); ?></p>
            <hr>

            <form method="post" action="options.php">
                <?php
                settings_fields( 'webhouse_disable_comments_group' );
                do_settings_sections( $this->plugin_page );
                submit_button();
                ?>
            </form>
            <h3><?php esc_html_e( 'Important Notes', 'webhouse' ); ?></h3>
            <ul>
                <li><?php esc_html_e( 'This module only affects the post types listed above (public, non-internal types).', 'webhouse' ); ?></li>
                <li><?php esc_html_e( 'The selected post types will have the "Allow Comments" checkbox removed from their edit screens.', 'webhouse' ); ?></li>
                <li><?php esc_html_e( 'Existing posts on selected types will have their comment status changed to "closed" automatically by this filter.', 'webhouse' ); ?></li>
            </ul>
        </div>
        <?php
    }

    /**
     * Renders the checkboxes for modifiable post types.
     */
    public function render_checkboxes() {
        $options = get_option( $this->option_name, [] );
        $selected_post_types = $options['post_types'] ?? [];
        $post_types = $this->get_modifiable_post_types();

        if ( empty( $post_types ) ) {
            echo '<p>' . esc_html__( 'No public post types found.', 'webhouse' ) . '</p>';
            return;
        }

        foreach ( $post_types as $post_type => $object ) {
            $checked = in_array( $post_type, $selected_post_types, true ) ? 'checked="checked"' : '';
            $label = esc_html( $object->labels->singular_name . " ($post_type)" );
            $id = 'webhouse_pt_' . $post_type;

            echo "<div style='margin-bottom: 5px;'>";
            echo "<label for='$id'>";
            echo "<input type='checkbox' id='$id' name='{$this->option_name}[post_types][]' value='" . esc_attr( $post_type ) . "' $checked>";
            echo " $label";
            echo "</label>";
            echo "</div>";
        }
        echo '<p class="description">' . esc_html__( 'Select all post types where you wish to permanently disable comments.', 'webhouse' ) . '</p>';
    }
}