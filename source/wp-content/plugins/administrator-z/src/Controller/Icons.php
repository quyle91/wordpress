<?php

namespace Adminz\Controller;

final class Icons {
    private static $instance = null;
    public $id = 'adminz_icons';
    public $name = 'Icons';
    public $option_name = 'adminz_icons';

    public $settings = [];
    public $icons = [];
    public $custom_icons = [];
    public $dashicons = [];

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
    }

    function load_settings() {
        $this->settings = get_option($this->option_name, []);

        // dashicons
        foreach ((array) adminz_get_list_dashicons() as $key => $value) {
            $this->dashicons[$value] = $value;
        }

        // icons
        foreach (glob(ADMINZ_DIR . '/assets/icons/*.svg') as $path) {
            // $this->icons[] = str_replace( '.svg', '', basename( $path ) );
            $icon = str_replace('.svg', '', basename($path));
            $this->icons[$icon] = $path;
        }

        // custom icons
        foreach ($this->settings['custom_icons'] ?? [] as $key => $item) {
            $this->custom_icons[$item['key']] = $item['value'];
        }
        // echo "<pre>"; print_r($this); echo "</pre>";
    }

    function add_admin_nav($nav) {
        $nav[$this->id] = $this->name;
        return $nav;
    }

    function register_settings() {
        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz_icons',
            'Icons',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Shortcode',
            function () {
                echo <<<HTML
                <small class="adminz_click_to_copy" data-text='[adminz_icon icon="clock" max_width="16px" class="footer_icon"]'>
                    shortcode: [adminz_icon icon="clock" max_width="16px" class="footer_icon"]
                </small>
                <small class="adminz_click_to_copy" data-text='[adminz_icon icon="clock" max_width="16px" class="footer_icon"]'>
                    php: adminz_get_icon('info-circle')
                </small>
                HTML;
            },
            $this->id,
            'adminz_icons'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Adminz icons',
            function () {

                // adminz icon
                if (!empty($this->icons)) {
                    foreach ($this->icons as $icon => $path) {
                        $_icon = $this->get_icon_html($icon, ['width' => '30px', 'height' => '30px']);
                        echo <<<HTML
                        <div
                            class="adminz_click_to_copy adminz_icon_item"
                            data-text="$icon">
                            $_icon
                            <small class="icon_name">$icon</small>
                        </div>
                        HTML;
                    }
                }
            },
            $this->id,
            'adminz_icons'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Dashicon icons',
            function () {

                // dashicon
                foreach ($this->dashicons as $icon) {
                    $_icon = $this->get_icon_html($icon, ['style' => ['font-size' => '30px']]);
                    $name = str_replace('dashicons-', '', $icon);
                    echo <<<HTML
                    <div
                        class="adminz_click_to_copy adminz_icon_item"
                        data-text="$icon">
                        $_icon
                        <small class="icon_name">
                            $name
                        </small>
                    </div>
                    HTML;
                }
            },
            $this->id,
            'adminz_icons'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Custom icons',
            function () {

                //
                if (!empty($this->custom_icons)) {
                    foreach ($this->custom_icons as $icon => $path) {
                        if (!$icon) continue;
                        $_icon = $this->get_icon_html($icon, ['width' => '30px', 'height' => '30px']);

                        echo <<<HTML
                        <div
                            class="adminz_click_to_copy adminz_icon_item"
                            data-text="$icon">
                            $_icon
                        </div>
                        HTML;
                    }
                    echo '</br>';
                    echo '</br>';
                }

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[custom_icons]')
                    ->value($this->settings['custom_icons'] ?? false)
                    ->childDirection('wrap')
                    ->fields([
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('key')
                            ->attributes(
                                [
                                    'placeholder' => 'Icon code'
                                ]
                            )
                            ->copyButton(true),

                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('wp_media')
                            ->name('value')
                            ->attributes(
                                [
                                    'placeholder' => 'Image url'
                                ]
                            )
                            ->copyButton(true),
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_icons'
        );
    }

    function get_icon_dashicons($icon, $attr = []) {
        wp_enqueue_style('dashicons');

        // Các thuộc tính mặc định
        $default_attr = [
            'class' => [
                $icon,
                'adminz_dashicon',
                'dashicons',
            ],
            'alt' => [
                'adminz'
            ],
            'style' => [
                'color' => 'currentColor',
                'font-size' => '1em',
                'height' => '1em',
                'width' => '1em',
                'position' => 'relative',
            ],
        ];

        // Chuẩn hóa các thuộc tính
        foreach ($attr as $key => $value) {
            $attr[$key] = is_array($value) ? $value : explode(' ', $value);
        }

        // Duyệt qua $default_attr để thêm thuộc tính nếu $attr không có
        foreach ($default_attr as $key => $default_values) {
            if (!isset($attr[$key])) {
                $attr[$key] = $default_values;
            } else {
                $attr[$key] = array_merge($default_values, $attr[$key]);
            }
        }

        // Xây dựng chuỗi thuộc tính
        $attr_strings = [];
        foreach ($attr as $key => $values) {
            if ($key === 'style' && is_array($values)) {
                // Định dạng style dưới dạng CSS
                $style_string = '';
                foreach ($values as $property => $value) {
                    $style_string .= is_int($property) ? "$value; " : "$property: $value; ";
                }
                $attr_strings[] = $key . '="' . trim($style_string) . '"';
            } else {
                // Định dạng các thuộc tính khác
                $attr_strings[] = $key . '="' . implode(' ', (array)$values) . '"';
            }
        }

        // Trả về mã HTML của icon
        return '<i ' . implode(' ', $attr_strings) . '></i>';
    }

    function get_icon_adminz($icon, $attr = []) {
        $attr_array = $this->get_icon_attrs($attr);
        $iconurl = $this->icons[$icon];
        $response = @file_get_contents($iconurl);
        return $this->cleansvg($response, implode(' ', $attr_array));
    }

    function get_icon_custom($icon, $attr = []) {
        $attr_array = $this->get_icon_attrs($attr);
        $icon_id = $this->custom_icons[$icon];
        $iconurl = wp_get_attachment_url($icon_id);

        // check if is svg $iconurl strendwith
        $extension = strtolower(pathinfo($iconurl, PATHINFO_EXTENSION));
        if ($extension === 'svg') {
            $icon_id = $this->custom_icons[$icon];
            $icon_path = get_attached_file($icon_id);
            $response = @file_get_contents($icon_path);
            return $this->cleansvg($response, implode(' ', $attr_array));
        }

        return '<img ' . implode(' ', $attr_array) . ' src="' . esc_url($iconurl) . '"/>';
    }

    function get_icon_attrs($attr = []) {
        // Thuộc tính mặc định
        $default_attr = [
            // 'width' => $is_image ? 'auto' : '1em',
            'width' => '1em',
            'height' => '1em',
            'class' => [
                'adminz_svg',
            ],
            'alt' => [
                'adminz',
            ],
            'style' => [
                // 'fill' => 'currentColor',
                'width' => '1em',
                'height' => '1em',
                'vertical-align' => 'middle',
            ],
        ];

        // override with param values
        $default_attr = wp_parse_args($attr, $default_attr);

        // fix style
        if (!empty($default_attr['width']) and !empty($default_attr['style']['width'])) {
            unset($default_attr['style']['width']);
        }
        if (!empty($default_attr['height']) and !empty($default_attr['style']['height'])) {
            unset($default_attr['style']['height']);
        }

        // Chuẩn hóa các thuộc tính
        foreach ($default_attr as $key => $default_values) {
            if (!isset($attr[$key])) {
                $attr[$key] = $default_values;
            } else {
                $attr[$key] = is_array($attr[$key]) ? array_merge($default_values, $attr[$key]) : explode(' ', $attr[$key]);
            }
        }


        // Xây dựng chuỗi thuộc tính
        $attr_strings = [];
        foreach ($attr as $key => $values) {
            if ($key === 'style' && is_array($values)) {
                // Định dạng style theo CSS
                $style_string = '';
                foreach ($values as $property => $value) {
                    $style_string .= is_int($property) ? "$value; " : "$property: $value; ";
                }
                $attr_strings[] = $key . '="' . trim($style_string) . '"';
            } else {
                // Định dạng cho các thuộc tính khác
                $attr_strings[] = $key . '="' . implode(' ', (array)$values) . '"';
            }
        }

        return $attr_strings;
    }

    function get_icon_html($icon = 'info-circle', $attr = []) {

        // Kiểm tra dashicon
        if (array_key_exists($icon, $this->dashicons)) {
            return $this->get_icon_dashicons($icon, $attr);
        }

        // kiểm tra custom icon
        if (array_key_exists($icon, $this->icons)) {
            return $this->get_icon_adminz($icon, $attr);
        }

        // kiểm tra icon svg custom
        if (array_key_exists($icon, $this->custom_icons)) {
            return $this->get_icon_custom($icon, $attr);
        }

        // prepare attr
        if (empty($attr)) {
            $attr = $this->get_icon_attrs();
        }

        // is local file path
        if (is_file($icon) && file_exists($icon)) {
            $response = @file_get_contents($icon);
            return $this->cleansvg($response, implode(' ', $attr));
        }

        // is url
        if (filter_var($icon, FILTER_VALIDATE_URL)) {
            $iconurl = $icon;
            return '<img ' . implode(' ', $attr) . ' src="' . esc_url($iconurl) . '"/>';
        }

        // is numeric
        if (ctype_digit($icon)) {
            $icon_id = $icon;
            $iconurl = wp_get_attachment_url($icon_id);
            return '<img ' . implode(' ', $attr) . ' src="' . esc_url($iconurl) . '"/>';
        }

        //
        return "not supported for this icon!";
    }

    public function cleansvg($response, $attr_item) {
        $return = "";
        preg_match('/<svg[^>]*>(.*?)<\/svg>/is', $response, $matches);
        if (isset($matches[0])) {
            $response = $matches[0];
            $return = str_replace(
                '<svg',
                '<svg ' . $attr_item,
                $response
            );
            $return = preg_replace('/<!--(.*)-->/', '', $return);
        }
        return $return;
    }
}
