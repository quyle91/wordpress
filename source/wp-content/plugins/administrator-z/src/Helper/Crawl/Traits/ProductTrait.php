<?php

namespace Adminz\Helper\Crawl\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait ProductTrait {

    function get_product_data($html = false) {
        $html    = $html ? $html : $this->html;
        $crawler = new Crawler($html);
        $return  = ['html_length' => strlen($html)];

        // title
        $node = $crawler->filter($this->config['adminz_import_product_title'] ?? 'h1.product_title');
        if ($node->count()) {
            $return['product_title'] = $node->first()->text('');
        }

        // price (simple)
        $node = $crawler->filter($this->config['adminz_import_product_prices'] ?? '.price');
        if ($node->count()) {
            $return['product_price'] = $this->fix_product_price($node->first()->text(''));
        }

        // variation
        $variations_form = $crawler->filter('.variations_form');
        if ($variations_form->count()) {
            $return['product_type'] = 'variation';
            $json = $variations_form->first()->attr('data-product_variations');
            if ($json) {
                $return['product_variations'] = json_decode($json);
            }
        }

        // grouped
        $grouped_form = $crawler->filter('.grouped_form');
        if ($grouped_form->count()) {
            $return['product_type']    = 'grouped';
            $return['grouped_products'] = [];
            $crawler->filter('.grouped_form tr a')->each(function ($link) use (&$return) {
                if (!$link->attr('aria-label')) {
                    $return['grouped_products'][] = [
                        'title' => $link->text(''),
                        'url'   => $link->attr('href'),
                    ];
                }
            });
        }

        // gallery
        $gallery_selector = ($this->config['adminz_import_product_gallery_children_item'] ?? '.woocommerce-product-gallery__image') . ' img';
        $crawler->filter($gallery_selector)->each(function ($img) use (&$return) {
            $return['product_gallery'][] = $this->get_image_src_node($img);
        });

        // short description
        $return['product_short_content'] = '';
        $node = $crawler->filter($this->config['adminz_import_product_short_description'] ?? '.woocommerce-product-details__short-description');
        if ($node->count()) {
            $return['product_short_content'] = $node->first()->text('');
        }

        // content
        $node = $crawler->filter($this->config['adminz_import_product_content'] ?? '#tab-description');
        if ($node->count()) {
            $remove_end   = (int) ($this->config['adminz_import_content_remove_end'] ?? 0);
            $remove_first = (int) ($this->config['adminz_import_content_remove_first'] ?? 0);
            $children     = $node->first()->children();
            $total        = $children->count();
            $content      = '';
            $children->each(function ($child, $i) use ($remove_first, $remove_end, $total, &$content) {
                if ($i >= $remove_first && $i <= ($total - $remove_end - 1)) {
                    $content .= $child->outerHtml();
                }
            });
            $return['product_content'] = $content;
        }

        $return['product_meta'] = [];
        $return = apply_filters('adminz_crawl_product_data', $return, $crawler, $this);
        return $return;
    }

    function crawl_product($data = false) {
        $data = $data ? $data : $this->crawl_data;

        if ($existing_id = $this->post_exists_on_wpposts($data['product_title'], 'product')) {
            $this->post_exists_on_wpposts = true;
            return $existing_id;
        }

        switch ($data['product_type'] ?? '') {
            case 'simple':
                $id = $this->crawl_product_simple($data);
                break;
            case 'variation':
                $id = $this->crawl_product_variable($data);
                break;
            case 'grouped':
                $id = $this->crawl_product_grouped($data);
                break;
            default:
                $id = $this->crawl_product_simple($data);
                break;
        }

        if ($id ?? '') {
            return $id;
        }
    }

    function crawl_product_simple($data) {
        $product    = new \WC_Product_Simple();
        $product    = $this->prepare_product_data($product, $data);
        $product_id = $product->save();
        return $product_id;
    }

    function crawl_product_grouped($data) {
        $product = new \WC_Product_Grouped();
        $product = $this->prepare_product_data($product, $data);

        $children = [];
        foreach (($data['grouped_products'] ?? []) as $key => $product_child) {
            $_Crawl              = new self();
            $_Crawl->set_return_type('ID');
            $_Crawl->set_config();
            $_Crawl->set_action('run_adminz_import_from_product');
            $_Crawl->set_url($product_child['url']);
            $_Crawl->is_cron    = $this->is_cron;
            $_Crawl->fixed_terms = $this->fixed_terms;
            $child_product_id   = $_Crawl->run();
            $children[]         = $child_product_id;
        }

        $product->set_children($children);
        $product_id = $product->save();
        return $product_id;
    }

    function crawl_product_variable($data) {
        $product    = new \WC_Product_Variable();
        $product    = $this->prepare_product_data($product, $data);
        $product_id = $product->save();

        $prepare_attr = [];
        foreach ($data['product_variations'] as $key => $value) {
            $attributes = (array) $value->attributes;
            foreach ($attributes as $_key => $_value) {
                $_key = str_replace('attribute_pa_', '', $_key);
                $this->create_attribute_if_not_exists($_key);
                $prepare_attr[$_key] = $prepare_attr[$_key] ?? [];
                if (!in_array($_value, $prepare_attr[$_key])) {
                    $this->create_attribute_tag_if_not_exists($_value, $_key);
                    $prepare_attr[$_key][] = $_value;
                }
            }
        }

        $atts = [];
        foreach ($prepare_attr as $name => $attr) {
            $name      = "pa_$name";
            $attribute = new \WC_Product_Attribute();
            $attribute->set_id(wc_attribute_taxonomy_id_by_name($name));
            $attribute->set_name($name);
            $attribute->set_options($attr);
            $attribute->set_position(0);
            $attribute->set_visible(true);
            $attribute->set_variation(true);
            $atts[] = $attribute;
        }

        $product->set_attributes($atts);
        $product->save();

        foreach ($data['product_variations'] as $key => $value) {
            $variation  = new \WC_Product_Variation();
            $attributes = (array) $value->attributes;
            $tmp        = [];
            foreach ($attributes as $key => $_value) {
                $term_tag   = str_replace('attribute_', '', $key);
                $term_value = $_value;
                $tmp[$term_tag] = $_value;
                $this->create_attribute_tag_if_not_exists($term_value, $term_tag);
            }
            $variation->set_attributes($tmp);
            $variation->set_parent_id($product_id);
            $variation->set_sku($value->sku ?? false);
            $variation->set_image_id($this->save_image($value->image->url));
            $variation->set_downloadable($value->is_downloadable ?? false);
            $variation->set_virtual($value->is_virtual ?? false);
            $variation->set_stock_status($value->is_in_stock ?? false);
            $variation->set_regular_price($value->display_regular_price ?? false);
            $variation->set_sale_price($value->display_price ?? false);
            $variation->set_date_on_sale_from($value->set_date_on_sale_from ?? false);
            $variation->set_date_on_sale_to($value->set_date_on_sale_to ?? false);
            $variation->set_description($value->description ?? false);
            $variation->set_download_limit($value->download_limit ?? false);
            $variation->set_download_expiry($value->download_expiry ?? false);
            $variation->save();
        }

        $product->save();
        return $product_id;
    }

    function prepare_product_data($product, $data) {
        $product->set_name($data['product_title'] ?? false);
        $product->set_regular_price($data['product_price'] ?? false);
        $data['product_content'] = $this->prepare_thumbnail_content($data['product_content'] ?? '');

        $images = [];
        if (!empty($data['product_gallery'])) {
            foreach ($data['product_gallery'] as $index => $image_url) {
                $image_id = $this->save_image($image_url);
                if ($image_id) {
                    $images[] = $image_id;
                }
            }
        }

        if (!empty($images)) {
            $image_id = array_shift($images);
            $product->set_image_id($image_id);
        }

        if (!empty($images)) {
            $product->set_gallery_image_ids($images);
        }

        $product->set_description($data['product_content'] ?? '');
        $product->set_short_description($data['product_short_content'] ?? '');
        $product->set_status('publish');

        foreach ((array) $data['product_meta'] as $key => $value) {
            $product->update_meta_data($key, $value);
        }

        $product->save();
        return $product;
    }

    function fix_product_price($price) {
        $price = preg_replace('/\D/', '', $price);
        return (int) $price / pow(10, (int) $this->config['product_price_decimal_seprator']);
    }

    function create_attribute_tag_if_not_exists($attribute_value, $attribute_name) {
        $taxonomy = 'pa_' . $attribute_name;
        if (!term_exists($attribute_value, $taxonomy)) {
            wp_insert_term($attribute_value, 'pa_' . $attribute_name);
        }
    }

    function create_attribute_if_not_exists($attribute_name, $type = 'select') {
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);
        if (!$attribute_id) {
            $label = strtoupper(str_replace('pa_', '', $attribute_name));
            $attribute_id = wc_create_attribute([
                'name'         => $label,
                'slug'         => $attribute_name,
                'type'         => $type,
                'order_by'     => 'menu_order',
                'has_archives' => false,
            ]);
        }
        return $attribute_id;
    }

    // ----------- Check functions
    function check_adminz_import_from_product() {
        return $this->report_check_single($this->get_product_data());
    }

    function check_adminz_import_from_product_category() {
        $crawler  = new Crawler($this->html);
        $return   = [];
        $seenUrls = [];

        $link_selector = $this->config['adminz_import_category_product_item_link'] ?? 'a';
        $crawler->filter($this->config['adminz_import_category_product_item'] ?? '.product')->each(
            function ($item) use (&$return, &$seenUrls, $link_selector) {
                $preview = preg_replace('/\s+/', ' ', $item->text(''));
                $href    = false;
                $item->filter($link_selector)->each(function ($link) use (&$href) {
                    $h = $this->fix_href($link->attr('href'));
                    if ($h) {
                        $href = $h;
                    }
                });
                if ($href && !isset($seenUrls[$href])) {
                    $return[]        = ['preview' => $preview, 'url' => $href];
                    $seenUrls[$href] = true;
                }
            }
        );

        if ($this->is_cron) {
            $this->cron_list($return);
            return;
        }

        return $this->report_list($return);
    }

    // ----------- Run functions
    function run_adminz_import_from_product_category() {
        return $this->check_adminz_import_from_product_category();
    }

    function run_adminz_import_from_product() {
        $this->crawl_data = $this->get_product_data();
        $post_id          = $this->crawl_product();
        $this->after_import($post_id);
        $note = $this->post_exists_on_wpposts ? '[exists_on_wpposts]' : '[new]';
        return $this->report_run_single($post_id, $note);
    }
}
