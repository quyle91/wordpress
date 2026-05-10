document.addEventListener('DOMContentLoaded',function(){
    if (adminz_photoswipe_js == undefined) {
        return;
    }

    const process_list = adminz_photoswipe_js;
    for (let i = 0; i < process_list.length; i++) {
        let gallery = process_list[i].key;
        let children = process_list[i].value;

        if (!gallery || !children) {
            return;
        }

        const domChecker = document.querySelectorAll(gallery + " " + children);
        if (!domChecker.length) {
            return;
        }

        const PhotoSwipeLightbox = window.PhotoSwipeLightbox;
        const lightbox = new PhotoSwipeLightbox({
            gallery: gallery,
            children: children,
            pswpModule: PhotoSwipe
        });

        lightbox.addFilter('domItemData', (itemData, element, linkEl) => {
            if (element) {
                let fullSrc = element.src;
                let width = element.width;
                let height = element.height;

                // kiểm tra srcset
                if (element.getAttribute('adminz_origin_src')) {
                    fullSrc = element.getAttribute('adminz_origin_src');
                }
                if (element.getAttribute('adminz_origin_width')) {
                    width = element.getAttribute('adminz_origin_width');
                }
                if (element.getAttribute('adminz_origin_height')) {
                    height = element.getAttribute('adminz_origin_height');
                }

                itemData.src = fullSrc;
                itemData.msrc = fullSrc;
                itemData.w = width;
                itemData.h = height;
                // itemData.thumbCropped = true;
            }
            return itemData;
        });
        lightbox.init();
    }
});