<?php

namespace Adminz\Controller;

final class Elementor {
    private static $instance = null;
    public $id = 'adminz_elementor';
    public $name = 'Elementor';
    public $option_name = 'adminz_elementor';

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
        // 
        foreach (glob(ADMINZ_DIR . '/includes/shortcodes/elementor-*.php') as $filename) {
            require_once $filename;
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
            'adminz_elementor_elements',
            'Elementor',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Widgets',
            function () {
                echo 'Some widgets from Elementor has been added. Open page builder to show';
            },
            $this->id,
            'adminz_elementor_elements'
        );
    }
}
