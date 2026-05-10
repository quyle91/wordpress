(function ($) {
    'use strict';

    const reindexRepeater = ($repeater) => {
        const base = $repeater.data('base');
        if (!base) {
            console.log($repeater);
            alert('base not found');
            return;
        }

        const escapeForRegex = (str) => {
            return str.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');
        }

        const baseEsc = escapeForRegex(base);

        // chỉ lấy item cấp 1 trong .wpdh-repeater-items
        $repeater.find('> .wpdh-repeater-items > .wpdh-repeater-item').each(function (index) {
            const $repeaterItem = $(this);

            //
            $repeaterItem.attr('data-index', index);

            // cập nhật name
            $repeaterItem.find('[name]').each(function () {
                const $el = $(this);
                let name = $el.attr('name');

                const regex = new RegExp('^' + baseEsc + '\\[\\d+\\]');
                if (regex.test(name)) {
                    const remainder = name.replace(regex, '');
                    const newName = base + '[' + index + ']' + remainder;
                    $el.attr('name', newName);

                    // debug if name included ]] then console.log
                    if (newName.includes(']]')) {
                        alert('error double ]]');
                        console.log('remainder', remainder);
                        console.log('base', base);
                        console.log('index', index);
                        return;
                    }
                }
            });

            // 🎯 cập nhật base cho repeater con (PATCH CHÍNH)
            $repeaterItem.children('.wpdh-repeater').each(function () {
                const $subRepeater = $(this);
                const oldBase = $subRepeater.attr('data-base') || $subRepeater.data('base') || '';

                // debug
                if (!oldBase) {
                    alert('base not found');
                    return;
                }
                const parentEsc = baseEsc + '\\[\\d+\\]';
                const newBase = oldBase.replace(
                    new RegExp('^' + parentEsc),
                    base + '[' + index + ']'
                );

                $subRepeater.data('base', newBase);
                $subRepeater.attr('data-base', newBase); // bug behavior jquery

            });

            // đệ quy cho repeater con
            $repeaterItem.find('> .wpdh-repeater').each(function () {
                reindexRepeater($(this));
            });
        });

        // debug cấp hiện tại
        $repeater.find('> .wpdh-debug').trigger('click');
    }

    // re-init wp_editor inside cloned element
    const wpdhReinitWpEditor = ($clone) => {
        // find textarea
        const $textarea = $clone.find('.wp-editor-area');
        if ($textarea.length === 0) {
            console.warn('No wp-editor-area found.');
            return;
        }

        // old ID for reference only
        const oldId = $textarea.attr('id');

        // create unique ID
        const newId = 'wpdh_editor_' + Date.now() + '_' + Math.floor(Math.random() * 999999);
        $textarea.attr('id', newId);

        // clone base WP config from "content"
        const baseMce = tinyMCEPreInit.mceInit.content;
        const baseQt = tinyMCEPreInit.qtInit.content;

        // create clean config (do NOT deep clone full object)
        const mceInit = {};
        const qtInit = {};

        // safe keys for TinyMCE WP config
        const safeMceKeys = [
            'wpautop', 'plugins', 'toolbar1', 'toolbar2', 'toolbar3',
            'resize', 'theme', 'skin', 'language', 'branding',
            'content_css', 'relative_urls', 'remove_script_host',
            'convert_urls', 'browser_spellcheck', 'contextmenu',
            'menubar', 'statusbar', 'height', 'formats',
            'block_formats', 'style_formats', 'image_advtab',
            'advlist_bullet_styles', 'advlist_number_styles'
        ];

        safeMceKeys.forEach((key) => {
            if (baseMce[key] !== undefined) {
                mceInit[key] = baseMce[key];
            }
        });

        // setup selector for new ID
        mceInit.selector = '#' + newId;
        mceInit.id = newId;

        // clone Quicktags safely
        qtInit.id = newId;
        qtInit.buttons = baseQt.buttons;

        // clear cloned UI: TinyMCE containers & Quicktags
        const $wrap = $clone.find('.wp-editor-wrap');
        $wrap.removeClass('html-active').removeClass('tmce-active');

        $wrap.find('.mce-container').remove(); // remove cloned editor UI
        $wrap.find('.quicktags-toolbar').remove(); // remove cloned toolbar

        // init Quicktags
        try {
            quicktags(qtInit);
            QTags._buttonsInit();
        } catch (e) {
            console.error('Quicktags init error:', e);
        }

        // init TinyMCE
        setTimeout(() => {
            try {
                tinymce.init(mceInit);
            } catch (e) {
                console.error('TinyMCE init error:', e);
            }
        }, 50);

        // switch to Visual mode
        setTimeout(() => {
            try {
                switchEditors.go(newId, 'tmce');
            } catch (e) {
                console.error('switchEditors error:', e);
            }
        }, 150);

        // debug
        console.log('WP Editor reinitialized:', {
            oldId: oldId,
            newId: newId,
            mceInit: mceInit,
            qtInit: qtInit
        });
    };


    // clone item
    $(document).on('click', '.wpdh-clone', function (e) {
        e.preventDefault();
        const $repeaterItem = $(this).closest('.wpdh-repeater-item');
        const $repeater = $repeaterItem.closest('.wpdh-repeater');

        // clone
        const $clone = $repeaterItem.clone();

        // giữ nguyên giá trị
        $clone.find('input,select,textarea').each(function () {
            const $el = $(this);
            $el.val($el.val());
        });

        // re-init wp_editor for cloned item
        // let wpdhFieldWpEditor = $clone.find('.wpdh-field-type-wp_editor'); 
        // let wpdhFieldWpFieldControl = wpdhFieldWpEditor.find('.wpdh-field-control');
        // let wpEditorArea = wpdhFieldWpFieldControl.find('.wp-editor-area');
        wpdhReinitWpEditor($clone);

        // insert
        $clone.insertAfter($repeaterItem);

        // reindex
        // const $repeaterItems = $repeater.children('.wpdh-repeater-items');
        reindexRepeater($repeater);
    });

    // remove item
    $(document).on('click', '.wpdh-remove', function (e) {
        e.preventDefault();

        // confirm before remove
        if (!confirm('Remove this item?')) {
            return;
        }

        const $repeaterItem = $(this).closest('.wpdh-repeater-item');
        const $repeater = $repeaterItem.closest('.wpdh-repeater');
        $repeaterItem.remove();
        reindexRepeater($repeater);
    });

    // up item
    $(document).on('click', '.wpdh-up', function (e) {
        e.preventDefault();
        const $repeaterItem = $(this).closest('.wpdh-repeater-item');
        const $repeater = $repeaterItem.closest('.wpdh-repeater');
        const $prev = $repeaterItem.prev('.wpdh-repeater-item');

        if ($prev.length) {
            $repeaterItem.insertBefore($prev);
            reindexRepeater($repeater);
        }
    });

    // down item
    $(document).on('click', '.wpdh-down', function (e) {
        e.preventDefault();
        const $repeaterItem = $(this).closest('.wpdh-repeater-item');
        const $repeater = $repeaterItem.closest('.wpdh-repeater');
        const $next = $repeaterItem.next('.wpdh-repeater-item');

        // move down
        if ($next.length) {
            $repeaterItem.insertAfter($next);
            reindexRepeater($repeater);
        }
    });


    // test button
    $(document).on('click', '.wpdh-debug', function (e) {
        e.preventDefault();

        const $repeater = $(this).closest('.wpdh-repeater');

        // Thu thập tất cả các field có name
        const fields = [];
        $repeater.find('input[name], textarea[name], select[name]').each(function () {
            const $el = $(this);
            fields.push({
                name: $el.attr('name') || '',
                value: $el.val()
            });
        });

        console.groupCollapsed('🧩 Repeater debug:', $repeater.data('name'));
        fields.forEach(f => console.log(f.name, '=', f.value));
        console.groupEnd();

        // -------------------- Simple checksum (synchronous) --------------------
        const jsonString = JSON.stringify(fields);
        let hash = 0;
        for (let i = 0; i < jsonString.length; i++) {
            hash = ((hash << 5) - hash) + jsonString.charCodeAt(i);
            hash |= 0; // convert to 32-bit integer
        }

        console.log('Checksum:', hash);
    });

    // 4️⃣ Click toggle button
    $(document).on('click', '.wpdh-extend-view', function () {
        const fieldParent = $(this).closest('.wpdh-repeater-item'); // get parent container
        const siblingFields = fieldParent.children('.wpdh-repeater-item-wrap').children('.wpdh-field, .wpdh-repeater').not('.wpdh-field-visible-show');
        console.log('fieldParent', fieldParent);
        console.log('wpdh-field-toggle-button', siblingFields);

        // 
        fieldParent.toggleClass('toggled');

        //
        siblingFields.each(function (index, el) {
            const $el = $(el);
            $el.toggleClass('hidden');
        });

        // Kích hoạt init tab sau khi toggle
        if (fieldParent.hasClass('toggled')) {
            window.initWpdhTabs(fieldParent)
        } else {
            window.destroyWpdhTabs(fieldParent);
        }
    });

})(jQuery);
