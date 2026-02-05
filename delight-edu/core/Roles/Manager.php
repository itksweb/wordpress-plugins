<?php
namespace DelightEDU\Roles;

class Manager {

    /**
     * Register the 3 core WordPress roles
     */
    public static function register_core_roles() {
        // 1. Staff - Can access the SMS dashboard
        add_role( 'dedu_staff', 'DelightEDU Staff', [
            'read' => true,
            'view_dedu_dashboard' => true,
        ]);

        // 2. Student - Restricted access
        add_role( 'dedu_student', 'DelightEDU Student', [
            'read' => true,
            'view_dedu_dashboard' => true,
        ]);

        // 3. Parent - Restricted access
        add_role( 'dedu_parent', 'DelightEDU Parent', [
            'read' => true,
            'view_dedu_dashboard' => true,
        ]);
    }

    /**
     * Helper to check if a staff member has a specific granular permission
     * Example: if ( Manager::staff_can('manage_fees') ) { ... }
     */
    public static function staff_can( $capability ) {
        $user = wp_get_current_user();
        
        // If they are a WP Super Admin, they can do everything
        if ( in_array( 'administrator', (array) $user->roles ) ) {
            return true;
        }

        // Get the internal staff role from usermeta
        $internal_role = get_user_meta( $user->ID, 'dedu_staff_role', true ); // e.g., 'accountant'

        // Logic to check if 'accountant' has $capability will go here
        // We will build this out once we have the Permissions UI
        return false; 
    }
}