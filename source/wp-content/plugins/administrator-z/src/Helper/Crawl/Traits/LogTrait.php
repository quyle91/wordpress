<?php

namespace Adminz\Helper\Crawl\Traits;

trait LogTrait {

    public $table_name = 'adminz_crawl_logs';

    function create_table_log() {
        global $wpdb;

        \WpDatabaseHelperV2\Database\DbTable::make()
            ->name($this->table_name)
            ->title('Crawl logs')
            ->fields([

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('id')
                    ->type('INT(11)')
                    ->notNull()
                    ->autoIncrement()
                    ->primary(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('post_id')
                    ->type('INT(11)')
                    ->nullable(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('url')
                    ->type('varchar(255)')
                    ->nullable(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('url_from')
                    ->type('varchar(255)')
                    ->nullable(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('action')
                    ->type('varchar(255)')
                    ->nullable(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('status')
                    ->type('varchar(255)')
                    ->nullable(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('type')
                    ->type('varchar(255)')
                    ->nullable(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('date')
                    ->type('datetime')
                    ->default('CURRENT_TIMESTAMP')
                    ->notNull(),

            ])
            ->registerAdminPage()
            ->create();

        // show post_id on table log
        add_filter("{$wpdb->prefix}{$this->table_name}_post_id", function ($_value) {
            if (!$_value) {
                return;
            }

            $post_title = get_the_title($_value);
            $post_type = get_post_type($_value);

            if ($post_title) {
                return "<a href=\"" . get_edit_post_link($_value) . "\">[$post_type] $post_title</a>";
            } else {
                return 'Post not exists';
            }
        }, 10, 1);
    }

    function delete_table_log() {
        add_action('init', function () {
            if (isset($_GET['adminz_delete_table_crawl_log'])) {
                if (!current_user_can('manage_options')) {
                    wp_die('Unauthorized');
                }
                if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'adminz_delete_table')) {
                    wp_die('Action failed. Invalid nonce.');
                }
                global $wpdb;
                require_once(ABSPATH . 'wp-includes/pluggable.php');
                $table_name = $wpdb->prefix . $this->table_name;
                $table_name_safe = esc_sql($table_name);
                $sql = "DROP TABLE IF EXISTS `{$table_name_safe}`";
                $wpdb->query($sql);
                die('done');
            }
        });
    }

    function save_log($post_id, $status, $url, $url_from) {
        global $wpdb;

        $data = [
            "post_id" => $post_id,
            'status'  => $status,
            "url"     => $url,
            'url_from' => $url_from,
            "type"    => $this->is_cron ? 'cron' : '',
            "action"  => $this->action,
        ];

        $table_name = $wpdb->prefix . $this->table_name;
        $wpdb->insert($table_name, $data);
    }

    function post_exists_on_logs($url, $action) {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->table_name;

        if (!$this->is_enable_cron_log()) {
            return false;
        }

        $query = $wpdb->prepare(
            "SELECT post_id FROM $table_name WHERE url = %s AND action = %s ORDER BY id DESC LIMIT 1",
            $url,
            $action
        );

        $post_id = $wpdb->get_var($query);

        // chỉ return nếu post chưa bị xoá.
        if (get_post_status($post_id)) {
            return $post_id;
        }
    }

    function is_enable_cron_log() {
        return ($this->config['enable_craw_log'] ?? '') == 'on';
    }

    function is_cron_single_done($url) {
        global $wpdb;
        $url_from = '[' . $this->cron_data['fixed_term'] . ']' . $this->url;
        $table_name = $wpdb->prefix . $this->table_name;
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE url = %s AND type = %s AND status = %s AND url_from = %s",
            $url,
            'cron',
            'done',
            $url_from
        );
        $count = $wpdb->get_var($query);
        return $count > 0;
    }

    function is_cron_category_done($url, $fixed_term, $action) {
        $count_on_url = 0;
        $count_on_log = 0;

        // count item on url
        $_Crawl = new self();
        $_Crawl->is_cron = false;
        $_Crawl->set_return_type('ID');
        $_Crawl->set_config();
        $_Crawl->set_url($url);
        $_Crawl->html = $_Crawl->maybe_load_html(false, false);

        $_action = '';
        if ($action == 'run_adminz_import_from_category') {
            $_action = 'check_adminz_import_from_category';
        }
        if ($action == 'run_adminz_import_from_product_category') {
            $_action = 'check_adminz_import_from_product_category';
        }

        if ($_action) {
            $items = (array)$_Crawl->$_action();
            $count_on_url = count((array)$items);
        }

        // count item on logs
        $urls = [];
        foreach ((array)$items as $key => $item) {
            $urls[] = $item['url'];
        }

        $__action = '';
        if ($action == 'run_adminz_import_from_category') {
            $__action = 'run_adminz_import_from_post';
        }
        if ($action == 'run_adminz_import_from_product_category') {
            $__action = 'run_adminz_import_from_product';
        }

        $fixed_term = (array) $fixed_term;
        $fixed_term = array_map('intval', $fixed_term);
        $prefix = json_encode($fixed_term);
        $url_from = $prefix . $url;
        global $wpdb;
        $table_name = $wpdb->prefix . $this->table_name;

        $placeholders = implode(',', array_fill(0, count($urls), '%s'));
        $query = $wpdb->prepare(
            "
            SELECT COUNT(DISTINCT url) FROM $table_name 
            WHERE 
            url_from = %s AND 
            type = %s AND 
            status = %s AND 
            action = %s AND
            url IN ($placeholders) AND
            post_id > 0
            ",
            array_merge([$url_from, 'cron', 'done', $__action], $urls)
        );

        $count_on_log = $wpdb->get_var($query);

        if (!$count_on_log) {
            return false;
        }

        return ($count_on_log == $count_on_url);
    }
}
