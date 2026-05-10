<?php
$_________ = new \Adminz\Helper\FlatsomeELement;
$_________->shortcode_name = 'adminz_term_posts';
$_________->shortcode_title = 'Uxbuilder term posts';
$_________->shortcode_icon = 'text';
$_________->shortcode_type = 'container';
$_________->shortcode_compile = false;


$options = [
    'layout' => [
        'type' => 'select',
        'heading' => 'Layout',
        'default' => '3-col',
        'options' => [
            "" => "Default",
            "list" => "List",
            "inline" => "Inline",
            "2-col" => "2 Cols",
            "3-col" => "3 Cols",
        ],
    ],
];

$options = array_merge(
    $options,
    require ADMINZ_DIR . "includes/shortcodes/inc/flatsome-element-advanced.php",
);
$_________->options = $options;

$_________->shortcode_callback = function ($atts, $content = null) {

    $atts = shortcode_atts(
        array(
            "layout"     => "3-col",
            'css' => '',
            'class' => '',
            'visibility' => '',
        ),
        $atts,
    );

    if ($atts['visibility'] == 'hidden') {
        return;
    }

    $term = get_queried_object();
    if ('WP_Term' !== get_class($term)) {
        echo adminz_preview_text();
        return ob_get_clean();
    }

    $classes = array();
    if (!empty($atts['class'])) $classes[] = $atts['class'];
    if (!empty($atts['visibility'])) $classes[] = $atts['visibility'];

    ob_start();
?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <?php
        get_template_part('template-parts/posts/archive', $atts['layout']);
        ?>
    </div>
<?php
    return ob_get_clean();
};



$_________->general_element();
