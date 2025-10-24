<?php
if (!defined('ABSPATH')) exit;

/**
 * Restituisce il link ANACT per genealogia di un cavallo
 * 
 * @param string $nome Nome del cavallo
 * @param string $tipo 'stalloni' o 'fattrici'
 * @return string URL del sito ANACT, oppure '#' se non trovato
 */
function am_get_anact_link($nome, $tipo = 'stalloni') {
    $nome = strtoupper(trim($nome));
    $url = "https://www.anact.it/autocomplete.php?object={$tipo}&search=" . urlencode($nome);

    $response = wp_remote_get($url, ['timeout' => 5]);
    if (is_wp_error($response)) return '#';

    $body = wp_remote_retrieve_body($response);
    $json = json_decode($body, true);
    if (empty($json['success']) || empty($json['data'])) return '#';

    // Cerca il primo risultato che corrisponde esattamente al nome
    foreach ($json['data'] as $item) {
        if (strtoupper($item['nome']) === $nome) {
            return "https://www.anact.it/genealogie/?codice=" . $item['codice'];
        }
    }

    // Fallback: primo risultato se non c’è corrispondenza esatta
    return "https://www.anact.it/genealogie/?codice=" . $json['data'][0]['codice'];
}

get_header(); // ✅ Mostra header e menu del tema

// Recupera lo slug dall’URL
$slug = get_query_var('allevatore');

if (empty($slug)) {
    status_header(404);
    echo '<h1>Allevatore non trovato</h1>';
    get_footer();
    return;
}

// Cerca l’allevatore per meta 'allevatore_slug'
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

// Enqueue stylesheet
$plugin_url = plugin_dir_url(__DIR__);
wp_enqueue_style('am-template-allevatore', $plugin_url . 'assets/css/template-allevatore.css', [], time());

// Dati allevatore
$allev_id = $allevatore->ID;
$logo_id = get_user_meta($allev_id, 'logo_allevatore', true);
$logo_url = $logo_id ? wp_get_attachment_url($logo_id) : '';
$nome_allevamento = $allevatore->user_login;
$descrizione = get_user_meta($allev_id, 'descrizione_allevamento', true);
$telefono = get_user_meta($allev_id, 'telefono', true);
$cellulare = get_user_meta($allev_id, 'cellulare', true);
$email = $allevatore->user_email;
$indirizzo = get_user_meta($allev_id, 'indirizzo', true);
$indirizzo_mappa = $indirizzo ? urlencode($indirizzo) : urlencode('Roma, Italia');

// Produzione anno default
$anno_default = date('Y');

// helper: query cavalli per tipo
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

    <!-- 🧭 Breadcrumb -->
    <nav class="am-breadcrumbs">
        <a href="<?php echo esc_url(home_url('/')); ?>">Home</a> › 
        <a href="<?php echo esc_url(home_url('/allevatori')); ?>">Allevatori</a> › 
        <span><?php echo esc_html($nome_allevamento); ?></span>
    </nav>

    <!-- 🔝 NAVIGATION ANCHORS -->
    <nav class="am-anchor-nav">
        <a href="#puledri">Puledri</a>
        <a href="#fattrici">Fattrici</a>
        <a href="#stalloni">Stalloni</a>
        <a href="#contatti">Contatti & Mappa</a>
    </nav>

    <!-- 🐎 HEADER -->
    <header class="am-header">
        <?php if ($logo_url): ?>
            <img class="am-logo" src="<?php echo esc_url($logo_url); ?>" alt="<?php echo esc_attr($nome_allevamento); ?>">
        <?php endif; ?>

        <h1 class="am-title"><?php echo esc_html($nome_allevamento); ?></h1>

        <?php if ($descrizione): ?>
            <p class="am-desc"><?php echo wp_kses_post(wpautop($descrizione)); ?></p>
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
    </header>
	
		<!-- 🐴 PULEDRI -->
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
                $disponibile = get_post_meta($post_id, '_disponibile', true);
                
                // Default valori
                if (empty($anno)) $anno = date('Y');
                if (empty($sesso)) $sesso = '-';
                if (empty($disponibile)) $disponibile = 'Disponibile';
                ?>
                <tr>
                    <td><?php echo esc_html($anno); ?></td>
                    <td><?php the_title(); ?></td>
                    <td><?php echo esc_html(strtoupper(substr($sesso, 0, 1))); ?></td>
                    <td><?php echo esc_html($padre); ?></td>
                    <td><?php echo esc_html($madre); ?></td>
                    <td><?php echo esc_html($disponibile); ?></td>
                    <td><a class="am-btn" href="<?php the_permalink(); ?>">Scopri di più</a></td>
                </tr>
            <?php endwhile; wp_reset_postdata(); ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nessun puledro registrato per <?php echo esc_html($anno_default); ?>.</p>
    <?php endif; ?>
</section>

    <!-- 🐎 FATTRICI -->
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
							<a class="am-btn" href="<?php echo esc_url(am_get_anact_link(get_the_title(), 'fattrici')); ?>" target="_blank">
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

    <!-- 🐴 STALLONI -->
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
							<a class="am-btn" href="<?php echo esc_url(am_get_anact_link(get_the_title(), 'stalloni')); ?>" target="_blank">
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

    <!-- 📍 MAPPA -->
    <section id="contatti" class="am-section am-map">
        <h2>Contatti e Mappa</h2>
        <p><?php echo $indirizzo ? esc_html($indirizzo) : 'Roma, Italia'; ?></p>
        <iframe 
            width="100%" height="400" style="border:0; border-radius: 10px;"
            loading="lazy" allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"
            src="https://www.google.com/maps?q=<?php echo $indirizzo_mappa; ?>&output=embed">
        </iframe>
    </section>

</div>

<!-- ✨ Scroll fluido -->
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

<?php get_footer(); // ✅ Mostra footer del tema ?>
