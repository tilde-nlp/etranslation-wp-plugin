<?php
class WP_Settings_eTranslation extends WP_Settings\WP_Settings {
    public $plugin_id = 'etranslation';
    public $option_page = 'etranslation_settings';
    public $menu_order = 15;
    public $parent_menu = 'options-general.php';
    public $defaultSettingsTab = 'ids';

    static function getPageTitle() {
        return __('eTranslation settings', 'etranslation');
    }

    static function getMenuTitle() {
        return __('eTranslation', 'etranslation');
    }

    function getSettingsStructure() {
        $settings = array(
            'ids' => array(
                'title' => __('Credentials', 'etranslation') ,
                'sections' => array()
            ) ,
            'integration' => array(
                'title' => __('Integration', 'etranslation') ,
                'sections' => array()
            )
        );

        $settings['ids']['sections']['identifiants'] = array(
            'title' => __('eTranslation credentials', 'etranslation') ,
            'fields' => array(

                array(
                    'id' => 'username',
                    'title' => __('Username', 'etranslation') ,
                    'type' => 'text',
                    'css' => 'width: 20em;',
                    'description' => __('Please enter username', 'etranslation')
                ) ,
                array(
                    'id' => 'password',
                    'title' => __('Password', 'etranslation') ,
                    'type' => 'password',
                    'css' => 'width: 20em;',
                    'description' => __('Please enter password', 'etranslation')
                ) ,
                array(
                    'id' => 'application',
                    'title' => __('Application', 'etranslation') ,
                    'type' => 'text',
                    'css' => 'width: 20em;',
                    'description' => __('Please enter application', 'etranslation')
                ) ,
                array(
                    'id' => 'institution',
                    'title' => __('Institution', 'etranslation') ,
                    'type' => 'text',
                    'css' => 'width: 20em;',
                    'description' => __('Please enter institution<br /><br />To get started, contact the <a title="machine translation service desk" href="mailto:help@cefat-tools-services.eu" rel="noopener noreferrer">machine translation service desk</a>', 'etranslation')
                ) ,
            )
        );

        $wp_post_types = get_post_types(array(
            'public' => true,
            'show_ui' => true
        ) , 'objects');
        $post_types = array();
        if ($wp_post_types) foreach ($wp_post_types as $post_type => $WP_Post_Type) {
            $post_types[$post_type] = $WP_Post_Type->label;
        }
        unset($post_types['product']);

        $settings['integration']['sections']['metabox'] = array(
            'title' => __('Metabox', 'etranslation') ,
            'fields' => array(
                array(
                    'id' => 'metabox_post_types',
                    'title' => __('Metabox should be displayed on:', 'etranslation') ,
                    'type' => 'multiselect',
                    'options' => $post_types,
                    'default' => array(
                        'post',
                        'page',
                        'attachment'
                    ) ,
                    'description' => __('Select which post types you want the metabox to appear on', 'etranslation') ,
                ) ,
                array(
                    'id' => 'metabox_context',
                    'title' => __('Metabox context', 'etranslation') ,
                    'type' => 'select',
                    'options' => array(
                        'normal' => 'normal',
                        'side' => 'side',
                        'advanced' => 'advanced'
                    ) ,
                    'default' => 'side',
                    'description' => __('<a href="https://developer.wordpress.org/reference/functions/add_meta_box/">See add_meta_box function reference</a>', 'etranslation') ,
                ) ,
                array(
                    'id' => 'metabox_priority',
                    'title' => __('Metabox priority', 'etranslation') ,
                    'type' => 'select',
                    'options' => array(
                        'high' => 'high',
                        'low' => 'low'
                    ) ,
                    'default' => 'high',
                    'description' => '',
                ) ,
            )
        );
        return apply_filters('etranslation_admin_configuration', $settings);
    }
}

