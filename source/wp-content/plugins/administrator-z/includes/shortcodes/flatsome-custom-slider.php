<?php

$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_slider_custom';
$___->shortcode_title = 'Slider custom';
$___->shortcode_type = 'container';
$___->shortcode_message = 'Add slides here';
$___->shortcode_allow = ['adminz_slider_custom_item_wrap'];
$___->shortcode_template = '<div id="slider-{{::$id}}" class="slider-wrapper relative slider-type-{{ shortcode.options.type }} {{ shortcode.options.visibility }} {{ shortcode.options.class }}"> <div class="slider slider-auto-height slider-nav-{{ shortcode.options.navPos }}slider-nav-{{ shortcode.options.navSize }} slider-style-{{ shortcode.options.style }} slider-nav-{{ shortcode.options.navColor }} slider-nav-{{ shortcode.options.navStyle }} slider-nav-dots-{{ shortcode.options.bulletStyle }} is-draggable" ng-class="{\'slider-show-nav\' : shortcode.options.hideNav}"> <content></content> </div> <style scope="scope"> #slider-{{::$id}} {margin-bottom: {{ shortcode.options.margin }}; } #slider-{{::$id}} { background-color: {{ shortcode.options.bgColor }}; } #slider-{{::$id}} .slider > *{ max-width: {{ shortcode.options.slideWidth }}!important; } #slider-{{::$id}} .slider > *{margin-left:0px ; margin-right:0px; } </style> </div>';
$___->shortcode_icon = 'text';
$___->shortcode_tool = 'shortcodes/ux_slider/ux-slider-tools.directive.html';
$___->shortcode_wrap = false;
$___->shortcode_children = array(
    'inline' => true,
    'addable_spots' => array('left', 'right'),
);
$___->shortcode_toolbar = array(
    'show_children_selector' => true,
    'show_on_child_active' => true,
);
$___->options = [
    'label' => array(
        'type' => 'textfield',
        'heading' => 'Admin label',
        'placeholder' => 'Enter admin label...',
    ),
    'type' => array(
        'type' => 'select',
        'heading' => 'Type',
        'default' => 'slide',
        'options' => array(
            'slide' => 'Slide',
            'fade' => 'Fade',
        ),
    ),
    'layout_options' => array(
        'type' => 'group',
        'heading' => __('Layout'),
        'options' => array(
            'style' => array(
                'type' => 'select',
                'heading' => 'Style',
                'default' => 'normal',
                'options' => array(
                    'normal' => 'Default',
                    'container' => 'Container',
                    'focus' => 'Focus',
                    'shadow' => 'Shadow',
                ),
                'conditions' => 'type !== "fade"',
            ),
            'slide_width' => array(
                'type' => 'scrubfield',
                'heading' => 'Slide item Width',
                'description' => 'Width in Percent',
                'responsive' => true,
                'unit' => "%",
                'min' => '0',
                'default' => '25%',
            ),
            'as_nav_for' => array(
                'type' => 'textfield',
                'heading' => 'As nav for',
                'description' => 'Parent custom class',
                'placeholder' => '.your-slider',
            ),
            'slide_item_padding' => array(
                'type' => 'scrubfield',
                'heading' => 'Slide item Padding',
                'responsive' => true,
                'description' => 'Width in Px',
                'default' => '',
                'min' => '0',
            ),
            'slide_align' => array(
                'type' => 'select',
                'heading' => 'Slide Align',
                'default' => 'center',
                'options' => array(
                    'center' => 'Center',
                    'left' => 'Left',
                    'right' => 'Right',
                ),
                'conditions' => 'type !== "fade"',
            ),
            'bg_color' => array(
                'type' => 'colorpicker',
                'heading' => __('Bg Color'),
                'format' => 'rgb',
                'position' => 'bottom right',
                'helpers' => require(get_template_directory() . "/inc/builder/shortcodes/helpers/colors.php"),
            ),
            'margin' => array(
                'type' => 'scrubfield',
                'responsive' => true,
                'heading' => __('Margin'),
                'default' => '30px',
                'min' => 0,
                'max' => 100,
                'step' => 1,
            ),
            'infinitive' => array(
                'type' => 'radio-buttons',
                'heading' => __('Infinitive'),
                'default' => 'true',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
            'freescroll' => array(
                'type' => 'radio-buttons',
                'heading' => __('Free Scroll'),
                'default' => 'false',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
            'draggable' => array(
                'type' => 'radio-buttons',
                'heading' => __('Draggable'),
                'default' => 'true',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
            'parallax' => array(
                'type' => 'slider',
                'heading' => 'Parallax',
                'unit' => '+',
                'default' => 0,
                'max' => 10,
                'min' => 0,
            ),
            // 'mobile' => array(
            //     'type' => 'radio-buttons',
            //     'heading' => __('Show for Mobile'),
            //     'default' => 'true',
            //     'options' => array(
            //         'false' => array('title' => 'Off'),
            //         'true' => array('title' => 'On'),
            //     ),
            // ),
            'equal_height' => array(
                'type' => 'radio-buttons',
                'heading' => __('Equal height'),
                'default' => 'false',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
        ),
    ),
    'nav_options' => array(
        'type' => 'group',
        'heading' => __('Navigation'),
        'options' => array(
            'hide_nav' => array(
                'type' => 'radio-buttons',
                'heading' => __('Always Visible'),
                'default' => '',
                'options' => array(
                    '' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
            'nav_pos' => array(
                'type' => 'select',
                'heading' => 'Position',
                'default' => '',
                'options' => array(
                    '' => 'Inside',
                    'outside' => 'Outside',
                ),
            ),
            'nav_size' => array(
                'type' => 'select',
                'heading' => 'Size',
                'default' => 'large',
                'options' => array(
                    'large' => 'Large',
                    'normal' => 'Normal',
                ),
            ),
            'arrows' => array(
                'type' => 'radio-buttons',
                'heading' => __('Arrows'),
                'default' => 'true',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
            'nav_style' => array(
                'type' => 'select',
                'heading' => __('Arrow Style'),
                'default' => 'circle',
                'options' => array(
                    'circle' => 'Circle',
                    'simple' => 'Simple',
                    'reveal' => 'Reveal',
                ),
            ),
            'nav_color' => array(
                'type' => 'radio-buttons',
                'heading' => __('Arrow Color'),
                'default' => 'light',
                'options' => array(
                    'dark' => array('title' => 'Dark'),
                    'light' => array('title' => 'Light'),
                ),
            ),

            'bullets' => array(
                'type' => 'radio-buttons',
                'heading' => __('Bullets'),
                'default' => 'true',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
            'bullet_style' => array(
                'type' => 'select',
                'heading' => 'Bullet Style',
                'default' => 'circle',
                'options' => array(
                    'circle' => 'Circle',
                    'dashes' => 'Dashes',
                    'dashes-spaced' => 'Dashes (Spaced)',
                    'simple' => 'Simple',
                    'square' => 'Square',
                ),
            ),
        ),
    ),
    'slide_options' => array(
        'type' => 'group',
        'heading' => __('Auto Slide'),
        'options' => array(
            'auto_slide' => array(
                'type' => 'radio-buttons',
                'heading' => __('Auto slide'),
                'default' => 'true',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
            'timer' => array(
                'type' => 'textfield',
                'heading' => 'Timer (ms)',
                'default' => 6000,
            ),
            'pause_hover' => array(
                'type' => 'radio-buttons',
                'heading' => __('Pause on Hover'),
                'default' => 'true',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
        ),
    ),
    'adminz_auto_freescroll_group' => array(
        'type' => 'group',
        'heading' => __('Auto Free scroll'),
        'options' => array(
            'adminz_auto_freescroll' => array(
                'type' => 'radio-buttons',
                'heading' => __('Auto Free scroll'),
                'default' => 'false',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),

            'adminz_auto_freescroll_direction' => array(
                'type' => 'radio-buttons',
                'heading' => __('Free scroll direction'),
                'default' => 'to_left',
                'options' => array(
                    'to_right' => array('title' => 'To right'),
                    'to_left' => array('title' => 'To left'),
                ),
            ),

            'adminz_auto_freescroll_speed' => array(
                'type' => 'slider',
                'heading' => __('Speed'),
                'unit' => 'px',
                'default' => 1,
                'max' => 10,
                'min' => 0,
            ),
            'adminz_auto_freescroll_mobile' => array(
                'type' => 'radio-buttons',
                'heading' => __('Work on mobile'),
                'default' => 'false',
                'options' => array(
                    'false' => array('title' => 'Off'),
                    'true' => array('title' => 'On'),
                ),
            ),
        ),
    ),
    'advanced_options' => require(get_template_directory() . "/inc/builder/shortcodes/commons/advanced.php"),

];
$___->shortcode_callback = function ($atts, $content = null) {
    extract($atts = shortcode_atts(array(
        '_id' => 'slider-' . rand(),
        'timer' => '6000',
        'bullets' => 'true',
        'visibility' => '',
        'class' => '',
        'type' => 'slide',
        'bullet_style' => '',
        'auto_slide' => 'true',
        'auto_height' => 'false',
        'bg_color' => '',
        'slide_align' => 'center',
        'style' => 'normal',
        'slide_width' => '25%',
        'slide_width__md' => '33.333%',
        'slide_width__sm' => '100%',
        'slide_item_padding' => '15px',
        'slide_item_padding__md' => '15px',
        'slide_item_padding__sm' => '15px',
        'arrows' => 'true',
        'pause_hover' => 'true',
        'hide_nav' => '',
        'nav_style' => 'circle',
        'nav_color' => 'light',
        'nav_size' => 'large',
        'nav_pos' => '',
        'infinitive' => 'true',
        'freescroll' => 'false',
        'adminz_auto_freescroll' => 'false',
        'adminz_auto_freescroll_direction' => 'to_left',
        'adminz_auto_freescroll_speed' => '1',
        'adminz_auto_freescroll_mobile' => 'false',
        'parallax' => '0',
        'margin' => '30px',
        'margin__md' => '30px',
        'margin__sm' => '30',
        'columns' => '1',
        'height' => '',
        'rtl' => 'false',
        'draggable' => 'true',
        'friction' => '0.6',
        'selectedattraction' => '0.1',
        'threshold' => '10',
        'as_nav_for' => '',
        'equal_height' => 'false',
        // Derpicated
        'mobile' => 'true',

    ), $atts));

    // Stop if visibility is hidden
    if ($visibility == 'hidden') return;
    if ($mobile !== 'true' && !$visibility) {
        $visibility = 'hide-for-small';
    }

    ob_start();

    $wrapper_classes = array('slider-wrapper', 'relative', 'adminz_slider_custom', 'row-slider');
    if ($class) $wrapper_classes[] = $class;
    if ($visibility) $wrapper_classes[] = $visibility;
    if ($equal_height == "true") {
        $wrapper_classes[] = 'equal-height';
    }
    $wrapper_classes = implode(" ", $wrapper_classes);

    $classes = array('slider');

    if ($type == 'fade') $classes[] = 'slider-type-' . $type;

    // Bullet style
    if ($bullet_style) $classes[] = 'slider-nav-dots-' . $bullet_style;

    // Nav style
    if ($nav_style) $classes[] = 'slider-nav-' . $nav_style;

    // Nav size
    if ($nav_size) $classes[] = 'slider-nav-' . $nav_size;

    // Nav Color
    if ($nav_color) $classes[] = 'slider-nav-' . $nav_color;

    // Nav Position
    if ($nav_pos) $classes[] = 'slider-nav-' . $nav_pos;

    if ($as_nav_for) $classes[] = 'has_as_nav_for';

    // Add timer
    if ($auto_slide == 'true') $auto_slide = $timer;

    // reset timer auto free scroll
    if ($adminz_auto_freescroll == 'true') {
        $auto_slide = 0;
    }

    // Add Slider style
    if ($style) $classes[] = 'slider-style-' . $style;

    // Always show Nav if set
    if ($hide_nav == 'true') {
        $classes[] = 'slider-show-nav';
    }

    // Always show Nav if set
    if ($adminz_auto_freescroll == 'true') {
        $classes[] = 'adminz_auto_freescroll';
    }

    // Slider Nav visebility
    $is_arrows = 'true';
    $is_bullets = 'true';

    if ($arrows == 'false') $is_arrows = 'false';
    if ($bullets == 'false') $is_bullets = 'false';

    if (is_rtl()) $rtl = 'true';

    $classes = implode(" ", $classes);

    // slide with 
    echo ux_builder_element_style_tag(
        "$_id .adminz_slider_item",
        array(
            'slide_width' => array(
                'selector' => '',
                'property' => 'max-width',
                'unit' => '%',
            ),
        ),
        $atts
    );

    // item padding
    echo ux_builder_element_style_tag(
        "$_id .adminz_slider_item",
        array(
            'slide_item_padding' => array(
                'selector' => '',
                'property' => 'padding-left,padding-right',
                'unit' => 'px',
            ),
        ),
        $atts
    );

    // wrapper margin
    $atts['wrapper_margin'] = '-' . $atts['slide_item_padding'];
    $atts['wrapper_margin__md'] = '-' . $atts['slide_item_padding__md'];
    $atts['wrapper_margin__sm'] = '-' . $atts['slide_item_padding__sm'];
    echo ux_builder_element_style_tag(
        "$_id .slider",
        array(
            'wrapper_margin' => array(
                'selector' => '',
                'property' => 'margin-left,margin-right',
                'unit' => 'px',
            ),
        ),
        $atts
    );

    // Inline CSS.
    $bg_color = array(
        'bg_color' => array(
            'attribute' => 'background-color',
            'value' => $bg_color,
        ),
    );

?>
    <div class="<?php echo esc_attr($wrapper_classes); ?>" id="<?php echo esc_attr($_id); ?>" <?php echo get_shortcode_inline_css($bg_color); ?>>
        <?php
        $flickity_options = [
            'cellAlign' => $slide_align,
            'imagesLoaded' => true,
            'lazyLoad' => 1,
            'freeScroll' => filter_var($freescroll, FILTER_VALIDATE_BOOLEAN),
            'wrapAround' => filter_var($infinitive, FILTER_VALIDATE_BOOLEAN),
            'pauseAutoPlayOnHover' => filter_var($pause_hover, FILTER_VALIDATE_BOOLEAN),
            'prevNextButtons' => filter_var($is_arrows, FILTER_VALIDATE_BOOLEAN),
            'contain' => true,
            'adaptiveHeight' => filter_var($auto_height, FILTER_VALIDATE_BOOLEAN),
            'dragThreshold' => (int) $threshold,
            'percentPosition' => true,
            'pageDots' => filter_var($is_bullets, FILTER_VALIDATE_BOOLEAN),
            'rightToLeft' => filter_var($rtl, FILTER_VALIDATE_BOOLEAN),
            'draggable' => filter_var($draggable, FILTER_VALIDATE_BOOLEAN),
            'selectedAttraction' => (float) $selectedattraction,
            'parallax' => filter_var($parallax, FILTER_VALIDATE_BOOLEAN),
            'friction' => (float) $friction,
            'groupCells' => $slide_width,
            'adminz_auto_freescroll_direction' => $adminz_auto_freescroll_direction,
            'adminz_auto_freescroll_speed' => $adminz_auto_freescroll_speed,
            'adminz_auto_freescroll_mobile' => $adminz_auto_freescroll_mobile,
        ];

        // Các điều kiện thêm option
        if ($auto_slide) {
            $flickity_options['autoPlay'] = (int) $auto_slide;
        }
        if ($as_nav_for) {
            $flickity_options['asNavFor'] = $as_nav_for . ' .slider';
        }

        ?>
        <div class="<?php echo esc_attr($classes); ?>" data-flickity-options='<?php echo wp_json_encode($flickity_options); ?>'>
            <?php echo do_shortcode($content); ?>
        </div>
        <div class="loading-spin dark large centered admz"></div>

        <?php
        echo ux_builder_element_style_tag(
            $_id,
            array(
                'margin' => array(
                    'selector' => '',
                    'property' => 'margin-bottom',
                ),
            ),
            $atts
        );
        ?>
    </div>
<?php
    return ob_get_clean();
};
$___->general_element();
















$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_slider_custom_item_wrap';
$___->shortcode_title = 'Slider item';
$___->shortcode_type = 'container';
$___->shortcode_icon = 'text';
$___->options = [
    //
];
$___->shortcode_callback = function ($atts, $content = null) {
    ob_start();
    echo '<div class="adminz_slider_item">';
    echo do_shortcode($content);
    echo '</div>';
    return ob_get_clean();
};
$___->general_element();
