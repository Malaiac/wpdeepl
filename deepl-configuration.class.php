<?php

class DeepLConfiguration {
	static function getAPIKey() {
		return apply_filters( __METHOD__, get_option( 'wpdeepl_api_key' ) );
	}

	static function getLogLevel() {
		return apply_filters( __METHOD__, get_option( 'wpdeepl_log_level') );
	}
	
	static function getMetaBoxPostTypes() {
		return apply_filters( __METHOD__, get_option( 'wpdeepl_metabox_post_types' ) );
	}
	static function getMetaBoxDefaultBehaviour() {
		return apply_filters( __METHOD__, get_option( 'wpdeepl_metabox_behaviour' ) );
	}
	static function getDefaultTargetLanguage() {
		return apply_filters( __METHOD__, get_option( 'wpdeepl_default_language' ) );
	}
	static function getMetaBoxContext() {
		return apply_filters( __METHOD__, get_option( 'wpdeepl_metabox_context' ) );
	}
	static function getMetaBoxPriority() {
		return apply_filters( __METHOD__, get_option( 'wpdeepl_metabox_priority' ) );
	}

/**
 * 0 = activated
 * 1 = activated and setup
 */
	static function isPluginInstalled() {
		return get_option( 'wpdeepl_plugin_installed' );
	}

	static function execWorks() {
		if( exec( 'echo EXEC' ) == 'EXEC' ){
		 return true;
		}
		return false;
	}

	static function DefaultsMetaboxBehaviours() {
		$array = array(
			'replace'		=> __( 'Replace content', 'wpdeepl' ),
			'append'		=> __( 'Append to content', 'wpdeepl' )
		);
		return apply_filters( __METHOD__ , $array );
	}
	static function DefaultsISOCodes() {
		$locale = get_locale();
		$all_languages = DeepLConfiguration::DefaultsAllLanguages();
		$languages = array();
		foreach( $all_languages as $isocode => $labels ) {
			$languages[$isocode] = $labels['labels'][$locale];
		}
		return apply_filters( __METHOD__ , $languages );
	}

	static function DefaultsAllLanguages() {
		$languages = array(
			'FR' => array(
				'labels' => array(
					'fr_FR' => 'français',
					'en_GB' => 'french',
					'en_US' => 'french',
					'de_DE' => 'französisch',
					'es_ES' => 'francés',
					'it_IT' => 'francese',
					'nl_NL' => 'frans',
					'pl_PL' => 'francuski',
				),
			),
			'EN' => array(
				'labels' => array(
					'fr_FR' => 'anglais',
					'en_GB' => 'english',
					'en_US' => 'english',
					'de_DE' => 'englisch',
					'es_ES' => 'inglès',
					'it_IT' => 'inglese',
					'nl_NL' => 'engels',
					'pl_PL' => 'anglik',
				)
			),
			'DE' => array(
				'labels' => array(
					'fr_FR' => 'allemand',
					'en_GB' => 'german',
					'en_US' => 'german',
					'de_DE' => 'deutsch',
					'es_ES' => 'alemán',
					'it_IT' => 'allemando',
					'nl_NL' => 'allemandrijk',
					'pl_PL' => 'niemiec',
				),
			),
			'ES' => array(
				'labels' => array(
					'fr_FR' => 'espagnol',
					'en_GB' => 'spanish',
					'en_US' => 'spanish',
					'de_DE' => 'spanisch',
					'es_ES' => 'castellano',
					'it_IT' => 'spagnola',
					'nl_NL' => 'spaans',
					'pl_PL' => 'hiszpanin',
				),
			),
			'IT' => array(
				'labels' => array(
					'fr_FR' => 'italien',
					'en_GB' => 'italian',
					'en_US' => 'italian',
					'de_DE' => 'italienisch',
					'es_ES' => 'italiano',
					'it_IT' => 'italiano',
					'nl_NL' => 'italiaan',
					'pl_PL' => 'włoch',
				)
			),
			'NL' => array(
				'labels' => array(
					'fr_FR' => 'néerlandais',
					'en_GB' => 'dutch',
					'en_US' => 'dutch',
					'de_DE' => 'holländisch',
					'es_ES' => 'neerlandés',
					'it_IT' => 'olandese',
					'nl_NL' => 'hollands',
					'pl_PL' => 'holender',
				)
			),
			'PL' => array(
				'labels' => array(
					'fr_FR' => 'polonais',
					'en_GB' => 'polish',
					'en_US' => 'polish',
					'de_DE' => 'polnisch',
					'es_ES' => 'polaco',
					'it_IT' => 'polacca',
					'nl_NL' => 'pools',
					'pl_PL' => 'polak',
				)
			),
		);
		return apply_filters( __METHOD__, $languages );
	}
}

