<?php
namespace Vnpay\Controller;
class ReturnUrl {
	private static $instance = null;
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		add_action( 'woocommerce_thankyou', [ $this, 'handle_vnpay_return' ], 11, 1 );
	}

	function handle_vnpay_return( $order_id ) {
		$order = wc_get_order( $order_id );

		// Kiểm tra nếu là thanh toán qua VNPay
		if ( $order->get_payment_method() !== 'vnpay' ) {
			return;
		}

		// Lấy các tham số từ URL
		$vnp_SecureHash = $_GET['vnp_SecureHash'];
		$inputData      = array();

		foreach ( $_GET as $key => $value ) {
			if ( substr( $key, 0, 4 ) === "vnp_" ) {
				$inputData[ $key ] = $value;
			}
		}

		// Loại bỏ 'vnp_SecureHash' khỏi mảng $inputData để xác minh
		unset( $inputData['vnp_SecureHash'] );
		ksort( $inputData );
		$hashData = "";
		$i        = 0;

		foreach ( $inputData as $key => $value ) {
			if ( $i == 1 ) {
				$hashData .= '&' . urlencode( $key ) . "=" . urlencode( $value );
			} else {
				$hashData .= urlencode( $key ) . "=" . urlencode( $value );
				$i        = 1;
			}
		}

		// Lấy chuỗi bí mật từ cấu hình VNPay của bạn
		$vnp_HashSecret = adminz_vnpay_get_vnp_HashSecret(); // Hàm này trả về HashSecret của VNPay
		$secureHash     = hash_hmac( 'sha512', $hashData, $vnp_HashSecret );

		// So sánh SecureHash nhận từ VNPay và SecureHash tính toán để xác minh
		if ( $secureHash === $vnp_SecureHash ) {
			// Kiểm tra mã phản hồi (vnp_ResponseCode) để xem giao dịch thành công hay thất bại
			if ( $_GET['vnp_ResponseCode'] === '00' ) {
				// Cập nhật trạng thái đơn hàng thành "completed" nếu thanh toán thành công
				$order->update_status( 'completed', __( 'Thanh toán qua VNPay thành công', 'vnpay' ) );
				$order->add_order_note( __( 'Return url Thanh toán qua VNPay thành công', 'vnpay' ) );
			} else {
				// Nếu không thành công
				$order->update_status( 'failed', __( 'Thanh toán qua VNPay không thành công', 'vnpay' ) );
				$order->add_order_note( __( 'Return url Thanh toán qua VNPay không thành công', 'vnpay' ) );
			}
		} else {
			// Nếu chữ ký không hợp lệ
			$order->update_status( 'failed', __( 'Chữ ký không hợp lệ', 'vnpay' ) );
			$order->add_order_note( __( 'Return url Chữ ký không hợp lệ', 'vnpay' ) );
		}
	}
}