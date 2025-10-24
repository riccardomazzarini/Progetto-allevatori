<?php
$current_user = wp_get_current_user();
$fields = get_option('am_form_fields', []);
$message = ''; // Puoi passarlo dal file principale se vuoi notifiche aggiornamento
?>
<h2>Informazioni Account</h2>
<?php echo $message; ?>
<form method="post" class="am-form">
    <?php foreach ($fields as $field): ?>
        <label>
            <span><?php echo esc_html($field['label']); ?></span>
            <input type="<?php echo esc_attr($field['type']); ?>"
                   name="<?php echo esc_attr($field['name']); ?>"
                   value="<?php echo esc_attr(get_user_meta($current_user->ID, $field['name'], true)); ?>"
                   placeholder="<?php echo esc_attr($field['label']); ?>"
                   <?php echo !empty($field['required']) ? 'required' : ''; ?>>
        </label>
    <?php endforeach; ?>
    <button type="submit" name="am_update_profile">Aggiorna Dati</button>
</form>
