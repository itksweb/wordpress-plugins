<?php
namespace DelightEDU\Models;

/**
 * Model for handling Custom Staff Roles and their capabilities.
 */
class StaffRole {

    protected $table_roles;
    protected $table_caps;

    public function __construct() {
        global $wpdb;
        $this->table_roles = $wpdb->prefix . 'dedu_staff_roles';
        $this->table_caps  = $wpdb->prefix . 'dedu_role_capabilities';
    }

    /**
     * Create a new staff role and assign its capabilities.
     * * @param string $name Name of the role (e.g., 'Librarian')
     * @param array $capabilities Array of strings (e.g., ['view_books', 'add_books'])
     */
    public function create( $name, $capabilities = [] ) {
        global $wpdb;

        $slug = sanitize_title( $name );

        // Insert the Role
        $wpdb->insert( $this->table_roles, [
            'role_name' => sanitize_text_field( $name ),
            'role_slug' => $slug
        ]);

        $role_id = $wpdb->insert_id;

        // Insert associated capabilities
        if ( ! empty( $capabilities ) && $role_id ) {
            foreach ( $capabilities as $cap ) {
                $wpdb->insert( $this->table_caps, [
                    'role_id'    => $role_id,
                    'capability' => sanitize_key( $cap )
                ]);
            }
        }

        return $role_id;
    }

    /**
     * Get all capabilities assigned to a specific role ID.
     */
    public function get_capabilities( $role_id ) {
        global $wpdb;
        
        return $wpdb->get_col( $wpdb->prepare(
            "SELECT capability FROM {$this->table_caps} WHERE role_id = %d",
            $role_id
        ));
    }

    /**
     * Fetch all roles for the Super Admin dashboard list.
     */
    public function get_all_roles() {
        global $wpdb;
        return $wpdb->get_results( "SELECT * FROM {$this->table_roles} ORDER BY role_name ASC" );
    }
}