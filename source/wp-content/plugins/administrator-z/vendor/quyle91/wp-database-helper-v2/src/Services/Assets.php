<?php

namespace WpDatabaseHelperV2\Services;

class Assets {
    protected string $version;
    protected string $plugin_dir;
    protected string $plugin_url;

    private static $instance = null;
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        $this->plugin_dir = wp_normalize_path(dirname(__DIR__, 2));
        $this->plugin_url = $this->getPluginUrl($this->plugin_dir);
        $this->version = $this->getVersion();

        // if call this method before admin_enqueue_scripts
        if (did_action('admin_enqueue_scripts')) {
            $this->enqueue();
        } else {
            add_action('admin_enqueue_scripts', [$this, 'enqueue']);
        }
    }

    private function getVersion(): string {
        $composerFile = $this->plugin_dir . '/composer.json';
        if (file_exists($composerFile)) {
            $composerData = json_decode(file_get_contents($composerFile), true);
            return $composerData['version'] ?? '0.0.0';
        }
        return '0.0.0';
    }

    private function getPluginUrl(string $path): string {
        $path = wp_normalize_path($path);
        $contentDir = wp_normalize_path(WP_CONTENT_DIR);

        if (strpos($path, $contentDir) === 0) {
            return str_replace($contentDir, WP_CONTENT_URL, $path);
        }

        // fallback: nếu nằm ngoài wp-content (hiếm gặp)
        return content_url(basename($path));
    }

    // register script/style
    public function enqueue(): void {

        // ============== global =================
        wp_enqueue_script(
            'wpdh-global',
            "$this->plugin_url/assets/js/wpdh-global.js",
            [],
            '1.0',
            true
        );

        wp_localize_script('wpdh-global', 'Wpdh', [
            'test_wpdh' => true,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpdh_nonce'),
            'i18n' => [],
        ]);

        // ============== field =================
        wp_enqueue_script(
            'wpdh-field',
            "$this->plugin_url/assets/js/field.js",
            ['jquery', 'wpdh-global'],
            $this->version,
            true
        );

        wp_enqueue_style(
            'wpdh-field',
            "$this->plugin_url/assets/css/field.css",
            [],
            $this->version
        );

        // ============== handle =================
        wp_enqueue_script(
            'wpdh-field-handleAppendRepeater',
            "$this->plugin_url/assets/js/field-handleAppendRepeater.js",
            ['jquery', 'wpdh-global'],
            $this->version,
            true
        );

        // ============== dbtable =================
        wp_enqueue_script(
            'wpdh-dbtable',
            "$this->plugin_url/assets/js/dbtable.js",
            ['jquery', 'wpdh-global', 'wpdh-field'],
            $this->version,
            true
        );

        wp_enqueue_style(
            'wpdh-dbtable',
            "$this->plugin_url/assets/css/dbtable.css",
            [],
            $this->version
        );

        // ============== repeater =================
        wp_enqueue_script(
            'wpdh-repeater',
            "$this->plugin_url/assets/js/repeater.js",
            ['jquery', 'wpdh-global', 'wpdh-field'],
            $this->version,
            true
        );

        wp_enqueue_style(
            'wpdh-repeater',
            "$this->plugin_url/assets/css/repeater.css",
            [],
            $this->version
        );

        // ============== meta =================
        wp_enqueue_script(
            'wpdh-meta',
            "$this->plugin_url/assets/js/meta.js",
            ['jquery', 'wpdh-global', 'wpdh-field'],
            $this->version,
            true
        );

        wp_enqueue_style(
            'wpdh-meta',
            "$this->plugin_url/assets/css/meta.css",
            [],
            $this->version
        );

        // ============== field: wp_media =================
        wp_enqueue_media();
        wp_enqueue_script('jquery-ui-sortable');

        // ============== Select2 =================
        wp_enqueue_style(
            'select2',
            "$this->plugin_url/assets/css/select2.min.css",
            [],
            '4.1.0-rc.0',
            'all'
        );
        
        wp_enqueue_script(
            'select2',
            "$this->plugin_url/assets/js/select2.min.js",
            ['jquery'],
            '4.1.0-rc.0',
            true
        );
    }
}
