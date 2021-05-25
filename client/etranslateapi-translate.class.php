<?php

class eTranslateApiTranslate extends eTranslateApi {
	protected $endPoint = 'translate';

	protected $langs = array( 'EN', 'DE', 'FR', 'ES', 'IT', 'NL', 'PL', 'RU' );
	protected $syntaxes = array( 'xml' );

	public $allow_cache = true;
	protected $http_mode = 'POST';

	protected $request = array(
		'source_lang' 			=> null,
		'target_lang' 			=> false,
		'tag_handling' 			=> null,
		'split_sentences' 		=> 1,
		'preserve_formatting' 	=> 1,
		'formality'				=> 'default',
		'text' 					=> array(),

	);

	protected function prepareString( $original_string ) {
		$string = $original_string;

		$string = preg_replace_callback( '/\\\\u( [0-9a-fA-F]{4} )/', function ( $match ) {
 		return mb_convert_encoding( pack( 'H*', $match[1] ), 'UTF-8', 'UCS-2BE' );
		}, $string );
		$string = str_replace( '&nbsp;', ' ', $string );
		// mandatory for POST requests
		$string = urlencode($string);
		//$string = htmlspecialchars($string);
		$string = trim( $string );

		//echo "\n ORIG $original_string \n = $string";

		return apply_filters( __METHOD__, $string, $original_string );
	}

	public function getTranslations( $strings = array(), $return_originals = false ) {
		if( !is_array( $strings ) ) {
			return new WP_Error( "wrong type", "Parameter is not an array" );
		}

		$this->finalPrepareRequest();

		$response = array();
		$translated = array();
		//plouf( $strings , " zeirpejzirjzi" );		//die( 'okokok' );

		$string_indexes = array();
		$i = 0;

		$cache_id_strings = '';

		foreach( $strings as $string_index => $string ) {
			$string_indexes[$i] = $string_index;
			$i++;
			$string = $this->prepareString( $string );
			$cache_id_strings .= $string;
			$this->addText( $string );

			if( $return_originals ) {
				$translated['_original_' . $string_index] = urldecode($string);
			}
		}

		//plouf($translated);		die('ok aziejzaiej');
		//plouf( $this );		die("zpijzaijeiaeij");

		$cache_id = ( $this->request['source_lang'] ) ? $this->request['source_lang'] : 'AUTO';
		$cache_id .= ':' . $this->request['target_lang'] . ':' . md5( $cache_id_strings );
		$this->setCacheID( $cache_id );

		//plouf($this);		die('okaziejaiej');
		if( !$this->isValidRequest() ) {
			$return = new WP_Error( "bad request", "Bof" );
			return $return;
		}

		//plouf($this);die('eizjrizj');
		$response = $this->request();

		if( is_wp_error( $response ) ) {
			return $response;
			//$error_string = $response->get_error_message();			echo '<div id="message" class="error"><p>' . $error_string . '</p></div>';			return false;
		}

		if( !array_key_exists( 'translations', $response ) ) {
			plouf( $response );
			die( 'big mistake' );
			$return = new WP_Error( "bad response", $response );
		}

		foreach( $response['translations'] as $index => $translation ) {
			$string_index = $string_indexes[$index];

			$translated_text = $translation->text;
			//var_dump($translated_text);

			// 2021 04 14
			$translated_text = str_replace( '&lt;!--', '<!--', $translated_text );
			$translated_text = str_replace( '--&gt;', '-->', $translated_text );
			$translated_text = str_replace( '--&gt', '-->', $translated_text );
			
			//var_dump($translated_text);

			$translated[$string_index] = $translated_text;
		}
//		plouf( $translated, " TRANSLATED" );

		return $translated;
	}
/*
	public function resetQuery() {
		$this->request = array();
		$this->headers = array();
	}*/

	private function finalPrepareRequest() {

		/*
		Sets whether the translated text should lean towards formal or informal language. This feature currently only works for target languages "DE" (German), "FR" (French), "IT" (Italian), "ES" (Spanish), "NL" (Dutch), "PL" (Polish), "PT-PT", "PT-BR" (Portuguese) and "RU" (Russian).
		*/
		$target_lang = $this->request['target_lang'];
		if( !in_array( $target_lang, array('DE', 'FR', 'IT', 'ES', 'NL', 'PL', 'PT-PT', 'PT-BR', 'RU' ) ) ) {
			unset( $this->request['formality'] );
		}

		return;
	}

	public function addText( $string ) {
		$this->request['text'][] = $string;
		return strlen( implode( '', $this->request['text'] ) );
	}

	public function setLangFrom( $source_lang = false ) {
		if( !$source_lang ) {
			return true;
		}

		$source = DeeplConfiguration::validateLang( $source_lang, 'assource' );
		if( $source ) {
			$this->request['source_lang'] = $source;
			return true;
		}
		else {
			return true;
		}
	}

	public function setLangTo( $target_lang ) {
		if( !$target_lang ) {
			return false;
		}

		$target = DeeplConfiguration::validateLang( $target_lang, 'astarget' );
		if( !$target ) {
			return false;
		}
		else {
			$this->request['target_lang'] = $target;
			return true;
		}
	}

	public function setTagHandling( $tag_handling = 'xml' ) {
		if( $tag_handling && in_array( $tag_handling, $this->syntaxes ) ) {
			$this->request['tag_handling'] = $tag_handling;
			return true;
		}
	}

	public function setSplitSentences( $split_sentences = true ) {
		if( false === filter_var( $split_sentences, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->request['split_sentences'] = 0;
		}
		else {
			$this->request['split_sentences'] = 1;
		}
	}

	public function setFormality( $formality = 'default' ) {
		if( in_array( $formality, array('more', 'less' ) ) ) {
			$this->request['formality'] = $formality;
		}
	}

	public function setPreserveFormatting( $preserve_formatting = false ) {
		if( true === filter_var( $split_sentences, FILTER_VALIDATE_BOOLEAN ) ) {
			$this->request['preserve_formatting'] = 1;
		}
		else {
			$this->request['preserve_formatting'] = 0;
		}
	}

	public function getRequestUniqueID() {
		$this->uniqid = md5( implode( '',$this->request['text'] ) );
		return $this->uniqid;
	}

	// RESPONSE
	public function getDetectedLanguage() {
		if( !isset( $this->result ) || !array_key_exists( 'detected_source_language', $this->result ) ) {
			return false;
		}
		return $this->result['detected_source_language'];
	}

	public function getMessage() {
		if( !isset( $this->result ) || !array_key_exists( 'message', $this->result ) ) {
			return false;
		}
		return $this->result['message'];
	}
	public function getTranslatedText() {
		if( !isset( $this->result ) || !array_key_exists( 'text', $this->result ) ) {
			return false;
		}
		return $this->result['text'];
	}
}

