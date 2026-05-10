<?php
if (!adminz_get_settings('Woocommerce')) {
    return;
}
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_product';
$___->shortcode_title = 'Woo product single';
$___->shortcode_icon = 'woo_products';
$___->options = [
    'id' => array(
        'type' => 'select',
        'heading' => 'Ids',
        'full_width' => true,
        'config' => array(
            'multiple' => false,
            'placeholder' => 'Select...',
            'postSelect' => array(
                'post_type' => array('product'),
            ),
        ),
    ),

];
$___->shortcode_callback = function ($atts) {
    if (empty($atts['id'])) {
        return 'Product ID is required.';
    }
    ob_start();
    $args = [
        'p' => $atts['id'],
        'post_type' => ['product'],
        'post_status' => ['publish'],
        'posts_per_page' => 1,
    ];
    $__the_query = new \WP_Query($args);
    if ($__the_query->have_posts()) {
        while ($__the_query->have_posts()) :
            $__the_query->the_post();
            // fix 
            remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10);
            remove_action('woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15);
            remove_action('woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20);
            wc_get_template_part('single-product/layouts/product-no-sidebar');
        endwhile;
        wp_reset_postdata();
    } else {
        echo __('Sorry, no posts matched your criteria.');
    }
    return ob_get_clean();
};
$___->general_element();
