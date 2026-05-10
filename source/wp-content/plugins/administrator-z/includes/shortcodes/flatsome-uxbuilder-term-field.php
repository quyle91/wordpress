<?php
$xxx = new \Adminz\Helper\FlatsomeELement;
$xxx->shortcode_name = 'adminz_term_field';
$xxx->shortcode_title = 'Uxbuilder term field';
$xxx->shortcode_icon = 'text';
$xxx->shortcode_type = 'container';
$xxx->shortcode_compile = false;


$options = [
    'term_field' => [
        'type' => 'select',
        'heading' => 'Select Field',
        'default' => 'term_title',
        'options' => [
            'term_id' => 'term_id',
            'name' => 'name',
            'slug' => 'slug',
            'term_group' => 'term_group',
            'term_taxonomy_id' => 'term_taxonomy_id',
            'taxonomy' => 'taxonomy',
            'description' => 'description',
            'parent' => 'parent',
            'count' => 'count',
            'filter' => 'filter',
        ],
    ],
];

$options = array_merge(
    $options,
    require ADMINZ_DIR . "includes/shortcodes/inc/flatsome-element-advanced.php",
);
$xxx->options = $options;

$xxx->shortcode_callback = function ($atts, $content = null) {
    $atts = shortcode_atts(
        array(
            "term_field" => "name",
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
            echo adminz_preview_text($atts['term_field']);
        } else {
            $term = get_queried_object();
            if ('WP_Term' == get_class($term)) {
                $field = get_term_field($atts['term_field'], $term);
                $content = trim($content);
                if ($content) {
                    $content = str_replace("XXX", $field, $content);
                } else {
                    $content = $field;
                }
                echo apply_filters('the_content', $content);
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
