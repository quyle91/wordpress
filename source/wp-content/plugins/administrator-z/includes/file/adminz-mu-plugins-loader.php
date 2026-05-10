<?php
// Lấy danh sách thư mục trong mu-plugins
$plugin_dirs = array_filter( glob( __DIR__ . '/*' ), 'is_dir' );

foreach ( $plugin_dirs as $dir ) {
	$plugin_file = $dir . '/' . basename( $dir ) . '.php';

	if ( file_exists( $plugin_file ) ) {
		require_once $plugin_file;
	}
}
