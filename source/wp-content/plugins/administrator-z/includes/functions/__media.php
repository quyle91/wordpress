<?php

/**
 * enctype="multipart/form-data"
 * input type='file' multiple
 * for (const file of upload.files) { formData.append('files[]', file); }
 * $uploaded_file = $_FILES['banner_id_upload'];
 */

function adminz_upload_media($uploaded_file) {
    $return = false;
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');
    $movefile = wp_handle_upload($uploaded_file, array('test_form' => false));
    if ($movefile && !isset($movefile['error'])) {
        $attachment = array(
            'post_mime_type' => $movefile['type'],
            'post_title' => preg_replace('/[^.]+$/', '', basename($movefile['file'])),
            'post_content' => '',
            'post_status' => 'inherit',
        );
        $attach_id = wp_insert_attachment($attachment, $movefile['file']);
        if (!is_wp_error($attach_id)) {
            $attach_data = wp_generate_attachment_metadata($attach_id, $movefile['file']);
            wp_update_attachment_metadata($attach_id, $attach_data);
            $return = $attach_id;
        }
    }
    return $return;
}

function adminz_replace_media($file) {
    global $wpdb;
    // Truy vấn cơ sở dữ liệu để tìm ID ảnh cũ dựa trên tên file
    $sql = $wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE guid LIKE %s LIMIT 1", '%' . $file['name']);
    $result = $wpdb->get_results($sql);
    $old = get_post($result[0]->ID);
    if ($old) {
        // Lấy thông tin về ảnh cũ
        $oldid = $old->ID;
        $parent = $old->post_parent;

        // Lấy các metadata của ảnh cũ
        $meta = get_post_meta($oldid);
        $_wp_attached_file = $meta['_wp_attached_file'][0];
        $_wp_attachment_metadata = $meta['_wp_attachment_metadata'][0];
        $olddir = "/" . substr($_wp_attached_file, 0, 7);

        // Tìm các post có sử dụng ảnh cũ như thumbnail
        $post_set_thumbnail = $wpdb->get_results("
            SELECT post_id
            FROM $wpdb->postmeta
            WHERE meta_key = '_thumbnail_id'
            AND meta_value = " . $oldid . "
        ");

        // Xóa ảnh cũ
        wp_delete_attachment($oldid, true);

        // Tải lên ảnh mới
        $_filterhook = true;
        add_filter('upload_dir', function ($arr) use (&$_filterhook, $olddir) {
            if ($_filterhook) {
                $target = $olddir;
                $arr['path'] = str_replace($arr['subdir'], "", $arr['path']) . $target;
                $arr['url'] = str_replace($arr['subdir'], "", $arr['url']) . $target;
                $arr['subdir'] = $target;
            }
            return $arr;
        });
        $res = wp_upload_bits($file['name'], null, file_get_contents($file['tmp_name']));
        $dirs = wp_upload_dir();
        $_filterhook = false; // Xóa bộ lọc
        // Cập nhật metadata của ảnh mới
        $restype = wp_check_filetype($res['file']);
        $attachment = array(
            'guid' => $dirs['baseurl'] . '/' . _wp_relative_upload_path($res['file']),
            'post_mime_type' => $restype['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($res['file'])),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_parent' => $parent,
        );
        $attach_id = wp_insert_attachment($attachment, $res['file']);
        $attach_data = wp_generate_attachment_metadata($attach_id, $res['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Cập nhật các liên kết và metadata của ảnh cũ thành ảnh mới
        $wpdb->update(
            $wpdb->posts,
            array('ID' => $oldid),
            array('ID' => $attach_id)
        );
        $wpdb->update(
            $wpdb->postmeta,
            array('post_id' => $oldid),
            array('post_id' => $attach_id)
        );
        $attach_id = $oldid;

        // Đặt thuộc tính ảnh mới là thumbnail cho các post đã được thiết lập trước đó
        if (!empty($post_set_thumbnail) && is_array($post_set_thumbnail)) {
            foreach ($post_set_thumbnail as $key => $value) {
                set_post_thumbnail($value->post_id, $attach_id);
            }
        }

        // Hiển thị thông báo thành công
        echo '<a target="blank" href="' . get_permalink($attach_id) . '">Success</a>';
    } else {
        // Hiển thị thông báo không tìm thấy ảnh cũ
        echo 'Old image not found';
    }
}
