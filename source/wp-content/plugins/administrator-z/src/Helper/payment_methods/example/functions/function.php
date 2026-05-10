<?php 
// Lấy mã TmnCode từ cài đặt WooCommerce
function adminz_vnpay_get_vnp_TmnCode(){
    $options = get_option('woocommerce_vnpay_settings', []);
    return isset($options['vnp_TmnCode']) ? $options['vnp_TmnCode'] : 'VM50EM00';
}

// Lấy mã HashSecret từ cài đặt WooCommerce
function adminz_vnpay_get_vnp_HashSecret(){
    $options = get_option('woocommerce_vnpay_settings', []);
    return isset($options['vnp_HashSecret']) ? $options['vnp_HashSecret'] : 'A7WWJX8RCV4659G1XG8DLCNSL52AKB1H';
}

// Lấy URL từ cài đặt WooCommerce
function adminz_vnpay_get_vnp_Url(){
    $options = get_option('woocommerce_vnpay_settings', []);
    return isset($options['vnp_Url']) ? $options['vnp_Url'] : 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html';
}

// Lấy URL từ cài đặt WooCommerce
function adminz_vnpay_get_vnp_Ipn_Url(){
    $options = get_option('woocommerce_vnpay_settings', []);
    $default = add_query_arg(
        [
            'action' => 'vnpay_ipn_'.wp_rand(),
        ],
        admin_url( 'admin-ajax.php' )
    );
    return isset($options['vnp_Ipn_Url']) ? $options['vnp_Ipn_Url'] : $default;
}
