<?php
namespace DelightEDU\Controllers\Admin;

use DelightEDU\Models\StaffRole;

class PostHandler {

    public function __construct() {
        // This hook matches the <input type="hidden" name="action" value="dedu_save_role">
        add_action( 'admin_post_dedu_save_role', [ $this, 'save_staff_role' ] );
        add_action( 'admin_post_dedu_delete_role', [ $this, 'delete_staff_role' ] );
        add_action('admin_post_dedu_bulk_action_roles', [$this, 'handle_bulk_actions']);
    }

    public function save_staff_role() {
        // 1. Security Check
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'dedu_role_nonce' ) ) {
            wp_die( 'Security check failed.' );
        }

        // 2. Permission Check
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'You do not have permission to do this.' );
        }

        $role_id   = isset( $_POST['role_id'] ) ? absint( $_POST['role_id'] ) : 0;
        $role_name = sanitize_text_field( $_POST['role_name'] );
        $caps      = isset( $_POST['capabilities'] ) ? (array) $_POST['capabilities'] : [];

        $role_model = new \DelightEDU\Models\StaffRole();

        if ( $role_id > 0 ) {
            // UPDATE existing
            $success = $role_model->update( $role_id, $role_name, $caps );
            $message = 'role_updated';
        } else {
            // CREATE new
            $success = $role_model->create( $role_name, $caps );
            $message = 'role_created';
        }

        if ( $success ) {
            wp_redirect( admin_url( 'admin.php?page=dedu-staff-roles&message=' . $message ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=dedu-staff-roles&error=save_failed' ) );
        }

        // 3. Data Collection & Sanitization
        // $role_name = isset( $_POST['role_name'] ) ? sanitize_text_field( $_POST['role_name'] ) : '';
        // $caps      = isset( $_POST['capabilities'] ) ? (array) $_POST['capabilities'] : [];

        // if ( empty( $role_name ) ) {
        //     wp_redirect( admin_url( 'admin.php?page=dedu-staff-roles&action=add&error=missing_name' ) );
        //     exit;
        // }

        // 4. Save to Database using our Model
        // $role_model = new StaffRole();
        // $role_id = $role_model->create( $role_name, $caps );

        // 5. Redirect back with success message
        // if ( $role_id ) {
        //     wp_redirect( admin_url( 'admin.php?page=dedu-staff-roles&message=role_created' ) );
        // } else {
        //     wp_redirect( admin_url( 'admin.php?page=dedu-staff-roles&error=save_failed' ) );
        // }
        exit;
    }

    public function delete_staff_role() {
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

        // 1. Security Check (Nonce)
        if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'dedu_delete_role_' . $id ) ) {
            wp_die( 'Security check failed.' );
        }

        // 2. Authorization
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized.' );
        }

        $role_model = new \DelightEDU\Models\StaffRole();
        if ( $role_model->delete( $id ) ) {
            wp_redirect( admin_url( 'admin.php?page=dedu-staff-roles&message=role_deleted' ) );
        } else {
            wp_redirect( admin_url( 'admin.php?page=dedu-staff-roles&error=delete_failed' ) );
        }
        exit;
    }

    public function handle_bulk_actions() {
        check_admin_referer('dedu_bulk_roles_action', 'dedu-role-nonce');

        $action   = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $role_ids = isset($_POST['role_ids']) ? explode(',', $_POST['role_ids']) : [];
        $role_ids = array_map('absint', $role_ids);

        if (empty($role_ids)) {
            wp_redirect(admin_url('admin.php?page=dedu-staff-roles&status=error'));
            exit;
        }

        $role_model = new \DelightEDU\Models\StaffRole();

        if ('delete' === $action) {
            $count = 0;
            foreach ($role_ids as $id) {
                if ($role_model->delete($id)) {
                    $count++;
                }
            }
            wp_redirect(admin_url("admin.php?page=dedu-staff-roles&message=bulk_deleted&count=$count"));
            exit;
        }

        // Redirect back if no valid action
        wp_redirect(admin_url('admin.php?page=dedu-staff-roles'));
        exit;
    }

}