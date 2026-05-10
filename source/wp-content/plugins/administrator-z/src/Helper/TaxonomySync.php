<?php
/*
Khi insert 1 post thì tạo ra 1 term tương ứng
updated: 22-6-2023
*/

// THAM KHẢO CODE NÀY.


namespace Adminz\Helper;

class TaxonomySync {

    // required
    public $taxonomy = '';
    public $post_type = '';

    function __construct($post_type, $taxonomy) {
        //
        if (empty($post_type) || empty($taxonomy)) {
            return;
        }

        //
        $this->post_type = $post_type;
        $this->taxonomy = $taxonomy;

        // action hooks post type
        add_action('wp_after_insert_post', [$this, 'update_tax_by_post_type'], 100, 3);
        add_action('before_delete_post', [$this, 'delete_tax_by_post_type'], 10, 1);
    }

    public function update_tax_by_post_type($post_id, $post, $update) {
        // chỉ xử lý đúng post type
        if ($post->post_type !== $this->post_type) {
            return;
        }

        // bỏ autosave
        if (wp_is_post_autosave($post_id)) {
            return;
        }

        // bỏ revision
        if (wp_is_post_revision($post_id)) {
            return;
        }

        // error_log('==============================');
        // error_log(__CLASS__ . '::update_record_by_post_type');
        // error_log('Post ID: ' . $post_id);
        // error_log('Post Status: ' . $post->post_status);
        // error_log('Is Update: ' . ($update ? 'true' : 'false'));

        // nếu publish → giả lập create/update
        if ($post->post_status === 'publish') {
            // create taxonomy term
            $this->upsert_taxonomy_term($post_id);
        } else {
            // delete taxonomy term
            $this->delete_taxonomy_term($post_id);
        }
    }

    public function delete_tax_by_post_type($post_id) {
        $post = get_post($post_id);

        if (!$post) {
            return;
        }

        if ($post->post_type !== $this->post_type) {
            return;
        }

        // error_log('==============================');
        // error_log(__CLASS__ . '::delete_tax_by_post_type');
        // error_log('Post ID: ' . $post_id);

        // delete taxonomy term
        $this->delete_taxonomy_term($post_id);
    }

    public function exists_taxonomy_term($post_id) {
        // Lấy slug của post
        $post_slug = get_post_field('post_name', $post_id);

        // Xoá hậu tố __trashed hoặc __trashed-2, __trashed-3...
        $pattern = '/__trashed(-[0-9]+)?$/';
        $post_slug_formated = preg_replace($pattern, '', $post_slug);

        // Tìm term theo slug đã xử lý
        $term = get_term_by(
            'slug',
            $post_slug_formated,
            $this->taxonomy
        );

        // Nếu tìm thấy term hợp lệ thì return object term
        if ($term && !is_wp_error($term)) {
            return $term;
        }

        // Không tìm thấy
        return false;
    }

    public function create_taxonomy_term($post_id) {
        //
        $post_title = get_the_title($post_id);
        $post_slug = get_post_field('post_name', $post_id);

        $args = [
            'name' => $post_title,
            'slug' => $post_slug,
            'description' => '',
        ];
        return wp_insert_term($post_title, $this->taxonomy, $args);
    }

    public function update_taxonomy_term($post_id, $term_exits) {
        //
        $post_title = get_the_title($post_id);
        $post_slug = get_post_field('post_name', $post_id);

        $args = [
            'name' => $post_title,
            'slug' => $post_slug,
            'description' => '',
        ];
        return wp_update_term($term_exits->term_id, $this->taxonomy, $args);
    }

    public function upsert_taxonomy_term($post_id) {
        //
        $term_exits = $this->exists_taxonomy_term($post_id);
        if ($term_exits) {
            $this->update_taxonomy_term($post_id, $term_exits);
        } else {
            $this->create_taxonomy_term($post_id);
        }
    }

    public function delete_taxonomy_term($post_id) {
        //
        $term_exits = $this->exists_taxonomy_term($post_id);
        if ($term_exits) {
            wp_delete_term($term_exits->term_id, $this->taxonomy);
        }
        
    }
}
