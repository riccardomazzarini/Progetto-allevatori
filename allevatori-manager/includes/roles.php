<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Aggiunge ruolo allevatore e capability personalizzate per CPT "cavallo"
 */
function am_add_roles() {
    // Rimuovo e ricreo il ruolo allevatore per sicurezza
    remove_role('allevatore');

    add_role('allevatore', 'Allevatore', [
        'read'                   => true,
        'upload_files'           => true,
        'edit_posts'             => true,
        'delete_posts'           => true,
        'publish_posts'          => true,
        'edit_published_posts'   => true,
        'delete_published_posts' => true,

        // Permessi personalizzati per il CPT "cavallo"
        'read_cavallo'             => true,
        'read_private_cavalli'     => true,
        'edit_cavallo'             => true,
        'edit_cavalli'             => true,
        'edit_others_cavalli'      => true,
        'edit_published_cavalli'   => true,
        'publish_cavalli'          => true,
        'delete_cavallo'           => true,
        'delete_cavalli'           => true,
        'delete_others_cavalli'    => true,
        'delete_published_cavalli' => true,
    ]);
}

/**
 * Aggiunge le capability admin **dopo** la registrazione del CPT
 */
function am_add_admin_caps() {
    $admin = get_role('administrator');
    if ($admin) {
        $caps = [
            'read_cavallo',
            'read_private_cavalli',
            'edit_cavallo',
            'edit_cavalli',
            'edit_others_cavalli',
            'edit_published_cavalli',
            'publish_cavalli',
            'delete_cavallo',
            'delete_cavalli',
            'delete_others_cavalli',
            'delete_published_cavalli',
        ];
        foreach($caps as $cap){
            $admin->add_cap($cap);
        }
    }
}

/**
 * Hook: aggiunge ruolo allevatore subito (priorità default)
 */
add_action('init', 'am_add_roles');

/**
 * Hook: aggiunge capability admin **dopo CPT**, priorità 20
 */
add_action('init', 'am_add_admin_caps', 20);

/**
 * Rimuove ruolo allevatore (utile se vuoi fare reset)
 */
function am_remove_roles() {
    remove_role('allevatore');
}
