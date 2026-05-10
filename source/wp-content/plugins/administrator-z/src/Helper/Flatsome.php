<?php

namespace Adminz\Helper;

class Flatsome {
    public $adminz_theme_locations = [];
    function __construct() {
        //
    }

    function fix_blog_divider() {
        add_action('init', function () {
            adminz_add_body_class('blog_layout_divider_' . get_theme_mod('blog_layout_divider'));
        });
    }

    function fix_mobile_overlay() {
        add_action('init', function () {
            if (get_theme_mod('mobile_overlay_bg')) {
                adminz_add_body_class('adminz_fix_mobile_overlay_bg');
            }
        });
    }

    function fix_select2() {
        add_action('init', function () {
            if (wp_script_is('select2')) {
                adminz_add_body_class('adminz_select2');
            }
        });
    }

    function set_slider_flickity_button_icon($previous_slider_nav_dark, $previous_slider_nav_light, $next_slider_nav_dark, $next_slider_nav_light) {
        $array = [
            'previous_slider_nav_dark' => [
                'post_id' => $previous_slider_nav_dark,
                'class' => 'slider',
                'button' => 'previous',
            ],
            'previous_slider_nav_light' => [
                'post_id' => $previous_slider_nav_light,
                'class' => 'slider-nav-light',
                'button' => 'previous',
            ],
            'next_slider_nav_dark' => [
                'post_id' => $next_slider_nav_dark,
                'class' => 'slider',
                'button' => 'next',
            ],
            'next_slider_nav_light' => [
                'post_id' => $next_slider_nav_light,
                'class' => 'slider-nav-light',
                'button' => 'next',
            ],
        ];

        foreach ($array as $key => $data) {
            if ($data['post_id']) {
                $url = get_post_field('guid', $data['post_id']);
                if ($url) {
                    add_action('wp_footer', function () use ($url, $data, $key) {
                        echo <<<HTML
						<style type="text/css">
							.{$data['class']} .flickity-prev-next-button.{$data['button']} svg {
								visibility: hidden;
							}
							.{$data['class']} .flickity-prev-next-button.{$data['button']} {
								background-image: url({$url});
								background-repeat: no-repeat;
								background-size: contain;
								background-position: center center;
							}
						</style>
						HTML;
                    });
                }
            }
        }
    }

    function fix_custom_footer_block_action_hooks() {
        // fix lỗi khi chọn custom footer block thì ko chạy vào hook flatsome_before_footer và flatsome_after_footer
        // lấy ra id của block
        // dùng filter hook để custom output của nó
        // wp-content\themes\flatsome\inc\structure\structure-footer.php
        // function: flatsome_page_footer
        // do_shortcode( '[block id="' . $block . '"]' );
        $block = get_theme_mod('footer_block');
        add_filter('do_shortcode_tag', function ($output, $tag, $attr, $m) use ($block) {
            if ($tag === 'block' && ($attr['id'] ?? '') == $block) {
                ob_start();
                do_action('flatsome_before_footer');
                echo do_shortcode($output);
                do_action('flatsome_after_footer');
                $output = ob_get_clean();
            }
            return $output;
        }, 10, 4);
    }

    function fix_payment_icons_custom() {
        if (get_theme_mod('payment_icons_custom')) {
            adminz_add_body_class('adminz_payment_icons_custom');
        }
    }

    function fix_archive_query() {
        // default template taxonomy
        add_action('pre_get_posts', function ($query) {
            // wp-content\themes\flatsome\inc\shortcodes\blog_posts.php:208
            // chỉ fix cho shortcode blog_posts
            if (
                isset($query->query_vars['orderby']) and
                $query->query_vars['orderby'] == 'post__in'
            ) {
                $post_types = array_merge(array_keys(get_post_types()), (array) $query->get('post_type'));
                // echo "<pre>"; print_r('fix_archive_query'); echo "</pre>";
                // echo "<pre>"; print_r($post_types); echo "</pre>";
                $query->set('post_type', $post_types);
            }
        });
    }

    function fix_tag_query() {
        add_action('pre_get_posts', function ($query) {
            if (
                !is_admin()
                && $query->is_main_query()
                && is_tag()
            ) {
                // get all post types that support "post_tag"
                $post_types = get_post_types(['public' => true], 'names');
                $with_tag = ['post'];

                foreach ($post_types as $pt) {
                    $obj = get_post_type_object($pt);
                    if (!empty($obj->taxonomies) && in_array('post_tag', $obj->taxonomies)) {
                        $with_tag[] = $pt;
                    }

                    // fix if post is not registed with post_tag
                    if ($pt == 'post') {
                        $with_tag[] = $pt;
                    }
                }

                // apply
                $with_tag = array_merge($with_tag, (array) $query->get('post_type'));
                $with_tag = array_unique(array_filter($with_tag));
                // echo "<pre>"; print_r('fix_tag_query'); echo "</pre>";
                // echo "<pre>"; print_r($with_tag); echo "</pre>";
                $query->set('post_type', $with_tag);
            }
        });
    }

    function create_addition_menus() {
        $this->adminz_theme_locations = [
            'desktop' => [
                'additional-menu' => '[Adminz] Additional Menu',
                'another-menu' => '[Adminz] Another Menu',
                'extra-menu' => '[Adminz] Extra Menu',
            ],
            'sidebar' => [
                'additional-menu-sidebar' => '[Adminz] Additional Menu - Sidebar',
                'another-menu-sidebar' => '[Adminz] Another Menu - Sidebar',
                'extra-menu-sidebar' => '[Adminz] Extra Menu - Sidebar',
            ],
        ];

        add_filter('flatsome_header_element', function ($arr) {
            foreach ($this->adminz_theme_locations as $navtype => $navgroup) {
                foreach ($navgroup as $key => $value) {
                    $arr[$key] = $value;
                }
            }
            return $arr;
        });

        add_action('flatsome_header_elements', function ($slug) {
            foreach ($this->adminz_theme_locations as $navtype => $navgroup) {
                foreach ($navgroup as $key => $value) {
                    $walker = 'FlatsomeNavDropdown';
                    if ($navtype == 'sidebar') $walker = 'FlatsomeNavSidebar';

                    if ($slug == $key) {
                        flatsome_header_nav($key, $walker); // phpcs:ignore
                    }
                }
            }
        });

        foreach ($this->adminz_theme_locations as $key => $value) {
            register_nav_menus($value);
        }
    }

    function fix_logo_mobile_width() {
        add_action('customize_register', function ($wp_customize) {
            $wp_customize->add_setting(
                'adminz_logo_mobile_max_width',
                array('default' => '')
            );
            $wp_customize->add_control('adminz_logo_mobile_max_width', array(
                'label' => __('Adminz Logo max width. Ex: 100'),
                'section' => 'header_mobile',
            ));
        });
        add_action('wp_footer', function () {
            if ($maxwidth = get_theme_mod('adminz_logo_mobile_max_width')) {
                $maxwidth .= 'px';
                echo <<<HTML
<style type="text/css">
@media only screen and (max-width: 48em) {
#logo {
max-width:$maxwidth;
}
}
</style>
HTML;
            }
        });
    }

    // MENU overlay
    public $menu_overlay_id;
    function create_menu_overlay() {

        $this->menu_overlay_id = 'adminz_menu_overlay_' . wp_rand();

        \Flatsome_Option::add_field('option', array(
            'type' => 'radio-image',
            'settings' => 'adminz_mobile_overlay',
            'label' => __('Adminz Menu Overlay'),
            'section' => 'header_mobile',
            'transport' => 'postMessage',
            'default' => 'left',
            'choices' => array(
                '' => get_template_directory_uri() . '/inc/admin/customizer/img/disabled.svg',
                '01' => ADMINZ_DIR_URL . 'assets/image/relative.svg', // default
                '02' => ADMINZ_DIR_URL . 'assets/image/absolute.svg', // absolute
                // '03' => ADMINZ_DIR_URL . 'assets/image/option-03.svg',
            ),
        ));

        // ------------------------ add new menu icon and 
        add_filter('flatsome_header_element', function ($arr) {
            $arr['adminz_nav_icon'] = '☰ Adminz Nav Icon';
            return $arr;
        });

        // ------------------------ html for new menu icon
        add_action('flatsome_header_elements', function ($slug) {
            if ($slug == 'adminz_nav_icon') {

                $icon_style = get_theme_mod('menu_icon_style');
                $div_start = $icon_style ? '<div class="header-button">' : '';
                $div_end = $icon_style ? '</div>' : '';
                $class = get_flatsome_icon_class($icon_style, 'small');

                echo <<<HTML
<li class="nav-icon has-icon">
$div_start
<a href="javascript:void(0)" class="adminz_nav_icon $class">
<span class="menu-title uppercase hide-for-small">Menu</span>
<span class="menu-icon">
<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"></path>
</svg>
</span>
</a>
$div_end
</li>
HTML;
            }
        });

        if (get_theme_mod('adminz_mobile_overlay', '')) {

            add_filter('body_class', function ($classes) {
                $levels = get_theme_mod('mobile_submenu_levels', '1');
                $classes[] = 'mobile-submenu-slide';
                $classes[] = 'mobile-submenu-slide-levels-' . $levels;
                return $classes;
            }, 10, 1);

            // move current mobile menu
            remove_action('wp_footer', 'flatsome_mobile_menu', 7);
            add_action('wp_footer', function () {
                $text = adminz_test(['content' => 'Removed by administrator z. <br> Go to customize -> Header mobile menu -> Adminz Menu Overlay -> X']);
                echo <<<HTML
<div id="main-menu" class="mfp-hide">
$text
</div>
HTML;
            }, 7);

            // sub menu mobile
            add_action('flatsome_header_wrapper', function () {
                // copy from \wp-content\themes\flatsome\template-parts\overlays\overlay-menu.php
                $flatsome_mobile_overlay = get_theme_mod('mobile_overlay');
                $flatsome_mobile_sidebar_classes = array(
                    'mobile-sidebar',
                    'no-scrollbar',

                );
                $flatsome_nav_classes = array('nav', 'nav-sidebar', 'nav-vertical', 'nav-uppercase');
                $flatsome_levels = 0;

                if ('center' == $flatsome_mobile_overlay) {
                    $flatsome_nav_classes[] = 'nav-anim';
                }

                if (
                    'center' != $flatsome_mobile_overlay &&
                    'slide' == get_theme_mod('mobile_submenu_effect')
                ) {
                    $flatsome_levels = (int) get_theme_mod('mobile_submenu_levels', '1');

                    $flatsome_mobile_sidebar_classes[] = 'mobile-sidebar-slide';
                    $flatsome_nav_classes[] = 'nav-slide';

                    for ($level = 1; $level <= $flatsome_levels; $level++) {
                        $flatsome_mobile_sidebar_classes[] = "mobile-sidebar-levels-{$level}";
                    }
                }

                $wrap_classes = [
                    'adminz_menu_overlay',
                    'style_' . get_theme_mod('adminz_mobile_overlay', ''),
                    get_theme_mod('mobile_overlay_color'),
                    'hidden',
                ];
?>
                <div class="<?= implode(" ", $wrap_classes) ?>">
                    <div id="<?= esc_attr($this->menu_overlay_id) ?>"
                        class="<?php echo esc_attr(implode(' ', $flatsome_mobile_sidebar_classes)); ?>" <?php echo $flatsome_levels ? ' data-levels="' . esc_attr($flatsome_levels) . '"' : ''; ?>>
                        <?php do_action('flatsome_before_sidebar_menu'); ?>
                        <div class="sidebar-menu no-scrollbar" style="transition: transform 0.3s;">
                            <?php do_action('flatsome_before_sidebar_menu_elements'); ?>
                            <?php if (get_theme_mod('mobile_sidebar_tabs')) : ?>
                                <ul class="sidebar-menu-tabs flex nav nav-line-bottom nav-uppercase">
                                    <li class="sidebar-menu-tabs__tab active">
                                        <a class="sidebar-menu-tabs__tab-link" href="#">
                                            <span
                                                class="sidebar-menu-tabs__tab-text"><?php echo get_theme_mod('mobile_sidebar_tab_text') ? esc_html(get_theme_mod('mobile_sidebar_tab_text')) : esc_html__('Menu', 'flatsome'); ?></span>
                                        </a>
                                    </li>
                                    <li class="sidebar-menu-tabs__tab">
                                        <a class="sidebar-menu-tabs__tab-link" href="#">
                                            <span
                                                class="sidebar-menu-tabs__tab-text"><?php echo get_theme_mod('mobile_sidebar_tab_2_text') ? esc_html(get_theme_mod('mobile_sidebar_tab_2_text')) : esc_html__('Categories', 'flatsome'); ?></span>
                                        </a>
                                    </li>
                                </ul>
                                <ul class="<?php echo esc_attr(implode(' ', $flatsome_nav_classes)); ?> hidden" data-tab="2">
                                    <?php flatsome_header_elements('mobile_sidebar_tab_2', 'sidebar'); ?>
                                </ul>
                                <ul class="<?php echo esc_attr(implode(' ', $flatsome_nav_classes)); ?>" data-tab="1">
                                    <?php flatsome_header_elements('mobile_sidebar', 'sidebar'); ?>
                                </ul>
                            <?php else : ?>
                                <ul class="<?php echo esc_attr(implode(' ', $flatsome_nav_classes)); ?>" data-tab="1">
                                    <?php flatsome_header_elements('mobile_sidebar', 'sidebar'); ?>
                                </ul>
                            <?php endif; ?>
                            <?php do_action('flatsome_after_sidebar_menu_elements'); ?>
                        </div>
                        <?php do_action('flatsome_after_sidebar_menu'); ?>
                    </div>
                </div>
<?php
            });
        }
    }

    function custom_blog_post_video_supported($post_type) {
        add_action('init', function () use ($post_type) {
            // B1. Kiểm tra post type có support post-formats không
            $is_post_type_support = post_type_supports($post_type, 'post-formats');
            if (!$is_post_type_support) {
                return;
            }

            // B2. Lấy danh sách post format mà theme đang support
            $theme_supports = get_theme_support('post-formats');
            if (!is_array($theme_supports)) {
                return;
            }
            if (!isset($theme_supports[0]) || !is_array($theme_supports[0])) {
                return;
            }

            // B3. Kiểm tra có support format video không
            $supported_formats = $theme_supports[0];
            $is_video_supported = in_array('video', $supported_formats, true);
            if (!$is_video_supported) {
                return;
            }

            \WpDatabaseHelperV2\Meta\WpMeta::make()
                ->post_type('post')
                ->label('Adminz Post Video Supported')
                ->fields(
                    [
                        // Field cơ bản
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('_adminz_video_url')
                            ->attributes(['placeholder' => 'Enter video url...'])
                            ->label('Full video url')
                            ->default(''),
                    ]
                )->register();
        });
    }
}
