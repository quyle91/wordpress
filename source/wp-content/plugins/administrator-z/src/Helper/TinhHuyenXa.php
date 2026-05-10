<?php

namespace Adminz\Helper;

class TinhHuyenXa {
    public $taxonomy = 'tinh_huyen_xa';
    public $fields = [
        'tinh' => "Custom: Tỉnh",
        'huyen' => "Custom: Huyện",
        'xa' => "Custom: Xã",
    ];

    function __construct() {
        //
    }

    function init() {
        $this->do_delete_tinh_huyen_xa();
        $this->do_import_tinh_huyen_xa();

        $this->register_taxonomy();

        add_filter('adminz_woo_search_field_types', [$this, 'add_woo_search_field_types']);
        add_action('adminz_woo_form_field', [$this, 'general_field_html'], 10, 2);
        add_filter('woocommerce_product_query_tax_query', [$this, 'apply_product_query']);

        add_action('wp_ajax_admiz_tinhhuyenxa_get_data', [$this, 'admiz_tinhhuyenxa_get_data']);
        add_action('wp_ajax_nopriv_admiz_tinhhuyenxa_get_data', [$this, 'admiz_tinhhuyenxa_get_data']); // ko cần đăng nhập
    }

    function apply_product_query($tax_query) {
        $tinh  = $_GET['tinh'] ?? "";
        $huyen = $_GET['huyen'] ?? "";
        $xa    = $_GET['xa'] ?? "";

        if (!$tinh and !$huyen and $xa) {
            return;
        }

        if ($xa) {
            $huyen = "";
        }

        if ($huyen) {
            $tinh = "";
        }

        if (!isset($tax_query['relation'])) {
            $tax_query['relation'] = 'AND';
        }

        if ($tinh) {
            $tax_query[] = array(
                'taxonomy'         => $this->taxonomy,
                'field'            => 'id',
                'terms'            => [$tinh],
                'operator'         => "IN",
                'include_children' => true,
            );
        }

        if ($huyen) {
            $tax_query[] = array(
                'taxonomy'         => $this->taxonomy,
                'field'            => 'id',
                'terms'            => [$huyen],
                'operator'         => "IN",
                'include_children' => true,
            );
        }

        if ($xa) {
            $tax_query[] = array(
                'taxonomy'         => $this->taxonomy,
                'field'            => 'id',
                'terms'            => [$xa],
                'operator'         => "IN",
                'include_children' => true,
            );
        }

        return $tax_query;
    }

    function add_woo_search_field_types($field_types) {
        foreach ($this->fields as $name => $label) {
            $field_types[$name] = $label;
        }
        return $field_types;
    }

    function general_field_html($type, $atts) {
        if (array_key_exists($type, $this->fields)) {
            echo call_user_func([$this, 'field_' . $type], $type, $atts);
        }
    }

    function field_tinh($type, $atts) {
        ob_start();
?>
        <select name="tinh" id="">
            <option value=""> <?= __("Select") ?> <?= strtolower(str_replace("Custom: ", "", $this->fields[$type])) ?> </option>
            <?php
            $taxonomy = $this->taxonomy;
            foreach (get_terms(['taxonomy' => $taxonomy, 'parent' => 0, 'hide_empty' => false, 'orderby' => 'name', 'order' => 'ASC']) as $key => $term) {
                $selected = (($_GET[$type] ?? "") == $term->term_id) ? "selected" : "";
            ?>
                <option <?= esc_attr($selected) ?> value="<?= esc_attr($term->term_id) ?>">
                    <?= esc_attr($term->name) ?>
                </option>
            <?php
            }
            ?>
        </select>
    <?php
        return ob_get_clean();
    }

    function field_huyen($type, $atts) {
        ob_start();
    ?>

        <select name="huyen" id="">
            <option value=""> <?= __("Select") ?> <?= strtolower(str_replace("Custom: ", "", $this->fields[$type])) ?> </option>
            <?php
            if ($tinh = ($_GET['tinh'] ?? "")) {
                $terms = $this->get_term_child($tinh);
                foreach ($terms as $key => $term) {
                    $selected = (($_GET[$type] ?? "") == $term->term_id) ? "selected" : "";
            ?>
                    <option <?= esc_attr($selected) ?> value="<?= esc_attr($term->term_id) ?>">
                        <?= esc_attr($term->name) ?>
                    </option>
            <?php
                }
            }
            ?>
        </select>
    <?php
        return ob_get_clean();
    }

    function field_xa($type, $atts) {
        ob_start();
    ?>
        <select name="xa" id="">
            <option value=""> <?= __("Select") ?> <?= strtolower(str_replace("Custom: ", "", $this->fields[$type])) ?> </option>
            <?php
            if ($huyen = ($_GET['huyen'] ?? "")) {
                $terms = $this->get_term_child($huyen);
                foreach ($terms as $key => $term) {
                    $selected = (($_GET[$type] ?? "") == $term->term_id) ? "selected" : "";
            ?>
                    <option <?= esc_attr($selected) ?> value="<?= esc_attr($term->term_id) ?>">
                        <?= esc_attr($term->name) ?>
                    </option>
            <?php
                }
            }
            ?>
        </select>
    <?php
        return ob_get_clean();
    }



    function maybe_add_term($name, $parent_id = false) {
        $taxonomy = $this->taxonomy;

        $term = term_exists($name, $taxonomy, $parent_id);
        if ($term) {
            return $term;
        }

        // Prepare the term data
        $args = array();
        if ($parent_id) {
            $args['parent'] = $parent_id;
        }

        // Insert the term into the taxonomy
        $term = wp_insert_term($name, $taxonomy, $args);

        // Check for errors
        if (is_wp_error($term)) {
            return false;
        }
        return $term;
    }

    function term_row($i, $name, $term, $number) {
        $link = "<a target='blank' href='" . get_edit_term_link($term['term_id'], $this->taxonomy) . "'>" . $name . "</a>";
        ob_start();
    ?>
        <tr>
            <td>
                <?= $i ?>
            </td>
            <td>
                <?php
                for ($i = 0; $i < $number; $i++) {
                    echo  "---";
                }
                ?>
                <?= wp_kses_post($name) ?>
            </td>
            <td>
                <?= wp_kses_post($link) ?>
            </td>
        </tr>
<?php
        return ob_get_clean();
    }

    function do_delete_tinh_huyen_xa() {
        add_action('init', function () {
            if (isset($_GET['do_delete_tinh_huyen_xa'])) {
                if (current_user_can('administrator')) {
                    $taxonomy = $this->taxonomy;
                    if (!taxonomy_exists($taxonomy)) {
                        return;
                    }

                    $terms = get_terms(array(
                        'taxonomy'   => $taxonomy,
                        'hide_empty' => false,
                    ));

                    if (is_wp_error($terms)) {
                        return;
                    }

                    foreach ($terms as $term) {
                        wp_delete_term($term->term_id, $taxonomy);

                        // Clear the object cache to free up memory
                        wp_cache_flush();
                    }
                    die;
                }
            }
        });
    }

    function do_import_tinh_huyen_xa() {
        add_action('init', function () {
            if (isset($_GET['do_import_tinh_huyen_xa'])) {
                if (current_user_can('administrator')) {
                    // code here
                    echo '<table style="width: 500px;">';
                    $list_tinh = require_once(ADMINZ_DIR . "includes/file/data_tinh_huyen_xa.php");
                    $i         = 1;
                    foreach ($list_tinh as $kt => $tinh) {
                        $ten_tinh = $tinh['ten_tinh'];
                        if ($term_tinh = $this->maybe_add_term($ten_tinh)) {
                            echo $this->term_row($i, $ten_tinh, $term_tinh, 0);
                            $i++;

                            $list_huyen = $tinh['huyen'];
                            foreach ($list_huyen as $kh => $huyen) {
                                $ten_huyen = $huyen['ten_huyen'];
                                if ($term_huyen = $this->maybe_add_term($ten_huyen, $term_tinh['term_id'])) {
                                    echo $this->term_row($i, $ten_huyen, $term_huyen, 1);
                                    $i++;

                                    $list_xa = $huyen['xa'];
                                    foreach ($list_xa as $xa) {
                                        $ten_xa = $xa['ten_xa'];
                                        if ($term = $this->maybe_add_term($ten_xa, $term_huyen['term_id'])) {
                                            echo $this->term_row($i, $ten_xa, $term, 2);
                                            $i++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    echo '</table>';
                    die;
                }
            }
        });
    }

    function get_term_child($parent) {
        $args = [
            'parent'     => esc_attr($parent),
            'hide_empty' => false,
            'taxonomy'   => $this->taxonomy,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ];
        $terms = get_terms($args);
        return $terms;
    }

    function admiz_tinhhuyenxa_get_data() {
        if (!wp_verify_nonce($_POST['nonce'], 'adminz_js')) exit;
        $terms = $this->get_term_child($_POST['parent']);
        if (!is_wp_error($terms)) {
            wp_send_json_success($terms);
            wp_die();
        }

        wp_send_json_error('Error');
        wp_die();
    }

    function register_taxonomy() {
        $labels = [
            "name"          => esc_html__("Tỉnh, huyện, xã"),
            "singular_name" => esc_html__("Tỉnh, huyện, xã"),
        ];

        $args = [
            "label"                 => esc_html__("Tỉnh, huyện, xã"),
            "labels"                => $labels,
            "public"                => true,
            "publicly_queryable"    => true,
            "hierarchical"          => true,
            "show_ui"               => true,
            "show_in_menu"          => true,
            "show_in_nav_menus"     => true,
            "query_var"             => true,
            "rewrite"               => false, // for rewrite, please add_action init
            "show_admin_column"     => true,
            "show_in_rest"          => true,
            "show_tagcloud"         => false,
            "rest_base"             => "tinh_huyen_xa",
            "rest_controller_class" => "WP_REST_Terms_Controller",
            "rest_namespace"        => "wp/v2",
            "show_in_quick_edit"    => true,
            "sort"                  => false,
            "show_in_graphql"       => false,
        ];
        register_taxonomy("tinh_huyen_xa", ["product"], $args);
    }

    function import() {
    }
}
