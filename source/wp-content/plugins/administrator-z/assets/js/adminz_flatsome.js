// equal flickity item height, 
jQuery(document).ready(function ($) {
    $('.adminz_slider_custom.equal-height .slider').on('ready.flickity change.flickity', function () {
        $(this).find('.adminz_slider_item').addClass('adminz_ready_equal_height');
    });
});




// active anchor links
document.addEventListener('DOMContentLoaded', function () {
    if (document.querySelector('body.adminz_menu_item_active_anchor')) {
        const menuItems = document.querySelectorAll('.header-nav li.menu-item, .nav-sidebar li.menu-item');

        function activateLinkByHash(hash) {
            menuItems.forEach(function (item) {
                const link = item.querySelector('a');
                if (!link) return;

                const href = link.getAttribute('href');
                let linkHash = '';

                try {
                    const url = new URL(href, window.location.origin);
                    linkHash = url.hash;
                } catch (e) {
                    linkHash = href;
                }

                if (linkHash === hash) {
                    item.classList.add('zactive');
                } else {
                    item.classList.remove('zactive');
                }
            });
        }

        // Gán sự kiện click cho từng link
        menuItems.forEach(function (item) {
            const link = item.querySelector('a');
            if (!link) return;

            link.addEventListener('click', function () {
                const href = link.getAttribute('href');
                let linkHash = '';

                try {
                    const url = new URL(href, window.location.origin);
                    linkHash = url.hash;
                } catch (e) {
                    linkHash = href;
                }

                if (linkHash && linkHash.startsWith('#')) {
                    activateLinkByHash(linkHash);
                }
            });
        });

        // Kích hoạt ban đầu nếu có hash trong URL
        if (window.location.hash) {
            activateLinkByHash(window.location.hash);
        }
    }
});
