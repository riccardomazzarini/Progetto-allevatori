<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$post_id = intval($_GET['id'] ?? 0);
$modalita = $post_id ? 'modifica' : 'crea';

if ($modalita === 'modifica') {
    $post = get_post($post_id);
    if (!$post || get_post_meta($post_id, '_allevatore_id', true) != $current_user->ID) {
        echo '<p>Puledro non accessibile.</p>';
        return;
    }
}

if ($modalita === 'modifica') {
    $titolo = esc_attr($post->post_title);
    $anno = get_post_meta($post_id, '_produzione', true) ?: date('Y');
    $padre = get_post_meta($post_id, '_padre', true);
    $madre = get_post_meta($post_id, '_madre', true);
    $sesso = get_post_meta($post_id, '_sesso', true);
    $disponibilita = get_post_meta($post_id, '_disponibilita', true) ?: 'disponibile';
} else {
    // Default per nuovo puledro
    $titolo = '';
    $anno = date('Y');
    $padre = '';
    $madre = '';
    $sesso = '';
    $disponibilita = 'disponibile';
    $foto_ids = [];
}


$anno        = get_post_meta($post_id, '_produzione', true) ?: date('Y');
$padre       = get_post_meta($post_id, '_padre', true);
$madre       = get_post_meta($post_id, '_madre', true);
$sesso       = get_post_meta($post_id, '_sesso', true);
$disponibilita = get_post_meta($post_id, '_disponibilita', true);
if (empty($disponibilita)) $disponibilita = 'disponibile';
$foto_ids    = get_post_meta($post_id, '_foto_ids', true) ?: [];

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('am_puledro_nonce');
wp_enqueue_media();
?>

<link rel="stylesheet" href="<?php echo plugin_dir_url(dirname(__FILE__,2)); ?>assets/css/template-allevatore.css">

<style>
/* Migliorie UX immagini */
.am-thumb-wrap {
  position: relative;
  cursor: grab;
  transition: transform 0.2s;
  display: inline-block;
  width: 120px;
  height: 90px;
  overflow: hidden;
  border-radius: 6px;
  background:#f6f6f6;
  vertical-align: top;
}
.am-thumb-wrap.dragging { opacity: 0.6; transform: scale(1.05); }
.am-thumb {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display:block;
}
.am-remove-photo {
  position:absolute;
  top:4px;
  right:6px;
  cursor: pointer;
  background: rgba(0,0,0,0.6);
  color:#fff;
  padding:2px 6px;
  border-radius: 12px;
  font-size:12px;
  line-height:1;
  transition: 0.15s;
}
.am-remove-photo:hover { transform: scale(1.05); }
.am-notify {
  position: fixed;
  top: 1rem; right: 1rem;
  background: #c00; color: #fff;
  padding: 0.8rem 1rem; border-radius: 8px;
  display: none; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
.am-form-actions { margin-top: 1rem; }
.am-btn { padding: 8px 12px; border-radius:6px; background:#0073aa; color:#fff; border:none; cursor:pointer; }
.am-btn:hover { opacity:0.95; }
</style>

<div id="am-notify" class="am-notify" role="status" aria-live="polite"></div>

<div class="am-template-allevatore">

    <div class="am-breadcrumbs">
        <a href="/dashboard-allevatori/">Dashboard Allevatore</a> › 
        <a href="/dashboard-allevatori/?sezione=puledri">Puledri</a> › Modifica Puledro
    </div>

    <div class="am-header">
        <h1 class="am-title">
    <?php echo $modalita === 'modifica' ? 'Modifica Puledro' : 'Aggiungi nuovo Puledro'; ?>
</h1>
<p class="am-desc">
    <?php echo $modalita === 'modifica' ? 'Aggiorna le informazioni e le immagini del tuo puledro.' : 'Compila i campi per creare un nuovo puledro.'; ?>
</p>

    </div>

    <form id="am-puledro-form" class="am-section">
        <div class="am-form-group">
            <label>Nome Puledro</label>
            <input type="text" name="nome_puledro" id="nome_puledro" value="<?php echo $titolo; ?>" required>
        </div>

        <div class="am-form-group">
            <label>Anno di nascita</label>
            <input type="number" name="anno_nascita" id="anno_nascita" value="<?php echo esc_attr($anno); ?>" required>
        </div>


		<div class="am-form-group">
			<label>Sesso</label>
			<select name="sesso" id="sesso" required>
				<option value="" <?php selected($sesso, ''); ?>>-</option>
				<option value="maschio" <?php selected($sesso, 'maschio'); ?>>Maschio</option>
				<option value="femmina" <?php selected($sesso, 'femmina'); ?>>Femmina</option>
			</select>
        </div>

		<div class="am-form-group">
			<label>Padre (da ANACT)</label>
			<input type="text" name="padre" id="padre" value="<?php echo esc_attr($padre); ?>" autocomplete="off">
			<div id="padre-suggestions" class="am-autocomplete"></div>
		</div>

		<div class="am-form-group">
			<label>Madre (da ANACT)</label>
			<input type="text" name="madre" id="madre" value="<?php echo esc_attr($madre); ?>" autocomplete="off">
			<div id="madre-suggestions" class="am-autocomplete"></div>
		</div>


		<div class="am-form-group">
			<label>Disponibilità</label>
			<select name="disponibilita" id="disponibilita" required>
				<option value="disponibile" <?php selected($disponibilita, 'disponibile'); ?>>Disponibile</option>
				<option value="non_disponibile" <?php selected($disponibilita, 'non_disponibile'); ?>>Non disponibile</option>
			</select>
		</div>

			<?php
			// Recupero delle immagini come nel backend
			$foto_ids = [];
			$foto1 = get_post_meta($post_id, '_foto_id', true);
			$foto2 = get_post_meta($post_id, '_foto2_id', true);
			$foto3 = get_post_meta($post_id, '_foto3_id', true);

			// Se esistono, li aggiungo all’array
			if ($foto1) $foto_ids[] = $foto1;
			if ($foto2) $foto_ids[] = $foto2;
			if ($foto3) $foto_ids[] = $foto3;
			?>

<div class="am-form-group">
    <label>Immagini (max 3)</label>
    <div id="am-photos-list" style="display:flex;gap:10px;flex-wrap:wrap;min-height:100px;border:2px dashed #ccc;padding:10px;border-radius:8px;">
        <?php foreach($foto_ids as $id):
            $url = wp_get_attachment_image_url($id, 'thumbnail'); // usa thumbnail per anteprima più piccola
            if(!$url) continue;
        ?>
            <div class="am-thumb-wrap" draggable="true" data-id="<?php echo esc_attr($id); ?>">
                <img src="<?php echo esc_url($url); ?>" class="am-thumb" alt="">
                <span class="am-remove-photo" data-id="<?php echo esc_attr($id); ?>">✖️</span>
            </div>
        <?php endforeach; ?>
    </div>
    <input type="file" id="am_upload_input" style="display:none;">
    <input type="hidden" id="am_foto_ids" value="<?php echo esc_attr(implode(',', $foto_ids)); ?>">
    <div class="am-form-actions">
        <button type="button" class="am-btn" id="am-add-photo">Carica / Cambia foto</button>
        <button type="submit" class="am-btn am-save-btn" id="am-save-puledro" style="margin-left:8px;">Salva</button>
    </div>
</div>


<script>
(function(){
    const ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
    const nonce = '<?php echo esc_js($nonce); ?>';
    let fotoIds = <?php echo json_encode(array_values($foto_ids)); ?>;
    const photosList = document.getElementById('am-photos-list');
    const uploadInput = document.getElementById('am_upload_input');
    const fotoIdsInput = document.getElementById('am_foto_ids');
    const form = document.getElementById('am-puledro-form');
    const notify = document.getElementById('am-notify');

    function syncHidden(){ fotoIdsInput.value = fotoIds.join(','); }
    function showNotify(msg){ notify.textContent=msg; notify.style.display='block'; setTimeout(()=>notify.style.display='none',2000); }

    // Drag & drop immagini
    let dragged = null;
    photosList.addEventListener('dragstart', e=>{
        const target = e.target.closest('.am-thumb-wrap');
        if(target) dragged = target;
    });
    photosList.addEventListener('dragover', e=>{ e.preventDefault(); });
    photosList.addEventListener('drop', e=>{
        e.preventDefault();
        const target = e.target.closest('.am-thumb-wrap');
        if(dragged && target && dragged!==target){
            photosList.insertBefore(dragged, target);
            // ricostruisci fotoIds in base all'ordine DOM
            fotoIds = Array.from(photosList.querySelectorAll('.am-thumb-wrap')).map(div => parseInt(div.getAttribute('data-id'))).filter(Boolean);
            syncHidden();
        }
    });

    // Carica immagini
    document.getElementById('am-add-photo').addEventListener('click', e=>{
        e.preventDefault();
        if(fotoIds.length>=3){ showNotify('Hai già caricato 3 foto.'); return; }
        uploadInput.click();
    });

    uploadInput.addEventListener('change', function(){
        const files = this.files;
        if(!files.length) return;
        for(let i=0;i<files.length;i++){
            if(fotoIds.length>=3) break;
            const fd = new FormData();
            fd.append('action','am_upload_puledro_photo');
            fd.append('file',files[i]);
            fd.append('security',nonce);
            fetch(ajaxUrl,{method:'POST',credentials:'same-origin',body:fd})
            .then(r=>r.json())
            .then(json=>{
                if(!json.success){ showNotify('Errore upload'); return; }
                fotoIds.push(json.data.id);
                syncHidden();
                const wrap = document.createElement('div');
                wrap.className='am-thumb-wrap'; wrap.setAttribute('draggable','true');
                wrap.setAttribute('data-id', json.data.id);
                wrap.innerHTML = `<img src="${json.data.url}" class="am-thumb" alt=""><span class="am-remove-photo" data-id="${json.data.id}">✖️</span>`;
                photosList.appendChild(wrap);
            }).catch(e=>{ console.error(e); showNotify('Errore upload'); });
        }
        this.value='';
    });

    // Rimuovi immagini
    photosList.addEventListener('click', e=>{
        const btn = e.target.closest('.am-remove-photo');
        if(!btn) return;
        const id = parseInt(btn.dataset.id);
        fotoIds = fotoIds.filter(x=>x!==id);
        const parent = btn.closest('.am-thumb-wrap');
        if(parent) parent.remove();
        syncHidden();
        showNotify('Immagine rimossa');
    });

    // Autocomplete ANACT
    function setupAutocomplete(inputId, tipo){
        const input = document.getElementById(inputId);
        const container = document.getElementById(inputId+'-suggestions');
        let timer = null;
        input.addEventListener('input', function(){
            const q = this.value.trim();
            if(q.length < 2){ container.style.display='none'; return; }
            clearTimeout(timer);
            timer=setTimeout(async ()=>{
                try{
                    const res = await fetch(`${ajaxUrl}?action=anact_autocomplete_cavalli&tipo=${tipo}&term=${encodeURIComponent(q)}`);
                    const json = await res.json();
                    container.innerHTML='';
                    if(!json.success || !json.data.length){ container.style.display='none'; return; }
                    json.data.forEach(item=>{
                        const div = document.createElement('div');
                        div.className='anact-item';
                        div.textContent=item.nome;
                        div.addEventListener('click', ()=>{ input.value=item.nome; container.style.display='none'; });
                        container.appendChild(div);
                    });
                    container.style.display='block';
                }catch(e){ container.style.display='none'; }
            },250);
        });
        document.addEventListener('click', e=>{ if(!container.contains(e.target) && e.target!==input) container.style.display='none'; });
    }
		setupAutocomplete('padre','stalloni');
		setupAutocomplete('madre','fattrici');

    // Submit form
    form.addEventListener('submit', e=>{
        e.preventDefault();
        const data = new FormData(form);
        <?php if ($modalita === 'modifica') : ?>
    data.append('post_id', '<?php echo $post_id; ?>');
<?php endif; ?>
data.append('modalita', '<?php echo $modalita; ?>');

        fotoIds.forEach(id=>data.append('foto_ids[]', id));
        data.append('action','am_save_puledro');
        data.append('security', nonce); // invia il nonce essenziale

        fetch(ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
        .then(r=>r.json())
        .then(json=>{
            if(!json.success){ showNotify('Errore: '+(json.data||'')); return; }
            showNotify('Puledro aggiornato correttamente');
            setTimeout(()=>window.location.href='/dashboard-allevatori/?sezione=puledri',800);
        }).catch(e=>{ console.error(e); showNotify('Errore di comunicazione'); });
    });

})();
</script>