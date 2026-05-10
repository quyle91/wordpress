<?php

namespace Adminz\Helper\Crawl\Traits;

trait HtmlTrait {

    public $transient_time = 6 * HOUR_IN_SECONDS;

    function maybe_load_html($url = false, $encode = true) {
        $url = $url ? $url : $this->url;
        if (!$url) {
            echo 'No URL found';
            die();
        }

        // kiểm tra trong transient
        $cached = $this->maybe_load_cached($url);
        if ($cached) {
            return $cached;
        }

        // start curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, '');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $headers = [];
        $headers[] = 'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $headers[] = 'Accept-Language: vi,en-US;q=0.9,en;q=0.8';
        $headers[] = 'Upgrade-Insecure-Requests: 1';
        $headers[] = 'Connection: keep-alive';
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3');

        error_log("LOAD HTML" . ($this->is_cron ? " CRON" : "") . ": $url");
        $response = curl_exec($ch);
        error_log("--- HTML response length: " . strlen($response));

        if ($response === false) {
            $error = "Curl error: " . curl_error($ch);
            curl_close($ch);
            echo $error;
            die();
        }

        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);

        $charset = 'UTF-8';
        if (preg_match('/charset=([\w-]+)/i', $contentType, $matches)) {
            $charset = strtoupper($matches[1]);
        }

        if ($encode) {
            $response = @mb_convert_encoding($response, 'HTML-ENTITIES', $charset);
        }

        // Tìm kiếm và thay thế
        $search  = explode("\r\n", $this->config['adminz_import_content_replace_from'] ?? '');
        $replace = explode("\r\n", $this->config['adminz_import_content_replace_to'] ?? '');
        $response = str_replace($search, $replace, $response);

        $this->always_cache_response($url, $response);
        return $response;
    }

    function maybe_load_cached($url) {
        $transient_key = 'adminz_crawl_list_' . md5(serialize($url));
        if (false !== ($cached_data = get_transient($transient_key))) {
            return $cached_data;
        }
        return;
    }

    function always_cache_response($url, $response) {
        $transient_key = 'adminz_crawl_list_' . md5(serialize($url));
        set_transient($transient_key, $response, $this->transient_time);
    }

    function curl_generateCloudflareCookies() {
        $timestamp = time();
        $cf_bm_value = bin2hex(random_bytes(16)) . '-' . $timestamp . '-1.0.1.1-' .
            bin2hex(random_bytes(32));
        $cfuvid_value = bin2hex(random_bytes(16)) . '-' . $timestamp . '087-0.0.1.1-604800000';
        $cookieHeader = 'Cookie: __cf_bm=' . $cf_bm_value . '; _cfuvid=' . $cfuvid_value;
        return $cookieHeader;
    }
}
