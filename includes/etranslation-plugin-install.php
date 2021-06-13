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
}
