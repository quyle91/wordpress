<?php
// Chỉ nên copy file này vì chưa finish
// hỗ trợ cho việc truyền từ $_POST vào cart item data, order item meta, và email
// chỉ áp dụng cho 1 key/ 1 label, với số nhiều thì gọi nhiều lần.
// Meta data sẽ được giữ nguyên khi recalculate order
// js mẫu và function custom item price ko hỗ trợ, tham khảo code ở ví dụ

namespace Adminz\Helper;

class WoocommerceOrderItem {

    const VERSION = '16.04.2025';

    public $item_label;
    public $item_key;
    public $validate_text;

    function __construct() {
        //
    }

    function setup($args = []) {
        foreach ((array) $args as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    function init() {
        $this->raw_woocommerce_add_cart_item_data();
        $this->display_woocommerce_get_item_data();
        $this->raw_woocommerce_checkout_create_order_line_item();
        $this->display_woocommerce_order_item_display_meta_key();
        $this->display_woocommerce_email_order_meta();
    }

    // raw: Thêm data vào mảng $cart_item_data khi add sản phẩm vào giỏ.
    function raw_woocommerce_add_cart_item_data() {
        add_filter('woocommerce_add_cart_item_data', function ($cart_item_data) {
            // luôn luôn truyền vào 
            $value                             = $_POST[$this->item_key] ?? '';
            $value                             = apply_filters(
                'WoocommerceOrderItem_raw',
                $value,
                $this->item_key,
                $cart_item_data
            );
            $cart_item_data[$this->item_key] = $value;
            return $cart_item_data;
        }, 10, 1);
    }

    // display: Hiển thị data trên giao diện cart (trang giỏ hàng).
    function display_woocommerce_get_item_data() {
        add_filter('woocommerce_get_item_data', function ($item_data, $cart_item) {
            // echo "<pre>"; print_r($cart_item); echo "</pre>"; die;
            if ($this->item_label) {
                $value   = $cart_item[$this->item_key];
                $display = apply_filters(
                    'WoocommerceOrderItem_display',
                    $value,
                    $this->item_key,
                    $cart_item
                );
                if ($display) {
                    $item_data[] = array(
                        'key'     => $this->item_label,
                        'value'   => $value,
                        'display' => $display,
                    );
                }
            }
            return $item_data;
        }, 10, 2);
    }

    // raw: Lưu data từ cart vào order item meta.
    function raw_woocommerce_checkout_create_order_line_item() {
        add_action('woocommerce_checkout_create_order_line_item', function ($item, $cart_item_key, $values, $order) {
            $value = $values[$this->item_key];
            // $value bắt buộc phải có giá trị thì mới dc đưa vào order
            // và phải xử lý từ bước cart 
            $item->add_meta_data(
                $this->item_key,
                $value
            );
        }, 10, 4);
    }

    function is_edit_order_admin() {
        // Kiểm tra nếu đang ở màn hình edit order (HPOS hoặc classic) thì bỏ qua
        if (is_admin() && function_exists('get_current_screen')) {
            $screen = get_current_screen();
            if ($screen && ($screen->id === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders')) {
                return true;
            }
        }
    }

    // display: Hiển thị data trong trang admin (mục chi tiết order), định dạng key và value để dễ đọc.
    function is_send_order_admin() {
        if (is_admin() and ($_POST['wc_order_action'] ?? '')) {
            return true;
        }
    }

    // display: Hiển thị data trong trang admin (mục chi tiết order), định dạng key và value để dễ đọc.
    function display_woocommerce_order_item_display_meta_key() {

        // ẩn đi những item ko có label
        add_filter('woocommerce_order_item_get_formatted_meta_data', function ($formatted_meta, $order_item) {

            // 
            if ($this->is_edit_order_admin() and !$this->is_send_order_admin()) {
                return $formatted_meta;
            }

            foreach ((array)$formatted_meta as $key => $item) {
                if ($this->item_key === $item->key and $this->item_label === '') {
                    unset($formatted_meta[$key]);
                }
            }
            return $formatted_meta;
        }, 10, 2);

        // cột trái
        add_filter('woocommerce_order_item_display_meta_key', function ($return, $meta, $item) {
            if ($meta->key === $this->item_key) {
                $return = $this->item_label;

                // 
                if ($this->is_edit_order_admin()) {
                    if (!$return) {
                        $return = $this->item_key;
                        return $return;
                    }
                }
            }
            return $return;
        }, 10, 3);

        // cột phải
        add_filter('woocommerce_order_item_display_meta_value', function ($return, $meta, $item) {
            if ($meta->key === $this->item_key) {
                if ($this->item_label) {
                    return apply_filters(
                        'WoocommerceOrderItem_display',
                        $return,
                        $this->item_key,
                        $item
                    );
                } else {
                    // keep default
                }
            }
            return $return;
        }, 10, 3);
    }

    // display: Hiển thị data trong email.
    function display_woocommerce_email_order_meta() {

        add_action('woocommerce_email_order_meta', function ($order) {
            if ($value = $order->get_meta($this->item_key)) {
                echo $value;
            }
        }, 10, 1);

        add_filter('woocommerce_email_order_meta_fields', function ($fields, $sent_to_admin, $order) {
            if ($this->item_label) {
                $value                     = $order->get_meta($this->item_key);
                $fields[$this->item_key] = array(
                    'label' => $this->item_label,
                    'value' => $value,
                );
            }
            return $fields;
        }, 10, 3);
    }

    // layout
    function init_cart_field() {
        // get form add to cart to loop item
        // add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_single_add_to_cart', 19 );

        // create field on single product
        add_action('woocommerce_before_add_to_cart_button', function () {
            $input_id = esc_attr($this->item_key);
            $label_for = $input_id;
            $input_name = $input_id;
            $label     = esc_attr($this->item_label);
            $placeholder = esc_attr($this->item_label);
            $input_value = sanitize_text_field($_POST[$this->item_key] ?? '');
            $input_maxlength = 15;

            echo <<<HTML
            <div class="">
                <label for="$label_for">$label</label>
                <input type="text" id="$input_id" name="$input_name" placeholder="$placeholder" value="$input_value" maxlength="$input_maxlength">
            </div>
            HTML;
        });


        add_action('woocommerce_add_to_cart_validation', function ($result, $product_id, $quantity) {
            $validate_text = $this->item_label . " is required!";
            if ($this->validate_text) {
                $validate_text = $this->validate_text;
            }

            if (empty($_REQUEST[$this->item_key])) {
                wc_add_notice($validate_text, 'error');
                return false;
            }
            return $result;
        }, 10, 3);
    }
}

/** ------- Khai báo ------------ */
// add_action('init', function () {

//     $list = [
//         // key => label
//         'property' => 'Property',
//         'property_1' => 'Property 1',
//         'property_2' => 'Property 2',
//     ];

//     foreach ((array)$list as $key => $value) {
//         $a             = new \WCP\Helper\WoocommerceOrderItem;
//         $a->item_label = $value;
//         $a->item_key   = $key;
//         $a->init();
//     }

//     add_filter('WoocommerceOrderItem_raw', function ($value, $key, $item) use ($list) {
//         if (in_array($key, array_keys($list)) and $key == 'property') {
//             return $value . ' Custom raw';
//         }
//         return $value;
//     }, 10, 3);

//     add_filter('WoocommerceOrderItem_display', function ($value, $key, $item) use ($list) {
//         if (in_array($key, array_keys($list)) and $key == 'property') {
//             return $value . ' Custom diplay';
//         }
//         return $value;
//     }, 10, 3);
// });

/** ------- hook để custom item price trong cart ------------ */
// add_action('woocommerce_before_calculate_totals', function ($cart) {
//     if (is_admin() && !defined('DOING_AJAX')) {
//         return;
//     }
//     foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
//         $product = $cart_item['data'];
//         $price = 100; // custom item price
//         if ($price) {
//             $product->set_price($price);
//         }
//     }
// });

/** ------- hook để custom item price trong reculcale order ------------ */
// add_action('woocommerce_order_before_calculate_totals', function ($and_taxes, $order) {
//     foreach ($order->get_items() as $item) {
//         // Chỉ xử lý với line item
//         if (!$item->is_type('line_item')) {
//             continue;
//         }

//         $original_subtotal = $item->get_subtotal();
//         $original_total = $item->get_total();
//         $discount = $original_subtotal - $original_total;

//         $_check_price = 100;
//         $new_subtotal = $_check_price;

//         // Tính toán total mới
//         $new_total = $new_subtotal - $discount;

//         // Đảm bảo total không âm
//         if ($new_total < 0) {
//             $new_total = 0;
//             // Có thể điều chỉnh discount ở đây nếu cần
//             $discount = $new_subtotal; // Discount tối đa = subtotal
//         }

//         // Cập nhật giá trị
//         $item->set_subtotal($new_subtotal);
//         $item->set_total($new_total);
//     }

//     // Tính lại thuế nếu cần
//     if ($and_taxes) {
//         $order->calculate_taxes();
//     }
// }, 10, 2);

/** ------- Script chuẩn để add item vào cart với formData ------------ */
// <script type="text/javascript">
//     document.addEventListener('DOMContentLoaded', () => {
//         const button = e.currentTarget;
//         if ('undefined' === typeof wc_add_to_cart_params) {
//             return false;
//         }
//         jQuery(button).addClass('processing');
//         const formData = this.getFormData(); // form.serialize()
//         jQuery.post(
//             wc_add_to_cart_params.wc_ajax_url.toString().replace('%%endpoint%%', 'add_to_cart'), formData,
//             function(response) {
//                 jQuery(button).removeClass('processing');
//                 if (!response) {
//                     return;
//                 }
//                 if (response.error && response.product_url) {
//                     window.location = response.product_url;
//                     return;
//                 }
//                 if ('yes' === wc_add_to_cart_params.cart_redirect_after_add) {
//                     window.location = wc_add_to_cart_params.cart_url;
//                     return;
//                 }
//                 jQuery(document.body).trigger('added_to_cart', [response.fragments, response.cart_hash]);
//             }
//         );
//     });
// </script>