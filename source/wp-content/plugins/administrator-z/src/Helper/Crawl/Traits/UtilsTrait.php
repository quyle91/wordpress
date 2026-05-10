<?php

namespace Adminz\Helper\Crawl\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait UtilsTrait {

    function prepare_thumbnail_content($html) {
        if (!$html) {
            return '';
        }

        $html = @mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');
        $doc  = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        // Dùng Crawler từ $doc đã load — không parse lại HTML
        $crawler = new Crawler($doc);

        // Remove attributes from specified tags
        foreach (explode(",", $this->config['adminz_import_content_remove_attrs']) as $tag) {
            $tag = trim($tag);
            if (!$tag) continue;
            // Collect trước để tránh live-list mutation
            $nodes = [];
            $crawler->filter($tag)->each(function ($n) use (&$nodes) {
                $nodes[] = $n->getNode(0);
            });
            foreach ($nodes as $node) {
                while ($node->attributes->length) {
                    $node->removeAttribute($node->attributes->item(0)->name);
                }
            }
        }

        // Remove specified HTML tags
        $tags_to_remove = explode(",", $this->config['adminz_import_content_remove_tags']);
        foreach ($tags_to_remove as $tag) {
            $tag = trim($tag);
            if (!$tag) continue;
            $nodes = [];
            $crawler->filter($tag)->each(function ($n) use (&$nodes) {
                $nodes[] = $n->getNode(0);
            });
            foreach ($nodes as $node) {
                $node->parentNode->removeChild($node);
            }
        }

        // Remove first and last specified number of DOM elements
        $body = $doc->getElementsByTagName('body')->item(0);
        if ($body) {
            $children = [];
            foreach ($body->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE || $child->nodeType === XML_TEXT_NODE) {
                    $children[] = $child;
                }
            }

            for ($i = 0; $i < $this->config['adminz_import_content_remove_first'] && $i < count($children); $i++) {
                $body->removeChild($children[$i]);
            }

            for ($i = 0; $i < $this->config['adminz_import_content_remove_end'] && count($children) - $i - 1 >= 0; $i++) {
                $body->removeChild($children[count($children) - $i - 1]);
            }
        }

        // Handle images — collect DOMElements trước khi replace
        $img_elements = [];
        $crawler->filter('img')->each(function ($n) use (&$img_elements) {
            $img_elements[] = $n->getNode(0);
        });

        foreach ($img_elements as $img) {
            $src      = $this->get_image_src($img); // $img vẫn là DOMElement
            $image_id = $this->save_image($src);
            if (!is_wp_error($image_id)) {
                $image_html = wp_get_attachment_image($image_id, 'full', false, ['class' => 'adminz_crawl']);
                if ($image_html) {
                    $img_doc = new \DOMDocument();
                    @$img_doc->loadHTML($image_html);
                    $new_img = $img_doc->getElementsByTagName('img')->item(0);
                    if ($new_img) {
                        $imported_img = $doc->importNode($new_img, true);
                        $img->parentNode->replaceChild($imported_img, $img);
                    }
                }
            }
        }

        $body        = $doc->getElementsByTagName('body')->item(0);
        $updated_html = '';
        if ($body) {
            foreach ($body->childNodes as $child) {
                $updated_html .= $doc->saveHTML($child);
            }
        }
        $updated_html = html_entity_decode($updated_html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $updated_html;
    }

    function fix_href($href) {
        $base_url = $this->url;

        if (empty($href)) {
            return false;
        }

        $href = str_replace(' ', '', $href);

        if (preg_match('/^(#|\?|javascript:|mailto:|tel:|data:)/', $href)) {
            return false;
        }

        $parsed_base_url = parse_url($base_url);
        if (!isset($parsed_base_url['scheme']) || !isset($parsed_base_url['host'])) {
            return false;
        }

        $parsed_href = parse_url($href);

        if (isset($parsed_href['scheme']) && isset($parsed_href['host'])) {
            if (!in_array($parsed_href['scheme'], ['http', 'https'])) {
                return false;
            }
            $fixed_url = $parsed_href['scheme'] . '://' . $parsed_href['host'] . (isset($parsed_href['path']) ? $parsed_href['path'] : '');
            return $fixed_url;
        }

        if (strpos($href, '//') === 0) {
            $fixed_url    = $parsed_base_url['scheme'] . ':' . $href;
            $parsed_fixed = parse_url($fixed_url);
            return $parsed_fixed['scheme'] . '://' . $parsed_fixed['host'] . (isset($parsed_fixed['path']) ? $parsed_fixed['path'] : '');
        }

        if (strpos($href, '/') === 0) {
            return $parsed_base_url['scheme'] . '://' . $parsed_base_url['host'] . $href;
        }

        $fixed_url    = $parsed_base_url['scheme'] . '://' . $parsed_base_url['host'] . '/' . ltrim($href, '/');
        $parsed_fixed = parse_url($fixed_url);
        return $parsed_fixed['scheme'] . '://' . $parsed_fixed['host'] . (isset($parsed_fixed['path']) ? $parsed_fixed['path'] : '');
    }
}
