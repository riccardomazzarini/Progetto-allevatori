<?php
if (!defined('ABSPATH')) exit;

// Router per dashboard allevatore
add_action('template_redirect', function() {
    if (!is_page('dashboard-allevatori')) return;

    // Controlla se c'è parametro "sezione" o "slug"
    $slug = isset($_GET['sezione']) ? sanitize_text_field($_GET['sezione']) : '';

    if ($slug === 'modifica-puledro') {
        include AM_PLUGIN_DIR . 'includes/dashboard/modifica-puledro.php';
        exit; // importante: blocca il rendering della dashboard classica
    }
});

// Shortcode principale dashboard allevatore
function am_allevatore_dashboard() {
    $current_user = wp_get_current_user();

    // Se non è allevatore, redirect
    if (!in_array('allevatore', (array)$current_user->roles)) {
        wp_redirect(home_url());
        exit;
    }


    ob_start(); ?>
    <div class="am-dashboard">
        <div class="am-sidebar">
            <ul>
                <li><a href="#" class="am-tab-link active" data-tab="bacheca">📊 Bacheca</a></li>
                <li><a href="#" class="am-tab-link" data-tab="informazioni">👤 Informazioni e Dettagli</a></li>
                <li class="am-submenu-title">🐎 Cavalli</li>
                <ul class="am-submenu">
                    <li><a href="#" class="am-tab-link" data-tab="puledri">Puledri</a></li>
                    <li><a href="#" class="am-tab-link" data-tab="stalloni">Stalloni</a></li>
                    <li><a href="#" class="am-tab-link" data-tab="fattrici">Fattrici</a></li>
                </ul>
            </ul>
        </div>
        <div class="am-content">
            <div id="am-tab-bacheca" class="am-tab active">
                <?php include plugin_dir_path(__FILE__) . 'bacheca.php'; ?>
            </div>
            <div id="am-tab-informazioni" class="am-tab">
                <?php include plugin_dir_path(__FILE__) . 'informazioni.php'; ?>
            </div>
            <div id="am-tab-puledri" class="am-tab">
                <?php include plugin_dir_path(__FILE__) . 'puledri.php'; ?>
            </div>
            <div id="am-tab-stalloni" class="am-tab">
                <?php include plugin_dir_path(__FILE__) . 'stalloni.php'; ?>
            </div>
            <div id="am-tab-fattrici" class="am-tab">
                <?php include plugin_dir_path(__FILE__) . 'fattrici.php'; ?>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('allevatore_dashboard', 'am_allevatore_dashboard');


// Non più dentro lo shortcode
add_action('wp_enqueue_scripts', 'am_enqueue_dashboard_assets');

function am_enqueue_dashboard_assets() {
    if ( is_page('dashboard-allevatori') ) {
        // URL del plugin principale
        $plugin_url = plugin_dir_url(dirname(__FILE__, 2));

        wp_enqueue_style('am-dashboard-css', $plugin_url . 'assets/css/dashboard-allevatori.css');
        wp_enqueue_script('am-dashboard-js', $plugin_url . 'assets/js/dashboard-allevatori.js', ['jquery'], false, true);
    }
}
