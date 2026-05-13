<?php

namespace FlatsomeChild\Controller;

class NghiLeTruyenThong {
    private static $instance = null;
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        //
        $this->page_metabox();
        $this->shortcode_nghi_le_truyen_thong(); // dùng trang list
    }

    function page_metabox() {
        $post_types = ['nghi-le-truyen-thong'];

        foreach ($post_types as $post_type) {
            \WpDatabaseHelperV2\Meta\WpMeta::make()
                ->post_type($post_type)
                ->label('Extra thumbnails')
                ->fields(
                    [
                        // Field cơ bản
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('wp_media')
                            ->name('big_image')
                            ->label('Big image')
                            ->adminColumn(true),

                        // Field cơ bản
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('wp_media')
                            ->name('small_image')
                            ->label('Small image')
                            ->adminColumn(true),
                        
                    ]
                )->register();
        }
    }

    function shortcode_nghi_le_truyen_thong() {

        if (!class_exists('\Adminz\Helper\FlatsomeELement')) {
            return;
        }

        $name = 'nghi_le_truyen_thong';
        $___ = new \Adminz\Helper\FlatsomeELement;
        $___->shortcode_title = ucfirst(str_replace('_', ' ', $name));
        $___->shortcode_name = $name;
        $___->shortcode_icon = 'text';
        $___->options = [
            // 'post_parent' => [
            // 'type'=> 'textfield',
            // 'heading' => 'Parent Id',
            // ],
            'advanced_options' => require(get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php'),
        ];
        $___->shortcode_callback = function ($atts, $content = null) use ($name) {
            $atts = apply_filters($name . '_atts', $atts);
            $atts = shortcode_atts(array(
                'class' => '',
                'visibility' => '',
                // 'post_parent'=> get_the_ID(),
            ), $atts);
            extract($atts);

            $classes = [$name];
            if (!empty($class)) $classes[] = $class;
            if (!empty($visibility)) $classes[] = $visibility;

            ob_start();
            echo "<div class='" . implode(' ', $classes) . "'>";


            // code here 
            $_args = [
                'post_type' => ['nghi-le-truyen-thong'],
                'post_status' => ['publish'],
                'posts_per_page' => -1,
            ];

            $___global_post = $GLOBALS['post']; // save global post before query
            $__the_query = new \WP_Query($_args);
            if ($__the_query->have_posts()) {
                while ($__the_query->have_posts()) : $__the_query->the_post();
                    echo '[row]';
                    echo '[col span__sm="12"]';
                    $link = get_permalink();
                    echo '<h3><a href="' . $link . '">' . get_the_title() . '</a></h3>';
                    echo '[/col]';
                    echo '[col span="8" span__sm="12"]';
                    $big_image = get_post_meta(get_the_ID(), 'big_image', true);
                    echo '[ux_image id=" ' . $big_image . '" height="31%"]';
                    echo '[/col]';
                    echo '[col span="4" span__sm="12"]';
                    $small_image = get_post_meta(get_the_ID(), 'small_image', true);
                    echo '[ux_image id=" ' . $small_image . '" height="48%"]';
                    $link = get_permalink();
                    echo '[button text="Xem thêm" color="white" style="outline" expand="true" link="' . $link . '"]';
                    echo '[/col]';
                    echo '[/row]';
                endwhile;
                wp_reset_postdata();
                $GLOBALS['post'] = $___global_post; // restore global post after query
            }
            echo '</div>';
            // return ob_get_clean();
            return do_shortcode(ob_get_clean());
        };
        $___->general_element();
    }
}
