<?php

namespace Adminz\Controller;

final class AdministratorZ {
    private static $instance = null;
    public $id = ADMINZ_SLUG;
    public $name = ADMINZ_NAME;
    public $option_name = 'adminz_administratorz';

    public $settings = [];
    public $data_version_site;

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

        add_action('init', [$this, 'adminz_run_upgrade']);
        add_action('wp_ajax_adminz_run_upgrade_ajax', [$this, 'adminz_run_upgrade_ajax']);
        add_action('wp_ajax_adminz_import_backup', [$this, 'adminz_import_backup']);
    }

    function load_settings() {
        $this->settings = (array) get_option($this->option_name, []) ?? [];
        $this->data_version_site = $this->settings['adminz_data_version_site'] ?? 0;
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    function register_settings() {
        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz',
            ADMINZ_NAME,
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            __('Version'),
            function () {
                echo '<code>' . ADMINZ_VERSION . '</code>';
            },
            $this->id,
            'adminz'
        );

        // field 
        add_settings_field(
            wp_rand(),
            __('What&#8217;s New'),
            function () {
                $list = [
                    // 'Crawl tool: refactor',
                    'Flatsome: Post type archive template',
                    'Downgrade to php 8.1 as required',
                    'Wordperss tool - Add tool detete image with image id in db',
                    'Flatsome - woocommerce - Add priority for Use hooks',
                    'Flatsome - rebuild sync portfolio to category',
                    'Fix Wordfence security issue',
                    'Wordperss - Fix Clone posts',
                    'Flatsome - New element: Before after',
                    'Taxonomy template/ post type template: add new source field',
                    'Fix clone post not working',
                    'WPdatabase helper v1 to v2, applied for all fields and metaboxes',
                    'Flatsome custom blogs: Video popup support',
                    'Contact form 7: Prefill form by Url params',
                    'Permalink: Remove taxonomy base slug, Fix removing custom post type permalink',
                    'Tools: Debug tools',
                    'Featured: Metabuilder and Database creator in beta mode',
                    'Featured: Wordpress seo - Custom permalink, enable this function is required',
                    'Restore option Flatsome: enable zalo skype whatsapp support checkbox',
                    'Flatsome - fix element openstreet map for wrong lat/long data',
                    'Pages Preload images: moved to page edit, enable this function is required.',
                    'Update helper (WP database helper): add new edit link of user_select, post_select or term_select field type',
                    [
                        'Flatsome - moved All autofix to manual fix',
                        'Also auto migrate old settings to new settings',
                        'items' => [
                            'fix_archive_query',
                            'fix_tag_query',
                            'create_addition_menus',
                            'fix_logo_mobile_width',
                            'create_menu_overlay',
                            'fix_payment_icons_custom',
                            'fix_custom_footer_block_action_hooks',
                            'fix_blog_divider',
                            'fix_mobile_overlay',
                            'fix_select2',
                        ]
                    ],
                    'Flatsome - Fix archive query (moved from auto fix to manual fix)',
                    'Flatsome - New section fix',
                    'Wordpress - Register post type with tag support',
                    'Tools - Test user field and meta',
                    'Wpcf7 - Date time placeholder',
                    'Wordpress - Move plugins to must use',
                    'Flatsome custom slider - New option: equal height',
                    'Wordpress/Seo - Pages Preload images and auto general meta tags',
                    'Tools - Clone post',
                    'Flatsome - New element custom blog posts',
                    'Wordpress - Override icons',
                    'Wordpress - Register post type / taxonomy',
                    'Wordpress - Page Preload images',
                    'Flatsome - Active on anchor link',
                    'Woocommerce - Checkout field validate',
                    'Wordpress - Hide admin url & tool check rewrite rules',
                    'Woocommerce - Custom email header/ footer',
                    'Tools - Test theme template',
                    'Flatsome - New element search form by post type',
                    'Sidebar selector - only flatsome',
                ];

                // crop list limit 10 items
                // $list = array_slice($list, 0, 15);

                echo '<table>';
                foreach ($list as $index => $item) {
                    echo '<tr>';
                    echo '<td>' . ($index + 1) . '</td>';
                    echo '<td><pre style="margin:0;">' . print_r($item, true) . '</pre></td>';
                    echo '</tr>';
                };
                echo '</table>';
            },
            $this->id,
            'adminz'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Data version',
            function () {
                echo '<small>';
                echo ADMINZ_DATA_VERSION;

                echo ' — ';
                if ($this->is_lastest_data_version()) {
                    echo __('Latest');
                } else {
                    echo __('New version available.');
                }

                // Field cơ bản
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('hidden')
                    ->name($this->option_name . '[adminz_data_version_site]')
                    ->value($this->settings['adminz_data_version_site'] ?? "")
                    ->render();
                echo '</small>';

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes(
                        [
                            'class' => ['button', 'button-primary', 'adminz_fetch'],
                            'data-response' => '.adminz_response',
                            'data-action' => 'adminz_run_upgrade_ajax',
                        ]
                    )
                    ->value('Run again')
                    ->copyButton(false)
                    ->render();
                echo '<div class="adminz_response"></div>';
            },
            $this->id,
            'adminz'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Adminz old version',
            function () {
                echo <<<HTML
                    <a class="button" target="_blank" href="https://quyle91.net/files/administrator-z.zip">
                        Download v3000
                    </a>
                HTML;
            },
            $this->id,
            'adminz'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Document',
            function () {
                echo <<<HTML
                    <a class="button" href="https://quyle91.net" target="_blank">
                        https://quyle91.net
                    </a>
                HTML;
            },
            $this->id,
            'adminz'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Report bugs',
            function () {
                echo <<<HTML
                    <a class="button" href="https://zalo.me/0972054206" target="_blank"> Zalo </a>
                    <a class="button" href="https://facebook.com/timquen2014" target="_blank"> Facebook </a>
                    <a class="button" href="mailto:quylv.dsth@gmail.com" target="_blank"> quylv.dsth@gmail.com </a>
                HTML;
            },
            $this->id,
            'adminz'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Import backup',
            function () {

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->name('import_data')
                    ->attributes(
                        [
                            'rows' => 4,
                            'cols' => 65
                        ]
                    )
                    ->value($this->get_adminz_settings())
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes(
                        [
                            'class' => ['button', 'button-primary', 'adminz_fetch'],
                            'data-response' => '.adminz_response_b',
                            'data-action' => 'adminz_import_backup',
                        ]
                    )
                    ->value('Run import')
                    ->copyButton(false)
                    ->render();
                
                echo '<div class="adminz_response adminz_response_b"></div>';
            },
            $this->id,
            'adminz'
        );
    }

    function get_adminz_settings() {
        global $adminz;

        $data = [];
        foreach ((array) $adminz as $key => $class) {
            if ($class->option_name ?? '') {
                $option_name = $class->option_name;
                $data[$option_name] = get_option($option_name);
            }
        }
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $base64 = base64_encode($json);
        return $base64;
    }

    function adminz_import_backup() {
        // nonce?
        if (!wp_verify_nonce($_POST['nonce'], 'adminz_js')) {
            wp_send_json_error(__('Invalid nonce', 'adminz'));
            wp_die();
        }

        // current user role?
        if (! current_user_can('manage_options')) {
            wp_send_json_error(__('Permission denied', 'adminz'));
            wp_die();
        }

        // prepare data
        $import_data = $_POST['import_data'];
        $import_data = sanitize_text_field($import_data);
        $import_data = base64_decode($import_data);
        $import_data = json_decode($import_data, true);

        foreach ((array) $import_data as $key => $value) {
            update_option($key, $value);
        }
        wp_send_json_success(_x('Completed', 'request status'));
        wp_die();
    }

    function run_upgrade() {
        global $adminz;

        switch (ADMINZ_DATA_VERSION) {
            case 1:
                // removed 
                break;

            case 2:
                $list = [
                    'fix_archive_query',
                    'fix_tag_query',
                    'create_addition_menus',
                    'fix_logo_mobile_width',
                    'create_menu_overlay',
                    'fix_payment_icons_custom',
                    'fix_custom_footer_block_action_hooks',
                    'fix_blog_divider',
                    'fix_mobile_overlay',
                    'fix_select2',
                ];
                $flatsome_settings = [];

                // check if 'Flatsome' exists and is an object with 'settings' property
                if (isset($adminz['Flatsome']) && is_object($adminz['Flatsome']) && property_exists($adminz['Flatsome'], 'settings')) {
                    $flatsome_settings = (array) $adminz['Flatsome']->settings;
                }

                foreach ((array)$list as $key) {
                    $flatsome_settings[$key] = "on";
                }

                update_option('adminz_flatsome', $flatsome_settings);
                break;
        }
    }

    public function adminz_run_upgrade_ajax() {
        $this->run_upgrade();
        $this->increase_data_verison();

        wp_send_json_success(_x('Completed', 'request status'));
        wp_die();
    }

    public function adminz_run_upgrade() {
        if ($this->is_lastest_data_version()) {
            return;
        }

        $this->run_upgrade();
        $this->increase_data_verison();
    }

    function is_lastest_data_version() {
        return (ADMINZ_DATA_VERSION == $this->data_version_site);
    }

    function increase_data_verison() {
        $this->settings['adminz_data_version_site'] = ADMINZ_DATA_VERSION;
        update_option($this->option_name, $this->settings);
    }
}
