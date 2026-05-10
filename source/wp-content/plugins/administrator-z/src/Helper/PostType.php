<?php

namespace Adminz\Helper;

class PostType {
    public $admin_column_key;

    function __construct() {
        //
    }

    function register_post_type($post_type, $args = []) {
        if (!$post_type) {
            return;
        }


        add_action('init', function () use ($post_type) {
            $labels = [
                'name' => $post_type,
                'singular_name' => $post_type,
                'menu_name' => $post_type,
                'name_admin_bar' => $post_type,
                'add_new' => 'Add New',
                'add_new_item' => 'Add New ' . $post_type,
                'new_item' => 'New ' . $post_type,
                'edit_item' => 'Edit ' . $post_type,
                'view_item' => 'View ' . $post_type,
                'all_items' => 'All ' . $post_type,
                'search_items' => 'Search ' . $post_type,
                'not_found' => 'No ' . $post_type . ' found.',
                'not_found_in_trash' => 'No ' . $post_type . ' found in Trash.',
            ];

            if (empty($args)) {
                $args = [
                    'labels' => $labels,
                    'public' => true,
                    'has_archive' => true,
                    'publicly_queryable' => true,
                    // 'rewrite'=> ['slug' => sanitize_title($post_type)],
                    'show_in_rest' => true,
                    'supports' => [
                        'title',
                        'editor',
                        'author',
                        'thumbnail',
                        'excerpt',
                        // 'trackbacks',
                        // 'custom-fields',
                        'comments',
                        'revisions',
                        'page-attributes',
                        'post-formats',
                    ],
                    'taxonomies' => ['post_tag'],
                ];
            }

            register_post_type(sanitize_title($post_type), $args);
        }, 0);
    }

    function init_thumbnail($post_type) {
        if ($post_type) {

            // prepare
            $this->admin_column_key = "adminz_{$post_type}_post_id";

            // Add columns and custom content for admin
            add_filter("manage_{$post_type}_posts_columns", function ($columns) {
                $new_columns = array();
                foreach ($columns as $key => $value) {
                    if ($key == 'title') {
                        $new_columns[$this->admin_column_key] = '<span class="dashicons dashicons-format-image"></span>';
                    }
                    $new_columns[$key] = $value;
                }
                return $new_columns;
            });
            add_action("manage_{$post_type}_posts_custom_column", function ($column, $post_id) {
                if ($column === $this->admin_column_key) {
                    $thumbnail = get_the_post_thumbnail($post_id, 'thumbnail', ['style' => 'width: 50px; height: 50px;']);
                    if ($thumbnail) {
                        echo $thumbnail;
                    }
                }
            }, 10, 2);
        }
    }

    function init_meta_key_column($post_type, $meta_key) {
        if ($post_type and $meta_key) {
            \WpDatabaseHelperV2\Meta\WpMeta::make()
                ->post_type($post_type)
                ->label(__FUNCTION__)
                ->fields(
                    [
                        \WpDatabaseHelperV2\Fields\WpField::make()
                            ->kind('input')
                            ->type('text')
                            ->name($meta_key)
                            ->label(ucwords(str_replace('_', ' ', preg_replace('/[^a-zA-Z0-9_]/', '', $meta_key))))
                            ->adminColumn(true)
                            ->showInMetaBox(false)
                    ]
                )
                ->register();
        }
    }
}
