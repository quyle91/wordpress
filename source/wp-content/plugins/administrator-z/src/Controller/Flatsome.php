<?php

namespace Adminz\Controller;

final class Flatsome {
    private static $instance = null;
    public $id = 'adminz_flatsome';
    public $name = 'Flatsome';
    public $option_name = 'adminz_flatsome';

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
        $this->after_setup_theme();
    }

    function load_settings() {
        $this->settings = get_option($this->option_name, []);
    }

    function after_setup_theme() {
        // 
        remove_action('admin_notices', 'flatsome_status_check_admin_notice');
        remove_action('admin_notices', 'flatsome_maintenance_admin_notice');

        // CSS
        add_action('init', function () {
            adminz_add_body_class('blog_layout_divider_' . get_theme_mod('blog_layout_divider'));
            if (get_theme_mod('mobile_overlay_bg')) {
                adminz_add_body_class('adminz_fix_mobile_overlay_bg');
            }
            if (wp_script_is('select2')) {
                adminz_add_body_class('adminz_select2');
            }
            if (get_theme_mod('blog_post_layout') == 'no-sidebar') {
                adminz_add_body_class('blog_post_layout_no_sidebar');
            }
            if (get_theme_mod('blog_layout') == 'no-sidebar') {
                adminz_add_body_class('blog_layout_no_sidebar');
            }
        });


        add_action('wp_enqueue_scripts', function () {

            wp_enqueue_script(
                'adminz_flatsome_adminz_js',
                ADMINZ_DIR_URL . "assets/js/adminz_flatsome.js",
                [],
                ADMINZ_VERSION,
                true
            );

            wp_enqueue_style(
                'adminz_flatsome_adminz_css',
                ADMINZ_DIR_URL . "assets/css/flatsome/flatsome_fix.css",
                [],
                ADMINZ_VERSION,
                'all'
            );

            $secondary_color = get_theme_mod('color_secondary', \Flatsome_Default::COLOR_SECONDARY);
            $success_color = get_theme_mod('color_success', \Flatsome_Default::COLOR_SUCCESS);
            $alert_color = get_theme_mod('color_alert', \Flatsome_Default::COLOR_ALERT);
            $header_height = get_theme_mod('header_mobile', 90);
            $header_mobile_height = get_theme_mod('header_height_mobile', 70);
            $hide_footer_absolute = (!get_theme_mod('footer_left_text') && !get_theme_mod('footer_right_text')) ? 'none' : 'block';
            $mobile_overlay_bg = get_theme_mod('mobile_overlay_bg', '#232323');
            $site_width = get_theme_mod('site_width', '1000') . "px";
            $css = <<<HTML
			:root {
				--secondary-color: $secondary_color;
				--success-color: $success_color;
				--alert-color: $alert_color;
				--adminz-header_height: {$header_height}px;
				--adminz-header_mobile_height: {$header_mobile_height}px;
				--adminz-hide_footer_absolute: $hide_footer_absolute;
				--adminz-mobile_overlay_bg: $mobile_overlay_bg;
				--adminz-site-width: $site_width;
			}
			HTML;
            wp_add_inline_style(
                'adminz_flatsome_adminz_css',
                $css
            );
        });

        // 
        add_action('init', function () {
            foreach (glob(ADMINZ_DIR . '/includes/shortcodes/flatsome-*.php') as $filename) {
                require_once $filename;
            }
        });


        // 
        if (!empty($this->settings['adminz_banner_post_types'] ?? [])) {
            foreach ((array) $this->settings['adminz_banner_post_types'] as $key => $value) {
                if ($value) {
                    $a = new \Adminz\Helper\FlatsomeBanner;
                    $a->post_type = $value;
                    $a->init();
                }
            }
        }

        // 
        if ($hooks = ($this->settings['adminz_flatsome_action_hook'] ?? "")) {
            foreach ($hooks as $key => $value) {
                if ($value['key'] ?? '' and $value['value'] ?? '') {
                    $hook = $value['key'] ?? '';
                    $shortcode = $value['value'] ?? '';
                    $priority = $value['priority'] ?? 10;

                    //
                    add_action($hook, function () use ($shortcode) {
                        // adminz_fix_override_post_global($shortcode);
                        echo do_shortcode($shortcode);
                    }, $priority);
                }
            }
        }

        // 
        if (($this->settings['create_addition_menus'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->create_addition_menus();
        }

        // 
        if (($this->settings['fix_logo_mobile_width'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->fix_logo_mobile_width();
        }

        // 
        if (($this->settings['create_menu_overlay'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->create_menu_overlay();
        }

        // 
        if (($this->settings['fix_payment_icons_custom'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->fix_payment_icons_custom();
        }

        // 
        if (($this->settings['fix_custom_footer_block_action_hooks'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->fix_custom_footer_block_action_hooks();
        }

        // 
        if (($this->settings['fix_blog_divider'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->fix_blog_divider();
        }

        // 
        if (($this->settings['fix_mobile_overlay'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->fix_mobile_overlay();
        }

        // 
        if (($this->settings['fix_select2'] ?? "") == "on") {
            $a = new \Adminz\Helper\Flatsome();
            $a->fix_select2();
        }

        // 
        if ($this->settings['adminz_flatsome_portfolio_name'] ?? "") {
            $a = new \Adminz\Helper\FlatsomePortfolio();
            $a->rename_post_type($this->settings['adminz_flatsome_portfolio_name']);
        }

        // 
        if ($this->settings['adminz_flatsome_portfolio_category'] ?? "") {
            $a = new \Adminz\Helper\FlatsomePortfolio();
            $a->rename_category($this->settings['adminz_flatsome_portfolio_category']);
        }

        // 
        if ($this->settings['adminz_flatsome_portfolio_tag'] ?? "") {
            $a = new \Adminz\Helper\FlatsomePortfolio();
            $a->rename_tag($this->settings['adminz_flatsome_portfolio_tag']);
        }

        // 
        if ($this->settings['adminz_flatsome_portfolio_product_tax'] ?? "") {
            $product_taxonomy = $this->settings['adminz_flatsome_portfolio_product_tax'];
            $portfolio_post_type = 'featured_item';
            if ($this->settings['adminz_flatsome_portfolio_name'] ?? "") {
                $portfolio_post_type = $this->settings['adminz_flatsome_portfolio_name'];
            }
            new \Adminz\Helper\TaxonomySync($portfolio_post_type, $product_taxonomy);
        }

        // 
        if ($this->settings['post_type_support'] ?? []) {
            foreach ($this->settings['post_type_support'] as $post_type) {
                if ($post_type) {
                    $xxx = new \Adminz\Helper\FlatsomeUxBuilder;
                    $xxx->post_type = $post_type;
                    $xxx->post_type_content_support();
                }
            }
        }

        // 
        if ($post_id_template = ($this->settings['post_id_template'] ?? [])) {
            foreach ($post_id_template as $value) {
                $post_id = $value['key'] ?? '';
                $template = $value['value'] ?? '';
                $source = $value['source'] ?? '';
                if ($post_id) {
                    $xxx = new \Adminz\Helper\FlatsomeUxBuilder;
                    $xxx->post_id = $post_id;
                    $xxx->template_block_id = $template;
                    $xxx->source = $source;
                    $xxx->post_id_layout_support();
                }
            }
        }

        // 
        if ($post_type_template = ($this->settings['post_type_template'] ?? [])) {
            foreach ($post_type_template as $value) {
                $post_type = $value['key'] ?? '';
                $template = $value['value'] ?? '';
                $source = $value['source'] ?? '';
                if ($template and $post_type) {
                    $xxx = new \Adminz\Helper\FlatsomeUxBuilder;
                    $xxx->post_type = $post_type;
                    $xxx->template_block_id = $template;
                    $xxx->source = $source;
                    $xxx->post_type_layout_support();
                }
            }
        }

        // 
        if ($post_type_archive_template = ($this->settings['post_type_archive_template'] ?? [])) {
            foreach ($post_type_archive_template as $value) {
                $post_type = $value['key'] ?? '';
                $template = $value['value'] ?? '';
                $source = $value['source'] ?? '';
                if ($template and $post_type) {
                    $xxx = new \Adminz\Helper\FlatsomeUxBuilder;
                    $xxx->post_type = $post_type;
                    $xxx->template_block_id = $template;
                    $xxx->source = $source;
                    $xxx->post_type_archive_layout_support();
                }
            }
        }

        // 
        if ($taxonomy_layout_support = ($this->settings['taxonomy_layout_support'] ?? [])) {
            foreach ($taxonomy_layout_support as $value) {
                $tax = $value['key'] ?? '';
                $template = $value['value'] ?? '';
                $source = $value['source'] ?? '';
                if ($template) {
                    $xxx = new \Adminz\Helper\FlatsomeUxBuilder;
                    $xxx->taxonomy = $tax;
                    $xxx->tax_template_block_id = $template;
                    $xxx->source = $source;
                    $xxx->taxonomy_layout_support();
                }
            }
        }

        // 
        if ($pack = ($this->settings['adminz_choose_stylesheet'] ?? "")) {

            adminz_add_body_class($pack);
            adminz_add_body_class(
                apply_filters('adminz_pack1_enable_sidebar', true) ? 'enable_sidebar_pack1' : false
            );
            adminz_add_body_class(
                apply_filters('adminz_pack2_enable_sidebar', true) ? 'enable_sidebar_pack2' : false
            );

            add_action('wp_enqueue_scripts', function () use ($pack) {
                if ($pack == 'pack1') {
                    $big_radius = apply_filters('adminz_pack1_big-radius', '10px');
                    $small_radius = apply_filters('adminz_pack1_small-radius', '5px');
                    $form_controls_radius = apply_filters('adminz_pack1_form-controls-radius', '5px');
                    $main_gray = apply_filters('adminz_pack1_main-gray', '#0000000a');
                    $border_color = apply_filters('adminz_pack1_border-color', 'transparent');

                    echo <<<HTML
                    <style type="text/css">
                    :root {
                    --big-radius: $big_radius;
                    --small-radius: $small_radius;
                    --form-controls-radius: $form_controls_radius;
                    --main-gray: $main_gray;
                    --border-color: $border_color;
                    }
                    </style>
                    HTML;
                }

                wp_enqueue_style(
                    'adminz_flatsome_css_' . $pack,
                    ADMINZ_DIR_URL . "assets/css/pack/$pack.css",
                    [],
                    ADMINZ_VERSION,
                    'all'
                );
            });
        }

        // 
        if ($this->settings['adminz_tiny_mce_plugins'] ?? []) {
            foreach ((array) $this->settings['adminz_tiny_mce_plugins'] as $key => $value) {
                if ($value) {
                    $a = new \Adminz\Helper\TinyMce;
                    $a->add_extra($value);
                }
            }
        }

        // 
        if ($this->settings['custom_editor_class'] ?? []) {
            add_filter('flatsome_text_formats', function ($arr) {
                $data = [
                    'title' => 'Adminz custom class',
                    'items' => [],
                ];
                $list = $this->settings['custom_editor_class'] ?? [];
                $list = array_unique($list);
                foreach ((array) $list as $value) {
                    if ($value) {
                        $data['items'][] = [
                            'title' => $value,
                            'inline' => 'span',
                            'classes' => $value,
                        ];
                    }
                }
                $arr[] = $data;
                return $arr;
            });
        }


        // 
        if (($this->settings['adminz_flatsome_viewport_meta'] ?? "") == "on") {
            add_filter('flatsome_viewport_meta', function () {
                return null;
            });
        }

        // 
        if (($this->settings['adminz_flatsome_lightbox_close_btn_inside'] ?? "") == "on") {
            add_filter('flatsome_lightbox_close_btn_inside', function () {
                return true;
            });
        }

        // 
        if ($this->settings['previous_slider_nav_dark'] ?? "") {
            $xxx = new \Adminz\Helper\Flatsome;
            $xxx->set_slider_flickity_button_icon(
                $this->settings['previous_slider_nav_dark'] ?? '',
                $this->settings['previous_slider_nav_light'] ?? '',
                $this->settings['next_slider_nav_dark'] ?? '',
                $this->settings['next_slider_nav_light'] ?? '',
            );
        }



        // 
        if (($this->settings['navigation_item_span'] ?? "") == "on") {
            adminz_navigation_item_span();
        }

        // 
        if (($this->settings['menu_dropdown_fullwidth_container'] ?? "") == "on") {
            adminz_add_body_class('menu_dropdown_fullwidth_container');
        }

        // 
        if (!empty($this->settings['do_shortcode_tag_wp_kses_post'] ?? [])) {
            add_filter('do_shortcode_tag', function ($output, $tag, $attr) {
                if (in_array($tag, $this->settings['do_shortcode_tag_wp_kses_post'])) {
                    foreach ((array)$attr as $key => $value) {
                        $output = str_replace(esc_html($attr[$key]), wp_kses_post($attr[$key]), $output);
                    }
                }
                return $output;
            }, 10, 3);
        }


        // 
        if (($this->settings['fix_archive_query'] ?? '') == 'on') {
            $x = new \Adminz\Helper\Flatsome;
            $x->fix_archive_query();
        }

        // 
        if (($this->settings['fix_tag_query'] ?? '') == 'on') {
            $x = new \Adminz\Helper\Flatsome;
            $x->fix_tag_query();
        }

        // 
        if (($this->settings['adminz_enable_zalo_support'] ?? "") == "on") {
            adminz_enable_zalo_support();
        }

        // 
        if ($this->settings['custom_follows'] ?? []) {
            foreach ((array) $this->settings['custom_follows'] as $item) {
                if ($item) {
                    $xxx = new \Adminz\Helper\FlatsomeFollows;
                    $xxx->name = $item['key'];
                    $xxx->icon = $item['value'];
                    $xxx->init();
                }
            }
        }

        // 
        if (($this->settings['adminz_hide_headermain_on_scroll'] ?? "") == "on") {
            adminz_add_body_class('adminz_hide_headermain_on_scroll');
        }

        // 
        if (($this->settings['adminz_menu_item_active_anchor'] ?? "") == "on") {
            adminz_add_body_class('adminz_menu_item_active_anchor');
        }

        // 
        if (($this->settings['adminz_minimal_page_shop_title'] ?? "") == "on") {
            adminz_add_body_class('adminz_minimal_page_shop_title');
        }

        // 
        if (($this->settings['adminz_section_padding_top'] ?? "") == "on") {
            adminz_add_body_class('adminz_section_padding_top');
        }

        // 
        if (($this->settings['adminz_banner_font_size'] ?? "") == "on") {
            adminz_add_body_class('adminz_banner_font_size');
        }

        // 
        if (($this->settings['blog_2_columns_mobile'] ?? "") == "on") {
            add_action('flatsome_before_blog', function () {
                ob_start(); // Bắt đầu buffering
            });

            add_action('flatsome_after_blog', function () {
                $output = ob_get_clean();
                echo preg_replace(
                    '/(row .*) small-columns-1/',
                    '$1 small-columns-2',
                    $output
                );
            });
        }

        // 
        if (($this->settings['blog_col_large_10_full_width'] ?? "") == "on") {
            adminz_add_body_class('blog_col_large_10_full_width');
        }

        // 
        if (($this->settings['slider_post_item_width_75vw'] ?? "") == "on") {
            adminz_add_body_class('slider_post_item_width_75vw');
        }

        // 
        if (($this->settings['nav_in_pagination'] ?? "") == "on") {
            adminz_add_body_class('nav_in_pagination');
        }

        // 
        if ($pages = ($this->settings['page_for_transparent'] ?? [])) {
            foreach ((array) $pages as $page) {
                if ($page) {
                    $x = new \Adminz\Helper\FlatsomeHeaderTransparent();
                    $x->object_id = $page;
                    $x->search = [
                        '<header id="header" class="header ',
                        '<div id="masthead" class="header-main ',
                    ];
                    $x->replace = [
                        '<header id="header" class="header ' . 'transparent has-transparent ',
                        '<div id="masthead" class="header-main ' . 'nav-light toggle-nav-light ',
                    ];
                    $x->init();
                }
            }
        }

        //
        if ($pages = ($this->settings['page_for_transparent_light_text'] ?? [])) {
            foreach ((array) $pages as $page) {
                if ($page) {
                    $x = new \Adminz\Helper\FlatsomeHeaderTransparent();
                    $x->object_id = $page;
                    $x->search = [
                        '<header id="header" class="header ',
                        '<div id="masthead" class="header-main ',
                    ];
                    $x->replace = [
                        '<header id="header" class="header ' . 'transparent has-transparent ',
                        '<div id="masthead" class="header-main ' . 'nav-dark toggle-nav-dark ',
                    ];
                    $x->init();
                }
            }
        }

        //
        if ($post_types = ($this->settings['custom_blog_post_video_supported'] ?? [])) {
            foreach ((array) $post_types as $post_type) {
                if ($post_type) {
                    $x = new \Adminz\Helper\Flatsome();
                    $x->custom_blog_post_video_supported($post_type);
                }
            }
        }

        // 
        if ($this->settings['adminz_mobile_verticalbox'] ?? "") {
            adminz_add_body_class('adminz_mobile_verticalbox');
        }
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    function register_settings() {
        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz_flatsome_config',
            'Flatsome config',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Download Flatsome theme',
            function () {
                echo <<<HTML
				<small>
					<a target="_blank" href="https://quyle91.net/blog/2024/09/13/tai-theme-flatsome-update-tu-dong/">Click here to see details</a>
				</small>
				HTML;
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Tiny MCE editor extra',
            function () {

                //
                $sub_folders = adminz_listSubdirectories(ADMINZ_DIR . "/includes/tinymce-plugins");
                $options = array_merge(
                    [
                        "" => __('Select'),
                        "alignjustify" => "alignjustify",
                        "subscript" => "subscript",
                        "superscript" => "superscript",
                    ],
                    $sub_folders
                );
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[adminz_tiny_mce_plugins]')
                    ->value($this->settings['adminz_tiny_mce_plugins'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        add_settings_field(
            wp_rand(),
            'Tiny MCE custom class',
            function () {
                //
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[custom_editor_class]')
                    ->value($this->settings['custom_editor_class'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name(null) // sẽ bị override
                    )
                    ->default([
                        'is-xxxlarge',
                        'is-large',
                        'is-larger',
                        'is-small',
                        'is-smaller',
                        'is-xsmall',
                        'is-xxsmall',
                        'heading-font'
                    ])
                    ->render();

                echo <<<HTML
                <small>
                Tiny mce Editor -> Select text -> formats -> adminz class
                </small>
                HTML;
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Custom follows',
            function () {
                // refactor
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[custom_follows]')
                    ->value($this->settings['custom_follows'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('key')
                            ->label('Position')
                            ->attributes([
                                'placeholder' => 'Name',
                            ])
                            ->copyButton(true)
                            ->options([]),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('value')
                            ->label('Icon')
                            ->options(adminz_get_list_icons())
                            ->default(''),

                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Zalo , skype, whatsapp icon',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_enable_zalo_support]')
                    ->value($this->settings['adminz_enable_zalo_support'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Banner support',
            function () {
                //
                $options = get_post_types();
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[adminz_banner_post_types]')
                    ->value($this->settings['adminz_banner_post_types'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Disable Meta viewport',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_flatsome_viewport_meta]')
                    ->value($this->settings['adminz_flatsome_viewport_meta'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Lightbox close button inside',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_flatsome_lightbox_close_btn_inside]')
                    ->value($this->settings['adminz_flatsome_lightbox_close_btn_inside'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Flickity slider nav icons',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('wp_media')
                    ->name($this->option_name . '[previous_slider_nav_dark]')
                    ->value($this->settings['previous_slider_nav_dark'] ?? '')
                    ->addNote('Previous - Slider nav dark')
                    ->copyButton(false)
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('wp_media')
                    ->name($this->option_name . '[next_slider_nav_dark]')
                    ->value($this->settings['next_slider_nav_dark'] ?? '')
                    ->addNote('Next - Slider nav dark')
                    ->copyButton(false)
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('wp_media')
                    ->name($this->option_name . '[previous_slider_nav_light]')
                    ->value($this->settings['previous_slider_nav_light'] ?? '')
                    ->addNote('Previous - Slider nav light')
                    ->copyButton(false)
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('wp_media')
                    ->name($this->option_name . '[next_slider_nav_light]')
                    ->value($this->settings['next_slider_nav_light'] ?? '')
                    ->addNote('Next - Slider nav light')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Navigation item ' . esc_attr("<span>"),
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[navigation_item_span]')
                    ->value($this->settings['navigation_item_span'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Menu dropdown full width - container',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[menu_dropdown_fullwidth_container]')
                    ->value($this->settings['menu_dropdown_fullwidth_container'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Shortcodes with wp_kses_post',
            function () {

                //
                $options = ['ux_menu_link' => 'ux_menu_link',];
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[do_shortcode_tag_wp_kses_post]')
                    ->value($this->settings['do_shortcode_tag_wp_kses_post'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                $note = __('Notes');
                echo <<<HTML
                <small><strong>$note: </strong>Allow html tags inside shortcode</small>
                HTML;
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Ux_hotspot product box small',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[ux_hotspot_product_box_small]')
                    ->value($this->settings['ux_hotspot_product_box_small'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Create Addition Menus',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[create_addition_menus]')
                    ->value($this->settings['create_addition_menus'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Create Menu Overlay',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[create_menu_overlay]')
                    ->value($this->settings['create_menu_overlay'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_config'
        );

        // add section
        add_settings_section(
            'adminz_flatsome_adminz_elements',
            'Flatsome Adminz Elements',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Custom blog posts',
            function () {
                //
                $options = get_post_types();
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[custom_blog_post_video_supported]')
                    ->value($this->settings['custom_blog_post_video_supported'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                echo <<<HTML
                <small>Create <strong>new meta field</strong>: Video Url for post, also enable video popup as directly</small>
                HTML;
            },
            $this->id,
            'adminz_flatsome_adminz_elements'
        );

        // add section
        add_settings_section(
            'adminz_flatsome_fix',
            'Flatsome Fix',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fix query',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_archive_query]')
                    ->value($this->settings['fix_archive_query'] ?? "")
                    ->copyButton(false)
                    ->render();


                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_tag_query]')
                    ->value($this->settings['fix_tag_query'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fix logo mobile width',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_logo_mobile_width]')
                    ->value($this->settings['fix_logo_mobile_width'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fix payment icons custom',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_payment_icons_custom]')
                    ->value($this->settings['fix_payment_icons_custom'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fix custom footer block action hooks',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_custom_footer_block_action_hooks]')
                    ->value($this->settings['fix_custom_footer_block_action_hooks'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fix blog divider',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_blog_divider]')
                    ->value($this->settings['fix_blog_divider'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fix mobile overlay',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_mobile_overlay]')
                    ->value($this->settings['fix_mobile_overlay'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Fix select2',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[fix_select2]')
                    ->value($this->settings['fix_select2'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Hide Header main on scroll - Desktop',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_hide_headermain_on_scroll]')
                    ->value($this->settings['adminz_hide_headermain_on_scroll'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Mobile vertical box',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_mobile_verticalbox]')
                    ->value($this->settings['adminz_mobile_verticalbox'] ?? "")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_fix'
        );

        // add section
        add_settings_section(
            'adminz_flatsome_css',
            'Flatsome CSS',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Css pack',
            function () {
                $options = ['' => __('Select')];
                foreach (glob(ADMINZ_DIR . 'assets/css/pack/*.css') as $filename) {
                    $_key = str_replace(".css", "", basename($filename));
                    $_value = basename($filename);
                    $options[$_key] = $_value;
                }

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('select')
                    ->name($this->option_name . '[adminz_choose_stylesheet]')
                    ->options($options)
                    ->value($this->settings['adminz_choose_stylesheet'] ?? "")
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Transparent header',
            function () {
                //
                $options = [];
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[page_for_transparent]')
                    ->value($this->settings['page_for_transparent'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->optionsTemplate('post_select', ['post_type' => 'page'])
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                $note = __('Notes');
                echo <<<HTML
                <p>
                <small>
                <strong>$note:</strong> 
                Transparent header <strong>dark text</strong> - Only for desktop.
                </small>
                </p>
                HTML;
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Transparent header light text',
            function () {
                //
                $options = [];
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[page_for_transparent_light_text]')
                    ->value($this->settings['page_for_transparent_light_text'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->optionsTemplate('post_select', ['post_type' => 'page'])
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                $note = __('Notes');
                echo <<<HTML
                <p>
                <small>
                <strong>$note:</strong> Transparent header <strong>light text</strong> - Only for desktop.
                </small>
                </p>
                HTML;
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Menu item active anchor link',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_menu_item_active_anchor]')
                    ->value($this->settings['adminz_menu_item_active_anchor'] ?? "")
                    ->addNote("Applies to links in the form #abc")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Section padding top',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_section_padding_top]')
                    ->value($this->settings['adminz_section_padding_top'] ?? "")
                    ->addNote("Bonus 30px for section padding top")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Banner font size',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_banner_font_size]')
                    ->value($this->settings['adminz_banner_font_size'] ?? "")
                    ->addNote("Banner font size 1em")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Archives 2 columns mobile',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[blog_2_columns_mobile]')
                    ->value($this->settings['blog_2_columns_mobile'] ?? "")
                    ->addNote("Mobile: 2 columns, table: 2 columns. Only working with row style.")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Blog col large-10 full width',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[blog_col_large_10_full_width]')
                    ->value($this->settings['blog_col_large_10_full_width'] ?? "")
                    ->addNote(".large-10.col full width")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Flickity slider item width',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[slider_post_item_width_75vw]')
                    ->value($this->settings['slider_post_item_width_75vw'] ?? "")
                    ->addNote("Mobile: .Col in slider column from 100% -> 2/3 screen")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Flickity Nav in pagination',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[nav_in_pagination]')
                    ->value($this->settings['nav_in_pagination'] ?? "")
                    ->addNote("Additional nav buttons in pagination")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_css'
        );



        // add section
        add_settings_section(
            'adminz_flatsome_woocommerce',
            'Woocommerce',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Minimal Filter button',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_minimal_page_shop_title]')
                    ->value($this->settings['adminz_minimal_page_shop_title'] ?? "")
                    ->addNote("Mobile screen")
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_woocommerce'
        );

        // add section
        add_settings_section(
            'adminz_flatsome_portfolio',
            'Portfolio',
            function () {
            },
            $this->id
        );

        // field 
        // add_settings_field(
        //     wp_rand(),
        //     'Enable',
        //     function () {
        //         // field
        //         echo \WpDatabaseHelperV2\Fields\WpField::make()
        //             ->kind('input')
        //             ->type('checkbox')
        //             ->name($this->option_name . '[adminz_flatsome_portfolio_custom]')
        //             ->value($this->settings['adminz_flatsome_portfolio_custom'] ?? "")
        //             ->copyButton(false)
        //             ->render();
        //     },
        //     $this->id,
        //     'adminz_flatsome_portfolio'
        // );


        // field 
        add_settings_field(
            wp_rand(),
            'Portfolio rename',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_flatsome_portfolio_name]')
                    ->attributes([
                        'class' => 'regular-text',
                        'placeholder' => 'Your text'
                    ])
                    ->value($this->settings['adminz_flatsome_portfolio_name'] ?? "")
                    ->addNote('First you can try with Customize->Portfolio->Custom portfolio page <a href="https://www.youtube.com/watch?v=3cl6XCUjOPI">Link</a>')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_portfolio'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Portfolio Categories rename',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_flatsome_portfolio_category]')
                    ->attributes([
                        'class' => 'regular-text',
                        'placeholder' => 'Your text'
                    ])
                    ->value($this->settings['adminz_flatsome_portfolio_category'] ?? "")
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_portfolio'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Portfolio Tags rename',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_flatsome_portfolio_tag]')
                    ->attributes([
                        'class' => 'regular-text',
                        'placeholder' => 'Your text'
                    ])
                    ->value($this->settings['adminz_flatsome_portfolio_tag'] ?? "")
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_portfolio'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Sync portfolio with product',
            function () {
                $options = ['' => __('Select')];
                foreach (get_object_taxonomies('product', 'objects') as $key => $value) {
                    $options[$key] = $value->label;
                }

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('select')
                    ->name($this->option_name . '[adminz_flatsome_portfolio_product_tax]')
                    ->value($this->settings['adminz_flatsome_portfolio_product_tax'] ?? "")
                    ->options($options)
                    ->notes(['Check exists by <strong>post_slug</strong>, Only from post item -> taxonomy term.'])
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_flatsome_portfolio'
        );

        // add section
        add_settings_section(
            'adminz_flatsome_ux_build',
            'UX builder',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post type content support',
            function () {
                //
                $options = get_post_types();
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[post_type_support]')
                    ->value($this->settings['post_type_support'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                $copy = adminz_copy('[adminz_post_field post_field="post_content"][/adminz_post_field]');
                echo <<<HTML
                <p>
                <small>
                Looking for: Remove the post's default <strong>sidebar</strong>? |
                Let's create a <strong>block</strong> valued:
                $copy |
                Then set that block to the post type layout in <strong>Uxbuilder Layout Support</strong><br>
                </small>
                </p>
                HTML;
            },
            $this->id,
            'adminz_flatsome_ux_build'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post ID template',
            function () {
                $value_options = [];
                $query_args = [
                    'post_type' => 'blocks',
                    'post_status' => 'publish',
                    'posts_per_page' => -1
                ];
                $the_query = new \WP_Query($query_args);
                if ($the_query->have_posts()) :
                    while ($the_query->have_posts()) :
                        $the_query->the_post();
                        $value_options["block_id_" . get_the_ID()] = "Block: " . get_the_title();
                    endwhile;
                endif;
                wp_reset_postdata();

                foreach (get_post_types() as $key => $post_type) {
                    $terms = [];
                    $taxonomies = get_object_taxonomies($post_type);
                    if (!empty($taxonomies) and is_array($taxonomies)) {
                        foreach ($taxonomies as $index => $_tax) {
                            $_value = "taxonomy_" . $_tax;
                            $_name = "Terms of: $_tax";
                            $terms[$_value] = $_name;
                        }
                    }
                    $value_options = $value_options + $terms;
                }

                // refactor
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[post_id_template]')
                    ->value($this->settings['post_id_template'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    // ->label('Items')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Post ID')
                            ->optionsTemplate('post_select', ['post_type' => 'any', 'post_status' => 'publish'])
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('value')
                            ->label('Template')
                            ->options($value_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('source')
                            ->label('Source')
                            ->options(
                                [
                                    'index.php' => 'blog template file',
                                    'page-blank-landingpage.php' => 'Page - No Header / No Footer',
                                    'checkout-simple.php' => 'Checkout simple layout',
                                ]
                            )
                            ->copyButton(true),

                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                            'source' => '',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_flatsome_ux_build'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post type template',
            function () {
                $value_options = [];
                $query_args = [
                    'post_type' => 'blocks',
                    'post_status' => 'publish',
                    'posts_per_page' => -1
                ];
                $the_query = new \WP_Query($query_args);
                if ($the_query->have_posts()) :
                    while ($the_query->have_posts()) :
                        $the_query->the_post();
                        $value_options["block_id_" . get_the_ID()] = "Block: " . get_the_title();
                    endwhile;
                endif;
                wp_reset_postdata();

                foreach (get_post_types() as $key => $post_type) {
                    $terms = [];
                    $taxonomies = get_object_taxonomies($post_type);
                    if (!empty($taxonomies) and is_array($taxonomies)) {
                        foreach ($taxonomies as $index => $_tax) {
                            $_value = "taxonomy_" . $_tax;
                            $_name = "Terms of: $_tax";
                            $terms[$_value] = $_name;
                        }
                    }
                    $value_options = $value_options + $terms;
                }

                // refactor
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[post_type_template]')
                    ->value($this->settings['post_type_template'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    // ->label('Items')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Post type')
                            ->optionsTemplate('post_types')
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('value')
                            ->label('Template')
                            ->options($value_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('source')
                            ->label('Source')
                            ->options(
                                [
                                    'index.php' => 'index.php',
                                    'page-blank-landingpage.php' => 'page-blank-landingpage.php',
                                    'checkout-simple.php',
                                ]
                            )
                            ->copyButton(true),

                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                            'source' => '',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_flatsome_ux_build'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Taxonomy layout',
            function () {
                $key_options = array_diff_key(get_taxonomies(), array_flip(get_object_taxonomies('product')));
                $value_options = [];
                $query_args = [
                    'post_type' => 'blocks',
                    'post_status' => 'publish',
                    'posts_per_page' => -1
                ];
                $the_query = new \WP_Query($query_args);
                if ($the_query->have_posts()) :
                    while ($the_query->have_posts()) :
                        $the_query->the_post();
                        $value_options["block_id_" . get_the_ID()] = "Block: " . get_the_title();
                    endwhile;
                endif;
                wp_reset_postdata();

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[taxonomy_layout_support]')
                    ->value($this->settings['taxonomy_layout_support'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Taxonomy')
                            ->options($key_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('value')
                            ->label('Template')
                            ->options($value_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('source')
                            ->label('Source')
                            ->options(
                                [
                                    'index.php' => 'index.php',
                                    'page-blank-landingpage.php' => 'page-blank-landingpage.php',
                                    'checkout-simple.php',
                                ]
                            )
                            ->copyButton(true),
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                            'source' => '',
                        ],
                    ])
                    ->render();

                $note = __('Notes');
                echo <<<HTML
                <p>
                <small>
                <strong>$note*: </strong> Looking for: posts grid?. Use element: <strong>Taxonomy Posts</strong>
                </small>
                </p>
                <p>
                <small>
                <strong>$note**: </strong> <strong>Product taxonomies </strong>should not here!.</strong>
                </small>
                </p>
                HTML;
            },
            $this->id,
            'adminz_flatsome_ux_build'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post type archive template',
            function () {
                $value_options = [];
                $query_args = [
                    'post_type' => 'blocks',
                    'post_status' => 'publish',
                    'posts_per_page' => -1
                ];
                $the_query = new \WP_Query($query_args);
                if ($the_query->have_posts()) :
                    while ($the_query->have_posts()) :
                        $the_query->the_post();
                        $value_options["block_id_" . get_the_ID()] = "Block: " . get_the_title();
                    endwhile;
                endif;
                wp_reset_postdata();

                // refactor
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[post_type_archive_template]')
                    ->value($this->settings['post_type_archive_template'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Post type')
                            ->optionsTemplate('post_types')
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('value')
                            ->label('Template')
                            ->options($value_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('source')
                            ->label('Source')
                            ->options(
                                [
                                    'index.php' => 'index.php',
                                    'page-blank-landingpage.php' => 'page-blank-landingpage.php',
                                    'checkout-simple.php' => 'checkout-simple.php',
                                ]
                            )
                            ->copyButton(true),

                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                            'source' => '',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_flatsome_ux_build'
        );

        // add section
        add_settings_section(
            'adminz_flatsome_hooks',
            'Flatsome template hooks',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Test flatsome hooks',
            function () {
                echo adminz_copy(add_query_arg(['adminz_test_hooks' => 'flatsome',], get_site_url()));
            },
            $this->id,
            'adminz_flatsome_hooks'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Use hooks',
            function () {
                $text = adminz_copy('[adminz_test]');
                echo <<<HTML
                <p> Use: $text </p>
                <br>
                HTML;

                $key_options = [];
                $flatsome_action_hooks = require(ADMINZ_DIR . "includes/file/flatsome_hooks.php");
                foreach ($flatsome_action_hooks as $value) {
                    $key_options[$value] = $value;
                }

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[adminz_flatsome_action_hook]')
                    ->value($this->settings['adminz_flatsome_action_hook'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Hook')
                            ->options($key_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('value')
                            ->attributes(['placeholder' => 'shortcode here'])
                            ->label('Your text')
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('number')
                            ->name('priority')
                            ->attributes(['placeholder' => '10'])
                            ->label('Priority')
                            ->copyButton(true),
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                            'priority' => '10',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_flatsome_hooks'
        );

        // add section
        add_settings_section(
            'adminz_flatsome_miscellaneous',
            'Miscellaneous',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Cheatsheet',
            function () {
                echo adminz_toggle_button(_x('Suggested', 'custom headers'), ".guild_process");
                $classcheatsheet = require(ADMINZ_DIR . 'includes/file/flatsome_css_classes.php');
                $table_content = '';
                foreach ($classcheatsheet as $key => $value) {
                    $table_content .= '<tr valign="top">';
                    $table_content .= '<th>' . $key . '</th>';
                    $table_content .= '<td>';
                    foreach ($value as $classes) {
                        foreach ($classes as $class) {
                            $table_content .= "<small class='adminz_click_to_copy' data-text='$class'>$class</small>";
                        }
                    }
                    $table_content .= '</td>';
                    $table_content .= '</tr>';
                }
                echo <<<HTML
                <table class="form-table guild_process hidden" style="margin-top: 15px;">
                $table_content
                </table>
                HTML;
            },
            $this->id,
            'adminz_flatsome_miscellaneous'
        );
    }
}
