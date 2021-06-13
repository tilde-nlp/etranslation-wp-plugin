<?php
/**
 * Plugin Name: eTranslation
 * Plugin URI: https://www.tilde.lv
 * Description: eTranslation api realized in WP plugin to help with web page localization
 * Version: 0.0.1
 * Author: Oskars Petriks
 * Author URI: https://github.com/YourLittleHelper
 */


 // Register callback methods for eTranslation
add_action( 'rest_api_init', 'register_callback');

function register_callback () {
	register_rest_route( 'etranslation/v1', 'error_callback', array(
	  'methods' => array( 'GET', 'POST' ),
	  'callback' => 'translation_error_callback',
	  'args' => array(),
	  'permission_callback' => function () {
		return true;
	  }
	) );

	register_rest_route( 'etranslation/v1', 'destination/(?P<id>[a-zA-Z0-9._-]+)', array(
		'methods' => array( 'GET', 'POST' ),
		'callback' => 'translation_destination',
		'args' => array(),
		'permission_callback' => function () {
		  return true;
		}
	) );
  }

function translation_error_callback( WP_REST_Request $request ) {
	$response = new WP_REST_Response("");
	$response->set_status(200);

	// TODO: Write error code to database

	return $response;
}

function translation_destination( WP_REST_Request $request ) {
	global $wpdb;
	$id = $request['id'];
	$body = $request->get_body();
	
	$wp_track_table = $wpdb->prefix . 'etranslate_jobs';
	$wpdb->update( 
		$wp_track_table, 
		array( 
			'status' => 'DONE',
			'body' => $body
		),
		array( 'id' => $id )
	);

	$response = new WP_REST_Response("");
	$response->set_status(200);

	return $response;
}

if (!function_exists('is_admin') || !is_admin()) {
    return;
}

defined('ETRANSLATE_PATH') or define('ETRANSLATE_PATH', realpath(__DIR__));
defined('ETRANSLATE_URL') or define('ETRANSLATE_URL', plugins_url('', __FILE__));
$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
defined('ETRANSLATE_VERSION') or define('ETRANSLATE_VERSION', $plugin_data['Version']);
$wp_upload_dir = wp_upload_dir();
defined('ETRANSLATE_FILES')	or define('ETRANSLATE_FILES', trailingslashit($wp_upload_dir['basedir']) . 'etranslate');
if (!is_dir(ETRANSLATE_FILES)) {
    mkdir(ETRANSLATE_FILES);
}

function settings_etranslate_paths($paths = array()) {
	$paths['etranslation_settings'] = array(
		'files'	=> ETRANSLATE_FILES
	);
	return $paths;
}

try {
	if (is_admin()) {
        require_once( trailingslashit( ETRANSLATE_PATH ) . 'etranslate-configuration.class.php' );

        require_once( trailingslashit(ETRANSLATE_PATH) . 'includes/etranslate-plugin-install.php');
        require_once( trailingslashit( ETRANSLATE_PATH ) . 'admin/etranslate-admin-hooks.php' );
        require_once( trailingslashit( ETRANSLATE_PATH ) . 'admin/etranslate-metabox.class.php' );

        require_once( trailingslashit( ETRANSLATE_PATH ) . 'settings/wp-settings-api.class.php' );
 		require_once( trailingslashit( ETRANSLATE_PATH ) . 'settings/wp-settings.class.php' );
 		require_once( trailingslashit( ETRANSLATE_PATH ) . 'settings/wp-settings-etranslate.class.php' );

		add_filter('settings_plugins_paths', 'settings_etranslate_paths'); 
	}
} catch (Exception $e) {
	if (current_user_can('manage_options')) {
		print_r($e);
		die(__( 'Error loading eTranslation','etranslate'));
	}
}

function etranslate_is_plugin_fully_configured() {
	$WP_Error = new WP_Error();

	if( count( $WP_Error->get_error_messages() ) ) {
		return $WP_Error;
	}
	return true;
}

// Register settings link
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'etranslate_action_links' );
function etranslate_action_links( $links ) {
    $links = array_merge( 
        array(
            '<a href="' . esc_url( admin_url( '/options-general.php?page=etranslation_settings' ) ) . '">' . __( 'Settings', 'etranslate' ) . '</a>'
        ), 
        $links 
    );
    return $links;
}

register_activation_hook(__FILE__, 'etranslate_plugin_activate');
function etranslate_plugin_activate() {
	etranslate_install_plugin();
}

register_deactivation_hook(__FILE__, 'etranslate_plugin_deactivate');
function etranslate_plugin_deactivate() {}

add_action( 'init', 'etranslate_init' );
function etranslate_init() {
	if (!is_admin()) {
		return;
	}

	load_plugin_textdomain('etranslate', false, dirname(plugin_basename( __FILE__ )) . '/languages');
}