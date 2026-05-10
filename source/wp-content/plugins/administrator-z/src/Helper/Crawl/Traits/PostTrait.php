<?php

namespace Adminz\Helper\Crawl\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait PostTrait {

    function get_post_data($html = false) {
        $html    = $html ? $html : $this->html;
        $crawler = new Crawler($html);
        $return  = ['html_length' => strlen($html)];

        // title
        $node = $crawler->filter($this->config['adminz_import_post_title'] ?? 'h1');
        if ($node->count()) {
            $return['post_title'] = $node->first()->text('');
        }

        // thumbnail
        $node = $crawler->filter($this->config['adminz_import_post_thumbnail'] ?? '.post-thumbnail img');
        if ($node->count()) {
            $return['post_thumbnail'] = $this->get_image_src_node($node->first());
        }

        // category
        $node = $crawler->filter($this->config['adminz_import_post_category'] ?? '.cat-links a');
        if ($node->count()) {
            $return['post_category'] = $node->each(fn($n) => $n->text(''));
        }

        // content
        $return['post_content'] = '';
        $node = $crawler->filter($this->config['adminz_import_post_content'] ?? '.entry-content');
        if ($node->count()) {
            $remove_end   = (int) ($this->config['adminz_import_content_remove_end'] ?? 0);
            $remove_first = (int) ($this->config['adminz_import_content_remove_first'] ?? 0);
            $children     = $node->first()->children();
            $total        = $children->count();
            $children->each(function ($child, $i) use ($remove_first, $remove_end, $total, &$return) {
                if ($i >= $remove_first && $i <= ($total - $remove_end - 1)) {
                    $return['post_content'] .= $child->outerHtml();
                }
            });
        }

        $return = apply_filters('adminz_crawl_post_data', $return, $crawler, $this);
        return $return;
    }

    function crawl_post($data = false) {
        $data    = $data ? $data : $this->crawl_data;
        $postarr = [
            'post_title'  => $data['post_title'] ?? '',
            'post_status' => 'publish',
            'post_type'   => $this->config['postarr']['post_type'] ?? null,
            'post_parent' => $this->config['postarr']['post_parent'] ?? null,
        ];

        if ($existing_id = $this->post_exists_on_wpposts($postarr['post_title'], $postarr['post_type'])) {
            $this->post_exists_on_wpposts = true;
            return $existing_id;
        }

        if (isset($data['post_thumbnail'])) {
            $postarr['_thumbnail_id'] = $this->save_image($data['post_thumbnail']);
        }

        if (isset($data['post_content'])) {
            $postarr['post_content'] = $this->prepare_thumbnail_content($data['post_content']);
        }

        if (!isset($postarr['_thumbnail_id']) and isset($this->images_saved[0])) {
            $postarr['_thumbnail_id'] = $this->images_saved[0];
        }

        if (isset($data['post_category'])) {
            $postarr['tax_input'] = ['category' => $this->save_taxonomy($data['post_category'])];
        }

        $id = wp_insert_post($postarr);
        if (!is_wp_error($id)) {
            return $id;
        }

        return false;
    }

    function post_exists_on_wpposts($title, $post_type) {
        if (($this->config['post_exists_on_wpposts'] ?? '') == 'on') {
            $sanitized_title = sanitize_title($title);
            $existing_post   = get_page_by_path($sanitized_title, OBJECT, $post_type);
            if ($existing_post) {
                return $existing_post->ID;
            }
        }
        return false;
    }

    function save_taxonomy($data, $taxonomy = 'category') {
        $category_ids = array();
        foreach ($data as $category_name) {
            $category = get_term_by('name', $category_name, $taxonomy);
            if ($category) {
                $category_ids[] = (int) $category->term_id;
            } else {
                $new_category = wp_insert_term($category_name, $taxonomy);
                if (!is_wp_error($new_category)) {
                    $category_ids[] = (int) $new_category['term_id'];
                }
            }
        }
        return $category_ids;
    }

    function set_post_fixed_terms($post_id) {
        $fixed_terms = $this->config['fixed_terms'];
        if (!empty($this->fixed_terms)) {
            $fixed_terms = $this->fixed_terms;
        }
        foreach ((array) $fixed_terms as $key => $term_id) {
            adminz_add_post_term($post_id, $term_id);
        }
    }

    function after_import($post_id) {
        $this->save_log($post_id, 'done', $this->url, $this->url_from);
        $this->set_post_fixed_terms($post_id);
    }

    // ----------- Check functions
    function check_adminz_import_from_post() {
        return $this->report_check_single($this->get_post_data());
    }

    function check_adminz_import_from_category() {
        $crawler  = new Crawler($this->html);
        $return   = [];
        $seenUrls = [];

        $items = $crawler->filter($this->config['adminz_import_category_post_item'] ?? '.post');
        $items->each(function ($item) use (&$return, &$seenUrls) {
            $preview = preg_replace('/\s+/', ' ', $item->text(''));
            $href    = false;

            $link_selector = $this->config['adminz_import_category_post_item_link'] ?? 'a';
            $links = $item->filter($link_selector);
            $links->each(function ($link) use (&$href) {
                $text = trim($link->text(''));
                if ($text) {
                    $h = $this->fix_href($link->attr('href'));
                    if ($h) {
                        $href = $h;
                    }
                }
            });

            if ($href && !isset($seenUrls[$href])) {
                $return[]       = ['preview' => $preview, 'url' => $href];
                $seenUrls[$href] = true;
            }
        });

        if ($this->is_cron) {
            $this->cron_list($return);
            return;
        }

        return $this->report_list($return);
    }

    // ----------- Run functions
    function run_adminz_import_from_category() {
        return $this->check_adminz_import_from_category();
    }

    function run_adminz_import_from_post() {
        $this->crawl_data = $this->get_post_data();
        $post_id          = $this->crawl_post();
        $this->after_import($post_id);
        $note = $this->post_exists_on_wpposts ? '[exists_on_wpposts]' : '[new]';
        return $this->report_run_single($post_id, $note);
    }
}
