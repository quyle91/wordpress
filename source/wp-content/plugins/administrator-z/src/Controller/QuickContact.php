<?php

namespace Adminz\Controller;

final class QuickContact {
    private static $instance = null;
    public $id = 'adminz_quick_contact';
    public $name = 'Quick contact';
    public $option_name = 'adminz_contactgroup';

    public $settings = [], $nav_asigned = [], $menus = [], $styles = [];

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
        add_action('init', [$this, 'init']);
    }

    function load_settings() {
        $this->settings = get_option($this->option_name, []);

        // styles 
        $this->styles = [
            'callback_style1' => array(
                'callback' => 'callback_style1',
                'title' => '[1] Fixed Right',
                'css' => [ADMINZ_DIR_URL . 'assets/css/style1.css', 'all'],
                'js' => [],
                'description' => '',
            ),
            'callback_style2' => array(
                'callback' => 'callback_style2',
                'title' => '[2] Left Expanding Group',
                'css' => [ADMINZ_DIR_URL . 'assets/css/style2.css', 'all'],
                'js' => [],
                'description' => 'add class <code>right</code> to right style',
            ),
            'callback_style3' => array(
                'callback' => 'callback_style3',
                'title' => '[3] Left zoom',
                'css' => [ADMINZ_DIR_URL . 'assets/css/style3.css', 'all'],
                'js' => [],
                'description' => '',
            ),
            'callback_style4' => array(
                'callback' => 'callback_style4',
                'title' => '[4] Left Expand',
                'css' => [ADMINZ_DIR_URL . 'assets/css/style4.css', 'all'],
                'js' => [],
                'description' => 'Allow shortcode into title attribute. To auto show, put <code>show_desktop</code> into classes',
            ),
            'callback_style5' => array(
                'callback' => 'callback_style5',
                'title' => '[5] Fixed Bottom Mobile',
                'css' => [ADMINZ_DIR_URL . 'assets/css/style5.css', '(max-width: 768px)'],
                'js' => [],
                'description' => '',
            ),
            'callback_style6' => array(
                'callback' => 'callback_style6',
                'title' => '[6] Left Expand Horizontal',
                'css' => [ADMINZ_DIR_URL . 'assets/css/style6.css', 'all'],
                'js' => [],
                'description' => 'Round button Horizontal and tooltip, put <code>active</code> into classes to show tooltip or <code>zeffect1</code> for effect animation',
            ),
            'callback_style7' => array(
                'callback' => 'callback_style7',
                'title' => '[7] Fixed Simple right',
                'css' => [ADMINZ_DIR_URL . 'assets/css/style7.css', 'all'],
                'js' => [],
                'description' => 'Simple fixed',
            ),
        ];

        // menu
        $settings = $this->settings['settings'] ?? [];
        $custom_menu = $this->settings['custom_menu'] ?? [];

        // old data
        if (isset($settings['custom_nav'])) {
            $custom_nav = (array) json_decode($settings['custom_nav']);
            $tmp = [];
            foreach ($custom_nav as $key => $value) {
                $tmp[] = [
                    'name' => $value[0],
                    'items' => $value[1],
                ];
            }
            $custom_menu = $tmp;
        }
        $this->menus = $custom_menu;

        // nav assigned
        $this->nav_asigned = $this->settings['nav_asigned'] ?? [];
    }

    function init() {
        if (is_admin()) {
            return;
        }

        if (empty($this->nav_asigned)) {
            return;
        }

        foreach ($this->nav_asigned as $_key => $nav_asigned) {

            $style = $nav_asigned['key'] ?? false;
            $menu = $nav_asigned['value'] ?? false;

            if ($menu and $style) {
                $menu_name = str_replace('adminz_', '', $menu);

                foreach ($this->menus as $key => $value) {
                    if (str_replace(' ', '', $value['name']) == $menu_name) {
                        $menu_data = $value['items'];
                        $style = str_replace('callback_', '', $style);

                        add_action('wp_enqueue_scripts', function () use ($style, $menu_data, $nav_asigned) {
                            wp_enqueue_style(
                                'adminz_quick_contact_style_' . $style,
                                ADMINZ_DIR_URL . "assets/css/quick-contact/" . str_replace('callback_', '', $style) . ".css",
                                [],
                                ADMINZ_VERSION,
                                'all'
                            );
                        });

                        add_action('wp_footer', function () use ($style, $menu_data, $nav_asigned) {
                            if (function_exists('adminz_quick_contact_' . $style)) {
                                echo call_user_func('adminz_quick_contact_' . $style, $menu_data, $nav_asigned);
                            }
                        });
                    }
                }
            }
        }
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    function register_settings() {
        register_setting($this->id, $this->option_name);

        // field 
        add_settings_field(
            wp_rand(),
            'Menu Creator',
            function () {
                // v2
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[custom_menu]')
                    ->value($this->settings['custom_menu'] ?? false) // giá trị đã lưu
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('name')
                            ->label('Menu name')
                            ->default(''),

                        // 
                        \WpDatabaseHelperV2\Fields\WpRepeater::make()
                            ->name('items')
                            ->label('Items')
                            ->childDirection('wrap')

                            ->fields([

                                //
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('text')
                                    ->name('0')
                                    ->label('Tel')
                                    ->default(''),

                                //
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('text')
                                    ->name('1')
                                    ->label('Label')
                                    ->default(''),

                                //
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('select')
                                    ->type('text')
                                    ->name('2')
                                    ->label('Icon')
                                    ->options(adminz_get_list_icons())
                                    ->default(''),

                                //
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('select')
                                    ->type('text')
                                    ->name('3')
                                    ->label('Target')
                                    ->options([
                                        '' => __('Default'),
                                        '_blank' => '_blank',
                                        '_self' => '_self',
                                        '_parent' => '_parent',
                                        '_top' => '_top',
                                    ])
                                    ->default(''),

                                //
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('text')
                                    ->name('4')
                                    ->label('Class')
                                    ->default(''),

                                //
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('color')
                                    ->name('5')
                                    ->label('Color')
                                    ->default(''),
                            ])
                    ])
                    ->default([
                        [
                            "name" => 'Adminz contact menu 1',
                            "items" => [
                                [
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                    '',
                                ],
                            ],
                        ],
                    ])
                    ->render();


                $icons_html = adminz_tab_link('Icons');
                $note_html = __('Note');
                echo <<<HTML
                <p>
                    <small>
                        <strong>
                            $note_html
                        </strong>
                        You can custom icons on $icons_html
                    </small>
                </p>
                HTML;
            },
            $this->id,
            'adminz_contactgroup_menu'
        );

        // add section
        add_settings_section(
            'adminz_contactgroup_menu',
            'Menu',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Menu Asign',
            function () {

                // v2
                $key_options = [];
                foreach ((array)$this->styles as $key => $value) {
                    $key_options[$key] = $value['title'];
                }

                $value_options = [];
                foreach (($this->settings['custom_menu'] ?? []) as $key => $value) {
                    $_name = 'adminz_' . str_replace(' ', '', $value['name']);
                    $value_options[$_name] = $value['name'];
                }

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[nav_asigned]')
                    ->value($this->settings['nav_asigned'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->label('Items')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Position')
                            ->default('')
                            ->options($key_options),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('value')
                            ->label('Menu name')
                            ->default('')
                            ->options($value_options),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('label')
                            ->label('Label')
                            ->default(''),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('class')
                            ->label('Class')
                            ->default(''),


                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                            'label' => '',
                            'class' => ''
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_contactgroup_menu'
        );
    }
}
