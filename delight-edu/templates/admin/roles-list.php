
<div class="wrap dedu-admin-wrapper">
    <?php wp_nonce_field('dedu_bulk_roles_action', 'dedu-role-nonce'); ?>
    <div class="dedu-page-header">
        <h1 class="dedu-page-title">Staff Roles</h1>
        <a href="?page=dedu-staff-roles&action=add" class="dedu-btn dedu-btn-primary">
            <span class="dashicons dashicons-plus"></span> Add New Role
        </a>
    </div>
    <div class="dedu-card dedu-table-container">
        <div class="dedu-table-toolbar">
            <div class="dedu-toolbar-left">
                <select id="dedu-bulk-action-selector" class="dedu-dropdown-btn">
                    <option value="">Bulk Actions</option>
                    <option value="delete">Delete</option>
                    <option value="edit">Edit</option>
                </select>
                <button type="button" id="dedu-apply-bulk-action" class="dedu-btn-apply">Apply</button>
            </div>
            <div class="dedu-toolbar-right">
                <div class="dedu-search-wrapper">
                    <span class="dashicons dashicons-search"></span>
                    <input type="text" id="dedu-role-search" placeholder="Filter roles..." class="dedu-search-input">
                </div>
            </div>
        </div>
        <div class="dedu-table-container">
            <table class="dedu-table-modern dedu-js-paginated">
                <thead>
                    <tr>
                        <th class="col-cb"><input type="checkbox" id="dedu-select-all"></th>
                        <th>Role Name</th>
                        <th>Permissions Assigned</th> <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $all_roles ) ) : ?>
                        <tr class="dedu-no-data-static">
                            <td colspan="4">
                                <div class="dedu-empty-state">
                                    <span class="dashicons dashicons-database"></span>
                                    <p>No roles found. Start by creating your first role!</p>
                                </div>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $all_roles as $role ) : 
                            $cap_count = isset($role->cap_count) ? $role->cap_count : 0;
                        ?>
                            <tr>
                                <td class="col-cb">
                                    <input type="checkbox" class="dedu-role-checkbox" value="<?php echo $role->id; ?>">
                                </td>
                                <td class="text-heading"><?php echo esc_html( $role->role_name ); ?></td>
                                <td>
                                    <span class="dedu-badge-count">
                                        <?php echo $cap_count; ?> Permissions
                                    </span>
                                </td>
                                <td class="dedu-row-action">
                                    <a href="?page=dedu-staff-roles&action=edit&id=<?php echo $role->id; ?>" class="dedu-action-link edit" title="Edit">
                                        <span class="dashicons dashicons-edit"></span>
                                    </a>
                                    <a href="javascript:void(0);" 
                                        class="dedu-action-link delete dedu-delete-role" 
                                        data-id="<?php echo $role->id; ?>" 
                                        data-name="<?php echo esc_attr($role->role_name); ?>" 
                                        data-nonce="<?php echo wp_create_nonce('dedu_delete_role_' . $role->id); ?>"
                                        title="Delete">
                                            <span class="dashicons dashicons-trash"></span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <tr id="dedu-no-search-results" style="display: none;">
                            <td colspan="4">
                                <div class="dedu-empty-state">
                                    <span class="dashicons dashicons-search"></span>
                                    <p>No roles match your search criteria.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>    
                </tbody>
            </table>
        </div>
        <div class="dedu-table-footer">
            <div class="dedu-table-footer-left">
                <label for="dedu-rows-per-page">Show</label>
                <select id="dedu-rows-per-page" class="dedu-select-sm">
                    <option value="2" selected>2</option>
                    <option value="5" >5</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span>entries</span>
            </div>

            <div class="dedu-table-footer-right">
                <div class="dedu-pagination-info">
                    Showing <span id="current-visible-range">0-0</span> of <span id="total-visible-items">0</span>
                </div>
                <div class="dedu-pagination-controls">
                    <button type="button" id="prev-page" class="butt">‹</button>
                    <span id="page-numbers"></span>
                    <button type="button" id="next-page" class="butt">›</button>
                </div>
            </div>
        </div>
        </div>         
    </div>
</div>



