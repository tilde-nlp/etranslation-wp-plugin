<?php

function etranslate_language_selector($type = 'target', $id = 'etranslate_language_selector', $selected = false) {
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

function etranslate_show_clear_logs_button() {
	echo '
	<p class="submit">
		<button name="clear_logs" class="button-primary" type="submit" value="clear_logs">' . __('Clear logs', 'etranslate') .'</button>
	</p>';
}

function etranslate_clear_logs() {
	$log_files = glob( trailingslashit( ETRANSLATE_FILES ) .'*.log');
	if($log_files) foreach( $log_files as $log_file) {
		unlink($log_file);
	}
	echo '<div class="notice notice-success"><p>' . __('Log files deleted', 'etranslate') . '</p></div>';
}
function etranslate_log( $bits, $type ) {
	$log_lines = array_merge(array('date'	=> date('d/m/Y H:i:s')), $bits);
	$log_line = serialize($log_lines) . "\n";
	$type = filter_var( $type, FILTER_SANITIZE_STRING );
	$log_file = trailingslashit( ETRANSLATE_FILES ) . date( 'Y-m' ) . '-' . $type . '.log';
	file_put_contents( $log_file, $log_line, FILE_APPEND );
}

function etranslate_display_logs() {
	echo '<h3 class="wc-settings-sub-title" id="logs">' . __('Logs','etranslate') . '</h3>';

	$log_files = glob( trailingslashit( ETRANSLATE_FILES ) .'*.log');
	if($log_files) {
		foreach($log_files as $log_file) {
			$file_name = basename( $log_file );
			$contents = file_get_contents( $log_file );
			if(preg_match('#(\d+)-(\d+)-(\w+)\.log#', $file_name, $match)) {
				$date = $match[2] . '/' . $match[1];
				echo '<h3>';
				printf( 
					__("File '%s' for %s", 'wpdeepl' ),
					$match[3],
					$date
				);
				echo '</h3>';

				$lines = explode("\n", $contents);
				foreach($lines as $line) {
					plouf(unserialize($line));
				}

			}

		}
	}
	else {
		_e( 'No log files', 'etranslate' );
	}
}
