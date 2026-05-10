<?php

// ajax
add_action('wp_ajax_adminz_custom_blogs', 'adminz_custom_blogs');
add_action('wp_ajax_nopriv_adminz_custom_blogs', 'adminz_custom_blogs');
function adminz_custom_blogs() {
    if (!wp_verify_nonce($_POST['nonce'], 'adminz_js')) exit;
    $return = false;

    ob_start();

    //code here
    $data =  wp_unslash($_POST['data'] ?? '');
    $shortcode_data = json_decode($data, true);
    $shortcode_name = 'adminz_custom_blogs';

    // 1. Chuyển mảng atts thành chuỗi shortcode
    $shortcode_attrs = '';
    foreach ($shortcode_data as $key => $value) {
        $shortcode_attrs .= ' ' . $key . '="' . esc_attr($value) . '"';
    }

    // 2. Xây dựng chuỗi shortcode hoàn chỉnh
    $shortcode_string = '[' . $shortcode_name . $shortcode_attrs . ']';

    // 3. Thực thi shortcode
    echo do_shortcode($shortcode_string);


    $return = ob_get_clean();

    if (!$return) {
        wp_send_json_error('Error');
        wp_die();
    }

    wp_send_json_success($return);
    wp_die();
}

// show paged
add_action('after_adminz_fl_custom_blog_posts', function ($repeater, $atts, $recentPosts) {
    if (!($atts['adminz_paged_style'] ?? '')) {
        return;
    }

    if (!$recentPosts->have_posts()) {
        return;
    }

    // default pagination
    echo '<div class="inner_pagination pb">';
    $prev_arrow = is_rtl() ? get_flatsome_icon('icon-angle-right') : get_flatsome_icon('icon-angle-left');
    $next_arrow = is_rtl() ? get_flatsome_icon('icon-angle-left') : get_flatsome_icon('icon-angle-right');
    $args = [
        'total'   => $recentPosts->max_num_pages,
        'current' => max(1, get_query_var('paged')),
        'mid_size' => 3,
        'type' => 'array',
        'prev_text' => $prev_arrow,
        'next_text' => $next_arrow,
    ];

    if (defined('DOING_AJAX') && DOING_AJAX) {
        $data =  wp_unslash($_POST['data'] ?? '');
        $shortcode_data = json_decode($data, true);
        if ($shortcode_data['page_number'] ?? '') {
            $args['current'] = $shortcode_data['page_number'];
        }
        
        $base_url = $_POST['base_url'] ?? home_url('/');
        $args['base'] = trailingslashit($base_url) . 'page/%#%';
        $args['format'] = '';
    }

    $pages = paginate_links($args);

    if (is_array($pages)) {
        // Chuyển $atts thành JSON và escape để sử dụng trong HTML
        $atts_json = htmlspecialchars(json_encode($atts), ENT_QUOTES, 'UTF-8');
        $ul_attrs = [
            'class' => 'page-numbers nav-pagination links text-center custom-blog-posts-page-numbers mb-0 mt-0',
            'data-base-url' => $_POST['base_url'] ?? get_permalink(get_the_ID()),
            'data-atts' => $atts_json
        ];

        $ul_attrs_string = '';
        foreach ($ul_attrs as $key => $value) {
            $ul_attrs_string .= ' ' . $key . '="' . esc_attr($value) . '"';
        }

        echo '<ul' . $ul_attrs_string . '>';
        foreach ($pages as $key => $page) {
            echo '<li data-paged="' . esc_attr(strip_tags($page)) . '">' . $page . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';


    // load more button
    echo <<<HTML
    <div class="inner_loadmore pb text-center">
        <button class="button is-outline">{$atts['adminz_loadmore']}</button>
    </div>
    HTML;
}, 10, 3);

// change atts
add_filter('adminz_fl_custom_blog_posts_atts', function ($atts) {
    
    // page number
    $page_number = max(1, (int) get_query_var('paged'));
    if (!empty($_REQUEST['paged']) && is_numeric($_REQUEST['paged'])) {
        $page_number = (int) $_REQUEST['paged'];
    } elseif (!empty($_REQUEST['page']) && is_numeric($_REQUEST['page'])) {
        $page_number = (int) $_REQUEST['page'];
    }

    // ajax: adminz_custom_blogs
    if (defined('DOING_AJAX') && DOING_AJAX) {
        $data =  wp_unslash($_POST['data'] ?? '');
        $shortcode_data = json_decode($data, true);
        if ($shortcode_data['page_number'] ?? '') {
            $page_number = $shortcode_data['page_number'];
        }
    }
    $atts['page_number'] = $page_number;

    // infinity in slider
    if(in_array($atts['adminz_paged_style'], ['ajax', 'loadmore', 'infinity'])) {
        $atts['infinitive'] = 'false';
    }

    // echo "<pre>"; print_r($atts['infinitive']); echo "</pre>";
    return $atts;
});



// change query args
add_filter('adminz_fl_custom_blog_posts_args', function ($args, $atts) {
    extract($atts);

    // ids
    if (!empty($adminz_ids)) {
        $adminz_ids = explode(',', $adminz_ids);
        $adminz_ids = array_map('trim', $adminz_ids);

        $args = array(
            'post__in' => $adminz_ids,
            'post_type' => array(
                'post',
                'featured_item', // Include for its tag archive listing.
            ),
            'numberposts' => -1,
            'orderby' => 'post__in',
            'posts_per_page' => 9999,
            'ignore_sticky_posts' => true,
        );

        // Include for search archive listing.
        if (is_search()) {
            $args['post_type'][] = 'page';
        }
    }

    // post type
    if (!empty($adminz_post_type)) {
        $args['post_type'] = explode(',', $adminz_post_type);
    }

    // Chuẩn hóa & loại bỏ trùng lặp giữa included và excluded
    $included_term_ids = !empty($adminz_terms_included) ? array_map('intval', explode(',', $adminz_terms_included)) : [];
    $excluded_term_ids = !empty($adminz_terms_excluded) ? array_map('intval', explode(',', $adminz_terms_excluded)) : [];

    // Loại bỏ trùng lặp: term không thể vừa included vừa excluded
    $included_term_ids = array_diff($included_term_ids, $excluded_term_ids);
    $excluded_term_ids = array_diff($excluded_term_ids, $included_term_ids);

    // Khởi tạo tax_query nếu cần
    if (!isset($args['tax_query'])) {
        $args['tax_query'] = [
            'relation' => 'AND'
        ];
    }

    // Xử lý terms included
    foreach ($included_term_ids as $term_id) {
        $term = get_term($term_id);
        if (!is_wp_error($term) && isset($term->taxonomy)) {
            $args['tax_query'][] = [
                'taxonomy' => $term->taxonomy,
                'field' => 'id',
                'terms' => $term_id,
                'include_children' => true,
                'operator' => 'IN',
            ];
        }
    }

    // Xử lý terms excluded
    foreach ($excluded_term_ids as $term_id) {
        $term = get_term($term_id);
        if (!is_wp_error($term) && isset($term->taxonomy)) {
            $args['tax_query'][] = [
                'taxonomy' => $term->taxonomy,
                'field' => 'id',
                'terms' => $term_id,
                'include_children' => true,
                'operator' => 'NOT IN',
            ];
        }
    }
    // echo "<pre>"; print_r($args); echo "</pre>";

    //
    return $args;
}, 10, 2);
