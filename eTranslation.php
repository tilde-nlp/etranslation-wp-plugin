<?php
/**
 * Plugin Name: eTranslation
 * Description: eTranslation api realized in WP plugin to help with web page localization
 * Version: 0.0.1
 */


 // Register callback methods for eTranslation
add_action( 'rest_api_init', 'register_callback');

function register_callback () {
	register_rest_route( 'etranslation/v1', 'error_callback/(?P<id>[a-zA-Z0-9._-]+)', array(
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
	global $wpdb;
	$id = $request['id'];
	$error_message = $request->get_param('error-message');
	$body = $request->get_body();
	
	$wp_track_table = $wpdb->prefix . 'etranslation_jobs';
	$wpdb->update( 
		$wp_track_table, 
		array( 
			'status' => 'ERROR',
			'body' => $error_message
		),
		array( 'id' => $id )
	);

	$response = new WP_REST_Response("");
	$response->set_status(200);

	return $response;
}

function translation_destination( WP_REST_Request $request ) {
	global $wpdb;
	$id = $request['id'];
	$body = $request->get_body();
	
	$wp_track_table = $wpdb->prefix . 'etranslation_jobs';
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

defined('ETRANSLATION_PATH') or define('ETRANSLATION_PATH', realpath(__DIR__));
defined('ETRANSLATION_TABLE') or define('ETRANSLATION_TABLE', 'etranslation_jobs');
defined('ETRANSLATION_URL') or define('ETRANSLATION_URL', plugins_url('', __FILE__));
$plugin_data = get_file_data(__FILE__, array('Version' => 'Version'), false);
defined('ETRANSLATION_VERSION') or define('ETRANSLATION_VERSION', $plugin_data['Version']);
$wp_upload_dir = wp_upload_dir();
defined('ETRANSLATION_FILES')	or define('ETRANSLATION_FILES', trailingslashit($wp_upload_dir['basedir']) . 'etranslation');
if (!is_dir(ETRANSLATION_FILES)) {
    mkdir(ETRANSLATION_FILES);
}

function settings_etranslation_paths($paths = array()) {
	$paths['etranslation_settings'] = array(
		'files'	=> ETRANSLATION_FILES
	);
	return $paths;
}

try {
	if (is_admin()) {
        require_once( trailingslashit( ETRANSLATION_PATH ) . 'etranslation-configuration.class.php' );

        require_once( trailingslashit(ETRANSLATION_PATH) . 'includes/etranslation-plugin-install.php');
        require_once( trailingslashit( ETRANSLATION_PATH ) . 'admin/etranslation-admin-hooks.php' );
        require_once( trailingslashit( ETRANSLATION_PATH ) . 'admin/etranslation-metabox.class.php' );
        require_once( trailingslashit( ETRANSLATION_PATH ) . 'admin/etranslation-api.class.php' );

        require_once( trailingslashit( ETRANSLATION_PATH ) . 'settings/wp-settings-api.class.php' );
 		require_once( trailingslashit( ETRANSLATION_PATH ) . 'settings/wp-settings.class.php' );
 		require_once( trailingslashit( ETRANSLATION_PATH ) . 'settings/wp-settings-etranslation.class.php' );

		add_filter('settings_plugins_paths', 'settings_etranslation_paths'); 
	}
} catch (Exception $e) {
	if (current_user_can('manage_options')) {
		print_r($e);
		die(__( 'Error loading eTranslation','etranslation'));
	}
}

function etranslation_is_plugin_fully_configured() {
	$WP_Error = new WP_Error();

	if( count( $WP_Error->get_error_messages() ) ) {
		return $WP_Error;
	}
	return true;
}

// Register settings link
add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'etranslation_action_links' );
function etranslation_action_links( $links ) {
    $links = array_merge( 
        array(
            '<a href="' . esc_url( admin_url( '/options-general.php?page=etranslation_settings' ) ) . '">' . __( 'Settings', 'etranslation' ) . '</a>'
        ), 
        $links 
    );
    return $links;
}

register_activation_hook(__FILE__, 'etranslation_install_plugin');
register_uninstall_hook(__FILE__, 'etranslation_uninstall_plugin');

add_action( 'init', 'etranslation_init' );
function etranslation_init() {
	if (!is_admin()) {
		return;
	}

	load_plugin_textdomain('etranslation', false, dirname(plugin_basename( __FILE__ )) . '/languages');
}