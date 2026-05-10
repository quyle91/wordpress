<?php
namespace Adminz\Helper;

class Comment {
	function __construct() {
		//
	}

    function init(){
        add_filter('comment_form_default_fields', [$this, 'custom_comment_form_fields'], 10, 1);
        add_action('preprocess_comment', [$this, 'check_comment_spam'], 10, 1);
    }

	// Thêm một trường ẩn vào form comment
	function custom_comment_form_fields( $fields ) {
		$fields['devvn'] = '<div style="display: none"><input type="text" name="devvn" id="devvn" value="" /></div>';
		return $fields;
	}

	// Kiểm tra nếu trường ẩn này có giá trị thì đánh dấu là spam
	function check_comment_spam( $commentdata ) {
		if ( !empty( $_POST['devvn'] ) ) {
			$commentdata['comment_approved'] = 'spam';
			wp_die( 'Comment marked as spam.' ); // Ngăn chặn comment và hiển thị thông báo
		}
		return $commentdata;
	}

	
}