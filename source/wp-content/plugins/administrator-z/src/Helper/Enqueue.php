<?php

namespace Adminz\Helper;

class Enqueue {

    function __construct() {
        //
    }

    static function adminz_enqueue_js($js) {
        add_action('wp_head', function () use ($js) {
            echo <<<HTML
        <script id="adminz_custom_js" type="text/javascript">
            {$js}
        </script>
        HTML;
        }, PHP_INT_MAX);
    }

    static function adminz_enqueue_css($css) {
        add_action('wp_head', function () use ($css) {
            echo <<<HTML
        <style id="adminz_custom_css" type="text/css">
            {$css}
        </style>
        HTML;
        }, PHP_INT_MAX);
    }

    static function adminz_enqueue_font_icon($custom_font_icons) {
        if (empty($custom_font_icons)) {
            return;
        }

        foreach ((array)$custom_font_icons as $key => $value) {
            $font_id = $value['font_id'];
            $items = $value['items'];

            if ($font_id and !empty($items)) {
                add_action('wp_enqueue_scripts', function () use ($font_id, $items) {
                    $font_url = get_post_field('guid', $font_id);
                    $font_type = pathinfo($font_url, PATHINFO_EXTENSION);
                    $font_format = match (strtolower($font_type)) {
                        'ttf'   => 'truetype',
                        'otf'   => 'opentype',
                        'woff'  => 'woff',
                        'woff2' => 'woff2',
                        default => 'truetype', // mặc định nếu không nhận ra
                    };

                    // preload
                    echo <<<HTML
                    <link rel="preload" href="{$font_url}" as="font" crossorigin="anonymous">
                    HTML;

                    // css
                    $css = '';
                    foreach ((array)$items as $selector => $item) {
                        $selector = $item['class'] ?? '';
                        $code = $item['icon_code'] ?? '';
                        
                        if ($selector and $code) {
                            $selector = "$selector:before";
                            $attrs = [
                                'font-family' => "'adminz-flatsome-font' !important",
                                'content'     => '"\\' . $code . '" !important',
                            ];

                            $css .= $selector . "{";
                            foreach ($attrs as $key => $value) {
                                $css .= "{$key}:{$value};";
                            }
                            $css .= "}";
                        }
                    }

                    echo <<<HTML
                    <style type="text/css">
                        @font-face {
                            font-family: 'adminz-flatsome-font';
                            src: url('{$font_url}') format('{$font_format}');
                            font-weight: normal;
                            font-style: normal;
                            font-stretch:  normal;
                            font-display: swap;
                        }
                        {$css}
                    </style>
                    HTML;
                });
            }
        }
    }

    static function adminz_enqueue_font_text($fonts) {

        // wp_head
        add_action('wp_enqueue_scripts', function () use ($fonts) {

            // preload
            foreach ($fonts as $key => $font) {
                if ($font[0]) {
                    $font_url = str_starts_with($font[0], 'http') ? $font[0] : get_post_field('guid', $font[0]);
                    echo <<<HTML
                    <link rel="preload" href="$font_url" as="font" crossorigin="anonymous">
                HTML;
                }
            }

            // font_face
            $font_face = [];
            foreach ($fonts as $key => $font) {
                if ($font[0]) {
                    $font_url = str_starts_with($font[0], 'http') ? $font[0] : get_post_field('guid', $font[0]);
                    $font_type = pathinfo($font_url, PATHINFO_EXTENSION);
                    $font_format = match (strtolower($font_type)) {
                        'ttf'   => 'truetype',
                        'otf'   => 'opentype',
                        'woff'  => 'woff',
                        'woff2' => 'woff2',
                        default => 'truetype', // mặc định nếu không nhận ra
                    };

                    $font_face[] = <<<HTML
                    @font-face {
                        src: url( $font_url) format( '$font_format' );
                        font-family:  '$font[1]';
                        font-weight:  $font[2];
                        font-style:  $font[3];
                        font-stretch:  $font[4];
                        font-display: swap;
                    }
                HTML;
                }
            }
            $font_face = array_unique($font_face);
            $font_face = implode(" ", $font_face);
            echo <<<HTML
            <style id="adminz_custom_fonts" type="text/css">
                {$font_face}
            </style>
        HTML;
        }, PHP_INT_MAX);
    }

    static function adminz_enqueue_font_supported($fonts) {

        add_action('wp_enqueue_scripts', function () use ($fonts) {

            foreach ($fonts as $key => $value) {
                switch ($value) {
                    case 'fontawesome':
                        echo <<<HTML
                        <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
                        <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></noscript>
                    HTML;
                        break;
                    case 'lato':
                        wp_enqueue_style(
                            'adminz_lato',
                            ADMINZ_DIR_URL . 'assets/fonts/lato/all.css',
                            array(),
                            ADMINZ_VERSION,
                            'all'
                        );
                        $adminz_dir_url = ADMINZ_DIR_URL;
                        echo <<<HTML
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Regular.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Italic.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Thin.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Bold.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Heavy.woff2" as="font" crossorigin="anonymous">
                        <link rel="preload" href="{$adminz_dir_url}assets/fonts/lato/fonts/Lato-Black.woff2" as="font" crossorigin="anonymous">
                    HTML;
                        break;
                }
            }
        }, PHP_INT_MAX);
    }
}
