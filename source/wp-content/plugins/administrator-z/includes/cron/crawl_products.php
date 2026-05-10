<?php
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

$wordpress_path = dirname(__FILE__) . '/../../../../../';
require_once($wordpress_path . 'wp-load.php');

global $adminz;
$action = 'run_adminz_import_from_product_category';
$cron_urls = $adminz['Tools']->settings['cron_product_categories'] ?? [];
// echo "<pre>"; print_r($cron_urls); echo "</pre>"; die;

$ran = 0; // biến quy định chỉ chạy 1 item 1 lần.
$all_done = true;
foreach ((array) $cron_urls as $key => $item) {
    // echo $item['url'] . "\r\n";
    if ($ran) {
        break;
    }

    $url = $item['url'];
    $fixed_term = $item['fixed_term'];
    $_Crawl = new \Adminz\Helper\Crawl();
    $is_done = $_Crawl->is_cron_category_done($url, $fixed_term, $action);
    // var_dump($is_done);	
    // die;

    if (!$is_done) {
        echo "Running on $url \r\n";

        //
        $all_done = false;
        $ran++;

        //
        $_Crawl->is_cron = true;
        $_Crawl->fixed_terms = $fixed_term;
        $_Crawl->cron_data = $item;
        $_Crawl->set_return_type('json');
        $_Crawl->set_config();
        $_Crawl->set_action($action);
        $_Crawl->set_url($url);
        echo $_Crawl->run();
    }
}

if ($all_done) {
    echo "ALL HAVE DONE! \r\n";
}
exit;
