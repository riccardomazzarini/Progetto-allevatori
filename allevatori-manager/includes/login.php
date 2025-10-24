<?php
if (!defined('ABSPATH')) exit;

// Avvia la sessione
if (!session_id()) session_start();

// FORZA OUTPUT BUFFERING per evitare errori redirect
ob_start();

/* ---------- SHORTCODE LOGIN ---------- */
function am_login_form_shortcode() {
    global $wpdb;

    // Recupera stili dal backend
    $button_color        = get_option('am_button_color', '#0055a5');
    $button_hover_color  = get_option('am_button_hover_color', '#0056d6');
    $button_text_color   = get_option('am_button_text_color', '#ffffff');
    $button_padding      = get_option('am_button_padding', '10px 20px');
    $button_border_radius= get_option('am_button_border_radius', '6px');
    $form_bg             = get_option('am_form_bg', '#f9f9f9');
    $input_padding       = get_option('am_input_padding', '10px');
    $input_margin        = get_option('am_input_margin', '15px 0');
    $input_border_color  = get_option('am_input_border_color', '#cccccc');
    $input_border_radius = get_option('am_input_border_radius', '6px');
    $input_font_size     = get_option('am_input_font_size', '14px');
    $label_font_size     = get_option('am_label_font_size', '13px');

    // Variabile per log debug
    $login_log = '';

    // Gestione login
    if ($_POST && isset($_POST['am_login'])) {
        $login_input = sanitize_text_field($_POST['email']); 
        $password    = $_POST['password'];
        $user        = false;

        $login_log .= "Tentativo login con input: [$login_input]<br>";

        // Primo tentativo: cerca codice ANACT nel campo meta
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='codice_anact' AND meta_value=%s LIMIT 1",
            $login_input
        ));

        if ($user_id) {
            $user = get_user_by('id', $user_id);
            $login_log .= "Trovato utente con codice_anact: ID $user_id<br>";
        } else {
            // Se non trovato, prova email o username
            $user = get_user_by('email', $login_input);
            if (!$user) $user = get_user_by('login', $login_input);
        }

        // Controlla password
        if ($user && wp_check_password($password, $user->user_pass, $user->ID)) {
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);

            $login_log .= "Login riuscito per utente ID " . $user->ID . "<br>";

            // Redirect alla dashboard
            wp_safe_redirect(site_url('/dashboard-allevatori'));
            exit;
        } else {
            $login_log .= "Errore login: credenziali non valide.<br>";
        }
    }

    // Output form
    ob_start(); ?>
    <style>
    .am-form {
        background: <?php echo esc_attr($form_bg); ?>;
        padding: 20px;
        max-width: 500px;
        margin: 20px auto;
        border-radius: 8px;
    }
    .am-form label {
        display: block;
        font-size: <?php echo esc_attr($label_font_size); ?>;
        margin-bottom: <?php echo esc_attr($input_margin); ?>;
    }
    .am-form input {
        width: 100%;
        padding: <?php echo esc_attr($input_padding); ?>;
        margin-bottom: <?php echo esc_attr($input_margin); ?>;
        font-size: <?php echo esc_attr($input_font_size); ?>;
        border: 1px solid <?php echo esc_attr($input_border_color); ?>;
        border-radius: <?php echo esc_attr($input_border_radius); ?>;
        box-sizing: border-box;
    }
    .am-form button {
        background: <?php echo esc_attr($button_color); ?>;
        color: <?php echo esc_attr($button_text_color); ?>;
        padding: <?php echo esc_attr($button_padding); ?>;
        border: none;
        border-radius: <?php echo esc_attr($button_border_radius); ?>;
        cursor: pointer;
    }
    .am-form button:hover {
        background: <?php echo esc_attr($button_hover_color); ?>;
    }
    .am-error { color: red; margin-bottom: 15px; }
    .am-log { color: blue; margin-bottom: 15px; font-size: 12px; }
    </style>

    <?php
    // Mostra log debug
    if (!empty($login_log)) {
        echo '<div class="am-log">' . $login_log . '</div>';
    }
    ?>

    <form method="post" class="am-form">
        <label>
            Email, Username o Codice ANACT:
            <input type="text" name="email" placeholder="Email, Username o Codice ANACT" required>
        </label>
        <label>
            Password:
            <input type="password" name="password" placeholder="Password" required>
        </label>
        <button type="submit" name="am_login">Accedi</button>
        <div class="am-lostpass">
            <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">Hai dimenticato la password?</a>
        </div>
    </form>
    <?php

    return ob_get_clean();
}
add_shortcode('allevatore_login', 'am_login_form_shortcode');
