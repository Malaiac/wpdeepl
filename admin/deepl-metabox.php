<?php

class DeepL_Metabox {
	protected $metabox_config = array();

	function __construct() {
		// adding the box
		add_action( 'add_meta_boxes', array( &$this, 'add_meta_box' ) );

		// adding the javascript footer
		//add_action( 'admin_footer', array( &$this, 'deepl_admin_footer_javascript' ) );

		// adding the ajax hook
		add_action( 'wp_ajax_deepl_translate', array( &$this, 'action_deepl_translate' ) );
	}

	function action_deepl_translate() {
		if( ! wp_verify_nonce( $_POST['nonce'], 'permission_to_translate' ) ){
			wp_send_json_error( "Unauthorized" );
		}

		$post_id = $_POST['post_id'];
		$strings = $_POST['to_translate'];
		foreach( $strings as $key => $string ) {
			$strings[$key] = stripslashes( $string );
		}
		$source_lang = $_POST['source_lang'];
		$target_lang = $_POST['target_lang'];

		//plouf( $strings, " strings zehruhr euh" );		plouf( $_POST, " POST " );		echo " from $source_lang to $target_lang for id $post_id";
		//die( 'ok' );

		$response = deepl_translate( $source_lang, $target_lang, $strings, $post_id );

		/*foreach( $translations as $key => $array ) {
			$responses[$key] = $array;
		}*/
		wp_send_json_success( $response );
	}

	public function add_meta_box() {
		$post_types = DeepLConfiguration::getMetaBoxPostTypes();

//echo " metabox = ".  DeepLConfiguration::getMetaBoxContext(),			DeepLConfiguration::getMetaBoxPriority();

		add_meta_box(
			'deepl_metabox',
			__( 'DeepL translation', 'wpdeepl' ),
			array( &$this, 'output' ),
			$post_types,
			DeepLConfiguration::getMetaBoxContext(),
			DeepLConfiguration::getMetaBoxPriority()
		);
	}

	public function output() {
		//echo '';		return false;

		$html = '';
		$html = '
		<form id="deepl_admin_translation" name="deepl_admin_translation" method="POST">';
		$html .= deepl_language_selector( 'source', 'deepl_source_lang', false );
		$html .= '<br />' . __( 'Translating to', 'wpdeepl' ) . '<br /> ';
		$html .= deepl_language_selector( 'target', 'deepl_target_lang', get_option( 'deepl_default_locale' ) );
		$html .= '
			<span id="deepl_spinner" class="spinner"></span>
		';

		$html .= wp_nonce_field( 'permission_to_translate', 'deepl_nonce', true, false );
		$html .= '
			<input id="deepl_translate" name="deepl_translate" type="button" class="button button-primary button-large" value="' . __( 'Translate' , 'wpdeepl' ) . '"></span>';

		$default_behaviour = DeepLConfiguration::getMetaBoxDefaultBehaviour();
		$default_metabox_behaviours = DeepLConfiguration::DefaultsMetaboxBehaviours();

		$html .= '
			<hr />';
		foreach( $default_metabox_behaviours as $value => $label ) {
			$html.= '
			<span style="display: block;">
				<input type="radio" name="deepl_replace" value="'. $value .'"';

			if( $value == $default_behaviour ) $html .= ' checked="checked"';
			$html .= '>
				<label for="deepl_replace">' . $label . '</label>
			</span>';
		}


		$html .= '
		</form>
		<div class="hidden_warning" style="display: none;">' . __( 'Gutenberg is not compatible with WPDeepL yet. Please use Classic Editor', 'wpdeepl' ) . '</div>';

		$html = apply_filters( 'deepl_metabox_html', $html);

		echo $html;
	}
}