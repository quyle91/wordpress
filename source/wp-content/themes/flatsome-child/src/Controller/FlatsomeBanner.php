<?php

namespace FlatsomeChild\Controller;

class FlatsomeBanner {
    private static $instance = null;
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        $this->page_metabox();
        add_action('flatsome_before_blog', [$this, 'flatsome_after_header']);
        add_action('flatsome_before_page', [$this, 'flatsome_after_header']);
    }

    function page_metabox() {
        $post_types = ['post', 'page', 'nghi-le-truyen-thong', 'nghe-thu-cong'];

        foreach ($post_types as $post_type) {
            \WpDatabaseHelperV2\Meta\WpMeta::make()
                ->post_type($post_type)
                ->label('Banner for ' . $post_type)
                ->fields(
                    [
                        // Field cơ bản
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('checkbox')
                            ->name('enable_banner')
                            ->label('Enable banner')
                            ->adminColumn(true),

                        // Field cơ bản
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('wp_media')
                            ->name('banner_image')
                            ->label('Banner image')
                            ->adminColumn(true),

                        // Field cơ bản
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('banner_text')
                            ->label('Banner text')
                            ->adminColumn(true)
                    ]
                )->register();
        }
    }

    function flatsome_after_header() {
        // is home page, blog page or archive page
        if (is_home() || is_front_page() || is_archive()) {
            return;
        }

        // 
        if (get_post_meta(get_the_ID(), 'enable_banner', true) != 'on') {
            return;
        }

        //
        $banner_image = get_post_meta(get_the_ID(), 'banner_image', true);
        $banner_text = get_post_meta(get_the_ID(), 'banner_text', true);
        if (!$banner_image) {
            return;
        }

        ob_start();
        echo '[gap]';
        echo '[row]';
        echo '[col span__sm="12"]';
        echo '[adminz_breadcrumb]';
        echo '[gap]';
        echo '<h2>' . get_the_title() . '</h2>';
        echo '[/col]';
        echo '[/row]';
        $bg_overlay = 'rgba(51, 51, 51, 0.488)';
        if (!$banner_text) {
            $bg_overlay = '';
        }
        echo '[section class="xbanner" bg="' . $banner_image . '" bg_size="original" bg_overlay="' . $bg_overlay . '" dark="true" height="720px" height__sm="200px" height__md="400px]';
        echo '[row]';
        echo '[col span__sm="12"]';
        echo '<h1 class="MTD_Carrington">' . $banner_text . '</h1>';
        echo '[/col]';
        echo '[/row]';
        echo '[/section]';
        echo do_shortcode(ob_get_clean());
    }
}
