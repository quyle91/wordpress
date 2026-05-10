<?php

namespace Adminz\Controller;

final class Acf {
    private static $instance = null;
    public $id = 'group_adminz_acf';
    public $name = 'ACF';
    public $option_name = 'adminz_acf';

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

        // ------------------ 
        if (($this->settings['multilang'] ?? "") == 'on') {
            $a = new \Adminz\Helper\Acf;
            $a->setup_multilang();
        }

        // ------------------ 
        if (($this->settings['enable_shortcode'] ?? "") == 'on') {
            add_action('acf/init', function () {
                acf_update_setting('enable_shortcode', true);
            });
        }
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
            'adminz_acf_section',
            'Advanced custom field',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Compatible with multilang',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[multilang]')
                    ->value($this->settings['multilang'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_acf_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Enable short code [acf]',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[enable_shortcode]')
                    ->value($this->settings['enable_shortcode'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_acf_section'
        );
    }
}
