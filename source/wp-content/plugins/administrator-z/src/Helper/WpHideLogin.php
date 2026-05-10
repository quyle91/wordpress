<?php

namespace Adminz\Helper;

class WpHideLogin {
    private $custom_login_slug;

    public function __construct() {
        //
    }

    public function init($slug) {
        if (!$slug) {
            return;
        }

        $this->custom_login_slug = $slug;

        // Hook chính cho rewrite và URL
        add_action('init', [$this, 'flush_rewrite_rule']);
        add_action('init', [$this, 'add_rewrite_rules']);
        add_action('parse_request', [$this, 'handle_custom_login']);

        // Filter URL
        add_filter('site_url', [$this, 'replace_login_url'], 10, 4);
        add_filter('network_site_url', [$this, 'replace_login_url'], 10, 3);
        add_filter('login_url', [$this, 'replace_login_url'], 10, 3);
        add_filter('lostpassword_url', [$this, 'replace_login_url'], 10, 2);
        add_filter('register_url', [$this, 'replace_login_url'], 10, 1);
        add_filter('wp_redirect', [$this, 'check_redirect'], 10, 2);

        // Xử lý logout
        add_action('wp_logout', [$this, 'redirect_after_logout']);

        // Loại bỏ redirect admin mặc định
        remove_action('template_redirect', 'wp_redirect_admin_locations', 1000);

        // Kiểm tra và áp dụng chặn login
        add_action('wp_loaded', [$this, 'block_default_login']);
    }

    function flush_rewrite_rule() {
        if (!empty($_POST['adminz_admin']['adminz_admin_url'])) {
            flush_rewrite_rules();
        }
    }

    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^' . $this->custom_login_slug . '/?$',
            'index.php?adminz_hide_admin_tag=login',
            'top'
        );
        add_rewrite_tag('%adminz_hide_admin_tag%', '([^&]+)');
    }

    public function handle_custom_login($wp) {
        // Kiểm tra nếu đây là request đến trang đăng nhập tùy chỉnh
        if (isset($wp->query_vars['adminz_hide_admin_tag']) && $wp->query_vars['adminz_hide_admin_tag'] === 'login') {
            // Người dùng đã đăng nhập thì chuyển hướng đến admin
            if (is_user_logged_in() && !isset($_REQUEST['action'])) {
                wp_safe_redirect(admin_url());
                exit;
            }

            // Thiết lập biến môi trường cần thiết
            $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
            $error = isset($_REQUEST['error']) ? $_REQUEST['error'] : '';
            $redirect_to = isset($_REQUEST['redirect_to']) ? $_REQUEST['redirect_to'] : admin_url();
            $user_login = isset($_REQUEST['log']) ? $_REQUEST['log'] : '';

            // Thiết lập các biến toàn cục mà wp-login.php có thể cần
            $GLOBALS['error'] = $error;
            $GLOBALS['action'] = $action;
            $GLOBALS['user_login'] = $user_login;
            $GLOBALS['interim_login'] = isset($_REQUEST['interim-login']) ? $_REQUEST['interim-login'] : false;

            // Định nghĩa các hằng số mà wp-login.php có thể cần
            // if (!defined('LOGINPAGE')) {
            //     define('LOGINPAGE', true);
            // }

            // Tải trang login
            global $pagenow;
            $pagenow = 'wp-login.php';

            // Thiết lập tiêu đề và đường dẫn
            status_header(200);

            // Tải wp-login.php
            require_once ABSPATH . 'wp-login.php';
            exit;
        }
    }

    public static function is_allowed_action(): bool {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $request_path = basename(parse_url($request_uri, PHP_URL_PATH));

        $allowed_actions = [
            'wp-cron.php',
            'xmlrpc.php',
            'wp-comments-post.php',
            'wp-signup.php',
            'wp-activate.php',
        ];

        return in_array($request_path, $allowed_actions, true);
    }


    public function block_default_login() {
        global $pagenow;

        $request_uri = $_SERVER['REQUEST_URI'] ?? '';

        // Cho phép các endpoint quan trọng
        if (self::is_allowed_action()) {
            return;
        }

        // Cho phép REST API hoạt động
        if ((defined('REST_REQUEST') && REST_REQUEST) || strpos($request_uri, '/wp-json/') !== false) {
            return;
        }

        // Kiểm tra trang login
        $is_wp_login = ($pagenow === 'wp-login.php' && strpos($request_uri, $this->custom_login_slug) === false);

        // Kiểm tra wp-admin
        $is_wp_admin = (strpos($request_uri, '/wp-admin') !== false && !is_user_logged_in());

        // Cho phép admin-ajax.php
        if ($is_wp_admin && strpos($request_uri, 'admin-ajax.php') !== false) {
            return;
        }

        // Chặn truy cập
        if ($is_wp_login || $is_wp_admin) {
            $this->send_404();
        }
    }

    public function replace_login_url($url, $path = '', $scheme = null, $blog_id = null) {
        if (strpos($url, 'wp-login.php') !== false) {
            // Tạo URL mới với slug tùy chỉnh
            $new_url = home_url("/{$this->custom_login_slug}", $scheme);

            // Xử lý các tham số truy vấn
            $query_params = [];
            $url_parts = parse_url($url);

            if (isset($url_parts['query'])) {
                parse_str($url_parts['query'], $query_params);
            }

            // Loại bỏ redirect_to nếu trỏ đến wp-login.php
            if (
                isset($query_params['redirect_to']) &&
                strpos($query_params['redirect_to'], 'wp-login.php') !== false
            ) {
                unset($query_params['redirect_to']);
            }

            // Thêm lại tham số vào URL mới
            if (!empty($query_params)) {
                $new_url = add_query_arg($query_params, $new_url);
            }

            return $new_url;
        }

        return $url;
    }

    public function check_redirect($location, $status) {
        // Thay thế wp-login.php bằng trang tùy chỉnh trong redirect
        if (strpos($location, 'wp-login.php') !== false && strpos($location, $this->custom_login_slug) === false) {
            $location = $this->replace_login_url($location);
        }

        return $location;
    }

    public function redirect_after_logout() {
        wp_safe_redirect(home_url("/{$this->custom_login_slug}"));
        exit;
    }

    private function send_404() {
        global $wp_query;
        status_header(404);
        $wp_query->set_404();
        nocache_headers();
        include(get_404_template());
        exit;
    }
}
