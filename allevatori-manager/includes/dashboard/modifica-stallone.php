<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$post_id = intval($_GET['id'] ?? 0);
$modalita = $post_id ? 'modifica' : 'crea';

if($modalita === 'modifica'){
    $post = get_post($post_id);
    if(!$post || get_post_meta($post_id,'_allevatore_id',true)!=$current_user->ID){
        echo '<p>Stallone non accessibile.</p>';
        return;
    }
}

$nome = $modalita==='modifica' ? esc_attr($post->post_title) : '';

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('am_stallone_nonce');
?>

<link rel="stylesheet" href="<?php echo plugin_dir_url(dirname(__FILE__,2)); ?>assets/css/template-allevatore.css">

<div class="am-template-allevatore">
    <div class="am-breadcrumbs">
        <a href="/dashboard-allevatori/">Dashboard Allevatore</a> › 
        <a href="/dashboard-allevatori/?sezione=stalloni">Stalloni</a> › 
        <?php echo $modalita==='modifica'?'Modifica Stallone':'Nuovo Stallone'; ?>
    </div>

    <div class="am-header">
        <h1 class="am-title"><?php echo $modalita==='modifica'?'Modifica Stallone':'Aggiungi nuovo Stallone'; ?></h1>
        <p class="am-desc">Inserisci il nome dello stallone e salva.</p>
    </div>

    <form id="am-stallone-form" class="am-section">
        <div class="am-form-group">
            <label>Nome Stallone</label>
            <input type="text" name="nome_stallone" id="nome_stallone" value="<?php echo $nome; ?>" autocomplete="off" required>
            <div id="nome_stallone-suggestions" class="am-autocomplete"></div>
        </div>

        <div class="am-form-actions">
            <button type="submit" class="am-btn am-save-btn">Salva</button>
        </div>
    </form>

    <div id="am-notify" class="am-notify" role="status" aria-live="polite"></div>
</div>

<script>
(function(){
    const ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
    const nonce = '<?php echo esc_js($nonce); ?>';
    const form = document.getElementById('am-stallone-form');
    const notify = document.getElementById('am-notify');

    function showNotify(msg){ notify.textContent=msg; notify.style.display='block'; setTimeout(()=>notify.style.display='none',2000); }

    // Autocomplete ANACT
    const input = document.getElementById('nome_stallone');
    const container = document.getElementById('nome_stallone-suggestions');
    let timer = null;
    input.addEventListener('input', function(){
        const q = this.value.trim();
        if(q.length < 2){ container.style.display='none'; return; }
        clearTimeout(timer);
        timer = setTimeout(async ()=>{
            try{
                const res = await fetch(`${ajaxUrl}?action=anact_autocomplete_cavalli&tipo=stalloni&term=${encodeURIComponent(q)}`);
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

    // Submit form
    form.addEventListener('submit', e=>{
        e.preventDefault();
        const data = new FormData(form);
        data.append('modalita','<?php echo $modalita; ?>');
        <?php if($modalita==='modifica'): ?> data.append('post_id','<?php echo $post_id; ?>'); <?php endif; ?>
        data.append('action','am_save_stallone');
        data.append('security',nonce);

        fetch(ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
        .then(r=>r.json())
        .then(json=>{
            if(!json.success){ showNotify('Errore: '+(json.data||'')); return; }
            showNotify('Stallone creato correttamente');
            setTimeout(()=>window.location.href='/dashboard-allevatori/?sezione=stalloni',800);
        }).catch(e=>{ console.error(e); showNotify('Errore di comunicazione'); });
    });
})();
</script>
