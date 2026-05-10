<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_icon';
$___->shortcode_title = 'Icon';
$___->shortcode_icon = 'text';
$___->options = [
    'icon' => array(
        'type' => 'select',
        'heading' => 'Select Icon',
        'default' => '',
        'options' => adminz_get_list_icons(),
    ),
    'image' => array(
        'type' => 'image',
        'heading' => 'Or Upload SVG',
        'default' => '',
        'conditions' => 'icon == ""',
    ),
    'color' => array(
        'type' => 'colorpicker',
        'heading' => __('Icon Color'),
        'alpha' => true,
        'format' => 'hex',
    ),
    'max_width' => array(
        'type' => 'scrubfield',
        'heading' => __('Width'),
        'default' => '100%',
        'responsive' => true,
        'min' => 0,
        'max' => 100,
        'description' => 'Type unit: px or %',
    ),
    'font_size' => array(
        'type' => 'scrubfield',
        'heading' => __('Font size'),
    ),
    'link' => array(
        'type' => 'textfield',
        'heading' => __('Link'),
    ),
    'text_before' => array(
        'type' => 'textfield',
        'heading' => __('Text before'),
    ),
    'text_after' => array(
        'type' => 'textfield',
        'heading' => __('Text after'),
    ),
    'line_break' => array(
        'type' => 'checkbox',
        'heading' => __('Line break'),
    ),
    'class' => array(
        'type' => 'textfield',
        'heading' => __('SVG Class'),
    ),
];
$___->shortcode_callback = function ($atts) {
    extract(shortcode_atts(array(
        '_id' => 'adminz_svg' . rand(),
        'icon' => '',
        'image' => '',
        'color' => '',
        'link' => '',
        'class' => '',
        'max_width' => '',
        'font_size' => '1em',
        'text_before' => '',
        'text_after' => '',
        'line_break' => '',
        'width' => "100%",
        'height' => '',
        'image_size' => 'full',
        'style' => [],
    ), $atts));
    ob_start();
    $attr = [];
    $attr['style'] = $style;

    if ($color) {
        if (!str_starts_with($icon, 'dashicons')) {
            $attr['style']['fill'] = $color;
        } else {
            $attr['style']['color'] = $color;
        }
    }
    if ($class) {
        $attr['class'] = $class;
    }
    if ($_id) {
        $attr['id'] = $_id;
    }

    if ($width) {
        $attr['width'] = $width;
        if (str_starts_with($icon, 'dashicons')) {
            $attr['style']['width'] = 'unset';
            $attr['style']['height'] = 'unset';
            $attr['style']['vertical-align'] = 'bottom';
        }
    }

    if ($height) {
        $attr['height'] = $height;
    }

    if ($font_size) {
        $attr['style']['font-size'] = $font_size;
    }

    if ($image) {
        $a = get_post($image);
        if (isset($a->post_mime_type) and $a->post_mime_type == 'image/svg+xml') {
            $icon = get_attached_file($image);
            $icon_html = adminz_get_icon($icon, $attr);
        } else {
            ob_start();
            echo wp_get_attachment_image($image, $image_size, false, $attr);
            $icon_html = ob_get_clean();
        }
    } else {
        $icon_html = adminz_get_icon($icon, $attr);
    }

    $before = "";
    $after = "";
    if ($link) {
        $before = "<a href='" . $link . "'>";
        $after = "</a>";
    }
    if ($line_break) echo '<p>';
    echo esc_attr($before);
    echo $text_before ? "<span>" . esc_attr($text_before) . " </span>" : "";

    echo apply_filters('the_title', $icon_html);
    $unit = 'px';
    if (isset($atts['max_width']) and strpos($atts['max_width'], "%")) {
        $unit = "%";
    }
    if (isset($atts['max_width']) and strpos($atts['max_width'], "em")) {
        $unit = "em";
    }
    if (isset($atts['max_width']) and strpos($atts['max_width'], "rem")) {
        $unit = "rem";
    }
    $args = array(
        'max_width' => array(
            'selector' => '',
            'property' => 'max-width',
            'unit' => $unit,
        ),
    );
    echo ux_builder_element_style_tag($_id, $args, $atts);
    echo $text_after ? "<span> " . esc_attr($text_after) . "</span>" : "";
    echo esc_attr($after);
    if ($line_break) echo '</p>';
    return ob_get_clean();
};
$___->general_element();
