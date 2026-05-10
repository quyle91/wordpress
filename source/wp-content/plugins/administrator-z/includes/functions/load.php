<?php

$GLOBALS['adminz'] = [
    'Admin' => \Adminz\Controller\Admin::get_instance(),
    'AdministratorZ' => \Adminz\Controller\AdministratorZ::get_instance(),
    'Wordpress' => \Adminz\Controller\Wordpress::get_instance(),
    'MetaBuilder' => \Adminz\Controller\MetaBuilder::get_instance(),
    'Tools' => \Adminz\Controller\Tools::get_instance(),
    'Enqueue' => \Adminz\Controller\Enqueue::get_instance(),
    'QuickContact' => \Adminz\Controller\QuickContact::get_instance(),
    'Mailer' => \Adminz\Controller\Mailer::get_instance(),
    'Icons' => \Adminz\Controller\Icons::get_instance(),
    'Api' => \Adminz\Controller\Api::get_instance(),
    'Test' => \Adminz\Controller\Test::get_instance(),
];

add_action('after_setup_theme', function () {
    global $adminz;
    if (!isset($adminz['Flatsome']) && in_array('Flatsome', [wp_get_theme()->name, wp_get_theme()->parent_theme])) {
        $adminz['Flatsome'] = \Adminz\Controller\Flatsome::get_instance();
    }
});

// integration
add_action('plugins_loaded', function () {
    global $adminz;

    if (!isset($adminz['Acf']) && class_exists('ACF')) {
        $adminz['Acf'] = \Adminz\Controller\Acf::get_instance();
    }

    if (!isset($adminz['Woocommerce']) && class_exists('WooCommerce')) {
        $adminz['Woocommerce'] = \Adminz\Controller\Woocommerce::get_instance();
    }

    if (!isset($adminz['Wpcf7']) && class_exists('WPCF7')) {
        $adminz['Wpcf7'] = \Adminz\Controller\Wpcf7::get_instance();
    }

    if (!isset($adminz['Elementor']) && class_exists('\Elementor\Plugin')) {
        $adminz['Elementor'] = \Adminz\Controller\Elementor::get_instance();
    }
});

add_filter('body_class', function ($classes) {
    $classes[] = 'administrator-z';
    return $classes;
}, 10, 1);

add_action('plugins_loaded', function () {
    load_plugin_textdomain(
        'administrator-z',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages/'
    );
});
