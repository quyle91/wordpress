<?php

namespace Adminz\Controller;

final class Wpcf7 {
    private static $instance = null;
    public $id = 'adminz_contactform7';
    public $name = 'Contact form 7';
    public $option_name = 'adminz_cf7';

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

        // ------------------ 
        if ($this->settings['anti_spam'] ?? "") {
            /*
			 * Chống spam cho contact form 7
			 * Author: levantoan.com
			 * */
            /*Thêm 1 field ẩn vào form cf7*/
            add_filter('wpcf7_form_elements', function ($html) {
                $html = '<div style="display: none"><p><span class="wpcf7-form-control-wrap" data-name="devvn"><input size="40" class="wpcf7-form-control wpcf7-text" aria-invalid="false" value="" type="text" name="devvn"></span></p></div>' . $html;
                return $html;
            });

            /*Kiểm tra form đó mà được nhập giá trị thì là spam*/
            add_action('wpcf7_posted_data', function ($posted_data) {
                $submission = \WPCF7_Submission::get_instance();
                if (!empty($posted_data['devvn'])) {
                    $submission->set_status('spam');
                    $submission->set_response('You are Spamer');
                }
                unset($posted_data['devvn']);
                return $posted_data;
            });
        }

        // ------------------ 
        if ($this->settings['allow_shortcode'] ?? "") {
            add_filter('wpcf7_form_elements', function ($form) {
                return do_shortcode($form);
            });
        }

        // ------------------ 
        if ($this->settings['allow_shortcode_email'] ?? "") {
            add_filter('wpcf7_mail_components', function ($components) {
                if (isset($components['body'])) {
                    $components['body'] = do_shortcode($components['body']);
                }
                return $components;
            }, 100, 1);
        }

        // ------------------ 
        if ($this->settings['remove_auto_p'] ?? "") {
            //
            add_filter('wpcf7_autop_or_not', '__return_false');
            // enable auto p on email 
            add_action('wpcf7_before_send_mail', function () {
                add_filter('wpcf7_autop_or_not', '__return_true');
            });
        }

        // ------------------ 
        if ($this->settings['custom_email_layout'] ?? "") {
            if ($custom_email_content = ($this->settings['custom_email_content'] ?? "")) {
                add_filter('wpcf7_mail_components', function ($components) use ($custom_email_content) {
                    $components['body'] = str_replace(
                        '{wpcf7_email_body}',
                        $components['body'],
                        $custom_email_content
                    );

                    return $components;
                }, 10, 1);
            }
        }

        // ------------------ 
        if ($this->settings['form_newletters'] ?? "") {
            $a = new \Adminz\Helper\Wpcf7();
            $a->make_form_newletters(
                $this->settings['form_newletters'],
                'adminz_newletters_email'
            );
        }

        // ------------------ 
        if ($this->settings['thankyou'] ?? "") {
            $a = new \Adminz\Helper\Wpcf7();
            $a->make_thankyou();
        }

        // ------------------ 
        if ($this->settings['save_submissions'] ?? "") {
            $a = new \Adminz\Helper\Wpcf7();
            $a->save_submissions();
        }

        // ------------------ 
        if ($this->settings['date_time_placeholder'] ?? "" == 'on') {
            adminz_add_body_class('adminz_enable_adminz_wpcf7_toggle');
        }

        // ------------------ 
        if ($this->settings['prefill_form_by_url_params'] ?? "" == 'on') {
            $a = new \Adminz\Helper\Wpcf7();
            $a->prefill_form_by_url_params();
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
            'adminz_cf7_section',
            'Contact Form 7',
            function () {
            },
            $this->id
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Anti spam by DevVN',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[anti_spam]')
                    ->value($this->settings['anti_spam'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Allow shortcode',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[allow_shortcode]')
                    ->value($this->settings['allow_shortcode'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Allow shortcode in email',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[allow_shortcode_email]')
                    ->value($this->settings['allow_shortcode_email'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Remove auto p tag',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[remove_auto_p]')
                    ->value($this->settings['remove_auto_p'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
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
                    ->render();

                // field
                echo adminz_toggle_button(__('Content'), ".xxxxxxxxxxxx");
                $default = <<<HTML
				<body style="background:#f6f6f6; padding: 70px 0;">
					<div style="max-width: 560px; padding: 20px; margin: auto; background-color: white;">
						{wpcf7_email_body}
					</div>
				</body>
				HTML;
                $value = $this->settings['custom_email_content'] ?? "";
                $value = $value ? $value : $default;

                // field
                echo '<div class=" xxxxxxxxxxxx hidden" style="margin-top: 15px;">';
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('textarea')
                    ->name($this->option_name . '[custom_email_content]')
                    ->value($value)
                    ->addNote('{wpcf7_email_body}')
                    ->copyButton(true)
                    ->render();
                echo '</div>';
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Form as newsletters',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('select')
                    ->name($this->option_name . '[form_newletters]')
                    ->optionsTemplate('post_select', [
                        'post_type' => 'wpcf7_contact_form',
                    ])
                    ->value($this->settings['form_newletters'] ?? "")
                    ->addNote('Use Field email name: ' . adminz_copy('adminz_newletters_email', false) . "as default, and only for Post type <strong>Post</strong>")
                    ->copyButton(true)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Thankyou',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[thankyou]')
                    ->value($this->settings['thankyou'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Save submissions',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[save_submissions]')
                    ->value($this->settings['save_submissions'] ?? '')
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Date time placeholder',
            function () {
                // field
                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[date_time_placeholder]')
                    ->value($this->settings['date_time_placeholder'] ?? '')
                    ->addNote('All hidden fields value will be merge into primary field')
                    ->copyButton(false)
                    ->render();

                // field
                echo adminz_toggle_button(__('Guid'), ".xxxxxxxxxxxx1");
                $value = '<div class="adminz_wpcf7_toggle">
					<div class="hidden">[date date-244]</div>
					<div class="hidden">[date date-245]</div>
					[text text-266 class:primary_field placeholder "Select dates"]
                </div>';
                echo '<br>';
                echo '<textarea disabled cols="65" rows="8" class="xxxxxxxxxxxx1 hidden">' . $value . '</textarea>';
            },
            $this->id,
            'adminz_cf7_section'
        );

        // field 
        add_settings_field(
            wp_rand(),
            'Prefill form by Url parameters',
            function () {
                // field

                $contact_page = get_site_url() . '/contact';
                $args = [
                    'your-name' => 'Quy',
                    'your-email' => 'test@gmail.com',
                    'your-phone' => '0988888888',
                    // 'your-subject' => 'Bao gia website',
                    // 'your-service' => 'SEO',
                    // 'your-gender' => 'Nam',
                    'your-interest' => 'Web design',
                    // 'your-message' => 'Test tu URL  ',
                ];
                $test_url = add_query_arg($args, $contact_page);

                echo \WpDatabaseHelperV2\Fields\WpField::make()
                    ->kind('input')
                    ->type('checkbox')
                    ->name($this->option_name . '[prefill_form_by_url_params]')
                    ->value($this->settings['prefill_form_by_url_params'] ?? '')
                    ->addNote(adminz_copy($test_url, true))
                    ->copyButton(false)
                    ->render();
            },
            $this->id,
            'adminz_cf7_section'
        );
    }
}
