<?php

namespace Adminz\Helper;

class Sidebar {
    function __construct() {
    }

    function register_sidebar($name) {
        register_sidebar(array(
            'name'          => $name,
            'id'            => sanitize_title($name),
            'description'   => '',
            'before_widget' => '<aside id="%1$s" class="widget %2$s">',
            'after_widget'  => '</aside>',
            'before_title'  => '<span class="widget-title"><span>',
            'after_title'   => '</span></span><div class="is-divider small"></div>',
        ));
    }

    function replace_sidebar() {
        add_action('init', function () {

            // only flatsome
            if (!adminz_is_flatsome()) {
                return;
            }

            $post_types = get_post_types(['public' => true], 'names');
            foreach ($post_types as $post_type) {
                add_action('add_meta_boxes', function () use ($post_type) {
                    add_meta_box(
                        "sidebar_select_{$post_type}",
                        'Adminz Replace Sidebar',
                        function ($post) {
                            $selected_sidebar = get_post_meta($post->ID, '_selected_sidebar', true);
                            $sidebars = $GLOBALS['wp_registered_sidebars'];

                            echo '<select name="selected_sidebar" id="selected_sidebar">';
                            echo '<option value="">-- ' . __('Select') . ' sidebar --</option>';
                            foreach ($sidebars as $id => $sidebar) {
                                echo '<option value="' . esc_attr($id) . '" ' . selected($selected_sidebar, $id, false) . '>';
                                echo esc_html($sidebar['name']);
                                echo '</option>';
                            }
                            echo '</select>';
                        },
                        $post_type,
                        'side',
                        'default'
                    );
                });
            }

            add_action('save_post', function ($post_id) {
                if (isset($_POST['selected_sidebar'])) {
                    update_post_meta($post_id, '_selected_sidebar', sanitize_text_field($_POST['selected_sidebar']));
                }
            });

            // replace sidebar
            add_filter('sidebars_widgets', function ($sidebars_widgets) {
                if (is_singular()) {
                    $post_id = get_the_ID();
                    if($selected_sidebar = get_post_meta($post_id, '_selected_sidebar', true)){
                        $sidebar = 'sidebar-main';
                        if (is_singular('product')) {
                            $sidebar = 'product-sidebar';
                        }
                        $sidebars_widgets[$sidebar] = $sidebars_widgets[$selected_sidebar];
                    }
                }

                return $sidebars_widgets;
            });
        });
    }
}
