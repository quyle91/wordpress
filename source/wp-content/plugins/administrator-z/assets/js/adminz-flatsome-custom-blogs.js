(() => {
    'use strict';

    // Class cho component Adminz_Flatsome_Custom_Blogs
    class Adminz_Flatsome_Custom_Blogs {
        static defaults = {
            debounceDelay: 100,
            // another custom setting ...
        };

        constructor (options = {}) {
            this.settings = {
                ...this.constructor.defaults, ...options
            };
            this.eventListeners = new Map();
            this.bindEvents();
        }

        // Khởi tạo tất cả các phần tử, có thể nhận các selector và logic riêng biệt
        initAll = () => {

            // Dọn dẹp trước khi init
            this.cleanupAllEventListeners(); 

            // example call
            this.init_custom_blogs('.adminz_custom_blogs');
        };

        // example function
        init_custom_blogs = (selector) => {
            document.querySelectorAll(selector).forEach(element => {
                if (element.classList.contains('paged_style_ajax')) {
                    this.set_ajax(element);
                }
                if (element.classList.contains('paged_style_loadmore')) {
                    this.set_ajax(element, false); // also is ajax but don't override
                    this.set_loadmore(element);
                }
                if (element.classList.contains('paged_style_infinity')) {
                    this.set_ajax(element, false); // also is ajax but don't override
                    this.set_loadmore(element); // also is loadmore
                    this.set_infinity(element);
                }
            });
        };

        handlePaginationClick = (e, element, link, override) => {
            const inner_pagination = element.querySelector('.inner_pagination');

            // Xử lý nút next
            if (link.classList.contains('next')) {
                const current = inner_pagination.querySelector('.current');
                const currentLi = current.closest('li');
                const nextLi = currentLi.nextElementSibling;
                if (nextLi) {
                    const nextLink = nextLi.querySelector('a');
                    nextLink.click();
                }
                return;
            }

            // Xử lý nút prev
            if (link.classList.contains('prev')) {
                const current = inner_pagination.querySelector('.current');
                const currentLi = current.closest('li');
                const prevLi = currentLi.previousElementSibling;
                if (prevLi) {
                    const prevLink = prevLi.querySelector('a');
                    prevLink.click();
                }
                return;
            }

            // Xử lý chuyển đến trang cụ thể
            const listItem = link.closest("li");
            const listItemPaged = listItem.getAttribute('data-paged');
            const ul = link.closest('ul');
            let atts = JSON.parse(ul.getAttribute('data-atts'));
            atts.page_number = listItemPaged;
            const base_url = ul.getAttribute('data-base-url');

            this.before_ajax(element);

            // Gọi AJAX
            (async () => {
                try {
                    const url = adminz_js.ajax_url;
                    const formData = new FormData();
                    formData.append('action', 'adminz_custom_blogs');
                    formData.append('data', JSON.stringify(atts));
                    formData.append('base_url', base_url);
                    formData.append('nonce', adminz_js.nonce);

                    const _fetch = await fetch(url, {
                        method: 'POST',
                        body: formData,
                    });

                    if (!_fetch.ok) throw new Error('Network response was not ok');

                    const response = await _fetch.json();
                    if (response.success) {
                        const htmlString = response.data;
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(htmlString, 'text/html');
                        const adminz_custom_blogs = doc.querySelector('.adminz_custom_blogs');

                        // Fix override nếu là slider
                        if (element.classList.contains('type_slider')) {
                            override = true;
                        }

                        if (override) {
                            element.innerHTML = adminz_custom_blogs.innerHTML;
                            if (element.classList.contains('paged_style_ajax')) {
                                const url = link.getAttribute('href');
                                history.pushState(null, '', url);
                            }
                        } else {
                            // Chèn nội dung mới vào
                            const sourceRow = adminz_custom_blogs.querySelector('.inner_wrap .row');
                            const targetRow = element.querySelector('.inner_wrap .row');

                            if (sourceRow && targetRow) {
                                const newCols = sourceRow.querySelectorAll('.col');
                                newCols.forEach(col => {
                                    targetRow.appendChild(col.cloneNode(true));
                                });
                            } else {
                                element.insertAdjacentHTML('beforeend', adminz_custom_blogs.innerHTML);
                            }

                            // Cập nhật phân trang và nút loadmore
                            this.replaceChildElement(element, adminz_custom_blogs, '.inner_pagination');
                            this.replaceChildElement(element, adminz_custom_blogs, '.inner_loadmore');
                        }

                        this.after_ajax(element);
                    } else {
                        console.error('Error:', response.message);
                    }
                } catch (error) {
                    console.error('Fetch error:', error);
                    element.classList.remove('processing');
                }
            })();
        };

        /**
         * Thay thế phần tử con bằng nội dung mới
         * @param {HTMLElement} parentElement - Phần tử cha chứa phần tử cần thay thế
         * @param {HTMLElement|DocumentFragment} newContent - Nội dung mới chứa phần tử thay thế
         * @param {string} selector - CSS selector của phần tử cần thay thế
         */
        replaceChildElement = (parentElement, newContent, selector) => {
            // Tìm phần tử nguồn (mới) và phần tử đích (cũ)
            const sourceElement = newContent.querySelector(selector);
            const targetElement = parentElement.querySelector(selector);

            // Chỉ thực hiện thay thế nếu tìm thấy cả 2 phần tử
            if (sourceElement && targetElement) {
                try {
                    // Clone node để tránh ảnh hưởng đến DOM gốc
                    const clonedElement = sourceElement.cloneNode(true);

                    // Thay thế phần tử cũ bằng phần tử mới
                    targetElement.replaceWith(clonedElement);

                    // Trả về phần tử mới đã được thay thế
                    return clonedElement;
                } catch (error) {
                    console.error('Error replacing element:', error);
                    return null;
                }
            } else {
                if (!sourceElement) console.warn(`Source element not found with selector: ${selector}`);
                if (!targetElement) console.warn(`Target element not found with selector: ${selector}`);
                return null;
            }
        };

        set_ajax = (element, override = true) => {
            const inner_pagination = element.querySelector('.inner_pagination');

            // Clean up existing event listeners first
            this.cleanupElementListeners(inner_pagination);

            inner_pagination.querySelectorAll('a').forEach(link => {
                // Tạo handler với các tham số cần thiết
                const handler = (e) => {
                    e.preventDefault();
                    this.handlePaginationClick(e, element, link, override);
                };

                // Thêm event listener mới
                link.addEventListener('click', handler);

                // Lưu lại reference để có thể clean up sau này
                this.addEventListener(link, 'click', handler);
            });
        };

        set_loadmore = (element) => {

            const buttonLoadmore = element.querySelector('.inner_loadmore .button');
            buttonLoadmore.addEventListener('click', (e) => {
                // Ngăn click liên tiếp
                if (buttonLoadmore.dataset.loading === '1') return;
                buttonLoadmore.dataset.loading = '1';

                const current = element.querySelector('.inner_pagination .current');
                if (current) {
                    const currentLi = current.closest('li');
                    const nextLi = currentLi.nextElementSibling;
                    if (nextLi) {
                        const nextLink = nextLi.querySelector('a');
                        nextLink.click();
                    }
                }

                // Sau khi xử lý xong, cho phép click lại (nếu cần delay để tránh spam)
                setTimeout(() => {
                    buttonLoadmore.dataset.loading = '0';
                }, 1000); // đổi thời gian nếu cần
            });

            // disable button loadmore
            this.disableLoadmore(element);
        };

        set_infinity = (element) => {
            if(element.classList.contains('type_slider')){
                console.log('skip infinity for slider'); 
                return;
            }

            let reachedElements = [];

            const isElementActuallyVisible = (element) => {
                if (!element || !(element instanceof HTMLElement)) return false;

                const style = window.getComputedStyle(element);
                if (
                    style.display === 'none' ||
                    style.visibility === 'hidden' ||
                    parseFloat(style.opacity) <= 0 ||
                    element.offsetWidth === 0 ||
                    element.offsetHeight === 0
                ) {
                    return false;
                }

                // Nếu là document.body thì dừng đệ quy
                if (element === document.body) {
                    return true;
                }

                // Đệ quy kiểm tra cha của element
                return isElementActuallyVisible(element.parentElement);
            };

            const checkVisibleAndScroll = (element) => {
                if (isElementActuallyVisible(element)) {
                    const scrollTop = window.scrollY || document.documentElement.scrollTop;
                    const windowHeight = window.innerHeight;
                    const elOffsetTop = element.getBoundingClientRect().top + scrollTop;
                    const elHeight = element.offsetHeight;

                    if (scrollTop + windowHeight >= elOffsetTop + elHeight) {
                        if (!reachedElements.includes(element)) {
                            reachedElements.push(element);
                            const loadmoreBtn = element.querySelector('.inner_loadmore .button');
                            if (loadmoreBtn) {
                                loadmoreBtn.click();
                            }
                        }
                    } else {
                        const index = reachedElements.indexOf(element);
                        if (index !== -1) reachedElements.splice(index, 1);
                    }
                } else {
                    const index = reachedElements.indexOf(element);
                    if (index !== -1) reachedElements.splice(index, 1);
                }
            }

            // check first
            checkVisibleAndScroll(element);

            // check when scroll
            let scrollTimeout;
            window.addEventListener('scroll', () => {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(checkVisibleAndScroll(element), 100);
            });

            // tab
            const panel = element.closest('.panel');
            const tabPanels = element.closest('.tab-panels');
            const panelIndex = Array.from(tabPanels.children).indexOf(panel);
            const tabNavs = tabPanels.previousElementSibling;
            tabNavs.querySelectorAll('a').forEach(link => {
                const linkIndex = Array.from(tabNavs.children).indexOf(link.closest('li'));
                if(linkIndex === panelIndex){
                    link.addEventListener('click', () => {
                        checkVisibleAndScroll(element);
                    });
                }
            });
        };

        before_ajax = (element) => {
            element.classList.add('processing');
        };

        after_ajax = (element) => {
            // Ghi nhớ scroll position
            const scrollTop = jQuery(window).scrollTop();

            // Xử lý slider
            jQuery(element).find('.row.row-slider').each(function () {
                const options = jQuery(this).data('flickity-options');
                jQuery(this).flickity(JSON.parse(options));
            });

            // Xử lý masonry/grid
            jQuery(element).find('.row.row-masonry, .row.row-grid').each(function () {
                if (jQuery(this).data('packery')) {
                    jQuery(this).packery('destroy');
                }
            });

            // Khởi tạo lại Flatsome components
            Flatsome.attach("commons", jQuery(element));

            // Xử lý loadmore button
            this.disableLoadmore(element);

            // Cleanup và re-init events CHO RIÊNG ELEMENT HIỆN TẠI
            this.cleanupElementListeners(element);

            // Re-init chỉ cho element vừa được cập nhật
            if (element.classList.contains('paged_style_ajax')) {
                this.set_ajax(element);
            }
            if (element.classList.contains('paged_style_loadmore')) {
                this.set_ajax(element, false);
                this.set_loadmore(element);
            }
            if (element.classList.contains('paged_style_infinity')) {
                this.set_ajax(element, false);
                this.set_loadmore(element);
                this.set_infinity(element);
            }

            element.classList.remove('processing');
            jQuery(window).scrollTop(scrollTop);
        };

        disableLoadmore = (element) => {
            const nextButton = element.querySelector('.inner_pagination .next');
            if (!nextButton) {
                const buttonLoadmore = element.querySelector('.inner_loadmore .button');
                if (!buttonLoadmore.hasAttribute('disabled')) {
                    console.log('ButtonLoadmore disabled');
                    buttonLoadmore.setAttribute('disabled', 'disabled');
                }
            }
        };

        // Hàm thêm event listener có quản lý
        addEventListener = (element, type, handler) => {
            element.addEventListener(type, handler);

            if (!this.eventListeners.has(element)) {
                this.eventListeners.set(element, []);
            }
            this.eventListeners.get(element).push({ type, handler });
        };

        // Dọn dẹp tất cả event listeners
        cleanupAllEventListeners = () => {
            this.eventListeners.forEach((listeners, element) => {
                listeners.forEach(({ type, handler }) => {
                    element.removeEventListener(type, handler);
                });
            });
            this.eventListeners.clear();
        };

        // Dọn dẹp event listeners của một element cụ thể
        cleanupElementListeners = (element) => {
            if (this.eventListeners.has(element)) {
                const listeners = this.eventListeners.get(element);
                listeners.forEach(({ type, handler }) => {
                    element.removeEventListener(type, handler);
                });
                this.eventListeners.delete(element);
            }
        };

        // Đăng ký các sự kiện global
        bindEvents = () => {
            // Sử dụng debounce cho resize
            const resizeHandler = this.debounce(this.onWindowResize, this.settings.debounceDelay);
            window.addEventListener('resize', resizeHandler);
            this.addEventListener(window, 'resize', resizeHandler);

            // Xử lý khi DOM ready
            const domReadyHandler = () => this.initAll();
            if (document.readyState === 'loading') {
                this.addEventListener(document, 'DOMContentLoaded', domReadyHandler);
            } else {
                domReadyHandler();
            }
        };

        // Hàm xử lý khi cửa sổ được resize
        onWindowResize = () => {
            console.log('Window resized, re-initializing...');
            this.initAll();
        };

        // Hàm debounce (dùng để xử lý sự kiện resize tránh gọi quá nhiều lần)
        debounce = (func, wait) => {
            let timeout;
            return (...args) => {
                clearTimeout(timeout);
                timeout = setTimeout(() => func.apply(this, args), wait);
            };
        };

        // Hủy toàn bộ khi cần (nếu component bị unmount)
        destroy = () => {
            this.cleanupAllEventListeners();
        };
    }
    // Khởi tạo Adminz_Flatsome_Custom_Blogs component
    window.Adminz_Flatsome_Custom_Blogs = new Adminz_Flatsome_Custom_Blogs();
})();