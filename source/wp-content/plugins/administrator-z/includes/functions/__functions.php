<?php
// nếu là mảng thì trả lại chính nó
// nêu là json và convert ok thì tra lại mảng, 
function adminz_maybeJson($json) {
    if (is_array($json)) {
        return $json;
    }
    $decoded = json_decode($json, true); // decode as an associative array
    if (json_last_error() == JSON_ERROR_NONE && is_array($decoded)) {
        return $decoded; // return the array if decode is successful and result is an array
    }
    return false; // return false otherwise
}

function adminz_preview_text($text = "Please preview in front-end") {
    return do_shortcode('[adminz_test content="' . $text . '"]');
}

function adminz_test($atts, $content = null) {

    if (is_string($atts)) {
        return '<div style="background: #71cedf; border: 2px dashed #000; display: flex; color: white; justify-content: center; align-items: center; "> ' . $atts . '</div>';
    }

    extract(shortcode_atts(array(
        'content' => 'Test',
        'class' => '',
        'visibility' => '',
    ), $atts));

    $classes = ['adminz_test'];

    if ($class) {
        $classes[] = $class;
    }
    if ($visibility) {
        $classes[] = $visibility;
    }

    $classes = implode(' ', $classes);
    $style = 'background: #71cedf; border: 2px dashed #000; display: flex; color: white; justify-content: center; align-items: center;';
    return <<<HTML
	<div class="$classes" style="$style">$content </div>
	HTML;
}

function adminz_get_settings($key = false, $property = 'settings') {
    global $adminz;
    if ($key) {
        // return false if not isset
        return $adminz[$key]->$property ?? false;
    }
    return $adminz;
}

function adminz_is_flatsome() {
    return (adminz_get_settings()['Flatsome'] ?? false) ? true : false;
}

// get or save data from $adminz_tmp
function adminz_tmp($name, $value = false) {
    global $adminz;
    if (!$value or empty($value)) {
        return $adminz['TMP'][$name] ?? $value;
    }
    $adminz['TMP'][$name] = $value;
    return $value;
}

function adminz_add_body_class($_class) {
    if ($_class) {
        add_filter('body_class', function ($class) use ($_class) {
            return array_merge($class, (array) $_class);
        });
    }
}

function adminz_add_admin_body_class($_class) {
    add_filter('admin_body_class', function ($class) use ($_class) {
        $class .= " $_class";
        return $class;
    });
}

function adminz_is_flatsome_block($shortcode) {
    return (adminz_is_flatsome() and str_starts_with($shortcode, '[block'));
}

// function adminz_fix_override_post_global($shortcode) {
//     // if shortcode = block in flatsome
//     // see flatsome\wp-content\themes\flatsome\inc\post-types\post-type-ux-blocks.php
//     // function block_shortcode, overridding global $post

//     if (adminz_is_flatsome() and str_starts_with($shortcode, '[block')) {
//         global $post;
//         adminz_tmp('adminz_post_global', $post);
//     }
// }

// function adminz_get_override_post_global($name) {
//     global $post;
//     $post = adminz_tmp($name);
// }

function adminz_get_object_id() {
    if (is_singular()) {
        return [
            'object_type' => 'get_post_meta',
            'object_id' => get_the_ID(),
        ];
    }
    if (is_category() || is_tag() || is_tax()) {
        return [
            'object_type' => 'get_term_meta',
            'object_id' => get_queried_object_id(),
        ];
    }
    if (is_home()) {
        return [
            'object_type' => 'get_post_meta',
            'object_id' => get_option('page_for_posts'),
        ];
    }
    if (is_front_page()) {
        return [
            'object_type' => 'get_post_meta',
            'object_id' => get_option('page_on_front'),
        ];
    }
    if (adminz_is_woocommerce()) {
        if (is_shop()) {
            return [
                'object_type' => 'get_post_meta',
                'object_id' => wc_get_page_id('shop'),
            ];
        }
        if (is_cart()) {
            return [
                'object_type' => 'get_post_meta',
                'object_id' => wc_get_page_id('cart'),
            ];
        }
        if (is_checkout()) {
            return [
                'object_type' => 'get_post_meta',
                'object_id' => wc_get_page_id('checkout'),
            ];
        }
        if (is_account_page()) {
            return [
                'object_type' => 'get_post_meta',
                'object_id' => wc_get_page_id('myaccount'),
            ];
        }
        if (is_product()) {
            global $post;
            return [
                'object_type' => 'get_post_meta',
                'object_id' => $post->ID,
            ];
        }
        if (is_product_category()) {
            $term = get_queried_object();
            return [
                'object_type' => 'get_term_meta',
                'object_id' => $term->term_id,
            ];
        }
        if (is_product_tag()) {
            $term = get_queried_object();
            return [
                'object_type' => 'get_term_meta',
                'object_id' => $term->term_id,
            ];
        }
    }

    return false;
}

function adminz_maybe_output_insert_image($output, $attr, $tag) {
    if ($attr['adminz_image'] ?? '') {
        $image_html = wp_get_attachment_image(
            $attr['adminz_image'],
            'thumbnail',
            false,
            ['style' => 'width: 1em; height: auto; display: inline-block; vertical-align: middle;']
        );

        // Sử dụng regex để chèn hình ảnh vào thẻ <a>
        if ($image_html) {
            // Tìm thẻ <a> và chèn hình ảnh vào sau mở thẻ <a>
            $output = preg_replace(
                '/(<a\s[^>]*>)/',
                '$1' . $image_html,
                $output
            );
        }
    }
    return $output;
}

function adminz_maybe_output_replace_icon($output, $attr) {
    if ($attr['icon'] ?? '') {
        global $adminz;

        // remove "dashicons " 
        $icon_code = str_replace('dashicons ', '', $attr['icon']);
        if (
            array_key_exists($icon_code, $adminz['Icons']->icons) ||
            array_key_exists($icon_code, $adminz['Icons']->custom_icons) ||
            array_key_exists($icon_code, $adminz['Icons']->dashicons)
        ) {
            $html_icon = adminz_get_icon($icon_code);
            $output = preg_replace('/<i[^>]*>(.*?)<\/i>/', $html_icon, $output);
        }
    }
    return $output;
}

function adminz_add_post_term($post_id, $term_id, $taxonomy = '') {
    if (!$post_id or !$term_id) {
        return;
    }

    $term = get_term($term_id);

    // Nếu post không tồn tại, thoát.
    if (!get_post($post_id)) {
        return;
    }

    // Nếu term không tồn tại hoặc có lỗi
    if (is_wp_error($term) || !$term) {
        return;
    }

    // đảm bảo có taxonomy
    if (!$taxonomy) {
        $taxonomy = $term->taxonomy ?? '';
    }

    // taxonomy tồn tại
    if (!taxonomy_exists($taxonomy)) {
        return;
    }

    // kiểm tra term có dành cho post_type hay ko, tránh việc tạo ra record thừa 
    $post_taxonomies = get_object_taxonomies(get_post_type($post_id));
    if (!in_array($taxonomy, $post_taxonomies)) {
        return;
    }

    // Kiểm tra nếu sản phẩm đã có term thì không cần thêm nữa
    if (has_term($term_id, $taxonomy, $post_id)) {
        return;
    }

    // Lấy danh sách các term hiện tại của thuộc tính
    $current_terms = wp_get_object_terms(
        $post_id,
        $taxonomy,
        array('fields' => 'ids')
    );

    // bắt buộc phải là một array trước khi sang step sau
    if (!is_array($current_terms)) {
        $current_terms = [];
    }

    // Thêm term mới vào danh sách vị trí cuối cùng
    $term_id = (int) $term_id; // đảm bảo là init để wordpress ko hiểu nhầm là slug
    $current_terms = array_unique(array_merge($current_terms, [$term_id]));

    // Gán danh sách term cho sản phẩm
    wp_set_object_terms($post_id, $current_terms, $taxonomy, false);

    if (get_post_type($post_id) == 'product') {
        if (str_starts_with($taxonomy, 'pa_')) {

            // Kiểm tra xem sản phẩm có tồn tại không
            $product = wc_get_product($post_id);
            if (!$product) {
                return;
            }
            // Lấy các thuộc tính hiện có của sản phẩm
            $attributes = $product->get_attributes();

            // Tạo đối tượng WC_Product_Attribute nếu thuộc tính chưa tồn tại
            if (!isset($attributes[$taxonomy])) {
                $name = $taxonomy;
                $attr_id = wc_attribute_taxonomy_id_by_name($name);

                if (!$attr_id) {
                    return;
                }

                $attribute = new \WC_Product_Attribute();
                $attribute->set_id($attr_id);
                $attribute->set_name($name);
                $attribute->set_options(array($term_id)); // Đảm bảo truyền vào mảng
                $attribute->set_position(0);
                $attribute->set_visible(true);
                $attribute->set_variation(true);

                $attributes[$taxonomy] = $attribute;
            } else {
                // Cập nhật options (term) cho thuộc tính hiện có
                $attributes[$taxonomy]->set_options($current_terms);
            }

            // Cập nhật thuộc tính cho sản phẩm
            $product->set_attributes($attributes);
            $product->save();
            // Xóa cache WooCommerce để cập nhật chính xác
            wc_delete_product_transients($post_id);
        }
    }
}

function adminz_get_related_post_ids($post_id = null, $limit = null) {
    // Lấy post ID hiện tại nếu không truyền vào
    if (!$post_id) {
        $post_id = get_the_ID();
    }

    // Xác định post_type từ post_id
    $post_type = get_post_type($post_id);

    // Lấy số lượng bài viết mặc định từ cài đặt WordPress
    if (!$limit) {
        $limit = get_option('posts_per_page', 3);
    }

    // Xác định taxonomy dựa trên post type
    $taxonomy = ($post_type === 'product') ? 'product_cat' : 'category';

    // Lấy danh sách category hoặc product_cat của bài viết/sản phẩm hiện tại
    $categories = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);

    // Query lấy bài viết/sản phẩm liên quan
    $args = [
        'post_type' => $post_type,
        'posts_per_page' => $limit,
        'post__not_in' => [$post_id], // Loại bỏ bài viết hiện tại
        'orderby' => 'rand', // Lấy ngẫu nhiên
        'fields' => 'ids', // Chỉ lấy ID
    ];

    // Nếu có category/product_cat, lấy bài viết/sản phẩm cùng danh mục
    if (!empty($categories)) {
        $args['tax_query'] = [
            [
                'taxonomy' => $taxonomy,
                'field' => 'term_id',
                'terms' => $categories,
            ],
        ];
    }

    return get_posts($args);
}


function adminz_is_frontend_request(string $uri = ''): bool {
    // Determine if this request is a frontend page request
    // Returns true for frontend page, false for REST, admin, ajax, assets

    $uri = $uri ?: $_SERVER['REQUEST_URI'];

    // skip WP REST API
    if (strpos($uri, '/wp-json/') === 0) {
        return false;
    }

    // skip admin ajax
    if (strpos($uri, '/wp-admin/admin-ajax.php') === 0) {
        return false;
    }

    // skip admin area
    if (is_admin()) {
        return false;
    }

    // skip favicon and static assets
    if (preg_match('#\.(js|css|png|jpg|jpeg|svg|webp|ico)$#', $uri)) {
        return false;
    }

    return true;
}

function adminz_flatsome_is_ux_builder(){
    return isset( $_POST['ux_builder_action'] );
}