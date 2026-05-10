jQuery(function ($) {
    $(document).on('change', '.clickToAppend', function (e) {
        const currentTarget = e.currentTarget;
        const dataAppendConfig = $(currentTarget).data('append-config');
        const Field = $(currentTarget).closest('.wpdh-field');
        const repeaterItem = Field.closest('.wpdh-repeater-item');
        const actionDom = repeaterItem.children('.wpdh-repeater-item-actions');


        // find closest with attribute data-base and closest with attribute data-index
        const elementBase = Field.closest('[data-base]');
        const closestNamePrefix = elementBase.data('base');
        const closestIndex = Field.closest('[data-index]').data('index');
        const namePrefix = closestNamePrefix + '[' + closestIndex + ']';

        //
        if (!Field.length) {
            alert('Click to append field must be inside field');
            return;
        }
        if (!repeaterItem.length) {
            alert('Click to append field must be inside repeater item');
            return;
        }
        if (!actionDom.length) {
            alert('Click to append field must be inside repeater item with actionDom');
            return;
        }
        if (!namePrefix) {
            alert('Click to append field must be inside repeater item with namePrefix');
            return;
        }

        // check current visibility
        const firstHiddenField = repeaterItem.children('.isHiddenField').first();
        const isHidden = firstHiddenField.hasClass('hidden');
        const showOrHide = isHidden ? 'hidden' : 'show';

        // reset all added fields
        repeaterItem.children('.wpdh-append-added').remove();

        // Gọi ajax để lấy repeater HTML
        jQuery.ajax({
            type: 'post',
            dataType: 'json',
            url: Wpdh.ajax_url,
            data: {
                action: 'HandleAppendRepeater',
                nonce: Wpdh.nonce,
                namePrefix: namePrefix,
                dataAppendConfig: dataAppendConfig,
                currentValue: currentTarget.value,
                showOrHide: showOrHide, // overide visibility
            },
            context: this,
            beforeSend: function () {
                // Có thể thêm hiệu ứng loading tại đây
            },
            success: function (response) {
                if (response.success) {
                    actionDom.before(response.data);
                } else {
                    console.warn('Reponse data', response.data);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.error('The following error occurred: ' + textStatus, errorThrown);
            },
            complete: function () {
                // Làm gì đó sau khi hoàn tất (tùy chọn)
            }
        });
    });
});
