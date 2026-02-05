<?php
namespace DelightEDU\Roles;

class PermissionsRegistry {

    /**
     * Returns a grouped array of all available capabilities.
     * This structure is perfect for rendering grouped checkboxes in the UI.
     */
    public static function get_all() {
        return [
            'Students' => [
                'view_students'   => 'View Student Records',
                'add_students'    => 'Admit New Students',
                'edit_students'   => 'Edit Student Info',
                'delete_students' => 'Remove Students',
            ],
            'Academics' => [
                'manage_classes'  => 'Manage Classes & Sections',
                'mark_attendance' => 'Take Attendance',
                'view_attendance' => 'View Attendance Reports',
                'manage_exams'    => 'Create/Edit Exams',
                'enter_marks'     => 'Enter Exam Marks',
            ],
            'Finance' => [
                'view_fees'       => 'View Fee Structures',
                'collect_fees'    => 'Collect/Record Payments',
                'manage_invoices' => 'Generate Invoices',
                'view_reports'    => 'View Financial Reports',
            ],
            'Staff & Roles' => [
                'manage_staff'    => 'Add/Edit Staff Members',
                'manage_roles'    => 'Configure Roles & Permissions',
            ]
        ];
    }

    /**
     * Get a flat list of just the keys (e.g., ['view_students', 'add_students', ...])
     */
    public static function get_list() {
        $flat_list = [];
        foreach ( self::get_all() as $group => $caps ) {
            foreach ( $caps as $key => $label ) {
                $flat_list[] = $key;
            }
        }
        return $flat_list;
    }
}