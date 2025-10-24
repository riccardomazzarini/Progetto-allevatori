<?php
$current_user = wp_get_current_user();
$stalloni = get_posts([
    'post_type' => 'cavallo',
    'meta_key' => '_tipo_cavallo',
    'meta_value' => 'stallone',
    'author' => $current_user->ID,
    'numberposts' => -1,
]);
?>
<h2>Stalloni</h2>
<a class="button" href="<?php echo admin_url('post-new.php?post_type=cavallo'); ?>&tipo_cavallo=stallone">Aggiungi Stallone</a>
<table class="am-table">
    <thead>
        <tr>
            <th>Nome</th>
            <th>Foto</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($stalloni as $s): 
            $foto_id = get_post_meta($s->ID, '_foto_id', true);
            $foto_url = $foto_id ? wp_get_attachment_image_url($foto_id, 'thumbnail') : '';
        ?>
        <tr>
            <td><?php echo esc_html($s->post_title); ?></td>
            <td><?php if($foto_url): ?><img src="<?php echo esc_url($foto_url); ?>" style="max-width:50px;"><?php endif; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
