<?php

namespace Adminz\Helper;

class WooEmail {

    function __construct() {
        //
    }

    private $header, $footer, $css;
    function init($header = '', $footer = '', $css = '') {

        // 
        $this->header = $header;
        $this->footer = $footer;
        $this->css = $css;

        // load default
        $this->load_default();

        // 
        $this->remove_default_header();
        $this->add_custom_header();

        //
        $this->remove_default_footer();
        $this->add_custom_footer();

        // css
        $this->add_custom_css();
    }

    function load_default() {
        if (!$this->header) {
            ob_start();
            include ADMINZ_DIR . 'src/View/Woocommerce/templates/emails/email-header.php';
            $default_header = ob_get_clean();
            $this->header = $default_header;
        }
        if (!$this->footer) {
            ob_start();
            include ADMINZ_DIR . 'src/View/Woocommerce/templates/emails/email-footer.php';
            $default_footer = ob_get_clean();
            $this->footer = $default_footer;
        }
    }

    function remove_default_header() {
        add_action('init', function () {
            $mailer = WC()->mailer();
            remove_action('woocommerce_email_header', [$mailer, 'email_header']);
        });
    }

    function remove_default_footer() {
        add_action('init', function () {
            $mailer = WC()->mailer();
            remove_action('woocommerce_email_footer', [$mailer, 'email_footer']);
        });
    }

    function add_custom_header() {
        add_action('woocommerce_email_header', function ($email_heading, $email) {
            echo $this->replace_variables($this->header, $email_heading, $email);
        }, 10, 2);
    }

    function add_custom_footer() {
        add_action('woocommerce_email_footer', function ($email) {
            echo $this->replace_variables($this->footer);
        }, 10, 1);
    }

    function add_custom_css() {
        add_filter('woocommerce_email_styles', function ($return) {
            $return .= $this->css;
            return $return;
        });
    }

    function replace_variables($string, $email_heading = false, $email = false) {

        // header
        if (str_contains($string, '{adminz_woocommerce_email_header_image}')) {
            ob_start();
            $email_improvements_enabled = \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled('email_improvements');
            $img = get_option('woocommerce_email_header_image');
            /**
             * This filter is documented in templates/emails/email-styles.php
             *
             * @since 9.6.0
             */
            if (apply_filters('woocommerce_is_email_preview', false)) {
                $img_transient = get_transient('woocommerce_email_header_image');
                $img           = false !== $img_transient ? $img_transient : $img;
            }

            if ($img) {
                echo '<p style="margin-top:0;"><img src="' . esc_url($img) . '" alt="' . esc_attr(get_bloginfo('name', 'display')) . '" /></p>';
            } elseif ($email_improvements_enabled) {
                echo '<p class="email-logo-text">' . esc_html(get_bloginfo('name', 'display')) . '</p>';
            }
            $content_replaced = ob_get_clean();
            $string = str_replace('{adminz_woocommerce_email_header_image}', $content_replaced, $string);
        }

        if (str_contains($string, '{adminz_woocommerce_email_heading}')) {
            $content_replaced = $email_heading;
            $string = str_replace('{adminz_woocommerce_email_heading}', $content_replaced, $string);
        }

        // footer
        if (str_contains($string, '{adminz_woocommerce_email_footer_text}')) {
            ob_start();
            $email_footer_text = get_option('woocommerce_email_footer_text');
            /**
             * This filter is documented in templates/emails/email-styles.php
             *
             * @since 9.6.0
             */
            if (apply_filters('woocommerce_is_email_preview', false)) {
                $text_transient    = get_transient('woocommerce_email_footer_text');
                $email_footer_text = false !== $text_transient ? $text_transient : $email_footer_text;
            }
            echo wp_kses_post(
                wpautop(
                    wptexturize(
                        /**
                         * Provides control over the email footer text used for most order emails.
                         *
                         * @since 4.0.0
                         *
                         * @param string $email_footer_text
                         */
                        apply_filters('woocommerce_email_footer_text', $email_footer_text)
                    )
                )
            );
            $content_replaced = ob_get_clean();
            $string = str_replace('{adminz_woocommerce_email_footer_text}', $content_replaced, $string);
        }

        return do_shortcode($string);
    }
}
