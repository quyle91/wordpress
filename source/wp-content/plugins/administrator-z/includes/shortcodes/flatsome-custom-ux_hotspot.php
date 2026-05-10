<?php

if ((get_option('adminz_flatsome')['ux_hotspot_product_box_small'] ?? '') != 'on') {
    return;
}

add_filter('do_shortcode_tag', function ($output, $tag, $attr, $m) {
    if ($tag === 'ux_hotspot') {
        if (isset($attr['prod_id']) && $attr['prod_id']) {
            ob_start();
            ux_hotspot($attr, null);
            $output = ob_get_clean();

            // class
            $output = str_replace(
                'class="hotspot tooltip',
                'class="hotspot tooltip-as-html',
                $output
            );

            // Lấy title từ thẻ <a> bằng regex
            preg_match('/title="([^"]*)"/', $output, $matches);
            $title = $matches[1] ?? '';
            $product = wc_get_product($attr['prod_id']);
            $price = $product->get_price_html($attr['prod_id']);
            $link = $product->get_permalink($attr['prod_id']);
            $image_html = wp_get_attachment_image(
                $product->get_image_id(),
                'thumbnail',
                '',
                [
                    'class' => 'adminz-hotspot-custom-image',
                    'style' => 'width: 75px; height: 75px;'
                ]
            );

            // Tạo HTML mới
            $custom_html = <<<HTML
            <a href="$link" class="adminz_hotspot_custom_content dark">
                <div class="ximg">$image_html</div>
                <div class="xcontent">
                    <p class="xtitle mb-half">
                        <strong>$title</strong>
                    </p>
                    <p class="xprice mb-half">
                        $price
                    </p>
                </div>
            </a>
            HTML;
            $custom_html = apply_filters('adminz_custom_ux_hotspot_product',$custom_html, $attr['prod_id']);

            $output = preg_replace(
                '/title="([^"]*)"/',
                'title="' . esc_attr($custom_html) . '"', // Chỉ update nội dung bên trong title
                $output
            );
        }
    }
    return $output;
}, 10, 4);
