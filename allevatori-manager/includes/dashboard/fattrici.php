<?php
$current_user = wp_get_current_user();
$fattrici = get_posts([
    'post_type' => 'cavallo',
    'meta_key' => '_tipo_cavallo',
    'meta_value' => 'fattrice',
    'author' => $current_user->ID,
    'numberposts' => -1,
]);
?>
<h2>Fattrici</h2>
<a class="button" href="<?php echo admin_url('post-new.php?post_type=cavallo'); ?>&tipo_cavallo=fattrice">Aggiungi Fattrice</a>
<table class="am-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Foto</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($fattrici as $f): 
            $foto_id = get_post_meta($f->ID, '_foto_id', true);
            $foto_url = $foto_id ? wp_get_attachment_image_url($foto_id, 'thumbnail') : '';
        ?>
        <tr>
            <td><?php echo esc_html($f->post_title); ?></td>
            <td><?php if($foto_url): ?><img src="<?php echo esc_url($foto_url); ?>" style="max-width:50px;"><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
