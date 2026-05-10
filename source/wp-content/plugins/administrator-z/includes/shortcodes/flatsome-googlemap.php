<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_googlemap';
$___->shortcode_title = 'Google map iframe';
$___->shortcode_icon = 'text';
$___->options = [
    'address' => array(
        'type' => 'textfield',
        'heading' => 'Address or latlong',
        'default' => '21.028232792016798, 105.83566338846242',
    ),
    'height' => array(
        'type' => 'textfield',
        'heading' => 'Height',
        'default' => '300px',
    ),
    'hl' => array(
        'type' => 'textfield',
        'heading' => 'Language',
        'default' => 'vn',
    ),
    'zoom' => array(
        'type' => 'textfield',
        'heading' => 'Zoom',
        'default' => '14',
    ),
];
$___->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'address' => '21.028232792016798, 105.83566338846242',
        'height' => '300px',
        'hl' => "vn",
        'zoom' => "14"
    ), $atts));

    ob_start();
    $src = "https://maps.google.com/maps?q=$address&hl=$hl&z=$zoom&output=embed";
?>
    <iframe
        style="
margin-bottom: -7px;
border: none; 
height: <?php echo esc_attr($height) ?>; 
width: 100%"
        src="<?= esc_attr($src) ?>">
    </iframe>
<?php
    return ob_get_clean();
};
$___->general_element();
