<?php
// THIS FILE IS CLONED FROM FLATSOME/page-blank-landingpage.php

/**
 * Template name: Page - No Header / No Footer
 *
 * @package          Flatsome\Templates
 * @flatsome-version 3.18.0
 */

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
    <?php do_action('flatsome_before_page'); ?>
    <?php do_action('flatsome_after_header'); ?>

    <div id="wrapper"
        class="<?php echo get_post_type(); ?>-wrapper <?php echo get_post_type(); ?>-single adminz_uxbuilder_template">
        <?php
        $key = 'adminz_fl_single_block_id_' . get_the_ID();
        if ($template_block_id = ($GLOBALS[$key] ?? '')) {
            $_post_content = get_post_field('post_content', $template_block_id);
            if ($_post_content) {
                echo do_shortcode($_post_content);
            }
        }
        ?>
    </div>

    <?php do_action('flatsome_after_page'); ?>

    <?php wp_footer(); ?>
</body>

</html>