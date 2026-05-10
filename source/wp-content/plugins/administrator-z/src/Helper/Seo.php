<?php

namespace Adminz\Helper;

class Seo {

    function __construct() {
        // 
    }

    function get_title($post) {
        return get_the_title($post);
    }

    function get_description($post) {
        $description = get_option('blogdescription');
        if ($excerpt = get_the_excerpt($post)) {
            $description = $excerpt;
        }
        return $description;
    }

    function general_meta_tags() {
        add_action('wp_head', function () {
            if (is_singular()) {
                global $post;
                setup_postdata($post);

                // title and description
                $title = $this->get_title($post);
                $description = $this->get_description($post);

                // Thêm title vào meta
                $meta_tags = [
                    [
                        'name' => 'title',
                        'content' => $title
                    ], // Thêm thẻ title cho SEO
                    [
                        'name' => 'description',
                        'content' => $description
                    ],
                    // ...
                ];

                $meta_tags_html = [];
                foreach ($meta_tags as $tag) {
                    if ($tag['content'] ?? '') {
                        $meta_tags_html[] = '<meta name="' . esc_attr($tag['name']) . '" content="' . esc_attr($tag['content']) . '" />';
                    }
                }
                echo implode("\n", $meta_tags_html) . "\n";
                wp_reset_postdata();
            }
        });
    }


    function general_og_twitter() {
        add_action('wp_head', function () {
            if (is_single() || is_page()) {
                global $post;

                setup_postdata($post);

                // title and description
                $title = $this->get_title($post);
                $description = $this->get_description($post);

                // url and site name
                $url = get_permalink($post);
                $site_name = get_bloginfo('name');

                // image
                if (has_post_thumbnail($post->ID)) {
                    $thumbnail_src = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), 'full');
                    $image = esc_url($thumbnail_src[0] ?? '');
                } else {
                    if ($site_logo = get_theme_mod('site_logo')) {
                        $image = wp_get_attachment_image_src($site_logo, 'full')[0] ?? '';
                    } else {
                        $image = '';
                    }
                }

                $meta_tags = [
                    // Open Graph
                    ['property' => 'og:title', 'content' => esc_attr($title)],
                    ['property' => 'og:description', 'content' => esc_attr($description)],
                    ['property' => 'og:image', 'content' => esc_url($image)],
                    ['property' => 'og:url', 'content' => esc_url($url)],
                    ['property' => 'og:type', 'content' => 'article'],
                    ['property' => 'og:site_name', 'content' => esc_attr($site_name)],

                    // Twitter Card
                    ['name' => 'twitter:card', 'content' => 'summary_large_image'],
                    ['name' => 'twitter:title', 'content' => esc_attr($title)],
                    ['name' => 'twitter:description', 'content' => esc_attr($description)],
                    ['name' => 'twitter:image', 'content' => esc_url($image)],
                ];

                // Echo từng thẻ meta
                foreach ($meta_tags as $tag) {
                    if ($tag['content'] ?? '') {
                        if (isset($tag['property'])) {
                            echo '<meta property="' . $tag['property'] . '" content="' . $tag['content'] . '" />' . "\n";
                        } elseif (isset($tag['name'])) {
                            echo '<meta name="' . $tag['name'] . '" content="' . $tag['content'] . '" />' . "\n";
                        }
                    }
                }
                wp_reset_postdata();
            }
        });
    }

    function no_index() {
        add_action('wp_head', function () {

            // get params
            $exclude_params = [
                'page',
                's',
            ];
            $query_params = array_keys($_GET);
            if (!empty($query_params) && count(array_diff($query_params, $exclude_params)) > 0) {
                echo '<meta name="robots" content="noindex, nofollow">';
                return;
            }

            // custom post_types
            // $exclude_post_types = [''];
            // if (is_singular() && in_array(get_post_type(), $exclude_post_types)) {
            // echo '<meta name="robots" content="noindex, nofollow">';
            // }
        });
    }

    function preload_image() {
        // create page metabox
        foreach (get_post_types() as $post_type => $post_type) {
            \WpDatabaseHelperV2\Meta\WpMeta::make()
                ->post_type($post_type)
                ->label('Adminz preload images')
                ->fields([
                    \WpDatabaseHelperV2\Fields\WpRepeater::make()
                        ->name('adminz_preload_images')
                        ->label('Preload images')
                        ->gridColumn(12)
                        ->fields(
                            [
                                \WpDatabaseHelperV2\Fields\WpField::make()
                                    ->name('image_id')
                                    ->label('Image')
                                    ->type('wp_media')
                                    ->attributes(
                                        ['type' => 'wp_media']
                                    )
                            ]
                        )
                        ->default([
                            [
                                'image_id' => '',
                            ]
                        ])
                ])
                ->register();
        }


        // show preload image 
        add_action('wp_head', function () {

            // skip on non-singular pages
            if (!is_singular()) {
                return;
            }

            // chec k if have preload images
            $preload_images = get_post_meta(get_the_ID(), 'adminz_preload_images', true);

            // skip if empty
            if (empty($preload_images)) {
                return;
            }

            // show images
            foreach ((array)$preload_images as $key => $value) {
                $image_id = $value['image_id'] ?? '';
                if (!$image_id) continue;
                $image_url = wp_get_attachment_image_src($image_id, 'full')[0] ?? '';
                if (!$image_url) continue;
                echo '<link rel="preload" as="image" class="adminz_preload_images" href="' . $image_url . '" />' . "\r\n";
            }
        });
    }

    function custom_permalink($post_type) {
        // Prepare one post type only
        $post_type = sanitize_key($post_type);

        // register meta box
        \WpDatabaseHelperV2\Meta\WpMeta::make()
            ->post_type($post_type)
            ->label('Adminz custom permalink')
            ->fields(
                [
                    \WpDatabaseHelperV2\Fields\WpField::make()
                        ->kind('input')
                        ->type('text')
                        ->name('adminz_custom_permalink')
                        ->gridColumn(12)
                        ->label('Custom permalink')
                        ->attributes(['placeholder' => 'custom-slug'])
                        ->notes([
                            '1/2/3/4/5/6abc-xyz/just-a-cool-blog-post'
                        ])
                ]
            )
            ->register();

        // add rewrite rule
        add_action('init', function () use ($post_type) {
            global $wpdb;

            // get all custom permalink in database for THIS post type only
            // comment: query only this post type, not array
            $results = $wpdb->get_results("
                SELECT p.ID AS post_id, pm.meta_value
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE pm.meta_key = 'adminz_custom_permalink'
                AND pm.meta_value <> ''
                AND p.post_type = '" . esc_sql($post_type) . "'
            ");

            if (!empty($results)) {
                foreach ($results as $row) {
                    $post_id = (int)$row->post_id;
                    $meta_value = trim($row->meta_value);

                    if (empty($meta_value)) {
                        continue;
                    }

                    // Build exact regex rule from meta_value
                    $regex = preg_quote($meta_value, '/');
                    add_rewrite_rule(
                        '^' . $regex . '/?$',
                        'index.php?post_type=' . $post_type . '&p=' . $post_id,
                        'top'
                    );
                }
            }
        });

        // Change post link
        $filter_hook = function ($post_link, $post) use ($post_type) {
            // only run for this one post type only
            $pid = is_object($post) ? $post->ID : (int)$post;
            if (get_post_type($pid) !== $post_type) {
                return $post_link;
            }

            // get custom permalink meta
            $custom = get_post_meta($pid, 'adminz_custom_permalink', true);

            if (!empty($custom)) {
                $custom_path = trim($custom, "/ \t\n\r\0\x0B");
                return home_url('/' . $custom_path . '/');
            }

            return $post_link;
        };

        add_filter('post_type_link', $filter_hook, 10, 2);
        add_filter('post_link', $filter_hook, 10, 2);
        add_filter('page_link', $filter_hook, 10, 2);
        add_filter('attachment_link', $filter_hook, 10, 2);

        // comment: when post saved -> update rewrite rules
        add_action('save_post', function ($postId, $post) use ($post_type) {
            if (wp_is_post_revision($postId)) {
                return;
            }

            // comment: only run for your CPT
            if (!in_array($post->post_type, [$post_type], true)) {
                return;
            }

            // comment: flush rewrite just that time to make sure new slug works
            flush_rewrite_rules(false);
        }, 10, 2);
    }
}
