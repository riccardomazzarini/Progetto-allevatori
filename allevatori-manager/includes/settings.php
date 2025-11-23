<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// ---- MENU IMPOSTAZIONI ----
function am_settings_menu() {
    // Pagina impostazioni grafiche
    add_submenu_page(
        'am-allevatori',
        'Impostazioni grafiche Allevatori',
        'Impostazioni grafiche',
        'manage_options',
        'am-graphics',
        'am_settings_page'
    );

    // Pagina impostazioni campi
    add_submenu_page(
        'am-allevatori',
        'Campi modulo Allevatori',
        'Campi modulo',
        'manage_options',
        'am-fields',
        'am_fields_page'
    );
}
add_action('admin_menu', 'am_settings_menu');


// ---- PAGINA IMPOSTAZIONI GRAFICHE ----
function am_settings_page() {
    echo '<div class="wrap"><h1>Impostazioni Grafiche Modulo Allevatori</h1>';
    echo '<p>Gestisci le opzioni grafiche del modulo di registrazione.</p>';

    echo '<form method="post" action="options.php">';
    settings_fields('am_style_settings');
    do_settings_sections('am_style_settings');

    echo '<table class="form-table">';
    echo '<tr><th scope="row">Colore pulsante</th><td><input type="text" name="am_button_color" value="'.esc_attr(get_option('am_button_color','#0055a5')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Colore hover pulsante</th><td><input type="text" name="am_button_hover_color" value="'.esc_attr(get_option('am_button_hover_color','#0056d6')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Colore testo pulsante</th><td><input type="text" name="am_button_text_color" value="'.esc_attr(get_option('am_button_text_color','#ffffff')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Padding pulsante</th><td><input type="text" name="am_button_padding" value="'.esc_attr(get_option('am_button_padding','10px 20px')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Border radius pulsante</th><td><input type="text" name="am_button_border_radius" value="'.esc_attr(get_option('am_button_border_radius','6px')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Sfondo form</th><td><input type="text" name="am_form_bg" value="'.esc_attr(get_option('am_form_bg','#f9f9f9')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Padding campi input</th><td><input type="text" name="am_input_padding" value="'.esc_attr(get_option('am_input_padding','10px')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Margine tra i campi</th><td><input type="text" name="am_input_margin" value="'.esc_attr(get_option('am_input_margin','15px 0')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Colore bordo input</th><td><input type="text" name="am_input_border_color" value="'.esc_attr(get_option('am_input_border_color','#cccccc')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Border radius input</th><td><input type="text" name="am_input_border_radius" value="'.esc_attr(get_option('am_input_border_radius','6px')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Font size input</th><td><input type="text" name="am_input_font_size" value="'.esc_attr(get_option('am_input_font_size','14px')).'" class="regular-text"></td></tr>';
    echo '<tr><th scope="row">Font size label</th><td><input type="text" name="am_label_font_size" value="'.esc_attr(get_option('am_label_font_size','13px')).'" class="regular-text"></td></tr>';
	echo '<tr><th scope="row">Colore pulsante leggero</th><td><input type="text" name="am_button_light_color" value="'.esc_attr(get_option('am_button_light_color','#e0e0e0')).'" class="regular-text"></td></tr>';
echo '<tr><th scope="row">Colore hover pulsante leggero</th><td><input type="text" name="am_button_light_hover_color" value="'.esc_attr(get_option('am_button_light_hover_color','#d0d0d0')).'" class="regular-text"></td></tr>';
echo '<tr><th scope="row">Colore testo pulsante leggero</th><td><input type="text" name="am_button_light_text_color" value="'.esc_attr(get_option('am_button_light_text_color','#333333')).'" class="regular-text"></td></tr>';
echo '<tr><th scope="row">Padding pulsante leggero</th><td><input type="text" name="am_button_light_padding" value="'.esc_attr(get_option('am_button_light_padding','6px 14px')).'" class="regular-text"></td></tr>';
echo '<tr><th scope="row">Border radius pulsante leggero</th><td><input type="text" name="am_button_light_border_radius" value="'.esc_attr(get_option('am_button_light_border_radius','4px')).'" class="regular-text"></td></tr>';

    echo '</table>';

    submit_button();
    echo '</form>';
    echo '</div>';
}


// ---- PAGINA GESTIONE CAMPI ----
function am_fields_page() {
    echo '<div class="wrap"><h1>Campi del modulo di registrazione</h1>';
    echo '<p>Aggiungi, rimuovi o modifica i campi del modulo di registrazione allevatori.</p>';

    echo '<form method="post" action="options.php">';
    settings_fields('am_form_fields_group');

    $fields = get_option('am_form_fields', [
        ['label' => 'Nome', 'name' => 'first_name', 'type' => 'text', 'required' => 1, 'margin' => '15px'],
        ['label' => 'Cognome', 'name' => 'last_name', 'type' => 'text', 'required' => 1, 'margin' => '15px'],
        ['label' => 'Allevamento', 'name' => 'allevamento', 'type' => 'text', 'required' => 1, 'margin' => '15px'],
        ['label' => 'Email', 'name' => 'email', 'type' => 'email', 'required' => 1, 'margin' => '15px'],
        ['label' => 'Telefono', 'name' => 'telefono', 'type' => 'tel', 'required' => 0, 'margin' => '15px'],
        ['label' => 'Password', 'name' => 'password', 'type' => 'password', 'required' => 1, 'margin' => '15px'],
    ]);

    echo '<table class="form-table" id="am-fields-table">';
    echo '<thead>
            <tr>
                <th>Ordina</th>
                <th>Etichetta / Testo</th>
                <th>Nome campo</th>
                <th>Tipo</th>
                <th>Obbligatorio</th>
                <th>Margin Bottom</th>
                <th>Elimina</th>
            </tr>
          </thead><tbody>';

    foreach ($fields as $index => $field) {
        echo '<tr>';
        echo '<td class="drag-handle" style="cursor: move;">☰</td>';
        echo '<td><input type="text" name="am_form_fields['.$index.'][label]" value="'.esc_attr($field['label']).'" placeholder="Etichetta o testo separatore"></td>';
        echo '<td><input type="text" name="am_form_fields['.$index.'][name]" value="'.esc_attr($field['name'] ?? '').'" placeholder="Nome campo (vuoto se separatore)"></td>';
        echo '<td>
                <select name="am_form_fields['.$index.'][type]">
                    <option value="text" '.selected($field['type'],'text',false).'>Testo</option>
                    <option value="email" '.selected($field['type'],'email',false).'>Email</option>
                    <option value="password" '.selected($field['type'],'password',false).'>Password</option>
                    <option value="tel" '.selected($field['type'],'tel',false).'>Telefono</option>
                    <option value="separator" '.selected($field['type'],'separator',false).'>Separatore</option>
                </select>
              </td>';
        echo '<td><input type="checkbox" name="am_form_fields['.$index.'][required]" value="1" '.checked($field['required'] ?? 0,1,false).'></td>';
        echo '<td><input type="text" name="am_form_fields['.$index.'][margin]" value="'.esc_attr($field['margin'] ?? '15px').'" placeholder="es: 20px"></td>';
        echo '<td><button type="button" class="remove-field button">✕</button></td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '<p><button type="button" id="add-field" class="button">Aggiungi campo</button></p>';
    echo '<p><button type="button" id="add-separator" class="button">Aggiungi separatore</button></p>';
    submit_button('Salva campi');
    echo '</form>';

    // JS per gestione campi
    echo <<<HTML
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const tbody = document.querySelector('#am-fields-table tbody');
    const form = document.querySelector('#am-fields-table').closest('form');

    // Drag & drop
    new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150
    });

    // Rinumerazione indici prima del submit
    form.addEventListener('submit', function() {
        const rows = tbody.querySelectorAll('tr');
        rows.forEach((row, index) => {
            row.querySelectorAll('input, select').forEach(input => {
                input.name = input.name.replace(/\\[\\d+\\]/, '['+index+']');
            });
        });
    });

    // Rimuovi campo
    tbody.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-field')) {
            e.target.closest('tr').remove();
        }
    });

    // Aggiungi campo normale
    document.getElementById('add-field').addEventListener('click', function(){
        var index = tbody.children.length;
        var row = document.createElement('tr');
        row.innerHTML = `
            <td class="drag-handle" style="cursor: move;">☰</td>
            <td><input type="text" name="am_form_fields[\${index}][label]" placeholder="Etichetta"></td>
            <td><input type="text" name="am_form_fields[\${index}][name]" placeholder="Nome campo"></td>
            <td>
                <select name="am_form_fields[\${index}][type]">
                    <option value="text">Testo</option>
                    <option value="email">Email</option>
                    <option value="password">Password</option>
                    <option value="tel">Telefono</option>
                    <option value="separator">Separatore</option>
                </select>
            </td>
            <td><input type="checkbox" name="am_form_fields[\${index}][required]" value="1"></td>
            <td><input type="text" name="am_form_fields[\${index}][margin]" value="15px" placeholder="es: 15px"></td>
            <td><button type="button" class="remove-field button">✕</button></td>
        `;
        tbody.appendChild(row);
    });

    // Aggiungi separatore
    document.getElementById('add-separator').addEventListener('click', function(){
        var index = tbody.children.length;
        var row = document.createElement('tr');
        row.innerHTML = `
            <td class="drag-handle" style="cursor: move;">☰</td>
            <td><input type="text" name="am_form_fields[\${index}][label]" placeholder="Testo separatore"></td>
            <td><input type="text" name="am_form_fields[\${index}][name]" value="" disabled placeholder="N/A"></td>
            <td>
                <select name="am_form_fields[\${index}][type]">
                    <option value="separator" selected>Separatore</option>
                    <option value="text">Testo</option>
                    <option value="email">Email</option>
                    <option value="password">Password</option>
                    <option value="tel">Telefono</option>
                </select>
            </td>
            <td><input type="checkbox" disabled></td>
            <td><input type="text" name="am_form_fields[\${index}][margin]" value="20px" placeholder="es: 20px"></td>
            <td><button type="button" class="remove-field button">✕</button></td>
        `;
        tbody.appendChild(row);
    });
});
</script>
HTML;

    echo '</div>';
}


// ---- REGISTRO OPZIONI ----
function am_register_options() {
    // Stili
    register_setting('am_style_settings','am_button_color');
    register_setting('am_style_settings','am_button_hover_color');
    register_setting('am_style_settings','am_button_text_color');
    register_setting('am_style_settings','am_button_padding');
    register_setting('am_style_settings','am_form_bg');
    register_setting('am_style_settings','am_input_padding');
    register_setting('am_style_settings','am_input_margin');
    register_setting('am_style_settings','am_input_border_color');
    register_setting('am_style_settings','am_input_font_size');
    register_setting('am_style_settings','am_label_font_size');
    register_setting('am_style_settings','am_input_border_radius');
    register_setting('am_style_settings','am_button_border_radius');
	register_setting('am_style_settings', 'am_button_light_color');
    register_setting('am_style_settings', 'am_button_light_hover_color');
    register_setting('am_style_settings', 'am_button_light_text_color');
    register_setting('am_style_settings', 'am_button_light_padding');
    register_setting('am_style_settings', 'am_button_light_border_radius');


    // Campi
    register_setting('am_form_fields_group','am_form_fields');
}
add_action('admin_init','am_register_options');
