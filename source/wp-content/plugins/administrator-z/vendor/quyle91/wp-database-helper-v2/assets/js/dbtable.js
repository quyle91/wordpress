jQuery(document).ready(function ($) {

    // ===========================================
    // 1️⃣ Toggle Filter / Create Form
    // ===========================================
    $('.wpdh-dbtable').each(function () {
        const dbtable = $(this);

        dbtable.find('.toggle_filter_search, .toggle_create').on('click', function () {
            const targetClass = $(this).data('target');
            const targetForm = dbtable.find('.' + targetClass);

            if (!targetForm.length) return;

            // Ẩn các form khác
            dbtable.find('.wpdh-filter-search-form, .wpdh-create-form')
                .not(targetForm)
                .addClass('hidden');

            // Toggle form được chọn
            targetForm.toggleClass('hidden');
        });
    });

    // ===========================================
    // 2️⃣ Checkbox Selection + Bulk Actions
    // ===========================================
    $('.wpdh-dbtable').each(function () {
        const dbtable = $(this);
        const checkboxes = dbtable.find('.wpdh-select-row');
        let lastChecked = null;

        // Shift + click chọn nhiều
        checkboxes.on('click', function (e) {
            if (!lastChecked) {
                lastChecked = this;
                return;
            }
            if (e.shiftKey) {
                let inBetween = false;
                checkboxes.each(function () {
                    if (this === e.target || this === lastChecked) inBetween = !inBetween;
                    if (inBetween || this === e.target || this === lastChecked) this.checked = true;
                });
            }
            lastChecked = this;
        });

        // Select all
        dbtable.find('#wpdh-select-all').on('change', function () {
            checkboxes.prop('checked', this.checked);
        });

        // Bulk form
        const bulkForm = dbtable.find('#wpdh-bulk-form');
        if (bulkForm.length) {
            const hiddenInput = bulkForm.find('#wpdh-selected-ids');
            bulkForm.on('submit', function (e) {
                const ids = checkboxes.filter(':checked').map(function () {
                    return this.value;
                }).get();
                hiddenInput.val(ids.join(','));
                if (!ids.length) {
                    alert('Please select at least one record.');
                    e.preventDefault();
                }
            });
        }
    });

    // ===========================================
    // 3️⃣ Inline Edit (click vào ô để mở form)
    // ===========================================
    $(document).on('click', '.wpdh-table td .wpdh-db-cell-wrap', function (e) {
        const wrap = $(this);

        // Bỏ qua cột checkbox
        if (wrap.find('.wpdh-select-row').length) return;

        // Ẩn tất cả form đang mở khác
        $('.wpdh-db-cell-form').addClass('hidden');
        $('.wpdh-db-cell-value').show();

        const cell = wrap.find('.wpdh-db-cell-form');
        const rowValue = wrap.find('.wpdh-db-cell-value');

        // Hiện form và focus input
        rowValue.hide();
        cell.removeClass('hidden');
        cell.find('.wpdh-db-input').focus();
    });

    // Click ra ngoài -> đóng form
    $(document).on('click', function (e) {
        if (!$(e.target).closest('.wpdh-db-cell, .wpdh-table td .wpdh-db-cell-wrap').length) {
            $('.wpdh-db-cell-form').addClass('hidden');
            $('.wpdh-db-cell-value').show();
        }
    });

    // ===========================================
    // 4️⃣ Save Inline Edit via AJAX
    // ===========================================
    $(document).on('click', '.wpdh-db-save', function (e) {
        e.preventDefault();

        const btn = $(this);
        const container = btn.closest('.wpdh-db-cell-form');
        const input = container.find('.wpdh-db-input');
        const recordId = container.data('record-id');
        const columnName = container.data('column-name');
        const tableName = container.data('table-name');
        const value = input.val();
        const status = container.find('.wpdh-db-status');

        status.text('Saving...').css('color', '#2271b1');

        $.ajax({
            url: Wpdh.ajax_url,
            method: 'POST',
            data: {
                action: 'wpdh_AjaxUpdateRecord',
                record_id: recordId,
                column_name: columnName,
                table_name: tableName,
                value: value,
                nonce: Wpdh.nonce
            },
            success: function (response) {
                if (response.success) {
                    const newVal = response.data.value;
                    status.text(response.data.message || 'Saved!').css('color', 'green');

                    // Update giao diện bằng giá trị thực tế từ DB
                    const td = container.closest('td');
                    td.find('.wpdh-db-cell-value').text(newVal).show();
                    container.addClass('hidden');
                } else {
                    const msg = response.data?.message || 'Error saving';
                    status.text(msg).css('color', 'red');
                    alert(msg);
                    console.error(response.data);
                }
            },
            error: function (xhr, statusText, error) {
                status.text('AJAX error: ' + error).css('color', 'red');
            },
        });
    });

});
