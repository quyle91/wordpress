<?php

namespace Adminz\Controller;

final class Enqueue {
    private static $instance = null;
    public $id = 'adminz_enqueue';
    public $name = 'Enqueue';
    public $option_name = 'adminz_enqueue';

    public $fonts_uploaded = [];
    public $fonts_supported = [];
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
        add_action('init', [$this, 'init']);
    }

    function init() {

        // 
        if (($this->settings['remove_upload_filters'] ?? '') == 'on') {
            if (!defined('ALLOW_UNFILTERED_UPLOADS')) {
                define('ALLOW_UNFILTERED_UPLOADS', true);
            }
        }

        // 
        if (($this->settings['photoswipe'] ?? "") == 'on') {
            $a = new \Adminz\Helper\Photoswipe;
            $a->init();
        }

        // 
        if (($this->settings['photoswipe'] ?? "") == 'on') {
            $list = array_values($this->settings['photoswipe_process'] ?? []);
            if (empty($list[0]['key'])) {
                return;
            }
            $a = new \Adminz\Helper\Photoswipe;
            $a->process($list);
        }

        // 
        if ($font_uploaded = ($this->settings['adminz_fonts_uploaded'] ?? [])) {
            \Adminz\Helper\Enqueue::adminz_enqueue_font_text($font_uploaded);
        }

        // 
        if ($custom_font_icons = ($this->settings['custom_font_icons'] ?? [])) {
            \Adminz\Helper\Enqueue::adminz_enqueue_font_icon($custom_font_icons);
        }

        // 
        if ($fonts_supported = ($this->settings['adminz_supported_font'] ?? "")) {
            \Adminz\Helper\Enqueue::adminz_enqueue_font_supported($fonts_supported);
        }

        if ($css = ($this->settings['adminz_custom_css_fonts'] ?? "")) {
            \Adminz\Helper\Enqueue::adminz_enqueue_css($css);
        }

        if ($js = ($this->settings['adminz_custom_js'] ?? "")) {
            \Adminz\Helper\Enqueue::adminz_enqueue_js($js);
        }
    }

    function load_settings() {
        $this->settings = get_option($this->option_name, []);

        // font uploaded
        $fonts_uploaded = $this->settings['adminz_fonts_uploaded'] ?? [];
        // old version
        $fonts_uploaded = adminz_maybeJson($fonts_uploaded) ?? [];
        $this->fonts_uploaded = $fonts_uploaded;

        // font supported
        $fonts_supported = $this->settings['adminz_supported_font'] ?? [];
        $this->fonts_supported = $fonts_supported;
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    function register_settings() {
        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz_custom_font',
            'Custom font',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Remove Upload filters',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[remove_upload_filters]')
                    ->value($this->settings['remove_upload_filters'] ?? '')

                    ->addNote('Check it to allow upload your fonts file. Dont forget to disable it later.')
                    ->render();
            },
            $this->id,
            'adminz_custom_font'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fonts text',
            function () {

                // options font weight
                $font_weight_options = [
                    'normal' => 'normal',
                    'bold' => 'bold',
                    'bolder' => 'bolder',
                    'lighter' => 'lighter',
                    '100' => '100',
                    '200' => '200',
                    '300' => '300',
                    '400' => '400',
                    '500' => '500',
                    '600' => '600',
                    '700' => '700',
                    '800' => '800',
                    '900' => '900',
                ];

                // options font style
                $font_style_options = [
                    'normal' => 'normal',
                    'italic' => 'italic',
                    'oblique' => 'oblique',
                    'initial' => 'initial',
                    'inherit' => 'inherit',
                ];

                // options font stretch
                $font_stretch_options = [
                    'ultra-condensed' => 'ultra-condensed',
                    'extra-condensed' => 'extra-condensed',
                    'condensed' => 'condensed',
                    'semi-condensed' => 'semi-condensed',
                    'normal' => 'normal',
                    'semi-expanded' => 'semi-expanded',
                    'expanded' => 'expanded',
                    'extra-expanded' => 'extra-expanded',
                    'ultra-expanded' => 'ultra-expanded',
                    'initial' => 'initial',
                    'inherit' => 'inherit',
                ];
    
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[adminz_fonts_uploaded]')
                    ->value($this->settings['adminz_fonts_uploaded'] ?? [])
                    ->childDirection('wrap')
                    ->label('Fonts Uploaded')
                    ->fields([

                        // [0] wp_media
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('wp_media')
                            ->name('0')
                            ->label('Font File')
                            ->default('')
                            ->copyButton(false),

                        // [1] font family
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('1')
                            ->label('Font Family')
                            ->attributes(
                                [
                                    'placeholder' => 'Font family: xxx',
                                ]
                            )
                            ->default('')
                            ->copyButton(true),

                        // [2] font weight
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('2')
                            ->label('Font Weight')
                            ->options($font_weight_options)
                            ->default('')
                            ->copyButton(true),

                        // [3] font style
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('3')
                            ->label('Font Style')
                            ->options($font_style_options)
                            ->default('')
                            ->copyButton(true),

                        // [4] font stretch
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('4')
                            ->label('Font Stretch')
                            ->options($font_stretch_options)
                            ->default('')
                            ->copyButton(true),
                    ])
                    ->default(
                        [
                            [
                                '',
                                '',
                                '',
                                '',
                                '',
                            ]
                        ]
                    )
                    ->render();

                $note = __('Note');
                echo <<<HTML
                <p>
                <small>
                <strong>{$note}:</strong> If the font name contains spaces, it must be surrounded by '. Example:
                <strong>'xxx'</strong>
                </small>
                </p>
                HTML;
            },
            $this->id,
            'adminz_custom_font'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Font icon',
            function () {

                //
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[custom_font_icons]')
                    ->value($this->settings['custom_font_icons'] ?? false)
                    ->childDirection('wrap')
                    ->fields([

                        // font_id
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('wp_media')
                            ->name('font_id')
                            ->label('Font File')
                            ->copyButton(false),

                        // items (repeater con)
                        \WpDatabaseHelperV2\Fields\WpRepeater::make()
                            ->name('items')
                            ->childDirection('horizontal')
                            ->label('Icons')
                            ->fields([

                                // class
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('text')
                                    ->name('class')
                                    ->label('Css Selector')
                                    // ->placeholder('.icon-shopping-cart')
                                    ->attributes(
                                        [
                                            'placeholder' => '.icon-shopping-cart',
                                        ]
                                    )
                                    ->copyButton(true),

                                // icon_code
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('input')
                                    ->type('text')
                                    ->name('icon_code')
                                    ->label('Font Icon Code')
                                    // ->placeholder('e900')
                                    ->attributes(
                                        [
                                            'placeholder' => 'e900',
                                        ]
                                    )
                                    ->copyButton(true),
                            ])
                    ])
                    ->default([
                        [
                            "font_id"  => '',
                            "items" => [
                                [
                                    'class' => '',
                                    'icon_code' => '',
                                ],
                            ],
                        ],
                    ])
                    ->render();

                // note
                $guid = ADMINZ_DIR_URL . 'assets/guide/flatsome_custom_icon.png';
                echo <<<HTML
                <p>
                <small>
                Note*: Make font file with <strong><a href="https://icomoon.io/app/#/select">Icomoon.io</a></strong> and here is <strong><a href="$guid">screenshot</a></strong><br>
                </small>
                </p>
                HTML;
            },
            $this->id,
            'adminz_custom_font'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fonts supported',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_supported_font]')
                    ->options([
                        'lato' => 'Lato Vietnamese',
                        'fontawesome' => 'font awesome 6.5.2-web',
                    ])
                    ->value($this->settings['adminz_supported_font'] ?? [])

                    ->render();
            },
            $this->id,
            'adminz_custom_font'
        );

        // add section
        add_settings_section(
            'adminz_enqueue_libary',
            'Library',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Photoswipe',
            function () {

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[photoswipe]')
                    ->value($this->settings['photoswipe'] ?? [])
                    ->addNote('Enable photoswipe')

                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[photoswipe_process]')
                    ->value($this->settings['photoswipe_process'] ?? false)
                    ->childDirection('wrap')
                    ->fields([

                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('key')
                            ->label('Gallery CSS selector')
                            ->options([]),

                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('value')
                            ->label('Children CSS selector')
                            ->options([]),

                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                        ],
                    ])
                    ->render();

                echo adminz_toggle_button(_x('Suggested', 'custom headers'), ".guild_process");
                $text1 = adminz_copy('body.single-post .entry-content p');
                $text2 = adminz_copy('img');
                echo <<<HTML
				<table class="guild_process hidden" style="margin-top: 15px;">
					<tr>
						<td> Ex </td>
						<td> Gallery </td>
						<td> Children </td>
					</tr>
					<tr>
						<td> Images on single post content </td>
						<td> {$text1} </td>
						<td> {$text2} </td>
					</tr>
				</table>
				HTML;
            },
            $this->id,
            'adminz_enqueue_libary'
        );

        // add section
        add_settings_section(
            'adminz_enqueue_code',
            'Custom code',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Custom Css',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->name($this->option_name . '[adminz_custom_css_fonts]')
                    ->attributes(
                        [
                            'placeholder' => "body{font-family: 'xxx';}",
                        ]
                    )
                    ->value($this->settings['adminz_custom_css_fonts'] ?? '')
                    ->render();


                echo "<small>Body:</small> " . adminz_copy('body{font-family: \'xxx\';}') . "</br>";
                echo "<small>Heading:</small> " . adminz_copy('h1, h2, h3, h4, h5, h6, .heading-font, .off-canvas-center .nav-sidebar.nav-vertical > li > a') . "</br>";
                echo "<small>Navigation:</small> " . adminz_copy('.nav>li>a') . "</br>";
                echo "<small>Widget:</small> " . adminz_copy('span.widget-title') . "</br>";
            },
            $this->id,
            'adminz_enqueue_code'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Custom Javascript',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->name($this->option_name . '[adminz_custom_js]')
                    ->value($this->settings['adminz_custom_js'] ?? '')
                    ->render();
            },
            $this->id,
            'adminz_enqueue_code'
        );
    }
}
