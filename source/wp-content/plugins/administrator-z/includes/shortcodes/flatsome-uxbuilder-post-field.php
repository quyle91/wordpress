<?php
$xxx = new \Adminz\Helper\FlatsomeELement;
$xxx->shortcode_name = 'adminz_post_field';
$xxx->shortcode_title = 'Uxbuilder post field';
$xxx->shortcode_icon = 'text';
$xxx->shortcode_type = 'container';
$xxx->shortcode_compile = false;


$options = [
    'post_field' => [
        'type' => 'select',
        'heading' => 'Select Field',
        'default' => 'post_title',
        'options' => [
            "ID" => "ID",
            "post_author" => "post_author",
            "post_date" => "post_date",
            "post_date_gmt" => "post_date_gmt",
            "post_content" => "post_content",
            "post_title" => "post_title",
            "post_excerpt" => "post_excerpt",
            "post_status" => "post_status",
            "comment_status" => "comment_status",
            "ping_status" => "ping_status",
            "post_password" => "post_password",
            "post_name" => "post_name",
            "to_ping" => "to_ping",
            "pinged" => "pinged",
            "post_modified" => "post_modified",
            "post_modified_gmt" => "post_modified_gmt",
            "post_content_filtered" => "post_content_filtered",
            "post_parent" => "post_parent",
            "guid" => "guid",
            "menu_order" => "menu_order",
            "post_type" => "post_type",
            "post_mime_type" => "post_mime_type",
            "comment_count" => "comment_count",
            "filter" => "filter",
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
            "post_field" => "post_title",
            'css' => '',
            'class' => '',
            'visibility' => '',
        ),
        $atts,
    );

    $classes = array();
    if (!empty($atts['class'])) $classes[] = $atts['class'];
    if (!empty($atts['visibility'])) $classes[] = $atts['visibility'];

    if($atts['visibility'] == 'hidden') {
        return;
    }

    ob_start(); ?>
    <div class="<?php echo esc_attr(implode(' ', $classes)); ?>">
        <?php
        $field = get_post_field($atts['post_field']);
        if ($field) {
            // Nếu là admin thì call luôn post field 
            if (adminz_flatsome_is_ux_builder()) {
                echo adminz_preview_text($field);
            } else {
                // Nếu front-end thì kiểm tra có Template không
                $content = trim($content);
                if ($content) {
                    $content = str_replace("XXX", $field, $content);
                } else {
                    $content = $field;
                }
                echo apply_filters('the_content', $content);
            }
        }
        ?>
    </div>
<?php
    return ob_get_clean();
};
$xxx->general_element();
