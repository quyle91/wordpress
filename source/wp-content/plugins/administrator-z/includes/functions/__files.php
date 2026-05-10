<?php
function adminz_listSubdirectories($parentDir, $only_name = true) {
    $return = [];
    $files = scandir($parentDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filePath = $parentDir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                if ($only_name) {
                    $return[$file] = $file;
                } else {
                    $return[$filePath] = $filePath;
                }
            }
        }
    }
    return $return;
}
