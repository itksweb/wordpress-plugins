public function add_settings_page() {
    // 1. Register the Top-Level Parent Menu: "Webhouse"
    add_menu_page(
        'Webhouse Dashboard',
        'Webhouse',
        'manage_options',
        'webhouse',
        // --- CHANGE IS HERE ---
        [ $this, 'redirect_parent_to_first_submenu' ], // Use the new redirection method
        // ----------------------
        'dashicons-admin-home',
        26
    );

    // 2. Register the Submenu: "Gmail SMTP" (This remains the same)
    add_submenu_page(
        'webhouse',
        'WP Gmail SMTP Settings',
        'Gmail SMTP',
        'manage_options',
        $this->plugin_page,
        [ $this, 'render_settings_page' ]
    );
}