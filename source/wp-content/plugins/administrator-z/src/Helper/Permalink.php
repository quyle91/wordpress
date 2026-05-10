<?php

namespace Adminz\Helper;

class Permalink {
    public $post_types = [];
    public function post_types($post_types) {
        $this->post_types = $post_types;
    }

    public $taxonomies = [];
    public function taxonomies($taxonomies) {
        $this->taxonomies = $taxonomies;
    }

    function add_post_type_rewrite_func($post) {
        // 2 ký tự language prefix trên url
        add_rewrite_rule(
            '^([a-z]{2}/)?' . preg_quote($post->post_name, '/') . '/?$',
            'index.php?adminz_rw_cpt_slug=' . $post->post_name,
            'top'
        );
    }

    function add_taxonomy_term_rewrite_func($term) {
        // 2 ký tự language prefix trên url
        add_rewrite_rule(
            '^([a-z]{2}/)?' . preg_quote($term->slug, '/') . '/?$',
            'index.php?adminz_rw_tax_slug=' . $term->slug,
            'top'
        );
    }

    function run() {

        // Đăng ký query var adminz_rw_cpt_slug
        add_filter('query_vars', function ($vars) {
            //
            if (!empty($this->post_types)) {
                $vars[] = 'adminz_rw_cpt_slug';
            }

            //
            if (!empty($this->taxonomies)) {
                $vars[] = 'adminz_rw_tax_slug';
            }
            return $vars;
        });

        // mỗi lần load page thì tạo rewrite rule
        add_action('init', function () {

            // 
            foreach ($this->post_types as $post_type) {
                $posts = get_posts([
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                    'post_status' => 'publish',
                ]);

                foreach ($posts as $post) {
                    $this->add_post_type_rewrite_func($post);
                }
            }

            // 
            foreach ($this->taxonomies as $taxonomy) {
                $terms = get_terms([
                    'taxonomy' => $taxonomy,
                    'hide_empty' => false,
                ]);

                foreach ($terms as $term) {
                    $this->add_taxonomy_term_rewrite_func($term);
                }
            }
        });

        // Mỗi lần save post thì cũng add rewrite rule
        add_action('save_post', function ($postId, $post) {
            if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
                return;
            }

            if (!in_array($post->post_type, $this->post_types, true)) {
                return;
            }

            // 
            if (empty($post->post_name)) {
                return;
            }

            // THÊM REWRITE RULE NGAY LẬP TỨC
            $this->add_post_type_rewrite_func($post);

            // comment: flush only when slug changed
            if (did_action('save_post') === 1) {
                flush_rewrite_rules(false);
            }
        }, 10, 2);

        add_action('saved_term', function ($termId, $ttId, $taxonomy) {
            if (!in_array($taxonomy, $this->taxonomies, true)) {
                return;
            }

            $term = get_term($termId, $taxonomy);
            if (empty($term) || is_wp_error($term)) {
                return;
            }

            $this->add_taxonomy_term_rewrite_func($term);

            // comment: flush rewrite rules only once per request
            if (did_action('saved_term') === 1) {
                flush_rewrite_rules(false);
            }
        }, 10, 3);

        // sử dụng query var adminz_rw_cpt_slug để cho wp biết đây là post nào.
        add_action('pre_get_posts', function ($query) {

            // 
            if (is_admin()) {
                return;
            }

            // kiểm tra là main query
            if (!$query->is_main_query()) {
                return;
            }

            // adminz_rw_cpt_slug
            $slug = $query->get('adminz_rw_cpt_slug');
            if (!empty($slug)) {
                // 
                foreach ($this->post_types as $post_type) {
                    $post = get_page_by_path($slug, OBJECT, $post_type);

                    if (empty($post)) {
                        continue;
                    }

                    // resolve CPT successfully
                    $query->set('post_type', $post_type);
                    $query->set('name', $slug);

                    // ===== BỔ SUNG STATE CHUẨN CHO SINGULAR =====

                    $query->is_singular = true;
                    $query->is_single = true;
                    $query->is_page = false;

                    // đảm bảo không bị fallback về archive / blog
                    $query->is_archive = false;
                    $query->is_home = false;

                    // gán queried object để body_class & conditional tag hoạt động đúng
                    $query->queried_object = $post;
                    $query->queried_object_id = (int) $post->ID;

                    return;
                }
            }

            // adminz_rw_tax_slug
            $slug = $query->get('adminz_rw_tax_slug');
            if (!empty($slug)) {
                foreach ($this->taxonomies as $taxonomy) {
                    $term = get_term_by('slug', $slug, $taxonomy);

                    if (empty($term)) {
                        continue;
                    }

                    // resolve taxonomy successfully
                    $query->set('taxonomy', $taxonomy);
                    $query->set('term', $slug);

                    // ===== BỔ SUNG STATE CHUẨN CHO WP =====

                    // đánh dấu là taxonomy archive
                    $query->is_tax = true;
                    $query->is_archive = true;
                    $query->is_home = false;

                    // nếu là category thì set thêm is_category
                    if ($taxonomy === 'category') {
                        $query->is_category = true;
                    }

                    // gán queried object để body_class hoạt động đúng
                    $query->queried_object = $term;
                    $query->queried_object_id = (int) $term->term_id;

                    return;
                }
            }
        });

        // sửa link front end
        add_filter('post_type_link', function ($permalink, $post) {

            //
            if (!in_array($post->post_type, $this->post_types, true)) {
                return $permalink;
            }

            //
            $permalink = str_replace('/' . $post->post_type . '/', '/', $permalink);
            return $permalink;
        }, 10, 2);

        add_filter('term_link', function ($termlink, $term, $taxonomy) {

            if (!in_array($taxonomy, $this->taxonomies, true)) {
                return $termlink;
            }

            // comment: remove taxonomy base from url
            $taxonomy_object = get_taxonomy($taxonomy);
            if (empty($taxonomy_object->rewrite['slug'])) {
                return $termlink;
            }

            $termlink = str_replace('/' . $taxonomy_object->rewrite['slug'] . '/', '/', $termlink);
            return $termlink;
        }, 10, 3);

        // redirect khi là url cũ
        add_action('template_redirect', function () {
            if (!empty($this->post_types)) {
                foreach ($this->post_types as $key => $post_type) {
                    if (is_singular($post_type)) {
                        global $post;
                        $currentUrl = home_url($_SERVER['REQUEST_URI']);

                        // comment: detect old url contain post type slug
                        if (strpos($currentUrl, '/' . $post_type . '/') !== false) {
                            $shortUrl = str_replace(
                                '/' . $post->post_type . '/',
                                '/',
                                $currentUrl
                            );
                            wp_redirect($shortUrl, 301);
                            exit;
                        }
                    }
                }
            }

            if (!empty($this->taxonomies)) {
                foreach ($this->taxonomies as $key => $taxonomy) {
                    if ((get_queried_object()->taxonomy ?? '') === $taxonomy) {
                        $currentUrl = home_url($_SERVER['REQUEST_URI']);
                        
                        // comment: detect old url contain taxonomy slug
                        if (strpos($currentUrl, '/' . $taxonomy . '/') !== false) {
                            $shortUrl = str_replace(
                                '/' . $taxonomy . '/',
                                '/',
                                $currentUrl
                            );
                            // wp_redirect($shortUrl, 301);
                            // exit;
                        }
                    }
                }
            }
        });
    }

    function ______backup_remove_post_type_slugs(array $post_types) {
        $post_types = array_map('sanitize_key', $post_types);

        // load rewrite rules on init
        add_action('init', function () use ($post_types) {
            foreach ($post_types as $post_type) {
                $posts = get_posts([
                    'post_type' => $post_type,
                    'posts_per_page' => -1,
                    'post_status' => 'publish'
                ]);

                foreach ($posts as $p) {
                    // comment: match optional 2 char language prefix then post slug
                    add_rewrite_rule(
                        '^([a-z]{2}/)?' . $p->post_name . '/?$',
                        'index.php?post_type=' . $post_type . '&name=' . $p->post_name,
                        'top'
                    );
                }
            }
        });

        // 3: link front end
        add_filter('post_type_link', function ($permalink, $post) use ($post_types) {

            //
            if (!in_array($post->post_type, $post_types, true)) {
                return $permalink;
            }

            //
            $permalink = str_replace('/' . $post->post_type . '/', '/', $permalink);
            return $permalink;
        }, 10, 2);

        // redirect
        add_action('template_redirect', function () use ($post_types) {
            if (is_singular($post_types)) {
                global $post;
                $currentUrl = home_url($_SERVER['REQUEST_URI']);

                // comment: detect old url contain post type slug
                foreach ($post_types as $post_type) {
                    if (strpos($currentUrl, '/' . $post_type . '/') !== false) {
                        $shortUrl = str_replace('/' . $post->post_type . '/', '/', $currentUrl);
                        wp_redirect($shortUrl, 301);
                        exit;
                    }
                }
            }
        });

        // when post saved -> update rewrite rules
        add_action('save_post', function ($postId, $post) use ($post_types) {
            if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
                return;
            }

            if (!in_array($post->post_type, $post_types, true)) {
                return;
            }

            // Đảm bảo post đã có slug
            if (empty($post->post_name)) {
                return;
            }

            // THÊM REWRITE RULE NGAY LẬP TỨC
            add_rewrite_rule(
                '^([a-z]{2}/)?' . $post->post_name . '/?$',
                'index.php?post_type=' . $post->post_type . '&name=' . $post->post_name,
                'top'
            );

            // FLUSH NGAY để rule có hiệu lực
            // error_log('Added rewrite rule for: ' . $post->post_name);
            flush_rewrite_rules(false);
        }, 10, 2);
    }
}
