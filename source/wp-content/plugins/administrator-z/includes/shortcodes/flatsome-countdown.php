<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_countdown';
$___->shortcode_title = 'Countdown custom';
$___->shortcode_icon = 'text';
$___->options = [
    'id' => array(
        'type' => 'textfield',
        'heading' => 'ID',
        'default' => "adminz_" . wp_rand(),
    ),
    'text_days' => array(
        'type' => 'textfield',
        'heading' => 'Days',
        'default' => 'Days',
    ),
    'text_hours' => array(
        'type' => 'textfield',
        'heading' => 'Hours',
        'default' => 'Hours',
    ),
    'text_minutes' => array(
        'type' => 'textfield',
        'heading' => 'Minutes',
        'default' => 'Minutes',
    ),
    'text_seconds' => array(
        'type' => 'textfield',
        'heading' => 'Secconds',
        'default' => 'Secconds',
    ),
    'rowspacing' => array(
        'type' => 'select',
        'heading' => 'Row spacing',
        'default' => 'small',
        'options' => [
            'small' => 'small',
            'large' => 'large',
            'collapse' => 'collapse',
        ],
    ),
    'padding' => array(
        'type' => 'slider',
        'unit' => 'px',
        'min' => 0,
        'max' => 100,
        'heading' => 'Row padding',
        'default' => '15',
    ),
    'timeleft' => array(
        'heading' => "Time left",
        'type' => 'slider',
        'default' => 30,
        'unit' => "minutes",
        'min' => 1,
        'max' => 10080,
    ),
];
$___->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'id' => '',
        'padding' => '15',
        'rowspacing' => 'small',
        'text_days' => 'Days',
        'text_hours' => 'Hours',
        'text_minutes' => 'Minutes',
        'text_seconds' => 'Secconds',
        'timeleft' => 30,
    ), $atts));
    ob_start();
?>
    <div
        class="adminz_countdown ux-timer-wrapper row row-<?php echo esc_attr($rowspacing); ?>"
        data-timeleft="<?= esc_attr($timeleft) ?>"
        data-name="adminz_countdown_<?= esc_attr($id) ?>">
        <div class="col small-3 cd countdown-item text-center">
            <div class="col-inner" style="border: 1px solid #ccc;padding: <?php echo esc_attr($padding); ?>px 0px;">
                <h3 class="top countdown-day"> 00 </h3>
                <?php echo esc_attr($text_days); ?>
            </div>
        </div>
        <div class="col small-3 cd countdown-item text-center">
            <div class="col-inner" style="border: 1px solid #ccc;padding: <?php echo esc_attr($padding); ?>px 0px;">
                <h3 class="top countdown-hour"> 00 </h3>
                <?php echo esc_attr($text_hours); ?>
            </div>
        </div>
        <div class="col small-3 cd countdown-item text-center">
            <div class="col-inner" style="border: 1px solid #ccc;padding: <?php echo esc_attr($padding); ?>px 0px;">
                <h3 class="top countdown-minute"> 00 </h3>
                <?php echo esc_attr($text_minutes); ?>
            </div>
        </div>
        <div class="col small-3 cd countdown-item text-center">
            <div class="col-inner" style="border: 1px solid #ccc;padding: <?php echo esc_attr($padding); ?>px 0px;">
                <h3 class="top countdown-second"> 00 </h3>
                <?php echo esc_attr($text_seconds); ?>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
};
$___->general_element();
