<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_lightbox';
$___->shortcode_type = 'container';
$___->shortcode_title = 'Lightbox custom';
$___->shortcode_icon = 'text';
$___->options = [
    'auto_open' => array(
        'type' => 'select',
        'heading' => __('Auto open'),
        'default' => 'false',
        'options' => array(
            'false' => 'False',
            'true' => 'True',
        ),
    ),
    'auto_show' => array(
        'type' => 'select',
        'heading' => __('Auto show'),
        'default' => 'once',
        'options' => array(
            'once' => 'Once',
            'always' => 'Always',
        ),
    ),
    'auto_timer' => array(
        'type' => 'slider',
        'heading' => __('First open timer'),
        'default' => 0,
        'min' => 0,
        'step' => 500,
        'unit' => "ms",
        'max' => 10000,
    ),
    'id' => array(
        'type' => 'textfield',
        'heading' => __('Lightbox ID'),
        'default' => "lightbox_" . rand()
    ),
    'width' => array(
        'type' => 'scrubfield',
        'heading' => __('Width'),
        'default' => '650px',
    ),
    'padding' => array(
        'type' => 'scrubfield',
        'heading' => __('Padding'),
        'default' => '20px',
        'min' => '0px',
    ),
    'close_bottom_text' => array(
        'type' => 'textfield',
        'heading' => __('Close on bottom text'),
        'default' => '',
    ),
    'interval' => array(
        'type' => 'group',
        'heading' => __("Interval Open lighbox"),
        'options' => array(
            'reopen' => array(
                'type' => 'checkbox',
                'heading' => __('Interval Open'),
                'default' => 'false',
            ),
            'reopen_timer' => array(
                'type' => 'slider',
                'heading' => __('Reopen timer'),
                'default' => 10,
                'min' => 1,
                'step' => 1,
                'unit' => "second",
                'max' => 60,
            ),
        ),
    ),
    'advanced_options' => require(get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php')
];
$___->shortcode_callback = function ($atts, $content = null) {
    $atts = wp_parse_args($atts, array(
        'id' => rand(),
        'width' => '650px',
        'padding' => '20px',
        'auto_open' => false,
        'auto_timer' => '0',
        'auto_show' => '',
        'version' => '1',
        'close_bottom_text' => '',
        'reopen' => 'false',
        'reopen_timer' => 10,
        'class' => '',
        'visibility' => '',
    ));

    if ($atts['visibility'] == 'hidden') {
        return;
    }

    ob_start();
?>
    <div
        id="<?php echo esc_attr($atts['id']); ?>"
        class="adminz_lightbox lightbox-by-id lightbox-content mfp-hide lightbox-white <?php echo esc_attr($atts['class']); ?>"
        style="max-width:<?php echo esc_attr($atts['width']); ?>; padding:<?php echo esc_attr($atts['padding']); ?>"
        data-lightbox='<?php echo esc_attr(wp_json_encode($atts)); ?>'>
        <?php echo $content ? do_shortcode($content) : ''; ?>
        <?php
        if ($atts['close_bottom_text']) {
        ?>
            <div class="close_on_bottom text-shadow-2">
                <em class="flex" style="cursor: pointer;" onClick="jQuery.magnificPopup.close();">
                    <?php
                    echo "<span style='line-height: 28px;'>" . wp_kses_post($atts['close_bottom_text']) . "</span>";
                    ?>
                </em>
            </div>
        <?php
        }
        ?>
    </div>
<?php
    return ob_get_clean();
};
$___->general_element();
