<?php
if (!defined('ABSPATH')) exit;

// -------------------------
// Template Dashboard Fattrici
// -------------------------
$current_user = wp_get_current_user();
$fattrici = get_posts([
    'post_type'      => 'cavallo',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'meta_query'     => [
        [ 'key' => '_tipo_cavallo', 'value' => 'fattrice', 'compare' => '=' ],
        [ 'key' => '_allevatore_id', 'value' => $current_user->ID, 'compare' => '=' ]
    ]
]);

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('am_fattrice_nonce');
?>

<div class="am-dashboard-section">
    <div class="am-dashboard-header">
        <h2>Le tue Fattrici</h2>
        <button class="button button-primary" id="am-open-add-fattrice">➕ Aggiungi nuova Fattrice</button>
    </div>

    <table class="am-table" id="am-fattrici-table">
        <thead>
            <tr>
                <th>Nome Fattrice</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($fattrici)) : ?>
                <?php foreach ($fattrici as $f) : ?>
                    <tr data-id="<?php echo $f->ID; ?>">
                        <td><strong><?php echo esc_html($f->post_title); ?></strong></td>
                        <td>
                            <button class="button am-delete-fattrice" data-id="<?php echo $f->ID; ?>">🗑️ Elimina</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="2" style="text-align:center;">Nessuna fattrice registrata.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
var nonce = '<?php echo esc_js($nonce); ?>';

// Aggiungi nuova fattrice
const createFattriceUrl = '<?php echo esc_url( add_query_arg( array('sezione'=>'modifica-fattrice'), home_url('dashboard-allevatori') ) ); ?>';
document.getElementById('am-open-add-fattrice').addEventListener('click', e=>{
    e.preventDefault();
    window.location.href = createFattriceUrl;
});

// Elimina fattrice
document.querySelectorAll('.am-delete-fattrice').forEach(btn=>{
    btn.addEventListener('click', e=>{
        e.preventDefault();
        if(!confirm('Sei sicuro di voler eliminare questa fattrice?')) return;
        const id = btn.dataset.id;
        const data = new FormData();
        data.append('action','am_delete_fattrice');
        data.append('post_id', id);
        data.append('security', nonce);

        fetch(ajaxUrl,{method:'POST',credentials:'same-origin',body:data})
        .then(r=>r.json())
        .then(json=>{
            if(!json.success){ alert('Errore: '+(json.data||'')); return; }
            btn.closest('tr').remove();
        }).catch(e=>console.error(e));
    });
});
</script>
