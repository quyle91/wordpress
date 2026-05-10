<?php
function adminz_copy($text, $is_small = true) {
    $tag = $is_small ? "small" : "span";
    return <<<HTML
<{$tag} class="adminz_click_to_copy" data-text="{$text}">{$text}</{$tag}>
HTML;
}

function adminz_tab_link($name = 'AdministratorZ', $only_link = false) {
    global $adminz;

    if (!isset($adminz[$name])) {
        return;
    }

    $object = $adminz[$name];
    $link = add_query_arg(
        [
            'page' => 'administrator-z',
            'group' => $object->option_name
        ],
        admin_url('tools.php')
    );
    if ($only_link) {
        return $link;
    }
    return "<a href='$link'>{$object->name}</a>";
}
