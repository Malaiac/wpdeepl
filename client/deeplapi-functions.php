<?php

function deepl_translate( $source_lang = false, $target_lang, $strings = array(), $cache_prefix = '' ) {
		$DeepLApiTranslate = new DeepLApiTranslate();
		$DeepLApiTranslate->setCachePrefix( $cache_prefix );

		if( $source_lang ) {
			$DeepLApiTranslate->setLangFrom( $source_lang );
		}

		if( !$DeepLApiTranslate->setLangTo( $target_lang ))
			return new WP_Error( $target_lang, __( "Target language '$target_lang' not valid", 'wpdeepl' ));

		$DeepLApiTranslate->setTagHandling( 'xml' );

		$translations = $DeepLApiTranslate->getTranslations( $strings );
		$request = array(
			'cached'				=> $DeepLApiTranslate->wasItCached(),
			'time'					=> $DeepLApiTranslate->getTimeElapsed(),
			'cache_file_request'	=> $DeepLApiTranslate->getCacheFile( 'request' ),
			'cache_file_response'	=> $DeepLApiTranslate->getCacheFile( 'response' ),
		);

		$return = compact( 'request', 'translations' );
		return apply_filters( 'deepl_translate', $return, $source_lang, $target_lang, $strings, $cache_prefix );
}

	