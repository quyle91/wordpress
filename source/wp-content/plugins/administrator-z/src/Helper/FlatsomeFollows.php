<?php

namespace Adminz\Helper;

/**
=======================================
BUGS: When element follow has only customize. Example: only Zalo link. 
Have no idea to check $use_global_link
=======================================
 */
class FlatsomeFollows {

    public $name;
    public $icon;

    function __construct() {
    }

    function init() {
        if (!$this->name or !$this->icon) {
            return;
        }
        $this->customize_settings();
        $this->add_flatsome_icon();
        $this->custom_builder();
        $this->custom_shortcode();
    }

    function customize_settings() {
        \Flatsome_Option::add_field('option', array(
            'type'      => 'text',
            'settings'  => 'follow_' . $this->icon,
            'label'     => $this->name,
            'section'   => 'follow',
            'transport' => 'postMessage', //$transport,
            'default'   => '',
        ));
    }

    function add_flatsome_icon() {
        add_filter('flatsome_icon', function ($icon_html, $name, $size, $atts) {
            if ($name == 'icon-' . $this->icon) {
                return adminz_get_icon(
                    $this->icon,
                    []
                );
            }
            return $icon_html;
        }, 10, 4);
    }

    function custom_builder() {
        add_filter('ux_builder_shortcode_data', function ($data, $tag) {
            if ($tag == 'follow') {
                $data['options']['social_icons']['options'][$this->icon] = [
                    'type'    => 'textfield',
                    'heading' => $this->name,
                    'default' => '',
                ];
            }
            return $data;
        }, 10, 2);
    }

    function custom_shortcode() {

        // shortcode: shortcode_atts 
        add_filter("shortcode_atts_follow", function ($out, $pairs, $atts, $shortcode) {
            $name    = $this->icon;
            $default = '';
            if (array_key_exists($name, $atts)) {
                $out[$name] = $atts[$name];
            } else {
                $out[$name] = $default;
            }
            return $out;
        }, 10, 4);

        // shortcode: $follow_links
        add_filter('flatsome_follow_links', function ($follow_links, $args) {
            extract($args);
            $_value = $atts[$this->icon] ?? '';

            if ($use_global_link) {
                $_value = get_theme_mod('follow_' . $this->icon);
            }

            $follow_links[$this->icon] = [
                'enabled'  => !empty($_value),
                'atts'     => array(
                    'href'       => esc_url($_value),
                    'target'     => '_blank',
                    'rel'        => 'noopener nofollow',
                    'data-label' => $this->name,
                    'class'      => "$style adminz_follow tooltip",
                    'title'      => sprintf(__("Follow on %s", 'flatsome'), $this->name), // phpcs:ignore
                    'aria-label' => sprintf(__("Follow on %s", 'flatsome'), $this->name), // phpcs:ignore
                ),
                'icon'     => '<span>' . get_flatsome_icon('icon-' . $this->icon) . '</span>',
                'priority' => 180,
            ];
            return $follow_links;
        }, 10, 2);
    }
}
