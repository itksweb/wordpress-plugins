<?php
/**
 * Plugin Name: Custom Autoupdate Notification Email
 * Plugin URI:  https://github.com/itksweb/wordpress-plugins
 * Description: Sends WordPress automatic update notification emails to a custom email address with customizable subject, body, sender details, and dynamic tokens. Groups multiple updates into a single email.
 * Version:     1.5
 * Author:      Kingsley Ikpefan
 * Author URI:  https://wa.me/2348060719978
 * License:     GPL2
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Add settings menu
 */
function cane_add_admin_menu() {
    add_options_page(
        'Update Email Settings',
        'Update Email Settings',
        'manage_options',
        'cane-settings',
        'cane_settings_page'
    );
}
add_action( 'admin_menu', 'cane_add_admin_menu' );

/**
 * Register settings
 */
function cane_register_settings() {
    register_setting( 'cane_settings_group', 'cane_email_address' );
    register_setting( 'cane_settings_group', 'cane_email_subject' );
    register_setting( 'cane_settings_group', 'cane_email_body' );
    register_setting( 'cane_settings_group', 'cane_from_name' );
    register_setting( 'cane_settings_group', 'cane_from_email' );
}
add_action( 'admin_init', 'cane_register_settings' );

/**
 * Settings page
 */
function cane_settings_page() { ?>
    <div class="wrap">
        <h1>Custom Update Notification Email</h1>
        <?php if ( isset($_GET['cane_test_sent']) && $_GET['cane_test_sent'] == '1' ): ?>
            <div class="updated notice is-dismissible"><p>✅ Test email has been sent. Check your inbox!</p></div>
        <?php endif; ?>

        <form method="post" action="options.php">
            <?php settings_fields( 'cane_settings_group' ); ?>
            <?php do_settings_sections( 'cane_settings_group' ); ?>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Recipient Email</th>
                    <td><input type="email" name="cane_email_address" value="<?php echo esc_attr( get_option('cane_email_address', get_bloginfo('admin_email')) ); ?>" class="regular-text" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Email Subject</th>
                    <td>
                        <input type="text" name="cane_email_subject" value="<?php echo esc_attr( get_option('cane_email_subject', '🚀 [site_name] Updates Completed') ); ?>" class="regular-text" />
                        <p class="description">Tokens: <code>[site_name]</code>, <code>[site_url]</code>, <code>[date]</code>.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Email Body</th>
                    <td>
                        <textarea name="cane_email_body" rows="8" class="large-text"><?php echo esc_textarea( get_option('cane_email_body', "Hi Admin,\n\nThe following updates were installed on [date]:\n\n[updates_list]\n\nSite: [site_url]\n\nCheers,\n[from_name]") ); ?></textarea>
                        <p class="description">Tokens: <code>[site_name]</code>, <code>[site_url]</code>, <code>[date]</code>, <code>[updates_list]</code>, <code>[from_name]</code>, <code>[from_email]</code>.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">From Name</th>
                    <td><input type="text" name="cane_from_name" value="<?php echo esc_attr( get_option('cane_from_name', get_bloginfo('name')) ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">From Email</th>
                    <td><input type="email" name="cane_from_email" value="<?php echo esc_attr( get_option('cane_from_email', get_bloginfo('admin_email')) ); ?>" class="regular-text" /></td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>

        <form method="post">
            <?php wp_nonce_field( 'cane_send_test_mail', 'cane_test_nonce' ); ?>
            <input type="hidden" name="cane_send_test" value="1">
            <?php submit_button( 'Send Test Mail', 'secondary' ); ?>
        </form>
    </div>
<?php }

/**
 * Replace tokens with real values
 */
function cane_replace_tokens( $text, $extra = array() ) {
    $replacements = array(
        '[site_name]'   => get_bloginfo('name'),
        '[site_url]'    => home_url(),
        '[date]'        => date_i18n( get_option('date_format') . ' ' . get_option('time_format') ),
        '[from_name]'   => get_option( 'cane_from_name', get_bloginfo('name') ),
        '[from_email]'  => get_option( 'cane_from_email', get_bloginfo('admin_email') ),
        '[updates_list]' => isset($extra['updates_list']) ? $extra['updates_list'] : '',
    );
    return str_replace( array_keys($replacements), array_values($replacements), $text );
}

/**
 * Custom From filters
 */
function cane_custom_from_name( $name ) {
    return get_option( 'cane_from_name', $name );
}
function cane_custom_from_email( $email ) {
    return get_option( 'cane_from_email', $email );
}

/**
 * Send grouped update email
 */
function cane_send_update_email( $results ) {
    if ( empty($results) ) return;

    $updates_list = "";

    // Core
    if ( ! empty($results['core']) ) {
        foreach ( $results['core'] as $core ) {
            if ( ! empty($core->result) ) {
                $updates_list .= "✅ Website core updated from {$core->current} → {$core->new_version}\n";
            }
        }
    }

    // Plugins
    if ( ! empty($results['plugin']) ) {
        foreach ( $results['plugin'] as $plugin => $data ) {
            if ( ! empty($data->result) && isset($data->item->Name) ) {
                $old = isset($data->old_version) ? $data->old_version : '';
                $new = isset($data->new_version) ? $data->new_version : '';
                $updates_list .= "✅ Plugin: {$data->item->Name} updated from {$old} → {$new}\n";
            }
        }
    }

    // Themes
    if ( ! empty($results['theme']) ) {
        foreach ( $results['theme'] as $theme => $data ) {
            if ( ! empty($data->result) && isset($data->item->Name) ) {
                $old = isset($data->old_version) ? $data->old_version : '';
                $new = isset($data->new_version) ? $data->new_version : '';
                $updates_list .= "✅ Theme: {$data->item->Name} updated from {$old} → {$new}\n";
            }
        }
    }

    if ( empty($updates_list) ) return;

    $to      = get_option('cane_email_address', get_bloginfo('admin_email'));
    $subject = cane_replace_tokens( get_option('cane_email_subject', '🚀 [site_name] Updates Completed') );
    $body    = cane_replace_tokens( get_option('cane_email_body', "Hi Admin,\n\nThe following updates were installed on [date]:\n\n[updates_list]\n\nSite: [site_url]\n\nCheers,\n[from_name]"), array( 'updates_list' => nl2br($updates_list) ) );

    add_filter( 'wp_mail_from', 'cane_custom_from_email' );
    add_filter( 'wp_mail_from_name', 'cane_custom_from_name' );
    add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );

    wp_mail( $to, $subject, nl2br($body) );

    remove_filter( 'wp_mail_from', 'cane_custom_from_email' );
    remove_filter( 'wp_mail_from_name', 'cane_custom_from_name' );
}

/**
 * Hook after updates complete
 */
add_action( 'automatic_updates_complete', 'cane_send_update_email' );

/**
 * Handle test mail
 */
function cane_handle_test_mail() {
    if ( isset($_POST['cane_send_test']) && $_POST['cane_send_test'] == '1' ) {
        check_admin_referer( 'cane_send_test_mail', 'cane_test_nonce' );

        $to      = get_option('cane_email_address', get_bloginfo('admin_email'));
        $subject = cane_replace_tokens( get_option('cane_email_subject', '🚀 [site_name] Updates Completed') );
        $body    = cane_replace_tokens( get_option('cane_email_body', "Hi Admin,\n\nYour website ([site_url]) was successfully updated.\n\n The following updates were installed on [date]:\n\n[updates_list]\n\nCheers,\n[from_name]"), array(
            'updates_list' => "✅ Core: WordPress updated from 6.5.2 → 6.5.3\n✅ Plugin: Test Plugin updated from 1.0 → 1.1\n✅ Theme: Test Theme updated from 2.3 → 2.4"
        ) );

        add_filter( 'wp_mail_from', 'cane_custom_from_email' );
        add_filter( 'wp_mail_from_name', 'cane_custom_from_name' );
        add_filter( 'wp_mail_content_type', function() { return 'text/html'; } );

        wp_mail( $to, $subject, nl2br($body) );

        wp_redirect( admin_url('options-general.php?page=cane-settings&cane_test_sent=1') );
        exit;
    }
}
add_action( 'admin_init', 'cane_handle_test_mail' );
