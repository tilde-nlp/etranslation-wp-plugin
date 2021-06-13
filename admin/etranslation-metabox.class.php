<?php

class eTranslation_Metabox {
	protected $metabox_config = array();
	protected $separator = '<hr class="BREAKLINE#$123" />';
	protected $errorMap = array(
		-20000 => 'Source language not specified',
		-20001 => 'Invalid source language',
		-20002 => 'Target language(s) not specified',
		-20003 => 'Invalid target language(s)',
		-20004 => 'DEPRECATED',
		-20005 => 'Caller information not specified',
		-20006 => 'Missing application name',
		-20007 => 'Application not authorized to access the service',
		-20008 => 'Bad format for ftp address',
		-20009 => 'Bad format for sftp address',
		-20010 => 'Bad format for http address',
		-20011 => 'Bad format for email address',
		-20012 => 'Translation request must be text type, document path type or document base64 type and not several at a time',
		-20013 => 'Language pair not supported by the domain',
		-20014 => 'Username parameter not specified',
		-20015 => 'Extension invalid compared to the MIME type',
		-20016 => 'DEPRECATED',
		-20017 => 'Username parameter too long',
		-20018 => 'Invalid output format',
		-20019 => 'Institution parameter too long',
		-20020 => 'Department number too long',
		-20021 => 'Text to translate too long',
		-20022 => 'Too many FTP destinations',
		-20023 => 'Too many SFTP destinations',
		-20024 => 'Too many HTTP destinations',
		-20025 => 'Missing destination',
		-20026 => 'Bad requester callback protocol',
		-20027 => 'Bad error callback protocol',
		-20028 => 'Concurrency quota exceeded',
		-20029 => 'Document format not supported',
		-20030 => 'Text to translate is empty',
		-20031 => 'Missing text or document to translate',
		-20032 => 'Email address too long',
		-20033 => 'Cannot read stream',
		-20034 => 'Output format not supported',
		-20035 => 'Email destination tag is missing or empty',
		-20036 => 'HTTP destination tag is missing or empty',
		-20037 => 'FTP destination tag is missing or empty',
		-20038 => 'SFTP destination tag is missing or empty',
		-20039 => 'Document to translate tag is missing or empty',
		-20040 => 'Format tag is missing or empty',
		-20041 => 'The content is missing or empty',
		-20042 => 'Source language defined in TMX file differs from request',
		-20043 => 'Source language defined in XLIFF file differs from request',
		-20044 => 'Output format is not available when quality estimate is requested. It should be blank or \'xslx\'',
		-20045 => 'Quality estimate is not available for text snippet',
		-20046 => 'Document too big (>20Mb)',
		-20047 => 'Quality estimation not available',
		-40010 => 'Too many segments to translate',
		-80004 => 'Cannot store notification file at specified FTP address',
		-80005 => 'Cannot store notification file at specified SFTP address',
		-80006 => 'Cannot store translated file at specified FTP address',
		-80007 => 'Cannot store translated file at specified SFTP address',
		-90000 => 'Cannot connect to FTP',
		-90001 => 'Cannot retrieve file at specified FTP address',
		-90002 => 'File not found at specified address on FTP',
		-90007 => 'Malformed FTP address',
		-90012 => 'Cannot retrieve file content on SFTP',
		-90013 => 'Cannot connect to SFTP',
		-90014 => 'Cannot store file at specified FTP address',
		-90015 => 'Cannot retrieve file content on SFTP',
		-90016 => 'Cannot retrieve file at specified SFTP address'
	);	

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

		if (intval($response) < 0) {
			wp_send_json_error($this->errorMap[intval($response)]);
			return;
		}

		global $wpdb;
		$wp_track_table = $wpdb->prefix . ETRANSLATION_TABLE;
		$wpdb->insert( 
			$wp_track_table, 
			array( 
				'id' => $id
			)
		);
		wp_send_json_success(array(
			"id" => $id
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
			<span id="etranslation_error_message" style="display: none;"></span>
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