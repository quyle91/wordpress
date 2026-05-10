<?php

namespace Adminz\Helper\Crawl\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait ReportTrait {

    function report_check_single($return) {
        if ($this->return_type == 'ID') {
            return $return;
        }
        if ($this->return_type == 'array') {
            return $return;
        }

        $table_content = '';
        foreach ($return as $key => $value) {
            $table_content .= '<tr>';
            $table_content .= '<td>' . esc_attr($key) . '</td>';
            if (is_array($value)) {
                $table_content .= '<td><pre>' . print_r($value, true) . '</pre></td>';
            } else {
                $table_content .= '<td>' . wp_kses_post($value) . '</td>';
            }
            $table_content .= '</tr>';
        }

        return <<<HTML
        <table class="adminz_table">
            {$table_content}
        </table>
        HTML;
    }

    function report_run_single($post_id, $context = '') {
        if (!get_the_title($post_id)) {
            return 'Post not exists!';
        }

        if ($this->return_type == 'ID') {
            return $post_id;
        }
        if ($this->return_type == 'json') {
            return json_encode([$this->url => get_permalink($post_id)]) . "\r\n";
        }

        $permalink = get_permalink($post_id);
        $title     = get_the_title($post_id);
        return <<<HTML
        <a href="$permalink" target="_blank">
            {$context}
            {$title}
        </a>
        HTML;
    }

    function cron_list($return) {
        $ran = 0;
        ob_start();
        foreach ($return as $key => $value) {
            $url      = $value['url'];
            $url_from = '[' . $this->cron_data['fixed_term'] . ']' . $this->url;
            $action   = '';

            if ($key == (count($return))) {
                $items = ($key + 1);
                $this->save_log('', 'done', $this->url, $url_from);
                echo "::: CRON DONE WITH $items items and saved log, URL from: $url_from \r\n";
                echo ob_get_clean();
                break;
            }

            if ($this->is_cron_single_done($url)) {
                continue;
            }

            switch ($this->action) {
                case 'run_adminz_import_from_category':
                    $action = 'run_adminz_import_from_post';
                    break;
                case 'run_adminz_import_from_product_category':
                    $action = 'run_adminz_import_from_product';
                    break;
                default:
                    $action = 'run_adminz_import_from_post';
                    break;
            }

            $_Crawl = new self();
            $_Crawl->set_return_type('json');
            $_Crawl->set_config();
            $_Crawl->set_action($action);
            $_Crawl->set_url($url);
            $_Crawl->set_return_type('json');
            $_Crawl->url_from   = $url_from;
            $_Crawl->is_cron    = $this->is_cron;
            $_Crawl->fixed_terms = $this->fixed_terms;
            echo $_Crawl->run();

            if ($ran >= (($this->config['crawl_items_per_time'] ?? 1) - 1)) {
                break;
            }

            $ran++;
        }

        echo ob_get_clean();
    }

    function report_list($return) {
        if ($this->return_type == 'ID') {
            return $return;
        }
        if ($this->return_type == 'ARRAY') {
            return $return;
        }
        if ($this->return_type == 'json') {
            $json = '';
            foreach ($return as $key => $value) {
                $url    = $value['url'];
                $action = '';
                switch ($this->action) {
                    case 'run_adminz_import_from_category':
                        $action = 'run_adminz_import_from_post';
                        break;
                    case 'run_adminz_import_from_product_category':
                        $action = 'run_adminz_import_from_product';
                        break;
                    default:
                        $action = 'run_adminz_import_from_post';
                        break;
                }

                $post_id = $this->post_exists_on_logs($url, $action);
                if ($post_id) {
                    continue;
                }

                $_Crawl = new self();
                $_Crawl->set_return_type('json');
                $_Crawl->set_config();
                $_Crawl->set_action($action);
                $_Crawl->set_url($url);
                $_Crawl->is_cron    = $this->is_cron;
                $_Crawl->fixed_terms = $this->fixed_terms;
                $json .= $_Crawl->run();
            }
            return $json;
        }

        $output = <<<EOT
			<table class="adminz_table">
		EOT;

        foreach ($return as $key => $item) {
            $output .= '<tr data-url="' . $item['url'] . '">' . "\n";
            $output .= '<td>' . ($key + 1) . '</td>' . "\n";
            $output .= '<td class="result">' . $item['preview'] . '</td>' . "\n";
            $output .= '<td><a target="blank" href="' . $item['url'] . '">Link</a></td>' . "\n";
            $output .= '<td><button type="button" class="button run">Run</button></td>' . "\n";
            $output .= '</tr>' . "\n";
        }

        $output .= <<<EOT
			</table>
		EOT;

        return $output;
    }

    function check_adminz_import_images() {
        $crawler  = new Crawler($this->html);
        $return   = [];
        $seenUrls = [];

        $src_attr = $this->config['images_selector_src'] ?? 'src';
        $crawler->filter($this->config['images_selector'] ?? 'img')->each(
            function ($node) use (&$return, &$seenUrls, $src_attr) {
                $link = $node->attr($src_attr);
                $link = $this->fix_href($link);
                if ($link && !isset($seenUrls[$link])) {
                    $return[]       = ['preview' => $link, 'url' => $link];
                    $seenUrls[$link] = true;
                }
            }
        );

        if ($this->is_cron) {
            $this->cron_list($return);
            return;
        }

        return $this->report_list($return);
    }

    function run_adminz_import_images() {
        return $this->check_adminz_import_images();
    }

    function run_adminz_import_image() {
        $post_id = $this->save_image($this->url, $this->html);
        $this->after_import($post_id);
        $note = $this->post_exists_on_wpposts ? ' (post_exists_on_wpposts)' : '[new]';
        return $this->report_run_single($post_id, $note);
    }
}
