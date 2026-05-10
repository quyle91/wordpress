<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_countviews';
$___->shortcode_title = 'Page view counter';
$___->shortcode_icon = 'text';
$___->options = [
    'icon' => array(
        'type' => 'select',
        'heading' => 'Use icon',
        'default' => 'eye',
        'options' => adminz_get_list_icons(),
    ),
    'textbefore' => array(
        'type' => 'textfield',
        'heading' => __('Text before'),
        'default' => '',
    ),
    'textafter' => array(
        'type' => 'textfield',
        'heading' => __('Text after'),
        'default' => '',
    ),
    'class' => array(
        'type' => 'textfield',
        'heading' => __('Class'),
        'default' => '',
    ),
];
$___->shortcode_callback = null; // shortcode is registered
$___->general_element();
