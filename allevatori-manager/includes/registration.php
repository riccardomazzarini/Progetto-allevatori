<?php
if (!defined('ABSPATH')) exit;

/* ---------- SHORTCODE REGISTRAZIONE ---------- */
function am_register_form() {

    // Recupera i campi definiti in backend (tranne Allevamento, che sarà sempre fisso)
    $fields = get_option('am_form_fields', [
        ['label' => 'Nome', 'name' => 'first_name', 'type' => 'text', 'required' => 1],
        ['label' => 'Cognome', 'name' => 'last_name', 'type' => 'text', 'required' => 1],
        ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'required' => 1],
        ['label' => 'Cellulare', 'name' => 'cellulare', 'type' => 'tel', 'required' => 1],
        ['label' => 'Telefono', 'name' => 'telefono', 'type' => 'tel', 'required' => 0],
        ['label' => 'Password', 'name' => 'password', 'type' => 'password', 'required' => 1],
    ]);

    // Recupera gli stili dal backend
    $button_color        = get_option('am_button_color', '#0055a5');
    $button_hover_color  = get_option('am_button_hover_color', '#0056d6');
    $button_text_color   = get_option('am_button_text_color', '#ffffff');
    $button_padding      = get_option('am_button_padding', '10px 20px');
    $button_border_radius= get_option('am_button_border_radius', '6px');
    $form_bg             = get_option('am_form_bg', '#f9f9f9');
    $input_padding       = get_option('am_input_padding', '10px');
    $input_margin        = get_option('am_input_margin', '15px 0');
    $input_border_color  = get_option('am_input_border_color', '#cccccc');
    $input_border_radius = get_option('am_input_border_radius', '6px');
    $input_font_size     = get_option('am_input_font_size', '14px');
    $label_font_size     = get_option('am_label_font_size', '13px');
	$button_light_color        = get_option('am_button_light_color', '#e0e0e0');
    $button_light_hover_color  = get_option('am_button_light_hover_color', '#d0d0d0');
    $button_light_text_color   = get_option('am_button_light_text_color', '#333333');
    $button_light_padding      = get_option('am_button_light_padding', '6px 14px');
    $button_light_border_radius= get_option('am_button_light_border_radius', '4px');


    // Gestione registrazione (stesso comportamento tuo)
    if ($_POST && isset($_POST['am_register'])) {

        $allevamento_codice = sanitize_text_field($_POST['allevamento_codice'] ?? '');
        $allevamento_nome   = sanitize_text_field($_POST['allevamento'] ?? '');

        // Validazione: l'utente deve selezionare/compilare correttamente l'allevamento
        if (!$allevamento_codice || !$allevamento_nome) {
            echo '<p class="am-error">Seleziona un allevamento valido (tramite ricerca o codice) prima di proseguire.</p>';
        } else {
            // Nome utente = nome allevamento (se preferisci usare email come user_login cambialo qui)
            $user_login = sanitize_user($allevamento_nome);

            $userdata = [
                'user_login' => $user_login,
                'user_pass'  => $_POST['password'],
                'user_email' => sanitize_email($_POST['email']),
                'role'       => 'allevatore',
            ];

            if (isset($_POST['first_name'])) $userdata['first_name'] = sanitize_text_field($_POST['first_name']);
            if (isset($_POST['last_name']))  $userdata['last_name']  = sanitize_text_field($_POST['last_name']);

            $user_id = wp_insert_user($userdata);

            if (!is_wp_error($user_id)) {
                // Salvataggio campi personalizzati
                foreach ($fields as $field) {
                    $name = $field['name'];
                    if (!in_array($name, ['first_name','last_name','email','password'])) {
                        if (isset($_POST[$name])) {
                            update_user_meta($user_id, $name, sanitize_text_field($_POST[$name]));
                        }
                    }
                }

                // Salva codice e nome allevamento in campi personalizzati
                update_user_meta($user_id, 'codice_anact', $allevamento_codice);
                update_user_meta($user_id, 'allevamento_nome', $allevamento_nome);
				
				// Salva anche i dati dell'indirizzo Google
				update_user_meta($user_id, 'indirizzo', sanitize_text_field($_POST['indirizzo'] ?? ''));
				update_user_meta($user_id, 'latitudine', sanitize_text_field($_POST['latitudine'] ?? ''));
				update_user_meta($user_id, 'longitudine', sanitize_text_field($_POST['longitudine'] ?? ''));
				update_user_meta($user_id, 'citta', sanitize_text_field($_POST['citta'] ?? ''));
				update_user_meta($user_id, 'cap', sanitize_text_field($_POST['cap'] ?? ''));
				update_user_meta($user_id, 'provincia', sanitize_text_field($_POST['provincia'] ?? ''));

                // --- INVIO EMAIL ---
                $user_email = sanitize_email($_POST['email']);
                $site_name  = get_bloginfo('name');

                // Email all'utente
                $subject_user = "Benvenuto su $site_name!";
                $message_user = "Grazie per esserti registrato su $site_name.\nInizia subito a far conoscere i tuoi cavalli!";
                wp_mail($user_email, $subject_user, $message_user);

                // Email all'amministratore
                $admin_email = get_option('admin_email');
                $subject_admin = "Nuova registrazione su $site_name";
                $message_admin = "Un altro allevatore si è iscritto al tuo sito!\n\nEcco i dettagli forniti:\n\n";
                foreach ($fields as $field) {
                    $fname = $field['name'];
                    if (isset($_POST[$fname])) {
                        $message_admin .= $field['label'] . ": " . sanitize_text_field($_POST[$fname]) . "\n";
                    }
                }
                $message_admin .= "Allevamento: $allevamento_nome\nCodice ANACT: $allevamento_codice\n";
                wp_mail($admin_email, $subject_admin, $message_admin);

                // --- LOGIN AUTOMATICO + REDIRECT ALLA DASHBOARD ---
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id);
                wp_redirect(site_url('/dashboard-allevatori'));
                exit;
            } else {
                echo '<p class="am-error">Errore: ' . $user_id->get_error_message() . '</p>';
            }
        }
    }

    // Output form
    ob_start(); ?>
    <style>
        .am-form { background: <?php echo esc_attr($form_bg); ?>; padding: 20px; max-width: 720px; margin: 20px auto; border-radius: 8px; position: relative; box-sizing:border-box; }
        .am-row { display:flex; gap:10px; align-items:flex-start; margin-bottom:10px; }
        .am-row label { flex:1; display:block; }
        .am-col-1-4 { flex: 0 0 25%; max-width:25%; }
        .am-col-3-4 { flex: 1; }
        .am-input, .am-select { width:100%; padding: <?php echo esc_attr($input_padding); ?>; font-size: <?php echo esc_attr($input_font_size); ?>; border:1px solid <?php echo esc_attr($input_border_color); ?>; border-radius: <?php echo esc_attr($input_border_radius); ?>; box-sizing:border-box; }
        .am-suggestions { border:1px solid #ccc; max-height:220px; overflow-y:auto; display:none; background:#fff; position:absolute; z-index:9999; width: calc(100% - 2px); box-shadow:0 4px 10px rgba(0,0,0,0.05); }
        .am-sugg-item { padding:8px 10px; cursor:pointer; }
        .am-sugg-item:hover { background:#f6f6f6; }
        .am-button { padding:6px 10px; margin-left:8px; cursor:pointer; background:<?php echo esc_attr($button_color); ?>; color:<?php echo esc_attr($button_text_color); ?>; border:none; border-radius:4px; }
        .am-form button[type="submit"] { background: <?php echo esc_attr($button_color); ?>; color: <?php echo esc_attr($button_text_color); ?>; padding: <?php echo esc_attr($button_padding); ?>; border:none; border-radius: <?php echo esc_attr($button_border_radius); ?>; cursor:pointer; }
        .am-error { color: red; }
		.am-button-light {
    background: <?php echo esc_attr($button_light_color); ?>;
    color: <?php echo esc_attr($button_light_text_color); ?>;
    padding: <?php echo esc_attr($button_light_padding); ?>;
    border: 1px solid #ccc;
    border-radius: <?php echo esc_attr($button_light_border_radius); ?>;
    cursor: pointer;
    font-size: 13px;
}
.am-button-light:hover {
    background: <?php echo esc_attr($button_light_hover_color); ?>;
}
    </style>

    <form method="post" class="am-form" autocomplete="off">
    <!-- Riga iniziale con 3/12 - 7/12 - 2/12 -->
    <div class="am-row" style="display:flex; gap:10px; align-items:flex-end; margin-bottom:4px;">
        
        <!-- Colonna 3/12 -->
        <div style="flex:0 0 25%;">
            <label for="allevamento_mode">Modalità:</label>
            <select id="allevamento_mode" class="am-select" style="width:100%;">
                <option value="nome">Cerca allevamento</option>
                <option value="codice">Inserisci codice ANACT</option>
            </select>
        </div>

        <!-- Colonna 7/12 -->
        <div style="flex:0 0 58%;">
            <div id="allevamento_nome_wrapper">
                <label for="allevamento_input">Allevamento:</label>
                <input type="text" id="allevamento_input" class="am-input" placeholder="Cerca allevamento..." style="width:100%;">
                <div id="allevamento_suggestions" class="am-suggestions" role="listbox" aria-label="Suggerimenti allevamenti"></div>
            </div>

            <div id="allevamento_codice_wrapper" style="display:none;">
                <label for="allevamento_codice_input">Codice ANACT:</label>
                <input type="text" id="allevamento_codice_input" class="am-input" placeholder="Es. 10318" style="width:100%;">
            </div>
        </div>

        <!-- Colonna 2/12 -->
        <div style="flex:0 0 16%;">
            <button type="button" id="allevamento_ok_nome" class="am-button-light">OK</button>
            <button type="button" id="allevamento_ok_codice" class="am-button-light">OK</button>
        </div>
    </div>

    <!-- Riga bloccata sotto (mostra il risultato e contiene gli hidden reali) -->
    <div style="margin-bottom:12px;">
        <label>
            Selezionato:
            <input type="text" id="allevamento_output" disabled 
                   style="width:100%; padding:8px; background:#f0f0f0; border-radius:4px; border:1px solid #ddd;">
            <input type="hidden" name="allevamento" id="allevamento_hidden_nome">
            <input type="hidden" name="allevamento_codice" id="allevamento_hidden_codice">
        </label>
    </div>
		
<!-- Campo indirizzo con autocompletamento -->
<div style="margin-bottom:15px; position:relative;">
  <label for="am_indirizzo">Indirizzo:</label>
  <input type="text" id="am_indirizzo" name="indirizzo" class="am-input" 
         placeholder="Es. Via Roma 123, Milano" required>
  <input type="hidden" id="am_lat" name="latitudine">
  <input type="hidden" id="am_lng" name="longitudine">
  <input type="hidden" id="am_citta" name="citta">
  <input type="hidden" id="am_cap" name="cap">
  <input type="hidden" id="am_provincia" name="provincia">
</div>
		
<!-- altri campi dinamici -->
<?php foreach ($fields as $field): ?>
    <?php $margin = !empty($field['margin']) ? $field['margin'] : '15px'; ?>

    <?php if ($field['type'] === 'separator'): ?>
        <div style="margin-bottom:<?php echo esc_attr($margin); ?>;">
            <strong><?php echo esc_html($field['label']); ?></strong>
        </div>
    <?php else: ?>
        <div style="margin-bottom:<?php echo esc_attr($margin); ?>;">
            <input class="am-input" 
                   type="<?php echo esc_attr($field['type']); ?>"
                   name="<?php echo esc_attr($field['name']); ?>"
                   placeholder="<?php echo esc_attr($field['label']); ?>"
                   <?php echo !empty($field['required']) ? 'required' : ''; ?>>
        </div>
    <?php endif; ?>
<?php endforeach; ?>



    <button type="submit" name="am_register">Registrati</button>
</form>


    <script>
(function(){
    const ajaxUrl = "<?php echo esc_js(admin_url('admin-ajax.php')); ?>";

    const modeSelect = document.getElementById('allevamento_mode');
    const nomeWrapper = document.getElementById('allevamento_nome_wrapper');
    const codiceWrapper = document.getElementById('allevamento_codice_wrapper');

    const inputNome = document.getElementById('allevamento_input');
    const inputCodice = document.getElementById('allevamento_codice_input');

    const hiddenNome = document.getElementById('allevamento_hidden_nome');
    const hiddenCodice = document.getElementById('allevamento_hidden_codice');
    const output = document.getElementById('allevamento_output');

    const sugg = document.getElementById('allevamento_suggestions');
    const okNome = document.getElementById('allevamento_ok_nome');
    const okCodice = document.getElementById('allevamento_ok_codice');

    let selectedItem = null; // {nome, codice}

    // Funzione di reset campi
    function resetFields(){
        hiddenNome.value = "";
        hiddenCodice.value = "";
        output.value = "";
        selectedItem = null;
    }

    // Switch modalità
    function switchMode(){
        if(modeSelect.value === "nome"){
            nomeWrapper.style.display = "block";
            codiceWrapper.style.display = "none";
            okNome.style.display = "inline-block";
            okCodice.style.display = "none";
            inputCodice.value = "";
            resetFields();
        } else {
            nomeWrapper.style.display = "none";
            codiceWrapper.style.display = "block";
            okNome.style.display = "none";
            okCodice.style.display = "inline-block";
            inputNome.value = "";
            sugg.style.display = "none";
            resetFields();
        }
    }

    // Inizializza modalità corretta
    switchMode();
    modeSelect.addEventListener('change', switchMode);

    // Autocomplete: ricerca per NOME (via admin-ajax)
    let timer = null;
    inputNome.addEventListener('input', function(){
        const q = inputNome.value.trim();
        selectedItem = null;
        if(q.length < 2){
            sugg.style.display = 'none';
            return;
        }
        clearTimeout(timer);
        timer = setTimeout(async ()=>{
            try {
                const res = await fetch(ajaxUrl + '?action=anact_autocomplete_allevatori&term=' + encodeURIComponent(q));
                const json = await res.json();
                sugg.innerHTML = '';
                if(!json.success || !json.data || json.data.length === 0){
                    sugg.style.display = 'none';
                    return;
                }
                json.data.forEach(item=>{
                    const div = document.createElement('div');
                    div.className = 'am-sugg-item';
                    div.textContent = item.nome + ' (' + item.codice + ')';
                    div.dataset.nome = item.nome;
                    div.dataset.codice = item.codice;
                    div.addEventListener('click', function(e){
                        selectedItem = { nome: this.dataset.nome, codice: this.dataset.codice };
                        inputNome.value = selectedItem.nome;
                        sugg.style.display = 'none';
                    });
                    sugg.appendChild(div);
                });
                sugg.style.display = 'block';
            } catch(err){
                console.error('Errore autocomplete:', err);
                sugg.style.display = 'none';
            }
        }, 200);
    });

    // OK per nome
    okNome.addEventListener('click', async function(){
        if(selectedItem){
            hiddenNome.value = selectedItem.nome;
            hiddenCodice.value = selectedItem.codice;
            output.value = selectedItem.nome + ' (Codice: ' + selectedItem.codice + ')';
            return;
        }
        const q = inputNome.value.trim();
        if(!q){
            alert('Inserisci un nome allevamento o selezionalo dai suggerimenti.');
            return;
        }
        try {
            const res = await fetch(ajaxUrl + '?action=anact_autocomplete_allevatori&term=' + encodeURIComponent(q));
            const json = await res.json();
            if(json.success && json.data && json.data.length>0){
                const first = json.data[0];
                hiddenNome.value = first.nome;
                hiddenCodice.value = first.codice;
                output.value = first.nome + ' (Codice: ' + first.codice + ')';
                selectedItem = { nome: first.nome, codice: first.codice };
            } else {
                alert('Nessun allevamento trovato per il termine inserito.');
            }
        } catch(err){
            console.error('Errore conferma nome:', err);
            alert('Errore durante la ricerca. Riprova più tardi.');
        }
    });

    // OK per codice
    okCodice.addEventListener('click', async function(){
        const codice = inputCodice.value.trim();
        if(!codice){
            alert('Inserisci il codice ANACT da cercare.');
            return;
        }
        try {
            const res = await fetch(ajaxUrl + '?action=anact_autocomplete_allevatori&term=' + encodeURIComponent(codice));
            const json = await res.json();
            if(json.success && json.data && json.data.length>0){
                const first = json.data[0];
                hiddenNome.value = first.nome;
                hiddenCodice.value = first.codice;
                output.value = first.nome + ' (Codice: ' + first.codice + ')';
                selectedItem = { nome: first.nome, codice: first.codice };
            } else {
                alert('Nessun allevamento trovato per questo codice.');
                resetFields();
            }
        } catch(err){
            console.error('Errore conferma codice:', err);
            alert('Errore durante la ricerca del codice.');
        }
    });

    // Chiudi suggerimenti se clicchi fuori
    document.addEventListener('click', function(e){
        if (sugg && !sugg.contains(e.target) && e.target !== inputNome) {
            sugg.style.display = 'none';
        }
    });
})();
		
// --- AUTOCOMPLETE GOOGLE PLACES ---
function initAutocomplete() {
  const input = document.getElementById("am_indirizzo");
  if (!input) return;

  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ["geocode"],
    componentRestrictions: { country: "it" }, // solo Italia
    fields: ["address_components", "geometry", "formatted_address"]
  });

  autocomplete.addListener("place_changed", function () {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;

    // Imposta indirizzo completo
    input.value = place.formatted_address;

    // Coordinate
    document.getElementById("am_lat").value = place.geometry.location.lat();
    document.getElementById("am_lng").value = place.geometry.location.lng();

    // Estrai dettagli (città, provincia, cap)
    let citta = "", provincia = "", cap = "";
    place.address_components.forEach(comp => {
      if (comp.types.includes("locality")) citta = comp.long_name;
      if (comp.types.includes("administrative_area_level_2")) provincia = comp.short_name;
      if (comp.types.includes("postal_code")) cap = comp.long_name;
    });

    document.getElementById("am_citta").value = citta;
    document.getElementById("am_cap").value = cap;
    document.getElementById("am_provincia").value = provincia;
  });
}

// Carica script Google Maps solo quando serve
document.addEventListener("DOMContentLoaded", function(){
  const script = document.createElement("script");
  script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BP-RdHovew1OGpv9ay6SU1EliBiLB-8&libraries=places&callback=initAutocomplete&loading=async";
  script.async = true;
  document.head.appendChild(script);
});	
</script>

    <?php

    return ob_get_clean();
}
add_shortcode('allevatore_register', 'am_register_form');

/* ---------- AJAX SERVER-SIDE ---------- */
function anact_autocomplete_allevatori() {
    $term = isset($_GET['term']) ? sanitize_text_field($_GET['term']) : '';
    if(empty($term)) wp_send_json_error('Nessun termine');

    $term = strtoupper(trim($term));
    $url = 'https://www.anact.it/autocomplete.php?object=allevatori&search=' . urlencode($term);

    $args = ['timeout'=>15,'headers'=>['Accept'=>'application/json','User-Agent'=>'WordPress/'.get_bloginfo('version')]];

    $response = wp_remote_get($url, $args);
    if(is_wp_error($response)) wp_send_json_error('Errore chiamata ANACT');

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body,true);
    if(json_last_error()!==JSON_ERROR_NONE) wp_send_json_error('JSON non valido');

    if(empty($json['success']) || empty($json['data'])) wp_send_json_error('Nessun risultato');

    // restituisce solo nome e codice
    $result = array_map(function($item){
        return ['nome'=>$item['nome'],'codice'=>$item['codice']];
    }, $json['data']);

    wp_send_json_success($result);
}
add_action('wp_ajax_anact_autocomplete_allevatori','anact_autocomplete_allevatori');
add_action('wp_ajax_nopriv_anact_autocomplete_allevatori','anact_autocomplete_allevatori');
