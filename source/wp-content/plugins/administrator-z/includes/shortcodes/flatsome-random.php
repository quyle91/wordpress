<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_random';
$___->shortcode_title = 'Random number';
$___->shortcode_icon = 'text';
$___->shortcode_inline = 'true';
$___->options = [
    'textbefore' => array(
        'type' => 'textfield',
        'heading' => __('Text before number'),
        'default' => '',
    ),
    'min' => array(
        'type' => 'scrubfield',
        'heading' => 'Start number',
        'unit' => '',
        'default' => 0,
    ),
    'max' => array(
        'type' => 'scrubfield',
        'heading' => 'End number',
        'unit' => '',
        'default' => 99,
        'max' => mt_getrandmax(),
    ),
    'textafter' => array(
        'type' => 'textfield',
        'heading' => __('Text after number'),
        'default' => '',
    ),
    'use_global' => array(
        'type' => 'checkbox',
        'heading' => 'Use Global',
    ),
    'use_inline' => array(
        'type' => 'checkbox',
        'heading' => 'Inline Element',
        'default' => 'true',
    )
];
$___->shortcode_callback = function ($atts) {
    extract(shortcode_atts(array(
        'min' => 1,
        'max' => 99,
        'textafter' => "",
        'textbefore' => "",
        'use_global' => false,
        'use_inline' => true,
    ), $atts));

    $return = mt_rand(intval($min), intval($max));

    if ($use_global) {
        if (!isset($GLOBALS['administrator-z']['random'])) {
            $GLOBALS['administrator-z']['random'] = $return;
        }
        $return = $GLOBALS['administrator-z']['random'];
    }

    $use_inline = $use_inline ? "span" : "div";
    return sprintf('<%1$s>%2$s %3$s %4$s</%1$s>', $use_inline, $textbefore, $return, $textafter);
};
$___->general_element();
