<?php
$___ = new \Adminz\Helper\FlatsomeELement;
$___->shortcode_name = 'adminz_flickity_gallery';
$___->shortcode_title = 'Flickity Gallery';
$___->shortcode_icon = 'text';
require_once(get_template_directory() . '/inc/builder/helpers.php');
if (!function_exists('flatsome_ux_builder_image_sizes')) return;

$___->options = array_merge(
    [
        [
            'type' => 'group',
            'heading' => 'Gallery',
            'options' => array(

                'ids' => array(
                    'type' => 'gallery',
                    'heading' => __('Gallery'),
                ),

                'big_image_size' => array(
                    'type' => 'select',
                    'heading' => 'Image Size',
                    'param_name' => 'image_size',
                    'default' => 'large',
                    'options' => flatsome_ux_builder_image_sizes(),
                ),
                'small_image_size' => array(
                    'type' => 'select',
                    'heading' => 'Image Size',
                    'param_name' => 'image_size',
                    'default' => 'thumbnail',
                    'options' => flatsome_ux_builder_image_sizes(),
                ),

                'lightbox' => array(
                    'type' => 'radio-buttons',
                    'heading' => __('Lightbox'),
                    'default' => '',
                    'options' => array(
                        '' => array('title' => 'Off'),
                        'true' => array('title' => 'On'),
                    ),
                ),

                'lightbox_image_size' => array(
                    'type' => 'select',
                    'heading' => __('Lightbox Image Size'),
                    'conditions' => 'lightbox == "true" || lightbox == "photoswipe"',
                    'default' => 'original',
                    'options' => flatsome_ux_builder_image_sizes(),
                ),

                'height' => array(
                    'type' => 'scrubfield',
                    'heading' => __('Height'),
                    'default' => '',
                    'placeholder' => __('Auto'),
                    'min' => 0,
                    'max' => 1000,
                    'step' => 1,
                    'helpers' => require(get_template_directory() . '/inc/builder/shortcodes/helpers/image-heights.php'),
                ),

                'thumbnails_width' => array(
                    'type' => 'slider',
                    'heading' => 'Thumbnails width',
                    'responsive' => true,
                    'default' => '25',
                    'unit' => '%',
                    'max' => '100',
                    'min' => '0',
                ),

                'gap' => array(
                    'type' => 'slider',
                    'heading' => 'Gap',
                    'responsive' => true,
                    'default' => '20',
                    'unit' => 'px',
                    'max' => '100',
                    'min' => '0',
                ),
            ),
        ],
        'slide_options' => [
            'type' => 'group',
            'heading' => __('Auto Slide'),
            'options' => array(
                'auto_slide' => array(
                    'type' => 'radio-buttons',
                    'heading' => __('Auto slide'),
                    'default' => 'false',
                    'options' => array(
                        'false' => array('title' => 'Off'),
                        'true' => array('title' => 'On'),
                    ),
                ),
                'timer' => array(
                    'type' => 'textfield',
                    'heading' => 'Timer (ms)',
                    'default' => 2000,
                ),
            ),
        ],
    ],
    [
        'advanced_options' => require(get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php')
    ]
);
$___->shortcode_callback = function ($atts, $content = null) {
    extract(shortcode_atts(array(
        'ids' => '', //
        'big_image_size' => 'large', // 
        'small_image_size' => 'thumbnail', // 
        'lightbox_image_size' => 'origin',
        'lightbox' => '', //
        'height' => '56.25%', // 
        'thumbnails_width' => '20',
        'thumbnails_width__md' => '25',
        'thumbnails_width__sm' => '33',
        'gap' => '10', //
        'auto_slide' => 'false', //
        'timer' => '2000', //
        'class' => '', //
        'visibility' => '', //
    ), $atts));

    $classes = ['adminz_flickity_slider', $class, $visibility];
    $ids = explode(",", $ids);

    $element_id = 'adminz_flickity_slider_' . wp_rand();
    $slides_class = $element_id . "_slide";

    ob_start();
?>
    <div id="<?= esc_attr($element_id) ?>" class="<?php implode(' ', $classes) ?>">
        <!-- main -->
        [adminz_slider_custom
        slide_width="100%"
        bullets="false"
        class="<?= esc_attr($slides_class) ?> slide_main"
        timer=<?= esc_attr($timer) ?>
        auto_slide="<?= esc_attr($auto_slide) ?>"
        pause_hover="true"
        ]
        <?php
        foreach ((array)$ids as $key => $id) {
        ?>
            [adminz_slider_custom_item_wrap]
            <div class="relative">
                <?php if (wp_script_is('adminz-photoswipe-js', 'enqueued')): ?>
                    <div class="image-tools absolute bottom left z-3">
                        <?php
                        $image_meta = wp_get_attachment_metadata($id);
                        $origin_src = wp_get_attachment_url($id);
                        $origin_width = $image_meta['width'] ?? '';
                        $origin_height = $image_meta['height'] ?? '';
                        ?>
                        <a
                            adminz_origin_src="<?= esc_attr($origin_src) ?>"
                            adminz_origin_width="<?= esc_attr($origin_width) ?>"
                            adminz_origin_height="<?= esc_attr($origin_height) ?>"
                            href="javascript:void(0)"
                            class="zoom-button button is-outline circle icon hide-for-small">
                            <i class="icon-expand"></i>
                        </a>
                    </div>
                    <?php $lightbox = false; //disable flatsome lightbox 
                    ?>
                <?php endif; ?>
                [ux_image
                id="<?= esc_attr($id) ?>"
                image_size="original"
                height="<?= esc_attr($height) ?>"
                <?php if ($lightbox) echo 'lightbox="true"'; ?>
                lightbox_image_size="<?= esc_attr($lightbox_image_size) ?>"
                ]
            </div>
            [/adminz_slider_custom_item_wrap]
        <?php
        }
        ?>
        [/adminz_slider_custom]

        <!-- gap -->
        [gap height="<?= esc_attr($gap) ?>px"]

        <!-- small -->
        [adminz_slider_custom
        class="<?= esc_attr($slides_class) ?> slide_small"
        slide_width="<?= esc_attr($thumbnails_width) ?>%"
        slide_width__md="<?= esc_attr($thumbnails_width__md) ?>%"
        slide_width__sm="<?= esc_attr($thumbnails_width__sm) ?>%"
        as_nav_for=".<?= esc_attr($slides_class) ?>"
        slide_item_padding="<?= ($gap / 2) ?>px"
        auto_slide="false"
        slide_align="left"
        bullets="false"
        ]
        <?php
        foreach ((array)$ids as $key => $id) {
        ?>
            [adminz_slider_custom_item_wrap]
            [ux_image
            id="<?= esc_attr($id) ?>"
            image_size="medium"
            height="<?= esc_attr($height) ?>"
            ]
            [/adminz_slider_custom_item_wrap]
        <?php
        }
        ?>
        [/adminz_slider_custom]
    </div>
    <?php

    if (wp_script_is('adminz-photoswipe-js', 'enqueued')) {

        // click to zoom
        add_filter('adminz_photoswipe_js', function ($args) use ($slides_class) {
            $args[] = [
                'key' => ".$slides_class",
                'value' => '.zoom-button',
            ];
            return $args;
        }, 10, 1);

        // click to image
        add_action('wp_footer', function () use ($slides_class) {
    ?>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function() {
                    document.querySelectorAll(".<?= esc_attr($slides_class) ?> img").forEach(img => {
                        img.addEventListener('click', () => {
                            const zoombutton = img.closest('.relative').querySelector('.zoom-button');
                            if (zoombutton) {
                                zoombutton.click();
                            }
                        });
                    });
                });
            </script>
<?php
        });
    }

    return do_shortcode(ob_get_clean());
};
$___->general_element();
