<?php
function adminz_explode($separator, $value, $default = []) {
    return $value ? explode($separator, $value) : $default;
}
