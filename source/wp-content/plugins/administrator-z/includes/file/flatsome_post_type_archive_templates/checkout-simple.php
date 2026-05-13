<?php

/**
 * Checkout simple layout.
 *
 * @package          Flatsome/WooCommerce/Templates
 * @flatsome-version 3.16.0
 */

do_action('get_header', null, array());
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php flatsome_html_classes(); ?>">

<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <link rel="profile" href="http://gmpg.org/xfn/11" />
    <link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

    <?php do_action('flatsome_after_body_open'); ?>
    <?php wp_body_open(); ?>

    <div id="main-content" class="site-main">

        <div id="main" class="page-checkout-simple <?php flatsome_main_classes(); ?> <?php echo get_post_type(); ?>-archive adminz_uxbuilder_template">
            <?php
            $key = 'adminz_fl_archive_block_id_' . get_post_type();
            if ($template_block_id = ($GLOBALS[$key] ?? '')) {
                $_post_content = get_post_field('post_content', $template_block_id);
                if ($_post_content) {
                    echo do_shortcode($_post_content);
                }
            }
            ?>
        </div>

        <div class="focused-checkout-footer">
            <?php get_template_part('template-parts/footer/footer', 'absolute'); ?>
        </div>

    </div>

    <?php wp_footer(); ?>

</body>

</html>
