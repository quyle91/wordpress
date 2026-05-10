<?php
function adminz_user_image_auto_excerpt() {
    if (is_admin()) {
        add_action('add_attachment', function ($post_ID) {
            if (wp_attachment_is_image($post_ID)) {
                $my_image_title = get_post($post_ID)->post_title;
                $my_image_meta = array(
                    'ID' => $post_ID,            // Specify the image (ID) to be updated
                    'post_title' => $my_image_title,        // Set image Title to sanitized title
                    'post_excerpt' => $my_image_title,        // Set image Caption (Excerpt) to sanitized title
                    'post_content' => $my_image_title,        // Set image Description (Content) to sanitized title
                );
                update_post_meta($post_ID, '_wp_attachment_image_alt', $my_image_title);
                wp_update_post($my_image_meta);
            }
        });
    }
}

function adminz_user_admin_notice($notice) {
    add_action('admin_notices', function () use ($notice) {
        if (!$notice) return;
        // Chuyển ký tự xuống dòng thành thẻ <br>
        $notice = nl2br(esc_html($notice));
        $heading = __('Notifications');
        echo <<<HTML
<div class="notice is-dismissible">
<p>
<strong> $heading:</strong> <br>
{$notice} 
</p>
</div>
HTML;
    });
}
