(function ($) {
    'use strict';

    // click_to_create_repeater
    $(document).on('change', '.click_to_create_repeater', function (e) {
        // const currentTarget = e.currentTarget;
        // const value = currentTarget.value;
        // const Field = $(currentTarget).closest('.wpdh-field');
        // const Repeater = Field.siblings('.wpdh-repeater');
        // const parentRepeater = Field.closest('.wpdh-repeater');
        // const parentRepeaterNamePrefix = parentRepeater.data('base');
        // const repeaterItem = Field.closest('.wpdh-repeater-item');
        // const repeaterItemIndex = repeaterItem.data('index');
        // const namePrefix = parentRepeaterNamePrefix + '[' + repeaterItemIndex + ']';

        // // 🧹 Nếu KHÔNG PHẢI repeater thì xoá repeater con (nếu có)
        // if (value !== 'repeater') {
        //     if (Repeater.length) {
        //         Repeater.remove();
        //     }
        //     console.log('Removed repeater siblings');
        //     return;
        // }

        // // Nếu chọn repeater mà đã có repeater con thì bỏ qua
        // if (Repeater.length) {
        //     console.log('Skip for exists repeater');
        //     return;
        // }

        // // Gọi ajax để lấy repeater HTML
        // jQuery.ajax({
        //     type: 'post',
        //     dataType: 'json',
        //     url: adminz_js.ajax_url,
        //     data: {
        //         action: 'adminz_wpdh_create_repeater',
        //         nonce: adminz_js.nonce,
        //         namePrefix: namePrefix,
        //         name: 'children',
        //         label: 'Children',
        //     },
        //     context: this,
        //     beforeSend: function () {
        //         // Có thể thêm hiệu ứng loading tại đây
        //     },
        //     success: function (response) {
        //         if (response.success) {
        //             // ✅ Thêm repeater vào DOM liền sau field
        //             console.log('Insert repeater after ', Field);
        //             Field.after(response.data);
        //         } else {
        //             console.warn('Reponse data', response.data);
        //         }
        //     },
        //     error: function (jqXHR, textStatus, errorThrown) {
        //         console.error('The following error occurred: ' + textStatus, errorThrown);
        //     },
        //     complete: function () {
        //         // Làm gì đó sau khi hoàn tất (tùy chọn)
        //     }
        // });
    });

})(jQuery);
