<?php 
namespace Vnpay\Controller;

class PaymentMethod extends \WC_Payment_Gateway {

	public function __construct() {
		$this->id                 = 'vnpay';
		$this->icon               = ''; // Đường dẫn đến icon của vnpay
		$this->has_fields         = false;
		$this->method_title       = 'Vnpay';
		$this->method_description = 'Thanh toán qua Vnpay.';

		// Cấu hình
		$this->init_form_fields();
		$this->init_settings();

		// Các thông tin cấu hình
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );

		add_filter( 'woocommerce_payment_gateways', function ($methods) {
			$methods[] = '\Vnpay\Controller\PaymentMethod';
			return $methods;
		} );

		// Xử lý thanh toán và callback
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	}

	// Cấu hình các trường cho admin
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => 'Kích hoạt',
				'type'    => 'checkbox',
				'label'   => 'Kích hoạt phương thức thanh toán vnpay',
				'default' => 'no',
			),
			'title'       => array(
				'title'       => 'Tiêu đề',
				'type'        => 'text',
				'description' => 'Tiêu đề phương thức thanh toán này sẽ xuất hiện trong trang checkout.',
				'placeholder' => 'Thanh toán qua vnpay',
				'default'     => 'Thanh toán qua vnpay',
			),
			'description' => array(
				'title'       => 'Mô tả',
				'type'        => 'textarea',
				'description' => 'Mô tả cho phương thức thanh toán sẽ hiển thị trên trang thanh toán.',
				'default'     => 'Thanh toán đơn hàng với Vnpay',
				'placeholder' => 'Thanh toán đơn hàng với Vnpay',
			),
			'vnp_TmnCode'    => array(
				'title'       => 'vnp_TmnCode',
				'type'        => 'text',
				'description' => 'Mã TmnCode của website đăng ký với VNPAY.',
				'default'     => adminz_vnpay_get_vnp_TmnCode(),
				'placeholder'     => adminz_vnpay_get_vnp_TmnCode(),
			),
			'vnp_HashSecret' => array(
				'title'       => 'vnp_HashSecret',
				'type'        => 'password',
				'description' => 'Chuỗi bí mật HashSecret từ VNPAY để mã hóa bảo mật.',
				'default'     => adminz_vnpay_get_vnp_HashSecret(),
				'placeholder'     => adminz_vnpay_get_vnp_HashSecret(),
			),
			'vnp_Url'        => array(
				'title'       => 'vnp_Url',
				'type'        => 'text',
				'description' => 'URL thanh toán của VNPAY (ví dụ: https://sandbox.vnpayment.vn/paymentv2/vpcpay.html).',
				'default'     => adminz_vnpay_get_vnp_Url(),
				'placeholder'     => adminz_vnpay_get_vnp_Url(),
			),
			'vnp_Ipn_Url'        => array(
				'title'       => 'vnp_Ipn_Url',
				'type'        => 'text',
				'description' => 'Sử dụng link này để cung cấp cho vnpay trong Cấu hình IPN URL',
				'default'     => adminz_vnpay_get_vnp_Ipn_Url(),
				'placeholder'     => adminz_vnpay_get_vnp_Ipn_Url(),
			),
		);

		add_filter( 'woocommerce_payment_gateways',  function ( $methods ) {
			$methods[] = 'Vnpay\Controller\PaymentMethod';
			return $methods;
		} );
	}

	// Xử lý thanh toán
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		// Tạo URL thanh toán vnpay
		$payment_url = $this->get_vnpay_payment_url( $order );
		
		// Trả về trang xác nhận với mã QR
		return array(
			'result'   => 'success',
			'redirect' => $payment_url,
		);
	}

	// Tạo URL thanh toán vnpay
	private function get_vnpay_payment_url($order){
		// copy code from vnpay: 
		// https://sandbox.vnpayment.vn/apis/docs/chuyen-doi-thuat-toan/changeTypeHash.html
		
		// error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
		// date_default_timezone_set('Asia/Ho_Chi_Minh');
		
		// $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
		$vnp_Url       = adminz_vnpay_get_vnp_Url();
		// $vnp_Returnurl = "https://localhost/vnpay_php/vnpay_return.php";
		$vnp_Returnurl = $this->get_return_url( $order );
		// $vnp_TmnCode = "";//Mã website tại VNPAY 
		$vnp_TmnCode    = adminz_vnpay_get_vnp_TmnCode();
		// $vnp_HashSecret = ""; //Chuỗi bí mật
		$vnp_HashSecret = adminz_vnpay_get_vnp_HashSecret();
		
		// $vnp_TxnRef = $_POST['order_id']; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
		$vnp_TxnRef = $order->get_id();
		// $vnp_OrderInfo = $_POST['order_desc'];
		$vnp_OrderInfo = 'Thanh toán đơn hàng ' . $order->get_id();
		// $vnp_OrderType = $_POST['order_type'];
		$vnp_OrderType = 'billpayment';
		// $vnp_Amount = $_POST['amount'] * 100;
		$vnp_Amount = $order->get_total() * 100;
		// $vnp_Locale = $_POST['language'];
		$vnp_Locale = get_locale();
		// $vnp_BankCode = $_POST['bank_code'];
		$vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

		//Add Params
		$inputData = array(
			"vnp_Version" => "2.1.0", //Phiên bản cũ là 2.0.0, 2.0.1 thay đổi sang 2.1.0
			"vnp_TmnCode" => $vnp_TmnCode,
			"vnp_Amount" => $vnp_Amount,
			"vnp_Command" => "pay",
			"vnp_CreateDate" => date('YmdHis'),
			"vnp_CurrCode" => "VND",
			"vnp_IpAddr" => $vnp_IpAddr,
			"vnp_Locale" => $vnp_Locale,
			"vnp_OrderInfo" => $vnp_OrderInfo,
			"vnp_OrderType" => $vnp_OrderType,
			"vnp_ReturnUrl" => $vnp_Returnurl,
			"vnp_TxnRef" => $vnp_TxnRef
		);
		
		if (isset($vnp_BankCode) && $vnp_BankCode != "") {
			$inputData['vnp_BankCode'] = $vnp_BankCode;
		}
		
		//var_dump($inputData);
		ksort($inputData);
		$query = "";
		$i = 0;
		$hashdata = "";

		foreach ($inputData as $key => $value) {
			if ($i == 1) {
				$hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
			} else {
				$hashdata .= urlencode($key) . "=" . urlencode($value);
				$i = 1;
			}
			$query .= urlencode($key) . "=" . urlencode($value) . '&';
		}

		$vnp_Url = $vnp_Url . "?" . $query;
		if (isset($vnp_HashSecret)) {
			$vnpSecureHash = hash_hmac( 'sha512', $hashdata, $vnp_HashSecret );//  
			$vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
		}
		return $vnp_Url;
	}
}