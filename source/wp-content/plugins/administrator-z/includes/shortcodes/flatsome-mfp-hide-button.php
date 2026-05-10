<?php
// là 1 phần của setup magnific popup
// Xem phần mô tả của option: href

$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_mfp_hide_button';
$___->shortcode_title = 'Mfp Hide button';
$___->shortcode_icon = 'text';
$___->options = array_merge(
    [
        'text' => array(
            'type' => 'textfield',
            'heading' => 'text',
            'default' => 'close',
        ),
        'icon' => array(
            'type' => 'select',
            'heading' => 'icon',
            'default' => 'close',
            'options' => adminz_get_list_icons(),
        ),
        'icon_pos' => array(
            'type' => 'select',
            'heading' => 'icon_pos',
            'default' => 'left',
            'options' => [
                'left' => 'left',
                'right' => 'right',
            ],
        ),
    ],
    [
        'advanced_options' => require(get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php')
    ]
);
$___->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'text' => 'close',
        'icon' => 'close',
        'icon_pos' => "left",
        'class' => '',
        'visibility' => '',
    ), $atts));

    if ($visibility == 'hiden') return;

    $classes = ['adminz_mfp_button', 'adminz_mfp_hide_button', $class, $visibility];

    ob_start();
?>
    <div class="<?= implode(" ", $classes) ?>">
        <a rel="nofollow" href="javascript:void(0);" onclick="jQuery.magnificPopup.close();">
            <?php
            if ($icon_pos == 'left') {
                echo adminz_get_icon($icon);
            }
            ?>
            <strong><?= esc_attr($text) ?></strong>
            <?php
            if ($icon_pos == 'right') {
                echo adminz_get_icon($icon);
            }
            ?>
        </a>
    </div>
<?php
    return do_shortcode(ob_get_clean());
};
$___->general_element();
