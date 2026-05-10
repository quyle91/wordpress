<?php

namespace Adminz\Helper;

class PosttypeClone {

    function __construct() {
        //
    }

    public $post_types;
    public $random_terms;
    public $numbers = 15;

    function init($post_types, $random_terms, $numbers) {

        //
        $this->post_types = $post_types;
        $this->random_terms = $random_terms;
        $this->numbers = $numbers;
        //
        $this->make_admin_links();
        $this->process_links();
    }
    
    function make_admin_links() {
        $func = function ($actions, $post) {
            $allowed_post_types = (array) $this->post_types;

            if (in_array($post->post_type, $allowed_post_types)) {
                $url = add_query_arg('adminz_post_clone', $post->ID, admin_url('edit.php?post_type=' . $post->post_type));
                $actions['clone'] = '<a href="' . esc_url($url) . '" >' . __('Copy') . '</a>';
            }

            return $actions;
        };

        add_filter('post_row_actions', $func, 10, 2);
        add_filter('page_row_actions', $func, 10, 2);
    }

    function process_links() {
        add_action('admin_init', function () {
            if ($post_id = ($_GET['adminz_post_clone'] ?? '')) {
                if (!$this->numbers) {
                    return;
                }

                // prepare
                $post = get_post($post_id);
                $post_title = $post->post_title;
                // echo "<pre>"; print_r($post); echo "</pre>";

                // titles
                $titles_array = $this->get_next_titles($post_title, $this->numbers, $post->post_type);
                if (empty($titles_array)) {
                    return;
                }

                foreach ((array)$titles_array as $key => $title) {

                    $data = (array)$post;

                    // 
                    unset($data['ID']);
                    $data['meta_input'] = [];
                    $data['tax_input'] = [];

                    // base post
                    if (!isset($data['meta_input']['_adminz_clone_base_id'])) {
                        $data['meta_input']['_adminz_clone_base_id'] = $post_id;
                    }

                    // title
                    $data['post_title'] = $title;

                    // status
                    $data['post_status'] = 'publish';

                    // meta_input
                    $all_meta = get_post_meta($post_id);
                    foreach ((array)$all_meta as $key => $meta) {
                        $excluded = ['_edit_last', '_edit_lock'];
                        if (in_array($key, $excluded)) continue;
                        $data['meta_input'][$key] = maybe_unserialize($meta[0]);
                    }

                    // same tax_input
                    if ($this->random_terms != 'on') {
                        $taxonomies = get_object_taxonomies($post->post_type);
                        foreach ((array)$taxonomies as $key => $taxonomy) {
                            $tax_input[$taxonomy] = [];
                            $terms = wp_get_object_terms($post_id, $taxonomy);
                            foreach ((array)$terms as $key => $term) {
                                $data['tax_input'][$taxonomy][] = $term->term_id;
                            }
                        }
                    }

                    // random tax_input
                    if ($this->random_terms == 'on') {
                        $taxonomies = get_object_taxonomies($post->post_type);
                        foreach ((array)$taxonomies as $key => $taxonomy) {
                            $tax_input[$taxonomy] = [];
                            $terms = get_terms(['taxonomy' => $taxonomy, 'hide_empty' => false]);
                            shuffle($terms);
                            $terms = array_slice($terms, 0, rand(1, 3));
                            foreach ((array)$terms as $key => $term) {
                                $data['tax_input'][$taxonomy][] = $term->term_id;
                            }
                        }
                    }
                    wp_insert_post($data);

                    // redirect
                    wp_redirect(admin_url('edit.php?post_type=' . $post->post_type));
                }

                $this->reset_posts_date($post);
            }
        });
    }

    function reset_posts_date($post) {
        global $wpdb;

        // get base post
        if ($base_id = get_post_meta($post->ID, '_adminz_clone_base_id', true)) {
            $post = get_post($base_id);
        }

        $post_title = $post->post_title;
        $post_date = $post->post_date; // giữ nguyên string từ DB
        $post_date_gmt = $post->post_date_gmt;

        // Tách phần base title (VD: "Bài học ABC 1" → "Bài học ABC")
        if (preg_match('/^(.*?)(\s(\d+))$/', $post_title, $matches)) {
            $base_title = trim($matches[1]);
        } else {
            $base_title = $post_title;
        }

        // Lấy tất cả các bài viết có tiêu đề bắt đầu với base_title
        $like_title = esc_sql($base_title . '%');
        $posts = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT ID, post_title FROM {$wpdb->posts}
                WHERE post_title LIKE %s 
                AND post_type = %s
                AND post_status IN ('publish', 'draft', 'pending', 'future', 'private')
                ORDER BY post_title ASC
                ",
                $like_title,
                $post->post_type
            )
        );

        // Cập nhật post_date và post_date_gmt với khoảng cách 1 giây
        error_log(__CLASS__ . '::' . __FUNCTION__ . '() $posts: ' . print_r($posts, true));
        foreach ($posts as $index => $p) {
            $new_post_date = date('Y-m-d H:i:s', strtotime($post_date) + 60 * $index);
            $new_post_date_gmt = date('Y-m-d H:i:s', strtotime($post_date_gmt) + 60 * $index);

            $wpdb->update(
                $wpdb->posts,
                [
                    'post_date' => $new_post_date,
                    'post_date_gmt' => $new_post_date_gmt,
                    'post_status' => 'publish',
                ],
                ['ID' => $p->ID]
            );

            clean_post_cache($p->ID);
        }
    }


    function get_next_titles($post_title, $numbers, $post_type = 'post') {
        $array = [];

        if (preg_match('/^(.*?)(\s(\d+))$/', $post_title, $matches)) {
            $base_title = trim($matches[1]); // phần chữ, ví dụ: "abc"
            $start_number = (int)$matches[3] + 1; // số bắt đầu, ví dụ: 21
        } else {
            $base_title = $post_title;
            $start_number = 1;
        }

        $current = $start_number;

        while (count($array) < $numbers) {
            $new_title = $base_title . ' ' . $current;

            // Dùng WP_Query để kiểm tra post tồn tại theo post_title
            $query = new \WP_Query([
                'post_type' => $post_type,
                'post_status' => 'any',
                'title' => $new_title,
                'posts_per_page' => 1,
                'fields' => 'ids',
            ]);

            if ($query->found_posts === 0) {
                $array[] = $new_title;
            }

            wp_reset_postdata();
            $current++;
        }

        return $array;
    }
}
