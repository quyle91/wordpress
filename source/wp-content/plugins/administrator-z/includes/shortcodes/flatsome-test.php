<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_test';
$___->shortcode_title = 'Adminz test';
$___->shortcode_icon = 'text';
$___->options = [
    'content' => array(
        'type' => 'textfield',
        'heading' => 'Text',
    ),
];
$___->shortcode_callback = null;
$___->general_element();
