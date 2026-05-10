jQuery(document).ready(function ($) {

    // ===========================
    // 1️⃣ Click mở form
    // ===========================
    $(document).on('click', '.wpdh-admin-column-wrap', function (e) {
        // Nếu click bên trong form thì bỏ qua (tránh toggle lại)
        if ($(e.target).closest('.wpdh-meta-form').length) return;

        e.stopPropagation();

        // Ẩn tất cả form đang mở khác
        $('.wpdh-meta-form').addClass('hidden');
        $('.wpdh-meta-value').show();

        // Hiện form của ô hiện tại
        const wrapper = $(this);
        wrapper.find('.wpdh-meta-value').hide();
        wrapper.find('.wpdh-meta-form').removeClass('hidden');
    });

    // ===========================
    // 2️⃣ Click ra ngoài đóng form
    // ===========================
    $(document).on('click', function (e) {
        const $target = $(e.target);

        // Nếu click nằm trong wpdh form wrapper hoặc trong wp media modal, bỏ qua
        if ($target.closest('.wpdh-admin-column-wrap, .media-modal').length) {
            return;
        }

        // tìm tất cả form đang mở và trigger save
        $('.wpdh-meta-form:not(.hidden) .wpdh-save-meta').each(function () {
            $(this).trigger('click');
        });

        // Đóng form
        $('.wpdh-meta-form').addClass('hidden');
        $('.wpdh-meta-value').show();
    });

    // ===========================
    // 3️⃣ Save meta qua AJAX
    // ===========================
    $(document).on('click', '.wpdh-save-meta', function (e) {
        e.preventDefault();

        const btn = $(this);
        const container = btn.closest('.wpdh-meta-form');
        const wrapper = btn.closest('.wpdh-admin-column-wrap');
        const postId = container.data('post-id');
        const termId = container.data('term-id');
        const fieldName = container.data('field-name');
        const fieldToArray = container.data('field-to-array');
        const status = container.find('.wpdh-saved-status');

        // Fix wp_editor before clone
        if (typeof tinymce !== 'undefined') {
            // sync content back to textarea
            tinymce.triggerSave();
        }

        // Clone form để lấy input data chính xác
        const clone = container.clone();
        
        // FIX: select value error
        clone.find('select').each(function () {
            const name = $(this).attr('name');
            if (!name) return;

            // Set clone's select value to match original container
            const value = container.find('select[name="' + name + '"]').val();
            $(this).val(value);
        });
        const form = $('<form></form>').append(clone);
        const formData = new FormData(form[0]);

        formData.append('action', 'wpdh_save_meta');
        formData.append('post_id', postId);
        formData.append('term_id', termId);
        formData.append('field_name', fieldName);
        formData.append('fieldToArray', JSON.stringify(fieldToArray));
        formData.append('nonce', Wpdh.nonce);

        // debug formData
        // for (let [key, value] of formData.entries()) {
        //     console.log(key, value);
        // }
        // return;

        status.text('Saving...').css('color', '#2271b1');

        $.ajax({
            url: Wpdh.ajax_url,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    let newValue = response.data.value;
                    status.text(response.data.message || 'Saved!').css('color', 'green');

                    // ✅ Chuẩn hóa dữ liệu hiển thị
                    if (Array.isArray(newValue) || typeof newValue === 'object') {
                        // Chuyển sang JSON format đẹp
                        newValue = JSON.stringify(newValue, null, 2);
                    } else if (newValue === null || newValue === undefined) {
                        newValue = '';
                    }

                    // ✅ Cập nhật giao diện
                    const metaValueEl = wrapper.find('.wpdh-meta-value');
                    metaValueEl.html(newValue).show();

                    container.addClass('hidden');
                } else {
                    const msg = response.data?.message || 'Error saving';
                    status.text(msg).css('color', 'red');
                    console.error(response.data);
                }
            },
            error: function (xhr, statusText, error) {
                status.text('AJAX error: ' + error).css('color', 'red');
            },
        });
    });
});
