<?php
$xxx = new \Adminz\Helper\FlatsomeELement;
$xxx->shortcode_name = 'adminz_term_meta';
$xxx->shortcode_title = 'Uxbuilder term meta';
$xxx->shortcode_icon = 'text';
$xxx->shortcode_type = 'container';
$xxx->shortcode_compile = false;

$options = [
    'meta_key' => [
        'type' => 'textfield',
        'heading' => 'Meta key',
        'default' => 'thumbnail_id',
    ],
];
$options = array_merge(
    $options,
    require ADMINZ_DIR . "includes/shortcodes/inc/flatsome-element-advanced.php",
);

$xxx->options = $options;
$xxx->shortcode_callback = function ($atts, $content) {

    $atts = shortcode_atts(
        array(
            "meta_key" => "thumbnail_id",
            'css' => '',
            'class' => '',
            'visibility' => '',
        ),
        $atts,
    );


    $classes = array();
    if (!empty($atts['class'])) $classes[] = $atts['class'];
    if (!empty($atts['visibility'])) $classes[] = $atts['visibility'];

    if ($atts['visibility'] == 'hidden') {
        return;
    }

    ob_start(); ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <?php
        // Nếu là admin thì call luôn term field 
        if (adminz_flatsome_is_ux_builder()) {
            echo adminz_preview_text($atts['meta_key']);
        } else {
            $term = get_queried_object();
            if ('WP_Term' == get_class($term)) {
                $field = get_term_meta($term->term_id, $atts['meta_key'], true);
                $content = trim($content);
                if ($content) {
                    echo do_shortcode(str_replace("XXX", $field, $content));
                } else {
                    echo do_shortcode($field);
                }
            } else {
                echo 'Not a Term';
            }
        }

        ?>
    </div>
<?php

    return ob_get_clean();
};

$xxx->general_element();
