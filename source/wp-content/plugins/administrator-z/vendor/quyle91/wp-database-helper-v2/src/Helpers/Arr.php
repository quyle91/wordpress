<?php

namespace WpDatabaseHelperV2\Helpers;

class Arr {

    private static $instance = null;
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        //
    }
}
