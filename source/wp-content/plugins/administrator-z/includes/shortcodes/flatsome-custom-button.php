<?php
// ----------------- buttons icon
add_filter('ux_builder_shortcode_data', function ($data, $tag) {
    if ($tag == 'button') {

        // custom icons
        foreach (adminz_get_list_icons() as $key => $value) {
            // add "dashicons " before icon class
            if (str_starts_with($key, 'dashicons')) {
                $data['options']['icon_options']['options']['icon']['options']["dashicons $key"] = $value;
            } else {
                $data['options']['icon_options']['options']['icon']['options'][$key] = $value;
            }
        }

        // sub text
        $sub_text = [
            'type' => 'textfield',
            'holder' => 'button',
            'heading' => 'Adminz sub text',
            'param_name' => 'text',
            'value' => 'Sub text',
            'default' => '',
        ];

        //
        $data['options'] =
            array_slice($data['options'], 0, 1, true) +
            ['subtext' => $sub_text] +
            array_slice($data['options'], 1, null, true);
    }
    return $data;
}, 10, 2);

add_filter('do_shortcode_tag', function ($output, $tag, $attr, $m) {
    if ($tag === 'button') {

        // icon
        $output = adminz_maybe_output_replace_icon($output, $attr);

        // subtext
        if ($attr['subtext'] ?? '') {
            $output = str_replace(
                '</a>',
                "<div><small>" . wp_kses_post($attr['subtext']) . "</small></div>" . "</a>",
                $output
            );
            $output = str_replace(
                'class="',
                'class="button_has_sub_text ',
                $output
            );
        }
    }
    return $output;
}, 10, 4);
