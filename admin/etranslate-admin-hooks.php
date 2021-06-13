<?php

add_action('init', 'etranslate_init_admin');
function etranslate_init_admin() {
	if (!is_admin()) {
		return;
	}

	if (eTranslateConfiguration::isPluginInstalled() === false) {
 		etranslate_install_plugin();
 	}

	global $WP_Settings_eTranslate;

	if( !$WP_Settings_eTranslate ) {
		$WP_Settings_eTranslate = new WP_Settings_eTranslate();
	}

	global $eTranslate_Metabox;
	$eTranslate_Metabox = new eTranslate_Metabox();
}

add_action( 'admin_enqueue_scripts', 'etranslate_load_admin_javascript' );
function etranslate_load_admin_javascript( $hook ) {
	if( $hook == 'settings_page_etranslation_settings' ) {
		wp_enqueue_style( 'etranslate_admin', ETRANSLATE_URL . '/assets/etranslate-admin.css', array(), ETRANSLATE_VERSION );
 	}
 if( $hook == 'settings_page_etranslation_settings' || $hook == 'post.php' ) {
	 wp_enqueue_script( 'etranslate_admin', trailingslashit( ETRANSLATE_URL ) . 'assets/etranslate-metabox.js' );
	 wp_localize_script('etranslate_admin', 'WPURLS', array( 'restUrl' => get_rest_url() ));
 }
}
