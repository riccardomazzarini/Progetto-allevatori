<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$post_id = intval($_GET['id'] ?? 0);

if (!$post_id) { echo '<p>Puledro non trovato.</p>'; return; }

$post = get_post($post_id);
if (!$post || get_post_meta($post_id, '_allevatore_id', true) != $current_user->ID) {
    echo '<p>Puledro non accessibile.</p>'; return;
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
.am-thumb-wrap { position: relative; cursor: grab; transition: transform 0.2s; }
.am-thumb-wrap.dragging { opacity: 0.6; transform: scale(1.05); }
.am-remove-photo { cursor: pointer; transition: 0.2s; }
.am-notify {
  position: fixed;
  top: 1rem; right: 1rem;
  background: #c00; color: #fff;
  padding: 0.8rem 1rem; border-radius: 8px;
  display: none; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}
</style>

<div class="am-template-allevatore">

    <div class="am-breadcrumbs">
        <a href="/dashboard-allevatori/">Dashboard Allevatore</a> › 
        <a href="/dashboard-allevatori/?sezione=puledri">Puledri</a> › Modifica Puledro
    </div>

    <div class="am-header">
        <h1 class="am-title">Modifica Puledro</h1>
        <p class="am-desc">Aggiorna le informazioni e le immagini del tuo puledro.</p>
    </div>

    <form id="am-puledro-form" class="am-section">
        <div class="am-form-group">
            <label>Nome Puledro</label>
            <input type="text" name="nome_puledro" id="nome_puledro" value="<?php echo esc_attr($post->post_title); ?>" required>
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
            $url = wp_get_attachment_url($id); 
            if(!$url) continue;
        ?>
            <div class="am-thumb-wrap" draggable="true">
                <img src="<?php echo esc_url($url); ?>" class="am-thumb">
                <span class="am-remove-photo" data-id="<?php echo $id; ?>">✖️</span>
            </div>
        <?php endforeach; ?>
    </div>
    <input type="file" id="am_upload_input" style="display:none;">
    <button class="am-btn" id="am-add-photo">Carica / Cambia foto</button>
    <input type="hidden" name="foto_ids[]" id="am_foto_ids" value="<?php echo implode(',', $foto_ids); ?>">
</div>


<script>
(function(){
    const ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
    const nonce = '<?php echo esc_js($nonce); ?>';
    let fotoIds = <?php echo json_encode($foto_ids); ?>;
    const photosList = document.getElementById('am-photos-list');
    const uploadInput = document.getElementById('am_upload_input');
    const fotoIdsInput = document.getElementById('am_foto_ids');
    const form = document.getElementById('am-puledro-form');
    const notify = document.getElementById('am-notify');

    function syncHidden(){ fotoIdsInput.value=fotoIds.join(','); }
    function showNotify(msg){ notify.textContent=msg; notify.style.display='block'; setTimeout(()=>notify.style.display='none',2000); }

    // Drag & drop immagini
    let dragged = null;
    photosList.addEventListener('dragstart', e=>{
        if(e.target.classList.contains('am-thumb-wrap')) dragged=e.target;
    });
    photosList.addEventListener('dragover', e=>{ e.preventDefault(); });
    photosList.addEventListener('drop', e=>{
        e.preventDefault();
        if(dragged && e.target.closest('.am-thumb-wrap') && dragged!==e.target.closest('.am-thumb-wrap')){
            photosList.insertBefore(dragged,e.target.closest('.am-thumb-wrap'));
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
                wrap.innerHTML = `<img src="${json.data.url}" class="am-thumb"><span class="am-remove-photo" data-id="${json.data.id}">✖️</span>`;
                photosList.appendChild(wrap);
            }).catch(e=>console.error(e));
        }
        this.value='';
    });

    // Rimuovi immagini
    photosList.addEventListener('click', e=>{
        const btn = e.target.closest('.am-remove-photo');
        if(!btn) return;
        const id = parseInt(btn.dataset.id);
        fotoIds = fotoIds.filter(x=>x!==id);
        btn.parentNode.remove();
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
        data.append('post_id', '<?php echo $post_id; ?>');
        fotoIds.forEach(id=>data.append('foto_ids[]',id));
        data.append('action','am_save_puledro');

        fetch(ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
        .then(r=>r.json())
        .then(json=>{
            if(!json.success){ showNotify('Errore: '+(json.data||'')); return; }
            showNotify('Puledro aggiornato correttamente');
            setTimeout(()=>window.location.href='/dashboard-allevatori/?sezione=puledri',800);
        }).catch(e=>console.error(e));
    });

})();
</script>
