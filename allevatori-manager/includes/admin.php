<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function am_admin_menu() {
    add_menu_page(
        'Allevatori Manager',
        'Allevatori',
        'manage_options',
        'am-allevatori',
        'am_admin_page',
        'dashicons-groups',
        20
    );

    add_submenu_page(
        null,
        'Modifica Allevatore',
        'Modifica Allevatore',
        'manage_options',
        'am-edit-allevatore',
        'am_edit_allevatore_page'
    );
}
add_action('admin_menu', 'am_admin_menu');

// Elenco allevatori
function am_admin_page() {
    $args = [
        'role' => 'allevatore',
        'orderby' => 'user_registered',
        'order' => 'DESC'
    ];
    $allevatori = get_users($args);

    echo '<div class="wrap"><h1>Elenco Allevatori</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Allevamento</th><th>Nome</th><th>Email</th><th>Telefono</th><th>Azione</th></tr></thead><tbody>';

    foreach ($allevatori as $a) {
        echo '<tr>';
        echo '<td>' . esc_html(get_user_meta($a->ID, 'allevamento', true)) . '</td>';
        echo '<td>' . esc_html($a->first_name . ' ' . $a->last_name) . '</td>';
        echo '<td>' . esc_html($a->user_email) . '</td>';
        echo '<td>' . esc_html(get_user_meta($a->ID, 'telefono', true)) . '</td>';
        echo '<td><a class="button button-primary" href="' . admin_url('admin.php?page=am-edit-allevatore&user_id=' . $a->ID) . '">Modifica</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

// Modifica allevatore
function am_edit_allevatore_page() {
    if (!isset($_GET['user_id'])) return;
    $user_id = intval($_GET['user_id']);
    $user = get_userdata($user_id);

    if ($_POST && check_admin_referer('am_edit_allevatore')) {
        wp_update_user([
            'ID' => $user_id,
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'user_email' => sanitize_email($_POST['user_email']),
        ]);
        update_user_meta($user_id, 'allevamento', sanitize_text_field($_POST['allevamento']));
        update_user_meta($user_id, 'telefono', sanitize_text_field($_POST['telefono']));

        if (!empty($_POST['password'])) {
            wp_set_password($_POST['password'], $user_id);
        }
        echo '<div class="updated"><p>Dati aggiornati!</p></div>';
    }

    echo '<div class="wrap"><h1>Modifica Allevatore</h1>';
    echo '<form method="post">';
    wp_nonce_field('am_edit_allevatore');
    echo '<table class="form-table">';
    echo '<tr><th>Nome</th><td><input type="text" name="first_name" value="' . esc_attr($user->first_name) . '"></td></tr>';
    echo '<tr><th>Cognome</th><td><input type="text" name="last_name" value="' . esc_attr($user->last_name) . '"></td></tr>';
    echo '<tr><th>Email</th><td><input type="email" name="user_email" value="' . esc_attr($user->user_email) . '"></td></tr>';
    echo '<tr><th>Allevamento</th><td><input type="text" name="allevamento" value="' . esc_attr(get_user_meta($user_id, 'allevamento', true)) . '"></td></tr>';
    echo '<tr><th>Telefono</th><td><input type="text" name="telefono" value="' . esc_attr(get_user_meta($user_id, 'telefono', true)) . '"></td></tr>';
    echo '<tr><th>Password</th><td><input type="password" name="password"></td></tr>';
    echo '</table>';
    submit_button('Aggiorna');
    echo '</form></div>';
}
