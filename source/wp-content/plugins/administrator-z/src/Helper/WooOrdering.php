<?php
namespace Adminz\Helper;

class WooOrdering {
	public $list = [];

	function __construct() {

	}

	function setup_save_discount_data() {
		// add product meta
		add_action( 'woocommerce_update_product', [ $this, 'save_discount_amount' ], 10, 1 );
	}

	function save_discount_amount( $product_id ) {
		$_product = wc_get_product( $product_id );
		$discount = $this->get_discount_amount( $_product );
		if ( $discount ) {
			update_post_meta( $product_id, '__discount_amount', $discount );
		} else {
			delete_post_meta( $product_id, '__discount_amount', "" );
		}
	}

	function get_discount_amount( $product ) {
		$post_id = $product->get_id();
		$return  = false;

		if ( $product->is_type( 'simple' ) || $product->is_type( 'external' ) || $product->is_type( 'variation' ) ) {
			$regular_price = $product->get_regular_price();
			$sale_price    = $product->get_sale_price();
			if ( isset( $_POST['_sale_price'] ) and $_POST['_sale_price'] == '' ) {
				// no thing
			} elseif ( floatval( $regular_price ) > 0 ) { // Tránh lỗi chia 0
				$return = round( ( ( floatval( $regular_price ) - floatval( $sale_price ) ) / floatval( value: $regular_price ) ) * 100 );
			}
		} elseif ( $product->is_type( 'variable' ) ) {
			if ( isset( $_POST['variable_sale_price'] ) ) {
				$available_variations = $product->get_available_variations();
				$list_sale_percent    = [];

				for ( $i = 0; $i < count( $available_variations ); ++$i ) {
					if ( isset( $_POST['variable_sale_price'][ $i ] ) ) {
						$regular_price = $_POST['variable_regular_price'][ $i ];
						$sale_price    = $_POST['variable_sale_price'][ $i ];
						if ( $sale_price ) {
							$list_sale_percent[] = round( ( ( floatval( $regular_price ) - floatval( $sale_price ) ) / floatval( $regular_price ) ) * 100 );
						}
					}
				}
				if ( !empty( $list_sale_percent ) ) {
					$return = max( $list_sale_percent );
				}
			} else {
				return get_post_meta( $post_id, '__discount_amount', true );
			}
		}
		return $return;
	}

	function setup_ordering( $option ) {
		if(empty($option)) return;
		
		add_action('init', function() use($option){
			// fix fillters
			$default = apply_filters('woocommerce_catalog_orderby', array(
				'menu_order' => __( 'Default sorting', 'woocommerce' ), // phpcs:ignore
				'popularity' => __( 'Sort by popularity', 'woocommerce' ), // phpcs:ignore
				'rating'     => __( 'Sort by average rating', 'woocommerce' ), // phpcs:ignore
				'date'       => __( 'Sort by latest', 'woocommerce' ), // phpcs:ignore
				'price'      => __( 'Sort by price: low to high', 'woocommerce' ), // phpcs:ignore
				'price-desc' => __( 'Sort by price: high to low', 'woocommerce' ), // phpcs:ignore
			));
			$default['__discount_amount'] = __( "Discount amount", 'woocommerce' ); // phpcs:ignore
			foreach ($option as $key => $value) {
				$this->list[$value] = $default[$value];
			}

			// replace form 
			add_filter( 'woocommerce_default_catalog_orderby_options', [ $this, 'replace_ordering_form' ] );
			add_filter( 'woocommerce_catalog_orderby', [ $this, 'replace_ordering_form' ] );

			// apply sort query
			add_filter( 'woocommerce_product_query_meta_query', [ $this, 'apply_order_query' ] );
		});
	}

	function replace_ordering_form(){
		return $this->list;
	}

	function apply_order_query($meta_query){
		if ( isset( $_GET['orderby'] ) and $_GET['orderby'] == '__discount_amount' ) {
			if ( !isset( $meta_query['relation'] ) ) {
				$meta_query['relation'] = 'AND';
			}
			$meta_query[] = [ 
				'key'     => '__discount_amount',
				'compare' => 'EXISTS',
			];
			$meta_query[] = [ 
				'key'     => '__discount_amount',
				'compare' => '!=',
				'value'   => '',
			];
		}
		return $meta_query;
	}

	

	
}
