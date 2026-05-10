<?php
namespace Adminz\Helper;
class Photoswipe {

	public $args;

	function __construct() {
        // custom css
		adminz_add_body_class( 'adminz_photoswipe' );
	}

    function init(){
		$this->enqueue();
		$this->add_size_data_image_content();
    }

    function enqueue(){
        add_action('wp_enqueue_scripts', function(){

            // single product
			if( function_exists('is_product') and is_product()){
                return;
            }

            // js
            wp_enqueue_script(
                'adminz-photoswipe-umd-js',
                ADMINZ_DIR_URL . "assets/vendor/photoswipe/dist/umd/photoswipe.umd.min.js",
                [],
                ADMINZ_VERSION,
                true
            );
            wp_enqueue_script(
                'adminz-photoswipe-umd-lightbox-js',
                ADMINZ_DIR_URL . "assets/vendor/photoswipe/dist/umd/photoswipe-lightbox.umd.min.js",
                [],
                ADMINZ_VERSION,
                true
            );
            // css
            wp_enqueue_style(
                'adminz-photoswipe-css',
                ADMINZ_DIR_URL . "assets/vendor/photoswipe/dist/photoswipe.css",
                [],
                ADMINZ_VERSION,
                'all'
            );
			
        });
    }

    function add_size_data_image_content(){

        // from wp_get_attachment_image
		// add_filter( 'wp_get_attachment_image_attributes', function ($attr, $attachment, $size) {
		// 	$image_meta = wp_get_attachment_metadata( $attachment->ID );
		// 	if ( $image_meta ) {
		// 		$origin_src    = wp_get_attachment_url( $attachment->ID );
		// 		$origin_width  = $image_meta['width'];
		// 		$origin_height = $image_meta['height'];
		// 		$attr['adminz_origin_src']    = esc_url( $origin_src );
		// 		$attr['adminz_origin_width']  = esc_attr( $origin_width );
		// 		$attr['adminz_origin_height'] = esc_attr( $origin_height );
		// 	}
		// 	return $attr;
		// }, 10, 3 );

        // from post content
		add_filter( 'the_content', function ($content) {
			$content = preg_replace_callback( '/<img([^>]+)>/', function ($matches) {
				$img_tag = $matches[0];
				if ( preg_match( '/wp-image-(\d+)/', $img_tag, $id_matches ) ) {
					$attachment_id = $id_matches[1]; // Lấy image ID
					$image_meta = wp_get_attachment_metadata( $attachment_id );
					if ( $image_meta ) {
						$origin_src    = wp_get_attachment_url( $attachment_id );
						$origin_width  = $image_meta['width'];
						$origin_height = $image_meta['height'];
						$img_tag = preg_replace(
							'/<img([^>]+)>/',
							'<img$1 adminz_origin_src="' . esc_attr( $origin_src ) . '" adminz_origin_width="' . esc_attr( $origin_width ) . '" adminz_origin_height="' . esc_attr( $origin_height ) . '">',
							$img_tag
						);
					}
				}
				return $img_tag;
			}, $content );
			return $content;
		} );
    }

    function process($list){

		add_action('wp_enqueue_scripts', function() use($list){
			wp_enqueue_script(
				'adminz-photoswipe-js',
				ADMINZ_DIR_URL . "assets/js/adminz-photoswipe.js",
				[],
				ADMINZ_VERSION,
				true
			);
        });

        add_action('wp_footer', function()use($list){

            wp_add_inline_script(
                'adminz-photoswipe-js',
                'const adminz_photoswipe_js = ' . json_encode( apply_filters(
                    'adminz_photoswipe_js',
                    $list
                ) ),
                'before'
            );

            ?>
            <style type="text/css">
                <?php
                    foreach ((array)$list as $key => $item) {
                        ?>
                        <?php echo esc_attr($item['key'] . " " .$item['value']) ?>{
                            cursor: zoom-in;
                        }
                        <?php
                    }
                ?>
            </style>
            <?php
        });
    }
}