<?php
namespace Vnpay\Controller;
// https://sandbox.vnpayment.vn/vnpaygw-sit-testing/order
// cấu hình ipn url: https://yourwebsite.com/wc-api/vnpay_pay_defauft_ipn
// tạo ra 1 gd với vnpay nhưng ko nhập mã otp
// xem chi tiết giao dịch
// copy các url (nhiều trạng thái) dán vào trình duyệt để test
class Ipn {
	private static $instance = null;
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {

		// create action ajax after get name from payment methods settings.
		// if($action_name = $this->get_action_name()){
		// 	add_action( "wp_ajax_nopriv_$action_name", [ $this, 'handle_vnpay_ipn' ] );
		// 	add_action( "wp_ajax_$action_name", [ $this, 'handle_vnpay_ipn' ] );
		// }
		add_action( 'woocommerce_api_vnpay_pay_defauft_ipn', array( $this, 'handle_vnpay_ipn' ) );
	}

	// function get_action_name(){
	// 	$options = get_option( 'woocommerce_vnpay_settings', [] );
	// 	$full_url = isset( $options['vnp_Ipn_Url'] ) ? $options['vnp_Ipn_Url'] : '';
	// 	if($full_url){
	// 		$parsed_url = parse_url( $full_url );
	// 		if ( isset( $parsed_url['query'] ) ) {
	// 			parse_str( $parsed_url['query'], $query_params );
	// 			if ( isset( $query_params['action'] ) ) {
	// 				return $query_params['action']; // ví dụ: "vnpay_ipn_2885937479"
	// 			}
	// 		}
	// 	}
	// 	return null;
	// }

	function handle_vnpay_ipn() {
		// document
		// https://sandbox.vnpayment.vn/apis/docs/chuyen-doi-thuat-toan/changeTypeHash.html#code-ipn-url
		$vnp_HashSecret = adminz_vnpay_get_vnp_HashSecret();

		$inputData  = array();
		$returnData = array();

		// Lấy dữ liệu từ URL
		foreach ( $_GET as $key => $value ) {
			if ( substr( $key, 0, 4 ) == "vnp_" ) {
				$inputData[ $key ] = $value;
			}
		}

		$vnp_SecureHash = $inputData['vnp_SecureHash'];
		unset( $inputData['vnp_SecureHash'] );
		ksort( $inputData );
		$i        = 0;
		$hashData = "";

		foreach ( $inputData as $key => $value ) {
			if ( $i == 1 ) {
				$hashData = $hashData . '&' . urlencode( $key ) . "=" . urlencode( $value );
			} else {
				$hashData = $hashData . urlencode( $key ) . "=" . urlencode( $value );
				$i        = 1;
			}
		}

		// Tạo hash để kiểm tra chữ ký
		$secureHash = hash_hmac( 'sha512', $hashData, $vnp_HashSecret );

		// Lấy thông tin giao dịch từ dữ liệu trả về
		$vnpTranId    = $inputData['vnp_TransactionNo'] ?? ''; // Mã giao dịch tại VNPAY
		$vnp_BankCode = $inputData['vnp_BankCode']; // Ngân hàng thanh toán
		$vnp_Amount   = $inputData['vnp_Amount'] / 100; // Số tiền thanh toán từ VNPAY phản hồi

		$Status = 0; // Trạng thái thanh toán của giao dịch chưa có IPN lưu tại hệ thống của merchant

		$orderId = $inputData['vnp_TxnRef']; // Mã đơn hàng từ VNPAY

		try {
			// Kiểm tra hash
			if ( $secureHash == $vnp_SecureHash ) {
				// Lấy đơn hàng từ WooCommerce
				$order = wc_get_order( $orderId ); // Lấy đơn hàng dựa trên mã đơn hàng

				if ( $order ) {
					// Kiểm tra số tiền thanh toán
					if ( $order->get_total() == $vnp_Amount ) {
						if ( $order->get_status() !== 'completed' && $order->get_status() !== 'processing' ) {
							if ( $inputData['vnp_ResponseCode'] == '00' && $inputData['vnp_TransactionStatus'] == '00' ) {
								// Cập nhật trạng thái thanh toán thành công
								$order->payment_complete( $vnpTranId );
								$order->add_order_note( __( 'IPN Thanh toán qua VNPay thành công' ) );
								$Status = 1; // Trạng thái thanh toán thành công
							} else {
								$order->update_status( 'failed', __( 'Thanh toán qua VNPay không thành công' ) );
								$order->add_order_note( __( 'IPN Thanh toán qua VNPay không thành công' ) );
								// Trạng thái thanh toán thất bại
								$Status = 2; // Trạng thái thanh toán thất bại / lỗi
							}
							// Trả kết quả về cho VNPAY
							$returnData['RspCode'] = '00';
							$returnData['Message'] = 'Confirm Success';
						} else {
							// Trả kết quả nếu đơn hàng đã được xác nhận
							$returnData['RspCode'] = '02';
							$returnData['Message'] = 'Order already confirmed';
						}
					} else {
						// Trả kết quả nếu số tiền không hợp lệ
						$returnData['RspCode'] = '04';
						$returnData['Message'] = 'Invalid amount';
					}
				} else {
					// Trả kết quả nếu không tìm thấy đơn hàng
					$returnData['RspCode'] = '01';
					$returnData['Message'] = 'Order not found';
				}
			} else {
				// Trả kết quả nếu chữ ký không hợp lệ
				$returnData['RspCode'] = '97';
				$returnData['Message'] = 'Invalid signature';
			}
		} catch (\Exception $e) {
			// Trả kết quả nếu có lỗi ngoài dự tính
			$returnData['RspCode'] = '99';
			$returnData['Message'] = 'Unknown error';
		}

		// Trả lại VNPAY theo định dạng JSON
		echo json_encode( $returnData );

		wp_die(); // Kết thúc xử lý AJAX
	}

}