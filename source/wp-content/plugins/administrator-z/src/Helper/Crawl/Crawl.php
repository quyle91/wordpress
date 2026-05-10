<?php

namespace Adminz\Helper\Crawl;

use Adminz\Helper\Crawl\Traits\LogTrait;
use Adminz\Helper\Crawl\Traits\HtmlTrait;
use Adminz\Helper\Crawl\Traits\ImageTrait;
use Adminz\Helper\Crawl\Traits\PostTrait;
use Adminz\Helper\Crawl\Traits\ProductTrait;
use Adminz\Helper\Crawl\Traits\ReportTrait;
use Adminz\Helper\Crawl\Traits\UtilsTrait;

class Crawl {
    use LogTrait;
    use HtmlTrait;
    use ImageTrait;
    use PostTrait;
    use ProductTrait;
    use ReportTrait;
    use UtilsTrait;

    public $action;
    public $url, $base_url, $url_from, $type, $crawl_data;
    public $fixed_terms = [];
    public $images_saved = [];
    public $config = [];
    public $html, $doc;
    public $return_type = 'default'; // default| json | ID
    public $is_cron;
    public $cron_data;
    public $post_exists_on_wpposts; // temporary to save text note
    public $transient_time = 6 * HOUR_IN_SECONDS;

    function __construct() {
        //
    }

    function set_config($override = false) {
        $config = get_option('adminz_tools');
        if ($override) {
            $config = $override;
        }
        $this->config = $config;
    }

    function set_url($url) {
        $this->url = $url;
        $this->base_url = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);
    }

    function set_action($action) {
        $this->action = $action;
    }

    function set_return_type($value) {
        $this->return_type = $value;
    }

    function run() {

        // check exists on db
        if ($post_id = $this->post_exists_on_logs($this->url, $this->action)) {
            $this->after_import($post_id);
            return $this->report_run_single($post_id, '[post_exists_on_logs]');
        }

        $this->doc = new \DOMDocument();
        libxml_use_internal_errors(true);

        // skip encode if run_adminz_import_image
        $encode = true;
        if ($this->action == 'run_adminz_import_image') {
            $encode = false;
        }

        if (!$this->is_enable_cron_log()) {
            return 'Enable cron log first!';
        }

        $this->html = $this->maybe_load_html(false, $encode);

        if ($this->action == 'check_adminz_import_from_post') {
            return $this->check_adminz_import_from_post();
        }

        if ($this->action == 'run_adminz_import_from_post') {
            return $this->run_adminz_import_from_post();
        }

        if ($this->action == 'check_adminz_import_from_category') {
            return $this->check_adminz_import_from_category();
        }

        if ($this->action == 'run_adminz_import_from_category') {
            return $this->run_adminz_import_from_category();
        }

        if ($this->action == 'check_adminz_import_from_product') {
            return $this->check_adminz_import_from_product();
        }

        if ($this->action == 'run_adminz_import_from_product') {
            return $this->run_adminz_import_from_product();
        }

        if ($this->action == 'check_adminz_import_from_product_category') {
            return $this->check_adminz_import_from_product_category();
        }

        if ($this->action == 'run_adminz_import_from_product_category') {
            return $this->run_adminz_import_from_product_category();
        }

        if ($this->action == 'check_adminz_import_images') {
            return $this->check_adminz_import_images();
        }

        if ($this->action == 'run_adminz_import_images') {
            return $this->run_adminz_import_images();
        }

        if ($this->action == 'run_adminz_import_image') {
            return $this->run_adminz_import_image();
        }
    }
}