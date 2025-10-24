<?php

if ( ! defined( 'ABSPATH' ) ) exit;

// ===============================
//  CUSTOM POST TYPE: CAVALLO
// ===============================
add_action('init', function() {

    $labels = [
        'name'               => 'Cavalli',
        'singular_name'      => 'Cavallo',
        'add_new'            => 'Aggiungi Cavallo',
        'add_new_item'       => 'Aggiungi nuovo Cavallo',
        'edit_item'          => 'Modifica Cavallo',
        'new_item'           => 'Nuovo Cavallo',
        'view_item'          => 'Vedi Cavallo',
        'search_items'       => 'Cerca Cavalli',
        'not_found'          => 'Nessun Cavallo trovato',
        'menu_name'          => 'Cavalli',
    ];

    $capabilities = [
        'edit_post'          => 'edit_cavallo',
        'read_post'          => 'read_cavallo',
        'delete_post'        => 'delete_cavallo',
        'edit_posts'         => 'edit_cavalli',
        'edit_others_posts'  => 'edit_others_cavalli',
        'publish_posts'      => 'publish_cavalli',
        'read_private_posts' => 'read_private_cavalli',
    ];

    register_post_type('cavallo', [
        'labels'             => $labels,
        'public'             => true,
        'menu_icon'          => 'dashicons-horse',
        'supports'           => ['title'],
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,
        'rewrite'            => ['slug' => 'cavalli/%tipo_cavallo%', 'with_front' => false],
        'capability_type'    => ['cavallo', 'cavalli'],
        'map_meta_cap'       => true,
        'capabilities'       => $capabilities,
    ]);

}, 10); // priorità 10 va bene, il ruolo/admin viene già gestito in role.php


// ===============================
//  PERMALINK DINAMICO IN BASE AL TIPO CAVALLO
// ===============================
add_filter('post_type_link', function($post_link, $post){
    if($post->post_type === 'cavallo'){
        $tipo = get_post_meta($post->ID, '_tipo_cavallo', true);
        $post_link = str_replace('%tipo_cavallo%', $tipo, $post_link);
    }
    return $post_link;
}, 10, 2);

// ===============================
//  META BOX
// ===============================
add_action('add_meta_boxes', function() {
    add_meta_box('cavallo_dettagli', 'Dettagli Cavallo', 'cavallo_dettagli_callback', 'cavallo', 'normal', 'high');
});

// ===============================
// CALLBACK DEL META BOX: CAVALLO
// ===============================
function cavallo_dettagli_callback($post) {
    wp_nonce_field('cavallo_save_meta', 'cavallo_meta_nonce');

    $padre = get_post_meta($post->ID, '_padre', true);
    $madre = get_post_meta($post->ID, '_madre', true);
    $allevatore_id = get_post_meta($post->ID, '_allevatore_id', true);
    $tipo_cavallo = get_post_meta($post->ID, '_tipo_cavallo', true);
    $foto_id = get_post_meta($post->ID, '_foto_id', true);
    $foto_url = $foto_id ? wp_get_attachment_image_url($foto_id, 'medium') : '';
    $nome_anact = get_post_meta($post->ID, '_nome_cavallo_anact', true);

    if (empty($tipo_cavallo)) $tipo_cavallo = 'puledro';

    $allevatori = get_users(['role' => 'allevatore']);
    ?>
    <style>
    .anact-suggestions {border:1px solid #ccc; background:#fff; max-height:180px; overflow-y:auto; display:none; position:absolute; z-index:9999; width:100%;}
    .anact-item {padding:6px 8px; cursor:pointer;}
    .anact-item:hover {background:#f2f2f2;}
    .cavallo-field {margin-bottom:15px;}
    </style>

    <!-- Tipo cavallo -->
    <div class="cavallo-field">
        <label><strong>Tipo Cavallo:</strong></label><br>
        <select id="tipo_cavallo" name="tipo_cavallo" style="width:100%;max-width:480px;">
            <option value="stallone" <?php selected($tipo_cavallo, 'stallone'); ?>>Stallone</option>
            <option value="fattrice" <?php selected($tipo_cavallo, 'fattrice'); ?>>Fattrice</option>
            <option value="puledro" <?php selected($tipo_cavallo, 'puledro'); ?>>Puledro</option>
        </select>
    </div>

    <!-- Nome da ANACT -->
    <div class="cavallo-field" id="campo_nome_stallone_fattrice" style="display:none;">
        <label><strong>Nome cavallo (da ANACT):</strong></label><br>
        <input type="text" id="nome_cavallo_anact" name="nome_cavallo_anact" value="<?php echo esc_attr($nome_anact); ?>" autocomplete="off" style="width:100%;max-width:480px;">
        <div id="nome-suggestions" class="anact-suggestions"></div>
    </div>
	
	<!-- Sesso (solo per Puledri) -->
	<div class="cavallo-field" id="campo_sesso" style="display:none;">
		<label><strong>Sesso:</strong></label><br>
		<?php $sesso = get_post_meta($post->ID, '_sesso', true); ?>
		<select name="sesso" id="sesso" style="width:100%;max-width:480px;">
			<option value="">— Seleziona —</option>
			<option value="maschio" <?php selected($sesso, 'maschio'); ?>>Maschio</option>
			<option value="femmina" <?php selected($sesso, 'femmina'); ?>>Femmina</option>
		</select>
	</div>

    <!-- Foto cavallo -->
    <div class="cavallo-field" id="campo_foto">
        <label><strong>Foto Cavallo:</strong></label><br>
        <img id="cavallo-preview" src="<?php echo esc_url($foto_url); ?>" style="max-width:150px;display:<?php echo $foto_url ? 'block' : 'none'; ?>;margin-bottom:8px;">
        <input type="hidden" name="foto_id" id="foto_id" value="<?php echo esc_attr($foto_id); ?>">
        <button type="button" class="button" id="upload-foto">Carica / Cambia immagine</button>
        <button type="button" class="button" id="rimuovi-foto" style="<?php echo $foto_url ? '' : 'display:none;'; ?>">Rimuovi</button>
    </div>

	<!-- Foto aggiuntiva 1 -->
	<div class="cavallo-field" id="campo_foto_extra1">
		<label><strong>Foto aggiuntiva 1:</strong></label><br>
		<?php 
		$foto2_id = get_post_meta($post->ID, '_foto2_id', true);
		$foto2_url = $foto2_id ? wp_get_attachment_image_url($foto2_id, 'medium') : '';
		?>
		<img id="foto2-preview" src="<?php echo esc_url($foto2_url); ?>" style="max-width:150px;display:<?php echo $foto2_url ? 'block' : 'none'; ?>;margin-bottom:8px;">
		<input type="hidden" name="foto2_id" id="foto2_id" value="<?php echo esc_attr($foto2_id); ?>">
		<button type="button" class="button" id="upload-foto2">Carica / Cambia immagine</button>
		<button type="button" class="button" id="rimuovi-foto2" style="<?php echo $foto2_url ? '' : 'display:none;'; ?>">Rimuovi</button>
	</div>

	<!-- Foto aggiuntiva 2 -->
	<div class="cavallo-field" id="campo_foto_extra2">
		<label><strong>Foto aggiuntiva 2:</strong></label><br>
		<?php 
		$foto3_id = get_post_meta($post->ID, '_foto3_id', true);
		$foto3_url = $foto3_id ? wp_get_attachment_image_url($foto3_id, 'medium') : '';
		?>
		<img id="foto3-preview" src="<?php echo esc_url($foto3_url); ?>" style="max-width:150px;display:<?php echo $foto3_url ? 'block' : 'none'; ?>;margin-bottom:8px;">
		<input type="hidden" name="foto3_id" id="foto3_id" value="<?php echo esc_attr($foto3_id); ?>">
		<button type="button" class="button" id="upload-foto3">Carica / Cambia immagine</button>
		<button type="button" class="button" id="rimuovi-foto3" style="<?php echo $foto3_url ? '' : 'display:none;'; ?>">Rimuovi</button>
	</div>


    <!-- Padre -->
    <div class="cavallo-field" id="campo_padre">
        <label><strong>Padre (da ANACT):</strong></label><br>
        <input type="text" id="padre" name="padre" value="<?php echo esc_attr($padre); ?>" autocomplete="off" style="width:100%;max-width:480px;">
        <div id="padre-suggestions" class="anact-suggestions"></div>
    </div>

    <!-- Madre -->
    <div class="cavallo-field" id="campo_madre">
        <label><strong>Madre (da ANACT):</strong></label><br>
        <input type="text" id="madre" name="madre" value="<?php echo esc_attr($madre); ?>" autocomplete="off" style="width:100%;max-width:480px;">
        <div id="madre-suggestions" class="anact-suggestions"></div>
    </div>

    <!-- Produzione -->
	<div class="cavallo-field" id="campo_produzione" style="display:none;">
		<label><strong>Produzione (Anno di nascita):</strong></label><br>
		<?php 
$produzione = get_post_meta($post->ID, '_produzione', true);
if (empty($produzione)) $produzione = date('Y');
?>
<input type="number" name="produzione" id="produzione" value="<?php echo esc_attr($produzione); ?>" min="1900" max="<?php echo date('Y'); ?>" step="1" style="width:100%;max-width:480px;">

	</div>
	
	<!-- Disponibilità (solo per Puledri) -->
	<div class="cavallo-field" id="campo_disponibilita" style="display:none;">
		<label><strong>Disponibilità:</strong></label><br>
		<?php 
		$disponibilita = get_post_meta($post->ID, '_disponibilita', true);
		if (empty($disponibilita)) $disponibilita = 'disponibile'; // ✅ default
		?>
		<select name="disponibilita" id="disponibilita" style="width:100%;max-width:480px;">
			<option value="disponibile" <?php selected($disponibilita, 'disponibile'); ?>>Disponibile</option>
			<option value="non_disponibile" <?php selected($disponibilita, 'non_disponibile'); ?>>Non disponibile</option>
		</select>
	</div>

    <!-- Allevatore -->
    <div class="cavallo-field">
        <label><strong>Assegna ad Allevatore:</strong></label><br>
        <select name="allevatore_id" style="width:100%;max-width:480px;">
            <option value="">— Seleziona allevatore —</option>
            <?php foreach($allevatori as $a): 
                $codice = get_user_meta($a->ID, 'codice_anact', true);
                ?>
                <option value="<?php echo $a->ID; ?>" <?php selected($a->ID, $allevatore_id); ?>>
                    <?php echo esc_html($a->user_login . ' (' . $codice . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <script>
    (function($){
        const ajaxUrl = "<?php echo esc_js(admin_url('admin-ajax.php')); ?>";

        // Autocomplete ANACT
        function setupAutocomplete(inputId, suggestionId, tipo) {
            const input = document.getElementById(inputId);
            const box = document.getElementById(suggestionId);
            let timer = null;
            input.addEventListener('input', function(){
                const q = input.value.trim();
                if(q.length < 2){ box.style.display = 'none'; return; }
                clearTimeout(timer);
                timer = setTimeout(async ()=>{
                    box.innerHTML = '';
                    try {
                        const res = await fetch(ajaxUrl + '?action=anact_autocomplete_cavalli&tipo=' + tipo + '&term=' + encodeURIComponent(q));
                        const json = await res.json();
                        if(!json.success || !json.data || json.data.length===0){ box.style.display='none'; return; }
                        json.data.forEach(item=>{
                            const div=document.createElement('div');
                            div.className='anact-item';
                            div.textContent=item.nome;
                            div.dataset.nome=item.nome;
                            div.addEventListener('click',function(){
    const nome = this.dataset.nome;
    input.value = nome;
    box.style.display = 'none';

// Aggiorna il titolo solo per Stalloni e Fattrici
const tipo = tipoSel.value; // recupera il tipo selezionato
if(tipo === 'stallone' || tipo === 'fattrice'){
    const titleInput = document.querySelector('#title');
    if (titleInput) {
        titleInput.value = nome;
        titleInput.dispatchEvent(new Event('input')); // forza l’update del titolo
    }

    // Aggiorna anche lo slug (solo se non è ancora salvato)
    const editableSlug = document.querySelector('#editable-post-name-full');
    if (editableSlug && !editableSlug.textContent.trim()) {
        editableSlug.textContent = nome.toLowerCase().replace(/\s+/g, '-');
    }
}

});

                            box.appendChild(div);
                        });
                        box.style.display='block';
                    }catch(e){ box.style.display='none'; }
                },300);
            });
            document.addEventListener('click',e=>{
                if(!box.contains(e.target)&&e.target!==input) box.style.display='none';
            });
        }

        setupAutocomplete('padre','padre-suggestions','stalloni');
        setupAutocomplete('madre','madre-suggestions','fattrici');
        setupAutocomplete('nome_cavallo_anact','nome-suggestions','stalloni');

        // Gestione campi a seconda del tipo
        const tipoSel = document.getElementById('tipo_cavallo');
        const nomeField = document.getElementById('campo_nome_stallone_fattrice');
        const fotoField = document.getElementById('campo_foto');
        const padreField = document.getElementById('campo_padre');
        const madreField = document.getElementById('campo_madre');
		const produzioneField = document.getElementById('campo_produzione');
		const sessoField = document.getElementById('campo_sesso');
		const disponibilitaField = document.getElementById('campo_disponibilita');



        function aggiornaCampi(){
            const val = tipoSel.value;
            if(val==='stallone'){
                nomeField.style.display='block';
                fotoField.style.display='none';
                padreField.style.display='none';
                madreField.style.display='none';
				produzioneField.style.display='none'
				sessoField.style.display='none';
        		disponibilitaField.style.display='none';
            } else if(val==='fattrice'){
                nomeField.style.display='block';
                fotoField.style.display='none';
                padreField.style.display='none';
                madreField.style.display='none';
				produzioneField.style.display='none'
				sessoField.style.display='none';
        		disponibilitaField.style.display='none';
                setupAutocomplete('nome_cavallo_anact','nome-suggestions','fattrici');
            } else {
                nomeField.style.display='none';
                fotoField.style.display='block';
                padreField.style.display='block';
                madreField.style.display='block';
				produzioneField.style.display = (val === 'puledro') ? 'block' : 'none';
				sessoField.style.display='block';
        		disponibilitaField.style.display='block';

            }
        }

        tipoSel.addEventListener('change', aggiornaCampi);
        aggiornaCampi();

        // ===========================
        // Upload immagine tramite WP Media Library
        // ===========================
        let frame;
        $('#upload-foto').on('click', function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: 'Seleziona o carica immagine',
                button: { text: 'Usa questa immagine' },
                multiple: false
            });
            frame.on('select', function(){
                const attachment = frame.state().get('selection').first().toJSON();
                $('#foto_id').val(attachment.id);
                $('#cavallo-preview').attr('src', attachment.url).show();
                $('#rimuovi-foto').show();
            });
            frame.open();
        });

        $('#rimuovi-foto').on('click', function(e){
            e.preventDefault();
            $('#foto_id').val('');
            $('#cavallo-preview').hide();
            $(this).hide();
        });
		
// ===========================
// Foto aggiuntiva 1
// ===========================
let frame2;
$('#upload-foto2').on('click', function(e){
    e.preventDefault();
    if (frame2) { frame2.open(); return; }
    frame2 = wp.media({
        title: 'Seleziona o carica immagine (Foto 2)',
        button: { text: 'Usa questa immagine' },
        multiple: false
    });
    frame2.on('select', function(){
        const attachment = frame2.state().get('selection').first().toJSON();
        $('#foto2_id').val(attachment.id);
        $('#foto2-preview').attr('src', attachment.url).show();
        $('#rimuovi-foto2').show();
    });
    frame2.open();
});

$('#rimuovi-foto2').on('click', function(e){
    e.preventDefault();
    $('#foto2_id').val('');
    $('#foto2-preview').hide();
    $(this).hide();
});

// ===========================
// Foto aggiuntiva 2
// ===========================
let frame3;
$('#upload-foto3').on('click', function(e){
    e.preventDefault();
    if (frame3) { frame3.open(); return; }
    frame3 = wp.media({
        title: 'Seleziona o carica immagine (Foto 3)',
        button: { text: 'Usa questa immagine' },
        multiple: false
    });
    frame3.on('select', function(){
        const attachment = frame3.state().get('selection').first().toJSON();
        $('#foto3_id').val(attachment.id);
        $('#foto3-preview').attr('src', attachment.url).show();
        $('#rimuovi-foto3').show();
    });
    frame3.open();
});

$('#rimuovi-foto3').on('click', function(e){
    e.preventDefault();
    $('#foto3_id').val('');
    $('#foto3-preview').hide();
    $(this).hide();
});

	
	})(jQuery);
		

    </script>
    <?php
}

// ===============================
// Carica media library per il meta box
// ===============================
add_action('admin_enqueue_scripts', function($hook){
    global $post;
    if(isset($post) && $post->post_type === 'cavallo'){
        wp_enqueue_media();
        wp_enqueue_script('jquery');
    }
});


// ===============================
//  SALVATAGGIO META (ottimizzato)
// ===============================
add_action('save_post_cavallo', function($post_id) {
    if (!isset($_POST['cavallo_meta_nonce']) || !wp_verify_nonce($_POST['cavallo_meta_nonce'], 'cavallo_save_meta')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Salvataggio campi base
    $fields = ['padre','madre','tipo_cavallo','allevatore_id','foto_id','foto2_id','foto3_id','nome_cavallo_anact','produzione','sesso','disponibilita'];
    foreach ($fields as $f) {
        if (isset($_POST[$f])) {
            update_post_meta($post_id, "_$f", sanitize_text_field($_POST[$f]));
        }
    }

    // ✅ Imposta l'immagine selezionata come immagine in evidenza
    if (!empty($_POST['foto_id'])) {
        $attachment_id = intval($_POST['foto_id']);
        set_post_thumbnail($post_id, $attachment_id);
    } else {
        // Se viene rimossa la foto, rimuovi anche l'immagine in evidenza
        delete_post_thumbnail($post_id);
    }
});

 

// ===============================
//  AJAX: Aggiorna titolo cavallo (evita blocchi)
// ===============================
add_action('wp_ajax_am_update_titolo_cavallo', function() {
    $post_id = intval($_GET['post_id'] ?? 0);
    $titolo  = sanitize_text_field($_GET['titolo'] ?? '');

    if (!$post_id || empty($titolo)) wp_send_json_error('Dati mancanti');

    $result = wp_update_post([
        'ID'         => $post_id,
        'post_title' => $titolo,
        'post_name'  => sanitize_title($titolo),
    ], true);

    if (is_wp_error($result)) {
        wp_send_json_error('Errore durante l’aggiornamento del titolo');
    } else {
        wp_send_json_success('Titolo aggiornato correttamente');
    }
});

// ===============================
//  AJAX AUTOCOMPLETE -> ANACT
// ===============================
add_action('wp_ajax_anact_autocomplete_cavalli', function() {
    $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
    $tipo = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : 'stalloni';
    if (empty($term)) wp_send_json_error('Termine mancante');

    $term = strtoupper(trim($term));
    $url = 'https://www.anact.it/autocomplete.php?object=' . urlencode($tipo) . '&search=' . urlencode($term);

    // ⏱ Timeout ridotto a 4 secondi (prima era 15!)
    $response = wp_remote_get($url, ['timeout' => 4]);

    // Se il server ANACT non risponde o è lento, ritorna subito errore locale
    if (is_wp_error($response)) wp_send_json_success([]); // Non blocca il caricamento
    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);

    if (empty($json['success']) || empty($json['data'])) wp_send_json_success([]);
    wp_send_json_success($json['data']);
});
add_action('wp_ajax_nopriv_anact_autocomplete_cavalli', function() {
    do_action('wp_ajax_anact_autocomplete_cavalli');
});
