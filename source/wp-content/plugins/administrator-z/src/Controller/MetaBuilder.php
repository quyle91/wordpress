<?php

namespace Adminz\Controller;

final class MetaBuilder {
    private static $instance = null;
    public $id = 'adminz_metabuilder';
    public $name = 'Meta Builder';
    public $option_name = 'adminz_metabuilder';

    public $settings = [];

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        add_filter('adminz_option_page_nav', [$this, 'add_admin_nav'], 10, 1);
        add_action('admin_init', [$this, 'register_settings']);
        $this->load_settings();
        $this->plugin_loaded();
    }

    function plugin_loaded() {

        // metabox builder
        \WpDatabaseHelperV2\Example\MetaBuilder::make(
            $this->option_name . '[metabox_builder]',
            $this->settings['metabox_builder'] ?? false
        )->read();

        // database builder
        \WpDatabaseHelperV2\Example\DbBuilder::make(
            $this->option_name . '[tables]',
            $this->settings['tables'] ?? false
        )->read();
    }    

    function load_settings() {
        $this->settings = get_option($this->option_name, []);
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    function register_settings() {

        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz_section_agreement',
            'Agreement',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Agreement',
            function () {

                // checkbox
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[agree]')
                    ->value($this->settings['agree'] ?? '')
                    ->copyButton(false)
                    ->addNote('I understand that this is a beta and I agree with all risks.')
                    ->render();
            },
            $this->id,
            'adminz_section_agreement'
        );

        if(($this->settings['agree'] ?? '') !== 'on'){
            return;
        }
        

        // add section
        add_settings_section(
            'adminz_section_meta',
            'Meta Builder',
            function () {
                //
            },
            $this->id
        );

        // field 
        \WpDatabaseHelperV2\Ajax\HandleAppendRepeater::register();
        add_settings_field(
            wp_rand(),
            'Metabox Builder',
            function () {
                //
                echo \WpDatabaseHelperV2\Example\MetaBuilder::make(
                    $this->option_name . '[metabox_builder]',
                    $this->settings['metabox_builder'] ?? false
                )->render();
            },
            $this->id,
            'adminz_section_meta'
        );


        // add section
        add_settings_section(
            'adminz_section_database',
            'Database',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Table Creator',
            function () {
                //
                echo \WpDatabaseHelperV2\Example\DbBuilder::make(
                    $this->option_name . '[tables]',
                    $this->settings['tables'] ?? false
                )->render();
            },
            $this->id,
            'adminz_section_database'
        );
    }
}
