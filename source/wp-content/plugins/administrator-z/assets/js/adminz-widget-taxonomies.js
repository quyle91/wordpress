(function ($) {
    // Hàm khởi tạo chức năng
    function initAdminzTaxonomies() {

        // Form submit
        $('.adminz_widget_taxonomies form').off('change.adminz').on('change.adminz', 'select', function (e) {
            e.preventDefault();
            var form = $(this).closest('form')[0];
            var isFlatsomePjax = $("body").hasClass("ux-shop-ajax-filters");
            
            if(isFlatsomePjax){
                var formData = new FormData(form);
                var queryString = new URLSearchParams(formData).toString();
                var url = $(form).attr('action') + "?" + queryString;
                $(form).find('ul').remove();
                var ul = document.createElement('ul');
                ul.style.display = 'xnone';
                var a = document.createElement('a');
                a.href = url;
                ul.appendChild(a);
                form.appendChild(ul);
                a.click();
                return;
            }

            form.submit();
            return;
        });

        // Count
        $('.adminz_widget_taxonomies .adminz_count_transient').each(function () {
            var $dom = $(this);
            var woo_tax_query = $dom.attr('data-woocommerce-tax_query');
            var woo_meta_query = $dom.attr('data-woocommerce-meta_query');
            var woo_date_query = $dom.attr('data-woocommerce-date_query');
            var term_ids = $dom.attr('data-adminz-term_ids');
            var $widget = $dom.closest('.widget');

            var children = [];

            if (this.tagName === 'FORM') {
                $dom.find('option').each(function () {
                    var classValue = $(this).attr('class');
                    var match = classValue.match(/cat-item-(\d+)/);
                    if (match) {
                        children[match[1]] = $(this);
                    }
                });
            }
            else if (this.tagName === 'UL') {
                $dom.find('li').each(function () {
                    var classValue = $(this).attr('class');
                    var match = classValue.match(/cat-item-(\d+)/);
                    if (match) {
                        children[match[1]] = $(this);
                    }
                });
            }

            $.ajax({
                url: adminz_js.ajax_url,
                type: 'POST',
                data: {
                    action: 'adminz_widget_taxonomies_get_count',
                    term_ids: term_ids,
                    woo_tax_query: woo_tax_query,
                    woo_meta_query: woo_meta_query,
                    woo_date_query: woo_date_query,
                    nonce: adminz_js.nonce
                },
                success: function (response) {
                    if (response.success) {
                        $.each(response.data, function (key, value) {
                            if (children[key]) {
                                var $item = children[key];
                                $item.toggleClass('adminz_empty_value', value === "0")
                                    .toggleClass('adminz_has_value', value !== "0");

                                if ($item.is('li')) {
                                    $item.find('a span').remove();
                                    $item.find('a').append("<span>(" + value + ")</span>");
                                }
                                else if ($item.is('option')) {
                                    $item.text($item.text().replace(/\s*\(\d+\)$/, '') + " (" + value + ")");
                                }
                            }
                        });

                        $widget.toggleClass('adminz_has_value_widget', response.data.widget_count > 0)
                            .toggleClass('adminz_empty_widget', response.data.widget_count <= 0);
                    }
                }
            });
        });
    }

    // Chạy lần đầu khi DOM ready
    $(document).ready(initAdminzTaxonomies);

    // Bind lại khi Flatsome PJAX hoàn thành
    $(document.body).on('experimental-flatsome-pjax-request-done', function (e, html, link) {
        initAdminzTaxonomies();
    });

})(jQuery);