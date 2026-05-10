<?php
function adminz_enable_zalo_support() {
    add_action('customize_register', function ($wp_customize) {
        $wp_customize->add_setting(
            'follow_zalo',
            array('default' => 'https://zalo.me/#')
        );
        $wp_customize->add_control('follow_zalo', array(
            'label' => __('Zalo'),
            'section' => 'follow',
        ));
        $wp_customize->add_setting(
            'follow_skype',
            array('default' => '#')
        );
        $wp_customize->add_control('follow_skype', array(
            'label' => __('Skype'),
            'section' => 'follow',
        ));
        $wp_customize->add_setting(
            'follow_whatsapp',
            array('default' => 'https://wa.me/#')
        );
        $wp_customize->add_control('follow_whatsapp', array(
            'label' => __('Whatsapp'),
            'section' => 'follow',
        ));
    });
}

function adminz_navigation_item_span() {

    // wp menu item
    add_filter('nav_menu_item_title', function ($title, $item, $args, $depth) {
        return '<span class=adminz_span>' . $title . '</span>';
    }, 10, 4);

    // polylang
    if (function_exists('pll_the_languages')) {
        $languages = pll_the_languages(array('raw' => 1));
        $search = [];
        $replace = [];
        foreach ((array) $languages as $key => $language) {
            $search[] = $language['name'];
            $replace[] = "<span class=adminz_span>{$language['name']}</span>";
        }

        add_action('flatsome_before_header', function () {
            ob_start();
        });
        add_action('flatsome_after_header', function () use ($search, $replace) {
            $content = ob_get_clean();
            echo str_replace($search, $replace, $content);
        });
    }
}
