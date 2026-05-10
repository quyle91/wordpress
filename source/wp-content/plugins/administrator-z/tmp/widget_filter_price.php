<?php


function d2c_widget_price() {
    $name                    = __FUNCTION__;
    $___                     = new \Adminz\Helper\FlatsomeELement;
    $___->shortcode_name     = $name;
    $___->shortcode_title    = ucfirst(str_replace('_', ' ', $name));
    $___->shortcode_icon     = 'text';
    $___->options            = [
        // 'post_parent' => [
        //     'type'    => 'textfield',
        //     'heading' => 'Parent Id',
        // ],
    ];
    $___->shortcode_callback = function ($atts, $content = null) use ($name) {
        extract(shortcode_atts(array(
            'class'      => '',
            'visibility' => '',
            // 'post_parent'      => get_the_ID(),
        ), $atts));

        $classes = [$name];
        if (!empty($class)) $classes[] = $class;
        if (!empty($visibility)) $classes[] = $visibility;


        ob_start();
        echo "<div class='" . implode(' ', $classes) . "'>";

        $prices = adminz_get_filtered_price(false);
        $min_price = ($prices->min_price ?? 0); // min_price
        $max_price = ($prices->max_price ?? 0); // max_price
        $step = get_option('d2c_interiors')['price_step'] ?? 100;

        // 
        $options = [];

        // Chỉ tạo options nếu có giá trị max_price > 0
        if ($max_price > 0) {
            // Bắt đầu từ min_price thay vì 0
            $start_from = $min_price + $step;

            // Tạo option đầu tiên "Less than [start_from]"
            $options[] = [
                'text' => "Less than $start_from",
                'min_price' => $min_price,
                'max_price' => $start_from
            ];

            // Bắt đầu từ start_from (min_price + step)
            for ($i = $start_from; $i < $max_price; $i += $step) {
                $next = $i + $step;
                $options[] = [
                    'text' => "$i - $next",
                    'min_price' => $i,
                    'max_price' => $next
                ];
            }

            // Tạo option cuối cùng "More than [last step]"
            $last_step = floor($max_price / $step) * $step;
            $options[] = [
                'text' => "More than $last_step",
                'min_price' => $last_step,
                'max_price' => null
            ];
        }
        // echo "<pre>"; print_r($min_price); echo "</pre>";
        // echo "<pre>"; print_r($max_price); echo "</pre>";
        // echo "<pre>"; print_r($options); echo "</pre>";
        // die;

        $page_shop_id = wc_get_page_id('shop');
        $link = get_permalink($page_shop_id);
        if (!empty($options)) {
            // Lấy giá trị từ URL
            $get_min = isset($_GET['min_price']) ? (int)$_GET['min_price'] : null;
            $get_max = isset($_GET['max_price']) ? (int)$_GET['max_price'] : null;

            $selected_html = '';
            $selected_html .= '<select>';
            $selected_html .= '<option value="">Price</option>';
            foreach ($options as $option) {
                $min_attr = isset($option['min_price']) ? 'min_price="' . $option['min_price'] . '"' : '';
                $max_attr = isset($option['max_price']) ? 'max_price="' . $option['max_price'] . '"' : '';

                // Kiểm tra option có được chọn không
                $selected = '';
                if ($get_min == $option['min_price'] && $get_max == $option['max_price']) {
                    $selected = 'selected';
                }

                // Chuẩn bị các thuộc tính
                $attrs = [
                    $min_attr,
                    $max_attr,
                    $selected
                ];

                // Lọc bỏ các thuộc tính rỗng
                $attrs = array_filter($attrs);

                // Ghép các thuộc tính thành chuỗi
                $attr_string = implode(' ', $attrs);

                // Tạo thẻ option
                $selected_html .= '<option ' . $attr_string . '>' . $option['text'] . '</option>';
            }
            $selected_html .= '</select>';
            $hidden_fields = adminz_wc_query_string_form_fields(null, ['paged'], '', true);

            echo <<<HTML
                <div class="adminz_widget_taxonomies d2c_prices">
                    <form action="$link" method="get">
                        $selected_html
                        $hidden_fields
                    </form>
                </div>
                HTML;
        }


        echo '</div>';
        return do_shortcode(ob_get_clean());
    };
    // echo "<pre>"; print_r($___); echo "</pre>"; die;
    $___->general_element();
}


// $('.d2c_prices select').off('change.d2c_prices_search').on('change.d2c_prices_search', function (e) {
//             e.preventDefault();
//             var $select = $(this);
//             var $form = $select.closest('form');
//             var selectedOption = $select.find('option:selected');

//             // Lấy giá trị từ thuộc tính
//             var minPriceVal = selectedOption.attr('min_price') || '';
//             var maxPriceVal = selectedOption.attr('max_price') || '';

//             // Xóa các input cũ nếu tồn tại
//             $form.find('input[name="min_price"]').remove();
//             $form.find('input[name="max_price"]').remove();

//             // Cập nhật giá trị cho input
//             if(minPriceVal){
//                 $form.append($('<input>', {
//                     type: 'hidden',
//                     name: 'min_price',
//                     value: minPriceVal
//                 }));
//             }

//             if(maxPriceVal){
//                 $form.append($('<input>', {
//                     type: 'hidden',
//                     name: 'max_price',
//                     value: maxPriceVal
//                 }));
//             }

//             return;
//         });