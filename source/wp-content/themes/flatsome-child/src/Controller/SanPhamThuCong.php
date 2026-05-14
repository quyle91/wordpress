<?php

namespace FlatsomeChild\Controller;

class SanPhamThuCong {
    private static $instance = null;
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        add_filter('theme_mod_blog_style', array($this, 'blog_style'));
    }

    function blog_style($blog_style) {
        
        // check is archive of post type: san-pham-thu-cong
        if (is_post_type_archive('san-pham-thu-cong')) {
            $blog_style = '3-col';
        }

        return $blog_style;
    }
}
