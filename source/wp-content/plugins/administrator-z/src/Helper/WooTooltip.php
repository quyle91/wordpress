<?php

namespace Adminz\Helper;

class WooTooltip {

    function __construct() {
        //
    }

    function init() {
        add_action('woocommerce_before_shop_loop_item', function () {
            global $product;

            ob_start();
            do_action('adminz_product_tooltip');
            $tooltip_content = ob_get_clean();

            echo <<<HTML
            <div class="tooltip_data" style="display: none;" data-product_id="{$product->get_id()}">
                {$tooltip_content}
            </div>
            HTML;
        });

        add_action('adminz_product_tooltip', function () {
            global $product;
            $short_description = apply_filters('the_content', $product->get_short_description());
            echo <<<HTML
            <div class="admz_shortdescription">{$short_description}</div>
            HTML;
        }, 30);

        add_action('wp_footer', function () {
            echo '<div class="adminz_tooltip_box entry-summary border-radius"></div>';
        });

        add_action('wp_enqueue_scripts', function () {
            wp_enqueue_script(
                'adminz_woo_tooltip',
                ADMINZ_DIR_URL . 'assets/js/adminz_woo_tooltip.js',
                [],
                ADMINZ_VERSION,
                true
            );
        });
    }
}
