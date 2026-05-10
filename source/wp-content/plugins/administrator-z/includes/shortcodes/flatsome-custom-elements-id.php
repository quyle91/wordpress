<?php

// ------------------- add adminz group for all uxbuilder elements
add_filter('ux_builder_shortcode_data', function ($data, $tag) {
    $allowed = ['section', 'row', 'col', 'banner', 'ux_html'];
    if (in_array($tag, $allowed)) {
        $group = [
            'adminz' => [
                'type' => 'group',
                'heading' => 'Administrator Z',
                'options' => [
                    '_id' => [
                        'type' => 'textfield',
                        'heading' => 'Fixed ID',
                        'placeholder' => 'Enter ID...',
                    ],
                ],
            ],
        ];
        $data['options'] = array_merge($data['options'], $group);
    }
    return $data;
}, 10, 2);


// fix id attribute for ux_html
add_filter('do_shortcode_tag', function ($output, $tag, $attr, $m) {
    if ($tag === 'ux_html' && !empty($attr['_id'])) {
        $id = 'id="' . esc_attr($attr['_id']) . '"';

        // Chèn ID vào thẻ HTML đầu tiên
        $output = preg_replace('/(<[a-zA-Z0-9]+)(\s|>)/', '$1 ' . $id . '$2', $output, 1);
    }
    return $output;
}, 10, 4);
