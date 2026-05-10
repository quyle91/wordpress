<?php

namespace Adminz\Widget;

class Adminz_Taxonomies extends \WP_Widget {

    public function __construct() {
        // .widget_product_categories ul a: see flatsomePjax 
        $widget_ops = array(
            'classname'                   => 'widget_taxonomies adminz_widget_taxonomies widget_product_categories',
            'description'                 => __('A list or dropdown of any taxonomy.'),
            'customize_selective_refresh' => true,
            'show_instance_in_rest'       => true,
        );
        parent::__construct('adminz_taxonomies', __('Adminz Taxonomies'), $widget_ops);

        add_filter('category_css_class', [$this, '__fix_category_current_for_url_params'], 10, 4);

        // ajax
        add_action('wp_ajax_adminz_widget_taxonomies_get_count', [$this, '__ajax_count']);
        add_action('wp_ajax_nopriv_adminz_widget_taxonomies_get_count', [$this, '__ajax_count']);

        // shortcode
        add_shortcode('adminz_taxonomies_active_filters', [$this, '__shortcode_active_filters']);
    }

    public function widget($args, $instance) {
        static $first_dropdown = true;

        $default_title = __('Taxonomies');
        $title         = !empty($instance['title']) ? $instance['title'] : $default_title;
        $title         = apply_filters('widget_title', $title, $instance, $this->id_base);

        $taxonomy     = !empty($instance['taxonomy']) ? $instance['taxonomy'] : 'category';
        // $count        = !empty($instance['count']) ? '1' : '0';
        $hierarchical = !empty($instance['hierarchical']) ? '1' : '0';
        $dropdown     = !empty($instance['dropdown']) ? '1' : '0';
        $dropdown_placeholder_prefix     = !empty($instance['dropdown_placeholder_prefix']) ? '1' : '0';

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . $title . $args['after_title'];
        }

        wp_enqueue_script(
            'adminz_widget_taxonomies',
            ADMINZ_DIR_URL . 'assets/js/adminz-widget-taxonomies.js',
            [],
            ADMINZ_VERSION,
            true
        );

        $tax_args = array(
            'taxonomy'     => $taxonomy,
            'orderby'      => 'name',
            'show_count'   => 0, // chuyển qua ajax và transient 
            'hierarchical' => $hierarchical,
            'hide_empty' => true,
        );

        if ($dropdown) {
            $form_url = home_url();

            // woo
            if (in_array($taxonomy, get_object_taxonomies('product'))) {
                $page_shop_id = wc_get_page_id('shop');
                $form_url = get_permalink($page_shop_id);
            }

            echo $this->__maybe_custom_open_tag(
                sprintf('<form action="%s" method="get">', $form_url),
                $instance
            );
            $dropdown_id    = ($first_dropdown) ? 'tax' : "{$this->id_base}-dropdown-{$this->number}";
            $first_dropdown = false;

            echo '<label class="screen-reader-text" for="' . esc_attr($dropdown_id) . '">' . $title . '</label>';

            $terms = get_terms($tax_args);
            $taxonomy_name = $this->__fix_taxonomy($taxonomy);
            $placeholder_text = get_taxonomy($taxonomy)->labels->singular_name ?? '';
            if ($dropdown_placeholder_prefix) {
                $placeholder_text = __('Select') . ' ' . strtolower(get_taxonomy($taxonomy)->labels->name ?? '');
            }

            echo '<select id="' . esc_attr($dropdown_id) . '" name="' . $taxonomy_name . '">';
            echo '<option value="" class="">' . $placeholder_text . '</option>';
            foreach ($terms as $term) {
                $selected = selected(get_query_var($taxonomy_name) ?? '', $term->slug, false);
                echo '<option class="cat-item-' . $term->term_id  . '" id="term-' . esc_attr($term->term_id) . '" value="' . esc_attr($term->slug) . '" ' . $selected . '>';
                echo esc_html($term->name);
                echo '</option>';
            }

            echo '</select>';
            echo adminz_wc_query_string_form_fields(null, ['paged', $taxonomy_name], '', true);
            echo '</form>';
        } else {
            add_filter('term_link', [$this, '__change_term_link'], 10, 3);
            $format = current_theme_supports('html5', 'navigation-widgets') ? 'html5' : 'xhtml';
            $format = apply_filters('navigation_widgets_format', $format);

            if ('html5' === $format) {
                $title      = trim(strip_tags($title));
                $aria_label = $title ? $title : $default_title;
                echo '<nav aria-label="' . esc_attr($aria_label) . '">';
            }

            echo $this->__maybe_custom_open_tag(
                '<ul>',
                $instance
            );
            $tax_args['title_li'] = '';
            wp_list_categories(apply_filters('widget_taxonomies_args', $tax_args, $instance));
            echo '</ul>';

            if ('html5' === $format) {
                echo '</nav>';
            }
            remove_filter('term_link', [$this, '__change_term_link']);
        }

        echo $args['after_widget'];
    }

    public function update($new_instance, $old_instance) {
        echo "<pre>";
        print_r($new_instance);
        echo "</pre>";
        $instance                 = $old_instance;
        $instance['title']        = sanitize_text_field($new_instance['title']);
        $instance['taxonomy']     = sanitize_text_field($new_instance['taxonomy']);
        $instance['count']        = !empty($new_instance['count']) ? 1 : 0;
        $instance['hierarchical'] = !empty($new_instance['hierarchical']) ? 1 : 0;
        $instance['dropdown']     = !empty($new_instance['dropdown']) ? 1 : 0;
        $instance['dropdown_placeholder_prefix']     = !empty($new_instance['dropdown_placeholder_prefix']) ? 1 : 0;

        return $instance;
    }

    public function form($instance) {
        $instance     = wp_parse_args(
            (array) $instance,
            [
                'title' => '',
                'taxonomy' => 'category'
            ]
        );
        $taxonomies   = get_taxonomies(
            [
                // 'public' => true
            ],
            'objects'
        );

        // echo "<pre>"; print_r($taxonomies); echo "</pre>"; die;
        $count        = isset($instance['count']) ? (bool) $instance['count'] : false;
        $hierarchical = isset($instance['hierarchical']) ? (bool) $instance['hierarchical'] : false;
        $dropdown     = isset($instance['dropdown']) ? (bool) $instance['dropdown'] : false;
        $dropdown_placeholder_prefix     = isset($instance['dropdown_placeholder_prefix']) ? (bool) $instance['dropdown_placeholder_prefix'] : false;
?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>"
                name="<?php echo $this->get_field_name('title'); ?>" type="text"
                value="<?php echo esc_attr($instance['title']); ?>" />
        </p>

        <p>
            <label for="<?php echo $this->get_field_id('taxonomy'); ?>"><?php _e('Taxonomy:'); ?></label>
            <select id="<?php echo $this->get_field_id('taxonomy'); ?>"
                name="<?php echo $this->get_field_name('taxonomy'); ?>" class="widefat">
                <?php foreach ($taxonomies as $taxonomy) : ?>
                    <option value="<?php echo esc_attr($taxonomy->name); ?>" <?php selected($instance['taxonomy'], $taxonomy->name); ?>>
                        <?php echo esc_html($taxonomy->labels->name); ?>
                        <?php echo $taxonomy->public ? '' : '(private)'; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown'); ?>"
                name="<?php echo $this->get_field_name('dropdown'); ?>" <?php checked($dropdown); ?> />
            <label for="<?php echo $this->get_field_id('dropdown'); ?>"><?php _e('Display as dropdown'); ?></label>
            <br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('dropdown_placeholder_prefix'); ?>"
                name="<?php echo $this->get_field_name('dropdown_placeholder_prefix'); ?>" <?php checked($dropdown_placeholder_prefix); ?> />
            <label for="<?php echo $this->get_field_id('dropdown_placeholder_prefix'); ?>">— <?php _e('Placeholder with prefix'); ?></label>
            <br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('count'); ?>"
                name="<?php echo $this->get_field_name('count'); ?>" <?php checked($count); ?> />
            <label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Show post counts'); ?></label>
            <br />

            <input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('hierarchical'); ?>"
                name="<?php echo $this->get_field_name('hierarchical'); ?>" <?php checked($hierarchical); ?> />
            <label for="<?php echo $this->get_field_id('hierarchical'); ?>"><?php _e('Show hierarchy'); ?></label>
        </p>
        <p>
            <small>
                Actived filters shortcode: <strong>[adminz_taxonomies_active_filters]</strong>
            </small>
        </p>
<?php
        if ($instance['taxonomy'] ?? '') {
            if ((get_taxonomy($instance['taxonomy'])->publicly_queryable) != 1) {
                echo <<<HTML
                <p>
                    <small>
                        <mark>
                            <strong>Warning:</strong> This taxonomy is not publicly queryable and maybe not working.
                        </mark>
                    </small>
                </p>
                HTML;
            }
        }
        return '';
    }

    function __fix_taxonomy($taxonomy) {
        if (str_starts_with($taxonomy, 'pa_')) {
            return str_replace('pa_', 'filter_', $taxonomy);
        }
        return $taxonomy;
    }

    function __maybe_custom_open_tag($openTag, $instance) {
        global $wp_query;

        $domAttributes = [
            'class' => 'widget-content',
        ];

        if (!empty($instance['taxonomy'])) {
            $domAttributes['class'] .= ' taxonomy-' . esc_attr($instance['taxonomy']);
        }

        if (!empty($instance['count']) && $instance['count'] == 1) {
            $domAttributes['class'] .= ' adminz_count_transient';

            // Chỉ có 3 loại này thôi
            $domAttributes['data-woocommerce-tax_query']  = json_encode($wp_query->tax_query->queries ?? []);
            $domAttributes['data-woocommerce-meta_query'] = json_encode($wp_query->meta_query->queries ?? []);
            $domAttributes['data-woocommerce-date_query'] = json_encode($wp_query->date_query->queries ?? []);

            //
            $term_ids = array_map(function ($term) {
                return $term->term_id;
            }, get_terms(['taxonomy' => $instance['taxonomy'] ?? '']));
            $domAttributes['data-adminz-term_ids']  = json_encode($term_ids);
        }

        // Xóa khoảng trắng dư thừa ở class
        $domAttributes['class'] = trim($domAttributes['class']);

        // Chuyển mảng thuộc tính thành chuỗi HTML
        $domAttributeString = '';
        foreach ($domAttributes as $key => $value) {
            if ($value !== '') {
                $domAttributeString .= sprintf(' %s="%s"', $key, esc_attr($value));
            }
        }

        $openTag = preg_replace(
            '/<([a-zA-Z]+)([^>]*)>/',
            '<$1$2' . $domAttributeString . '>',
            $openTag,
            1
        );

        return $openTag;
    }

    function __ajax_count() {
        if (!wp_verify_nonce($_POST['nonce'], 'adminz_js')) exit;
        $return = [
            'widget_count' => 0
        ];

        // code here
        $woo_tax_query  = json_decode(stripslashes_deep($_POST['woo_tax_query']), true);
        $woo_meta_query = json_decode(stripslashes_deep($_POST['woo_meta_query']), true);
        $woo_date_query = json_decode(stripslashes_deep($_POST['woo_date_query']), true);

        $term_ids     = json_decode(stripslashes_deep($_POST['term_ids']));

        foreach ((array) $term_ids as $key => $term_id) {
            $count              = adminz_term_count(
                $term_id,
                '',
                $woo_tax_query,
                $woo_meta_query,
                $woo_date_query,
            );
            $return[$term_id] = $count;
            $return['widget_count'] += $count;
        }

        if (!$return) {
            wp_send_json_error('Error');
            wp_die();
        }

        wp_send_json_success($return);
        wp_die();
    }

    // tạm thời chỉ áp dụng cho woo
    function __change_term_link($termlink, $term, $taxonomy) {
        if (adminz_flatsome_is_shop_archive()) {
            global $wp_query;
            if (!empty($wp_query->query)) {
                $query_vars = $wp_query->query;

                // thêm hoặc xoá $term hiện tại
                if (($query_vars[$taxonomy] ?? '') == $term->slug) {
                    unset($query_vars[$taxonomy]);
                } else {
                    $taxonomy = $this->__fix_taxonomy($taxonomy);
                    $query_vars[$taxonomy] = $term->slug;
                }

                $page_shop_id = wc_get_page_id('shop');
                return add_query_arg(
                    $query_vars,
                    get_permalink($page_shop_id)
                );
            }
        }
        return $termlink;
    }

    function __fix_category_current_for_url_params($css_classes, $term, $depth, $args) {
        // kiêm tra có url param của term này ko
        if ($term->taxonomy ?? '') {
            $taxonomy = $term->taxonomy;
            if (($_GET[$taxonomy] ?? '') == $term->slug) {
                $css_classes[] = 'current-cat';
            }
        }

        return $css_classes;
    }

    function __shortcode_active_filters() {
        if (empty($_GET)) {
            return;
        }

        ob_start();
        echo '<div class="adminz-active-filter tagcloud">';
        foreach ((array) $_GET as $taxonomy => $term_slug) {
            $term = get_term_by('slug', $term_slug, $taxonomy);
            if ($term) {
                $href = remove_query_arg($taxonomy);
                $name = $term->name;
                echo <<<HTML
                <a href="$href">
                    x
                    $name
                </a>
                HTML;
            }
        }

        echo '</div>';
    }
}
