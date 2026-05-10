(function () {
    'use strict';

    class Adminz {
        constructor() {
            window.addEventListener('resize', () => this.onWindowResize());
            document.addEventListener('DOMContentLoaded', () => this.onDOMContentLoaded());
        };

        onDOMContentLoaded = () => {

            //
            document.querySelectorAll('.adminz_countdown').forEach(element => {
                this.countDown_setup(element);
            });

            // 
            document.querySelectorAll('.adminz_lightbox').forEach(element => {
                this.lightbox_setup(element);
            });

            // 
            document.querySelectorAll('.adminz_toggle').forEach(element => {
                this.toggle_setup(element);
            });

            // 
            document.querySelectorAll('.adminz_map').forEach(element => {
                this[element.id] = [];
                this.map_setup(element);
                this.map_run(element, true);
            });

            // readmore
            document.querySelectorAll('.adminz_readmore').forEach(element => {
                this.readmore_setup(element);
            });

            // adminz_woo_search
            document.querySelectorAll('.adminz_woo_search').forEach(element => {
                this.wooSearch_setup(element);
            });

            // adminz_readmore_description
            document.querySelectorAll('.adminz_readmoreContent').forEach(element => {
                this.readMoreContent_setup(element);
            });

            // adminz_min_max_price
            document.querySelectorAll('.adminz_min_max_price').forEach(element => {
                this.minMaxPrice_setup(element);
            });

            // adminz_use_photoswipe
            document.querySelectorAll('.adminz_use_photoswipe').forEach(element => {
                this.adminz_use_photoswipe(element);
            });

            // adminz_nav_icon
            document.querySelectorAll('.adminz_nav_icon').forEach(element => {
                this.adminz_nav_icon(element);
            });

            // adminz_nav_icon
            document.querySelectorAll('.adminz_auto_freescroll').forEach(element => {
                this.adminz_auto_freescroll(element);
            });

            // Flatsome: menu_dropdown_fullwidth_container
            document.querySelectorAll('.menu_dropdown_fullwidth_container .menu-item-design-full-width>ul.nav-dropdown').forEach(nav => {
                nav.classList.add("items-" + nav.childElementCount);
            });

            // wpcf7: adminz_wpcf7_toggle
            document.querySelectorAll('.adminz_enable_adminz_wpcf7_toggle .adminz_wpcf7_toggle').forEach(element => {
                this.adminz_wpcf7_toggle(element);
            });

            // adminz_twentytwenty_container
            document.querySelectorAll('.adminz_twentytwenty_container').forEach(element => {
                this.adminz_twentytwenty_container(element);
            });


        };

        onWindowResize = () => {
            //
        }

        adminz_twentytwenty_container = (element) => {
            // 1. Kiểm tra element
            if (!element) {
                return;
            }

            // 2. Kiểm tra jQuery
            if (typeof window.jQuery === 'undefined') {
                console.warn('adminz_twentytwenty_container: jQuery chưa load');
                return;
            }

            // 3. Kiểm tra plugin twentytwenty
            if (typeof jQuery.fn.twentytwenty !== 'function') {
                console.warn('adminz_twentytwenty_container: twentytwenty chưa load');
                return;
            }

            const $element = jQuery(element);

            // 4. Tránh init nhiều lần
            if ($element.data('adminz-initialized')) {
                return;
            }

            // 5. Lấy data-atts từ HTML
            const rawAtts = element.getAttribute('data-atts');
            if (!rawAtts) {
                // không có config thì init mặc định
                $element.twentytwenty();
                $element.data('adminz-initialized', true);
                return;
            }

            let options = {};

            try {
                options = JSON.parse(rawAtts);
            } catch (error) {
                console.error('adminz_twentytwenty_container: data-atts không phải JSON hợp lệ', error);
                return;
            }

            // 6. Init twentytwenty với options từ PHP
            $element.twentytwenty(options);

            // 7. Đánh dấu đã init
            $element.data('adminz-initialized', true);
        };

        adminz_wpcf7_toggle = (element) => {
            console.log('adminz_wpcf7_toggle');

            const hiddenContainers = element.querySelectorAll('.hidden'); // div.hidden
            const primaryField = element.querySelector('.primary_field'); // field hiển thị chính

            // Bước 1: Lấy tất cả input nằm trong div.hidden
            const extraFields = Array.from(hiddenContainers)
                .map(container => container.querySelector('.wpcf7-form-control'))
                .filter(Boolean); // bỏ qua nếu không có field

            // Bước 2: Gộp các giá trị từ extraFields và gán vào primaryField
            const handleHiddenFields = () => {
                const values = extraFields
                    .map(field => field.value.trim())
                    .filter(value => value !== '');

                const mergedValue = values.join(' - ');

                if (primaryField.tagName === 'INPUT' || primaryField.tagName === 'TEXTAREA') {
                    primaryField.value = mergedValue;
                }
            };

            // Bước 3: Lắng nghe thay đổi từ các field còn lại
            extraFields.forEach(field => {
                field.addEventListener('input', handleHiddenFields);
            });

            // Bước 4: Khi click vào primary_field thì hiện các field ẩn, ẩn chính nó
            primaryField.addEventListener('click', () => {
                hiddenContainers.forEach(container => {
                    container.classList.remove('hidden');
                });

                if (extraFields[0]) {
                    extraFields[0].focus();
                }

                primaryField.classList.add('hidden');
            });
        };

        adminz_auto_freescroll = (element) => {

            // mobile check
            const isMobile = /Mobi|Android/i.test(navigator.userAgent);
            const isWorkOnMobile = jQuery(element).data('flickity-options').adminz_auto_freescroll_mobile;
            if (isWorkOnMobile === 'false' && isMobile) {
                return;
            }

            // status global
            let allowScroll = true;

            // Function để tái sử dụng khởi tạo _direction và _speed cho Flickity
            const initializeFlickityOptions = (flkty) => {
                const data = jQuery(element).data('flickity-options');
                const _direction = data.adminz_auto_freescroll_direction || 'to_right';
                const _speed = parseInt(data.adminz_auto_freescroll_speed || 5);

                // Thiết lập lại hướng và tốc độ cho Flickity
                flkty._speed = _speed;
                flkty._direction = _direction;
            };

            // Hàm tự động cuộn
            const autoScroll = (flkty) => {
                if (!allowScroll) return;

                // Điều chỉnh hướng và tốc độ cuộn
                if (flkty._direction === 'to_right') {
                    flkty.x = (flkty.x + flkty._speed) % -flkty.slideableWidth;
                } else {
                    flkty.x = (flkty.x - flkty._speed) % -flkty.slideableWidth;
                }

                flkty.positionSlider();
            };

            // Khi hover vào slider thì stop auto scroll
            jQuery(element).on('flatsome-flickity-ready', function () {
                const flkty = jQuery(element).data('flickity');
                if (flkty) {
                    initializeFlickityOptions(flkty);
                    // Sử dụng setTimeout để đảm bảo function bắt đầu sau 500ms
                    setTimeout(() => {
                        startAutoScroll(flkty);
                    }, 500);
                }
            }).on('mouseenter', function () {
                allowScroll = false;
            }).on('mouseleave', function () {
                allowScroll = true;
                const flkty = jQuery(element).data('flickity');
                if (flkty) {
                    initializeFlickityOptions(flkty);
                }
            });

            // Hàm bắt đầu auto scroll
            const startAutoScroll = (flkty) => {
                const scrollLoop = () => {
                    if (allowScroll) {
                        autoScroll(flkty);
                    }
                    requestAnimationFrame(scrollLoop);
                };
                scrollLoop();
            };
        };

        adminz_nav_icon = (element) => {
            let adminz_menu_overlay = document.querySelector('.adminz_menu_overlay');

            element.addEventListener('click', function () {
                adminz_menu_overlay.classList.toggle('hidden');
            });
        }

        minMaxPrice_setup = (element) => {
            const {
                step,
                woocommerce_currency,
                woocommerce_currency_pos,
                woocommerce_price_thousand_sep,
                woocommerce_price_decimal_sep,
                woocommerce_price_num_decimals
            } = JSON.parse(element.getAttribute('data-woocommerce'));

            const minSlider = element.querySelector('.minSlider');
            const maxSlider = element.querySelector('.maxSlider');
            const minValue = element.querySelector('.minValue');
            const maxValue = element.querySelector('.maxValue');

            // Lấy giá trị số từ các phần tử .minValue và .maxValue
            let minVal = parseFloat(minValue.querySelector('.woocommerce-Price-amount bdi').textContent.trim().replace(woocommerce_currency, '').replace(/[,]/g, ''));
            let maxVal = parseFloat(maxValue.querySelector('.woocommerce-Price-amount bdi').textContent.trim().replace(woocommerce_currency, '').replace(/[,]/g, ''));

            const updateValues = () => {
                minVal = parseInt(minSlider.value);
                maxVal = parseInt(maxSlider.value);

                // Đảm bảo minVal và maxVal luôn đúng về thứ tự
                if (minVal >= maxVal) {
                    if (minSlider === document.activeElement) {
                        maxVal = minVal + step;
                        if (maxVal > maxSlider.max) {
                            maxVal = parseInt(maxSlider.max);
                            minVal = maxVal - step;
                            minSlider.value = minVal;
                        }
                        maxSlider.value = maxVal;
                    } else if (maxSlider === document.activeElement) {
                        minVal = maxVal - step;
                        if (minVal < minSlider.min) {
                            minVal = parseInt(minSlider.min);
                            maxVal = minVal + step;
                            maxSlider.value = maxVal;
                        }
                        minSlider.value = minVal;
                    }
                }

                // Format lại giá trị và cập nhật vào các phần tử .minValue và .maxValue
                let formattedMinVal = `${woocommerce_currency}${minVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}`;
                let formattedMaxVal = `${woocommerce_currency}${maxVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}`;

                if (woocommerce_currency_pos === 'right') {
                    formattedMinVal = `${minVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}${woocommerce_currency}`;
                    formattedMaxVal = `${maxVal.toFixed(woocommerce_price_num_decimals).replace(/\B(?=(\d{3})+(?!\d))/g, woocommerce_price_thousand_sep).replace('.', woocommerce_price_decimal_sep)}${woocommerce_currency}`;
                }

                minValue.querySelector('.woocommerce-Price-amount bdi').textContent = formattedMinVal;
                maxValue.querySelector('.woocommerce-Price-amount bdi').textContent = formattedMaxVal;
            };

            updateValues();

            minSlider.addEventListener('input', updateValues);
            maxSlider.addEventListener('input', updateValues);
        };

        readMoreContent_setup = (element) => {
            const readMore = document.createElement('div');
            readMore.className = 'readmore_bottom';
            const button = document.createElement('button');
            button.classList.add('button', 'white');
            const icon = document.createElement('i');
            icon.classList.add('icon-angle-down');
            const text = document.createElement('span');
            text.innerHTML = adminz_js.i18n.readmore;
            button.appendChild(icon);
            button.appendChild(text);
            readMore.appendChild(button);
            element.appendChild(readMore);
            button.addEventListener('click', function () {
                var icon = this.querySelector('i');
                icon.classList.toggle('icon-angle-down');
                icon.classList.toggle('icon-angle-up');
                element.classList.toggle('toggled');
            });
        };

        wooSearch_empty_field = (select) => {
            for (let i = select.options.length - 1; i >= 0; i--) {
                if (select.options[i].value !== "") {
                    select.remove(i);
                }
            }
            select.setAttribute('disabled', 'disabled');
        };

        wooSearch_fill_data = (select, data) => {
            data.forEach(item => {
                let option = document.createElement('option');
                option.value = item.term_id;
                option.text = item.name;
                select.add(option);
            });
            select.removeAttribute('disabled');
        };

        wooSearch_setup = (element) => {
            // view more
            const viewmoreButton = element.querySelector('button.view_more');
            if (viewmoreButton) {
                viewmoreButton.onclick = () => {
                    const fields = element.querySelectorAll(".col.view_more");
                    if (fields) {
                        fields.forEach(field => {
                            field.classList.toggle('hidden');
                        });
                    }
                }
            }

            // thx

            const fieldTinh = element.querySelector('[name="tinh"]');
            const fieldHuyen = element.querySelector('[name="huyen"]');
            const fieldXa = element.querySelector('[name="xa"]');

            if (fieldTinh && fieldHuyen && fieldXa) {
                fieldTinh.addEventListener('change', () => {
                    const parent = fieldTinh.value;
                    // empty
                    this.wooSearch_empty_field(fieldHuyen);
                    this.wooSearch_empty_field(fieldXa);

                    // Fetch 
                    (async () => {
                        try {
                            const url = adminz_js.ajax_url;
                            const formData = new FormData();
                            formData.append('action', 'admiz_tinhhuyenxa_get_data');
                            formData.append('nonce', adminz_js.nonce);
                            formData.append('parent', parent);
                            // console.log('Before Fetch:', formData.get('parent'));

                            const response = await fetch(url, {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }

                            const data = await response.json(); // reponse.text()
                            if (data.success) {
                                //Code here
                                this.wooSearch_fill_data(fieldHuyen, data.data);
                            } else {
                            }
                        } catch (error) {
                            console.error('Fetch error:', error);
                        }
                    })();
                });

                fieldHuyen.addEventListener('change', () => {
                    const parent = fieldHuyen.value;
                    // empty
                    this.wooSearch_empty_field(fieldXa);

                    // Fetch 
                    (async () => {
                        try {
                            const url = adminz_js.ajax_url;
                            const formData = new FormData();
                            formData.append('action', 'admiz_tinhhuyenxa_get_data');
                            formData.append('nonce', adminz_js.nonce);
                            formData.append('parent', parent);
                            // console.log('Before Fetch:', formData.get('parent'));

                            const response = await fetch(url, {
                                method: 'POST',
                                body: formData,
                            });

                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }

                            const data = await response.json(); // reponse.text()
                            if (data.success) {
                                //Code here
                                this.wooSearch_fill_data(fieldXa, data.data);
                            } else {
                            }
                        } catch (error) {
                            console.error('Fetch error:', error);
                        }
                    })();
                });
            }
        };

        readmore_setup = (element) => {
            const inner = element.querySelector('.adminz_readmore_inner');
            const button = element.querySelector('.adminz_button');
            button.onclick = () => {
                inner.classList.toggle('unset');
            };
        };

        map_setup = (element) => {
            // init data
            this.map_initData(element);
            this.map_createForm(element);
            this.map_createSelect1(element);
        };

        map_run = (element, auto_focus) => {
            this.map_findItems(element);
            this.map_setList(element);
            this.map_setMap(element, auto_focus);
        };

        map_initData = (element) => {

            // doms
            this[element.id].element = element;
            this[element.id].form = element.querySelector('.form');
            this[element.id].list = element.querySelector('.list');
            this[element.id].map = element.querySelector('.map');
            this[element.id].__config = JSON.parse(element.getAttribute('data-map'));

            // items
            const items = element.querySelectorAll('.item');
            const data_selects = [];
            items.forEach(item => {
                const data = JSON.parse(item.getAttribute('data'));
                data_selects.push(data);
                item.onclick = () => {
                    this.map_handleClickItem(element, item);

                }
            });
            this[element.id].__items = data_selects;
        };

        map_handleClickItem = (element, item) => {
            const data = JSON.parse(item.getAttribute('data'));
            let latLngParts = data.latlong.split(", ");
            let latlng = {
                lat: parseFloat(latLngParts[0]),
                lng: parseFloat(latLngParts[1]),
            };

            document.dispatchEvent(
                new CustomEvent(
                    'adminz_map_focus_item',
                    {
                        detail: {
                            context: this[element.id],
                            latlng: latlng,
                            auto_focus: false,
                        }
                    }
                )
            );
        };

        map_createForm = (element) => {
            this[element.id].form.textContent = "";

            // h4
            const h4 = document.createElement('h4');
            h4.textContent = this[element.id].__config.search_text;
            h4.classList.add('uppercase');
            this[element.id].h4 = h4;
            this[element.id].form.appendChild(h4);

            // select1
            const select1 = document.createElement('select');
            select1.setAttribute('name', 'address_op_1');
            this.map_addDefaultOption(element, select1, 'field1');
            select1.addEventListener('change', () => { this.map_handleChangeSelect1(element); });
            this[element.id].select1 = select1;
            this[element.id].form.appendChild(select1);


            // select 2
            const select2 = document.createElement('select');
            select2.setAttribute('name', 'address_op_2');
            this.map_addDefaultOption(element, select2, 'field2');
            select2.addEventListener('change', () => { this.map_handleChangeSelect2(element); });
            this[element.id].select2 = select2;
            this[element.id].form.appendChild(select2);
        };

        map_createSelect1 = (element) => {
            const __items = this[element.id].__items;
            let appended = [];
            for (let i = 0; i < __items.length; i++) {
                let name = __items[i].address_opt_1;
                if (!appended.includes(name)) {
                    const option = document.createElement('option');
                    option.textContent = name;
                    this[element.id].select1.appendChild(option);
                    appended.push(name);
                }
            }
        };

        map_findItems = (element) => {
            const __items = this[element.id].__items;

            // get values
            const value1 = this[element.id].select1.value;
            const value2 = this[element.id].select2.value;
            let __itemsFound = [];

            let has_value1 = true;
            let has_value2 = true;

            if (!value1) {
                has_value1 = false;
            }

            if (!value2) {
                has_value2 = false;
            }

            for (let i = 0; i < __items.length; i++) {
                let match_value1 = false;
                let match_value2 = false;

                if (has_value1 && __items[i].address_opt_1 === value1) {
                    match_value1 = true;
                }

                if (has_value2 && __items[i].address_opt_2 === value2) {
                    match_value2 = true;
                }

                if (!has_value1) {
                    match_value1 = true;
                }

                if (!has_value2) {
                    match_value2 = true;
                }

                if (match_value1 && match_value2) {
                    __itemsFound.push(__items[i].id);
                }
            }

            this[element.id].__itemsFound = __itemsFound;
            return;
        };

        map_setList = (element) => {
            const items = this[element.id].list.querySelectorAll('.item');
            if (items) {
                items.forEach(item => {
                    item.classList.add('hidden');
                    const data = JSON.parse(item.getAttribute('data'));
                    if (this[element.id].__itemsFound.includes(data.id)) {
                        item.classList.remove('hidden');
                    }
                });
            }
        };

        map_setMap = (element, auto_focus) => {
            document.dispatchEvent(
                new CustomEvent(
                    'adminz_map_initmap',
                    {
                        detail: {
                            context: this[element.id],
                            auto_focus: auto_focus,
                        }
                    }
                )
            );
        };

        map_handleChangeSelect1 = (element) => {
            const select2 = this[element.id].select2;
            select2.textContent = '';
            this.map_addDefaultOption(element, select2, 'field2');
            const value = this[element.id].select1.value;
            // find opt 2
            const __items = this[element.id].__items;
            let appended = [];
            for (let i = 0; i < __items.length; i++) {
                if (__items[i].address_opt_1 === value) {
                    let name = __items[i].address_opt_2;
                    if (!appended.includes(name)) {
                        const option = document.createElement('option');
                        option.textContent = name;
                        this[element.id].select2.appendChild(option);
                        appended.push(name);
                    }
                }
            }
            this.map_run(element, false);
        };

        map_handleChangeSelect2 = (element) => {
            this.map_run(element, false);
        };

        map_addDefaultOption = (element, select, text) => {
            const placeholder = this.map_getConfig(element, 'placeholder_text');
            const option_default = document.createElement('option');
            option_default.setAttribute('value', '');
            const textValue = this.map_getConfig(element, text);
            option_default.setAttribute('value', '');
            option_default.textContent = " — " + placeholder + " " + textValue + " — ";
            select.appendChild(option_default);
        };

        map_getConfig = (element, key) => {
            return this[element.id].__config[key];
        };

        toggle_setup = (element) => {
            element.onclick = () => {
                console.log(element);
                // single structure
                const target = element.getAttribute('data-target');
                const toggleClass = element.getAttribute('data-toggle-class');

                if (target && toggleClass) {
                    document.querySelectorAll(target).forEach(target => {
                        target.classList.toggle(toggleClass);
                    });
                }

                // multiple structure
                if (element.getAttribute('data-target-toggle-class')) {
                    const targetToggleClass = JSON.parse(element.getAttribute('data-target-toggle-class'));
                    if (targetToggleClass.length) {
                        for (let i = 0; i < targetToggleClass.length; i++) {
                            document.querySelectorAll(targetToggleClass[i].target).forEach(_element => {
                                const className = targetToggleClass[i].class;
                                _element.classList.toggle(className);
                            });
                        }
                    }
                }
            }
        };

        lightbox_setup = (element) => {
            const data = JSON.parse(element.getAttribute('data-lightbox'));
            const cookie = (name, value, days) => {
                if (typeof value !== 'undefined') { // Set cookie
                    var expires = '';
                    if (days) {
                        var date = new Date();
                        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000)); // Calculate expiry date
                        expires = "; expires=" + date.toUTCString();
                    }
                    document.cookie = name + "=" + (value || "") + expires + "; path=/"; // Set the cookie
                } else { // Get cookie
                    var nameEQ = name + "=";
                    var ca = document.cookie.split(';');
                    for (var i = 0; i < ca.length; i++) {
                        var c = ca[i];
                        while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                        if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
                    }
                    return null; // Return null if cookie is not found
                }
            }
            if (data.auto_open) {
                var closelightbox_custom = flatsomeVars.lightbox.close_markup;
                if (data.close_bottom_text) {
                    var closelightbox_custom = data.close_bottom_text;
                }
                var cookieId = "lightbox_" + data.id;
                var cookieValue = "opened_" + data.version;
                var timer = parseInt(data.auto_timer);
                var closeBtnInside = true;
                var reopen = data.reopen;
                var reopen_timer = parseInt(1000 * data.reopen_timer);

                if (data.auto_show === 'always') {
                    cookie(cookieId, false);
                }
                if (cookie(cookieId) !== cookieValue) {
                    window.Adminz.lightbox_regeral(data, timer, closeBtnInside, closelightbox_custom);
                    cookie(cookieId, cookieValue, 1);
                    if (reopen == 'true' && reopen_timer) {
                        setInterval(function () {
                            if (!jQuery.magnificPopup.instance.isOpen) {
                                window.Adminz.lightbox_regeral(data, timer, closeBtnInside, closelightbox_custom);
                            }
                        }, reopen_timer);
                    }
                }
            }
        };

        lightbox_regeral = (data, timer, closeBtnInside, closelightbox_custom) => {
            setTimeout(function () {
                if (jQuery.fn.magnificPopup) jQuery.magnificPopup.close()
            }, timer - 350);
            setTimeout(function () {
                jQuery.loadMagnificPopup().then(function () {
                    jQuery.magnificPopup.open({
                        midClick: true,
                        removalDelay: 300,
                        closeBtnInside: closeBtnInside,
                        closeMarkup: closelightbox_custom,
                        fixedContentPos: true,
                        items: {
                            src: '#' + data.id,
                            type: 'inline'
                        },
                        callbacks: {
                            open: function () { },
                        }
                    })
                })
            }, timer);
        };

        countDown_setup = (element) => {
            const timeleft = parseInt(element.getAttribute('data-timeleft'));
            const name = element.getAttribute('data-name');
            let future;

            if (this.countDown_creatCookie(name)) {
                future = this.countDown_creatCookie(name);
            } else {
                future = new Date().getTime() / 1000 + timeleft * 60;
                this.countDown_creatCookie(name, future, 365);
            }
            this.coutnDown_calculateHMSleft(element, future, name);
            setInterval(() => {
                this.coutnDown_calculateHMSleft(element, future, name);
            }, 1000);
        };

        countDown_creatCookie = (name, value, days) => {
            if (value !== undefined) {
                var expires = "";
                if (days) {
                    var date = new Date();
                    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                    expires = "; expires=" + date.toUTCString();
                }
                document.cookie = name + "=" + (value || "") + expires + "; path=/";
            } else {
                var nameEQ = name + "=";
                var ca = document.cookie.split(';');
                for (var i = 0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                }
                return null;
            }
        };

        coutnDown_calculateHMSleft = (element, future, name) => {
            var diff = Math.floor(future - new Date().getTime() / 1000);
            var dayleft = Math.floor((diff / (24 * 60 * 60)));
            var hoursleft = Math.floor((diff % (24 * 60 * 60)) / 60 / 60);
            var minutesleft = Math.floor((diff % (60 * 60)) / 60);
            var secondsleft = Math.floor((diff % (60 * 60)) % 60);

            if (!((dayleft < 0) || (hoursleft < 0) || (minutesleft < 0) || (secondsleft < 0))) {
                if (dayleft < 10) dayleft = "0" + dayleft;
                if (hoursleft < 10) hoursleft = "0" + hoursleft;
                if (minutesleft < 10) minutesleft = "0" + minutesleft;
                if (secondsleft < 10) secondsleft = "0" + secondsleft;

                element.querySelector(".countdown-day").innerHTML = dayleft;
                element.querySelector(".countdown-hour").innerHTML = hoursleft;
                element.querySelector(".countdown-minute").innerHTML = minutesleft;
                element.querySelector(".countdown-second").innerHTML = secondsleft;
            } else {
                this.countDown_creatCookie(name, false);
            }
        };

        ___check_click_element = (element) => {
            element.onclick = function (event) {
                console.log(event.currentTarget);
            }
        };
    }

    window.Adminz = new Adminz();

})();