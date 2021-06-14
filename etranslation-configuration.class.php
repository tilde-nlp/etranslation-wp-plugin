<?php
class eTranslationConfiguration {
    static function getMetaBoxPostTypes() {
        return apply_filters(__METHOD__, get_option('etranslation_metabox_post_types'));
    }

    static function getMetaBoxContext() {
        return apply_filters(__METHOD__, get_option('etranslation_metabox_context'));
    }

    static function getMetaBoxPriority() {
        return apply_filters(__METHOD__, get_option('etranslation_metabox_priority'));
    }

    static function isPluginInstalled() {
        return get_option('etranslation_plugin_installed');
    }
}

