<?php

namespace Adminz\Helper;

final class WooTeleGram {

    public $botToken;
    public $chatId;

    // Khởi tạo, đăng ký hook
    function __construct() {
        //
    }

    function init($botToken, $chatId) {

        $this->botToken = $botToken;
        $this->chatId = $chatId;

        // Đăng ký hook WooCommerce khi order được hoàn tất
        add_action('woocommerce_order_status_completed', [$this, 'on_order_completed'], 10, 1);
    }

    // Hook WooCommerce khi đơn hàng hoàn tất
    function on_order_completed($order_id) {
        // Lấy thông tin đơn hàng từ ID
        $order = wc_get_order($order_id);

        if (! $order) {
            return;
        }

        // Tạo thông điệp từ thông tin đơn hàng và thông tin người dùng trong đơn hàng
        $message = $this->getMessage($order);

        // Thay thế bằng bot token và chat ID của bạn
        $botToken = $this->botToken;  // Token bot của bạn
        $chatId   = $this->chatId;  // Chat ID bạn vừa lấy

        // Gửi tin nhắn Telegram
        $this->sendTelegramMessage($botToken, $chatId, $message);
    }

    // Tạo thông điệp từ thông tin đơn hàng
    function getMessage($order) {
        // Lấy thông tin đơn hàng
        $order_id = $order->get_id();  // Lấy order ID
        $order_date = $order->get_date_created()->date(get_option('date_format') . ' ' . get_option('time_format'));
        $order_total = $order->get_total();
        $order_items = $order->get_items();

        // Lấy thông tin khách hàng trong đơn hàng
        $customer_first_name = $order->get_billing_first_name();
        $customer_last_name = $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();

        $billing = implode(", ", array_filter([
            $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
            $order->get_billing_city(),
            $order->get_billing_postcode(),
            $order->get_billing_state(),
            $order->get_billing_country(),
        ]));

        $shipping = implode(", ", array_filter([
            $order->get_shipping_address_1() . ' ' . $order->get_shipping_address_2(),
            $order->get_shipping_city(),
            $order->get_shipping_postcode(),
            $order->get_shipping_state(),
            $order->get_shipping_country(),
        ]));

        $customer_phone      = $order->get_billing_phone();

        $message = __('Completed order', 'woocommerce') . "\n";
        $message .= "--------------\n";
        $message .= __("Order ID", 'woocommerce') . ": $order_id\n";  // Thêm order ID
        $message .= __("Date created:", 'woocommerce') . " $order_date\n";
        $message .= __("Total:", 'woocommerce') . " " . str_replace('₫', 'đ', html_entity_decode(wp_strip_all_tags(wc_price($order_total)), ENT_NOQUOTES, 'UTF-8')) . "\n";
        $message .= "--------------\n";
        $message .= __("Customer:", 'woocommerce') . " " . $customer_first_name . $customer_last_name . "\n";
        $message .= __("Phone", 'woocommerce') . ": " . "$customer_phone\n";
        $message .= __("Email:", 'woocommerce') . " " . "$customer_email\n";
        $message .= __("Billing address", 'woocommerce') . ": $billing\n";
        if ($shipping) {
            $message .= __("Shipping address", 'woocommerce') . ": $shipping\n";
        }
        $message .= "--------------\n";
        $message .= __("Product Details", 'woocommerce') . ":\n";

        // Lặp qua các sản phẩm trong đơn hàng và thêm vào tin nhắn
        foreach ($order_items as $item_id => $item) {
            $product_name = $item->get_name();
            $product_qty  = $item->get_quantity();
            $message .= "$product_name x $product_qty\n";
        }

        // Lấy Order note (ghi chú khách hàng điền trong checkout)
        $order_note = $order->get_customer_note();
        if ($order_note) {
            $message .= "--------------\n";
            if (!empty($order_note)) {
                $message .= __("Order notes", 'woocommerce') . ":\n";
                $message .= $order_note;
            }
        }

        return $message;
    }

    // Gửi tin nhắn qua Telegram
    function sendTelegramMessage($botToken, $chatId, $message) {
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";

        // Dữ liệu sẽ được gửi qua POST
        $data = [
            'chat_id' => $chatId,
            'text'    => $message,
        ];

        // Khởi tạo cURL
        $ch = curl_init();

        // Cấu hình các tùy chọn cho cURL
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        // Thực thi cURL và lấy kết quả
        $response = curl_exec($ch);

        // Kiểm tra lỗi cURL
        if (curl_errno($ch)) {
            error_log('Error: ' . curl_error($ch));
        }

        // Đóng cURL
        curl_close($ch);
    }
}
