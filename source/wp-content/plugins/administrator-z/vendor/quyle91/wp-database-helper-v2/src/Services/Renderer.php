<?php

namespace WpDatabaseHelperV2\Services;

class Renderer {
    public static function view(string $path, array $data = []): string {
        ob_start();
        extract($data);
        $file = dirname(__DIR__, 2) . '/views/' . $path . '.php';
        if (file_exists($file)) {
            include $file;
        } else {
            echo '<div class="error"><p>View not found: ' . esc_html($path) . '</p></div>';
        }
        return ob_get_clean();
    }
}
