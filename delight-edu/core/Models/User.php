<?php
namespace DelightEDU\Models;

use DelightEDU\Roles\PermissionsRegistry;
use DelightEDU\Models\StaffRole;

class User {

    /**
     * Check if a specific user has a capability (Role-based + Overrides)
     */
    public static function has_cap( $user_id, $capability ) {
        // 1. Get the user's assigned Staff Role ID
        $role_id = get_user_meta( $user_id, 'dedu_staff_role_id', true );
        
        // 2. Get Capabilities from their Role
        $role_model = new StaffRole();
        $role_caps = $role_id ? $role_model->get_capabilities( $role_id ) : [];

        // 3. Get User-Specific Overrides (Individual additions)
        $user_overrides = get_user_meta( $user_id, 'dedu_extra_caps', true );
        $user_overrides = is_array( $user_overrides ) ? $user_overrides : [];

        // 4. Combine them
        $final_caps = array_unique( array_merge( $role_caps, $user_overrides ) );

        // 5. Check if the requested capability exists in the merged array
        return in_array( $capability, $final_caps );
    }

    /**
     * Add an extra capability to a specific user
     */
    public static function add_extra_cap( $user_id, $capability ) {
        $extra_caps = get_user_meta( $user_id, 'dedu_extra_caps', true );
        $extra_caps = is_array( $extra_caps ) ? $extra_caps : [];

        if ( ! in_array( $capability, $extra_caps ) ) {
            $extra_caps[] = $capability;
            update_user_meta( $user_id, 'dedu_extra_caps', $extra_caps );
        }
    }
}