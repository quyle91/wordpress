<?php
// THIS FILE IS CLONED FROM FLATSOME/INDEX.PHP

/**
 * The blog template file.
 *
 * @package Flatsome\Templates
 * @flatsome-version 3.16.0
 */

get_header();

?>

<div id="content" class="<?php echo get_post_type(); ?>-wrapper <?php echo get_post_type(); ?>-archive adminz_uxbuilder_template">
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

<?php get_footer();
