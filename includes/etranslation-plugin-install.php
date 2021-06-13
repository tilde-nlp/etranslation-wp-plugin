<?php

function etranslation_install_plugin() {
	if (!get_option('etranslation_plugin_installed')) {
		update_option('etranslation_plugin_installed', 0);
	}
	if (!get_option('etranslation_metabox_post_types')) {
		update_option('etranslation_metabox_post_types', array('post', 'page', 'attachment'));
	}
	if (!get_option('etranslation_metabox_context')) {
		update_option('etranslation_metabox_context','side');
	}
	if (!get_option('etranslation_metabox_priority')) {
		update_option('etranslation_metabox_priority','high');
	}

	create_plugin_database_table();
}

function create_plugin_database_table()
{
	global $wpdb;
	$wp_track_table = $wpdb->prefix . ETRANSLATION_TABLE;

	#Check to see if the table exists already, if not, then create it
	if($wpdb->get_var( "show tables like '$wp_track_table'" ) != $wp_track_table) 
	{
		$sql = "CREATE TABLE `". $wp_track_table . "` ( ";
		$sql .= "  `id`  VARCHAR(13) NOT NULL, ";
		$sql .= "  `status`  ENUM('TRANSLATING','DONE','ERROR') NOT NULL DEFAULT 'TRANSLATING', ";
		$sql .= "  `body`  TEXT NULL DEFAULT NULL, ";
		$sql .= "  PRIMARY KEY (`id`) "; 
		$sql .= ") COLLATE='utf8mb4_unicode_520_ci'; ";
		require_once( ABSPATH . '/wp-admin/includes/upgrade.php' );
		$result = dbDelta($sql);
	}
}

function etranslation_uninstall_plugin() {
	if (get_option('etranslation_plugin_installed')) {
		delete_option('etranslation_plugin_installed');
	}
	if (get_option('etranslation_metabox_post_types')) {
		delete_option('etranslation_metabox_post_types');
	}
	if (get_option('etranslation_metabox_context')) {
		delete_option('etranslation_metabox_context');
	}
	if (get_option('etranslation_metabox_priority')) {
		delete_option('etranslation_metabox_priority');
	}

	if (get_option('etranslation_username')) {
		delete_option('etranslation_username');
	}
	if (get_option('etranslation_password')) {
		delete_option('etranslation_password');
	}
	if (get_option('etranslation_application')) {
		delete_option('etranslation_application');
	}
	if (get_option('etranslation_institution')) {
		delete_option('etranslation_institution');
	}

	global $wpdb;
	$wpdb->query("DROP TABLE IF EXISTS " . $wpdb->prefix . ETRANSLATION_TABLE);
}
