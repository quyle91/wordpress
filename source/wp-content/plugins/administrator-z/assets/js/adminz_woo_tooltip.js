document.querySelectorAll('.adminz_tooltip_box').forEach(element => {
    if (
        !/Android|webOS|iPhone|iPad|Mac|Macintosh|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
    ) {
        let productBox = document.querySelectorAll('.product-small');
        let tooltipBox = element;

        productBox.forEach(function (box) {
            box.addEventListener('mousemove', function (event) {
                let needLeft = event.pageX + 20;
                let needTop = event.pageY - 20;

                if ((needLeft + tooltipBox.offsetWidth + 20) > document.documentElement.clientWidth) {
                    needLeft = needLeft - tooltipBox.offsetWidth - 60;
                }
                adminzTooltipContent(tooltipBox, box, needLeft, needTop);
            });
            box.addEventListener('mouseleave', function () {
                tooltipBox.style.display = 'none';
            });
        });

        function adminzTooltipContent(tooltipBox, hoverBox, needLeft, needTop) {
            let dataBox = hoverBox.querySelector('.tooltip_data');
            let currentProductId = tooltipBox.getAttribute('data-product_id');
            if (dataBox && currentProductId !== dataBox.getAttribute('data-product_id')) {
                tooltipBox.setAttribute('data-product_id', dataBox.getAttribute('data-product_id'));
                tooltipBox.innerHTML = dataBox.innerHTML;
                adminzChangeSrc(tooltipBox);
            }
            if (tooltipBox.querySelector('*:not(:empty)')) {
                tooltipBox.style.display = 'inline';
                tooltipBox.style.left = `${needLeft}px`;
                tooltipBox.style.top = `${needTop}px`;
            } else {
                tooltipBox.style.display = 'none';
            }
        }

        function adminzChangeSrc(dom) {
            let images = dom.querySelectorAll('img');
            images.forEach(function (img) {
                if (img.classList.contains('lazy-load')) {
                    let domSrc = img.getAttribute('src');
                    let domDataSrc = img.getAttribute('data-src');
                    img.setAttribute('src', domDataSrc);
                    img.setAttribute('data-src', domSrc);
                    img.classList.remove('lazy-load');
                }
            });
        }
    }
});