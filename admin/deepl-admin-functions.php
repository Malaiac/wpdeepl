<?php

function deepl_language_selector(
	$type = 'target',
	$id = 'deepl_language_selector',
	$selected = false
	) {
	$languages = DeepLConfiguration::DefaultsAllLanguages();

	$wp_locale = get_locale();

	$default_target_language = DeepLConfiguration::getDefaultTargetLanguage();

	if( $type == 'target' && $selected == false ) {
		$selected = $default_target_language;
		//plouf( $languages );
	}

	$html = '';

	$html .= "\n" . '<select id="' . $id . '" name="' . $id . '">';

	if( $type == 'source' ) $html .= '
	<option value="auto">' . __( 'Automatic', 'wpdeepl' ) . '</option>';

	foreach( $languages as $ln_id => $language ) {
		if(
			$default_target_language
			&& $ln_id == $default_target_language
			&& $type == 'source' 
		) {
			continue;
		}

		$html .= '
		<option value="' . $ln_id .'"';

		if( $ln_id == $selected ) {
			$html .= ' selected="selected"';
		}
		$label = ( $wp_locale && isset( $language['labels'][$wp_locale] )) ? $language['labels'][$wp_locale] : $language['labels']['fr_FR'];
		$html .= '>' . $label. '</option>';
	}
	if( $type == 'target' ) $html .= '
	<option value="notranslation">' . __( 'Dont\'t translate', 'wpdeepl' ) . '</option>';

	$html .="\n</select>";

	return $html;
}

function wpdeepl_show_clear_logs_button() {
	echo '
	<p class="submit">
		<button name="clear_logs" class="button-primary" type="submit" value="clear_logs">' . __('Clear logs', 'wpdeepl') .'</button>
	</p>';

}



function wpdeepl_clear_logs() {
	$log_files = glob( trailingslashit( WPDEEPL_FILES ) .'*.log');
	if($log_files) foreach( $log_files as $log_file) {
		unlink($log_file);
	}
	echo '<div class="notice notice-success"><p>' . __('Log files deleted', 'wpdeepl') . '</p></div>';
	


}
function wpdeepl_log( $bits, $type ) {
	$log_lines = array_merge(array('date'	=> date('d/m/Y H:i:s')), $bits);
	$log_line = serialize($log_lines) . "\n";
	$type = filter_var( $type, FILTER_SANITIZE_STRING );
	$log_file = trailingslashit( WPDEEPL_FILES ) . date( 'Y-m' ) . '-' . $type . '.log';
	file_put_contents( $log_file, $log_line, FILE_APPEND );
}

function wpdeepl_display_logs() {

	
	echo '<h3 class="wc-settings-sub-title" id="logs">' . __('Logs','wpdeepl') . '</h3>';

	$log_files = glob( trailingslashit( WPDEEPL_FILES ) .'*.log');
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
		_e( 'No log files', 'wpdeepl' );
	}

 
}

