<?php
if (!defined('ABSPATH')) exit;

if (!function_exists('am_render_pagina_puledri')) {
  function am_render_pagina_puledri($atts = []) {
    if (defined('AM_PLUGIN_URL')) {
      wp_enqueue_style('am-pagina-puledri', AM_PLUGIN_URL . 'assets/css/pagina-puledri.css', [], time());
      wp_enqueue_script('am-filtro-puledri', AM_PLUGIN_URL . 'assets/js/filtro-puledri.js', [], time(), true);
    }

    $atts = shortcode_atts(['posts_per_page' => 50], $atts, 'am_puledri_lista');
    $args = [
      'post_type'      => 'cavallo',
      'posts_per_page' => intval($atts['posts_per_page']),
      'meta_query'     => [
        ['key' => '_tipo_cavallo', 'value' => 'puledro', 'compare' => '=']
      ]
    ];

    $query = new WP_Query($args);
    $puledri = $query->posts;

    ob_start(); ?>

    <div class="am-pagina-puledri">
      <h1>Elenco Puledri</h1>

      <?php if ($query->have_posts()): ?>

      <div class="am-filtro-container">
        <form id="am-filtro-puledri">
          <input type="text" id="filtro-nome" placeholder="Cerca nome puledro...">

          <!-- Anno -->
          <select id="filtro-anno">
            <option value="">Anno di nascita (tutti)</option>
            <?php
            $anni = [];
            foreach ($puledri as $p) {
              $anno = get_post_meta($p->ID, '_produzione', true);
              if ($anno && !in_array($anno, $anni)) $anni[] = $anno;
            }
            rsort($anni);
            foreach ($anni as $a) echo "<option value='$a'>$a</option>";
            ?>
          </select>

          <!-- Sesso -->
          <select id="filtro-sesso">
            <option value="">Sesso (tutti)</option>
            <option value="Maschio">Maschio</option>
            <option value="Femmina">Femmina</option>
          </select>

          <!-- Padre -->
          <input list="lista-padri" id="filtro-padre" placeholder="Padre (tutti)">
          <datalist id="lista-padri">
            <?php
            $padri = [];
            foreach ($puledri as $p) {
              $padre = get_post_meta($p->ID, '_padre', true);
              if ($padre && !in_array($padre, $padri)) $padri[] = $padre;
            }
            sort($padri);
            foreach ($padri as $pa) echo "<option value='$pa'>";
            ?>
          </datalist>

          <!-- Madre -->
          <input list="lista-madri" id="filtro-madre" placeholder="Madre (tutti)">
          <datalist id="lista-madri">
            <?php
            $madri = [];
            foreach ($puledri as $p) {
              $madre = get_post_meta($p->ID, '_madre', true);
              if ($madre && !in_array($madre, $madri)) $madri[] = $madre;
            }
            sort($madri);
            foreach ($madri as $ma) echo "<option value='$ma'>";
            ?>
          </datalist>

          <!-- Disponibilità -->
          <select id="filtro-disponibilita">
            <option value="">Disponibilità (tutti)</option>
            <option value="disponibile">Disponibili</option>
            <option value="non_disponibile">Non Disponibili</option>
          </select>

          <button type="button" id="btn-cerca">Cerca</button>
          <button type="button" id="btn-reset">Azzera filtri</button>
        </form>
      </div>

      <table class="am-table am-table-pubblici">
  <thead>
    <tr>
      <th>Anno</th>
      <th>Nome Puledro</th>
      <th>Sesso</th>
      <th>Padre</th>
      <th>Madre</th>
      <th>Disponibilità</th>
      <th>Allevatore</th>
      <th></th>
    </tr>
  </thead>
  <tbody>
    <?php while ($query->have_posts()): $query->the_post();
      $post_id = get_the_ID();

      // Metadati principali
      $anno  = get_post_meta($post_id, '_produzione', true);
      $padre = get_post_meta($post_id, '_padre', true);
      $madre = get_post_meta($post_id, '_madre', true);
      $sesso = strtoupper(substr(get_post_meta($post_id, '_sesso', true), 0, 1)); // M o F
      $disp  = strtolower(get_post_meta($post_id, '_disponibilita', true));
      if (empty($disp)) $disp = 'disponibile';

      // Icona disponibilità
      $disp_label = ($disp === 'disponibile')
        ? '<span style="color:green;">✔️</span>'
        : '<span style="color:red;">❌</span>';

      // Recupera allevatore
      $allev_id = get_post_meta($post_id, '_allevatore_id', true);
      $allevatore_nome = '';
      if ($allev_id) {
        $user = get_user_by('id', $allev_id);
        if ($user) $allevatore_nome = esc_html($user->user_login);
      }
    ?>
    <tr data-disp="<?php echo esc_attr($disp); ?>">
      <td><?php echo esc_html($anno); ?></td>
      <td><?php the_title(); ?></td>
      <td><?php echo esc_html($sesso ?: '-'); ?></td>
      <td><?php echo esc_html($padre); ?></td>
      <td><?php echo esc_html($madre); ?></td>
      <td><?php echo $disp_label; ?></td>
      <td><?php echo $allevatore_nome ?: '—'; ?></td>
      <td><a class="am-btn" href="<?php the_permalink(); ?>">Scopri di più</a></td>
    </tr>
    <?php endwhile; wp_reset_postdata(); ?>
  </tbody>
</table>
<p id="nessun-risultato" 
   style="display:none; text-align:center; font-style:italic; margin-top:15px; 
          color:#b30000; background:#fff4f4; border:1px solid #f0c2c2; 
          padding:10px; border-radius:6px; max-width:600px; margin-left:auto; margin-right:auto;">
  ❌ Nessun puledro trovato con questi parametri.
</p>



      <?php else: ?>
        <p>Nessun puledro trovato.</p>
      <?php endif; ?>
    </div>

    <?php
    return ob_get_clean();
  }
  add_shortcode('am_puledri_lista', 'am_render_pagina_puledri');
}
?>
