<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Core Loader and Bootstrap Class
 */
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
     * Load necessary module class files and instantiate them.
     */
    public function load_modules() {
        // Load module files
        require_once WEBHOUSE_PLUGIN_DIR . 'includes/modules/class-webhouse-module-smtp.php';
        require_once WEBHOUSE_PLUGIN_DIR . 'includes/modules/class-webhouse-module-updates.php';
        
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
            'WebHOUSE Core',                               // Menu Title
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
}