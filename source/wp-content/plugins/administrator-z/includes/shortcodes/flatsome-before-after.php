<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_before_after';
$___->shortcode_title = 'Adminz before after';
$___->shortcode_icon = 'text';
$___->options = [
    'img_1' => [
        'type' => 'image',
        'heading' => 'Field 1',
        'default' => '',
    ],
    'img_2' => [
        'type' => 'image',
        'heading' => 'Field 2',
        'default' => '',
    ],
    'default_offset_pct' => [
        'type' => 'textfield',
        'heading' => 'Default offset percentage',
        'default'=> 0.5,
        'placeholder' => '0.5',
    ],
    'orientation' => [
        'type' => 'select',
        'heading' => 'Orientation',
        'default' => 'horizontal',
        'options' => [
            'horizontal' => 'Horizontal',
            'vertical' => 'Vertical',
        ],
    ],
    'before_label' => [
        'type' => 'textfield',
        'heading' => 'Before label',
        'default'=> 'Before',
        'placeholder' => 'Before',
    ],
    'after_label' => [
        'type' => 'textfield',
        'heading' => 'After label',
        'default'=> 'After',
        'placeholder' => 'After',
    ],
    'no_overlay' => [
        'type' => 'checkbox',
        'heading' => 'No overlay',
        'default'=> false,
        'options' => [
            '1' => 'Yes',
        ],
    ],
    'move_slider_on_hover' => [
        'type' => 'checkbox',
        'heading' => 'Move slider on hover',
        'default'=> false,
        'options' => [
            '1' => 'Yes',
        ],
    ],
    'move_with_handle_only' => [
        'type' => 'checkbox',
        'heading' => 'Move with handle only',
        'default'=> false,
        'options' => [
            '1' => 'Yes',
        ],
    ],
    'click_to_move' => [
        'type' => 'checkbox',
        'heading' => 'Click to move',
        'default'=> false,
        'options' => [
            '1' => 'Yes',
        ],
    ],
];
$___->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'class' => '',
        'visibility' => '',
        'img_1' => '',
        'img_2' => '',
        'default_offset_pct' => 0.5, // Tỷ lệ hiển thị ảnh "before" khi trang vừa load (0 → 1)
        'orientation' => 'horizontal', // Hướng so sánh ảnh: 'horizontal' (ngang) hoặc 'vertical' (dọc)
        'before_label' => 'Before', // Nhãn hiển thị cho ảnh before
        'after_label' => 'After', // Nhãn hiển thị cho ảnh after
        'no_overlay' => false, // Không hiển thị lớp overlay chứa nhãn before / after
        'move_slider_on_hover' => false, // Di chuyển thanh slider khi rê chuột lên ảnh
        'move_with_handle_only' => false, // Chỉ cho phép kéo bằng tay cầm slider (không kéo trực tiếp trên ảnh)
        'click_to_move' => false // Cho phép click (hoặc tap) vào vị trí bất kỳ để di chuyển slider
    ), $atts));

    $classes = array('adminz_before_after');
    if ($class) $classes[] = $class;
    if ($visibility) $classes[] = $visibility;

    // enqueue jquery.event.move.js
    wp_enqueue_script(
        'jquery.event.move.js',
        ADMINZ_DIR_URL . '/assets/twentytwenty/js/jquery.event.move.js',
        array('jquery'),
        ADMINZ_VERSION,
        true
    );

    // enqueue jquery.twentytwenty.js
    wp_enqueue_script(
        'jquery.twentytwenty.js',
        ADMINZ_DIR_URL . '/assets/twentytwenty/js/jquery.twentytwenty.js',
        array('jquery'),
        ADMINZ_VERSION,
        true
    );

    // enqueue twentytwenty.css    
    wp_enqueue_style(
        'twentytwenty.css',
        ADMINZ_DIR_URL . '/assets/twentytwenty/css/twentytwenty.css',
        array(),
        ADMINZ_VERSION
    );


    ob_start();
    echo '<div class="' . implode(' ', $classes) . '">';

    // code here
    $data_twentytwenty = array(
        'default_offset_pct' => $default_offset_pct,
        'orientation' => $orientation,
        'before_label' => $before_label,
        'after_label' => $after_label,
        'no_overlay' => $no_overlay,
        'move_slider_on_hover' => $move_slider_on_hover,
        'move_with_handle_only' => $move_with_handle_only,
        'click_to_move' => $click_to_move,
    );
    // echo '<pre>'; print_r($data_twentytwenty); echo '</pre>';

    echo '<div class="adminz_twentytwenty_container" data-atts=\'' . json_encode($data_twentytwenty) . '\'>';
    $img_1_src = wp_get_attachment_image_src($img_1, 'full')[0] ?? '';
    $img_2_src = wp_get_attachment_image_src($img_2, 'full')[0] ?? '';
    echo '<img alt="x" src="' . $img_1_src . '" alt="" class="adminz-before">';

    // chỉ show ở font
    if (!adminz_flatsome_is_ux_builder()) {
        echo '<img alt="x" src="' . $img_2_src . '" alt="" class="adminz-after">';
    }
    echo '</div>';

    echo '</div>'; // end wrapper

    return do_shortcode(ob_get_clean());
};
$___->general_element();
