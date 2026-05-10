<?php
function adminz_get_all_child_pages($parent_id) {
    $args = [
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'post_parent' => $parent_id,
        'fields' => 'ids'
    ];

    $child_pages = get_posts($args);

    foreach ($child_pages as $child_id) {
        $child_pages = array_merge($child_pages, adminz_get_all_child_pages($child_id));
    }

    return $child_pages;
}

function adminz_is_term_active($term_slug, $taxonomy) {
    // 1. Check nếu đang ở trang taxonomy cụ thể
    if (is_tax($taxonomy, $term_slug)) {
        return true;
    }

    // 2. Check nếu có $_GET['taxonomy'] và khớp slug
    if (isset($_GET[$taxonomy]) && $_GET[$taxonomy] === $term_slug) {
        return true;
    }

    // 3. Check queried object có phải là term mong muốn không
    $term = get_queried_object();
    if ($term instanceof WP_Term && $term->taxonomy === $taxonomy && $term->slug === $term_slug) {
        return true;
    }

    return false;
}

function adminz_get_preserved_query_vars($key, $value, $url, $excluded = ['page', 'paged']) {
    $query_vars = array_merge($_GET, [$key => $value]);
    foreach ((array)$excluded as $key) {
        if (isset($query_vars[$key])) {
            unset($query_vars[$key]);
        }
    }
    return add_query_arg(
        $query_vars,
        $url
    );
}

function adminz_is_yoast_or_rankmath(){
    return (is_plugin_active('wordpress-seo/wp-seo.php') or is_plugin_active('seo-by-rank-math/rank-math.php'));
}