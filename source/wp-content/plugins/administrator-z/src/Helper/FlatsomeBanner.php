<?php

namespace Adminz\Helper;

class FlatsomeBanner {

    public $post_type = 'page';
    public $metafields = [];
    public $meta_box_label = '';

    function __construct() {
        //
    }

    function init(): void {
        $this->create_metafields();
        $this->create_hook();
    }

    function create_metafields() {
        $this->metafields = [
            // adminz_banner
            \WpDatabaseHelperV2\Fields\WpField::make()
                ->kind('input')
                ->type('wp_media')
                ->name('adminz_banner')
                ->label('Banner image')
                ->adminColumn(true),

            // banner_height
            \WpDatabaseHelperV2\Fields\WpField::make()
                ->kind('input')
                ->type('text')
                ->name('banner_height')
                ->label('Banner height')
                ->attributes(['placeholder' => '400px'])
                ->adminColumn(true),

            // breadcrumb_shortcode
            \WpDatabaseHelperV2\Fields\WpField::make()
                ->kind('input')
                ->type('text')
                ->name('breadcrumb_shortcode')
                ->label('Breadcrumb shortcode')
                ->attributes(['placeholder' => '[adminz_breadcrumb]'])
                ->adminColumn(false),

            // adminz_title
            \WpDatabaseHelperV2\Fields\WpField::make()
                ->kind('input')
                ->type('text')
                ->name('adminz_title')
                ->label('Title')
                ->adminColumn(false),

            // adminz_acf_banner_shortcode
            \WpDatabaseHelperV2\Fields\WpField::make()
                ->kind('input')
                ->type('text')
                ->name('adminz_acf_banner_shortcode')
                ->label('ACF banner shortcode')
                ->adminColumn(false),

        ];

        \WpDatabaseHelperV2\Meta\WpMeta::make()
            ->post_type($this->post_type)
            ->label('Adminz Banner')
            ->fields($this->metafields)
            ->register();
        
    }

    function create_hook() {
        add_action('flatsome_after_header', function () {
            if ($this->post_type == get_post_type()) {
                $this->create_html();
            }
        }, 0);
    }

    function create_html() {
        // check banner image
        $banner = $this->get_banner();
        if (!$banner) return;

        // prepare data
        $title = $this->get_title();
        $height = $this->get_banner_height();
        $shortcode = $this->get_shortcode();

        echo $this->template($banner, $height, $title, $shortcode);
    }

    function template($banner, $height = false, $title = false, $shortcode = false) {

        ob_start();
?>
        [section class="adminz_banner" bg_overlay="rgba(0,0,0,.5)" bg="<?php echo esc_attr($banner) ?>" bg_size="original"
        dark="true" height="<?php echo esc_attr($height); ?>"]
        [row class="adminz_banner_row"]
        [col span__sm="12" span="9" class="adminz_banner_col"]
        <div class="adminz_banner_breadcrumb mb-half">
            <?php echo $this->get_breadcrumb() ?>
        </div>
        <?php if ($title) : ?>
            <h1 class="adminz_banner_title h1 uppercase mb-0"><?php echo esc_attr($title); ?></h1>
        <?php endif; ?>
        [/col]
        [/row]
        <?php if ($shortcode) echo do_shortcode($shortcode); ?>
        <?php echo do_action('adminz_acf_banner_after', $this); ?>
        [/section]
        <style type="text/css">
            @media (max-width: 549px) {
                .adminz_banner {
                    min-height: 30vh !important;
                }
            }
        </style>
<?php
        return do_shortcode(ob_get_clean());
    }

    function get_breadcrumb() {
        $meta_key = 'breadcrumb_shortcode';
        $object = adminz_get_object_id();

        if (!$object || !isset($object['object_id'])) {
            return false;
        }
        $meta = call_user_func($object['object_type'], $object['object_id'], $meta_key, true);
        $meta = apply_filters('breadcrumb_shortcode', $meta, $object);
        if ($meta) {
            return $meta;
        }
        $default = do_shortcode('[adminz_breadcrumb]');
        return $default;
    }

    function get_banner() {
        $meta_key = 'adminz_banner';
        $object = adminz_get_object_id();
        if (!$object || !isset($object['object_id'])) {
            return false;
        }
        $meta = call_user_func($object['object_type'], $object['object_id'], $meta_key, true);
        $meta = apply_filters('adminz_banner', $meta, $object);
        if ($meta) {
            return $meta;
        }
        $default = false;
        return $default;
    }

    function get_banner_height() {
        $meta_key = 'banner_height';
        $object = adminz_get_object_id();
        if (!$object || !isset($object['object_id'])) {
            return false;
        }
        $meta = call_user_func($object['object_type'], $object['object_id'], $meta_key, true);
        $meta = apply_filters('adminz_banner_height', $meta, $object);
        if ($meta) {
            return $meta;
        }
        $default = '400px';
        return $default;
    }

    function get_title() {
        $meta_key = 'adminz_title';
        $object = adminz_get_object_id();
        if (!$object || !isset($object['object_id'])) {
            return false;
        }
        $meta = call_user_func($object['object_type'], $object['object_id'], $meta_key, true);
        $meta = apply_filters('adminz_title', $meta, $object);
        if ($meta) {
            return $meta;
        }

        if (is_singular()) {
            return get_the_title();
        }

        if (get_queried_object()->name ?? '') {
            return get_queried_object()->name;
        }

        if (is_home()) {
            return get_the_title(get_option('page_for_posts'));
        }

        if (is_front_page()) {
            return get_the_title(get_option('page_on_front'));
        }

        return;
    }

    function get_shortcode() {
        $meta_key = 'adminz_acf_banner_shortcode';
        $object = adminz_get_object_id();
        if (!$object || !isset($object['object_id'])) {
            return false;
        }
        $meta = call_user_func($object['object_type'], $object['object_id'], $meta_key, true);
        $meta = apply_filters('adminz_acf_banner_shortcode', $meta, $object);
        if ($meta) {
            return $meta;
        }
        return false;
    }
}



/*
	EXAMPLE
	$sa = new \Adminz\Helper\FlatsomeAcfBanner;
$sa->init();
	
*/


/* 
customize example
add_filter( 'adminz_banner', function ($meta, $object) {
	if ( is_product_category() ) {
		if ( !get_term_meta( $object['object_id'] ?? '', 'adminz_banner', true ) ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			return get_post_meta( $shop_page_id, 'adminz_banner', true );
		}
	}

	if ( is_product() ) {
		if ( !get_post_meta( $object['object_id'] ?? '', 'adminz_banner', true ) ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			return get_post_meta( $shop_page_id, 'adminz_banner', true );
		}
	}
	return $meta;
}, 10, 2 );

add_filter( 'adminz_title', function ($meta, $object) {
	if ( is_product_category() ) {
		if ( !get_term_meta( $object['object_id'] ?? '', 'adminz_title', true ) ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			return get_the_title( $shop_page_id );
		}
	}
	if ( is_product() ) {
		if ( !get_post_meta( $object['object_id'] ?? '', 'adminz_title', true ) ) {
			$shop_page_id = wc_get_page_id( 'shop' );
			return get_the_title( $shop_page_id );
		}
	}
	return $meta;
}, 10, 2 );

add_filter( 'adminz_banner_height', function ($meta, $object) {
	return '300px';
}, 10, 2 ); */