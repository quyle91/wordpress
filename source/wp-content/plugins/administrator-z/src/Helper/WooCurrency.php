<?php
namespace Adminz\Helper;

class WooCurrency {
	function __construct() {
        //
	}

	function currency_unit( $string ) {
		add_filter( 'woocommerce_currency_symbol', function ($currency_symbol, $currency) use ($string) {
			if ( !$string ) {
				return $currency_symbol;
			}
			return $string;
		}, 10, 2 );
	}

	function change_shortend() {
		add_filter( 'formatted_woocommerce_price', function ($return, $price, $decimal, $decimal_separator, $thousand_separator, $original_price) {
			if ( $custom = $this->custom_format_price( $original_price ) ) {
				return $custom;
			}
			return $return;
		}, 10, 6 );
	}

	function custom_format_price( $price ) {
		if ( $price >= 1000000000 ) {
			$billions        = intval( $price / 1000000000 );
			$remainder       = $price % 1000000000;
			$formatted_price = number_format( $billions, 0 ) . ' tỷ';
			if ( $remainder >= 1000000 ) {
				$millions        = intval( $remainder / 1000000 );
				$formatted_price .= ' ' . number_format( $millions, 0 ) . ' triệu';
			}
			return $formatted_price;
		} elseif ( $price >= 1000000 ) {
			$millions        = intval( $price / 1000000 );
			$formatted_price = number_format( $millions, 0 ) . ' triệu';
			return $formatted_price;
		}
	}
}