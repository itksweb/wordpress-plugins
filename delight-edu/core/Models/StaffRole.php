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

        // 1. Insert the Role into the main table
        $wpdb->insert( $this->table_roles, [
            'role_name' => sanitize_text_field( $name ),
            'role_slug' => $slug
        ]);

        $role_id = $wpdb->insert_id;

        // 2. Insert each capability into the secondary table
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
     * Delete a role by ID
     * * @param int $id The role ID to delete
     * @return bool True on success, false on failure
     */
    public function delete( $id ) {
        global $wpdb;

        // 1. Delete associated capabilities first (Cleanup Orphans)
        $wpdb->delete(
            $this->table_caps, 
            [ 'role_id' => $id ], 
            [ '%d' ]
        );

        // 2. Delete the main role
        $result = $wpdb->delete(
            $this->table_roles,
            [ 'id' => $id ],
            [ '%d' ]
        );

        return false !== $result;
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
    // public function get_all_roles() {
    //     global $wpdb;
    //     return $wpdb->get_results( "SELECT * FROM {$this->table_roles} ORDER BY role_name ASC" );
    // }

    public function get_all_roles() {
        global $wpdb;
        $query = "
            SELECT r.*, COUNT(c.id) as cap_count 
            FROM {$this->table_roles} r
            LEFT JOIN {$this->table_caps} c ON r.id = c.role_id
            GROUP BY r.id
            ORDER BY r.role_name ASC
        ";
        return $wpdb->get_results( $query );
    }

    /**
     * Get a single role by ID
     */
    public function get_role( $id ) {
        global $wpdb;
        
        // 1. Fetch the main role info
        $role = $wpdb->get_row( $wpdb->prepare( 
            "SELECT * FROM {$this->table_roles} WHERE id = %d", 
            $id 
        ));

        if ( $role ) {
            // 2. Fetch the capabilities from the other table
            $caps = $wpdb->get_col( $wpdb->prepare( 
                "SELECT capability FROM {$this->table_caps} WHERE role_id = %d", 
                $id 
            ));
            
            // 3. Attach them to the object so our template can find them
            $role->capabilities = $caps; 
        }

        return $role;
    }

    /**
     * Update an existing role
     */
    public function update( $id, $name, $capabilities = [] ) {
        global $wpdb;

        // 1. Update the main role name
        $wpdb->update(
            $this->table_roles,
            [ 'role_name' => sanitize_text_field( $name ) ],
            [ 'id' => $id ],
            [ '%s' ],
            [ '%d' ]
        );

        // 2. Clear old permissions for this role
        $wpdb->delete( $this->table_caps, [ 'role_id' => $id ], [ '%d' ] );

        // 3. Insert the new set of permissions
        if ( ! empty( $capabilities ) ) {
            foreach ( $capabilities as $cap ) {
                $wpdb->insert( $this->table_caps, [
                    'role_id'    => $id,
                    'capability' => sanitize_key( $cap )
                ]);
            }
        }

        return true;
    }
}