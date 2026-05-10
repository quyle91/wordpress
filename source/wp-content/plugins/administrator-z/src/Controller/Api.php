<?php

namespace Adminz\Controller;

final class Api {
    private static $instance = null;
    public $id = 'adminz_api';
    public $name = 'API';
    public $option_name = 'adminz_api';

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
            'adminz_api_section',
            'Contact Form 7',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Telegram Bot',
            function () {
                //
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[telegram_botToken]')
                    ->attributes([
                        'placeholder' => 'botToken',
                        'class' => 'regular-text'
                    ])
                    ->value($this->settings['telegram_botToken'] ?? '')
                    ->render();
                //
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[telegram_chatId]')
                    ->attributes([
                        'placeholder' => 'chatId',
                        'class' => 'regular-text'
                    ])
                    ->value($this->settings['telegram_chatId'] ?? '')
                    ->render();

                echo adminz_copy(add_query_arg(['adminz_guid_telegram' => '',], get_site_url()));
            },
            $this->id,
            'adminz_api_section'
        );
    }
}
