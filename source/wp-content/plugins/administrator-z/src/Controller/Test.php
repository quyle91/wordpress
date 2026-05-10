<?php

namespace Adminz\Controller;

final class Test {

    // Singleton pattern
    private static $instance = null;
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Khởi tạo, đăng ký hook
    function __construct() {
        $this->run();
        add_action('after_setup_theme', [$this, 'after_setup_theme']);
        add_action('plugins_loaded', [$this, 'plugins_loaded']);
        add_action('init', [$this, 'init']);
    }

    function run() {
        //
    }

    /**
     * Centralized security check for all tools in init().
     * - Ensures only administrators can access.
     * - Verifies nonce on POST requests, but ONLY when an adminz_* GET param
     *   is active — so regular WP POST requests (Media, AJAX...) are not affected.
     *
     * Each form in a tool must include: wp_nonce_field('adminz_tool')
     */
    function check_security() {
        // 1. Role check
        if (!current_user_can('administrator')) {
            return false;
        }

        // 2. CSRF check: only when this is an adminz tool request
        $is_adminz_tool = !empty(array_filter(
            array_keys($_GET ?? []),
            fn($k) => str_starts_with($k, 'adminz_')
        ));

        if ($is_adminz_tool && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $nonce = $_POST['_wpnonce'] ?? '';
            if (!wp_verify_nonce($nonce, 'adminz_tool')) {
                wp_die('Security check failed. Invalid or expired nonce.', 403);
            }
        }

        return true;
    }

    function after_setup_theme() {
        // 
        if (current_user_can('administrator')) {
            if (($_GET['adminz_test_hooks'] ?? '') == 'flatsome') {
                $hooks = require_once(ADMINZ_DIR . "includes/file/flatsome_hooks.php");
                foreach ($hooks as $hook) {
                    add_action($hook, function () use ($hook) {
                        echo do_shortcode('[adminz_test content="' . $hook . '"]');
                    });
                }
            }
        }
    }

    function plugins_loaded() {
        // 
        if (current_user_can('administrator')) {
            if (($_GET['adminz_test_hooks'] ?? '') == 'woocommerce') {
                $hooks = require_once(ADMINZ_DIR . "includes/file/woocommerce_hooks.php");
                foreach ($hooks as $hook) {
                    add_action($hook, function () use ($hook) {
                        echo do_shortcode('[adminz_test content="' . $hook . '"]');
                    });
                }
            }
        }
    }

    function init() {
        if (!$this->check_security()) {
            return;
        }

        // ========================= SERVER TOOLS ========================= 
        // 
        if ($_GET['adminz_server_tool'] ?? '') {
            switch ($_GET['adminz_server_tool']) {

                case 'serverinfo':
                    echo "<h2>::: Server Information ====================</h2>";
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    echo "<tr><th>Key</th><th>Value</th></tr>";

                    // Thông tin cơ bản
                    echo "<tr><td>PHP Version</td><td>" . phpversion() . "</td></tr>";
                    echo "<tr><td>Server Software</td><td>" . $_SERVER['SERVER_SOFTWARE'] . "</td></tr>";
                    echo "<tr><td>Operating System</td><td>" . php_uname() . "</td></tr>";
                    echo "<tr><td>Server IP</td><td>" . $_SERVER['SERVER_ADDR'] . "</td></tr>";
                    echo "<tr><td>Client IP</td><td>" . $_SERVER['REMOTE_ADDR'] . "</td></tr>";

                    // Thông tin MySQL (nếu có)
                    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASSWORD')) {
                        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
                        if ($link) {
                            echo "<tr><td>MySQL Version</td><td>" . mysqli_get_server_info($link) . "</td></tr>";
                            mysqli_close($link);
                        } else {
                            echo "<tr><td>MySQL Version</td><td>Unable to connect to MySQL</td></tr>";
                        }
                    } else {
                        echo "<tr><td>MySQL Version</td><td>MySQL constants not defined</td></tr>";
                    }

                    // Thông tin PHP Configuration
                    echo "<tr><td>PHP Memory Limit</td><td>" . ini_get('memory_limit') . "</td></tr>";
                    echo "<tr><td>PHP Max Execution Time</td><td>" . ini_get('max_execution_time') . " seconds</td></tr>";
                    echo "<tr><td>PHP Upload Max Filesize</td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
                    echo "<tr><td>PHP Post Max Size</td><td>" . ini_get('post_max_size') . "</td></tr>";

                    // Thông tin thời gian hoạt động của server (nếu có)
                    if (function_exists('shell_exec')) {
                        $uptime = @shell_exec('uptime');
                        if ($uptime) {
                            echo "<tr><td>Server Uptime</td><td>{$uptime}</td></tr>";
                        } else {
                            echo "<tr><td>Server Uptime</td><td>Unable to retrieve uptime</td></tr>";
                        }
                    } else {
                        echo "<tr><td>Server Uptime</td><td>shell_exec not enabled</td></tr>";
                    }

                    // Thông tin về thư mục gốc của WordPress
                    echo "<tr><td>WordPress Root Directory</td><td>" . ABSPATH . "</td></tr>";
                    echo "<tr><td>WordPress Content Directory</td><td>" . WP_CONTENT_DIR . "</td></tr>";

                    // Thông tin về phiên bản WordPress
                    echo "<tr><td>WordPress Version</td><td>" . get_bloginfo('version') . "</td></tr>";

                    echo "</table>";
                    break;

                case 'serverspeed':
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    echo "<h2>::: Server Speed ====================</h2>";
                    echo "<tr><th>Thông số</th><th>Giá trị</th><th>Đánh giá (1-10)</th></tr>";

                    // 3. PHP Execution Speed
                    $start_time = microtime(true);
                    for ($i = 0; $i < 1000000; $i++) {
                        md5($i);
                    }
                    $execution_time = microtime(true) - $start_time;
                    $phpScore = max(1, 10 - ($execution_time * 10));
                    echo "<tr><td>PHP Execution Speed</td><td>{$execution_time} seconds</td><td>" . round($phpScore) . "</td></tr>";

                    // 4. Disk I/O Speed
                    $file = sys_get_temp_dir() . '/speedtest.tmp';
                    $start_time = microtime(true);
                    file_put_contents($file, str_repeat('A', 1024 * 1024 * 10)); // 10MB
                    $write_time = microtime(true) - $start_time;
                    $start_time = microtime(true);
                    file_get_contents($file);
                    $read_time = microtime(true) - $start_time;
                    unlink($file);
                    $diskScore = max(1, 10 - (($write_time + $read_time) * 5));
                    echo "<tr><td>Disk I/O Speed</td><td>Write: {$write_time}s | Read: {$read_time}s</td><td>" . round($diskScore) . "</td></tr>";

                    // 5. MySQL Query Speed
                    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_NAME')) {
                        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
                        if ($link) {
                            $start_time = microtime(true);
                            $result = mysqli_query($link, "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES");
                            $query_time = microtime(true) - $start_time;
                            $mysqlScore = max(1, 10 - ($query_time * 10));
                            echo "<tr><td>MySQL Query Speed</td><td>{$query_time} seconds</td><td>" . round($mysqlScore) . "</td></tr>";
                            mysqli_close($link);
                        }
                    }

                    // 6. Network Latency
                    $start_time = microtime(true);
                    @file_get_contents("https://www.google.com");
                    $network_time = microtime(true) - $start_time;
                    $networkScore = max(1, 10 - ($network_time * 10));
                    echo "<tr><td>Network Latency</td><td>{$network_time} seconds</td><td>" . round($networkScore) . "</td></tr>";

                    echo "</table>";
                    break;

                case 'phpinfo':
                    phpinfo();
                    break;

                case 'phpextensions':
                    echo "<h2>::: PHP Extensions ====================</h2>";
                    $extensions = get_loaded_extensions();
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    echo "<tr><th>Extension</th></tr>";
                    foreach ($extensions as $extension) {
                        echo "<tr><td>{$extension}</td></tr>";
                    }
                    echo "</table>";
                    break;

                case 'cacheinfo':
                    echo "<h2>::: Cache Information ====================</h2>";

                    // Kiểm tra OPcache
                    if (function_exists('opcache_get_status')) {
                        echo "<h3>::: OPcache Information ==============================</h3>";
                        $opcacheStatus = opcache_get_status();
                        echo "<table border='1' style='border-collapse: collapse;'>";
                        echo "<tr><th>Key</th><th>Value</th></tr>";
                        foreach ($opcacheStatus as $key => $value) {
                            echo "<tr><td>{$key}</td><td><pre>" . print_r($value, true) . "</pre></td></tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "::: OPcache is not enabled. =====================<br>";
                    }

                    // Kiểm tra APCu cache (nếu có)
                    if (function_exists('apcu_cache_info')) {
                        echo "<h3>::: APCu Cache Information ==============================</h3>";
                        $apcuCacheInfo = apcu_cache_info(true);
                        echo "<table border='1' style='border-collapse: collapse;'>";
                        echo "<tr><th>Key</th><th>Value</th></tr>";
                        foreach ($apcuCacheInfo as $key => $value) {
                            echo "<tr><td>{$key}</td><td><pre>" . print_r($value, true) . "</pre></td></tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "::: APCu is not enabled. =====================<br>";
                    }

                    // Kiểm tra Memcached (nếu có)
                    if (class_exists('Memcached')) {
                        echo "<h3>::: Memcached Information ==============================</h3>";
                        $memcached = new \Memcached();
                        $memcached->addServer(
                            $_GET['memcached_host'] ?? 'localhost',
                            $_GET['memcached_port'] ?? 11211
                        );
                        $stats = $memcached->getStats();
                        echo "<table border='1' style='border-collapse: collapse;'>";
                        echo "<tr><th>Key</th><th>Value</th></tr>";
                        foreach ($stats as $key => $value) {
                            echo "<tr><td>{$key}</td><td><pre>" . print_r($value, true) . "</pre></td></tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "::: Memcached is not enabled. =====================<br>";
                    }

                    // Kiểm tra Redis (nếu có)
                    if (class_exists('Redis')) {
                        echo "<h3>::: Redis Information ==============================</h3>";
                        $redis = new \Redis();
                        $redis->connect(
                            $_GET['redis_host'] ?? 'localhost',
                            $_GET['redis_port'] ?? 6379
                        );
                        $redisInfo = $redis->info();
                        echo "<table border='1' style='border-collapse: collapse;'>";
                        echo "<tr><th>Key</th><th>Value</th></tr>";
                        foreach ($redisInfo as $key => $value) {
                            echo "<tr><td>{$key}</td><td><pre>" . print_r($value, true) . "</pre></td></tr>";
                        }
                        echo "</table>";
                    } else {
                        echo "::: Redis is not enabled. =====================<br>";
                    }

                    // Kiểm tra Object Cache của WordPress (nếu có)
                    if (function_exists('wp_using_ext_object_cache') && wp_using_ext_object_cache()) {
                        echo "<h3>::: WordPress Object Cache Information ==============================</h3>";
                        global $wp_object_cache;
                        if (isset($wp_object_cache)) {
                            echo "<table border='1' style='border-collapse: collapse;'>";
                            echo "<tr><th>Key</th><th>Value</th></tr>";
                            foreach ($wp_object_cache->cache as $key => $value) {
                                echo "<tr><td>{$key}</td><td><pre>" . print_r($value, true) . "</pre></td></tr>";
                            }
                            echo "</table>";
                        } else {
                            echo "::: WordPress Object Cache is not available. =====================<br>";
                        }
                    } else {
                        echo "::: WordPress is not using an external object cache. =====================<br>";
                    }
                    break;

                case 'resetcache':
                    echo "<h2>Reset Cache</h2>";

                    // Reset OPcache
                    if (function_exists('opcache_reset')) {
                        if (opcache_reset()) {
                            echo "OPcache reset successfully.<br>";
                        } else {
                            echo "Failed to reset OPcache.<br>";
                        }
                    } else {
                        echo "OPcache is not enabled.<br>";
                    }

                    // Reset Memcached Cache
                    if (class_exists('Memcached')) {
                        $memcached = new \Memcached();
                        $memcachedHost = isset($_GET['memcached_host']) ? $_GET['memcached_host'] : 'localhost';
                        $memcachedPort = isset($_GET['memcached_port']) ? (int) $_GET['memcached_port'] : 11211;
                        $memcached->addServer($memcachedHost, $memcachedPort);

                        if ($memcached->flush()) {
                            echo "Memcached cache reset successfully.<br>";
                        } else {
                            echo "Failed to reset Memcached cache.<br>";
                        }
                    } else {
                        echo "Memcached is not enabled.<br>";
                    }

                    // Reset Redis Cache
                    if (class_exists('Redis')) {
                        $redis = new \Redis();
                        $redisHost = isset($_GET['redis_host']) ? $_GET['redis_host'] : 'localhost';
                        $redisPort = isset($_GET['redis_port']) ? (int) $_GET['redis_port'] : 6379;

                        if ($redis->connect($redisHost, $redisPort)) {
                            if ($redis->flushAll()) {
                                echo "Redis cache reset successfully.<br>";
                            } else {
                                echo "Failed to reset Redis cache.<br>";
                            }
                        } else {
                            echo "Failed to connect to Redis.<br>";
                        }
                    } else {
                        echo "Redis is not enabled.<br>";
                    }
                    break;

                case 'mysqlinfo':
                    echo "<h2>::: MySQL Information ====================</h2>";
                    // Sử dụng constants từ wp-config.php
                    if (defined('DB_HOST') && defined('DB_USER') && defined('DB_PASSWORD')) {
                        $link = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD);
                        if (!$link) {
                            die('Could not connect: ' . mysqli_error());
                        }
                        echo "<table border='1' style='border-collapse: collapse;'>";
                        echo "<tr><th>Key</th><th>Value</th></tr>";
                        echo "<tr><td>MySQL Version</td><td>" . mysqli_get_server_info($link) . "</td></tr>";
                        echo "</table>";

                        echo "<h3>::: MySQL Variables ==============================</h3>";
                        $result = mysqli_query($link, 'SHOW VARIABLES');
                        echo "<table border='1' style='border-collapse: collapse;'>";
                        echo "<tr><th>Variable Name</th><th>Value</th></tr>";
                        while ($row = mysqli_fetch_assoc($result)) {
                            echo "<tr><td>{$row['Variable_name']}</td><td>{$row['Value']}</td></tr>";
                        }
                        echo "</table>";
                        mysqli_close($link);
                    } else {
                        echo "MySQL constants are not defined in wp-config.php.";
                    }
                    break;

                case 'memoryinfo':
                    echo "<h2>::: Memory Usage and Execution Time ====================</h2>";
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    echo "<tr><th>Key</th><th>Value</th><th>Unit</th><th>Analysis</th></tr>";

                    // Memory Usage
                    $memoryUsage = memory_get_usage();
                    echo "<tr><td>Memory Usage</td><td>{$memoryUsage}</td><td>bytes</td>";
                    echo "<td>" . round($memoryUsage / 1024, 2) . " KB / " . round($memoryUsage / (1024 * 1024), 2) . " MB</td></tr>";

                    // Peak Memory Usage
                    $peakMemoryUsage = memory_get_peak_usage();
                    echo "<tr><td>Peak Memory Usage</td><td>{$peakMemoryUsage}</td><td>bytes</td>";
                    echo "<td>" . round($peakMemoryUsage / 1024, 2) . " KB / " . round($peakMemoryUsage / (1024 * 1024), 2) . " MB</td></tr>";

                    // Execution Time
                    $executionTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"];
                    echo "<tr><td>Execution Time</td><td>{$executionTime}</td><td>seconds</td>";
                    echo "<td>" . round($executionTime * 1000, 2) . " milliseconds</td></tr>";

                    echo "</table>";
                    break;

                case 'connectioncheck':
                    echo "<h2>::: Connection Check ====================</h2>";
                    // Kiểm tra kết nối đến một API thực tế (ví dụ: WordPress.org)
                    $url = 'https://api.wordpress.org/core/version-check/1.7/';
                    $response = @file_get_contents($url);
                    echo "<table border='1' style='border-collapse: collapse;'>";
                    echo "<tr><th>Key</th><th>Value</th></tr>";
                    echo "<tr><td>Connection to WordPress API</td><td>" . ($response !== false ? 'Success' : 'Failed') . "</td></tr>";
                    echo "</table>";
                    break;

                default:
                    echo "Invalid tool selected.";
                    break;
            }
            die; // Dừng script sau khi hiển thị kết quả
        }

        // ========================= GUILD TOOLS ========================= 
        //
        if (isset($_GET['adminz_guid_telegram'])) {
            echo <<<HTML
				<p>------------------------------ </p>
				<p>https://www.thegioididong.com/game-app/huong-dan-tao-bot-telegram-don-gian-ai-cung-co-the-thuc-hien-1395501</p>

				<p>------------------------------ </p>
				<p>Create new bot with @botfather</p>
				<p>Testadminzbot</p>
				<p>1801547325:GAEDhT5bFLfCjKec8istwkKaOa4Pl7R0ZQE</p>

				<p>------------------------------ </p>
				<p>Get your chat ID</p>
				<p>1640851433</p>

				<p>------------------------------ </p>
				<p>Send a message to your chatbot</p>
				<p>search with your chat bot name and type XXX</p>

				<p>------------------------------ </p>
				<p>Link to test: copy this link to open in browser</p>
				<p>https://api.telegram.org/bot1801547325:GAEDhT5bFLfCjKec8istwkKaOa4Pl7R0ZQE/sendMessage?chat_id=1640851433&text=XXX
				</p>
				HTML;
            die;
        }

        // ========================= FILE TOOLS ========================= 
        //
        if (isset($_GET['adminz_file_included_files'])) {
            echo "<h2>::: Included Files ====================</h2>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>#</th><th>File Path</th><th>Size</th><th>Last Modified</th></tr>";

            // Lấy danh sách các file đã được include
            $includedFiles = get_included_files();

            // Hiển thị từng file trong bảng
            foreach ($includedFiles as $index => $file) {
                echo "<tr>";
                echo "<td style='text-align: center;'>" . ($index + 1) . "</td>";
                echo "<td>{$file}</td>";
                echo "<td style='text-align: right;'>" . filesize($file) . " bytes</td>";
                echo "<td>" . date("Y-m-d H:i:s", filemtime($file)) . "</td>";
                echo "</tr>";
            }

            echo "</table>";
            die; // Dừng script sau khi hiển thị
        }

        // 
        // 
        if (isset($_GET['adminz_file_security_scan_php'])) {
            $this->set_view_file();

            echo "<h2>::: Security Scan ==============</h2>";
            echo "<h4>::: This tool is only php file. For other file types, please use other tools.</h4>";

            $current_file = str_replace('\\', '/', __FILE__);
            $current_file = preg_replace('#/+#', '/', $current_file);

            $scan_directory = function ($dir) use (&$scan_directory, $current_file) {
                $files = scandir($dir);
                $results = [];
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $path = $dir . '/' . $file;
                    $path = str_replace('\\', '/', $path);
                    $path = preg_replace('#/+#', '/', $path);
                    if (is_dir($path)) {
                        $results = array_merge($results, $scan_directory($path));
                    } elseif (pathinfo($path, PATHINFO_EXTENSION) === 'php' && $path !== $current_file) {
                        $content = file_get_contents($path);
                        $reasons = [];

                        if (preg_match('/eval\(/i', $content)) {
                            $reasons[] = 'eval() found';
                        }
                        if (preg_match('/assert\(/i', $content)) {
                            $reasons[] = 'assert() found';
                        }
                        if (preg_match('/create_function\(/i', $content)) {
                            $reasons[] = 'create_function() found';
                        }

                        if (!empty($reasons)) {
                            $results[] = [
                                'file' => $path,
                                'reasons' => implode(', ', $reasons),
                            ];
                        }
                    }
                }
                return $results;
            };

            $suspicious_files = $scan_directory(ABSPATH);

            if ($suspicious_files) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>File Path</th><th>Reasons</th><th>View</th></tr>";
                foreach ($suspicious_files as $file) {
                    $file_url = urlencode($file['file']);
                    echo "<tr>";
                    echo "<td style='vertical-align: top; padding: 5px;'>{$file['file']}</td>";
                    echo "<td style='vertical-align: top; padding: 5px;'>{$file['reasons']}</td>";
                    echo "<td style='vertical-align: top; padding: 5px;'><a href='?adminz_file_security_scan_php=1&view_file={$file_url}' target='_blank'>View</a></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No suspicious files found.";
            }

            die;
        }

        // 
        if (isset($_GET['adminz_file_core_checksums'])) {

            $this->set_view_file();


            echo "<h2>::: Core File Check ===================</h2>";

            // Lấy danh sách các file core từ WordPress.org
            $response = wp_remote_get('https://api.wordpress.org/core/checksums/1.0/?version=' . get_bloginfo('version'));
            if (is_wp_error($response)) {
                echo "Failed to fetch core checksums from WordPress.org.";
                die;
            }

            $checksums = json_decode($response['body'], true);
            if (empty($checksums['checksums'])) {
                echo "No checksums found for this WordPress version.";
                die;
            }

            $modified_files = [];
            foreach ($checksums['checksums'][get_bloginfo('version')] as $file => $checksum) {
                $file_path = ABSPATH . $file;
                if (file_exists($file_path)) {
                    $file_checksum = md5_file($file_path);
                    if ($file_checksum !== $checksum) {
                        $modified_files[] = [
                            'file' => $file,
                            'last_modified' => date("Y-m-d H:i:s", filemtime($file_path)),
                        ];
                    }
                }
            }

            if ($modified_files) {
                echo "<h3>Modified Core Files:</h3>";
                echo "<form method='post'>";
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>STT</th><th>File Path</th><th>Last Modified</th><th>View</th></tr>";
                $stt = 1;
                foreach ($modified_files as $file_info) {
                    echo "<tr>";
                    echo "<td style='vertical-align: top; padding: 5px;'>{$stt}</td>";
                    echo "<td style='vertical-align: top; padding: 5px;'>{$file_info['file']}</td>";
                    echo "<td style='vertical-align: top; padding: 5px;'>{$file_info['last_modified']}</td>";
                    $file_url = urlencode(ABSPATH . $file_info['file']);
                    echo "<td style='vertical-align: top; padding: 5px;'><a href='?adminz_file_core_checksums=1&view_file={$file_url}' target='_blank'>View</a></td>";
                    echo "</tr>";
                    $stt++;
                }
                echo "</table>";
                echo "</form>";
            } else {
                echo "No modified core files found.";
            }
            die;
        }

        //
        if (isset($_GET['adminz_file_large'])) {
            echo "<h2>::: Large Files in wp-content/uploads =================== </h2>";

            $upload_dir = wp_upload_dir();
            $base_dir = $upload_dir['basedir'];
            $large_files = [];
            $size_limit = 5 * 1024 * 1024; // 5MB

            // Hàm đệ quy để quét thư mục
            $scan_directory = function ($dir) use (&$scan_directory, &$large_files, $size_limit) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if ($file === '.' || $file === '..') continue;
                    $path = $dir . '/' . $file;
                    if (is_dir($path)) {
                        $scan_directory($path);
                    } else {
                        $size = filesize($path);
                        if ($size > $size_limit) {
                            $large_files[] = [
                                'path' => $path,
                                'size' => size_format($size, 2),
                            ];
                        }
                    }
                }
            };

            $scan_directory($base_dir);

            if ($large_files) {
                echo "<table border='1'>";
                echo "<tr><th>File Path</th><th>Size</th></tr>";
                foreach ($large_files as $file) {
                    echo "<tr>";
                    echo "<td>{$file['path']}</td>";
                    echo "<td>{$file['size']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No large files found.";
            }
            die;
        }

        if (isset($_GET['adminz_file_permission_check'])) {
            echo "<h2>::: File Permission Checker ====================</h2>";

            // Danh sách các file và thư mục cần kiểm tra
            $paths_to_check = [
                ABSPATH . 'wp-config.php',
                ABSPATH . '.htaccess',
                WP_CONTENT_DIR,
                WP_CONTENT_DIR . '/uploads',
                WP_CONTENT_DIR . '/plugins',
                WP_CONTENT_DIR . '/themes',
                WP_CONTENT_DIR . '/cache',
            ];

            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Path</th><th>Permissions</th><th>Status</th></tr>";

            foreach ($paths_to_check as $path) {
                if (file_exists($path)) {
                    // Lấy quyền truy cập
                    $permissions = fileperms($path);
                    $permissions_formatted = substr(sprintf('%o', $permissions), -4); // Chuyển sang dạng số (ví dụ: 0755)
                    $permissions_human_readable = $this->get_permissions_human_readable($permissions); // Chuyển sang dạng ký tự (ví dụ: -rwxr-xr-x)

                    // Đánh giá quyền truy cập
                    $status = '✅ Safe';
                    if (is_file($path) && ($permissions & 0x0002)) {
                        $status = '⚠️ Warning: File is writable by others';
                    } elseif (is_dir($path) && ($permissions & 0x0002)) {
                        $status = '⚠️ Warning: Directory is writable by others';
                    }

                    // Hiển thị kết quả
                    echo "<tr>";
                    echo "<td>{$path}</td>";
                    echo "<td>{$permissions_formatted} ({$permissions_human_readable})</td>";
                    echo "<td>{$status}</td>";
                    echo "</tr>";
                } else {
                    echo "<tr>";
                    echo "<td>{$path}</td>";
                    echo "<td colspan='2'>File/Directory not found</td>";
                    echo "</tr>";
                }
            }

            echo "</table>";
            die; // Dừng script sau khi hiển thị
        }

        //
        if (isset($_GET['adminz_file_image_clear_orphaned'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            echo "<h2>::: Clearing Orphaned Image Files ====================</h2>";
            global $wpdb;

            // Đường dẫn thư mục uploads
            $upload_dir = wp_upload_dir();
            $folderTarget = $upload_dir['basedir'];

            // Đường dẫn thư mục backup
            $folderBackup = WP_CONTENT_DIR . '/uploads/administrator-z/clear_image_orphaned_backup';

            // Kiểm tra và tạo thư mục backup nếu nó không tồn tại
            if (!file_exists($folderBackup)) {
                wp_mkdir_p($folderBackup); // Tạo thư mục và các thư mục con nếu cần
                echo "<p>✅ Created backup folder: {$folderBackup}</p>";
            }

            // Lấy danh sách các thư mục con trong uploads có tên là 4 chữ số (ví dụ: 2025, 2024)
            $sub_folders = glob($folderTarget . '/*', GLOB_ONLYDIR);

            $moved_files = [];

            foreach ($sub_folders as $sub_folder) {
                // Kiểm tra xem tên thư mục có phải là 4 chữ số không (ví dụ: 2025, 2024, v.v.)
                $folder_name = basename($sub_folder);
                if (preg_match('/^\d{4}$/', $folder_name)) {
                    // Lấy tất cả các file trong thư mục con
                    $files = list_files($sub_folder);

                    foreach ($files as $file) {
                        // Kiểm tra xem file có phải là ảnh không
                        if (wp_check_filetype($file)['type'] && strpos(wp_check_filetype($file)['type'], 'image') !== false) {
                            // Lấy tên file (không bao gồm kích thước)
                            $file_name = basename($file);
                            $file_name_without_size = preg_replace('/-\d+x\d+\.(jpg|jpeg|png|gif)$/', '.$1', $file_name);

                            // Kiểm tra xem file có tồn tại trong metadata của bất kỳ attachment nào không
                            $attachment = $wpdb->get_row(
                                $wpdb->prepare(
                                    "SELECT post_id FROM {$wpdb->postmeta} WHERE (meta_key = '_wp_attachment_metadata' OR meta_key = '_wp_attached_file') AND meta_value LIKE %s",
                                    '%' . $wpdb->esc_like($file_name_without_size) . '%'
                                )
                            );

                            if (!$attachment) {
                                // Đường dẫn file mới trong thư mục backup
                                $new_file_path = $folderBackup . '/' . str_replace($folderTarget . '/', '', $file);

                                // Kiểm tra xem file đã tồn tại trong thư mục backup chưa
                                if (!file_exists($new_file_path)) {
                                    // Tạo thư mục con trong thư mục backup nếu nó chưa tồn tại
                                    $new_file_dir = dirname($new_file_path);
                                    if (!file_exists($new_file_dir)) {
                                        wp_mkdir_p($new_file_dir);
                                    }

                                    // Di chuyển file vào thư mục backup
                                    if (rename($file, $new_file_path)) {
                                        $moved_files[] = [
                                            'name' => basename($file),
                                            'path' => str_replace($folderTarget . '/', '', $file),
                                        ];
                                        echo "<p>✅ Moved: {$file_name}</p>";
                                    } else {
                                        echo "<p>❌ Failed to move: {$file_name}</p>";
                                    }
                                } else {
                                    echo "<p>⚠️ File already exists in backup: {$file_name}</p>";
                                }
                            }
                        }
                    }
                }
            }

            // Hiển thị danh sách các file đã được di chuyển
            if (!empty($moved_files)) {
                echo "<h3>📂 List of moved files:</h3>";
                echo "<table border=1 >";
                echo "<tr><th>#</th><th>File Name</th><th>URL</th></tr>";
                foreach ($moved_files as $key => $moved_file) {
                    // Tạo URL của ảnh từ thư mục backup
                    $file_url_backup = $upload_dir['baseurl'] . '/administrator-z/clear_image_orphaned_backup/' . $moved_file['path'];

                    echo "<tr>";
                    echo "<td>{$key}</td>";
                    echo "<td>{$moved_file['name']}</td>";
                    echo "<td><a href='{$file_url_backup}' target='_blank'>View</a></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>❌ No orphaned image files found.</p>";
            }

            // Hiển thị danh sách các file trong thư mục backup
            echo "<h3>📂 Files in Backup Folder:</h3>";
            $backup_files = list_files($folderBackup);
            if (!empty($backup_files)) {
                echo "<table border=1 >";
                echo "<tr><th>#</th><th>File Name</th><th>URL</th></tr>";
                foreach ($backup_files as $key => $backup_file) {
                    // Tạo URL của ảnh từ thư mục backup
                    $file_url_backup = $upload_dir['baseurl'] . '/administrator-z/clear_image_orphaned_backup/' . str_replace($folderBackup . '/', '', $backup_file);

                    echo "<tr>";
                    echo "<td>{$key}</td>";
                    echo "<td>" . basename($backup_file) . "</td>";
                    echo "<td><a href='{$file_url_backup}' target='_blank'>View</a></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>❌ No files found in backup folder.</p>";
            }

            die; // Dừng script sau khi hoàn thành
        }


        // ========================= DEBUG TOOLS ========================= 
        if (isset($_GET['adminz_show_debug_log'])) {
            $debug_log_path = WP_CONTENT_DIR . '/debug.log';

            // Stop execution if log file does not exist
            if (!file_exists($debug_log_path)) {
                wp_die('Debug log file not found.');
            }

            // Output log content as plain text
            header('Content-Type: text/plain; charset=utf-8');
            readfile($debug_log_path);

            exit;
        }

        if (isset($_GET['adminz_empty_debug_log'])) {
            $debug_log_path = WP_CONTENT_DIR . '/debug.log';
            file_put_contents($debug_log_path, '');
            echo 'done';

            exit;
        }


        // ========================= DATABASE TOOLS ========================= 
        // 
        if (isset($_GET['adminz_database_transients'])) {
            echo "<h2>::: Transients Information =================</h2>";

            global $wpdb;
            // Lấy tất cả các transients từ bảng wp_options
            $transients = $wpdb->get_results(
                "SELECT option_name, option_value, autoload FROM $wpdb->options WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'"
            );

            if ($transients) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>#</th><th>Transient Name</th><th>Value (Raw from Database)</th><th>Autoload</th></tr>";

                $counter = 1; // Biến đếm số thứ tự
                foreach ($transients as $transient) {
                    $name = str_replace(['_transient_', '_site_transient_'], '', $transient->option_name);
                    $value = $transient->option_value; // Lấy giá trị trực tiếp từ database
                    echo "<tr>";
                    echo "<td style='text-align: center; vertical-align: top; padding: 5px;'>{$counter}</td>"; // Cột số thứ tự
                    echo "<td style='vertical-align: top; padding: 5px;'>{$name}</td>";
                    echo "<td style='vertical-align: top; padding: 5px;'>";
                    echo "<textarea style='width: 100%; height: 100px; font-family: monospace;' readonly>{$value}</textarea>";
                    echo "</td>";
                    echo "<td style='vertical-align: top; padding: 5px;'>{$transient->autoload}</td>";
                    echo "</tr>";
                    $counter++; // Tăng biến đếm
                }

                echo "</table>";
            } else {
                echo "No transients found.";
            }
            die;
        }

        // 
        if (isset($_GET['adminz_database_transients_clear'])) {
            echo "<h2>::: Clear Transients ====================</h2>";

            global $wpdb;

            // Xóa các transient hết hạn
            $expired_transients_deleted = $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%' OR option_name LIKE '\_site\_transient\_%' AND option_value < UNIX_TIMESTAMP()"
            );

            // Xóa các transient không hết hạn (nếu có yêu cầu)
            $all_transients_deleted = 0;
            if (isset($_GET['force_clear'])) {
                $all_transients_deleted = $wpdb->query(
                    "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%' OR option_name LIKE '\_site\_transient\_%'"
                );
            }

            // Hiển thị kết quả
            echo "<p>✅ Successfully deleted {$expired_transients_deleted} expired transients.</p>";
            if (isset($_GET['force_clear'])) {
                echo "<p>✅ Successfully deleted {$all_transients_deleted} non-expired transients.</p>";
            }

            // Liên kết để xóa tất cả transient (bao gồm cả không hết hạn)
            echo "<p><a href='?adminz_database_transients_clear&force_clear=1'>Click here to clear ALL transients (including non-expired)</a></p>";

            die; // Dừng script sau khi hiển thị
        }

        //
        if (isset($_GET['adminz_database_postmeta_clear_orphaned'])) {
            echo "<h2>::: Clearing Orphaned Post Meta ====================</h2>";
            global $wpdb;
            // Xóa các bản ghi trong postmeta không có post_id tương ứng trong posts
            $query = " DELETE pm FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL ";
            $result = $wpdb->query($query);
            if ($result !== false) {
                echo "<p>✅ Successfully deleted {$result} orphaned post meta records.</p>";
            } else {
                echo "<p>❌ Error: Unable to delete orphaned post meta records.</p>";
            }
            die; // Dừng script sau khi hoàn thành
        }

        if ($_GET['adminz_database_user_delete_by_role'] ?? '') {
            global $wpdb;
            $user_role = sanitize_text_field($_GET['adminz_database_user_delete_by_role']);

            // Xóa tất cả người dùng từ bảng users dựa trên role, sử dụng JOIN với bảng usermeta
            $wpdb->query($wpdb->prepare("
					DELETE u
					FROM {$wpdb->users} u
					INNER JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id
					WHERE um.meta_key = '{$wpdb->prefix}capabilities'
					AND um.meta_value LIKE %s
				", '%' . $wpdb->esc_like($user_role) . '%'));

            echo "All users with the role '$user_role' have been deleted.";
            die;
        }

        // 
        if (isset($_GET['adminz_database_usermeta_clear_orphaned'])) {
            echo "<h2>::: Clearing Orphaned User Meta ====================</h2>";
            global $wpdb;
            // Xóa các bản ghi trong usermeta không có user_id tương ứng trong users
            $query = " DELETE um FROM {$wpdb->usermeta} um LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID WHERE u.ID IS NULL ";
            $result = $wpdb->query($query);
            if ($result !== false) {
                echo "<p>✅ Successfully deleted {$result} orphaned user meta records.</p>";
            } else {
                echo "<p>❌ Error: Unable to delete orphaned user meta records.</p>";
            }
            die; // Dừng script sau khi hoàn thành
        }

        if (isset($_GET['adminz_database_attachment_clear_orphaned'])) {
            echo "<h2>::: Clearing Attachment record without image file ====================</h2>";
            global $wpdb;

            // Lấy tất cả các bản ghi attachment từ database
            $attachments = $wpdb->get_results("SELECT ID, guid FROM {$wpdb->posts} WHERE post_type = 'attachment'");

            $deleted_count = 0;

            foreach ($attachments as $attachment) {
                // Lấy đường dẫn file ảnh từ guid
                $file_path = str_replace(wp_upload_dir()['baseurl'], wp_upload_dir()['basedir'], $attachment->guid);

                // Kiểm tra xem file ảnh có tồn tại không
                if (!file_exists($file_path)) {
                    // Nếu file ảnh không tồn tại, xóa bản ghi attachment
                    $delete_result = $wpdb->delete($wpdb->posts, array('ID' => $attachment->ID));

                    if ($delete_result !== false) {
                        $deleted_count++;
                    }
                }
            }

            if ($deleted_count > 0) {
                echo "<p>✅ Successfully deleted {$deleted_count} orphaned attachment records.</p>";
            } else {
                echo "<p>❌ No orphaned attachment records found.</p>";
            }

            die; // Dừng script sau khi hoàn thành
        }

        // 
        if (isset($_GET['adminz_database_optimize_tables'])) {
            echo "<h2>::: Database Table Optimizer ====================</h2>";

            global $wpdb;

            // Lấy danh sách các bảng trong cơ sở dữ liệu
            $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);

            if (empty($tables)) {
                echo "<p>❌ No tables found in the database.</p>";
                die;
            }

            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Table Name</th><th>Status</th><th>Optimization Result</th></tr>";

            foreach ($tables as $table) {
                $table_name = $table[0];
                echo "<tr>";
                echo "<td>{$table_name}</td>";

                // Tối ưu hóa bảng
                $result = $wpdb->query("OPTIMIZE TABLE {$table_name}");

                if ($result !== false) {
                    echo "<td>✅ Optimized</td>";
                    // Lấy thông tin chi tiết về kết quả tối ưu hóa
                    $optimize_info = $wpdb->get_row("SHOW TABLE STATUS LIKE '{$table_name}'");
                    if ($optimize_info) {
                        echo "<td>Data Length: " . size_format($optimize_info->Data_length) . "<br>";
                        echo "Index Length: " . size_format($optimize_info->Index_length) . "<br>";
                        echo "Data Free: " . size_format($optimize_info->Data_free) . "</td>";
                    } else {
                        echo "<td>No details available</td>";
                    }
                } else {
                    echo "<td>❌ Failed</td>";
                    echo "<td>Error: " . $wpdb->last_error . "</td>";
                }

                echo "</tr>";
            }

            echo "</table>";
            die; // Dừng script sau khi hiển thị
        }

        // 
        if (isset($_GET['adminz_database_scan'])) {
            echo "<h2>::: Database Scan ====================</h2>";
            global $wpdb;

            // Lấy danh sách tất cả các bảng trong cơ sở dữ liệu
            $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);

            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>Table Name</th><th>Status</th><th>Malware Scan Result</th></tr>";

            // Danh sách các mẫu mã độc phổ biến để quét (sử dụng regex)
            $malware_patterns = [
                '/<\?php/i', // <?php
                '/<\?=/i', // <?=
                '/\beval\s*\(/i', // eval(
                '/\bbase64_decode\s*\(/i', // base64_decode(
                '/\bfunction_exists\s*\(/i', // function_exists(
                '/\bfunction\s*\(/i', // function(
                '/\bgzinflate\s*\(/i', // gzinflate(
                '/\bshell_exec\s*\(/i', // shell_exec(
                '/\bpassthru\s*\(/i', // passthru(
                '/\bexec\s*\(/i', // exec(
                '/\bsystem\s*\(/i', // system(
                '/\bphpinfo\s*\(/i', // phpinfo(
                '/<script/i', // <script
                '/document\.cookie/i', // document.cookie
                '/window\.location/i', // window.location
                // '/iframe/i',// iframe (bỏ comment nếu cần)
                '/onload\s*=/i', // onload=
            ];

            foreach ($tables as $table) {
                $table_name = $table[0];
                echo "<tr>";
                echo "<td valign=top>" . htmlentities($table_name) . "</td>";

                // Quét từng bảng để tìm các dấu hiệu của mã độc
                $found_malware = false;
                $malware_details = [];

                // Lấy tất cả các cột trong bảng
                $columns = $wpdb->get_results("SHOW COLUMNS FROM {$table_name}", ARRAY_A);

                foreach ($columns as $column) {
                    $column_name = $column['Field'];

                    // Kiểm tra từng cột có kiểu dữ liệu TEXT hoặc VARCHAR
                    if (strpos($column['Type'], 'text') !== false || strpos($column['Type'], 'varchar') !== false) {
                        // Lấy toàn bộ dữ liệu từ cột
                        $query = "SELECT * FROM {$table_name}";
                        $results = $wpdb->get_results($query);

                        if (!empty($results)) {
                            foreach ($results as $result) {
                                $column_value = $result->$column_name;

                                // Kiểm tra từng mẫu mã độc bằng regex
                                foreach ($malware_patterns as $pattern) {
                                    if (preg_match($pattern, $column_value)) {
                                        $found_malware = true;
                                        $record_id = reset($result); // Lấy giá trị của cột đầu tiên (Record ID)
                                        $malware_data = htmlentities($column_value); // Mã hóa nội dung mã độc

                                        // Thêm thông tin vào chi tiết mã độc
                                        $malware_details[] = "<mark>Found pattern: " . htmlentities($pattern) . "</mark>";
                                        $malware_details[] = "<table border='1' style='border-collapse: collapse; margin-top: 10px;'>";
                                        $malware_details[] = "<tr><th>Record ID</th><th>Malware Content</th></tr>";
                                        $malware_details[] = "<tr>";
                                        $malware_details[] = "<td valign=top>" . htmlentities($record_id) . "</td>";
                                        $malware_details[] = "<td valign=top><textarea rows='5' cols='100'>" . $malware_data . "</textarea></td>";
                                        $malware_details[] = "</tr>";
                                        $malware_details[] = "</table>";
                                    }
                                }
                            }
                        }
                    }
                }

                if ($found_malware) {
                    echo "<td valign=top>⚠️ Malware Detected</td>";
                    echo "<td valign=top>" . implode("", $malware_details) . "</td>";
                } else {
                    echo "<td valign=top>✅ Clean</td>";
                    echo "<td valign=top>No malware patterns found.</td>";
                }

                echo "</tr>";
            }

            echo "</table>";
            die;
        }

        // ========================= USER TOOLS ========================= 
        // 
        if ($_GET['adminz_user_login_with_user_id'] ?? '') {
            $user_id = intval($_GET['adminz_user_login_with_user_id']);
            $user = get_user_by('id', $user_id);
            if ($user) {

                // skip redirect 
                if (get_current_user_id() == $user_id) {
                    return;
                }

                // Đăng nhập người dùng
                wp_clear_auth_cookie(); // Xóa cookie hiện tại
                wp_set_current_user($user_id); // Thiết lập người dùng hiện tại
                wp_set_auth_cookie($user_id); // Thiết lập cookie đăng nhập

                // Chuyển hướng về trang chủ
                // wp_redirect( home_url() );
                // exit;
            } else {
                echo "::: User ID $user_id không tồn tại. ===================";
            }
            die;
        }


        // ========================= TEST TOOLS ========================= 			
        //
        if (($_GET['adminz_test_hooks'] ?? '') == 'wordpress') {
            add_action('shutdown', function () {
                global $wp_actions;
                // echo "<pre>"; print_r( $wp_actions ); echo "</pre>";
                // die;
                echo '<table style="background: white; width: 200px; margin: auto;">';
                $i = 1;
                $focus = [
                    'muplugins_loaded',
                    'plugins_loaded',
                    'after_setup_theme',
                    'init',
                    'widgets_init',
                    'pre_get_posts',
                    'wp_loaded',
                    'wp',
                    'template_redirect',
                    'wp_head',
                    'wp_enqueue_scripts',
                    'wp_footer',
                ];
                foreach ($wp_actions as $key => $value) {
                    if (in_array($key, $focus)) {
                        $key = "<mark>$key</mark>";
                    }

                    echo <<<HTML
						<tr>
							<td>$i</td>
							<td>$key</td>
							<td>$value</td>
						</tr>
						HTML;
                    $i++;
                }
                echo '</table>';
            });
        }

        //
        if ($_GET['adminz_test_postmeta'] ?? '') {
            $post_id = esc_attr($_GET['adminz_test_postmeta']);
            // global $wpdb;
            // $results = $wpdb->get_results(
            //     $wpdb->prepare("SELECT * FROM {$wpdb->prefix}postmeta WHERE post_id = %d", $post_id)
            // );
            // if (!empty($results)) {
            //     echo "<pre>";
            //     print_r($results);
            //     echo "</pre>";
            // } else {
            //     echo 'Meta not found: post_id = ' . $post_id;
            // }
            echo $this->view_meta_table(get_post_meta($post_id));
            die;
        }

        //
        if ($_GET['adminz_test_postfield'] ?? '') {
            $post_id = esc_attr($_GET['adminz_test_postfield']);
            $post = get_post($post_id);
            if ($post) {
                echo "<pre>";
                print_r($post);
                echo "</pre>";
            } else {
                echo 'Post not found: post_id = ' . $post_id;
            }
            die;
        }

        //
        if ($_GET['adminz_test_posttaxonomy'] ?? '') {
            $post_id = esc_attr($_GET['adminz_test_posttaxonomy']);
            $post = get_post($post_id);
            if ($post) {
                // Lấy tất cả các taxonomy của post type
                $taxonomies = get_object_taxonomies($post->post_type, 'objects');

                foreach ($taxonomies as $taxonomy) {
                    $terms = get_the_terms($post_id, $taxonomy->name);
                    if (!empty($terms)) {
                        echo "<pre>";
                        print_r('---------- Taxonomy: ' . $taxonomy->name . ' - ' . $taxonomy->label);
                        echo "</pre>";
                        echo "<pre>";
                        print_r($terms);
                        echo "</pre>";
                    }
                }
            } else {
                echo 'Post not found: post_id = ' . $post_id;
            }
            die;
        }

        //
        if ($_GET['adminz_test_termfield'] ?? '') {
            $term_id = esc_attr($_GET['adminz_test_termfield']);
            global $wpdb;
            $results = get_term($term_id);
            if (!empty($results)) {
                echo "<pre>";
                print_r($results);
                echo "</pre>";
            } else {
                echo 'Term meta not found: term_id = ' . $term_id;
            }
            die;
        }

        //
        if ($_GET['adminz_test_termmeta'] ?? '') {
            $term_id = esc_attr($_GET['adminz_test_termmeta']);
            // global $wpdb;
            // $results = $wpdb->get_results(
            //     $wpdb->prepare("SELECT * FROM {$wpdb->prefix}termmeta WHERE term_id = %d", $term_id)
            // );
            // if (!empty($results)) {
            //     echo "<pre>";
            //     print_r($results);
            //     echo "</pre>";
            // } else {
            //     echo 'Term meta not found: term_id = ' . $term_id;
            // }
            echo $this->view_meta_table(get_term_meta($term_id));
            die;
        }

        //
        if ($_GET['adminz_test_userfield'] ?? '') {
            $user_id = esc_attr($_GET['adminz_test_userfield']);
            global $wpdb;
            $results = get_user_by('id', $user_id);
            if (!empty($results)) {
                echo "<pre>";
                print_r($results);
                echo "</pre>";
            } else {
                echo 'User not found: user_id = ' . $user_id;
            }
            die;
        }

        //
        if ($_GET['adminz_test_usermeta'] ?? '') {
            $user_id = esc_attr($_GET['adminz_test_usermeta']);
            // global $wpdb;
            // $results = $wpdb->get_results(
            //     $wpdb->prepare("SELECT * FROM {$wpdb->prefix}usermeta WHERE user_id = %d", $user_id)
            // );
            // if (!empty($results)) {
            //     echo "<pre>";
            //     print_r($results);
            //     echo "</pre>";
            // } else {
            //     echo 'User not found: user_id = ' . $user_id;
            // }
            echo $this->view_meta_table(get_user_meta($user_id));
            die;
        }

        //
        if ($_GET['adminz_test_theme_template'] ?? '') {
            add_action('wp_footer', function () {
                global $template;
                echo '<div style="position:fixed;bottom:0;left:0;background:#000;color:#fff;padding:10px;z-index:9999;">' . basename($template) . '</div>';
            });
        }

        // ========================= TEST TOOLS ========================= 
        //
        if (isset($_GET['adminz_rewrite_rules_explore'])) {
            echo "<h2>::: Rewrite Rules Explorer =================</h2>";

            // Lấy tất cả các rewrite rules
            global $wp_rewrite;
            $rewrite_rules = $wp_rewrite->wp_rewrite_rules();

            if (!empty($rewrite_rules)) {
                echo "<table border='1' style='border-collapse: collapse;'>";
                echo "<tr><th>#</th><th>Rule</th><th>Rewrite</th></tr>";

                $counter = 1; // Biến đếm số thứ tự
                foreach ($rewrite_rules as $rule => $rewrite) {
                    echo "<tr>";
                    echo "<td style='text-align: center; vertical-align: top; padding: 5px;'>{$counter}</td>"; // Cột số thứ tự
                    echo "<td style='vertical-align: top; padding: 5px;'><code>{$rule}</code></td>";
                    echo "<td style='vertical-align: top; padding: 5px;'><code>{$rewrite}</code></td>";
                    echo "</tr>";
                    $counter++; // Tăng biến đếm
                }

                echo "</table>";
            } else {
                echo "<p>No rewrite rules found.</p>";
            }

            die; // Dừng script sau khi hiển thị
        }

        //
        if (isset($_GET['adminz_rewrite_rules_flush'])) {
            echo "<h2>::: Flush Rewrite Rules =================</h2>";

            // Làm mới rewrite rules
            flush_rewrite_rules();

            echo "<p>✅ Rewrite rules have been flushed successfully.</p>";
            die; // Dừng script sau khi hiển thị
        }

        // ========================= Others TOOLS ========================= 
        //
        if (isset($_GET['adminz_show_debug_log'])) {
            $debug_log_path = WP_CONTENT_DIR . '/debug.log';

            if (file_exists($debug_log_path)) {
                header('Content-Type: text/plain');
                readfile($debug_log_path);
                exit;
            } else {
                echo 'Không tìm thấy tệp debug.log';
                exit;
            }
        }

        // 
        if (isset($_GET['adminz_delete_all_products'])) {
            global $wpdb;

            $post_types = ['product', 'product_variation'];
            $posts = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('%s', '%s')",
                    $post_types[0],
                    $post_types[1]
                )
            );

            if (!empty($posts)) {
                $post_ids = wp_list_pluck($posts, 'ID');
                $post_ids_imploded = implode(',', array_map('absint', $post_ids));

                $wpdb->query("DELETE FROM {$wpdb->posts} WHERE ID IN ($post_ids_imploded)");
                $wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($post_ids_imploded)");

                $wpdb->query(
                    "DELETE tr, tt FROM {$wpdb->term_relationships} tr
                        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                        WHERE tr.object_id IN ($post_ids_imploded)"
                );

                $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_post_ID IN ($post_ids_imploded)");
                $wpdb->query(
                    "DELETE cm FROM {$wpdb->commentmeta} cm
                        INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
                        WHERE c.comment_post_ID IN ($post_ids_imploded)"
                );

                // Xóa trong lookup tables của WooCommerce
                $wpdb->query("DELETE FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id IN ($post_ids_imploded)");
                $wpdb->query("DELETE FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id IN ($post_ids_imploded)");

                echo ' done!';
            } else {
                echo ' no post found ';
            }
            die;
        }

        // xóa ảnh trong wp_uploads nếu ko còn trong db
        if (isset($_GET['adminz_delete_image_files_without_id'])) {
            global $wpdb;

            $upload_dir = wp_get_upload_dir();
            $base_upload_path = $upload_dir['basedir'];

            // Handle Confirmation
            if (isset($_POST['confirm_delete_files'])) {
                $selected_files = isset($_POST['selected_files']) ? $_POST['selected_files'] : [];
                if (!empty($selected_files)) {
                    echo "<h2>Deleting selected files...</h2>";
                    foreach ($selected_files as $file_rel_path) {
                        $file_full_path = realpath($base_upload_path . '/' . $file_rel_path);
                        // Security check: ensure path is within uploads
                        if ($file_full_path && strpos($file_full_path, realpath($base_upload_path)) === 0) {
                            if (file_exists($file_full_path)) {
                                if (@unlink($file_full_path)) {
                                    echo "✅ Deleted file: {$file_rel_path} <br>";
                                } else {
                                    echo "❌ Failed to delete file: {$file_rel_path} <br>";
                                }
                            }
                        }
                    }
                } else {
                    echo "<p>No files selected for deletion.</p>";
                }
                echo "<p><a href='" . esc_url(remove_query_arg(['confirm_delete_files'])) . "'>Back to scan</a></p>";
                die;
            }

            // Scan for files
            echo "<h2>::: Scanning for Image Files Not in Database</h2>";
            echo "<p>Building database file map...</p>";

            // Get all attached files from DB to compare
            $attached_files = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file'");
            $metadata = $wpdb->get_col("SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attachment_metadata'");

            $known_files = array_flip($attached_files);

            foreach ($metadata as $m) {
                $m = maybe_unserialize($m);
                if ($m && isset($m['file'])) {
                    $known_files[$m['file']] = true;
                    if (isset($m['sizes']) && is_array($m['sizes'])) {
                        $base_path = dirname($m['file']);
                        foreach ($m['sizes'] as $size) {
                            $thumb_path = ($base_path === '.' ? '' : $base_path . '/') . $size['file'];
                            $known_files[$thumb_path] = true;
                        }
                    }
                }
            }

            // Scan uploads/YYYY/MM
            $sub_folders = glob($base_upload_path . '/*', GLOB_ONLYDIR);
            $orphans = [];

            if (!function_exists('list_files')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
            }

            foreach ($sub_folders as $sub_folder) {
                $folder_name = basename($sub_folder);
                if (preg_match('/^\d{4}$/', $folder_name)) {
                    $files = list_files($sub_folder);
                    foreach ($files as $file) {
                        if (wp_check_filetype($file)['type'] && strpos(wp_check_filetype($file)['type'], 'image') !== false) {
                            // Normalize path to relative
                            $rel_path = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($base_upload_path . DIRECTORY_SEPARATOR, '', $file));
                            if (!isset($known_files[$rel_path])) {
                                $path_info = pathinfo($rel_path);
                                $filename  = $path_info['filename'];
                                $dir       = $path_info['dirname'] !== '.' ? $path_info['dirname'] . '/' : '';
                                $ext       = $path_info['extension'];

                                // Rule 1 & 2: thumbnail pattern (name-WxH.ext)
                                // → kiểm tra ảnh gốc trực tiếp hoặc qua scaled version
                                if (preg_match('/^(.+)-(\d+x\d+)$/', $filename, $thumb_matches)) {
                                    $base_name = $thumb_matches[1];

                                    // Rule 1: e.g. Before-70-600x396.jpg → Before-70.jpg
                                    if (isset($known_files[$dir . $base_name . '.' . $ext])) {
                                        continue;
                                    }
                                    // Rule 2: e.g. 5_Before-100x100.jpg → 5_Before-scaled.jpg
                                    if (isset($known_files[$dir . $base_name . '-scaled.' . $ext])) {
                                        continue;
                                    }
                                }

                                // Rule 3: pre-scaled original (name.jpg khi DB lưu name-scaled.jpg)
                                // WP giữ file gốc trên disk nhưng _wp_attached_file trỏ đến -scaled
                                if (isset($known_files[$dir . $filename . '-scaled.' . $ext])) {
                                    continue;
                                }

                                // Rule 4: rotated hoặc edited backup
                                // e.g. photo-rotated.jpg, photo-e1609459200.jpg
                                if (preg_match('/^(.+)-(rotated|e\d+)$/', $filename, $edit_matches)) {
                                    $base_name = $edit_matches[1];
                                    // Kiểm tra ảnh gốc hoặc scaled version
                                    if (
                                        isset($known_files[$dir . $base_name . '.' . $ext]) ||
                                        isset($known_files[$dir . $base_name . '-scaled.' . $ext])
                                    ) {
                                        continue;
                                    }
                                }

                                $orphans[] = $rel_path;
                            }
                        }
                    }
                }
            }

            if (empty($orphans)) {
                echo "<p>✅ No orphaned image files found.</p>";
            } else {
                echo "<p>Found " . count($orphans) . " files not referenced in database.</p>";
                echo "<form method='POST'>";
                wp_nonce_field('adminz_tool');
                echo "<table border='1'>";
                echo "<tr>
                            <th>#</th>
                            <th>File Relation Path</th>
                            <th>Preview</th>
                            <th>Delete? <br><input type='checkbox' id='check_all_files' checked></th>
                          </tr>";

                $upload_url = wp_get_upload_dir()['baseurl'];
                foreach ($orphans as $index => $file_rel) {
                    $file_url = $upload_url . '/' . $file_rel;
                    echo "<tr>";
                    echo "<td>" . ($index + 1) . "</td>";
                    echo "<td><code>{$file_rel}</code></td>";
                    echo "<td><a href='{$file_url}' target='_blank'><img src='{$file_url}' style='max-width: 50px; max-height: 50px;'></a></td>";
                    echo "<td style='padding: 8px; text-align: center;'><input type='checkbox' name='selected_files[]' value='{$file_rel}' class='file_checkbox' checked></td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<br>";
                echo "<button type='submit' name='confirm_delete_files' class='button button-primary'>Confirm Delete Selected Files</button>";
                echo "</form>";

                echo "
                    <script>
                    document.getElementById('check_all_files').addEventListener('change', function(e) {
                        const checkboxes = document.querySelectorAll('.file_checkbox');
                        checkboxes.forEach(cb => cb.checked = e.target.checked);
                    });
                    </script>
                    ";
            }
            die;
        }

        // clean db image, nếu file ko còn trong wp_uploads
        if (isset($_GET['adminz_delete_image_ids_without_file'])) {
            global $wpdb;

            $upload_dir = wp_get_upload_dir();
            $base_upload_path = $upload_dir['basedir'];

            // Handle Confirmation
            if (isset($_POST['confirm_delete']) && !empty($_POST['selected_ids'])) {
                $selected_ids = array_map('intval', $_POST['selected_ids']);
                echo "<h2>Deleting selected attachments...</h2>";
                foreach ($selected_ids as $id) {
                    $meta_value = get_post_meta($id, '_wp_attached_file', true);
                    $file_path = $base_upload_path . '/' . $meta_value;

                    if (!empty($meta_value) && !file_exists($file_path)) {
                        wp_delete_attachment($id, true);
                        echo "✅ Deleted attachment ID: {$id} <br>";
                    } else {
                        echo "⚠️ Skipped attachment ID: {$id} (File exists or empty meta) <br>";
                    }
                }
                echo "<p><a href='" . esc_url(remove_query_arg(['confirm_delete'])) . "'>Back to scan</a></p>";
                die;
            }

            // Scan for orphans
            $attachments = $wpdb->get_results("
					SELECT ID, meta_value FROM {$wpdb->posts} 
					LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id
					WHERE post_type = 'attachment' AND meta_key = '_wp_attached_file'
				");

            $orphans = [];
            foreach ($attachments as $attachment) {
                $file_path = $base_upload_path . '/' . $attachment->meta_value;
                if (!empty($attachment->meta_value) && !file_exists($file_path)) {
                    $orphans[] = [
                        'ID' => $attachment->ID,
                        'file' => $attachment->meta_value,
                        'path' => $file_path
                    ];
                }
            }

            echo "<h2>::: Delete Attachment Records Without Image Files</h2>";

            if (empty($orphans)) {
                echo "<p>No orphaned attachment records found.</p>";
            } else {
                echo "<p>Found " . count($orphans) . " orphaned records.</p>";
                echo "<form method='POST'>";
                wp_nonce_field('adminz_tool');
                echo "<table border='1'>";
                echo "<tr>
                            <th>#</th>
                            <th>ID</th>
                            <th>Attached File</th>
                            <th>Full Path</th>
                            <th>Delete? <br><input type='checkbox' id='check_all' checked></th>
                          </tr>";

                foreach ($orphans as $index => $orphan) {
                    echo "<tr>";
                    echo "<td>" . ($index + 1) . "</td>";
                    echo "<td>{$orphan['ID']}</td>";
                    echo "<td>{$orphan['file']}</td>";
                    echo "<td><code>{$orphan['path']}</code></td>";
                    echo "<td><input type='checkbox' name='selected_ids[]' value='{$orphan['ID']}' class='orphan_checkbox' checked></td>";
                    echo "</tr>";
                }
                echo "</table>";
                echo "<br>";
                echo "<button type='submit' name='confirm_delete'>Confirm Delete Selected</button>";
                echo "</form>";

                // Add a tiny script for check all
                echo "
                    <script>
                    document.getElementById('check_all').addEventListener('change', function(e) {
                        const checkboxes = document.querySelectorAll('.orphan_checkbox');
                        checkboxes.forEach(cb => cb.checked = e.target.checked);
                    });
                    </script>
                    ";
            }
            die;
        }

        //
        if (isset($_GET['adminz_test_rewrite_rules'])) {
            global $wp_rewrite;
            $rules = $wp_rewrite->wp_rewrite_rules();

            echo '<div class="wrap">';
            echo '<h1>Rewrite Rules</h1>';
            echo '<table class="widefat fixed striped">';
            echo '<thead><tr><th>Regex</th><th>Query</th></tr></thead>';
            echo '<tbody>';

            if (!empty($rules)) {
                foreach ($rules as $regex => $query) {
                    echo '<tr>';
                    echo '<td><code>' . esc_html($regex) . '</code></td>';
                    echo '<td><code>' . esc_html($query) . '</code></td>';
                    echo '</tr>';
                }
            } else {
                echo '<tr><td colspan="2">Không có rewrite rules nào.</td></tr>';
            }

            echo '</tbody></table>';
            echo '</div>';
            die;
        }
    }

    /**
     * Chuyển đổi quyền truy cập từ dạng số sang dạng ký tự (ví dụ: -rwxr-xr-x)
     */
    function get_permissions_human_readable($permissions) {
        $type = is_dir($permissions) ? 'd' : '-';
        $owner = ($permissions & 0x0100) ? 'r' : '-';
        $owner .= ($permissions & 0x0080) ? 'w' : '-';
        $owner .= ($permissions & 0x0040) ? 'x' : '-';
        $group = ($permissions & 0x0020) ? 'r' : '-';
        $group .= ($permissions & 0x0010) ? 'w' : '-';
        $group .= ($permissions & 0x0008) ? 'x' : '-';
        $others = ($permissions & 0x0004) ? 'r' : '-';
        $others .= ($permissions & 0x0002) ? 'w' : '-';
        $others .= ($permissions & 0x0001) ? 'x' : '-';
        return $type . $owner . $group . $others;
    }

    function set_view_file() {
        if (isset($_GET['view_file'])) {
            $file_path = urldecode($_GET['view_file']);
            if (file_exists($file_path) && pathinfo($file_path, PATHINFO_EXTENSION) === 'php') {
                if (str_contains($file_path, 'wp-config.php')) {
                    echo "wp-config.php is ignored for security reasons.";
                } else {
                    echo "<h2>Viewing File: {$file_path}</h2>";
                    echo "<pre style='white-space: pre-wrap; word-wrap: break-word; background: #f4f4f4; padding: 10px; border: 1px solid #ccc;'>";
                    echo htmlspecialchars(file_get_contents($file_path));
                    echo "</pre>";
                }
            } else {
                echo "Invalid file path.";
            }
            die;
        }
    }

    function view_meta_table($metavalue) {
        // kiểm tra dữ liệu đầu vào
        if (empty($metavalue) || !is_array($metavalue)) {
            return '<em>Meta rỗng hoặc không hợp lệ</em>';
        }

        // khởi tạo html
        $html = '';
        $html .= '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
        $html .= '<thead>';
        $html .= '<tr>';
        $html .= '<th style="text-align:left;">Meta key</th>';
        $html .= '<th style="text-align:left;">Meta value</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($metavalue as $meta_key => $meta_values) {

            // lấy value đầu tiên (chuẩn WP meta)
            $raw_value = $meta_values[0] ?? '';

            // unserialize nếu cần
            $value = maybe_unserialize($raw_value);

            $html .= '<tr>';

            // cột meta key
            $html .= '<td style="vertical-align:top;">' . esc_html((string) $meta_key) . '</td>';

            // cột meta value: chỉ print_r trong <pre>
            $html .= '<td><pre>' . esc_html(print_r($value, true)) . '</pre></td>';

            $html .= '</tr>';
        }

        $html .= '</tbody>';
        $html .= '</table>';

        return $html;
    }
}
