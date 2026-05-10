<?php
// là 1 phần của setup magnific popup
// Xem phần mô tả của option: href

$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_mfp_button';
$___->shortcode_title = 'Mfp Toggle button';
$___->shortcode_icon = 'text';
$___->options = array_merge(
    [
        'filter_text' => array(
            'type' => 'textfield',
            'heading' => 'filter_text',
            'default' => 'filter_text',
        ),
        'href' => array(
            'type' => 'textfield',
            'heading' => 'href',
            'default' => '#xxx',
            'description' => 'Make sure #xxx exists and has class .mfp-hide. You can copy as shortcode and use HTML Element to make an div with ID.',
        ),
        'pos' => array(
            'type' => 'textfield',
            'heading' => 'pos',
            'default' => 'left',
        ),
        'link_class' => array(
            'type' => 'textfield',
            'heading' => 'link_class',
            'default' => 'filter-button uppercase plain',
        ),
        'icon' => array(
            'type' => 'select',
            'heading' => 'icon',
            'default' => 'dashicons-menu',
            'options' => adminz_get_list_icons(),
        ),
    ],
    [
        'advanced_options' => require(get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php')
    ]
);
$___->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'filter_text' => 'filter_text',
        'href' => '#xxx',
        'pos' => "left",
        'link_class' => 'filter-button uppercase plain',
        'icon' => 'dashicons-menu',
        'class' => '',
        'visibility' => '',
    ), $atts));

    if ($visibility == 'hiden') return;

    $classes = ['adminz_mfp_button', 'adminz_mfp_toggle_button', $class, $visibility];

    ob_start();
?>
    <div class="<?= implode(" ", $classes) ?>">
        <a rel="nofollow" href="<?= esc_attr($href) ?>" data-open="<?= esc_attr($href) ?>" data-pos="<?= esc_attr($pos) ?>"
            class="<?= esc_attr($link_class) ?>">
            <?php echo adminz_get_icon($icon) ?>
            <strong><?= esc_attr($filter_text) ?></strong>
        </a>
    </div>
<?php
    return do_shortcode(ob_get_clean());
};
$___->general_element();
