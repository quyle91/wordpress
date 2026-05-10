<?php

namespace Adminz\Helper\Logs;

class HttpPostRequest {
    function __construct() {
    }

    private $table_name = 'adminz_http_post_request_logs';
    function init() {

        \WpDatabaseHelperV2\Database\DbTable::make()
            ->name($this->table_name)
            ->title('Request logs')
            ->fields([

                // 
                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('id')
                    ->type('INT(11)')
                    ->notNull()
                    ->autoIncrement()
                    ->primary(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('timestamp')
                    ->type('DATETIME')
                    ->notNull()
                    ->default('CURRENT_TIMESTAMP'),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('url')
                    ->type('VARCHAR(255)')
                    ->notNull(),

                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('request')
                    ->type('TEXT')
                    ->notNull(),
                
                \WpDatabaseHelperV2\Database\DbColumn::make()
                    ->name('response')
                    ->type('TEXT')
                    ->notNull(),

                
            ])
            ->registerAdminPage()
            ->create();


        // save logs

        add_action('http_api_debug', function ($response, $context, $class, $args, $url) {
            // bỏ qua wp-cron.php
            if (str_contains($url, 'wp-cron.php')) {
                return;
            }


            // Chỉ xử lý các yêu cầu POST
            if (isset($args['method']) && strtoupper($args['method']) === 'POST') {
                // Chuẩn bị dữ liệu để lưu vào bảng
                $log_data = array(
                    'timestamp' => current_time('mysql'),
                    'url'       => $url,
                    'request'   => json_encode($args, JSON_PRETTY_PRINT), // Chuyển đổi request thành JSON
                    'response'  => json_encode($response, JSON_PRETTY_PRINT), // Chuyển đổi response thành JSON
                );

                // Chèn dữ liệu vào bảng
                global $wpdb;
                $wpdb->insert(
                    $wpdb->prefix . $this->table_name, // Tên bảng (có tiền tố prefix)
                    $log_data // Dữ liệu cần chèn
                );

                // Kiểm tra lỗi (nếu có)
                if ($wpdb->last_error) {
                    error_log('Failed to save log: ' . $wpdb->last_error);
                }
            }
        }, 10, 5);
    }
}
