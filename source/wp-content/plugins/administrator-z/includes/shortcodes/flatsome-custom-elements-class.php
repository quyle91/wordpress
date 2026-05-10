<?php
// ----------------- buttons icon
add_filter( 'ux_builder_shortcode_data', function ($data, $tag) {
	if ( in_array( $tag, [ 'divider', 'video_button' ] ) ) {
		if ( !isset( $data['options']['advanced_options'] ) ) {
			$data['options']['advanced_options'] = require( get_template_directory() . '/inc/builder/shortcodes/commons/advanced.php' );
		}
	}
	return $data;
}, 10, 2 );

add_filter( 'do_shortcode_tag', function ($output, $tag, $attr, $m) {
	if ( in_array( $tag, [ 'divider', 'video_button' ] ) ) {

		// icon
		$classes = [];
		if ( isset( $attr['class'] ) ) {
			$classes[] = $attr['class'];
		}
		if ( isset( $attr['visibility'] ) ) {
			$classes[] = $attr['visibility'];
		}

		if ( !empty( $classes ) ) {
			$pattern = '/class="/'; // tìm đúng `class="` đầu tiên
			$replacement = 'class="' . implode( " ", $classes ) . ' ';
			$output = preg_replace($pattern, $replacement, $output, 1);
		}
	}
	return $output;
}, 10, 4 );
