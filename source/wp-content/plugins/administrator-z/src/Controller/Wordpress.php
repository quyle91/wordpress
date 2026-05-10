<?php

namespace Adminz\Controller;

final class Wordpress {
    private static $instance = null;
    public $id = 'adminz_wordpress';
    public $name = 'Wordpress';
    public $option_name = 'adminz_admin';
    public $settings = [];

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        add_filter('adminz_option_page_nav', [$this, 'add_admin_nav'], 10, 1);
        add_filter('admin_menu', [$this, 'save_admin_menu'], 999, 1);
        add_action('admin_init', [$this, 'register_settings']);
        $this->load_settings();
        $this->run();
        $this->setup_tools_inpage();
    }

    function setup_tools_inpage() {
        // zip download
        add_action('wp_ajax_adminz_move_to_must_use', [$this, 'adminz_move_to_must_use']);
    }

    function run() {
        // shortcode
        add_shortcode('adminz_test', 'adminz_test');

        // spam protect
        $a = new \Adminz\Helper\Comment();
        $a->init();

        // 
        if ($this->settings['adminz_tax_thumb'] ?? []) {
            foreach ((array) $this->settings['adminz_tax_thumb'] as $taxonomy) {
                $a = new \Adminz\Helper\TermTaxonomy();
                $a->init_thumbnail($taxonomy);
            }
        }

        // 
        if ($this->settings['register_taxonomy'] ?? []) {
            // prepare
            $taxonomies = [];
            foreach ((array)$this->settings['register_taxonomy'] as $key => $item) {
                $tax_name = $item['key'] ?? '';
                $post_types = $item['value'] ?? [];
                $post_types = array_column($post_types, 0);
                $taxonomies[$tax_name] = array_merge($taxonomies[$tax_name] ?? [], $post_types);
            }

            foreach ((array)$taxonomies as $tax_name => $post_types) {
                $a = new \Adminz\Helper\TermTaxonomy();
                $a->register_taxonomy($tax_name, $post_types);
            }
        }

        // 
        if ($this->settings['adminz_post_type_thumb'] ?? []) {
            foreach ((array) $this->settings['adminz_post_type_thumb'] as $post_type) {
                $a = new \Adminz\Helper\PostType();
                $a->init_thumbnail($post_type);
            }
        }


        // 
        if ($this->settings['register_post_type'] ?? []) {
            foreach ((array) $this->settings['register_post_type'] as $post_type) {
                $a = new \Adminz\Helper\PostType();
                $a->register_post_type($post_type, []);
            }
        }

        // 
        if ($this->settings['post_type_meta_key_column'] ?? []) {
            foreach ((array) $this->settings['post_type_meta_key_column'] as $item) {
                $a = new \Adminz\Helper\PostType();
                $a->init_meta_key_column($item['key'], $item['value']);
            }
        }

        // 
        if ($this->settings['term_meta_key_column'] ?? []) {
            foreach ((array) $this->settings['term_meta_key_column'] as $item) {
                $a = new \Adminz\Helper\TermTaxonomy();
                $a->init_meta_key_column($item['key'], $item['value']);
            }
        }

        // 
        foreach (glob(ADMINZ_DIR . '/includes/shortcodes/wp-*.php') as $filename) {
            require_once $filename;
        }

        // 
        if ($this->settings['adminz_notice'] ?? '') {
            $notice = $this->settings['adminz_notice'];
            adminz_user_admin_notice($notice);
        }

        // 
        if ($this->settings['adminz_admin_logo'] ?? '') {
            $image_url = $this->settings['adminz_admin_logo'];
            adminz_admin_login_logo($image_url);
        }

        // 
        if ($this->settings['adminz_admin_login_heading'] ?? '') {
            $text = $this->settings['adminz_admin_login_heading'];
            adminz_admin_login_heading($text);
        }

        // 
        if ($this->settings['adminz_admin_login_footer_text'] ?? '') {
            $text = $this->settings['adminz_admin_login_footer_text'];
            adminz_admin_login_footer_text($text);
        }

        // 
        if ($this->settings['adminz_admin_background'] ?? '') {
            $image_url = $this->settings['adminz_admin_background'];
            adminz_admin_background($image_url);
        }

        // 
        if ($this->settings['adminz_admin_url'] ?? '') {
            $slug = $this->settings['adminz_admin_url'];
            $a = new \Adminz\Helper\WpHideLogin();
            $a->init($slug);
        }

        // 
        if ($this->settings['adminz_admin_login_quiz'] ?? '') {
            $a = new \Adminz\Helper\WordpressAdmin();
            $a->init_quiz();
        }

        // 
        if ($this->settings['adminz_use_classic_editor'] ?? '') {
            add_filter('use_block_editor_for_post', function () {
                return false;
            });
            add_filter('use_widgets_block_editor', function () {
                return false;
            });
        }

        // 
        if ($this->settings['hide_admin_menu'] ?? []) {
            add_action('admin_menu', function () {
                foreach ((array) $this->settings['hide_admin_menu'] ?? [] as $key => $value) {
                    if (!in_array(
                        get_current_user_id(),
                        $this->settings['hide_admin_menu_excluded_user'] ?? []
                    )) {
                        remove_menu_page($value);
                    }
                }
            }, 1000);
        }

        // 
        if ($this->settings['adminz_use_adminz_widgets'] ?? []) {
            add_action('widgets_init', function () {
                foreach ((array) $this->settings['adminz_use_adminz_widgets'] as $widget_class) {
                    if ($widget_class) {
                        register_widget($widget_class);
                    }
                }
            });
        }

        // 
        if ($this->settings['disable_plugins_update'] ?? []) {
            foreach ((array) $this->settings['disable_plugins_update'] as $key => $value) {
                if ($value) {
                    adminz_disable_plugin_update($value);
                }
            }
        }

        // 
        if ($this->settings['adminz_sidebars'] ?? []) {
            foreach ((array) $this->settings['adminz_sidebars'] as $key => $value) {
                if ($value) {
                    $a = new \Adminz\Helper\Sidebar;
                    $a->register_sidebar($value);
                }
            }
        }

        // 
        if (($this->settings['replace_sidebar'] ?? '') == 'on') {
            $a = new \Adminz\Helper\Sidebar;
            $a->replace_sidebar();
        }

        // 
        if ($this->settings['auto_image_excerpt'] ?? '') {
            adminz_user_image_auto_excerpt();
        }

        // 
        if ($this->settings['add_toggle_button'] ?? '') {
            adminz_add_admin_body_class('adminz_menus_toggle_button');
        }

        // 
        if (!empty($this->settings['preload_images'] ?? '') == 'on') {
            $a = new \Adminz\Helper\Seo();
            $a->preload_image();
        }

        // 
        if (!empty($this->settings['support_no_index'] ?? '') == 'on') {
            $a = new \Adminz\Helper\Seo();
            $a->no_index();
        }

        // 
        if (!empty($this->settings['support_meta_tags'] ?? '') == 'on') {
            $a = new \Adminz\Helper\Seo();
            $a->general_meta_tags();
        }

        // 
        if (!empty($this->settings['support_meta_og_twitter'] ?? '') == 'on') {
            $a = new \Adminz\Helper\Seo();
            $a->general_og_twitter();
        }

        // 
        $remove_post_type_slugs = array_filter($this->settings['remove_post_type_slugs'] ?? []);
        $remove_taxonomy_slugs = array_filter($this->settings['remove_taxonomy_slugs'] ?? []);
        if (!empty($remove_post_type_slugs) || !empty($remove_taxonomy_slugs)) {
            $a = new \Adminz\Helper\Permalink();
            if (!empty($remove_post_type_slugs)) {
                $a->post_types($remove_post_type_slugs);
            }
            if (!empty($remove_taxonomy_slugs)) {
                $a->taxonomies($remove_taxonomy_slugs);
            }
            $a->run();
        }


        if (!empty($this->settings['support_custom_permalinks'] ?? [])) {
            foreach ((array)$this->settings['support_custom_permalinks'] as $post_type) {
                if ($post_type) {
                    $a = new \Adminz\Helper\Seo();
                    $a->custom_permalink($post_type);
                }
            }
        }

        // 
        if ($this->settings['post_thumbnail_size'] ?? '') {
            add_filter('post_thumbnail_size', function ($size) {
                if (is_admin() && is_main_query()) {
                    return $size;
                }
                return $this->settings['post_thumbnail_size'];
            }, 10, 1);
        }

        // 
        if ($taxonomies = ($this->settings['adminz_tiny_mce_taxonomy'] ?? [])) {
            foreach ($taxonomies as $taxonomy) {
                $a = new \Adminz\Helper\Category();
                $a->init($taxonomy);
            }
        }

        // 
        if ($preg_replace = ($this->settings['preg_replace'] ?? [])) {
            $preg_replace = array_filter($this->settings['preg_replace'], function ($item) {
                return !empty($item['key']) && isset($item['value']);
            });
            if (!empty($preg_replace)) {

                add_action('wp_body_open', function () {
                    ob_start();
                });

                add_action('get_footer', function () use ($preg_replace) {
                    $search = [];
                    $replace = [];
                    foreach ((array) $preg_replace as $key => $value) {
                        $search[] = '#' . $value['key'] . '#s';
                        $replace[] = $value['value'];
                    }

                    echo preg_replace(
                        $search,
                        $replace,
                        ob_get_clean()
                    );
                });
            }
        }

        // 
        if ($gettext = ($this->settings['gettext'] ?? [])) {
            if (!empty($gettext)) {
                foreach ((array) $gettext as $item) {
                    if ($item['your_text'] ?? '') {
                        add_filter('gettext', function ($translation, $text, $domain) use ($item) {

                            $match_domain = true;
                            $item_domain = $item['domain'] ?? '';
                            if ($item_domain and $domain != $item_domain) {
                                $match_domain = false;
                            }

                            $match_text = true;
                            $item_text = $item['text'] ?? '';
                            if ($item_text and $text != $item_text) {
                                $match_domain = false;
                            }

                            $match_translation = false;
                            if (($item['translation'] ?? '') == $translation) {
                                $match_translation = true;
                            }

                            if ($match_domain and $match_text and $match_translation) {
                                return $item['your_text'];
                            }

                            return $translation;
                        }, 10, 3);
                    }
                }
            }
        }
    }

    function load_settings() {
        $this->settings = get_option($this->option_name, []);
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    public $admin_menu;
    function save_admin_menu() {
        global $menu;
        $this->admin_menu = $menu;
    }

    function adminz_move_to_must_use() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'adminz_js')) {
            wp_send_json_error('Invalid nonce');
            wp_die();
        }

        $folder_path = ABSPATH . esc_attr($_POST['folder_path']); // Đường dẫn tuyệt đối của plugin
        if (!is_dir($folder_path)) {
            wp_send_json_error('Folder not exists');
            wp_die();
        }

        // Kiểm tra xem thư mục plugin hiện tại có thuộc mu-plugins không
        $is_mu_plugin = strpos($folder_path, 'mu-plugins') !== false;

        // Đặt đường dẫn đích
        $plugin_folder_name = basename($folder_path);
        $to_plugin_path = ABSPATH . ($is_mu_plugin ? 'wp-content/plugins/' : 'wp-content/mu-plugins/');
        $new_path = $to_plugin_path . $plugin_folder_name;

        // Tạo thư mục đích nếu chưa có
        if (!file_exists($to_plugin_path)) {
            if (!mkdir($to_plugin_path, 0755, true)) {
                wp_send_json_error('Can not create folder');
                wp_die();
            }
        }

        // Di chuyển thư mục plugin
        if (!rename($folder_path, $new_path)) {
            wp_send_json_error('Can not move folder');
            wp_die();
        }

        // Copy file loader nếu chưa tồn tại
        $source_loader_file_path = ADMINZ_DIR . 'includes/file/adminz-mu-plugins-loader.php';
        $to_file = WP_CONTENT_DIR . '/mu-plugins/adminz-mu-plugins-loader.php';
        if (!file_exists($to_file)) {
            if (!copy($source_loader_file_path, $to_file)) {
                wp_send_json_error('Can not copy mu-plugins-loader.php');
                wp_die();
            }
        }

        wp_send_json_success('Move completed, Please fresh page!');
        wp_die();
    }

    function register_settings() {

        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz_admin',
            'Admin',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Admin login',
            function () {

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('wp_media')
                    ->name($this->option_name . '[adminz_admin_logo]')
                    ->value($this->settings['adminz_admin_logo'] ?? '')
                    ->copyButton(false)
                    ->label('Login logo')
                    ->render();


                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('wp_media')
                    ->name($this->option_name . '[adminz_admin_background]')
                    ->value($this->settings['adminz_admin_background'] ?? '')
                    ->copyButton(false)
                    ->label('Admin login background')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_admin_url]')
                    ->value($this->settings['adminz_admin_url'] ?? '')
                    ->attributes(
                        [
                            'placeholder' => 'wp-admin',
                        ]
                    )
                    ->label('Admin login Url')
                    ->addNote(($this->settings['adminz_admin_url'] ?? '') ? get_site_url() . '/' . $this->settings['adminz_admin_url'] : '')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->attributes(
                        [
                            'plaeholder' => ''
                        ]
                    )
                    ->name($this->option_name . '[adminz_admin_login_quiz]')
                    ->value($this->settings['adminz_admin_login_quiz'] ?? '')
                    ->copyButton(false)
                    ->label('Admin login quiz')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->attributes(
                        [
                            'placeholder' => "<h1>Login to your site</h1>\r\n<p>Fill your email and password</p>"
                        ]
                    )
                    ->name($this->option_name . '[adminz_admin_login_heading]')
                    ->value($this->settings['adminz_admin_login_heading'] ?? '')
                    ->copyButton(true)
                    ->label('Admin login heading')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->attributes(
                        [
                            'placeholder' => 'Customer support number: 0123456789'
                        ]
                    )
                    ->name($this->option_name . '[adminz_admin_login_footer_text]')
                    ->value($this->settings['adminz_admin_login_footer_text'] ?? '')
                    ->copyButton(true)
                    ->label('Admin login Footer text')
                    ->render();
            },
            $this->id,
            'adminz_admin'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Admin notice',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->attributes(
                        [
                            'placeholder' => 'Your text here'
                        ]
                    )
                    ->name($this->option_name . '[adminz_notice]')
                    ->value($this->settings['adminz_notice'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_admin'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Classic editor',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_use_classic_editor]')
                    ->value($this->settings['adminz_use_classic_editor'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_admin'
        );

        // field 		
        add_settings_field(
            wp_rand(),
            'Hide admin menu',
            function () {

                // hide admin menu
                $options = [];
                foreach ($this->admin_menu as $menu_item) {
                    $menu_slug = $menu_item[2];
                    $menu_name = $menu_item[0];
                    if (!$menu_name) {
                        $menu_name = $menu_slug;
                    }
                    $options[$menu_slug] = wp_strip_all_tags($menu_name);
                }

                // test simple repeater
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[hide_admin_menu]')
                    ->value($this->settings['hide_admin_menu'] ?? false) // giá trị đã lưu
                    ->label('Select Menu item')
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                // user excluded
                $users = get_users(['role' => 'administrator']);
                $options = [];
                foreach ((array) $users as $key => $user) {
                    $options[$user->data->ID] = $user->data->user_login;
                }

                //
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[hide_admin_menu_excluded_user]')
                    ->value($this->settings['hide_admin_menu_excluded_user'] ?? false) // giá trị đã lưu
                    ->label('User excluded')
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
            },
            $this->id,
            'adminz_admin'
        );

        // add section
        add_settings_section(
            'adminz_attachment',
            'Attchment',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Auto image excerpt',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[auto_image_excerpt]')
                    ->value($this->settings['auto_image_excerpt'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_attachment'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post thumbnail size',
            function () {

                //
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('select')
                    ->options(array_combine(get_intermediate_image_sizes(), get_intermediate_image_sizes()))
                    ->attributes(
                        [
                            'placeholder' => ''
                        ]
                    )
                    ->name($this->option_name . '[post_thumbnail_size]')
                    ->value($this->settings['post_thumbnail_size'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_attachment'
        );

        // add section
        add_settings_section(
            'adminz_plugins',
            'Plugins',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Disable plugins update',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[disable_plugins_update]')
                    ->value($this->settings['disable_plugins_update'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options(adminz_plugins_installed())
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
            },
            $this->id,
            'adminz_plugins'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Move to must use plugins',
            function () {
?>
            <div class="wrap move_to_must_use_plugins select_folders">
                <div class="form">
                    <input type="text" name="folder-path" class="folder-path regular-text adminz_field"
                        placeholder="e.g., plugins/contact-form-7" />
                    <button type="button" class="xbutton button button-primary">
                        Move
                    </button>
                    <span class="xstatus"></span>
                </div>
                <div class="suggestions">
                    <ul>
                        <li class="mu_plugin">
                            <strong> Mu Plugins: </strong>
                            <?php
                            $mu_plugin_dir = WP_CONTENT_DIR . '/mu-plugins';

                            // Quét thư mục con (folder)
                            foreach (glob($mu_plugin_dir . '/*', GLOB_ONLYDIR) as $folder_path) {
                                $folder_name = basename($folder_path);

                                // Gợi ý: Đọc file PHP đầu tiên trong folder để lấy thông tin (nếu muốn)
                                $php_files = glob($folder_path . '/*.php');
                                $display_name = $folder_name;

                                if (!empty($php_files)) {
                                    $plugin_data = get_plugin_data($php_files[0], false, false);
                                    if (!empty($plugin_data['Name'])) {
                                        $display_name = esc_html($plugin_data['Name']);
                                    }
                                }

                                echo '<button type="button" class="button button-small plugin-suggestion" data-path="wp-content/mu-plugins/' . esc_attr($folder_name) . '">' . $display_name . '</button> ';
                            }
                            ?>
                        </li>
                        <li class="plugin">
                            <strong> Plugins: </strong>
                            <?php
                            $plugin_dir = WP_CONTENT_DIR . '/plugins';
                            foreach (glob($plugin_dir . '/*', GLOB_ONLYDIR) as $plugin_path) {
                                $plugin_name = basename($plugin_path);
                                $plugin_file = $plugin_name;
                                $plugin_data = get_plugins('/' . $plugin_file);
                                if (!empty($plugin_data)) {
                                    $plugin_name_display = esc_html($plugin_data[key($plugin_data)]['Name']);
                                    echo '<button type=button class="button button-small plugin-suggestion" data-path="wp-content/plugins/' . esc_attr($plugin_file) . '">' . $plugin_name_display . '</button> ';
                                }
                            }
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
<?php
            },
            $this->id,
            'adminz_plugins'
        );


        // add section
        add_settings_section(
            'adminz_menus',
            'Menus',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Toggle button support',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[add_toggle_button]')
                    ->value($this->settings['add_toggle_button'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_menus'
        );

        // add section
        add_settings_section(
            'adminz_seo',
            'SEO',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Preload images',
            function () {

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[preload_images]')
                    ->value($this->settings['preload_images'] ?? '')
                    ->copyButton(true)
                    ->addNote('Create a field in page edit to add preload image url')
                    ->render();
            },
            $this->id,
            'adminz_seo'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'No index support',
            function () {

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[support_no_index]')
                    ->value($this->settings['support_no_index'] ?? '')
                    ->copyButton(true)
                    ->addNote('Create noindex meta tag for url params with <strong>s</strong> and <strong>page</strong>')
                    ->render();
            },
            $this->id,
            'adminz_seo'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Meta tags support',
            function () {
                // 
                if (adminz_is_yoast_or_rankmath()) {
                    echo '<small><i>Use Yoast seo or Rank math instead of this function</i></small>';
                    return;
                }

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[support_meta_tags]')
                    ->value($this->settings['support_meta_tags'] ?? '')
                    ->copyButton(true)
                    ->addNote('Meta title: Page title, Meta description: blog description or home page excerpt')
                    ->render();
            },
            $this->id,
            'adminz_seo'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Meta Og, twitter support',
            function () {
                // 
                if (adminz_is_yoast_or_rankmath()) {
                    echo '<small><i>Use Yoast seo or Rank math instead of this function</i></small>';
                    return;
                }
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[support_meta_og_twitter]')
                    ->value($this->settings['support_meta_og_twitter'] ?? '')
                    ->copyButton(true)
                    ->addNote('Create meta name=og:')
                    ->render();
            },
            $this->id,
            'adminz_seo'
        );

        // add section
        add_settings_section(
            'adminz_seo_permalink',
            'Permalink',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Remove Post type slug',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[remove_post_type_slugs]')
                    ->value($this->settings['remove_post_type_slugs'] ?? false) // giá trị đã lưu
                    ->label('User excluded')
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options(get_post_types())
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                $permalink_option_link = admin_url('options-permalink.php');
                echo <<<HTML
				<p>
					<small>Please <strong><a target="_blank" href="{$permalink_option_link}">Save permalink</a></strong> again after changes </small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_seo_permalink'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Remove Taxonomy term slug',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[remove_taxonomy_slugs]')
                    ->value($this->settings['remove_taxonomy_slugs'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options(get_taxonomies())
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                $permalink_option_link = admin_url('options-permalink.php');
                echo <<<HTML
				<p>
					<small>Please <strong><a target="_blank" href="{$permalink_option_link}">Save permalink</a></strong> again after changes </small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_seo_permalink'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Custom url support',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[support_custom_permalinks]')
                    ->value($this->settings['support_custom_permalinks'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options(get_post_types())
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

                echo adminz_toggle_button(_x('Suggested', 'custom headers'), ".guild_process");
                $test_meta_values = [
                    'abc-xycx-just-a-cool-blog-post-with-images' => 'Work',
                    'abc/100/sdfalhsdal;fsadh' => 'Work',
                    'folder/subfolder/file.name' => 'Work',
                    '@weird!$+chars/slug' => 'Work',
                    '中文/slug' => 'Work (if the server supports Unicode)',
                    '%E6%97%A5%E6%9C%AC%E8%AA%9E/slug' => 'Work (but percent-encoded)',
                    'foo#bar' => 'Not Work (# = fragment, browser dont accept it)',
                    'foo?bar' => 'Not Work (? = query string)',
                    'path with spaces' => 'Not Work (browser send %20)',
                    'foo%bar' => 'Not Work (% must be %HH)',
                    'folder\\name' => 'Not Work (Invalid backslash)',
                ];
                echo <<<HTML
                <table class="guild_process hidden" style="margin-top: 15px;">
                    <tr>
                        <td>Meta_value</td>
                        <td>Work or Not</td>
                    </tr>
                HTML;
                foreach ($test_meta_values as $meta => $result) {
                    echo "<tr>";
                    echo "<td>" . esc_html($meta) . "</td>";
                    echo "<td>" . esc_html($result) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            },
            $this->id,
            'adminz_seo_permalink'
        );

        // add section
        add_settings_section(
            'adminz_sidebar',
            'Sidebar',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Sidebar creator',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[adminz_sidebars]')
                    ->value($this->settings['adminz_sidebars'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->attributes(
                                [
                                    'placeholder' => 'Sidebar name',
                                ]
                            )
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
                
                echo <<<HTML
				<div><small> Note: dynamic_sidebar(<strong>slug</strong>) </small></div>
				HTML;
            },
            $this->id,
            'adminz_sidebar'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Replace sidebar',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[replace_sidebar]')
                    ->value($this->settings['replace_sidebar'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_sidebar'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Use adminz widgets',
            function () {
                $options = [
                    'Adminz\Widget\Adminz_Taxonomies' => 'Adminz Taxonomies',
                    'Adminz\Widget\Adminz_RecentPosts' => 'Adminz Recent Posts',
                ];

                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[adminz_use_adminz_widgets]')
                    ->value($this->settings['adminz_use_adminz_widgets'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
                
            },
            $this->id,
            'adminz_sidebar'
        );

        // add section
        add_settings_section(
            'adminz_posttype',
            'Post types',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Register post type',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[register_post_type]')
                    ->value($this->settings['register_post_type'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->attributes(
                                [
                                    'placeholder' => 'Adminz Example',
                                ]
                            )
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
                
                echo <<<HTML
				<p>
					<small>Slug: Adminz Example -> <strong>adminz-example</strong></small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_posttype'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post type thumbnail support',
            function () {
                $options = get_post_types();
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[adminz_post_type_thumb]')
                    ->value($this->settings['adminz_post_type_thumb'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
                
                $metaKey = adminz_copy('thumbnail_id');
                echo <<<HTML
				<p>
					<small>Meta key: {$metaKey}</small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_posttype'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post type meta column',
            function () {

                // repeater
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[post_type_meta_key_column]')
                    ->value($this->settings['post_type_meta_key_column'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Post type')
                            ->copyButton(true)
                            ->options(get_post_types()),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('value')
                            ->label('Meta key')
                            ->attributes(
                                [
                                    'placeholder' => 'meta_key',
                                ]
                            )
                            ->copyButton(true)
                            ->options([]),
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                        ],
                    ])
                    ->render();

                $link = adminz_copy(add_query_arg(['adminz_test_postmeta' => 'XXX',], get_site_url()));
                echo <<<HTML
				<p>
					<small>Test meta keys: {$link}</small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_posttype'
        );

        // add section
        add_settings_section(
            'adminz_term_taxonomy',
            'Term Taxonomies',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Register Taxonomy',
            function () {
                // repeater
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[register_taxonomy]')
                    ->value($this->settings['register_taxonomy'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([
                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('key')
                            ->label('Taxonomy')
                            ->attributes([
                                'placeholder' => 'Taxonomy Name',
                            ])
                            ->copyButton(true),
                        //
                        \WpDatabaseHelperV2\Fields\WpRepeater::make()
                            ->name('value')
                            ->label('Post type')
                            ->fields([
                                //
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->kind('select')
                                    ->name('0')
                                    ->copyButton(true)
                                    ->options(get_post_types()),
                            ])
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => [
                                [''],
                            ],
                        ],
                    ])
                    ->render();

                echo <<<HTML
				<p>
					<small>Slug: Taxonomy Name -> <strong>taxonomy-name</strong></small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_term_taxonomy'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Term thumbnail support',
            function () {
                $options = get_taxonomies();
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[adminz_tax_thumb]')
                    ->value($this->settings['adminz_tax_thumb'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();
                
                $metaKey = adminz_copy('thumbnail_id');
                echo <<<HTML
				<p>
					<small>Meta key: {$metaKey}</small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_term_taxonomy'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Term meta admin column',
            function () {

                // repeater
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[term_meta_key_column]')
                    ->value($this->settings['term_meta_key_column'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->label('Items')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->attributes([
                                'placeholder' => 'Position'
                            ])
                            ->default('')
                            ->copyButton(true)
                            ->options(get_taxonomies()),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('value')
                            ->attributes([
                                'placeholder' => 'Meta key'
                            ])
                            ->default('')
                            ->copyButton(true),
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                        ],
                    ])
                    ->render();

                $link = adminz_copy(add_query_arg(['adminz_test_termmeta' => 'XXX',], get_site_url()));
                echo <<<HTML
				<p>
					<small>Test meta keys: {$link}</small>
				</p>
				HTML;
            },
            $this->id,
            'adminz_term_taxonomy'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Term content tiny mce',
            function () {
                $options = get_taxonomies();
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[adminz_tiny_mce_taxonomy]')
                    ->value($this->settings['adminz_tiny_mce_taxonomy'] ?? false) // giá trị đã lưu
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();

            },
            $this->id,
            'adminz_term_taxonomy'
        );

        // add section
        add_settings_section(
            'adminz_wordpress_content',
            'Content Replace',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Preg Replace',
            function () {

                // repeater
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[preg_replace]')
                    ->value($this->settings['preg_replace'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->label('Items')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('textarea')
                            ->name('key')
                            ->label('Search')
                            ->attributes([
                                'placeholder' => __('Search'),
                            ])
                            ->copyButton(true)
                            ->options([]),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('textarea')
                            ->name('value')
                            ->label('Replace')
                            ->attributes([
                                'placeholder' => __('Replace'),
                            ])
                            ->copyButton(true)
                            ->options([]),
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                        ],
                    ])
                    ->render();

                $searchEx = adminz_copy(htmlentities2('<h3 class="comments-title uppercase">(.*?)</h3>'));
                $replaceEx = adminz_copy(htmlentities2('<h4 class="comments-title uppercase">$1</h4>'));
                echo <<<HTML
				<p>
					<strong><small>Search Ex:</small> </strong>
					{$searchEx}
				</p>
				<p>
					<strong><small>Replace Ex:</small> </strong>
					{$replaceEx}
				</p>
				HTML;
            },
            $this->id,
            'adminz_wordpress_content'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Gettext',
            function () {

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[gettext]')
                    ->value($this->settings['gettext'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('your_text')
                            ->label('Your_text')
                            ->attributes([
                                'placeholder' => 'xxx',
                            ])
                            ->copyButton(true)
                            ->options([]),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('translation')
                            ->label('Translation')
                            ->attributes([
                                'placeholder' => 'Danh mục',
                            ])
                            ->copyButton(true)
                            ->options([]),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('text')
                            ->label('Text')
                            ->attributes([
                                'placeholder' => 'Categories',
                            ])
                            ->copyButton(true)
                            ->options([]),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->name('domain')
                            ->label('Domain')
                            ->attributes([
                                'placeholder' => 'text-domain or empty',
                            ])
                            ->copyButton(true)
                            ->options([]),
                    ])
                    ->default([
                        [
                            'your_text' => '',
                            'translation' => '',
                            'text' => '',
                            'domain' => '',
                        ],
                    ])
                    ->render();

                $note = __('Note');
                echo <<<HTML
				<p> <small> <strong> {$note}: </strong> Like filter hooks <a target="_blank" href="https://developer.wordpress.org/reference/hooks/gettext"> gettext </a> </small> </p>
				HTML;
            },
            $this->id,
            'adminz_wordpress_content'
        );
    }
}
