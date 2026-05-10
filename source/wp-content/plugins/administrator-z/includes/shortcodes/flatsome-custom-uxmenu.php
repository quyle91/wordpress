<?php
// sửa element
add_filter('ux_builder_shortcode_data', function ($data, $tag) {
    if ($tag == 'ux_menu_link') {
        // echo "<pre>"; print_r($data); echo "</pre>"; die;
        // dành cho dashicons
        foreach (adminz_get_list_icons() as $key => $value) {
            if (str_starts_with($key, 'dashicons')) {
                $data['options']['icon']['options']["dashicons $key"] = $value;
            } else {
                $data['options']['icon']['options'][$key] = $value;
            }
        }
        // Kiểm tra key của $data['options']['icon']
        $options_keys = array_keys($data['options']);
        $icon_index = array_search('icon', $options_keys);

        if ($icon_index !== false) {
            // Slice mảng để chèn adminz_image ngay sau icon
            $before = array_slice($data['options'], 0, $icon_index + 1, true);
            $after = array_slice($data['options'], $icon_index + 1, null, true);

            // Chèn adminz_image vào giữa
            $data['options'] = $before + ['adminz_image' => [
                'type' => 'image',
                'heading' => 'Adminz Icon image',
                'default' => '',
                'conditions' => 'icon == ""',
            ]] + $after;
        }
    }
    return $data;
}, 10, 2);


add_filter('do_shortcode_tag', function ($output, $tag, $attr, $m) {
    if ($tag == 'ux_menu_link') {
        // icon
        $output = adminz_maybe_output_replace_icon($output, $attr);
        // image
        $output = adminz_maybe_output_insert_image($output, $attr, 'a');
    }
    return $output;
}, 10, 4);
