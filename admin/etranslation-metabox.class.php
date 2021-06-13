<?php

class eTranslation_Metabox {
	protected $metabox_config = array();
	protected $separator = '<hr class="BREAKLINE#$123" />';	

	function __construct() {
		// adding the box
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );

		// adding the ajax hook
		add_action( 'wp_ajax_etranslation_translate', array( &$this, 'action_etranslation_translate' ) );
		add_action( 'wp_ajax_etranslation_translate_status', array( &$this, 'action_etranslation_translate_status' ) );
	}

	function action_etranslation_translate() {
		$username = trim(get_option('etranslation_username'));
		$password = trim(get_option('etranslation_password'));
		$application = trim(get_option('etranslation_application'));
		$institution = trim(get_option('etranslation_institution'));
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
		$wp_track_table = $wpdb->prefix . ETRANSLATION_TABLE;
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

	function action_etranslation_translate_status() {
		global $wpdb;
		$id = $_POST['id'];
		$wp_track_table = $wpdb->prefix . ETRANSLATION_TABLE;
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

	public function add_meta_box() {
		$post_types = eTranslationConfiguration::getMetaBoxPostTypes();
		add_meta_box(
			'etranslation_metabox',
			__( 'eTranslation', 'etranslation' ),
			array( &$this, 'output' ),
			$post_types,
			eTranslationConfiguration::getMetaBoxContext(),
			eTranslationConfiguration::getMetaBoxPriority()
		);
	}

	public function output() {
		$html = '';
		$html = '
		<form id="etranslation_admin_translation" name="etranslation_admin_translation" method="POST">';
		$html .= $this->etranslation_language_selector( 'source', 'etranslation_source_lang', false );
		$html .= '<br />' . __( 'Translating to', 'etranslation' ) . '<br /> ';
		$html .= $this->etranslation_language_selector( 'target', 'etranslation_target_lang', get_option( 'etranslation_default_locale' ) );
		$html .= '
			<span id="etranslation_error" class="error" style="display: none;"></span>
			<span id="etranslation_spinner" class="spinner"></span>
		';

		$html .= wp_nonce_field( 'permission_to_translate', 'etranslation_nonce', true, false );
		$html .= '<br />
			<input style="margin-top: 16px;" id="etranslation_translate" name="etranslation_translate" type="button" class="button button-primary button-large" value="' . __( 'Translate' , 'etranslation' ) . '"></span>';

		$html .= '
			<hr />';

		$html .= '
		</form>
		<div class="hidden_warning" style="display: none;">' . __( 'Gutenberg is not compatible with eTranslation yet. Please use Classic Editor', 'etranslation' ) . '</div>';

		$html = apply_filters( 'etranslation_metabox_html', $html);

		echo $html;
	}

	protected function etranslation_language_selector($type = 'target', $id = 'etranslation_language_selector', $selected = false) {
		$html = '';
		$html .= "\n" . '<select style="margin-top: 8px; margin-bottom: 8px;" id="' . $id . '" name="' . $id . '">';
	
		$EU_OFFICIAL_LANGS = array(
			"bg" => "Bulgarian",
			"hr" => "Croatian",
			"cs" => "Czech",
			"da" => "Danish",
			"nl" => "Dutch",
			"en" => "English",
			"et" => "Estonian",
			"fi" => "Finnish",
			"fr" => "French",
			"de" => "German",
			"el" => "Greek",
			"hu" => "Hungarian",
			"ga" => "Irish",
			"it" => "Italian",
			"lv" => "Latvian",
			"lt" => "Lithuanian",
			"mt" => "Maltese",
			"pl" => "Polish",
			"pt" => "Portuguese",
			"ro" => "Romanian",
			"sk" => "Slovak",
			"sl" => "Slovene",
			"es" => "Spanish",
			"sv" => "Swedish",
			# unoficial but supported languages
			"is" => "Islandic",
			"nb" => "Norwegian (Bokmål)",
			# non-European languages
			"ru" => "Russian",
			"zh" => "Chinese",
			"ja" => "Japanese",
			"ar" => "Arabic"
		);
	
		foreach( $EU_OFFICIAL_LANGS as $lang_id => $label ) {
			$html .= '
			<option value="' . $lang_id .'"';
	
			if ($type == 'source' && $lang_id == 'en') {
				$html .= ' selected="selected"';
			}
			if ($type == 'target' && $lang_id == 'de') {
				$html .= ' selected="selected"';
			}
			$html .= '>' . $label. '</option>';
		}
	
		$html .="\n</select>";
	
		return $html;
	}
}