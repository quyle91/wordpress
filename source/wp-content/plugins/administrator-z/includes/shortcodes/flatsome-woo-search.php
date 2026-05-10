<?php
if (!adminz_get_settings('Woocommerce')) {
    return;
}

$parent = new \Adminz\Helper\FlatsomeELement;
$parent->shortcode_name = 'adminz_woo_search';
$parent->shortcode_type = 'container';
$parent->shortcode_allow = ['adminz_woo_search_item'];
$parent->shortcode_title = 'Form Woo search';
$parent->shortcode_icon = 'text';
$parent->shortcode_presets = array(
    array(
        'name' => __('Default'),
        'content' => '
			[adminz_woo_search]
				[adminz_woo_search_item col_large="12"]
				[adminz_woo_search_item type="product_cat" col_large="6"]
				[adminz_woo_search_item type="submit" col_large="6"]
			[/adminz_woo_search]'
    ),
);
$parent->options = [
    'appearance' => [
        'type' => 'group',
        'heading' => 'Appearance',
        'options' => [
            'placeholder' => array(
                'type' => 'textfield',
                'heading' => 'Text placeholder',
                'default' => __('Search'),
            ),
            'text_view_more' => array(
                'type' => 'textfield',
                'heading' => 'Text view more',
                'default' => __('Read more'),
            ),
            'style' => array(
                'type' => 'select',
                'heading' => 'Column Spacing',
                'default' => 'small',
                'options' => array(
                    '' => 'Normal',
                    'small' => 'Small',
                    'large' => 'Large',
                    'collapse' => 'Collapse',
                ),
            ),
            'class' => array(
                'type' => 'textfield',
                'heading' => 'Class',
                'default' => '',
            ),
        ],
    ],
];
$parent->shortcode_callback = function ($atts, $content = null) {
    $default = [
        '_id' => "id_" . rand(),
        'placeholder' => __('Search'),
        'text_view_more' => __('Read more'),
        'style' => 'small',
        'class' => '',
    ];
    $atts = shortcode_atts($default, $atts);
    $atts['class'] .= " adminz_woo_search";

    // save to global 
    $atts['has_view_more'] = false;
    adminz_tmp('parent_state', $atts);
    ob_start();
?>
    <form method="get" action="<?= wc_get_page_permalink('shop') ?>" id="<?= esc_attr($atts['_id']) ?>"
        class="<?= esc_attr($atts['class']) ?>" data="<?= esc_attr(json_encode($atts)) ?>">
        <div class="row row-<?= esc_attr($atts['style']) ?>">
            <?= do_shortcode($content) ?>
        </div>
    </form>
<?php
    return ob_get_clean();
};
$parent->general_element();
















$child = new \Adminz\Helper\FlatsomeELement;
$child->shortcode_name = 'adminz_woo_search_item';
$child->shortcode_title = 'Item';
$child->shortcode_wrap = false;
$child->shortcode_icon = 'text';
$child->shortcode_info = '{{type}}';
$child->options = function () {
    $options = [];
    $taxonomy_options = adminz_tmp(
        'taxonomy_options',
        adminz_get_taxonomy_options('product'),
    );

    $meta_options = adminz_tmp(
        'meta_options',
        adminz_get_meta_options('product')
    );

    $attribute_options = adminz_tmp(
        'attribute_options',
        adminz_get_attribute_options('product')
    );

    $field_types = adminz_tmp(
        'taxonomy_meta_fields',
        array_merge(
            $taxonomy_options,
            $attribute_options,
            $meta_options,
            [
                'rating_filter' => 'Woo: Rating',
                'min_max_price' => 'Woo: Price'
            ]
        )
    );

    // 2 added fields
    $field_types['name'] = "Field: Search by name";
    $field_types['submit'] = "Field: Submit button";
    $field_types = apply_filters('adminz_woo_search_field_types', $field_types);

    // field type
    $options['label'] = [
        'type' => 'textfield',
        'heading' => 'Label',
        'default' => '',
    ];
    $options['field_type'] = [
        'type' => 'group',
        'heading' => 'Field type',
        'options' => [
            'type' => [
                'type' => 'select',
                'default' => 'name',
                'heading' => 'Type',
                'options' => $field_types,
            ],
        ],
    ];

    // appearance
    $options['appearance'] = [
        'type' => 'group',
        'heading' => 'Appearance',
        'options' => [
            'col_large' => array(
                'type' => 'select',
                'heading' => 'Large columns size',
                'default' => '4',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '6' => '6',
                    '8' => '8',
                    '9' => '9',
                    '12' => '12',
                ),
            ),
            'col_small' => array(
                'type' => 'select',
                'heading' => 'Small columns size',
                'default' => '12',
                'options' => array(
                    '1' => '1',
                    '2' => '2',
                    '3' => '3',
                    '4' => '4',
                    '6' => '6',
                    '12' => '12',
                ),
            ),
            'hide' => array(
                'type' => 'checkbox',
                'heading' => 'Hide',
                'default' => '',
            ),
            'class' => array(
                'type' => 'textfield',
                'heading' => 'Class',
                'default' => '',
            ),
        ],
    ];
    return $options;
};
$child->shortcode_callback = function ($atts, $content = null) {
    $default = [
        '_id' => "id_" . rand(),
        'label' => '',
        'type' => 'name',
        'col_large' => '4',
        'col_small' => '12',
        'hide' => '',
        'break_line' => '',
        'class' => '',
    ];
    $atts = shortcode_atts($default, $atts);
    $return = adminz_woo_search_field($atts);
    return $return;
};
$child->general_element();
