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


	static function geti18nDomain() {
		return 'etranslate';
	}

	static function getPageTitle() {
		return __( 'eTranslation settings', 'etranslate' );
	}

	static function getMenuTitle() {
		return __( 'eTranslation', 'etranslate' );
	}

	function on_save() {
		update_option( 'etranslate_plugin_installed', 1 );

		if( $_REQUEST['tab'] == 'cron_jobs' ) {
		}
	}

	function maybe_print_notices() {
		$fully_configured = etranslate_is_plugin_fully_configured();
		if( $fully_configured !== true ) {
			$class = 'notice notice-error';

			$messages = array();

			if( is_wp_error( $fully_configured ) ) {
				foreach( $fully_configured->get_error_codes() as $error_code ) {
					foreach( $fully_configured->get_error_messages( $error_code ) as $error_message ) {
						$messages[] = sprintf(
							__( '<li><a href="%s">%s</a></li>', 'etranslate' ),
							admin_url( '/admin.php?page=etranslation_settings&tab=' . $error_code ),
							$error_message
						);
					}
				}
			}
			$message = sprintf(
				__( 'The eTranslation plugin is not fully configured yet: <ul>%s</ul>', 'etranslate' ),
				implode( "\n", $messages )
			);
			if( count( $messages ) ) {
				$message .= sprintf(
					__( '<a href="%s">Please provide required informations</a>', 'etranslate' ),
					admin_url( '/admin.php?page=etranslation_settings' )
				);
			}

			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), ( $message ) );
		}
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

		/** IDS **/

		$servers = array();
		$possibilities = eTranslateConfiguration::geteTranslateAPIServers();
		foreach( $possibilities as $key => $data ) {
			$servers[$key] = $data['description'];
		}

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


		if( eTranslateConfiguration::getAPIKey() ) {
			$settings['ids']['footer']['actions'] = array( 'etranslate_show_usage' );
		/** END IDS **/

		/** TRANSLATION **/
		$formality_levels = array(
			'default'	=>"(default)",
			'more'		=> 'for a more formal language',
			'less'		=> 'for a more informal language',
		);

		$settings['translation']['sections']['languages'] = array(
			'title'		=> __( 'Translation', 'etranslate' ),
			'fields'	=> array(
					array(
						'id'			=> 'default_language',
						'title'			=> __( 'Default target language', 'etranslate' ),
						'type'			=> 'select',
						'options'		=> eTranslateConfiguration::DefaultsISOCodes(),
						'default'		=> substr( get_locale(), 0, 2 ),
						'css'			=> 'width: 15rem; ',
					),
					array(
						'id'			=> 'displayed_languages',
						'title'			=> __( 'Displayed languages', 'etranslate' ),
						'type'			=> 'multiselect',
						'options'		=> eTranslateConfiguration::DefaultsISOCodes(),
						'default'		=> substr( get_locale(), 0, 2 ),
						'css'			=> 'width: 15rem; height: 20rem;',
					),
					array(
						'id'			=> 'default_formality',
						'title'			=> __( 'Formality level', 'etranslate' ),
						'type'			=> 'select',
						'options'		=> $formality_levels,
						'default'		=> 'default',
					),
			)
		);
		}

		/** END TRANSLATION **/

		/** INTEGRATION **/
		$wp_post_types = get_post_types( array( 'public'	=> true, 'show_ui' => true ), 'objects' );
		$post_types = array();
		if( $wp_post_types ) foreach( $wp_post_types as $post_type => $WP_Post_Type ) {
			$post_types[$post_type] = $WP_Post_Type->label;
		}
		unset( $post_types['product'] );

		$default_metabox_behaviours = eTranslateConfiguration::DefaultsMetaboxBehaviours();

		$settings['integration']['sections']['metabox'] = array(
			'title'			=> __( 'Metabox', 'etranslate' ),
			'fields'	=> array(
				array(
					'id'			=> 'metabox_post_types',
					'title'			=> __( 'Metabox should be displayed on:', 'etranslate' ),
					'type'			=> 'multiselect',
					'options'		=> $post_types,
					'default'		=> array( 'post', 'page' ),
					'description'	=> __( 'Select which post types you want the metabox to appear on', 'etranslate' ),
 				),
				array(
					'id'			=> 'metabox_behaviour',
					'title'			=> __( 'Metabox behaviour', 'etranslate' ),
					'type'			=> 'radio',
					'values'		=> $default_metabox_behaviours,
					'default'		=> 'replace',
					'description'	=> __( 'For content to be appended, you need to use a supported multilingual plugin', 'etranslate' ),
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