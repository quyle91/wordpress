jQuery(function ($) {
    // ================= Hàm khởi tạo tab (cho phép gọi lại sau khi append HTML) ================= 
    window.initWpdhTabs = function (context = document) {
        $(context).find('.wpdh-field-type-nav').each(function () {
            const $nav = $(this);

            // stop if has class hidden 
            // An example: nav inside toggle fields 
            if ($nav.hasClass('hidden')) {
                // console.log('Stop init tab because hidden', $nav);
                return;
            }

            // console.log('initWpdhTabs', context);
            const $first = $nav.find('li.active').first().length
                ? $nav.find('li.active').first()
                : $nav.find('li').first();

            if ($first.length) {
                const slug = $first.data('tab');
                const $siblings = $nav.siblings('.wpdh-field-type-start');
                $siblings.each(function () {
                    const $tab = $(this);
                    // remove class hidden nếu đúng slug
                    if ($tab.data('tab') === slug) {
                        // match => show
                        $tab.removeClass('hidden'); // remove hidden
                    } else {
                        // not match => hide
                        $tab.addClass('hidden'); // add hidden
                    }
                });
            }
        });
    };

    window.destroyWpdhTabs = function (context = document) {
        // find all nav wrapper
        const $navs = $(context).find('.wpdh-field-type-nav');
        if ($navs.length === 0) return;

        $navs.each(function () {
            const $nav = $(this);

            // get all siblings start (all of them, include first)
            const $tabs = $nav.siblings('.wpdh-field-type-start');

            // hide all
            $tabs.each(function () {
                const $tab = $(this);
                $tab.addClass('hidden'); // force hidden
            });
        });
    }
    window.initWpdhTabs();

    // ================= Debug control ================= 
    $(document).on('click', '.wpdh-control', function () {
        console.log('Debug control', {
            name: $(this).attr('name'),
            value: $(this).val()
        });
    });

    // ================= Click tab item ================= 
    $(document).on('click', '.wpdh-field-type-nav li[data-tab]', function (e) {
        e.preventDefault();

        const $li = $(this);
        const slug = $li.data('tab');
        const $nav = $li.closest('.wpdh-field-type-nav');
        const $siblings = $nav.siblings('.wpdh-field-type-start');

        $li.addClass('active').siblings().removeClass('active');

        $siblings.each(function () {
            const $tab = $(this);
            $tab.toggleClass('hidden', $tab.data('tab') !== slug);
        });
    });

    // =================  click to copy =================  ================= 
    $(document).on('click', '.wpdh-field-copy-button .button', function () {
        const fieldControl = $(this).closest('.wpdh-field-control');
        const control = fieldControl.find('.wpdh-control');
        let nameControl = control.attr('name');

        // case wp_editor
        const wpdhField = fieldControl.closest('.wpdh-field');
        if (wpdhField.hasClass('wpdh-field-type-wp_editor')) {
            nameControl = wpdhField.find('.wp-editor-area').attr('name');
        }

        // copy text
        navigator.clipboard.writeText(nameControl);

        // alert with name is copied ,
        alert('Copied: ' + nameControl);
    });

    // ================= wp_media =================
    // let wpdh_frame = null;
    let wpdh_frame_single = null;
    let wpdh_frame_multiple = null;

    // Build PHP-style serialized array string
    function wpdh_serializeArray(ids) {
        const count = ids.length;
        let body = '';

        ids.forEach(function (id, index) {
            const str = String(id);
            const len = str.length;
            body += `i:${index};s:${len}:"${str}";`;
        });

        return `a:${count}:{${body}}`;
    }

    function wpdh_unserialize_php(str) {
        if (typeof str !== 'string') return [];

        const result = [];
        // Match pattern s:length:"value"; inside a:N:{...}
        const regex = /s:\d+:"(.*?)";/g;
        let match;
        while ((match = regex.exec(str)) !== null) {
            result.push(match[1]);
        }
        return result;
    }

    function wpdh_initSortable(wrapper) {
        const preview = wrapper.find('.wpdh-media-preview');
        const input = wrapper.find('.output');

        if (!preview.length) {
            return;
        }

        // jQuery UI sortable
        preview.sortable({
            items: '.wpdh-media-item',
            cursor: 'move',
            update: function () {
                // Step 1: lấy lại thứ tự ID mới
                const newIds = [];

                preview.find('.wpdh-media-item img').each(function () {
                    const id = $(this).data('id');

                    if (!id) {
                        return;
                    }

                    newIds.push(id);
                });

                // Step 2: lưu serialized PHP array vào hidden input
                input.val(wpdh_serializeArray(newIds));
            }
        });
    }

    $('.wpdh-media-wrapper[data-type="multiple"]').each(function () {
        wpdh_initSortable($(this));
    });

    // Open WP Media Library
    window.wpdh_openMediaLibrary = function (button, multiple) {

        const wrapper = button.closest('.wpdh-media-wrapper');
        const input = wrapper.find('.output');
        const preview = wrapper.find('.wpdh-media-preview');

        // --------------------------------
        // Step 1: parse saved IDs
        // --------------------------------
        let ids = [];
        const raw = input.val();

        if (multiple) {
            try {
                ids = wpdh_unserialize_php(raw);
            } catch (e) {
                ids = [];
            }

            if (!Array.isArray(ids)) {
                ids = [];
            }
        } else if (raw) {
            ids = [raw];
        }

        // --------------------------------
        // Step 2: get correct frame (single / multiple)
        // --------------------------------
        let frame = null;

        if (multiple) {
            if (!wpdh_frame_multiple) {
                wpdh_frame_multiple = wp.media({
                    multiple: true,
                    library: { type: 'image' }
                });
            }

            frame = wpdh_frame_multiple;
        } else {
            if (!wpdh_frame_single) {
                wpdh_frame_single = wp.media({
                    multiple: false,
                    library: { type: 'image' }
                });
            }

            frame = wpdh_frame_single;
        }

        // --------------------------------
        // Step 3: clear previous callbacks
        // --------------------------------
        frame.off('open select');

        // --------------------------------
        // Step 4: preselect when open
        // --------------------------------
        frame.on('open', function () {
            const selection = frame.state().get('selection');

            if (!selection) {
                return;
            }

            selection.reset();

            ids.forEach(function (id) {
                const attachment = wp.media.attachment(id);

                if (!attachment) {
                    return;
                }

                attachment.fetch();
                selection.add(attachment);
            });
        });

        // --------------------------------
        // Step 5: on select
        // --------------------------------
        frame.on('select', function () {
            const selection = frame.state().get('selection');

            const wpdh_get_thumb_url = function (data) {
                if (!data.sizes) {
                    return data.url;
                }

                if (data.sizes.thumbnail) {
                    return data.sizes.thumbnail.url;
                }

                if (data.sizes.medium) {
                    return data.sizes.medium.url;
                }

                return data.url;
            };

            preview.empty();

            // -------- multiple --------
            if (multiple) {
                const newIds = [];

                selection.each(function (attachment) {
                    const data = attachment.toJSON();
                    newIds.push(data.id);

                    const thumb = wpdh_get_thumb_url(data);

                    preview.append(
                        `<div class="wpdh-media-item">
                        <img class="wpdh-media-thumb" src="${thumb}" data-id="${data.id}">
                    </div>`
                    );
                });

                input.val(wpdh_serializeArray(newIds));

                if (preview.data('ui-sortable')) {
                    preview.sortable('refresh');
                }

                return;
            }

            // -------- single --------
            const att = selection.first().toJSON();
            input.val(att.id);

            const thumb = wpdh_get_thumb_url(att);

            preview.append(
                `<div class="wpdh-media-item">
                <img class="wpdh-media-thumb" src="${thumb}" data-id="${att.id}">
            </div>`
            );
        });

        // --------------------------------
        // Step 6: open modal
        // --------------------------------
        frame.open();
    };


    // Click select button
    $(document).on('click', '.wpdh-btn-media', function () {
        const button = $(this);
        const wrapper = button.closest('.wpdh-media-wrapper');
        const isMultiple = wrapper.data('type') === 'multiple';
        window.wpdh_openMediaLibrary(button, isMultiple);
    });

    // Remove selected media
    $(document).on('click', '.wpdh-btn-remove-media', function () {
        const button = $(this);
        const wrapper = button.closest('.wpdh-media-wrapper');
        const isMultiple = wrapper.data('type') === 'multiple';
        const preview = wrapper.find('.wpdh-media-preview');
        preview.empty();
        const input = wrapper.find('input');
        input.val(isMultiple ? 'a:0:{}' : '');
    });

    // ================= Select2 ================= 
    window.wpdh_select2 = function (selector) {
        // check selector exists
        if (!$(selector).length) {
            return; // stop early
        }

        // loop each select
        $(selector).each(function () {
            const select = $(this);

            // skip if already initialized
            if (select.hasClass('select2-initialized')) {
                return; // stop early
            }

            // mark initialized
            select.addClass('select2-initialized');

            // init Select2
            select.select2({
                width: '100%',
                allowClear: true,
                // placeholder: select.attr('placeholder') || '',
                placeholder: select.attr('placeholder') || null,
            });
        });
    }

    window.wpdh_select2('.wpdh-field-kind-select.wpdh-field-type-select2>.wpdh-field-control>select');

});
