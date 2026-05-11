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
        \WpDatabaseHelperV2\Meta\WpMeta::make()
            ->post_type('post')
            ->label('Banner page')
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
                        ->label('banner image')
                        ->adminColumn(true)
                ]
            )->register();
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
        if (!$banner_image) {
            return;
        }
    
        ob_start();
        echo '[section padding="0px"]';
        echo '[row]';
        echo '[col span__sm="12"]';
        echo '[adminz_breadcrumb]';
        echo '<h2>' . get_the_title() . '</h2>';
        echo '[/col]';
        echo '[/row]';
        echo '[ux_image id="' . $banner_image . '" image_size="original" height="37%"]';

        if (is_singular('post')) {
            echo '[gap]';
            echo '[row]';
            echo '[col span__sm="12"]';
            echo '[adminz_breadcrumb]';
            echo '<p>';
            // author
            $post_id = get_the_ID();
            $author_id = get_post_field('post_author', $post_id);
            echo '<span>' . get_the_author_meta('display_name', $author_id) . '</span>';
            // post date
            echo '<span class="op-5"> | ';
            echo get_the_date();
            echo '</span>';
            echo '<p>';
            echo '[/col]';
            echo '[/row]';
        }
        echo '[/section]';
        echo do_shortcode(ob_get_clean());
    }
}
