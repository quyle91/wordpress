<?php

namespace Adminz\Helper;

class TermTaxonomy {

    function __construct() {
        //
    }

    function register_taxonomy($tax_name, $post_types = []) {
        if (!$tax_name) {
            return;
        }

        add_action('init', function () use ($tax_name, $post_types) {
            $taxonomy_args = [
                'labels' => [
                    'name' => $tax_name,
                    'singular_name' => $tax_name,
                    'search_items' => 'Search ' . $tax_name,
                    'all_items' => 'All' . $tax_name,
                    'parent_item' => 'Parent ' . $tax_name,
                    'parent_item_colon' => 'Parent ' . $tax_name . ':',
                    'edit_item' => 'Edit ' . $tax_name,
                    'update_item' => 'Update ' . $tax_name,
                    'add_new_item' => 'Add New ' . $tax_name,
                    'new_item_name' => 'New ' . $tax_name,
                    'menu_name' => $tax_name,
                ],
                'public' => true,
                'hierarchical' => true,
                'show_ui' => true,
                'show_admin_column' => true,
            ];

            register_taxonomy(sanitize_title($tax_name), $post_types, $taxonomy_args);
        }, 0);
    }

    function init_meta_key_column($taxonomy, $meta_key) {
        if ($taxonomy and $meta_key) {
            \WpDatabaseHelperV2\Meta\WpMeta::make()
                ->taxonomy($taxonomy)
                ->label(__FUNCTION__)
                ->fields(
                    [
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name($meta_key)
                            ->label(ucwords(str_replace('_', ' ', preg_replace('/[^a-zA-Z0-9_]/', '', $meta_key))))
                            ->adminColumn(true)
                            ->showInMetaBox(true)
                    ]
                )
                ->register();
        }
    }

    public $metakey;
    public $admin_column_key;
    function init_thumbnail($taxonomy, $metakey = 'thumbnail_id') {
        if ($taxonomy) {

            // prepare
            $this->metakey = $metakey;
            $this->admin_column_key = "adminz_{$taxonomy}_post_id";

            // input
            add_action($taxonomy . '_add_form_fields', [$this, 'thumbnail_field_in_add_term']);
            add_action($taxonomy . '_edit_form_fields', [$this, 'thumbnail_field_in_edit_term']);

            // Admin term columns
            add_filter('manage_edit-' . $taxonomy . '_columns', [$this, 'add_term_admin_column']);
            add_filter('manage_' . $taxonomy . '_custom_column', [$this, 'add_term_admin_column_value'], 10, 3);

            // save if have $_POST
            add_action('edit_' . $taxonomy, [$this, 'save_term_thumbnail_image'], 10, 1);
            add_action('create_' . $taxonomy, [$this, 'save_term_thumbnail_image'], 10, 1);
        }
    }

    function add_term_admin_column($columns) {
        $new_columns = array();
        foreach ($columns as $key => $value) {
            if ($key == 'name') {
                $new_columns[$this->admin_column_key] = _x('Featured image', 'page');
            }
            $new_columns[$key] = $value;
        }
        return $new_columns;
    }

    function add_term_admin_column_value($content, $column_name, $term_id) {
        if ($column_name == $this->admin_column_key) {
            if ($thumbnail_id = get_term_meta($term_id, $this->metakey, true)) {
                $content = wp_get_attachment_image(
                    $thumbnail_id,
                    'post-thumbnail',
                    false,
                    ["style" => "width: 50px; height: 50px;"]
                );
            } else {
                $content = "—";
            }
        }
        return $content;
    }

    function save_term_thumbnail_image($term_id) {
        if (isset($_POST['tempolary_field_name_here'])) {
            update_term_meta($term_id, $this->metakey, sanitize_text_field($_POST['tempolary_field_name_here']));
        }
    }

    function get_input_image_field($term) {
        $value = '';
        if ($term->term_id ?? '') {
            $value = get_term_meta($term->term_id, $this->metakey, true);
        }

        // text
        return \WpDatabaseHelperV2\Fields\WpField::make()
            ->kind('input')
            ->type('wp_media')
            ->name('tempolary_field_name_here')
            ->value($value)
            ->render();
    }

    function thumbnail_field_in_add_term($taxonomy) {
        $string = $this->get_input_image_field($taxonomy);
        $label = _x('Featured image', 'page');
        echo <<<HTML
		<div class="form-field">
			<label for="">
				$label
			</label>
			$string
		</div>
		HTML;
    }

    function thumbnail_field_in_edit_term($taxonomy) {
        $string = $this->get_input_image_field($taxonomy);
        $label = _x('Featured image', 'page');
        echo <<<HTML
		<tr class="form-field">
			<th scope="row" valign="top">
				$label
			</th>
			<td>
				$string
			</td>
		</tr>
		HTML;
    }
}
