<?php
if (!defined('ABSPATH')) exit;

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

get_header();

$slug = get_query_var('allevatore');
if (empty($slug)) {
    status_header(404);
    echo '<h1>Allevatore non trovato</h1>';
    get_footer();
    return;
}

$users = get_users([
    'meta_key'   => 'allevatore_slug',
    'meta_value' => $slug,
    'number'     => 1,
]);

$allevatore = !empty($users) ? $users[0] : false;
if (!$allevatore) {
    echo '<h2>Allevatore non trovato</h2>';
    get_footer();
    return;
}

$plugin_url = plugin_dir_url(__DIR__);
wp_enqueue_style('am-template-allevatore', $plugin_url . 'assets/css/template-allevatore.css', [], time());

$allev_id = $allevatore->ID;
$featured_id = get_user_meta($allev_id, 'immagine_evidenza', true);
$logo_id = get_user_meta($allev_id, 'logo_allevamento', true);
$nome_allevamento = get_user_meta($allev_id, 'allevamento_nome', true) ?: $allevatore->user_login;
$descrizione = get_user_meta($allev_id, 'descrizione_allevamento', true);
$telefono = get_user_meta($allev_id, 'telefono', true);
$cellulare = get_user_meta($allev_id, 'cellulare', true);
$email = $allevatore->user_email;
$indirizzo = get_user_meta($allev_id, 'indirizzo', true);
$indirizzo_mappa = $indirizzo ? urlencode($indirizzo) : urlencode('Roma, Italia');

$anno_default = date('Y');

function am_query_cavalli_by_type($allevatore_id, $tipo, $anno = null) {
    $meta_query = [
        ['key' => '_allevatore_id', 'value' => (string)$allevatore_id, 'compare' => '='],
        ['key' => '_tipo_cavallo', 'value' => $tipo, 'compare' => '='],
    ];
    if ($anno) {
        $meta_query[] = ['key' => '_produzione', 'value' => (string)$anno, 'compare' => '='];
    }
    return new WP_Query([
        'post_type' => 'cavallo',
        'posts_per_page' => -1,
        'meta_query' => $meta_query,
    ]);
}
?>

<div class="am-template-allevatore">

    <!-- Breadcrumb -->
    <nav class="am-breadcrumbs">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> ›
        <a href="<?php echo esc_url(home_url('/allevatori')); ?>">Allevatori</a> ›
        <span><?php echo esc_html($nome_allevamento); ?></span>
    </nav>

    <!-- Anchor nav -->
    <nav class="am-anchor-nav">
        <a href="#puledri">Puledri</a>
        <a href="#fattrici">Fattrici</a>
        <a href="#stalloni">Stalloni</a>
        <a href="#contatti">Contatti & Mappa</a>
    </nav>

    <!-- Banner (featured image) -->
    <?php if (!empty($featured_id) && (int)$featured_id > 0): ?>
        <div class="am-banner">
            <?php echo wp_get_attachment_image((int)$featured_id, 'full', false, [
                'class' => 'am-banner-img',
                'alt'   => 'Banner ' . esc_attr($nome_allevamento)
            ]); ?>
        </div>
    <?php else: ?>
        <div class="am-banner am-banner-placeholder" aria-hidden="true"></div>
    <?php endif; ?>

    <!-- Header with overlapping logo -->
    <header class="am-header">
        <?php if (!empty($logo_id) && (int)$logo_id > 0): ?>
            <div class="am-logo-wrap">
                <?php echo wp_get_attachment_image((int)$logo_id, 'medium', false, [
                    'class' => 'am-logo',
                    'alt'   => esc_attr($nome_allevamento)
                ]); ?>
            </div>
        <?php else: ?>
            <div class="am-logo-wrap">
                <div class="am-logo-placeholder" aria-hidden="true"></div>
            </div>
        <?php endif; ?>

        <div class="am-header-content">
            <h1 class="am-title"><?php echo esc_html($nome_allevamento); ?></h1>
            <?php if ($descrizione): ?>
                <div class="am-desc"><?php echo wp_kses_post(wpautop($descrizione)); ?></div>
            <?php endif; ?>
            <div class="am-contacts">
                <?php if ($telefono): ?>
                    <a href="tel:<?php echo esc_attr($telefono); ?>" class="am-contact-item">📞 Telefono</a>
                <?php endif; ?>
                <?php if ($cellulare): ?>
                    <a href="tel:<?php echo esc_attr($cellulare); ?>" class="am-contact-item">📱 Cellulare</a>
                <?php endif; ?>
                <?php if ($email): ?>
                    <a href="mailto:<?php echo esc_attr($email); ?>" class="am-contact-item">✉️ Email</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <!-- Puledri -->
    <section id="puledri" class="am-section">
        <h2>Puledri — Produzione <?php echo esc_html($anno_default); ?></h2>
        <?php
        $puledri = am_query_cavalli_by_type($allev_id, 'puledro', $anno_default);
        if ($puledri->have_posts()): ?>
            <table class="am-table">
                <thead>
                    <tr>
                        <th>Anno</th>
                        <th>Nome Puledro</th>
                        <th>Sesso</th>
                        <th>Padre</th>
                        <th>Madre</th>
                        <th>Disponibile</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php while($puledri->have_posts()): $puledri->the_post();
                    $post_id = get_the_ID();
                    $anno = get_post_meta($post_id, '_produzione', true);
                    $padre = get_post_meta($post_id, '_padre', true);
                    $madre = get_post_meta($post_id, '_madre', true);
                    $sesso = get_post_meta($post_id, '_sesso', true);
                    $disponibile_meta = get_post_meta($post_id, '_disponibilita', true);
                    if (empty($anno)) $anno = date('Y');
                    if (empty($sesso)) $sesso = '-';
                    if (empty($disponibile_meta) || strtolower($disponibile_meta) === 'disponibile') {
                        $disponibile = '<span style="color:green;">✔️</span>';
                    } elseif ($disponibile_meta === 'non_disponibile' || strtolower($disponibile_meta) === 'non_disponibile') {
                        $disponibile = '<span style="color:red;">❌</span>';
                    } else {
                        $disponibile = '<span style="color:#555;">' . ucfirst(str_replace(['_','-'], ' ', $disponibile_meta)) . '</span>';
                    }
                    ?>
                    <tr>
                        <td><?php echo esc_html($anno); ?></td>
                        <td><?php the_title(); ?></td>
                        <td><?php echo esc_html(strtoupper(substr($sesso, 0, 1))); ?></td>
                        <td><?php echo esc_html($padre); ?></td>
                        <td><?php echo esc_html($madre); ?></td>
                        <td><?php echo ($disponibile); ?></td>
                        <td><a class="am-btn" href="<?php the_permalink(); ?>">Scopri di più</a></td>
                    </tr>
                <?php endwhile; wp_reset_postdata(); ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nessun puledro registrato per <?php echo esc_html($anno_default); ?>.</p>
        <?php endif; ?>
    </section>

    <!-- Fattrici -->
    <section id="fattrici" class="am-section">
        <h2>Fattrici</h2>
        <?php
        $fattrici = am_query_cavalli_by_type($allev_id, 'fattrice');
        if ($fattrici->have_posts()): ?>
            <table class="am-table">
                <thead>
                    <tr>
                        <th>Nome Fattrice</th>
                        <th>Genealogia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($fattrici->have_posts()): $fattrici->the_post(); ?>
                    <tr>
                        <td><?php the_title(); ?></td>
                        <td>
                            <a class="am-btn" href="<?php echo esc_url(am_get_anact_link(get_the_title(), 'fattrici')); ?>" target="_blank" rel="noopener">
                                Guarda genealogia
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nessuna fattrice trovata.</p>
        <?php endif; ?>
    </section>

    <!-- Stalloni -->
    <section id="stalloni" class="am-section">
        <h2>Stalloni</h2>
        <?php
        $stalloni = am_query_cavalli_by_type($allev_id, 'stallone');
        if ($stalloni->have_posts()): ?>
            <table class="am-table">
                <thead>
                    <tr>
                        <th>Nome Stallone</th>
                        <th>Genealogia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($stalloni->have_posts()): $stalloni->the_post(); ?>
                    <tr>
                        <td><?php the_title(); ?></td>
                        <td>
                            <a class="am-btn" href="<?php echo esc_url(am_get_anact_link(get_the_title(), 'stalloni')); ?>" target="_blank" rel="noopener">
                                Guarda genealogia
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nessuno stallone registrato.</p>
        <?php endif; ?>
    </section>

    <!-- Contatti social + mappa -->
    <section id="contatti" class="am-section am-map">
        <h2>Contatti e Mappa</h2>

        <div class="am-socials">
            <?php
            $facebook = get_user_meta($allev_id, 'facebook_allevamento', true);
            $instagram = get_user_meta($allev_id, 'instagram_allevamento', true);
            $sito = get_user_meta($allev_id, 'sito_allevamento', true);
            if ($facebook || $instagram || $sito): ?>
                <div class="am-social-list">
                    <?php if ($facebook): ?>
                        <a href="<?php echo esc_url($facebook); ?>" target="_blank" rel="noopener" class="am-contact-item">Facebook</a>
                    <?php endif; ?>
                    <?php if ($instagram): ?>
                        <a href="<?php echo esc_url($instagram); ?>" target="_blank" rel="noopener" class="am-contact-item">Instagram</a>
                    <?php endif; ?>
                    <?php if ($sito): ?>
                        <a href="<?php echo esc_url($sito); ?>" target="_blank" rel="noopener" class="am-contact-item">Sito web</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <p>Nessun contatto social disponibile.</p>
            <?php endif; ?>
        </div>

        <p><?php echo $indirizzo ? esc_html($indirizzo) : 'Roma, Italia'; ?></p>
        <iframe
            width="100%" height="400" style="border:0; border-radius: 10px;"
            loading="lazy" allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps?q=<?php echo $indirizzo_mappa; ?>&output=embed">
        </iframe>
    </section>

</div>

<script>
document.querySelectorAll('.am-anchor-nav a').forEach(link => {
    link.addEventListener('click', function(e){
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            window.scrollTo({
                top: target.offsetTop - 100,
                behavior: 'smooth'
            });
        }
    });
});
</script>

<?php get_footer(); ?>
