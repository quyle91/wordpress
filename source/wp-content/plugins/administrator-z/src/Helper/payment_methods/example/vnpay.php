<?php 

// https://sandbox.vnpayment.vn/devreg
// https://sandbox.vnpayment.vn/apis/docs/chuyen-doi-thuat-toan/changeTypeHash.html
// IPN default: https://yourdomain.com/wp-admin/admin-ajax.php?action=vnpay_ipn
// https://sandbox.vnpayment.vn/merchantv2/
// https://sandbox.vnpayment.vn/vnpaygw-sit-testing/user/login

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

require __DIR__ . "/vendor/autoload.php";

// IPN
\Vnpay\Controller\Ipn::get_instance();
// Return Url
\Vnpay\Controller\ReturnUrl::get_instance();

// woocomerce payment gateways
add_filter( 'woocommerce_payment_gateways', function($methods){
	$methods[] = '\Vnpay\Controller\PaymentMethod';
	return $methods;
} );

add_filter('woocommerce_payment_complete_order_status', function($status){
	return 'completed';
});