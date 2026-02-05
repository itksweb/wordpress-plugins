
<?php
$is_edit = ! empty( $role );
$title   = $is_edit ? 'Edit Role: ' . esc_html( $role->role_name ) : 'Add New Staff Role';
$current_caps = ( $is_edit && isset($role->capabilities) ) ? $role->capabilities : [];
?>

<div class="wrap dedu-admin-wrapper">
    <div class="dedu-page-header">
        <div>
            <h1 class="dedu-page-title"><?php echo esc_html( $title ); ?></h1>
            <p class="dedu-page-subtitle">Define the name and access levels for your staff members.</p>
        </div>
        <div class="dedu-header-actions">
            <a href="<?php echo admin_url('admin.php?page=dedu-staff-roles'); ?>" class="dedu-btn dedu-btn-secondary">
                <span class="dashicons dashicons-arrow-left-alt"></span> Back to List
            </a>
        </div>
    </div>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="dedu_save_role">
        <?php if ( $is_edit ) : ?>
            <input type="hidden" name="role_id" value="<?php echo $role->id; ?>">
        <?php endif; ?>
        <?php wp_nonce_field( 'dedu_role_nonce' ); ?>

        <div class="dedu-card dedu-carded">
            <div class="dedu-card-title">
                <span class="dashicons dashicons-id"></span> Role Identity
            </div>
            
            <div class="dedu-form-group">
                <label class="dedu-label" for="role_name">Display Name</label>
                <input name="role_name" type="text" id="role_name" 
                       value="<?php echo $is_edit ? esc_attr($role->role_name) : ''; ?>" 
                       class="dedu-input" placeholder="e.g. Senior Accountant" required>
                <p class="dedu-field-help">Give this role a clear name that describes the staff's responsibility.</p>
            </div>
        </div>

        <div class="dedu-card dedu-carded">
            <div class="dedu-card-title">
                <span class="dashicons dashicons-shield"></span> Access Permissions
            </div>
            
            <div class="dedu-permissions-grid">
                <?php foreach ( $groups as $group_name => $capabilities ) : ?>
                    <div class="dedu-permission-card">
                        <h4 class="dedu-group-label"><?php echo esc_html( $group_name ); ?></h4>
                        <div class="dedu-cap-list">
                            <?php foreach ( $capabilities as $cap_slug => $cap_label ) : ?>
                                <label class="dedu-checkbox-label">
                                    <input type="checkbox" name="capabilities[]" value="<?php echo esc_attr( $cap_slug ); ?>"
                                        <?php checked( in_array( $cap_slug, $current_caps ) ); ?>>
                                    <span class="dedu-checkbox-text"><?php echo esc_html( $cap_label ); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="dedu-form-actions">
            <button type="submit" class="dedu-btn dedu-btn-primary">
                <?php echo $is_edit ? 'Update Staff Role' : 'Create Staff Role'; ?>
            </button>
        </div>
    </form>
</div>