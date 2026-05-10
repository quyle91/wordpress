<?php
$xxx = new \Adminz\Helper\FlatsomeELement;
$xxx->shortcode_name = 'adminz_search_cpt';
$xxx->shortcode_title = 'Search CPT';
$xxx->shortcode_icon = 'text';
$xxx->shortcode_compile = false;

$xxx->options = [
    'post_type' => array(
        'type' => 'select',
        'heading' => __('Post type'),
        'default' => 'post',
        'config' => array(
            'multiple' => true,
            'placeholder' => 'Select..',
            'options' => get_post_types()
        ),
    ),
    'style' => array(
        'type' => 'select',
        'heading' => __('Style'),
        'options' => array(
            '' => 'Normal',
            'flat' => 'Flat',
        )
    ),

    'size' => array(
        'type' => 'radio-buttons',
        'heading' => __('Size'),
        'default' => 'medium',
        'options' => require(get_template_directory() . '/inc/builder/shortcodes/values/text-sizes.php'),
        'on_change' => array(
            'class' => 'is-{{ value }}'
        )
    ),
    'advanced_options' => require(get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php'),
];

$xxx->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'post_type' => 'post',
        'size' => 'normal',
        'style' => '',
        'class' => '',
        'visibility' => ''
    ), $atts));

    $classes = array('searchform-wrapper', 'ux-search-box', 'relative', 'adminz_search_cpt');

    if ($class) $classes[] = $class;
    if ($visibility) $classes[] = $visibility;
    if ($style) $classes[] = 'form-' . $style;
    if ($size) $classes[] = 'is-' . $size;
    $classes = implode(' ', $classes);

    ob_start();
    echo '<div class="' . esc_attr($classes) . '">';
    get_search_form();
    echo '</div>';
    $content = ob_get_clean();

    // Thay class searchform tránh trigger của flatsome live search
    $content = str_replace('"searchform"', '"adminz-searchform"', $content);

    // thêm field post_type
    $content = str_replace('</form>', '<input type="hidden" name="post_type" value="' . $post_type . '"/></form>', $content);
    $content = str_replace('<form', '<form data-adminz-post_type="' . $post_type . '"', $content);

    wp_enqueue_script(
        'adminz-flatsome-search-cpt',
        ADMINZ_DIR_URL . 'assets/js/adminz-flatsome-search-cpt.js',
        ['jquery'],
        ADMINZ_VERSION,
        true
    );
    return $content;
};
$xxx->general_element();



// @see: wp-content\themes\flatsome\inc\extensions\flatsome-live-search\flatsome-live-search.php
$ajax_func = function () {
    $post_type = $_REQUEST['post_type'] ?? 'page';
    $post_type = explode(',', $post_type);

    // The string from search text field.
    $query = apply_filters('flatsome_ajax_search_query', $_REQUEST['query']);
    // $wc_activated = is_woocommerce_activated();
    // $products = array();
    $posts = array();
    // $sku_products = array();
    // $tag_products = array();
    // $suggestions= array();

    $args = array(
        's' => $query,
        'orderby' => '',
        'post_type' => array(),
        'post_status' => 'publish',
        'posts_per_page' => 100,
        'ignore_sticky_posts' => 1,
        'post_password' => '',
        'suppress_filters' => false,
    );

    // if ($wc_activated) {
    // $products = flatsome_ajax_search_get_products('product', $args);
    // $sku_products = get_theme_mod('search_by_sku', 0) ? flatsome_ajax_search_get_products('sku', $args) : array();
    // $tag_products = get_theme_mod('search_by_product_tag', 0) ? flatsome_ajax_search_get_products('tag', $args) : array();
    // }

    // if ((! $wc_activated || get_theme_mod('search_result', 1)) && ! isset($_REQUEST['product_cat'])) {
    // $posts = flatsome_ajax_search_posts($args);
    // }

    $filter_func = function ($return) use ($post_type) {
        $return = (array)$post_type;
        return $return;
    };
    add_filter('flatsome_ajax_search_post_type', $filter_func);
    $posts = flatsome_ajax_search_posts($args);
    remove_filter('flatsome_ajax_search_post_type', $filter_func);

    // $results = array_merge($products, $sku_products, $tag_products, $posts);
    $results = $posts;

    foreach ($results as $key => $post) {
        // if ($wc_activated && ($post->post_type === 'product' || $post->post_type === 'product_variation')) {
        // $product = wc_get_product($post);

        // if ($product->get_parent_id()) {
        // $parent_product = wc_get_product($product->get_parent_id());
        // $visible= $parent_product->get_catalog_visibility() === 'visible' || $parent_product->get_catalog_visibility() === 'search';
        // if ($parent_product->get_status() !== 'publish' || ! $visible) {
        // unset($results[$key]);
        // continue;
        // }
        // }

        // $product_image = wp_get_attachment_image_src(get_post_thumbnail_id($product->get_id()));

        // $suggestions[] = array(
        // 'type'=> 'Product',
        // 'id'=> $product->get_id(),
        // 'value' => $product->get_title(),
        // 'url' => $product->get_permalink(),
        // 'img' => $product_image ? $product_image[0] : '',
        // 'price' => $product->get_price_html(),
        // );
        // } else {
        $suggestions[] = array(
            'type' => 'Page',
            'id' => $post->ID,
            'value' => get_the_title($post->ID),
            'url' => get_the_permalink($post->ID),
            'img' => get_the_post_thumbnail_url($post->ID, 'thumbnail'),
            'price' => '',
        );
        // }
    }

    if (empty($results)) {
        // $no_results = $wc_activated ? __('No products found.', 'woocommerce') : __('No matches found', 'flatsome');
        $no_results = __('No matches found', 'flatsome');

        $suggestions[] = array(
            'id' => -1,
            'value' => $no_results,
            'url' => '',
        );
    }

    // $suggestions = flatsome_unique_suggestions(array($products, $sku_products, $tag_products), $suggestions);

    wp_send_json(array('suggestions' => $suggestions));
};

add_action('wp_ajax_adminz_flatsome_ajax_search_products', $ajax_func);
add_action('wp_ajax_nopriv_adminz_flatsome_ajax_search_products', $ajax_func);
