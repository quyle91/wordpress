// @see: wp-content\themes\flatsome\inc\extensions\flatsome-live-search\flatsome-live-search.js
jQuery(document).ready(function ($) {
    "use strict";
    $('.adminz-searchform').each(function () {

        var adminz_post_type = $(this).data('adminz-post_type');
        var append = $(this).find('.live-search-results');
        var search_categories = $(this).find('.search_categories');
        var serviceUrl = flatsomeVars.ajaxurl + '?action=adminz_flatsome_ajax_search_products'; // change to adminz function
        serviceUrl += '&post_type=' + adminz_post_type; // thêm post_type vào param
        var product_cat = '';

        if (search_categories.length && search_categories.val() !== '') {
            serviceUrl += '&product_cat=' + search_categories.val();
        }
        
        $(this).find('.search-field').devbridgeAutocomplete({
            minChars: 3,
            appendTo: append,
            triggerSelectOnValidInput: false,
            serviceUrl: serviceUrl,
            deferRequestBy: parseInt(flatsomeVars.options.search_result_latency),
            onSearchStart: function () {
                $('.submit-button').removeClass('loading');
                $('.submit-button').addClass('loading');
            },
            onSelect: function (suggestion) {
                if (suggestion.id != -1) {
                    window.location.href = suggestion.url;
                }
            },
            onSearchComplete: function () {
                $('.submit-button').removeClass('loading');
            },
            beforeRender: function (container) {
                $(container).removeAttr('style');
            },
            formatResult: function (suggestion, currentValue) {
                var pattern = '(' + $.Autocomplete.utils.escapeRegExChars(currentValue) + ')';
                var html = '';
                if (suggestion.img) html += '<img class="search-image" src="' + suggestion.img + '">';
                html += '<div class="search-name">' + suggestion.value.replace(new RegExp(pattern, 'gi'), '<strong>$1<\/strong>') + '</div>';
                if (suggestion.price) html += '<span class="search-price">' + suggestion.price + '<span>';

                return html;
            }
        });

        if (search_categories.length) {
            var searchForm = $(this).find('.search-field').devbridgeAutocomplete();

            search_categories.on('change', function (e) {

                if (search_categories.val() != '') {
                    searchForm.setOptions({
                        serviceUrl: flatsomeVars.ajaxurl + '?action=flatsome_ajax_search_products&product_cat=' + search_categories.val()
                    });
                } else {
                    searchForm.setOptions({
                        serviceUrl: flatsomeVars.ajaxurl + '?action=flatsome_ajax_search_products'
                    });
                }

                // update suggestions
                searchForm.hide();
                searchForm.onValueChange();
            });
        }
    });


});