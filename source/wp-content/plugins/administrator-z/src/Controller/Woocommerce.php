<?php

namespace Adminz\Controller;

final class Woocommerce {
    private static $instance = null;
    public $id = 'adminz_woocommerce';
    public $name = 'Woocommerce';
    public $option_name = 'adminz_woocommerce';

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
    }

    function plugin_loaded() {
        // tinh huyen xa
        if (($this->settings['adminz_woocommerce_tinh_huyen_xa'] ?? "") == 'on') {
            $a = new \Adminz\Helper\TinhHuyenXa();
            $a->init();
        }

        // 
        if (($this->settings['adminz_woocommerce_simple_checkout_field'] ?? "") == 'on') {
            $a = new \Adminz\Helper\WooCheckout();
            $a->init();
        }

        // 
        if (($this->settings['validate_fields'] ?? "")) {
            $a = new \Adminz\Helper\WooCheckout();
            $a->validate_fields((array)$this->settings['validate_fields']);
        }

        // 
        if (($this->settings['custom_email_layout'] ?? "") == 'on') {
            $a = new \Adminz\Helper\WooEmail();
            $header = $this->settings['custom_email_header'] ?? '';
            $footer = $this->settings['custom_email_footer'] ?? '';
            $css = $this->settings['custom_email_css'] ?? '';
            $a->init($header, $footer, $css);
        }

        // 
        if (($this->settings['order_notification_telegram'] ?? '') == 'on') {
            global $adminz;
            if (
                ($adminz['Api']->settings['telegram_botToken'] ?? '') and
                ($adminz['Api']->settings['telegram_chatId'] ?? '')
            ) {
                $a = new \Adminz\Helper\WooTeleGram();
                $a->init(
                    $adminz['Api']->settings['telegram_botToken'],
                    $adminz['Api']->settings['telegram_chatId']
                );
            }
        }

        // 
        if ($this->settings['adminz_woo_currency_unit'] ?? "") {
            $a = new \Adminz\Helper\WooCurrency();
            $a->currency_unit($this->settings['adminz_woo_currency_unit']);
        }

        // 
        if (($this->settings['adminz_woo_currency_shortend'] ?? "") == 'on') {
            $a = new \Adminz\Helper\WooCurrency();
            $a->change_shortend();
        }

        // 
        if (($this->settings['adminz_woocommerce_discount_amount'] ?? "") == 'on') {
            $a = new \Adminz\Helper\WooOrdering();
            $a->setup_save_discount_data();
        }

        // 
        if (
            ($this->settings['adminz_woocommerce_enable_list_ordering'] ?? "") == 'on' and
            $this->settings['sort_ordering'] ?? []
        ) {
            $a = new \Adminz\Helper\WooOrdering();
            $a->setup_ordering($this->settings['sort_ordering']);
        }

        // 
        if (($this->settings['adminz_tooltip_products'] ?? "") == 'on') {
            $a = new \Adminz\Helper\WooTooltip();
            $a->init();
        }

        // 
        if (($this->settings['variable_product_price_custom'] ?? "") == 'on') {
            $a = new \Adminz\Helper\WooVariation();
            $a->setup_hide_max_price();
        }

        // 
        if ($text = ($this->settings['adminz_woocommerce_ajax_add_to_cart_text'] ?? "")) {
            adminz_add_body_class('adminz_custom_add_to_cart_text');
            add_filter('woocommerce_product_add_to_cart_text', function () use ($text) {
                return $text;
            });
            add_filter('woocommerce_product_single_add_to_cart_text', function () use ($text) {
                return $text;
            });
            add_filter('woocommerce_product_text', function () use ($text) {
                return $text;
            });
        }

        // 
        if ($text = ($this->settings['adminz_woocommerce_empty_price_html'] ?? "")) {
            add_action('woocommerce_single_product_summary', function () use ($text) {
                global $product;
                if (!$product->get_price()) {
                    echo do_shortcode($text);
                }
            }, 21);
        }

        // 
        if ($prefix = ($this->settings['price_prefix'] ?? "")) {
            add_filter('woocommerce_get_price_html', function ($price_html, $product) use ($prefix) {
                $price_html = "$prefix $price_html";
                return $price_html;
            }, 20, 2);
        }

        // 
        if ($suffix = ($this->settings['price_suffix'] ?? "")) {
            add_filter('woocommerce_get_price_html', function ($price_html, $product) use ($suffix) {
                $price_html = "$price_html $suffix";
                return $price_html;
            }, 20, 2);
        }

        // 
        if (($this->settings['adminz_woocommerce_description_readmore'] ?? "") == 'on') {
            add_action('woocommerce_before_single_product', function () {
                // add class to compatity with adminz.js
                echo <<<HTML
                    <script type="text/javascript">
                    document.addEventListener('DOMContentLoaded', function() {
                    var el = document.querySelector('.woocommerce-Tabs-panel--description');
                    if (el) {
                    el.classList.add('adminz_readmoreContent');
                    }
                    });
                    </script>
                HTML;
            });
        }

        // 
        if (($this->settings['move_yith_wishlist_button_inside_form'] ?? "") == 'on') {
            add_action('woocommerce_after_add_to_cart_button', function () {
                echo do_shortcode('[yith_wcwl_add_to_wishlist]');
            });
        }

        // Search-------------------
        add_filter('woocommerce_redirect_single_search_result', '__return_false');
        add_filter('woocommerce_product_query_meta_query', function ($meta_query) {
            foreach ($_GET as $key => $value) {
                if (str_starts_with($key, "meta_") and $value) {
                    $_key = str_replace('meta_', '', $key);
                    if (!isset($meta_query['relation'])) {
                        $meta_query['relation'] = 'AND';
                    }
                    $meta_query[] = [
                        'key' => $_key,
                        'compare' => 'EXISTS',
                    ];
                    $meta_query[] = [
                        'key' => $_key,
                        'compare' => 'IN',
                        'value' => $value,
                    ];
                }
            }
            return $meta_query;
        });

        // 
        if ($hooks = ($this->settings['adminz_woocommerce_action_hook'] ?? [])) {
            foreach ($hooks as $key => $value) {
                if ($value['key'] ?? '' and $value['value'] ?? '') {
                    $hook = $value['key'] ?? '';
                    $shortcode = $value['value'] ?? '';
                    $priority = $value['priority'] ?? '';

                    //
                    add_action($hook, function () use ($shortcode) {
                        // adminz_fix_override_post_global($shortcode);
                        echo do_shortcode($shortcode);
                    }, $priority);
                }
            }
        }

        // adminz_test_query
        if (($_GET['adminz_test_query'] ?? '') == 'woocommerce') {
            if (current_user_can('administrator')) {
                add_action('woocommerce_product_query', function ($query) {
                    // $query->set('posts_per_page', -1);
                    echo "<pre>";
                    print_r($query);
                    echo "</pre>";
                    die;
                });
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

    function register_settings() {
        register_setting($this->id, $this->option_name);

        // add section
        add_settings_section(
            'adminz_woocommerce_product_single',
            'Product single',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Add to cart text',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_woocommerce_ajax_add_to_cart_text]')
                    ->attributes([
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_woocommerce_ajax_add_to_cart_text'] ?? '')
                    ->addNote('x')
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_product_single'
        );

        add_settings_field(
            wp_rand(),
            'Empty price html',
            function () {
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_woocommerce_empty_price_html]')
                    ->attributes([
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_woocommerce_empty_price_html'] ?? '')
                    ->copyButton(true)
                    ->addNote(adminz_copy('[button text="Call now!" icon="icon-phone" icon_pos="left"]'))
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_product_single'
        );

        add_settings_field(
            wp_rand(),
            'Price prefix - suffix',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[price_prefix]')
                    ->attributes([
                        'placeholder' => 'from'
                    ])
                    ->value($this->settings['price_prefix'] ?? '')
                    ->copyButton(true)
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[price_suffix]')
                    ->attributes([
                        'placeholder' => 'per day'
                    ])
                    ->value($this->settings['price_suffix'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_product_single'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Description readmore',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_woocommerce_description_readmore]')
                    ->value($this->settings['adminz_woocommerce_description_readmore'] ?? '')
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_product_single'
        );

        if (defined('YITH_WCWL')) {
            // field 
            add_settings_field(
                wp_rand(),
                'Yith wishlist button',
                function () {
                    // field
                    echo \WpDatabaseHelperV2\Fields\WpField::make()
                        ->kind('input')
                        ->type('checkbox')
                        ->name($this->option_name . '[move_yith_wishlist_button_inside_form]')
                        ->value($this->settings['move_yith_wishlist_button_inside_form'] ?? '')
                        ->copyButton(false)
                        ->addNote('Move Yith wishlist button inside form')
                        ->render();
                },
                $this->id,
                'adminz_woocommerce_product_single'
            );
        }

        // field
        if (get_locale() == 'vi') {
            add_settings_field(
                wp_rand(),
                'Tỉnh/ huyện/ xã',
                function () {
                    // field
                    echo \WpDatabaseHelperV2\Fields\WpField::make()
                        ->kind('input')
                        ->type('checkbox')
                        ->name($this->option_name . '[adminz_woocommerce_tinh_huyen_xa]')
                        ->value($this->settings['adminz_woocommerce_tinh_huyen_xa'] ?? '')
                        ->copyButton(false)
                        ->addNote(get_site_url() . '/?do_import_tinh_huyen_xa')
                        ->render();
                },
                $this->id,
                'adminz_woocommerce_product_single'
            );
        }

        // add section
        add_settings_section(
            'adminz_woocommerce_product_archive',
            'Product archive',
            function () {
            },
            $this->id
        );

        add_settings_field(
            wp_rand(),
            'Order by discount amount',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_woocommerce_discount_amount]')
                    ->value($this->settings['adminz_woocommerce_discount_amount'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_product_archive'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'List ordering',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_woocommerce_enable_list_ordering]')
                    ->value($this->settings['adminz_woocommerce_enable_list_ordering'] ?? '')
                    ->copyButton(false)
                    ->render();

                //
                $options = apply_filters(
                    'woocommerce_catalog_orderby',
                    array(
                        'menu_order' => __('Default sorting', 'woocommerce'), // phpcs:ignore
                        'popularity' => __('Sort by popularity', 'woocommerce'), // phpcs:ignore
                        'rating' => __('Sort by average rating', 'woocommerce'), // phpcs:ignore
                        'date' => __('Sort by latest', 'woocommerce'), // phpcs:ignore
                        'price' => __('Sort by price: low to high', 'woocommerce'), // phpcs:ignore
                        'price-desc' => __('Sort by price: high to low', 'woocommerce'), // phpcs:ignore
                    )
                );
                $options['__discount_amount'] = __("Discount amount", 'woocommerce'); // phpcs:ignore
                echo \WpDatabaseHelperV2\Fields\WpSimpleRepeater::make()
                    ->name($this->option_name . '[sort_ordering]')
                    ->value($this->settings['sort_ordering'] ?? false) // giá trị đã lưu
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
            'adminz_woocommerce_product_archive'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Variation hide max price',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[variable_product_price_custom]')
                    ->value($this->settings['variable_product_price_custom'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_product_archive'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Tooltip hover',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_tooltip_products]')
                    ->value($this->settings['adminz_tooltip_products'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_product_archive'
        );

        // add section
        add_settings_section(
            'adminz_woocommerce_checkout',
            'Checkout',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Simple checkout fields',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_woocommerce_simple_checkout_field]')
                    ->value($this->settings['adminz_woocommerce_simple_checkout_field'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_checkout'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Validate fields',
            function () {
                $key_options = [];
                $checkout_fields = WC()->checkout()->get_checkout_fields();
                foreach ($checkout_fields as $fieldset_key => $fields) {
                    foreach ($fields as $field_key => $field_props) {
                        $key_options[$field_key] = $field_key;
                    }
                }

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[adminz_flatsome_action_hook]')
                    ->value($this->settings['adminz_flatsome_action_hook'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('field')
                            ->label('Field')
                            ->options($key_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('regex')
                            ->attributes(['placeholder' => "/^\+?[\d\s]{20,}$/"])
                            ->label('Your regex')
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('error')
                            ->attributes(['placeholder' => 'Error message'])
                            ->label('Error message')
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
            'adminz_woocommerce_checkout'
        );

        // add section
        add_settings_section(
            'adminz_woocommerce_email',
            'Email',
            function () {
            },
            $this->id
        );


        // field 
        add_settings_field(
            wp_rand(),
            'Custom email layout',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[custom_email_layout]')
                    ->value($this->settings['custom_email_layout'] ?? '')
                    ->copyButton(false)
                    ->addNote('<strong>Use HTML content type</strong> is required!')
                    ->render();


                // button
                echo adminz_toggle_button(__('Content'), ".xxxxxxxxxxxx");

                // start wrap
                echo '<div class="xxxxxxxxxxxx hidden" style="margin-top: 15px;">';

                // field
                ob_start();
                include ADMINZ_DIR . 'src/View/Woocommerce/templates/emails/email-header.php';
                $default_header = ob_get_clean();

                $value = $this->settings['custom_email_header'] ?? "";
                $value = $value ? $value : $default_header;
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->name($this->option_name . '[custom_email_header]')
                    ->attributes([
                        'cols' => 150,
                    ])
                    ->value($value)
                    ->addNote('adminz_woocommerce_email_header_image')
                    ->addNote('adminz_woocommerce_email_heading')
                    ->copyButton(true)
                    ->render();


                // field
                ob_start();
                include ADMINZ_DIR . 'src/View/Woocommerce/templates/emails/email-footer.php';
                $default_footer = ob_get_clean();
                $value = $this->settings['custom_email_footer'] ?? "";
                $value = $value ? $value : $default_footer;
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->name($this->option_name . '[custom_email_footer]')
                    ->attributes([
                        'cols' => 150,
                    ])
                    ->value($value)
                    ->addNote('adminz_woocommerce_email_footer_text')
                    ->copyButton(true)
                    ->render();

                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->name($this->option_name . '[custom_email_css]')
                    ->attributes([
                        'cols' => 150,
                        'placeholder' => 'Css here',
                    ])
                    ->value($this->settings['custom_email_css'] ?? '')
                    ->addNote('#header_wrapper { padding: 0 !important; }')
                    ->copyButton(true)
                    ->render();


                $preview_link = wp_nonce_url(admin_url('?preview_woocommerce_mail=true'), 'preview-mail');
                echo '<p><small><strong>' . _x('Suggested', 'custom headers') . ': </strong>' . adminz_copy($preview_link) . '</small></p>';
                echo '<p><small><strong>' . __('Notes') . ':*</strong> Leave empty and save to load default</small></p>';
                echo '<p><small><strong>' . __('Notes') . ':**</strong> CSS: Use !important in most cases. CSS is version 2.1</small></p>';
                echo '<p><small><strong>' . __('Notes') . ':***</strong> Images on email: Use <a href="https://imgur.com/">imgur.com</a> to get static image url. Height or width must be <strong>auto</strong>. </small></p>';

                // end wrap
                echo '</div>';
            },
            $this->id,
            'adminz_woocommerce_email'
        );

        // add section
        add_settings_section(
            'adminz_woocommerce_order',
            'Order',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Telegram order notification',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[order_notification_telegram]')
                    ->value($this->settings['order_notification_telegram'] ?? '')
                    ->copyButton(false)
                    ->addNote('Go to tab <strong>' . adminz_tab_link('Api') . '</strong>')
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_order'
        );

        // add section
        add_settings_section(
            'adminz_woocommerce_other',
            'Other',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Currency',
            function () {
                // text
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('text')
                    ->name($this->option_name . '[adminz_woo_currency_unit]')
                    ->attributes([
                        'placeholder' => 'VND',
                        'class' => 'regular-text',
                    ])
                    ->value($this->settings['adminz_woo_currency_unit'] ?? '')
                    ->copyButton(true)
                    ->addNote('Change current currency unit')
                    ->render();

                // checkbox
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[adminz_woo_currency_shortend]')
                    ->value($this->settings['adminz_woo_currency_shortend'] ?? '')
                    ->copyButton(false)
                    ->addNote('Currency shortend')
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_other'
        );

        // field 
        add_settings_section(
            'adminz_woocommerce_hooks',
            'Woocommere template hooks',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Test woocommerce hooks',
            function () {
                echo adminz_copy(add_query_arg(['adminz_test_hooks' => 'woocommerce',], get_site_url()));
            },
            $this->id,
            'adminz_woocommerce_hooks'
        );

        add_settings_field(
            wp_rand(),
            'Test woocommerce query',
            function () {
                echo adminz_copy(
                    add_query_arg(
                        ['adminz_test_query' => 'woocommerce',],
                        get_permalink(wc_get_page_id('shop'))
                    )
                );
            },
            $this->id,
            'adminz_woocommerce_hooks'
        );

        // field
        add_settings_field(
            wp_rand(),
            'Use hooks',
            function () {
                $shortcode = adminz_copy('[adminz_test]');
                echo <<<HTML
                <p> Use: {$shortcode} </p>
                <br>
                HTML;


                $key_options = [];
                $woocommerce_action_hooks = require(ADMINZ_DIR . "includes/file/woocommerce_hooks.php");
                foreach ($woocommerce_action_hooks as $value) {
                    $key_options[$value] = $value;
                }

                echo \WpDatabaseHelperV2\Fields\WpRepeater::make()
                    ->name($this->option_name . '[adminz_woocommerce_action_hook]')
                    ->value($this->settings['adminz_woocommerce_action_hook'] ?? false) // giá trị đã lưu
                    ->childDirection('wrap')
                    ->fields([

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('select')
                            ->name('key')
                            ->label('Hook')
                            ->options($key_options)
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name('value')
                            ->attributes(['placeholder' => 'shortcode here'])
                            ->label('Your text')
                            ->copyButton(true),

                        //
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('number')
                            ->name('priority')
                            ->attributes(['placeholder' => '10'])
                            ->label('Priority')
                            ->copyButton(true),
                    ])
                    ->default([
                        [
                            'key' => '',
                            'value' => '',
                            'priority' => '10',
                        ],
                    ])
                    ->render();
            },
            $this->id,
            'adminz_woocommerce_hooks'
        );
    }
}
