<?php

namespace Adminz\Helper;

class Watermark {
    // private static $instance = null;
    // public static function get_instance() {
    // 	if ( is_null( self::$instance ) ) {
    // 		self::$instance = new self();
    // 	}
    // 	return self::$instance;
    // }

    public $watermark_id;
    public $watermark_image;
    public static $backup_folder = '/administrator-z/watermark_backup';
    public static $test_folder = '/administrator-z/watermark_backup/test';
    public $transient_name = 'image_watermark1';

    function __construct() {
    }

    function init() {
        add_action('init', [$this, 'run_watermark']);
        add_action('init', [$this, 'test_watermark']);
        add_filter('wp_generate_attachment_metadata', [$this, 'add_watermark_images'], 10, 2);
        add_action('wp_ajax_adminz_restore_watermark', [$this, 'adminz_restore_watermark']);
    }

    function adminz_restore_watermark() {
        if (!wp_verify_nonce($_POST['nonce'], 'adminz_js')) exit;
        $return = false;

        ob_start();

        // code here
        $images = json_decode(stripslashes($_POST['data']), true);
        foreach ((array)$images as $key => $value) {
            $image_id = $value['id'] ?? false;
            if (!$image_id) {
                continue;
            }

            if ($this->remove_watermark_image($image_id)) {
                $link = wp_get_attachment_url($image_id);
                $image_name = get_post_field('guid', $image_id);
?>
                <p>
                    <?= __('Done') ?>:
                    <a href="<?= esc_url($link) ?>" target=_blank>
                        <?= esc_attr($image_name) ?>
                    </a>
                </p>
                <?php
            }
        }
        echo '<p>Dont forget press <strong>Ctrl + F5</strong> </p>';
        echo '<p>Maybe you need <a target=_blank href="https://vi.wordpress.org/plugins/regenerate-thumbnails-advanced/">reGenerate Thumbnails Advanced</a> </p>';
        $return = ob_get_clean();

        if (!$return) {
            wp_send_json_error('Error');
            wp_die();
        }

        wp_send_json_success($return);
        wp_die();
    }

    public static function get_test_folder() {
        $return = WP_CONTENT_DIR . "/uploads" . self::$test_folder;
        if (!file_exists($return)) {
            mkdir($return, 0755, true);
        }
        return $return;
    }

    public static function get_backup_folder() {
        $return = WP_CONTENT_DIR . "/uploads" . self::$backup_folder;
        if (!file_exists($return)) {
            mkdir($return, 0755, true);
        }
        return $return;
    }

    public static function check_gd_library() {
        if (extension_loaded('gd') && function_exists('gd_info')) {
            return true;
        }
    }

    function test_watermark() {

        // only for get param
        if (!isset($_GET['adminz_test_watermark'])) {
            return;
        }

        // only for admin
        if (!current_user_can('administrator')) {
            return;
        }

        // only valid watermark
        if (!$this->get_watermark_image()) {
            die("watermark not valid! </br> You can use: https://cloudconvert.com/png-converter");
        }

        $__args = [
            'post_type' => ['attachment'],
            'post_status' => ['inherit'],
            'posts_per_page' => 1,
        ];

        $posts = get_posts($__args); // Có thể sử dụng get_posts để tránh lỗi overhead
        if (!empty($posts) and is_array($posts)) {
            foreach ($posts as $key => $post) {
                $image_id = $post->ID;
                $images = $this->test_watermark_image($image_id);
                if (empty($images)) {
                    continue;
                }

                foreach ($images as $index => $image) {
                    if ($image instanceof \GdImage) {
                        // Lưu hình ảnh tạm thời vào một tệp
                        $test_folder    = $this->get_test_folder();
                        $test_file_path = $test_folder . '/' . basename($index) . '.jpg';
                        imagejpeg($image, $test_file_path); // Lưu tệp hình ảnh
                        imagedestroy($image); // Giải phóng bộ nhớ

                        // Xuất hình ảnh ra HTML
                        $test_file_url = wp_upload_dir()['baseurl'] . self::$test_folder . '/' . basename($index) . '.jpg';
                        $ximage = imagesx($image);
                        $yimage = imagesy($image);
                        echo <<<HTML
                            <div>
                                <img src="$test_file_url" alt="" style="max-width: 100%;">
                                <pre>
                                    $ximage x $yimage
                                </pre>
                            </div>
                            HTML;
                    }
                }
            }
        } else {
            echo __('Sorry, no posts matched your criteria.');
        }
        exit;
    }

    function run_watermark() {

        // only for get param
        if (!isset($_GET['adminz_run_watermark'])) {
            return;
        }

        // only for admin
        if (!current_user_can('administrator')) {
            return;
        }

        // only valid watermark
        if (!$this->get_watermark_image()) {
            die("watermark not valid! </br> You can use: https://cloudconvert.com/png-converter");
        }

        global $wpdb;

        // default array
        $ran_ids = get_transient($this->transient_name);
        if (!$ran_ids) $ran_ids = [0];

        // ignore waterwark id
        $ran_ids[] = $this->watermark_id;
        $ran_ids = array_map('intval', $ran_ids);
        $placeholders = implode(',', array_fill(0, count($ran_ids), '%d'));
        $sql = $wpdb->prepare(
            "
			SELECT ID 
			FROM $wpdb->posts 
			WHERE post_type = 'attachment' 
			AND post_mime_type LIKE 'image/%' 
			AND ID NOT IN ($placeholders)
			",
            $ran_ids
        );
        $image_ids = $wpdb->get_col($sql);

        if (!empty($image_ids)) {
            foreach ((array) $image_ids as $key => $image_id) {
                $this->add_watermark_image($image_id);
                echo "<pre>";
                print_r($image_id . "------------- DONE ----------------- ");
                echo "</pre>";
                $ran_ids[] = $image_id;
            }
            set_transient($this->transient_name, $ran_ids, DAY_IN_SECONDS);
        } else {
            echo 'No image found!';
        }

        echo "<pre>";
        print_r('------------- IMAGES PROCESSED -------------');
        echo "</pre>";
        echo "<pre>";
        print_r($ran_ids);
        echo "</pre>";

        exit;
    }

    function remove_watermark_image($attachment_id) {
        // get paths
        $paths = $this->get_image_paths($attachment_id);
        $statuses = [];

        // Áp dụng watermark cho tất cả các file trong danh sách
        foreach ((array) $paths as $key => $path) {
            $statuses[] = $this->restoreWatermark($path);
        }

        return (count(array_unique($statuses)) == 1);
    }

    function get_image_paths($attachment_id) {
        // Kiểm tra xem attachment có tồn tại hay không
        $attachment_path = get_attached_file($attachment_id);

        if (!$attachment_path || !file_exists($attachment_path)) {
            return false; // Nếu không tồn tại, kết thúc function
        }

        // Lấy metadata của attachment
        $metadata = wp_get_attachment_metadata($attachment_id);

        if (empty($metadata) || !isset($metadata['file'])) {
            return false;
        }

        // Lấy đường dẫn upload base
        $upload_dir = wp_upload_dir();

        // Tập hợp các đường dẫn cần xử lý
        $paths = [];

        // origin file
        $origin_file_path = str_replace('-scaled', '', $upload_dir['basedir'] . '/' . $metadata['file']);
        if (file_exists($origin_file_path)) {
            $paths[] = $origin_file_path;
        }

        // scaled
        $scaled_file_path = $upload_dir['basedir'] . '/' . $metadata['file'];
        if (file_exists($scaled_file_path)) {
            $paths[] = $scaled_file_path;
        }

        // Thêm các kích thước khác vào danh sách
        if (isset($metadata['sizes']) && is_array($metadata['sizes'])) {
            foreach ($metadata['sizes'] as $size => $size_info) {
                $size_file_path = $upload_dir['basedir'] . '/' . dirname($metadata['file']) . '/' . $size_info['file'];
                if (file_exists($size_file_path)) {
                    $paths[] = $size_file_path;
                }
            }
        }

        // Loại bỏ trùng lặp (nếu có)
        $paths = array_unique($paths);

        return $paths;
    }

    function test_watermark_image($attachment_id) {
        // get paths
        $paths = $this->get_image_paths($attachment_id);

        // Áp dụng watermark cho tất cả các file trong danh sách
        $return = [];
        foreach ((array) $paths as $key => $path) {
            $image = $this->testWatermark($path);
            if ($image instanceof \GdImage) {
                $return[] = $image; // Lưu hình ảnh vào danh sách
            }
        }

        return $return;
    }

    function add_watermark_image($attachment_id) {
        // get paths
        $paths = $this->get_image_paths($attachment_id);

        // Áp dụng watermark cho tất cả các file trong danh sách
        foreach ((array) $paths as $key => $path) {
            $this->setWatermark($path);
        }

        return true;
    }

    function add_watermark_images($metadata, $attachment_id) {
        $this->add_watermark_image($attachment_id);
        return $metadata;
    }

    function backup_image($file_path) {
        $backup_folder = $this->get_backup_folder();
        $backup_file_path = $backup_folder . '/' . basename($file_path);
        rename($file_path, $backup_file_path); // Di chuyển ảnh cũ vào backup
        error_log(json_encode("Backup: " . $backup_file_path));
    }

    function restore_image($file_path) {
        $backup_folder = $this->get_backup_folder();
        $backup_file_path = $backup_folder . '/' . basename($file_path);
        if (file_exists($backup_file_path)) {
            // xoá $file_path
            unlink($file_path);
            // move $backup_file_path về file_path
            rename($backup_file_path, $file_path);
            return true;
        }
        return false;
    }

    function save_image($image, $file_path) {
        $file_extension = pathinfo($file_path, PATHINFO_EXTENSION);
        switch (strtolower($file_extension)) {
            case 'jpeg':
            case 'jpg':
                imagejpeg($image, $file_path, 100);
                break;
            case 'png':
                imagepng($image, $file_path, 0);
                break;
            case 'gif':
                imagegif($image, $file_path);
                break;
            default:
                // Không hỗ trợ loại file
                return;
        }
    }

    function copy_watermark_to_image($image, $watermark) {
        // Lấy kích thước ảnh gốc
        $image_width  = imagesx($image);
        $image_height = imagesy($image);

        // Tính toán kích thước watermark
        $watermark_height_max = $image_height * 0.1; // Chiều cao tối đa 10% của ảnh
        $watermark_width      = imagesx($watermark); // Chiều rộng của watermark
        $watermark_height     = imagesy($watermark); // Chiều cao thực tế của watermark

        // Nếu chiều cao watermark lớn hơn tối đa, điều chỉnh lại chiều rộng
        if ($watermark_height > $watermark_height_max) {
            $scale_factor         = $watermark_height_max / $watermark_height;
            $new_watermark_width  = (int) ($watermark_width * $scale_factor);
            $new_watermark_height = (int) ($watermark_height * $scale_factor);
            $scaled_watermark     = imagecreatetruecolor($new_watermark_width, $new_watermark_height);

            // Bật chế độ xử lý alpha
            imagealphablending($scaled_watermark, false);
            imagesavealpha($scaled_watermark, true);

            // Tạo màu trong suốt
            $transparent = imagecolorallocatealpha($scaled_watermark, 255, 255, 255, 127);
            imagefill($scaled_watermark, 0, 0, $transparent);

            imagecopyresampled($scaled_watermark, $watermark, 0, 0, 0, 0, $new_watermark_width, $new_watermark_height, $watermark_width, $watermark_height);
            $watermark = $scaled_watermark; // Cập nhật watermark với kích thước mới
        }

        // Tính toán vị trí chèn watermark
        $y = (int) ($image_height * 0.5); // Vị trí từ đỉnh là 70%

        // Lặp lại watermark theo chiều ngang
        for ($x = 0; $x < $image_width; $x += imagesx($watermark)) {
            imagecopy(
                $image,
                $watermark,
                $x,
                $y,
                0,
                0,
                imagesx($watermark),
                imagesy($watermark)
            );
        }

        return $image;
    }

    function restoreWatermark($file_path) {
        return $this->restore_image($file_path);
    }

    function testWatermark($file_path) {
        $watermark = $this->get_watermark_image();

        if (!$file_path) {
            return;
        }

        if (!$watermark) {
            return;
        }

        // Kiểm tra loại file và tạo ảnh gốc tương ứng
        $image = $this->getGdImage($file_path);
        if (!$image) {
            return;
        }

        // copy watermark to image
        $image = $this->copy_watermark_to_image($image, $watermark);

        // Giải phóng bộ nhớ
        imagedestroy($image);
        imagedestroy($watermark);

        return $image;
    }

    function setWatermark($file_path) {

        $watermark = $this->get_watermark_image();

        if (!$file_path) {
            return;
        }

        if (!$watermark) {
            return;
        }

        // Kiểm tra loại file và tạo ảnh gốc tương ứng
        $image = $this->getGdImage($file_path);
        if (!$image) {
            return;
        }

        // copy watermark to image
        $image = $this->copy_watermark_to_image($image, $watermark);

        // Di chuyển ảnh cũ vào thư mục backup
        $this->backup_image($file_path);

        // Lưu ảnh đã được chèn watermark theo đúng định dạng file gốc
        $this->save_image($image, $file_path);

        // Giải phóng bộ nhớ
        imagedestroy($image);
        imagedestroy($watermark);
    }

    function get_watermark_image() {

        // return if exists
        if ($this->watermark_image) {
            return $this->watermark_image;
        }

        // check gd library
        if (!$this->check_gd_library()) {
            return;
        }

        if ($watermark_image = $this->getGdImage()) {
            $this->watermark_image = $watermark_image;
            return $this->watermark_image;
        }
        return false;
    }

    function getGdImage($file_path = false) {
        if (!$file_path) {
            $file_path = get_attached_file($this->watermark_id);
        }
        $filetype = wp_check_filetype(basename($file_path), null);

        $return = false;
        switch ($filetype['type']) {
            case 'image/jpeg':
                $return = @imagecreatefromjpeg($file_path);
                break;
            case 'image/png':
                $return = @imagecreatefrompng($file_path);
                break;
            case 'image/gif':
                $return = @imagecreatefromgif($file_path);
                break;
        }
        return $return;
    }
}
