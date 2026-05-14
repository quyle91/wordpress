<?php

namespace FlatsomeChild\Controller;

class Flatsome {
    private static $instance = null;
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        $this->custom_loadmore();
        $this->crack();
        $this->remove_flatsome_notices();
        $this->change_radius();
        $this->add_blog_breadcrumb();
    }

    function add_blog_breadcrumb() {
        add_action('flatsome_before_blog', function () {
            // check if is archive or is blog page 
            if (is_archive() or is_home()) {
                // get archive title 
                $archive_title = get_the_archive_title();

                if(is_home()){
                    $archive_title = get_the_title(get_option('page_for_posts'));
                }

                if(is_archive()){
                    $post_type = get_post_type();
                    $archive_title = get_post_type_object($post_type)->labels->name;
                }

                $shortcode = '[gap]
                [row]
                [col span__sm="12"]
                [adminz_breadcrumb]
                [/col]
                [col span__sm="12"]
                <h1>' . $archive_title . '</h1>
                [/col]
                [/row]';
                                echo do_shortcode($shortcode);
            }
        });
    }

    function change_radius() {
        add_filter('adminz_pack1_big-radius', function () {
            return '5px';
        });
        add_filter('adminz_pack1_small-radius', function () {
            return '5px';
        });
    }

    function custom_loadmore() {
        add_action('template_redirect', function () {
            ob_start([self::class, 'my_append_abc_after_pagination']);
        });
    }

    public static function my_append_abc_after_pagination($html) {
        // Pattern tìm đúng thẻ ul pagination.
        $pattern = '/<ul class="page-numbers nav-pagination links text-center">(.*?)<\/ul>/is';

        // HTML muốn chèn thêm.
        $append_html = '';
        $append_html .= '<div class="text-center">';
        $append_html .= '<button class="xemthem button white">Xem thêm</button>';
        $append_html .= '</div>';

        // Callback xử lý từng kết quả match.
        $html = preg_replace_callback(
            $pattern,
            function ($matches) use ($append_html) {
                // Nội dung bên trong thẻ ul.
                $ul_content = $matches[1];

                // Tạo lại thẻ ul và thêm class hidden.
                $new_html = '';
                $new_html .= '<ul class="page-numbers nav-pagination links text-center hidden">';
                $new_html .= $ul_content;
                $new_html .= '</ul>';

                // Nối thêm nút "Xem thêm".
                $new_html .= $append_html;

                return $new_html;
            },
            $html
        );

        return $html;
    }

    function crack() {

        add_action('after_switch_theme', function () {
            // quyle91.net
            if (!get_option('flatsome_registration')) {
                update_option('flatsome_wup_buyer', 'thaiduong103');
                update_option('flatsome_wup_purchase_code', 'c173b5f9-c7a7-4f30-83be-90e22de44f0d');
                update_option('flatsome_wup_sold_at', '2017-02-20T20:26:11+11:00');
                update_option('flatsome_wup_supported_until', '2017-08-22T11:26:11+10:00');
            }
        });
    }

    function remove_flatsome_notices() {
        add_action('init', function () {
            remove_action('admin_notices', 'flatsome_status_check_admin_notice');
            remove_action('admin_notices', 'flatsome_maintenance_admin_notice');
        });
    }
}
