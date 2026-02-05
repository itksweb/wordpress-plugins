<?php
namespace DelightEDU\Controllers\Admin;

use DelightEDU\Roles\PermissionsRegistry;
use DelightEDU\Models\StaffRole;

class Menu {

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
    }

    public function register_menus() {
        // Main Menu
        add_menu_page(
            'DelightEDU', 
            'DelightEDU', 
            'manage_options', // Only WP Admins see the main setup initially
            'delight-edu', 
            [ $this, 'render_dashboard' ], 
            'dashicons-welcome-learn-more', 
            25
        );

        // Submenu: Staff Roles
        add_submenu_page(
            'delight-edu',
            'Staff Roles',
            'Staff Roles',
            'manage_options',
            'dedu-staff-roles',
            [ $this, 'render_roles_page' ]
        );
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>DelightEDU Dashboard</h1><p>Welcome to your bespoke School Management System.</p></div>';
    }
    
    // Enqueue CSS and JS for the Admin Dashboard
    public function enqueue_admin_assets( $hook ) {
        // Only load our styles on DelightEDU pages to keep the rest of WP fast
        if ( strpos( $hook, 'dedu-' ) === false && strpos( $hook, 'delight-edu' ) === false ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style( 
            'dedu-admin-style', 
            \DEDU_URL . 'assets/css/admin-style.css', 
            [], 
            '1.0.0' 
        );

        // Enqueue JS
        wp_enqueue_script(
            'dedu-admin-scripts', 
            \DEDU_URL . 'assets/js/admin-scripts.js', 
            ['jquery'], // Dependencies
            '1.0.0', 
            true // Load in footer
        );
    }

    // Renders the Roles Management UI
    public function render_roles_page() {

        // Check for messages in URL
        $message_key = isset( $_GET['message'] ) ? $_GET['message'] : '';
        
        if ( ! empty($message_key) && in_array( $message_key, ['role_created', 'role_deleted', 'role_updated', 'bulk_deleted'] ) ) {
            $messages = [
                'role_created' => 'Role created successfully.',
                'role_deleted' => 'Role deleted successfully.',
                'role_updated' => 'Role updated successfully.',
                'bulk_deleted' => isset($_GET['count']) ? absint($_GET['count']) . ' roles deleted successfully.' : 'Roles deleted successfully.'
            ];
            
            // Ensure the key exists in our array to avoid another warning
            $text = isset($messages[$message_key]) ? $messages[$message_key] : '';
            
            if ( $text ) : ?>
                <div id="dedu-toast" class="dedu-toast dedu-toast-success">
                    <span class="dashicons dashicons-yes-alt dedu-toast-icon"></span>
                    <div><strong>Success!</strong> <?php echo esc_html( $text ); ?></div>
                </div>
                <script>
                    document.addEventListener("DOMContentLoaded", function() {
                        const toast = document.getElementById("dedu-toast");
                        if (toast) {
                            setTimeout(() => toast.classList.add("show"), 500);
                            setTimeout(() => toast.classList.remove("show"), 4500);
                        }
                    });
                </script>
            <?php endif;
        }


        $role_model = new \DelightEDU\Models\StaffRole();
        $action     = isset($_GET['action']) ? $_GET['action'] : 'list';

        if ( in_array( $action, ['add', 'edit'] ) ) {
            $groups = \DelightEDU\Roles\PermissionsRegistry::get_all();
            $role   = null;

            if ( 'edit' === $action ) {
                $id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
                $role = $role_model->get_role( $id );

                if ( ! $role ) {
                    wp_die( 'Role not found.' );
                }
            }

            include \DEDU_PATH . 'templates/admin/roles-add.php';
        } else {
            $all_roles = $role_model->get_all_roles();
            include \DEDU_PATH . 'templates/admin/roles-list.php';
        }

        
    }
}