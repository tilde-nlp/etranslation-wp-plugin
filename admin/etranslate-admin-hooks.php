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
	 wp_localize_script( 'etranslate_admin', 'eTranslateStrings', etranslate_get_localized_strings() );
	 wp_localize_script('etranslate_admin', 'WPURLS', array( 'restUrl' => get_rest_url() ));
 }
}

function etranslate_get_localized_strings() {
	$strings = array();
	$strings = apply_filters( 'etranslate_localized_strings', $strings );

	return $strings;
}


add_action('admin_footer', 'etranslate_admin_footer');
function etranslate_admin_footer() {

	if( get_current_screen()->base != 'settings_page_etranslation_settings' ) {
		return;
	}
	?>
	<script>
		jQuery(document).ready(function() {

			var behaviours_notice = jQuery('input[name="etranslate_metabox_behaviour"]').parent().find('p.description');
			var usingMultilingualPlugins = <?php echo eTranslateConfiguration::usingMultilingualPlugins() ? 1 : 0; ?>;
			if( !usingMultilingualPlugins ) {

				jQuery('#etranslate_metabox_behaviour_append').attr('disabled','disabled');
				jQuery(behaviours_notice).show();
			}
			else {
				jQuery(behaviours_notice).hide();

			}

		});
	</script>

	<?php 
}

function etranslate_modify_list_row_actions( $actions, $post ) {
 // Check for your post type.
 $post_types = eTranslateConfiguration::getMetaBoxPostTypes();

 if ( in_array( $post->post_type, $post_types )) {
 // Build your links URL.
 $url = admin_url( 'admin.php?page=mycpt_page&post=' . $post->ID );

 // Maybe put in some extra arguments based on the post status.
 $edit_link = add_query_arg( array( 'action' => 'edit' ), $url );

 // The default $actions passed has the Edit, Quick-edit and Trash links.
 $actions['translate'] = sprintf(
 	'<a href="%1$s">%2$s</a>',
 esc_url( $edit_link ),
 'Test'
 );
 	}
 plouf( $actions );
 return $actions;
}
