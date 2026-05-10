<?php

namespace Adminz\Helper;

class WooCheckout {
    function __construct() {
        //
    }

    function init() {
        add_filter('woocommerce_checkout_fields', [$this, 'custom_billing_city_field']);
        add_filter('woocommerce_checkout_fields', [$this, 'custom_remove_woo_checkout_fields']);
    }

    function custom_billing_city_field($fields) {
        if (get_locale() == 'vi') {
            $cities = array(
                'Hà Nội'            => 'Hà Nội',
                'Vĩnh Phúc'         => 'Vĩnh Phúc',
                'Bắc Ninh'          => 'Bắc Ninh',
                'Quảng Ninh'        => 'Quảng Ninh',
                'Hải Dương'         => 'Hải Dương',
                'Hải Phòng'         => 'Hải Phòng',
                'Hưng Yên'          => 'Hưng Yên',
                'Thái Bình'         => 'Thái Bình',
                'Hà Nam'            => 'Hà Nam',
                'Nam Định'          => 'Nam Định',
                'Ninh Bình'         => 'Ninh Bình',
                'Hà Giang'          => 'Hà Giang',
                'Cao Bằng'          => 'Cao Bằng',
                'Bắc Kạn'           => 'Bắc Kạn',
                'Tuyên Quang'       => 'Tuyên Quang',
                'Lào Cai'           => 'Lào Cai',
                'Yên Bái'           => 'Yên Bái',
                'Thái Nguyên'       => 'Thái Nguyên',
                'Lạng Sơn'          => 'Lạng Sơn',
                'Bắc Giang'         => 'Bắc Giang',
                'Phú Thọ'           => 'Phú Thọ',
                'Điện Biên'         => 'Điện Biên',
                'Lai Châu'          => 'Lai Châu',
                'Sơn La'            => 'Sơn La',
                'Hoà Bình'          => 'Hoà Bình',
                'Thanh Hoá'         => 'Thanh Hoá',
                'Nghệ An'           => 'Nghệ An',
                'Hà Tĩnh'           => 'Hà Tĩnh',
                'Quảng Bình'        => 'Quảng Bình',
                'Quảng Trị'         => 'Quảng Trị',
                'Thừa Thiên Huế'    => 'Thừa Thiên Huế',
                'Đà Nẵng'           => 'Đà Nẵng',
                'Quảng Nam'         => 'Quảng Nam',
                'Quảng Ngãi'        => 'Quảng Ngãi',
                'Bình Định'         => 'Bình Định',
                'Phú Yên'           => 'Phú Yên',
                'Khánh Hoà'         => 'Khánh Hoà',
                'Ninh Thuận'        => 'Ninh Thuận',
                'Bình Thuận'        => 'Bình Thuận',
                'Tây Nguyên'        => 'Tây Nguyên',
                'Kon Tum'           => 'Kon Tum',
                'Gia Lai'           => 'Gia Lai',
                'Đắk Lắk'           => 'Đắk Lắk',
                'Đắk Nông'          => 'Đắk Nông',
                'Lâm Đồng'          => 'Lâm Đồng',
                'Đông Nam Bộ'       => 'Đông Nam Bộ',
                'Bình Phước'        => 'Bình Phước',
                'Tây Ninh'          => 'Tây Ninh',
                'Bình Dương'        => 'Bình Dương',
                'Đồng Nai'          => 'Đồng Nai',
                'Bà Rịa - Vũng Tàu' => 'Bà Rịa - Vũng Tàu',
                'TP.Hồ Chí Minh'    => 'TP.Hồ Chí Minh',
                'Long An'           => 'Long An',
                'Tiền Giang'        => 'Tiền Giang',
                'Bến Tre'           => 'Bến Tre',
                'Trà Vinh'          => 'Trà Vinh',
                'Vĩnh Long'         => 'Vĩnh Long',
                'Đồng Tháp'         => 'Đồng Tháp',
                'An Giang'          => 'An Giang',
                'Kiên Giang'        => 'Kiên Giang',
                'Cần Thơ'           => 'Cần Thơ',
                'Hậu Giang'         => 'Hậu Giang',
                'Sóc Trăng'         => 'Sóc Trăng',
                'Bạc Liêu'          => 'Bạc Liêu',
                'Cà Mau'            => 'Cà Mau',
            );

            $fields['billing']['billing_city'] = array(
                'type'     => 'select',
                'label'    => __('City', 'woocommerce'), // phpcs:ignore
                'required' => false,
                'options'  => $cities,
                'class'    => array('form-row-last'),
                'clear'    => false,
            );

            $fields['shipping']['shipping_city'] = array(
                'type'     => 'select',
                'label'    => __('Shipping City', 'woocommerce'), // phpcs:ignore
                'required' => false,
                'options'  => $cities,
                'class'    => array('form-row-last'),
                'clear'    => false,
            );
        }
        return $fields;
    }

    function custom_remove_woo_checkout_fields($fields) {
        $required_fields = [
            'billing_first_name' => [
                'class'       => ['form-row-first'],
                'title' => (get_locale() == 'vi') ? "Họ và tên" : __('Full Name', 'woocommerce'),
                'required'    => true,
            ],
            'billing_address_1'  => [
                'class'       => ['form-row-last'],
                'title' => __('Address', 'woocommerce'),
                'required'    => false,
            ],
            'billing_city'       => [
                'class'       => ['form-row-first'],
                'title' => __('City', 'woocommerce'),
                'required'    => false,
            ],
            'billing_phone'      => [
                'class'       => ['form-row-last'],
                'title' => __('Phone', 'woocommerce'),
                'required'    => true,
            ],
            'billing_email'      => [
                'class'       => ['form-row-full'],
                'title' => __('Email', 'woocommerce'),
                'required'    => false,
            ],
        ];

        $new_fields = [];

        foreach ($required_fields as $field => $attributes) {
            if (isset($fields['billing'][$field])) {
                $new_fields['billing'][$field]                = $fields['billing'][$field];
                $new_fields['billing'][$field]['class']       = $attributes['class'];
                $new_fields['billing'][$field]['placeholder'] = $attributes['title'];
                $new_fields['billing'][$field]['label']       = $attributes['title'];
                $new_fields['billing'][$field]['required']    = $attributes['required'];
            }
        }

        foreach ($new_fields['billing'] as $key => $field) {
            $key = str_replace('billing_', 'shipping_', $key);
            $new_fields['shipping'][$key] = $field;
        }

        $fields['billing']  = $new_fields['billing'];
        $fields['shipping'] = $new_fields['shipping'];

        return $fields;
    }

    function __get_field_label($field) {
        $label = '';
        $checkout_fields = WC()->checkout()->get_checkout_fields();
        foreach ($checkout_fields as $fieldset_key => $fields) {
            foreach ($fields as $field_key => $field_props) {
                if ($field_key == $field) {
                    $label = $field_props['label'];
                }
            }
        }
        return $label;
    }

    function validate_fields($field_skipped) {

        // php
        // echo "<pre>"; print_r($field_skipped); echo "</pre>"; die;

        foreach ($field_skipped as $item) {

            // Khai báo pattern mới
            add_filter('woocommerce_checkout_fields', function ($fields) use ($item) {
                if (isset($fields['billing'][$item['field']])) {
                    // Thêm custom validation class
                    $fields['billing'][$item['field']]['class'][] = 'validate-custom-pattern';

                    // Thêm custom attributes nếu cần
                    $fields['billing'][$item['field']]['custom_attributes']['data-pattern'] = $item['regex'];
                    $fields['billing'][$item['field']]['custom_attributes']['data-error'] = $item['error'];
                }
                return $fields;
            });

            // thực thi pattern
            add_action('woocommerce_after_checkout_validation', function ($data, $errors) use ($item) {
                $field = $item['field'];

                // Unset hoàn toàn lỗi cho field này vì logic phụ thuộc hoàn toàn vào new pattern.
                if (isset($errors->errors[$field . '_required'])) {
                    unset($errors->errors[$field . '_required']);
                }

                // Thêm custom validation của bạn
                if (isset($data[$field])) {
                    $posted = esc_attr($_POST[$item['field']]);
                    if (!preg_match($item['regex'], $posted)) {
                        $errors->add(
                            $field,
                            '<strong>' . esc_html($this->__get_field_label($field)) . '</strong> ' . esc_html($item['error']),
                            ['id' => $field]
                        );
                    }
                }
            }, 10, 2);
        }
    }
}
