<?php
if (!defined('ABSPATH')) exit;

/**
 * Template Name: Template Puledro
 * Description: Pagina singola per la scheda di un puledro
 */

get_header();

// Enqueue CSS
$plugin_url = plugin_dir_url(__DIR__);
wp_enqueue_style('am-template-puledro', $plugin_url . 'assets/css/template-puledro.css', [], time());

// Ottieni dati del puledro
global $post;
$post_id = get_the_ID();

$titolo       = get_the_title();
$anno         = get_post_meta($post_id, '_produzione', true);
$sesso        = get_post_meta($post_id, '_sesso', true);
$padre        = get_post_meta($post_id, '_padre', true);
$madre        = get_post_meta($post_id, '_madre', true);
$disponibile  = get_post_meta($post_id, '_disponibilita', true);
$allev_id     = get_post_meta($post_id, '_allevatore_id', true);

// Recupera immagini
$foto1_id = get_post_meta($post_id, '_foto_id', true);
$foto2_id = get_post_meta($post_id, '_foto2_id', true);
$foto3_id = get_post_meta($post_id, '_foto3_id', true);

$foto1_url = $foto1_id ? wp_get_attachment_image_url($foto1_id, 'medium') : '';
$foto2_url = $foto2_id ? wp_get_attachment_image_url($foto2_id, 'medium') : '';
$foto3_url = $foto3_id ? wp_get_attachment_image_url($foto3_id, 'medium') : '';

// Default valori
if (empty($anno)) $anno = date('Y');
if (empty($disponibile)) $disponibile = 'Disponibile';

// Recupera allevatore
$allevatore = get_user_by('ID', $allev_id);
$nome_allevamento = $allevatore ? $allevatore->user_login : 'Allevamento sconosciuto';
$slug_allevatore = $allevatore ? get_user_meta($allev_id, 'allevatore_slug', true) : '';
$telefono = $allevatore ? get_user_meta($allev_id, 'telefono', true) : '';
$cellulare = $allevatore ? get_user_meta($allev_id, 'cellulare', true) : '';
$email = $allevatore ? $allevatore->user_email : '';
$logo_id = $allevatore ? get_user_meta($allev_id, 'logo_allevatore', true) : '';
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';

// Funzione genealogia
function am_get_anact_link($nome, $tipo = 'stalloni') {
    $nome = strtoupper(trim($nome));
    $url = "https://www.anact.it/autocomplete.php?object={$tipo}&search=" . urlencode($nome);
    $response = wp_remote_get($url, ['timeout' => 5]);
    if (is_wp_error($response)) return '#';

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);
    if (empty($json['success']) || empty($json['data'])) return '#';

    foreach ($json['data'] as $item) {
        if (strtoupper($item['nome']) === $nome) {
            return "https://www.anact.it/genealogie/?codice=" . $item['codice'];
        }
    }
    return "https://www.anact.it/genealogie/?codice=" . $json['data'][0]['codice'];
}

?>

<div class="am-template-puledro">

    <!-- 🧭 Breadcrumb -->
    <nav class="am-breadcrumbs">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › 
        <a href="<?php echo esc_url(home_url('/allevatore')); ?>">Allevatori</a> › 
        <?php if ($slug_allevatore): ?>
            <a href="<?php echo esc_url(home_url('/allevatore/' . $slug_allevatore)); ?>">
                <?php echo esc_html($nome_allevamento); ?>
            </a> ›
        <?php endif; ?>
        <span><?php echo esc_html($titolo); ?></span>
    </nav>

    <!-- 🐴 Dettaglio Puledro -->
    <section class="am-puledro-info">

       <!-- Immagini -->
<div class="am-puledro-img">
    <?php if ($foto1_url): ?>
        <div class="am-main-img">
            <img id="am-main-image" src="<?php echo esc_url($foto1_url); ?>" alt="<?php echo esc_attr($titolo); ?>">
        </div>

        <div class="am-thumb-images">
            <!-- Prima immagine -->
            <img src="<?php echo esc_url($foto1_url); ?>" alt="<?php echo esc_attr($titolo); ?>" class="am-thumb active">
            <!-- Seconda immagine -->
            <?php if ($foto2_url): ?>
                <img src="<?php echo esc_url($foto2_url); ?>" alt="<?php echo esc_attr($titolo); ?>" class="am-thumb">
            <?php endif; ?>
            <!-- Terza immagine -->
            <?php if ($foto3_url): ?>
                <img src="<?php echo esc_url($foto3_url); ?>" alt="<?php echo esc_attr($titolo); ?>" class="am-thumb">
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="am-no-img">Nessuna immagine</div>
    <?php endif; ?>
</div>

        <!-- Dati principali -->
        <div class="am-puledro-data">
            <h1 class="am-title"><?php echo esc_html($titolo); ?></h1>

            <ul class="am-meta">
                <li><strong>Anno:</strong> <?php echo esc_html($anno); ?></li>
                <li><strong>Sesso:</strong> <?php echo esc_html(strtoupper(substr($sesso, 0, 1))); ?></li>
                <li><strong>Disponibilità:</strong> <?php echo esc_html($disponibile); ?></li>
            </ul>

            <div class="am-parents">
                <?php if ($padre): ?>
                    <p><strong>Padre:</strong> <?php echo esc_html($padre); ?>  
                        <a class="am-btn" href="<?php echo esc_url(am_get_anact_link($padre, 'stalloni')); ?>" target="_blank">
                            Vedi genealogia
                        </a>
                    </p>
                <?php endif; ?>

                <?php if ($madre): ?>
                    <p><strong>Madre:</strong> <?php echo esc_html($madre); ?>  
                        <a class="am-btn" href="<?php echo esc_url(am_get_anact_link($madre, 'fattrici')); ?>" target="_blank">
                            Vedi genealogia
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- 🏇 Banner Allevatore -->
    <?php if ($allevatore): ?>
        <section class="am-allevatore-banner">
            <div class="am-banner-content">
                <?php if ($logo_url): ?>
                    <img src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($nome_allevamento); ?>" class="am-banner-logo">
                <?php endif; ?>

                <div class="am-banner-text">
                    <h3><?php echo esc_html($nome_allevamento); ?></h3>
                    <p>
                        <?php if ($telefono): ?>📞 <a href="tel:<?php echo esc_attr($telefono); ?>"><?php echo esc_html($telefono); ?></a> &nbsp;<?php endif; ?>
                        <?php if ($cellulare): ?>📱 <a href="tel:<?php echo esc_attr($cellulare); ?>"><?php echo esc_html($cellulare); ?></a> &nbsp;<?php endif; ?>
                        <?php if ($email): ?>✉️ <a href="mailto:<?php echo esc_attr($email); ?>"><?php echo esc_html($email); ?></a><?php endif; ?>
                    </p>
                    <a class="am-btn" href="<?php echo esc_url(home_url('/allevatore/' . $slug_allevatore)); ?>">
                        ← Torna all'allevamento
                    </a>
                </div>
            </div>
        </section>
    <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const mainImg = document.getElementById('am-main-image');
    const thumbs = document.querySelectorAll('.am-thumb');

    thumbs.forEach(function(thumb) {
        thumb.addEventListener('click', function() {
            mainImg.src = this.src;
            
            // Aggiorna miniatura attiva
            thumbs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>


<?php get_footer(); ?>
