<?php

class eTranslate_Metabox {
	protected $metabox_config = array();
	protected $tblname = 'etranslate_jobs';
	protected $separator = '<hr class="BREAKLINE#$123" />';	

	function __construct() {
		// adding the box
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );

		// adding the ajax hook
		add_action( 'wp_ajax_etranslate_translate', array( &$this, 'action_etranslate_translate' ) );
		add_action( 'wp_ajax_etranslate_translate_status', array( &$this, 'action_etranslate_translate_status' ) );
	}

	function action_etranslate_translate() {
		$username = trim(get_option('etranslate_username'));
		$password = trim(get_option('etranslate_password'));
		$application = trim(get_option('etranslate_application'));
		$institution = trim(get_option('etranslate_institution'));
		global $wpdb;
		$this->create_plugin_database_table();
		$strings = $_POST['to_translate'];
		$source_lang = $_POST['source_lang'];
		$target_lang = $_POST['target_lang'];
		$strings = $_POST['to_translate'];
		foreach( $strings as $key => $string ) {
			$strings[$key] = stripslashes( $string );
		}
		$finalString = $strings['post_title'] . $this->separator . $strings['post_excerpt'] . $this->separator . $strings['post_content'];
		$base64String = base64_encode($finalString);
		$base64ToTranslate = array(
			"content" => $base64String,
			"format" => "html",
			"filename" => "translateMe"
		);

		$id = uniqid();
		$error_callback = get_rest_url() . 'etranslation/v1/error_callback';
		$destination = get_rest_url() . 'etranslation/v1/destination/' . $id;
 
    $caller_information = array(
            'application' => $application,
			'username' => $username,
			'institution' => $institution
        );
 
    $translationRequest= array(
			"documentToTranslateBase64" => $base64ToTranslate,
            'sourceLanguage' => $source_lang,
            'targetLanguages' => array(
                $target_lang
            ),
            'errorCallback' => $error_callback,
            'callerInformation' => $caller_information,
			'destinations' =>  array(
				"httpDestinations" => array($destination)
			)
        );
 
		$post = json_encode($translationRequest);
		$client = curl_init();
		
		curl_setopt($client, CURLOPT_URL, "https://webgate.ec.europa.eu/etranslation/si/translate");
		curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($client, CURLOPT_POST, 1);
		curl_setopt($client, CURLOPT_POSTFIELDS, $post);
		curl_setopt($client, CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
		curl_setopt($client, CURLOPT_USERPWD, $application . ":" . $password);
		curl_setopt($client, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($client, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($client, CURLOPT_TIMEOUT, 30);
	
		curl_setopt($client, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Content-Length: ' . strlen($post)
		));
	
		$response = curl_exec($client);
		$wp_track_table = $wpdb->prefix . $this->tblname;
		$wpdb->insert( 
			$wp_track_table, 
			array( 
				'id' => $id
			)
		);
		wp_send_json_success(array(
			"id" => $id,
			"response" => $response,
			"destination" => $destination
		));
	}

	function action_etranslate_translate_status() {
		global $wpdb;
		$id = $_POST['id'];
		$wp_track_table = $wpdb->prefix . $this->tblname;
		$row = $wpdb->get_row( "SELECT * FROM $wp_track_table WHERE id = '$id'" );
		$translation = null;
		$decoded = base64_decode($row->body);
		if ($row->status == 'DONE') {
			$rawTranslations = explode($this->separator, $decoded);
			$translation = array(
				'post_title' => $rawTranslations[0],
				'post_excerpt' => $rawTranslations[1],
				'post_content' => $rawTranslations[2]
			);
			$wpdb->delete(
				$wp_track_table,
				array(
					'id' => $id
				),
				array(
					'%s'
				)
			);
		}
		$returnData = array(
			"status" => $row->status,
			"translation" => $translation,
			"external_reference" => $row->external_reference,
			"body" => $decoded
		);
		wp_send_json_success($returnData);
	}

	function create_plugin_database_table()
	{
		global $wpdb;
		$wp_track_table = $wpdb->prefix . $this->tblname;

		#Check to see if the table exists already, if not, then create it
		if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) 
		{
			$sql = "CREATE TABLE `". $wp_track_table . "` ( ";
			$sql .= "  `id`  VARCHAR(13) NOT NULL, ";
			$sql .= "  `status`  ENUM('TRANSLATING','DONE','ERROR') NOT NULL DEFAULT 'TRANSLATING', ";
			$sql .= "  `body`  TEXT NULL DEFAULT NULL, ";
			$sql .= "  PRIMARY KEY (`id`) "; 
			$sql .= ") COLLATE='utf8mb4_unicode_520_ci'; ";
			require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
			$result = dbDelta($sql);
		}
	}

	public function add_meta_box() {
		$post_types = eTranslateConfiguration::getMetaBoxPostTypes();
		add_meta_box(
			'etranslate_metabox',
			__( 'eTranslation', 'etranslate' ),
			array( &$this, 'output' ),
			$post_types,
			eTranslateConfiguration::getMetaBoxContext(),
			eTranslateConfiguration::getMetaBoxPriority()
		);
	}

	public function output() {
		$html = '';
		$html = '
		<form id="etranslate_admin_translation" name="etranslate_admin_translation" method="POST">';
		$html .= etranslate_language_selector( 'source', 'etranslate_source_lang', false );
		$html .= '<br />' . __( 'Translating to', 'etranslate' ) . '<br /> ';
		$html .= etranslate_language_selector( 'target', 'etranslate_target_lang', get_option( 'etranslate_default_locale' ) );
		$html .= '
			<span id="etranslate_error" class="error" style="display: none;"></span>
			<span id="etranslate_spinner" class="spinner"></span>
		';

		$html .= wp_nonce_field( 'permission_to_translate', 'etranslate_nonce', true, false );
		$html .= '<br />
			<input style="margin-top: 16px;" id="etranslate_translate" name="etranslate_translate" type="button" class="button button-primary button-large" value="' . __( 'Translate' , 'etranslate' ) . '"></span>';

		$default_behaviour = eTranslateConfiguration::getMetaBoxDefaultBehaviour();
		$default_metabox_behaviours = eTranslateConfiguration::DefaultsMetaboxBehaviours();

		if( !eTranslateConfiguration::usingMultilingualPlugins() ) {
			$default_behaviour = 'replace';
		}
		if( !$default_behaviour ) {
			$default_behaviour = 'replace';
		}
		$html .= '
			<hr />';
		foreach( $default_metabox_behaviours as $value => $label ) {
			$html.= '
			<span style="visibility: hidden;">
				<input type="radio" name="etranslate_replace" value="'. $value .'"';

			if( $value == $default_behaviour ) {
				$html .= ' checked="checked"';
			}
			if( $value == 'append' && !eTranslateConfiguration::usingMultilingualPlugins() ) {
				$html .= ' disabled="disabled"';
			}
			$html .= '>
				<label for="etranslate_replace">' . $label . '</label>
			</span>';
		}


		$html .= '
		</form>
		<div class="hidden_warning" style="display: none;">' . __( 'Gutenberg is not compatible with eTranslate yet. Please use Classic Editor', 'etranslate' ) . '</div>';

		$html = apply_filters( 'etranslate_metabox_html', $html);

		echo $html;
	}
}