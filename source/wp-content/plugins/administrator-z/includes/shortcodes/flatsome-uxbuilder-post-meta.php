<?php
$xxx = new \Adminz\Helper\FlatsomeELement;
$xxx->shortcode_name = 'adminz_post_meta';
$xxx->shortcode_title = 'Uxbuilder post meta';
$xxx->shortcode_icon = 'text';
$xxx->shortcode_type = 'container';
$xxx->shortcode_compile = false;

$options = [
    'meta_key' => [
        'type' => 'textfield',
        'heading' => 'Meta key',
        'default' => '_thumbnail_id',
    ]
];
$options = array_merge(
    $options,
    require ADMINZ_DIR . "includes/shortcodes/inc/flatsome-element-advanced.php",
);

$xxx->options = $options;
$xxx->shortcode_callback = function ($atts, $content) {

    $atts = shortcode_atts(
        array(
            "meta_key" => "_thumbnail_id",
            'css' => '',
            'class' => '',
            'visibility' => '',
        ),
        $atts,
    );


    $classes = array();
    if (! empty($atts['class'])) $classes[] = $atts['class'];
    if (! empty($atts['visibility'])) $classes[] = $atts['visibility'];

    if ($atts['visibility'] == 'hidden') {
        return;
    }

    ob_start(); ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <?php
        $meta_value = get_post_meta(get_the_ID(), $atts['meta_key'], true);
        if ($meta_value) {
            // Nếu là admin thì call luôn post field 
            if (adminz_flatsome_is_ux_builder()) {
                echo adminz_preview_text($meta_value);
            } else {
                // Nếu front-end thì kiểm tra có Template không
                $content = trim($content);
                if ($content) {
                    echo do_shortcode(str_replace("XXX", $meta_value, $content));
                } else {
                    echo do_shortcode($meta_value);
                }
            }
        }
        ?>
    </div>
<?php

    return ob_get_clean();
};

$xxx->general_element();
