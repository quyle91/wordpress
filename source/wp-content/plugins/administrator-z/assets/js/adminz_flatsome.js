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


// nav_in_pagination
document.addEventListener('DOMContentLoaded', function () {
    // Chỉ chạy toàn bộ logic nếu body có class 'nav_in_pagination'
    if (!document.body.classList.contains('nav_in_pagination')) return;

    function applyCustomFlickityNav() {
        jQuery('.flickity-enabled, .flickity-slider-wrapper').each(function () {
            var $slider = jQuery(this);
            if ($slider.hasClass('custom-dots-nav-applied')) return;

            var $prev = $slider.find('.flickity-prev-next-button.previous');
            var $next = $slider.find('.flickity-prev-next-button.next');
            var $dots = $slider.find('.flickity-page-dots');

            if ($dots.length) {
                $slider.addClass('custom-dots-nav-applied');

                var injectCustomLis = function () {
                    if (!$dots.find('.custom-li-prev').length) {
                        var button = '<svg style="width: 1em; height: 1em; vertical-align: middle;" class="flickity-button-icon" viewBox="0 0 100 100"><path d="M 10,50 L 60,100 L 70,90 L 30,50  L 70,10 L 60,0 Z" class="arrow"></path></svg>';
                        var $newPrev = jQuery('<li class="custom-li-prev inline ml-0 mr-half">' + button + '</li>');
                        $newPrev.on('click', function (e) {
                            e.stopPropagation();
                            $prev.length ? $prev.click() : $slider.flickity('previous');
                        });
                        $dots.prepend($newPrev);
                    }

                    if (!$dots.find('.custom-li-next').length) {
                        var button = '<svg style="width: 1em; height: 1em; vertical-align: middle;" class="flickity-button-icon" viewBox="0 0 100 100"><path d="M 10,50 L 60,100 L 70,90 L 30,50  L 70,10 L 60,0 Z" class="arrow" transform="translate(100, 100) rotate(180) "></path></svg>';
                        var $newNext = jQuery('<li class="custom-li-next inline ml-half mr-0">' + button + '</li>');
                        $newNext.on('click', function (e) {
                            e.stopPropagation();
                            $next.length ? $next.click() : $slider.flickity('next');
                        });
                        $dots.append($newNext);
                    }
                };

                injectCustomLis();

                if (window.MutationObserver) {
                    var observer = new MutationObserver(function () {
                        observer.disconnect();
                        injectCustomLis();
                        observer.observe($dots[0], { childList: true });
                    });
                    observer.observe($dots[0], { childList: true });
                }
            }
        });
    }

    applyCustomFlickityNav();

    jQuery(document).on('flatsome-flickity-ready', function () {
        setTimeout(applyCustomFlickityNav, 100);
        setTimeout(applyCustomFlickityNav, 500);
    });

    setTimeout(applyCustomFlickityNav, 500);
    setTimeout(applyCustomFlickityNav, 2000);
});
