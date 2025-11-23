<?php
if (!defined('ABSPATH')) exit;

// -------------------------
// Template Dashboard Puledri
// -------------------------
$current_user = wp_get_current_user();
$puledri = get_posts([
    'post_type'      => 'cavallo',
    'posts_per_page' => -1,
    'orderby'        => 'date',
    'order'          => 'DESC',
    'meta_query'     => [
        [ 'key' => '_tipo_cavallo', 'value' => 'puledro', 'compare' => '=' ],
        [ 'key' => '_allevatore_id', 'value' => $current_user->ID, 'compare' => '=' ]
    ]
]);

$ajax_url = admin_url('admin-ajax.php');
$nonce = wp_create_nonce('am_puledro_nonce');
wp_enqueue_media();
?>

<style>
.am-modal-overlay{position:fixed;inset:0;background:rgba(12,18,30,0.6);display:none;align-items:center;justify-content:center;z-index:9999}
.am-modal{background:#fff;border-radius:14px;max-width:760px;width:94%;padding:20px;box-shadow:0 20px 60px rgba(2,6,23,0.4);}
.am-modal-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px}
.am-modal-body{max-height:65vh;overflow:auto}
.am-thumb{width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid #eee}
.am-thumb-wrap{display:inline-block;position:relative;margin-right:8px}
.am-remove-photo{position:absolute;top:-6px;right:-6px;background:#fff;border-radius:50%;padding:2px 6px;border:1px solid #ddd;cursor:pointer}
</style>

<div class="am-dashboard-section">
    <div class="am-dashboard-header">
        <h2>I tuoi Puledri</h2>
        <button class="button button-primary" id="am-open-add-puledro">➕ Aggiungi nuovo Puledro</button>
    </div>

    <table class="am-table" id="am-puledri-table">
        <thead>
            <tr>
                <th>Anno</th>
                <th>Nome</th>
                <th>Sesso</th>
                <th>Padre</th>
                <th>Madre</th>
                <th>Disponibile</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($puledri)) : ?>
                <?php foreach ($puledri as $p) :
                    $anno        = get_post_meta($p->ID, '_produzione', true);
                    $padre       = get_post_meta($p->ID, '_padre', true);
                    $madre       = get_post_meta($p->ID, '_madre', true);
                    $sesso       = get_post_meta($p->ID, '_sesso', true);
                    $disponibile = get_post_meta($p->ID, '_disponibilita', true);
                    if (empty($anno)) $anno = date('Y');
                    if (empty($sesso)) $sesso = '-';
                    if (empty($disponibile)) $disponibile = 'Disponibile';
                ?>
                    <tr data-id="<?php echo $p->ID; ?>">
                        <td><?php echo esc_html($anno); ?></td>
                        <td><strong><?php echo esc_html($p->post_title); ?></strong></td>
                        <td><?php echo esc_html(strtoupper(substr($sesso, 0, 1))); ?></td>
                        <td><?php echo esc_html($padre ?: '-'); ?></td>
                        <td><?php echo esc_html($madre ?: '-'); ?></td>
                        <td><?php echo esc_html($disponibile); ?></td>
                        <td>
                            <button class="button am-edit-puledro" data-id="<?php echo $p->ID; ?>">✏️ Modifica</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="7" style="text-align:center;">Nessun puledro registrato.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<script>
// Quando clicco su "Modifica", vado a una nuova pagina
document.querySelectorAll('.am-edit-puledro').forEach(btn=>{
    btn.addEventListener('click', e=>{
        e.preventDefault();
        const postId = btn.getAttribute('data-id');
        if(!postId) return;
        window.location.href = `/dashboard-allevatori/?sezione=modifica-puledro&id=${postId}`;
    });
});
	
// Quando clicco su "Aggiungi nuovo Puledro", apro la stessa pagina ma senza ID
document.getElementById('am-open-add-puledro').addEventListener('click', e => {
    e.preventDefault();
    window.location.href = `/dashboard-allevatori/?sezione=modifica-puledro`;
});

</script>