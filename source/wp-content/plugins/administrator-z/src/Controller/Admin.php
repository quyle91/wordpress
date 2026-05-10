<?php

namespace Adminz\Controller;

final class Admin {
    private static $instance = null;
    public $default_slug = ADMINZ_SLUG;
    public $nav = [];

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        add_action('admin_menu', [$this, 'create_option_page']);
        add_filter('plugin_action_links_' . ADMINZ_BASENAME, [$this, 'add_action_links']);
        add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'wp_enqueue_script']);
    }

    function create_option_page() {
        add_submenu_page(
            'tools.php',
            'Administrator Z',
            'Administrator Z',
            'manage_options',
            $this->default_slug,
            [$this, 'html']
        );
    }

    function get_current_group() {
        $option_group = $this->default_slug;
        if (!empty($_GET['group'])) {
            $option_group = $_GET['group'];
        }
        return $option_group;
    }

    function html() {
?>
        <div class="wrap adminz_wrap">
            <h2> <?= ADMINZ_NAME ?> </h2>

            <!-- nav -->
            <div class="nav adminz_nav">
                <?php
                $this->nav = apply_filters('adminz_option_page_nav', $this->nav);
                $current_group = $this->get_current_group();
                foreach ($this->nav as $group => $name) {
                    $link = add_query_arg(
                        [
                            'group' => $group,
                        ],
                        $this->get_adminz_tool_url()
                    );
                    $classes = ['adminz_option_page_nav button'];
                    if ($current_group == $group) {
                        $classes[] = "button-primary";
                    }
                ?>
                    <a class="<?= implode(" ", $classes); ?>" href="<?= esc_url($link); ?>">
                        <?= esc_attr($name) ?>
                    </a>
                <?php
                }
                ?>
            </div>

            <!-- section wordpress -->
            <form method="post" action="options.php">
                <?php
                $current_group = $this->get_current_group();
                settings_fields($current_group);
                do_settings_sections($current_group);
                submit_button();
                ?>
            </form>
        </div>
<?php
    }

    function get_adminz_tool_url() {
        return admin_url('tools.php' . '?page=' . $this->default_slug);
    }

    function add_action_links($actions) {
        $mylinks = array(
            '<a href="' . $this->get_adminz_tool_url() . '">' . __('Tools') . '</a>',
        );
        $actions = array_merge($actions, $mylinks);
        return $actions;
    }

    function wp_enqueue_script() {

        wp_enqueue_style(
            'adminz_css',
            ADMINZ_DIR_URL . "assets/css/adminz.css",
            [],
            ADMINZ_VERSION,
            'all'
        );

        wp_enqueue_script(
            'adminz_js',
            ADMINZ_DIR_URL . "assets/js/adminz.js",
            adminz_is_flatsome() ? ['flatsome-js'] : [],
            ADMINZ_VERSION,
            true,
        );

        $args = apply_filters('adminz_js_inline_args', [
            'post_id' => get_the_ID(),
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('adminz_js'),
            'script_debug' => (defined('SCRIPT_DEBUG') and SCRIPT_DEBUG),
            'i18n' => [
                'readmore' => __("Read more..."),
            ],
        ]);

        wp_add_inline_script(
            'adminz_js',
            'const adminz_js = ' . json_encode($args),
            'before'
        );
    }

    function admin_enqueue_scripts($hook) {

        wp_enqueue_style(
            'adminz_admin',
            ADMINZ_DIR_URL . "assets/css/adminz-admin.css",
            [],
            ADMINZ_VERSION,
            'all'
        );

        wp_enqueue_script(
            'adminz_admin',
            ADMINZ_DIR_URL . "assets/js/adminz-admin.js",
            [],
            ADMINZ_VERSION,
            true,
        );

        wp_add_inline_script(
            'adminz_admin',
            'const adminz_js = ' . json_encode(
                array(
                    'post_id' => get_the_ID(),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('adminz_js'),
                    'script_debug' => (defined('SCRIPT_DEBUG') and SCRIPT_DEBUG),
                )
            ),
            'before'
        );
    }
}
