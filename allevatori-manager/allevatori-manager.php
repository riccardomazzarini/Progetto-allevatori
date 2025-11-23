<?php
/*
Plugin Name: Allevatori Manager
Description: Gestione registrazione e profili allevatori con dashboard dedicata.
Version: 1.0
Author: Frided Communication
*/

if ( ! defined( 'ABSPATH' ) ) exit;
error_log('✅ ALLEVATORI MANAGER PLUGIN ATTIVO');


// Define paths
define('AM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include core files
include_once AM_PLUGIN_DIR . 'includes/roles.php';
include_once AM_PLUGIN_DIR . 'includes/admin.php';
include_once AM_PLUGIN_DIR . 'includes/registration.php';
include_once AM_PLUGIN_DIR . 'includes/login.php';
include_once AM_PLUGIN_DIR . 'includes/settings.php';
include_once AM_PLUGIN_DIR . 'includes/dashboard/dashboard.php';
include_once AM_PLUGIN_DIR . 'includes/cpt-cavallo.php';
include_once AM_PLUGIN_DIR . 'includes/pagine-pubbliche/pagina-puledri.php';


// -----------------------------
// Rewrite rules dinamiche per cavalli in base al tipo
// -----------------------------
add_action('init', function() {
    $tipi = ['puledro', 'fattrice', 'stallone'];

    foreach ($tipi as $tipo) {
        add_rewrite_rule(
            '^cavalli/' . $tipo . '/([^/]+)/?$',
            'index.php?cavallo=$matches[1]',
            'top'
        );
    }
});

// -----------------------------
// Template loader per PULEDRO
// -----------------------------
add_filter('template_include', function($template) {
    if (is_singular('cavallo')) {
        $tipo = get_post_meta(get_the_ID(), '_tipo_cavallo', true);
        if (strtolower($tipo) === 'puledro') {
            $custom = plugin_dir_path(__FILE__) . 'includes/template-puledro.php';
            if (file_exists($custom)) {
                return $custom;
            }
        }
    }
    return $template;
});

// Enqueue styles
function am_enqueue_styles() {
    wp_enqueue_style('am-style', AM_PLUGIN_URL . 'assets/css/style.css');
}
add_action('wp_enqueue_scripts', 'am_enqueue_styles');


// ---- CREA O AGGIORNA RUOLO ALLEVATORE ----
function am_setup_allevatore_role() {
    $role_name = 'allevatore';

    // Permessi minimi consigliati per un utente allevatore
    $capabilities = [
        'read' => true,
        'edit_posts' => false,
        'delete_posts' => false,
    ];

    // Se il ruolo esiste già, aggiorna le capability
    if (get_role($role_name)) {
        $role = get_role($role_name);
        foreach ($capabilities as $cap => $grant) {
            if ($grant && !$role->has_cap($cap)) {
                $role->add_cap($cap);
            } elseif (!$grant && $role->has_cap($cap)) {
                $role->remove_cap($cap);
            }
        }
    } else {
        // Altrimenti crea il ruolo
        add_role($role_name, 'Allevatore', $capabilities);
    }
}
add_action('init', 'am_setup_allevatore_role');

// Gestione login allevatori prima di qualsiasi output
function am_handle_login() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['am_login'])) {
        $email = sanitize_user($_POST['email']);
        $password = $_POST['password'];

        $user = get_user_by('email', $email);
        if (!$user) $user = get_user_by('login', $email);

        if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            do_action('wp_login', $user->user_login, $user);

            // Redirect a dashboard allevatori
            if (in_array('allevatore', $user->roles)) {
                wp_redirect(site_url('/dashboard-allevatori'));
                exit;
            } else {
                wp_redirect(home_url());
                exit;
            }
        } else {
            // Memorizza l'errore in sessione per mostrarlo nello shortcode
            if(!session_id()) session_start();
            $_SESSION['am_login_error'] = 'Email o password non corretta.';
        }
    }
}
add_action('init', 'am_handle_login');

// Nascondi admin bar e blocca accesso al backend agli allevatori
function am_restrict_backend_for_allevatori() {
    $user = wp_get_current_user();

    if ( in_array('allevatore', (array) $user->roles) ) {
        // Nascondi admin bar
        show_admin_bar(false);

        // Blocca accesso al backend e redirect alla dashboard allevatori
        if ( is_admin() && ! defined('DOING_AJAX') ) {
            wp_redirect(site_url('/dashboard-allevatori'));
            exit;
        }
    }
}
add_action('after_setup_theme', 'am_restrict_backend_for_allevatori');


// Registra il campo codice_anact per gli utenti
function register_codice_anact_meta() {
    register_meta('user', 'codice_anact', [
        'type' => 'string',
        'description' => 'Codice ANACT dell’allevatore',
        'single' => true,
        'show_in_rest' => true, // utile se usi l’API REST
    ]);
}
add_action('init', 'register_codice_anact_meta');


// Aggiungi campo nel profilo utente
function add_codice_anact_profile_field($user) {
    ?>
    <h3>Codice ANACT</h3>
    <table class="form-table">
        <tr>
            <th><label for="codice_anact">Codice ANACT</label></th>
            <td>
                <input type="text" name="codice_anact" id="codice_anact" value="<?php echo esc_attr(get_user_meta($user->ID, 'codice_anact', true)); ?>" class="regular-text" readonly />
                <p class="description">Codice ANACT assegnato all’allevatore.</p>
            </td>
        </tr>
    </table>
    <?php
}
add_action('show_user_profile', 'add_codice_anact_profile_field');
add_action('edit_user_profile', 'add_codice_anact_profile_field');

// -----------------------------
// Rewrite per /allevatore/{slug}/
// -----------------------------
function am_register_allevatore_rewrite() {
    add_rewrite_tag('%allevatore%', '([^&]+)');
    // accetta lettere, numeri, punti, trattini, underscore e percentuali (per slug encoded)
    add_rewrite_rule('^allevatore/([^/]+)/?$', 'index.php?allevatore=$matches[1]', 'top');
}
add_action('init', 'am_register_allevatore_rewrite');

// -----------------------------
// Template loader per allevatore
// -----------------------------
function am_template_allevatore($template) {
    if (get_query_var('allevatore')) {
        $new_template = plugin_dir_path(__FILE__) . 'includes/template-allevatore.php';
        if (file_exists($new_template)) {
            return $new_template;
        }
    }
    return $template;
}
add_filter('template_include', 'am_template_allevatore');

// -----------------------------
// Template loader per puledro (CPT cavallo)
// -----------------------------

function am_template_puledro($template) {
    error_log('💡 [am_template_puledro] filtro attivo su pagina: ' . $_SERVER['REQUEST_URI']);

    if (is_singular('cavallo')) {
        $post_id = get_the_ID();
        $tipo = get_post_meta($post_id, '_tipo_cavallo', true);

        error_log('💡 È un singolo cavallo, ID=' . $post_id);
        error_log('💡 tipo cavallo meta=' . var_export($tipo, true));

        $new_template = plugin_dir_path(__FILE__) . 'includes/template-puledro.php';
        $exists = file_exists($new_template) ? 'YES' : 'NO';
        error_log('💡 template path: ' . $new_template . ' | exists=' . $exists);

        if (strtolower(trim($tipo)) === 'puledro') {
            error_log('✅ MATCH: tipo cavallo è "puledro", imposto template personalizzato');
            return $new_template;
        } else {
            error_log('❌ NON È puledro, template default');
        }
    } else {
        error_log('❌ Non è un singolo cavallo');
    }

    return $template;
}


add_filter('template_include', 'am_template_puledro');


// -----------------------------
// Flush rules solo all’attivazione
// -----------------------------
function am_flush_rewrite_rules_on_activation() {
    am_register_allevatore_rewrite();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'am_flush_rewrite_rules_on_activation');

// -----------------------------
// Flush anche alla disattivazione (pulizia)
// -----------------------------
function am_flush_rewrite_rules_on_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'am_flush_rewrite_rules_on_deactivation');

// Genera slug "pulito" e lo salva come meta quando si aggiorna o crea un allevatore
function am_sync_allevatore_slug_meta($user_id) {
    $user = get_userdata($user_id);
    if (in_array('allevatore', (array)$user->roles)) {
        $slug = sanitize_title($user->user_login);
        update_user_meta($user_id, 'allevatore_slug', $slug);
    }
}
add_action('profile_update', 'am_sync_allevatore_slug_meta');
add_action('user_register', 'am_sync_allevatore_slug_meta');

// -------------------------
// Salva o aggiorna puledro via AJAX
// -------------------------
add_action('wp_ajax_am_save_puledro', function(){
    error_log("DEBUG: am_save_puledro chiamato");

    if (!is_user_logged_in()) wp_send_json_error('Non autorizzato');

    // Controllo nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'am_puledro_nonce')) {
        wp_send_json_error('Nonce non valido');
    }

    if (!current_user_can('edit_cavalli')) wp_send_json_error('Permessi insufficienti');

    $user_id = get_current_user_id();
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $nome = isset($_POST['nome_puledro']) ? sanitize_text_field($_POST['nome_puledro']) : '';
    $anno = isset($_POST['anno_nascita']) ? intval($_POST['anno_nascita']) : date('Y');
    $sesso = isset($_POST['sesso']) ? sanitize_text_field($_POST['sesso']) : '';
    $padre = isset($_POST['padre']) ? sanitize_text_field($_POST['padre']) : '';
    $madre = isset($_POST['madre']) ? sanitize_text_field($_POST['madre']) : '';
    $disponibilita = isset($_POST['disponibilita']) ? sanitize_text_field($_POST['disponibilita']) : 'disponibile';
    $foto_ids = isset($_POST['foto_ids']) ? array_map('intval', (array)$_POST['foto_ids']) : [];

    if (empty($nome)) wp_send_json_error('Nome mancante');

// Se esiste post_id => aggiorna, altrimenti crea nuovo
if ($post_id) {

    // Recupero il vecchio titolo prima dell'update
    $old_title = get_the_title($post_id);

    // Aggiorno il titolo
    $postarr = [
        'ID'         => $post_id,
        'post_title' => $nome,
    ];
    $updated = wp_update_post($postarr);
    if (!$updated || is_wp_error($updated)) wp_send_json_error('Errore aggiornamento puledro');

    // Se il nome è cambiato, aggiorna anche lo slug (post_name)
    if ($old_title !== $nome) {
        $new_slug = sanitize_title($nome);
        wp_update_post([
            'ID'        => $post_id,
            'post_name' => $new_slug,
        ]);
    }

} else {

    // Creazione nuovo post
    $postarr = [
        'post_title'  => $nome,
        'post_type'   => 'cavallo',
        'post_status' => 'publish',
        'post_author' => $user_id,
    ];
    $post_id = wp_insert_post($postarr);
    if (!$post_id || is_wp_error($post_id)) wp_send_json_error('Errore creazione puledro');
}


    // Aggiorna meta comuni
    update_post_meta($post_id, '_tipo_cavallo', 'puledro');
    update_post_meta($post_id, '_produzione', (string)$anno);
    update_post_meta($post_id, '_sesso', $sesso);
    update_post_meta($post_id, '_padre', $padre);
    update_post_meta($post_id, '_madre', $madre);
    update_post_meta($post_id, '_disponibilita', $disponibilita);
    update_post_meta($post_id, '_allevatore_id', $user_id);

    // Salva immagini (max 3) e setta featured image
    if (!empty($foto_ids)) {
        update_post_meta($post_id, '_foto_id', $foto_ids[0] ?? '');
        update_post_meta($post_id, '_foto2_id', $foto_ids[1] ?? '');
        update_post_meta($post_id, '_foto3_id', $foto_ids[2] ?? '');

        if(!empty($foto_ids[0])) set_post_thumbnail($post_id, $foto_ids[0]);
    }

    // HTML riga tabella
    $sesso_out = $sesso ? strtoupper(substr($sesso,0,1)) : '-';
    $dispon_out = $disponibilita ?: 'Disponibile';

    ob_start(); ?>
    <tr data-id="<?php echo esc_attr($post_id); ?>">
        <td><?php echo esc_html($anno); ?></td>
        <td><strong><?php echo esc_html($nome); ?></strong></td>
        <td><?php echo esc_html($sesso_out); ?></td>
        <td><?php echo esc_html($padre ?: '-'); ?></td>
        <td><?php echo esc_html($madre ?: '-'); ?></td>
        <td><?php echo esc_html($dispon_out); ?></td>
        <td><button class="button am-edit-puledro" data-id="<?php echo esc_attr($post_id); ?>">✏️ Modifica</button></td>
    </tr>
    <?php
    $row_html = ob_get_clean();
    wp_send_json_success(['post_id'=>$post_id, 'row'=>$row_html]);
});


// -------------------------
// Upload immagini puledro via AJAX
// -------------------------
add_action('wp_ajax_am_upload_puledro_photo', function(){
    if (!is_user_logged_in()) wp_send_json_error('Non autorizzato');
    if (!current_user_can('upload_files')) wp_send_json_error('Permessi insufficienti');

    check_ajax_referer('am_puledro_nonce','security');

    if(empty($_FILES['file'])) wp_send_json_error('Nessun file selezionato');

    require_once(ABSPATH.'wp-admin/includes/file.php');
    require_once(ABSPATH.'wp-admin/includes/media.php');
    require_once(ABSPATH.'wp-admin/includes/image.php');

    $attach_id = media_handle_upload('file', 0);
    if(is_wp_error($attach_id)) wp_send_json_error('Errore upload: '.implode('; ',$attach_id->get_error_messages()));

    $thumb_url = wp_get_attachment_image_url($attach_id,'thumbnail');
    wp_send_json_success(['id'=>$attach_id,'url'=>$thumb_url]);
});

add_action('wp_ajax_am_get_puledro', function(){
    if (!is_user_logged_in()) wp_send_json_error('Non autorizzato');
    check_ajax_referer('am_puledro_nonce','security');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if(!$post_id) wp_send_json_error('ID mancante');

    $post = get_post($post_id);
    if(!$post || $post->post_type!=='cavallo') wp_send_json_error('Puledro non trovato');

    $current_user = get_current_user_id();
    $allevatore_id = get_post_meta($post_id,'_allevatore_id',true);
    if($allevatore_id != $current_user) wp_send_json_error('Non puoi modificare questo puledro');

    // Recupera dati
    $data = [
        'nome' => $post->post_title,
        'anno' => get_post_meta($post_id,'_produzione',true),
        'sesso' => get_post_meta($post_id,'_sesso',true),
        'padre' => get_post_meta($post_id,'_padre',true),
        'madre' => get_post_meta($post_id,'_madre',true),
        'disponibilita' => get_post_meta($post_id,'_disponibilita',true),
        'foto_ids' => [],
        'foto_urls' => [],
    ];

    $foto_ids = get_post_meta($post_id,'_foto_ids',true);
    if($foto_ids && is_array($foto_ids)){
        $data['foto_ids'] = $foto_ids;
        foreach($foto_ids as $id){
            $data['foto_urls'][$id] = wp_get_attachment_image_url($id,'thumbnail');
        }
    }

    wp_send_json_success($data);
});

// Upload logo e immagine in evidenza per la dashboard allevatore
function am_enqueue_scripts() {
    wp_enqueue_script(
        'am-front-uploader',
        plugin_dir_url(__FILE__) . 'assets/js/am-front-uploader.js',
        ['jquery'],
        '1.0',
        true
    );

    wp_localize_script('am-front-uploader', 'amAllevatore', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('am_allevatore_nonce')
    ]);
}
add_action('wp_enqueue_scripts', 'am_enqueue_scripts');


// Gestione upload AJAX
add_action('wp_ajax_am_upload_allevatore_image', 'am_upload_allevatore_image');

function am_upload_allevatore_image() {
    if (!is_user_logged_in() || !current_user_can('edit_user', get_current_user_id())) {
        wp_send_json_error('Permesso negato');
    }

    check_ajax_referer('am_allevatore_nonce', 'security');

    if (empty($_FILES['file'])) {
        wp_send_json_error('Nessun file inviato');
    }

    $file = $_FILES['file'];

    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');

    // Carica il file nella cartella uploads di WordPress
    $overrides = ['test_form' => false];
    $movefile = wp_handle_upload($file, $overrides);

    if (!$movefile || isset($movefile['error'])) {
        wp_send_json_error($movefile['error'] ?? 'Errore upload');
    }

    $user_id = get_current_user_id();
    $meta_key = isset($_POST['type']) && $_POST['type'] === 'logo' ? 'logo_allevamento' : 'immagine_evidenza';

    // Crea un attachment nella libreria media
    $attachment = [
        'post_mime_type' => $movefile['type'],
        'post_title'     => sanitize_file_name($file['name']),
        'post_content'   => '',
        'post_status'    => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $movefile['file']);
    $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Salva l'ID dell'allegato nel meta utente
    update_user_meta($user_id, $meta_key, $attach_id);

    // Restituisci sia ID che URL per il JS
    $url = wp_get_attachment_url($attach_id);
    wp_send_json_success([
        'id'  => $attach_id,
        'url' => $url
    ]);
}




add_action('wp_ajax_am_save_stallone', function(){
    if (!is_user_logged_in()) wp_send_json_error('Non autorizzato');

    // Controllo nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'am_stallone_nonce')) {
        wp_send_json_error('Nonce non valido');
    }

    $user_id = get_current_user_id();
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $nome = isset($_POST['nome_stallone']) ? sanitize_text_field($_POST['nome_stallone']) : '';

    if (empty($nome)) wp_send_json_error('Nome mancante');

    if ($post_id) {
        // Aggiorna
        $postarr = [
            'ID'         => $post_id,
            'post_title' => $nome,
        ];
        $updated = wp_update_post($postarr);
        if (!$updated || is_wp_error($updated)) wp_send_json_error('Errore aggiornamento stallone');

        // Aggiorna slug se necessario
        wp_update_post([
            'ID' => $post_id,
            'post_name' => sanitize_title($nome),
        ]);

    } else {
        // Crea nuovo stallone
        $postarr = [
            'post_title'  => $nome,
            'post_type'   => 'cavallo',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ];
        $post_id = wp_insert_post($postarr);
        if (!$post_id || is_wp_error($post_id)) wp_send_json_error('Errore creazione stallone');
    }

    // Aggiorna meta comuni
    update_post_meta($post_id, '_tipo_cavallo', 'stallone');
    update_post_meta($post_id, '_allevatore_id', $user_id);

    wp_send_json_success(['post_id'=>$post_id]);
});


add_action('wp_ajax_am_delete_stallone', function(){
    if (!is_user_logged_in()) wp_send_json_error('Non autorizzato');

    // Controllo nonce
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'am_stallone_nonce')) {
        wp_send_json_error('Nonce non valido');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) wp_send_json_error('ID mancante');

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'cavallo') wp_send_json_error('Stallone non trovato');

    // Controllo che sia dell'allevatore corrente
    $current_user = get_current_user_id();
    $allevatore_id = get_post_meta($post_id, '_allevatore_id', true);
    if ($allevatore_id != $current_user) wp_send_json_error('Non puoi eliminare questo stallone');

    $deleted = wp_delete_post($post_id, true);
    if (!$deleted) wp_send_json_error('Errore eliminazione stallone');

    wp_send_json_success();
});

// Salva / aggiorna fattrice
add_action('wp_ajax_am_save_fattrice', function(){
    if (!is_user_logged_in()) wp_send_json_error('Non autorizzato');
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'am_fattrice_nonce')) {
        wp_send_json_error('Nonce non valido');
    }

    $user_id = get_current_user_id();
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $nome = isset($_POST['nome_fattrice']) ? sanitize_text_field($_POST['nome_fattrice']) : '';

    if (empty($nome)) wp_send_json_error('Nome mancante');

    if ($post_id) {
        $postarr = [
            'ID'         => $post_id,
            'post_title' => $nome,
        ];
        $updated = wp_update_post($postarr);
        if (!$updated || is_wp_error($updated)) wp_send_json_error('Errore aggiornamento fattrice');

        wp_update_post([
            'ID' => $post_id,
            'post_name' => sanitize_title($nome),
        ]);

    } else {
        $postarr = [
            'post_title'  => $nome,
            'post_type'   => 'cavallo',
            'post_status' => 'publish',
            'post_author' => $user_id,
        ];
        $post_id = wp_insert_post($postarr);
        if (!$post_id || is_wp_error($post_id)) wp_send_json_error('Errore creazione fattrice');
    }

    update_post_meta($post_id, '_tipo_cavallo', 'fattrice');
    update_post_meta($post_id, '_allevatore_id', $user_id);

    wp_send_json_success(['post_id'=>$post_id]);
});

// Elimina fattrice
add_action('wp_ajax_am_delete_fattrice', function(){
    if (!is_user_logged_in()) wp_send_json_error('Non autorizzato');
    if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'am_fattrice_nonce')) {
        wp_send_json_error('Nonce non valido');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) wp_send_json_error('ID mancante');

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'cavallo') wp_send_json_error('Fattrice non trovata');

    $current_user = get_current_user_id();
    $allevatore_id = get_post_meta($post_id, '_allevatore_id', true);
    if ($allevatore_id != $current_user) wp_send_json_error('Non puoi eliminare questa fattrice');

    $deleted = wp_delete_post($post_id, true);
    if (!$deleted) wp_send_json_error('Errore eliminazione fattrice');

    wp_send_json_success();
});

// --- CAMPi PERSONALIZZATI PROFILO ALLEVATORE ---
function am_allevatore_extra_profile_fields($user) {
    if (!in_array('allevatore', $user->roles)) return;
    ?>

    <h2>Dettagli Allevamento</h2>
    <table class="form-table">
        <!-- Immagine in evidenza -->
        <tr>
            <th><label for="immagine_evidenza">Immagine in evidenza</label></th>
            <td>
                <?php $img_id = get_user_meta($user->ID, 'immagine_evidenza', true); ?>
                <input type="hidden" name="immagine_evidenza" id="immagine_evidenza" value="<?php echo esc_attr($img_id); ?>">
                <div id="immagine_evidenza_preview">
                    <?php if ($img_id) echo wp_get_attachment_image($img_id, [150,150]); ?>
                </div>
                <button type="button" class="button" id="immagine_evidenza_upload">Seleziona immagine</button>
            </td>
        </tr>

        <!-- Logo -->
        <tr>
            <th><label for="logo_allevamento">Logo</label></th>
            <td>
                <?php $logo_id = get_user_meta($user->ID, 'logo_allevamento', true); ?>
                <input type="hidden" name="logo_allevamento" id="logo_allevamento" value="<?php echo esc_attr($logo_id); ?>">
                <div id="logo_allevamento_preview">
                    <?php if ($logo_id) echo wp_get_attachment_image($logo_id, [150,150]); ?>
                </div>
                <button type="button" class="button" id="logo_allevamento_upload">Seleziona logo</button>
            </td>
        </tr>

        <!-- Descrizione -->
        <tr>
            <th><label for="descrizione_allevamento">Descrizione</label></th>
            <td>
                <textarea name="descrizione_allevamento" id="descrizione_allevamento" rows="5" cols="30"><?php echo esc_textarea(get_user_meta($user->ID, 'descrizione_allevamento', true)); ?></textarea>
            </td>
        </tr>

        <!-- Link Facebook -->
        <tr>
            <th><label for="facebook_allevamento">Facebook</label></th>
            <td>
                <input type="url" name="facebook_allevamento" id="facebook_allevamento" value="<?php echo esc_attr(get_user_meta($user->ID, 'facebook_allevamento', true)); ?>" class="regular-text">
            </td>
        </tr>

        <!-- Link Instagram -->
        <tr>
            <th><label for="instagram_allevamento">Instagram</label></th>
            <td>
                <input type="url" name="instagram_allevamento" id="instagram_allevamento" value="<?php echo esc_attr(get_user_meta($user->ID, 'instagram_allevamento', true)); ?>" class="regular-text">
            </td>
        </tr>

        <!-- Link sito web -->
        <tr>
            <th><label for="sito_allevamento">Sito web</label></th>
            <td>
                <input type="url" name="sito_allevamento" id="sito_allevamento" value="<?php echo esc_attr(get_user_meta($user->ID, 'sito_allevamento', true)); ?>" class="regular-text">
            </td>
        </tr>
    </table>

    <script>
    jQuery(document).ready(function($){
        // Media uploader per Immagine in evidenza
        var media_uploader;
        $('#immagine_evidenza_upload').click(function(e){
            e.preventDefault();
            if(media_uploader){ media_uploader.open(); return; }
            media_uploader = wp.media.frames.file_frame = wp.media({
                title: 'Seleziona immagine in evidenza',
                button: { text: 'Usa immagine' },
                multiple: false
            });
            media_uploader.on('select', function(){
                var attachment = media_uploader.state().get('selection').first().toJSON();
                $('#immagine_evidenza').val(attachment.id);
                $('#immagine_evidenza_preview').html('<img src="'+attachment.url+'" style="max-width:150px;">');
            });
            media_uploader.open();
        });

        // Media uploader per Logo
        var logo_uploader;
        $('#logo_allevamento_upload').click(function(e){
            e.preventDefault();
            if(logo_uploader){ logo_uploader.open(); return; }
            logo_uploader = wp.media.frames.file_frame = wp.media({
                title: 'Seleziona logo allevamento',
                button: { text: 'Usa logo' },
                multiple: false
            });
            logo_uploader.on('select', function(){
                var attachment = logo_uploader.state().get('selection').first().toJSON();
                $('#logo_allevamento').val(attachment.id);
                $('#logo_allevamento_preview').html('<img src="'+attachment.url+'" style="max-width:150px;">');
            });
            logo_uploader.open();
        });
    });
    </script>

    <?php
}
add_action('show_user_profile', 'am_allevatore_extra_profile_fields');
add_action('edit_user_profile', 'am_allevatore_extra_profile_fields');


function am_allevatore_save_extra_profile_fields($user_id){
    if (!current_user_can('edit_user', $user_id)) return false;

    $fields = [
        'immagine_evidenza',
        'logo_allevamento',
        'descrizione_allevamento',
        'facebook_allevamento',
        'instagram_allevamento',
        'sito_allevamento'
    ];

foreach ($fields as $f) {
    if (! isset($_POST[$f])) {
        continue;
    }

    if ($f === 'immagine_evidenza' || $f === 'logo_allevamento') {
        // Assicuriamoci di salvare l'ID come intero; se vuoto rimuoviamo il meta
        $val = intval($_POST[$f]);
        if ($val > 0) {
            update_user_meta($user_id, $f, $val);
        } else {
            delete_user_meta($user_id, $f);
        }
    } else {
        update_user_meta($user_id, $f, sanitize_text_field($_POST[$f]));
    }
}

}
add_action('personal_options_update', 'am_allevatore_save_extra_profile_fields');
add_action('edit_user_profile_update', 'am_allevatore_save_extra_profile_fields');


