<?php
$current_user = wp_get_current_user();
$message = '';

// ------------------------
// Gestione aggiornamento dati
// ------------------------
if ($_POST) {

    // Aggiorna contatto (email, indirizzo)
    if (isset($_POST['update_contact']) && isset($_POST['am_nonce_contact']) && wp_verify_nonce($_POST['am_nonce_contact'], 'am_update_contact')) {
        if (!empty($_POST['email'])) {
            wp_update_user(['ID' => $current_user->ID, 'user_email' => sanitize_email($_POST['email'])]);
        }
        update_user_meta($current_user->ID, 'indirizzo', sanitize_text_field($_POST['indirizzo']));
        update_user_meta($current_user->ID, 'latitudine', sanitize_text_field($_POST['latitudine']));
        update_user_meta($current_user->ID, 'longitudine', sanitize_text_field($_POST['longitudine']));
        update_user_meta($current_user->ID, 'citta', sanitize_text_field($_POST['citta']));
        update_user_meta($current_user->ID, 'cap', sanitize_text_field($_POST['cap']));
        update_user_meta($current_user->ID, 'provincia', sanitize_text_field($_POST['provincia']));
        $message = '<div class="updated"><p>Dati contatto aggiornati!</p></div>';
    }

    // Aggiorna referente
    if (isset($_POST['update_referente']) && isset($_POST['am_nonce_referente']) && wp_verify_nonce($_POST['am_nonce_referente'], 'am_update_referente')) {
        $fields = ['first_name','last_name','cellulare','telefono'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) update_user_meta($current_user->ID, $f, sanitize_text_field($_POST[$f]));
        }
        $message = '<div class="updated"><p>Dati referente aggiornati!</p></div>';
    }

    // Reimposta password
    if (isset($_POST['send_password_reset']) && isset($_POST['am_nonce_pass']) && wp_verify_nonce($_POST['am_nonce_pass'], 'am_send_password_reset')) {
        wp_send_new_user_notifications($current_user->ID, 'user');
        $message = '<div class="updated"><p>Email per reimpostazione password inviata!</p></div>';
    }

// Aggiorna dettagli allevatore (immagini, descrizione, social)
if (isset($_POST['update_dettagli']) && isset($_POST['am_nonce_dettagli']) && wp_verify_nonce($_POST['am_nonce_dettagli'], 'am_update_dettagli')) {

    $fields_new = [
        'immagine_evidenza',
        'logo_allevamento',
        'descrizione_allevamento',
        'facebook_allevamento',
        'instagram_allevamento',
        'sito_allevamento'
    ];

    foreach ($fields_new as $f) {
        if (! isset($_POST[$f])) {
            continue;
        }

        switch ($f) {
            case 'immagine_evidenza':
            case 'logo_allevamento':
                // Salva sempre l'ID dell'allegato come intero; rimuovi meta se vuoto
                $val = intval($_POST[$f]);
                if ($val > 0) {
                    update_user_meta($current_user->ID, $f, $val);
                } else {
                    delete_user_meta($current_user->ID, $f);
                }
                break;

            case 'descrizione_allevamento':
                update_user_meta($current_user->ID, $f, wp_kses_post($_POST[$f]));
                break;

            case 'facebook_allevamento':
            case 'instagram_allevamento':
            case 'sito_allevamento':
                update_user_meta($current_user->ID, $f, esc_url_raw($_POST[$f]));
                break;

            default:
                update_user_meta($current_user->ID, $f, sanitize_text_field($_POST[$f]));
        }
    }

    $message = '<div class="updated"><p>Dati allevatore aggiornati!</p></div>';
}

}

// ------------------------
// Recupero valori
// ------------------------
$allevamento_nome = get_user_meta($current_user->ID, 'allevamento_nome', true);
$codice_anact = get_user_meta($current_user->ID, 'codice_anact', true);

$immagine_evidenza = get_user_meta($current_user->ID,'immagine_evidenza',true);
$logo_allevamento = get_user_meta($current_user->ID,'logo_allevamento',true);
$descrizione_allevamento = get_user_meta($current_user->ID,'descrizione_allevamento',true);
$facebook_allevamento = get_user_meta($current_user->ID,'facebook_allevamento',true);
$instagram_allevamento = get_user_meta($current_user->ID,'instagram_allevamento',true);
$sito_allevamento = get_user_meta($current_user->ID,'sito_allevamento',true);
?>

<h2><?php echo esc_html($allevamento_nome . ' - Codice: ' . $codice_anact); ?></h2>
<?php echo $message; ?>

<!-- FORM CONTATTO -->
<h3>Contatto</h3>
<form method="post" class="am-form">
    <?php wp_nonce_field('am_update_contact','am_nonce_contact'); ?>
    <label>
        <span>Email</span>
        <input type="email" name="email" value="<?php echo esc_attr($current_user->user_email); ?>" required>
    </label>
    <label>
        <span>Indirizzo</span>
        <input type="text" id="am_indirizzo" name="indirizzo" value="<?php echo esc_attr(get_user_meta($current_user->ID,'indirizzo',true)); ?>" placeholder="Via Roma 123, Milano" required>
        <input type="hidden" id="am_lat" name="latitudine" value="<?php echo esc_attr(get_user_meta($current_user->ID,'latitudine',true)); ?>">
        <input type="hidden" id="am_lng" name="longitudine" value="<?php echo esc_attr(get_user_meta($current_user->ID,'longitudine',true)); ?>">
        <input type="hidden" id="am_citta" name="citta" value="<?php echo esc_attr(get_user_meta($current_user->ID,'citta',true)); ?>">
        <input type="hidden" id="am_cap" name="cap" value="<?php echo esc_attr(get_user_meta($current_user->ID,'cap',true)); ?>">
        <input type="hidden" id="am_provincia" name="provincia" value="<?php echo esc_attr(get_user_meta($current_user->ID,'provincia',true)); ?>">
    </label>
    <button type="submit" name="update_contact">Aggiorna Dati</button>
</form>

<!-- FORM REFERENTE -->
<h3>Dati Referente</h3>
<form method="post" class="am-form">
    <?php wp_nonce_field('am_update_referente','am_nonce_referente'); ?>
    <label>
        <span>Nome</span>
        <input type="text" name="first_name" value="<?php echo esc_attr(get_user_meta($current_user->ID,'first_name',true)); ?>" required>
    </label>
    <label>
        <span>Cognome</span>
        <input type="text" name="last_name" value="<?php echo esc_attr(get_user_meta($current_user->ID,'last_name',true)); ?>" required>
    </label>
    <label>
        <span>Cellulare</span>
        <input type="tel" name="cellulare" value="<?php echo esc_attr(get_user_meta($current_user->ID,'cellulare',true)); ?>" required>
    </label>
    <label>
        <span>Telefono</span>
        <input type="tel" name="telefono" value="<?php echo esc_attr(get_user_meta($current_user->ID,'telefono',true)); ?>">
    </label>
    <button type="submit" name="update_referente">Aggiorna Referente</button>
</form>

<!-- FORM PASSWORD -->
<h3>Reimposta Password</h3>
<form method="post" class="am-form">
    <?php wp_nonce_field('am_send_password_reset','am_nonce_pass'); ?>
    <button type="submit" name="send_password_reset">Invia email reimpostazione password</button>
</form>

<!-- FORM DETTAGLI ALLEVATORE -->
<h3>Dettagli Allevatore</h3>
<form method="post" class="am-form">
    <?php wp_nonce_field('am_update_dettagli','am_nonce_dettagli'); ?>

    <?php
    // Recupero ID immagini e generazione URL per preview
    $immagine_evidenza_url = $immagine_evidenza ? wp_get_attachment_url($immagine_evidenza) : '';
    $logo_allevamento_url = $logo_allevamento ? wp_get_attachment_url($logo_allevamento) : '';
    ?>

    <!-- IMMAGINE IN EVIDENZA -->
    <label>
        <span>Immagine in evidenza</span><br>
        <img id="immagine_evidenza_preview" src="<?php echo esc_url($immagine_evidenza_url); ?>" style="max-width:150px; display:block; margin-bottom:10px;">
        <input type="hidden" id="immagine_evidenza" name="immagine_evidenza" value="<?php echo esc_attr($immagine_evidenza); ?>">
        <button type="button" class="button am-upload-btn" data-type="featured">Seleziona immagine</button>
    </label>

    <!-- LOGO -->
    <label>
        <span>Logo</span><br>
        <img id="logo_allevamento_preview" src="<?php echo esc_url($logo_allevamento_url); ?>" style="max-width:150px; display:block; margin-bottom:10px;">
        <input type="hidden" id="logo_allevamento" name="logo_allevamento" value="<?php echo esc_attr($logo_allevamento); ?>">
        <button type="button" class="button am-upload-btn" data-type="logo">Seleziona logo</button>
    </label>

    <!-- DESCRIZIONE -->
    <label>
        <span>Descrizione</span>
        <textarea name="descrizione_allevamento" rows="5"><?php echo esc_textarea($descrizione_allevamento); ?></textarea>
    </label>

    <!-- SOCIAL & SITO -->
    <label>
        <span>Link Facebook</span>
        <input type="url" name="facebook_allevamento" value="<?php echo esc_attr($facebook_allevamento); ?>">
    </label>
    <label>
        <span>Link Instagram</span>
        <input type="url" name="instagram_allevamento" value="<?php echo esc_attr($instagram_allevamento); ?>">
    </label>
    <label>
        <span>Sito web</span>
        <input type="url" name="sito_allevamento" value="<?php echo esc_attr($sito_allevamento); ?>">
    </label>

    <button type="submit" name="update_dettagli">Aggiorna Dettagli</button>
</form>




<!-- AUTOCOMPLETE GOOGLE PLACES -->
<script>
function initAutocomplete() {
  const input = document.getElementById("am_indirizzo");
  if (!input) return;

  const autocomplete = new google.maps.places.Autocomplete(input, {
    types: ["geocode"],
    componentRestrictions: { country: "it" },
    fields: ["address_components", "geometry", "formatted_address"]
  });

  autocomplete.addListener("place_changed", function () {
    const place = autocomplete.getPlace();
    if (!place.geometry) return;

    input.value = place.formatted_address;
    document.getElementById("am_lat").value = place.geometry.location.lat();
    document.getElementById("am_lng").value = place.geometry.location.lng();

    let citta="",provincia="",cap="";
    place.address_components.forEach(comp=>{
      if(comp.types.includes("locality")) citta=comp.long_name;
      if(comp.types.includes("administrative_area_level_2")) provincia=comp.short_name;
      if(comp.types.includes("postal_code")) cap=comp.long_name;
    });
    document.getElementById("am_citta").value=citta;
    document.getElementById("am_cap").value=cap;
    document.getElementById("am_provincia").value=provincia;
  });
}

document.addEventListener("DOMContentLoaded", function(){
  const script = document.createElement("script");
  script.src = "https://maps.googleapis.com/maps/api/js?key=AIzaSyB7BP-RdHovew1OGpv9ay6SU1EliBiLB-8&libraries=places&callback=initAutocomplete";
  script.async = true;
  document.head.appendChild(script);
});
</script>