<?php
if (!defined('ABSPATH')) exit;

// -------------------------
// Template Dashboard Stalloni
// -------------------------
$current_user = wp_get_current_user();
$stalloni = get_posts([
    'post_type'      => 'cavallo',
    'posts_per_page' => -1,
    'orderby'        => 'title',
    'order'          => 'ASC',
    'meta_query'     => [
        [ 'key' => '_tipo_cavallo', 'value' => 'stallone', 'compare' => '=' ],
        [ 'key' => '_allevatore_id', 'value' => $current_user->ID, 'compare' => '=' ]
    ]
]);

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('am_stallone_nonce');
?>

<div class="am-dashboard-section">
    <div class="am-dashboard-header">
        <h2>I tuoi Stalloni</h2>
        <button class="button button-primary" id="am-open-add-stallone">➕ Aggiungi nuovo Stallone</button>
    </div>

    <table class="am-table" id="am-stalloni-table">
        <thead>
            <tr>
                <th>Nome Stallone</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($stalloni)) : ?>
                <?php foreach ($stalloni as $s) : ?>
                    <tr data-id="<?php echo $s->ID; ?>">
                        <td><strong><?php echo esc_html($s->post_title); ?></strong></td>
                        <td>
                            <button class="button am-delete-stallone" data-id="<?php echo $s->ID; ?>">🗑️ Elimina</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="2" style="text-align:center;">Nessuno stallone registrato.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
var ajaxUrl = '<?php echo esc_js($ajax_url); ?>';
var nonce = '<?php echo esc_js($nonce); ?>';


// Aggiungi nuovo stallone
document.getElementById('am-open-add-stallone').addEventListener('click', e => {
    e.preventDefault();
    const createStalloneUrl = '<?php echo esc_url( add_query_arg( array('sezione' => 'modifica-stallone'), home_url('dashboard-allevatori') ) ); ?>';
    window.location.href = createStalloneUrl;
});


// Elimina stallone
document.querySelectorAll('.am-delete-stallone').forEach(btn=>{
    btn.addEventListener('click', e=>{
        e.preventDefault();
        if(!confirm('Sei sicuro di voler eliminare questo stallone?')) return;
        const id = btn.dataset.id;
        const data = new FormData();
        data.append('action','am_delete_stallone');
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
