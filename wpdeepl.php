<?php
/**
 * Plugin Name: DeepL for WordPress : translation plugin
 * Description: Get DeepL translation magic right inside your WordPress editor (with a paid DeepL Pro account)
 * Version: 1.1.5
 * Plugin Slug: wpdeepl
 * Author: Fluenx
 * Author URI: https://www.fluenx.com/
 * Requires at least: 4.0.0
 * Tested up to: 5.0.3
 * Text Domain: wpdeepl
 * Domain Path: /languages
 */

if( !function_exists( 'is_admin' ) || !is_admin() )
	return;

defined( 'WPDEEPL_FLAVOR' )		or define( 'WPDEEPL_FLAVOR', 'free' );
defined( 'WPDEEPL_NAME' ) 		or define( 'WPDEEPL_NAME', 		plugin_basename( __FILE__ ) ); // plugin name as known by WP.
defined( 'WPDEEPL_SLUG' ) 		or define( 'WPDEEPL_SLUG', 		'wpdeepl' );// plugin slug ( should match above meta: Text Domain ).
defined( 'WPDEEPL_DIR' ) 		or define( 'WPDEEPL_DIR', 		dirname( __FILE__ ) ); // our directory.
defined( 'WPDEEPL_PATH' ) 		or define( 'WPDEEPL_PATH', 		realpath( __DIR__ ) ); // our directory.
defined( 'WPDEEPL_URL' ) 		or define( 'WPDEEPL_URL', 		plugins_url( '', __FILE__ ) );
$plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );
defined( 'WPDEEPL_VERSION' ) 	or define( 'WPDEEPL_VERSION', $plugin_data['Version'] );

$wp_upload_dir = wp_upload_dir();
defined( 'WPDEEPL_FILES' )		or define( 'WPDEEPL_FILES', 		trailingslashit( $wp_upload_dir['basedir'] ) . 'wpdeepl' );
defined( 'WPDEEPL_FILES_URL' )	or define( 'WPDEEPL_FILES_URL', 	trailingslashit( $wp_upload_dir['baseurl'] ) . 'wpdeepl' );
if( !is_dir( WPDEEPL_FILES ) ) mkdir( WPDEEPL_FILES );

defined( 'DEEPL_API_URL' ) 		or define( 'DEEPL_API_URL',	'https://api.deepl.com/v2/' );

try {
	if( is_admin() ) {
		require_once( trailingslashit( WPDEEPL_PATH ) . 'deepl-configuration.class.php' );
		require_once( trailingslashit( WPDEEPL_PATH ) . 'includes/deepl-functions.php' );
		require_once( trailingslashit( WPDEEPL_PATH ) . 'includes/deepl-plugin-install.php' );

 		require_once( trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-admin-hooks.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-admin-functions.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'admin/deepl-metabox.php' );

 		require_once( trailingslashit( WPDEEPL_PATH ) . 'settings/wp-improved-settings-api.class.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'settings/wp-improved-settings.class.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'settings/wp-improved-settings-wpdeepl.class.php' );

 		require_once( trailingslashit( WPDEEPL_PATH ) . 'client/deepl-data.class.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi.class.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi-translate.class.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi-usage.class.php' );
 		require_once( trailingslashit( WPDEEPL_PATH ) . 'client/deeplapi-functions.php' );
	}
} catch ( Exception $e ) {
	if( current_user_can( 'manage_options' ) ) {
		print_r( $e );
		die( __( 'Error loading WPDeepL','wpdeepl' ) );
	}
}

//global $DeepLForWordPress; $DeepLForWordPress = new DeepLForWordPress();
function deepl_is_plugin_fully_configured() {
	$WP_Error = new WP_Error();

	if( count( $WP_Error->get_error_messages() ) ) {
		return $WP_Error;
	}
	return true;
}

if( !function_exists( 'plouf' ) ) {
	function plouf( $e, $txt = '' ) {
		if( $txt != '' ) echo "<br />\n$txt";
		echo '<pre>';
		print_r( $e );
		echo '</pre>';
	}
}

add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wpdeepl_plugin_action_links' );
function wpdeepl_plugin_action_links( $links ) {
 $links = array_merge( 
 	array(
 		'<a href="' . esc_url( admin_url( '/options-general.php?page=deepl_settings' ) ) . '">' . __( 'Settings', 'wpdeepl' ) . '</a>'
 	), 
 	$links 
 );
 return $links;
}

register_activation_hook( __FILE__, 'deepl_plugin_activate' );
function deepl_plugin_activate() {
	deepl_install_plugin();
}

register_deactivation_hook( __FILE__, 'deepl_plugin_deactivate' );
function deepl_plugin_deactivate() {
}


add_action( 'init', 'deepl_init' );
function deepl_init() {
	if( !is_admin() ) {
		return;
	}

	load_plugin_textdomain( 'wpdeepl', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}