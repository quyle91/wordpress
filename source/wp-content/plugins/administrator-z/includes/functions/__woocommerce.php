<?php
function adminz_is_woocommerce() {
    // Check if WooCommerce is active
    $active = in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    );

    // Also check if WooCommerce class exists (plugin is loaded)
    return $active && class_exists('WooCommerce');
}

// Ex: \wp-content\plugins\administrator-z\src\Widget\Adminz_Taxonomies.php
function adminz_term_count($term, $taxonomy = '', $woo_tax_query = [], $woo_meta_query = [], $woo_date_query = []) {

    // Tạo một key duy nhất cho transient dựa trên term và query vars
    $transient_key = 'adminz_term_count_' . md5(serialize([$term, $taxonomy, $woo_tax_query]));
    $transient_time = 6 * HOUR_IN_SECONDS;

    // clear 
    if (current_user_can('administrator')) {
        delete_transient($transient_key);
    }

    // Trả về dữ liệu đã lưu nếu tồn tại
    $cached_count = get_transient($transient_key);
    if ($cached_count !== false) {
        return $cached_count;
    }

    // Kiểm tra và lấy term đúng
    $_term = adminz_get_term($term, $taxonomy);

    if (is_wp_error($_term) || !$_term) {
        return 0;
    }

    $args = [
        'tax_query' => $woo_tax_query,
        'meta_query' => $woo_meta_query,
        'date_query' => $woo_date_query,
        'posts_per_page' => -1,
        'nopaging' => true,
    ];

    // kiểm tra và xoá hết các taxonomy tồn tại trong tax_query
    foreach ((array) $args['tax_query'] as $key => $condition) {
        if (($condition['taxonomy'] ?? '') == $_term->taxonomy) {
            unset($args['tax_query'][$key]);
        }
    }

    // thêm vào lại 1 lần nữa hoặc thêm mới
    $args['tax_query'][] = [
        'taxonomy' => $_term->taxonomy,
        'terms' => [$_term->slug],
        'field' => 'slug',
        'operator' => 'IN',
        'include_children' => 1,
    ];

    $query = new \WP_Query($args);
    $post_count = $query->post_count;

    // Reset query
    wp_reset_postdata();

    // luôn luôn set transient
    set_transient($transient_key, $post_count, $transient_time);

    return $post_count;
}

/**
 * Hỗ trợ lấy term từ nhiều kiểu đầu vào khác nhau.
 *
 * @param mixed$term Slug, term_id, hoặc đối tượng WP_Term.
 * @param string $taxonomy Taxonomy (nếu cần).
 * @return WP_Term|WP_Error
 */
function adminz_get_term($term, $taxonomy = '') {
    if ($term instanceof WP_Term) {
        // Nếu $term là đối tượng WP_Term, trả về luôn
        return $term;
    }

    // Kiểm tra nếu $term là chuỗi số (ví dụ: "100")
    if (is_string($term) && ctype_digit($term)) {
        $term = (int) $term; // Chuyển đổi thành số nguyên
    }

    if (is_int($term)) {
        // Nếu $term là term_id
        return get_term($term, $taxonomy);
    }

    if (is_string($term)) {

        if (empty($taxonomy)) {
            return new WP_Error('invalid_taxonomy', 'Taxonomy is requied!.');
        }
        // Nếu $term là slug
        return get_term_by('slug', $term, $taxonomy);
    }

    return new WP_Error('invalid_term', 'Invalid term.');
}

// clone of wc_query_string_form_fields
function adminz_wc_query_string_form_fields($values = null, $exclude = array(), $current_key = '', $return = false) {
    if (function_exists('wc_query_string_form_fields')) {
        return wc_query_string_form_fields($values, $exclude, $current_key, $return);
    }

    if (is_null($values)) {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $values = $_GET;
    } elseif (is_string($values)) {
        $url_parts = wp_parse_url($values);
        $values = array();

        if (! empty($url_parts['query'])) {
            // This is to preserve full-stops, pluses and spaces in the query string when ran through parse_str.
            $replace_chars = array(
                '.' => '{dot}',
                '+' => '{plus}',
            );

            $query_string = str_replace(array_keys($replace_chars), array_values($replace_chars), $url_parts['query']);

            // Parse the string.
            parse_str($query_string, $parsed_query_string);

            // Convert the full-stops, pluses and spaces back and add to values array.
            foreach ($parsed_query_string as $key => $value) {
                $new_key = str_replace(array_values($replace_chars), array_keys($replace_chars), $key);
                $new_value = str_replace(array_values($replace_chars), array_keys($replace_chars), $value);
                $values[$new_key] = $new_value;
            }
        }
    }
    $html = '';

    foreach ($values as $key => $value) {
        if (in_array($key, $exclude, true)) {
            continue;
        }
        if ($current_key) {
            $key = $current_key . '[' . $key . ']';
        }
        if (is_array($value)) {
            $html .= adminz_wc_query_string_form_fields($value, $exclude, $key, true);
        } else {
            $html .= '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr(wp_unslash($value)) . '" />';
        }
    }

    if ($return) {
        return $html;
    }

    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

function adminz_flatsome_is_shop_archive() {
    if(!adminz_is_woocommerce()){
        return false;
    }

    $queried_object = get_queried_object();
    $taxonomy = ($queried_object && property_exists($queried_object, 'taxonomy')) ? $queried_object->taxonomy : false;
    $additional_taxonomy_archives = [
        'product_brand', // Included in WooCommerce core from 9.4
        'berocket_brand',
        'product_brands',
        'pwb-brand',
        'yith_product_brand',
    ];

    $is_product_search_archive = is_search() && is_post_type_archive('product');
    $is_product_attribute_archive = $taxonomy && taxonomy_is_product_attribute($taxonomy);
    $is_additional_taxonomy_archive = $taxonomy && in_array($taxonomy, $additional_taxonomy_archives, true);

    return apply_filters(
        'flatsome_is_shop_archive',
        is_shop() || is_product_category() || is_product_tag() || $is_product_search_archive || $is_product_attribute_archive || $is_additional_taxonomy_archive
    );
}
