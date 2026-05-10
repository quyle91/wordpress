<?php

namespace Adminz\Helper\Crawl\Traits;

use Symfony\Component\DomCrawler\Crawler;

trait ImageTrait {

    public $images_saved = [];

    function save_image($image_url, $image_data = false) {
        $image_url = $this->fix_href($image_url);

        // Check if file exists
        $filename = basename($image_url);
        $upload_dir = wp_upload_dir();
        $existing_attachment = get_posts(array(
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_query'  => array(
                array(
                    'key'     => '_wp_attached_file',
                    'value'   => ltrim($upload_dir['subdir'] . '/' . $filename, '/'),
                    'compare' => 'LIKE',
                ),
            ),
        ));

        if (!empty($existing_attachment)) {
            $attach_id = $existing_attachment[0]->ID;
            $this->images_saved[] = $attach_id;
            return $attach_id;
        }

        // Check if the image URL is accessible
        $response = wp_remote_get($image_url);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log("Image URL not accessible: $image_url");
            return;
        }

        // Get HTML and import to library
        if (!$image_data) {
            $image_data = $this->maybe_load_html($image_url, false);
            if ($image_data === false) {
                return;
            }
        }

        $upload_dir = wp_upload_dir();
        $temp_file  = $upload_dir['path'] . '/' . $filename;
        file_put_contents($temp_file, $image_data);
        if (!file_exists($temp_file)) {
            return;
        }
        $file_type  = wp_check_filetype($filename, null);
        $guid       = $upload_dir['url'] . '/' . basename($filename);
        $attachment = array(
            'guid'           => $upload_dir['url'] . '/' . basename($filename),
            'post_mime_type' => $file_type['type'],
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );
        $attach_id = wp_insert_attachment($attachment, $temp_file);
        if (!is_wp_error($attach_id)) {
            $this->images_saved[] = $attach_id;
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            $attach_data = wp_generate_attachment_metadata($attach_id, $temp_file);
            wp_update_attachment_metadata($attach_id, $attach_data);
            $this->save_log($attach_id, 'done', $image_url, $this->url_from);
            return $attach_id;
        }

        return false;
    }

    function save_pdf($pdf_url) {
        $pdf_url = $this->fix_href($pdf_url);

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        if (empty($pdf_url) || !filter_var($pdf_url, FILTER_VALIDATE_URL)) {
            return;
        }

        global $wpdb;
        $query = $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type = 'application/pdf' AND post_title = %s LIMIT 1",
            pathinfo($pdf_url, PATHINFO_FILENAME)
        );
        $existing_pdf_id = $wpdb->get_var($query);
        if ($existing_pdf_id) {
            return $existing_pdf_id;
        }

        $tmp = download_url($pdf_url);
        if (is_wp_error($tmp)) {
            return $tmp;
        }

        $file_array             = [];
        $file_array['name']     = basename(parse_url($pdf_url, PHP_URL_PATH));
        $file_array['tmp_name'] = $tmp;

        if (is_wp_error($file_array['tmp_name'])) {
            @unlink($file_array['tmp_name']);
            return $file_array['tmp_name'];
        }

        $attachment_id = media_handle_sideload($file_array, 0);

        if (is_wp_error($attachment_id)) {
            @unlink($file_array['tmp_name']);
            return $attachment_id;
        }

        return $attachment_id;
    }

    function get_image_src($image) {
        $src = $image->getAttribute('src');
        if ($image->hasAttribute('data-src')) {
            $src = $image->getAttribute('data-src');
        }

        return $this->clean_image_src($src);
    }

    // for Symfony Crawler node
    function get_image_src_node($node) {
        $src = $node->attr('data-src') ?: $node->attr('src');
        return $this->clean_image_src($src ?? '');
    }

    private function clean_image_src($src) {
        $is_absolute = filter_var($src, FILTER_VALIDATE_URL);

        if (!$is_absolute) {
            $base_url = rtrim($this->base_url, '/');
            $path     = ltrim($src, '/');
            $src      = $base_url . '/' . $path;
        }

        $parsed_url = parse_url($src);
        $scheme     = $parsed_url['scheme'] ?? '';
        $host       = $parsed_url['host'] ?? '';
        $path       = $parsed_url['path'] ?? '';
        $clean_url  = $scheme . '://' . $host . $path;

        return $clean_url;
    }
}
