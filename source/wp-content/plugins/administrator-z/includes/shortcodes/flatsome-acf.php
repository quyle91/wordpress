<?php
if (!function_exists('acf_shortcode')) {
    return;
}

global $adminz;
if (!($adminz['Acf']->settings['enable_shortcode'] ?? '') == 'on') {
    return;
}

// [acf field="_phone_number" post_id="option"]

$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_acf';
$___->shortcode_title = 'Adminz ACF';
$___->shortcode_icon = 'text';
$___->options = [
    'field' => array(
        'type' => 'textfield',
        'heading' => 'Field',
    ),
    'post_id' => array(
        'type' => 'textfield',
        'heading' => 'Post id',
        'description' => 'Read acf document for more settings',
    ),
    'format_value' => array(
        'type' => 'textfield',
        'heading' => 'Format Value',
    ),
];
$___->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'class' => '',
        'visibility' => '',
        'field' => '',
        'post_id' => '',
        'format_value' => "false",
    ), $atts));

    $classes = array('adminz_acf', $field);
    if ($class) $classes[] = $class;
    if ($visibility) $classes[] = $visibility;

    ob_start();
    echo '<div class="' . implode(' ', $classes) . '">';
    echo "[acf field=\"$field\" post_id=\"$post_id\" format_value=\"$format_value\"]";
    echo '</div>';
    return do_shortcode(ob_get_clean());
};
$___->general_element();
