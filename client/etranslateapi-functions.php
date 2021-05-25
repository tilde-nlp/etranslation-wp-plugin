<?php


function etranslate_translate( $source_lang = false, $target_lang, $strings = array(), $cache_prefix = '' ) {
    die('is cool');
    return '{"dog": "dog is cool"}';
		// $eTranslateApiTranslate = new eTranslateApiTranslate();
		// $eTranslateApiTranslate->setCachePrefix( $cache_prefix );

		// if( $source_lang ) {
		// 	$eTranslateApiTranslate->setLangFrom( $source_lang );
		// }

		// if( !$eTranslateApiTranslate->setLangTo( $target_lang ) ) {
		// 	return new WP_Error( sprintf( __( "Target language '%s' not valid", 'etranslate' ), $target_lang ) );
		// }

		// $eTranslateApiTranslate->setTagHandling( 'xml' );

		// $eTranslateApiTranslate->setFormality( eTranslateConfiguration::getFormalityLevel() );
		// $translations = $eTranslateApiTranslate->getTranslations( $strings );

		// if( is_wp_error( $translations ) ) {
		// 	return $translations;
		// }
		// $request = array(
		// 	'cached'				=> $eTranslateApiTranslate->wasItCached(),
		// 	'time'					=> $eTranslateApiTranslate->getTimeElapsed(),
		// 	'cache_file_request'	=> $eTranslateApiTranslate->getCacheFile( 'request' ),
		// 	'cache_file_response'	=> $eTranslateApiTranslate->getCacheFile( 'response' ),
		// );


		// $return = compact( 'request', 'translations' );
		// return apply_filters( 'etranslate_translate', $return, $source_lang, $target_lang, $strings, $cache_prefix );
}

	
function etranslate_show_usage() {
	?>
		<h3><?php _e( 'Usage', 'etranslate' ); ?></h3>
		<?php
		$eTranslateApiUsage = new eTranslateApiUsage();
		$usage = $eTranslateApiUsage->request();
		if( $usage && is_array( $usage ) && array_key_exists( 'character_count', $usage ) && array_key_exists( 'character_limit', $usage )) :
			$ratio = round( 100 * ( $usage['character_count'] / $usage['character_limit'] ), 3 );
			$left_chars = $usage['character_limit'] - $usage['character_count'];

		?>
			<div class="progress-bar blue">
				<span style="width: <?php echo round( (100 - $ratio ), 0 ); ?>%"><b><?php printf( __( '%s characters remaining', 'etranslate' ), number_format( $left_chars )); ?></b></span>
				<div class="progress-text"><?php
				printf( __( '%s / %s characters translated', 'etranslate' ), number_format_i18n( $usage['character_count'] ), number_format_i18n( $usage['character_limit'] ) );
				 echo " - " . $ratio; ?> %</div>
				 <small class="request_time"><?php printf( __( 'Request done in: %f milliseconds', 'etranslate' ), $eTranslateApiUsage->getRequestTime( true )) ?></small>
			</div>
		<?php
		else :
			_e('No response from the server', 'etranslate');
			echo '<br />';
			printf(__('Did you select the right server ? If yes, check your plan on <a href="%s">DeepL Pro website</a>. "DeepL API" should be included in it.', 'etranslate' ), 
				'https://www.deepl.com/pro-account/plan'
			);

		endif;
}
