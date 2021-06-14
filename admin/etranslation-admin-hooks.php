<?php
add_action('init', 'etranslation_init_admin');
function etranslation_init_admin() {
    if (!is_admin()) {
        return;
    }

    if (eTranslationConfiguration::isPluginInstalled() === false) {
        etranslation_install_plugin();
    }

    global $WP_Settings_eTranslation;

    if (!$WP_Settings_eTranslation) {
        $WP_Settings_eTranslation = new WP_Settings_eTranslation();
    }

    global $eTranslation_Metabox;
    $eTranslation_Metabox = new eTranslation_Metabox();
}

add_action('admin_enqueue_scripts', 'etranslation_load_admin_javascript');
function etranslation_load_admin_javascript($hook) {
    if ($hook == 'settings_page_etranslation_settings') {
        wp_enqueue_style('etranslation_admin', ETRANSLATION_URL . '/assets/etranslation-admin.css', array() , ETRANSLATION_VERSION);
    }
    if ($hook == 'settings_page_etranslation_settings' || $hook == 'post.php') {
        wp_enqueue_script('etranslation_admin', trailingslashit(ETRANSLATION_URL) . 'assets/etranslation-metabox.js');
        wp_localize_script('etranslation_admin', 'WPURLS', array(
            'restUrl' => get_rest_url()
        ));
    }
}

