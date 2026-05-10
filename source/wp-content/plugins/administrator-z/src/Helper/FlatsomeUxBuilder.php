<?php

namespace Adminz\Helper;

class FlatsomeUxBuilder {
    public $post_id;
    public $post_type;
    public $template_block_id;
    public $taxonomy;
    public $tax_template_block_id;
    public $source;

    function __construct() {
        //
    }

    function init() {
    }

    function post_type_content_support() {
        if (!$this->post_type) {
            return;
        }

        if (function_exists('add_ux_builder_post_type')) {
            add_ux_builder_post_type($this->post_type);
        }

        $this->post_type_build_adminbar_link();
    }

    function taxonomy_layout_support() {
        if (!$this->taxonomy) {
            return;
        }

        $this->taxonomy_build_adminbar_link();
        $this->taxonomy_build_template();
        $this->term_taxonomy_meta_box();
    }

    function post_id_layout_support() {
        if (!$this->post_id) {
            return;
        }

        $this->post_id_layout_build_template();
        $this->post_id_layout_build_adminbar_link();
    }

    function post_type_layout_support() {
        if (!$this->post_type) {
            return;
        }

        $this->post_type_layout_build_template();
        $this->post_type_layout_build_adminbar_link();
        $this->post_meta_box();
    }

    function taxonomy_build_adminbar_link() {
        if (!$this->taxonomy) {
            return;
        }

        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            if (!is_archive()) {
                return;
            }

            $queried_object = get_queried_object();
            if (!isset($queried_object->taxonomy) or $queried_object->taxonomy !== $this->taxonomy) {
                return;
            }

            // find template_block_id
            $template_block_id = false;
            global $adminz;
            $taxonomy_layout_support = $adminz['Flatsome']->settings['taxonomy_layout_support'] ?? [];
            foreach ((array) $taxonomy_layout_support as $key => $value) {
                if ($value['key'] == $queried_object->taxonomy) {
                    $template_block_id = $value['value'];
                    break;
                }
            }

            if ($template_block_id) {
                $template_block_id = self::post_type_get_block_id($template_block_id);
                $wp_admin_bar->add_menu(array(
                    'parent' => 'edit',
                    'id'     => 'edit_uxbuilder',
                    'title'  => 'Edit ' . get_the_title($template_block_id) . 'with UX Builder',
                    'href'   => ux_builder_edit_url($template_block_id),
                ));
            }
        });
    }

    function post_type_build_adminbar_link() {
        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            global $post;
            if (!is_page() && !is_single()) {
                return;
            }
            if (!current_user_can('edit_post', $post->ID)) {
                return;
            }
            if (get_post_type() != $this->post_type) return;

            $wp_admin_bar->add_menu(array(
                'parent' => 'edit',
                'id'     => 'edit_uxbuilder',
                'title'  => 'Edit content with UX Builder',
                'href'   => ux_builder_edit_url($post->ID),
            ));
        });
    }

    function post_id_layout_build_adminbar_link() {
        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            global $post;
            if (!is_page() && !is_single()) {
                return;
            }
            if (!current_user_can('edit_post', $post->ID)) {
                return;
            }
            if (get_the_ID() != $this->post_id) return;

            $wp_admin_bar->add_menu(array(
                'parent' => 'edit',
                'id'     => 'edit_uxbuilder_product_layout',
                'title'  => 'Edit ' . get_the_title(str_replace('block_id_', '', $this->template_block_id)) . ' with UX Builder',
                'href'   => ux_builder_edit_url($post->ID, self::post_type_get_block_id($this->template_block_id)),
            ));
        });
    }

    function post_type_layout_build_adminbar_link() {
        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            global $post;
            if (!is_page() && !is_single()) {
                return;
            }
            if (!current_user_can('edit_post', $post->ID)) {
                return;
            }
            if (get_post_type() != $this->post_type) return;

            $wp_admin_bar->add_menu(array(
                'parent' => 'edit',
                'id'     => 'edit_uxbuilder_product_layout',
                'title'  => 'Edit ' . get_the_title(str_replace('block_id_', '', $this->template_block_id)) . ' with UX Builder',
                'href'   => ux_builder_edit_url($post->ID, self::post_type_get_block_id($this->template_block_id)),
            ));
        });
    }

    function taxonomy_build_template() {
        if (!$this->taxonomy) {
            return;
        }

        $filter_name = 'taxonomy_template';
        if ($this->taxonomy == 'category') {
            $filter_name = 'category_template';
        }

        add_filter($filter_name, function ($template, $type, $templates) {
            $queried_object = get_queried_object();
            if (($queried_object->taxonomy ?? '') == $this->taxonomy) {
                // find template_block_id
                $template_block_id = false;
                global $adminz;
                $taxonomy_layout_support = $adminz['Flatsome']->settings['taxonomy_layout_support'] ?? [];
                foreach ((array) $taxonomy_layout_support as $key => $value) {
                    if ($value['key'] == $queried_object->taxonomy) {
                        $template_block_id = $value['value'];
                        break;
                    }
                }
                if ($template_block_id = self::post_type_get_block_id($template_block_id)) {
                    $key = 'adminz_fl_term_block_id_' . $queried_object->term_id;
                    $GLOBALS[$key] = $template_block_id; // save block id to global
                    $source = $this->source;
                    $source = $source ? $source : 'index.php';
                    $template = ADMINZ_DIR . "includes/file/flatsome_taxonomy_templates/" . $source;
                }
            }
            return $template;
        }, 10, 3);
    }

    function post_id_layout_build_template() {
        if (!$this->post_id) {
            return;
        }

        add_filter('template_include', function ($template) {
            if (get_the_ID() == $this->post_id) {
                // find template_block_id
                $template_block_id = false;
                global $adminz;
                $post_id_template = $adminz['Flatsome']->settings['post_id_template'] ?? [];
                foreach ((array)$post_id_template as $key => $value) {
                    if ($value['key'] == $this->post_id) {
                        $template_block_id = $value['value'];
                        break;
                    }
                }

                if ($template_block_id = self::post_id_get_block_id($template_block_id)) {
                    $key = 'adminz_fl_single_block_id_' . get_the_ID();
                    $GLOBALS[$key] = $template_block_id;
                }

                //
                $source = $this->source;
                $source = $source ? $source : 'index.php';
                $template = ADMINZ_DIR . "includes/file/flatsome_post_id_templates/" . $source;
            }
            return $template;
        }, 10);
    }

    function post_type_layout_build_template() {
        if (!$this->post_type) {
            return;
        }

        add_filter('single_template', function ($template, $type, $templates) {
            if (is_single() && get_post_type() == $this->post_type) {
                // find template_block_id
                $template_block_id = false;
                global $adminz;
                $post_type_template = $adminz['Flatsome']->settings['post_type_template'] ?? [];
                foreach ((array)$post_type_template as $key => $value) {
                    if ($value['key'] == $this->post_type) {
                        $template_block_id = $value['value'];
                        break;
                    }
                }

                if ($template_block_id = self::post_type_get_block_id($template_block_id)) {
                    $key = 'adminz_fl_single_block_id_' . get_the_ID();
                    $GLOBALS[$key] = $template_block_id;
                    $source = $this->source;
                    $source = $source ? $source : 'index.php';
                    $template = ADMINZ_DIR . "includes/file/flatsome_post_type_templates/" . $source;
                }
            }
            return $template;
        }, 10, 3);
    }

    function save_custom_taxonomy_metabox($term_id) {
        if (isset($_POST['tax_template_block_id'])) {
            $custom_value = sanitize_text_field($_POST['tax_template_block_id']);
            update_term_meta($term_id, 'tax_template_block_id', $custom_value);
        }
    }

    function term_taxonomy_meta_box() {
        if (!$this->taxonomy) {
            return;
        }

        add_action("{$this->taxonomy}_add_form_fields", function () {
            ob_start();
?>
            <div class="form-field term-parent-wrap">
                <label for="parent">Layout block ID</label>
                <select name="tax_template_block_id" id="parent" class="postform" aria-describedby="parent-description">
                    <option value="">Inherit </option>
                    <?php
                    $block_posts = get_posts(array('post_type' => 'blocks', 'posts_per_page' => -1));
                    foreach ($block_posts as $block_post) {
                        echo '<option value="block_id_' . $block_post->ID . '">' . $block_post->post_title . '</option>';
                    }
                    ?>
                </select>
                <p id="parent-description">Uxbuilder Layout Support - Taxonomy</p>
            </div>
        <?php
            echo ob_get_clean();
        });

        add_action("{$this->taxonomy}_edit_form_fields", function ($term) {
            ob_start();
        ?>
            <tr class="form-field term-parent-wrap">
                <th scope="row">
                    <label for="parent">Layout block ID</label>
                </th>
                <td>
                    <select name="tax_template_block_id" id="parent" class="postform" aria-describedby="parent-description">
                        <option value="">Inherit</option>
                        <?php
                        $block_posts = get_posts(array('post_type' => 'blocks', 'posts_per_page' => -1));
                        foreach ($block_posts as $block_post) {
                            $selected = "";
                            if (get_term_meta($term->term_id, 'tax_template_block_id', true) == "block_id_" . $block_post->ID) {
                                $selected = "selected";
                            }
                            echo '<option ' . $selected . ' value="block_id_' . $block_post->ID . '">' . $block_post->post_title . '</option>';
                        }
                        ?>
                    </select>
                    <p id="parent-description">Uxbuilder Layout Support - Taxonomy</p>
                </td>
            </tr>
<?php
            echo ob_get_clean();
        });

        add_action('edited_term', [$this, 'save_custom_taxonomy_metabox']);
        add_action('create_term', [$this, 'save_custom_taxonomy_metabox']);
    }

    function post_meta_box() {
        if (!$this->post_type) {
            return;
        }
        $string   = $this->template_block_id;
        $taxonomy = str_replace("taxonomy_", "", $string);
        if ($taxonomy) {
            // Thêm select box vào trang thêm mới term của taxonomy "book_type"
            add_action($taxonomy . '_add_form_fields', function () use ($taxonomy) {
                echo '<div class="form-field">';
                echo '<label for="' . $taxonomy . '_block_id">UXBlock Template: ' . $this->post_type . '</label>';
                echo '<select id="' . $taxonomy . '_block_id" name="' . $taxonomy . '_block_id">';
                echo '<option value="0">--</option>';

                // Lấy danh sách bài viết từ post type 'block'
                $block_posts = get_posts(array('post_type' => 'blocks', 'posts_per_page' => -1));

                foreach ($block_posts as $block_post) {
                    echo '<option value="' . $block_post->ID . '">' . $block_post->post_title . '</option>';
                }

                echo '</select>';
                echo '</div>';
            });

            add_action('created_' . $taxonomy, function ($term_id) use ($taxonomy) {
                if (isset($_POST[$taxonomy . '_block_id'])) {
                    update_term_meta($term_id, $taxonomy . '_block_id', $_POST[$taxonomy . '_block_id']);
                }
            });

            // Thêm select box vào trang chỉnh sửa term của taxonomy "book_type"
            add_action($taxonomy . '_edit_form_fields', function ($term) use ($taxonomy) {
                $selected_block_id = get_term_meta($term->term_id, $taxonomy . '_block_id', true);

                // Lấy danh sách bài viết từ post type 'block'
                $block_posts = get_posts(array('post_type' => 'blocks', 'posts_per_page' => -1));

                echo '<tr class="form-field">';
                echo '<th scope="row" valign="top"><label for="' . $taxonomy . '_block_id">UXBlock Template: ' . $this->post_type . ':</label></th>';
                echo '<td>';
                echo '<select id="' . $taxonomy . '_block_id" name="' . $taxonomy . '_block_id">';
                echo '<option value="0">--</option>';

                foreach ($block_posts as $block_post) {
                    echo '<option value="' . $block_post->ID . '"';
                    if ($selected_block_id == $block_post->ID) {
                        echo ' selected';
                    }
                    echo '>' . $block_post->post_title . '</option>';
                }

                echo '</select>';
                echo '</td>';
                echo '</tr>';
            });

            // Lưu giá trị khi cập nhật term của taxonomy "book_type"
            add_action('edited_' . $taxonomy, function ($term_id) use ($taxonomy) {
                if (isset($_POST[$taxonomy . '_block_id'])) {
                    update_term_meta($term_id, $taxonomy . '_block_id', $_POST[$taxonomy . '_block_id']);
                }
            });
        }

        // edit post type
        add_action('add_meta_boxes', function () use ($taxonomy) {
            add_meta_box(
                'book_template_metabox',
                'UXBlock Template',
                function ($post) use ($taxonomy) {
                    $selected_block_id = get_post_meta($post->ID, 'template_block_id', true);

                    // Retrieve the list of "block" posts
                    $block_posts = get_posts(array('post_type' => 'blocks', 'posts_per_page' => -1));

                    echo '<p><label for="template_block_id">UXBlock Template</label><p>';
                    echo '<p><select id="template_block_id" name="template_block_id">';
                    echo '<option value="0">--</option>';

                    foreach ($block_posts as $block_post) {
                        echo '<option value="' . $block_post->ID . '"';
                        if ($selected_block_id == $block_post->ID) {
                            echo ' selected';
                        }
                        echo '>' . $block_post->post_title . '</option>';
                    }

                    echo '</select><p>';
                    echo '<p><small>Enabled by Administrator Z. Goto Tool/ Administratorz/ Flatsome/ Uxbuilder Layout Support</small><p>';
                },
                'book', // Custom post type
                'side',  // Where to display the metabox (e.g., 'normal', 'side', 'advanced')
                'default' // Priority
            );
        });

        // save metadata
        add_action('save_post', function ($post_id) use ($taxonomy) {
            if (get_post_type($post_id) == $this->post_type && current_user_can('edit_post', $post_id)) {
                if (isset($_POST['template_block_id'])) {
                    $selected_block_id = sanitize_text_field($_POST['template_block_id']);
                    update_post_meta($post_id, 'template_block_id', $selected_block_id);
                }
            }
        });
    }

    public static function taxonomy_get_block_id($template_block_id) {
        $queried_object = get_queried_object();

        // ghi đè giá trị
        if ($a = get_term_meta($queried_object->term_id, 'tax_template_block_id', true)) {
            $template_block_id = $a;
        }

        $a = str_replace('block_id_', '', $template_block_id);
        if ($a) {
            return $a;
        }
        return false;
    }

    // lấy block id theo setting từ options
    public static function post_id_get_block_id($template_block_id) {
        $return = (int) $template_block_id;
        global $post;

        // update_post_meta($post_id, 'template_block_id', $selected_block_id);
        if ($meta = get_post_meta($post->ID, 'template_block_id', true)) {
            $return = $meta;
            return $return;
        }

        // $_value = "taxonomy_".$_tax;
        if (strpos($template_block_id, "taxonomy_") === 0) {
            $taxonomy = str_replace("taxonomy_", "", $template_block_id);
            $_terms   = wp_get_post_terms($post->ID, $taxonomy);
            $_terms   = array_reverse($_terms);
            if (!empty($_terms) and is_array($_terms)) {
                foreach ($_terms as $kt => $term) {
                    //update_term_meta($term_id, $taxonomy.'_block_id', $_POST[$taxonomy.'_block_id']);				    	
                    if ($_block_id = get_term_meta($term->term_id, $taxonomy . '_block_id', true)) {
                        $return = $_block_id;
                        return $return;
                    }
                }
            }
        }

        // $_value = "block_id_".$block_id;
        if (strpos($template_block_id, "block_id_") === 0) {
            $return = str_replace("block_id_", "", $template_block_id);
            return $return;
        }
        return $return;
    }

    // lấy block id theo setting từ options
    public static function post_type_get_block_id($template_block_id) {
        $return = (int) $template_block_id;
        global $post;

        // update_post_meta($post_id, 'template_block_id', $selected_block_id);
        if ($meta = get_post_meta($post->ID, 'template_block_id', true)) {
            $return = $meta;
            return $return;
        }

        // $_value = "taxonomy_".$_tax;
        if (strpos($template_block_id, "taxonomy_") === 0) {
            $taxonomy = str_replace("taxonomy_", "", $template_block_id);
            $_terms   = wp_get_post_terms($post->ID, $taxonomy);
            $_terms   = array_reverse($_terms);
            if (!empty($_terms) and is_array($_terms)) {
                foreach ($_terms as $kt => $term) {
                    //update_term_meta($term_id, $taxonomy.'_block_id', $_POST[$taxonomy.'_block_id']);				    	
                    if ($_block_id = get_term_meta($term->term_id, $taxonomy . '_block_id', true)) {
                        $return = $_block_id;
                        return $return;
                    }
                }
            }
        }

        // $_value = "block_id_".$block_id;
        if (strpos($template_block_id, "block_id_") === 0) {
            $return = str_replace("block_id_", "", $template_block_id);
            return $return;
        }
        return $return;
    }
}
