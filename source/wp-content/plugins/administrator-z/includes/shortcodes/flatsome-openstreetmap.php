<?php

// Container -----------------------------------------
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_map';
$___->shortcode_type = 'container';
$___->shortcode_allow = ['adminz_map-item'];
$___->shortcode_title = 'Open street map';
$___->shortcode_icon = 'text';
$___->options = [
    'map_config' => [
        'type' => 'group',
        'heading' => 'Map config',
        'options' => [
            'mapzoom' => array(
                'type' => 'slider',
                'heading' => __('Map zoom'),
                'min' => 1,
                'max' => 18,
                'default' => 5,
            ),
            'mapheight' => array(
                'type' => 'slider',
                'unit' => 'px',
                'heading' => __('Map height'),
                'default' => 500,
                'max' => 1000,
                'min' => 200,
                'step' => 10,
            ),
        ],
    ],
    'marker_config' => array(
        'type' => 'group',
        'heading' => 'Marker config',
        'options' => array(
            'markerzoom' => array(
                'type' => 'slider',
                'heading' => __('Marker zoom'),
                'default' => 9,
                'min' => 1,
                'max' => 18,
            ),
        ),
    ),
    'other_config' => array(
        'type' => 'group',
        'heading' => 'Other config',
        'options' => array(
            'class' => array(
                'type' => 'textfield',
                'heading' => 'Class',
            ),
            'field1' => array(
                'type' => 'textfield',
                'heading' => __('Field 1 title'),
                'default' => 'Country',
            ),
            'field2' => array(
                'type' => 'textfield',
                'heading' => __('Field 2 title'),
                'default' => 'City',
            ),
            'search_text' => array(
                'type' => 'textfield',
                'heading' => __('Form title'),
                'default' => '',
            ),
            'show_form' => array(
                'type' => 'checkbox',
                'heading' => __('Show form'),
                'default' => '',
            ),
        ),
    )
];
$___->shortcode_callback = function ($atts, $content = null) {
    $atts = shortcode_atts(array(
        'id' => "id_" . rand(),
        'mapzoom' => '8',
        'mapheight' => '500',
        'field1' => 'Country',
        'field2' => 'City',
        'markerzoom' => '9',
        'show_form' => '',
        'class' => '',
        'search_text' => '',

        // other 
        'placeholder_text' => __("Select"),
    ), $atts);

    // scripts footer

    wp_enqueue_style(
        'leaflet-css',
        ADMINZ_DIR_URL . "assets/vendor/leaflet/dist/leaflet.css",
        [],
        ADMINZ_VERSION,
        'all'
    );

    wp_enqueue_script(
        'leaflet-js',
        ADMINZ_DIR_URL . "assets/vendor/leaflet/dist/leaflet.js",
        [],
        ADMINZ_VERSION,
        true
    );

    wp_enqueue_script(
        'adminz_map_js',
        ADMINZ_DIR_URL . "assets/js/adminz-map.js",
        ['adminz_js'],
        ADMINZ_VERSION,
        true,
    );

    ob_start();
    $atts['class'] .= " adminz_map row align-equal";
?>
    <div
        id="<?= $atts['id'] ?>"
        class="<?= $atts['class'] ?? "" ?>"
        data-map='<?= json_encode($atts) ?>'>
        <div class="col medium-9 small-12 adminz_list">
            <div class="col-inner relative">
                <div id="<?= $atts['id'] ?>_map" class="map" style="height: 100%; min-height: <?= esc_attr($atts['mapheight']); ?>px;">
                    <!-- map -->
                    <?php
                    if (adminz_flatsome_is_ux_builder()) {
                        echo adminz_preview_text();
                    }
                    ?>
                </div>
            </div>
        </div>
        <div class="col medium-3 small-12 adminz_form_search">
            <div class="col-inner" style="max-height: <?= esc_attr($atts['mapheight']); ?>px; overflow: auto;">
                <form class="form mb mt-half <?= esc_attr($atts['show_form']) ?>">
                    <?php
                    if (adminz_flatsome_is_ux_builder()) {
                        echo adminz_preview_text();
                    }
                    ?>
                </form>
                <div class="list">
                    <?php echo do_shortcode($content); ?>
                </div>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
};
$___->general_element();










// Marker -----------------------------------------

$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_map-item';
$___->shortcode_title = 'Marker';
$___->shortcode_icon = 'text';
$___->options = [
    'image' => array(
        'type' => 'image',
        'heading' => __('Item thumbnail'),
        'default' => '',
    ),
    'title' => array(
        'type' => 'textfield',
        'heading' => __('Title'),
    ),
    'address' => array(
        'type' => 'textfield',
        'heading' => __('Address'),
        'default' => __(''),
    ),
    'phone_number' => array(
        'type' => 'textfield',
        'heading' => __('Phone number'),
        'default' => __(''),
    ),
    'description' => array(
        'type' => 'textarea',
        'heading' => __('More descriptions'),
        'default' => __(''),
    ),
    'address_opt_1' => array(
        'type' => 'textfield',
        'heading' => __('Filter option 1'),
        'default' => __(''),
    ),
    'address_opt_2' => array(
        'type' => 'textfield',
        'heading' => __('Filter option 2'),
        'default' => __(''),
    ),
    'latlong' => array(
        'type' => 'textfield',
        'heading' => __('Lat Long'),
    ),
    'popup' => array(
        'type' => 'checkbox',
        'heading' => __('Open popup & flyto'),
        'default' => '',
    ),
];
$___->shortcode_callback = function ($atts, $content = null) {
    $atts = shortcode_atts(array(
        'id' => 'item_' . rand(),
        'image' => '',
        'marker' => ADMINZ_DIR_URL . "assets/image/marker-icon-gg.png",
        // 'is_custom_marker' => '',
        'title' => '',
        'address' => '',
        'phone_number' => '',
        'description' => '',
        'address_opt_1' => '',
        'address_opt_2' => '',
        'latlong' => '',
        'popup' => '',
        // 'flyto' => '',
    ), $atts);

    if (adminz_flatsome_is_ux_builder()) {
        return adminz_preview_text($atts['title'] . " -- click dup to edit --");
    }

    ob_start();

?>
    <div
        id="<?= esc_attr($atts['id']) ?>"
        class="item flex mb-half hidden"
        data='<?= json_encode($atts); ?>'>
        <div class="thumb">
            <?= wp_get_attachment_image($atts['image'], 'thumbnail', false, []) ?>
        </div>
        <div class="text">
            <div class="h4"> <?= esc_attr($atts['title']) ?> </div>
            <div class="">
                <small><?= esc_attr($atts['address']) ?></small>
            </div>
            <div class="">
                <small>
                    <a class="button is-underline" href="tel:<?= str_replace(" ", "", $atts['phone_number']) ?>">
                        <?= esc_attr($atts['phone_number']) ?>
                    </a>
                </small>
            </div>
        </div>
    </div>
<?php
    return ob_get_clean();
};
$___->general_element();
