<?php
if (!defined('ABSPATH')) exit;

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
    echo '<thead><tr>
            <th>Allevamento</th>
            <th>Codice ANACT</th>
            <th>Nome</th>
            <th>Email</th>
            <th>Cellulare</th>
            <th>Telefono</th>
            <th>Indirizzo</th>
            <th>Azione</th>
          </tr></thead><tbody>';

    foreach ($allevatori as $a) {
        echo '<tr>';
        echo '<td>' . esc_html(get_user_meta($a->ID, 'allevamento_nome', true)) . '</td>';
        echo '<td>' . esc_html(get_user_meta($a->ID, 'codice_anact', true)) . '</td>';
        echo '<td>' . esc_html($a->first_name . ' ' . $a->last_name) . '</td>';
        echo '<td>' . esc_html($a->user_email) . '</td>';
        echo '<td>' . esc_html(get_user_meta($a->ID, 'cellulare', true)) . '</td>';
        echo '<td>' . esc_html(get_user_meta($a->ID, 'telefono', true)) . '</td>';
        echo '<td>' . esc_html(get_user_meta($a->ID, 'indirizzo', true)) . '</td>';
        echo '<td><a class="button button-primary" href="' . admin_url('admin.php?page=am-edit-allevatore&user_id=' . $a->ID) . '">Modifica</a></td>';
        echo '</tr>';
    }

    echo '</tbody></table></div>';
}

// ----- MODIFICA ALLEVATORE -----
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

        $meta_fields = [
            'allevamento_nome','codice_anact','cellulare','telefono','indirizzo',
            'latitudine','longitudine','citta','cap','provincia',
            'immagine_evidenza','logo_allevamento','descrizione_allevamento',
            'facebook_allevamento','instagram_allevamento','sito_allevamento'
        ];

foreach ($meta_fields as $f) {
    if (isset($_POST[$f])) {

        // Se è un ID di un'immagine → NON sanificare come testo
        if ($f === 'immagine_evidenza' || $f === 'logo_allevamento') {
            update_user_meta($user_id, $f, intval($_POST[$f]));
        }

        // Altri campi normali
        else {
            update_user_meta($user_id, $f, sanitize_text_field($_POST[$f]));
        }
    }
}



        if (!empty($_POST['password'])) {
            wp_set_password($_POST['password'], $user_id);
        }

        echo '<div class="updated"><p>Dati aggiornati!</p></div>';
    }

    // Recupero metadati
    $fields = [
        'allevamento_nome','codice_anact','cellulare','telefono','indirizzo',
        'latitudine','longitudine','citta','cap','provincia',
        'immagine_evidenza','logo_allevamento','descrizione_allevamento',
        'facebook_allevamento','instagram_allevamento','sito_allevamento'
    ];

    foreach($fields as $f){
        $$f = get_user_meta($user_id, $f, true);
    }

    echo '<div class="wrap"><h1>Modifica Allevatore</h1>';
    echo '<form method="post">';
    wp_nonce_field('am_edit_allevatore');
    echo '<table class="form-table">';

    // ----- CAMPI BASE -----
    echo '<tr><th>Nome</th><td><input type="text" name="first_name" value="' . esc_attr($user->first_name) . '"></td></tr>';
    echo '<tr><th>Cognome</th><td><input type="text" name="last_name" value="' . esc_attr($user->last_name) . '"></td></tr>';
    echo '<tr><th>Email</th><td><input type="email" name="user_email" value="' . esc_attr($user->user_email) . '"></td></tr>';

    echo '<tr><th>Allevamento</th><td><input type="text" name="allevamento_nome" value="' . esc_attr($allevamento_nome) . '"></td></tr>';
    echo '<tr><th>Codice ANACT</th><td><input type="text" name="codice_anact" value="' . esc_attr($codice_anact) . '"></td></tr>';
    echo '<tr><th>Cellulare</th><td><input type="text" name="cellulare" value="' . esc_attr($cellulare) . '"></td></tr>';
    echo '<tr><th>Telefono</th><td><input type="text" name="telefono" value="' . esc_attr($telefono) . '"></td></tr>';

    // ----- INDIRIZZO -----
    echo '<tr><th>Indirizzo</th><td><input type="text" name="indirizzo" value="' . esc_attr($indirizzo) . '" style="width:100%">';
    echo '<input type="hidden" name="latitudine" value="' . esc_attr($latitudine) . '">';
    echo '<input type="hidden" name="longitudine" value="' . esc_attr($longitudine) . '">';
    echo '<input type="hidden" name="citta" value="' . esc_attr($citta) . '">';
    echo '<input type="hidden" name="cap" value="' . esc_attr($cap) . '">';
    echo '<input type="hidden" name="provincia" value="' . esc_attr($provincia) . '">';
    echo '</td></tr>';

    // ----- IMMAGINE EVIDENZA -----
    echo '<tr><th>Immagine in evidenza</th><td>';

    if($immagine_evidenza){
        $url = wp_get_attachment_url($immagine_evidenza);
        echo '<img id="immagine_evidenza_preview" src="'.$url.'" style="width:150px;height:auto;">';
    } else {
        echo '<img id="immagine_evidenza_preview" src="" style="display:none;width:150px;height:auto;">';
    }

    echo '<br><input type="hidden" id="immagine_evidenza" name="immagine_evidenza" value="' . esc_attr($immagine_evidenza) . '">';
    echo '<button type="button" class="button" id="upload_immagine_evidenza">Seleziona immagine</button>';
    echo '</td></tr>';

    // ----- LOGO -----
    echo '<tr><th>Logo</th><td>';

    if($logo_allevamento){
        $url2 = wp_get_attachment_url($logo_allevamento);
        echo '<img id="logo_allevamento_preview" src="'.$url2.'" style="width:150px;height:auto;">';
    } else {
        echo '<img id="logo_allevamento_preview" src="" style="display:none;width:150px;height:auto;">';
    }

    echo '<br><input type="hidden" id="logo_allevamento" name="logo_allevamento" value="' . esc_attr($logo_allevamento) . '">';
    echo '<button type="button" class="button" id="upload_logo_allevamento">Seleziona logo</button>';
    echo '</td></tr>';

    // ----- SOCIAL -----
    echo '<tr><th>Descrizione</th><td><textarea name="descrizione_allevamento" rows="5" cols="50">' . esc_textarea($descrizione_allevamento) . '</textarea></td></tr>';
    echo '<tr><th>Facebook</th><td><input type="url" name="facebook_allevamento" value="' . esc_attr($facebook_allevamento) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Instagram</th><td><input type="url" name="instagram_allevamento" value="' . esc_attr($instagram_allevamento) . '" class="regular-text"></td></tr>';
    echo '<tr><th>Sito web</th><td><input type="url" name="sito_allevamento" value="' . esc_attr($sito_allevamento) . '" class="regular-text"></td></tr>';

    echo '<tr><th>Password</th><td><input type="password" name="password"></td></tr>';

    echo '</table>';
    submit_button('Aggiorna');
    echo '</form></div>';
}


// ----- SCRIPTS DEI MEDIA -----
function am_enqueue_media_uploader($hook) {
    // Carichiamo sempre su pagine admin dove servono i campi:
    // - la pagina del menu principale 'am-allevatori' (toplevel_page_am-allevatori)
    // - la pagina di modifica nascosta 'am-edit-allevatore' (admin_page_am-edit-allevatore)
    // - la pagina profilo utente (profile.php) e la pagina di modifica utente (user-edit.php)
    $allowed = [
        'toplevel_page_am-allevatori',
        'admin_page_am-edit-allevatore',
        'profile.php',
        'user-edit.php'
    ];

    // Se il $hook non è fra quelli, proviamo anche a verificare la GET 'page' (utile per pagine custom)
    $page = isset($_GET['page']) ? $_GET['page'] : '';

    if (!in_array($hook, $allowed) && !in_array($page, ['am-allevatori','am-edit-allevatore'])) {
        return;
    }

    // Assicuriamoci che la libreria media sia pronta
    wp_enqueue_media();

    // Usa plugins_url per costruire il path corretto anche se questo file è in includes/
    $script_url = plugins_url('assets/js/am-media-uploader.js', dirname(__FILE__));


    wp_enqueue_script(
        'am-media-uploader',
        $script_url,
        ['jquery'],
        filemtime( plugin_dir_path( __FILE__ ) . '../assets/js/am-media-uploader.js' ), // cache busting (se file esiste)
        true
    );

    // DEBUG: stampa in console che lo script è stato caricato (rimuovere in produzione)
    wp_add_inline_script('am-media-uploader', "console.log('am-media-uploader loaded. hook={$hook} page={$page}');");
}
add_action('admin_enqueue_scripts', 'am_enqueue_media_uploader');
