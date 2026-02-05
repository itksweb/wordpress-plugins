<?php
namespace DelightEDU\Database;

/**
 * Handles Plugin Database Migrations
 */
class Schema {

    /**
     * Create the custom tables
     */
    public static function install() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // 1. Classes Table (The foundation)
        $table_classes = $wpdb->prefix . 'dedu_classes';
        $sql_classes = "CREATE TABLE $table_classes (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            class_name varchar(100) NOT NULL,
            section_name varchar(50),
            capacity int(3) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // 2. Students Table (Relational)
        $table_students = $wpdb->prefix . 'dedu_students';
        $sql_students = "CREATE TABLE $table_students (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL, -- Links to wp_users
            class_id bigint(20) NOT NULL, -- Links to our classes table
            admission_no varchar(50) NOT NULL,
            roll_no varchar(50),
            date_of_birth date,
            gender varchar(10),
            phone varchar(20),
            address text,
            status enum('active', 'suspended', 'graduated') DEFAULT 'active',
            PRIMARY KEY  (id),
            UNIQUE KEY admission_no (admission_no)
        ) $charset_collate;";

        // 3. Custom Staff Roles Table
        $table_staff_roles = $wpdb->prefix . 'dedu_staff_roles';
        $sql_staff_roles = "CREATE TABLE $table_staff_roles (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            role_name varchar(100) NOT NULL,
            role_slug varchar(100) NOT NULL, -- e.g., 'junior-accountant'
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY role_slug (role_slug)
        ) $charset_collate;";

        // 4. Role Capabilities Mapping Table
        $table_capabilities = $wpdb->prefix . 'dedu_role_capabilities';
        $sql_capabilities = "CREATE TABLE $table_capabilities (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            role_id bigint(20) NOT NULL,
            capability varchar(100) NOT NULL, -- e.g., 'edit_grades', 'view_reports'
            PRIMARY KEY  (id),
            KEY role_id (role_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql_classes );
        dbDelta( $sql_students );
        dbDelta( $sql_staff_roles );
        dbDelta( $sql_capabilities );
    }
}