<?php
define('WPDBH_PLUGIN_DIR', __DIR__);
if (function_exists('plugin_dir_url')) {
    define('WPDBH_PLUGIN_URL', plugin_dir_url(__FILE__));
} else {
    define('WPDBH_PLUGIN_URL', '/');
}
