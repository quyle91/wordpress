<?php

namespace Adminz\Controller;

final class Tools {
    private static $instance = null;
    public $id = 'adminz_tools';
    public $name = 'Tools';
    public $option_name = 'adminz_tools';

    public $settings = [];

    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    function __construct() {
        add_filter('adminz_option_page_nav', [$this, 'add_admin_nav'], 10, 1);
        add_action('admin_init', [$this, 'register_settings']);
        $this->load_settings();
        $this->plugin_loaded();
        $this->setup_crawl();
        $this->setup_tools_inpage();
    }

    function load_settings() {
        $this->settings = get_option($this->option_name, []);
    }

    function plugin_loaded() {

        // ------------------
        if (isset($_GET['adminz_delete_table_crawl_log'])) {
            $Crawl = new \Adminz\Helper\Crawl\Crawl();
            $Crawl->delete_table_log();
        }

        // ------------------ 
        if ($this->settings['enable_craw_log'] ?? '') {
            $Crawl = new \Adminz\Helper\Crawl\Crawl();
            $Crawl->create_table_log();
        }

        // ------------------ 
        if (!empty($this->settings['logs'] ?? [])) {
            foreach ((array) $this->settings['logs'] as $class_name) {
                if (class_exists($class_name)) {
                    $a = new $class_name();
                    $a->init();
                }
            }
        }

        // 
        if ($this->settings['image_auto_watermark'] ?? "") {
            $a = new \Adminz\Helper\Watermark();
            $a->watermark_id = $this->settings['image_auto_watermark'];
            $a->init();
        }

        // 
        if ($this->settings['clone_setup']['post_types'][0] ?? '') {
            $clone_setup = $this->settings['clone_setup'] ?? [];
            $post_types = $clone_setup['post_types'] ?? [];
            $random_terms = $clone_setup['random_terms'] ?? '';
            $number = $clone_setup['number'] ?? '10';
            $a = new \Adminz\Helper\PosttypeClone();
            $a->init(
                $post_types,
                $random_terms,
                $number
            );
        }
    }

    function setup_crawl() {
        add_action('wp_ajax_check_adminz_import_from_post', [$this, 'adminz_crawl']);
        add_action('wp_ajax_run_adminz_import_from_post', [$this, 'adminz_crawl']);
        add_action('wp_ajax_check_adminz_import_from_category', [$this, 'adminz_crawl']);
        add_action('wp_ajax_run_adminz_import_from_category', [$this, 'adminz_crawl']);
        add_action('wp_ajax_check_adminz_import_from_product', [$this, 'adminz_crawl']);
        add_action('wp_ajax_run_adminz_import_from_product', [$this, 'adminz_crawl']);
        add_action('wp_ajax_check_adminz_import_from_product_category', [$this, 'adminz_crawl']);
        add_action('wp_ajax_run_adminz_import_from_product_category', [$this, 'adminz_crawl']);
        add_action('wp_ajax_check_adminz_import_images', [$this, 'adminz_crawl']);
        add_action('wp_ajax_run_adminz_import_images', [$this, 'adminz_crawl']);
        add_action('wp_ajax_run_adminz_import_image', [$this, 'adminz_crawl']);
    }

    function setup_tools_inpage() {
        // ajax
        add_action('wp_ajax_adminz_replace_image', [$this, 'adminz_replace_image']);

        // zip download
        add_action('wp_ajax_adminz_zip_download', [$this, 'adminz_zip_download']);
    }

    function adminz_crawl() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            wp_die();
        }
        if (!wp_verify_nonce($_POST['nonce'], 'adminz_js')) exit;
        ob_start();

        // call helper
        $Crawl = new \Adminz\Helper\Crawl\Crawl();
        $Crawl->set_config($_POST['adminz_tools'] ?? false);

        // khai báo
        $action = '';
        $url = '';

        // Truyền từ form data
        if (isset($_POST['adminz_tools'])) {
            $action = $_POST['action'] ?? '';
            $type = str_starts_with($action, "run_") ? 'run' : 'check';
            $key = str_replace($type . "_", '', $action);
            $url = $_POST['adminz_tools'][$key] ?? '';
        }

        // truyền trực tiếp
        if ($_POST['action'] ?? '') {
            $action = $_POST['action'];
        }
        if ($_POST['url'] ?? '') {
            $url = $_POST['url'];
        }

        $Crawl->set_action($action);
        $Crawl->set_url($url);
        echo $Crawl->run();
        $return = ob_get_clean();

        if (!$return) {
            wp_send_json_error('Error');
            wp_die();
        }

        wp_send_json_success($return);
        wp_die();
    }

    function adminz_replace_image() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
            wp_die();
        }
        if (!wp_verify_nonce($_POST['nonce'], 'adminz_js')) exit;
        $return = false;

        ob_start();

        foreach ($_FILES as $key => $file) {
            echo adminz_replace_media($file);
        }

        $return = ob_get_clean();

        if (!$return) {
            wp_send_json_error('Error');
            wp_die();
        }

        wp_send_json_success($return);
        wp_die();
    }

    function adminz_download_folder($folder_path) {
        $folder_path = ABSPATH . $folder_path;

        if (!file_exists($folder_path) || !is_dir($folder_path)) {
            wp_die('The specified folder does not exist');
        }

        $folder_name = basename($folder_path); // Lấy tên folder từ path
        $upload_dir = wp_upload_dir();
        $timestamp = time();
        $zip_file = $upload_dir['path'] . "/$folder_name-$timestamp.zip"; // Sử dụng tên thư mục làm tiền tố

        // Create a zip file
        $zip = new \ZipArchive();
        if ($zip->open($zip_file, \ZipArchive::CREATE) !== true) {
            return false;
        }

        // Thêm một folder vào file zip
        $zip->addEmptyDir($folder_name);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder_path), \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $file_path = $file->getRealPath();
                $relative_path = substr($file_path, strlen($folder_path) + 1);
                // Thay đổi relative path để có folder bên trong
                $zip->addFile($file_path, $folder_name . '/' . $relative_path);
            }
        }

        $zip->close();

        return $zip_file; // Trả về đường dẫn tới file zip đã tạo
    }

    function adminz_zip_download() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized user');
        }

        $folder_path = isset($_POST['folder_path']) ? sanitize_text_field($_POST['folder_path']) : '';

        if (!$folder_path) {
            wp_die('No folder path provided');
        }


        // Sử dụng hàm adminz_download_folder để tạo file zip
        $zip_file = $this->adminz_download_folder($folder_path);

        if (!$zip_file) {
            wp_die('Failed to create zip file');
        }

        // Force download the zip file
        $folder_name = basename($folder_path); // Lấy tên folder từ path
        $timestamp = time();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $folder_name . '-' . $timestamp . '.zip"');
        header('Content-Length: ' . filesize($zip_file));
        readfile($zip_file);

        // Delete the zip file after download
        unlink($zip_file);

        exit;
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    function register_settings() {
        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz_tools_file',
            'File tools',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Zip downloader',
            function () {
                if (!class_exists('ZipArchive')) {
                    echo '<em>Enable zip archive to use this tool</em></mark';
                    return;
                }
?>
            <div class="wrap zip_downloader select_folders">
                <div class="form">
                    <input type="text" name="folder-path" class="folder-path regular-text adminz_field"
                        placeholder="e.g., plugins/contact-form-7" />
                    <button type="button" class="xbutton button button-primary">
                        Download Zip
                    </button>
                    <span class="xstatus"></span>
                </div>
                <div class="suggestions">
                    <ul>
                        <li class="theme">
                            <strong> Themes: </strong>
                            <?php
                            $theme_dir = WP_CONTENT_DIR . '/themes';
                            foreach (glob($theme_dir . '/*', GLOB_ONLYDIR) as $theme_path) {
                                $theme_name = basename($theme_path);
                                $theme = wp_get_theme($theme_name);
                                echo '<button type=button class="button button-small theme-suggestion" data-path="wp-content/themes/' . esc_attr($theme_name) . '">' . esc_html($theme->get('Name')) . '</button> ';
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
            'adminz_tools_file'
        );

        // add section
        add_settings_section(
            'adminz_tools_log',
            'Log tools',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Logs',
            function () {
                $options = [
                    '\Adminz\Helper\Logs\HttpPostRequest' => 'Http Post Request',
                    '\Adminz\Helper\Logs\Email' => 'Emails',
                ];
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[logs]')
                    ->value($this->settings['logs'] ?? false) // giá trị đã lưu
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
            'adminz_tools_log'
        );

        // add section
        add_settings_section(
            'adminz_tools_clone',
            'Clone post',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Setup',
            function () {

                //
                $options = get_post_types();
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[clone_setup][post_types]')
                    ->value($this->settings['clone_setup']['post_types'] ?? false) // giá trị đã lưu
                    ->label('Post types')
                    ->direction('wrap')
                    ->field(
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->options($options)
                            ->name(null) // sẽ bị override
                    )
                    ->default([''])
                    ->render();


                // number
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('number')
                    ->name($this->option_name . '[clone_setup][numbers]')
                    ->default(10)
                    ->attributes([
                        'placeholder' => 10
                    ])
                    ->value($this->settings['clone_setup']['numbers'] ?? '')
                    ->copyButton(true)
                    ->label('Numbers posts')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[clone_setup][random_terms]')
                    ->value($this->settings['clone_setup']['random_terms'] ?? '')
                    ->options([
                        'on' => 'Random taxonomy terms'
                    ])
                    ->render();
            },
            $this->id,
            'adminz_tools_clone'
        );


        // add section
        add_settings_section(
            'adminz_tools_image',
            'Image tools',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Image auto watermark',
            function () {
                // field
                $gd_status = \Adminz\Helper\Watermark::check_gd_library() ? 'Yes' : 'No';
                // wp_media
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('wp_media')
                    ->name($this->option_name . '[image_auto_watermark]')
                    ->value($this->settings['image_auto_watermark'] ?? '')
                    ->addNote('Gd library enabled status: ' . $gd_status)
                    ->render();

                // Others
                echo adminz_toggle_button(__('Tools'), ".xxxxxxxxxxxx");
                echo '<div class="xxxxxxxxxxxx hidden" style="margin-top: 15px;">';
                $link_tool = add_query_arg(['adminz_run_watermark' => '',], get_site_url());
                echo "<a target=_blank class=button href='/?adminz_test_watermark'>" . __("Test watermark") . "</a> ";
                echo "<a target=_blank class=button href='$link_tool'>" . __('Set watermark') . "</a> ";

                // button
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes(
                        [
                            'class' => ['button', 'adminz_open_wpmedia'],
                            'data-config' => json_encode([
                                'title' => __('Media'),
                                'button' => [
                                    'text' => __('Select'),
                                ],
                                'multiple' => true,
                            ]),
                            'data-callback' => 'watermark_restore',
                        ]
                    )
                    ->copyButton(false)
                    ->value(__('Restore'))
                    ->render();

                echo <<<HTML
				<div>
					<script type="text/javascript">
						document.addEventListener('watermark_restore', function(e) {
							const images = e.detail.images;
							const button = e.detail.context;
							const output = document.querySelector('.watermark_restore_output');

							// Fetch 
							(async () => {
								try {
									const url = adminz_js.ajax_url;
									const formData = new FormData();
									formData.append('action', 'adminz_restore_watermark');
									formData.append('data', JSON.stringify(images));
									formData.append('nonce', adminz_js.nonce);
									//console.log('Before Fetch:', formData.get('data');

									const response = await fetch(url, {
										method: 'POST',
										body: formData,
									});

									if (!response.ok) {
										throw new Error('Network response was not ok');
									}

									const data = await response.json(); // reponse.text()
									// console.log(data.data);
									if (data.success) {
										output.innerHTML = data.data

									} else {}
								} catch (error) {
									console.error('Fetch error:', error);
								}
							})();
						});
					</script>
					<div class="adminz_response watermark_restore_output"></div>
				</div>
				HTML;
                echo '</div>';
            },
            $this->id,
            'adminz_tools_image'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Replace Image',
            function () {
                // file
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('file')
                    ->attributes([
                        'class' => ['adminz_replace_image', 'adminz_field'],
                        'data-action' => 'adminz_replace_image',
                        'data-response' => '.adminz_response5',
                        'accept' => "image/*",
                    ])
                    ->value($this->settings['numbers'] ?? '')
                    ->copyButton(false)
                    ->addNote('Please use same image name')
                    ->render();
        ?>
            <div class="adminz_response adminz_response5"></div>
        <?php
            },
            $this->id,
            'adminz_tools_image'
        );




        // ------------------------------------ CRAWL -------------------------------------------------------

        // add section
        add_settings_section(
            'adminz_tools_crawl_tools',
            'Crawl tools',
            function () {
                //
            },
            $this->id
        );

        add_settings_field(
            wp_rand(),
            'Logs',
            function () {

                // checkbox
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[enable_craw_log]')
                    ->value($this->settings['enable_craw_log'] ?? '')
                    ->copyButton(false)
                    ->addNote('Create table log is required import anything')
                    ->render();

                $link_delete = wp_nonce_url(
                    add_query_arg(['adminz_delete_table_crawl_log' => ''], get_site_url()),
                    'adminz_delete_table'
                );
                echo "<a target=blank href='$link_delete'>Delete table log</a>";
            },
            $this->id,
            'adminz_tools_crawl_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post',
            function () {

                echo '<div class="multiple_fields_a_line fields_with_note_same_line">';

                // text
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_from_post]')
                    ->attributes(['class' => 'regular-text'])
                    ->value($this->settings['adminz_import_from_post'] ?? "https://demos.flatsome.com/2015/10/13/velkommen-til-bloggen-min/")
                    ->render();

                // button
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button', 'adminz_fetch'],
                        'data-response' => '.adminz_response1',
                        'data-action' => 'check_adminz_import_from_post',
                    ])
                    ->value('Check')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button button-primary', 'adminz_fetch'],
                        'data-response' => '.adminz_response1',
                        'data-action' => 'run_adminz_import_from_post',
                    ])
                    ->value('Run')
                    ->addNote('https://demos.flatsome.com/2015/10/13/velkommen-til-bloggen-min/')
                    ->render();

                echo '</div> <!-- multiple_fields_a_line -->';
                echo '<div class="adminz_response adminz_response1"></div>';
            },
            $this->id,
            'adminz_tools_crawl_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Category',
            function () {
                echo '<div class="multiple_fields_a_line fields_with_note_same_line">';

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_from_category]')
                    ->attributes(['class' => 'regular-text'])
                    ->value($this->settings['adminz_import_from_category'] ?? 'https://demos.flatsome.com/blog/')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button', 'adminz_fetch'],
                        'data-response' => '.adminz_response2',
                        'data-action' => 'check_adminz_import_from_category',
                    ])
                    ->value('Check')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button button-primary', 'adminz_fetch'],
                        'data-response' => '.adminz_response2',
                        'data-action' => 'run_adminz_import_from_category',
                    ])
                    ->value('Run')
                    ->addNote('https://demos.flatsome.com/blog/')
                    ->render();

                echo '</div> <!-- multiple_fields_a_line -->';
                echo '<div class="adminz_response adminz_response2"></div>';
            },
            $this->id,
            'adminz_tools_crawl_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Product',
            function () {
                echo '<div class="multiple_fields_a_line fields_with_note_same_line">';
                // text
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_from_product]')
                    ->attributes(['class' => 'regular-text'])
                    ->value($this->settings['adminz_import_from_product'] ?? 'https://demos.flatsome.com/shop/clothing/hoodies/ship-your-idea-2/')
                    ->render();

                // button check
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button', 'adminz_fetch'],
                        'data-response' => '.adminz_response3',
                        'data-action' => 'check_adminz_import_from_product',
                    ])
                    ->value('Check')
                    ->render();

                // button run
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button button-primary', 'adminz_fetch'],
                        'data-response' => '.adminz_response3',
                        'data-action' => 'run_adminz_import_from_product',
                    ])
                    ->value('Run')
                    ->addNote('https://demos.flatsome.com/shop/clothing/hoodies/ship-your-idea-2/')
                    ->render();

                echo '</div> <!-- multiple_fields_a_line -->';
                echo '<div class="adminz_response adminz_response3"></div>';
            },
            $this->id,
            'adminz_tools_crawl_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Product category',
            function () {
                echo '<div class="multiple_fields_a_line fields_with_note_same_line">';
                // text
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_from_product_category]')
                    ->attributes(['class' => 'regular-text'])
                    ->value($this->settings['adminz_import_from_product_category'] ?? 'https://demos.flatsome.com/product-category/clothing/')
                    ->render();

                // button check
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button', 'adminz_fetch'],
                        'data-response' => '.adminz_response4',
                        'data-action' => 'check_adminz_import_from_product_category',
                    ])
                    ->value('Check')
                    ->render();

                // button run
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button', 'button-primary', 'adminz_fetch'],
                        'data-response' => '.adminz_response4',
                        'data-action' => 'run_adminz_import_from_product_category',
                    ])
                    ->value('Run')
                    ->addNote('https://demos.flatsome.com/product-category/clothing/')
                    ->render();

                echo '</div> <!-- multiple_fields_a_line -->';
                echo '<div class="adminz_response adminz_response4"></div>';
            },
            $this->id,
            'adminz_tools_crawl_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Images only',
            function () {
                echo '<div class="multiple_fields_a_line fields_with_note_same_line">';
                // text
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_images]')
                    ->attributes(['class' => 'regular-text'])
                    ->value($this->settings['adminz_import_images'] ?? 'https://demos.flatsome.com/product-category/clothing/')
                    ->render();

                // button check
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button', 'adminz_fetch'],
                        'data-response' => '.adminz_response55',
                        'data-action' => 'check_adminz_import_images',
                    ])
                    ->value('Check')
                    ->render();

                // button run
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('button')
                    ->attributes([
                        'class' => ['button', 'button-primary', 'adminz_fetch'],
                        'data-response' => '.adminz_response55',
                        'data-action' => 'run_adminz_import_images',
                    ])
                    ->value('Run')
                    ->addNote('https://demos.flatsome.com/product-category/clothing/')
                    ->render();

                echo '</div> <!-- multiple_fields_a_line -->';
                echo '<div class="adminz_response adminz_response55"></div>';
            },
            $this->id,
            'adminz_tools_crawl_tools'
        );

        // add section
        add_settings_section(
            'adminz_tools_css_selector',
            'Css Selector',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Post single',
            function () {
                echo '<div class="fields_with_note_same_line">';
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_post_title]')
                    ->attributes([
                        'placeholder' => 'Title wrapper',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_post_title'] ?? '.article-inner .entry-header .entry-title')
                    ->addNote('.article-inner .entry-header .entry-title')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_post_thumbnail]')
                    ->attributes([
                        'placeholder' => 'Thumbnail image',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_post_thumbnail'] ?? '.article-inner .entry-header .entry-image img')
                    ->addNote('.article-inner .entry-header .entry-image img')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_post_category]')
                    ->attributes([
                        'placeholder' => 'Categories ',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_post_category'] ?? '.entry-header .entry-category a')
                    ->addNote('.entry-header .entry-category a')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_post_content]')
                    ->attributes([
                        'placeholder' => 'Content wrapper',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_post_content'] ?? '.article-inner .entry-content')
                    ->addNote('.article-inner .entry-content')
                    ->render();
                echo '</div> <!-- fields_with_note_same_line -->';
            },

            $this->id,
            'adminz_tools_css_selector'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Category/ blog',
            function () {
                echo '<div class="fields_with_note_same_line">';
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_category_post_item]')
                    ->attributes([
                        'placeholder' => 'Post item wrapper',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_category_post_item'] ?? '#post-list article')
                    ->addNote('#post-list article')
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_category_post_item_link]')
                    ->attributes([
                        'placeholder' => 'Post item link',
                        'class' => 'regular-text',
                    ])
                    ->addClass('xchild')
                    ->value($this->settings['adminz_import_category_post_item_link'] ?? '.more-link')
                    ->addNote('.more-link')
                    ->render();
                echo '</div> <!-- fields_with_note_same_line -->';
            },
            $this->id,
            'adminz_tools_css_selector'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Product single',
            function () {
                echo '<div class="fields_with_note_same_line">';
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_product_title]')
                    ->attributes([
                        'placeholder' => 'Title wrapper',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_product_title'] ?? '.product-info>.product-title')
                    ->addNote('.product-info>.product-title')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_product_prices]')
                    ->attributes([
                        'placeholder' => 'Prices',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_product_prices'] ?? '.product-info .price-wrapper .woocommerce-Price-amount')
                    ->addNote('.product-info .price-wrapper .woocommerce-Price-amount')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_product_gallery_children_item]')
                    ->attributes([
                        'placeholder' => 'Gallery children item',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_product_gallery_children_item'] ?? '.woocommerce-product-gallery__image')
                    ->addNote('.woocommerce-product-gallery__image')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_product_content]')
                    ->attributes([
                        'placeholder' => 'Product content',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_product_content'] ?? '.woocommerce-Tabs-panel--description')
                    ->addNote('.woocommerce-Tabs-panel--description')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_product_short_description]')
                    ->attributes([
                        'placeholder' => 'Short description',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_product_short_description'] ?? '.product-short-description')
                    ->addNote('.product-short-description')
                    ->render();

                echo '</div> <!-- fields_with_note_same_line -->';
            },
            $this->id,
            'adminz_tools_css_selector'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Product list',
            function () {
                echo '<div class="fields_with_note_same_line">';
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_category_product_item]')
                    ->attributes([
                        'placeholder' => 'Item wrapper',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_category_product_item'] ?? '.products .product')
                    ->addNote('.products .product')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_category_product_item_link]')
                    ->attributes([
                        'placeholder' => 'Item wrapper link',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_import_category_product_item_link'] ?? '.box-image a')
                    ->addNote('.box-image a')
                    ->render();

                echo '</div> <!-- fields_with_note_same_line -->';
            },
            $this->id,
            'adminz_tools_css_selector'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Images only',
            function () {
                echo '<div class="fields_with_note_same_line">';
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[images_selector]')
                    ->attributes([
                        'placeholder' => 'Images',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['images_selector'] ?? '.products img')
                    ->addNote('.products img')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[images_selector_src]')
                    ->attributes([
                        'placeholder' => 'Images src',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['images_selector_src'] ?? 'src')
                    ->addNote('src')
                    ->render();
                echo '</div> <!-- fields_with_note_same_line -->';
            },
            $this->id,
            'adminz_tools_css_selector'
        );

        // add section
        add_settings_section(
            'adminz_tools_setup',
            'Setup crawl',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Check exists on wp_posts',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[post_exists_on_wpposts]')
                    ->value($this->settings['post_exists_on_wpposts'] ?? '')
                    ->copyButton(false)
                    ->addNote('If enabled, no posts with the same name will be created')
                    ->render();
            },
            $this->id,
            'adminz_tools_setup'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Content Fix',
            function () {
                echo '<div class="fields_with_note_same_line">';
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_content_remove_attrs]')
                    ->attributes([
                        'placeholder' => 'a',
                    ])
                    ->value($this->settings['adminz_import_content_remove_attrs'] ?? 'a')
                    ->addNote('Remove Attributes for Tags')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_import_content_remove_tags]')
                    ->attributes([
                        'placeholder' => 'iframe,script,video,audio',
                    ])
                    ->value($this->settings['adminz_import_content_remove_tags'] ?? 'iframe,script,video,audio')
                    ->addNote('Remove HTML Tags')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('number')
                    ->name($this->option_name . '[adminz_import_content_remove_first]')
                    ->attributes([
                        'min' => '0',
                        'placeholder' => 0,
                    ])
                    ->value($this->settings['adminz_import_content_remove_first'] ?? 0)
                    ->addNote('Removes the number of elements from the First')
                    ->render();

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('number')
                    ->name($this->option_name . '[adminz_import_content_remove_end]')
                    ->attributes([
                        'min' => '0',
                        'placeholder' => 0,
                    ])
                    ->value($this->settings['adminz_import_content_remove_end'] ?? 0)
                    ->addNote('Removes the number of elements from the End')
                    ->render();

                echo '</div> <!-- fields_with_note_same_line -->';
            },
            $this->id,
            'adminz_tools_setup'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Search and replace content',
            function () {
        ?>
            <table>
                <tr>
                    <td>
                        <?php
                        // field
                        $default = implode(
                            "\r\n",
                            [
                                'January',
                                'February',
                                'March',
                                'April',
                                'May',
                                'June',
                                'July',
                                'August',
                                'September',
                                'October',
                                'November',
                                'December',
                                '-100x100',
                                '-247x296',
                                '-510x510',
                            ]
                        );
                        echo \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('textarea')
                            ->name($this->option_name . '[adminz_import_content_replace_from]')
                            ->attributes([
                                'placeholder' => $default,
                                'rows' => 5,
                            ])
                            ->value($this->settings['adminz_import_content_replace_from'] ?? $default)
                            ->addNote('search')
                            ->render();

                        ?>
                    </td>
                    <td>
                        <?php
                        // field
                        $default = implode(
                            "\r\n",
                            [
                                'January',
                                'February',
                                'March',
                                'April',
                                'May',
                                'June',
                                'July',
                                'August',
                                'September',
                                'October',
                                'November',
                                'December',
                                '-100x100',
                                '-247x296',
                                '-510x510',
                            ]
                        );
                        echo \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('textarea')
                            ->name($this->option_name . '[adminz_import_content_replace_to]')
                            ->attributes([
                                'placeholder' => $default,
                                'rows' => 5,
                            ])
                            ->value($this->settings['adminz_import_content_replace_to'] ?? $default)
                            ->addNote('replace')
                            ->render();

                        ?>
                    </td>
                </tr>
            </table>
            <p>
                <small>
                    <strong>*Note: </strong>
                    You can put <strong>image size</strong> here
                </small>
            </p>
        <?php
            },
            $this->id,
            'adminz_tools_setup'
        );

        // field 
        add_settings_field(
            wp_rand(),
            "Post type",
            function () {

                //
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('select')
                    ->name($this->option_name . '[postarr][post_type]')
                    ->options(get_post_types(['public' => true]))
                    ->value($this->settings['postarr']['post_type'] ?? '')
                    ->addNote('Only for post')
                    ->render();
            },
            $this->id,
            'adminz_tools_setup'
        );

        // field 
        add_settings_field(
            wp_rand(),
            "Post parent",
            function () {

                //
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[postarr][post_parent]')
                    ->attributes([
                        'placeholder' => 'post parent ID',
                    ])
                    ->value($this->settings['postarr']['post_parent'] ?? '')
                    ->addNote(get_the_title($this->settings['postarr']['post_parent'] ?? ''))
                    ->render();
            },
            $this->id,
            'adminz_tools_setup'
        );

        // field 
        add_settings_field(
            wp_rand(),
            "Fixed taxonomy terms",
            function () {
                //
                $options = [];
                $taxonomies = get_taxonomies();
                foreach ((array) $taxonomies as $key => $value) {
                    $taxonomy = $key;
                    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
                    foreach ((array) $terms as $_key => $term) {
                        $_value = $term->term_id;
                        $_option = "$term->name ($term->term_id)($taxonomy)";
                        $options[$_value] = $_option;
                    }
                }
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[fixed_terms]')
                    ->value($this->settings['fixed_terms'] ?? false) // giá trị đã lưu
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
            'adminz_tools_setup'
        );

        // add section
        add_settings_section(
            'adminz_tools_setup_w',
            'Setup Woocommerce',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Price fix',
            function () {

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('number')
                    ->name($this->option_name . '[product_price_decimal_seprator]')
                    ->attributes([
                        'placeholder' => 2,
                    ])
                    ->value($this->settings['product_price_decimal_seprator'] ?? '2')
                    ->addNote('Product price remove decimal separator from END')
                    ->render();
            },
            $this->id,
            'adminz_tools_setup_w'
        );

        // add section
        add_settings_section(
            'adminz_tools_setup_cron',
            'Crawl Cron ',
            function () {
                //
            },
            $this->id
        );

        // 
        add_settings_field(
            wp_rand(),
            'Items per time',
            function () {

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[crawl_items_per_time]')
                    ->attributes([
                        'placeholder' => 1,
                    ])
                    ->value($this->settings['crawl_items_per_time'] ?? '')
                    ->addNote('Items per time')
                    ->render();
            },
            $this->id,
            'adminz_tools_setup_cron'
        );


        // field 
        add_settings_field(
            wp_rand(),
            'Crawl products by url',
            function () {
                $options = [];
                $taxonomies = get_taxonomies();
                foreach ((array) $taxonomies as $key => $value) {
                    $taxonomy = $key;
                    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
                    foreach ((array) $terms as $_key => $term) {
                        $_value = $term->term_id;
                        $_option = "$term->name ($term->term_id)($taxonomy)";
                        $options[$_value] = $_option;
                    }
                }

                // repeater
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[cron_product_categories]')
                    ->value($this->settings['cron_product_categories'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('fixed_term')
                            ->label('Taxonomy term')
                            ->copyButton(true)
                            ->options($options),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('url')
                            ->label('Url')
                            ->copyButton(true),

                    ])
                    ->default([
                        [
                            'fixed_term' => '',
                            'url' => '',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_tools_setup_cron'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Crawl posts by url',
            function () {
                $options = [];
                $taxonomies = get_taxonomies();
                foreach ((array) $taxonomies as $key => $value) {
                    $taxonomy = $key;
                    $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
                    foreach ((array) $terms as $_key => $term) {
                        $_value = $term->term_id;
                        $_option = "$term->name ($term->term_id)($taxonomy)";
                        $options[$_value] = $_option;
                    }
                }

                // repeater
                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[cron_post_categories]')
                    ->value($this->settings['cron_post_categories'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('fixed_term')
                            ->label('Taxonomy term')
                            ->copyButton(true)
                            ->options($options),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('url')
                            ->label('Url')
                            ->copyButton(true),

                    ])
                    ->default([
                        [
                            'fixed_term' => '',
                            'url' => '',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_tools_setup_cron'
        );

        // field 
        // add_settings_field(
        // 	wp_rand(),
        // 	'Crawl posts by url',
        // 	function () {
        // 		echo 'coming soon';
        // 	},
        // 	$this->id,
        // 	'adminz_tools_setup_cron'
        // );

        // field 
        add_settings_field(
            wp_rand(),
            'Files',
            function () {

        ?>
            <table>
                <tr>
                    <?php
                    foreach (glob(ADMINZ_DIR . 'includes/cron/*.php') as $filepath) {
                        $dir = dirname($filepath);
                        $filename = basename($filepath);
                        $text = "* 22-23,0-8 * * * cd $dir && php $filename >> log.txt 2>&1";
                        echo <<<HTML
                        <td>
                            <small class="adminz_click_to_copy" data-text="$text">
                                $text
                            </small>
                        </td>
                        HTML;
                    }
                    ?>

                </tr>
            </table>
<?php

            },
            $this->id,
            'adminz_tools_setup_cron'
        );


        // add section
        add_settings_section(
            'adminz_tools',
            'Tools',
            function () {
                //
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Server tools',
            function () {
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'serverinfo',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'serverspeed',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'phpinfo',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'phpextensions',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'cacheinfo',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'cacheinfo', 'memcached_host' => '', 'memcached_port' => ''], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'cacheinfo', 'redis_host' => '', 'redis_port' => ''], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'resetcache',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'resetcache', 'memcached_host' => '', 'memcached_port' => ''], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'resetcache', 'redis_host' => '', 'redis_port' => ''], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'mysqlinfo',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'memoryinfo',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_server_tool' => 'connectioncheck',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'File tools',
            function () {
                echo adminz_copy(add_query_arg(['adminz_file_permission_check' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_file_included_files' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_file_core_checksums' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_file_security_scan_php' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_file_large' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_file_image_clear_orphaned' => '',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Debug tools',
            function () {
                echo adminz_copy(add_query_arg(['adminz_show_debug_log' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_empty_debug_log' => '',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'User tools',
            function () {
                echo adminz_copy(add_query_arg(['adminz_user_login_with_user_id' => 'XXX',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Database tools',
            function () {
                echo adminz_copy(add_query_arg(['adminz_database_optimize_tables' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_database_transients' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_database_transients_clear' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_database_postmeta_clear_orphaned' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_database_user_delete_by_role' => 'subscriber',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_database_usermeta_clear_orphaned' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_database_attachment_clear_orphaned' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_database_scan' => '',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Test Tools',
            function () {
                echo adminz_copy(add_query_arg(['adminz_test_hooks' => 'wordpress',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_postfield' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_posttaxonomy' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_postmeta' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_termfield' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_userfield' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_usermeta' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_termmeta' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_theme_template' => 'XXX',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_test_rewrite_rules' => 'XXX',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Rewrite Rules',
            function () {
                echo adminz_copy(add_query_arg(['adminz_rewrite_rules_explore' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_rewrite_rules_flush' => '',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Others',
            function () {
                echo adminz_copy(add_query_arg(['adminz_show_debug_log' => '',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Woocommerce',
            function () {
                echo adminz_copy(add_query_arg(['adminz_delete_all_products' => '',], get_site_url())) . "<br>";
            },
            $this->id,
            'adminz_tools'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Media',
            function () {
                echo adminz_copy(add_query_arg(['adminz_delete_image_files_without_id' => '',], get_site_url())) . "<br>";
                echo adminz_copy(add_query_arg(['adminz_delete_image_ids_without_file' => '',], get_site_url())) . "<br>";

            },
            $this->id,
            'adminz_tools'
        );
    }
}
