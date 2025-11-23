<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$profilo_url = home_url('/allevatore/' . sanitize_title($current_user->user_login) . '/');
$logout_url = wp_logout_url( home_url() ); // reindirizza alla homepage dopo il logout
?>
<h2>Bacheca</h2>

<p>Benvenuto, <?php echo esc_html($current_user->first_name ?: $current_user->display_name); ?>!  
Qui in futuro mostreremo statistiche e riepiloghi.</p>

<div style="margin-top: 20px;">
    <a href="<?php echo esc_url($profilo_url); ?>" target="_blank" class="am-btn-view-profile">
        👁️ Visualizza la tua pagina pubblica
    </a>
</div>

<div style="margin-top: 10px;">
    <a href="<?php echo esc_url($logout_url); ?>" class="am-btn-logout">
        🔒 Logout
    </a>
</div>
