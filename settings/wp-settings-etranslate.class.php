<?php

if( !class_exists( 'WP_Settings\WP_Settings' ) ) {
	require( dirname( __FILE__ ) . '/wp-settings.class.php' );
}

class WP_Settings_eTranslate extends WP_Settings\WP_Settings {
	public $plugin_id = 'etranslate';
	public $option_page = 'etranslation_settings';
	public $menu_order = 15;
	public $parent_menu = 'options-general.php';
	public $defaultSettingsTab = 'ids';
	public $extendedActions = array();

	static function getPageTitle() {
		return __( 'eTranslation settings', 'etranslate' );
	}

	static function getMenuTitle() {
		return __( 'eTranslation', 'etranslate' );
	}

	function on_save() {
		update_option( 'etranslate_plugin_installed', 1 );
	}

	function getSettingsStructure() {
		$settings = array(
			'ids'			=> array(
				'title'			=> __( 'Credentials', 'etranslate' ),
				'sections'		=> array()
			),
			'integration'	=> array(
				'title'			=> __( 'Integration', 'etranslate' ),
				'sections'		=> array()
			)
		);

		$settings['ids']['sections']['identifiants'] = array(
			'title'			=> __( 'eTranslation credentials', 'etranslate' ),
			'fields'	=> array(

				array(
					'id'			=> 'username',
					'title'			=> __( 'Username', 'etranslate' ),
					'type'			=> 'text',
					'css'			=> 'width: 20em;',
					'description'	=> __( 'Please enter username', 'etranslate' )
				),
				array(
					'id'			=> 'password',
					'title'			=> __( 'Password', 'etranslate' ),
					'type'			=> 'password',
					'css'			=> 'width: 20em;',
					'description'	=> __( 'Please enter password', 'etranslate' )
				),
				array(
					'id'			=> 'application',
					'title'			=> __( 'Application', 'etranslate' ),
					'type'			=> 'text',
					'css'			=> 'width: 20em;',
					'description'	=> __( 'Please enter application', 'etranslate' )
				),
				array(
					'id'			=> 'institution',
					'title'			=> __( 'Institution', 'etranslate' ),
					'type'			=> 'text',
					'css'			=> 'width: 20em;',
					'description'	=> __( 'Please enter institution', 'etranslate' )
				),
			)
		);

		$wp_post_types = get_post_types( array( 'public'	=> true, 'show_ui' => true ), 'objects' );
		$post_types = array();
		if( $wp_post_types ) foreach( $wp_post_types as $post_type => $WP_Post_Type ) {
			$post_types[$post_type] = $WP_Post_Type->label;
		}
		unset( $post_types['product'] );

		$settings['integration']['sections']['metabox'] = array(
			'title'			=> __( 'Metabox', 'etranslate' ),
			'fields'	=> array(
				array(
					'id'			=> 'metabox_post_types',
					'title'			=> __( 'Metabox should be displayed on:', 'etranslate' ),
					'type'			=> 'multiselect',
					'options'		=> $post_types,
					'default'		=> array( 'post', 'page', 'attachment' ),
					'description'	=> __( 'Select which post types you want the metabox to appear on', 'etranslate' ),
 				),
 				array(
					'id'			=> 'metabox_context',
					'title'			=> __( 'Metabox context', 'etranslate' ),
					'type'			=> 'select',
					'options'		=> array(
						'normal' 		=> 'normal',
						 'side' 		=> 'side',
						 'advanced'		=> 'advanced'
					),
					'default'		=> 'side',
					'description'	=> __('<a href="https://developer.wordpress.org/reference/functions/add_meta_box/">See add_meta_box function reference</a>','etranslate' ),
 				),
 				array(
					'id'			=> 'metabox_priority',
					'title'			=> __( 'Metabox priority', 'etranslate' ),
					'type'			=> 'select',
					'options'		=> array(
						'high'			=> 'high',
						'low'			=> 'low'
					),
					'default'		=> 'high',
					'description'	=> '',
 				),
			)
		);
		return apply_filters( 'etranslate_admin_configuration', $settings );
	}
}